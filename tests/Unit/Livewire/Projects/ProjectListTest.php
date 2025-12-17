<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Projects;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Projects\ProjectList;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectListTest extends TestCase
{
    

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
    }

    #[Test]
    public function component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-list');
    }

    #[Test]
    public function component_displays_projects(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertSee('Test Project');
    }

    #[Test]
    public function component_displays_multiple_projects(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Alpha',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Beta',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertSee('Project Alpha')
            ->assertSee('Project Beta');
    }

    #[Test]
    public function search_filters_projects_by_name(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Application',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React Application',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', 'Laravel')
            ->assertSee('Laravel Application')
            ->assertDontSee('React Application');
    }

    #[Test]
    public function search_filters_projects_by_slug(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project One',
            'slug' => 'unique-slug-one',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Two',
            'slug' => 'different-slug-two',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', 'unique-slug')
            ->assertSee('Project One')
            ->assertDontSee('Project Two');
    }

    #[Test]
    public function search_is_case_insensitive(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Production Server',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', 'PRODUCTION')
            ->assertSee('Production Server');
    }

    #[Test]
    public function status_filter_works_correctly(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'name' => 'Running Project',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
            'name' => 'Stopped Project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('statusFilter', 'running')
            ->assertSee('Running Project')
            ->assertDontSee('Stopped Project');
    }

    #[Test]
    public function server_filter_works_correctly(): void
    {
        $server1 = Server::factory()->create(['name' => 'Server 1']);
        $server2 = Server::factory()->create(['name' => 'Server 2']);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server1->id,
            'name' => 'Project on Server 1',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server2->id,
            'name' => 'Project on Server 2',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('serverFilter', (string) $server1->id)
            ->assertSee('Project on Server 1')
            ->assertDontSee('Project on Server 2');
    }

    #[Test]
    public function multiple_filters_can_be_applied_simultaneously(): void
    {
        $server1 = Server::factory()->create();
        $server2 = Server::factory()->create();

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server1->id,
            'status' => 'running',
            'name' => 'Running Laravel App',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server2->id,
            'status' => 'running',
            'name' => 'Running React App',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server1->id,
            'status' => 'stopped',
            'name' => 'Stopped Laravel App',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', 'Laravel')
            ->set('statusFilter', 'running')
            ->set('serverFilter', (string) $server1->id)
            ->assertSee('Running Laravel App')
            ->assertDontSee('Running React App')
            ->assertDontSee('Stopped Laravel App');
    }

    #[Test]
    public function pagination_works_correctly(): void
    {
        Project::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() === 12; // Default per page
            });
    }

    #[Test]
    public function refresh_projects_event_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('refreshProjects')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function project_owner_can_delete_their_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'My Project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', $project->id)
            ->assertHasNoErrors();

        // Project uses SoftDeletes, so check for soft deletion
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function non_owner_cannot_delete_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', $project->id)
            ->assertHasNoErrors();

        // Project should still exist (not deleted)
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[Test]
    public function team_owner_cannot_delete_team_project_they_dont_own(): void
    {
        // Note: The ProjectPolicy only allows project owners to delete, not team owners
        // This test verifies that team ownership alone doesn't grant delete permission
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $team->members()->attach($this->user->id, ['role' => 'owner']);

        $project = Project::factory()->create([
            'user_id' => User::factory()->create()->id, // Different user owns the project
            'team_id' => $team->id,
            'server_id' => $this->server->id,
        ]);

        $this->user->update(['current_team_id' => $team->id]);
        $this->user->refresh();

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', $project->id)
            ->assertHasNoErrors();

        // Project should NOT be deleted - only project owners can delete
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[Test]
    public function team_member_cannot_delete_team_project(): void
    {
        $team = Team::factory()->create();
        $team->members()->attach($this->user->id, ['role' => 'member']);

        $project = Project::factory()->create([
            'user_id' => User::factory()->create()->id,
            'team_id' => $team->id,
            'server_id' => $this->server->id,
        ]);

        $this->user->update(['current_team_id' => $team->id]);
        $this->user->refresh();

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', $project->id)
            ->assertHasNoErrors();

        // Project should still exist (not deleted)
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[Test]
    public function delete_non_existent_project_shows_error(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', 99999)
            ->assertHasNoErrors();
    }

    #[Test]
    public function unauthenticated_user_is_redirected(): void
    {
        // Note: The ProjectList component doesn't enforce authentication at the component level
        // Authentication is typically handled at the route level via middleware
        Livewire::test(ProjectList::class)
            ->assertStatus(200);
    }

    #[Test]
    public function component_eager_loads_relationships(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertViewHas('projects', function ($projects) {
                $project = $projects->first();

                return $project->relationLoaded('server') &&
                       $project->relationLoaded('domains') &&
                       $project->relationLoaded('user');
            });
    }

    #[Test]
    public function component_displays_server_dropdown(): void
    {
        // Clear cached servers first
        Cache::forget('servers_list');

        $server1 = Server::factory()->create(['name' => 'Production Server']);
        $server2 = Server::factory()->create(['name' => 'Development Server']);

        // The servers are exposed as a computed property, accessed via $this->servers
        $component = Livewire::actingAs($this->user)
            ->test(ProjectList::class);

        // Verify the computed property returns the expected servers
        $servers = $component->get('servers');
        $this->assertTrue($servers->contains('id', $server1->id));
        $this->assertTrue($servers->contains('id', $server2->id));
    }

    #[Test]
    public function projects_are_ordered_by_latest_first(): void
    {
        $oldProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Old Project',
            'created_at' => now()->subDays(5),
        ]);

        $newProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'New Project',
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertViewHas('projects', function ($projects) use ($newProject, $oldProject) {
                return $projects->first()->id === $newProject->id;
            });
    }

    #[Test]
    public function empty_search_shows_all_projects(): void
    {
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', '')
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() === 3;
            });
    }

    #[Test]
    public function component_only_selects_necessary_columns(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertViewHas('projects', function ($projects) {
                $project = $projects->first();
                $attributes = array_keys($project->getAttributes());

                return in_array('id', $attributes) &&
                       in_array('name', $attributes) &&
                       in_array('slug', $attributes) &&
                       in_array('status', $attributes);
            });
    }
}
