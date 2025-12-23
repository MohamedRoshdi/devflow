<?php

namespace Tests\Traits;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;

trait CreatesProjects
{
    /**
     * Create a project with default attributes.
     */
    protected function createProject(array $attributes = []): Project
    {
        return Project::factory()->create($attributes);
    }

    /**
     * Create a Laravel project.
     */
    protected function createLaravelProject(array $attributes = []): Project
    {
        return Project::factory()->laravel()->create($attributes);
    }

    /**
     * Create a running project.
     */
    protected function createRunningProject(array $attributes = []): Project
    {
        return Project::factory()->running()->create($attributes);
    }

    /**
     * Create a stopped project.
     */
    protected function createStoppedProject(array $attributes = []): Project
    {
        return Project::factory()->stopped()->create($attributes);
    }

    /**
     * Create multiple projects.
     */
    protected function createProjects(int $count = 3, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return Project::factory()->count($count)->create($attributes);
    }

    /**
     * Create a project with a specific server.
     */
    protected function createProjectForServer(Server $server, array $attributes = []): Project
    {
        return Project::factory()->create(array_merge([
            'server_id' => $server->id,
        ], $attributes));
    }

    /**
     * Create a project with a specific user.
     */
    protected function createProjectForUser(User $user, array $attributes = []): Project
    {
        return Project::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $attributes));
    }
}
