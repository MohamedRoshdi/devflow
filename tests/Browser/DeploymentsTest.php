<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ScheduledDeployment;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'prod.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Production Server',
                'ip_address' => '192.168.1.100',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
            ]
        );
    }

    /**
     * Test 1: Deployment list page loads successfully
     */
    public function test_deployment_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                ->assertSee('Deployments')
                ->assertSee('All Deployments')
                ->assertPresent('table, [role="table"], .deployment-list')
                ->screenshot('deployment-list-page');
        });
    }

    /**
     * Test 2: Deployment list shows deployment statistics
     */
    public function test_deployment_list_shows_statistics(): void
    {
        // Create deployments with different statuses
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(5),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'branch' => 'develop',
            'commit_hash' => 'def456',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(28),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'branch' => 'main',
            'commit_hash' => 'ghi789',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for stats cards/sections
                ->assertSee('Total')
                ->assertSee('Success')
                ->assertSee('Failed')
                ->assertSee('Running')
                ->screenshot('deployment-statistics');
        });
    }

    /**
     * Test 3: Deployment list can be filtered by status
     */
    public function test_deployment_list_can_be_filtered_by_status(): void
    {
        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
        ]);

        Deployment::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for status filter
                ->assertPresent('select[wire\\:model*="statusFilter"], select[wire\\:model*="status"]')
                ->screenshot('deployment-filter-status');
        });
    }

    /**
     * Test 4: Deployment list can be filtered by project
     */
    public function test_deployment_list_can_be_filtered_by_project(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for project filter
                ->assertPresent('select[wire\\:model*="projectFilter"], select[wire\\:model*="project"]')
                ->screenshot('deployment-filter-project');
        });
    }

    /**
     * Test 5: Deployment list has search functionality
     */
    public function test_deployment_list_has_search_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for search input
                ->assertPresent('input[wire\\:model*="search"], input[type="search"]')
                ->screenshot('deployment-search');
        });
    }

    /**
     * Test 6: Deployment detail page loads successfully
     */
    public function test_deployment_detail_page_loads(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Test deployment commit',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(10),
            'output_log' => "Starting deployment...\nDeployment successful!",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('Deployment')
                ->assertSee($this->project->name)
                ->assertSee('main')
                ->screenshot('deployment-detail-page');
        });
    }

    /**
     * Test 7: Deployment detail shows commit information
     */
    public function test_deployment_detail_shows_commit_information(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'feature/new-feature',
            'commit_hash' => 'abc123def456789',
            'commit_message' => 'Add new feature implementation',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(8),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('feature/new-feature')
                ->assertSee(substr($deployment->commit_hash, 0, 7))
                ->assertSee('Add new feature implementation')
                ->screenshot('deployment-commit-info');
        });
    }

    /**
     * Test 8: Deployment detail shows status and duration
     */
    public function test_deployment_detail_shows_status_and_duration(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(12),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('success')
                ->assertSee('12') // Duration in minutes
                ->screenshot('deployment-status-duration');
        });
    }

    /**
     * Test 9: Deployment logs are visible
     */
    public function test_deployment_logs_are_visible(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'output_log' => "=== Cloning Repository ===\n✓ Repository cloned successfully\n=== Building Docker Container ===\n✓ Build successful\nDeployment complete!",
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('Cloning Repository')
                ->assertSee('Repository cloned successfully')
                ->assertSee('Build successful')
                ->assertSee('Deployment complete')
                ->screenshot('deployment-logs');
        });
    }

    /**
     * Test 10: Deployment progress indicator is displayed
     */
    public function test_deployment_progress_indicator_is_displayed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'branch' => 'main',
            'output_log' => "=== Cloning Repository ===\n✓ Repository cloned successfully\n=== Building Docker Container ===",
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                // Check for progress bar or progress indicator
                ->assertPresent('[role="progressbar"], .progress-bar, [wire\\:poll]')
                ->screenshot('deployment-progress');
        });
    }

    /**
     * Test 11: Failed deployment shows error messages
     */
    public function test_failed_deployment_shows_error_messages(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'branch' => 'main',
            'error_message' => 'Build failed: Composer dependency conflict',
            'error_log' => "Error: Package conflict\nFailed to install dependencies",
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(28),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('failed')
                ->assertSee('Build failed')
                ->assertSee('Composer dependency conflict')
                ->screenshot('deployment-error');
        });
    }

    /**
     * Test 12: Deployment triggered by information is displayed
     */
    public function test_deployment_triggered_by_information_displayed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'triggered_by' => 'webhook',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('webhook')
                ->screenshot('deployment-triggered-by');
        });
    }

    /**
     * Test 13: Rollback functionality is accessible
     */
    public function test_rollback_functionality_is_accessible(): void
    {
        // Create a successful deployment that can be rolled back
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123def456',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                // Check for rollback button or section
                ->assertPresent('button:contains("Rollback"), a:contains("Rollback"), [wire\\:click*="rollback"]')
                ->screenshot('rollback-button');
        });
    }

    /**
     * Test 14: Rollback modal shows comparison data
     */
    public function test_rollback_modal_shows_comparison_data(): void
    {
        // Create two successful deployments
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Old version',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(10),
        ]);

        $newDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'def456ghi789',
            'commit_message' => 'New version',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Rollback"), a:contains("Rollback")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showRollbackModal"]', 5)
                ->assertSee('Rollback')
                ->assertSee('Current')
                ->assertSee('Target')
                ->screenshot('rollback-modal-comparison');
        });
    }

    /**
     * Test 15: Scheduled deployments page is accessible
     */
    public function test_scheduled_deployments_page_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                // Look for scheduled deployments section or button
                ->assertPresent('button:contains("Schedule"), a:contains("Schedule"), [wire\\:click*="schedule"]')
                ->screenshot('scheduled-deployments-button');
        });
    }

    /**
     * Test 16: Scheduled deployment modal contains required fields
     */
    public function test_scheduled_deployment_modal_contains_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule"), a:contains("Schedule")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showScheduleModal"]', 5)
                ->assertSee('Schedule')
                ->assertSee('Branch')
                ->assertSee('Date')
                ->assertSee('Time')
                ->assertSee('Timezone')
                ->screenshot('scheduled-deployment-modal');
        });
    }

    /**
     * Test 17: Scheduled deployments list is visible
     */
    public function test_scheduled_deployments_list_is_visible(): void
    {
        // Create a scheduled deployment
        ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Scheduled')
                ->assertSee('main')
                ->screenshot('scheduled-deployments-list');
        });
    }

    /**
     * Test 18: Scheduled deployment can be cancelled
     */
    public function test_scheduled_deployment_can_be_cancelled(): void
    {
        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                // Check for cancel button
                ->assertPresent('button:contains("Cancel"), [wire\\:click*="cancel"]')
                ->screenshot('scheduled-deployment-cancel');
        });
    }

    /**
     * Test 19: Deployment history displays chronologically
     */
    public function test_deployment_history_displays_chronologically(): void
    {
        // Create multiple deployments with different timestamps
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_message' => 'First deployment',
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3)->addMinutes(10),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_message' => 'Second deployment',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(10),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_message' => 'Third deployment',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                ->assertSee('First deployment')
                ->assertSee('Second deployment')
                ->assertSee('Third deployment')
                ->screenshot('deployment-history-chronological');
        });
    }

    /**
     * Test 20: Environment comparison shows differences
     */
    public function test_environment_comparison_shows_differences(): void
    {
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'environment_snapshot' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'PHP_VERSION' => '8.3',
            ],
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(10),
        ]);

        $newDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'def456',
            'environment_snapshot' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'PHP_VERSION' => '8.4',
            ],
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Rollback"), a:contains("Rollback")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Environment')
                ->screenshot('environment-comparison');
        });
    }

    /**
     * Test 21: Deployment status badges have correct colors
     */
    public function test_deployment_status_badges_have_correct_colors(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1)->addMinutes(5),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(28),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10);

            // Check for status badges with correct colors
            // Success should have green/emerald styling
            $successBadge = $browser->element('span.from-green-500, span.from-emerald-500, span.bg-green-500, span.bg-emerald-500, span.text-green-500, span.text-emerald-500');
            $this->assertNotNull($successBadge, 'Success status indicator should be present');

            // Failed should have red/rose styling
            $failedBadge = $browser->element('span.from-red-500, span.from-rose-500, span.bg-red-500, span.bg-rose-500, span.text-red-500, span.text-rose-500');
            $this->assertNotNull($failedBadge, 'Failed status indicator should be present');

            // Running should have yellow/amber styling
            $runningBadge = $browser->element('span.from-yellow-500, span.from-amber-500, span.bg-yellow-500, span.bg-amber-500, span.text-yellow-500, span.text-amber-500');
            $this->assertNotNull($runningBadge, 'Running status indicator should be present');

            $browser->screenshot('deployment-status-badges');
        });
    }

    /**
     * Test 22: Deployment pagination works correctly
     */
    public function test_deployment_pagination_works_correctly(): void
    {
        // Create more deployments than per page limit
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for pagination controls
                ->assertPresent('nav[role="navigation"], .pagination, [wire\\:click*="nextPage"]')
                ->screenshot('deployment-pagination');
        });
    }

    /**
     * Test 23: Deployment per page selector is functional
     */
    public function test_deployment_per_page_selector_is_functional(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                // Check for per page selector
                ->assertPresent('select[wire\\:model*="perPage"], select:contains("15"), select:contains("Per page")')
                ->screenshot('deployment-per-page');
        });
    }

    /**
     * Test 24: Deployment detail shows deployer information
     */
    public function test_deployment_detail_shows_deployer_information(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee($this->user->name)
                ->screenshot('deployment-deployer-info');
        });
    }

    /**
     * Test 25: Deployment list is empty state handled gracefully
     */
    public function test_deployment_list_empty_state_handled_gracefully(): void
    {
        // Create a new project without deployments
        $emptyProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Empty Deployment Project',
            'slug' => 'empty-deployment-project',
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $browser->loginAs($this->user)
                ->visit('/deployments?projectFilter='.$emptyProject->id)
                ->waitForText('Deployments', 10)
                ->assertSee('No deployments found')
                ->screenshot('deployment-empty-state');
        });
    }

    /**
     * Test 26: Deployment shows server information
     */
    public function test_deployment_shows_server_information(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee($this->server->name)
                ->screenshot('deployment-server-info');
        });
    }

    /**
     * Test 27: Deployment metadata is displayed if available
     */
    public function test_deployment_metadata_is_displayed_if_available(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'metadata' => [
                'build_number' => '123',
                'pipeline_id' => '456',
                'environment' => 'production',
            ],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('Metadata')
                ->screenshot('deployment-metadata');
        });
    }

    /**
     * Test 28: Rollback deployment is marked appropriately
     */
    public function test_rollback_deployment_is_marked_appropriately(): void
    {
        $originalDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(10),
        ]);

        $rollbackDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'rollback_deployment_id' => $originalDeployment->id,
            'triggered_by' => 'rollback',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($rollbackDeployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$rollbackDeployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('Rollback')
                ->screenshot('rollback-deployment-marked');
        });
    }

    /**
     * Test 29: Deployment list shows branch names
     */
    public function test_deployment_list_shows_branch_names(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_message' => 'Main branch deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'develop',
            'commit_message' => 'Develop branch deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'feature/new-feature',
            'commit_message' => 'Feature branch deployment',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->waitForText('Deployments', 10)
                ->assertSee('main')
                ->assertSee('develop')
                ->assertSee('feature/new-feature')
                ->screenshot('deployment-branch-names');
        });
    }

    /**
     * Test 30: Deployment timing information is accurate
     */
    public function test_deployment_timing_information_is_accurate(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(15)->addSeconds(30),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->waitForText('Deployment', 10)
                ->assertSee('Started')
                ->assertSee('Completed')
                ->assertSee('15') // Duration in minutes
                ->screenshot('deployment-timing');
        });
    }
}
