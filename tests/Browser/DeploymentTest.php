<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DeploymentTest extends DuskTestCase
{
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
     * Test deployments list page loads with deployment history
     *
     */

    #[Test]
    public function deployments_list_page_loads_with_history()
    {
        // Create multiple deployments with different statuses
        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        Deployment::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->assertSee('Deployment Activity')
                ->assertSee('Total Deployments')
                ->assertSee('Successful')
                ->assertSee('Failed')
                ->assertSee('Running')
                    // Should show at least 5 deployments (3 success + 2 failed)
                ->assertPresent('[data-test="deployment-card"]')
                ->screenshot('deployments-list-page');
        });
    }

    /**
     * Test deployment cards show correct information
     *
     */

    #[Test]
    public function deployment_cards_show_correct_info()
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
            'commit_hash' => 'abc1234567890def',
            'commit_message' => 'Fix critical bug in authentication',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Check project name is displayed
                ->assertSee($this->project->name)
                    // Check branch is displayed
                ->assertSee('main')
                    // Check short commit hash (first 7 chars)
                ->assertSee(substr($deployment->commit_hash, 0, 7))
                    // Check commit message
                ->assertSee('Fix critical bug in authentication')
                    // Check server name
                ->assertSee($this->server->name)
                ->screenshot('deployment-card-info');
        });
    }

    /**
     * Test status badges display correctly with correct colors
     *
     */

    #[Test]
    public function status_badges_display_correctly()
    {
        // Create deployments with all statuses
        $successDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $failedDeployment = Deployment::factory()->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $runningDeployment = Deployment::factory()->running()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $pendingDeployment = Deployment::factory()->pending()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments');

            // Test success badge (green/emerald)
            $successBadge = $browser->element('span.from-emerald-500, span.from-green-500');
            $this->assertNotNull($successBadge, 'Success badge should be green/emerald');
            $browser->assertSee('Success');

            // Test failed badge (red/rose)
            $failedBadge = $browser->element('span.from-red-500, span.from-rose-500');
            $this->assertNotNull($failedBadge, 'Failed badge should be red/rose');
            $browser->assertSee('Failed');

            // Test running badge (amber/orange - should animate)
            $runningBadge = $browser->element('span.from-amber-500, span.from-orange-500');
            $this->assertNotNull($runningBadge, 'Running badge should be amber/orange');
            $browser->assertSee('Running');

            // Test pending badge (blue/indigo)
            $pendingBadge = $browser->element('span.from-blue-500, span.from-indigo-500');
            $this->assertNotNull($pendingBadge, 'Pending badge should be blue/indigo');
            $browser->assertSee('Pending')
                ->screenshot('status-badges');
        });
    }

    /**
     * Test clicking on deployment navigates to detail page
     *
     */

    #[Test]
    public function clicking_deployment_navigates_to_detail_page()
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Test deployment for navigation',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->assertSee('Test deployment for navigation')
                    // Click "View Details" button
                ->clickLink('View Details')
                ->waitForLocation('/deployments/'.$deployment->id)
                ->assertPathIs('/deployments/'.$deployment->id)
                ->assertSee('Deployment #'.$deployment->id)
                ->assertSee($this->project->name)
                ->screenshot('deployment-detail-navigation');
        });
    }

    /**
     * Test deployment detail shows progress and steps
     *
     */

    #[Test]
    public function deployment_detail_shows_progress_and_steps()
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => "=== Cloning Repository ===\n✓ Repository cloned successfully\n=== Building Docker Container ===",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                    // Check progress bar exists
                ->assertPresent('.bg-blue-600')
                    // Check deployment steps section
                ->assertSee('Deployment Steps')
                ->assertSee('Clone Repository')
                ->assertSee('Record Commit Info')
                ->assertSee('Build Docker Image')
                ->assertSee('Start Container')
                    // Check running status indicator
                ->assertSee('Deployment in Progress')
                ->screenshot('deployment-progress-steps');
        });
    }

    /**
     * Test deployment logs are visible
     *
     */

    #[Test]
    public function deployment_logs_are_visible()
    {
        $logContent = "Starting deployment...\nPulling latest changes from repository\nBuilding Docker image\nStarting containers\nDeployment completed successfully";

        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $logContent,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Deployment Logs')
                    // Check log content is displayed
                ->assertSee('Starting deployment')
                ->assertSee('Pulling latest changes')
                ->assertSee('Building Docker image')
                ->assertSee('Deployment completed successfully')
                    // Check terminal-like styling
                ->assertPresent('.bg-\\[\\#1a1a2e\\]')
                ->assertPresent('.font-mono')
                ->screenshot('deployment-logs-visible');
        });
    }

    /**
     * Test log auto-scroll functionality
     *
     */

    #[Test]
    public function log_auto_scroll_works()
    {
        // Create long log output to enable scrolling
        $logLines = [];
        for ($i = 1; $i <= 50; $i++) {
            $logLines[] = "Log line {$i}: Processing step {$i}";
        }
        $longLog = implode("\n", $logLines);

        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => $longLog,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Deployment Logs')
                    // Check auto-scroll controls exist
                ->assertSee('Pause Auto-scroll')
                ->assertSee('Scroll to Bottom')
                    // Click pause button
                ->click('button:contains("Pause Auto-scroll")')
                ->assertSee('Resume Auto-scroll')
                    // Click resume button
                ->click('button:contains("Resume Auto-scroll")')
                ->assertSee('Pause Auto-scroll')
                ->screenshot('log-auto-scroll');
        });
    }

    /**
     * Test deployment from project page works
     *
     */

    #[Test]
    public function deployment_from_project_page_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                ->assertSee($this->project->name)
                    // Look for deploy button
                ->assertPresent('button:contains("Deploy"), a:contains("Deploy")')
                ->screenshot('project-page-deploy-button');

            // Note: Actual deployment trigger would require more setup
            // This test verifies the button exists on the project page
        });
    }

    /**
     * Test deployment status updates in real-time (polling)
     *
     */

    #[Test]
    public function deployment_status_updates_in_real_time()
    {
        $deployment = Deployment::factory()->running()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Running')
                ->assertPresent('.animate-spin');

            // Update deployment status in database
            $deployment->update([
                'status' => 'success',
                'completed_at' => now(),
                'duration_seconds' => 120,
            ]);

            // Wait for Livewire polling to update (wire:poll.3s)
            $browser->pause(4000) // Wait 4 seconds for poll
                ->assertSee('Success')
                ->screenshot('deployment-status-updated');
        });
    }

    /**
     * Test failed deployment shows error message
     *
     */

    #[Test]
    public function failed_deployment_shows_error_message()
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'error_message' => 'Docker build failed: out of memory',
            'error_log' => "ERROR: Container build failed\nFATAL: Out of memory\nStack trace...",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                    // Check failed status badge
                ->assertSee('Failed')
                    // Check error message is displayed
                ->assertSee('Docker build failed: out of memory')
                    // Check error logs section
                ->assertSee('Error Logs')
                ->assertSee('Container build failed')
                ->assertSee('Out of memory')
                    // Error section should have red styling
                ->assertPresent('.text-red-600, .text-red-400')
                ->assertPresent('.bg-red-50')
                ->screenshot('failed-deployment-error');
        });
    }

    /**
     * Test successful deployment shows success message
     *
     */

    #[Test]
    public function successful_deployment_shows_success_message()
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'output_log' => "Deployment started\nAll checks passed\n✓ Build successful\nContainer started\nDeployment completed successfully",
            'duration_seconds' => 180,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                    // Check success status badge (green/emerald)
                ->assertSee('Success')
                ->assertPresent('.from-emerald-500, .from-green-500')
                    // Check duration is displayed
                ->assertSee('180s')
                ->assertSee('3.0 min')
                    // Check success indicators in logs
                ->assertSee('✓ Build successful')
                ->assertSee('Deployment completed successfully')
                ->screenshot('successful-deployment');
        });
    }

    /**
     * Test filter deployments by status
     *
     */

    #[Test]
    public function filter_deployments_by_status()
    {
        // Create deployments with different statuses
        Deployment::factory()->count(2)->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(3)->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Initially should see all deployments
                ->assertSee('Success')
                ->assertSee('Failed')
                    // Filter by success status
                ->select('[wire\\:model\\.live="statusFilter"]', 'success')
                ->waitForLivewire()
                ->pause(1000)
                ->assertSee('Success')
                ->screenshot('filter-by-success')
                    // Filter by failed status
                ->select('[wire\\:model\\.live="statusFilter"]', 'failed')
                ->waitForLivewire()
                ->pause(1000)
                ->assertSee('Failed')
                ->screenshot('filter-by-failed')
                    // Reset filter
                ->select('[wire\\:model\\.live="statusFilter"]', '')
                ->waitForLivewire()
                ->pause(1000)
                ->screenshot('filter-reset');
        });
    }

    /**
     * Test filter deployments by project
     *
     */

    #[Test]
    public function filter_deployments_by_project()
    {
        // Create second project
        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Second Test Project',
        ]);

        // Create deployments for different projects
        Deployment::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(2)->create([
            'project_id' => $project2->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($project2) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Should see both projects initially
                ->assertSee($this->project->name)
                ->assertSee($project2->name)
                    // Filter by first project
                ->select('[wire\\:model\\.live="projectFilter"]', (string) $this->project->id)
                ->waitForLivewire()
                ->pause(1000)
                ->assertSee($this->project->name)
                ->screenshot('filter-by-project')
                    // Filter by second project
                ->select('[wire\\:model\\.live="projectFilter"]', (string) $project2->id)
                ->waitForLivewire()
                ->pause(1000)
                ->assertSee($project2->name)
                ->screenshot('filter-by-project-2');
        });
    }

    /**
     * Test pagination works with many deployments
     *
     */

    #[Test]
    public function pagination_works_with_many_deployments()
    {
        // Create 25 deployments to trigger pagination (default is 15 per page)
        Deployment::factory()->count(25)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Check pagination exists
                ->assertPresent('nav[role="navigation"]')
                    // Should show page 1
                ->assertSee('1')
                ->assertSee('2')
                ->screenshot('pagination-page-1')
                    // Click next page
                ->clickLink('2')
                ->waitForLivewire()
                ->pause(1000)
                ->assertQueryStringHas('page', '2')
                ->screenshot('pagination-page-2')
                    // Test per-page selector
                ->select('[wire\\:model\\.live="perPage"]', '20')
                ->waitForLivewire()
                ->pause(1000)
                ->screenshot('pagination-per-page-20');
        });
    }

    /**
     * Test rollback button appears for successful deployments
     *
     */

    #[Test]
    public function rollback_button_appears_for_successful_deployments()
    {
        // Create a successful deployment
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc123def456',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->id)
                    // Look for rollback section or button
                    // Note: The exact location depends on implementation
                ->assertSee('Rollback')
                ->screenshot('rollback-button-visible');
        });
    }

    /**
     * Test search functionality in deployments
     *
     */

    #[Test]
    public function search_functionality_works()
    {
        // Create deployments with distinct commit messages
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix authentication bug in login system',
            'branch' => 'hotfix',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Add new dashboard feature',
            'branch' => 'feature',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Search by commit message
                ->type('[wire\\:model\\.live\\.debounce\\.500ms="search"]', 'authentication')
                ->pause(1000) // Wait for debounce
                ->waitForLivewire()
                ->assertSee('Fix authentication bug')
                ->screenshot('search-by-commit-message')
                    // Clear and search by branch
                ->clear('[wire\\:model\\.live\\.debounce\\.500ms="search"]')
                ->type('[wire\\:model\\.live\\.debounce\\.500ms="search"]', 'hotfix')
                ->pause(1000)
                ->waitForLivewire()
                ->assertSee('hotfix')
                ->screenshot('search-by-branch');
        });
    }

    /**
     * Test deployment statistics cards
     *
     */

    #[Test]
    public function deployment_statistics_display_correctly()
    {
        // Create deployments with known counts
        Deployment::factory()->count(5)->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(3)->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(2)->running()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                    // Check statistics cards
                ->assertSee('Total Deployments')
                ->assertSee('10') // 5 + 3 + 2
                ->assertSee('Successful')
                ->assertSee('5')
                ->assertSee('Failed')
                ->assertSee('3')
                ->assertSee('Running')
                ->assertSee('2')
                ->screenshot('deployment-statistics');
        });
    }

    /**
     * Test deployment detail page shows all required information
     *
     */

    #[Test]
    public function deployment_detail_shows_all_required_information()
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
            'commit_hash' => 'abc1234567890',
            'commit_message' => 'Important production fix',
            'triggered_by' => 'webhook',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'duration_seconds' => 300,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $browser->loginAs($this->user)
                ->visit('/deployments/'.$deployment->id)
                    // Header information
                ->assertSee('Deployment #'.$deployment->id)
                ->assertSee($this->project->name)
                    // Status information
                ->assertSee('Status')
                ->assertSee('Success')
                ->assertSee('Branch')
                ->assertSee('main')
                ->assertSee('Duration')
                ->assertSee('300s')
                ->assertSee('Triggered By')
                ->assertSee('Webhook')
                    // Details section
                ->assertSee('Deployment Details')
                ->assertSee('Project:')
                ->assertSee('Server:')
                ->assertSee($this->server->name)
                ->assertSee('Commit Hash:')
                ->assertSee(substr($deployment->commit_hash, 0, 8))
                    // Commit information
                ->assertSee('Commit Information')
                ->assertSee('Important production fix')
                ->screenshot('deployment-detail-complete');
        });
    }

    /**
     * Test empty state when no deployments exist
     *
     */

    #[Test]
    public function empty_state_displays_when_no_deployments()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/deployments')
                ->assertSee('No deployments found')
                ->assertSee('Adjust your filters or trigger a new deployment')
                ->screenshot('deployments-empty-state');
        });
    }
}
