<?php

namespace App\Policies;

use App\Models\LogSource;
use App\Models\User;

/**
 * LogSource Policy
 *
 * Log sources are associated with servers or projects.
 * Users can manage log sources if they have access to the server/project.
 */
class LogSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by server/project ownership
    }

    public function view(User $user, LogSource $logSource): bool
    {
        return $this->hasAccess($user, $logSource);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create log sources for their servers/projects
    }

    public function update(User $user, LogSource $logSource): bool
    {
        return $this->hasAccess($user, $logSource);
    }

    public function delete(User $user, LogSource $logSource): bool
    {
        return $this->hasAccess($user, $logSource);
    }

    public function sync(User $user, LogSource $logSource): bool
    {
        // Trigger manual log sync
        return $this->hasAccess($user, $logSource);
    }

    public function toggle(User $user, LogSource $logSource): bool
    {
        // Toggle active/inactive status
        return $this->hasAccess($user, $logSource);
    }

    public function viewLogs(User $user, LogSource $logSource): bool
    {
        // View log entries from this source
        return $this->hasAccess($user, $logSource);
    }

    /**
     * Check if user has access to the log source
     */
    private function hasAccess(User $user, LogSource $logSource): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Server-based log sources
        if ($logSource->server_id) {
            $server = $logSource->server;
            if (!$server) {
                return false;
            }

            // Server owner can access
            if ($server->user_id === $user->id) {
                return true;
            }

            // Team members can access team servers
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
        }

        return false;
    }
}
