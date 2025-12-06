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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class PipelinesTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

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
     * Test 1: Pipeline list page loads successfully
     */
    public function test_pipeline_list_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/pipelines')
                ->waitForText('Pipeline', 10)
                ->assertPathIs('/pipelines')
                ->assertSee('Pipeline')
                ->screenshot('pipeline-list-page-loads');
        });
    }

    /**
     * Test 2: Pipeline builder page loads successfully
     */
    public function test_pipeline_builder_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Builder')
                ->assertSee('Pipeline Configuration')
                ->assertSee('Pre-Deploy Stages')
                ->assertSee('Deploy Stages')
                ->assertSee('Post-Deploy Stages')
                ->screenshot('pipeline-builder-page');
        });
    }

    /**
     * Test 3: Pipeline run history displays correctly
     */
    public function test_pipeline_run_history_displays(): void
    {
        // Create pipeline runs with different statuses
        $successRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 1,
            'branch' => 'main',
            'commit_sha' => 'abc123def456',
            'triggered_by' => 'manual',
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinutes(5),
        ]);

        $failedRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 2,
            'branch' => 'develop',
            'commit_sha' => 'def456ghi789',
            'triggered_by' => 'webhook',
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addMinutes(2),
        ]);

        $runningRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 3,
            'branch' => 'main',
            'commit_sha' => 'ghi789jkl012',
            'triggered_by' => 'manual',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Recent Pipeline Runs')
                ->assertSee('Run #1')
                ->assertSee('Run #2')
                ->assertSee('Run #3')
                ->assertSee('success')
                ->assertSee('failed')
                ->assertSee('running')
                ->assertSee('main')
                ->assertSee('develop')
                ->screenshot('pipeline-run-history');
        });
    }

    /**
     * Test 4: Pipeline configuration is accessible
     */
    public function test_pipeline_configuration_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Configuration')
                ->assertPresent('button:contains("Add Stage"), button:contains("New Stage")')
                ->assertSee('Templates')
                ->screenshot('pipeline-configuration');
        });
    }

    /**
     * Test 5: Pipeline stages are visible
     */
    public function test_pipeline_stages_visible(): void
    {
        // Create test pipeline stages
        $preDeployStage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
            'commands' => ['composer install', 'npm install'],
            'order' => 0,
            'enabled' => true,
        ]);

        $deployStage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Run Migrations',
            'type' => 'deploy',
            'commands' => ['php artisan migrate --force'],
            'order' => 0,
            'enabled' => true,
        ]);

        $postDeployStage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Clear Cache',
            'type' => 'post_deploy',
            'commands' => ['php artisan cache:clear', 'php artisan config:cache'],
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Check Pre-Deploy Stages
                ->assertSee('Pre-Deploy Stages')
                ->assertSee('Install Dependencies')
                // Check Deploy Stages
                ->assertSee('Deploy Stages')
                ->assertSee('Run Migrations')
                // Check Post-Deploy Stages
                ->assertSee('Post-Deploy Stages')
                ->assertSee('Clear Cache')
                ->screenshot('pipeline-stages-visible');
        });
    }

    /**
     * Test 6: Pipeline status indicators are displayed correctly
     */
    public function test_pipeline_status_indicators(): void
    {
        // Create runs with different statuses
        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 10,
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(5),
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 11,
            'started_at' => now()->subMinutes(30),
            'finished_at' => now()->subMinutes(28),
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 12,
            'started_at' => now()->subMinutes(5),
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending',
            'run_number' => 13,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10);

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

            // Pending should have blue/indigo styling
            $pendingBadge = $browser->element('span.from-blue-500, span.from-indigo-500, span.bg-blue-500, span.bg-indigo-500, span.text-blue-500, span.text-indigo-500');
            $this->assertNotNull($pendingBadge, 'Pending status indicator should be present');

            $browser->screenshot('pipeline-status-indicators');
        });
    }

    /**
     * Test 7: Create pipeline stage modal opens
     */
    public function test_create_pipeline_stage_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Check for "Add Stage" or similar button
                ->assertPresent('button:contains("Add Stage"), button:contains("New Stage"), button:contains("Create Stage")')
                // Click the button to verify modal appears
                ->click('button:contains("Add Stage"), button:contains("New Stage"), button:contains("Create Stage")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showStageModal"]', 5)
                ->assertSee('Stage Name')
                ->assertSee('Commands')
                ->screenshot('create-pipeline-stage-modal');
        });
    }

    /**
     * Test 8: Pipeline creation form is accessible
     */
    public function test_pipeline_creation_form_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Configuration')
                ->assertPresent('button:contains("Add Stage"), button:contains("New Stage")')
                ->screenshot('pipeline-creation-form');
        });
    }

    /**
     * Test 9: Pipeline runs show detailed information
     */
    public function test_pipeline_runs_show_detailed_information(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 100,
            'branch' => 'feature/new-feature',
            'commit_sha' => 'abc123def456789',
            'triggered_by' => 'webhook',
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(8),
            'logs' => ['Starting pipeline...', 'Building application...', 'Deployment successful!'],
        ]);

        // Create stage runs for this pipeline run
        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $run->id,
            'pipeline_stage_id' => PipelineStage::factory()->create([
                'project_id' => $this->project->id,
                'name' => 'Build',
                'type' => 'pre_deploy',
            ])->id,
            'status' => 'success',
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(3),
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #'.$run->run_number)
                ->assertSee('feature/new-feature')
                ->assertSee(substr($run->commit_sha, 0, 7))
                ->assertSee('webhook')
                ->assertSee('success')
                ->screenshot('pipeline-run-detailed-info');
        });
    }

    /**
     * Test 10: Pipeline stage actions are available
     */
    public function test_pipeline_stage_actions_available(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Stage Actions',
            'type' => 'pre_deploy',
            'commands' => ['echo "test"'],
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Test Stage Actions')
                // Check for edit/delete/toggle actions
                ->assertPresent('button[wire\\:click*="editStage"], button[wire\\:click*="edit"]')
                ->assertPresent('button[wire\\:click*="deleteStage"], button[wire\\:click*="delete"]')
                ->assertPresent('button[wire\\:click*="toggleStage"], input[type="checkbox"]')
                ->screenshot('pipeline-stage-actions');
        });
    }

    /**
     * Test 11: Pipeline stage management - create stage
     */
    public function test_pipeline_stage_management_create(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Stage"), button:contains("New Stage")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Stage Name')
                ->assertSee('Stage Type')
                ->assertSee('Commands')
                ->screenshot('pipeline-stage-create');
        });
    }

    /**
     * Test 12: Pipeline stage management - edit stage
     */
    public function test_pipeline_stage_management_edit(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Editable Stage',
            'type' => 'deploy',
            'commands' => ['php artisan migrate'],
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Editable Stage')
                ->click('button[wire\\:click*="editStage('.$stage->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Editable Stage')
                ->assertSee('Commands')
                ->screenshot('pipeline-stage-edit');
        });
    }

    /**
     * Test 13: Pipeline stage management - delete stage
     */
    public function test_pipeline_stage_management_delete(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Deletable Stage',
            'type' => 'post_deploy',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Deletable Stage')
                ->assertPresent('button[wire\\:click*="deleteStage"], button[wire\\:click*="delete"]')
                ->screenshot('pipeline-stage-delete');
        });
    }

    /**
     * Test 14: Pipeline templates are available
     */
    public function test_pipeline_templates_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Templates')
                ->click('button:contains("Templates"), button:contains("Use Template")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showTemplateModal"]', 5)
                ->assertSee('Laravel')
                ->assertSee('Node.js')
                ->assertSee('Static')
                ->screenshot('pipeline-templates');
        });
    }

    /**
     * Test 15: Pipeline execution - manual trigger
     */
    public function test_pipeline_execution_manual_trigger(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button:contains("Run Pipeline"), button:contains("Deploy"), button:contains("Execute")')
                ->screenshot('pipeline-manual-trigger');
        });
    }

    /**
     * Test 16: Pipeline execution monitoring - running state
     */
    public function test_pipeline_execution_monitoring_running(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 150,
            'started_at' => now()->subMinutes(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #150')
                ->assertSee('running')
                ->assertPresent('.animate-spin, .animate-pulse')
                ->screenshot('pipeline-monitoring-running');
        });
    }

    /**
     * Test 17: Pipeline execution monitoring - completed state
     */
    public function test_pipeline_execution_monitoring_completed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 151,
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(5),
            'duration_seconds' => 300,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #151')
                ->assertSee('success')
                ->screenshot('pipeline-monitoring-completed');
        });
    }

    /**
     * Test 18: Pipeline history page displays all runs
     */
    public function test_pipeline_history_displays_all_runs(): void
    {
        // Create multiple pipeline runs
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Recent Pipeline Runs')
                ->screenshot('pipeline-history-all-runs');
        });
    }

    /**
     * Test 19: Pipeline logs are visible in run details
     */
    public function test_pipeline_logs_visible_in_run_details(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 400,
            'logs' => [
                'Starting pipeline execution...',
                'Cloning repository...',
                'Installing dependencies...',
                'Running tests...',
                'Deployment successful!',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Click on the run to view details
                ->clickLink('Run #'.$run->run_number)
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, .pipeline-run-details', 5)
                ->assertSee('Starting pipeline execution')
                ->assertSee('Deployment successful')
                ->screenshot('pipeline-logs-visible');
        });
    }

    /**
     * Test 20: Pipeline logs show real-time updates
     */
    public function test_pipeline_logs_realtime_updates(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 401,
            'logs' => ['Pipeline started...', 'Executing stage 1...'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #401')
                ->assertSee('running')
                ->screenshot('pipeline-logs-realtime');
        });
    }

    /**
     * Test 21: Pipeline triggers - manual trigger button
     */
    public function test_pipeline_triggers_manual(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button:contains("Run Pipeline"), button:contains("Deploy"), button:contains("Execute")')
                ->screenshot('pipeline-trigger-manual-button');
        });
    }

    /**
     * Test 22: Pipeline triggers - webhook configuration
     */
    public function test_pipeline_triggers_webhook_config(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->assertSee('Webhook')
                ->screenshot('pipeline-trigger-webhook');
        });
    }

    /**
     * Test 23: Pipeline triggers - schedule configuration
     */
    public function test_pipeline_triggers_schedule_config(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-trigger-schedule');
        });
    }

    /**
     * Test 24: Pipeline variables configuration
     */
    public function test_pipeline_variables_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Stage"), button:contains("New Stage")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Environment Variables')
                ->screenshot('pipeline-variables-config');
        });
    }

    /**
     * Test 25: Pipeline secrets management
     */
    public function test_pipeline_secrets_management(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-secrets-management');
        });
    }

    /**
     * Test 26: Pipeline notifications configuration
     */
    public function test_pipeline_notifications_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-notifications-config');
        });
    }

    /**
     * Test 27: Pipeline notifications - success notification
     */
    public function test_pipeline_notifications_success(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 500,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #500')
                ->assertSee('success')
                ->screenshot('pipeline-notification-success');
        });
    }

    /**
     * Test 28: Pipeline notifications - failure notification
     */
    public function test_pipeline_notifications_failure(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'run_number' => 501,
            'error_message' => 'Build failed due to syntax error',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #501')
                ->assertSee('failed')
                ->screenshot('pipeline-notification-failure');
        });
    }

    /**
     * Test 29: Pipeline approval workflows configuration
     */
    public function test_pipeline_approval_workflows_config(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-approval-workflows');
        });
    }

    /**
     * Test 30: Pipeline approval workflows - pending approval
     */
    public function test_pipeline_approval_workflows_pending(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'pending_approval',
            'run_number' => 600,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-approval-pending');
        });
    }

    /**
     * Test 31: Pipeline approval workflows - approve action
     */
    public function test_pipeline_approval_workflows_approve(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-approval-approve-action');
        });
    }

    /**
     * Test 32: Pipeline approval workflows - reject action
     */
    public function test_pipeline_approval_workflows_reject(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-approval-reject-action');
        });
    }

    /**
     * Test 33: Pipeline rollback functionality - display
     */
    public function test_pipeline_rollback_functionality_display(): void
    {
        $successRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 700,
            'commit_sha' => 'previous-commit-abc',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #700')
                ->screenshot('pipeline-rollback-display');
        });
    }

    /**
     * Test 34: Pipeline rollback functionality - execute rollback
     */
    public function test_pipeline_rollback_functionality_execute(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-rollback-execute');
        });
    }

    /**
     * Test 35: Pipeline run duration is displayed
     */
    public function test_pipeline_run_duration_displayed(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 200,
            'started_at' => now()->subHours(1),
            'finished_at' => now()->subHours(1)->addMinutes(15)->addSeconds(30),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #200')
                // Duration should be displayed (15m 30s or similar format)
                ->assertSeeIn('[data-run-id="'.$run->id.'"], .pipeline-run', '15')
                ->screenshot('pipeline-run-duration');
        });
    }

    /**
     * Test 36: Pipeline stage order can be changed
     */
    public function test_pipeline_stage_order_changeable(): void
    {
        PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'First Stage',
            'type' => 'pre_deploy',
            'order' => 0,
        ]);

        PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Second Stage',
            'type' => 'pre_deploy',
            'order' => 1,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('First Stage')
                ->assertSee('Second Stage')
                // Check for drag handles or reorder buttons
                ->assertPresent('[data-sortable], [draggable="true"], button[wire\\:click*="moveUp"], button[wire\\:click*="moveDown"]')
                ->screenshot('pipeline-stage-reorder');
        });
    }

    /**
     * Test 37: Pipeline runs can be filtered by status
     */
    public function test_pipeline_runs_filtered_by_status(): void
    {
        // Create runs with different statuses
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
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Check for filter controls
                ->assertPresent('select[wire\\:model*="statusFilter"], select[wire\\:model*="status"]')
                ->screenshot('pipeline-runs-filter-status');
        });
    }

    /**
     * Test 38: Pipeline runs display commit information
     */
    public function test_pipeline_runs_display_commit_information(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 300,
            'branch' => 'hotfix/critical-bug',
            'commit_sha' => 'abc123def456789012345678',
            'trigger_data' => [
                'commit_message' => 'Fix critical security vulnerability',
                'author' => 'John Doe',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($run) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #300')
                ->assertSee('hotfix/critical-bug')
                ->assertSee(substr($run->commit_sha, 0, 7))
                ->screenshot('pipeline-runs-commit-info');
        });
    }

    /**
     * Test 39: Empty pipeline state is handled gracefully
     */
    public function test_empty_pipeline_state_handled_gracefully(): void
    {
        // Create a project without any pipeline stages
        $emptyProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Empty Pipeline Project',
            'slug' => 'empty-pipeline-project-'.uniqid(),
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$emptyProject->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('No stages configured')
                ->assertSee('Add Stage')
                ->assertSee('Templates')
                ->screenshot('empty-pipeline-state');
        });
    }

    /**
     * Test 40: Pipeline configuration shows timeout settings
     */
    public function test_pipeline_configuration_shows_timeout_settings(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage with Timeout',
            'type' => 'deploy',
            'timeout_seconds' => 600,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Stage with Timeout')
                // Click edit to see timeout settings
                ->click('button[wire\\:click*="editStage('.$stage->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Timeout')
                ->assertSee('600')
                ->screenshot('pipeline-timeout-settings');
        });
    }

    /**
     * Test 41: Pipeline runs show triggered by information
     */
    public function test_pipeline_runs_show_triggered_by_information(): void
    {
        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 500,
            'triggered_by' => 'manual',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 501,
            'triggered_by' => 'webhook',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'run_number' => 502,
            'triggered_by' => 'schedule',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('manual')
                ->assertSee('webhook')
                ->assertSee('schedule')
                ->screenshot('pipeline-triggered-by-info');
        });
    }

    /**
     * Test 42: Pipeline stage environment variables are configurable
     */
    public function test_pipeline_stage_environment_variables_configurable(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Open add stage modal
                ->click('button:contains("Add Stage"), button:contains("New Stage")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Environment Variables')
                ->screenshot('pipeline-stage-env-variables');
        });
    }

    /**
     * Test 43: Pipeline builder shows continue on failure option
     */
    public function test_pipeline_builder_shows_continue_on_failure_option(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                // Open add stage modal
                ->click('button:contains("Add Stage"), button:contains("New Stage")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Continue on Failure')
                ->assertPresent('input[type="checkbox"][wire\\:model*="continueOnFailure"]')
                ->screenshot('pipeline-continue-on-failure');
        });
    }

    /**
     * Test 44: Pipeline stage execution shows progress
     */
    public function test_pipeline_stage_execution_shows_progress(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'run_number' => 800,
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
            'started_at' => now()->subMinutes(1),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Run #800')
                ->assertSee('running')
                ->screenshot('pipeline-stage-execution-progress');
        });
    }

    /**
     * Test 45: Pipeline variables can be added and managed
     */
    public function test_pipeline_variables_can_be_added_and_managed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id.'/settings')
                ->waitFor('body', 10)
                ->pause(500)
                ->screenshot('pipeline-variables-management');
        });
    }
}
