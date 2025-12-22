<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PipelineConfig;
use App\Models\User;

/**
 * PipelineConfig Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-pipelines, edit-pipelines, delete-pipelines
 */
class PipelineConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-pipelines');
    }

    public function view(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('view-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function create(User $user): bool
    {
        return $user->can('create-pipelines');
    }

    public function update(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function delete(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('delete-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function toggle(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function regenerateWebhookSecret(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function updateBranches(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    public function updatePatterns(User $user, PipelineConfig $pipelineConfig): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipelineConfig);
    }

    /**
     * Check if user has ownership access to the pipeline config
     */
    private function hasOwnershipAccess(User $user, PipelineConfig $pipelineConfig): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-pipelines')) {
            return true;
        }

        $project = $pipelineConfig->project;
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
