<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Livewire\Docker\DockerDashboard;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Feature tests for DockerDashboard Livewire component
 *
 * Tests component rendering, Docker information display, container management,
 * resource usage display, image/volume/network listing, authorization,
 * and error handling for Docker API failures.
 * */
#[CoversClass(\App\Livewire\Docker\DockerDashboard::class)]
class DockerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    private DockerService|MockInterface $dockerService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test server with Docker installed
        $this->server = Server::factory()->withDocker()->online()->create([
            'name' => 'Test Docker Server',
        ]);

        // Create mock DockerService
        $this->dockerService = Mockery::mock(DockerService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Component renders successfully for authenticated user
     */
    public function test_component_renders_for_authenticated_user(): void
    {
        $this->mockDockerServiceInitialData();

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->assertOk()
            ->assertViewIs('livewire.docker.docker-dashboard')
            ->assertSet('server.id', $this->server->id)
            ->assertSet('activeTab', 'overview')
            ->assertSet('loading', false);
    }

    /**
     * Test: Guest cannot access component
     */
    public function test_guest_cannot_access_docker_dashboard(): void
    {
        $this->get(route('servers.docker', $this->server))
            ->assertRedirect(route('login'));
    }

    /**
     * Test: Component displays Docker system information
     */
    public function test_component_displays_docker_system_info(): void
    {
        $dockerInfo = [
            'success' => true,
            'info' => [
                'ServerVersion' => '24.0.7',
                'OperatingSystem' => 'Ubuntu 22.04 LTS',
                'Architecture' => 'x86_64',
                'NCPU' => 4,
                'MemTotal' => 8589934592,
                'Containers' => 5,
                'ContainersRunning' => 3,
                'ContainersStopped' => 2,
                'Images' => 10,
            ],
        ];

        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->with($this->server)
            ->once()
            ->andReturn($dockerInfo);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->once()
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->once()
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->once()
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->once()
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('dockerInfo', $dockerInfo['info'])
            ->assertSet('isLoading', false);
    }

    /**
     * Test: Component displays disk usage information
     */
    public function test_component_displays_disk_usage(): void
    {
        $diskUsage = [
            'success' => true,
            'usage' => [
                [
                    'Type' => 'Images',
                    'TotalCount' => 10,
                    'Active' => 5,
                    'Size' => 2147483648,
                    'Reclaimable' => 1073741824,
                ],
                [
                    'Type' => 'Containers',
                    'TotalCount' => 5,
                    'Active' => 3,
                    'Size' => 536870912,
                    'Reclaimable' => 268435456,
                ],
                [
                    'Type' => 'Volumes',
                    'TotalCount' => 3,
                    'Active' => 2,
                    'Size' => 1073741824,
                    'Reclaimable' => 357913941,
                ],
            ],
        ];

        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->once()
            ->andReturn(['success' => true, 'info' => []]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->with($this->server)
            ->once()
            ->andReturn($diskUsage);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->once()
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->once()
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->once()
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('diskUsage', $diskUsage['usage']);
    }

    /**
     * Test: Component displays volumes list
     */
    public function test_component_displays_volumes_list(): void
    {
        $volumes = [
            'success' => true,
            'volumes' => [
                [
                    'Name' => 'project_data',
                    'Driver' => 'local',
                    'Mountpoint' => '/var/lib/docker/volumes/project_data/_data',
                ],
                [
                    'Name' => 'mysql_data',
                    'Driver' => 'local',
                    'Mountpoint' => '/var/lib/docker/volumes/mysql_data/_data',
                ],
            ],
        ];

        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->once()
            ->andReturn(['success' => true, 'info' => []]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->once()
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->with($this->server)
            ->once()
            ->andReturn($volumes);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->once()
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->once()
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('volumes', $volumes['volumes']);
    }

    /**
     * Test: Component displays networks list
     */
    public function test_component_displays_networks_list(): void
    {
        $networks = [
            'success' => true,
            'networks' => [
                [
                    'Name' => 'bridge',
                    'Driver' => 'bridge',
                    'Scope' => 'local',
                ],
                [
                    'Name' => 'host',
                    'Driver' => 'host',
                    'Scope' => 'local',
                ],
                [
                    'Name' => 'custom_network',
                    'Driver' => 'bridge',
                    'Scope' => 'local',
                ],
            ],
        ];

        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->once()
            ->andReturn(['success' => true, 'info' => []]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->once()
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->once()
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->with($this->server)
            ->once()
            ->andReturn($networks);

        $this->dockerService
            ->shouldReceive('listImages')
            ->once()
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('networks', $networks['networks']);
    }

    /**
     * Test: Component displays images list
     */
    public function test_component_displays_images_list(): void
    {
        $images = [
            'success' => true,
            'images' => [
                [
                    'ID' => 'sha256:abc123',
                    'Repository' => 'nginx',
                    'Tag' => 'latest',
                    'Size' => '142MB',
                    'Created' => '2 weeks ago',
                ],
                [
                    'ID' => 'sha256:def456',
                    'Repository' => 'mysql',
                    'Tag' => '8.0',
                    'Size' => '521MB',
                    'Created' => '1 month ago',
                ],
            ],
        ];

        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->once()
            ->andReturn(['success' => true, 'info' => []]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->once()
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->once()
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->once()
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->with($this->server)
            ->once()
            ->andReturn($images);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('images', $images['images']);
    }

    /**
     * Test: Switch between tabs
     */
    public function test_switch_between_tabs(): void
    {
        $this->mockDockerServiceInitialData();

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->assertSet('activeTab', 'overview')
            ->call('switchTab', 'images')
            ->assertSet('activeTab', 'images')
            ->call('switchTab', 'volumes')
            ->assertSet('activeTab', 'volumes')
            ->call('switchTab', 'networks')
            ->assertSet('activeTab', 'networks');
    }

    /**
     * Test: Prune unused images successfully
     */
    public function test_prune_images_successfully(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('pruneImages')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'Deleted Images: 5, Space reclaimed: 1.2GB',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('pruneImages')
            ->assertHasNoErrors()
            ->assertSessionHas('message', 'Images pruned successfully! Deleted Images: 5, Space reclaimed: 1.2GB');
    }

    /**
     * Test: Prune images fails gracefully
     */
    public function test_prune_images_handles_failure(): void
    {
        $this->mockDockerServiceInitialData();

        $this->dockerService
            ->shouldReceive('pruneImages')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Unable to connect to Docker daemon',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('pruneImages')
            ->assertSet('error', 'Failed to prune images: Unable to connect to Docker daemon');
    }

    /**
     * Test: System prune successfully
     */
    public function test_system_prune_successfully(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('systemPrune')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'Total reclaimed space: 2.5GB',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('systemPrune')
            ->assertHasNoErrors()
            ->assertSessionHas('message', 'System cleaned up successfully! Total reclaimed space: 2.5GB');
    }

    /**
     * Test: System prune handles failure
     */
    public function test_system_prune_handles_failure(): void
    {
        $this->mockDockerServiceInitialData();

        $this->dockerService
            ->shouldReceive('systemPrune')
            ->with($this->server, false)
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Permission denied',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('systemPrune')
            ->assertSet('error', 'Failed to clean up system: Permission denied');
    }

    /**
     * Test: Delete image successfully
     */
    public function test_delete_image_successfully(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('deleteImage')
            ->with($this->server, 'sha256:abc123')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'Deleted: sha256:abc123',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteImage', 'sha256:abc123')
            ->assertHasNoErrors()
            ->assertSessionHas('message', 'Image deleted successfully!');
    }

    /**
     * Test: Delete image handles failure
     */
    public function test_delete_image_handles_failure(): void
    {
        $this->mockDockerServiceInitialData();

        $this->dockerService
            ->shouldReceive('deleteImage')
            ->with($this->server, 'sha256:abc123')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Image is being used by running container',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteImage', 'sha256:abc123')
            ->assertSet('error', 'Failed to delete image: Image is being used by running container');
    }

    /**
     * Test: Delete volume successfully
     */
    public function test_delete_volume_successfully(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('deleteVolume')
            ->with($this->server, 'project_data')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'project_data',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteVolume', 'project_data')
            ->assertHasNoErrors()
            ->assertSessionHas('message', 'Volume deleted successfully!');
    }

    /**
     * Test: Delete volume handles failure
     */
    public function test_delete_volume_handles_failure(): void
    {
        $this->mockDockerServiceInitialData();

        $this->dockerService
            ->shouldReceive('deleteVolume')
            ->with($this->server, 'project_data')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Volume is in use',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteVolume', 'project_data')
            ->assertSet('error', 'Failed to delete volume: Volume is in use');
    }

    /**
     * Test: Delete network successfully
     */
    public function test_delete_network_successfully(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('deleteNetwork')
            ->with($this->server, 'custom_network')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => 'custom_network',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteNetwork', 'custom_network')
            ->assertHasNoErrors()
            ->assertSessionHas('message', 'Network deleted successfully!');
    }

    /**
     * Test: Delete network handles failure
     */
    public function test_delete_network_handles_failure(): void
    {
        $this->mockDockerServiceInitialData();

        $this->dockerService
            ->shouldReceive('deleteNetwork')
            ->with($this->server, 'custom_network')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Network has active endpoints',
            ]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('deleteNetwork', 'custom_network')
            ->assertSet('error', 'Failed to delete network: Network has active endpoints');
    }

    /**
     * Test: Docker API failure sets error message
     */
    public function test_docker_api_failure_sets_error_message(): void
    {
        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->with($this->server)
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->never();

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->never();

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->never();

        $this->dockerService
            ->shouldReceive('listImages')
            ->never();

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('error', 'Failed to load Docker information: Connection timeout')
            ->assertSet('loading', false);
    }

    /**
     * Test: Loading states are managed correctly
     */
    public function test_loading_states_are_managed_correctly(): void
    {
        $this->mockDockerServiceInitialData();

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->assertSet('isLoading', true)
            ->call('loadInitialData')
            ->assertSet('isLoading', false)
            ->assertSet('loading', false);
    }

    /**
     * Test: Error is cleared when loading new data
     */
    public function test_error_is_cleared_when_loading_new_data(): void
    {
        $this->mockDockerServiceInitialData();

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->set('error', 'Previous error message')
            ->call('loadDockerInfo')
            ->assertSet('error', null);
    }

    /**
     * Test: Initial lazy loading with wire:init
     */
    public function test_initial_lazy_loading(): void
    {
        $this->mockDockerServiceInitialData();

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->assertSet('isLoading', true)
            ->assertSet('dockerInfo', null)
            ->call('loadInitialData')
            ->assertSet('isLoading', false)
            ->assertSet('dockerInfo', Mockery::type('array'));
    }

    /**
     * Test: Reload Docker info after operations
     */
    public function test_reload_docker_info_after_operations(): void
    {
        $this->mockDockerServiceInitialData();
        $this->mockDockerServiceReload();

        $this->dockerService
            ->shouldReceive('pruneImages')
            ->with($this->server, false)
            ->once()
            ->andReturn(['success' => true, 'output' => 'Success']);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('pruneImages')
            ->assertSet('loading', false);
    }

    /**
     * Test: Multiple service calls handle partial failures
     */
    public function test_handles_partial_service_failures(): void
    {
        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->with($this->server)
            ->once()
            ->andReturn(['success' => true, 'info' => ['ServerVersion' => '24.0.7']]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->with($this->server)
            ->once()
            ->andReturn(['success' => false, 'error' => 'Disk usage error']);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->with($this->server)
            ->once()
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->with($this->server)
            ->once()
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->with($this->server)
            ->once()
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);

        Livewire::actingAs($this->user)
            ->test(DockerDashboard::class, ['server' => $this->server])
            ->call('loadInitialData')
            ->assertSet('dockerInfo', ['ServerVersion' => '24.0.7'])
            ->assertSet('diskUsage', null);
    }

    /**
     * Helper method to mock initial Docker service data loading
     */
    private function mockDockerServiceInitialData(): void
    {
        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->with($this->server)
            ->andReturn([
                'success' => true,
                'info' => [
                    'ServerVersion' => '24.0.7',
                    'NCPU' => 4,
                    'MemTotal' => 8589934592,
                ],
            ]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->with($this->server)
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->with($this->server)
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->with($this->server)
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->with($this->server)
            ->andReturn(['success' => true, 'images' => []]);

        $this->app->instance(DockerService::class, $this->dockerService);
    }

    /**
     * Helper method to mock Docker service reload after operations
     */
    private function mockDockerServiceReload(): void
    {
        $this->dockerService
            ->shouldReceive('getSystemInfo')
            ->with($this->server)
            ->andReturn([
                'success' => true,
                'info' => [
                    'ServerVersion' => '24.0.7',
                    'NCPU' => 4,
                    'MemTotal' => 8589934592,
                ],
            ]);

        $this->dockerService
            ->shouldReceive('getDiskUsage')
            ->with($this->server)
            ->andReturn(['success' => true, 'usage' => []]);

        $this->dockerService
            ->shouldReceive('listVolumes')
            ->with($this->server)
            ->andReturn(['success' => true, 'volumes' => []]);

        $this->dockerService
            ->shouldReceive('listNetworks')
            ->with($this->server)
            ->andReturn(['success' => true, 'networks' => []]);

        $this->dockerService
            ->shouldReceive('listImages')
            ->with($this->server)
            ->andReturn(['success' => true, 'images' => []]);
    }
}
