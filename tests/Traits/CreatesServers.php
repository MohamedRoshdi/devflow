<?php

namespace Tests\Traits;

use App\Models\Server;
use App\Models\User;

trait CreatesServers
{
    /**
     * Create a server with default attributes.
     */
    protected function createServer(array $attributes = []): Server
    {
        return Server::factory()->create($attributes);
    }

    /**
     * Create an online server.
     *
     * Uses localhost IP to bypass SSH wrapping in tests,
     * allowing Process::fake() patterns to match direct commands.
     */
    protected function createOnlineServer(array $attributes = []): Server
    {
        return Server::factory()->online()->localhost()->create($attributes);
    }

    /**
     * Create an offline server.
     */
    protected function createOfflineServer(array $attributes = []): Server
    {
        return Server::factory()->offline()->create($attributes);
    }

    /**
     * Create a server with Docker installed.
     *
     * Uses localhost IP to bypass SSH wrapping in tests,
     * allowing Process::fake() patterns to match direct commands.
     */
    protected function createServerWithDocker(array $attributes = []): Server
    {
        return Server::factory()->withDocker()->localhost()->create($attributes);
    }

    /**
     * Create a server with password authentication.
     */
    protected function createServerWithPassword(array $attributes = []): Server
    {
        return Server::factory()->withPassword()->create($attributes);
    }

    /**
     * Create a server with SSH key authentication.
     */
    protected function createServerWithSshKey(array $attributes = []): Server
    {
        return Server::factory()->withSshKey()->create($attributes);
    }

    /**
     * Create multiple servers.
     */
    protected function createServers(int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Server::factory()->count($count)->create($attributes);
    }

    /**
     * Create a server for a specific user.
     */
    protected function createServerForUser(User $user, array $attributes = []): Server
    {
        return Server::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $attributes));
    }
}
