<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Project Policy
 *
 * All projects are shared across all authenticated users.
 * Any authenticated user can view, create, update, delete, and deploy projects.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        // All authenticated users can view any project
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        // All authenticated users can update any project
        return true;
    }

    public function delete(User $user, Project $project): bool
    {
        // All authenticated users can delete any project
        return true;
    }

    public function deploy(User $user, Project $project): bool
    {
        // All authenticated users can deploy any project
        return true;
    }
}
