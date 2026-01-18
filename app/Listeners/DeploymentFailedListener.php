<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DeploymentFailed;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeploymentFailedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected AuditService $auditService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(DeploymentFailed $event): void
    {
        $deployment = $event->deployment;
        $error = $event->error;

        try {
            // Log deployment failure in audit log with high priority
            $this->auditService->log(
                action: 'deployment.failed',
                model: $deployment,
                oldValues: [
                    'status' => 'running',
                ],
                newValues: [
                    'status' => $deployment->status,
                    'project_id' => $deployment->project_id,
                    'project_name' => $deployment->project?->name,
                    'branch' => $deployment->branch,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => $deployment->commit_message,
                    'triggered_by' => $deployment->triggered_by,
                    'error_message' => $deployment->error_message ?? $error,
                    'started_at' => $deployment->started_at?->toIso8601String(),
                    'completed_at' => $deployment->completed_at?->toIso8601String(),
                    'duration_seconds' => $deployment->duration_seconds,
                ]
            );

            // Send critical failure alert notification
            $this->notificationService->notifyDeploymentEvent(
                deployment: $deployment,
                event: 'deployment.failed'
            );

            // Log critical error for monitoring
            Log::critical('Deployment failed', [
                'deployment_id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'project_name' => $deployment->project?->name,
                'branch' => $deployment->branch,
                'error' => $error,
                'error_message' => $deployment->error_message,
                'commit_hash' => $deployment->commit_hash,
                'duration_seconds' => $deployment->duration_seconds,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle DeploymentFailed event', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DeploymentFailed $event, \Throwable $exception): void
    {
        Log::error('DeploymentFailedListener failed', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
