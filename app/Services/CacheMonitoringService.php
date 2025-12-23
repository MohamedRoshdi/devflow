<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Cache Monitoring Service
 *
 * Provides comprehensive cache hit/miss monitoring, statistics tracking,
 * and performance metrics for the application's caching layer.
 */
class CacheMonitoringService
{
    /**
     * Cache key prefix for monitoring data
     */
    private const MONITORING_PREFIX = 'cache_monitor:';

    /**
     * TTL for monitoring data (24 hours)
     */
    private const MONITORING_TTL = 86400;

    /**
     * Increment hit counter for a cache key
     */
    public function recordHit(string $key): void
    {
        $this->incrementCounter('hits');
        $this->incrementKeyCounter($key, 'hits');
        $this->recordLatestAccess($key, 'hit');
    }

    /**
     * Increment miss counter for a cache key
     */
    public function recordMiss(string $key): void
    {
        $this->incrementCounter('misses');
        $this->incrementKeyCounter($key, 'misses');
        $this->recordLatestAccess($key, 'miss');
    }

    /**
     * Get value with automatic hit/miss tracking
     *
     * @template T
     * @param string $key Cache key
     * @param callable(): T $callback Callback to generate value if not cached
     * @param int|null $ttl Time to live in seconds
     * @return T
     */
    public function rememberWithTracking(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $startTime = microtime(true);

        if (Cache::has($key)) {
            $value = Cache::get($key);
            $this->recordHit($key);
            $this->recordLatency($key, microtime(true) - $startTime);

            return $value;
        }

        $this->recordMiss($key);
        $value = $callback();
        $this->recordLatency($key, microtime(true) - $startTime);

        Cache::put($key, $value, $ttl ?? 300);

        return $value;
    }

