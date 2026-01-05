<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\DeploymentStatusUpdated;
use App\Models\Deployment;
use Illuminate\Support\Facades\Log;

class DeploymentObserver
{
    /**
     * Handle the Deployment "created" event.
     *
     * Note: We do NOT auto-dispatch DeployProjectJob here to avoid double dispatching.
     * The job should be dispatched explicitly where the deployment is created.
     * This gives controllers/services full control over when and how the job is dispatched
     * (e.g., with delays, conditions, or custom logic).
     */
    public function created(Deployment $deployment): void
    {
        Log::info("Deployment #{$deployment->id} created for project #{$deployment->project_id}", [
            'status' => $deployment->status,
            'triggered_by' => $deployment->triggered_by,
        ]);
    }

    /**
     * Handle the Deployment "updated" event.
     */
    public function updated(Deployment $deployment): void
    {
        // Dispatch status update event when status changes
        if ($deployment->isDirty('status')) {
            $message = "Deployment status changed to {$deployment->status}";
            $type = match ($deployment->status) {
                'success' => 'success',
                'failed' => 'error',
                'running' => 'info',
                default => 'info',
            };
            event(new DeploymentStatusUpdated($deployment, $message, $type));
        }
    }
}
