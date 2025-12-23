<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\ProjectEdit;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProjectEditTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;
    private Server $server;
    private Project $project;

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
            'repository_url' => 'https://github.com/user/repo.git',
            'branch' => 'main',
            'framework' => 'laravel',
        ]);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('name', 'Test Project')
            ->assertSet('slug', 'test-project')
            ->assertSet('repository_url', 'https://github.com/user/repo.git')
            ->assertSet('branch', 'main')
            ->assertSet('framework', 'laravel')
            ->assertSet('server_id', $this->server->id);
    }

    public function test_component_loads_servers(): void
    {
        Server::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('servers', function ($servers) {
                return $servers->count() === 4;
            });
    }

    // ==================== AUTHORIZATION TESTS ====================

    public function test_owner_can_access_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_non_owner_cannot_access_project(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertStatus(403);
    }

    public function test_team_member_can_access_team_project(): void
    {
        $team = Team::factory()->create();
        $teamMember = User::factory()->create(['current_team_id' => $team->id]);
        $teamMember->teams()->attach($team);

        $teamProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $team->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($teamMember)
            ->test(ProjectEdit::class, ['project' => $teamProject])
            ->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);

        Livewire::test(ProjectEdit::class, ['project' => $this->project]);
    }

    // ==================== UPDATE PROJECT TESTS ====================

    public function test_can_update_project_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('name', 'Updated Project Name')
            ->set('slug', 'updated-project-name')
            ->call('updateProject')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.show', $this->project));

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('Updated Project Name', $freshProject->name);
    }

    public function test_can_update_repository_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('repository_url', 'https://gitlab.com/org/new-repo.git')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('https://gitlab.com/org/new-repo.git', $freshProject->repository_url);
    }

    public function test_can_update_branch(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('branch', 'develop')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('develop', $freshProject->branch);
    }

    public function test_can_update_framework(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('framework', 'nextjs')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('nextjs', $freshProject->framework);
    }

    public function test_can_update_php_version(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('php_version', '8.4')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('8.4', $freshProject->php_version);
    }

    public function test_can_update_node_version(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('node_version', '22')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('22', $freshProject->node_version);
    }

    public function test_can_update_auto_deploy(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('auto_deploy', true)
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertTrue($freshProject->auto_deploy);
    }

    public function test_can_update_build_and_start_commands(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('build_command', 'npm run build')
            ->set('start_command', 'npm start')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('npm run build', $freshProject->build_command);
        $this->assertEquals('npm start', $freshProject->start_command);
    }

    public function test_can_update_server(): void
    {
        $newServer = Server::factory()->create(['status' => 'online']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('server_id', $newServer->id)
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals($newServer->id, $freshProject->server_id);
    }

    public function test_update_flashes_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('name', 'Updated Name')
            ->set('slug', 'updated-name')
            ->call('updateProject')
            ->assertSessionHas('message', 'Project updated successfully!');
    }

    // ==================== VALIDATION TESTS ====================

    public function test_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('name', '')
            ->call('updateProject')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_slug_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('slug', '')
            ->call('updateProject')
            ->assertHasErrors(['slug' => 'required']);
    }

    public function test_slug_must_be_unique_except_self(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'slug' => 'existing-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('slug', 'existing-slug')
            ->call('updateProject')
            ->assertHasErrors(['slug' => 'unique']);
    }

    public function test_can_keep_same_slug(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('slug', 'test-project')
            ->call('updateProject')
            ->assertHasNoErrors(['slug']);
    }

    public function test_server_id_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('server_id', '')
            ->call('updateProject')
            ->assertHasErrors(['server_id' => 'required']);
    }

    public function test_server_id_must_exist(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('server_id', 99999)
            ->call('updateProject')
            ->assertHasErrors(['server_id' => 'exists']);
    }

    public function test_repository_url_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('repository_url', '')
            ->call('updateProject')
            ->assertHasErrors(['repository_url' => 'required']);
    }

    public function test_repository_url_must_be_valid_git_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('repository_url', 'not-a-valid-url')
            ->call('updateProject')
            ->assertHasErrors(['repository_url' => 'regex']);
    }

    public function test_accepts_https_git_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->call('updateProject')
            ->assertHasNoErrors(['repository_url']);
    }

    public function test_accepts_ssh_git_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('repository_url', 'git@github.com:user/repo.git')
            ->call('updateProject')
            ->assertHasNoErrors(['repository_url']);
    }

    public function test_branch_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('branch', '')
            ->call('updateProject')
            ->assertHasErrors(['branch' => 'required']);
    }

    public function test_branch_validation_pattern(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('branch', 'feature/new-feature')
            ->call('updateProject')
            ->assertHasNoErrors(['branch']);
    }

    public function test_latitude_must_be_in_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('latitude', 100)
            ->call('updateProject')
            ->assertHasErrors(['latitude']);
    }

    public function test_longitude_must_be_in_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('longitude', 200)
            ->call('updateProject')
            ->assertHasErrors(['longitude']);
    }

    // ==================== AUTO SLUG GENERATION TESTS ====================

    public function test_updating_name_auto_updates_slug(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('name', 'My Awesome Project')
            ->assertSet('slug', 'my-awesome-project');
    }

    // ==================== SERVER STATUS TESTS ====================

    public function test_can_refresh_server_status(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pingAndUpdateStatus')->once();
        });

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->call('refreshServerStatus', $this->server->id)
            ->assertSessionHas('server_status_updated', 'Server status refreshed');
    }

    public function test_refresh_nonexistent_server_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->call('refreshServerStatus', 99999)
            ->assertSessionMissing('server_status_updated');
    }

    public function test_servers_are_ordered_by_status(): void
    {
        Server::factory()->create(['status' => 'offline', 'name' => 'Offline Server']);
        Server::factory()->create(['status' => 'maintenance', 'name' => 'Maintenance Server']);
        Server::factory()->create(['status' => 'error', 'name' => 'Error Server']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('servers', function ($servers) {
                // Online should be first
                return $servers->first()->status === 'online';
            });
    }

    // ==================== FORM FIELD TESTS ====================

    public function test_loads_root_directory(): void
    {
        $this->project->update(['root_directory' => '/app']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('root_directory', '/app');
    }

    public function test_loads_coordinates(): void
    {
        $this->project->update([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('latitude', 40.7128)
            ->assertSet('longitude', -74.0060);
    }

    // ==================== UPDATE ALL FIELDS TEST ====================

    public function test_can_update_all_fields(): void
    {
        $newServer = Server::factory()->create(['status' => 'online']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('name', 'Completely New Name')
            ->set('slug', 'completely-new-name')
            ->set('server_id', $newServer->id)
            ->set('repository_url', 'https://gitlab.com/org/new.git')
            ->set('branch', 'production')
            ->set('framework', 'nextjs')
            ->set('php_version', '8.4')
            ->set('node_version', '22')
            ->set('root_directory', '/src')
            ->set('build_command', 'npm run build:prod')
            ->set('start_command', 'npm run start:prod')
            ->set('auto_deploy', true)
            ->set('latitude', 51.5074)
            ->set('longitude', -0.1278)
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('Completely New Name', $freshProject->name);
        $this->assertEquals('completely-new-name', $freshProject->slug);
        $this->assertEquals($newServer->id, $freshProject->server_id);
        $this->assertEquals('https://gitlab.com/org/new.git', $freshProject->repository_url);
        $this->assertEquals('production', $freshProject->branch);
        $this->assertEquals('nextjs', $freshProject->framework);
        $this->assertEquals('8.4', $freshProject->php_version);
        $this->assertEquals('22', $freshProject->node_version);
        $this->assertEquals('/src', $freshProject->root_directory);
        $this->assertTrue($freshProject->auto_deploy);
    }

    // ==================== PROJECT NOTES TESTS ====================

    public function test_can_update_project_notes(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('notes', 'These are my project notes for deployment instructions.')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('These are my project notes for deployment instructions.', $freshProject->notes);
    }

    public function test_loads_existing_notes(): void
    {
        $this->project->update(['notes' => 'Existing deployment notes']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->assertSet('notes', 'Existing deployment notes');
    }

    public function test_notes_are_nullable(): void
    {
        $this->project->update(['notes' => 'Some notes']);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('notes', '')
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertNull($freshProject->notes);
    }

    public function test_notes_max_length_validation(): void
    {
        $longNotes = str_repeat('a', 2001);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('notes', $longNotes)
            ->call('updateProject')
            ->assertHasErrors(['notes' => 'max']);
    }

    public function test_notes_accept_max_2000_characters(): void
    {
        $maxNotes = str_repeat('a', 2000);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('notes', $maxNotes)
            ->call('updateProject')
            ->assertHasNoErrors(['notes']);

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals(2000, strlen($freshProject->notes ?? ''));
    }

    public function test_notes_are_sanitized_for_xss(): void
    {
        $xssNotes = '<script>alert("xss")</script>Deployment notes';

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $this->project])
            ->set('notes', $xssNotes)
            ->call('updateProject')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertStringNotContainsString('<script>', $freshProject->notes ?? '');
        $this->assertStringContainsString('Deployment notes', $freshProject->notes ?? '');
    }
}
