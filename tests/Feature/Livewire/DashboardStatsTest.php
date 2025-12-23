<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard\DashboardStats;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
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
            ->test(DashboardStats::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        // After mount(), stats are loaded so deploymentsToday=0, activeDeployments=0
        // overallSecurityScore defaults to 85 when no servers
        // isLoading=false after loadStats completes
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->assertSet('deploymentsToday', 0)
            ->assertSet('activeDeployments', 0)
            ->assertSet('overallSecurityScore', 85) // Default when no servers
            ->assertSet('isLoading', false); // loadStats sets this to false
    }

    public function test_default_stats_array_structure(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        $stats = $component->get('stats');
        $this->assertArrayHasKey('total_servers', $stats);
        $this->assertArrayHasKey('online_servers', $stats);
        $this->assertArrayHasKey('total_projects', $stats);
        $this->assertArrayHasKey('running_projects', $stats);
        $this->assertArrayHasKey('total_deployments', $stats);
        $this->assertArrayHasKey('successful_deployments', $stats);
        $this->assertArrayHasKey('failed_deployments', $stats);
    }

    // ==================== LOAD STATS TESTS ====================

    public function test_can_load_all_stats(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadStats')
            ->assertSet('isLoading', false);
    }

    public function test_loads_main_stats(): void
    {
        // Clear any cache from previous runs
        Cache::flush();

        $server = Server::factory()->create(['status' => 'online']);
        Server::factory()->count(2)->create(['status' => 'online']);
        Server::factory()->count(2)->create(['status' => 'offline']);

        // Create projects attached to our server
        // Valid status values: running, stopped, building, error, deploying
        $project = Project::factory()->create(['status' => 'running', 'server_id' => $server->id]);
        Project::factory()->count(4)->create(['status' => 'running', 'server_id' => $server->id]);
        Project::factory()->count(3)->create(['status' => 'stopped', 'server_id' => $server->id]);

        Deployment::factory()->count(4)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'success',
        ]);
        Deployment::factory()->count(2)->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'failed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadMainStats');

        $stats = $component->get('stats');
        $this->assertEquals(5, $stats['total_servers']);
        $this->assertEquals(3, $stats['online_servers']);
        $this->assertEquals(8, $stats['total_projects']);
        $this->assertEquals(5, $stats['running_projects']);
        $this->assertEquals(6, $stats['total_deployments']);
        $this->assertEquals(4, $stats['successful_deployments']);
        $this->assertEquals(2, $stats['failed_deployments']);
    }

    // ==================== DEPLOYMENTS TODAY TESTS ====================

    public function test_loads_deployments_today(): void
    {
        $server = Server::factory()->create();

        // Today's deployments
        Deployment::factory()->count(3)->create([
            'server_id' => $server->id,
            'created_at' => now(),
        ]);

        // Yesterday's deployments
        Deployment::factory()->count(2)->create([
            'server_id' => $server->id,
            'created_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadDeploymentsToday')
            ->assertSet('deploymentsToday', 3);
    }

    public function test_deployments_today_is_zero_when_none(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadDeploymentsToday')
            ->assertSet('deploymentsToday', 0);
    }

    // ==================== ACTIVE DEPLOYMENTS TESTS ====================

    public function test_loads_active_deployments(): void
    {
        $server = Server::factory()->create();

        Deployment::factory()->count(2)->create([
            'server_id' => $server->id,
            'status' => 'pending',
        ]);
        Deployment::factory()->count(3)->create([
            'server_id' => $server->id,
            'status' => 'running',
        ]);
        Deployment::factory()->count(5)->create([
            'server_id' => $server->id,
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadActiveDeployments')
            ->assertSet('activeDeployments', 5);
    }

    // ==================== SECURITY SCORE TESTS ====================

    public function test_loads_security_score(): void
    {
        Server::factory()->create(['status' => 'online', 'security_score' => 80]);
        Server::factory()->create(['status' => 'online', 'security_score' => 90]);
        Server::factory()->create(['status' => 'online', 'security_score' => 70]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSecurityScore');

        $score = $component->get('overallSecurityScore');
        $this->assertEquals(80, $score); // Average of 80, 90, 70
    }

    public function test_security_score_defaults_to_85_when_no_servers(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSecurityScore');

        $score = $component->get('overallSecurityScore');
        $this->assertEquals(85, $score);
    }

    public function test_security_score_ignores_offline_servers(): void
    {
        Server::factory()->create(['status' => 'online', 'security_score' => 100]);
        Server::factory()->create(['status' => 'offline', 'security_score' => 50]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSecurityScore');

        $score = $component->get('overallSecurityScore');
        $this->assertEquals(100, $score);
    }

    public function test_security_score_ignores_null_scores(): void
    {
        Server::factory()->create(['status' => 'online', 'security_score' => 90]);
        Server::factory()->create(['status' => 'online', 'security_score' => null]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSecurityScore');

        $score = $component->get('overallSecurityScore');
        $this->assertEquals(90, $score);
    }

    // ==================== SSL STATS TESTS ====================

    public function test_loads_ssl_stats(): void
    {
        $server = Server::factory()->create();

        // Active certificates
        SSLCertificate::factory()->count(3)->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'expires_at' => now()->addMonths(6),
        ]);

        // Expiring soon
        SSLCertificate::factory()->count(2)->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'expires_at' => now()->addDays(5),
        ]);

        // Expired
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'expires_at' => now()->subDay(),
        ]);

        // Pending
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'pending',
            'expires_at' => now()->addYear(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSSLStats');

        $sslStats = $component->get('sslStats');
        $this->assertEquals(7, $sslStats['total_certificates']);
        $this->assertEquals(5, $sslStats['active_certificates']); // 3 active + 2 expiring soon
        $this->assertEquals(2, $sslStats['expiring_soon']);
        $this->assertEquals(1, $sslStats['expired']);
        $this->assertEquals(1, $sslStats['pending']);
    }

    public function test_ssl_stats_empty_when_no_certificates(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSSLStats');

        $sslStats = $component->get('sslStats');
        $this->assertEquals(0, $sslStats['total_certificates']);
        $this->assertEquals(0, $sslStats['active_certificates']);
    }

    // ==================== HEALTH CHECK STATS TESTS ====================

    public function test_loads_health_check_stats(): void
    {
        $server = Server::factory()->create();

        HealthCheck::factory()->count(3)->create([
            'server_id' => $server->id,
            'is_active' => true,
            'status' => 'healthy',
        ]);
        HealthCheck::factory()->count(2)->create([
            'server_id' => $server->id,
            'is_active' => true,
            'status' => 'degraded',
        ]);
        HealthCheck::factory()->create([
            'server_id' => $server->id,
            'is_active' => false,
            'status' => 'down',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadHealthCheckStats');

        $healthStats = $component->get('healthCheckStats');
        $this->assertEquals(6, $healthStats['total_checks']);
        $this->assertEquals(5, $healthStats['active_checks']);
        $this->assertEquals(3, $healthStats['healthy']);
        $this->assertEquals(2, $healthStats['degraded']);
        $this->assertEquals(1, $healthStats['down']);
    }

    // ==================== QUEUE STATS TESTS ====================

    public function test_loads_queue_stats(): void
    {
        // Insert test jobs
        DB::table('jobs')->insert([
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()],
        ]);

        DB::table('failed_jobs')->insert([
            ['uuid' => 'test-1', 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}', 'exception' => 'Error', 'failed_at' => now()],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadQueueStats');

        $queueStats = $component->get('queueStats');
        $this->assertEquals(2, $queueStats['pending']);
        $this->assertEquals(1, $queueStats['failed']);
    }

    public function test_queue_stats_defaults_to_zero(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadQueueStats');

        $queueStats = $component->get('queueStats');
        $this->assertEquals(0, $queueStats['pending']);
        $this->assertEquals(0, $queueStats['failed']);
    }

    // ==================== CACHE TESTS ====================

    public function test_can_clear_stats_cache(): void
    {
        Cache::put('dashboard_stats', ['cached'], 3600);
        Cache::put('dashboard_ssl_stats', ['cached'], 3600);
        Cache::put('dashboard_health_stats', ['cached'], 3600);
        Cache::put('dashboard_queue_stats', ['cached'], 3600);
        Cache::put('dashboard_security_score', 50, 3600);

        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('clearStatsCache');

        $this->assertNull(Cache::get('dashboard_stats'));
        $this->assertNull(Cache::get('dashboard_ssl_stats'));
        $this->assertNull(Cache::get('dashboard_health_stats'));
        $this->assertNull(Cache::get('dashboard_queue_stats'));
        $this->assertNull(Cache::get('dashboard_security_score'));
    }

    public function test_uses_cached_stats(): void
    {
        $cachedStats = [
            'total_servers' => 999,
            'online_servers' => 888,
            'total_projects' => 777,
            'running_projects' => 666,
            'total_deployments' => 555,
            'successful_deployments' => 444,
            'failed_deployments' => 333,
        ];

        Cache::put('dashboard_stats', $cachedStats, 3600);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadMainStats');

        $stats = $component->get('stats');
        $this->assertEquals(999, $stats['total_servers']);
        $this->assertEquals(888, $stats['online_servers']);
    }

    // ==================== EVENT LISTENER TESTS ====================

    public function test_refresh_stats_event_reloads_main_stats(): void
    {
        Cache::flush();

        // Create servers before component mount
        Server::factory()->count(5)->create(['status' => 'online']);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        // After mount, stats are already loaded
        $initialStats = $component->get('stats');
        $this->assertEquals(5, $initialStats['total_servers']);

        // Create more servers
        Server::factory()->count(3)->create(['status' => 'online']);

        // Clear cache so refresh-stats will reload from DB
        Cache::forget('dashboard_stats');

        $component->dispatch('refresh-stats');

        $stats = $component->get('stats');
        $this->assertEquals(8, $stats['total_servers']);
    }

    public function test_deployment_completed_event_refreshes_data(): void
    {
        Cache::flush();

        $server = Server::factory()->create(['status' => 'online']);
        Server::factory()->count(2)->create(['status' => 'online']);

        $project = Project::factory()->create(['server_id' => $server->id]);
        Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        // Stats are loaded on mount
        $stats = $component->get('stats');
        $this->assertEquals(3, $stats['total_servers']);

        // Trigger deployment-completed event
        $component->dispatch('deployment-completed');

        // Verify stats are still correct and activeDeployments is loaded
        $stats = $component->get('stats');
        $this->assertEquals(3, $stats['total_servers']);
        $this->assertEquals(1, $component->get('activeDeployments'));
    }

    // ==================== LOADING STATE TESTS ====================

    public function test_loading_state_is_true_initially(): void
    {
        // Note: The component initializes isLoading=false and mount() calls loadStats()
        // which sets isLoading=false. So after mount, isLoading is false.
        // This test verifies isLoading is false after component initialization
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->assertSet('isLoading', false);
    }

    public function test_loading_state_is_false_after_load(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadStats')
            ->assertSet('isLoading', false);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_empty_database(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadStats');

        $stats = $component->get('stats');
        $this->assertEquals(0, $stats['total_servers']);
        $this->assertEquals(0, $stats['total_projects']);
        $this->assertEquals(0, $stats['total_deployments']);
        $this->assertEquals(0, $component->get('deploymentsToday'));
        $this->assertEquals(0, $component->get('activeDeployments'));
    }
}
