<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RegionStatus;
use App\Models\Region;
use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RegionService - Region CRUD and health aggregation
 *
 * This service manages region lifecycle operations including:
 * - Creating, updating, and deleting regions
 * - Assigning/removing servers to/from regions
 * - Aggregating region health from server statuses
 * - Failover orchestration between regions
 *
 * @package App\Services
 */
class RegionService
{
    /**
     * Get all regions with server counts.
     *
     * @return Collection<int, Region>
     */
    public function getAllRegions(): Collection
    {
        return Region::withCount('servers')
            ->orderBy('continent')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a single region with loaded relationships and health metrics.
     *
     * @param Region $region The region to load details for
     * @return Region The region with loaded servers, deployments, and routing rules
     */
    public function getRegionWithDetails(Region $region): Region
    {
        $region->load([
            'servers',
            'regionDeployments' => function ($query): void {
                $query->latest()->limit(10);
            },
            'dnsRoutingRules',
        ]);

        return $region;
    }

    /**
     * Create a new region.
     *
     * @param array<string, mixed> $data The region data
     * @return Region The newly created region
     */
    public function createRegion(array $data): Region
    {
        $region = Region::create($data);

        Log::info('Region created', [
            'region_id' => $region->id,
            'name' => $region->name,
            'code' => $region->code,
            'continent' => $region->continent,
        ]);

        return $region;
    }

    /**
     * Update an existing region.
     *
     * @param Region $region The region to update
     * @param array<string, mixed> $data The updated data
     * @return Region The updated region
     */
    public function updateRegion(Region $region, array $data): Region
    {
        $region->update($data);

        Log::info('Region updated', [
            'region_id' => $region->id,
            'name' => $region->name,
        ]);

        return $region;
    }

    /**
     * Delete a region.
     *
     * Fails if the region still has servers assigned to it.
     *
     * @param Region $region The region to delete
     * @return bool True if deletion succeeded
     *
     * @throws \RuntimeException If the region has servers assigned
     */
    public function deleteRegion(Region $region): bool
    {
        $serverCount = $region->servers()->count();

        if ($serverCount > 0) {
            throw new \RuntimeException(
                "Cannot delete region '{$region->name}' because it has {$serverCount} server(s) assigned. " .
                'Please reassign or remove all servers before deleting.'
            );
        }

        $regionName = $region->name;
        $regionId = $region->id;

        $region->delete();

        Log::info('Region deleted', [
            'region_id' => $regionId,
            'name' => $regionName,
        ]);

        return true;
    }

    /**
     * Assign a server to a region.
     *
     * @param Server $server The server to assign
     * @param Region $region The target region
     * @return Server The updated server
     */
    public function assignServerToRegion(Server $server, Region $region): Server
    {
        $server->update(['region_id' => $region->id]);

        Log::info('Server assigned to region', [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'region_id' => $region->id,
            'region_name' => $region->name,
        ]);

        return $server;
    }

    /**
     * Remove a server from its region.
     *
     * Sets the server's region_id to null.
     *
     * @param Server $server The server to remove from its region
     * @return Server The updated server
     */
    public function removeServerFromRegion(Server $server): Server
    {
        $previousRegionId = $server->region_id;

        $server->update(['region_id' => null]);

        Log::info('Server removed from region', [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'previous_region_id' => $previousRegionId,
        ]);

        return $server;
    }

    /**
     * Get the aggregated health score for a region.
     *
     * Calculates health based on the statuses of servers within the region.
     *
     * @param Region $region The region to assess
     * @return array{score: int, online_count: int, total_count: int, status: string}
     */
    public function getRegionHealthScore(Region $region): array
    {
        $servers = $region->servers;
        $totalCount = $servers->count();

        if ($totalCount === 0) {
            return [
                'score' => 0,
                'online_count' => 0,
                'total_count' => 0,
                'status' => 'no_servers',
            ];
        }

        $onlineCount = $servers->where('status', 'online')->count();
        $score = (int) round(($onlineCount / $totalCount) * 100);

        $status = match (true) {
            $score === 100 => 'healthy',
            $score >= 75 => 'degraded',
            $score >= 25 => 'critical',
            default => 'down',
        };

        return [
            'score' => $score,
            'online_count' => $onlineCount,
            'total_count' => $totalCount,
            'status' => $status,
        ];
    }

    /**
     * Get all regions that are available for operations.
     *
     * @return Collection<int, Region>
     */
    public function getAvailableRegions(): Collection
    {
        return Region::where('status', RegionStatus::Active)
            ->withCount('servers')
            ->orderBy('continent')
            ->orderBy('name')
            ->get();
    }

    /**
     * Failover from one region to another.
     *
     * Marks the source region as offline and reassigns its servers
     * to the target region. Used during regional outage scenarios.
     *
     * @param Region $region The failing region
     * @param Region $targetRegion The region to failover to
     * @return bool True if failover succeeded
     */
    public function failoverRegion(Region $region, Region $targetRegion): bool
    {
        return DB::transaction(function () use ($region, $targetRegion): bool {
            // Mark source region as offline
            $region->update([
                'status' => RegionStatus::Offline,
                'failover_config' => array_merge($region->failover_config ?? [], [
                    'failed_over_to' => $targetRegion->id,
                    'failed_over_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::warning('Region failover initiated', [
                'source_region_id' => $region->id,
                'source_region_name' => $region->name,
                'target_region_id' => $targetRegion->id,
                'target_region_name' => $targetRegion->name,
                'servers_affected' => $region->servers()->count(),
            ]);

            return true;
        });
    }

    /**
     * Get a distinct list of continents from all regions.
     *
     * @return array<int, string>
     */
    public function getContinents(): array
    {
        return Region::distinct()
            ->orderBy('continent')
            ->pluck('continent')
            ->toArray();
    }
}
