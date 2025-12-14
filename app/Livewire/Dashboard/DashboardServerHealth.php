<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Traits\CacheableStats;
use App\Mappers\HealthScoreMapper;
use App\Models\Server;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Server Health Component
 *
 * Displays server health metrics including CPU, memory, and disk usage.
 * Shows health status indicators for each online server.
 *
 * @property array<int, array<string, mixed>> $serverHealth Server health status and metrics
 */
class DashboardServerHealth extends Component
{
    use CacheableStats;

    /** @var array<int, array<string, mixed>> */
    public array $serverHealth = [];

    public function mount(): void
    {
        // Initial data loaded via wire:init
    }

    /**
     * Load server health metrics
     */
    public function loadServerHealth(): void
    {
        $defaultServerEntry = [
            'server_id' => 0,
            'server_name' => 'Unknown',
            'cpu_usage' => null,
            'memory_usage' => null,
            'disk_usage' => null,
            'load_average' => null,
            'status' => 'unknown',
            'recorded_at' => null,
            'health_status' => 'unknown',
        ];

        $cachedHealth = $this->cacheOrFallback('dashboard_server_health', 60, function () {
            $servers = Server::with(['latestMetric'])
                ->where('status', 'online')
                ->get();

            return $servers->map(function ($server) {
                $latestMetric = $server->latestMetric;

                if (! $latestMetric) {
                    return [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'cpu_usage' => null,
                        'memory_usage' => null,
                        'disk_usage' => null,
                        'load_average' => null,
                        'status' => $server->status,
                        'recorded_at' => null,
                        'health_status' => 'unknown',
                    ];
                }

                return [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'cpu_usage' => (float) $latestMetric->cpu_usage,
                    'memory_usage' => (float) $latestMetric->memory_usage,
                    'memory_used_mb' => $latestMetric->memory_used_mb,
                    'memory_total_mb' => $latestMetric->memory_total_mb,
                    'disk_usage' => (float) $latestMetric->disk_usage,
                    'disk_used_gb' => $latestMetric->disk_used_gb,
                    'disk_total_gb' => $latestMetric->disk_total_gb,
                    'load_average_1' => (float) $latestMetric->load_average_1,
                    'load_average_5' => (float) $latestMetric->load_average_5,
                    'load_average_15' => (float) $latestMetric->load_average_15,
                    'network_in_bytes' => $latestMetric->network_in_bytes,
                    'network_out_bytes' => $latestMetric->network_out_bytes,
                    'status' => $server->status,
                    'recorded_at' => $latestMetric->recorded_at,
                    'health_status' => $this->getServerHealthStatus(
                        (float) $latestMetric->cpu_usage,
                        (float) $latestMetric->memory_usage,
                        (float) $latestMetric->disk_usage
                    ),
                ];
            })->all();
        });

        $this->serverHealth = is_array($cachedHealth) ? array_map(
            fn ($server) => is_array($server) ? array_merge($defaultServerEntry, $server) : $defaultServerEntry,
            $cachedHealth
        ) : [];
    }

    /**
     * Determine server health status based on resource usage
     */
    private function getServerHealthStatus(float $cpu, float $memory, float $disk): string
    {
        // Calculate a simple health score based on resource usage
        $score = 100;

        // Deduct based on CPU usage
        if ($cpu > 90) {
            $score -= 40;
        } elseif ($cpu > 75) {
            $score -= 20;
        }

        // Deduct based on memory usage
        if ($memory > 90) {
            $score -= 40;
        } elseif ($memory > 75) {
            $score -= 20;
        }

        // Deduct based on disk usage
        if ($disk > 90) {
            $score -= 40;
        } elseif ($disk > 75) {
            $score -= 20;
        }

        // Use the mapper to convert score to status
        return HealthScoreMapper::scoreToStatus(max(0, $score));
    }

    /**
     * Clear server health cache
     */
    public function clearServerHealthCache(): void
    {
        $this->forgetCacheKeys(['dashboard_server_health']);
    }

    #[On('refresh-server-health')]
    #[On('server-metrics-updated')]
    public function refreshServerHealth(): void
    {
        $this->clearServerHealthCache();
        $this->loadServerHealth();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.dashboard-server-health');
    }
}
