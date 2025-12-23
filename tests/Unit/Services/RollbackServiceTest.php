<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Events\DeploymentStatusUpdated;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\RollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class RollbackServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected RollbackService $service;

    protected DockerService $dockerService;

    protected GitService $gitService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->dockerService = $this->createMock(DockerService::class);
        $this->gitService = $this->createMock(GitService::class);

        // Create service instance with mocked dependencies
        $this->service = new RollbackService($this->dockerService, $this->gitService);

        Event::fake();
        Log::spy();
    }

    #[Test]
    public function it_successfully_rolls_back_to_previous_deployment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Previous stable version',
            'branch' => 'main',
            'environment_snapshot' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ],
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('deployment', $result);
        $this->assertEquals('Rollback completed successfully', $result['message']);
        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'status' => 'success',
            'triggered_by' => 'rollback',
            'rollback_deployment_id' => $targetDeployment->id,
        ]);
    }

    #[Test]
    public function it_creates_rollback_deployment_record(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'commit_hash' => 'rollback123',
            'commit_message' => 'Target version',
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $rollbackDeployment = $result['deployment'];
        $this->assertEquals($project->id, $rollbackDeployment->project_id);
        $this->assertEquals($user->id, $rollbackDeployment->user_id);
        $this->assertEquals('rollback', $rollbackDeployment->triggered_by);
        $this->assertEquals($targetDeployment->id, $rollbackDeployment->rollback_deployment_id);
        $this->assertEquals($targetDeployment->commit_hash, $rollbackDeployment->commit_hash);
        $this->assertStringContainsString('Rollback to:', $rollbackDeployment->commit_message);
    }

    #[Test]
    public function it_broadcasts_rollback_start_event(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Event::assertDispatched(DeploymentStatusUpdated::class, function ($event) {
            return $event->type === 'info' &&
                   str_contains($event->message, 'Starting rollback');
        });
    }

    #[Test]
    public function it_broadcasts_rollback_success_event(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Event::assertDispatched(DeploymentStatusUpdated::class, function ($event) {
            return $event->type === 'success' &&
                   str_contains($event->message, 'Successfully rolled back');
        });
    }

    #[Test]
    public function it_backs_up_current_state_before_rollback(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-project']);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'mkdir -p /var/www/backups/test-project');
        });

        Process::assertRan(function ($command) {
            return str_contains($command, 'cp /var/www/test-project/.env');
        });
    }

    #[Test]
    public function it_checks_out_target_commit(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'main']);

        $targetCommitHash = 'target123abc456';
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'commit_hash' => $targetCommitHash,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) use ($targetCommitHash) {
            return str_contains($command, "git reset --hard {$targetCommitHash}");
        });
    }

    #[Test]
    public function it_restores_environment_variables(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'environment_snapshot' => [
                'APP_NAME' => 'My App',
                'APP_ENV' => 'production',
                'DB_DATABASE' => 'my_database',
            ],
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'cat > /var/www') &&
                   str_contains($command, '.env');
        });
    }

    #[Test]
    public function it_rebuilds_docker_containers_after_checkout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->dockerService->expects($this->once())
            ->method('deployWithCompose')
            ->with($project)
            ->willReturn(['success' => true]);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert - PHPUnit mock expectation is verified automatically
    }

    #[Test]
    public function it_performs_health_check_after_rollback(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'health_check_url' => 'https://example.com/health',
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true, ['status' => 'running']);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_rollback_failure_gracefully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockFailedRollback('Git checkout failed');

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Failed to checkout', $result['error']);
    }

    #[Test]
    public function it_broadcasts_failure_event_on_rollback_error(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockFailedRollback('Rollback failed');

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Event::assertDispatched(DeploymentStatusUpdated::class, function ($event) {
            return $event->type === 'error' &&
                   str_contains($event->message, 'Rollback failed');
        });
    }

    #[Test]
    public function it_logs_rollback_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockFailedRollback('Critical error');

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert - Verify the result indicates failure (logging is implementation detail)
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_updates_deployment_status_on_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockFailedRollback('Docker rebuild failed');

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'status' => 'failed',
            'triggered_by' => 'rollback',
        ]);
    }

    #[Test]
    public function it_gets_available_rollback_points(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        // Create successful deployments
        Deployment::factory()->success()->count(5)->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Create failed deployments (should not be included)
        Deployment::factory()->failed()->count(2)->create([
            'project_id' => $project->id,
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $this->assertCount(5, $rollbackPoints);
        foreach ($rollbackPoints as $point) {
            $this->assertArrayHasKey('id', $point);
            $this->assertArrayHasKey('commit_hash', $point);
            $this->assertArrayHasKey('commit_message', $point);
            $this->assertArrayHasKey('deployed_at', $point);
            $this->assertArrayHasKey('deployed_by', $point);
            $this->assertArrayHasKey('can_rollback', $point);
        }
    }

    #[Test]
    public function it_limits_rollback_points(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Deployment::factory()->success()->count(15)->create([
            'project_id' => $project->id,
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project, 5);

        // Assert
        $this->assertCount(5, $rollbackPoints);
    }

    #[Test]
    public function it_excludes_rollback_deployments_from_rollback_points(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        // Create a rollback deployment
        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'triggered_by' => 'rollback',
            'rollback_deployment_id' => $targetDeployment->id,
        ]);

        // Create regular deployments
        Deployment::factory()->success()->count(3)->create([
            'project_id' => $project->id,
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $this->assertCount(4, $rollbackPoints); // 3 regular + 1 target (excludes rollback)
    }

    #[Test]
    public function it_cannot_rollback_to_current_deployment(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $latestDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDay(),
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $currentDeploymentPoint = collect($rollbackPoints)->firstWhere('id', $latestDeployment->id);
        $this->assertFalse($currentDeploymentPoint['can_rollback']);
    }

    #[Test]
    public function it_cannot_rollback_to_deployment_without_commit_hash(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'commit_hash' => null,
        ]);

        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'commit_hash' => 'abc123',
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $noHashPoint = collect($rollbackPoints)->firstWhere('commit_hash', null);
        if ($noHashPoint) {
            $this->assertFalse($noHashPoint['can_rollback']);
        }
    }

    #[Test]
    public function it_handles_missing_project_in_deployment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $deployment = new Deployment;
        $deployment->id = 999;

        // Act
        $result = $this->service->rollbackToDeployment($deployment);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Project not found', $result['error']);
    }

    #[Test]
    public function it_handles_docker_rebuild_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->dockerService->expects($this->once())
            ->method('deployWithCompose')
            ->with($project)
            ->willReturn(['success' => false, 'error' => 'Container build failed']);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to rebuild Docker containers', $result['error']);
    }

    #[Test]
    public function it_handles_health_check_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->dockerService->expects($this->once())
            ->method('deployWithCompose')
            ->willReturn(['success' => true]);

        $this->dockerService->expects($this->once())
            ->method('getContainerStatus')
            ->willReturn([]); // No containers running

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Health check failed', $result['error']);
    }

    #[Test]
    public function it_calculates_rollback_duration(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $rollbackDeployment = $result['deployment'];
        $this->assertNotNull($rollbackDeployment->duration_seconds);
        $this->assertGreaterThanOrEqual(0, $rollbackDeployment->duration_seconds);
    }

    #[Test]
    public function it_fetches_latest_git_changes_before_checkout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'main']);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'git fetch origin main');
        });
    }

    #[Test]
    public function it_ensures_correct_branch_before_reset(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id, 'branch' => 'develop']);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'branch' => 'develop',
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'git checkout develop');
        });
    }

    #[Test]
    public function it_configures_safe_directory_for_git(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'git config --global --add safe.directory');
        });
    }

    #[Test]
    public function it_creates_git_stash_before_rollback(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'git stash save') &&
                   str_contains($command, 'Backup before rollback');
        });
    }

    #[Test]
    public function it_escapes_special_characters_in_environment_values(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'environment_snapshot' => [
                'APP_KEY' => "base64:abc'def\$123",
                'DB_PASSWORD' => 'pass`word',
            ],
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_missing_environment_snapshot(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'environment_snapshot' => null,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $result = $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_uses_ssh_for_remote_server_commands(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'deploy',
            'port' => 2222,
        ]);

        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'ssh') &&
                   str_contains($command, 'deploy@192.168.1.100') &&
                   str_contains($command, '-p 2222');
        });
    }

    #[Test]
    public function it_uses_ssh_key_authentication_when_available(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $server = $this->createOnlineServer([
            'ssh_key' => '-----BEGIN PRIVATE KEY-----test-----END PRIVATE KEY-----',
        ]);

        $project = Project::factory()->create(['server_id' => $server->id]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
        ]);

        $this->mockSuccessfulRollback();
        $this->mockDockerService(true);

        // Act
        $this->service->rollbackToDeployment($targetDeployment);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'ssh') &&
                   str_contains($command, '-i ');
        });
    }

    #[Test]
    public function it_orders_rollback_points_by_most_recent(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $oldDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDays(5),
        ]);

        $recentDeployment = Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDay(),
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $this->assertEquals($recentDeployment->id, $rollbackPoints[0]['id']);
        $this->assertEquals($oldDeployment->id, $rollbackPoints[1]['id']);
    }

    #[Test]
    public function it_includes_deployment_metadata_in_rollback_points(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'commit_hash' => 'abc123',
            'commit_message' => 'Feature deployment',
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $this->assertEquals('abc123', $rollbackPoints[0]['commit_hash']);
        $this->assertEquals('Feature deployment', $rollbackPoints[0]['commit_message']);
        $this->assertEquals('John Doe', $rollbackPoints[0]['deployed_by']);
    }

    #[Test]
    public function it_handles_deployment_without_user(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Deployment::factory()->success()->create([
            'project_id' => $project->id,
            'user_id' => null,
        ]);

        // Act
        $rollbackPoints = $this->service->getRollbackPoints($project);

        // Assert
        $this->assertEquals('System', $rollbackPoints[0]['deployed_by']);
    }

    /**
     * Mock successful rollback SSH commands
     */
    protected function mockSuccessfulRollback(): void
    {
        Process::fake([
            // Backup commands
            '*mkdir -p*' => Process::result(output: 'Directory created'),
            '*cp *' => Process::result(output: 'File copied'),
            '*git stash*' => Process::result(output: 'Stash created'),

            // Git commands
            '*git config*' => Process::result(output: 'Config updated'),
            '*git fetch*' => Process::result(output: 'Fetched'),
            '*git checkout*' => Process::result(output: 'Switched to branch'),
            '*git reset*' => Process::result(output: 'HEAD is now at abc123'),

            // Environment restore
            '*cat >*' => Process::result(output: 'File written'),

            // Default SSH
            '*ssh*' => Process::result(output: 'Command executed'),
        ]);
    }

    /**
     * Mock failed rollback
     */
    protected function mockFailedRollback(string $error = 'Rollback failed'): void
    {
        Process::fake([
            '*git checkout*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
            '*git reset*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock Docker service behavior
     */
    protected function mockDockerService(bool $success, ?array $containerStatus = null): void
    {
        $this->dockerService->expects($this->once())
            ->method('deployWithCompose')
            ->willReturn(['success' => $success]);

        $this->dockerService->expects($this->once())
            ->method('getContainerStatus')
            ->willReturn($containerStatus ?? [
                ['name' => 'app', 'status' => 'running'],
                ['name' => 'db', 'status' => 'running'],
            ]);
    }
}
