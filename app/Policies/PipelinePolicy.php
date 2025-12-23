<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;

/**
 * Pipeline Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-pipelines, create-pipelines, edit-pipelines, delete-pipelines, execute-pipelines
 */
class PipelinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-pipelines');
    }

    public function view(User $user, Pipeline $pipeline): bool
    {
        return $user->can('view-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    public function create(User $user): bool
    {
        return $user->can('create-pipelines');
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        return $user->can('delete-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    public function execute(User $user, Pipeline $pipeline): bool
    {
        return $user->can('execute-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    public function toggle(User $user, Pipeline $pipeline): bool
    {
        return $user->can('edit-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    public function viewRuns(User $user, Pipeline $pipeline): bool
    {
        return $user->can('view-pipelines') && $this->hasOwnershipAccess($user, $pipeline);
    }

    /**
     * Check if user has ownership access to the pipeline through the project
     */
    private function hasOwnershipAccess(User $user, Pipeline $pipeline): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-pipelines')) {
            return true;
        }

        $project = $pipeline->project;
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
