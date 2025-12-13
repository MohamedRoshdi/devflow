<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\DeploymentList;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for DeploymentList Livewire component
 *
 * Tests component rendering, filtering, search, pagination, authorization,
 * and statistics display functionality.
 */
class DeploymentListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Project $userProject;

    private Project $otherProject;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create servers
        $this->server = Server::factory()->create();

        // Create projects owned by different users
        $this->userProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'User Project',
        ]);

        $this->otherProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
            'name' => 'Other User Project',
        ]);
    }

    /**
     * Test: Component can be rendered by authenticated user
     */
    public function test_component_can_be_rendered_by_authenticated_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk()
            ->assertViewIs('livewire.deployments.deployment-list')
            ->assertViewHas('deployments')
            ->assertViewHas('projects')
            ->assertViewHas('stats');
    }

    /**
     * Test: Guest cannot access component
     *
     * Note: This test is skipped because the DeploymentList component
     * assumes authentication and will throw an error before Livewire
     * can handle the unauthorized check. The route is protected by
     * auth middleware which prevents guest access at the route level.
     */
    public function test_guest_cannot_access_deployment_list(): void
    {
        $this->get(route('deployments.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * Test: Component displays deployments for user's projects
     */
    public function test_component_displays_deployments_for_user_projects(): void
    {
        // Create deployments for user's project
        $deployment1 = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix authentication bug',
            'status' => 'success',
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Add new feature',
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk()
            ->assertSee($deployment1->commit_message)
            ->assertSee($deployment2->commit_message);
    }

    /**
     * Test: User can only see deployments from their own projects
     */
    public function test_user_can_only_see_own_project_deployments(): void
    {
        // Create deployment for user's project
        $userDeployment = Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'User deployment message',
        ]);

        // Create deployment for other user's project
        $otherDeployment = Deployment::factory()->create([
            'project_id' => $this->otherProject->id,
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Other user deployment message',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk()
            ->assertSee($userDeployment->commit_message)
            ->assertDontSee($otherDeployment->commit_message);
    }

    /**
     * Test: Filter deployments by status - success
     */
    public function test_filter_deployments_by_status_success(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_message' => 'Successful deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'commit_message' => 'Failed deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->assertSee('Successful deployment')
            ->assertDontSee('Failed deployment');
    }

    /**
     * Test: Filter deployments by status - failed
     */
    public function test_filter_deployments_by_status_failed(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_message' => 'Successful deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'commit_message' => 'Failed deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'failed')
            ->assertSee('Failed deployment')
            ->assertDontSee('Successful deployment');
    }

    /**
     * Test: Filter deployments by status - running
     */
    public function test_filter_deployments_by_status_running(): void
    {
        Deployment::factory()->running()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Running deployment',
        ]);

        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Completed deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'running')
            ->assertSee('Running deployment')
            ->assertDontSee('Completed deployment');
    }

    /**
     * Test: Filter deployments by status - pending
     */
    public function test_filter_deployments_by_status_pending(): void
    {
        Deployment::factory()->pending()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Pending deployment',
        ]);

        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Completed deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'pending')
            ->assertSee('Pending deployment')
            ->assertDontSee('Completed deployment');
    }

    /**
     * Test: Filter deployments by project
     */
    public function test_filter_deployments_by_project(): void
    {
        // Create second project for the same user
        $userProject2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Second Project',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'First project deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $userProject2->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Second project deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('projectFilter', (string) $this->userProject->id)
            ->assertSee('First project deployment')
            ->assertDontSee('Second project deployment');
    }

    /**
     * Test: Search deployments by commit message
     */
    public function test_search_deployments_by_commit_message(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix authentication bug',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Add new feature',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'authentication')
            ->assertSee('Fix authentication bug')
            ->assertDontSee('Add new feature');
    }

    /**
     * Test: Search deployments by branch name
     */
    public function test_search_deployments_by_branch(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'feature/authentication',
            'commit_message' => 'Feature branch deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
            'commit_message' => 'Main branch deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'feature')
            ->assertSee('Feature branch deployment')
            ->assertDontSee('Main branch deployment');
    }

    /**
     * Test: Search deployments by project name
     */
    public function test_search_deployments_by_project_name(): void
    {
        // Create projects with specific names
        $projectAlpha = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Alpha Project',
        ]);

        $projectBeta = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Beta Project',
        ]);

        Deployment::factory()->create([
            'project_id' => $projectAlpha->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Alpha deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $projectBeta->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Beta deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha deployment')
            ->assertDontSee('Beta deployment');
    }

    /**
     * Test: Pagination works correctly
     */
    public function test_component_paginates_deployments(): void
    {
        // Create 30 deployments
        Deployment::factory()->count(30)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk()
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->count() === 15; // Default perPage is 15
            });
    }

    /**
     * Test: Change per page value
     */
    public function test_change_per_page_value(): void
    {
        // Create 30 deployments
        Deployment::factory()->count(30)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 10)
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->count() === 10;
            });
    }

    /**
     * Test: Per page value is validated to be within range
     */
    public function test_per_page_value_is_validated(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 100) // Too high
            ->assertSet('perPage', 15); // Should reset to default

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 2) // Too low
            ->assertSet('perPage', 15); // Should reset to default
    }

    /**
     * Test: Valid per page values are accepted
     */
    public function test_valid_per_page_values_are_accepted(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 25)
            ->assertSet('perPage', 25);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 5)
            ->assertSet('perPage', 5);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 50)
            ->assertSet('perPage', 50);
    }

    /**
     * Test: Statistics display total deployments count
     */
    public function test_statistics_display_total_deployments_count(): void
    {
        // Create various deployments
        Deployment::factory()->count(5)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 5;
            });
    }

    /**
     * Test: Statistics display success count
     */
    public function test_statistics_display_success_count(): void
    {
        Deployment::factory()->success()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->failed()->count(2)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['success'] === 3 && $stats['total'] === 5;
            });
    }

    /**
     * Test: Statistics display failed count
     */
    public function test_statistics_display_failed_count(): void
    {
        Deployment::factory()->success()->count(2)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->failed()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['failed'] === 3 && $stats['total'] === 5;
            });
    }

    /**
     * Test: Statistics display running count
     */
    public function test_statistics_display_running_count(): void
    {
        Deployment::factory()->running()->count(2)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->success()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['running'] === 2 && $stats['total'] === 5;
            });
    }

    /**
     * Test: Statistics only include user's project deployments
     */
    public function test_statistics_only_include_user_project_deployments(): void
    {
        // User's deployments
        Deployment::factory()->success()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Other user's deployments (should not be counted)
        Deployment::factory()->success()->count(5)->create([
            'project_id' => $this->otherProject->id,
            'user_id' => $this->otherUser->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 3 && $stats['success'] === 3;
            });
    }

    /**
     * Test: Pagination resets when status filter changes
     */
    public function test_pagination_resets_when_status_filter_changes(): void
    {
        Deployment::factory()->count(30)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->call('gotoPage', 2)
            ->set('statusFilter', 'success')
            ->assertSet('paginators.page', 1);
    }

    /**
     * Test: Pagination resets when project filter changes
     */
    public function test_pagination_resets_when_project_filter_changes(): void
    {
        Deployment::factory()->count(30)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->call('gotoPage', 2)
            ->set('projectFilter', (string) $this->userProject->id)
            ->assertSet('paginators.page', 1);
    }

    /**
     * Test: Pagination resets when search term changes
     */
    public function test_pagination_resets_when_search_changes(): void
    {
        Deployment::factory()->count(30)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->call('gotoPage', 2)
            ->set('search', 'test')
            ->assertSet('paginators.page', 1);
    }

    /**
     * Test: Component eager loads relationships
     */
    public function test_component_eager_loads_relationships(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) {
                $deployment = $deployments->first();

                return $deployment !== null &&
                    $deployment->relationLoaded('project') &&
                    $deployment->relationLoaded('server') &&
                    $deployment->relationLoaded('user');
            });
    }

    /**
     * Test: Projects list only includes user's projects
     */
    public function test_projects_list_only_includes_user_projects(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() === 1 && $projects->first()->id === $this->userProject->id;
            });
    }

    /**
     * Test: Empty state when no deployments exist
     */
    public function test_empty_state_when_no_deployments_exist(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->isEmpty();
            })
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 0 &&
                    $stats['success'] === 0 &&
                    $stats['failed'] === 0 &&
                    $stats['running'] === 0;
            });
    }

    /**
     * Test: Combined filters work together
     */
    public function test_combined_filters_work_together(): void
    {
        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Target deployment',
        ]);

        Deployment::factory()->failed()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Should not appear',
        ]);

        Deployment::factory()->success()->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Different message',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->set('search', 'Target')
            ->assertSee('Target deployment')
            ->assertDontSee('Should not appear')
            ->assertDontSee('Different message');
    }

    /**
     * Test: URL parameters are persisted
     */
    public function test_url_parameters_are_persisted(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->set('search', 'test')
            ->set('perPage', 25);

        $component->assertSet('statusFilter', 'success')
            ->assertSet('search', 'test')
            ->assertSet('perPage', 25);
    }

    /**
     * Test: Cache is used for statistics
     */
    public function test_cache_is_used_for_statistics(): void
    {
        Deployment::factory()->success()->count(3)->create([
            'project_id' => $this->userProject->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // First render should cache the stats
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 3;
            });

        // Cache key should exist
        $cacheKey = 'deployment_stats_user_'.$this->user->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test: Cache is used for user project IDs
     */
    public function test_cache_is_used_for_user_project_ids(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk();

        // Cache key should exist
        $cacheKey = 'user_project_ids_'.$this->user->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test: Cache is used for projects dropdown
     */
    public function test_cache_is_used_for_projects_dropdown(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertOk();

        // Cache key should exist
        $cacheKey = 'projects_dropdown_list_user_'.$this->user->id;
        $this->assertTrue(Cache::has($cacheKey));
    }
}
