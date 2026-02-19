<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoutingType;
use App\Models\DnsRoutingRule;
use App\Models\Project;
use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DnsRoutingService - DNS routing configuration management
 *
 * This service manages DNS routing rules for projects across regions,
 * including latency-based, geolocation, weighted, and failover routing.
 *
 * @package App\Services
 */
class DnsRoutingService
{
    /**
     * Get all routing rules for a project.
     *
     * @param Project $project The project to get rules for
     * @return Collection<int, DnsRoutingRule>
     */
    public function getRoutingRules(Project $project): Collection
    {
        return DnsRoutingRule::where('project_id', $project->id)
            ->with('region')
            ->orderBy('priority')
            ->get();
    }

    /**
     * Create a new routing rule for a project.
     *
     * @param Project $project The project to create the rule for
     * @param array<string, mixed> $data The rule data
     * @return DnsRoutingRule The created rule
     */
    public function createRoutingRule(Project $project, array $data): DnsRoutingRule
    {
        $data['project_id'] = $project->id;

        $rule = DnsRoutingRule::create($data);

        Log::info('DNS routing rule created', [
            'rule_id' => $rule->id,
            'project_id' => $project->id,
            'region_id' => $rule->region_id,
            'routing_type' => $rule->routing_type->value,
        ]);

        return $rule;
    }

    /**
     * Update an existing routing rule.
     *
     * @param DnsRoutingRule $rule The rule to update
     * @param array<string, mixed> $data The updated data
     * @return DnsRoutingRule The updated rule
     */
    public function updateRoutingRule(DnsRoutingRule $rule, array $data): DnsRoutingRule
    {
        $rule->update($data);

        Log::info('DNS routing rule updated', [
            'rule_id' => $rule->id,
            'project_id' => $rule->project_id,
        ]);

        return $rule;
    }

    /**
     * Delete a routing rule.
     *
     * @param DnsRoutingRule $rule The rule to delete
     * @return bool True if deletion succeeded
     */
    public function deleteRoutingRule(DnsRoutingRule $rule): bool
    {
        $ruleId = $rule->id;
        $projectId = $rule->project_id;

        $rule->delete();

        Log::info('DNS routing rule deleted', [
            'rule_id' => $ruleId,
            'project_id' => $projectId,
        ]);

        return true;
    }

    /**
     * Generate nginx geo routing configuration for a project.
     *
     * Produces an nginx-compatible configuration block for
     * geo-based traffic distribution across regions.
     *
     * @param Project $project The project to generate config for
     * @return string The nginx geo configuration
     */
    public function generateNginxGeoConfig(Project $project): string
    {
        $rules = $this->getRoutingRules($project);

        if ($rules->isEmpty()) {
            return "# No routing rules configured for project: {$project->name}\n";
        }

        $config = "# Auto-generated geo routing config for: {$project->name}\n";
        $config .= "# Generated at: " . now()->toIso8601String() . "\n\n";

        $activeRules = $rules->filter(fn (DnsRoutingRule $rule): bool => $rule->is_active);

        if ($activeRules->isEmpty()) {
            return $config . "# No active routing rules found.\n";
        }

        $firstRule = $activeRules->first();

        if ($firstRule === null) {
            return $config . "# No active routing rules found.\n";
        }

        $routingType = $firstRule->routing_type;

        $config .= match ($routingType) {
            RoutingType::Weighted => $this->generateWeightedConfig($activeRules, $project),
            RoutingType::Geolocation => $this->generateGeoConfig($activeRules, $project),
            RoutingType::Failover => $this->generateFailoverConfig($activeRules, $project),
            RoutingType::Latency => $this->generateLatencyConfig($activeRules, $project),
        };

        return $config;
    }

    /**
     * Calculate the nearest region to a given coordinate using the Haversine formula.
     *
     * @param float $latitude The latitude to search from
     * @param float $longitude The longitude to search from
     * @return Region|null The nearest region, or null if no regions exist
     */
    public function calculateNearestRegion(float $latitude, float $longitude): ?Region
    {
        $regions = Region::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($regions->isEmpty()) {
            return null;
        }

        $nearestRegion = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($regions as $region) {
            $regionLat = (float) $region->latitude;
            $regionLng = (float) $region->longitude;

            $distance = $this->haversineDistance($latitude, $longitude, $regionLat, $regionLng);

            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestRegion = $region;
            }
        }

