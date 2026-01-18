<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Deployment;
use App\Models\User;

/**
 * Deployment Policy
 *
 * Controls access to deployments based on project ownership.
 * Users can only access deployments for projects they own.
 */
class DeploymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deployment $deployment): bool
    {
        // Users can only view deployments for their own projects
        return $deployment->project && $deployment->project->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, Deployment $deployment): bool
    {
        // Users can only cancel deployments for their own projects
        return $deployment->project && $deployment->project->user_id === $user->id;
    }

    public function rollback(User $user, Deployment $deployment): bool
    {
        // Users can only rollback deployments for their own projects
        return $deployment->project && $deployment->project->user_id === $user->id;
    }
}
