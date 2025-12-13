<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Projects\ProjectShow;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use App\Services\DockerService;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectShowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'running',
            'branch' => 'main',
            'framework' => 'laravel',
            'env_variables' => [
                'APP_NAME' => 'Test App',
                'APP_KEY' => 'base64:test-key-here',
                'DB_PASSWORD' => 'secret-password',
            ],
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function component_renders_successfully_with_project_data(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-show')
            ->assertSee($this->project->name)
            ->assertSee($this->project->branch);
    }

    /** @test */
    public function component_requires_view_authorization(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $this->server->id,
        ]);

        $this->actingAs($this->user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(ProjectShow::class, ['project' => $otherProject]);
    }

    /** @test */
    public function component_allows_owner_to_view_project(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee($this->project->name);
    }

    /** @test */
    public function component_allows_team_member_to_view_team_project(): void
    {
        $team = Team::factory()->create();
        $teamMember = User::factory()->create();

        // Add team member to the team
        $team->members()->attach($teamMember->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $teamProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'server_id' => $this->server->id,
        ]);

        $this->actingAs($teamMember);

        Livewire::test(ProjectShow::class, ['project' => $teamProject])
            ->assertStatus(200)
            ->assertSee($teamProject->name);
    }

    /** @test */
    public function component_loads_with_eager_loaded_relationships(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee('example.com');
    }

    /** @test */
    public function default_active_tab_is_overview(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('activeTab', 'overview');
    }

    /** @test */
    public function can_switch_to_git_tab(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getLatestCommits')
                ->andReturn([
                    'success' => true,
                    'commits' => [
                        [
                            'hash' => 'abc123',
                            'message' => 'Test commit',
                            'author' => 'Test User',
                            'date' => now()->toISOString(),
                        ],
                    ],
                    'total' => 1,
                ]);

            $mock->shouldReceive('checkForUpdates')
                ->andReturn([
                    'success' => true,
                    'up_to_date' => true,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('setActiveTab', 'git')
            ->assertSet('activeTab', 'git');
    }

    /** @test */
    public function tab_navigation_works_correctly(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('activeTab', 'overview')
            ->call('setActiveTab', 'overview')
            ->assertSet('activeTab', 'overview');
    }

    /** @test */
    public function git_tab_loads_commits_on_first_access(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getLatestCommits')
                ->once()
                ->with($this->project, 8, 1)
                ->andReturn([
                    'success' => true,
                    'commits' => [
                        [
                            'hash' => 'abc123',
                            'message' => 'Initial commit',
                            'author' => 'Test User',
                            'date' => now()->toISOString(),
                        ],
                    ],
                    'total' => 1,
                ]);

            $mock->shouldReceive('checkForUpdates')
                ->andReturn([
                    'success' => true,
                    'up_to_date' => true,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('gitLoaded', false)
            ->call('setActiveTab', 'git')
            ->assertSet('gitLoaded', true)
            ->assertSet('commits', [
                [
                    'hash' => 'abc123',
                    'message' => 'Initial commit',
                    'author' => 'Test User',
                    'date' => now()->toISOString(),
                ],
            ]);
    }

    /** @test */
    public function recent_deployments_list_shows_with_pagination(): void
    {
        // Create multiple deployments
        Deployment::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertStatus(200);

        $deployments = $component->viewData('deployments');
        $this->assertNotNull($deployments);
        $this->assertLessThanOrEqual(5, $deployments->count()); // deploymentsPerPage = 5
    }

    /** @test */
    public function deploy_button_creates_new_deployment(): void
    {
        Queue::fake();

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('deploy')
            ->assertSessionHas('message', 'Deployment started successfully!')
            ->assertRedirect();

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'triggered_by' => 'manual',
        ]);

        Queue::assertPushed(DeployProjectJob::class);
    }

    /** @test */
    public function deploy_button_prevents_concurrent_deployments(): void
    {
        // Create an active deployment
        $activeDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('deploy')
            ->assertSessionHas('error')
            ->assertRedirect(route('deployments.show', $activeDeployment));
    }

    /** @test */
    public function start_project_updates_project_status(): void
    {
        $this->project->update(['status' => 'stopped']);

        $this->mock(DockerService::class, function ($mock) {
            $mock->shouldReceive('startContainer')
                ->once()
                ->andReturn([
                    'success' => true,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('startProject')
            ->assertSessionHas('message', 'Project started successfully');

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'status' => 'running',
        ]);
    }

    /** @test */
    public function stop_project_updates_project_status(): void
    {
        $this->project->update(['status' => 'running']);

        $this->mock(DockerService::class, function ($mock) {
            $mock->shouldReceive('stopContainer')
                ->once()
                ->andReturn([
                    'success' => true,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('stopProject')
            ->assertSessionHas('message', 'Project stopped successfully');

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'status' => 'stopped',
        ]);
    }

    /** @test */
    public function domain_information_displays_correctly(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'subdomain' => 'app',
            'ssl_enabled' => true,
            'is_primary' => true,
        ]);

        $component = Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertStatus(200);

        $domains = $component->viewData('domains');
        $this->assertNotNull($domains);
        $this->assertTrue($domains->contains($domain));
    }

    /** @test */
    public function check_for_updates_queries_git_service(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('checkForUpdates')
                ->once()
                ->with($this->project)
                ->andReturn([
                    'success' => true,
                    'up_to_date' => false,
                    'behind' => 5,
                    'commits' => [],
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('checkForUpdates', true)
            ->assertSet('updateStatusLoaded', true)
            ->assertSet('checkingForUpdates', false);
    }

    /** @test */
    public function refresh_git_data_reloads_commits_and_update_status(): void
    {
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
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->set('gitLoaded', true)
            ->call('refreshGitData')
            ->assertSet('gitLoaded', true)
            ->assertSet('updateStatusLoaded', true);
    }

    /** @test */
    public function branch_selector_loads_available_branches(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getBranches')
                ->once()
                ->with($this->project)
                ->andReturn([
                    'success' => true,
                    'branches' => [
                        ['name' => 'main', 'current' => true],
                        ['name' => 'develop', 'current' => false],
                        ['name' => 'feature/test', 'current' => false],
                    ],
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('toggleBranchSelector')
            ->assertSet('showBranchSelector', true)
            ->assertSet('availableBranches', [
                ['name' => 'main', 'current' => true],
                ['name' => 'develop', 'current' => false],
                ['name' => 'feature/test', 'current' => false],
            ]);
    }

    /** @test */
    public function branch_switch_updates_project_branch(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('switchBranch')
                ->once()
                ->with($this->project, 'develop')
                ->andReturn([
                    'success' => true,
                    'message' => 'Branch switched successfully',
                ]);

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
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('selectBranchForSwitch', 'develop')
            ->assertSet('selectedBranch', 'develop')
            ->assertSet('showBranchConfirmModal', true)
            ->call('confirmBranchSwitch')
            ->assertSessionHas('message', 'Branch switched successfully')
            ->assertSet('showBranchConfirmModal', false)
            ->assertSet('showBranchSelector', false);
    }

    /** @test */
    public function cancel_branch_switch_resets_selected_branch(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('selectBranchForSwitch', 'develop')
            ->assertSet('selectedBranch', 'develop')
            ->call('cancelBranchSwitch')
            ->assertSet('showBranchConfirmModal', false)
            ->assertSet('selectedBranch', 'main'); // Should reset to project branch
    }

    /** @test */
    public function commit_pagination_works_correctly(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getLatestCommits')
                ->twice()
                ->andReturn([
                    'success' => true,
                    'commits' => [],
                    'total' => 20,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('commitPage', 1)
            ->call('nextCommitPage')
            ->assertSet('commitPage', 2)
            ->call('previousCommitPage')
            ->assertSet('commitPage', 1);
    }

    /** @test */
    public function auto_refresh_toggle_updates_state(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('autoRefreshEnabled', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefreshEnabled', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefreshEnabled', true);
    }

    /** @test */
    public function auto_refresh_interval_can_be_set(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('setAutoRefreshInterval', 60)
            ->assertSet('autoRefreshInterval', 60);
    }

    /** @test */
    public function auto_refresh_interval_respects_min_and_max_bounds(): void
    {
        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('setAutoRefreshInterval', 5) // Below minimum
            ->assertSet('autoRefreshInterval', 10) // Should be clamped to 10
            ->call('setAutoRefreshInterval', 400) // Above maximum
            ->assertSet('autoRefreshInterval', 300); // Should be clamped to 300
    }

    /** @test */
    public function tab_parameter_sets_initial_active_tab(): void
    {
        Livewire::withQueryParams(['tab' => 'overview'])
            ->test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('firstTab', 'overview')
            ->assertSet('activeTab', 'overview');
    }

    /** @test */
    public function start_project_handles_docker_service_errors_gracefully(): void
    {
        $this->mock(DockerService::class, function ($mock) {
            $mock->shouldReceive('startContainer')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Container not found',
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('startProject')
            ->assertSessionHas('error', 'Failed to start project: Container not found');
    }

    /** @test */
    public function stop_project_handles_docker_service_errors_gracefully(): void
    {
        $this->mock(DockerService::class, function ($mock) {
            $mock->shouldReceive('stopContainer')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Container not running',
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('stopProject')
            ->assertSessionHas('error', 'Failed to stop project: Container not running');
    }

    /** @test */
    public function preload_update_status_is_called_on_mount(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('checkForUpdates')
                ->once()
                ->with($this->project)
                ->andReturn([
                    'success' => true,
                    'up_to_date' => true,
                ]);
        });

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('preloadUpdateStatus')
            ->assertSet('updateStatusRequested', true);
    }

    /** @test */
    public function component_displays_active_deployment_banner_when_deployment_is_running(): void
    {
        $activeDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->project->refresh();

        Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->assertSee('Deployment in Progress')
            ->assertSee('Running');
    }

    /** @test */
    public function filtered_branches_property_filters_by_search_term(): void
    {
        $this->mock(GitService::class, function ($mock) {
            $mock->shouldReceive('getBranches')
                ->andReturn([
                    'success' => true,
                    'branches' => [
                        ['name' => 'main', 'current' => true],
                        ['name' => 'develop', 'current' => false],
                        ['name' => 'feature/auth', 'current' => false],
                        ['name' => 'feature/payment', 'current' => false],
                    ],
                ]);
        });

        $component = Livewire::test(ProjectShow::class, ['project' => $this->project])
            ->call('loadBranches')
            ->set('branchSearch', 'feature');

        $filteredBranches = $component->get('filteredBranches');
        $this->assertCount(2, $filteredBranches);
        $this->assertContains('feature/auth', array_column($filteredBranches, 'name'));
        $this->assertContains('feature/payment', array_column($filteredBranches, 'name'));
    }
}
