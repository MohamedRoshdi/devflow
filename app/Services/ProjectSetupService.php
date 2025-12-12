<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ProjectSetupUpdated;
use App\Models\BackupSchedule;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\ProjectSetupTask;
use Illuminate\Support\Facades\Log;

class ProjectSetupService
{
    public function __construct(
        protected ?SSLService $sslService = null,
        protected ?HealthCheckService $healthCheckService = null,
        protected ?NotificationService $notificationService = null,
        protected ?DatabaseBackupService $backupService = null
    ) {}

    /**
     * Initialize project setup with configured tasks
     */
    public function initializeSetup(Project $project, array $config): void
    {
        // Store setup config
        $project->update([
            'setup_status' => 'pending',
            'setup_config' => $config,
        ]);

        // Create setup tasks based on config
        $tasks = [];

        if ($config['ssl'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_SSL;
        }

        if ($config['webhook'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_WEBHOOK;
        }

        if ($config['health_check'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_HEALTH_CHECK;
        }

        if ($config['backup'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_BACKUP;
        }

        if ($config['notifications'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_NOTIFICATIONS;
        }

        if ($config['deployment'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_DEPLOYMENT;
        }

        // Create task records
        foreach ($tasks as $taskType) {
            ProjectSetupTask::create([
                'project_id' => $project->id,
                'task_type' => $taskType,
                'status' => ProjectSetupTask::STATUS_PENDING,
                'progress' => 0,
            ]);
        }

        // Dispatch event
        event(new ProjectSetupUpdated($project));
    }

    /**
     * Execute all pending setup tasks
     */
    public function executeSetup(Project $project): void
    {
        $project->update(['setup_status' => 'in_progress']);
        event(new ProjectSetupUpdated($project));

        $tasks = $project->setupTasks()->where('status', ProjectSetupTask::STATUS_PENDING)->get();

        foreach ($tasks as $task) {
            try {
                $this->executeTask($project, $task);
            } catch (\Exception $e) {
                Log::error('Project setup task failed', [
                    'project_id' => $project->id,
                    'task_type' => $task->task_type,
                    'error' => $e->getMessage(),
                ]);

                $task->markAsFailed($e->getMessage());
                event(new ProjectSetupUpdated($project));
            }
        }

        // Check if all tasks are done
        $this->checkSetupCompletion($project);
    }

    /**
     * Execute a single setup task
     */
    protected function executeTask(Project $project, ProjectSetupTask $task): void
    {
        $task->markAsRunning();
        event(new ProjectSetupUpdated($project));

        match ($task->task_type) {
            ProjectSetupTask::TYPE_SSL => $this->setupSSL($project, $task),
            ProjectSetupTask::TYPE_WEBHOOK => $this->setupWebhook($project, $task),
            ProjectSetupTask::TYPE_HEALTH_CHECK => $this->setupHealthChecks($project, $task),
            ProjectSetupTask::TYPE_BACKUP => $this->setupBackup($project, $task),
            ProjectSetupTask::TYPE_NOTIFICATIONS => $this->setupNotifications($project, $task),
            ProjectSetupTask::TYPE_DEPLOYMENT => $this->setupDeployment($project, $task),
            default => $task->markAsSkipped('Unknown task type'),
        };

        event(new ProjectSetupUpdated($project));
    }

    /**
     * Setup SSL certificate for the project
     */
    protected function setupSSL(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(10, 'Getting primary domain...');

        $domain = $project->domains()->where('is_primary', true)->first();

        if (! $domain) {
            $task->markAsFailed('No primary domain found');

            return;
        }

        $task->updateProgress(30, 'Requesting SSL certificate...');

        // Mark as completed - SSL will be issued via SSLService or manually
        $task->markAsCompleted('SSL certificate setup initiated', [
            'domain' => $domain->domain,
            'note' => 'SSL certificate will be issued automatically or can be configured manually.',
        ]);
    }

    /**
     * Setup webhook for the project
     */
    protected function setupWebhook(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Generating webhook secret...');

        // Generate webhook secret if not exists
        if (! $project->webhook_secret) {
            $project->update([
                'webhook_secret' => $project->generateWebhookSecret(),
                'webhook_enabled' => true,
            ]);
        }

        $task->updateProgress(60, 'Building webhook URL...');

        $webhookUrl = route('webhooks.github', ['secret' => $project->webhook_secret]);

        $task->markAsCompleted('Webhook configured successfully', [
            'webhook_url' => $webhookUrl,
            'secret' => $project->webhook_secret,
        ]);
    }

    /**
     * Setup health checks for the project
     */
    protected function setupHealthChecks(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Creating HTTP health check...');

        // Get primary domain for health check
        $domain = $project->domains()->where('is_primary', true)->first();
        $healthCheckUrl = $domain ? "https://{$domain->domain}" : $project->health_check_url;

        if ($healthCheckUrl) {
            // Create HTTP health check if model exists
            if (class_exists(HealthCheck::class)) {
                HealthCheck::create([
                    'project_id' => $project->id,
                    'name' => "{$project->name} HTTP Check",
                    'type' => 'http',
                    'target' => $healthCheckUrl,
                    'interval_minutes' => 5,
                    'is_active' => true,
                ]);
            }

            $task->updateProgress(80, 'Health check created...');
        }

        $task->markAsCompleted('Health checks configured', [
            'url' => $healthCheckUrl,
        ]);
    }

    /**
     * Setup database backup schedule
     */
    protected function setupBackup(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(30, 'Creating backup schedule...');

        // Create daily backup schedule if model exists
        if (class_exists(BackupSchedule::class)) {
            BackupSchedule::create([
                'project_id' => $project->id,
                'name' => "{$project->name} Daily Backup",
                'frequency' => 'daily',
                'time' => '02:00',
                'retention_days' => 7,
                'is_active' => true,
            ]);
        }

        $task->markAsCompleted('Backup schedule created', [
            'frequency' => 'daily',
            'time' => '02:00',
            'retention' => '7 days',
        ]);
    }

    /**
     * Setup notifications for the project
     */
    protected function setupNotifications(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(50, 'Configuring notifications...');

        // Notifications are typically user-configured, just mark as ready
        $task->markAsCompleted('Notifications ready to configure', [
            'note' => 'Configure notification channels in settings to receive alerts.',
        ]);
    }

    /**
     * Trigger initial deployment
     */
    protected function setupDeployment(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Preparing initial deployment...');

        // Check if DeployProjectJob exists
        if (class_exists(\App\Jobs\DeployProjectJob::class)) {
            $task->updateProgress(50, 'Queueing deployment job...');
            \App\Jobs\DeployProjectJob::dispatch($project)->afterCommit();
            $task->markAsCompleted('Initial deployment queued', [
                'queued_at' => now()->toIso8601String(),
            ]);
        } else {
            $task->markAsCompleted('Deployment ready', [
                'note' => 'Deploy manually from the project page.',
            ]);
        }
    }

    /**
     * Check if all setup tasks are complete
     */
    protected function checkSetupCompletion(Project $project): void
    {
        $project->refresh();
        $tasks = $project->setupTasks;

        $allDone = $tasks->every(fn ($task) => $task->isDone());
        $anyFailed = $tasks->contains(fn ($task) => $task->isFailed());

        if ($allDone) {
            $project->update([
                'setup_status' => $anyFailed ? 'failed' : 'completed',
                'setup_completed_at' => now(),
            ]);
        }

        event(new ProjectSetupUpdated($project));
    }

    /**
     * Get setup progress summary
     */
    public function getSetupProgress(Project $project): array
    {
        $tasks = $project->setupTasks;

        return [
            'status' => $project->setup_status,
            'progress' => $project->setup_progress,
            'completed_at' => $project->setup_completed_at,
            'tasks' => $tasks->map(fn ($task) => [
                'type' => $task->task_type,
                'label' => ProjectSetupTask::getTypeLabel($task->task_type),
                'status' => $task->status,
                'progress' => $task->progress,
                'message' => $task->message,
                'result_data' => $task->result_data,
            ])->toArray(),
        ];
    }

    /**
     * Retry a failed task
     */
    public function retryTask(Project $project, string $taskType): void
    {
        $task = $project->setupTasks()->where('task_type', $taskType)->first();

        if ($task && $task->isFailed()) {
            $task->update([
                'status' => ProjectSetupTask::STATUS_PENDING,
                'progress' => 0,
                'message' => null,
                'result_data' => null,
                'started_at' => null,
                'completed_at' => null,
            ]);

            $project->update(['setup_status' => 'in_progress']);

            $this->executeTask($project, $task);
            $this->checkSetupCompletion($project);
        }
    }

    /**
     * Skip a pending task
     */
    public function skipTask(Project $project, string $taskType): void
    {
        $task = $project->setupTasks()->where('task_type', $taskType)->first();

        if ($task && $task->isPending()) {
            $task->markAsSkipped();
            $this->checkSetupCompletion($project);
        }
    }
}
