<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheableStats
{
    /**
     * Execute a callback with caching, falling back to direct execution if cache fails
     *
     * @param  string  $cacheKey  The cache key to use
     * @param  int  $ttl  Time to live in seconds
     * @param  callable  $callback  The callback that returns the data to cache
     * @return mixed The result from the callback
     */
    protected function cacheOrFallback(string $cacheKey, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            // If Redis/cache is not available, execute directly without caching
            return $callback();
        }
    }

    /**
     * Execute a callback with default data fallback in case of exceptions
     *
     * @param  callable  $callback  The callback that returns the data
     * @param  mixed  $default  Default value to return on failure
     * @return mixed The result from the callback or default value
     */
    protected function executeOrDefault(callable $callback, mixed $default = []): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Combined method: cache with fallback AND default on error
     * This is the most common pattern in the dashboard
     *
     * @param  string  $cacheKey  The cache key to use
     * @param  int  $ttl  Time to live in seconds
     * @param  callable  $callback  The callback that returns the data to cache
     * @param  mixed  $default  Default value to return on complete failure
     * @return mixed The result from the callback or default value
     */
    protected function cachedStats(string $cacheKey, int $ttl, callable $callback, mixed $default = []): mixed
    {
        try {
            return Cache::remember($cacheKey, $ttl, function () use ($callback, $default) {
                try {
                    return $callback();
                } catch (\Exception $e) {
                    return $default;
                }
            });
        } catch (\Exception $e) {
            // If Redis/cache is not available, execute directly without caching
            try {
                return $callback();
            } catch (\Exception $e) {
                return $default;
            }
        }
    }

    /**
     * Forget multiple cache keys safely
     *
     * @param  array<int, string>  $keys  Array of cache keys to forget
     */
    protected function forgetCacheKeys(array $keys): void
    {
        try {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } catch (\Exception $e) {
            // Silently fail if Redis is not available
        }
    }
}
