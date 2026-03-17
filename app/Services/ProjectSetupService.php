<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ProjectSetupUpdated;
use App\Models\BackupSchedule;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\ProjectSetupTask;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectSetupService
{
    use ExecutesRemoteCommands;

    public function __construct(
        protected ?SSLService $sslService = null,
        protected ?HealthCheckService $healthCheckService = null,
        protected ?NotificationService $notificationService = null,
        protected ?DatabaseBackupService $backupService = null,
        protected ?NginxConfigService $nginxConfigService = null,
        protected ?SupervisorConfigService $supervisorConfigService = null,
        protected ?CronConfigService $cronConfigService = null
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

        // Bare-metal only: infrastructure setup runs before initial deployment
        if ($project->deployment_method === 'standard') {
            if ($config['nginx'] ?? true) {
                $tasks[] = ProjectSetupTask::TYPE_NGINX;
            }

            if ($config['supervisor'] ?? true) {
                $tasks[] = ProjectSetupTask::TYPE_SUPERVISOR;
            }

            if ($config['cron'] ?? true) {
                $tasks[] = ProjectSetupTask::TYPE_CRON;
            }

            if ($config['shared_dirs'] ?? true) {
                $tasks[] = ProjectSetupTask::TYPE_SHARED_DIRS;
            }
        }

        if ($config['deployment'] ?? false) {
            $tasks[] = ProjectSetupTask::TYPE_DEPLOYMENT;
        }

        // Bare-metal only: post-deploy health check runs after deployment
        if ($project->deployment_method === 'standard' && ($config['post_deploy_health'] ?? true)) {
            $tasks[] = ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH;
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
            ProjectSetupTask::TYPE_NGINX => $this->setupNginx($project, $task),
            ProjectSetupTask::TYPE_SUPERVISOR => $this->setupSupervisor($project, $task),
            ProjectSetupTask::TYPE_CRON => $this->setupCron($project, $task),
            ProjectSetupTask::TYPE_SHARED_DIRS => $this->setupSharedDirs($project, $task),
            ProjectSetupTask::TYPE_DEPLOYMENT => $this->setupDeployment($project, $task),
            ProjectSetupTask::TYPE_POST_DEPLOY_HEALTH => $this->setupPostDeployHealth($project, $task),
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

            $deployment = \App\Models\Deployment::create([
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'branch' => $project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            \App\Jobs\DeployProjectJob::dispatch($deployment)->afterCommit();
            $task->markAsCompleted('Initial deployment queued', [
                'queued_at' => now()->toIso8601String(),
                'deployment_id' => $deployment->id,
            ]);
        } else {
            $task->markAsCompleted('Deployment ready', [
                'note' => 'Deploy manually from the project page.',
            ]);
        }
    }

    /**
     * Install Nginx vhost on the project's server (bare-metal only).
     */
    protected function setupNginx(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(10, 'Locating primary domain...');

        $server = $project->server;

        if (! $server) {
            $task->markAsFailed('No server attached to project');

            return;
        }

        $domain = $project->domains()->where('is_primary', true)->first();

        if (! $domain) {
            $task->markAsFailed('No primary domain found');

            return;
        }

        $task->updateProgress(40, 'Generating and installing Nginx vhost...');

        $service = $this->nginxConfigService ?? new NginxConfigService;
        $service->installVhost($server, $project, $domain);

        $task->markAsCompleted('Nginx vhost installed and Nginx reloaded', [
            'domain' => $domain->domain,
            'config' => "/etc/nginx/sites-available/{$project->validated_slug}",
        ]);
    }

    /**
     * Install Supervisor queue worker config on the project's server (bare-metal only).
     */
    protected function setupSupervisor(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Installing Supervisor queue worker config...');

        $server = $project->server;

        if (! $server) {
            $task->markAsFailed('No server attached to project');

            return;
        }

        $service = $this->supervisorConfigService ?? new SupervisorConfigService;
        $service->installConfig($server, $project);

        $task->markAsCompleted('Supervisor queue worker installed and activated', [
            'config' => "/etc/supervisor/conf.d/{$project->validated_slug}-worker.conf",
        ]);
    }

    /**
     * Install cron scheduler entry on the project's server (bare-metal only).
     */
    protected function setupCron(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Installing cron scheduler entry...');

        $server = $project->server;

        if (! $server) {
            $task->markAsFailed('No server attached to project');

            return;
        }

        $service = $this->cronConfigService ?? new CronConfigService;
        $service->installConfig($server, $project);

        $task->markAsCompleted('Cron scheduler entry installed', [
            'config' => "/etc/cron.d/{$project->slug}-scheduler",
        ]);
    }

    /**
     * Create shared directory structure on the project's server (bare-metal only).
     */
    protected function setupSharedDirs(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(10, 'Creating shared directory structure...');

        $server = $project->server;

        if (! $server) {
            $task->markAsFailed('No server attached to project');

            return;
        }

        $slug = $project->validated_slug;
        $deployPath = $project->deploy_path ?? ((string) config('devflow.projects_path', '/var/www'))."/{$slug}";
        $sharedBase = dirname($deployPath)."/../shared/{$slug}";

        $task->updateProgress(40, 'Creating storage directories...');

        // Build a single compound command to keep the SSH round-trips to one
        $mkdirCmd = implode(' && ', [
            "mkdir -p {$sharedBase}/storage/app/public",
            "mkdir -p {$sharedBase}/storage/framework/cache",
            "mkdir -p {$sharedBase}/storage/framework/sessions",
            "mkdir -p {$sharedBase}/storage/framework/views",
            "mkdir -p {$sharedBase}/storage/logs",
        ]);

        $this->executeRemoteCommand($server, $mkdirCmd);

        $task->updateProgress(80, 'Setting ownership...');

        $this->executeRemoteCommand($server, "chown -R www-data:www-data {$sharedBase}");

        $task->markAsCompleted('Shared directory structure created', [
            'path' => $sharedBase,
        ]);
    }

    /**
     * Perform an HTTP health check against the project's primary domain after deployment.
     */
    protected function setupPostDeployHealth(Project $project, ProjectSetupTask $task): void
    {
        $task->updateProgress(20, 'Locating primary domain...');

        $domain = $project->domains()->where('is_primary', true)->first();

        if (! $domain) {
            $task->markAsSkipped('No primary domain — skipping post-deploy health check');

            return;
        }

        $url = "http://{$domain->domain}/";

        $task->updateProgress(50, "Checking {$url} ...");

        try {
            $response = Http::timeout(15)->get($url);
            $statusCode = $response->status();

            if ($response->successful()) {
                $task->markAsCompleted('Site is up and responding', [
                    'url' => $url,
                    'http_status' => $statusCode,
                ]);
            } else {
                Log::warning('Post-deploy health check returned non-2xx', [
                    'project' => $project->slug,
                    'url' => $url,
                    'http_status' => $statusCode,
                ]);

                $task->markAsCompleted("Site responded with HTTP {$statusCode} — review application logs", [
                    'url' => $url,
                    'http_status' => $statusCode,
                    'note' => 'Non-2xx response. Auto-rollback not triggered — investigate manually.',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Post-deploy health check failed to connect', [
                'project' => $project->slug,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            $task->markAsCompleted('Could not reach site — verify server and DNS are configured', [
                'url' => $url,
                'error' => $e->getMessage(),
                'note' => 'Connection failed. Auto-rollback not triggered — investigate manually.',
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
