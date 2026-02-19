<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanaryMetric;
use App\Models\CanaryRelease;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CanaryMetricsCollectorService
{
    /**
     * Collect metrics for both stable and canary versions.
     */
    public function collectMetrics(CanaryRelease $canaryRelease): void
    {
        $project = $canaryRelease->project;
        if ($project === null) {
            return;
        }

        // Collect stable metrics
        $stableMetrics = $this->collectVersionMetrics($project, 'stable');
        if ($stableMetrics !== null) {
            CanaryMetric::create(array_merge($stableMetrics, [
                'canary_release_id' => $canaryRelease->id,
                'version_type' => 'stable',
                'recorded_at' => now(),
            ]));
        }

        // Collect canary metrics
        $canaryMetrics = $this->collectVersionMetrics($project, 'canary');
        if ($canaryMetrics !== null) {
            CanaryMetric::create(array_merge($canaryMetrics, [
                'canary_release_id' => $canaryRelease->id,
                'version_type' => 'canary',
                'recorded_at' => now(),
            ]));
        }
    }

    /**
     * Get the latest metrics for both versions.
     *
     * @return array{stable: CanaryMetric|null, canary: CanaryMetric|null}|null
     */
    public function getLatestMetrics(CanaryRelease $canaryRelease): ?array
    {
        $stable = CanaryMetric::where('canary_release_id', $canaryRelease->id)
            ->where('version_type', 'stable')
            ->latest('recorded_at')
            ->first();

        $canary = CanaryMetric::where('canary_release_id', $canaryRelease->id)
            ->where('version_type', 'canary')
            ->latest('recorded_at')
            ->first();

        if ($stable === null && $canary === null) {
            return null;
        }

        return [
            'stable' => $stable,
            'canary' => $canary,
        ];
    }

    /**
     * Get metrics comparison for display.
     *
     * @return array{stable: array{avg_error_rate: float, avg_response_time: int, total_requests: int, total_errors: int, p95_response_time: int, p99_response_time: int}, canary: array{avg_error_rate: float, avg_response_time: int, total_requests: int, total_errors: int, p95_response_time: int, p99_response_time: int}}
     */
    public function getMetricsComparison(CanaryRelease $canaryRelease, int $lastMinutes = 10): array
    {
        $since = now()->subMinutes($lastMinutes);

        $stableMetrics = CanaryMetric::where('canary_release_id', $canaryRelease->id)
            ->where('version_type', 'stable')
            ->where('recorded_at', '>=', $since)
            ->get();

        $canaryMetrics = CanaryMetric::where('canary_release_id', $canaryRelease->id)
            ->where('version_type', 'canary')
            ->where('recorded_at', '>=', $since)
            ->get();

        return [
            'stable' => [
                'avg_error_rate' => round((float) $stableMetrics->avg('error_rate'), 4),
                'avg_response_time' => (int) round((float) $stableMetrics->avg('avg_response_time_ms')),
                'total_requests' => (int) $stableMetrics->sum('request_count'),
                'total_errors' => (int) $stableMetrics->sum('error_count'),
                'p95_response_time' => (int) round((float) $stableMetrics->avg('p95_response_time_ms')),
                'p99_response_time' => (int) round((float) $stableMetrics->avg('p99_response_time_ms')),
            ],
            'canary' => [
                'avg_error_rate' => round((float) $canaryMetrics->avg('error_rate'), 4),
                'avg_response_time' => (int) round((float) $canaryMetrics->avg('avg_response_time_ms')),
                'total_requests' => (int) $canaryMetrics->sum('request_count'),
                'total_errors' => (int) $canaryMetrics->sum('error_count'),
                'p95_response_time' => (int) round((float) $canaryMetrics->avg('p95_response_time_ms')),
                'p99_response_time' => (int) round((float) $canaryMetrics->avg('p99_response_time_ms')),
            ],
        ];
    }

    /**
     * Collect metrics for a specific version by parsing nginx access logs.
     *
     * @return array{error_rate: float, avg_response_time_ms: int, p95_response_time_ms: int, p99_response_time_ms: int, request_count: int, error_count: int}|null
     */
    private function collectVersionMetrics(Project $project, string $versionType): ?array
    {
        $server = $project->server;
        if ($server === null) {
            return null;
        }

        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        $port = match ($versionType) {
            'canary' => ($project->port ?? 8080) + 1,
            default => $project->port ?? 8080,
        };

        // Parse nginx access logs for the last 30 seconds for this upstream port
        $logCmd = "tail -1000 /var/log/nginx/access.log 2>/dev/null | grep ':{$port}' | tail -100";

        try {
            $result = Process::timeout(15)->run("{$sshPrefix} \"{$logCmd}\"");
            $lines = array_filter(explode("\n", trim($result->output())));
            $totalRequests = count($lines);

            if ($totalRequests === 0) {
                return [
                    'error_rate' => 0.0,
                    'avg_response_time_ms' => 0,
                    'p95_response_time_ms' => 0,
                    'p99_response_time_ms' => 0,
                    'request_count' => 0,
                    'error_count' => 0,
                ];
            }

            // Count errors (5xx status codes)
            $errorCount = 0;
            /** @var array<int, int> $responseTimes */
            $responseTimes = [];

            foreach ($lines as $line) {
                // Look for HTTP status codes 5xx
                if (preg_match('/\s(5\d{2})\s/', $line) === 1) {
                    $errorCount++;
                }
                // Extract response time if available
                if (preg_match('/(\d+\.\d+)\s*$/', $line, $timeMatches) === 1) {
                    $responseTimes[] = (int) (((float) $timeMatches[1]) * 1000);
                }
            }

            sort($responseTimes);
            $count = count($responseTimes);

            return [
                'error_rate' => $totalRequests > 0 ? round(($errorCount / $totalRequests) * 100, 4) : 0.0,
                'avg_response_time_ms' => $count > 0 ? (int) round(array_sum($responseTimes) / $count) : 0,
                'p95_response_time_ms' => $count > 0 ? ($responseTimes[(int) floor($count * 0.95)] ?? 0) : 0,
                'p99_response_time_ms' => $count > 0 ? ($responseTimes[(int) floor($count * 0.99)] ?? 0) : 0,
                'request_count' => $totalRequests,
                'error_count' => $errorCount,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to collect canary metrics', [
                'project_id' => $project->id,
                'version' => $versionType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
