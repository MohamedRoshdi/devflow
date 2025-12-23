<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerCreate;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ServerCreateTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->assertOk();
    }

    public function test_guest_cannot_access_component(): void
    {
        Livewire::test(ServerCreate::class)
            ->assertUnauthorized();
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->assertSet('name', '')
            ->assertSet('hostname', '')
            ->assertSet('ip_address', '')
            ->assertSet('port', 22)
            ->assertSet('username', 'root')
            ->assertSet('ssh_password', '')
            ->assertSet('ssh_key', '')
            ->assertSet('auth_method', 'password')
            ->assertSet('latitude', null)
            ->assertSet('longitude', null)
            ->assertSet('location_name', '');
    }

    public function test_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', '')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_name_must_not_exceed_255_characters(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', str_repeat('a', 256))
            ->set('ip_address', '192.168.1.100')
            ->call('createServer')
            ->assertHasErrors(['name' => 'max']);
    }

    public function test_hostname_is_optional(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('hostname', '')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'hostname' => null,
        ]);
    }

    public function test_hostname_must_not_exceed_255_characters(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('hostname', str_repeat('a', 256))
            ->set('ip_address', '192.168.1.100')
            ->call('createServer')
            ->assertHasErrors(['hostname' => 'max']);
    }

    public function test_ip_address_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '')
            ->call('createServer')
            ->assertHasErrors(['ip_address' => 'required']);
    }

    public function test_ip_address_must_be_valid_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', 'invalid-ip')
            ->call('createServer')
            ->assertHasErrors(['ip_address' => 'ip']);
    }

    public function test_ip_address_accepts_valid_ipv4(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'ip_address' => '192.168.1.100',
        ]);
    }

    public function test_ip_address_accepts_valid_ipv6(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '2001:0db8:85a3:0000:0000:8a2e:0370:7334')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);
    }

    public function test_port_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', null)
            ->call('createServer')
            ->assertHasErrors(['port' => 'required']);
    }

    public function test_port_must_be_integer(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('port', 'not-a-number')
            ->call('createServer')
            ->assertHasErrors(['port' => 'integer']);
    }

    public function test_port_must_be_at_least_1(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('port', 0)
            ->call('createServer')
            ->assertHasErrors(['port' => 'min']);
    }

    public function test_port_must_not_exceed_65535(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('port', 65536)
            ->call('createServer')
            ->assertHasErrors(['port' => 'max']);
    }

    public function test_port_accepts_valid_range(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 2222)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'port' => 2222,
        ]);
    }

    public function test_username_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('username', '')
            ->call('createServer')
            ->assertHasErrors(['username' => 'required']);
    }

    public function test_username_must_not_exceed_255_characters(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('username', str_repeat('a', 256))
            ->call('createServer')
            ->assertHasErrors(['username' => 'max']);
    }

    public function test_username_must_match_valid_pattern(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('username', 'user@invalid')
            ->call('createServer')
            ->assertHasErrors(['username' => 'regex']);
    }

    public function test_username_accepts_alphanumeric_underscore_hyphen(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'deploy_user-123')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'username' => 'deploy_user-123',
        ]);
    }

    public function test_ssh_password_required_when_auth_method_is_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('auth_method', 'password')
            ->set('ssh_password', '')
            ->call('createServer')
            ->assertHasErrors(['ssh_password' => 'required_if']);
    }

    public function test_ssh_key_required_when_auth_method_is_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('auth_method', 'key')
            ->set('ssh_key', '')
            ->call('createServer')
            ->assertHasErrors(['ssh_key' => 'required_if']);
    }

    public function test_auth_method_must_be_password_or_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('auth_method', 'invalid')
            ->call('createServer')
            ->assertHasErrors(['auth_method' => 'in']);
    }

    public function test_latitude_must_be_numeric(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('latitude', 'not-a-number')
            ->call('createServer')
            ->assertHasErrors(['latitude' => 'numeric']);
    }

    public function test_latitude_must_be_between_minus_90_and_90(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('latitude', 91)
            ->call('createServer')
            ->assertHasErrors(['latitude' => 'between']);

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('latitude', -91)
            ->call('createServer')
            ->assertHasErrors(['latitude' => 'between']);
    }

    public function test_longitude_must_be_numeric(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('longitude', 'not-a-number')
            ->call('createServer')
            ->assertHasErrors(['longitude' => 'numeric']);
    }

    public function test_longitude_must_be_between_minus_180_and_180(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('longitude', 181)
            ->call('createServer')
            ->assertHasErrors(['longitude' => 'between']);

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('longitude', -181)
            ->call('createServer')
            ->assertHasErrors(['longitude' => 'between']);
    }

    public function test_location_name_is_optional(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->set('location_name', '')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'location_name' => null,
        ]);
    }

    public function test_location_name_must_not_exceed_255_characters(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('location_name', str_repeat('a', 256))
            ->call('createServer')
            ->assertHasErrors(['location_name' => 'max']);
    }

    public function test_test_connection_validates_all_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', '')
            ->set('ip_address', '')
            ->call('testConnection')
            ->assertHasErrors(['name', 'ip_address']);
    }

    public function test_test_connection_shows_success_message_on_successful_connection(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => true,
                    'message' => 'SSH connection successful',
                    'latency_ms' => 45.23,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('testConnection')
            ->assertSessionHas('connection_test', 'SSH connection successful (Latency: 45.23ms)');
    }

    public function test_test_connection_shows_error_message_on_failed_connection(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => false,
                    'message' => 'Connection refused',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('testConnection')
            ->assertSessionHas('connection_error', 'Connection refused');
    }

    public function test_test_connection_handles_exceptions(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andThrow(new \Exception('Network timeout'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('testConnection')
            ->assertSessionHas('connection_error', 'Connection failed: Network timeout');
    }

    public function test_create_server_with_password_authentication(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Production Server')
            ->set('hostname', 'prod.example.com')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'secure_password')
            ->set('latitude', 40.7128)
            ->set('longitude', -74.0060)
            ->set('location_name', 'New York')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'user_id' => $this->user->id,
            'name' => 'Production Server',
            'hostname' => 'prod.example.com',
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'ssh_password' => 'secure_password',
            'ssh_key' => null,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'location_name' => 'New York',
            'status' => 'online',
        ]);
    }

    public function test_create_server_with_key_authentication(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        $sshKey = "-----BEGIN RSA PRIVATE KEY-----\nMIIEpAIBAAKCAQEA...\n-----END RSA PRIVATE KEY-----";

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Production Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'deploy')
            ->set('auth_method', 'key')
            ->set('ssh_key', $sshKey)
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'user_id' => $this->user->id,
            'name' => 'Production Server',
            'username' => 'deploy',
            'ssh_password' => null,
            'ssh_key' => $sshKey,
        ]);
    }

    public function test_create_server_updates_status_to_online_when_reachable(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'status' => 'online',
        ]);
    }

    public function test_create_server_updates_status_to_offline_when_unreachable(): void
    {
        $this->mockFailedConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'status' => 'offline',
        ]);
    }

    public function test_create_server_retrieves_server_information_when_online(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getServerInfo')
                ->once()
                ->andReturn([
                    'os' => 'Linux',
                    'cpu_cores' => 4,
                    'memory_gb' => 16,
                    'disk_gb' => 500,
                ]);
        });

        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'os' => 'Linux',
            'cpu_cores' => 4,
            'memory_gb' => 16,
            'disk_gb' => 500,
        ]);
    }

    public function test_create_server_does_not_retrieve_server_info_when_offline(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->once()
                ->andReturn(false);

            $mock->shouldReceive('getServerInfo')
                ->never();
        });

        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'os' => null,
            'cpu_cores' => null,
            'memory_gb' => null,
            'disk_gb' => null,
        ]);
    }

    public function test_create_server_checks_docker_installation(): void
    {
        $this->mockSuccessfulConnection();

        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andReturn([
                    'installed' => true,
                    'version' => '24.0.7',
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);
    }

    public function test_create_server_handles_docker_not_installed(): void
    {
        $this->mockSuccessfulConnection();

        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andReturn([
                    'installed' => false,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'docker_installed' => false,
            'docker_version' => null,
        ]);
    }

    public function test_create_server_handles_docker_check_exception(): void
    {
        $this->mockSuccessfulConnection();

        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkDockerInstallation')
                ->once()
                ->andThrow(new \Exception('Docker check failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer');

        $server = Server::where('name', 'Test Server')->first();
        $this->assertNotNull($server);
        // Docker installation status is not updated when exception occurs
        $this->assertFalse($server->docker_installed);
        $this->assertNull($server->docker_version);
    }

    public function test_create_server_dispatches_server_created_event(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer')
            ->assertDispatched('server-created');
    }

    public function test_create_server_redirects_to_server_show_page(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('ssh_password', 'password123')
            ->call('createServer')
            ->assertRedirect();
    }

    public function test_get_location_sets_location_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerCreate::class)
            ->call('getLocation')
            ->assertSet('location_name', 'Auto-detected location');
    }

    /**
     * Mock successful server connectivity service
     */
    private function mockSuccessfulConnection(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->andReturn(true);

            $mock->shouldReceive('getServerInfo')
                ->andReturn([
                    'os' => 'Linux',
                    'cpu_cores' => 4,
                    'memory_gb' => 16,
                    'disk_gb' => 500,
                ]);
        });
    }

    /**
     * Mock failed server connectivity service
     */
    private function mockFailedConnection(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->andReturn(false);
        });
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
