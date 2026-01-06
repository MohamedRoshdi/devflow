<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Project Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-projects, create-projects, edit-projects, delete-projects, deploy-projects
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('view-projects') && $this->hasOwnershipAccess($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->can('create-projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('edit-projects') && $this->hasOwnershipAccess($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('delete-projects') && $this->hasOwnershipAccess($user, $project);
    }

    public function deploy(User $user, Project $project): bool
    {
        return $user->can('deploy-projects') && $this->hasOwnershipAccess($user, $project);
    }

    /**
     * Determine if the user can manage project environment variables
     *
     * Required for adding/editing/deleting local env variables
     */
    public function manageEnvironment(User $user, Project $project): bool
    {
        return $user->can('edit-projects') && $this->hasOwnershipAccess($user, $project);
    }

    /**
     * Determine if the user can modify server environment variables
     *
     * This is a sensitive operation that modifies production .env files.
     * Requires elevated permissions (deploy-projects or delete-projects).
     */
    public function manageServerEnvironment(User $user, Project $project): bool
    {
        // Requires deploy permission as it affects running servers
        if (! $user->can('deploy-projects')) {
            return false;
        }

        return $this->hasOwnershipAccess($user, $project);
    }

    /**
     * Determine if the user can modify sensitive environment variables
     *
     * Sensitive keys like DB_PASSWORD, APP_KEY require admin-level access.
     */
    public function manageSensitiveEnvironment(User $user, Project $project): bool
    {
        // Only users with delete permission (admin-level) can modify sensitive keys
        if (! $user->can('delete-projects')) {
            return false;
        }

        return $this->hasOwnershipAccess($user, $project);
    }

    /**
     * Check if user has ownership access to the project
     */
    private function hasOwnershipAccess(User $user, Project $project): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-projects')) {
            return true;
        }

        // Owner
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
