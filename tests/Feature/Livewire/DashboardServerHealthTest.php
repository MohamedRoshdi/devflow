<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard\DashboardServerHealth;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardServerHealthTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Cache::flush();
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->assertSet('serverHealth', []);
    }

    // ==================== LOAD HEALTH TESTS ====================

    public function test_can_load_server_health(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 60.0,
            'disk_usage' => 70.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertNotEmpty($health);
        $this->assertCount(1, $health);
    }

    public function test_excludes_offline_servers(): void
    {
        Server::factory()->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);
        Server::factory()->create(['status' => 'maintenance']);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertCount(1, $health);
    }

    public function test_loads_server_metrics(): void
    {
        $server = Server::factory()->create(['status' => 'online', 'name' => 'Production Server']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 55.5,
            'disk_usage' => 65.5,
            'load_average_1' => 1.5,
            'load_average_5' => 1.2,
            'load_average_15' => 1.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $serverHealth = $health[0];

        $this->assertEquals($server->id, $serverHealth['server_id']);
        $this->assertEquals('Production Server', $serverHealth['server_name']);
        $this->assertEquals(45.5, $serverHealth['cpu_usage']);
        $this->assertEquals(55.5, $serverHealth['memory_usage']);
        $this->assertEquals(65.5, $serverHealth['disk_usage']);
    }

    public function test_handles_server_without_metrics(): void
    {
        $server = Server::factory()->create(['status' => 'online', 'name' => 'New Server']);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertCount(1, $health);

        $serverHealth = $health[0];
        $this->assertEquals($server->id, $serverHealth['server_id']);
        $this->assertEquals('New Server', $serverHealth['server_name']);
        $this->assertNull($serverHealth['cpu_usage']);
        $this->assertNull($serverHealth['memory_usage']);
        $this->assertNull($serverHealth['disk_usage']);
        $this->assertEquals('unknown', $serverHealth['health_status']);
    }

    // ==================== HEALTH STATUS TESTS ====================

    public function test_healthy_status_for_low_usage(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('healthy', $health[0]['health_status']);
    }

    public function test_warning_status_for_high_cpu(): void
    {
        // Score calculation: > 90 = -40 points, > 75 = -20 points
        // For warning status (score 50-79), need 91%+ CPU to get score 60
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 91.0,  // > 90 = -40 points = score 60 (warning)
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('warning', $health[0]['health_status']);
    }

    public function test_warning_status_for_high_memory(): void
    {
        // Score calculation: > 90 = -40 points, > 75 = -20 points
        // For warning status (score 50-79), need 91%+ memory to get score 60
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 91.0,  // > 90 = -40 points = score 60 (warning)
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('warning', $health[0]['health_status']);
    }

    public function test_warning_status_for_high_disk(): void
    {
        // Score calculation: > 90 = -40 points, > 75 = -20 points
        // For warning status (score 50-79), need 91%+ disk to get score 60
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 91.0,  // > 90 = -40 points = score 60 (warning)
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('warning', $health[0]['health_status']);
    }

    public function test_critical_status_for_very_high_cpu(): void
    {
        // For critical status (score < 50), need multiple high metrics
        // CPU > 90 = -40, memory > 75 = -20 = score 40 (critical)
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,    // > 90 = -40 points
            'memory_usage' => 80.0, // > 75 = -20 points = total -60 = score 40
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('critical', $health[0]['health_status']);
    }

    public function test_critical_status_for_very_high_memory(): void
    {
        // For critical status (score < 50), need multiple high metrics
        // Memory > 90 = -40, CPU > 75 = -20 = score 40 (critical)
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 80.0,    // > 75 = -20 points
            'memory_usage' => 95.0, // > 90 = -40 points = total -60 = score 40
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('critical', $health[0]['health_status']);
    }

    public function test_critical_status_for_very_high_disk(): void
    {
        // For critical status (score < 50), need multiple high metrics
        // Disk > 90 = -40, CPU > 75 = -20 = score 40 (critical)
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 80.0,    // > 75 = -20 points
            'memory_usage' => 50.0,
            'disk_usage' => 95.0,   // > 90 = -40 points = total -60 = score 40
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('critical', $health[0]['health_status']);
    }

    public function test_boundary_values_for_warning_threshold(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        // At exactly 75, should still be healthy
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 75.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('healthy', $health[0]['health_status']);
    }

    public function test_boundary_values_for_critical_threshold(): void
    {
        // At exactly 90, condition is > 90 (not >=), so only > 75 applies
        // Score = 100 - 20 = 80 = healthy
        // For warning at the boundary, we need > 90 (e.g., 90.1)
        // Test that 91% triggers warning (score 60)
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 91.0,  // > 90 = -40 = score 60 = warning
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('warning', $health[0]['health_status']);
    }

    // ==================== CACHE TESTS ====================

    public function test_can_clear_server_health_cache(): void
    {
        Cache::put('dashboard_server_health', ['cached_data'], 3600);

        Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('clearServerHealthCache');

        $this->assertNull(Cache::get('dashboard_server_health'));
    }

    public function test_uses_cached_data(): void
    {
        $cachedData = [
            [
                'server_id' => 999,
                'server_name' => 'Cached Server',
                'cpu_usage' => 25.0,
                'memory_usage' => 35.0,
                'disk_usage' => 45.0,
                'health_status' => 'healthy',
            ],
        ];

        Cache::put('dashboard_server_health', $cachedData, 3600);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals(999, $health[0]['server_id']);
        $this->assertEquals('Cached Server', $health[0]['server_name']);
    }

    // ==================== EVENT LISTENER TESTS ====================

    public function test_refreshes_on_server_health_event(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        // Component loads serverHealth on mount
        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class);

        // Data should be loaded on mount
        $this->assertNotEmpty($component->get('serverHealth'));

        // Event should work and keep data populated
        $component->dispatch('refresh-server-health')
            ->assertStatus(200);

        $this->assertNotEmpty($component->get('serverHealth'));
    }

    public function test_refreshes_on_metrics_updated_event(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        // Component loads serverHealth on mount
        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class);

        // Data should be loaded on mount
        $this->assertNotEmpty($component->get('serverHealth'));

        // Event should work and keep data populated
        $component->dispatch('server-metrics-updated')
            ->assertStatus(200);

        $this->assertNotEmpty($component->get('serverHealth'));
    }

    public function test_refresh_clears_cache_before_loading(): void
    {
        $cachedData = [
            [
                'server_id' => 1,
                'server_name' => 'Old Cached',
                'cpu_usage' => null,
                'memory_usage' => null,
                'disk_usage' => null,
                'health_status' => 'unknown',
            ],
        ];

        Cache::put('dashboard_server_health', $cachedData, 3600);

        $server = Server::factory()->create(['status' => 'online', 'name' => 'New Server']);
        ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 50.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('refreshServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEquals('New Server', $health[0]['server_name']);
    }

    // ==================== MULTIPLE SERVERS TESTS ====================

    public function test_loads_multiple_servers(): void
    {
        $servers = Server::factory()->count(3)->create(['status' => 'online']);

        foreach ($servers as $server) {
            ServerMetric::factory()->create([
                'server_id' => $server->id,
                'cpu_usage' => 50.0,
                'memory_usage' => 50.0,
                'disk_usage' => 50.0,
            ]);
        }

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertCount(3, $health);
    }

    public function test_mixed_health_statuses(): void
    {
        // Health score: >= 80 = healthy, 50-79 = warning, < 50 = critical
        // Deductions: > 90 = -40 points, > 75 = -20 points per metric
        $healthyServer = Server::factory()->create(['status' => 'online', 'name' => 'Healthy']);
        $warningServer = Server::factory()->create(['status' => 'online', 'name' => 'Warning']);
        $criticalServer = Server::factory()->create(['status' => 'online', 'name' => 'Critical']);

        // Healthy: 30% each = score 100 = healthy
        ServerMetric::factory()->create([
            'server_id' => $healthyServer->id,
            'cpu_usage' => 30.0,
            'memory_usage' => 30.0,
            'disk_usage' => 30.0,
        ]);

        // Warning: 91% CPU = -40 = score 60 = warning
        ServerMetric::factory()->create([
            'server_id' => $warningServer->id,
            'cpu_usage' => 91.0,
            'memory_usage' => 30.0,
            'disk_usage' => 30.0,
        ]);

        // Critical: 91% CPU + 91% memory = -80 = score 20 = critical
        ServerMetric::factory()->create([
            'server_id' => $criticalServer->id,
            'cpu_usage' => 91.0,
            'memory_usage' => 91.0,
            'disk_usage' => 30.0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');

        $statuses = array_column($health, 'health_status');
        $this->assertContains('healthy', $statuses);
        $this->assertContains('warning', $statuses);
        $this->assertContains('critical', $statuses);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_servers(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEmpty($health);
    }

    public function test_handles_only_offline_servers(): void
    {
        Server::factory()->count(3)->create(['status' => 'offline']);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardServerHealth::class)
            ->call('loadServerHealth');

        $health = $component->get('serverHealth');
        $this->assertEmpty($health);
    }
}
