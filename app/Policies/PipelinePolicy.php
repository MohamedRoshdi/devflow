<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;

/**
 * Pipeline Policy
 *
 * Pipelines are owned through their parent project.
 * Users can manage pipelines if they have access to the project.
 */
class PipelinePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by project ownership
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        return $this->hasAccess($user, $pipeline);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create pipelines for their projects
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        return $this->hasAccess($user, $pipeline);
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        // Only project owner can delete pipelines
        $project = $pipeline->project;
        return $project && $project->user_id === $user->id;
    }

    public function execute(User $user, Pipeline $pipeline): bool
    {
        // Any user with access can execute pipelines
        return $this->hasAccess($user, $pipeline);
    }

    public function toggle(User $user, Pipeline $pipeline): bool
    {
        // Toggle active status - any user with access
        return $this->hasAccess($user, $pipeline);
    }

    public function viewRuns(User $user, Pipeline $pipeline): bool
    {
        return $this->hasAccess($user, $pipeline);
    }

    /**
     * Check if user has access to the pipeline through the project
     */
    private function hasAccess(User $user, Pipeline $pipeline): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $pipeline->project;
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
