<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LogSource;
use App\Models\User;

/**
 * LogSource Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permission: view-logs
 */
class LogSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-logs');
    }

    public function view(User $user, LogSource $logSource): bool
    {
        return $user->can('view-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    public function create(User $user): bool
    {
        return $user->can('view-logs');
    }

    public function update(User $user, LogSource $logSource): bool
    {
        return $user->can('view-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    public function delete(User $user, LogSource $logSource): bool
    {
        return $user->can('delete-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    public function sync(User $user, LogSource $logSource): bool
    {
        return $user->can('view-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    public function toggle(User $user, LogSource $logSource): bool
    {
        return $user->can('view-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    public function viewLogs(User $user, LogSource $logSource): bool
    {
        return $user->can('view-logs') && $this->hasOwnershipAccess($user, $logSource);
    }

    /**
     * Check if user has ownership access to the log source
     */
    private function hasOwnershipAccess(User $user, LogSource $logSource): bool
    {
        // Users with delete-logs permission have global access
        if ($user->can('delete-logs')) {
            return true;
        }

        // Server-based log sources
        if ($logSource->server_id) {
            $server = $logSource->server;
            if (! $server) {
                return false;
            }

            if ($server->user_id === $user->id) {
                return true;
            }

            if ($server->team_id) {
                $currentTeam = $user->currentTeam;
                if ($currentTeam && $currentTeam->id === $server->team_id) {
                    return true;
                }
            }
        }

        // Project-based log sources
        if ($logSource->project_id) {
            $project = $logSource->project;
            if (! $project) {
                return false;
            }

            if ($project->user_id === $user->id) {
                return true;
            }

            if ($project->team_id) {
                $team = $project->team;
                if ($team && $team->hasMember($user)) {
                    return true;
                }
            }
        }

        return false;
    }
}
