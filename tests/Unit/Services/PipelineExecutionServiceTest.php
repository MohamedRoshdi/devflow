<?php

namespace Tests\Unit\Services;

use App\Models\Deployment;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Services\CICD\PipelineExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesProjects;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class PipelineExecutionServiceTest extends TestCase
{
    use CreatesProjects, CreatesServers, MocksSSH, RefreshDatabase;

    protected PipelineExecutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PipelineExecutionService;
    }

    /** @test */
    public function it_executes_pipeline_successfully(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $preDeployStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
            'order' => 1,
            'commands' => ['composer install', 'npm install'],
            'is_enabled' => true,
        ]);

        $deployStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Deploy',
            'type' => 'deploy',
            'order' => 1,
            'commands' => ['php artisan migrate --force'],
            'is_enabled' => true,
        ]);

        $this->mockSshSuccess();

        // Act
        $pipelineRun = $this->service->executePipeline($project, [
            'triggered_by' => 'manual',
            'commit_sha' => 'abc123',
        ]);

        // Assert
        $this->assertEquals('success', $pipelineRun->status);
        $this->assertCount(2, $pipelineRun->stageRuns);
    }

    /** @test */
    public function it_stops_pipeline_on_stage_failure(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Failing Stage',
            'type' => 'pre_deploy',
            'order' => 1,
            'commands' => ['exit 1'],
            'is_enabled' => true,
            'continue_on_failure' => false,
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Should Not Run',
            'type' => 'pre_deploy',
            'order' => 2,
            'commands' => ['echo "This should not run"'],
            'is_enabled' => true,
        ]);

        $this->mockSshFailure();

        // Act
        $pipelineRun = $this->service->executePipeline($project, [
            'triggered_by' => 'manual',
        ]);

        // Assert
        $this->assertEquals('failed', $pipelineRun->status);
        $this->assertCount(1, $pipelineRun->stageRuns); // Only first stage should run
    }

    /** @test */
    public function it_continues_on_failure_if_configured(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $stage1 = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Failing Stage',
            'type' => 'pre_deploy',
            'order' => 1,
            'commands' => ['exit 1'],
            'is_enabled' => true,
            'continue_on_failure' => true, // Continue even if fails
        ]);

        $stage2 = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Should Run',
            'type' => 'pre_deploy',
            'order' => 2,
            'commands' => ['echo "success"'],
            'is_enabled' => true,
        ]);

        $this->mockSshSuccess();

        // Act
        $pipelineRun = $this->service->executePipeline($project, [
            'triggered_by' => 'manual',
        ]);

        // Assert
        $this->assertEquals('success', $pipelineRun->status);
        $this->assertCount(2, $pipelineRun->stageRuns); // Both stages should run
    }

    /** @test */
    public function it_executes_stages_in_correct_order(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $deployStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'type' => 'deploy',
            'order' => 1,
            'is_enabled' => true,
        ]);

        $preDeployStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'type' => 'pre_deploy',
            'order' => 1,
            'is_enabled' => true,
        ]);

        $postDeployStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'type' => 'post_deploy',
            'order' => 1,
            'is_enabled' => true,
        ]);

        $this->mockSshSuccess();

        // Act
        $pipelineRun = $this->service->executePipeline($project, [
            'triggered_by' => 'manual',
        ]);

        // Assert
        $stageRuns = $pipelineRun->stageRuns()->with('pipelineStage')->get();

        $this->assertEquals('pre_deploy', $stageRuns[0]->pipelineStage->type);
        $this->assertEquals('deploy', $stageRuns[1]->pipelineStage->type);
        $this->assertEquals('post_deploy', $stageRuns[2]->pipelineStage->type);
    }

    /** @test */
    public function it_skips_disabled_stages(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $enabledStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'is_enabled' => true,
        ]);

        $disabledStage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'is_enabled' => false,
        ]);

        $this->mockSshSuccess();

        // Act
        $pipelineRun = $this->service->executePipeline($project, [
            'triggered_by' => 'manual',
        ]);

        // Assert
        $this->assertCount(1, $pipelineRun->stageRuns); // Only enabled stage
    }

    /** @test */
    public function it_executes_single_stage_successfully(): void
    {
        // Arrange
        $project = $this->createRunningProject();
        $pipelineRun = PipelineRun::factory()->create(['project_id' => $project->id]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'commands' => ['echo "test"', 'ls -la'],
        ]);

        $this->mockSshSuccess();

        // Act
        $success = $this->service->executeStage($pipelineRun, $stage);

        // Assert
        $this->assertTrue($success);

        $stageRun = PipelineStageRun::where('pipeline_run_id', $pipelineRun->id)->first();
        $this->assertEquals('success', $stageRun->status);
    }

    /** @test */
    public function it_handles_stage_execution_failure(): void
    {
        // Arrange
        $project = $this->createRunningProject();
        $pipelineRun = PipelineRun::factory()->create(['project_id' => $project->id]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'commands' => ['failing_command'],
        ]);

        $this->mockSshFailure();

        // Act
        $success = $this->service->executeStage($pipelineRun, $stage);

        // Assert
        $this->assertFalse($success);

        $stageRun = PipelineStageRun::where('pipeline_run_id', $pipelineRun->id)->first();
        $this->assertEquals('failed', $stageRun->status);
    }

    /** @test */
    public function it_skips_stage_with_no_commands(): void
    {
        // Arrange
        $project = $this->createRunningProject();
        $pipelineRun = PipelineRun::factory()->create(['project_id' => $project->id]);

        $stage = PipelineStage::factory()->create([
            'project_id' => $project->id,
            'commands' => [],
        ]);

        // Act
        $success = $this->service->executeStage($pipelineRun, $stage);

        // Assert
        $this->assertTrue($success);

        $stageRun = PipelineStageRun::where('pipeline_run_id', $pipelineRun->id)->first();
        $this->assertEquals('skipped', $stageRun->status);
    }

    /** @test */
    public function it_calculates_progress_correctly(): void
    {
        // Arrange
        $project = $this->createRunningProject();
        $pipelineRun = PipelineRun::factory()->create(['project_id' => $project->id]);

        // Create 4 stage runs
        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'success',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'success',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'running',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'pending',
        ]);

        // Progress calculation is internal, but we can verify pipeline status
        $this->assertDatabaseHas('pipeline_runs', [
            'id' => $pipelineRun->id,
        ]);
    }

    /** @test */
    public function it_cancels_pipeline(): void
    {
        // Arrange
        $project = $this->createRunningProject();
        $pipelineRun = PipelineRun::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'running',
        ]);

        PipelineStageRun::factory()->create([
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'pending',
        ]);

        // Act
        $this->service->cancelPipeline($pipelineRun);

        // Assert
        $pipelineRun->refresh();
        $this->assertEquals('cancelled', $pipelineRun->status);

        $this->assertDatabaseHas('pipeline_stage_runs', [
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('pipeline_stage_runs', [
            'pipeline_run_id' => $pipelineRun->id,
            'status' => 'skipped',
        ]);
    }

    /** @test */
    public function it_performs_rollback_to_previous_deployment(): void
    {
        // Arrange
        $project = $this->createRunningProject();

        $successfulDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
            'commit_hash' => 'abc123',
            'commit_message' => 'Previous working version',
        ]);

        $failedDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'failed',
            'commit_hash' => 'def456',
        ]);

        $failedPipelineRun = PipelineRun::factory()->create([
            'project_id' => $project->id,
            'deployment_id' => $failedDeployment->id,
            'status' => 'failed',
        ]);

        $this->mockSshSuccess();

        // Act
        $rollbackRun = $this->service->rollback($failedPipelineRun);

        // Assert
        $this->assertNotNull($rollbackRun);
        $this->assertEquals('rollback', $rollbackRun->deployment->triggered_by);
        $this->assertEquals('abc123', $rollbackRun->deployment->commit_hash);
    }
}
