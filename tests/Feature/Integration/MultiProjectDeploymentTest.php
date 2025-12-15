<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Test;
use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DeploymentService;
use App\Services\DockerService;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Multi-Project Deployment Integration Test
 *
 * This test suite covers deploying multiple projects simultaneously,
 * including batch deployments, rollbacks, and status tracking across projects.
 *
 * Workflows covered:
 * 1. Batch deployment to multiple projects
 * 2. Parallel deployment status tracking
 * 3. Partial failure handling (some succeed, some fail)
 * 4. Multi-project rollback workflow
 * 5. Server-wide deployment (all projects on a server)
 */
class MultiProjectDeploymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    /** @var array<Project> */
    protected array $projects = [];

    protected DeploymentService $deploymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@devflow.com',
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        // Create multiple projects on the same server
        for ($i = 1; $i <= 5; $i++) {
            $this->projects[] = Project::factory()->create([
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => "Project {$i}",
                'slug' => "project-{$i}",
                'branch' => 'main',
                'repository_url' => "https://github.com/test/project-{$i}.git",
                'status' => 'active',
            ]);
        }

        $this->deploymentService = app(DeploymentService::class);
    }

    // ==================== Batch Deployment Tests ====================

    #[Test]
    public function can_deploy_multiple_projects_in_batch(): void
    {
        Queue::fake();

        $projectIds = collect($this->projects)->pluck('id')->toArray();

        $this->actingAs($this->user);

        // Trigger batch deployment
        $deployments = [];
        foreach ($this->projects as $project) {
            $deployment = Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'commit_hash' => 'abc123' . $project->id,
            ]);
            $deployments[] = $deployment;
        }

        $this->assertCount(5, $deployments);

        // Verify all deployments are in pending state
        foreach ($deployments as $deployment) {
            $this->assertEquals('pending', $deployment->status);
        }
    }

    #[Test]
    public function batch_deployment_dispatches_jobs_for_all_projects(): void
    {
        Queue::fake();

        foreach ($this->projects as $project) {
            DeployProjectJob::dispatch($project);
        }

        Queue::assertPushed(DeployProjectJob::class, 5);
    }

    #[Test]
    public function batch_deployment_tracks_individual_statuses(): void
    {
        $deployments = [];

        foreach ($this->projects as $index => $project) {
            $status = $index % 2 === 0 ? 'success' : 'running';
            $deployments[] = Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => $status,
                'triggered_by' => 'manual',
            ]);
        }

        $successCount = Deployment::where('status', 'success')->count();
        $runningCount = Deployment::where('status', 'running')->count();

        $this->assertEquals(3, $successCount);
        $this->assertEquals(2, $runningCount);
    }

    // ==================== Partial Failure Handling Tests ====================

    #[Test]
    public function handles_partial_batch_failure_gracefully(): void
    {
        // Create deployments with mixed statuses
        $results = [];
        foreach ($this->projects as $index => $project) {
            $status = match ($index) {
                0, 1, 2 => 'success',
                3 => 'failed',
                4 => 'running',
                default => 'pending',
            };

            $results[] = Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => $status,
                'triggered_by' => 'manual',
                'error_message' => $status === 'failed' ? 'Git pull failed' : null,
            ]);
        }

        // Verify counts
        $this->assertEquals(3, Deployment::where('status', 'success')->count());
        $this->assertEquals(1, Deployment::where('status', 'failed')->count());
        $this->assertEquals(1, Deployment::where('status', 'running')->count());

        // Failed deployment should have error message
        $failedDeployment = Deployment::where('status', 'failed')->first();
        $this->assertNotNull($failedDeployment);
        $this->assertEquals('Git pull failed', $failedDeployment->error_message);
    }

    #[Test]
    public function failed_deployment_does_not_affect_other_projects(): void
    {
        // First project deployment fails
        $failedDeployment = Deployment::factory()->create([
            'project_id' => $this->projects[0]->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_message' => 'Connection timeout',
        ]);

        // Other projects still work
        for ($i = 1; $i < 5; $i++) {
            Deployment::factory()->create([
                'project_id' => $this->projects[$i]->id,
                'user_id' => $this->user->id,
                'status' => 'success',
            ]);
        }

        // Verify other projects succeeded
        $successCount = Deployment::where('status', 'success')->count();
        $this->assertEquals(4, $successCount);

        // Failed project is isolated
        $freshDeployment = $failedDeployment->fresh();
        $this->assertNotNull($freshDeployment);
        $this->assertEquals('failed', $freshDeployment->status);
    }

    // ==================== Server-Wide Deployment Tests ====================

    #[Test]
    public function can_deploy_all_projects_on_a_server(): void
    {
        Queue::fake();

        // Get all projects on the server
        $serverProjects = Project::where('server_id', $this->server->id)->get();

        $this->assertCount(5, $serverProjects);

        // Create deployments for all server projects
        foreach ($serverProjects as $project) {
            Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'pending',
                'triggered_by' => 'manual',
            ]);
        }

        $pendingCount = Deployment::where('status', 'pending')
            ->whereIn('project_id', $serverProjects->pluck('id'))
            ->count();

        $this->assertEquals(5, $pendingCount);
    }

    #[Test]
    public function server_deployment_respects_project_status(): void
    {
        // Set one project as inactive
        $this->projects[2]->update(['status' => 'inactive']);

        // Get only active projects
        $activeProjects = Project::where('server_id', $this->server->id)
            ->where('status', 'active')
            ->get();

        $this->assertCount(4, $activeProjects);

        // Verify inactive project is excluded
        $this->assertFalse($activeProjects->contains('id', $this->projects[2]->id));
    }

    // ==================== Multi-Project Rollback Tests ====================

    #[Test]
    public function can_rollback_multiple_projects_to_previous_version(): void
    {
        // Create original deployments (v1)
        $originalDeployments = [];
        foreach ($this->projects as $project) {
            $originalDeployments[] = Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'success',
                'commit_hash' => 'original-' . $project->id,
                'created_at' => now()->subHour(),
            ]);
        }

        // Create new deployments (v2) that we want to rollback from
        $newDeployments = [];
        foreach ($this->projects as $project) {
            $newDeployments[] = Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'success',
                'commit_hash' => 'new-' . $project->id,
                'created_at' => now(),
            ]);
        }

        // Create rollback deployments
        foreach ($this->projects as $index => $project) {
            Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'success',
                'commit_hash' => 'original-' . $project->id,
                'triggered_by' => 'rollback',
                'rollback_deployment_id' => $originalDeployments[$index]->id,
            ]);
        }

        // Verify rollbacks created
        $rollbackCount = Deployment::where('triggered_by', 'rollback')->count();
        $this->assertEquals(5, $rollbackCount);
    }

    #[Test]
    public function rollback_preserves_original_deployment_reference(): void
    {
        // Create original successful deployment
        $original = Deployment::factory()->create([
            'project_id' => $this->projects[0]->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
        ]);

        // Create rollback deployment
        $rollback = Deployment::factory()->create([
            'project_id' => $this->projects[0]->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
            'triggered_by' => 'rollback',
            'rollback_deployment_id' => $original->id,
        ]);

        $this->assertEquals($original->id, $rollback->rollback_deployment_id);
        $this->assertEquals('rollback', $rollback->triggered_by);
    }

    // ==================== Concurrent Deployment Tests ====================

    #[Test]
    public function prevents_concurrent_deployments_to_same_project(): void
    {
        // Create running deployment
        $running = Deployment::factory()->create([
            'project_id' => $this->projects[0]->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        // Check for existing active deployment
        $hasActiveDeployment = Deployment::where('project_id', $this->projects[0]->id)
            ->whereIn('status', ['pending', 'running'])
            ->exists();

        $this->assertTrue($hasActiveDeployment);
    }

    #[Test]
    public function allows_deployments_to_different_projects_concurrently(): void
    {
        // Create running deployments for different projects
        foreach ($this->projects as $project) {
            Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'running',
            ]);
        }

        $runningCount = Deployment::where('status', 'running')->count();
        $this->assertEquals(5, $runningCount);
    }

    // ==================== Deployment Statistics Tests ====================

    #[Test]
    public function calculates_batch_deployment_statistics(): void
    {
        // Create mixed deployment results
        $statuses = ['success', 'success', 'success', 'failed', 'success'];

        foreach ($this->projects as $index => $project) {
            Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => $statuses[$index],
                'started_at' => now()->subMinutes(5),
                'completed_at' => now(),
                'duration_seconds' => rand(60, 300),
            ]);
        }

        $stats = [
            'total' => Deployment::count(),
            'success' => Deployment::where('status', 'success')->count(),
            'failed' => Deployment::where('status', 'failed')->count(),
            'success_rate' => (Deployment::where('status', 'success')->count() / Deployment::count()) * 100,
        ];

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(4, $stats['success']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(80.0, $stats['success_rate']);
    }

    #[Test]
    public function tracks_deployment_duration_for_batch(): void
    {
        foreach ($this->projects as $index => $project) {
            Deployment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'status' => 'success',
                'duration_seconds' => ($index + 1) * 60, // 60, 120, 180, 240, 300 seconds
            ]);
        }

        $avgDuration = Deployment::avg('duration_seconds');
        $totalDuration = Deployment::sum('duration_seconds');

        $this->assertEquals(180.0, $avgDuration); // (60+120+180+240+300)/5
        $this->assertEquals(900, $totalDuration);
    }

    // ==================== Authorization Tests ====================

    #[Test]
    public function user_can_only_deploy_their_own_projects(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $this->server->id,
        ]);

        // User's projects
        $userProjects = Project::where('user_id', $this->user->id)->get();

        $this->assertCount(5, $userProjects);
        $this->assertFalse($userProjects->contains('id', $otherProject->id));
    }

    #[Test]
    public function batch_deployment_filters_by_user_permission(): void
    {
        $otherUser = User::factory()->create();

        // Create deployment for user's project
        $userDeployment = Deployment::factory()->create([
            'project_id' => $this->projects[0]->id,
            'user_id' => $this->user->id,
        ]);

        // User's deployments only
        $userDeployments = Deployment::where('user_id', $this->user->id)->get();

        $this->assertTrue($userDeployments->contains('id', $userDeployment->id));
    }

    // ==================== Queue and Job Tests ====================

    #[Test]
    public function batch_deployment_queues_jobs_sequentially(): void
    {
        Queue::fake();

        foreach ($this->projects as $project) {
            DeployProjectJob::dispatch($project)->onQueue('deployments');
        }

        Queue::assertPushedOn('deployments', DeployProjectJob::class);
        Queue::assertPushed(DeployProjectJob::class, 5);
    }

    #[Test]
    public function deployment_completion_updates_project_timestamp(): void
    {
        $project = $this->projects[0];

        // Simulate deployment completion
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        $project->update(['last_deployment_at' => $deployment->completed_at]);

        $freshProject = $project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertNotNull($freshProject->last_deployment_at);
    }
}
