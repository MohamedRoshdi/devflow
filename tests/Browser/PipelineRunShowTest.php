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

class PipelineRunShowTest extends DuskTestCase
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
            ['slug' => 'test-pipeline-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Pipeline Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
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
     * Test 1: Pipeline run detail page access
     */
    public function test_pipeline_run_detail_page_access(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 1,
            'started_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Run #'.$run->run_number)
                ->screenshot('pipeline-run-detail-page-access');
        });
    }

    /**
     * Test 2: Run status display - pending
     */
    public function test_run_status_display_pending(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
            'run_number' => 10,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('pending')
                ->assertPresent('span.from-blue-500, span.from-indigo-500, span.bg-blue-500, span.bg-indigo-500, span.text-blue-500, span.text-indigo-500')
                ->screenshot('pipeline-run-status-pending');
        });
    }

    /**
     * Test 3: Run status display - running
     */
    public function test_run_status_display_running(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 11,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('running')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->assertPresent('span.from-yellow-500, span.from-amber-500, span.bg-yellow-500, span.bg-amber-500, span.text-yellow-500, span.text-amber-500')
                ->screenshot('pipeline-run-status-running');
        });
    }

    /**
     * Test 4: Run status display - success
     */
    public function test_run_status_display_success(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 12,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('success')
                ->assertPresent('span.from-green-500, span.from-emerald-500, span.bg-green-500, span.bg-emerald-500, span.text-green-500, span.text-emerald-500')
                ->screenshot('pipeline-run-status-success');
        });
    }

    /**
     * Test 5: Run status display - failed
     */
    public function test_run_status_display_failed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 13,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(3),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('failed')
                ->assertPresent('span.from-red-500, span.from-rose-500, span.bg-red-500, span.bg-rose-500, span.text-red-500, span.text-rose-500')
                ->screenshot('pipeline-run-status-failed');
        });
    }

    /**
     * Test 6: Run status display - cancelled
     */
    public function test_run_status_display_cancelled(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'cancelled',
            'run_number' => 14,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(1),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('cancelled')
                ->assertPresent('span.from-gray-500, span.bg-gray-500, span.text-gray-500')
                ->screenshot('pipeline-run-status-cancelled');
        });
    }

    /**
     * Test 7: Run duration display for completed run
     */
    public function test_run_duration_display_completed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 20,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(15)->addSeconds(30),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('15m 30s')
                ->screenshot('pipeline-run-duration-completed');
        });
    }

    /**
     * Test 8: Run timestamps display
     */
    public function test_run_timestamps_display(): void
    {
        $startedAt = now()->subHour();
        $finishedAt = now()->subHour()->addMinutes(10);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 21,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Started')
                ->assertSee('Finished')
                ->screenshot('pipeline-run-timestamps');
        });
    }

    /**
     * Test 9: Trigger information - manual
     */
    public function test_trigger_information_manual(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 30,
            'triggered_by' => 'manual',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('manual')
                ->assertSee('Triggered by')
                ->screenshot('pipeline-run-trigger-manual');
        });
    }

    /**
     * Test 10: Trigger information - webhook
     */
    public function test_trigger_information_webhook(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 31,
            'triggered_by' => 'webhook',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('webhook')
                ->screenshot('pipeline-run-trigger-webhook');
        });
    }

    /**
     * Test 11: Trigger information - schedule
     */
    public function test_trigger_information_schedule(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 32,
            'triggered_by' => 'schedule',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('schedule')
                ->screenshot('pipeline-run-trigger-schedule');
        });
    }

    /**
     * Test 12: Commit information display
     */
    public function test_commit_information_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 40,
            'commit_sha' => 'abc123def456789012345678',
            'trigger_data' => [
                'commit_message' => 'Fix critical bug in authentication',
                'author' => 'John Doe',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee(substr($run->commit_sha, 0, 7))
                ->assertSee('Commit')
                ->screenshot('pipeline-run-commit-info');
        });
    }

    /**
     * Test 13: Branch information display
     */
    public function test_branch_information_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 41,
            'branch' => 'feature/new-feature',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('feature/new-feature')
                ->assertSee('Branch')
                ->screenshot('pipeline-run-branch-info');
        });
    }

    /**
     * Test 14: Stage list displays correctly
     */
    public function test_stage_list_displays_correctly(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 50,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
            'order' => 0,
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Run Tests',
            'type' => 'pre_deploy',
            'order' => 1,
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

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Install Dependencies')
                ->assertSee('Run Tests')
                ->screenshot('pipeline-run-stage-list');
        });
    }

    /**
     * Test 15: Stage status indicators - success
     */
    public function test_stage_status_indicators_success(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 51,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Successful Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Successful Stage')
                ->assertSee('success')
                ->screenshot('pipeline-run-stage-status-success');
        });
    }

    /**
     * Test 16: Stage status indicators - failed
     */
    public function test_stage_status_indicators_failed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 52,
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
            'error_message' => 'Deployment failed: Connection timeout',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Failed Stage')
                ->assertSee('failed')
                ->screenshot('pipeline-run-stage-status-failed');
        });
    }

    /**
     * Test 17: Stage status indicators - running
     */
    public function test_stage_status_indicators_running(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 53,
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

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Running Stage')
                ->assertSee('running')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->screenshot('pipeline-run-stage-status-running');
        });
    }

    /**
     * Test 18: Stage status indicators - pending
     */
    public function test_stage_status_indicators_pending(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 54,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Pending Stage',
            'type' => 'post_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Pending Stage')
                ->assertSee('pending')
                ->screenshot('pipeline-run-stage-status-pending');
        });
    }

    /**
     * Test 19: Stage status indicators - skipped
     */
    public function test_stage_status_indicators_skipped(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 55,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Skipped Stage',
            'type' => 'post_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'skipped',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Skipped Stage')
                ->assertSee('skipped')
                ->screenshot('pipeline-run-stage-status-skipped');
        });
    }

    /**
     * Test 20: Stage execution order display
     */
    public function test_stage_execution_order_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 60,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'First Stage',
            'type' => 'pre_deploy',
            'order' => 0,
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Second Stage',
            'type' => 'pre_deploy',
            'order' => 1,
        ]);

        $stage3 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Third Stage',
            'type' => 'deploy',
            'order' => 0,
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage1->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage2->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(9),
            'completed_at' => now()->subMinutes(7),
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage3->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(7),
            'completed_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('First Stage')
                ->assertSee('Second Stage')
                ->assertSee('Third Stage')
                ->screenshot('pipeline-run-stage-execution-order');
        });
    }

    /**
     * Test 21: Stage duration per stage
     */
    public function test_stage_duration_per_stage(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 61,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Timed Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(3),
            'duration_seconds' => 120,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Timed Stage')
                ->assertSee('2m')
                ->screenshot('pipeline-run-stage-duration');
        });
    }

    /**
     * Test 22: Stage logs viewing toggle
     */
    public function test_stage_logs_viewing_toggle(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 70,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage with Logs',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'output' => "Installing dependencies...\nBuilding application...\nDeployment successful!",
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Stage with Logs')
                ->click('button[wire\\:click*="toggleStage"], .stage-header, .stage-name')
                ->pause(500)
                ->assertSee('Installing dependencies')
                ->screenshot('pipeline-run-stage-logs-toggle');
        });
    }

    /**
     * Test 23: Stage output display
     */
    public function test_stage_output_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 71,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Output Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'output' => "Cloning repository...\nInstalling packages...\nRunning migrations...\nCompleted successfully!",
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header, .stage-name')
                ->pause(500)
                ->assertSee('Cloning repository')
                ->assertSee('Running migrations')
                ->screenshot('pipeline-run-stage-output');
        });
    }

    /**
     * Test 24: Stage commands executed display
     */
    public function test_stage_commands_executed_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 72,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Command Stage',
            'type' => 'deploy',
            'commands' => ['composer install', 'npm install', 'php artisan migrate'],
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header, .stage-name')
                ->pause(500)
                ->assertSee('composer install')
                ->screenshot('pipeline-run-stage-commands');
        });
    }

    /**
     * Test 25: Stage environment variables display
     */
    public function test_stage_environment_variables_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 73,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Env Vars Stage',
            'type' => 'deploy',
            'environment_variables' => [
                'APP_ENV' => 'production',
                'DEBUG_MODE' => 'false',
            ],
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header, .stage-name')
                ->pause(500)
                ->screenshot('pipeline-run-stage-env-vars');
        });
    }

    /**
     * Test 26: Failed stage error details display
     */
    public function test_failed_stage_error_details_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 80,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Failed Build Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'failed',
            'error_message' => 'Fatal error: Class not found in /app/src/Controller.php on line 42',
            'output' => "Running composer install...\nRunning php artisan migrate...\nError occurred!",
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Failed Build Stage')
                ->click('button[wire\\:click*="toggleStage"], .stage-header, .stage-name')
                ->pause(500)
                ->assertSee('Fatal error')
                ->assertSee('Class not found')
                ->screenshot('pipeline-run-stage-error-details');
        });
    }

    /**
     * Test 27: Stage retry functionality button exists
     */
    public function test_stage_retry_functionality_button_exists(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 81,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Retry Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'failed',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Retry"), button[wire\\:click*="retry"]')
                ->screenshot('pipeline-run-stage-retry-button');
        });
    }

    /**
     * Test 28: Cancel running pipeline button exists
     */
    public function test_cancel_running_pipeline_button_exists(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 90,
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Cancel"), button[wire\\:click*="cancel"]')
                ->screenshot('pipeline-run-cancel-button');
        });
    }

    /**
     * Test 29: Cancel running pipeline functionality
     */
    public function test_cancel_running_pipeline_functionality(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 91,
            'started_at' => now()->subMinutes(3),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button[wire\\:click*="cancelPipeline"]')
                ->screenshot('pipeline-run-cancel-functionality');
        });
    }

    /**
     * Test 30: Re-run pipeline button for completed pipeline
     */
    public function test_rerun_pipeline_button_completed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 100,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Re-run"), button:contains("Retry"), button[wire\\:click*="retry"]')
                ->screenshot('pipeline-run-rerun-button');
        });
    }

    /**
     * Test 31: Re-run failed pipeline
     */
    public function test_rerun_failed_pipeline(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 101,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button[wire\\:click*="retryPipeline"]')
                ->screenshot('pipeline-run-rerun-failed');
        });
    }

    /**
     * Test 32: Artifacts download button exists
     */
    public function test_artifacts_download_button_exists(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 110,
            'artifacts' => [
                'build.zip' => '/path/to/build.zip',
                'coverage.html' => '/path/to/coverage.html',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Download"), a:contains("Artifacts")')
                ->screenshot('pipeline-run-artifacts-download');
        });
    }

    /**
     * Test 33: Test results display
     */
    public function test_test_results_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 120,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Run Tests',
            'type' => 'pre_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'output' => "Running PHPUnit tests...\nTests: 125 passed, 0 failed\nTime: 45.32s",
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header')
                ->pause(500)
                ->assertSee('Running PHPUnit tests')
                ->assertSee('125 passed')
                ->screenshot('pipeline-run-test-results');
        });
    }

    /**
     * Test 34: Code coverage display
     */
    public function test_code_coverage_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 121,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Coverage Report',
            'type' => 'pre_deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'output' => "Code Coverage Report:\nLines: 87.5%\nFunctions: 92.3%\nBranches: 78.9%",
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header')
                ->pause(500)
                ->assertSee('Coverage Report')
                ->assertSee('87.5%')
                ->screenshot('pipeline-run-code-coverage');
        });
    }

    /**
     * Test 35: Pipeline metrics display
     */
    public function test_pipeline_metrics_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 130,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(12),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('12m')
                ->screenshot('pipeline-run-metrics');
        });
    }

    /**
     * Test 36: Overall progress percentage display
     */
    public function test_overall_progress_percentage_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 140,
        ]);

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Completed Stage 1',
            'type' => 'pre_deploy',
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Completed Stage 2',
            'type' => 'pre_deploy',
        ]);

        $stage3 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Running Stage 3',
            'type' => 'deploy',
        ]);

        $stage4 = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Pending Stage 4',
            'type' => 'post_deploy',
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

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage3->id,
            'status' => 'running',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage4->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('.progress-bar, [role="progressbar"], .w-\\[50%\\]')
                ->screenshot('pipeline-run-progress-percentage');
        });
    }

    /**
     * Test 37: Stage statistics summary display
     */
    public function test_stage_statistics_summary_display(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 141,
        ]);

        // Create 5 stages with different statuses
        for ($i = 0; $i < 5; $i++) {
            $stage = PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => "Stage $i",
                'type' => 'pre_deploy',
            ]);

            $status = match ($i) {
                0, 1 => 'success',
                2 => 'running',
                3 => 'failed',
                4 => 'pending',
            };

            PipelineStageRun::factory()->create([
                'pipeline_run_id' => $run->id,
                'pipeline_stage_id' => $stage->id,
                'status' => $status,
            ]);
        }

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('2')
                ->assertSee('success')
                ->screenshot('pipeline-run-stage-statistics');
        });
    }

    /**
     * Test 38: Real-time progress updates indicator
     */
    public function test_realtime_progress_updates_indicator(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 150,
            'started_at' => now()->subMinutes(3),
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Live Update Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(1),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('.animate-spin, .animate-pulse, [wire\\:poll]')
                ->screenshot('pipeline-run-realtime-updates');
        });
    }

    /**
     * Test 39: Stage timeline visualization
     */
    public function test_stage_timeline_visualization(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 160,
        ]);

        $stages = [];
        for ($i = 0; $i < 4; $i++) {
            $stage = PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => "Timeline Stage $i",
                'type' => 'pre_deploy',
                'order' => $i,
            ]);

            PipelineStageRun::factory()->create([
                'pipeline_run_id' => $run->id,
                'pipeline_stage_id' => $stage->id,
                'status' => 'success',
                'started_at' => now()->subMinutes(10 - $i * 2),
                'completed_at' => now()->subMinutes(9 - $i * 2),
            ]);

            $stages[] = $stage;
        }

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Timeline Stage 0')
                ->assertSee('Timeline Stage 1')
                ->assertSee('Timeline Stage 2')
                ->assertSee('Timeline Stage 3')
                ->screenshot('pipeline-run-timeline-visualization');
        });
    }

    /**
     * Test 40: Expand all stages button
     */
    public function test_expand_all_stages_button(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 170,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $stage = PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => "Expandable Stage $i",
                'type' => 'pre_deploy',
            ]);

            PipelineStageRun::factory()->create([
                'pipeline_run_id' => $run->id,
                'pipeline_stage_id' => $stage->id,
                'status' => 'success',
            ]);
        }

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Expand All"), button[wire\\:click*="expandAll"]')
                ->screenshot('pipeline-run-expand-all-button');
        });
    }

    /**
     * Test 41: Collapse all stages button
     */
    public function test_collapse_all_stages_button(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 171,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $stage = PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => "Collapsible Stage $i",
                'type' => 'pre_deploy',
            ]);

            PipelineStageRun::factory()->create([
                'pipeline_run_id' => $run->id,
                'pipeline_stage_id' => $stage->id,
                'status' => 'success',
            ]);
        }

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Collapse All"), button[wire\\:click*="collapseAll"]')
                ->screenshot('pipeline-run-collapse-all-button');
        });
    }

    /**
     * Test 42: Auto-scroll toggle functionality
     */
    public function test_auto_scroll_toggle_functionality(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 180,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Auto-scroll"), input[type="checkbox"][wire\\:model*="autoScroll"]')
                ->screenshot('pipeline-run-auto-scroll-toggle');
        });
    }

    /**
     * Test 43: Download stage output functionality
     */
    public function test_download_stage_output_functionality(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 190,
        ]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Downloadable Output Stage',
            'type' => 'deploy',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'success',
            'output' => "Very long output\n".str_repeat("Log line\n", 100),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->click('button[wire\\:click*="toggleStage"], .stage-header')
                ->pause(500)
                ->assertPresent('button:contains("Download"), a:contains("Download")')
                ->screenshot('pipeline-run-download-output');
        });
    }

    /**
     * Test 44: Empty pipeline run (no stages) handling
     */
    public function test_empty_pipeline_run_no_stages_handling(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
            'run_number' => 200,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('No stages')
                ->screenshot('pipeline-run-empty-no-stages');
        });
    }

    /**
     * Test 45: Project navigation from run detail
     */
    public function test_project_navigation_from_run_detail(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 210,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee($this->project->name)
                ->assertPresent('a[href*="/projects"]')
                ->screenshot('pipeline-run-project-navigation');
        });
    }

    /**
     * Test 46: Pipeline navigation from run detail
     */
    public function test_pipeline_navigation_from_run_detail(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 211,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('a[href*="/pipeline"]')
                ->screenshot('pipeline-run-pipeline-navigation');
        });
    }

    /**
     * Test 47: Refresh pipeline data button
     */
    public function test_refresh_pipeline_data_button(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 220,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertPresent('button:contains("Refresh"), button[wire\\:click*="refresh"]')
                ->screenshot('pipeline-run-refresh-button');
        });
    }

    /**
     * Test 48: Pipeline run shows correct run number
     */
    public function test_pipeline_run_shows_correct_run_number(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 999,
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/pipelines/runs/'.$run->id)
                ->waitForText('Pipeline Run', 10)
                ->assertSee('Run #999')
                ->assertSee('#999')
                ->screenshot('pipeline-run-correct-run-number');
        });
    }
}
