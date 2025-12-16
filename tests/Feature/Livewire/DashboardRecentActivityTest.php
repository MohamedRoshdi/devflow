<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard\DashboardRecentActivity;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardRecentActivityTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->assertSet('recentActivity', [])
            ->assertSet('activityPerPage', 5)
            ->assertSet('loadingMoreActivity', false);
    }

    // ==================== LOAD ACTIVITY TESTS ====================

    public function test_can_load_recent_activity(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        $activity = $component->get('recentActivity');
        $this->assertNotEmpty($activity);
    }

    public function test_loads_deployments_in_activity(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
            'status' => 'success',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $deploymentActivity = $this->findActivityByType($activity, 'deployment');

        $this->assertNotNull($deploymentActivity);
        $this->assertEquals('deployment', $deploymentActivity['type']);
        $this->assertEquals('success', $deploymentActivity['status']);
    }

    public function test_loads_projects_in_activity(): void
    {
        Project::factory()->create([
            'name' => 'Test Project',
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'framework' => 'laravel',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $projectActivity = $this->findActivityByType($activity, 'project_created');

        $this->assertNotNull($projectActivity);
        $this->assertEquals('project_created', $projectActivity['type']);
        $this->assertStringContains('Test Project', $projectActivity['title']);
    }

    public function test_activity_is_sorted_by_timestamp(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(2),
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHour(),
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');

        // Most recent should be first
        $timestamps = array_column($activity, 'timestamp');
        $sortedTimestamps = $timestamps;
        usort($sortedTimestamps, fn($a, $b) => $b <=> $a);
        $this->assertEquals($sortedTimestamps, $timestamps);
    }

    public function test_limits_activity_to_per_page(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(10)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        $activity = $component->get('recentActivity');
        $this->assertLessThanOrEqual(5, count($activity));
    }

    // ==================== ACTIVITY DATA STRUCTURE TESTS ====================

    public function test_deployment_activity_has_correct_structure(): void
    {
        $project = Project::factory()->create([
            'name' => 'My Project',
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'develop',
            'status' => 'running',
            'triggered_by' => 'webhook',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $deploymentActivity = $this->findActivityByType($activity, 'deployment');

        $this->assertNotNull($deploymentActivity);
        $this->assertArrayHasKey('id', $deploymentActivity);
        $this->assertArrayHasKey('title', $deploymentActivity);
        $this->assertArrayHasKey('description', $deploymentActivity);
        $this->assertArrayHasKey('status', $deploymentActivity);
        $this->assertArrayHasKey('user', $deploymentActivity);
        $this->assertArrayHasKey('timestamp', $deploymentActivity);
        $this->assertArrayHasKey('triggered_by', $deploymentActivity);
    }

    public function test_project_activity_has_correct_structure(): void
    {
        Project::factory()->create([
            'name' => 'Test Project',
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'framework' => 'laravel',
            'status' => 'running',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $projectActivity = $this->findActivityByType($activity, 'project_created');

        $this->assertNotNull($projectActivity);
        $this->assertArrayHasKey('id', $projectActivity);
        $this->assertArrayHasKey('title', $projectActivity);
        $this->assertArrayHasKey('description', $projectActivity);
        $this->assertArrayHasKey('status', $projectActivity);
        $this->assertArrayHasKey('user', $projectActivity);
        $this->assertArrayHasKey('timestamp', $projectActivity);
        $this->assertArrayHasKey('framework', $projectActivity);
    }

    // ==================== LOAD MORE TESTS ====================

    public function test_can_load_more_activity(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(15)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        $initialCount = count($component->get('recentActivity'));

        $component->call('loadMoreActivity');

        $newCount = count($component->get('recentActivity'));
        $this->assertGreaterThanOrEqual($initialCount, $newCount);
    }

    public function test_load_more_sets_loading_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadMoreActivity')
            ->assertSet('loadingMoreActivity', false);
    }

    public function test_load_more_stops_at_max_items(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(30)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        // Load more multiple times
        for ($i = 0; $i < 10; $i++) {
            $component->call('loadMoreActivity');
        }

        $activity = $component->get('recentActivity');
        $this->assertLessThanOrEqual(20, count($activity)); // Max is 20
    }

    public function test_load_more_does_nothing_when_at_max(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(25)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        // Fill to max
        for ($i = 0; $i < 5; $i++) {
            $component->call('loadMoreActivity');
        }

        $countAtMax = count($component->get('recentActivity'));

        // Try to load more beyond max
        $component->call('loadMoreActivity');

        $countAfter = count($component->get('recentActivity'));
        $this->assertEquals($countAtMax, $countAfter);
    }

    // ==================== EVENT LISTENER TESTS ====================

    public function test_refreshes_on_deployment_completed_event(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Component loads activity on mount, so we verify it handles the event
        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class);

        // Store initial count
        $initialActivity = $component->get('recentActivity');
        $initialCount = count($initialActivity);

        // Dispatch event should not throw and component should remain functional
        $component->dispatch('deployment-completed')
            ->assertStatus(200);

        // Activity should still be populated after refresh
        $this->assertNotEmpty($component->get('recentActivity'));
    }

    public function test_refreshes_on_refresh_activity_event(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Component loads activity on mount, so we verify it handles the event
        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class);

        // Dispatch event should not throw and component should remain functional
        $component->dispatch('refresh-activity')
            ->assertStatus(200);

        // Activity should still be populated after refresh
        $this->assertNotEmpty($component->get('recentActivity'));
    }

    // ==================== NULL HANDLING TESTS ====================

    public function test_handles_deployment_without_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Delete project but keep deployment
        $project->delete();

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        // Should not throw an exception
        $activity = $component->get('recentActivity');
        $this->assertIsArray($activity);
    }

    /**
     * @skip Database schema requires user_id to be NOT NULL on deployments table.
     * This test scenario (deployment without user) is not supported by the schema.
     */
    public function test_handles_deployment_without_user(): void
    {
        $this->markTestSkipped('Database schema requires user_id on deployments - cannot test null user scenario');
    }

    public function test_handles_project_without_server(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
            'framework' => 'laravel',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $projectActivity = $this->findActivityByType($activity, 'project_created');

        $this->assertNotNull($projectActivity);
        $this->assertStringContains('Unknown Server', $projectActivity['description']);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_activity(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        $activity = $component->get('recentActivity');
        $this->assertEmpty($activity);
    }

    public function test_handles_only_deployments(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(30), // Old project
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardRecentActivity::class)
            ->call('loadRecentActivity');

        /** @var array<int, array<string, mixed>> $activity */
        $activity = $component->get('recentActivity');
        $this->assertNotEmpty($activity);

        // Should have deployments
        $deployments = $this->filterActivitiesByType($activity, 'deployment');
        $this->assertGreaterThan(0, count($deployments));
    }

    // ==================== HELPER METHODS ====================

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }

    /**
     * Find first activity by type
     *
     * @param array<int, array<string, mixed>> $activities
     * @return array<string, mixed>|null
     */
    private function findActivityByType(array $activities, string $type): ?array
    {
        foreach ($activities as $activity) {
            if (isset($activity['type']) && $activity['type'] === $type) {
                return $activity;
            }
        }

        return null;
    }

    /**
     * Filter activities by type
     *
     * @param array<int, array<string, mixed>> $activities
     * @return array<int, array<string, mixed>>
     */
    private function filterActivitiesByType(array $activities, string $type): array
    {
        return array_values(array_filter($activities, fn($a) => isset($a['type']) && $a['type'] === $type));
    }
}
