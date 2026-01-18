<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Console\Kernel;

/**
 * Cache Management Service
 *
 * Provides centralized cache management for the application.
 * Handles cache clearing, invalidation, and consistent TTL management.
 */
class CacheManagementService
{
    /**
     * Default cache TTL in seconds (5 minutes)
     */
    private const DEFAULT_TTL = 300;

    /**
     * Short cache TTL for frequently changing data (30 seconds)
     */
    private const SHORT_TTL = 30;

    /**
     * Long cache TTL for rarely changing data (1 hour)
     */
    private const LONG_TTL = 3600;

    public function __construct(
        private readonly DockerService $dockerService
    ) {}

    /**
     * Clear all application caches
     *
     * @return array{app: bool, config: bool, route: bool, view: bool, event: bool}
     */
    public function clearAllCaches(): array
    {
        return [
            'app' => $this->clearAppCache(),
            'config' => $this->clearConfigCache(),
            'route' => $this->clearRouteCache(),
            'view' => $this->clearViewCache(),
            'event' => $this->clearEventCache(),
        ];
    }

    /**
     * Clear application cache
     */
    public function clearAppCache(): bool
    {
        try {
            Artisan::call('cache:clear');
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to clear app cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear config cache
     */
    public function clearConfigCache(): bool
    {
        try {
            Artisan::call('config:clear');
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to clear config cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear route cache
     */
    public function clearRouteCache(): bool
    {
        try {
            Artisan::call('route:clear');
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to clear route cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear view cache
     */
    public function clearViewCache(): bool
    {
        try {
            Artisan::call('view:clear');
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to clear view cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear event cache
     */
    public function clearEventCache(): bool
    {
        try {
            Artisan::call('event:clear');
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to clear event cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear cache for a specific project (Docker containers)
     */
    public function clearProjectCache(Project $project): bool
    {
        try {
            $result = $this->dockerService->clearProjectCache($project);
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            \Log::error("Failed to clear project cache for {$project->slug}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get cached value with consistent TTL
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int|null $ttl Time to live in seconds (uses DEFAULT_TTL if null)
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Get cached value with short TTL (30 seconds)
     *
     * For frequently changing data like metrics, queue stats
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @return mixed
     */
    public function rememberShort(string $key, callable $callback): mixed
    {
        return Cache::remember($key, self::SHORT_TTL, $callback);
    }

    /**
     * Get cached value with long TTL (1 hour)
     *
     * For rarely changing data like SSL stats, security scores
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @return mixed
     */
    public function rememberLong(string $key, callable $callback): mixed
    {
        return Cache::remember($key, self::LONG_TTL, $callback);
    }

    /**
     * Invalidate cache by key
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Invalidate multiple cache keys
     *
     * @param array<int, string> $keys
     * @return int Number of keys invalidated
     */
    public function forgetMultiple(array $keys): int
    {
        $count = 0;
        foreach ($keys as $key) {
            if ($this->forget($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Invalidate cache by prefix
     *
     * Note: This requires a cache driver that supports tagging (Redis, Memcached)
     * Falls back to manual invalidation for other drivers
     */
    public function forgetByPrefix(string $prefix): bool
    {
        try {
            // For drivers that support tags
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags($prefix)->flush();
                return true;
            }

            // Fallback: Manual invalidation (less efficient)
            // This is a simplified version - in production, you might want to
            // maintain a registry of cache keys or use a more sophisticated approach
            \Log::warning("Cache invalidation by prefix requires Redis or Memcached. Using fallback method.");

            return false;
        } catch (\Exception $e) {
            \Log::error("Failed to invalidate cache by prefix {$prefix}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Invalidate dashboard-related caches
     *
     * @return int Number of cache keys invalidated
     */
    public function invalidateDashboardCache(): int
    {
        $keys = [
            'dashboard_stats',
            'dashboard_ssl_stats',
            'dashboard_health_stats',
            'dashboard_server_health',
            'dashboard_queue_stats',
            'dashboard_security_score',
            'dashboard_onboarding_status',
        ];

        return $this->forgetMultiple($keys);
    }

    /**
     * Invalidate project-related caches
     */
    public function invalidateProjectCache(int $projectId): bool
    {
        $keys = [
            "project_health_{$projectId}",
            "project_stats_{$projectId}",
            "project_deployments_{$projectId}",
        ];

        return $this->forgetMultiple($keys) > 0;
    }

    /**
     * Invalidate server-related caches
     */
    public function invalidateServerCache(int $serverId): bool
    {
        $keys = [
            "server_health_{$serverId}",
            "server_metrics_{$serverId}",
            "server_stats_{$serverId}",
        ];

        return $this->forgetMultiple($keys) > 0;
    }

    /**
     * Cache with tags (for drivers that support it)
     *
     * @param string|array<int, string> $tags Cache tags
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int|null $ttl Time to live in seconds
     * @return mixed
     */
    public function rememberWithTags(string|array $tags, string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                return Cache::tags($tags)->remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
            }

            // Fallback to regular cache for drivers that don't support tags
            return $this->remember($key, $callback, $ttl);
        } catch (\Exception $e) {
            \Log::error("Failed to cache with tags: {$e->getMessage()}");
            return $callback();
        }
    }

    /**
     * Flush all cache tags
     *
     * @param string|array<int, string> $tags Cache tags to flush
     */
    public function flushTags(string|array $tags): bool
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags($tags)->flush();
                return true;
            }

            \Log::warning("Cache tag flushing requires Redis or Memcached.");
            return false;
        } catch (\Exception $e) {
            \Log::error("Failed to flush cache tags: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array{driver: string, supported_features: array}
     */
    public function getCacheStats(): array
    {
        $driver = config('cache.default');

        return [
            'driver' => $driver,
            'supported_features' => [
                'tagging' => in_array($driver, ['redis', 'memcached']),
                'persistence' => in_array($driver, ['redis', 'database', 'file']),
                'prefix_invalidation' => in_array($driver, ['redis', 'memcached']),
            ],
        ];
    }

    /**
     * Warm up cache with commonly used data
     *
     * Pre-populates cache with frequently accessed data to improve performance
     */
    public function warmUpCache(): array
    {
        $results = [];

        try {
            // Warm up dashboard stats
            $results['dashboard_stats'] = $this->remember('dashboard_stats', function () {
                return [
                    'total_servers' => \App\Models\Server::count(),
                    'online_servers' => \App\Models\Server::where('status', 'online')->count(),
                    'total_projects' => \App\Models\Project::count(),
                    'running_projects' => \App\Models\Project::where('status', 'running')->count(),
                ];
            });

            // Warm up SSL stats
            $results['ssl_stats'] = $this->rememberLong('dashboard_ssl_stats', function () {
                return [
                    'total_certificates' => \App\Models\SSLCertificate::count(),
                    'active_certificates' => \App\Models\SSLCertificate::where('status', 'issued')->count(),
                ];
            });

            return [
                'success' => true,
                'cached_items' => count($results),
                'results' => $results,
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to warm up cache: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if cache key exists
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Get cache value without updating expiration
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Set cache value with custom TTL
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Set cache value forever (no expiration)
     */
    public function forever(string $key, mixed $value): bool
    {
        return Cache::forever($key, $value);
    }

    /**
     * Increment cache value
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement cache value
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Add value to cache only if it doesn't exist
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::add($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Get and delete cache value
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return Cache::pull($key, $default);
    }

    /**
     * Clear all caches including optimized caches
     *
     * @return array{cleared: array, failed: array}
     */
    public function clearAllCachesComplete(): array
    {
        $cleared = [];
        $failed = [];

        // Clear Laravel caches
        $caches = $this->clearAllCaches();
        foreach ($caches as $type => $success) {
            if ($success) {
                $cleared[] = $type;
            } else {
                $failed[] = $type;
            }
        }

        // Clear application cache
        try {
            Cache::flush();
            $cleared[] = 'cache_store';
        } catch (\Exception $e) {
            $failed[] = 'cache_store';
            \Log::error("Failed to flush cache store: {$e->getMessage()}");
        }

        // Clear optimized files
        try {
            Artisan::call('optimize:clear');
            $cleared[] = 'optimize';
        } catch (\Exception $e) {
            $failed[] = 'optimize';
            \Log::error("Failed to clear optimized files: {$e->getMessage()}");
        }

        return [
            'cleared' => $cleared,
            'failed' => $failed,
        ];
    }

    /**
     * Get TTL constants for public access
     *
     * @return array{default: int, short: int, long: int}
     */
    public function getTTLConstants(): array
    {
        return [
            'default' => self::DEFAULT_TTL,
            'short' => self::SHORT_TTL,
            'long' => self::LONG_TTL,
        ];
    }
}
