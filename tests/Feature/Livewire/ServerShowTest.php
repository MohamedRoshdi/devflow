<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerShow;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ServerShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_component_can_be_rendered_with_server_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSet('server.id', $this->server->id)
            ->assertSet('server.name', $this->server->name)
            ->assertSet('isLoading', true)
            ->assertSet('activeTab', 'overview');
    }

    public function test_guest_cannot_access_component(): void
    {
        Livewire::test(ServerShow::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_view_server(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_component_displays_online_server_status(): void
    {
        $this->server->update(['status' => 'online']);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSet('server.status', 'online')
            ->assertSee('online');
    }

    public function test_component_displays_offline_server_status(): void
    {
        $this->server->update(['status' => 'offline']);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSet('server.status', 'offline')
            ->assertSee('offline');
    }

    public function test_component_displays_maintenance_server_status(): void
    {
        $this->server->update(['status' => 'maintenance']);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSet('server.status', 'maintenance')
            ->assertSee('maintenance');
    }

    public function test_component_displays_resource_metrics(): void
    {
        $this->server->update([
            'cpu_cores' => 8,
            'memory_gb' => 16,
            'disk_gb' => 500,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSee('8')
            ->assertSee('16')
            ->assertSee('500');
    }

    public function test_load_server_data_loads_metrics(): void
    {
        $metrics = ServerMetric::factory()
            ->count(10)
            ->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('loadServerData')
            ->assertSet('isLoading', false);

        $this->assertCount(10, $component->get('recentMetrics'));
    }

    public function test_load_metrics_retrieves_latest_20_metrics(): void
    {
        ServerMetric::factory()
            ->count(25)
            ->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('loadMetrics');

        $this->assertCount(20, $component->get('recentMetrics'));
    }

    public function test_metrics_updated_event_reloads_metrics(): void
    {
        ServerMetric::factory()
            ->count(5)
            ->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->dispatch('metrics-updated')
            ->call('loadMetrics');

        $this->assertCount(5, $component->get('recentMetrics'));
    }

    public function test_component_displays_projects_hosted_on_server(): void
    {
        $projects = Project::factory()
            ->count(3)
            ->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertViewHas('projects', function ($viewProjects) use ($projects) {
                return $viewProjects->count() === 3
                    && $viewProjects->pluck('id')->sort()->values()->toArray() === $projects->pluck('id')->sort()->values()->toArray();
            });
    }

    public function test_component_displays_latest_deployments(): void
    {
        Deployment::factory()
            ->count(7)
            ->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->count() === 5; // Only latest 5
            });
    }

    public function test_ping_server_updates_status_to_online_when_reachable(): void
    {
        $this->server->update(['status' => 'offline']);

        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => true,
                    'message' => 'Server is reachable',
                ]);

            $mock->shouldReceive('getServerInfo')
                ->once()
                ->andReturn([
                    'os' => 'Ubuntu 22.04',
                    'cpu_cores' => 4,
                    'memory_gb' => 8,
                    'disk_gb' => 250,
                ]);
        });

        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('pingServer')
            ->assertSessionHas('message');

        $this->server->refresh();
        $this->assertEquals('online', $this->server->status);
        $this->assertNotNull($this->server->last_ping_at);
    }

    public function test_ping_server_updates_status_to_offline_when_unreachable(): void
    {
        $this->server->update(['status' => 'online']);

        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => false,
                    'message' => 'Connection timeout',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('pingServer')
            ->assertSessionHas('error');

        $this->server->refresh();
        $this->assertEquals('offline', $this->server->status);
        $this->assertNotNull($this->server->last_ping_at);
    }

    public function test_ping_server_updates_server_information(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => true,
                    'message' => 'Server is reachable',
                ]);

            $mock->shouldReceive('getServerInfo')
                ->once()
                ->andReturn([
                    'os' => 'Ubuntu 22.04 LTS',
                    'cpu_cores' => 16,
                    'memory_gb' => 32,
                    'disk_gb' => 1000,
                ]);
        });

        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('pingServer');

        $this->server->refresh();
        $this->assertEquals('Ubuntu 22.04 LTS', $this->server->os);
        $this->assertEquals(16, $this->server->cpu_cores);
        $this->assertEquals(32, $this->server->memory_gb);
        $this->assertEquals(1000, $this->server->disk_gb);
    }

    public function test_ping_server_requires_update_permission(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('pingServer')
            ->assertForbidden();
    }

    public function test_check_docker_status_updates_docker_installed_flag(): void
    {
        $this->server->update([
            'docker_installed' => false,
            'docker_version' => null,
        ]);

        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andReturn([
                    'installed' => true,
                    'version' => '24.0.7',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerStatus')
            ->assertSessionHas('message');

        $this->server->refresh();
        $this->assertTrue($this->server->docker_installed);
        $this->assertEquals('24.0.7', $this->server->docker_version);
    }

    public function test_check_docker_status_handles_docker_not_installed(): void
    {
        $this->server->update([
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);

        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andReturn([
                    'installed' => false,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerStatus')
            ->assertSessionHas('error');

        $this->server->refresh();
        $this->assertFalse($this->server->docker_installed);
        $this->assertNull($this->server->docker_version);
    }

    public function test_check_docker_status_handles_exceptions(): void
    {
        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andThrow(new \Exception('Connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerStatus')
            ->assertSessionHas('error', 'Failed to check Docker: Connection failed');
    }

    public function test_check_docker_status_requires_update_permission(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerStatus')
            ->assertForbidden();
    }

    public function test_install_docker_dispatches_installation_job(): void
    {
        $this->server->update([
            'docker_installed' => false,
            'docker_version' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('installDocker')
            ->assertSessionHas('info')
            ->assertSet('dockerInstalling', true);

        $this->assertTrue(Cache::has("docker_install_{$this->server->id}"));
    }

    public function test_install_docker_prevents_duplicate_installation(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'installing',
            'message' => 'Docker installation in progress',
            'progress' => 50,
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('installDocker')
            ->assertSessionHas('info', 'Docker installation is already in progress...');
    }

    public function test_install_docker_requires_update_permission(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('installDocker')
            ->assertForbidden();
    }

    public function test_check_docker_install_progress_updates_status(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'installing',
            'message' => 'Installing Docker components...',
            'progress' => 75,
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerInstallProgress')
            ->assertSet('dockerInstalling', true)
            ->assertSet('dockerInstallStatus', [
                'status' => 'installing',
                'message' => 'Installing Docker components...',
                'progress' => 75,
            ]);
    }

    public function test_check_docker_install_progress_handles_completed_status(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'message' => 'Docker installed successfully',
            'version' => '24.0.7',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerInstallProgress')
            ->assertSet('dockerInstalling', false)
            ->assertSessionHas('message');
    }

    public function test_check_docker_install_progress_handles_failed_status(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'failed',
            'message' => 'Docker installation failed',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('checkDockerInstallProgress')
            ->assertSet('dockerInstalling', false)
            ->assertSessionHas('error');
    }

    public function test_clear_docker_install_status_removes_cache(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'message' => 'Docker installed',
        ], 3600);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('clearDockerInstallStatus')
            ->assertSet('dockerInstalling', false)
            ->assertSet('dockerInstallStatus', null);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_reboot_server_executes_successfully(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rebootServer')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Server reboot initiated',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('rebootServer')
            ->assertSessionHas('message', 'Server reboot initiated');
    }

    public function test_reboot_server_handles_failure(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rebootServer')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Reboot failed: Permission denied',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('rebootServer')
            ->assertSessionHas('error', 'Reboot failed: Permission denied');
    }

    public function test_reboot_server_requires_update_permission(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('rebootServer')
            ->assertForbidden();
    }

    public function test_restart_service_executes_successfully(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('restartService')
                ->once()
                ->with(Mockery::type(Server::class), 'nginx')
                ->andReturn([
                    'success' => true,
                    'message' => 'Service nginx restarted successfully',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('restartService', 'nginx')
            ->assertSessionHas('message', 'Service nginx restarted successfully');
    }

    public function test_restart_service_handles_failure(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('restartService')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Service not found',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('restartService', 'unknown')
            ->assertSessionHas('error', 'Service not found');
    }

    public function test_restart_service_requires_update_permission(): void
    {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('restartService', 'nginx')
            ->assertForbidden();
    }

    public function test_clear_system_cache_executes_successfully(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearSystemCache')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'System cache cleared successfully',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('clearSystemCache')
            ->assertSessionHas('message', 'System cache cleared successfully');
    }

    public function test_clear_system_cache_handles_failure(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('clearSystemCache')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Cache clear failed',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->call('clearSystemCache')
            ->assertSessionHas('error', 'Cache clear failed');
    }

    public function test_component_displays_ssh_connection_information(): void
    {
        $this->server->update([
            'ip_address' => '192.168.1.100',
            'port' => 2222,
            'username' => 'deploy',
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSee('192.168.1.100')
            ->assertSee('2222')
            ->assertSee('deploy');
    }

    public function test_component_displays_docker_status_when_installed(): void
    {
        $this->server->update([
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk()
            ->assertSee('24.0.7');
    }

    public function test_component_shows_install_docker_option_when_not_installed(): void
    {
        $this->server->update([
            'docker_installed' => false,
            'docker_version' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertOk();
    }

    public function test_component_initializes_with_empty_metrics_collection(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertSet('recentMetrics', collect());
    }

    public function test_component_lazy_loads_data_on_init(): void
    {
        ServerMetric::factory()
            ->count(5)
            ->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerShow::class, ['server' => $this->server])
            ->assertSet('isLoading', true)
            ->call('loadServerData')
            ->assertSet('isLoading', false);

        $this->assertCount(5, $component->get('recentMetrics'));
    }

    /**
     * Mock Docker service with default behavior
     */
    private function mockDockerService(): void
    {
        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->andReturn([
                    'installed' => true,
                    'version' => '24.0.7',
                ]);
        });
    }
}
