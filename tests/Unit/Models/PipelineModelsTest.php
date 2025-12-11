<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Models\Project;
use Tests\TestCase;

class PipelineModelsTest extends TestCase
{
    // ========================
    // PipelineStage Model Tests
    // ========================

    /** @test */
    public function pipeline_stage_can_be_created_with_factory(): void
    {
        $stage = PipelineStage::factory()->create();

        $this->assertInstanceOf(PipelineStage::class, $stage);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
        ]);
    }

    /** @test */
    public function pipeline_stage_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $stage = PipelineStage::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $stage->project);
        $this->assertEquals($project->id, $stage->project->id);
    }

    /** @test */
    public function pipeline_stage_has_many_stage_runs(): void
    {
        $stage = PipelineStage::factory()->create();
        PipelineStageRun::factory()->count(3)->create(['pipeline_stage_id' => $stage->id]);

        $this->assertCount(3, $stage->stageRuns);
        $this->assertInstanceOf(PipelineStageRun::class, $stage->stageRuns->first());
    }

    /** @test */
    public function pipeline_stage_has_latest_run_relationship(): void
    {
        $stage = PipelineStage::factory()->create();
        $oldest = PipelineStageRun::factory()->create([
            'pipeline_stage_id' => $stage->id,
            'created_at' => now()->subHours(2),
        ]);
        $latest = PipelineStageRun::factory()->create([
            'pipeline_stage_id' => $stage->id,
            'created_at' => now(),
        ]);

        $this->assertNotNull($stage->latestRun);
        $this->assertEquals($latest->id, $stage->latestRun->id);
    }

    /** @test */
    public function pipeline_stage_casts_commands_as_array(): void
    {
        $commands = ['composer install', 'npm run build', 'php artisan migrate'];
        $stage = PipelineStage::factory()->create(['commands' => $commands]);

        $this->assertIsArray($stage->commands);
        $this->assertEquals($commands, $stage->commands);
    }

    /** @test */
    public function pipeline_stage_casts_environment_variables_as_array(): void
    {
        $envVars = ['NODE_ENV' => 'production', 'APP_DEBUG' => 'false'];
        $stage = PipelineStage::factory()->create(['environment_variables' => $envVars]);

        $this->assertIsArray($stage->environment_variables);
        $this->assertEquals($envVars, $stage->environment_variables);
    }

    /** @test */
    public function pipeline_stage_casts_enabled_as_boolean(): void
    {
        $stage = PipelineStage::factory()->create(['enabled' => true]);

        $this->assertTrue($stage->enabled);
        $this->assertIsBool($stage->enabled);
    }

    /** @test */
    public function pipeline_stage_casts_continue_on_failure_as_boolean(): void
    {
        $stage = PipelineStage::factory()->create(['continue_on_failure' => true]);

        $this->assertTrue($stage->continue_on_failure);
        $this->assertIsBool($stage->continue_on_failure);
    }

    /** @test */
    public function pipeline_stage_casts_timeout_seconds_as_integer(): void
    {
        $stage = PipelineStage::factory()->create(['timeout_seconds' => 300]);

        $this->assertIsInt($stage->timeout_seconds);
        $this->assertEquals(300, $stage->timeout_seconds);
    }

    /** @test */
    public function pipeline_stage_casts_order_as_integer(): void
    {
        $stage = PipelineStage::factory()->create(['order' => 1]);

        $this->assertIsInt($stage->order);
        $this->assertEquals(1, $stage->order);
    }

    /** @test */
    public function pipeline_stage_scope_enabled_filters_enabled_stages(): void
    {
        PipelineStage::factory()->create(['enabled' => true]);
        PipelineStage::factory()->create(['enabled' => true]);
        PipelineStage::factory()->create(['enabled' => false]);

        $enabled = PipelineStage::enabled()->get();
        $this->assertCount(2, $enabled);
    }

    /** @test */
    public function pipeline_stage_scope_ordered_sorts_by_order_field(): void
    {
        $stage3 = PipelineStage::factory()->create(['order' => 3]);
        $stage1 = PipelineStage::factory()->create(['order' => 1]);
        $stage2 = PipelineStage::factory()->create(['order' => 2]);

        $ordered = PipelineStage::ordered()->get();

        $this->assertEquals($stage1->id, $ordered->first()->id);
        $this->assertEquals($stage3->id, $ordered->last()->id);
    }

    /** @test */
    public function pipeline_stage_scope_by_type_filters_by_type(): void
    {
        PipelineStage::factory()->count(2)->create(['type' => 'pre_deploy']);
        PipelineStage::factory()->create(['type' => 'deploy']);
        PipelineStage::factory()->create(['type' => 'post_deploy']);

        $preDeployStages = PipelineStage::byType('pre_deploy')->get();
        $this->assertCount(2, $preDeployStages);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_test_icon_for_test_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Run Tests']);

        $this->assertEquals('flask', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_build_icon_for_build_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Build Assets']);

        $this->assertEquals('gear', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_deploy_icon_for_deploy_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Deploy to Production']);

        $this->assertEquals('rocket', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_security_icon_for_security_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Security Scan']);

        $this->assertEquals('shield', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_package_icon_for_install_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Install Dependencies']);

        $this->assertEquals('package', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_database_icon_for_migration_stage(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Run Migrations']);

        $this->assertEquals('database', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_icon_attribute_returns_default_code_icon(): void
    {
        $stage = PipelineStage::factory()->create(['name' => 'Custom Stage']);

        $this->assertEquals('code', $stage->icon);
    }

    /** @test */
    public function pipeline_stage_color_attribute_returns_correct_colors(): void
    {
        $preDeploy = PipelineStage::factory()->create(['type' => 'pre_deploy']);
        $this->assertEquals('blue', $preDeploy->color);

        $deploy = PipelineStage::factory()->create(['type' => 'deploy']);
        $this->assertEquals('green', $deploy->color);

        $postDeploy = PipelineStage::factory()->create(['type' => 'post_deploy']);
        $this->assertEquals('purple', $postDeploy->color);

        $custom = PipelineStage::factory()->create(['type' => 'custom']);
        $this->assertEquals('gray', $custom->color);
    }

    // ========================
    // PipelineStageRun Model Tests
    // ========================

    /** @test */
    public function pipeline_stage_run_can_be_created_with_factory(): void
    {
        $run = PipelineStageRun::factory()->create();

        $this->assertInstanceOf(PipelineStageRun::class, $run);
        $this->assertDatabaseHas('pipeline_stage_runs', [
            'id' => $run->id,
        ]);
    }

    /** @test */
    public function pipeline_stage_run_belongs_to_pipeline_run(): void
    {
        $pipelineRun = PipelineRun::factory()->create();
        $stageRun = PipelineStageRun::factory()->create(['pipeline_run_id' => $pipelineRun->id]);

        $this->assertInstanceOf(PipelineRun::class, $stageRun->pipelineRun);
        $this->assertEquals($pipelineRun->id, $stageRun->pipelineRun->id);
    }

    /** @test */
    public function pipeline_stage_run_belongs_to_pipeline_stage(): void
    {
        $stage = PipelineStage::factory()->create();
        $stageRun = PipelineStageRun::factory()->create(['pipeline_stage_id' => $stage->id]);

        $this->assertInstanceOf(PipelineStage::class, $stageRun->pipelineStage);
        $this->assertEquals($stage->id, $stageRun->pipelineStage->id);
    }

    /** @test */
    public function pipeline_stage_run_casts_datetime_attributes(): void
    {
        $run = PipelineStageRun::factory()->create([
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $run->started_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $run->completed_at);
    }

    /** @test */
    public function pipeline_stage_run_casts_duration_seconds_as_integer(): void
    {
        $run = PipelineStageRun::factory()->create(['duration_seconds' => 120]);

        $this->assertIsInt($run->duration_seconds);
        $this->assertEquals(120, $run->duration_seconds);
    }

    /** @test */
    public function pipeline_stage_run_mark_running_updates_status_and_started_at(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'pending']);

        $run->markRunning();
        $run->refresh();

        $this->assertEquals('running', $run->status);
        $this->assertNotNull($run->started_at);
    }

    /** @test */
    public function pipeline_stage_run_mark_success_updates_status_and_completed_at(): void
    {
        $run = PipelineStageRun::factory()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(2),
        ]);

        $run->markSuccess();
        $run->refresh();

        $this->assertEquals('success', $run->status);
        $this->assertNotNull($run->completed_at);
        $this->assertGreaterThan(0, $run->duration_seconds);
    }

    /** @test */
    public function pipeline_stage_run_mark_failed_updates_status_and_error_message(): void
    {
        $run = PipelineStageRun::factory()->create([
            'status' => 'running',
            'started_at' => now()->subMinutes(1),
        ]);

        $run->markFailed('Command failed with error');
        $run->refresh();

        $this->assertEquals('failed', $run->status);
        $this->assertEquals('Command failed with error', $run->error_message);
        $this->assertNotNull($run->completed_at);
        $this->assertGreaterThan(0, $run->duration_seconds);
    }

    /** @test */
    public function pipeline_stage_run_mark_skipped_updates_status(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'pending']);

        $run->markSkipped();
        $run->refresh();

        $this->assertEquals('skipped', $run->status);
        $this->assertNotNull($run->completed_at);
    }

    /** @test */
    public function pipeline_stage_run_append_output_adds_line_to_output(): void
    {
        $run = PipelineStageRun::factory()->create(['output' => 'Line 1']);

        $run->appendOutput('Line 2');
        $run->refresh();

        $this->assertStringContainsString('Line 1', $run->output);
        $this->assertStringContainsString('Line 2', $run->output);
    }

    /** @test */
    public function pipeline_stage_run_append_output_handles_null_output(): void
    {
        $run = PipelineStageRun::factory()->create(['output' => null]);

        $run->appendOutput('First line');
        $run->refresh();

        $this->assertStringContainsString('First line', $run->output);
    }

    /** @test */
    public function pipeline_stage_run_is_running_returns_true_when_running(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'running']);

        $this->assertTrue($run->isRunning());
    }

    /** @test */
    public function pipeline_stage_run_is_running_returns_false_when_not_running(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'success']);

        $this->assertFalse($run->isRunning());
    }

    /** @test */
    public function pipeline_stage_run_is_success_returns_true_when_success(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'success']);

        $this->assertTrue($run->isSuccess());
    }

    /** @test */
    public function pipeline_stage_run_is_failed_returns_true_when_failed(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'failed']);

        $this->assertTrue($run->isFailed());
    }

    /** @test */
    public function pipeline_stage_run_is_skipped_returns_true_when_skipped(): void
    {
        $run = PipelineStageRun::factory()->create(['status' => 'skipped']);

        $this->assertTrue($run->isSkipped());
    }

    /** @test */
    public function pipeline_stage_run_status_color_returns_correct_colors(): void
    {
        $success = PipelineStageRun::factory()->create(['status' => 'success']);
        $this->assertEquals('green', $success->statusColor);

        $failed = PipelineStageRun::factory()->create(['status' => 'failed']);
        $this->assertEquals('red', $failed->statusColor);

        $running = PipelineStageRun::factory()->create(['status' => 'running']);
        $this->assertEquals('yellow', $running->statusColor);

        $pending = PipelineStageRun::factory()->create(['status' => 'pending']);
        $this->assertEquals('blue', $pending->statusColor);

        $skipped = PipelineStageRun::factory()->create(['status' => 'skipped']);
        $this->assertEquals('gray', $skipped->statusColor);
    }

    /** @test */
    public function pipeline_stage_run_status_icon_returns_correct_icons(): void
    {
        $success = PipelineStageRun::factory()->create(['status' => 'success']);
        $this->assertEquals('check-circle', $success->statusIcon);

        $failed = PipelineStageRun::factory()->create(['status' => 'failed']);
        $this->assertEquals('x-circle', $failed->statusIcon);

        $running = PipelineStageRun::factory()->create(['status' => 'running']);
        $this->assertEquals('arrow-path', $running->statusIcon);

        $pending = PipelineStageRun::factory()->create(['status' => 'pending']);
        $this->assertEquals('clock', $pending->statusIcon);

        $skipped = PipelineStageRun::factory()->create(['status' => 'skipped']);
        $this->assertEquals('minus-circle', $skipped->statusIcon);
    }

    /** @test */
    public function pipeline_stage_run_formatted_duration_returns_dash_when_null(): void
    {
        $run = PipelineStageRun::factory()->create(['duration_seconds' => null]);

        $this->assertEquals('-', $run->formattedDuration);
    }

    /** @test */
    public function pipeline_stage_run_formatted_duration_returns_seconds_only_under_one_minute(): void
    {
        $run = PipelineStageRun::factory()->create(['duration_seconds' => 45]);

        $this->assertEquals('45s', $run->formattedDuration);
    }

    /** @test */
    public function pipeline_stage_run_formatted_duration_returns_minutes_and_seconds(): void
    {
        $run = PipelineStageRun::factory()->create(['duration_seconds' => 125]);

        $this->assertEquals('2m 5s', $run->formattedDuration);
    }

    /** @test */
    public function pipeline_stage_run_formatted_duration_handles_exact_minutes(): void
    {
        $run = PipelineStageRun::factory()->create(['duration_seconds' => 120]);

        $this->assertEquals('2m 0s', $run->formattedDuration);
    }
}
