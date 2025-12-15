<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\LogAggregationService;
use App\Services\ProjectManager\ProjectManagerService;
use App\Services\RollbackService;
use App\Services\SSLService;
use App\Services\StorageService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProjectManagerServiceTest extends TestCase
{
    

    private ProjectManagerService $service;
    private DockerService $dockerService;
    private GitService $gitService;
    private SSLService $sslService;
    private LogAggregationService $logManager;
    private StorageService $storageService;
    private RollbackService $rollbackService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->gitService = Mockery::mock(GitService::class);
        $this->sslService = Mockery::mock(SSLService::class);
        $this->logManager = Mockery::mock(LogAggregationService::class);
        $this->storageService = Mockery::mock(StorageService::class);
        $this->rollbackService = Mockery::mock(RollbackService::class);

        $this->service = new ProjectManagerService(
            $this->dockerService,
            $this->gitService,
            $this->sslService,
            $this->logManager,
            $this->storageService,
            $this->rollbackService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // PROJECT CREATION TESTS
    // ==========================================

    #[Test]
    public function it_creates_project_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = \App\Models\Server::factory()->create();
        $projectData = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
            'framework' => 'laravel',
        ];

        $project = $this->service->createProject($projectData);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('test-project', $project->slug);
        $this->assertEquals('completed', $project->setup_status);
        $this->assertNotNull($project->setup_completed_at);
    }

    #[Test]
    public function it_initializes_project_storage(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = \App\Models\Server::factory()->create();
        $projectData = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
        ];

        $project = $this->service->createProject($projectData);

        $this->assertEquals(0, $project->storage_used_mb);
    }

    #[Test]
    public function it_handles_project_creation_failure(): void
    {
        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create project');

        // Missing required fields should cause failure
        $this->service->createProject([]);
    }

    #[Test]
    public function it_rolls_back_on_project_creation_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $initialCount = Project::count();

        try {
            $this->service->createProject([
                'name' => 'Test',
                'slug' => null, // Will cause validation error
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals($initialCount, Project::count());
    }

    // ==========================================
    // DEPLOYMENT TESTS
    // ==========================================

    #[Test]
    public function it_deploys_project_successfully(): void
    {
        Log::shouldReceive('info')->times(3);

        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create([
            'branch' => 'main',
        ]);

        $this->gitService->shouldReceive('getCurrentCommit')
            ->once()
            ->andReturn([
                'hash' => 'abc123',
                'message' => 'Test commit',
            ]);

        $this->dockerService->shouldReceive('usesDockerCompose')
            ->once()
            ->andReturn(true);

        $this->dockerService->shouldReceive('deployWithCompose')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'Deployment successful',
            ]);

        $this->gitService->shouldReceive('updateProjectCommitInfo')
            ->once();

        $deployment = $this->service->deployProject($project);

        $this->assertEquals('success', $deployment->status);
        $this->assertEquals('abc123', $deployment->commit_hash);
        $this->assertEquals('running', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->last_deployed_at);
    }

    #[Test]
    public function it_prevents_concurrent_deployments(): void
    {
        $project = Project::factory()->create();

        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Deployment already in progress');

        $this->service->deployProject($project);
    }

    #[Test]
    public function it_handles_deployment_failure(): void
    {
        Log::shouldReceive('info')->times(2);
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();

        $this->gitService->shouldReceive('getCurrentCommit')
            ->andReturn(['hash' => 'abc123', 'message' => 'Test']);

        $this->dockerService->shouldReceive('usesDockerCompose')
            ->andReturn(true);

        $this->dockerService->shouldReceive('deployWithCompose')
            ->andReturn([
                'success' => false,
                'error' => 'Docker deployment failed',
            ]);

        $this->expectException(\Exception::class);

        $this->service->deployProject($project);
    }

    #[Test]
    public function it_deploys_standalone_container(): void
    {
        Log::shouldReceive('info')->times(3);

        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();

        $this->gitService->shouldReceive('getCurrentCommit')
            ->andReturn(['hash' => 'abc123', 'message' => 'Test']);

        $this->dockerService->shouldReceive('usesDockerCompose')
            ->andReturn(false);

        $this->dockerService->shouldReceive('buildContainer')
            ->once()
            ->andReturn(['success' => true, 'output' => 'Built']);

        $this->dockerService->shouldReceive('startContainer')
            ->once()
            ->andReturn(['success' => true, 'output' => 'Started']);

        $this->gitService->shouldReceive('updateProjectCommitInfo')
            ->once();

        $deployment = $this->service->deployProject($project);

        $this->assertEquals('success', $deployment->status);
    }

    // ==========================================
    // PROJECT HEALTH TESTS
    // ==========================================

    #[Test]
    public function it_gets_project_health_successfully(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'exists' => true,
                'container' => ['State' => 'Up 2 hours'],
            ]);

        $this->dockerService->shouldReceive('getContainerLogs')
            ->once()
            ->andReturn([
                'success' => true,
                'logs' => 'Application running',
            ]);

        $health = $this->service->getProjectHealth($project);

        $this->assertEquals('healthy', $health['overall_status']);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('containers', $health['checks']);
        $this->assertTrue($health['checks']['containers']['healthy']);
    }

    #[Test]
    public function it_detects_unhealthy_containers(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'exists' => false,
            ]);

        $this->dockerService->shouldReceive('getContainerLogs')
            ->once()
            ->andReturn(['success' => true, 'logs' => '']);

        $health = $this->service->getProjectHealth($project);

        $this->assertEquals('unhealthy', $health['overall_status']);
        $this->assertFalse($health['checks']['containers']['healthy']);
    }

    #[Test]
    public function it_detects_ssl_expiry_warnings(): void
    {
        $project = Project::factory()->create();

        $domain = \App\Models\Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(15),
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $this->dockerService->shouldReceive('getContainerLogs')
            ->andReturn(['success' => true, 'logs' => '']);

        $health = $this->service->getProjectHealth($project);

        $this->assertEquals('warning', $health['overall_status']);
        $this->assertNotEmpty($health['checks']['domains']['ssl_issues']);
    }

    #[Test]
    public function it_detects_storage_warnings(): void
    {
        $project = Project::factory()->create([
            'storage_used_mb' => 6000, // Over 5GB threshold
        ]);

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $this->dockerService->shouldReceive('getContainerLogs')
            ->andReturn(['success' => true, 'logs' => '']);

        $health = $this->service->getProjectHealth($project);

        $this->assertTrue($health['checks']['storage']['warning']);
        $this->assertEquals('warning', $health['overall_status']);
    }

    #[Test]
    public function it_extracts_recent_errors_from_logs(): void
    {
        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('getContainerStatus')
            ->andReturn(['success' => true, 'exists' => true, 'container' => ['State' => 'Up']]);

        $this->dockerService->shouldReceive('getContainerLogs')
            ->andReturn([
                'success' => true,
                'logs' => "INFO: Starting\nERROR: Database connection failed\nFATAL: Out of memory",
            ]);

        $health = $this->service->getProjectHealth($project);

        $this->assertNotEmpty($health['recent_errors']);
        $this->assertCount(2, $health['recent_errors']);
    }

    // ==========================================
    // PROJECT CLEANUP TESTS
    // ==========================================

    #[Test]
    public function it_cleans_up_project_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $project = Project::factory()->create(['framework' => 'laravel']);

        $this->dockerService->shouldReceive('execInContainer')
            ->times(4)
            ->andReturn(['success' => true, 'output' => 'Cache cleared']);

        $this->dockerService->shouldReceive('clearLaravelLogs')
            ->once()
            ->andReturn(['success' => true]);

        $this->storageService->shouldReceive('cleanupProjectStorage')
            ->once()
            ->andReturn(['success' => true, 'freed_mb' => 100]);

        $this->storageService->shouldReceive('calculateProjectStorage')
            ->once();

        $result = $this->service->cleanupProject($project);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('operations', $result);
    }

    #[Test]
    public function it_handles_cleanup_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $project = Project::factory()->create(['framework' => 'laravel']);

        $this->dockerService->shouldReceive('execInContainer')
            ->andThrow(new \Exception('Container not running'));

        $result = $this->service->cleanupProject($project);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // PROJECT START/STOP TESTS
    // ==========================================

    #[Test]
    public function it_stops_project_successfully(): void
    {
        Log::shouldReceive('info')->once();

        $project = Project::factory()->create(['status' => 'running']);

        $this->dockerService->shouldReceive('stopContainer')
            ->once()
            ->with($project)
            ->andReturn(['success' => true, 'message' => 'Stopped']);

        $result = $this->service->stopProject($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('stopped', $project->fresh()->status);
    }

    #[Test]
    public function it_starts_project_successfully(): void
    {
        Log::shouldReceive('info')->once();

        $project = Project::factory()->create(['status' => 'stopped']);

        $this->dockerService->shouldReceive('startContainer')
            ->once()
            ->with($project)
            ->andReturn(['success' => true, 'message' => 'Started']);

        $result = $this->service->startProject($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('running', $project->fresh()->status);
    }

    #[Test]
    public function it_restarts_project_successfully(): void
    {
        Log::shouldReceive('info')->once();

        $project = Project::factory()->create(['status' => 'running']);

        $this->dockerService->shouldReceive('stopContainer')
            ->once()
            ->andReturn(['success' => true]);

        $this->dockerService->shouldReceive('startContainer')
            ->once()
            ->andReturn(['success' => true]);

        $result = $this->service->restartProject($project);

        $this->assertTrue($result['success']);
        $this->assertEquals('running', $project->fresh()->status);
    }

    #[Test]
    public function it_handles_restart_failure_on_stop(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $project = Project::factory()->create();

        $this->dockerService->shouldReceive('stopContainer')
            ->andThrow(new \Exception('Stop failed'));

        $result = $this->service->restartProject($project);

        $this->assertFalse($result['success']);
    }

    // ==========================================
    // ROLLBACK TESTS
    // ==========================================

    #[Test]
    public function it_performs_rollback_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
        ]);

        $newDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
        ]);

        $this->rollbackService->shouldReceive('rollbackToDeployment')
            ->once()
            ->with($deployment)
            ->andReturn([
                'success' => true,
                'deployment' => $newDeployment,
            ]);

        $result = $this->service->rollbackProject($project, $deployment->id);

        $this->assertEquals($newDeployment->id, $result->id);
    }

    #[Test]
    public function it_prevents_rollback_to_different_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project2->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not belong to this project');

        $this->service->rollbackProject($project1, $deployment->id);
    }

    #[Test]
    public function it_handles_rollback_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->rollbackService->shouldReceive('rollbackToDeployment')
            ->andReturn([
                'success' => false,
                'error' => 'Rollback failed',
            ]);

        $this->expectException(\Exception::class);

        $this->service->rollbackProject($project, $deployment->id);
    }

    #[Test]
    public function it_gets_rollback_points(): void
    {
        $project = Project::factory()->create();

        $this->rollbackService->shouldReceive('getRollbackPoints')
            ->once()
            ->with($project, 10)
            ->andReturn([
                ['id' => 1, 'commit_hash' => 'abc123'],
                ['id' => 2, 'commit_hash' => 'def456'],
            ]);

        $points = $this->service->getRollbackPoints($project, 10);

        $this->assertCount(2, $points);
    }
}
