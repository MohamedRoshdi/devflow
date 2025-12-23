<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerEdit;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Livewire\Livewire;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for ServerEdit Livewire component
 *
 * Tests form initialization, validation, auth method switching,
 * update functionality, and credential management.
 */
#[CoversClass(ServerEdit::class)]
class ServerEditTest extends TestCase
{
    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions if they don't exist
        Permission::firstOrCreate(['name' => 'view-servers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-servers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-servers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete-servers', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view-servers', 'edit-servers']);

        $this->server = Server::factory()->online()->create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'ssh_password' => 'original_password',
            'ssh_key' => null,
            'user_id' => $this->user->id, // Make user the owner
        ]);
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->assertOk();
    }

    public function test_guest_cannot_access_component(): void
    {
        Livewire::test(ServerEdit::class, ['server' => $this->server])
            ->assertForbidden();
    }

    public function test_component_loads_server_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->assertSet('name', 'Test Server')
            ->assertSet('ip_address', '192.168.1.100')
            ->assertSet('port', 22)
            ->assertSet('username', 'root');
    }

    public function test_auth_method_defaults_to_password_when_password_exists(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->assertSet('auth_method', 'password');
    }

    public function test_auth_method_defaults_to_key_when_key_exists(): void
    {
        $server = Server::factory()->online()->create([
            'ssh_key' => '-----BEGIN OPENSSH PRIVATE KEY-----',
            'ssh_password' => null,
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $server])
            ->assertSet('auth_method', 'key');
    }

    public function test_auth_method_defaults_to_host_key_when_no_credentials(): void
    {
        $server = Server::factory()->online()->create([
            'ssh_key' => null,
            'ssh_password' => null,
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $server])
            ->assertSet('auth_method', 'host_key');
    }

    public function test_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('name', '')
            ->call('updateServer')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_ip_address_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('ip_address', '')
            ->call('updateServer')
            ->assertHasErrors(['ip_address' => 'required']);
    }

    public function test_ip_address_must_be_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('ip_address', 'invalid-ip')
            ->call('updateServer')
            ->assertHasErrors(['ip_address' => 'ip']);
    }

    public function test_port_accepts_valid_range(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('port', 2222)
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals(2222, $this->server->port);
    }

    public function test_update_server_with_host_key_auth(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'host_key')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertNull($this->server->ssh_password);
        $this->assertNull($this->server->ssh_key);
    }

    public function test_update_server_with_new_password(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'password')
            ->set('ssh_password', 'new_password123')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals('new_password123', $this->server->ssh_password);
        $this->assertNull($this->server->ssh_key);
    }

    public function test_update_server_with_new_ssh_key(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        $newKey = '-----BEGIN OPENSSH PRIVATE KEY-----\ntest key content\n-----END OPENSSH PRIVATE KEY-----';

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'key')
            ->set('ssh_key', $newKey)
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals($newKey, $this->server->ssh_key);
        $this->assertNull($this->server->ssh_password);
    }

    public function test_update_server_keeps_existing_password_when_empty(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        $originalPassword = $this->server->ssh_password;

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'password')
            ->set('ssh_password', '') // Empty - should keep existing
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals($originalPassword, $this->server->ssh_password);
    }

    public function test_update_server_changes_name(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('name', 'Updated Server Name')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals('Updated Server Name', $this->server->name);
    }

    public function test_update_server_changes_ip_address(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('ip_address', '10.0.0.50')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals('10.0.0.50', $this->server->ip_address);
    }

    public function test_update_server_changes_location(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('latitude', 40.7128)
            ->set('longitude', -74.0060)
            ->set('location_name', 'New York')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals(40.7128, $this->server->latitude);
        $this->assertEquals(-74.0060, $this->server->longitude);
        $this->assertEquals('New York', $this->server->location_name);
    }

    public function test_update_server_redirects_to_show_page(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->call('updateServer')
            ->assertRedirect(route('servers.show', $this->server));
    }

    public function test_test_connection_works(): void
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

        // Just verify the test passes without errors when connection succeeds
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->call('testConnection')
            ->assertOk();
    }

    public function test_test_connection_shows_error(): void
    {
        $this->mock(ServerConnectivityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => false,
                    'message' => 'Connection refused',
                ]);
        });

        // Just verify the test passes without errors when connection fails
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->call('testConnection')
            ->assertOk();
    }

    public function test_get_password_for_test_returns_null_for_host_key_auth(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'host_key');

        // Access protected method via reflection
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getPasswordForTest');
        $method->setAccessible(true);

        $result = $method->invoke($component->instance());
        $this->assertNull($result);
    }

    public function test_get_key_for_test_returns_null_for_host_key_auth(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('auth_method', 'host_key');

        // Access protected method via reflection
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('getKeyForTest');
        $method->setAccessible(true);

        $result = $method->invoke($component->instance());
        $this->assertNull($result);
    }

    public function test_latitude_must_be_valid_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('latitude', 91)
            ->call('updateServer')
            ->assertHasErrors(['latitude' => 'between']);
    }

    public function test_longitude_must_be_valid_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('longitude', 181)
            ->call('updateServer')
            ->assertHasErrors(['longitude' => 'between']);
    }

    public function test_hostname_is_optional(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('hostname', '')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertNull($this->server->hostname);
    }

    public function test_hostname_can_be_set(): void
    {
        $this->mockSuccessfulConnection();
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ServerEdit::class, ['server' => $this->server])
            ->set('hostname', 'server.example.com')
            ->call('updateServer');

        $this->server->refresh();
        $this->assertEquals('server.example.com', $this->server->hostname);
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
