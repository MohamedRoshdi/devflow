<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\DockerService;
use App\Services\Health\ProjectHealthScorer;
use App\Services\ProjectHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProjectHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectHealthService $service;
    private DockerService $dockerService;
    private ProjectHealthScorer $healthScorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->healthScorer = new ProjectHealthScorer();
        $this->service = new ProjectHealthService($this->dockerService, $this->healthScorer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // PROJECT HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_all_projects(): void
    {
        Cache::shouldReceive('remember')
            ->times(3)
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Project::factory()->count(3)->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->times(3)
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'Up 2 hours'],
            ]);

        $results = $this->service->checkAllProjects();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_checks_single_project_health(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'Up 2 hours'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('health_score', $health);
        $this->assertArrayHasKey('issues', $health);
    }

    /** @test */
    public function it_identifies_healthy_project(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'status' => 'running',
        ]);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 50.0,
        ]);

        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'running'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('healthy', $health['status']);
        $this->assertGreaterThanOrEqual(80, $health['health_score']);
    }

    /** @test */
    public function it_identifies_unhealthy_project(): void
    {
        $project = Project::factory()->create(['status' => 'stopped']);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn([
                'success' => true,
                'exists' => false,
            ]);

        $health = $this->service->checkProject($project);

        $this->assertNotEquals('healthy', $health['status']);
        $this->assertLessThan(80, $health['health_score']);
    }

    // ==========================================
    // HTTP HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_http_health_successfully(): void
    {
        $project = Project::factory()->create([
            'health_check_url' => 'https://example.com/health',
        ]);

        Http::fake([
            'https://example.com/health' => Http::response('OK', 200),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('healthy', $health['checks']['http']['status']);
        $this->assertEquals(200, $health['checks']['http']['http_code']);
        $this->assertNotNull($health['checks']['http']['response_time']);
    }

    /** @test */
    public function it_detects_http_failure(): void
    {
        $project = Project::factory()->create([
            'health_check_url' => 'https://example.com/health',
        ]);

        Http::fake([
            'https://example.com/health' => Http::response('Error', 500),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('unhealthy', $health['checks']['http']['status']);
        $this->assertEquals(500, $health['checks']['http']['http_code']);
    }

    /** @test */
    public function it_handles_http_timeout(): void
    {
        $project = Project::factory()->create([
            'health_check_url' => 'https://example.com/health',
        ]);

        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('unreachable', $health['checks']['http']['status']);
        $this->assertNotNull($health['checks']['http']['error']);
    }

    /** @test */
    public function it_uses_primary_domain_for_health_check(): void
    {
        $project = Project::factory()->create([
            'health_check_url' => null,
        ]);

        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'full_domain' => 'example.com',
        ]);

        Http::fake(['*' => Http::response('OK', 200)]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertNotNull($health['checks']['http']);
    }

    // ==========================================
    // SSL HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_ssl_health_with_no_domains(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('unknown', $health['checks']['ssl']['status']);
        $this->assertFalse($health['checks']['ssl']['valid']);
    }

    /** @test */
    public function it_detects_expiring_ssl_certificate(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(5),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertNotEquals('healthy', $health['status']);
    }

    // ==========================================
    // DOCKER HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_running_docker_container(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'running'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('running', $health['checks']['docker']['status']);
        $this->assertTrue($health['checks']['docker']['running']);
    }

    /** @test */
    public function it_detects_stopped_docker_container(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'exited'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('stopped', $health['checks']['docker']['status']);
        $this->assertFalse($health['checks']['docker']['running']);
    }

    /** @test */
    public function it_handles_docker_check_failure(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn([
                'success' => false,
                'error' => 'Connection failed',
            ]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('error', $health['checks']['docker']['status']);
        $this->assertFalse($health['checks']['docker']['running']);
    }

    // ==========================================
    // DISK USAGE CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_disk_usage_from_server_metrics(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 60.0,
            'disk_used_gb' => 120,
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('healthy', $health['checks']['disk']['status']);
        $this->assertEquals(60, $health['checks']['disk']['usage_percent']);
    }

    /** @test */
    public function it_detects_critical_disk_usage(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 95.0,
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('critical', $health['checks']['disk']['status']);
    }

    /** @test */
    public function it_detects_warning_disk_usage(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 80.0,
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('warning', $health['checks']['disk']['status']);
    }

    // ==========================================
    // DEPLOYMENT HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_successful_deployment(): void
    {
        $project = Project::factory()->create();

        \App\Models\Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('healthy', $health['checks']['deployment']['status']);
        $this->assertEquals('success', $health['checks']['deployment']['last_status']);
    }

    /** @test */
    public function it_detects_failed_deployment(): void
    {
        $project = Project::factory()->create();

        \App\Models\Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'failed',
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('failed', $health['checks']['deployment']['status']);
        $this->assertNotNull($health['checks']['deployment']['error']);
    }

    /** @test */
    public function it_handles_no_deployments(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $health = $this->service->checkProject($project);

        $this->assertEquals('none', $health['checks']['deployment']['status']);
    }

    // ==========================================
    // SERVER HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_server_health(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 60.0,
            'disk_usage' => 55.0,
        ]);

        $this->dockerService->shouldReceive('checkDockerInstallation')
            ->once()
            ->andReturn(['installed' => true, 'version' => '24.0.5']);

        $health = $this->service->checkServerHealth($server);

        $this->assertEquals('healthy', $health['status']);
        $this->assertGreaterThanOrEqual(80, $health['health_score']);
    }

    /** @test */
    public function it_detects_offline_server(): void
    {
        $server = Server::factory()->create(['status' => 'offline']);

        $this->dockerService->shouldReceive('checkDockerInstallation')
            ->andReturn(['installed' => false]);

        $health = $this->service->checkServerHealth($server);

        $this->assertNotEquals('healthy', $health['status']);
        $this->assertContains('Server is offline', $health['issues']);
    }

    /** @test */
    public function it_detects_critical_server_resources(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
            'memory_usage' => 92.0,
            'disk_usage' => 93.0,
        ]);

        $this->dockerService->shouldReceive('checkDockerInstallation')
            ->andReturn(['installed' => true]);

        $health = $this->service->checkServerHealth($server);

        $this->assertEquals('critical', $health['status']);
        $this->assertNotEmpty($health['issues']);
    }

    /** @test */
    public function it_detects_missing_docker_installation(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $this->dockerService->shouldReceive('checkDockerInstallation')
            ->andReturn(['installed' => false]);

        $health = $this->service->checkServerHealth($server);

        $this->assertContains('Docker is not installed', $health['issues']);
    }

    // ==========================================
    // CACHE INVALIDATION TESTS
    // ==========================================

    /** @test */
    public function it_invalidates_project_cache(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('forget')
            ->once()
            ->with("project_health_{$project->id}");

        $this->service->invalidateProjectCache($project);

        $this->assertTrue(true); // Assert called without exception
    }

    /** @test */
    public function it_invalidates_all_project_caches(): void
    {
        Project::factory()->count(3)->create();

        Cache::shouldReceive('forget')
            ->times(3);

        $this->service->invalidateAllProjectCaches();

        $this->assertTrue(true); // Assert called without exception
    }

    // ==========================================
    // HEALTH SCORE CALCULATION TESTS
    // ==========================================

    /** @test */
    public function it_calculates_perfect_health_score(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'status' => 'running',
        ]);

        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'disk_usage' => 30.0,
        ]);

        Http::fake(['*' => Http::response('OK', 200)]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'running'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertEquals(100, $health['health_score']);
    }

    /** @test */
    public function it_deducts_points_for_slow_response(): void
    {
        $project = Project::factory()->create([
            'health_check_url' => 'https://example.com',
            'status' => 'running',
        ]);

        // Simulate slow response
        Http::fake([
            '*' => Http::response('OK', 200)->delay(3000),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'running'],
            ]);

        $health = $this->service->checkProject($project);

        $this->assertLessThan(100, $health['health_score']);
    }
}
