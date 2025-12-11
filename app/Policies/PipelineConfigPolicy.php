<?php

namespace App\Policies;

use App\Models\PipelineConfig;
use App\Models\User;

/**
 * PipelineConfig Policy
 *
 * Pipeline configurations are owned through their parent project.
 * Users can manage pipeline configs if they have access to the project.
 */
class PipelineConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by project ownership
    }

    public function view(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $this->hasAccess($user, $pipelineConfig);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create pipeline configs for their projects
    }

    public function update(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $this->hasAccess($user, $pipelineConfig);
    }

    public function delete(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Only project owner can delete pipeline configs
        $project = $pipelineConfig->project;
        return $project && $project->user_id === $user->id;
    }

    public function toggle(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Toggle enabled/disabled status
        return $this->hasAccess($user, $pipelineConfig);
    }

    public function regenerateWebhookSecret(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Only project owner can regenerate webhook secret
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $pipelineConfig->project;
        return $project && $project->user_id === $user->id;
    }

    public function updateBranches(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Update auto-deploy branches
        return $this->hasAccess($user, $pipelineConfig);
    }

    public function updatePatterns(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Update skip/deploy patterns
        return $this->hasAccess($user, $pipelineConfig);
    }

    /**
     * Check if user has access to the pipeline config through the project
     */
    private function hasAccess(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $pipelineConfig->project;
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
