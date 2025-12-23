<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\DeploymentComment;
use App\Models\DeploymentScript;
use App\Models\DeploymentScriptRun;
use App\Models\KubernetesCluster;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Models\Project;
use App\Models\ProjectAnalytic;
use App\Models\ProjectSetupTask;
use App\Models\ResourceAlert;
use App\Models\SecurityEvent;
use App\Models\SecurityScan;
use App\Models\Server;
use App\Models\Tenant;
use App\Models\TenantDeployment;
use App\Models\User;

use Tests\TestCase;

class AdditionalModelsTest extends TestCase
{
    

    // ========================================
    // KubernetesCluster Model Tests
    // ========================================

    public function test_kubernetes_cluster_has_fillable_attributes(): void
    {
        $cluster = new KubernetesCluster();
        $fillable = $cluster->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('api_server_url', $fillable);
        $this->assertContains('kubeconfig', $fillable);
        $this->assertContains('namespace', $fillable);
        $this->assertContains('context', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('metadata', $fillable);
    }

    public function test_kubernetes_cluster_has_correct_casts(): void
    {
        $cluster = new KubernetesCluster();
        $casts = $cluster->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertArrayHasKey('metadata', $casts);
        $this->assertEquals('array', $casts['metadata']);
    }

    public function test_kubernetes_cluster_hides_kubeconfig(): void
    {
        $cluster = new KubernetesCluster();
        $hidden = $cluster->getHidden();

        $this->assertContains('kubeconfig', $hidden);
    }

    public function test_kubernetes_cluster_encrypts_kubeconfig_on_set(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'kubeconfig' => 'test-config-data',
        ]);

