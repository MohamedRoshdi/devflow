<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Events\ProjectSetupUpdated;
use App\Models\Project;
use App\Models\ProjectSetupTask;
use App\Services\CronConfigService;
use App\Services\NginxConfigService;
use App\Services\ProjectSetupService;
use App\Services\SupervisorConfigService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_handles_missing_task_when_skipping(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act & Assert - Should not throw exception
        $this->service->skipTask($project, 'non_existent_task');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_missing_task_when_retrying(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act & Assert - Should not throw exception
        $this->service->retryTask($project, 'non_existent_task');

        $this->assertTrue(true);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    // -----------------------------------------------------------------------
    // Bare-metal infrastructure setup tasks
    // -----------------------------------------------------------------------

    #[Test]
    public function it_adds_bare_metal_tasks_for_standard_deployment_method(): void
    {
        $project = Project::factory()->create(['deployment_method' => 'standard']);

        $this->service->initializeSetup($project, ['deployment' => true]);

        foreach ([
            ProjectSetupTask::TYPE_NGINX,
            ProjectSetupTask::TYPE_SUPERVISOR,
            ProjectSetupTask::TYPE_CRON,
            ProjectSetupTask::TYPE_SHARED_DIRS,
            ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
        ] as $type) {
            $this->assertDatabaseHas('project_setup_tasks', [
                'project_id' => $project->id,
                'task_type' => $type,
                'status' => ProjectSetupTask::STATUS_PENDING,
            ]);
        }
    }

    #[Test]
    public function it_does_not_add_bare_metal_tasks_for_non_standard_deployment(): void
    {
        $project = Project::factory()->create(['deployment_method' => 'docker']);

        $this->service->initializeSetup($project, ['deployment' => true]);

        foreach ([
            ProjectSetupTask::TYPE_NGINX,
            ProjectSetupTask::TYPE_SUPERVISOR,
            ProjectSetupTask::TYPE_CRON,
            ProjectSetupTask::TYPE_SHARED_DIRS,
            ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
        ] as $type) {
            $this->assertDatabaseMissing('project_setup_tasks', [
                'project_id' => $project->id,
                'task_type' => $type,
            ]);
        }
    }

    #[Test]
    public function it_can_opt_out_of_bare_metal_tasks_via_config(): void
    {
        $project = Project::factory()->create(['deployment_method' => 'standard']);

        $this->service->initializeSetup($project, [
            'nginx' => false,
            'supervisor' => false,
            'cron' => false,
            'shared_dirs' => false,
            'post_deploy_health' => false,
        ]);

        foreach ([
            ProjectSetupTask::TYPE_NGINX,
            ProjectSetupTask::TYPE_SUPERVISOR,
            ProjectSetupTask::TYPE_CRON,
            ProjectSetupTask::TYPE_SHARED_DIRS,
            ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
        ] as $type) {
            $this->assertDatabaseMissing('project_setup_tasks', [
                'project_id' => $project->id,
                'task_type' => $type,
            ]);
        }
    }

    #[Test]
    public function it_fails_nginx_setup_when_no_server_attached(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NGINX,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No server attached to project', $task->message);
    }

    #[Test]
    public function it_fails_nginx_setup_when_no_primary_domain(): void
    {
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NGINX,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No primary domain found', $task->message);
    }

    #[Test]
    public function it_completes_nginx_setup_via_nginx_config_service(): void
    {
        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'myapp.com',
            'is_primary' => true,
        ]);

        $nginxService = Mockery::mock(NginxConfigService::class);
        $nginxService->shouldReceive('installVhost')->once()->andReturn(true);

        $service = new ProjectSetupService(nginxConfigService: $nginxService);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_NGINX,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertArrayHasKey('domain', $task->result_data);
        $this->assertEquals('myapp.com', $task->result_data['domain']);
    }

    #[Test]
    public function it_fails_supervisor_setup_when_no_server_attached(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SUPERVISOR,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No server attached to project', $task->message);
    }

    #[Test]
    public function it_completes_supervisor_setup_via_supervisor_config_service(): void
    {
        $project = Project::factory()->create();

        $supervisorService = Mockery::mock(SupervisorConfigService::class);
        $supervisorService->shouldReceive('installConfig')->once()->andReturn(true);

        $service = new ProjectSetupService(supervisorConfigService: $supervisorService);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SUPERVISOR,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertArrayHasKey('config', $task->result_data);
    }

    #[Test]
    public function it_fails_cron_setup_when_no_server_attached(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_CRON,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No server attached to project', $task->message);
    }

    #[Test]
    public function it_completes_cron_setup_via_cron_config_service(): void
    {
        $project = Project::factory()->create();

        $cronService = Mockery::mock(CronConfigService::class);
        $cronService->shouldReceive('installConfig')->once()->andReturn(true);

        $service = new ProjectSetupService(cronConfigService: $cronService);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_CRON,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertArrayHasKey('config', $task->result_data);
    }

    #[Test]
    public function it_fails_shared_dirs_setup_when_no_server_attached(): void
    {
        $project = Project::factory()->create(['server_id' => null]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SHARED_DIRS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_FAILED, $task->status);
        $this->assertEquals('No server attached to project', $task->message);
    }

    #[Test]
    public function it_completes_shared_dirs_setup_via_ssh_commands(): void
    {
        Process::fake([
            '*mkdir*' => Process::result('', '', 0),
            '*chown*' => Process::result('', '', 0),
        ]);

        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_SHARED_DIRS,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertArrayHasKey('path', $task->result_data);
    }

    #[Test]
    public function it_skips_post_deploy_health_when_no_primary_domain(): void
    {
        $project = Project::factory()->create();

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_SKIPPED, $task->status);
    }

    #[Test]
    public function it_completes_post_deploy_health_when_site_returns_2xx(): void
    {
        Http::fake(['http://myapp.com/' => Http::response('OK', 200)]);

        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'myapp.com',
            'is_primary' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals(200, $task->result_data['http_status']);
    }

    #[Test]
    public function it_completes_post_deploy_health_with_warning_on_non_2xx_response(): void
    {
        Http::fake(['http://myapp.com/' => Http::response('Not Found', 404)]);

        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'myapp.com',
            'is_primary' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        // Marks completed (not failed) — no auto-rollback
        $task->refresh();
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals(404, $task->result_data['http_status']);
        $this->assertArrayHasKey('note', $task->result_data);
    }

    #[Test]
    public function it_completes_post_deploy_health_with_note_on_connection_failure(): void
    {
        Http::fake(['http://myapp.com/' => fn () => throw new \Exception('Connection refused')]);

        $project = Project::factory()->create();

        $project->domains()->create([
            'domain' => 'myapp.com',
            'is_primary' => true,
        ]);

        $task = ProjectSetupTask::factory()->create([
            'project_id' => $project->id,
            'task_type' => ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH,
            'status' => ProjectSetupTask::STATUS_PENDING,
        ]);

        $this->service->executeSetup($project);

        $task->refresh();
        // Still marked completed — log warning, no auto-rollback
        $this->assertEquals(ProjectSetupTask::STATUS_COMPLETED, $task->status);
        $this->assertArrayHasKey('error', $task->result_data);
        $this->assertArrayHasKey('note', $task->result_data);
    }

    #[Test]
    public function it_returns_correct_labels_for_new_task_types(): void
    {
        $this->assertEquals('Nginx Vhost', ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_NGINX));
        $this->assertEquals('Supervisor Queue Worker', ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_SUPERVISOR));
        $this->assertEquals('Cron Scheduler', ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_CRON));
        $this->assertEquals('Shared Directory Structure', ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_SHARED_DIRS));
        $this->assertEquals('Post-Deploy Health Check', ProjectSetupTask::getTypeLabel(ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH));
    }
}
