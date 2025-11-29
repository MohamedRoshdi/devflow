<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

/**
 * Server Policy
 *
 * All servers are shared across all authenticated users.
 * Any authenticated user can view, create, update, and delete servers.
 */
class ServerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Server $server): bool
    {
        // All authenticated users can view any server
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Server $server): bool
    {
        // All authenticated users can update any server
        return true;
    }

    public function delete(User $user, Server $server): bool
    {
        // All authenticated users can delete any server
        return true;
    }
}

