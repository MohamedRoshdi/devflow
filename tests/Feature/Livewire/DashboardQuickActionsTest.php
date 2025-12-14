<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Dashboard\DashboardQuickActions;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\CacheManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

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
            ->test(DashboardQuickActions::class)
            ->assertStatus(200);
    }

    // ==================== CLEAR CACHE TESTS ====================

    public function test_can_clear_all_caches(): void
    {
        $this->mock(CacheManagementService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearAllCachesComplete')
                ->once()
                ->andReturn([
                    'cleared' => ['config', 'route', 'view', 'application'],
                    'failed' => [],
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], '4 caches cleared');
            })
            ->assertDispatched('refresh-dashboard');
    }

    public function test_clear_caches_shows_warning_on_partial_failure(): void
    {
        $this->mock(CacheManagementService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearAllCachesComplete')
                ->once()
                ->andReturn([
                    'cleared' => ['config', 'route'],
                    'failed' => ['redis', 'opcache'],
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'warning' &&
                    str_contains($data['message'], '2 caches cleared') &&
                    str_contains($data['message'], '2 failed');
            });
    }

    public function test_clear_caches_handles_exception(): void
    {
        $this->mock(CacheManagementService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearAllCachesComplete')
                ->once()
                ->andThrow(new \Exception('Cache service unavailable'));
        });

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'Failed to clear caches');
            });
    }

    public function test_clear_caches_dispatches_refresh_dashboard_event(): void
    {
        $this->mock(CacheManagementService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearAllCachesComplete')
                ->andReturn(['cleared' => ['config'], 'failed' => []]);
        });

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('clearAllCaches')
            ->assertDispatched('refresh-dashboard');
    }

    // ==================== DEPLOY ALL TESTS ====================

    public function test_can_deploy_all_active_projects(): void
    {
        Queue::fake();

        Project::factory()->count(3)->create([
            'status' => 'active',
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Deploying 3 projects');
            })
            ->assertDispatched('refresh-stats');

        Queue::assertPushed(DeployProjectJob::class, 3);
        $this->assertDatabaseCount('deployments', 3);
    }

    public function test_deploy_all_includes_running_projects(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);
        Project::factory()->create([
            'status' => 'running',
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll')
            ->assertDispatched('notification', function ($name, $data): bool {
                return str_contains($data['message'], 'Deploying 2 projects');
            });

        Queue::assertPushed(DeployProjectJob::class, 2);
    }

    public function test_deploy_all_excludes_inactive_projects(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);
        Project::factory()->create([
            'status' => 'inactive',
            'server_id' => $this->server->id,
        ]);
        Project::factory()->create([
            'status' => 'failed',
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        Queue::assertPushed(DeployProjectJob::class, 1);
        $this->assertDatabaseCount('deployments', 1);
    }

    public function test_deploy_all_excludes_projects_without_server(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);
        Project::factory()->create([
            'status' => 'active',
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        Queue::assertPushed(DeployProjectJob::class, 1);
    }

    public function test_deploy_all_shows_warning_when_no_projects(): void
    {
        Queue::fake();

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'warning' &&
                    str_contains($data['message'], 'No active projects found');
            });

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('deployments', 0);
    }

    public function test_deploy_all_creates_deployments_with_correct_data(): void
    {
        Queue::fake();

        $project = Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
            'branch' => 'develop',
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'branch' => 'develop',
            'status' => 'pending',
            'triggered_by' => 'manual',
        ]);
    }

    public function test_deploy_all_uses_main_branch_as_default(): void
    {
        Queue::fake();

        $project = Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
            'branch' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'branch' => 'main',
        ]);
    }

    public function test_deploy_all_creates_deployment_with_pending_status(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        $deployment = Deployment::first();
        $this->assertNotNull($deployment);
        $this->assertEquals('pending', $deployment->status);
    }

    public function test_deploy_all_dispatches_refresh_stats_event(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll')
            ->assertDispatched('refresh-stats');
    }

    public function test_deploy_all_does_not_dispatch_refresh_when_no_projects(): void
    {
        Queue::fake();

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll')
            ->assertNotDispatched('refresh-stats');
    }

    // ==================== DEPLOYMENT TRACKING TESTS ====================

    public function test_deployment_is_associated_with_current_user(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        $anotherUser = User::factory()->create();

        Livewire::actingAs($anotherUser)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        $deployment = Deployment::first();
        $this->assertNotNull($deployment);
        $this->assertEquals($anotherUser->id, $deployment->user_id);
    }

    public function test_deployment_has_pending_commit_hash(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class)
            ->call('deployAll');

        $deployment = Deployment::first();
        $this->assertNotNull($deployment);
        $this->assertEquals('pending', $deployment->commit_hash);
    }

    // ==================== MULTIPLE CALLS TESTS ====================

    public function test_can_call_deploy_all_multiple_times(): void
    {
        Queue::fake();

        Project::factory()->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class);

        $component->call('deployAll');
        $component->call('deployAll');

        $this->assertDatabaseCount('deployments', 2);
        Queue::assertPushed(DeployProjectJob::class, 2);
    }

    public function test_can_clear_caches_multiple_times(): void
    {
        $this->mock(CacheManagementService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearAllCachesComplete')
                ->twice()
                ->andReturn(['cleared' => ['config'], 'failed' => []]);
        });

        $component = Livewire::actingAs($this->user)
            ->test(DashboardQuickActions::class);

        $component->call('clearAllCaches')
            ->assertDispatched('notification');

        $component->call('clearAllCaches')
            ->assertDispatched('notification');
    }
}
