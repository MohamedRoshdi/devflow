<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Services\CacheManagementService;
use App\Services\DockerService;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CacheManagementServiceTest extends TestCase
{
    

    private CacheManagementService $service;
    private DockerService $dockerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->service = new CacheManagementService($this->dockerService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // CACHE CLEARING TESTS
    // ==========================================

    #[Test]
    public function it_clears_all_caches_successfully(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('event:clear')
            ->once();

        $result = $this->service->clearAllCaches();

        $this->assertTrue($result['app']);
        $this->assertTrue($result['config']);
        $this->assertTrue($result['route']);
        $this->assertTrue($result['view']);
        $this->assertTrue($result['event']);
    }

    #[Test]
    public function it_clears_app_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();

        $result = $this->service->clearAppCache();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_clears_config_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $result = $this->service->clearConfigCache();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_clears_route_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once();

        $result = $this->service->clearRouteCache();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_clears_view_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once();

        $result = $this->service->clearViewCache();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_clears_event_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('event:clear')
            ->once();

        $result = $this->service->clearEventCache();

        $this->assertTrue($result);
    }

    // ==========================================
    // PROJECT CACHE TESTS
    // ==========================================

    #[Test]
    public function it_clears_project_cache(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);

        $this->dockerService->shouldReceive('clearProjectCache')
            ->once()
            ->with($project)
            ->andReturn(['success' => true]);

        $result = $this->service->clearProjectCache($project);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_handles_project_cache_clear_failure(): void
    {
        // No error log is made when success is false - only on exception
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('clearProjectCache')
            ->andReturn(['success' => false]);

        $result = $this->service->clearProjectCache($project);

        $this->assertFalse($result);
    }

    // ==========================================
    // CACHE REMEMBER TESTS
    // ==========================================

    #[Test]
    public function it_remembers_cached_value(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 300, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->remember('test_key', fn() => 'cached_value');

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_remembers_with_custom_ttl(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 600, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->remember('test_key', fn() => 'cached_value', 600);

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_remembers_short_ttl(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 30, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->rememberShort('test_key', fn() => 'cached_value');

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_remembers_long_ttl(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 3600, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->rememberLong('test_key', fn() => 'cached_value');

        $this->assertEquals('cached_value', $result);
    }

    // ==========================================
    // CACHE INVALIDATION TESTS
    // ==========================================

    #[Test]
    public function it_forgets_cache_key(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('test_key')
            ->andReturn(true);

        $result = $this->service->forget('test_key');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_forgets_multiple_keys(): void
    {
        Cache::shouldReceive('forget')
            ->times(3)
            ->andReturn(true);

        $count = $this->service->forgetMultiple(['key1', 'key2', 'key3']);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_invalidates_dashboard_cache(): void
    {
        Cache::shouldReceive('forget')
            ->times(7)
            ->andReturn(true);

        $count = $this->service->invalidateDashboardCache();

        $this->assertEquals(7, $count);
    }

    #[Test]
    public function it_invalidates_project_cache(): void
    {
        Cache::shouldReceive('forget')
            ->times(3)
            ->andReturn(true);

        $result = $this->service->invalidateProjectCache(1);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_invalidates_server_cache(): void
    {
        Cache::shouldReceive('forget')
            ->times(3)
            ->andReturn(true);

        $result = $this->service->invalidateServerCache(1);

        $this->assertTrue($result);
    }

    // ==========================================
    // CACHE WITH TAGS TESTS
    // ==========================================

    #[Test]
    public function it_remembers_with_tags_on_redis(): void
    {
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('tags')
            ->once()
            ->with('project')
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 300, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->rememberWithTags('project', 'test_key', fn() => 'cached_value');

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_falls_back_without_tags_on_file_cache(): void
    {
        config(['cache.default' => 'file']);

        Cache::shouldReceive('remember')
            ->once()
            ->with('test_key', 300, Mockery::type('callable'))
            ->andReturn('cached_value');

        $result = $this->service->rememberWithTags('project', 'test_key', fn() => 'cached_value');

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_flushes_tags_on_redis(): void
    {
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('tags')
            ->once()
            ->with('project')
            ->andReturnSelf();

        Cache::shouldReceive('flush')
            ->once();

        $result = $this->service->flushTags('project');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_cannot_flush_tags_on_file_cache(): void
    {
        \Log::shouldReceive('warning')->once();

        config(['cache.default' => 'file']);

        $result = $this->service->flushTags('project');

        $this->assertFalse($result);
    }

    // ==========================================
    // CACHE OPERATIONS TESTS
    // ==========================================

    #[Test]
    public function it_checks_cache_key_exists(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->with('test_key')
            ->andReturn(true);

        $result = $this->service->has('test_key');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_gets_cache_value(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('test_key', null)
            ->andReturn('cached_value');

        $result = $this->service->get('test_key');

        $this->assertEquals('cached_value', $result);
    }

    #[Test]
    public function it_puts_cache_value(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('test_key', 'value', 300)
            ->andReturn(true);

        $result = $this->service->put('test_key', 'value');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_stores_cache_forever(): void
    {
        Cache::shouldReceive('forever')
            ->once()
            ->with('test_key', 'value')
            ->andReturn(true);

        $result = $this->service->forever('test_key', 'value');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_increments_cache_value(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->with('counter', 1)
            ->andReturn(2);

        $result = $this->service->increment('counter');

        $this->assertEquals(2, $result);
    }

    #[Test]
    public function it_decrements_cache_value(): void
    {
        Cache::shouldReceive('decrement')
            ->once()
            ->with('counter', 1)
            ->andReturn(4);

        $result = $this->service->decrement('counter');

        $this->assertEquals(4, $result);
    }

    #[Test]
    public function it_adds_cache_value_if_not_exists(): void
    {
        Cache::shouldReceive('add')
            ->once()
            ->with('test_key', 'value', 300)
            ->andReturn(true);

        $result = $this->service->add('test_key', 'value');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_pulls_cache_value(): void
    {
        Cache::shouldReceive('pull')
            ->once()
            ->with('test_key', null)
            ->andReturn('cached_value');

        $result = $this->service->pull('test_key');

        $this->assertEquals('cached_value', $result);
    }

    // ==========================================
    // CACHE STATS TESTS
    // ==========================================

    #[Test]
    public function it_gets_cache_stats_for_redis(): void
    {
        config(['cache.default' => 'redis']);

        $stats = $this->service->getCacheStats();

        $this->assertEquals('redis', $stats['driver']);
        $this->assertTrue($stats['supported_features']['tagging']);
        $this->assertTrue($stats['supported_features']['persistence']);
        $this->assertTrue($stats['supported_features']['prefix_invalidation']);
    }

    #[Test]
    public function it_gets_cache_stats_for_file(): void
    {
        config(['cache.default' => 'file']);

        $stats = $this->service->getCacheStats();

        $this->assertEquals('file', $stats['driver']);
        $this->assertFalse($stats['supported_features']['tagging']);
        $this->assertTrue($stats['supported_features']['persistence']);
        $this->assertFalse($stats['supported_features']['prefix_invalidation']);
    }

    #[Test]
    public function it_gets_ttl_constants(): void
    {
        $constants = $this->service->getTTLConstants();

        $this->assertEquals(300, $constants['default']);
        $this->assertEquals(30, $constants['short']);
        $this->assertEquals(3600, $constants['long']);
    }

    // ==========================================
    // CACHE WARMUP TESTS
    // ==========================================

    #[Test]
    public function it_warms_up_cache(): void
    {
        Cache::shouldReceive('remember')
            ->twice()
            ->andReturn(['data' => 'test']);

        $result = $this->service->warmUpCache();

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['cached_items']);
    }

    #[Test]
    public function it_handles_warmup_failure(): void
    {
        \Log::shouldReceive('error')->once();

        Cache::shouldReceive('remember')
            ->andThrow(new \Exception('Cache error'));

        $result = $this->service->warmUpCache();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // COMPLETE CACHE CLEAR TESTS
    // ==========================================

    #[Test]
    public function it_clears_all_caches_complete(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('event:clear')
            ->once();

        Cache::shouldReceive('flush')
            ->once();

        Artisan::shouldReceive('call')
            ->with('optimize:clear')
            ->once();

        $result = $this->service->clearAllCachesComplete();

        $this->assertNotEmpty($result['cleared']);
        $this->assertContains('app', $result['cleared']);
        $this->assertContains('cache_store', $result['cleared']);
        $this->assertContains('optimize', $result['cleared']);
    }
}
