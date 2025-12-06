<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentRollbackTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user
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
     * Test user can view rollback page
     */
    public function test_user_can_view_rollback_page(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id.'/rollback')
                ->assertSee('Rollback')
                ->screenshot('rollback-page-view');
        });
    }

    /**
     * Test rollback page shows deployment information
     */
    public function test_rollback_page_shows_deployment_info(): void
    {
        $deployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'abc1234567890',
            'commit_message' => 'Current deployment',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id.'/rollback')
                ->assertSee($this->project->name)
                ->assertSee(substr($deployment->commit_hash, 0, 7))
                ->assertSee('Current deployment')
                ->screenshot('rollback-deployment-info');
        });
    }

    /**
     * Test rollback page shows available target deployments
     */
    public function test_rollback_page_shows_target_deployments(): void
    {
        // Create current deployment
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        // Create previous successful deployments
        $previousDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Previous stable version',
            'created_at' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Select Target Deployment')
                ->assertSee('Previous stable version')
                ->screenshot('rollback-target-list');
        });
    }

    /**
     * Test user can select rollback target deployment
     */
    public function test_user_can_select_rollback_target(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment, $targetDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->radio('target_deployment_id', (string) $targetDeployment->id)
                ->pause(500)
                ->assertRadioSelected('target_deployment_id', (string) $targetDeployment->id)
                ->screenshot('rollback-target-selected');
        });
    }

    /**
     * Test rollback confirmation dialog appears
     */
    public function test_rollback_confirmation_dialog_appears(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment, $targetDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->radio('target_deployment_id', (string) $targetDeployment->id)
                ->press('Rollback')
                ->pause(500)
                ->assertSee('Confirm Rollback')
                ->assertSee('Are you sure')
                ->screenshot('rollback-confirmation-dialog');
        });
    }

    /**
     * Test rollback confirmation shows warning message
     */
    public function test_rollback_confirmation_shows_warning(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->press('Rollback')
                ->pause(500)
                ->assertSee('This action cannot be undone')
                ->screenshot('rollback-warning-message');
        });
    }

    /**
     * Test user can cancel rollback from confirmation dialog
     */
    public function test_user_can_cancel_rollback(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->press('Rollback')
                ->pause(500)
                ->assertSee('Confirm Rollback')
                ->press('Cancel')
                ->pause(500)
                ->assertDontSee('Confirm Rollback')
                ->screenshot('rollback-cancelled');
        });
    }

    /**
     * Test rollback progress tracking is visible
     */
    public function test_rollback_progress_tracking_visible(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolling_back',
            'rollback_deployment_id' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Rolling Back')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->screenshot('rollback-progress');
        });
    }

    /**
     * Test rollback progress shows steps
     */
    public function test_rollback_progress_shows_steps(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolling_back',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Rollback Steps')
                ->assertSee('Stop Current')
                ->assertSee('Restore Previous')
                ->screenshot('rollback-steps');
        });
    }

    /**
     * Test rollback history page is accessible
     */
    public function test_rollback_history_page_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/rollbacks')
                ->assertSee('Rollback History')
                ->screenshot('rollback-history-page');
        });
    }

    /**
     * Test rollback history shows past rollbacks
     */
    public function test_rollback_history_shows_past_rollbacks(): void
    {
        $rollbackDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolled_back',
            'rollback_deployment_id' => 1,
            'commit_message' => 'Rolled back deployment',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/rollbacks')
                ->assertSee('Rolled back deployment')
                ->assertSee('Rolled Back')
                ->screenshot('rollback-history-list');
        });
    }

    /**
     * Test automatic rollback on failure option is visible
     */
    public function test_automatic_rollback_option_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->assertSee('Automatic Rollback')
                ->screenshot('auto-rollback-option');
        });
    }

    /**
     * Test user can enable automatic rollback
     */
    public function test_user_can_enable_automatic_rollback(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->check('auto_rollback')
                ->pause(500)
                ->assertChecked('auto_rollback')
                ->screenshot('auto-rollback-enabled');
        });
    }

    /**
     * Test automatic rollback notification settings
     */
    public function test_automatic_rollback_notification_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->assertSee('Rollback Notifications')
                ->assertSee('Notify on rollback')
                ->screenshot('rollback-notification-settings');
        });
    }

    /**
     * Test rollback comparison view is accessible
     */
    public function test_rollback_comparison_view_accessible(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Compare')
                ->screenshot('rollback-comparison-view');
        });
    }

    /**
     * Test rollback comparison shows current version
     */
    public function test_rollback_comparison_shows_current_version(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'current123',
            'commit_message' => 'Current version',
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Current Version')
                ->assertSee(substr('current123', 0, 7))
                ->assertSee('Current version')
                ->screenshot('comparison-current-version');
        });
    }

    /**
     * Test rollback comparison shows target version
     */
    public function test_rollback_comparison_shows_target_version(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'target456',
            'commit_message' => 'Target version',
            'created_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment, $targetDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->radio('target_deployment_id', (string) $targetDeployment->id)
                ->pause(500)
                ->assertSee('Target Version')
                ->assertSee(substr('target456', 0, 7))
                ->screenshot('comparison-target-version');
        });
    }

    /**
     * Test rollback shows affected files
     */
    public function test_rollback_shows_affected_files(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Affected Files')
                ->screenshot('rollback-affected-files');
        });
    }

    /**
     * Test multi-server rollback option is visible
     */
    public function test_multi_server_rollback_option_visible(): void
    {
        // Create multiple servers
        Server::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);

        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Servers')
                ->screenshot('multi-server-rollback-option');
        });
    }

    /**
     * Test user can select multiple servers for rollback
     */
    public function test_user_can_select_multiple_servers(): void
    {
        $server2 = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Staging Server',
            'status' => 'online',
        ]);

        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('servers[]')
                ->pause(500)
                ->screenshot('multi-server-selected');
        });
    }

    /**
     * Test database rollback option is visible
     */
    public function test_database_rollback_option_visible(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Database Rollback')
                ->screenshot('database-rollback-option');
        });
    }

    /**
     * Test user can enable database rollback
     */
    public function test_user_can_enable_database_rollback(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('rollback_database')
                ->pause(500)
                ->assertChecked('rollback_database')
                ->screenshot('database-rollback-enabled');
        });
    }

    /**
     * Test database rollback shows migration warning
     */
    public function test_database_rollback_shows_migration_warning(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('rollback_database')
                ->pause(500)
                ->assertSee('Warning')
                ->assertSee('migrations will be rolled back')
                ->screenshot('database-rollback-warning');
        });
    }

    /**
     * Test file rollback option is visible
     */
    public function test_file_rollback_option_visible(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('File Rollback')
                ->screenshot('file-rollback-option');
        });
    }

    /**
     * Test user can enable file rollback
     */
    public function test_user_can_enable_file_rollback(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('rollback_files')
                ->pause(500)
                ->assertChecked('rollback_files')
                ->screenshot('file-rollback-enabled');
        });
    }

    /**
     * Test file rollback shows excluded paths
     */
    public function test_file_rollback_shows_excluded_paths(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('rollback_files')
                ->pause(500)
                ->assertSee('Excluded')
                ->assertSee('storage/')
                ->screenshot('file-rollback-excluded-paths');
        });
    }

    /**
     * Test rollback dry-run option is visible
     */
    public function test_rollback_dry_run_option_visible(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Dry Run')
                ->screenshot('rollback-dry-run-option');
        });
    }

    /**
     * Test user can enable rollback dry-run
     */
    public function test_user_can_enable_rollback_dry_run(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('dry_run')
                ->pause(500)
                ->assertChecked('dry_run')
                ->screenshot('rollback-dry-run-enabled');
        });
    }

    /**
     * Test dry-run shows preview results
     */
    public function test_dry_run_shows_preview_results(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->check('dry_run')
                ->press('Preview')
                ->pause(1000)
                ->assertSee('Preview Results')
                ->screenshot('dry-run-preview-results');
        });
    }

    /**
     * Test rollback success status is displayed
     */
    public function test_rollback_success_status_displayed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolled_back',
            'rollback_deployment_id' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Rolled Back')
                ->assertPresent('.from-green-500, .from-emerald-500')
                ->screenshot('rollback-success-status');
        });
    }

    /**
     * Test rollback failure status is displayed
     */
    public function test_rollback_failure_status_displayed(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rollback_failed',
            'error_message' => 'Rollback failed: connection timeout',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Rollback Failed')
                ->assertSee('connection timeout')
                ->assertPresent('.from-red-500, .from-rose-500')
                ->screenshot('rollback-failure-status');
        });
    }

    /**
     * Test emergency rollback button is visible
     */
    public function test_emergency_rollback_button_visible(): void
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Emergency Rollback')
                ->screenshot('emergency-rollback-button');
        });
    }

    /**
     * Test emergency rollback has distinct styling
     */
    public function test_emergency_rollback_has_distinct_styling(): void
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertPresent('button.bg-red-600, button.bg-rose-600')
                ->screenshot('emergency-rollback-styling');
        });
    }

    /**
     * Test emergency rollback shows immediate confirmation
     */
    public function test_emergency_rollback_shows_immediate_confirmation(): void
    {
        $deployment = Deployment::factory()->failed()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->press('Emergency Rollback')
                ->pause(500)
                ->assertSee('Emergency Rollback Confirmation')
                ->assertSee('This is an emergency action')
                ->screenshot('emergency-rollback-confirmation');
        });
    }

    /**
     * Test rollback shows deployment timeline
     */
    public function test_rollback_shows_deployment_timeline(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(3)->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(rand(1, 24)),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Timeline')
                ->screenshot('rollback-deployment-timeline');
        });
    }

    /**
     * Test rollback shows estimated downtime
     */
    public function test_rollback_shows_estimated_downtime(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Estimated Downtime')
                ->screenshot('rollback-estimated-downtime');
        });
    }

    /**
     * Test rollback requires confirmation password
     */
    public function test_rollback_requires_confirmation_password(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->press('Rollback')
                ->pause(500)
                ->assertSee('Confirm Password')
                ->screenshot('rollback-password-confirmation');
        });
    }

    /**
     * Test rollback shows related deployments
     */
    public function test_rollback_shows_related_deployments(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolled_back',
            'rollback_deployment_id' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Related Deployments')
                ->screenshot('rollback-related-deployments');
        });
    }

    /**
     * Test rollback notification preferences
     */
    public function test_rollback_notification_preferences(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/notifications')
                ->assertSee('Rollback Notifications')
                ->assertSee('Email on rollback')
                ->assertSee('Slack on rollback')
                ->screenshot('rollback-notification-preferences');
        });
    }

    /**
     * Test rollback logs are visible
     */
    public function test_rollback_logs_are_visible(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolling_back',
            'output_log' => "Starting rollback process\nStopping current containers\nRestoring previous version\nRollback in progress",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Rollback Logs')
                ->assertSee('Starting rollback process')
                ->assertSee('Restoring previous version')
                ->screenshot('rollback-logs-visible');
        });
    }

    /**
     * Test rollback cancellation is available during process
     */
    public function test_rollback_cancellation_available_during_process(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolling_back',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Cancel Rollback')
                ->screenshot('rollback-cancel-button');
        });
    }

    /**
     * Test rollback cancellation confirmation dialog
     */
    public function test_rollback_cancellation_confirmation_dialog(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolling_back',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->press('Cancel Rollback')
                ->pause(500)
                ->assertSee('Cancel Rollback?')
                ->assertSee('Are you sure you want to cancel')
                ->screenshot('rollback-cancel-confirmation');
        });
    }

    /**
     * Test rollback shows commit differences
     */
    public function test_rollback_shows_commit_differences(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'current123',
        ]);

        $targetDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_hash' => 'target456',
            'created_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment, $targetDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->radio('target_deployment_id', (string) $targetDeployment->id)
                ->pause(500)
                ->assertSee('Commit Differences')
                ->screenshot('rollback-commit-differences');
        });
    }

    /**
     * Test rollback statistics are displayed
     */
    public function test_rollback_statistics_displayed(): void
    {
        // Create multiple rollback deployments
        Deployment::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'rolled_back',
            'rollback_deployment_id' => 1,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/rollbacks')
                ->assertSee('Total Rollbacks')
                ->assertSee('Successful Rollbacks')
                ->assertSee('Failed Rollbacks')
                ->screenshot('rollback-statistics');
        });
    }

    /**
     * Test rollback reason field is available
     */
    public function test_rollback_reason_field_available(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Reason for Rollback')
                ->assertPresent('textarea[name="rollback_reason"]')
                ->screenshot('rollback-reason-field');
        });
    }

    /**
     * Test user can enter rollback reason
     */
    public function test_user_can_enter_rollback_reason(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->type('rollback_reason', 'Critical bug in authentication system')
                ->pause(500)
                ->assertInputValue('rollback_reason', 'Critical bug in authentication system')
                ->screenshot('rollback-reason-entered');
        });
    }

    /**
     * Test rollback shows environment variables changes
     */
    public function test_rollback_shows_environment_variables_changes(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Environment Changes')
                ->screenshot('rollback-env-changes');
        });
    }

    /**
     * Test rollback shows dependency changes
     */
    public function test_rollback_shows_dependency_changes(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Dependency Changes')
                ->screenshot('rollback-dependency-changes');
        });
    }

    /**
     * Test rollback empty state when no previous deployments
     */
    public function test_rollback_empty_state_no_previous_deployments(): void
    {
        $onlyDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($onlyDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$onlyDeployment->id.'/rollback')
                ->assertSee('No previous deployments available')
                ->screenshot('rollback-empty-state');
        });
    }

    /**
     * Test rollback filter by date range
     */
    public function test_rollback_filter_by_date_range(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/rollbacks')
                ->assertSee('Date Range')
                ->assertPresent('input[type="date"]')
                ->screenshot('rollback-date-filter');
        });
    }

    /**
     * Test rollback shows server health before rollback
     */
    public function test_rollback_shows_server_health_before_rollback(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Server Health')
                ->screenshot('rollback-server-health');
        });
    }

    /**
     * Test rollback accessibility from project dashboard
     */
    public function test_rollback_accessibility_from_project_dashboard(): void
    {
        Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->assertSee('Rollback')
                ->screenshot('rollback-from-dashboard');
        });
    }

    /**
     * Test rollback shows backup status
     */
    public function test_rollback_shows_backup_status(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Backup Status')
                ->screenshot('rollback-backup-status');
        });
    }

    /**
     * Test rollback completion time estimate
     */
    public function test_rollback_completion_time_estimate(): void
    {
        $currentDeployment = Deployment::factory()->success()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'duration_seconds' => 180,
        ]);

        $this->browse(function (Browser $browser) use ($currentDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$currentDeployment->id.'/rollback')
                ->assertSee('Estimated Time')
                ->screenshot('rollback-time-estimate');
        });
    }
}
