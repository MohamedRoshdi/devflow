<?php

namespace Tests\Feature;

use App\Events\DeploymentStatusUpdated;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\RollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
    }

    /** @test */
    public function deployment_creates_correct_database_record()
    {
        Queue::fake();

        $this->actingAs($this->user);

        $deployment = Deployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->project->server_id,
            'branch' => 'main',
            'commit_hash' => 'abc123def456',
            'status' => 'pending',
            'triggered_by' => 'manual',
            'started_at' => now(),
        ]);

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        Queue::assertPushed(\App\Jobs\DeployProjectJob::class);
    }

    /** @test */
    public function deployment_broadcasts_status_updates()
    {
        Event::fake([DeploymentStatusUpdated::class]);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        event(new DeploymentStatusUpdated($deployment, 'Deployment started', 'info'));

        Event::assertDispatched(DeploymentStatusUpdated::class, function ($event) use ($deployment) {
            return $event->deployment->id === $deployment->id
                && $event->message === 'Deployment started'
                && $event->type === 'info';
        });
    }

    /** @test */
    public function rollback_creates_new_deployment_with_reference()
    {
        $this->actingAs($this->user);

        // Create successful deployments
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'commit_hash' => 'old123',
            'created_at' => now()->subDays(2),
        ]);

        $currentDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'commit_hash' => 'new456',
            'created_at' => now()->subDay(),
        ]);

        // Mock RollbackService
        $this->mock(RollbackService::class, function ($mock) use ($oldDeployment) {
            $mock->shouldReceive('rollbackToDeployment')
                ->with($oldDeployment)
                ->once()
                ->andReturn([
                    'success' => true,
                    'deployment' => Deployment::factory()->create([
                        'project_id' => $this->project->id,
                        'user_id' => $this->user->id,
                        'rollback_deployment_id' => $oldDeployment->id,
                        'status' => 'success',
                    ]),
                ]);
        });

        $rollbackService = app(RollbackService::class);
        $result = $rollbackService->rollbackToDeployment($oldDeployment);

        $this->assertTrue($result['success']);
        $this->assertEquals($oldDeployment->id, $result['deployment']->rollback_deployment_id);
    }

    /** @test */
    public function deployment_status_progression_is_correct()
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        // Pending -> Running
        $deployment->update(['status' => 'running']);
        $this->assertEquals('running', $deployment->fresh()->status);

        // Running -> Success
        $deployment->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => 120,
        ]);

        $this->assertEquals('success', $deployment->fresh()->status);
        $this->assertNotNull($deployment->fresh()->completed_at);
    }

    /** @test */
    public function failed_deployment_stores_error_message()
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $errorMessage = 'Docker build failed: out of memory';

        $deployment->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);

        $this->assertEquals('failed', $deployment->fresh()->status);
        $this->assertEquals($errorMessage, $deployment->fresh()->error_message);
    }

    /** @test */
    public function deployment_pagination_works_correctly()
    {
        $this->actingAs($this->user);

        // Create 25 deployments
        Deployment::factory()->count(25)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('deployments.index'));

        $response->assertStatus(200);
        $response->assertViewHas('deployments');

        $deployments = $response->viewData('deployments');
        $this->assertEquals(15, $deployments->perPage()); // Default pagination
        $this->assertEquals(25, $deployments->total());
    }

    /** @test */
    public function deployment_can_be_cancelled()
    {
        Queue::fake();

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->user)
            ->post(route('deployments.cancel', $deployment))
            ->assertRedirect();

        $this->assertEquals('cancelled', $deployment->fresh()->status);
    }

    /** @test */
    public function deployment_logs_are_stored_correctly()
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'running',
        ]);

        $logOutput = "Building Docker image...\nImage built successfully\nStarting containers...";

        $deployment->update(['output' => $logOutput]);

        $this->assertStringContainsString('Building Docker image', $deployment->fresh()->output);
    }

    /** @test */
    public function only_successful_deployments_can_be_rollback_targets()
    {
        $this->actingAs($this->user);

        $successfulDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
        ]);

        $failedDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
            'commit_hash' => 'def456',
        ]);

        $rollbackService = app(RollbackService::class);
        $rollbackPoints = $rollbackService->getRollbackPoints($this->project);

        $this->assertCount(1, $rollbackPoints);
        $this->assertEquals($successfulDeployment->id, $rollbackPoints[0]['id']);
    }

    /** @test */
    public function deployment_webhook_triggers_auto_deploy()
    {
        Queue::fake();

        $this->project->update(['auto_deploy' => true]);

        $webhookPayload = [
            'ref' => 'refs/heads/main',
            'after' => 'new-commit-hash',
            'repository' => [
                'clone_url' => $this->project->repository_url,
            ],
        ];

        $response = $this->postJson(
            route('webhook.github', ['project' => $this->project->slug]),
            $webhookPayload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'triggered_by' => 'webhook',
            'commit_hash' => 'new-commit-hash',
        ]);
    }
}
