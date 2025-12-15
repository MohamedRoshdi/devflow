<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\Services\DeploymentService;
use App\Services\DockerService;
use App\Services\GitService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DeploymentServiceTest extends TestCase
{
    

    private DeploymentService $service;
    private DockerService $dockerService;
    private GitService $gitService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dockerService = Mockery::mock(DockerService::class);
        $this->gitService = Mockery::mock(GitService::class);

        $this->service = new DeploymentService(
            $this->dockerService,
            $this->gitService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // DEPLOYMENT CREATION TESTS
    // ==========================================

    #[Test]
    public function it_creates_deployment_successfully(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['branch' => 'main']);

        $this->gitService->shouldReceive('getCurrentCommit')
            ->once()
            ->with($project)
            ->andReturn([
                'hash' => 'abc123',
                'message' => 'Test commit',
            ]);

        $this->actingAs($user);
        $deployment = $this->service->deploy($project, $user, 'manual');

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertEquals('pending', $deployment->status);
        $this->assertEquals('abc123', $deployment->commit_hash);
        $this->assertEquals('Test commit', $deployment->commit_message);
        $this->assertEquals($project->id, $deployment->project_id);
        $this->assertEquals($user->id, $deployment->user_id);

        Queue::assertPushed(DeployProjectJob::class);
    }

    #[Test]
    public function it_prevents_concurrent_deployments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Create an active deployment
        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('deployment is already in progress');

        $this->service->deploy($project, $user);
    }

    #[Test]
    public function it_captures_environment_snapshot(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'branch' => 'develop',
            'environment' => 'staging',
            'php_version' => '8.4',
            'framework' => 'laravel',
            'env_variables' => ['APP_ENV' => 'staging'],
        ]);

        $this->gitService->shouldReceive('getCurrentCommit')
            ->andReturn(['hash' => 'abc123', 'message' => 'Test']);

        $deployment = $this->service->deploy($project, $user);

        $this->assertIsArray($deployment->environment_snapshot);
        $this->assertEquals('develop', $deployment->environment_snapshot['branch']);
        $this->assertEquals('staging', $deployment->environment_snapshot['environment']);
        $this->assertEquals('8.4', $deployment->environment_snapshot['php_version']);
    }

    #[Test]
    public function it_uses_provided_commit_hash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create();

        $deployment = $this->service->deploy($project, $user, 'manual', 'custom123');

        $this->assertEquals('custom123', $deployment->commit_hash);
    }

    // ==========================================
    // ACTIVE DEPLOYMENT CHECKS
    // ==========================================

    #[Test]
    public function it_detects_active_deployment(): void
    {
        $project = Project::factory()->create();

        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        $this->assertTrue($this->service->hasActiveDeployment($project));
    }

    #[Test]
    public function it_detects_no_active_deployment(): void
    {
        $project = Project::factory()->create();

        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
        ]);

        $this->assertFalse($this->service->hasActiveDeployment($project));
    }

    #[Test]
    public function it_gets_active_deployment(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $activeDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
        ]);

        $result = $this->service->getActiveDeployment($project);

        $this->assertNotNull($result);
        $this->assertEquals($activeDeployment->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_no_active_deployment(): void
    {
        $project = Project::factory()->create();

        $result = $this->service->getActiveDeployment($project);

        $this->assertNull($result);
    }

    // ==========================================
    // DEPLOYMENT LOGS TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_deployment_logs(): void
    {
        $deployment = Deployment::factory()->create([
            'output_log' => 'Building project...\nDeployment successful',
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'duration_seconds' => 300,
        ]);

        $result = $this->service->getDeploymentLogs($deployment);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Building project', $result['logs']);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(300, $result['duration_seconds']);
    }

    #[Test]
    public function it_includes_error_logs_in_failed_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'output_log' => 'Starting deployment',
            'error_log' => 'Fatal error occurred',
            'status' => 'failed',
        ]);

        $result = $this->service->getDeploymentLogs($deployment);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Starting deployment', $result['logs']);
        $this->assertStringContainsString('Fatal error occurred', $result['logs']);
        $this->assertStringContainsString('ERRORS', $result['logs']);
    }

    // ==========================================
    // DEPLOYMENT CANCELLATION TESTS
    // ==========================================

    #[Test]
    public function it_cancels_pending_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'status' => 'pending',
            'started_at' => now(),
        ]);

        $result = $this->service->cancelDeployment($deployment);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $deployment->fresh()->status);
        $this->assertNotNull($deployment->fresh()->completed_at);
    }

    #[Test]
    public function it_cannot_cancel_completed_deployment(): void
    {
        Log::shouldReceive('warning')->once();

        $deployment = Deployment::factory()->create([
            'status' => 'success',
        ]);

        $result = $this->service->cancelDeployment($deployment);

        $this->assertFalse($result);
        $this->assertEquals('success', $deployment->fresh()->status);
    }

    // ==========================================
    // ROLLBACK TESTS
    // ==========================================

    #[Test]
    public function it_creates_rollback_deployment(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $targetDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
            'commit_message' => 'Original deployment',
            'branch' => 'main',
        ]);

        $rollback = $this->service->rollback($project, $targetDeployment, $user);

        $this->assertEquals('abc123', $rollback->commit_hash);
        $this->assertEquals('rollback', $rollback->triggered_by);
        $this->assertEquals($targetDeployment->id, $rollback->rollback_deployment_id);
        $this->assertStringContainsString('Rollback to:', $rollback->commit_message);

        Queue::assertPushed(DeployProjectJob::class);
    }

    #[Test]
    public function it_prevents_rollback_to_failed_deployment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $targetDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'failed',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can only rollback to successful deployments');

        $this->service->rollback($project, $targetDeployment, $user);
    }

    #[Test]
    public function it_prevents_rollback_to_different_project(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $targetDeployment = Deployment::factory()->create([
            'project_id' => $project2->id,
            'status' => 'success',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not belong to this project');

        $this->service->rollback($project1, $targetDeployment, $user);
    }

    #[Test]
    public function it_prevents_rollback_during_active_deployment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        $targetDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot rollback while another deployment is in progress');

        $this->service->rollback($project, $targetDeployment, $user);
    }

    // ==========================================
    // BATCH DEPLOYMENT TESTS
    // ==========================================

    #[Test]
    public function it_batch_deploys_multiple_projects(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create();

        $this->gitService->shouldReceive('getCurrentCommit')
            ->times(3)
            ->andReturn(['hash' => 'abc123', 'message' => 'Test']);

        $result = $this->service->batchDeploy($projects->all(), $user);

        $this->assertEquals(3, $result['successful']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(3, $result['deployments']);
    }

    #[Test]
    public function it_skips_projects_without_server(): void
    {
        Log::shouldReceive('warning')->once();

        $user = User::factory()->create();
        $project = Project::factory()->create(['server_id' => null]);

        $result = $this->service->batchDeploy([$project], $user);

        $this->assertEquals(0, $result['successful']);
        $this->assertEquals(1, $result['failed']);
    }

    #[Test]
    public function it_skips_projects_with_active_deployments(): void
    {
        Log::shouldReceive('warning')->once();

        $user = User::factory()->create();
        $project = Project::factory()->create();

        Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        $result = $this->service->batchDeploy([$project], $user);

        $this->assertEquals(0, $result['successful']);
        $this->assertEquals(1, $result['failed']);
    }

    // ==========================================
    // DEPLOYMENT STATISTICS TESTS
    // ==========================================

    #[Test]
    public function it_calculates_deployment_stats(): void
    {
        $project = Project::factory()->create();

        Deployment::factory()->count(7)->create([
            'project_id' => $project->id,
            'status' => 'success',
            'duration_seconds' => 120,
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'status' => 'failed',
            'duration_seconds' => null,
        ]);

        $stats = $this->service->getDeploymentStats($project, 30);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(7, $stats['successful']);
        $this->assertEquals(3, $stats['failed']);
        $this->assertEquals(70.0, $stats['success_rate']);
        $this->assertEquals(120.0, $stats['avg_duration']);
    }

    #[Test]
    public function it_handles_no_deployments_in_stats(): void
    {
        $project = Project::factory()->create();

        $stats = $this->service->getDeploymentStats($project, 30);

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['successful']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['success_rate']);
        $this->assertEquals(0, $stats['avg_duration']);
    }

    // ==========================================
    // UPDATE CHECK TESTS
    // ==========================================

    #[Test]
    public function it_checks_for_available_updates(): void
    {
        $project = Project::factory()->create();

        $this->gitService->shouldReceive('checkForUpdates')
            ->once()
            ->with($project)
            ->andReturn([
                'success' => true,
                'up_to_date' => false,
                'commits_behind' => 5,
                'local_commit' => 'abc123',
                'remote_commit' => 'def456',
            ]);

        $result = $this->service->checkForUpdates($project);

        $this->assertTrue($result['has_updates']);
        $this->assertEquals(5, $result['commits_behind']);
        $this->assertEquals('abc123', $result['local_commit']);
        $this->assertEquals('def456', $result['remote_commit']);
    }

    #[Test]
    public function it_handles_update_check_failure(): void
    {
        $project = Project::factory()->create();

        $this->gitService->shouldReceive('checkForUpdates')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Git error',
            ]);

        $result = $this->service->checkForUpdates($project);

        $this->assertFalse($result['has_updates']);
        $this->assertEquals(0, $result['commits_behind']);
        $this->assertEquals('Git error', $result['error']);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    #[Test]
    public function it_validates_deployment_prerequisites(): void
    {
        $server = \App\Models\Server::factory()->create(['status' => 'online']);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
        ]);

        $result = $this->service->validateDeploymentPrerequisites($project);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    #[Test]
    public function it_detects_missing_server(): void
    {
        $project = Project::factory()->create([
            'server_id' => null,
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
        ]);

        $result = $this->service->validateDeploymentPrerequisites($project);

        $this->assertFalse($result['valid']);
        $this->assertContains('Project does not have a server assigned', $result['errors']);
    }

    #[Test]
    public function it_detects_offline_server(): void
    {
        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/test/repo',
            'branch' => 'main',
        ]);

        $project->server()->associate(
            \App\Models\Server::factory()->create(['status' => 'offline'])
        );
        $project->save();

        $result = $this->service->validateDeploymentPrerequisites($project);

        $this->assertFalse($result['valid']);
        $this->assertContains('Server is not online', $result['errors']);
    }

    // ==========================================
    // RECENT DEPLOYMENTS TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_recent_deployments(): void
    {
        $project = Project::factory()->create();

        Deployment::factory()->count(15)->create([
            'project_id' => $project->id,
        ]);

        $recent = $this->service->getRecentDeployments($project, 10);

        $this->assertCount(10, $recent);
    }

    // ==========================================
    // MANUAL STATUS CHANGE TESTS
    // ==========================================

    #[Test]
    public function it_marks_deployment_as_success(): void
    {
        Log::shouldReceive('info')->once();

        $deployment = Deployment::factory()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $result = $this->service->markAsSuccess($deployment);

        $this->assertTrue($result);
        $this->assertEquals('success', $deployment->fresh()->status);
        $this->assertNotNull($deployment->fresh()->completed_at);
        $this->assertNotNull($deployment->fresh()->duration_seconds);
    }

    #[Test]
    public function it_marks_deployment_as_failed(): void
    {
        Log::shouldReceive('info')->once();

        $deployment = Deployment::factory()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $result = $this->service->markAsFailed($deployment, 'Custom error message');

        $this->assertTrue($result);
        $this->assertEquals('failed', $deployment->fresh()->status);
        $this->assertEquals('Custom error message', $deployment->fresh()->error_log);
        $this->assertNotNull($deployment->fresh()->completed_at);
    }
}
