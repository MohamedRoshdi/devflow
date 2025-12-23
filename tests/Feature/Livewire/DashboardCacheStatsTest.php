<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard\DashboardCacheStats;
use App\Models\User;
use App\Services\CacheMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class DashboardCacheStatsTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::flush();
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockCacheMonitoringService();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        $this->mockCacheMonitoringService();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSet('showDetails', false)
            ->assertSet('isLoading', false)
            ->assertSet('hasError', false);
    }

    public function test_component_displays_cache_stats(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 1500,
                'misses' => 500,
                'hit_rate' => 75.0,
                'total_requests' => 2000,
                'avg_latency_ms' => 1.5,
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSee('75.0%')
            ->assertSee('1,500')
            ->assertSee('500')
            ->assertSee('1.50ms');
    }

    // ==================== STATS LOADING TESTS ====================

    public function test_loads_cache_stats_on_mount(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 100,
                'misses' => 20,
                'hit_rate' => 83.33,
                'total_requests' => 120,
                'avg_latency_ms' => 0.8,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $stats = $component->get('stats');
        $this->assertEquals(100, $stats['hits']);
        $this->assertEquals(20, $stats['misses']);
        $this->assertEquals(83.33, $stats['hit_rate']);
        $this->assertEquals(120, $stats['total_requests']);
        $this->assertEquals(0.8, $stats['avg_latency_ms']);
    }

    public function test_loads_top_keys(): void
    {
        $this->mockCacheMonitoringService([
            'top_keys' => [
                ['key' => 'users:list', 'hits' => 500],
                ['key' => 'projects:all', 'hits' => 300],
                ['key' => 'servers:health', 'hits' => 200],
                ['key' => 'settings:cache', 'hits' => 150],
                ['key' => 'dashboard:stats', 'hits' => 100],
                ['key' => 'extra:key', 'hits' => 50], // Should be trimmed
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $topKeys = $component->get('topKeys');
        $this->assertCount(5, $topKeys);
        $this->assertEquals('users:list', $topKeys[0]['key']);
    }

    public function test_loads_low_performing_keys(): void
    {
        $this->mockCacheMonitoringService([
            'low_performing' => [
                ['key' => 'slow:query', 'hits' => 10, 'misses' => 90, 'hit_rate' => 10.0],
                ['key' => 'another:slow', 'hits' => 20, 'misses' => 80, 'hit_rate' => 20.0],
                ['key' => 'third:slow', 'hits' => 30, 'misses' => 70, 'hit_rate' => 30.0],
                ['key' => 'fourth:slow', 'hits' => 40, 'misses' => 60, 'hit_rate' => 40.0], // Should be trimmed
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $lowPerforming = $component->get('lowPerformingKeys');
        $this->assertCount(3, $lowPerforming);
    }

    public function test_loads_recommendations(): void
    {
        $this->mockCacheMonitoringService([
            'recommendations' => [
                'Consider increasing TTL for frequently accessed keys',
                'Review keys with low hit rates',
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $recommendations = $component->get('recommendations');
        $this->assertCount(2, $recommendations);
        $this->assertStringContainsString('TTL', $recommendations[0]);
    }

    public function test_loads_redis_stats(): void
    {
        $this->mockCacheMonitoringService([
            'redis' => [
                'used_memory' => '25MB',
                'db_size' => 1500,
                'uptime_days' => 45,
                'connected_clients' => 12,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $redisStats = $component->get('redisStats');
        $this->assertEquals('25MB', $redisStats['used_memory']);
        $this->assertEquals(1500, $redisStats['db_size']);
        $this->assertEquals(45, $redisStats['uptime_days']);
        $this->assertEquals(12, $redisStats['connected_clients']);
    }

    // ==================== TOGGLE DETAILS TESTS ====================

    public function test_can_toggle_details(): void
    {
        $this->mockCacheMonitoringService();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_expanded_details_show_top_keys(): void
    {
        $this->mockCacheMonitoringService([
            'top_keys' => [
                ['key' => 'users:list', 'hits' => 500],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->call('toggleDetails')
            ->assertSee('Top Keys')
            ->assertSee('users:list');
    }

    // ==================== REFRESH STATS TESTS ====================

    public function test_can_refresh_stats(): void
    {
        // Set up mock that returns different values on successive calls
        $service = Mockery::mock(CacheMonitoringService::class);
        $service->shouldReceive('getMonitoringReport')
            ->once()
            ->andReturn([
                'summary' => [
                    'hits' => 100,
                    'misses' => 50,
                    'hit_rate' => 66.67,
                    'total_requests' => 150,
                    'avg_latency_ms' => 1.0,
                ],
                'top_keys' => [],
                'low_performing' => [],
                'recommendations' => [],
                'redis' => null,
            ]);
        $service->shouldReceive('getMonitoringReport')
            ->once()
            ->andReturn([
                'summary' => [
                    'hits' => 200,
                    'misses' => 50,
                    'hit_rate' => 80.0,
                    'total_requests' => 250,
                    'avg_latency_ms' => 0.9,
                ],
                'top_keys' => [],
                'low_performing' => [],
                'recommendations' => [],
                'redis' => null,
            ]);

        $this->app->instance(CacheMonitoringService::class, $service);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $component->call('refreshStats');

        $stats = $component->get('stats');
        $this->assertEquals(200, $stats['hits']);
        $this->assertEquals(80.0, $stats['hit_rate']);
    }

    public function test_refresh_responds_to_event(): void
    {
        $this->mockCacheMonitoringService();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->dispatch('refresh-cache-stats')
            ->assertSet('isLoading', false);
    }

    // ==================== RESET STATS TESTS ====================

    public function test_can_reset_stats(): void
    {
        $service = $this->mockCacheMonitoringService();
        $service->shouldReceive('resetStatistics')->once();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->call('resetStats')
            ->assertDispatched('notify');
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_handles_service_error(): void
    {
        $service = Mockery::mock(CacheMonitoringService::class);
        $service->shouldReceive('getMonitoringReport')
            ->andThrow(new \Exception('Connection failed'));

        $this->app->instance(CacheMonitoringService::class, $service);

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSet('hasError', true)
            ->assertSet('errorMessage', 'Failed to load cache statistics.');
    }

    public function test_error_state_displays_message(): void
    {
        $service = Mockery::mock(CacheMonitoringService::class);
        $service->shouldReceive('getMonitoringReport')
            ->andThrow(new \Exception('Redis unavailable'));

        $this->app->instance(CacheMonitoringService::class, $service);

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSee('Failed to load cache statistics.');
    }

    // ==================== HIT RATE STATUS TESTS ====================

    public function test_hit_rate_status_excellent(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 90,
                'misses' => 10,
                'hit_rate' => 90.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('text-green-500', $component->invade()->getHitRateStatus());
    }

    public function test_hit_rate_status_good(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 60,
                'misses' => 40,
                'hit_rate' => 60.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('text-yellow-500', $component->invade()->getHitRateStatus());
    }

    public function test_hit_rate_status_needs_attention(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 30,
                'misses' => 70,
                'hit_rate' => 30.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('text-red-500', $component->invade()->getHitRateStatus());
    }

    // ==================== HIT RATE BADGE CLASS TESTS ====================

    public function test_hit_rate_badge_class_excellent(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 85,
                'misses' => 15,
                'hit_rate' => 85.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $badgeClass = $component->invade()->getHitRateBadgeClass();
        $this->assertStringContainsString('bg-green-100', $badgeClass);
    }

    public function test_hit_rate_badge_class_good(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 55,
                'misses' => 45,
                'hit_rate' => 55.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $badgeClass = $component->invade()->getHitRateBadgeClass();
        $this->assertStringContainsString('bg-yellow-100', $badgeClass);
    }

    public function test_hit_rate_badge_class_needs_attention(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 40,
                'misses' => 60,
                'hit_rate' => 40.0,
                'total_requests' => 100,
                'avg_latency_ms' => 0.5,
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $badgeClass = $component->invade()->getHitRateBadgeClass();
        $this->assertStringContainsString('bg-red-100', $badgeClass);
    }

    // ==================== FORMAT BYTES TESTS ====================

    public function test_format_bytes_small(): void
    {
        $this->mockCacheMonitoringService();

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('500 B', $component->invade()->formatBytes(500));
    }

    public function test_format_bytes_kilobytes(): void
    {
        $this->mockCacheMonitoringService();

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('1.5 KB', $component->invade()->formatBytes(1536));
    }

    public function test_format_bytes_megabytes(): void
    {
        $this->mockCacheMonitoringService();

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('2.5 MB', $component->invade()->formatBytes(2621440));
    }

    public function test_format_bytes_gigabytes(): void
    {
        $this->mockCacheMonitoringService();

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $this->assertEquals('1 GB', $component->invade()->formatBytes(1073741824));
    }

    // ==================== LOADING STATE TESTS ====================

    public function test_loading_state_after_mount(): void
    {
        $this->mockCacheMonitoringService();

        Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class)
            ->assertSet('isLoading', false);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_empty_stats(): void
    {
        $this->mockCacheMonitoringService([
            'summary' => [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0.0,
                'total_requests' => 0,
                'avg_latency_ms' => 0.0,
            ],
            'top_keys' => [],
            'low_performing' => [],
            'recommendations' => [],
            'redis' => null,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardCacheStats::class);

        $stats = $component->get('stats');
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['hit_rate']);
        $this->assertEmpty($component->get('topKeys'));
        $this->assertNull($component->get('redisStats'));
    }

    // ==================== HELPER METHODS ====================

    /**
     * Mock the CacheMonitoringService with default or custom data.
     *
     * @param  array<string, mixed>  $overrides
     * @return \Mockery\MockInterface
     */
    private function mockCacheMonitoringService(array $overrides = []): \Mockery\MockInterface
    {
        $defaultReport = [
            'summary' => [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0.0,
                'total_requests' => 0,
                'avg_latency_ms' => 0.0,
            ],
            'top_keys' => [],
            'low_performing' => [],
            'recommendations' => [],
            'redis' => null,
        ];

        $report = array_merge($defaultReport, $overrides);

        $service = Mockery::mock(CacheMonitoringService::class);
        $service->shouldReceive('getMonitoringReport')
            ->andReturn($report);

        $this->app->instance(CacheMonitoringService::class, $service);

        return $service;
    }
}
