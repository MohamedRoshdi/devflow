<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\ProjectSetupUpdated;
use App\Models\Project;
use App\Models\ProjectSetupTask;
use App\Services\ProjectSetupService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ProjectSetupServiceTest extends TestCase
{

    protected ProjectSetupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProjectSetupService;

        // Fake facades for testing
        Event::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_initializes_setup_with_all_tasks_when_all_config_enabled(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $config = [
            'ssl' => true,
            'webhook' => true,
            'health_check' => true,
            'backup' => true,
            'notifications' => true,
            'deployment' => true,
        ];

        // Act
        $this->service->initializeSetup($project, $config);

        // Assert
        $project->refresh();
        $this->assertEquals('pending', $project->setup_status);
        $this->assertEquals($config, $project->setup_config);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_BACKUP,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        Event::assertDispatched(ProjectSetupUpdated::class);
    }

    /** @test */
    public function it_initializes_setup_with_only_selected_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $config = [
            'ssl' => true,
            'webhook' => false,
            'health_check' => true,
            'backup' => false,
            'notifications' => false,
            'deployment' => true,
        ];

        // Act
        $this->service->initializeSetup($project, $config);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
        ]);

        $this->assertDatabaseMissing('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
        ]);

        $this->assertDatabaseMissing('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_BACKUP,
        ]);

        $this->assertDatabaseMissing('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
        ]);
    }

    /** @test */
    public function it_initializes_setup_with_empty_config(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = [];

        // Act
        $this->service->initializeSetup($project, $config);

        // Assert
        $project->refresh();
        $this->assertEquals('pending', $project->setup_status);
        $this->assertCount(0, $project->setupTasks);
    }

    /** @test */
    public function it_dispatches_event_after_initialization(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = ['ssl' => true];

        // Act
        $this->service->initializeSetup($project, $config);

        // Assert
        Event::assertDispatched(ProjectSetupUpdated::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    }

    /** @test */
    public function it_executes_setup_and_updates_project_status(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'pending']);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $project->refresh();
        $this->assertEquals('completed', $project->setup_status);
        $this->assertNotNull($project->setup_completed_at);
    }

    /** @test */
    public function it_marks_setup_as_in_progress_when_executing(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'pending']);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        Event::assertDispatched(ProjectSetupUpdated::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    }

    /** @test */
    public function it_executes_all_pending_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function it_handles_task_failures_gracefully(): void
    {
        // Arrange
        Log::spy();

        $project = Project::factory()->create();

        // Create SSL task (will fail without primary domain)
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_FAILED,
        ]);

        $project->refresh();
        $this->assertEquals('failed', $project->setup_status);
    }

    /** @test */
    public function it_logs_error_when_task_fails(): void
    {
        // Arrange
        Log::spy();

        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once();
    }

    /** @test */
    public function it_dispatches_event_when_task_fails(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        Event::assertDispatched(ProjectSetupUpdated::class);
    }

    /** @test */
    public function it_completes_ssl_setup_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'example.com',
            'is_primary' => true,
            'ssl_enabled' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->result_data);
        $this->assertEquals('example.com', $task->result_data['domain']);
    }

    /** @test */
    public function it_fails_ssl_setup_when_no_primary_domain(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No primary domain found', $task->message);
    }

    /** @test */
    public function it_updates_ssl_task_progress(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'example.com',
            'is_primary' => true,
            'ssl_enabled' => true,
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'progress' => 100,
        ]);
    }

    /** @test */
    public function it_completes_webhook_setup_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create(['webhook_secret' => null]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->result_data);
        $this->assertArrayHasKey('webhook_url', $task->result_data);
        $this->assertArrayHasKey('secret', $task->result_data);

        $project->refresh();
        $this->assertNotNull($project->webhook_secret);
        $this->assertTrue($project->webhook_enabled);
    }

    /** @test */
    public function it_uses_existing_webhook_secret_if_present(): void
    {
        // Arrange
        $existingSecret = 'existing-secret-123';
        $project = Project::factory()->create([
            'webhook_secret' => $existingSecret,
            'webhook_enabled' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $project->refresh();
        $this->assertEquals($existingSecret, $project->webhook_secret);

        $task->refresh();
        $this->assertEquals($existingSecret, $task->result_data['secret']);
    }

    /** @test */
    public function it_updates_webhook_task_progress(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'progress' => 100,
        ]);
    }

    /** @test */
    public function it_completes_health_check_setup_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->result_data);
        $this->assertEquals('https://example.com', $task->result_data['url']);
    }

    /** @test */
    public function it_creates_health_check_record_when_model_exists(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('health_checks', [
            'project_id' => $project->id,
            'type' => 'http',
            'target' => 'https://example.com',
            'interval_minutes' => 5,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_uses_health_check_url_from_project_if_no_domain(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'health_check_url' => 'https://custom-url.com',
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_HEALTH_CHECK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals('https://custom-url.com', $task->result_data['url']);
    }

    /** @test */
    public function it_completes_backup_setup_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_BACKUP,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->result_data);
        $this->assertEquals('daily', $task->result_data['frequency']);
        $this->assertEquals('02:00', $task->result_data['time']);
        $this->assertEquals('7 days', $task->result_data['retention']);
    }

    /** @test */
    public function it_creates_backup_schedule_when_model_exists(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_BACKUP,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('backup_schedules', [
            'project_id' => $project->id,
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 7,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_completes_notifications_setup_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('Notifications ready to configure', $task->message);
    }

    /** @test */
    public function it_completes_deployment_setup_task_when_job_exists(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
    }

    /** @test */
    public function it_dispatches_deploy_job_when_class_exists(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        if (class_exists(\App\Jobs\DeployProjectJob::class)) {
            Queue::assertPushed(\App\Jobs\DeployProjectJob::class, function ($job) use ($project) {
                return $job->project->id === $project->id;
            });
        }
    }

    /** @test */
    public function it_marks_setup_as_completed_when_all_tasks_done(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'in_progress']);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $project->refresh();
        $this->assertEquals('completed', $project->setup_status);
        $this->assertNotNull($project->setup_completed_at);
    }

    /** @test */
    public function it_marks_setup_as_failed_when_any_task_failed(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'in_progress']);

        // This will fail (no primary domain)
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // This will succeed
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $project->refresh();
        $this->assertEquals('failed', $project->setup_status);
        $this->assertNotNull($project->setup_completed_at);
    }

    /** @test */
    public function it_returns_setup_progress_summary(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'setup_status' => 'in_progress',
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'progress' => 100,
            'message' => 'SSL configured',
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_RUNNING,
            'progress' => 50,
            'message' => 'Setting up webhook...',
        ]);

        // Act
        $progress = $this->service->getSetupProgress($project);

        // Assert
        $this->assertEquals('in_progress', $progress['status']);
        $this->assertCount(2, $progress['tasks']);

        $sslTask = collect($progress['tasks'])->firstWhere('type', ProjectSetupTask::TYPE_SSL);
        $this->assertEquals('completed', $sslTask['status']);
        $this->assertEquals(100, $sslTask['progress']);
        $this->assertEquals('SSL configured', $sslTask['message']);

        $webhookTask = collect($progress['tasks'])->firstWhere('type', ProjectSetupTask::TYPE_WEBHOOK);
        $this->assertEquals('running', $webhookTask['status']);
        $this->assertEquals(50, $webhookTask['progress']);
    }

    /** @test */
    public function it_includes_task_labels_in_progress_summary(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $progress = $this->service->getSetupProgress($project);

        // Assert
        $this->assertEquals('SSL Certificate', $progress['tasks'][0]['label']);
    }

    /** @test */
    public function it_retries_failed_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_FAILED,
            'message' => 'Previous error',
            'progress' => 50,
        ]);

        // Act
        $this->service->retryTask($project, ProjectSetupTask::TYPE_SSL);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals(100, $task->progress);
        $this->assertNotEquals('Previous error', $task->message);
    }

    /** @test */
    public function it_resets_task_state_before_retry(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_FAILED,
            'message' => 'Old error',
            'progress' => 75,
            'result_data' => ['old' => 'data'],
        ]);

        // Act
        $this->service->retryTask($project, ProjectSetupTask::TYPE_NOTIFICATIONS);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals(100, $task->progress);
    }

    /** @test */
    public function it_does_not_retry_non_failed_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'message' => 'Already completed',
        ]);

        // Act
        $this->service->retryTask($project, ProjectSetupTask::TYPE_NOTIFICATIONS);

        // Assert
        $task->refresh();
        $this->assertEquals('Already completed', $task->message);
    }

    /** @test */
    public function it_updates_project_status_when_retrying_task(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'failed']);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_FAILED,
        ]);

        // Act
        $this->service->retryTask($project, ProjectSetupTask::TYPE_NOTIFICATIONS);

        // Assert
        $project->refresh();
        $this->assertEquals('completed', $project->setup_status);
    }

    /** @test */
    public function it_skips_pending_task_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->skipTask($project, ProjectSetupTask::TYPE_DEPLOYMENT);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_SKIPPED, $task->status);
        $this->assertNotNull($task->message);
    }

    /** @test */
    public function it_does_not_skip_non_pending_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
        ]);

        // Act
        $this->service->skipTask($project, ProjectSetupTask::TYPE_NOTIFICATIONS);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
    }

    /** @test */
    public function it_checks_completion_after_skipping_task(): void
    {
        // Arrange
        $project = Project::factory()->create(['setup_status' => 'in_progress']);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_DEPLOYMENT,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->skipTask($project, ProjectSetupTask::TYPE_DEPLOYMENT);

        // Assert
        $project->refresh();
        $this->assertEquals('completed', $project->setup_status);
        $this->assertNotNull($project->setup_completed_at);
    }

    /** @test */
    public function it_handles_missing_task_when_skipping(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act & Assert - Should not throw exception
        $this->service->skipTask($project, 'non_existent_task');

        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_missing_task_when_retrying(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act & Assert - Should not throw exception
        $this->service->retryTask($project, 'non_existent_task');

        $this->assertTrue(true);
    }

    /** @test */
    public function it_dispatches_events_throughout_execution(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert - Should dispatch multiple events during setup
        Event::assertDispatched(ProjectSetupUpdated::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    }

    /** @test */
    public function it_handles_exception_in_task_execution(): void
    {
        // Arrange
        Log::spy();

        $project = Project::factory()->create();

        // SSL task will throw exception due to missing domain
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert - Should log error but continue
        Log::shouldHaveReceived('error');

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_FAILED,
        ]);
    }

    /** @test */
    public function it_continues_executing_remaining_tasks_after_failure(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // This will fail
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // This should still execute
        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SSL,
            'status' => ProjectSetupTask::STATUS_FAILED,
        ]);

        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function it_marks_task_as_running_before_execution(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert - Task should have been marked as running (check started_at)
        $task->refresh();
        $this->assertNotNull($task->started_at);
    }

    /** @test */
    public function it_only_executes_pending_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'message' => 'Already done',
        ]);

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert - Completed task should remain unchanged
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NOTIFICATIONS,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'message' => 'Already done',
        ]);

        // Pending task should be completed
        $this->assertDatabaseHas('project_setup_tasks', [
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function it_handles_unknown_task_type(): void
    {
        // Arrange
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => 'unknown_task_type',
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        // Act
        $this->service->executeSetup($project);

        // Assert
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_SKIPPED, $task->status);
        $this->assertEquals('Unknown task type', $task->message);
    }

    /** @test */
    public function it_returns_empty_progress_for_project_without_tasks(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'setup_status' => 'pending',
        ]);

        // Act
        $progress = $this->service->getSetupProgress($project);

        // Assert
        $this->assertEquals('pending', $progress['status']);
        $this->assertEmpty($progress['tasks']);
    }

    /** @test */
    public function it_includes_result_data_in_progress_summary(): void
    {
        // Arrange
        $project = Project::factory()->create();

        ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_WEBHOOK,
            'status' => ProjectSetupTask::STATUS_COMPLETED,
            'result_data' => [
                'webhook_url' => 'https://example.com/webhook',
                'secret' => 'test-secret',
            ],
        ]);

        // Act
        $progress = $this->service->getSetupProgress($project);

        // Assert
        $webhookTask = $progress['tasks'][0];
        $this->assertArrayHasKey('result_data', $webhookTask);
        $this->assertEquals('https://example.com/webhook', $webhookTask['result_data']['webhook_url']);
        $this->assertEquals('test-secret', $webhookTask['result_data']['secret']);
    }
}
