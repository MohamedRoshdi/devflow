<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\GitHubRepoPicker;
use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class GitHubRepoPickerTest extends TestCase
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

    // ===== COMPONENT RENDERING =====

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.github-repo-picker');
    }

    public function test_component_renders_without_connection(): void
    {
        $userWithoutConnection = User::factory()->create();
        $this->actingAs($userWithoutConnection);

        Livewire::test(GitHubRepoPicker::class)
            ->assertStatus(200);
    }

    // ===== CONNECTION =====

    public function test_connection_returns_active_connection(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class);

        $connection = $component->viewData('connection');
        $this->assertNotNull($connection);
        $this->assertEquals($this->connection->id, $connection->id);
    }

    public function test_connection_returns_null_without_connection(): void
    {
        $userWithoutConnection = User::factory()->create();
        $this->actingAs($userWithoutConnection);

        $component = Livewire::test(GitHubRepoPicker::class);

        $connection = $component->viewData('connection');
        $this->assertNull($connection);
    }

    public function test_connection_returns_null_with_inactive_connection(): void
    {
        $this->connection->update(['is_active' => false]);
        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class);

        $connection = $component->viewData('connection');
        $this->assertNull($connection);
    }

    // ===== REPOSITORIES =====

    public function test_repositories_shows_user_repos(): void
    {
        GitHubRepository::factory()->count(3)->create([
            'github_connection_id' => $this->connection->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class);

        $repositories = $component->viewData('repositories');
        $this->assertCount(3, $repositories);
    }

    public function test_repositories_filtered_by_search(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'laravel-app',
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'react-frontend',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('search', 'laravel');

        $repositories = $component->viewData('repositories');
        $this->assertCount(1, $repositories);
        $this->assertEquals('laravel-app', $repositories->first()->name);
    }

    public function test_repositories_filtered_by_visibility_public(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('visibilityFilter', 'public');

        $repositories = $component->viewData('repositories');
        $this->assertCount(1, $repositories);
        $this->assertFalse($repositories->first()->private);
    }

    public function test_repositories_filtered_by_visibility_private(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('visibilityFilter', 'private');

        $repositories = $component->viewData('repositories');
        $this->assertCount(1, $repositories);
        $this->assertTrue($repositories->first()->private);
    }

    public function test_repositories_ordered_by_stars(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'stars_count' => 10,
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'stars_count' => 100,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class);

        $repositories = $component->viewData('repositories');
        $this->assertEquals(100, $repositories->first()->stars_count);
    }

    public function test_repositories_limited_to_50(): void
    {
        GitHubRepository::factory()->count(60)->create([
            'github_connection_id' => $this->connection->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class);

        $repositories = $component->viewData('repositories');
        $this->assertCount(50, $repositories);
    }

    public function test_repositories_empty_without_connection(): void
    {
        $userWithoutConnection = User::factory()->create();
        $this->actingAs($userWithoutConnection);

        $component = Livewire::test(GitHubRepoPicker::class);

        $repositories = $component->viewData('repositories');
        $this->assertCount(0, $repositories);
    }

    // ===== OPEN/CLOSE MODAL =====

    public function test_can_open_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSet('step', 'select-repo');
    }

    public function test_open_resets_selection(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', 123)
            ->set('selectedBranch', 'main')
            ->call('open')
            ->assertSet('selectedRepoId', 0)
            ->assertSet('selectedBranch', '');
    }

    public function test_open_without_connection_shows_error(): void
    {
        $userWithoutConnection = User::factory()->create();
        $this->actingAs($userWithoutConnection);

        Livewire::test(GitHubRepoPicker::class)
            ->call('open')
            ->assertDispatched('notification', fn (string $type): bool => $type === 'error')
            ->assertSet('isOpen', false);
    }

    public function test_can_close_modal(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('selectedRepoId', 0)
            ->assertSet('selectedBranch', '');
    }

    // ===== SELECT REPOSITORY =====

    public function test_can_select_repository(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'default_branch' => 'main',
        ]);

        $this->actingAs($this->user);

        $this->mock(GitHubService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listBranches')
                ->once()
                ->andReturn([
                    ['name' => 'main', 'protected' => false],
                    ['name' => 'develop', 'protected' => false],
                ]);
        });

        Livewire::test(GitHubRepoPicker::class)
            ->call('selectRepository', $repo->id)
            ->assertSet('selectedRepoId', $repo->id)
            ->assertSet('selectedBranch', 'main')
            ->assertSet('step', 'select-branch');
    }

    public function test_select_repository_loads_branches(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(GitHubService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listBranches')
                ->once()
                ->andReturn([
                    ['name' => 'main', 'protected' => true],
                    ['name' => 'develop', 'protected' => false],
                    ['name' => 'feature/test', 'protected' => false],
                ]);
        });

        $component = Livewire::test(GitHubRepoPicker::class)
            ->call('selectRepository', $repo->id);

        $branches = $component->get('branches');
        $this->assertCount(3, $branches);
        $this->assertEquals('main', $branches[0]['name']);
        $this->assertTrue($branches[0]['protected']);
    }

    public function test_select_repository_handles_branch_loading_error(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);

        $this->actingAs($this->user);

        $this->mock(GitHubService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listBranches')
                ->once()
                ->andThrow(new \Exception('API rate limit exceeded'));
        });

        Livewire::test(GitHubRepoPicker::class)
            ->call('selectRepository', $repo->id)
            ->assertDispatched('notification', fn (string $type): bool => $type === 'error')
            ->assertSet('loadingBranches', false);
    }

    // ===== BACK TO REPO SELECTION =====

    public function test_can_go_back_to_repo_selection(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('step', 'select-branch')
            ->set('selectedRepoId', 123)
            ->set('selectedBranch', 'main')
            ->call('backToRepoSelection')
            ->assertSet('step', 'select-repo')
            ->assertSet('selectedRepoId', 0)
            ->assertSet('selectedBranch', '');
    }

    // ===== CONFIRM SELECTION =====

    public function test_can_confirm_selection(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'clone_url' => 'https://github.com/test/repo.git',
        ]);

        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedBranch', 'main')
            ->call('confirmSelection')
            ->assertSet('repositoryUrl', 'https://github.com/test/repo.git')
            ->assertSet('branch', 'main')
            ->assertDispatched('repository-selected')
            ->assertDispatched('notification', fn (string $type): bool => $type === 'success')
            ->assertSet('isOpen', false);
    }

    public function test_confirm_without_repo_shows_error(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', 0)
            ->set('selectedBranch', 'main')
            ->call('confirmSelection')
            ->assertDispatched('notification', fn (string $type): bool => $type === 'error');
    }

    public function test_confirm_without_branch_shows_error(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedBranch', '')
            ->call('confirmSelection')
            ->assertDispatched('notification', fn (string $type): bool => $type === 'error');
    }

    public function test_confirm_with_invalid_repo_shows_error(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', 99999)
            ->set('selectedBranch', 'main')
            ->call('confirmSelection')
            ->assertDispatched('notification', fn (string $type): bool => $type === 'error');
    }

    // ===== DEFAULT VALUES =====

    public function test_default_property_values(): void
    {
        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->assertSet('repositoryUrl', '')
            ->assertSet('branch', '')
            ->assertSet('search', '')
            ->assertSet('visibilityFilter', 'all')
            ->assertSet('selectedRepoId', 0)
            ->assertSet('isOpen', false)
            ->assertSet('step', 'select-repo')
            ->assertSet('loadingBranches', false);
    }

    // ===== SEARCH FUNCTIONALITY =====

    public function test_search_filters_by_description(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'my-app',
            'description' => 'A Laravel application for testing',
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'another-app',
            'description' => 'A React frontend',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('search', 'Laravel');

        $repositories = $component->viewData('repositories');
        $this->assertCount(1, $repositories);
    }

    public function test_search_filters_by_full_name(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'full_name' => 'organization/my-project',
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'full_name' => 'user/other-project',
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('search', 'organization');

        $repositories = $component->viewData('repositories');
        $this->assertCount(1, $repositories);
    }

    // ===== EVENTS =====

    public function test_repository_selected_event_contains_data(): void
    {
        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'name' => 'test-repo',
            'clone_url' => 'https://github.com/test/repo.git',
        ]);

        $this->actingAs($this->user);

        Livewire::test(GitHubRepoPicker::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedBranch', 'develop')
            ->call('confirmSelection')
            ->assertDispatched('repository-selected', function (array $data) {
                return isset($data['repository']) &&
                    isset($data['branch']) &&
                    $data['branch'] === 'develop';
            });
    }

    // ===== VISIBILITY FILTER =====

    public function test_visibility_filter_all_shows_all_repos(): void
    {
        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => true,
        ]);

        GitHubRepository::factory()->create([
            'github_connection_id' => $this->connection->id,
            'private' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(GitHubRepoPicker::class)
            ->set('visibilityFilter', 'all');

        $repositories = $component->viewData('repositories');
        $this->assertCount(2, $repositories);
    }
}
