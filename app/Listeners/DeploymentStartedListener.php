<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DeploymentStarted;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeploymentStartedListener implements ShouldQueue
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
    public function handle(DeploymentStarted $event): void
    {
        $deployment = $event->deployment;

        try {
            // Log deployment start in audit log
            $this->auditService->log(
                action: 'deployment.started',
                model: $deployment,
                oldValues: null,
                newValues: [
                    'project_id' => $deployment->project_id,
                    'project_name' => $deployment->project?->name,
                    'branch' => $deployment->branch,
                    'commit_hash' => $deployment->commit_hash,
                    'triggered_by' => $deployment->triggered_by,
                    'started_at' => $deployment->started_at?->toIso8601String(),
                ]
            );

            // Send deployment notification
            $this->notificationService->notifyDeploymentEvent(
                deployment: $deployment,
                event: 'deployment.started'
            );

            Log::info('Deployment started notification sent', [
                'deployment_id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'branch' => $deployment->branch,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle DeploymentStarted event', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DeploymentStarted $event, \Throwable $exception): void
    {
        Log::error('DeploymentStartedListener failed', [
            'deployment_id' => $event->deployment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
