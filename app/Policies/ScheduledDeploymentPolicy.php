<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduledDeployment;
use App\Models\User;

/**
 * ScheduledDeployment Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-deployments, create-deployments, approve-deployments
 */
class ScheduledDeploymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-deployments');
    }

    public function view(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        return $user->can('view-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    public function create(User $user): bool
    {
        return $user->can('create-deployments');
    }

    public function update(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        if (! $scheduledDeployment->isPending()) {
            return false;
        }

        return $user->can('create-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    public function delete(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        if (! $scheduledDeployment->isPending()) {
            return false;
        }

        return $user->can('create-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    public function cancel(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        if (! $scheduledDeployment->canCancel()) {
            return false;
        }

        return $user->can('create-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    public function execute(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        if (! $scheduledDeployment->isPending()) {
            return false;
        }

        return $user->can('approve-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    public function reschedule(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        if (! $scheduledDeployment->isPending()) {
            return false;
        }

        return $user->can('create-deployments') && $this->hasOwnershipAccess($user, $scheduledDeployment);
    }

    /**
     * Check if user has ownership access to the scheduled deployment
     */
    private function hasOwnershipAccess(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Users with approve permission have global access
        if ($user->can('approve-deployments')) {
            return true;
        }

        $project = $scheduledDeployment->project;
        if (! $project) {
            return false;
        }

        // Project owner
        if ($project->user_id === $user->id) {
            return true;
        }

        // Team members
        if ($project->team_id) {
            $team = $project->team;
            if ($team && $team->hasMember($user)) {
                return true;
            }
        }

        return false;
    }
}
