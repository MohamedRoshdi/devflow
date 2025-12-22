<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DatabaseBackup;
use App\Models\User;

/**
 * Backup Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-backups, create-backups, restore-backups, delete-backups
 */
class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-backups');
    }

    public function view(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('view-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    public function create(User $user): bool
    {
        return $user->can('create-backups');
    }

    public function update(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('create-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    public function delete(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('delete-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    public function restore(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('restore-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    public function download(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('view-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    public function verify(User $user, DatabaseBackup $backup): bool
    {
        return $user->can('view-backups') && $this->hasOwnershipAccess($user, $backup);
    }

    /**
     * Check if user has ownership access to the backup
     */
    private function hasOwnershipAccess(User $user, DatabaseBackup $backup): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-backups')) {
            return true;
        }

        $project = $backup->project;
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
