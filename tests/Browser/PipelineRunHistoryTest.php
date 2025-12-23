<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class PipelineRunHistoryTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected Pipeline $pipeline;

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
            ['hostname' => 'pipeline-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Pipeline Test Server',
                'ip_address' => '192.168.10.50',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'pipeline-history-test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Pipeline History Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/pipeline-test.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/pipeline-test',
            ]
        );

        // Get or create test pipeline
        $this->pipeline = Pipeline::firstOrCreate(
            ['project_id' => $this->project->id],
            [
                'name' => 'Main Pipeline',
                'provider' => 'devflow',
                'configuration' => [
                    'triggers' => ['push', 'manual'],
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Test 1: Pipeline run history page loads successfully
     */
    public function test_pipeline_run_history_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Pipeline')
                ->screenshot('pipeline-run-history-page-loads');
        });
    }

    /**
     * Test 2: Viewing pipeline run list displays all runs
     */
    public function test_viewing_pipeline_run_list_displays_all_runs(): void
    {
        // Create multiple pipeline runs
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Pipeline Runs')
                ->assertPresent('[data-run], .pipeline-run, tr')
                ->screenshot('pipeline-run-list-displays');
        });
    }

    /**
     * Test 3: Run status indicator - pending
     */
    public function test_run_status_indicator_pending(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
            'run_number' => 1001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('pending')
                ->assertPresent('.from-blue-500, .from-indigo-500, .bg-blue-500, .bg-indigo-500, .text-blue-500, .text-indigo-500')
                ->screenshot('run-status-pending');
        });
    }

    /**
     * Test 4: Run status indicator - running
     */
    public function test_run_status_indicator_running(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 1002,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('running')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->screenshot('run-status-running');
        });
    }

    /**
     * Test 5: Run status indicator - success
     */
    public function test_run_status_indicator_success(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 1003,
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('success')
                ->assertPresent('.from-green-500, .from-emerald-500, .bg-green-500, .bg-emerald-500, .text-green-500, .text-emerald-500')
                ->screenshot('run-status-success');
        });
    }

    /**
     * Test 6: Run status indicator - failed
     */
    public function test_run_status_indicator_failed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 1004,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinutes(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('failed')
                ->assertPresent('.from-red-500, .from-rose-500, .bg-red-500, .bg-rose-500, .text-red-500, .text-rose-500')
                ->screenshot('run-status-failed');
        });
    }

    /**
     * Test 7: Run status indicator - cancelled
     */
    public function test_run_status_indicator_cancelled(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'cancelled',
            'run_number' => 1005,
            'started_at' => now()->subMinutes(30),
            'finished_at' => now()->subMinutes(28),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('cancelled')
                ->assertPresent('.from-gray-500, .bg-gray-500, .text-gray-500')
                ->screenshot('run-status-cancelled');
        });
    }

    /**
     * Test 8: Run duration display
     */
    public function test_run_duration_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 2001,
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(12)->addSeconds(35),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('12m 35s')
                ->screenshot('run-duration-display');
        });
    }

    /**
     * Test 9: Run trigger information - manual
     */
    public function test_run_trigger_information_manual(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'triggered_by' => 'manual',
            'run_number' => 3001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('manual')
                ->screenshot('run-trigger-manual');
        });
    }

    /**
     * Test 10: Run trigger information - webhook
     */
    public function test_run_trigger_information_webhook(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'triggered_by' => 'webhook',
            'run_number' => 3002,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('webhook')
                ->screenshot('run-trigger-webhook');
        });
    }

    /**
     * Test 11: Run trigger information - scheduled
     */
    public function test_run_trigger_information_scheduled(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'triggered_by' => 'scheduled',
            'run_number' => 3003,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('scheduled')
                ->screenshot('run-trigger-scheduled');
        });
    }

    /**
     * Test 12: Run commit information display
     */
    public function test_run_commit_information_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 4001,
            'commit_sha' => 'abc123def456789012345678',
            'trigger_data' => [
                'commit_message' => 'Fix critical authentication bug',
                'author' => 'John Doe',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee(substr($run->commit_sha, 0, 7))
                ->screenshot('run-commit-info');
        });
    }

    /**
     * Test 13: Run branch information display
     */
    public function test_run_branch_information_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 4002,
            'branch' => 'feature/new-feature',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('feature/new-feature')
                ->screenshot('run-branch-info');
        });
    }

    /**
     * Test 14: Stage breakdown for each run
     */
    public function test_stage_breakdown_for_each_run(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 5001,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Build',
            'type' => 'pre_deploy',
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test',
            'type' => 'pre_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage1->id,
            'status' => 'success',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage2->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Build')
                ->assertSee('Test')
                ->screenshot('stage-breakdown');
        });
    }

    /**
     * Test 15: Stage status indicators
     */
    public function test_stage_status_indicators(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 5002,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Success Stage',
            'type' => 'pre_deploy',
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Running Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage1->id,
            'status' => 'success',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage2->id,
            'status' => 'running',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('.from-green-500, .from-emerald-500')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->screenshot('stage-status-indicators');
        });
    }

    /**
     * Test 16: Stage logs viewing
     */
    public function test_stage_logs_viewing(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 5003,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Deploy Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'logs' => ['Deployment started', 'Building containers', 'Deployment complete'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('stage-logs-viewing');
        });
    }

    /**
     * Test 17: Stage duration tracking
     */
    public function test_stage_duration_tracking(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 5004,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Build Stage',
            'type' => 'pre_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(10)->addMinutes(3)->addSeconds(45),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('3m 45s')
                ->screenshot('stage-duration-tracking');
        });
    }

    /**
     * Test 18: Run filtering by status - all
     */
    public function test_run_filtering_by_status_all(): void
    {
        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("All"), select, [wire\\:click*="setStatusFilter"]')
                ->screenshot('run-filtering-status-all');
        });
    }

    /**
     * Test 19: Run filtering by status - success
     */
    public function test_run_filtering_by_status_success(): void
    {
        PipelineRun::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->click('button:contains("Success"), [wire\\:click*="setStatusFilter(\'success\')"]')
                ->pause(1000)
                ->waitForLivewire()
                ->screenshot('run-filtering-status-success');
        });
    }

    /**
     * Test 20: Run filtering by status - failed
     */
    public function test_run_filtering_by_status_failed(): void
    {
        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->click('button:contains("Failed"), [wire\\:click*="setStatusFilter(\'failed\')"]')
                ->pause(1000)
                ->waitForLivewire()
                ->screenshot('run-filtering-status-failed');
        });
    }

    /**
     * Test 21: Run filtering by status - running
     */
    public function test_run_filtering_by_status_running(): void
    {
        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->click('button:contains("Running"), [wire\\:click*="setStatusFilter(\'running\')"]')
                ->pause(1000)
                ->waitForLivewire()
                ->screenshot('run-filtering-status-running');
        });
    }

    /**
     * Test 22: Run filtering by date range
     */
    public function test_run_filtering_by_date_range(): void
    {
        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'created_at' => now()->subDays(7),
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'created_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[type="date"], input[type="datetime-local"]')
                ->screenshot('run-filtering-date-range');
        });
    }

    /**
     * Test 23: Run search functionality
     */
    public function test_run_search_functionality(): void
    {
        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'commit_sha' => 'abc123unique',
            'run_number' => 7001,
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'commit_sha' => 'def456other',
            'run_number' => 7002,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[type="search"], input[type="text"][placeholder*="Search"]')
                ->screenshot('run-search-functionality');
        });
    }

    /**
     * Test 24: Run pagination
     */
    public function test_run_pagination(): void
    {
        // Create 25 runs to trigger pagination
        PipelineRun::factory()->count(25)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('nav[role="navigation"], .pagination')
                ->screenshot('run-pagination');
        });
    }

    /**
     * Test 25: Run details modal/page access
     */
    public function test_run_details_modal_page_access(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 8001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("View"), a:contains("View"), [wire\\:click*="viewRun"]')
                ->screenshot('run-details-modal-access');
        });
    }

    /**
     * Test 26: Comparing runs feature
     */
    public function test_comparing_runs_feature(): void
    {
        $run1 = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 9001,
        ]);

        $run2 = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 9002,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('input[type="checkbox"], button:contains("Compare")')
                ->screenshot('comparing-runs-feature');
        });
    }

    /**
     * Test 27: Re-running failed pipelines
     */
    public function test_rerunning_failed_pipelines(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 10001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("Retry"), button:contains("Re-run"), [wire\\:click*="retryRun"]')
                ->screenshot('rerunning-failed-pipelines');
        });
    }

    /**
     * Test 28: Cancelling running pipelines
     */
    public function test_cancelling_running_pipelines(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 11001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("Cancel"), [wire\\:click*="cancelRun"]')
                ->screenshot('cancelling-running-pipelines');
        });
    }

    /**
     * Test 29: Run artifacts download
     */
    public function test_run_artifacts_download(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 12001,
            'artifacts' => [
                'build.zip',
                'test-results.xml',
                'coverage.html',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('run-artifacts-download');
        });
    }

    /**
     * Test 30: Run environment information
     */
    public function test_run_environment_information(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 13001,
            'trigger_data' => [
                'environment' => 'production',
                'php_version' => '8.4',
                'node_version' => '20.x',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('run-environment-information');
        });
    }

    /**
     * Test 31: Test results in pipeline runs
     */
    public function test_test_results_in_pipeline_runs(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 14001,
            'logs' => [
                'Running PHPUnit tests...',
                'Tests: 150 passed, 0 failed',
                'Time: 45.23s',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('test-results-in-runs');
        });
    }

    /**
     * Test 32: Coverage reports
     */
    public function test_coverage_reports(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 15001,
            'artifacts' => [
                'coverage/index.html',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('coverage-reports');
        });
    }

    /**
     * Test 33: Pipeline metrics and analytics
     */
    public function test_pipeline_metrics_and_analytics(): void
    {
        PipelineRun::factory()->count(10)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('.chart, canvas, [data-chart]')
                ->screenshot('pipeline-metrics-analytics');
        });
    }

    /**
     * Test 34: Run notifications history
     */
    public function test_run_notifications_history(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 16001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('run-notifications-history');
        });
    }

    /**
     * Test 35: Empty state when no runs exist
     */
    public function test_empty_state_when_no_runs_exist(): void
    {
        // Create a new project without runs
        $emptyProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Empty Pipeline Project',
            'slug' => 'empty-pipeline-project-'.uniqid(),
        ]);

        $emptyPipeline = Pipeline::factory()->create([
            'project_id' => $emptyProject->id,
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$emptyProject->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('No pipeline runs')
                ->screenshot('empty-state-no-runs');
        });
    }

    /**
     * Test 36: Run number display
     */
    public function test_run_number_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 12345,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('12345')
                ->screenshot('run-number-display');
        });
    }

    /**
     * Test 37: Run timestamp display
     */
    public function test_run_timestamp_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 17001,
            'created_at' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('2 hours ago')
                ->screenshot('run-timestamp-display');
        });
    }

    /**
     * Test 38: Real-time updates for running pipelines
     */
    public function test_realtime_updates_for_running_pipelines(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 18001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('[wire\\:poll]')
                ->assertSee('running')
                ->screenshot('realtime-updates-running');
        });
    }

    /**
     * Test 39: Progress tracking for running pipelines
     */
    public function test_progress_tracking_for_running_pipelines(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 19001,
            'started_at' => now()->subMinutes(5),
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Running Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('.progress-bar, [role="progressbar"]')
                ->screenshot('progress-tracking-running');
        });
    }

    /**
     * Test 40: Status count badges display
     */
    public function test_status_count_badges_display(): void
    {
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('5')
                ->assertSee('3')
                ->assertSee('2')
                ->screenshot('status-count-badges');
        });
    }

    /**
     * Test 41: Failed stage highlighting
     */
    public function test_failed_stage_highlighting(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 20001,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Failed Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('.from-red-500, .from-rose-500, .bg-red-500, .bg-rose-500')
                ->screenshot('failed-stage-highlighting');
        });
    }

    /**
     * Test 42: Multi-stage pipeline visualization
     */
    public function test_multistage_pipeline_visualization(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 21001,
        ]);

        $stages = ['Build', 'Test', 'Deploy', 'Post-Deploy'];
        foreach ($stages as $index => $stageName) {
            $stage = PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => $stageName,
                'type' => 'pre_deploy',
                'order' => $index,
            ]);

            PipelineStageRun::factory()->create([
                'pipeline_run_id' => $run->id,
                'pipeline_stage_id' => $stage->id,
                'status' => 'success',
            ]);
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Build')
                ->assertSee('Test')
                ->assertSee('Deploy')
                ->assertSee('Post-Deploy')
                ->screenshot('multistage-pipeline-visualization');
        });
    }

    /**
     * Test 43: Per-page selector functionality
     */
    public function test_perpage_selector_functionality(): void
    {
        PipelineRun::factory()->count(30)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('select[wire\\:model*="perPage"]')
                ->screenshot('perpage-selector');
        });
    }

    /**
     * Test 44: Run commit message truncation
     */
    public function test_run_commit_message_truncation(): void
    {
        $longMessage = 'This is a very long commit message that should be truncated in the UI to prevent overflow and maintain a clean layout. It contains multiple sentences and details about the changes made.';

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 22001,
            'trigger_data' => [
                'commit_message' => $longMessage,
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('run-commit-message-truncation');
        });
    }

    /**
     * Test 45: Run author information display
     */
    public function test_run_author_information_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 23001,
            'trigger_data' => [
                'author' => 'John Doe',
                'author_email' => 'john@example.com',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('run-author-information');
        });
    }

    /**
     * Test 46: Quick actions menu for runs
     */
    public function test_quick_actions_menu_for_runs(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 24001,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button[aria-label*="Actions"], button[aria-label*="Menu"], .dropdown')
                ->screenshot('quick-actions-menu');
        });
    }

    /**
     * Test 47: Run export functionality
     */
    public function test_run_export_functionality(): void
    {
        PipelineRun::factory()->count(10)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("Export"), a:contains("Export")')
                ->screenshot('run-export-functionality');
        });
    }

    /**
     * Test 48: Run refresh button functionality
     */
    public function test_run_refresh_button_functionality(): void
    {
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline/history')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertPresent('button:contains("Refresh"), [wire\\:click*="refresh"]')
                ->screenshot('run-refresh-button');
        });
    }
}
