<?php

declare(strict_types=1);

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class CacheManager extends Component
{
    /** @var array<string, mixed> */
    public array $cacheStats = [];

    // Cache driver info
    public string $cacheDriver = '';

    /** @var array<string, int|float|string> */
    public array $storageInfo = [];

    /** @var array<string, mixed> */
    public array $redisInfo = [];
    public bool $redisConnected = false;

    public function mount(): void
    {
        $this->loadCacheDriver();
        $this->loadStorageInfo();
        $this->loadRedisInfo();
    }

    private function loadCacheDriver(): void
    {
        $this->cacheDriver = config('cache.default');
    }

    private function loadStorageInfo(): void
    {
        $basePath = base_path();
        $storagePath = storage_path();

        $diskTotal = disk_total_space($basePath) ?: 0;
        $diskFree = disk_free_space($basePath) ?: 0;

        $this->storageInfo = [
            'disk_total' => $diskTotal,
            'disk_free' => $diskFree,
            'disk_used' => $diskTotal - $diskFree,
            'disk_percent' => $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0,
            'storage_logs' => $this->getDirectorySize($storagePath . '/logs'),
            'storage_cache' => $this->getDirectorySize($storagePath . '/framework/cache'),
            'storage_sessions' => $this->getDirectorySize($storagePath . '/framework/sessions'),
            'storage_views' => $this->getDirectorySize($storagePath . '/framework/views'),
            'public_build' => $this->getDirectorySize(public_path('build')),
            'vendor' => $this->getDirectorySize($basePath . '/vendor'),
            'node_modules' => $this->getDirectorySize($basePath . '/node_modules'),
        ];
    }

    private function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) return 0;

        $size = 0;
        try {
            $result = Process::timeout(10)->run("du -sb {$path} 2>/dev/null | cut -f1");
            $size = (int) trim($result->output());
        } catch (\Exception $e) {
            $size = 0;
        }
        return $size;
    }

    private function loadRedisInfo(): void
    {
        try {
            if (config('database.redis.client') !== null) {
                $redis = app('redis');
                $info = $redis->info();

                $this->redisConnected = true;
                $this->redisInfo = [
                    'version' => $info['redis_version'] ?? 'Unknown',
                    'uptime_days' => round(($info['uptime_in_seconds'] ?? 0) / 86400, 1),
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? 'Unknown',
                    'total_keys' => $this->getRedisKeyCount(),
                    'host' => config('database.redis.default.host', '127.0.0.1'),
                    'port' => config('database.redis.default.port', 6379),
                ];
            } else {
                $this->redisConnected = false;
                $this->redisInfo = ['status' => 'Redis not configured'];
            }
        } catch (\Exception $e) {
            $this->redisConnected = false;
            $this->redisInfo = ['error' => $e->getMessage()];
        }
    }

    private function getRedisKeyCount(): int
    {
        try {
            $result = Process::run("redis-cli DBSIZE 2>/dev/null");
            if (preg_match('/(\d+)/', $result->output(), $matches)) {
                return (int) $matches[1];
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function clearCache(string $type = 'all'): void
    {
        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'cache':
                    Artisan::call('cache:clear');
                    break;
                case 'event':
                    Artisan::call('event:clear');
                    break;
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    break;
            }

            $this->loadStorageInfo();
            session()->flash('message', ucfirst($type) . ' cache cleared successfully!');
            Log::info('DevFlow cache cleared', ['type' => $type, 'user_id' => auth()->id()]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function rebuildCache(): void
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            session()->flash('message', 'All caches rebuilt successfully!');
            Log::info('DevFlow caches rebuilt', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to rebuild cache: ' . $e->getMessage());
        }
    }

    public function flushRedis(): void
    {
        try {
            $redis = app('redis');
            $redis->flushdb();
            $this->loadRedisInfo();
            session()->flash('message', 'Redis cache flushed successfully');
            Log::info('DevFlow Redis flushed', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to flush Redis: ' . $e->getMessage());
        }
    }

    public function cleanStorage(string $type): void
    {
        try {
            $path = match($type) {
                'logs' => storage_path('logs/*.log'),
                'cache' => storage_path('framework/cache/data/*'),
                'sessions' => storage_path('framework/sessions/*'),
                'views' => storage_path('framework/views/*'),
                default => throw new \Exception('Invalid storage type'),
            };

            // Keep .gitignore files
            Process::run("find " . dirname($path) . " -type f ! -name '.gitignore' -delete 2>/dev/null");

            // For logs, keep laravel.log but empty it
            if ($type === 'logs') {
                file_put_contents(storage_path('logs/laravel.log'), '');
            }

            $this->loadStorageInfo();
            session()->flash('message', ucfirst($type) . ' cleaned successfully');
            Log::info('DevFlow storage cleaned', ['type' => $type, 'user_id' => auth()->id()]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clean ' . $type . ': ' . $e->getMessage());
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.cache-manager');
    }
}
