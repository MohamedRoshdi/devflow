<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthCheck;
use App\Models\User;

/**
 * Health Check Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-health-checks, manage-health-checks
 */
class HealthCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-health-checks');
    }

    public function view(User $user, HealthCheck $healthCheck): bool
    {
        return $user->can('view-health-checks') && $this->hasOwnershipAccess($user, $healthCheck);
    }

    public function create(User $user): bool
    {
        return $user->can('manage-health-checks');
    }

    public function update(User $user, HealthCheck $healthCheck): bool
    {
        return $user->can('manage-health-checks') && $this->hasOwnershipAccess($user, $healthCheck);
    }

    public function delete(User $user, HealthCheck $healthCheck): bool
    {
        return $user->can('manage-health-checks') && $this->hasOwnershipAccess($user, $healthCheck);
    }

    public function execute(User $user, HealthCheck $healthCheck): bool
    {
        return $user->can('manage-health-checks') && $this->hasOwnershipAccess($user, $healthCheck);
    }

    public function toggle(User $user, HealthCheck $healthCheck): bool
    {
        return $user->can('manage-health-checks') && $this->hasOwnershipAccess($user, $healthCheck);
    }

    /**
     * Check if user has ownership access to the health check
     */
    private function hasOwnershipAccess(User $user, HealthCheck $healthCheck): bool
    {
        // Users with manage permission have global access
        if ($user->can('manage-health-checks')) {
            return true;
        }

        // Check project access
        if ($healthCheck->project_id) {
            $project = $healthCheck->project;
            if ($project) {
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
        }

        // Check server access
        if ($healthCheck->server_id) {
            $server = $healthCheck->server;
            if ($server) {
                if ($server->user_id === $user->id) {
                    return true;
                }

                if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
                    return true;
                }
            }
        }

        return false;
    }
}
