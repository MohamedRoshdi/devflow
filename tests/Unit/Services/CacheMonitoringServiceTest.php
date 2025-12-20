<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CacheMonitoringService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheMonitoringServiceTest extends TestCase
{
    private CacheMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Use array cache driver for tests
        config(['cache.default' => 'array']);

        $this->service = new CacheMonitoringService();

        // Reset statistics before each test
        $this->service->resetStatistics();
    }

    public function test_record_hit_increments_hit_counter(): void
    {
        $this->service->recordHit('test_key');
        $this->service->recordHit('test_key');

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
    }

    public function test_record_miss_increments_miss_counter(): void
    {
        $this->service->recordMiss('test_key');
        $this->service->recordMiss('test_key');
        $this->service->recordMiss('test_key');

        $stats = $this->service->getStatistics();

        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(3, $stats['misses']);
    }

    public function test_get_statistics_calculates_hit_rate(): void
    {
        // 3 hits, 1 miss = 75% hit rate
        $this->service->recordHit('key1');
        $this->service->recordHit('key2');
        $this->service->recordHit('key3');
        $this->service->recordMiss('key4');

        $stats = $this->service->getStatistics();

        $this->assertEquals(3, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(75.0, $stats['hit_rate']);
        $this->assertEquals(4, $stats['total_requests']);
    }

    public function test_get_statistics_returns_zero_hit_rate_when_no_requests(): void
    {
        $stats = $this->service->getStatistics();

        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
        $this->assertEquals(0.0, $stats['hit_rate']);
        $this->assertEquals(0, $stats['total_requests']);
    }

    public function test_get_key_statistics_tracks_individual_keys(): void
    {
        $this->service->recordHit('specific_key');
        $this->service->recordHit('specific_key');
        $this->service->recordMiss('specific_key');
        $this->service->recordHit('other_key');

        $keyStats = $this->service->getKeyStatistics('specific_key');

        $this->assertEquals('specific_key', $keyStats['key']);
        $this->assertEquals(2, $keyStats['hits']);
        $this->assertEquals(1, $keyStats['misses']);
        $this->assertEqualsWithDelta(66.67, $keyStats['hit_rate'], 0.01);
    }

    public function test_remember_with_tracking_records_hit_on_cache_hit(): void
    {
        $key = 'tracked_key_' . uniqid();
        $value = 'cached_value';

        // First call - should be a miss
        Cache::put($key, $value, 300);

        $result = $this->service->rememberWithTracking($key, fn() => 'new_value');

        $this->assertEquals($value, $result);

        $stats = $this->service->getStatistics();
        $this->assertEquals(1, $stats['hits']);
    }

    public function test_remember_with_tracking_records_miss_on_cache_miss(): void
    {
        $key = 'missing_key_' . uniqid();

        $result = $this->service->rememberWithTracking($key, fn() => 'generated_value');

        $this->assertEquals('generated_value', $result);

        $stats = $this->service->getStatistics();
        $this->assertEquals(1, $stats['misses']);
    }

    public function test_remember_with_tracking_caches_value_on_miss(): void
    {
        $key = 'cacheable_key_' . uniqid();

        $this->service->rememberWithTracking($key, fn() => 'cached_value', 300);

        $this->assertTrue(Cache::has($key));
        $this->assertEquals('cached_value', Cache::get($key));
    }

    public function test_get_top_keys_returns_keys_sorted_by_hits(): void
    {
        // Create keys with different hit counts
        $this->service->recordHit('popular_key');
        $this->service->recordHit('popular_key');
        $this->service->recordHit('popular_key');
        $this->service->recordHit('medium_key');
        $this->service->recordHit('medium_key');
        $this->service->recordHit('low_key');

        $topKeys = $this->service->getTopKeys(3);

        $this->assertCount(3, $topKeys);
        $this->assertEquals('popular_key', $topKeys[0]['key']);
        $this->assertEquals(3, $topKeys[0]['hits']);
        $this->assertEquals('medium_key', $topKeys[1]['key']);
        $this->assertEquals(2, $topKeys[1]['hits']);
    }

    public function test_get_low_performing_keys_filters_by_minimum_requests(): void
    {
        // Create a key with many requests but low hit rate
        for ($i = 0; $i < 15; $i++) {
            $this->service->recordMiss('low_rate_key');
        }
        $this->service->recordHit('low_rate_key');

        // Create a key with few requests
        $this->service->recordMiss('few_requests_key');
        $this->service->recordMiss('few_requests_key');

        $lowPerforming = $this->service->getLowPerformingKeys(5, 10);

        // Only the key with 16 requests should be included
        $this->assertCount(1, $lowPerforming);
        $this->assertEquals('low_rate_key', $lowPerforming[0]['key']);
    }

    public function test_reset_statistics_clears_all_counters(): void
    {
        $this->service->recordHit('key1');
        $this->service->recordMiss('key2');

        $statsBefore = $this->service->getStatistics();
        $this->assertGreaterThan(0, $statsBefore['total_requests']);

        $this->service->resetStatistics();

        $statsAfter = $this->service->getStatistics();
        $this->assertEquals(0, $statsAfter['total_requests']);
    }

    public function test_get_monitoring_report_returns_comprehensive_data(): void
    {
        $this->service->recordHit('key1');
        $this->service->recordMiss('key2');

        $report = $this->service->getMonitoringReport();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('top_keys', $report);
        $this->assertArrayHasKey('low_performing', $report);
        $this->assertArrayHasKey('hourly', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('generated_at', $report);
    }

    public function test_recommendations_generated_for_low_hit_rate(): void
    {
        // Create scenario with low hit rate (below 50%)
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordMiss('miss_key_' . $i);
        }
        $this->service->recordHit('hit_key');

        $report = $this->service->getMonitoringReport();

        $recommendations = $report['recommendations'];
        $this->assertNotEmpty($recommendations);
        $this->assertTrue(
            str_contains(implode(' ', $recommendations), 'hit rate') ||
            str_contains(implode(' ', $recommendations), 'below 50')
        );
    }

    public function test_get_hourly_performance_returns_24_hours_by_default(): void
    {
        $hourly = $this->service->getHourlyPerformance();

        $this->assertCount(24, $hourly);
    }

    public function test_get_hourly_performance_returns_requested_hours(): void
    {
        $hourly = $this->service->getHourlyPerformance(12);

        $this->assertCount(12, $hourly);
    }

    public function test_statistics_track_average_latency(): void
    {
        // Use rememberWithTracking which records latency
        $key = 'latency_test_' . uniqid();
        $this->service->rememberWithTracking($key, fn() => 'value');

        $stats = $this->service->getStatistics();

        $this->assertArrayHasKey('avg_latency_ms', $stats);
        $this->assertIsFloat($stats['avg_latency_ms']);
    }

    public function test_get_redis_stats_returns_null_for_non_redis_driver(): void
    {
        // Default test environment uses array driver
        config(['cache.default' => 'array']);

        $redisStats = $this->service->getRedisStats();

        $this->assertNull($redisStats);
    }
}
