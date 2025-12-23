<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DeploymentCompleted;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeploymentCompletedListener implements ShouldQueue
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
    public function handle(DeploymentCompleted $event): void
    {
        $deployment = $event->deployment;

        try {
            // Log deployment completion in audit log
            $this->auditService->log(
                action: 'deployment.completed',
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
                    'started_at' => $deployment->started_at?->toIso8601String(),
                    'completed_at' => $deployment->completed_at?->toIso8601String(),
                    'duration_seconds' => $deployment->duration_seconds,
                ]
            );

            // Send success notification
            $this->notificationService->notifyDeploymentEvent(
                deployment: $deployment,
                event: 'deployment.completed'
            );

            Log::info('Deployment completed successfully', [
                'deployment_id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'branch' => $deployment->branch,
                'duration_seconds' => $deployment->duration_seconds,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle DeploymentCompleted event', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DeploymentCompleted $event, \Throwable $exception): void
    {
        Log::error('DeploymentCompletedListener failed', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
