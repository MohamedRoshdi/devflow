<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\GitHubSettings;
use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class GitHubSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private GitHubConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    }

    private function mockGitHubService(int $syncCount = 5): void
    {
        $this->mock(GitHubService::class, function (MockInterface $mock) use ($syncCount): void {
            $mock->shouldReceive('syncRepositories')->andReturn($syncCount);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->assertStatus(200);
    }

    public function test_shows_connection_when_exists(): void
    {
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $connection = $component->viewData('connection');
        $this->assertNotNull($connection);
        $this->assertEquals($this->connection->id, $connection->id);
    }

    public function test_returns_null_connection_when_not_connected(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $this->assertNull($component->viewData('connection'));
    }

    public function test_loads_repositories_for_connection(): void
    {
        GitHubRepository::factory()->count(3)->create([
            'github_connection_id' => $this->connection->id,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $repos = $component->viewData('repositories');
        $this->assertCount(3, $repos);
    }

    // ==================== FILTER TESTS ====================

    public function test_can_search_repositories(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'laravel-project',
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'vue-app',
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('search', 'laravel');

        $repos = $component->viewData('repositories');
        $this->assertCount(1, $repos);
        $this->assertEquals('laravel-project', $repos->first()->name);
    }

    public function test_search_includes_description(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'my-project',
            'description' => 'A Laravel application',
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('search', 'Laravel');

        $repos = $component->viewData('repositories');
        $this->assertCount(1, $repos);
    }

    public function test_can_filter_by_visibility_public(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('visibilityFilter', 'public');

        $repos = $component->viewData('repositories');
        $this->assertCount(1, $repos);
        $this->assertFalse($repos->first()->private);
    }

    public function test_can_filter_by_visibility_private(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('visibilityFilter', 'private');

        $repos = $component->viewData('repositories');
        $this->assertCount(1, $repos);
        $this->assertTrue($repos->first()->private);
    }

    public function test_can_filter_by_language(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'PHP',
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'JavaScript',
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('languageFilter', 'PHP');

        $repos = $component->viewData('repositories');
        $this->assertCount(1, $repos);
        $this->assertEquals('PHP', $repos->first()->language);
    }

    public function test_all_filter_shows_all_repositories(): void
    {
        GitHubRepository::factory()->count(5)->create([
            'github_connection_id' => $this->connection->id,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('visibilityFilter', 'all')
            ->set('languageFilter', 'all');

        $repos = $component->viewData('repositories');
        $this->assertCount(5, $repos);
    }

    // ==================== STATS TESTS ====================

    public function test_stats_shows_correct_counts(): void
    {
        GitHubRepository::factory()->count(3)->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);
        GitHubRepository::factory()->count(2)->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['public']);
        $this->assertEquals(2, $stats['private']);
    }

    public function test_stats_shows_linked_count(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);

        GitHubRepository::factory()->count(3)->create([
            'github_connection_id' => $this->connection->id,
            'project_id' => null,
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'project_id' => $project->id,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(1, $stats['linked']);
    }

    public function test_stats_returns_zeros_without_connection(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['public']);
        $this->assertEquals(0, $stats['private']);
        $this->assertEquals(0, $stats['linked']);
    }

    // ==================== AVAILABLE LANGUAGES TESTS ====================

    public function test_available_languages_returns_distinct_languages(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'PHP',
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'JavaScript',
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'PHP',
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $languages = $component->viewData('availableLanguages');
        $this->assertCount(2, $languages);
        $this->assertContains('PHP', $languages);
        $this->assertContains('JavaScript', $languages);
    }

    public function test_available_languages_excludes_null(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => 'PHP',
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'language' => null,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $languages = $component->viewData('availableLanguages');
        $this->assertCount(1, $languages);
    }

    public function test_available_languages_returns_empty_without_connection(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $languages = $component->viewData('availableLanguages');
        $this->assertEmpty($languages);
    }

    // ==================== SYNC TESTS ====================

    public function test_can_sync_repositories(): void
    {
        $this->mockGitHubService(10);

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('syncRepositories')
            ->assertSet('syncing', false)
            ->assertSet('syncProgress', 100)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], '10');
            });
    }

    public function test_sync_shows_error_without_connection(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('syncRepositories')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_sync_handles_exception(): void
    {
        $this->mock(GitHubService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncRepositories')
                ->andThrow(new \Exception('API rate limit exceeded'));
        });

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('syncRepositories')
            ->assertSet('syncing', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'rate limit');
            });
    }

    // ==================== LINK MODAL TESTS ====================

    public function test_can_open_link_modal(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('openLinkModal', $repo->id)
            ->assertSet('showLinkModal', true)
            ->assertSet('selectedRepoId', $repo->id)
            ->assertSet('selectedProjectId', 0);
    }

    // ==================== LINK TO PROJECT TESTS ====================

    public function test_can_link_repository_to_project(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
            'repository_url' => null,
        ]);
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'clone_url' => 'https://github.com/user/repo.git',
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedProjectId', $project->id)
            ->call('linkToProject')
            ->assertSet('showLinkModal', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $freshRepo = $repo->fresh();
        $this->assertNotNull($freshRepo);
        $this->assertEquals($project->id, $freshRepo->project_id);

        $freshProject = $project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('https://github.com/user/repo.git', $freshProject->repository_url);
    }

    public function test_link_requires_project_selection(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedProjectId', 0)
            ->call('linkToProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_link_requires_repo_selection(): void
    {
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', 0)
            ->set('selectedProjectId', 1)
            ->call('linkToProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_link_unlinks_previous_repo(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $oldRepo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'project_id' => $project->id,
        ]);
        $newRepo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', $newRepo->id)
            ->set('selectedProjectId', $project->id)
            ->call('linkToProject');

        $freshOldRepo = $oldRepo->fresh();
        $this->assertNotNull($freshOldRepo);
        $this->assertNull($freshOldRepo->project_id);

        $freshNewRepo = $newRepo->fresh();
        $this->assertNotNull($freshNewRepo);
        $this->assertEquals($project->id, $freshNewRepo->project_id);
    }

    public function test_link_does_not_overwrite_existing_repository_url(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
            'repository_url' => 'https://existing.url/repo.git',
        ]);
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'clone_url' => 'https://github.com/user/repo.git',
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedProjectId', $project->id)
            ->call('linkToProject');

        $freshProject = $project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('https://existing.url/repo.git', $freshProject->repository_url);
    }

    // ==================== UNLINK TESTS ====================

    public function test_can_unlink_repository(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'project_id' => $project->id,
        ]);
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('unlinkProject', $repo->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'unlinked');
            });

        $freshRepo = $repo->fresh();
        $this->assertNotNull($freshRepo);
        $this->assertNull($freshRepo->project_id);
    }

    public function test_unlink_handles_invalid_repo(): void
    {
        $this->mockGitHubService();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('unlinkProject', 99999);
    }

    // ==================== DISCONNECT TESTS ====================

    public function test_disconnect_dispatches_confirm_event(): void
    {
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->call('disconnect')
            ->assertDispatched('confirm-disconnect');
    }

    public function test_confirm_disconnect_redirects(): void
    {
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->dispatch('disconnect-confirmed')
            ->assertRedirect(route('github.disconnect'));
    }

    // ==================== PROJECTS LIST TESTS ====================

    public function test_shows_available_projects(): void
    {
        $server = Server::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $projects = $component->viewData('projects');
        $this->assertCount(3, $projects);
    }

    public function test_projects_are_ordered_by_name(): void
    {
        $server = Server::factory()->create();
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
            'name' => 'Zebra Project',
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
            'name' => 'Alpha Project',
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $projects = $component->viewData('projects');
        $this->assertEquals('Alpha Project', $projects->first()->name);
    }

    // ==================== ORDERING TESTS ====================

    public function test_repositories_ordered_by_stars_desc(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'stars_count' => 10,
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'stars_count' => 100,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $repos = $component->viewData('repositories');
        $this->assertEquals(100, $repos->first()->stars_count);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        $this->mockGitHubService();

        Livewire::actingAs($this->user)
            ->test(GitHubSettings::class)
            ->assertSet('search', '')
            ->assertSet('visibilityFilter', 'all')
            ->assertSet('languageFilter', 'all')
            ->assertSet('syncing', false)
            ->assertSet('syncProgress', 0)
            ->assertSet('selectedRepoId', 0)
            ->assertSet('selectedProjectId', 0)
            ->assertSet('showLinkModal', false);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_empty_repositories_without_connection(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $repos = $component->viewData('repositories');
        $this->assertCount(0, $repos);
    }

    public function test_repositories_includes_project_relation(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $server->id,
        ]);
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'project_id' => $project->id,
        ]);
        $this->mockGitHubService();

        $component = Livewire::actingAs($this->user)
            ->test(GitHubSettings::class);

        $repos = $component->viewData('repositories');
        $this->assertNotNull($repos->first()->project);
        $this->assertEquals($project->id, $repos->first()->project->id);
    }
}
