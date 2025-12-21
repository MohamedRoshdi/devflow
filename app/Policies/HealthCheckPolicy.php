<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthCheck;
use App\Models\User;

/**
 * Health Check Policy
 *
 * Health checks can be associated with projects or servers.
 * Users can manage health checks if they have access to the related project/server.
 */
class HealthCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership
    }

    public function view(User $user, HealthCheck $healthCheck): bool
    {
        return $this->hasAccess($user, $healthCheck);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create health checks for their projects/servers
    }

    public function update(User $user, HealthCheck $healthCheck): bool
    {
        return $this->hasAccess($user, $healthCheck);
    }

    public function delete(User $user, HealthCheck $healthCheck): bool
    {
        return $this->hasAccess($user, $healthCheck);
    }

    public function execute(User $user, HealthCheck $healthCheck): bool
    {
        return $this->hasAccess($user, $healthCheck);
    }

    public function toggle(User $user, HealthCheck $healthCheck): bool
    {
        // Toggle active status
        return $this->hasAccess($user, $healthCheck);
    }

    /**
     * Check if user has access to the health check
     */
    private function hasAccess(User $user, HealthCheck $healthCheck): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Check project access
        if ($healthCheck->project_id) {
            $project = $healthCheck->project;
            if ($project) {
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
        }

        // Check server access
        if ($healthCheck->server_id) {
            $server = $healthCheck->server;
            if ($server) {
                // Server owner can access
                if ($server->user_id === $user->id) {
                    return true;
                }

                // Team members can access team servers
                if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
                    return true;
                }
            }
        }

        return false;
    }
}
