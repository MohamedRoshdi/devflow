<?php

namespace App\Livewire\Settings;

use App\Services\QueueMonitorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class SystemStatus extends Component
{
    public array $services = [];
    public array $reverbStatus = [];
    public array $queueStats = [];
    public array $cacheStats = [];
    public array $databaseStats = [];
    public bool $isLoading = true;

    private QueueMonitorService $queueMonitor;

    public function boot(QueueMonitorService $queueMonitor): void
    {
        $this->queueMonitor = $queueMonitor;
    }

    public function mount(): void
    {
        $this->loadAllStats();
        $this->isLoading = false;
    }

    public function loadAllStats(): void
    {
        $this->loadReverbStatus();
        $this->loadQueueStats();
        $this->loadCacheStats();
        $this->loadDatabaseStats();
        $this->loadServiceStatus();
    }

    public function loadReverbStatus(): void
    {
        try {
            $reverbHost = config('reverb.servers.reverb.host', '127.0.0.1');
            $reverbPort = config('reverb.servers.reverb.port', 8080);

            // Check if Reverb is running by testing the connection
            $socket = @fsockopen($reverbHost, (int) $reverbPort, $errno, $errstr, 2);

            $this->reverbStatus = [
                'running' => $socket !== false,
                'host' => $reverbHost,
                'port' => $reverbPort,
                'app_id' => config('reverb.apps.0.app_id', 'N/A'),
                'error' => $socket === false ? "$errstr ($errno)" : null,
            ];

            if ($socket) {
                fclose($socket);
            }
        } catch (\Exception $e) {
            $this->reverbStatus = [
                'running' => false,
                'host' => config('reverb.servers.reverb.host', '127.0.0.1'),
                'port' => config('reverb.servers.reverb.port', 8080),
                'error' => $e->getMessage(),
            ];
        }
    }

    public function loadQueueStats(): void
    {
        try {
            $this->queueStats = $this->queueMonitor->getQueueStatistics();
        } catch (\Exception $e) {
            $this->queueStats = [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'worker_status' => ['is_running' => false, 'status' => 'error'],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function loadCacheStats(): void
    {
        try {
            $driver = config('cache.default');
            $this->cacheStats = [
                'driver' => $driver,
                'working' => $this->testCacheConnection(),
                'prefix' => config('cache.prefix'),
            ];

            if ($driver === 'redis') {
                $this->cacheStats['redis_info'] = $this->getRedisInfo();
            }
        } catch (\Exception $e) {
            $this->cacheStats = [
                'driver' => config('cache.default'),
                'working' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function testCacheConnection(): bool
    {
        try {
            $key = 'system_status_test_' . time();
            Cache::put($key, 'test', 10);
            $result = Cache::get($key) === 'test';
            Cache::forget($key);
            return $result;
        } catch (\Exception) {
            return false;
        }
    }

    private function getRedisInfo(): array
    {
        try {
            $redis = Cache::getStore()->getRedis();
            $info = $redis->info();
            return [
                'version' => $info['redis_version'] ?? 'N/A',
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'uptime_days' => round(($info['uptime_in_seconds'] ?? 0) / 86400, 1),
            ];
        } catch (\Exception) {
            return [];
        }
    }

    public function loadDatabaseStats(): void
    {
        try {
            $pdo = DB::connection()->getPdo();
            $this->databaseStats = [
                'driver' => config('database.default'),
                'connected' => true,
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ];
        } catch (\Exception $e) {
            $this->databaseStats = [
                'driver' => config('database.default'),
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function loadServiceStatus(): void
    {
        $this->services = [
            [
                'name' => 'Laravel Application',
                'status' => 'running',
                'details' => 'Laravel ' . app()->version(),
            ],
            [
                'name' => 'WebSocket Server (Reverb)',
                'status' => ($this->reverbStatus['running'] ?? false) ? 'running' : 'stopped',
                'details' => ($this->reverbStatus['running'] ?? false)
                    ? "Port {$this->reverbStatus['port']}"
                    : ($this->reverbStatus['error'] ?? 'Not running'),
            ],
            [
                'name' => 'Queue Workers',
                'status' => ($this->queueStats['worker_status']['is_running'] ?? false) ? 'running' : 'stopped',
                'details' => ($this->queueStats['worker_status']['worker_count'] ?? 0) . ' workers',
            ],
            [
                'name' => 'Cache (' . ($this->cacheStats['driver'] ?? 'unknown') . ')',
                'status' => ($this->cacheStats['working'] ?? false) ? 'running' : 'error',
                'details' => ($this->cacheStats['working'] ?? false) ? 'Connected' : 'Connection failed',
            ],
            [
                'name' => 'Database (' . ($this->databaseStats['driver'] ?? 'unknown') . ')',
                'status' => ($this->databaseStats['connected'] ?? false) ? 'running' : 'error',
                'details' => ($this->databaseStats['connected'] ?? false)
                    ? ($this->databaseStats['version'] ?? 'Connected')
                    : 'Connection failed',
            ],
        ];
    }

    public function refreshStats(): void
    {
        $this->isLoading = true;
        $this->loadAllStats();
        $this->isLoading = false;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'System status refreshed',
        ]);
    }

    public function testBroadcast(): void
    {
        try {
            event(new \App\Events\DashboardUpdated('test', [
                'message' => 'Test broadcast from System Status',
                'timestamp' => now()->toIso8601String(),
            ]));

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Test broadcast sent! Check browser console for WebSocket message.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Broadcast failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.settings.system-status');
    }
}
