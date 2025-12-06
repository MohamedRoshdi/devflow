<?php

namespace App\Policies;

use App\Models\Deployment;
use App\Models\User;

/**
 * Deployment Policy
 *
 * All deployments are shared across all authenticated users.
 * Any authenticated user can view any deployment.
 */
class DeploymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deployment $deployment): bool
    {
        // All authenticated users can view any deployment
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, Deployment $deployment): bool
    {
        // All authenticated users can cancel any deployment
        return true;
    }

    public function rollback(User $user, Deployment $deployment): bool
    {
        // All authenticated users can rollback any deployment
        return true;
    }
}
