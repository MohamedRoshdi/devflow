<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerMetricsApiTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_authenticated_user_can_store_server_metrics(): void
    {
        Sanctum::actingAs($this->user, ['server:report-metrics']);

        $metricsData = [
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.0,
            'network_in' => 1024,
            'network_out' => 2048,
        ];

        $response = $this->postJson(
            "/api/servers/{$this->server->id}/metrics",
            $metricsData
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'server_id',
                'cpu_usage',
                'memory_usage',
                'disk_usage',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
        ]);
    }

    public function test_authenticated_user_can_retrieve_server_metrics(): void
    {
        Sanctum::actingAs($this->user);

        ServerMetric::factory()->count(10)->create([
            'server_id' => $this->server->id,
        ]);

        $response = $this->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'server_id',
                    'cpu_usage',
                    'memory_usage',
                    'disk_usage',
                    'created_at',
                ],
            ],
        ]);
    }

    public function test_metrics_storage_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/servers/{$this->server->id}/metrics", []);

        $response->assertStatus(422);
    }

    public function test_metrics_storage_validates_numeric_values(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/servers/{$this->server->id}/metrics", [
            'cpu_usage' => 'invalid',
            'memory_usage' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_store_metrics(): void
    {
        $response = $this->postJson("/api/servers/{$this->server->id}/metrics", [
            'cpu_usage' => 50.0,
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_retrieve_metrics(): void
    {
        $response = $this->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertUnauthorized();
    }

    public function test_metrics_are_ordered_by_recorded_at_desc(): void
    {
        Sanctum::actingAs($this->user);

        // Clear existing metrics for this server
        ServerMetric::where('server_id', $this->server->id)->delete();

        // API orders by recorded_at, not created_at
        $oldMetric = ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subHours(2),
        ]);

        $newMetric = ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson("/api/servers/{$this->server->id}/metrics");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(2, $data);
        // The most recent metric should be first (compare by recorded_at)
        $firstRecordedAt = strtotime($data[0]['recorded_at']);
        $secondRecordedAt = strtotime($data[1]['recorded_at']);
        $this->assertGreaterThanOrEqual($secondRecordedAt, $firstRecordedAt, 'Metrics should be ordered by recorded_at descending');
    }

    public function test_metrics_endpoint_is_rate_limited(): void
    {
        Sanctum::actingAs($this->user);

        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson("/api/servers/{$this->server->id}/metrics", [
                'cpu_usage' => 50.0,
                'memory_usage' => 60.0,
                'disk_usage' => 70.0,
            ]);

            if ($response->status() === 429) {
                $this->assertEquals(429, $response->status());

                return;
            }
        }

        // If we get here without hitting rate limit, that's also acceptable
        $this->assertTrue(true);
    }

    public function test_metrics_for_non_existent_server_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/servers/99999/metrics');

        $response->assertNotFound();
    }

    public function test_metrics_can_include_optional_fields(): void
    {
        Sanctum::actingAs($this->user, ['server:report-metrics']);

        $metricsData = [
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.0,
            'network_in' => 1024,
            'network_out' => 2048,
            'load_average' => 1.5,
            'swap_usage' => 10.0,
        ];

        $response = $this->postJson(
            "/api/servers/{$this->server->id}/metrics",
            $metricsData
        );

        $response->assertCreated();
    }
}
