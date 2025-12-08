<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentShowTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Deployment $deployment = null;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->deployment = Deployment::first();
    }

    /**
     * Test deployment show page loads successfully
     */
    public function test_deployment_show_page_loads_successfully(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Deployment #'.$this->deployment->id)
                ->screenshot('deployment-show-page-loads');
        });
    }

    /**
     * Test deployment status is displayed
     */
    public function test_deployment_status_displayed(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Status')
                ->assertSee(ucfirst($this->deployment->status))
                ->screenshot('deployment-status-displayed');
        });
    }

    /**
     * Test progress bar shown for running deployments
     */
    public function test_progress_bar_shown_for_running_deployment(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Deployment in Progress')
                ->assertPresent('div.bg-blue-600')
                ->assertSee('%')
                ->screenshot('deployment-progress-bar');
        });
    }

    /**
     * Test current step is indicated
     */
    public function test_current_step_indicated(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'output_log' => '=== Cloning Repository ==='."\n".'Cloning into repository',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Cloning repository')
                ->assertPresent('svg.animate-spin')
                ->screenshot('deployment-current-step');
        });
    }

    /**
     * Test live logs section displayed
     */
    public function test_live_logs_section_displayed(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Deployment Logs')
                ->assertPresent('div.bg-\\[\\#1a1a2e\\]')
                ->screenshot('deployment-live-logs-section');
        });
    }

    /**
     * Test commit hash is displayed
     */
    public function test_commit_hash_displayed(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Commit Hash')
                ->screenshot('deployment-commit-hash');
        });
    }

    /**
     * Test commit message is shown
     */
    public function test_commit_message_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'commit_message' => 'Fix critical authentication bug',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Commit Information')
                ->assertSee('Fix critical authentication bug')
                ->screenshot('deployment-commit-message');
        });
    }

    /**
     * Test branch name is displayed
     */
    public function test_branch_name_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Branch')
                ->assertSee('main')
                ->screenshot('deployment-branch-name');
        });
    }

    /**
     * Test duration is shown
     */
    public function test_duration_shown(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Duration')
                ->screenshot('deployment-duration');
        });
    }

    /**
     * Test started at timestamp displayed
     */
    public function test_started_at_timestamp_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'started_at' => now()->subMinutes(30),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Started At')
                ->screenshot('deployment-started-at');
        });
    }

    /**
     * Test completed at timestamp displayed
     */
    public function test_completed_at_timestamp_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(15),
            'duration_seconds' => 900,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Completed At')
                ->screenshot('deployment-completed-at');
        });
    }

    /**
     * Test project name is linked
     */
    public function test_project_name_linked(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Project:')
                ->assertSee($this->deployment->project->name)
                ->assertPresent('a[href*="/projects/'.$this->deployment->project->id.'"]')
                ->screenshot('deployment-project-linked');
        });
    }

    /**
     * Test server name is displayed
     */
    public function test_server_name_displayed(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Server:')
                ->screenshot('deployment-server-name');
        });
    }

    /**
     * Test triggered by is shown
     */
    public function test_triggered_by_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'triggered_by' => 'manual',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Triggered By')
                ->assertSee('Manual')
                ->screenshot('deployment-triggered-by');
        });
    }

    /**
     * Test refresh button works for running deployments
     */
    public function test_refresh_button_works(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Refresh')
                ->click('button:has-text("Refresh")')
                ->pause(500)
                ->assertPresent('button:has-text("Refresh")')
                ->screenshot('deployment-refresh-button');
        });
    }

    /**
     * Test auto-scroll logs feature
     */
    public function test_auto_scroll_logs_feature(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'output_log' => implode("\n", array_fill(0, 50, 'Log line content for testing')),
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Scroll to Bottom')
                ->assertPresent('button:has-text("Pause Auto-scroll")')
                ->screenshot('deployment-auto-scroll-logs');
        });
    }

    /**
     * Test log level colors are applied
     */
    public function test_log_level_colors_applied(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'output_log' => "INFO: Starting deployment\nERROR: Failed to connect to database\nWARNING: Deprecated package found",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Failed to connect to database')
                ->assertPresent('div.text-red-400')
                ->assertPresent('div.text-yellow-400')
                ->screenshot('deployment-log-level-colors');
        });
    }

    /**
     * Test error logs section displayed when errors exist
     */
    public function test_error_logs_section_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_log' => 'Database connection failed: Access denied for user',
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Error Logs')
                ->assertSee('Database connection failed')
                ->screenshot('deployment-error-logs-section');
        });
    }

    /**
     * Test back to list button works
     */
    public function test_back_to_list_button_works(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Back to List')
                ->click('a:has-text("Back to List")')
                ->pause(1000)
                ->assertPathIs('/deployments')
                ->screenshot('deployment-back-to-list');
        });
    }

    /**
     * Test deployment steps progress shown for running deployment
     */
    public function test_deployment_steps_progress_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'output_log' => '=== Cloning Repository ==='."\n".'✓ Repository cloned successfully'."\n".'=== Building Docker Container ===',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Deployment Steps')
                ->assertSee('Clone Repository')
                ->assertSee('Build Docker Image')
                ->screenshot('deployment-steps-progress');
        });
    }

    /**
     * Test estimated time shown for running deployment
     */
    public function test_estimated_time_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Estimated time')
                ->assertSee('minutes')
                ->screenshot('deployment-estimated-time');
        });
    }

    /**
     * Test live streaming indicator shown for running deployment
     */
    public function test_live_streaming_indicator_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertSee('Live Streaming')
                ->assertPresent('span.animate-pulse')
                ->screenshot('deployment-live-streaming-indicator');
        });
    }

    /**
     * Test deployment success status styling
     */
    public function test_deployment_success_status_styling(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $successDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
            'duration_seconds' => 900,
        ]);

        $this->browse(function (Browser $browser) use ($successDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$successDeployment->id)
                ->assertSee('Success')
                ->assertPresent('span.from-emerald-500, span.from-green-500')
                ->screenshot('deployment-success-styling');
        });
    }

    /**
     * Test deployment failed status styling
     */
    public function test_deployment_failed_status_styling(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $failedDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'failed',
            'error_log' => 'Critical deployment error',
        ]);

        $this->browse(function (Browser $browser) use ($failedDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$failedDeployment->id)
                ->assertSee('Failed')
                ->assertPresent('span.from-red-500, span.from-rose-500')
                ->screenshot('deployment-failed-styling');
        });
    }

    /**
     * Test deployment pending status displayed
     */
    public function test_deployment_pending_status_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $pendingDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($pendingDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$pendingDeployment->id)
                ->assertSee('Pending')
                ->assertPresent('span.from-blue-500, span.from-indigo-500')
                ->assertSee('Deployment Pending')
                ->screenshot('deployment-pending-status');
        });
    }

    /**
     * Test page auto-refreshes for running deployment
     */
    public function test_page_auto_refreshes_for_running_deployment(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $runningDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($runningDeployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$runningDeployment->id)
                ->assertPresent('[wire\\:poll\\.3s]')
                ->assertSee('auto-refreshes every 3 seconds')
                ->screenshot('deployment-auto-refresh');
        });
    }

    /**
     * Test deployment details card displayed
     */
    public function test_deployment_details_card_displayed(): void
    {
        if (! $this->deployment) {
            $this->markTestSkipped('No deployment found in database');

            return;
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$this->deployment->id)
                ->assertSee('Deployment Details')
                ->assertSee('Project:')
                ->assertSee('Server:')
                ->assertSee('Commit Hash:')
                ->screenshot('deployment-details-card');
        });
    }

    /**
     * Test no logs message displayed when logs empty
     */
    public function test_no_logs_message_when_empty(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'output_log' => null,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('No logs available')
                ->screenshot('deployment-no-logs');
        });
    }

    /**
     * Test log line numbers displayed
     */
    public function test_log_line_numbers_displayed(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'output_log' => "Line 1\nLine 2\nLine 3\nLine 4",
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('1')
                ->assertSee('2')
                ->assertSee('3')
                ->assertSee('Line 1')
                ->screenshot('deployment-log-line-numbers');
        });
    }

    /**
     * Test starting deployment message shown
     */
    public function test_starting_deployment_message_shown(): void
    {
        $server = Server::first() ?? Server::factory()->create(['user_id' => $this->user->id]);
        $project = Project::first() ?? Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'running',
            'started_at' => now(),
            'output_log' => null,
        ]);

        $this->browse(function (Browser $browser) use ($deployment) {
            $this->loginViaUI($browser)
                ->visit('/deployments/'.$deployment->id)
                ->assertSee('Starting deployment')
                ->screenshot('deployment-starting-message');
        });
    }
}
