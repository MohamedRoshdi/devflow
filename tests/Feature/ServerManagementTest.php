<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Mockery;

class ServerManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_servers_list()
    {
        $this->actingAs($this->user);

        Server::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('servers.index'));

        $response->assertStatus(200);
        $response->assertViewIs('servers.index');
    }

    /** @test */
    public function user_can_create_server_with_password_authentication()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.100')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'secure_password_123')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'user_id' => $this->user->id,
        ]);

        $server = Server::where('name', 'Test Server')->first();
        $this->assertNotNull($server->ssh_password);
        $this->assertNull($server->ssh_key);
    }

    /** @test */
    public function user_can_create_server_with_ssh_key_authentication()
    {
        $this->actingAs($this->user);

        $sshKey = '-----BEGIN OPENSSH PRIVATE KEY-----
test_key_content
-----END OPENSSH PRIVATE KEY-----';

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'SSH Key Server')
            ->set('ip_address', '192.168.1.101')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'key')
            ->set('ssh_key', $sshKey)
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'SSH Key Server',
            'ip_address' => '192.168.1.101',
        ]);

        $server = Server::where('name', 'SSH Key Server')->first();
        $this->assertNotNull($server->ssh_key);
        $this->assertNull($server->ssh_password);
    }

    /** @test */
    public function hostname_is_optional_when_creating_server()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'No Hostname Server')
            ->set('ip_address', '10.0.0.1')
            ->set('hostname', '') // Empty hostname
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'test_password')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'No Hostname Server',
            'ip_address' => '10.0.0.1',
            'hostname' => null,
        ]);
    }

    /** @test */
    public function hostname_can_be_provided_when_creating_server()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'With Hostname Server')
            ->set('ip_address', '10.0.0.2')
            ->set('hostname', 'server.example.com')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'test_password')
            ->call('createServer');

        $this->assertDatabaseHas('servers', [
            'name' => 'With Hostname Server',
            'hostname' => 'server.example.com',
        ]);
    }

    /** @test */
    public function ip_address_is_required()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '') // Empty IP
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'test_password')
            ->call('createServer')
            ->assertHasErrors(['ip_address' => 'required']);
    }

    /** @test */
    public function ip_address_must_be_valid()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', 'not-an-ip')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', 'test_password')
            ->call('createServer')
            ->assertHasErrors(['ip_address' => 'ip']);
    }

    /** @test */
    public function password_is_required_when_auth_method_is_password()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.1')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'password')
            ->set('ssh_password', '') // Empty password
            ->call('createServer')
            ->assertHasErrors(['ssh_password']);
    }

    /** @test */
    public function ssh_key_is_required_when_auth_method_is_key()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Servers\ServerCreate::class)
            ->set('name', 'Test Server')
            ->set('ip_address', '192.168.1.1')
            ->set('port', 22)
            ->set('username', 'root')
            ->set('auth_method', 'key')
            ->set('ssh_key', '') // Empty SSH key
            ->call('createServer')
            ->assertHasErrors(['ssh_key']);
    }

    /** @test */
    public function user_cannot_view_other_users_server()
    {
        $otherUser = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('servers.show', $server));

        $response->assertStatus(403);
    }

    /** @test */
    public function server_status_is_set_correctly()
    {
        $server = Server::factory()->online()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('online', $server->status);
        $this->assertTrue($server->isOnline());
        $this->assertFalse($server->isOffline());
    }

    /** @test */
    public function server_status_color_attribute_returns_correct_value()
    {
        $onlineServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);

        $offlineServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offline',
        ]);

        $maintenanceServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'maintenance',
        ]);

        $this->assertEquals('green', $onlineServer->status_color);
        $this->assertEquals('red', $offlineServer->status_color);
        $this->assertEquals('yellow', $maintenanceServer->status_color);
    }

    /** @test */
    public function ssh_password_is_hidden_from_array()
    {
        $server = Server::factory()->withPassword()->create([
            'user_id' => $this->user->id,
        ]);

        $array = $server->toArray();

        $this->assertArrayNotHasKey('ssh_password', $array);
        $this->assertArrayNotHasKey('ssh_key', $array);
    }

    /** @test */
    public function server_can_have_gps_location()
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'location_name' => 'New York, USA',
        ]);

        $this->assertEquals(40.7128, $server->latitude);
        $this->assertEquals(-74.0060, $server->longitude);
        $this->assertEquals('New York, USA', $server->location_name);
    }

    /** @test */
    public function server_tracks_last_ping_time()
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'last_ping_at' => now(),
        ]);

        $this->assertNotNull($server->last_ping_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $server->last_ping_at);
    }

    /** @test */
    public function connectivity_service_extracts_numeric_values_correctly()
    {
        $service = new ServerConnectivityService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractNumericValue');
        $method->setAccessible(true);

        // Test with clean numeric output
        $this->assertEquals(8, $method->invoke($service, '8'));

        // Test with SSH warning mixed in
        $warningOutput = "Warning: Permanently added '192.168.1.1' (ED25519) to the list of known hosts.\n8";
        $this->assertEquals(8, $method->invoke($service, $warningOutput));

        // Test with just numbers at the end
        $this->assertEquals(16, $method->invoke($service, "Some text\n16"));

        // Test extraction from mixed content
        $this->assertEquals(32, $method->invoke($service, 'CPU cores: 32'));
    }

    /** @test */
    public function server_has_correct_relationships()
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $server->user);
        $this->assertEquals($this->user->id, $server->user->id);
    }
}
