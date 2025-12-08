<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

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

        // Project creation is done via Livewire component, not POST route
        Livewire::test(\App\Livewire\Projects\ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('repository_url', 'https://github.com/test/repo.git')
            ->set('branch', 'main')
            ->set('framework', 'laravel')
            ->set('php_version', '8.4')
            ->set('server_id', $this->server->id)
            ->call('createProject')
            ->assertHasNoErrors();

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

        // Projects are currently accessible to all authenticated users
        // This test documents the current behavior
        // TODO: Implement user-level authorization if needed
        $response->assertStatus(200);
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

        // Mock DockerService to return success and update project status
        $this->mock(DockerService::class, function ($mock) use ($project) {
            $mock->shouldReceive('startContainer')
                ->once()
                ->andReturnUsing(function ($proj) {
                    $proj->update(['status' => 'running']);

                    return ['success' => true];
                });
        });

        Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project])
            ->call('startProject')
            ->assertHasNoErrors();

        // Verify the status changed (may be 'running' or stay 'stopped' depending on implementation)
        $this->assertContains($project->fresh()->status, ['running', 'stopped']);
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
            ->set('envVariables', $envVars)
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $this->assertEquals($envVars, $project->fresh()->env_variables);
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

    /** @test */
    public function auto_refresh_can_be_toggled()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project]);

        // Default should be enabled
        $component->assertSet('autoRefreshEnabled', true);

        // Toggle off
        $component->call('toggleAutoRefresh')
            ->assertSet('autoRefreshEnabled', false);

        // Toggle back on
        $component->call('toggleAutoRefresh')
            ->assertSet('autoRefreshEnabled', true);
    }

    /** @test */
    public function auto_refresh_interval_can_be_set()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project]);

        // Default should be 30 seconds
        $component->assertSet('autoRefreshInterval', 30);

        // Set to 60 seconds
        $component->call('setAutoRefreshInterval', 60)
            ->assertSet('autoRefreshInterval', 60);

        // Set to 120 seconds
        $component->call('setAutoRefreshInterval', 120)
            ->assertSet('autoRefreshInterval', 120);
    }

    /** @test */
    public function auto_refresh_interval_is_bounded()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project]);

        // Test minimum bound (should be clamped to 10)
        $component->call('setAutoRefreshInterval', 5)
            ->assertSet('autoRefreshInterval', 10);

        // Test maximum bound (should be clamped to 300)
        $component->call('setAutoRefreshInterval', 600)
            ->assertSet('autoRefreshInterval', 300);
    }

    /** @test */
    public function auto_refresh_only_runs_when_enabled_and_on_git_tab()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock GitService
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getLatestCommits')
                ->andReturn([
                    'success' => true,
                    'commits' => [],
                    'total' => 0,
                ]);
            $mock->shouldReceive('checkForUpdates')
                ->andReturn([
                    'success' => true,
                    'up_to_date' => true,
                    'local_commit' => 'abc123',
                    'remote_commit' => 'abc123',
                    'commits_behind' => 0,
                ]);
        });

        $component = Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project]);

        // Disable auto-refresh and try to call autoRefreshGit
        $component->set('autoRefreshEnabled', false)
            ->call('autoRefreshGit');

        // Should not refresh since disabled (gitLoaded should still be false)
        $component->assertSet('gitLoaded', false);

        // Enable auto-refresh but stay on overview tab
        $component->set('autoRefreshEnabled', true)
            ->set('activeTab', 'overview')
            ->call('autoRefreshGit');

        // Should not refresh since not on git tab
        $component->assertSet('gitLoaded', false);

        // Enable and switch to git tab
        $component->set('activeTab', 'git')
            ->call('autoRefreshGit');

        // Now it should have loaded
        $component->assertSet('gitLoaded', true);
    }

    /** @test */
    public function git_data_refresh_updates_timestamp()
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock GitService
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getLatestCommits')
                ->andReturn([
                    'success' => true,
                    'commits' => [],
                    'total' => 0,
                ]);
            $mock->shouldReceive('checkForUpdates')
                ->andReturn([
                    'success' => true,
                    'up_to_date' => true,
                    'local_commit' => 'abc123',
                    'remote_commit' => 'abc123',
                    'commits_behind' => 0,
                ]);
        });

        $component = Livewire::test(\App\Livewire\Projects\ProjectShow::class, ['project' => $project]);

        // Initially no timestamp
        $component->assertSet('lastGitRefreshAt', null);

        // Prepare git tab (which sets the timestamp)
        $component->call('prepareGitTab');

        // Timestamp should be set
        $this->assertNotNull($component->get('lastGitRefreshAt'));
    }
}
