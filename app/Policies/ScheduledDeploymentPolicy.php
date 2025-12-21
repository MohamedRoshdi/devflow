<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ScheduledDeployment;
use App\Models\User;

/**
 * ScheduledDeployment Policy
 *
 * Scheduled deployments are owned through their parent project.
 * Users can manage scheduled deployments if they have access to the project.
 */
class ScheduledDeploymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by project ownership
    }

    public function view(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        return $this->hasAccess($user, $scheduledDeployment);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can schedule deployments for their projects
    }

    public function update(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Can only update pending scheduled deployments
        if (!$scheduledDeployment->isPending()) {
            return false;
        }

        return $this->hasAccess($user, $scheduledDeployment);
    }

    public function delete(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Can only delete pending scheduled deployments
        if (!$scheduledDeployment->isPending()) {
            return false;
        }

        return $this->hasAccess($user, $scheduledDeployment);
    }

    public function cancel(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Can only cancel pending scheduled deployments
        if (!$scheduledDeployment->canCancel()) {
            return false;
        }

        return $this->hasAccess($user, $scheduledDeployment);
    }

    public function execute(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Manual execution of scheduled deployment before scheduled time
        if (!$scheduledDeployment->isPending()) {
            return false;
        }

        // Only project owner or admin can manually execute
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $scheduledDeployment->project;
        if (!$project) {
            return false;
        }

        return $project->user_id === $user->id;
    }

    public function reschedule(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Can only reschedule pending scheduled deployments
        if (!$scheduledDeployment->isPending()) {
            return false;
        }

        return $this->hasAccess($user, $scheduledDeployment);
    }

    /**
     * Check if user has access to the scheduled deployment through the project
     */
    private function hasAccess(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $scheduledDeployment->project;
        if (!$project) {
            return false;
        }

        // Project owner can access
        if ($project->user_id === $user->id) {
            return true;
        }

        // Team members can access team projects
        if ($project->team_id) {
            $team = $project->team;
            if ($team && $team->hasMember($user)) {
                return true;
            }
        }

        return false;
    }
}
