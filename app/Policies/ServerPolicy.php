<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

/**
 * Server Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-servers, create-servers, edit-servers, delete-servers
 */
class ServerPolicy
{
    /**
     * Check if user has ownership or team access to the server.
     */
    private function hasOwnershipAccess(User $user, Server $server): bool
    {
        // User owns the server
        if ($server->user_id === $user->id) {
            return true;
        }

        // User is a team member with access to the server's team
        if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-servers');
    }

    public function view(User $user, Server $server): bool
    {
        // Must have view permission AND (ownership OR global access)
        if (! $user->can('view-servers')) {
            return false;
        }

        // Ownership check or user has elevated permissions
        return $this->hasOwnershipAccess($user, $server)
            || $user->can('edit-servers'); // Users who can edit all can view all
    }

    public function create(User $user): bool
    {
        return $user->can('create-servers');
    }

    public function update(User $user, Server $server): bool
    {
        if (! $user->can('edit-servers')) {
            return false;
        }

        // Global edit permission or ownership
        return $this->hasOwnershipAccess($user, $server)
            || $user->can('delete-servers'); // Higher permission implies global access
    }

    public function delete(User $user, Server $server): bool
    {
        if (! $user->can('delete-servers')) {
            return false;
        }

        // Delete permission + ownership, or full delete permission implies global
        return $this->hasOwnershipAccess($user, $server)
            || $user->can('delete-servers');
    }
}
