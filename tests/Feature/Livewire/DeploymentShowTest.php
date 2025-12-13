<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\DeploymentShow;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for DeploymentShow Livewire component
 *
 * Tests component rendering, deployment details display, logs display,
 * authorization, status badges, progress tracking, and real-time updates.
 */
class DeploymentShowTest extends TestCase
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
        $this->server = Server::factory()->create();

        // Create projects owned by different users
        $this->userProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'User Project',
        ]);

        $this->otherProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
            'name' => 'Other User Project',
        ]);
    }

    /**
     * Test: Component can be rendered by authorized user
     */
    public function test_component_can_be_rendered_by_authorized_user(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertOk()
            ->assertViewIs('livewire.deployments.deployment-show')
            ->assertSet('deployment.id', $deployment->id);
    }

    /**
     * Test: Unauthorized user cannot view deployment
     */
    public function test_unauthorized_user_cannot_view_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->otherProject->id,
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertForbidden();
    }

    /**
     * Test: Deployment details are displayed correctly
     */
    public function test_deployment_details_are_displayed_correctly(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Fix critical authentication bug',
            'branch' => 'main',
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee($this->userProject->name)
            ->assertSee($this->server->name)
            ->assertSee('abc123de') // First 8 chars of commit hash
            ->assertSee('Fix critical authentication bug')
            ->assertSee('main');
    }

    /**
     * Test: Success status is displayed with correct styling
     */
    public function test_success_status_is_displayed_correctly(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Success')
            ->assertSet('deployment.status', 'success');
    }

    /**
     * Test: Failed status is displayed with correct styling
     */
    public function test_failed_status_is_displayed_correctly(): void
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'error_log' => 'Docker build failed: out of memory',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Failed')
            ->assertSee('Docker build failed: out of memory')
            ->assertSet('deployment.status', 'failed');
    }

    /**
     * Test: Running status is displayed with correct styling
     */
    public function test_running_status_is_displayed_correctly(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Running')
            ->assertSet('deployment.status', 'running');
    }

    /**
     * Test: Pending status is displayed with correct styling
     */
    public function test_pending_status_is_displayed_correctly(): void
    {
        $deployment = Deployment::factory()->pending()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Pending')
            ->assertSet('deployment.status', 'pending');
    }

    /**
     * Test: Deployment duration is displayed for completed deployments
     */
    public function test_deployment_duration_is_displayed_for_completed_deployments(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'duration_seconds' => 180, // 3 minutes
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('180s')
            ->assertSee('3.0 min');
    }

    /**
     * Test: Deployment logs are displayed from output_log
     */
    public function test_deployment_logs_are_displayed_from_output_log(): void
    {
        $logContent = "=== Starting Deployment ===\nCloning repository\n✓ Repository cloned successfully";

        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Starting Deployment')
            ->assertSee('Cloning repository')
            ->assertSee('Repository cloned successfully');
    }

    /**
     * Test: Live logs are initialized for completed deployments
     */
    public function test_live_logs_are_initialized_for_completed_deployments(): void
    {
        $logContent = "Line 1\nLine 2\nLine 3";

        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('liveLogs', function ($logs) {
                return count($logs) === 3;
            });
    }

    /**
     * Test: Error level logs are detected correctly
     */
    public function test_error_level_logs_are_detected_correctly(): void
    {
        $logContent = "Starting\nError: Something went wrong\nFailed to connect\nWarning: Deprecated function";

        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment]);

        // Check that error lines are detected
        $liveLogs = $component->get('liveLogs');
        $errorLogs = array_filter($liveLogs, fn ($log) => $log['level'] === 'error');
        $warningLogs = array_filter($liveLogs, fn ($log) => $log['level'] === 'warning');

        $this->assertGreaterThan(0, count($errorLogs));
        $this->assertGreaterThan(0, count($warningLogs));
    }

    /**
     * Test: Progress is calculated correctly during running deployment
     */
    public function test_progress_is_calculated_correctly_during_running_deployment(): void
    {
        $logContent = "=== Cloning Repository ===\n✓ Repository cloned successfully\n✓ Commit information recorded";

        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('progress', function ($progress) {
                return $progress >= 20; // Should be at least 20% based on logs
            });
    }

    /**
     * Test: Current step is tracked during deployment
     */
    public function test_current_step_is_tracked_during_deployment(): void
    {
        $logContent = "=== Cloning Repository ===\n✓ Repository cloned successfully";

        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('currentStep', 'Recording commit information');
    }

    /**
     * Test: Progress shows 100% for successful deployments
     */
    public function test_progress_shows_100_percent_for_successful_deployments(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('progress', 100)
            ->assertSet('currentStep', 'Deployment successful');
    }

    /**
     * Test: Progress shows 0% for failed deployments
     */
    public function test_progress_shows_0_percent_for_failed_deployments(): void
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('progress', 0)
            ->assertSet('currentStep', 'Deployment failed');
    }

    /**
     * Test: Refresh button is shown for pending and running deployments
     */
    public function test_refresh_button_is_shown_for_pending_and_running_deployments(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Refresh');
    }

    /**
     * Test: Refresh method updates deployment data
     */
    public function test_refresh_method_updates_deployment_data(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => 'Initial log',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment]);

        // Update the deployment in the database
        $deployment->update(['output_log' => 'Updated log']);

        $component->call('refresh')
            ->assertSet('deployment.output_log', 'Updated log');
    }

    /**
     * Test: Triggered by information is displayed
     */
    public function test_triggered_by_information_is_displayed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'triggered_by' => 'webhook',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Webhook');
    }

    /**
     * Test: Started at timestamp is displayed
     */
    public function test_started_at_timestamp_is_displayed(): void
    {
        $startedAt = now()->subMinutes(5);

        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'started_at' => $startedAt,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee($startedAt->format('Y-m-d H:i:s'));
    }

    /**
     * Test: Completed at timestamp is displayed for completed deployments
     */
    public function test_completed_at_timestamp_is_displayed_for_completed_deployments(): void
    {
        $completedAt = now()->subMinutes(2);

        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'completed_at' => $completedAt,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee($completedAt->format('Y-m-d H:i:s'));
    }

    /**
     * Test: Progress bar is displayed for running deployments
     */
    public function test_progress_bar_is_displayed_for_running_deployments(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Deployment in Progress');
    }

    /**
     * Test: Error log section is displayed when error_log exists
     */
    public function test_error_log_section_is_displayed_when_error_log_exists(): void
    {
        $errorLog = 'Fatal error: Container failed to start';

        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'error_log' => $errorLog,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Error Logs')
            ->assertSee($errorLog);
    }

    /**
     * Test: Deployment steps are shown for pending and running deployments
     */
    public function test_deployment_steps_are_shown_for_pending_and_running_deployments(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => '=== Building Docker Container ===',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Deployment Steps')
            ->assertSee('Setup Repository')
            ->assertSee('Build Docker Image');
    }

    /**
     * Test: No logs message is displayed when no logs are available
     */
    public function test_no_logs_message_is_displayed_when_no_logs_available(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('No logs available');
    }

    /**
     * Test: Live log event updates logs array
     */
    public function test_live_log_event_updates_logs_array(): void
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment]);

        // Simulate receiving a log event
        $component->dispatch('DeploymentLogUpdated', [
            'line' => 'New log line from WebSocket',
            'level' => 'info',
            'timestamp' => now()->toIso8601String(),
        ]);

        // Check that the log was added
        $liveLogs = $component->get('liveLogs');
        $lastLog = end($liveLogs);

        $this->assertEquals('New log line from WebSocket', $lastLog['line']);
        $this->assertEquals('info', $lastLog['level']);
    }

    /**
     * Test: Component displays back to list link
     */
    public function test_component_displays_back_to_list_link(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee('Back to List');
    }

    /**
     * Test: Component shows deployment ID in header
     */
    public function test_component_shows_deployment_id_in_header(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSee("Deployment #{$deployment->id}");
    }
}
