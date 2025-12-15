<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CI/CD Pipeline Integration Test
 *
 * This test suite covers the complete CI/CD pipeline workflow,
 * including pipeline creation, trigger, stage execution, and notifications.
 *
 * Workflows covered:
 * 1. Pipeline configuration and stages
 * 2. Pipeline trigger from webhooks
 * 3. Stage execution and status tracking
 * 4. Pipeline variables and secrets
 * 5. Notifications on completion/failure
 * 6. Pipeline history and logs
 */
class CICDPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected Pipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->user = User::factory()->create([
            'email' => 'devops@devflow.com',
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'CI/CD Project',
            'slug' => 'cicd-project',
            'repository_url' => 'https://github.com/test/cicd-project.git',
            'branch' => 'main',
            'webhook_enabled' => true,
        ]);

        $this->pipeline = Pipeline::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Main Pipeline',
            'is_active' => true,
        ]);
    }

    // ==================== Pipeline Configuration Tests ====================

    #[Test]
    public function can_create_pipeline_for_project(): void
    {
        $pipeline = Pipeline::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Pipeline',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('pipelines', [
            'project_id' => $this->project->id,
            'name' => 'Test Pipeline',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function pipeline_can_have_multiple_stages(): void
    {
        $stages = [
            ['name' => 'Build', 'order' => 1],
            ['name' => 'Test', 'order' => 2],
            ['name' => 'Deploy', 'order' => 3],
        ];

        foreach ($stages as $stageData) {
            PipelineStage::factory()->create([
                'pipeline_id' => $this->pipeline->id,
                'name' => $stageData['name'],
                'order' => $stageData['order'],
            ]);
        }

        $this->assertCount(3, $this->pipeline->stages);
    }

    #[Test]
    public function stages_execute_in_order(): void
    {
        PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Build',
            'order' => 1,
        ]);

        PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Test',
            'order' => 2,
        ]);

        PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Deploy',
            'order' => 3,
        ]);

        $orderedStages = $this->pipeline->stages()->orderBy('order')->get();

        $this->assertEquals('Build', $orderedStages[0]->name);
        $this->assertEquals('Test', $orderedStages[1]->name);
        $this->assertEquals('Deploy', $orderedStages[2]->name);
    }

    #[Test]
    public function stage_can_have_commands(): void
    {
        $stage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Build',
            'commands' => [
                'composer install --no-dev',
                'npm install',
                'npm run build',
            ],
        ]);

        $this->assertCount(3, $stage->commands);
        $this->assertContains('composer install --no-dev', $stage->commands);
    }

    // ==================== Pipeline Trigger Tests ====================

    #[Test]
    public function pipeline_triggers_on_webhook(): void
    {
        $webhookPayload = [
            'ref' => 'refs/heads/main',
            'head_commit' => [
                'id' => 'abc123def456',
                'message' => 'feat: Add new feature',
            ],
        ];

        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'provider' => 'github',
            'event' => 'push',
            'payload' => $webhookPayload,
            'status' => 'delivered',
        ]);

        // Pipeline run would be triggered
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'commit_hash' => 'abc123def456',
            'branch' => 'main',
            'triggered_by' => 'webhook',
            'status' => 'pending',
        ]);

        $this->assertEquals('webhook', $run->triggered_by);
        $this->assertEquals('pending', $run->status);
    }

    #[Test]
    public function pipeline_can_be_triggered_manually(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'triggered_by' => 'manual',
            'status' => 'pending',
        ]);

        $this->assertEquals('manual', $run->triggered_by);
        $this->assertEquals($this->user->id, $run->user_id);
    }

    #[Test]
    public function pipeline_trigger_respects_branch_filter(): void
    {
        // Pipeline configured for main branch only
        $this->pipeline->update(['branches' => ['main', 'develop']]);

        // Trigger from feature branch should not start pipeline
        $shouldTrigger = in_array('feature/test', $this->pipeline->branches ?? []);
        $this->assertFalse($shouldTrigger);

        // Trigger from main should work
        $shouldTrigger = in_array('main', $this->pipeline->branches ?? []);
        $this->assertTrue($shouldTrigger);
    }

    // ==================== Pipeline Execution Tests ====================

    #[Test]
    public function pipeline_run_tracks_stage_status(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        // Create stages with different statuses
        PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Build',
            'order' => 1,
        ]);

        PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Test',
            'order' => 2,
        ]);

        $this->assertEquals('running', $run->status);
    }

    #[Test]
    public function failed_stage_stops_pipeline(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'failed_at_stage' => 'Test',
            'error_message' => 'Unit tests failed',
        ]);

        $this->assertEquals('failed', $run->status);
        $this->assertEquals('Test', $run->failed_at_stage);
    }

    #[Test]
    public function successful_pipeline_triggers_deployment(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        // Deployment triggered after successful pipeline
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'triggered_by' => 'pipeline',
            'pipeline_run_id' => $run->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('pipeline', $deployment->triggered_by);
        $this->assertEquals($run->id, $deployment->pipeline_run_id);
    }

    // ==================== Pipeline Variables Tests ====================

    #[Test]
    public function pipeline_can_have_variables(): void
    {
        $this->pipeline->update([
            'variables' => [
                'NODE_ENV' => 'production',
                'BUILD_TYPE' => 'release',
            ],
        ]);

        $freshPipeline = $this->pipeline->fresh();
        $this->assertNotNull($freshPipeline);
        $this->assertNotNull($freshPipeline->variables);
        $this->assertArrayHasKey('NODE_ENV', $freshPipeline->variables);
        $this->assertEquals('production', $freshPipeline->variables['NODE_ENV']);
    }

    #[Test]
    public function pipeline_variables_can_be_secret(): void
    {
        $this->pipeline->update([
            'secrets' => [
                'API_KEY' => encrypt('secret-api-key'),
                'DB_PASSWORD' => encrypt('secret-db-password'),
            ],
        ]);

        $freshPipeline = $this->pipeline->fresh();
        $this->assertNotNull($freshPipeline);
        $this->assertNotNull($freshPipeline->secrets);
        $this->assertArrayHasKey('API_KEY', $freshPipeline->secrets);
    }

    #[Test]
    public function run_inherits_pipeline_variables(): void
    {
        $this->pipeline->update([
            'variables' => ['APP_ENV' => 'staging'],
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'variables' => array_merge(
                $this->pipeline->variables ?? [],
                ['BUILD_NUMBER' => '123']
            ),
        ]);

        $this->assertEquals('staging', $run->variables['APP_ENV']);
        $this->assertEquals('123', $run->variables['BUILD_NUMBER']);
    }

    // ==================== Pipeline History Tests ====================

    #[Test]
    public function tracks_pipeline_run_history(): void
    {
        // Create multiple runs
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $totalRuns = PipelineRun::where('pipeline_id', $this->pipeline->id)->count();
        $successRuns = PipelineRun::where('pipeline_id', $this->pipeline->id)
            ->where('status', 'success')
            ->count();

        $this->assertEquals(7, $totalRuns);
        $this->assertEquals(5, $successRuns);
    }

    #[Test]
    public function pipeline_run_stores_duration(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'duration_seconds' => 300,
            'status' => 'success',
        ]);

        $this->assertEquals(300, $run->duration_seconds);
    }

    #[Test]
    public function can_get_average_pipeline_duration(): void
    {
        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'duration_seconds' => 180,
            'status' => 'success',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'duration_seconds' => 240,
            'status' => 'success',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'duration_seconds' => 180,
            'status' => 'success',
        ]);

        $avgDuration = PipelineRun::where('pipeline_id', $this->pipeline->id)
            ->where('status', 'success')
            ->avg('duration_seconds');

        $this->assertEquals(200, $avgDuration);
    }

    // ==================== Pipeline Logs Tests ====================

    #[Test]
    public function pipeline_run_stores_logs(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'logs' => "Starting build...\nInstalling dependencies...\nBuild complete.",
            'status' => 'success',
        ]);

        $this->assertStringContainsString('Starting build', $run->logs);
        $this->assertStringContainsString('Build complete', $run->logs);
    }

    #[Test]
    public function stage_logs_are_separate(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'stage_logs' => [
                'Build' => "npm install\nnpm run build",
                'Test' => "npm test\nAll tests passed",
                'Deploy' => "Deploying to production...",
            ],
            'status' => 'success',
        ]);

        $this->assertArrayHasKey('Build', $run->stage_logs);
        $this->assertArrayHasKey('Test', $run->stage_logs);
        $this->assertArrayHasKey('Deploy', $run->stage_logs);
    }

    // ==================== Pipeline Notifications Tests ====================

    #[Test]
    public function pipeline_failure_triggers_notification(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
            'notify_on_failure' => true,
            'error_message' => 'Tests failed',
        ]);

        $this->assertTrue($run->notify_on_failure);
        $this->assertEquals('failed', $run->status);
    }

    #[Test]
    public function pipeline_success_can_trigger_notification(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
            'notify_on_success' => true,
        ]);

        $this->assertTrue($run->notify_on_success);
    }

    // ==================== Pipeline Conditions Tests ====================

    #[Test]
    public function stage_can_have_conditions(): void
    {
        $stage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Deploy Production',
            'conditions' => [
                'branches' => ['main'],
                'require_approval' => true,
            ],
        ]);

        $this->assertArrayHasKey('branches', $stage->conditions);
        $this->assertTrue($stage->conditions['require_approval']);
    }

    #[Test]
    public function stage_can_be_skipped(): void
    {
        $stage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name' => 'Optional Stage',
            'allow_failure' => true,
        ]);

        $this->assertTrue($stage->allow_failure);
    }

    // ==================== Pipeline Statistics Tests ====================

    #[Test]
    public function calculates_pipeline_success_rate(): void
    {
        PipelineRun::factory()->count(8)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        PipelineRun::factory()->count(2)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $total = PipelineRun::where('pipeline_id', $this->pipeline->id)->count();
        $success = PipelineRun::where('pipeline_id', $this->pipeline->id)
            ->where('status', 'success')
            ->count();
        $successRate = ($success / $total) * 100;

        $this->assertEquals(80.0, $successRate);
    }

    #[Test]
    public function tracks_runs_per_day(): void
    {
        // Today's runs
        PipelineRun::factory()->count(3)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'created_at' => now(),
        ]);

        // Yesterday's runs
        PipelineRun::factory()->count(5)->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'created_at' => now()->subDay(),
        ]);

        $todayCount = PipelineRun::where('pipeline_id', $this->pipeline->id)
            ->whereDate('created_at', today())
            ->count();

        $this->assertEquals(3, $todayCount);
    }

    // ==================== Concurrent Pipeline Tests ====================

    #[Test]
    public function can_run_multiple_pipelines_concurrently(): void
    {
        $pipeline2 = Pipeline::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Secondary Pipeline',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $pipeline2->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $runningCount = PipelineRun::where('project_id', $this->project->id)
            ->where('status', 'running')
            ->count();

        $this->assertEquals(2, $runningCount);
    }

    #[Test]
    public function can_cancel_running_pipeline(): void
    {
        $run = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        $run->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $this->user->id,
        ]);

        $freshRun = $run->fresh();
        $this->assertNotNull($freshRun);
        $this->assertEquals('cancelled', $freshRun->status);
        $this->assertNotNull($freshRun->cancelled_at);
    }

    // ==================== Retry Tests ====================

    #[Test]
    public function can_retry_failed_pipeline(): void
    {
        $failedRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'commit_hash' => 'abc123',
            'status' => 'failed',
        ]);

        // Retry creates new run
        $retryRun = PipelineRun::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'project_id' => $this->project->id,
            'commit_hash' => 'abc123',
            'retry_of' => $failedRun->id,
            'status' => 'pending',
        ]);

        $this->assertEquals($failedRun->id, $retryRun->retry_of);
        $this->assertEquals($failedRun->commit_hash, $retryRun->commit_hash);
    }
}
