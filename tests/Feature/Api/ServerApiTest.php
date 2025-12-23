<?php

declare(strict_types=1);

namespace Tests\Feature\Api;


use PHPUnit\Framework\Attributes\Test;
use App\Models\ApiToken;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServerApiTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    protected User $user;
    protected string $apiToken;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->apiToken = 'server-api-token-123';
        ApiToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $this->apiToken),
            'expires_at' => now()->addDays(30),
        ]);

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
        ];
    }

    // ==================== List Servers ====================

    #[Test]
    public function it_can_list_all_servers(): void
    {
        Server::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/servers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'ip_address', 'status'],
                ],
            ]);
    }

    #[Test]
    public function it_requires_authentication_to_list_servers(): void
    {
        $response = $this->getJson('/api/v1/servers');

        $response->assertUnauthorized();
    }

    // ==================== Create Server ====================

    #[Test]
    public function it_can_create_a_server(): void
    {
        $serverData = [
            'name' => 'Test Server',
            'hostname' => 'test-server.example.com',
            'ip_address' => '192.168.1.100',
            'ssh_user' => 'root',
            'ssh_port' => 22,
            'username' => 'root',
        ];

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', $serverData);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Server');

        $this->assertDatabaseHas('servers', [
            'name' => 'Test Server',
            'ip_address' => '192.168.1.100',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_server(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'ip_address']);
    }

    #[Test]
    public function it_validates_ip_address_format(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', [
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'ip_address' => 'not-an-ip',
                'username' => 'root',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address']);
    }

    #[Test]
    public function it_validates_unique_ip_address(): void
    {
        Server::factory()->create([
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100',
        ]);

        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', [
                'name' => 'Another Server',
                'hostname' => 'another.example.com',
                'ip_address' => '192.168.1.100',
                'username' => 'root',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address']);
    }

    // ==================== Get Single Server ====================

    #[Test]
    public function it_can_get_a_single_server(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/servers/' . $server->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $server->id);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_server(): void
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/servers/99999');

        $response->assertNotFound();
    }

    // ==================== Update Server ====================

    #[Test]
    public function it_can_update_a_server(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $response = $this->withHeaders($this->headers)
            ->putJson('/api/v1/servers/' . $server->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'name' => 'Updated Name',
        ]);
    }

    // ==================== Delete Server ====================

    #[Test]
    public function it_can_delete_a_server(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->deleteJson('/api/v1/servers/' . $server->id);

        $response->assertNoContent();

        // Server uses SoftDeletes
        $this->assertSoftDeleted('servers', [
            'id' => $server->id,
        ]);
    }

    // ==================== Server Status ====================

    #[Test]
    public function it_can_get_server_status(): void
    {
        // Status info is included in the server show endpoint, not a separate route
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/servers/' . $server->id);

        $response->assertOk()
            ->assertJsonPath('data.status', 'online');
    }

    // ==================== Server Metrics ====================

    #[Test]
    public function it_can_get_server_metrics(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/servers/' . $server->id . '/metrics');

        $response->assertOk();
    }

    // ==================== Port Validation ====================

    #[Test]
    public function it_creates_server_with_default_port(): void
    {
        // Verify server creation works without specifying port
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', [
                'name' => 'Default Port Server',
                'hostname' => 'default-port.example.com',
                'ip_address' => '192.168.1.101',
                'username' => 'root',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('servers', [
            'name' => 'Default Port Server',
            'port' => 22, // Default SSH port
        ]);
    }

    #[Test]
    public function it_accepts_valid_ssh_port(): void
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/servers', [
                'name' => 'Test Server',
                'hostname' => 'valid-port.example.com',
                'ip_address' => '192.168.1.102',
                'username' => 'admin',
                'ssh_port' => 2222,
            ]);

        $response->assertCreated();
    }
}