    /**
     * Get overall cache statistics
     *
     * @return array{hits: int, misses: int, hit_rate: float, total_requests: int, avg_latency_ms: float}
     */
    public function getStatistics(): array
    {
        $hits = $this->getCounter('hits');
        $misses = $this->getCounter('misses');
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
            'total_requests' => $total,
            'avg_latency_ms' => $this->getAverageLatency(),
        ];
    }

    /**
     * Get statistics for a specific cache key
     *
     * @return array{key: string, hits: int, misses: int, hit_rate: float, last_access: string|null, avg_latency_ms: float}
     */
    public function getKeyStatistics(string $key): array
    {
        $hits = $this->getKeyCounter($key, 'hits');
        $misses = $this->getKeyCounter($key, 'misses');
        $total = $hits + $misses;

        return [
            'key' => $key,
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
            'last_access' => $this->getLastAccess($key),
            'avg_latency_ms' => $this->getKeyAverageLatency($key),
        ];
    }

    /**
     * Get top cache keys by hit count
     *
     * @param int $limit Number of keys to return
     * @return array<int, array{key: string, hits: int, misses: int, hit_rate: float}>
     */
    public function getTopKeys(int $limit = 10): array
    {
        $keysData = $this->getAllKeyStats();

        usort($keysData, fn($a, $b) => $b['hits'] - $a['hits']);

        return array_slice($keysData, 0, $limit);
    }

    /**
     * Get keys with lowest hit rate (potential optimization targets)
     *
     * @param int $limit Number of keys to return
     * @param int $minRequests Minimum requests to be considered
     * @return array<int, array{key: string, hits: int, misses: int, hit_rate: float}>
     */
    public function getLowPerformingKeys(int $limit = 10, int $minRequests = 10): array
    {
        $keysData = $this->getAllKeyStats();

        // Filter by minimum requests
        $keysData = array_filter($keysData, fn($data) => ($data['hits'] + $data['misses']) >= $minRequests);

        usort($keysData, fn($a, $b) => $a['hit_rate'] <=> $b['hit_rate']);

        return array_slice($keysData, 0, $limit);
    }

    /**
     * Get cache performance over time (hourly breakdown)
     *
     * @param int $hours Number of hours to look back
     * @return array<string, array{hour: string, hits: int, misses: int, hit_rate: float}>
     */
    public function getHourlyPerformance(int $hours = 24): array
    {
        $performance = [];
        $now = now();

        for ($i = 0; $i < $hours; $i++) {
            $hour = $now->copy()->subHours($i)->format('Y-m-d H:00');
            $hourKey = $now->copy()->subHours($i)->format('YmdH');

            $hits = (int) Cache::get(self::MONITORING_PREFIX . "hourly:hits:{$hourKey}", 0);
            $misses = (int) Cache::get(self::MONITORING_PREFIX . "hourly:misses:{$hourKey}", 0);
            $total = $hits + $misses;

            $performance[$hour] = [
                'hour' => $hour,
                'hits' => $hits,
                'misses' => $misses,
                'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
            ];
        }

        return array_reverse($performance);
    }

    /**
     * Get Redis-specific statistics (if Redis is configured)
     *
     * @return array<string, mixed>|null
     */
    public function getRedisStats(): ?array
    {
        if (config('cache.default') !== 'redis') {
            return null;
        }

        try {
            $info = Redis::info();

            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'used_memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateRedisHitRate($info),
                'uptime_days' => isset($info['uptime_in_days']) ? (int) $info['uptime_in_days'] : 0,
                'db_size' => $this->getRedisDbSize(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get Redis stats: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Reset all monitoring statistics
     */
    public function resetStatistics(): bool
    {
        try {
            Cache::forget(self::MONITORING_PREFIX . 'total:hits');
            Cache::forget(self::MONITORING_PREFIX . 'total:misses');
            Cache::forget(self::MONITORING_PREFIX . 'latency:sum');
            Cache::forget(self::MONITORING_PREFIX . 'latency:count');
            Cache::forget(self::MONITORING_PREFIX . 'keys:list');

            Log::info('Cache monitoring statistics have been reset');

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reset cache statistics: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get comprehensive monitoring report
     *
     * @return array{
     *   summary: array,
     *   top_keys: array,
     *   low_performing: array,
     *   hourly: array,
     *   redis: array|null,
     *   recommendations: array
     * }
     */
    public function getMonitoringReport(): array
    {
        $stats = $this->getStatistics();
        $topKeys = $this->getTopKeys(10);
        $lowPerforming = $this->getLowPerformingKeys(5);
        $hourly = $this->getHourlyPerformance(24);
        $redis = $this->getRedisStats();

        return [
            'summary' => $stats,
            'top_keys' => $topKeys,
            'low_performing' => $lowPerforming,
            'hourly' => $hourly,
            'redis' => $redis,
            'recommendations' => $this->generateRecommendations($stats, $lowPerforming),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Increment a global counter
     */
    private function incrementCounter(string $type): void
    {
        $key = self::MONITORING_PREFIX . "total:{$type}";
        Cache::increment($key);

        // Also track hourly
        $hourKey = self::MONITORING_PREFIX . "hourly:{$type}:" . now()->format('YmdH');
        Cache::increment($hourKey);
        // Ensure hourly keys expire
        Cache::put($hourKey, (int) Cache::get($hourKey, 0), self::MONITORING_TTL);
    }

    /**
     * Get a global counter value
     */
    private function getCounter(string $type): int
    {
        return (int) Cache::get(self::MONITORING_PREFIX . "total:{$type}", 0);
    }

    /**
     * Increment a key-specific counter
     */
    private function incrementKeyCounter(string $key, string $type): void
    {
        $counterKey = self::MONITORING_PREFIX . "key:{$key}:{$type}";
        Cache::increment($counterKey);

        // Track this key in our list
        $this->trackKey($key);
    }

    /**
     * Get a key-specific counter value
     */
    private function getKeyCounter(string $key, string $type): int
    {
        return (int) Cache::get(self::MONITORING_PREFIX . "key:{$key}:{$type}", 0);
    }

    /**
     * Track a key for statistics gathering
     */
    private function trackKey(string $key): void
    {
        $listKey = self::MONITORING_PREFIX . 'keys:list';
        $keys = Cache::get($listKey, []);

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put($listKey, $keys, self::MONITORING_TTL);
        }
    }

    /**
     * Record the latest access for a key
     */
    private function recordLatestAccess(string $key, string $type): void
    {
        Cache::put(
            self::MONITORING_PREFIX . "key:{$key}:last_access",
            ['time' => now()->toIso8601String(), 'type' => $type],
            self::MONITORING_TTL
        );
    }

    /**
     * Get last access info for a key
     */
    private function getLastAccess(string $key): ?string
    {
        $data = Cache::get(self::MONITORING_PREFIX . "key:{$key}:last_access");

        return $data['time'] ?? null;
    }

    /**
     * Record latency for a cache operation
     */
    private function recordLatency(string $key, float $latencySeconds): void
    {
        $latencyMs = $latencySeconds * 1000;

        // Global latency tracking
        Cache::increment(self::MONITORING_PREFIX . 'latency:count');
        $sum = (float) Cache::get(self::MONITORING_PREFIX . 'latency:sum', 0);
        Cache::put(self::MONITORING_PREFIX . 'latency:sum', $sum + $latencyMs, self::MONITORING_TTL);

        // Per-key latency tracking
        Cache::increment(self::MONITORING_PREFIX . "key:{$key}:latency:count");
        $keySum = (float) Cache::get(self::MONITORING_PREFIX . "key:{$key}:latency:sum", 0);
        Cache::put(self::MONITORING_PREFIX . "key:{$key}:latency:sum", $keySum + $latencyMs, self::MONITORING_TTL);
    }

    /**
     * Get average latency across all operations
     */
    private function getAverageLatency(): float
    {
        $count = (int) Cache::get(self::MONITORING_PREFIX . 'latency:count', 0);
        $sum = (float) Cache::get(self::MONITORING_PREFIX . 'latency:sum', 0);

        return $count > 0 ? round($sum / $count, 3) : 0.0;
    }

    /**
     * Get average latency for a specific key
     */
    private function getKeyAverageLatency(string $key): float
    {
        $count = (int) Cache::get(self::MONITORING_PREFIX . "key:{$key}:latency:count", 0);
        $sum = (float) Cache::get(self::MONITORING_PREFIX . "key:{$key}:latency:sum", 0);

        return $count > 0 ? round($sum / $count, 3) : 0.0;
    }

    /**
     * Get all tracked key statistics
     *
     * @return array<int, array{key: string, hits: int, misses: int, hit_rate: float}>
     */
    private function getAllKeyStats(): array
    {
        $keys = Cache::get(self::MONITORING_PREFIX . 'keys:list', []);
        $stats = [];

        foreach ($keys as $key) {
            $hits = $this->getKeyCounter($key, 'hits');
            $misses = $this->getKeyCounter($key, 'misses');
            $total = $hits + $misses;

            $stats[] = [
                'key' => $key,
                'hits' => $hits,
                'misses' => $misses,
                'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
            ];
        }

        return $stats;
    }

    /**
     * Calculate Redis hit rate from info
     *
     * @param array<string, mixed> $info
     */
    private function calculateRedisHitRate(array $info): float
    {
        $hits = (int) ($info['keyspace_hits'] ?? 0);
        $misses = (int) ($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Get Redis database size
     */
    private function getRedisDbSize(): int
    {
        try {
            return (int) Redis::dbsize();
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * Generate recommendations based on statistics
     *
     * @param array<string, mixed> $stats
     * @param array<int, array<string, mixed>> $lowPerforming
     * @return array<int, string>
     */
    private function generateRecommendations(array $stats, array $lowPerforming): array
    {
        $recommendations = [];

        // Check overall hit rate
        if ($stats['hit_rate'] < 50) {
            $recommendations[] = 'Overall cache hit rate is below 50%. Consider reviewing TTL settings or cache warming strategies.';
        } elseif ($stats['hit_rate'] < 75) {
            $recommendations[] = 'Cache hit rate could be improved. Consider increasing TTL for stable data.';
        }

        // Check for low performing keys
        if (count($lowPerforming) > 0) {
            $keyList = implode(', ', array_map(fn($k) => $k['key'], array_slice($lowPerforming, 0, 3)));
            $recommendations[] = "Low hit rate detected for keys: {$keyList}. Consider adjusting TTL or caching strategy.";
        }

        // Check latency
        if ($stats['avg_latency_ms'] > 10) {
            $recommendations[] = 'Average cache latency is high. Consider using Redis if not already configured.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Cache performance looks good! No immediate optimizations needed.';
        }

        return $recommendations;
    }
}
