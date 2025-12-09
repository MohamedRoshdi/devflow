<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Project Policy
 *
 * Projects are owned by users and optionally shared with teams.
 * Only owners, team members, or admins can access projects.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership
    }

    public function view(User $user, Project $project): bool
    {
        return $this->hasAccess($user, $project);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create projects
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasAccess($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        // Only owner can delete
        return $project->user_id === $user->id;
    }

    public function deploy(User $user, Project $project): bool
    {
        return $this->hasAccess($user, $project);
    }

    /**
     * Check if user has access to the project
     */
    private function hasAccess(User $user, Project $project): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Owner can access
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
