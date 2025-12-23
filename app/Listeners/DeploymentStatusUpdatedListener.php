<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DeploymentStatusUpdated;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handles DeploymentStatusUpdated events for server-side actions.
 *
 * This listener logs deployment status changes to the audit log for
 * compliance and tracking purposes. Unlike DeploymentStarted/Completed/Failed,
 * this listener only performs audit logging without notifications, as it
 * represents intermediate status updates during an active deployment.
 */
class DeploymentStatusUpdatedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(DeploymentStatusUpdated $event): void
    {
        $deployment = $event->deployment;
        $message = $event->message;
        $type = $event->type;

        try {
            // Log status update in audit log for tracking
            $this->auditService->log(
                action: 'deployment.status_updated',
                model: $deployment,
                oldValues: null,
                newValues: [
                    'deployment_id' => $deployment->id,
                    'project_id' => $deployment->project_id,
                    'project_name' => $deployment->project?->name,
                    'status' => $deployment->status,
                    'message' => $message,
                    'type' => $type,
                    'progress' => $deployment->progress ?? 0,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            // Log based on message type
            $logLevel = match ($type) {
                'error' => 'error',
                'warning' => 'warning',
                'success' => 'info',
                default => 'debug',
            };

            Log::log($logLevel, 'Deployment status updated', [
                'deployment_id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'status' => $deployment->status,
                'message' => $message,
                'type' => $type,
                'progress' => $deployment->progress ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle DeploymentStatusUpdated event', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DeploymentStatusUpdated $event, \Throwable $exception): void
    {
        Log::error('DeploymentStatusUpdatedListener failed', [
            'deployment_id' => $event->deployment->id,
            'message' => $event->message,
            'error' => $exception->getMessage(),
        ]);
    }
}
