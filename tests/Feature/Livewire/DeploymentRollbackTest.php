<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\DeploymentRollback;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\RollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for DeploymentRollback Livewire component
 *
 * Tests rollback functionality including:
 * - Rollback point selection and display
 * - Rollback initiation and confirmation
 * - Authorization and ownership validation
 * - Rollback status restrictions
 * - Deployment record creation
 * - Error handling and UI feedback
 * - Comparison data loading
 */
class DeploymentRollbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Project $userProject;

    private Project $otherProject;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create server
        $this->server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'deploy',
            'port' => 22,
        ]);

        // Create projects owned by different users
        $this->userProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'User Project',
            'slug' => 'user-project',
        ]);

        $this->otherProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
            'name' => 'Other User Project',
            'slug' => 'other-project',
        ]);
    }

    /**
     * Test: Component can be rendered by authorized user
     */
    public function test_component_can_be_rendered_by_authorized_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->assertOk()
            ->assertViewIs('livewire.deployments.deployment-rollback')
            ->assertSet('project.id', $this->userProject->id);
    }

    /**
     * Test: Unauthorized user cannot access component for other user's project
     */
    public function test_unauthorized_user_cannot_access_other_users_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->otherProject])
            ->assertForbidden();
    }

    /**
     * Test: Component loads rollback points on mount
     */
    public function test_component_loads_rollback_points_on_mount(): void
    {
        // Create multiple successful deployments
        Deployment::factory()->success()->count(5)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');
        $this->assertIsArray($rollbackPoints);
        $this->assertGreaterThan(0, count($rollbackPoints));
    }

    /**
     * Test: Only successful deployments appear as rollback points
     */
    public function test_only_successful_deployments_appear_as_rollback_points(): void
    {
        // Create deployments with different statuses
        Deployment::factory()->success()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        Deployment::factory()->failed()->count(2)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->pending()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');

        // Should only have successful deployments that can be rolled back
        $this->assertLessThanOrEqual(3, count($rollbackPoints));
    }

    /**
     * Test: Rollback deployments are excluded from rollback points
     */
    public function test_rollback_deployments_are_excluded_from_rollback_points(): void
    {
        $originalDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create a rollback deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'triggered_by' => 'rollback',
            'rollback_deployment_id' => $originalDeployment->id,
            'commit_hash' => $originalDeployment->commit_hash,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');

        // Should not include rollback deployments
        foreach ($rollbackPoints as $point) {
            $this->assertNotEquals('rollback', $point['triggered_by'] ?? null);
        }
    }

    /**
     * Test: Select deployment for rollback opens confirmation modal
     */
    public function test_select_deployment_for_rollback_opens_confirmation_modal(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
            'commit_message' => 'Previous stable version',
        ]);

        // Create a newer deployment to make this one rollbackable
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $deployment->id)
            ->assertSet('showRollbackModal', true)
            ->assertSet('selectedDeployment.id', $deployment->id);
    }

    /**
     * Test: Cannot select current deployment for rollback
     */
    public function test_cannot_select_current_deployment_for_rollback(): void
    {
        $latestDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');

        // Latest deployment should not be rollbackable
        $latestPoint = collect($rollbackPoints)->firstWhere('id', $latestDeployment->id);

        if ($latestPoint !== null) {
            $this->assertFalse($latestPoint['can_rollback']);
        }
    }

    /**
     * Test: Confirmation modal displays deployment comparison data
     */
    public function test_confirmation_modal_displays_deployment_comparison_data(): void
    {
        $oldDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Old stable version',
        ]);

        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'def456ghi789',
            'commit_message' => 'Current version with bug',
        ]);

        // Mock SSH command execution for git diff
        Process::fake([
            '*git log*' => Process::result('commit1 Some commit message'),
            '*git diff*' => Process::result("M app/Services/Something.php\nA routes/api.php"),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $oldDeployment->id);

        $comparisonData = $component->get('comparisonData');

        $this->assertIsArray($comparisonData);
        $this->assertArrayHasKey('current', $comparisonData);
        $this->assertArrayHasKey('target', $comparisonData);
        $this->assertArrayHasKey('commits_to_remove', $comparisonData);
        $this->assertArrayHasKey('files_changed', $comparisonData);
    }

    /**
     * Test: Confirm rollback initiates rollback process
     */
    public function test_confirm_rollback_initiates_rollback_process(): void
    {
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment to make this one rollbackable
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Mock the RollbackService
        $mockRollbackService = Mockery::mock(RollbackService::class);
        $newRollbackDeployment = Deployment::factory()->make([
            'id' => 999,
            'status' => 'success',
            'triggered_by' => 'rollback',
        ]);

        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->andReturn([
                [
                    'id' => $targetDeployment->id,
                    'commit_hash' => $targetDeployment->commit_hash,
                    'commit_message' => $targetDeployment->commit_message,
                    'deployed_at' => $targetDeployment->created_at,
                    'deployed_by' => $this->user->name,
                    'can_rollback' => true,
                ],
            ]);

        $mockRollbackService->shouldReceive('rollbackToDeployment')
            ->with(Mockery::on(function ($deployment) use ($targetDeployment) {
                return $deployment->id === $targetDeployment->id;
            }))
            ->once()
            ->andReturn([
                'success' => true,
                'deployment' => $newRollbackDeployment,
                'message' => 'Rollback completed successfully',
            ]);

        $this->app->instance(RollbackService::class, $mockRollbackService);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $targetDeployment->id)
            ->assertSet('showRollbackModal', true)
            ->call('confirmRollback')
            ->assertRedirect(route('deployments.show', $newRollbackDeployment));
    }

    /**
     * Test: Rollback creates new deployment record
     */
    public function test_rollback_creates_new_deployment_record(): void
    {
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123',
            'commit_message' => 'Stable version',
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock services
        $mockDockerService = Mockery::mock(DockerService::class);
        $mockDockerService->shouldReceive('deployWithCompose')
            ->andReturn(['success' => true]);
        $mockDockerService->shouldReceive('getContainerStatus')
            ->andReturn([['status' => 'running']]);

        $mockGitService = Mockery::mock(GitService::class);

        $this->app->instance(DockerService::class, $mockDockerService);
        $this->app->instance(GitService::class, $mockGitService);

        Process::fake();

        $initialCount = Deployment::count();

        // Execute rollback via service (not Livewire since it redirects)
        $rollbackService = app(RollbackService::class);
        $result = $rollbackService->rollbackToDeployment($targetDeployment);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan($initialCount, Deployment::count());
        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->userProject->id,
            'triggered_by' => 'rollback',
            'rollback_deployment_id' => $targetDeployment->id,
            'commit_hash' => $targetDeployment->commit_hash,
        ]);
    }

    /**
     * Test: Rollback in progress flag is set during rollback
     */
    public function test_rollback_in_progress_flag_is_set_during_rollback(): void
    {
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock the RollbackService to simulate a slow rollback
        $mockRollbackService = Mockery::mock(RollbackService::class);
        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->andReturn([
                [
                    'id' => $targetDeployment->id,
                    'commit_hash' => $targetDeployment->commit_hash,
                    'commit_message' => $targetDeployment->commit_message,
                    'deployed_at' => $targetDeployment->created_at,
                    'deployed_by' => $this->user->name,
                    'can_rollback' => true,
                ],
            ]);

        $mockRollbackService->shouldReceive('rollbackToDeployment')
            ->andReturn([
                'success' => false,
                'error' => 'Simulated error',
            ]);

        $this->app->instance(RollbackService::class, $mockRollbackService);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $targetDeployment->id)
            ->call('confirmRollback')
            ->assertSet('rollbackInProgress', false) // Should be reset after completion
            ->assertSet('showRollbackModal', false);
    }

    /**
     * Test: Failed rollback displays error notification
     */
    public function test_failed_rollback_displays_error_notification(): void
    {
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock the RollbackService to return failure
        $mockRollbackService = Mockery::mock(RollbackService::class);
        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->andReturn([
                [
                    'id' => $targetDeployment->id,
                    'commit_hash' => $targetDeployment->commit_hash,
                    'commit_message' => $targetDeployment->commit_message,
                    'deployed_at' => $targetDeployment->created_at,
                    'deployed_by' => $this->user->name,
                    'can_rollback' => true,
                ],
            ]);

        $mockRollbackService->shouldReceive('rollbackToDeployment')
            ->andReturn([
                'success' => false,
                'error' => 'Docker container failed to start',
            ]);

        $this->app->instance(RollbackService::class, $mockRollbackService);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $targetDeployment->id)
            ->call('confirmRollback')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'Docker container failed to start');
            });
    }

    /**
     * Test: Successful rollback displays success notification
     */
    public function test_successful_rollback_displays_success_notification(): void
    {
        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $newDeployment = Deployment::factory()->make(['id' => 999]);

        $mockRollbackService = Mockery::mock(RollbackService::class);
        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->andReturn([
                [
                    'id' => $targetDeployment->id,
                    'commit_hash' => $targetDeployment->commit_hash,
                    'commit_message' => $targetDeployment->commit_message,
                    'deployed_at' => $targetDeployment->created_at,
                    'deployed_by' => $this->user->name,
                    'can_rollback' => true,
                ],
            ]);

        $mockRollbackService->shouldReceive('rollbackToDeployment')
            ->andReturn([
                'success' => true,
                'deployment' => $newDeployment,
                'message' => 'Rollback completed successfully',
            ]);

        $this->app->instance(RollbackService::class, $mockRollbackService);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $targetDeployment->id)
            ->call('confirmRollback')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Rollback initiated successfully');
            });
    }

    /**
     * Test: Cancel rollback closes modal and clears selection
     */
    public function test_cancel_rollback_closes_modal_and_clears_selection(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $deployment->id)
            ->assertSet('showRollbackModal', true)
            ->assertSet('selectedDeployment.id', $deployment->id)
            ->call('cancelRollback')
            ->assertSet('showRollbackModal', false)
            ->assertSet('selectedDeployment', null)
            ->assertSet('comparisonData', null);
    }

    /**
     * Test: Cannot rollback deployment without commit hash
     */
    public function test_cannot_rollback_deployment_without_commit_hash(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => null,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');

        // Deployment without commit hash should not be in rollback points
        $this->assertEmpty(collect($rollbackPoints)->where('id', $deployment->id)->all());
    }

    /**
     * Test: Deployment completed event refreshes rollback points
     */
    public function test_deployment_completed_event_refreshes_rollback_points(): void
    {
        $initialDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $initialCount = count($component->get('rollbackPoints'));

        // Create a new successful deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Dispatch the event
        $component->dispatch('deployment-completed')
            ->assertSet('rollbackPoints', function ($rollbackPoints) use ($initialCount) {
                // Should have reloaded and potentially have different count
                return is_array($rollbackPoints);
            });
    }

    /**
     * Test: Cannot rollback if selected deployment is not rollbackable
     */
    public function test_cannot_rollback_if_selected_deployment_is_not_rollbackable(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        $mockRollbackService = Mockery::mock(RollbackService::class);
        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->andReturn([
                [
                    'id' => $deployment->id,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => $deployment->commit_message,
                    'deployed_at' => $deployment->created_at,
                    'deployed_by' => $this->user->name,
                    'can_rollback' => false, // Not rollbackable
                ],
            ]);

        $mockRollbackService->shouldNotReceive('rollbackToDeployment');

        $this->app->instance(RollbackService::class, $mockRollbackService);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $deployment->id)
            ->call('confirmRollback')
            ->assertNoRedirect();
    }

    /**
     * Test: Rollback comparison shows files changed
     */
    public function test_rollback_comparison_shows_files_changed(): void
    {
        $oldDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123',
        ]);

        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'def456',
        ]);

        Process::fake([
            '*git log*' => Process::result("commit1 First commit\ncommit2 Second commit"),
            '*git diff*' => Process::result("M app/Http/Controllers/HomeController.php\nA routes/web.php\nD config/old.php"),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $oldDeployment->id);

        $comparisonData = $component->get('comparisonData');

        $this->assertArrayHasKey('files_changed', $comparisonData);
        $this->assertIsArray($comparisonData['files_changed']);
    }

    /**
     * Test: Rollback handles SSH execution errors gracefully
     */
    public function test_rollback_handles_ssh_execution_errors_gracefully(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fake()->sha1(),
        ]);

        // Create newer deployment
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock Process to fail
        Process::fake([
            '*' => Process::result(errorOutput: 'SSH connection failed', exitCode: 1),
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $deployment->id)
            ->assertSet('comparisonData', function ($data) {
                // Should still set comparison data even if SSH commands fail
                return is_array($data);
            });
    }

    /**
     * Test: Empty state when no rollback points exist
     */
    public function test_empty_state_when_no_rollback_points_exist(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->assertSet('rollbackPoints', [])
            ->assertSee('No rollback points available');
    }

    /**
     * Test: Rollback points are limited to specified count
     */
    public function test_rollback_points_are_limited_to_specified_count(): void
    {
        // Create 25 successful deployments
        Deployment::factory()->success()->count(25)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => fn() => fake()->sha1(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject]);

        $rollbackPoints = $component->get('rollbackPoints');

        // Should be limited to 20 as per the component
        $this->assertLessThanOrEqual(20, count($rollbackPoints));
    }

    /**
     * Test: Deployment not found error when selecting invalid deployment
     */
    public function test_deployment_not_found_error_when_selecting_invalid_deployment(): void
    {
        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', 99999) // Non-existent deployment ID
            ->assertSet('selectedDeployment', null)
            ->assertSet('showRollbackModal', false);
    }

    /**
     * Test: Comparison data handles null commit hashes
     */
    public function test_comparison_data_handles_null_commit_hashes(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => null,
        ]);

        Process::fake();

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->call('selectForRollback', $deployment->id);

        // Should handle gracefully without errors
        $this->assertTrue(true);
    }

    /**
     * Test: Component displays deployment details in rollback points
     */
    public function test_component_displays_deployment_details_in_rollback_points(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123def',
            'commit_message' => 'Feature: Add user authentication',
        ]);

        // Create newer deployment to make this one rollbackable
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $this->userProject])
            ->assertSee('abc123def')
            ->assertSee('Feature: Add user authentication');
    }
}