        $this->assertNotEquals('test-config-data', $cluster->getRawOriginal('kubeconfig'));
    }

    public function test_kubernetes_cluster_decrypts_kubeconfig_on_get(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'kubeconfig' => 'test-config-data',
        ]);

        $this->assertEquals('test-config-data', $cluster->kubeconfig);
    }

    public function test_kubernetes_cluster_handles_null_kubeconfig(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'kubeconfig' => null,
        ]);

        $this->assertNull($cluster->kubeconfig);
    }

    public function test_kubernetes_cluster_factory_creates_valid_instance(): void
    {
        $cluster = KubernetesCluster::factory()->create();

        $this->assertInstanceOf(KubernetesCluster::class, $cluster);
        $this->assertDatabaseHas('kubernetes_clusters', [
            'id' => $cluster->id,
        ]);
    }

    // ========================================
    // Pipeline Model Tests
    // ========================================

    public function test_pipeline_has_fillable_attributes(): void
    {
        $pipeline = new Pipeline();
        $fillable = $pipeline->getFillable();

        $this->assertContains('project_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('provider', $fillable);
        $this->assertContains('configuration', $fillable);
        $this->assertContains('triggers', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    public function test_pipeline_has_correct_casts(): void
    {
        $pipeline = new Pipeline();
        $casts = $pipeline->getCasts();

        $this->assertArrayHasKey('configuration', $casts);
        $this->assertEquals('array', $casts['configuration']);
        $this->assertArrayHasKey('triggers', $casts);
        $this->assertEquals('array', $casts['triggers']);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_pipeline_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $pipeline = Pipeline::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $pipeline->project);
        $this->assertEquals($project->id, $pipeline->project->id);
    }

    public function test_pipeline_has_many_runs(): void
    {
        $pipeline = Pipeline::factory()->create();
        PipelineRun::factory()->count(3)->create(['pipeline_id' => $pipeline->id]);

        $this->assertCount(3, $pipeline->runs);
    }

    public function test_pipeline_has_latest_run(): void
    {
        $pipeline = Pipeline::factory()->create();
        PipelineRun::factory()->create(['pipeline_id' => $pipeline->id]);
        $latestRun = PipelineRun::factory()->create(['pipeline_id' => $pipeline->id]);

        $this->assertInstanceOf(PipelineRun::class, $pipeline->latestRun);
        $this->assertEquals($latestRun->id, $pipeline->latestRun->id);
    }

    // ========================================
    // PipelineRun Model Tests
    // ========================================

    public function test_pipeline_run_has_fillable_attributes(): void
    {
        $pipelineRun = new PipelineRun();
        $fillable = $pipelineRun->getFillable();

        $this->assertContains('pipeline_id', $fillable);
        $this->assertContains('project_id', $fillable);
        $this->assertContains('deployment_id', $fillable);
        $this->assertContains('run_number', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('triggered_by', $fillable);
        $this->assertContains('branch', $fillable);
        $this->assertContains('commit_sha', $fillable);
    }

    public function test_pipeline_run_has_correct_casts(): void
    {
        $pipelineRun = new PipelineRun();
        $casts = $pipelineRun->getCasts();

        $this->assertArrayHasKey('logs', $casts);
        $this->assertEquals('array', $casts['logs']);
        $this->assertArrayHasKey('artifacts', $casts);
        $this->assertEquals('array', $casts['artifacts']);
        $this->assertArrayHasKey('trigger_data', $casts);
        $this->assertEquals('array', $casts['trigger_data']);
        $this->assertEquals('datetime', $casts['started_at']);
        $this->assertEquals('datetime', $casts['finished_at']);
    }

    public function test_pipeline_run_belongs_to_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create();
        $pipelineRun = PipelineRun::factory()->create(['pipeline_id' => $pipeline->id]);

        $this->assertInstanceOf(Pipeline::class, $pipelineRun->pipeline);
        $this->assertEquals($pipeline->id, $pipelineRun->pipeline->id);
    }

    public function test_pipeline_run_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $pipelineRun = PipelineRun::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $pipelineRun->project);
        $this->assertEquals($project->id, $pipelineRun->project->id);
    }

    public function test_pipeline_run_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $pipelineRun = PipelineRun::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $pipelineRun->deployment);
        $this->assertEquals($deployment->id, $pipelineRun->deployment->id);
    }

    public function test_pipeline_run_has_many_stage_runs(): void
    {
        $pipelineRun = PipelineRun::factory()->create();
        PipelineStageRun::factory()->count(3)->create(['pipeline_run_id' => $pipelineRun->id]);

        $this->assertCount(3, $pipelineRun->stageRuns);
    }

    public function test_pipeline_run_mark_running(): void
    {
        $pipelineRun = PipelineRun::factory()->create(['status' => 'pending']);

        $pipelineRun->markRunning();

        $this->assertEquals('running', $pipelineRun->status);
        $this->assertNotNull($pipelineRun->started_at);
    }

    public function test_pipeline_run_mark_success(): void
    {
        $pipelineRun = PipelineRun::factory()->create(['status' => 'running']);

        $pipelineRun->markSuccess();

        $this->assertEquals('success', $pipelineRun->status);
        $this->assertNotNull($pipelineRun->finished_at);
    }

    public function test_pipeline_run_mark_failed(): void
    {
        $pipelineRun = PipelineRun::factory()->create(['status' => 'running']);

        $pipelineRun->markFailed();

        $this->assertEquals('failed', $pipelineRun->status);
        $this->assertNotNull($pipelineRun->finished_at);
    }

    public function test_pipeline_run_mark_cancelled(): void
    {
        $pipelineRun = PipelineRun::factory()->create(['status' => 'running']);

        $pipelineRun->markCancelled();

        $this->assertEquals('cancelled', $pipelineRun->status);
        $this->assertNotNull($pipelineRun->finished_at);
    }

    public function test_pipeline_run_duration(): void
    {
        $pipelineRun = PipelineRun::factory()->create([
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);

        $duration = $pipelineRun->duration();

        $this->assertIsInt($duration);
        $this->assertEquals(300, $duration);
    }

    public function test_pipeline_run_duration_returns_null_when_not_finished(): void
    {
        $pipelineRun = PipelineRun::factory()->create([
            'started_at' => now(),
            'finished_at' => null,
        ]);

        $this->assertNull($pipelineRun->duration());
    }

    public function test_pipeline_run_formatted_duration(): void
    {
        $pipelineRun = PipelineRun::factory()->create([
            'started_at' => now()->subMinutes(2)->subSeconds(30),
            'finished_at' => now(),
        ]);

        $formatted = $pipelineRun->formatted_duration;

        $this->assertStringContainsString('2m', $formatted);
        $this->assertStringContainsString('30s', $formatted);
    }

    public function test_pipeline_run_is_complete(): void
    {
        $successRun = PipelineRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineRun::factory()->create(['status' => 'failed']);
        $cancelledRun = PipelineRun::factory()->create(['status' => 'cancelled']);
        $runningRun = PipelineRun::factory()->create(['status' => 'running']);

        $this->assertTrue($successRun->isComplete());
        $this->assertTrue($failedRun->isComplete());
        $this->assertTrue($cancelledRun->isComplete());
        $this->assertFalse($runningRun->isComplete());
    }

    public function test_pipeline_run_is_running(): void
    {
        $runningRun = PipelineRun::factory()->create(['status' => 'running']);
        $successRun = PipelineRun::factory()->create(['status' => 'success']);

        $this->assertTrue($runningRun->isRunning());
        $this->assertFalse($successRun->isRunning());
    }

    public function test_pipeline_run_is_pending(): void
    {
        $pendingRun = PipelineRun::factory()->create(['status' => 'pending']);
        $runningRun = PipelineRun::factory()->create(['status' => 'running']);

        $this->assertTrue($pendingRun->isPending());
        $this->assertFalse($runningRun->isPending());
    }

    public function test_pipeline_run_status_color(): void
    {
        $successRun = PipelineRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineRun::factory()->create(['status' => 'failed']);
        $runningRun = PipelineRun::factory()->create(['status' => 'running']);

        $this->assertEquals('green', $successRun->status_color);
        $this->assertEquals('red', $failedRun->status_color);
        $this->assertEquals('yellow', $runningRun->status_color);
    }

    public function test_pipeline_run_status_icon(): void
    {
        $successRun = PipelineRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineRun::factory()->create(['status' => 'failed']);
        $runningRun = PipelineRun::factory()->create(['status' => 'running']);

        $this->assertEquals('check-circle', $successRun->status_icon);
        $this->assertEquals('x-circle', $failedRun->status_icon);
        $this->assertEquals('arrow-path', $runningRun->status_icon);
    }

    // ========================================
    // PipelineStage Model Tests
    // ========================================

    public function test_pipeline_stage_has_fillable_attributes(): void
    {
        $stage = new PipelineStage();
        $fillable = $stage->getFillable();

        $this->assertContains('project_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('order', $fillable);
        $this->assertContains('commands', $fillable);
        $this->assertContains('enabled', $fillable);
        $this->assertContains('continue_on_failure', $fillable);
        $this->assertContains('timeout_seconds', $fillable);
        $this->assertContains('environment_variables', $fillable);
    }

    public function test_pipeline_stage_has_correct_casts(): void
    {
        $stage = new PipelineStage();
        $casts = $stage->getCasts();

        $this->assertEquals('array', $casts['commands']);
        $this->assertEquals('array', $casts['environment_variables']);
        $this->assertEquals('boolean', $casts['enabled']);
        $this->assertEquals('boolean', $casts['continue_on_failure']);
        $this->assertEquals('integer', $casts['timeout_seconds']);
        $this->assertEquals('integer', $casts['order']);
    }

    public function test_pipeline_stage_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $stage = PipelineStage::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $stage->project);
        $this->assertEquals($project->id, $stage->project->id);
    }

    public function test_pipeline_stage_has_many_stage_runs(): void
    {
        $stage = PipelineStage::factory()->create();
        PipelineStageRun::factory()->count(3)->create(['pipeline_stage_id' => $stage->id]);

        $this->assertCount(3, $stage->stageRuns);
    }

    public function test_pipeline_stage_has_latest_run(): void
    {
        $stage = PipelineStage::factory()->create();
        PipelineStageRun::factory()->create(['pipeline_stage_id' => $stage->id]);
        $latestRun = PipelineStageRun::factory()->create(['pipeline_stage_id' => $stage->id]);

        $this->assertInstanceOf(PipelineStageRun::class, $stage->latestRun);
        $this->assertEquals($latestRun->id, $stage->latestRun->id);
    }

    public function test_pipeline_stage_enabled_scope(): void
    {
        PipelineStage::factory()->create(['enabled' => true]);
        PipelineStage::factory()->create(['enabled' => false]);

        $enabledStages = PipelineStage::enabled()->get();

        $this->assertCount(1, $enabledStages);
        $this->assertTrue($enabledStages->first()->enabled);
    }

    public function test_pipeline_stage_ordered_scope(): void
    {
        $stage1 = PipelineStage::factory()->create(['order' => 2]);
        $stage2 = PipelineStage::factory()->create(['order' => 1]);
        $stage3 = PipelineStage::factory()->create(['order' => 3]);

        $orderedStages = PipelineStage::ordered()->get();

        $this->assertEquals($stage2->id, $orderedStages[0]->id);
        $this->assertEquals($stage1->id, $orderedStages[1]->id);
        $this->assertEquals($stage3->id, $orderedStages[2]->id);
    }

    public function test_pipeline_stage_by_type_scope(): void
    {
        PipelineStage::factory()->create(['type' => 'pre_deploy']);
        PipelineStage::factory()->create(['type' => 'deploy']);
        PipelineStage::factory()->create(['type' => 'post_deploy']);

        $deployStages = PipelineStage::byType('deploy')->get();

        $this->assertCount(1, $deployStages);
        $this->assertEquals('deploy', $deployStages->first()->type);
    }

    public function test_pipeline_stage_icon_accessor(): void
    {
        $testStage = PipelineStage::factory()->create(['name' => 'Run Tests']);
        $buildStage = PipelineStage::factory()->create(['name' => 'Build Application']);
        $deployStage = PipelineStage::factory()->create(['name' => 'Deploy to Production']);

        $this->assertEquals('flask', $testStage->icon);
        $this->assertEquals('gear', $buildStage->icon);
        $this->assertEquals('rocket', $deployStage->icon);
    }

    public function test_pipeline_stage_color_accessor(): void
    {
        $preDeployStage = PipelineStage::factory()->create(['type' => 'pre_deploy']);
        $deployStage = PipelineStage::factory()->create(['type' => 'deploy']);
        $postDeployStage = PipelineStage::factory()->create(['type' => 'post_deploy']);

        $this->assertEquals('blue', $preDeployStage->color);
        $this->assertEquals('green', $deployStage->color);
        $this->assertEquals('purple', $postDeployStage->color);
    }

    // ========================================
    // PipelineStageRun Model Tests
    // ========================================

    public function test_pipeline_stage_run_has_fillable_attributes(): void
    {
        $stageRun = new PipelineStageRun();
        $fillable = $stageRun->getFillable();

        $this->assertContains('pipeline_run_id', $fillable);
        $this->assertContains('pipeline_stage_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('output', $fillable);
        $this->assertContains('error_message', $fillable);
        $this->assertContains('started_at', $fillable);
        $this->assertContains('completed_at', $fillable);
        $this->assertContains('duration_seconds', $fillable);
    }

    public function test_pipeline_stage_run_has_correct_casts(): void
    {
        $stageRun = new PipelineStageRun();
        $casts = $stageRun->getCasts();

        $this->assertEquals('datetime', $casts['started_at']);
        $this->assertEquals('datetime', $casts['completed_at']);
        $this->assertEquals('integer', $casts['duration_seconds']);
    }

    public function test_pipeline_stage_run_belongs_to_pipeline_run(): void
    {
        $pipelineRun = PipelineRun::factory()->create();
        $stageRun = PipelineStageRun::factory()->create(['pipeline_run_id' => $pipelineRun->id]);

        $this->assertInstanceOf(PipelineRun::class, $stageRun->pipelineRun);
        $this->assertEquals($pipelineRun->id, $stageRun->pipelineRun->id);
    }

    public function test_pipeline_stage_run_belongs_to_pipeline_stage(): void
    {
        $pipelineStage = PipelineStage::factory()->create();
        $stageRun = PipelineStageRun::factory()->create(['pipeline_stage_id' => $pipelineStage->id]);

        $this->assertInstanceOf(PipelineStage::class, $stageRun->pipelineStage);
        $this->assertEquals($pipelineStage->id, $stageRun->pipelineStage->id);
    }

    public function test_pipeline_stage_run_mark_running(): void
    {
        $stageRun = PipelineStageRun::factory()->create(['status' => 'pending']);

        $stageRun->markRunning();

        $this->assertEquals('running', $stageRun->status);
        $this->assertNotNull($stageRun->started_at);
    }

    public function test_pipeline_stage_run_mark_success(): void
    {
        $stageRun = PipelineStageRun::factory()->create([
            'status' => 'running',
            'started_at' => now()->subSeconds(30),
        ]);

        $stageRun->markSuccess();

        $this->assertEquals('success', $stageRun->status);
        $this->assertNotNull($stageRun->completed_at);
        $this->assertNotNull($stageRun->duration_seconds);
    }

    public function test_pipeline_stage_run_mark_failed(): void
    {
        $stageRun = PipelineStageRun::factory()->create([
            'status' => 'running',
            'started_at' => now()->subSeconds(30),
        ]);

        $stageRun->markFailed('Test error message');

        $this->assertEquals('failed', $stageRun->status);
        $this->assertEquals('Test error message', $stageRun->error_message);
        $this->assertNotNull($stageRun->completed_at);
    }

    public function test_pipeline_stage_run_mark_skipped(): void
    {
        $stageRun = PipelineStageRun::factory()->create(['status' => 'pending']);

        $stageRun->markSkipped();

        $this->assertEquals('skipped', $stageRun->status);
        $this->assertNotNull($stageRun->completed_at);
    }

    public function test_pipeline_stage_run_append_output(): void
    {
        $stageRun = PipelineStageRun::factory()->create(['output' => 'Line 1']);

        $stageRun->appendOutput('Line 2');

        $this->assertStringContainsString('Line 1', $stageRun->output);
        $this->assertStringContainsString('Line 2', $stageRun->output);
    }

    public function test_pipeline_stage_run_status_checks(): void
    {
        $runningRun = PipelineStageRun::factory()->create(['status' => 'running']);
        $successRun = PipelineStageRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineStageRun::factory()->create(['status' => 'failed']);
        $skippedRun = PipelineStageRun::factory()->create(['status' => 'skipped']);

        $this->assertTrue($runningRun->isRunning());
        $this->assertTrue($successRun->isSuccess());
        $this->assertTrue($failedRun->isFailed());
        $this->assertTrue($skippedRun->isSkipped());
    }

    public function test_pipeline_stage_run_status_color(): void
    {
        $successRun = PipelineStageRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineStageRun::factory()->create(['status' => 'failed']);
        $runningRun = PipelineStageRun::factory()->create(['status' => 'running']);

        $this->assertEquals('green', $successRun->statusColor);
        $this->assertEquals('red', $failedRun->statusColor);
        $this->assertEquals('yellow', $runningRun->statusColor);
    }

    public function test_pipeline_stage_run_status_icon(): void
    {
        $successRun = PipelineStageRun::factory()->create(['status' => 'success']);
        $failedRun = PipelineStageRun::factory()->create(['status' => 'failed']);
        $runningRun = PipelineStageRun::factory()->create(['status' => 'running']);

        $this->assertEquals('check-circle', $successRun->statusIcon);
        $this->assertEquals('x-circle', $failedRun->statusIcon);
        $this->assertEquals('arrow-path', $runningRun->statusIcon);
    }

    public function test_pipeline_stage_run_formatted_duration(): void
    {
        $stageRun = PipelineStageRun::factory()->create(['duration_seconds' => 150]);

        $formatted = $stageRun->formattedDuration;

        $this->assertStringContainsString('2m', $formatted);
        $this->assertStringContainsString('30s', $formatted);
    }

    // ========================================
    // ResourceAlert Model Tests
    // ========================================

    public function test_resource_alert_has_fillable_attributes(): void
    {
        $alert = new ResourceAlert();
        $fillable = $alert->getFillable();

        $this->assertContains('server_id', $fillable);
        $this->assertContains('resource_type', $fillable);
        $this->assertContains('threshold_type', $fillable);
        $this->assertContains('threshold_value', $fillable);
        $this->assertContains('notification_channels', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('cooldown_minutes', $fillable);
        $this->assertContains('last_triggered_at', $fillable);
    }

    public function test_resource_alert_has_correct_casts(): void
    {
        $alert = new ResourceAlert();
        $casts = $alert->getCasts();

        $this->assertEquals('array', $casts['notification_channels']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('decimal:2', $casts['threshold_value']);
        $this->assertEquals('integer', $casts['cooldown_minutes']);
        $this->assertEquals('datetime', $casts['last_triggered_at']);
    }

    public function test_resource_alert_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $alert = ResourceAlert::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $alert->server);
        $this->assertEquals($server->id, $alert->server->id);
    }

    public function test_resource_alert_active_scope(): void
    {
        ResourceAlert::factory()->create(['is_active' => true]);
        ResourceAlert::factory()->create(['is_active' => false]);

        $activeAlerts = ResourceAlert::active()->get();

        $this->assertCount(1, $activeAlerts);
        $this->assertTrue($activeAlerts->first()->is_active);
    }

    public function test_resource_alert_for_server_scope(): void
    {
        $server1 = Server::factory()->create();
        $server2 = Server::factory()->create();
        ResourceAlert::factory()->create(['server_id' => $server1->id]);
        ResourceAlert::factory()->create(['server_id' => $server2->id]);

        $alerts = ResourceAlert::forServer($server1->id)->get();

        $this->assertCount(1, $alerts);
        $this->assertEquals($server1->id, $alerts->first()->server_id);
    }

    public function test_resource_alert_for_resource_type_scope(): void
    {
        ResourceAlert::factory()->create(['resource_type' => 'cpu']);
        ResourceAlert::factory()->create(['resource_type' => 'memory']);

        $alerts = ResourceAlert::forResourceType('cpu')->get();

        $this->assertCount(1, $alerts);
        $this->assertEquals('cpu', $alerts->first()->resource_type);
    }

    public function test_resource_alert_resource_type_icon(): void
    {
        $cpuAlert = ResourceAlert::factory()->create(['resource_type' => 'cpu']);
        $memoryAlert = ResourceAlert::factory()->create(['resource_type' => 'memory']);

        $this->assertEquals('heroicon-o-cpu-chip', $cpuAlert->resource_type_icon);
        $this->assertEquals('heroicon-o-circle-stack', $memoryAlert->resource_type_icon);
    }

    public function test_resource_alert_resource_type_label(): void
    {
        $cpuAlert = ResourceAlert::factory()->create(['resource_type' => 'cpu']);
        $memoryAlert = ResourceAlert::factory()->create(['resource_type' => 'memory']);

        $this->assertEquals('CPU Usage', $cpuAlert->resource_type_label);
        $this->assertEquals('Memory Usage', $memoryAlert->resource_type_label);
    }

    public function test_resource_alert_threshold_display(): void
    {
        $alert = ResourceAlert::factory()->create([
            'threshold_type' => 'above',
            'threshold_value' => 80,
            'resource_type' => 'cpu',
        ]);

        // threshold_value is cast as decimal:2, so it shows '80.00%'
        $this->assertEquals('> 80.00%', $alert->threshold_display);
    }

    public function test_resource_alert_is_in_cooldown(): void
    {
        $alertInCooldown = ResourceAlert::factory()->create([
            'last_triggered_at' => now()->subMinutes(5),
            'cooldown_minutes' => 10,
        ]);

        $alertNotInCooldown = ResourceAlert::factory()->create([
            'last_triggered_at' => now()->subMinutes(15),
            'cooldown_minutes' => 10,
        ]);

        $this->assertTrue($alertInCooldown->isInCooldown());
        $this->assertFalse($alertNotInCooldown->isInCooldown());
    }

    public function test_resource_alert_cooldown_remaining_minutes(): void
    {
        $alert = ResourceAlert::factory()->create([
            'last_triggered_at' => now()->subMinutes(5),
            'cooldown_minutes' => 10,
        ]);

        $remaining = $alert->cooldown_remaining_minutes;

        $this->assertNotNull($remaining);
        $this->assertGreaterThan(0, $remaining);
    }

    // ========================================
    // SecurityEvent Model Tests
    // ========================================

    public function test_security_event_has_fillable_attributes(): void
    {
        $event = new SecurityEvent();
        $fillable = $event->getFillable();

        $this->assertContains('server_id', $fillable);
        $this->assertContains('event_type', $fillable);
        $this->assertContains('source_ip', $fillable);
        $this->assertContains('details', $fillable);
        $this->assertContains('metadata', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    public function test_security_event_has_correct_casts(): void
    {
        $event = new SecurityEvent();
        $casts = $event->getCasts();

        $this->assertEquals('array', $casts['metadata']);
    }

    public function test_security_event_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $event = SecurityEvent::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $event->server);
        $this->assertEquals($server->id, $event->server->id);
    }

    public function test_security_event_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $event = SecurityEvent::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    public function test_security_event_type_label(): void
    {
        $event = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED]);

        $this->assertEquals('Firewall Enabled', $event->getEventTypeLabel());
    }

    public function test_security_event_type_color(): void
    {
        $enabledEvent = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED]);
        $disabledEvent = SecurityEvent::factory()->create(['event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED]);

        $this->assertEquals('green', $enabledEvent->event_type_color);
        $this->assertEquals('red', $disabledEvent->event_type_color);
    }

    // ========================================
    // SecurityScan Model Tests
    // ========================================

    public function test_security_scan_has_fillable_attributes(): void
    {
        $scan = new SecurityScan();
        $fillable = $scan->getFillable();

        $this->assertContains('server_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('score', $fillable);
        $this->assertContains('risk_level', $fillable);
        $this->assertContains('findings', $fillable);
        $this->assertContains('recommendations', $fillable);
        $this->assertContains('started_at', $fillable);
        $this->assertContains('completed_at', $fillable);
        $this->assertContains('triggered_by', $fillable);
    }

    public function test_security_scan_has_correct_casts(): void
    {
        $scan = new SecurityScan();
        $casts = $scan->getCasts();

        $this->assertEquals('integer', $casts['score']);
        $this->assertEquals('array', $casts['findings']);
        $this->assertEquals('array', $casts['recommendations']);
        $this->assertEquals('datetime', $casts['started_at']);
        $this->assertEquals('datetime', $casts['completed_at']);
    }

    public function test_security_scan_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $scan = SecurityScan::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $scan->server);
        $this->assertEquals($server->id, $scan->server->id);
    }

    public function test_security_scan_belongs_to_triggered_by_user(): void
    {
        $user = User::factory()->create();
        $scan = SecurityScan::factory()->create(['triggered_by' => $user->id]);

        $this->assertInstanceOf(User::class, $scan->triggeredBy);
        $this->assertEquals($user->id, $scan->triggeredBy->id);
    }

    public function test_security_scan_duration_accessor(): void
    {
        $scan = SecurityScan::factory()->create([
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $duration = $scan->duration;

        $this->assertIsInt($duration);
        $this->assertEquals(300, $duration);
    }

    public function test_security_scan_risk_level_color(): void
    {
        $criticalScan = SecurityScan::factory()->create(['risk_level' => SecurityScan::RISK_CRITICAL]);
        $secureScan = SecurityScan::factory()->create(['risk_level' => SecurityScan::RISK_SECURE]);

        $this->assertEquals('red', $criticalScan->risk_level_color);
        $this->assertEquals('emerald', $secureScan->risk_level_color);
    }

    public function test_security_scan_score_color(): void
    {
        $highScoreScan = SecurityScan::factory()->create(['score' => 95]);
        $lowScoreScan = SecurityScan::factory()->create(['score' => 30]);

        $this->assertEquals('emerald', $highScoreScan->score_color);
        $this->assertEquals('red', $lowScoreScan->score_color);
    }

    public function test_security_scan_get_risk_level_from_score(): void
    {
        $this->assertEquals(SecurityScan::RISK_SECURE, SecurityScan::getRiskLevelFromScore(95));
        $this->assertEquals(SecurityScan::RISK_LOW, SecurityScan::getRiskLevelFromScore(85));
        $this->assertEquals(SecurityScan::RISK_MEDIUM, SecurityScan::getRiskLevelFromScore(70));
        $this->assertEquals(SecurityScan::RISK_HIGH, SecurityScan::getRiskLevelFromScore(50));
        $this->assertEquals(SecurityScan::RISK_CRITICAL, SecurityScan::getRiskLevelFromScore(30));
    }

    // ========================================
    // NotificationLog Model Tests
    // ========================================

    public function test_notification_log_has_fillable_attributes(): void
    {
        $log = new NotificationLog();
        $fillable = $log->getFillable();

        $this->assertContains('notification_channel_id', $fillable);
        $this->assertContains('event_type', $fillable);
        $this->assertContains('payload', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('error_message', $fillable);
    }

    public function test_notification_log_has_correct_casts(): void
    {
        $log = new NotificationLog();
        $casts = $log->getCasts();

        $this->assertEquals('array', $casts['payload']);
    }

    public function test_notification_log_belongs_to_channel(): void
    {
        $channel = NotificationChannel::factory()->create();
        $log = NotificationLog::factory()->create(['notification_channel_id' => $channel->id]);

        $this->assertInstanceOf(NotificationChannel::class, $log->channel);
        $this->assertEquals($channel->id, $log->channel->id);
    }

    public function test_notification_log_is_sent(): void
    {
        $sentLog = NotificationLog::factory()->create(['status' => 'sent']);
        $failedLog = NotificationLog::factory()->create(['status' => 'failed']);

        $this->assertTrue($sentLog->isSent());
        $this->assertFalse($failedLog->isSent());
    }

    public function test_notification_log_is_failed(): void
    {
        $sentLog = NotificationLog::factory()->create(['status' => 'sent']);
        $failedLog = NotificationLog::factory()->create(['status' => 'failed']);

        $this->assertFalse($sentLog->isFailed());
        $this->assertTrue($failedLog->isFailed());
    }

    // ========================================
    // ProjectAnalytic Model Tests
    // ========================================

    public function test_project_analytic_has_fillable_attributes(): void
    {
        $analytic = new ProjectAnalytic();
        $fillable = $analytic->getFillable();

        $this->assertContains('project_id', $fillable);
        $this->assertContains('metric_type', $fillable);
        $this->assertContains('metric_value', $fillable);
        $this->assertContains('metadata', $fillable);
        $this->assertContains('recorded_at', $fillable);
    }

    public function test_project_analytic_has_correct_casts(): void
    {
        $analytic = new ProjectAnalytic();
        $casts = $analytic->getCasts();

        $this->assertEquals('decimal:2', $casts['metric_value']);
        $this->assertEquals('array', $casts['metadata']);
        $this->assertEquals('datetime', $casts['recorded_at']);
    }

    public function test_project_analytic_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $analytic = ProjectAnalytic::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $analytic->project);
        $this->assertEquals($project->id, $analytic->project->id);
    }

    // ========================================
    // ProjectSetupTask Model Tests
    // ========================================

    public function test_project_setup_task_has_fillable_attributes(): void
    {
        $task = new ProjectSetupTask();
        $fillable = $task->getFillable();

        $this->assertContains('project_id', $fillable);
        $this->assertContains('task_type', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('message', $fillable);
        $this->assertContains('result_data', $fillable);
        $this->assertContains('progress', $fillable);
        $this->assertContains('started_at', $fillable);
        $this->assertContains('completed_at', $fillable);
    }

    public function test_project_setup_task_has_correct_casts(): void
    {
        $task = new ProjectSetupTask();
        $casts = $task->getCasts();

        $this->assertEquals('array', $casts['result_data']);
        $this->assertEquals('integer', $casts['progress']);
        $this->assertEquals('datetime', $casts['started_at']);
        $this->assertEquals('datetime', $casts['completed_at']);
    }

    public function test_project_setup_task_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $task = ProjectSetupTask::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    public function test_project_setup_task_mark_as_running(): void
    {
        $task = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_PENDING]);

        $task->markAsRunning();

        $this->assertEquals(ProjectSetupTask::STATUS_RUNNING, $task->status);
        $this->assertNotNull($task->started_at);
    }

    public function test_project_setup_task_mark_as_completed(): void
    {
        $task = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_RUNNING]);

        $task->markAsCompleted('Task completed', ['result' => 'success']);

        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('Task completed', $task->message);
        $this->assertEquals(['result' => 'success'], $task->result_data);
        $this->assertEquals(100, $task->progress);
        $this->assertNotNull($task->completed_at);
    }

    public function test_project_setup_task_mark_as_failed(): void
    {
        $task = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_RUNNING]);

        $task->markAsFailed('Task failed');

        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('Task failed', $task->message);
        $this->assertNotNull($task->completed_at);
    }

    public function test_project_setup_task_mark_as_skipped(): void
    {
        $task = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_PENDING]);

        $task->markAsSkipped('Skipped by user');

        $this->assertEquals(ProjectSetupTask::STATUS_SKIPPED, $task->status);
        $this->assertEquals('Skipped by user', $task->message);
        $this->assertNotNull($task->completed_at);
    }

    public function test_project_setup_task_update_progress(): void
    {
        $task = ProjectSetupTask::factory()->create(['progress' => 0]);

        $task->updateProgress(50, 'Halfway there');

        $this->assertEquals(50, $task->progress);
        $this->assertEquals('Halfway there', $task->message);
    }

    public function test_project_setup_task_status_checks(): void
    {
        $pendingTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_PENDING]);
        $runningTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_RUNNING]);
        $completedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_COMPLETED]);
        $failedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_FAILED]);
        $skippedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_SKIPPED]);

        $this->assertTrue($pendingTask->isPending());
        $this->assertTrue($runningTask->isRunning());
        $this->assertTrue($completedTask->isCompleted());
        $this->assertTrue($failedTask->isFailed());
        $this->assertTrue($skippedTask->isSkipped());
    }

    public function test_project_setup_task_is_done(): void
    {
        $completedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_COMPLETED]);
        $failedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_FAILED]);
        $runningTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_RUNNING]);

        $this->assertTrue($completedTask->isDone());
        $this->assertTrue($failedTask->isDone());
        $this->assertFalse($runningTask->isDone());
    }

    public function test_project_setup_task_status_color(): void
    {
        $completedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_COMPLETED]);
        $failedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_FAILED]);

        $this->assertEquals('green', $completedTask->status_color);
        $this->assertEquals('red', $failedTask->status_color);
    }

    public function test_project_setup_task_status_icon(): void
    {
        $completedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_COMPLETED]);
        $failedTask = ProjectSetupTask::factory()->create(['status' => ProjectSetupTask::STATUS_FAILED]);

        $this->assertEquals('check-circle', $completedTask->status_icon);
        $this->assertEquals('x-circle', $failedTask->status_icon);
    }

    public function test_project_setup_task_get_all_types(): void
    {
        $types = ProjectSetupTask::getAllTypes();

        $this->assertIsArray($types);
        $this->assertContains(ProjectSetupTask::TYPE_SSL, $types);
        $this->assertContains(ProjectSetupTask::TYPE_WEBHOOK, $types);
    }

    public function test_project_setup_task_get_type_label(): void
    {
        $label = ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_SSL);

        $this->assertEquals('SSL Certificate', $label);
    }

    // ========================================
    // DeploymentApproval Model Tests
    // ========================================

    public function test_deployment_approval_has_fillable_attributes(): void
    {
        $approval = new DeploymentApproval();
        $fillable = $approval->getFillable();

        $this->assertContains('deployment_id', $fillable);
        $this->assertContains('requested_by', $fillable);
        $this->assertContains('approved_by', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('notes', $fillable);
        $this->assertContains('requested_at', $fillable);
        $this->assertContains('responded_at', $fillable);
    }

    public function test_deployment_approval_has_correct_casts(): void
    {
        $approval = new DeploymentApproval();
        $casts = $approval->getCasts();

        $this->assertEquals('datetime', $casts['requested_at']);
        $this->assertEquals('datetime', $casts['responded_at']);
    }

    public function test_deployment_approval_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $approval = DeploymentApproval::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $approval->deployment);
        $this->assertEquals($deployment->id, $approval->deployment->id);
    }

    public function test_deployment_approval_belongs_to_requester(): void
    {
        $user = User::factory()->create();
        $approval = DeploymentApproval::factory()->create(['requested_by' => $user->id]);

        $this->assertInstanceOf(User::class, $approval->requester);
        $this->assertEquals($user->id, $approval->requester->id);
    }

    public function test_deployment_approval_belongs_to_approver(): void
    {
        $user = User::factory()->create();
        $approval = DeploymentApproval::factory()->create(['approved_by' => $user->id]);

        $this->assertInstanceOf(User::class, $approval->approver);
        $this->assertEquals($user->id, $approval->approver->id);
    }

    public function test_deployment_approval_status_checks(): void
    {
        $pendingApproval = DeploymentApproval::factory()->create(['status' => 'pending']);
        $approvedApproval = DeploymentApproval::factory()->create(['status' => 'approved']);
        $rejectedApproval = DeploymentApproval::factory()->create(['status' => 'rejected']);

        $this->assertTrue($pendingApproval->isPending());
        $this->assertTrue($approvedApproval->isApproved());
        $this->assertTrue($rejectedApproval->isRejected());
    }

    public function test_deployment_approval_status_color(): void
    {
        $approvedApproval = DeploymentApproval::factory()->create(['status' => 'approved']);
        $rejectedApproval = DeploymentApproval::factory()->create(['status' => 'rejected']);

        $this->assertEquals('green', $approvedApproval->status_color);
        $this->assertEquals('red', $rejectedApproval->status_color);
    }

    public function test_deployment_approval_status_icon(): void
    {
        $approvedApproval = DeploymentApproval::factory()->create(['status' => 'approved']);
        $rejectedApproval = DeploymentApproval::factory()->create(['status' => 'rejected']);

        $this->assertEquals('check-circle', $approvedApproval->status_icon);
        $this->assertEquals('x-circle', $rejectedApproval->status_icon);
    }

    // ========================================
    // DeploymentComment Model Tests
    // ========================================

    public function test_deployment_comment_has_fillable_attributes(): void
    {
        $comment = new DeploymentComment();
        $fillable = $comment->getFillable();

        $this->assertContains('deployment_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('content', $fillable);
        $this->assertContains('mentions', $fillable);
    }

    public function test_deployment_comment_has_correct_casts(): void
    {
        $comment = new DeploymentComment();
        $casts = $comment->getCasts();

        $this->assertEquals('array', $casts['mentions']);
    }

    public function test_deployment_comment_belongs_to_deployment(): void
    {
        $deployment = Deployment::factory()->create();
        $comment = DeploymentComment::factory()->create(['deployment_id' => $deployment->id]);

        $this->assertInstanceOf(Deployment::class, $comment->deployment);
        $this->assertEquals($deployment->id, $comment->deployment->id);
    }

    public function test_deployment_comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $comment = DeploymentComment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    public function test_deployment_comment_extract_mentions(): void
    {
        $user = User::factory()->create(['name' => 'John']);
        $comment = DeploymentComment::factory()->create([
            'content' => 'Hey @John, check this out!',
        ]);

        $mentions = $comment->extractMentions();

        $this->assertIsArray($mentions);
        $this->assertContains($user->id, $mentions);
    }

    public function test_deployment_comment_formatted_content(): void
    {
        $user = User::factory()->create(['name' => 'John']);
        $comment = DeploymentComment::factory()->create([
            'content' => 'Hey @John, check this out!',
            'mentions' => [$user->id],
        ]);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('@John', $formatted);
        $this->assertStringContainsString('span', $formatted);
    }

    // ========================================
    // DeploymentScript Model Tests
    // ========================================

    public function test_deployment_script_has_fillable_attributes(): void
    {
        $script = new DeploymentScript();
        $fillable = $script->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('language', $fillable);
        $this->assertContains('script', $fillable);
        $this->assertContains('variables', $fillable);
        $this->assertContains('run_as', $fillable);
        $this->assertContains('timeout', $fillable);
        $this->assertContains('is_template', $fillable);
        $this->assertContains('tags', $fillable);
    }

    public function test_deployment_script_has_correct_casts(): void
    {
        $script = new DeploymentScript();
        $casts = $script->getCasts();

        $this->assertEquals('array', $casts['variables']);
        $this->assertEquals('boolean', $casts['is_template']);
        $this->assertEquals('array', $casts['tags']);
        $this->assertEquals('integer', $casts['timeout']);
    }

    public function test_deployment_script_has_many_runs(): void
    {
        $script = DeploymentScript::factory()->create();

        // Create runs manually since factory doesn't exist
        $project = Project::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            DeploymentScriptRun::create([
                'project_id' => $project->id,
                'deployment_script_id' => $script->id,
                'status' => 'pending',
            ]);
        }

        $this->assertCount(3, $script->runs);
    }

    public function test_deployment_script_executable_script(): void
    {
        $script = DeploymentScript::factory()->create([
            'script' => 'echo {VAR1} {VAR2}',
            'variables' => ['{VAR1}' => 'Hello', '{VAR2}' => 'World'],
        ]);

        $executable = $script->executable_script;

        $this->assertEquals('echo Hello World', $executable);
    }

    // ========================================
    // TenantDeployment Model Tests
    // ========================================

    public function test_tenant_deployment_has_fillable_attributes(): void
    {
        $tenantDeployment = new TenantDeployment();
        $fillable = $tenantDeployment->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('deployment_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('output', $fillable);
    }

    public function test_tenant_deployment_belongs_to_tenant(): void
    {
        $project = Project::factory()->create();
        $tenant = Tenant::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $tenantDeployment = TenantDeployment::create([
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenantDeployment->tenant);
        $this->assertEquals($tenant->id, $tenantDeployment->tenant->id);
    }

    public function test_tenant_deployment_belongs_to_deployment(): void
    {
        $project = Project::factory()->create();
        $tenant = Tenant::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $tenantDeployment = TenantDeployment::create([
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Deployment::class, $tenantDeployment->deployment);
        $this->assertEquals($deployment->id, $tenantDeployment->deployment->id);
    }

    public function test_tenant_deployment_creates_valid_instance(): void
    {
        $project = Project::factory()->create();
        $tenant = Tenant::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $tenantDeployment = TenantDeployment::create([
            'tenant_id' => $tenant->id,
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(TenantDeployment::class, $tenantDeployment);
        $this->assertDatabaseHas('tenant_deployments', [
            'id' => $tenantDeployment->id,
        ]);
    }
}