        return $nearestRegion;
    }

    /**
     * Validate that routing weights sum to 100 for weighted routing rules of a project.
     *
     * @param Project $project The project to validate
     * @return bool True if weights sum to 100 (or no weighted rules exist)
     */
    public function validateRoutingWeights(Project $project): bool
    {
        $weightedRules = DnsRoutingRule::where('project_id', $project->id)
            ->where('routing_type', RoutingType::Weighted)
            ->where('is_active', true)
            ->get();

        if ($weightedRules->isEmpty()) {
            return true;
        }

        $totalWeight = $weightedRules->sum('weight');

        return $totalWeight === 100;
    }

    /**
     * Calculate distance between two coordinates using the Haversine formula.
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in kilometers
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0; // kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Generate weighted upstream config for nginx.
     *
     * @param Collection<int, DnsRoutingRule> $rules Active weighted rules
     * @param Project $project The project
     * @return string Nginx upstream configuration block
     */
    private function generateWeightedConfig(Collection $rules, Project $project): string
    {
        $config = "upstream {$project->slug}_backend {\n";

        foreach ($rules as $rule) {
            $region = $rule->region;

            if ($region === null) {
                continue;
            }

            $weight = $rule->weight > 0 ? $rule->weight : 1;
            $config .= "    server {$region->dns_zone} weight={$weight}; # {$region->name}\n";
        }

        $config .= "}\n";

        return $config;
    }

    /**
     * Generate geolocation-based config for nginx.
     *
     * @param Collection<int, DnsRoutingRule> $rules Active geo rules
     * @param Project $project The project
     * @return string Nginx geo configuration block
     */
    private function generateGeoConfig(Collection $rules, Project $project): string
    {
        $config = "geo \$geo_{$project->slug} {\n";
        $config .= "    default default_backend;\n";

        foreach ($rules as $rule) {
            $region = $rule->region;

            if ($region === null) {
                continue;
            }

            $geoRestrictions = $rule->geo_restrictions ?? [];
            foreach ($geoRestrictions as $cidr) {
                if (is_string($cidr)) {
                    $config .= "    {$cidr} {$region->code}_backend;\n";
                }
            }
        }

        $config .= "}\n";

        return $config;
    }

    /**
     * Generate failover config for nginx.
     *
     * @param Collection<int, DnsRoutingRule> $rules Active failover rules
     * @param Project $project The project
     * @return string Nginx upstream configuration block with failover
     */
    private function generateFailoverConfig(Collection $rules, Project $project): string
    {
        $config = "upstream {$project->slug}_backend {\n";

        $sortedRules = $rules->sortBy('priority');

        $isFirst = true;
        foreach ($sortedRules as $rule) {
            $region = $rule->region;

            if ($region === null) {
                continue;
            }

            if ($isFirst) {
                $config .= "    server {$region->dns_zone}; # Primary: {$region->name}\n";
                $isFirst = false;
            } else {
                $config .= "    server {$region->dns_zone} backup; # Failover: {$region->name}\n";
            }
        }

        $config .= "}\n";

        return $config;
    }

    /**
     * Generate latency-based config for nginx (using least_conn).
     *
     * @param Collection<int, DnsRoutingRule> $rules Active latency rules
     * @param Project $project The project
     * @return string Nginx upstream configuration block with least_conn
     */
    private function generateLatencyConfig(Collection $rules, Project $project): string
    {
        $config = "upstream {$project->slug}_backend {\n";
        $config .= "    least_conn;\n";

        foreach ($rules as $rule) {
            $region = $rule->region;

            if ($region === null) {
                continue;
            }

            $config .= "    server {$region->dns_zone}; # {$region->name}\n";
        }

        $config .= "}\n";

        return $config;
    }
}
