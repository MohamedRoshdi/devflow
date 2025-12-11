<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

/**
 * Server Policy
 *
 * Servers are owned by users. Only the owner or team members can access them.
 */
class ServerPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership
    }

    public function view(User $user, Server $server): bool
    {
        // Admins can view all servers
        if ($user->hasRole('admin')) {
            return true;
        }

        // User must own the server or be a team member
        if ($server->user_id === $user->id) {
            return true;
        }

        // Check if user is a team member with access
        if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create servers
    }

    public function update(User $user, Server $server): bool
    {
        // Admins can update all servers
        if ($user->hasRole('admin')) {
            return true;
        }

        // User must own the server or be a team member
        if ($server->user_id === $user->id) {
            return true;
        }

        // Check if user is a team member with access
        if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Server $server): bool
    {
        // Admins can delete all servers
        if ($user->hasRole('admin')) {
            return true;
        }

        // Only owner can delete
        return $server->user_id === $user->id;
    }
}
