<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;

class DeploymentObserver
{
    /**
     * Handle the Deployment "created" event.
     */
    public function created(Deployment $deployment): void
    {
        // Only dispatch job for pending deployments
        // Skip if status is not 'pending' to allow manual creation without auto-deploying
        if ($deployment->status === 'pending') {
            DeployProjectJob::dispatch($deployment);
        }
    }
}
