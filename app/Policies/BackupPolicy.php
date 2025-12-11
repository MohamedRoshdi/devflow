<?php

namespace App\Policies;

use App\Models\DatabaseBackup;
use App\Models\User;

/**
 * Backup Policy
 *
 * Backups are owned through their parent project.
 * Users can manage backups if they have access to the project.
 * This policy applies to all backup types: DatabaseBackup, FileBackup, ServerBackup.
 */
class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by project ownership
    }

    public function view(User $user, DatabaseBackup $backup): bool
    {
        return $this->hasAccess($user, $backup);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create backups for their projects
    }

    public function update(User $user, DatabaseBackup $backup): bool
    {
        return $this->hasAccess($user, $backup);
    }

    public function delete(User $user, DatabaseBackup $backup): bool
    {
        return $this->hasAccess($user, $backup);
    }

    public function restore(User $user, DatabaseBackup $backup): bool
    {
        // Restore is a critical operation, requires project ownership or admin role
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $backup->project;
        if (!$project) {
            return false;
        }

        // Only project owner can restore backups
        return $project->user_id === $user->id;
    }

    public function download(User $user, DatabaseBackup $backup): bool
    {
        return $this->hasAccess($user, $backup);
    }

    public function verify(User $user, DatabaseBackup $backup): bool
    {
        return $this->hasAccess($user, $backup);
    }

    /**
     * Check if user has access to the backup through the project
     */
    private function hasAccess(User $user, DatabaseBackup $backup): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $backup->project;
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
