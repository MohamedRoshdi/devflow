<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Server;
use App\Services\GitService;
use App\Services\DockerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'status' => 'online',
            'docker_installed' => true,
        ]);
    }

    /** @test */
    public function user_can_create_project()
    {
        $this->actingAs($this->user);

        $projectData = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'repository_url' => 'https://github.com/test/repo.git',
            'branch' => 'main',
            'framework' => 'laravel',
            'php_version' => '8.4',
            'server_id' => $this->server->id,
        ];

        $response = $this->post(route('projects.store'), $projectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_cannot_access_other_users_project()
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $this->server->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(403);
    }

    /** @test */
    public function project_status_updates_correctly()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
        ]);

        // Mock DockerService
        $this->mock(DockerService::class, function ($mock) use ($project) {
            $mock->shouldReceive('startContainer')
                ->with($project)
                ->once()
                ->andReturn(['success' => true]);
        });

        Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project])
            ->call('startProject')
            ->assertHasNoErrors();

        $this->assertEquals('running', $project->fresh()->status);
    }

    /** @test */
    public function git_commits_are_loaded_correctly()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock GitService
        $this->mock(GitService::class, function ($mock) use ($project) {
            $mock->shouldReceive('getLatestCommits')
                ->with($project, 8, 1)
                ->once()
                ->andReturn([
                    'success' => true,
                    'commits' => [
                        [
                            'hash' => 'abc123',
                            'message' => 'Test commit',
                            'author' => 'Test Author',
                            'date' => now()->toDateTimeString(),
                        ],
                    ],
                    'total' => 1,
                ]);
        });

        Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project])
            ->call('prepareGitTab')
            ->assertSet('commits', function ($commits) {
                return count($commits) === 1 && $commits[0]['hash'] === 'abc123';
            });
    }

    /** @test */
    public function deployment_can_be_triggered()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project])
            ->set('showDeployModal', true)
            ->call('deploy')
            ->assertRedirect();

        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function project_search_works_correctly()
    {
        $this->actingAs($this->user);

        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $searchProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Searchable Project',
        ]);

        Livewire::test(\App\Livewire\Projects\ProjectList::class)
            ->set('search', 'Searchable')
            ->assertSee('Searchable Project')
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() === 1;
            });
    }

    /** @test */
    public function environment_variables_can_be_updated()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $envVars = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ];

        Livewire::test(\App\Livewire\Projects\ProjectEnvironment::class, ['project' => $project])
            ->set('environmentVariables', $envVars)
            ->call('saveEnvironment')
            ->assertHasNoErrors()
            ->assertDispatched('notification');

        $this->assertEquals($envVars, $project->fresh()->environment_variables);
    }

    /** @test */
    public function domain_can_be_added_to_project()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $domainData = [
            'domain' => 'example.com',
            'subdomain' => 'app',
            'ssl_enabled' => true,
        ];

        $response = $this->post(route('projects.domains.store', $project), $domainData);

        $response->assertRedirect();
        $this->assertDatabaseHas('domains', [
            'project_id' => $project->id,
            'domain' => 'example.com',
            'subdomain' => 'app',
        ]);
    }

    /** @test */
    public function project_status_badge_has_correct_color()
    {
        $this->actingAs($this->user);

        $runningProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $stoppedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
        ]);

        $response = $this->get(route('projects.show', $runningProject));
        $response->assertSee('bg-green-500/90');

        $response = $this->get(route('projects.show', $stoppedProject));
        $response->assertSee('bg-slate-500/90');
    }

    /** @test */
    public function cache_is_cleared_on_project_update()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Cache some data
        cache()->put("project_{$project->id}_stats", ['test' => 'data'], 3600);

        // Update project
        $project->update(['name' => 'Updated Name']);

        // Check cache is cleared
        $this->assertNull(cache()->get("project_{$project->id}_stats"));
    }
}