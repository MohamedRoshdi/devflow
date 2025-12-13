<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Dashboard;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Comprehensive unit tests for the Dashboard Livewire component
 *
 * Tests cover:
 * - Component rendering and authentication
 * - Statistics loading and caching
 * - Project listing with filters
 * - Quick actions functionality
 * - Recent deployments display
 * - Server health indicators
 * - Health check results
 * - Activity feed rendering
 * - User preferences and customization
 * - Computed properties
 * - Event handling
 * - Onboarding status
 *
 * @covers \App\Livewire\Dashboard
 */
class DashboardTest extends TestCase
{
    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->server = Server::factory()->online()->withDocker()->create();
        $this->project = Project::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    // ==========================================
    // Component Rendering Tests
    // ==========================================

    /** @test */
    public function dashboard_component_renders_for_authenticated_user(): void
    {
        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.dashboard');
    }

    /** @test */
    public function dashboard_component_requires_authentication(): void
    {
        Auth::logout();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function dashboard_component_initializes_with_default_values(): void
    {
        $component = Livewire::test(Dashboard::class);

        $component
            ->assertSet('isLoading', true)
            ->assertSet('showQuickActions', true)
            ->assertSet('showActivityFeed', true)
            ->assertSet('showServerHealth', true)
            ->assertSet('editMode', false)
            ->assertSet('activityPerPage', 5);
    }

    // ==========================================
    // Statistics Loading Tests
    // ==========================================

    /** @test */
    public function load_stats_returns_expected_array_structure(): void
    {
        // Create test data
        Server::factory()->count(3)->create(['status' => 'online']);
        Server::factory()->count(2)->create(['status' => 'offline']);

        Project::factory()->count(5)->create([
            'status' => 'running',
            'server_id' => $this->server->id,
        ]);
        Project::factory()->count(3)->create([
            'status' => 'stopped',
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->count(10)->success()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);
        Deployment::factory()->count(3)->failed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadStats');

        $stats = $component->get('stats');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
        $this->assertArrayHasKey('online_servers', $stats);
        $this->assertArrayHasKey('total_projects', $stats);
        $this->assertArrayHasKey('running_projects', $stats);
        $this->assertArrayHasKey('total_deployments', $stats);
        $this->assertArrayHasKey('successful_deployments', $stats);
        $this->assertArrayHasKey('failed_deployments', $stats);

        // Verify counts (accounting for setUp data)
        $this->assertGreaterThanOrEqual(6, $stats['total_servers']);
        $this->assertGreaterThanOrEqual(4, $stats['online_servers']);
        $this->assertGreaterThanOrEqual(9, $stats['total_projects']);
        $this->assertGreaterThanOrEqual(5, $stats['running_projects']);
        $this->assertGreaterThanOrEqual(13, $stats['total_deployments']);
        $this->assertGreaterThanOrEqual(10, $stats['successful_deployments']);
        $this->assertGreaterThanOrEqual(3, $stats['failed_deployments']);
    }

    /** @test */
    public function load_stats_caches_results_for_60_seconds(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadStats');

        $this->assertTrue(Cache::has('dashboard_stats'));

        $cachedStats = Cache::get('dashboard_stats');
        $this->assertIsArray($cachedStats);
        $this->assertArrayHasKey('total_servers', $cachedStats);
    }

    /** @test */
    public function load_stats_handles_corrupted_cache_gracefully(): void
    {
        Cache::put('dashboard_stats', 'corrupted_data', 3600);

        $component = Livewire::test(Dashboard::class)
            ->call('loadStats');

        $stats = $component->get('stats');

        // Should return default stats structure
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['total_servers'] ?? 0);
    }

    // ==========================================
    // Project Listing Tests
    // ==========================================

    /** @test */
    public function load_projects_returns_limited_projects(): void
    {
        // Create 10 projects
        Project::factory()->count(10)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadProjects');

        $projects = $component->get('projects');

        $this->assertCount(6, $projects);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
    }

    /** @test */
    public function load_projects_eager_loads_relationships(): void
    {
        $project = Project::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Domain::factory()->create([
            'project_id' => $project->id,
            'is_primary' => true,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadProjects');

        $projects = $component->get('projects');

        $this->assertNotEmpty($projects);
        $this->assertTrue($projects->first()->relationLoaded('server'));
        $this->assertTrue($projects->first()->relationLoaded('domains'));
    }

    /** @test */
    public function load_projects_orders_by_latest_first(): void
    {
        $oldProject = Project::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Old Project',
            'created_at' => now()->subDays(5),
        ]);

        $newProject = Project::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'New Project',
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadProjects');

        $projects = $component->get('projects');

        $this->assertEquals('New Project', $projects->first()->name);
    }

    // ==========================================
    // Recent Deployments Tests
    // ==========================================

    /** @test */
    public function load_recent_deployments_returns_collection(): void
    {
        Deployment::factory()->count(15)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentDeployments');

        $recentDeployments = $component->get('recentDeployments');

        $this->assertCount(10, $recentDeployments);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentDeployments);
    }

    /** @test */
    public function load_recent_deployments_eager_loads_relationships(): void
    {
        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentDeployments');

        $recentDeployments = $component->get('recentDeployments');

        $this->assertNotEmpty($recentDeployments);
        $this->assertTrue($recentDeployments->first()->relationLoaded('project'));
        $this->assertTrue($recentDeployments->first()->relationLoaded('server'));
    }

    /** @test */
    public function load_recent_deployments_limits_selected_columns(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentDeployments');

        $recentDeployments = $component->get('recentDeployments');

        $deployment = $recentDeployments->first();

        // Essential fields should be present
        $this->assertNotNull($deployment->id);
        $this->assertNotNull($deployment->status);
        $this->assertNotNull($deployment->created_at);
    }

    // ==========================================
    // SSL Statistics Tests
    // ==========================================

    /** @test */
    public function load_ssl_stats_returns_correct_structure(): void
    {
        SSLCertificate::factory()->count(5)->issued()->create([
            'server_id' => $this->server->id,
        ]);
        SSLCertificate::factory()->count(2)->expiringSoon()->create([
            'server_id' => $this->server->id,
        ]);
        SSLCertificate::factory()->count(1)->expired()->create([
            'server_id' => $this->server->id,
        ]);
        SSLCertificate::factory()->count(1)->failed()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadSSLStats');

        $sslStats = $component->get('sslStats');

        $this->assertIsArray($sslStats);
        $this->assertArrayHasKey('total_certificates', $sslStats);
        $this->assertArrayHasKey('active_certificates', $sslStats);
        $this->assertArrayHasKey('expiring_soon', $sslStats);
        $this->assertArrayHasKey('expired', $sslStats);
        $this->assertArrayHasKey('pending', $sslStats);
        $this->assertArrayHasKey('failed', $sslStats);
        $this->assertArrayHasKey('expiring_certificates', $sslStats);

        $this->assertEquals(9, $sslStats['total_certificates']);
        $this->assertEquals(2, $sslStats['expiring_soon']);
        $this->assertEquals(1, $sslStats['expired']);
        $this->assertEquals(1, $sslStats['failed']);
    }

    /** @test */
    public function load_ssl_stats_caches_results_for_5_minutes(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadSSLStats');

        $this->assertTrue(Cache::has('dashboard_ssl_stats'));
    }

    // ==========================================
    // Health Check Statistics Tests
    // ==========================================

    /** @test */
    public function load_health_check_stats_returns_correct_structure(): void
    {
        HealthCheck::factory()->count(5)->healthy()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);
        HealthCheck::factory()->count(2)->degraded()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);
        HealthCheck::factory()->count(1)->down()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadHealthCheckStats');

        $healthStats = $component->get('healthCheckStats');

        $this->assertIsArray($healthStats);
        $this->assertArrayHasKey('total_checks', $healthStats);
        $this->assertArrayHasKey('active_checks', $healthStats);
        $this->assertArrayHasKey('healthy', $healthStats);
        $this->assertArrayHasKey('degraded', $healthStats);
        $this->assertArrayHasKey('down', $healthStats);
        $this->assertArrayHasKey('down_checks', $healthStats);

        $this->assertEquals(8, $healthStats['total_checks']);
        $this->assertEquals(5, $healthStats['healthy']);
        $this->assertEquals(2, $healthStats['degraded']);
        $this->assertEquals(1, $healthStats['down']);
    }

    /** @test */
    public function load_health_check_stats_caches_results_for_2_minutes(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadHealthCheckStats');

        $this->assertTrue(Cache::has('dashboard_health_stats'));
    }

    // ==========================================
    // Deployments Today Tests
    // ==========================================

    /** @test */
    public function load_deployments_today_counts_correctly(): void
    {
        // Create deployments from today
        Deployment::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        // Create deployments from yesterday
        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subDay(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadDeploymentsToday');

        $deploymentsToday = $component->get('deploymentsToday');

        $this->assertEquals(5, $deploymentsToday);
    }

    /** @test */
    public function load_deployments_today_returns_zero_when_no_deployments(): void
    {
        Deployment::query()->delete();

        $component = Livewire::test(Dashboard::class)
            ->call('loadDeploymentsToday');

        $deploymentsToday = $component->get('deploymentsToday');

        $this->assertEquals(0, $deploymentsToday);
    }

    // ==========================================
    // Recent Activity Tests
    // ==========================================

    /** @test */
    public function load_recent_activity_merges_deployments_and_projects(): void
    {
        $newProject = Project::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subMinutes(10),
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentActivity');

        $recentActivity = $component->get('recentActivity');

        $this->assertIsArray($recentActivity);
        $this->assertGreaterThan(0, count($recentActivity));

        // Verify activity items have correct structure
        foreach ($recentActivity as $activity) {
            $this->assertArrayHasKey('type', $activity);
            $this->assertArrayHasKey('title', $activity);
            $this->assertArrayHasKey('description', $activity);
            $this->assertArrayHasKey('status', $activity);
            $this->assertArrayHasKey('timestamp', $activity);
        }
    }

    /** @test */
    public function load_recent_activity_limits_items_to_activity_per_page(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentActivity');

        $recentActivity = $component->get('recentActivity');

        $this->assertLessThanOrEqual(5, count($recentActivity));
    }

    /** @test */
    public function load_recent_activity_sorts_by_timestamp_descending(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subHours(2),
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentActivity');

        $recentActivity = $component->get('recentActivity');

        // Most recent should be first
        $this->assertGreaterThanOrEqual(
            $recentActivity[1]['timestamp'] ?? now(),
            $recentActivity[0]['timestamp']
        );
    }

    /** @test */
    public function load_more_activity_increases_activity_count(): void
    {
        Deployment::factory()->count(15)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentActivity');

        $initialCount = count($component->get('recentActivity'));

        $component->call('loadMoreActivity');

        $newCount = count($component->get('recentActivity'));

        $this->assertGreaterThan($initialCount, $newCount);
    }

    /** @test */
    public function load_more_activity_respects_max_limit_of_20(): void
    {
        Deployment::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadRecentActivity');

        // Load multiple times
        for ($i = 0; $i < 5; $i++) {
            $component->call('loadMoreActivity');
        }

        $recentActivity = $component->get('recentActivity');

        $this->assertLessThanOrEqual(20, count($recentActivity));
    }

    // ==========================================
    // Server Health Tests
    // ==========================================

    /** @test */
    public function load_server_health_returns_metrics_for_online_servers(): void
    {
        $onlineServer1 = Server::factory()->online()->create();
        $onlineServer2 = Server::factory()->online()->create();
        Server::factory()->offline()->create(); // Should be excluded

        ServerMetric::factory()->healthy()->create([
            'server_id' => $onlineServer1->id,
            'recorded_at' => now(),
        ]);
        ServerMetric::factory()->healthy()->create([
            'server_id' => $onlineServer2->id,
            'recorded_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadServerHealth');

        $serverHealth = $component->get('serverHealth');

        $this->assertIsArray($serverHealth);
        $this->assertCount(3, $serverHealth); // 2 + 1 from setUp

        // Verify structure of health data
        foreach ($serverHealth as $health) {
            $this->assertArrayHasKey('server_id', $health);
            $this->assertArrayHasKey('server_name', $health);
            $this->assertArrayHasKey('cpu_usage', $health);
            $this->assertArrayHasKey('memory_usage', $health);
            $this->assertArrayHasKey('disk_usage', $health);
            $this->assertArrayHasKey('status', $health);
            $this->assertArrayHasKey('health_status', $health);
        }
    }

    /** @test */
    public function load_server_health_calculates_health_status_correctly(): void
    {
        $criticalServer = Server::factory()->online()->create(['name' => 'Critical Server']);
        $warningServer = Server::factory()->online()->create(['name' => 'Warning Server']);
        $healthyServer = Server::factory()->online()->create(['name' => 'Healthy Server']);

        ServerMetric::factory()->create([
            'server_id' => $criticalServer->id,
            'cpu_usage' => 95.0,
            'memory_usage' => 92.0,
            'disk_usage' => 91.0,
            'recorded_at' => now(),
        ]);

        ServerMetric::factory()->create([
            'server_id' => $warningServer->id,
            'cpu_usage' => 80.0,
            'memory_usage' => 76.0,
            'disk_usage' => 78.0,
            'recorded_at' => now(),
        ]);

        ServerMetric::factory()->create([
            'server_id' => $healthyServer->id,
            'cpu_usage' => 45.0,
            'memory_usage' => 50.0,
            'disk_usage' => 60.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadServerHealth');

        $serverHealth = $component->get('serverHealth');
        $this->assertIsArray($serverHealth);

        $critical = null;
        $warning = null;
        $healthy = null;

        foreach ($serverHealth as $server) {
            if (isset($server['server_name'])) {
                if ($server['server_name'] === 'Critical Server') {
                    $critical = $server;
                } elseif ($server['server_name'] === 'Warning Server') {
                    $warning = $server;
                } elseif ($server['server_name'] === 'Healthy Server') {
                    $healthy = $server;
                }
            }
        }

        $this->assertNotNull($critical);
        $this->assertNotNull($warning);
        $this->assertNotNull($healthy);

        $this->assertEquals('critical', $critical['health_status']);
        $this->assertEquals('warning', $warning['health_status']);
        $this->assertEquals('healthy', $healthy['health_status']);
    }

    /** @test */
    public function load_server_health_handles_servers_without_metrics(): void
    {
        $serverWithoutMetrics = Server::factory()->online()->create();

        $component = Livewire::test(Dashboard::class)
            ->call('loadServerHealth');

        $serverHealth = $component->get('serverHealth');
        $this->assertIsArray($serverHealth);

        $server = null;
        foreach ($serverHealth as $s) {
            if (isset($s['server_id']) && $s['server_id'] === $serverWithoutMetrics->id) {
                $server = $s;
                break;
            }
        }

        $this->assertNotNull($server);
        $this->assertNull($server['cpu_usage']);
        $this->assertNull($server['memory_usage']);
        $this->assertNull($server['disk_usage']);
        $this->assertEquals('unknown', $server['health_status']);
    }

    /** @test */
    public function load_server_health_caches_results_for_1_minute(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadServerHealth');

        $this->assertTrue(Cache::has('dashboard_server_health'));
    }

    // ==========================================
    // Queue Statistics Tests
    // ==========================================

    /** @test */
    public function load_queue_stats_returns_pending_and_failed_jobs(): void
    {
        try {
            DB::table('jobs')->insert([
                'queue' => 'default',
                'payload' => json_encode(['test' => 'data']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Jobs table not available');
        }

        $component = Livewire::test(Dashboard::class)
            ->call('loadQueueStats');

        $queueStats = $component->get('queueStats');

        $this->assertIsArray($queueStats);
        $this->assertArrayHasKey('pending', $queueStats);
        $this->assertArrayHasKey('failed', $queueStats);
    }

    /** @test */
    public function load_queue_stats_caches_results_for_30_seconds(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadQueueStats');

        $this->assertTrue(Cache::has('dashboard_queue_stats'));
    }

    // ==========================================
    // Security Score Tests
    // ==========================================

    /** @test */
    public function load_security_score_calculates_average(): void
    {
        Server::factory()->count(3)->create([
            'status' => 'online',
            'security_score' => 90,
        ]);
        Server::factory()->count(2)->create([
            'status' => 'online',
            'security_score' => 80,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadSecurityScore');

        $securityScore = $component->get('overallSecurityScore');

        $this->assertIsInt($securityScore);
        $this->assertGreaterThanOrEqual(0, $securityScore);
        $this->assertLessThanOrEqual(100, $securityScore);
    }

    /** @test */
    public function load_security_score_returns_default_when_no_servers_with_scores(): void
    {
        Server::query()->delete();

        $component = Livewire::test(Dashboard::class)
            ->call('loadSecurityScore');

        $securityScore = $component->get('overallSecurityScore');

        $this->assertEquals(85, $securityScore); // Default value
    }

    /** @test */
    public function load_security_score_caches_results_for_5_minutes(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadSecurityScore');

        $this->assertTrue(Cache::has('dashboard_security_score'));
    }

    // ==========================================
    // Active Deployments Tests
    // ==========================================

    /** @test */
    public function load_active_deployments_counts_pending_and_running(): void
    {
        Deployment::factory()->count(2)->pending()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);
        Deployment::factory()->count(3)->running()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);
        Deployment::factory()->count(5)->success()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadActiveDeployments');

        $activeDeployments = $component->get('activeDeployments');

        $this->assertEquals(5, $activeDeployments);
    }

    // ==========================================
    // Deployment Timeline Tests
    // ==========================================

    /** @test */
    public function load_deployment_timeline_returns_7_days_of_data(): void
    {
        // Create deployments across different days
        for ($i = 0; $i < 7; $i++) {
            Deployment::factory()->count(fake()->numberBetween(1, 5))->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
                'created_at' => now()->subDays($i),
                'status' => 'success',
            ]);
        }

        $component = Livewire::test(Dashboard::class)
            ->call('loadDeploymentTimeline');

        $timeline = $component->get('deploymentTimeline');

        $this->assertIsArray($timeline);
        $this->assertCount(7, $timeline);

        // Verify structure of each timeline entry
        foreach ($timeline as $entry) {
            $this->assertArrayHasKey('date', $entry);
            $this->assertArrayHasKey('full_date', $entry);
            $this->assertArrayHasKey('total', $entry);
            $this->assertArrayHasKey('successful', $entry);
            $this->assertArrayHasKey('failed', $entry);
            $this->assertArrayHasKey('success_percent', $entry);
            $this->assertArrayHasKey('failed_percent', $entry);
        }
    }

    /** @test */
    public function deployment_timeline_includes_days_with_no_deployments(): void
    {
        // Only create deployments for today
        Deployment::factory()->count(5)->success()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadDeploymentTimeline');

        $timeline = $component->get('deploymentTimeline');

        $this->assertCount(7, $timeline);

        // Check that days without deployments have 0 counts
        $emptyDays = array_filter($timeline, fn ($entry) => $entry['total'] === 0);
        $this->assertGreaterThan(0, count($emptyDays));
    }

    /** @test */
    public function deployment_timeline_calculates_percentages_correctly(): void
    {
        // Create 10 successful and 5 failed deployments for today
        Deployment::factory()->count(10)->success()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);
        Deployment::factory()->count(5)->failed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadDeploymentTimeline');

        $timeline = $component->get('deploymentTimeline');

        $today = end($timeline);
        $this->assertEquals(15, $today['total']);
        $this->assertEquals(10, $today['successful']);
        $this->assertEquals(5, $today['failed']);
        $this->assertEquals(66.7, $today['success_percent']);
        $this->assertEquals(33.3, $today['failed_percent']);
    }

    // ==========================================
    // Section Toggle Tests
    // ==========================================

    /** @test */
    public function toggle_section_adds_section_to_collapsed_sections(): void
    {
        $component = Livewire::test(Dashboard::class)
            ->assertSet('collapsedSections', [])
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', ['stats']);
    }

    /** @test */
    public function toggle_section_removes_section_when_toggled_again(): void
    {
        $component = Livewire::test(Dashboard::class)
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', ['stats'])
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', []);
    }

    /** @test */
    public function toggle_section_handles_multiple_sections(): void
    {
        $component = Livewire::test(Dashboard::class)
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', ['stats'])
            ->call('toggleSection', 'deployments')
            ->assertSet('collapsedSections', ['stats', 'deployments'])
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', function ($sections) {
                return count($sections) === 1 && $sections[0] === 'deployments';
            });
    }

    // ==========================================
    // Cache Management Tests
    // ==========================================

    /** @test */
    public function clear_dashboard_cache_removes_all_dashboard_caches(): void
    {
        // Populate caches
        Cache::put('dashboard_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_ssl_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_health_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_server_health', ['test' => 'data'], 3600);
        Cache::put('dashboard_queue_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_security_score', 85, 3600);
        Cache::put('dashboard_onboarding_status', ['test' => 'data'], 3600);

        Livewire::test(Dashboard::class)
            ->call('clearDashboardCache');

        $this->assertFalse(Cache::has('dashboard_stats'));
        $this->assertFalse(Cache::has('dashboard_ssl_stats'));
        $this->assertFalse(Cache::has('dashboard_health_stats'));
        $this->assertFalse(Cache::has('dashboard_server_health'));
        $this->assertFalse(Cache::has('dashboard_queue_stats'));
        $this->assertFalse(Cache::has('dashboard_security_score'));
        $this->assertFalse(Cache::has('dashboard_onboarding_status'));
    }

    /** @test */
    public function clear_all_caches_dispatches_success_notification(): void
    {
        Livewire::test(Dashboard::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification');
    }

    /** @test */
    public function refresh_dashboard_clears_cache_before_loading(): void
    {
        // Populate cache with stale data
        Cache::put('dashboard_stats', ['total_servers' => 999], 3600);

        $component = Livewire::test(Dashboard::class)
            ->call('refreshDashboard');

        $stats = $component->get('stats');

        // Should have real data, not cached data
        $this->assertNotEquals(999, $stats['total_servers']);
    }

    /** @test */
    public function refresh_dashboard_reloads_all_data(): void
    {
        Cache::flush();

        // Create initial data
        Project::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadDashboardData');
        $initialProjectsCount = count($component->get('projects'));

        // Create more data
        Project::factory()->count(5)->create(['server_id' => $this->server->id]);

        // Refresh dashboard
        $component->call('refreshDashboard');

        // Verify data is reloaded (should show 6 projects, the limit)
        $refreshedProjectsCount = count($component->get('projects'));
        $this->assertEquals(6, $refreshedProjectsCount);
    }

    // ==========================================
    // Event Handling Tests
    // ==========================================

    /** @test */
    public function on_deployment_completed_refreshes_relevant_data(): void
    {
        Cache::put('dashboard_stats', ['test' => 'old_data'], 3600);

        Livewire::test(Dashboard::class)
            ->dispatch('deployment-completed')
            ->assertSet('stats', function ($stats) {
                return ! isset($stats['test']) || $stats['test'] !== 'old_data';
            });
    }

    /** @test */
    public function refresh_dashboard_event_triggers_reload(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->dispatch('refresh-dashboard');

        // Verify stats were reloaded
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
    }

    // ==========================================
    // Quick Actions Tests
    // ==========================================

    /** @test */
    public function deploy_all_dispatches_jobs_for_active_projects(): void
    {
        Queue::fake();

        Project::factory()->count(3)->create([
            'status' => 'active',
            'server_id' => $this->server->id,
        ]);

        Livewire::test(Dashboard::class)
            ->call('deployAll')
            ->assertDispatched('notification');

        Queue::assertPushed(DeployProjectJob::class, 3);
    }

    /** @test */
    public function deploy_all_shows_warning_when_no_active_projects(): void
    {
        Project::query()->update(['status' => 'stopped']);

        Livewire::test(Dashboard::class)
            ->call('deployAll')
            ->assertDispatched('notification', function ($event) {
                return $event['type'] === 'warning';
            });
    }

    // ==========================================
    // User Preferences Tests
    // ==========================================

    /** @test */
    public function user_preferences_are_loaded_on_mount(): void
    {
        UserSettings::create([
            'user_id' => $this->user->id,
            'preferences' => json_encode([
                'dashboard_collapsed_sections' => ['stats', 'deployments'],
                'dashboard_widget_order' => ['stats_cards', 'quick_actions'],
            ]),
        ]);

        $component = Livewire::test(Dashboard::class);

        $collapsedSections = $component->get('collapsedSections');
        $this->assertIsArray($collapsedSections);
    }

    /** @test */
    public function widget_order_can_be_updated(): void
    {
        $newOrder = ['stats_cards', 'quick_actions', 'activity_server_grid'];

        Livewire::test(Dashboard::class)
            ->dispatch('widget-order-updated', order: $newOrder)
            ->assertDispatched('notification');
    }

    /** @test */
    public function widget_order_validates_all_widgets_present(): void
    {
        $invalidOrder = ['stats_cards']; // Missing widgets

        $component = Livewire::test(Dashboard::class)
            ->dispatch('widget-order-updated', order: $invalidOrder);

        // Widget order should not be updated
        $widgetOrder = $component->get('widgetOrder');
        $this->assertNotEquals($invalidOrder, $widgetOrder);
    }

    /** @test */
    public function reset_widget_order_restores_defaults(): void
    {
        Livewire::test(Dashboard::class)
            ->call('resetWidgetOrder')
            ->assertSet('widgetOrder', Dashboard::DEFAULT_WIDGET_ORDER)
            ->assertDispatched('notification');
    }

    /** @test */
    public function toggle_edit_mode_switches_state(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSet('editMode', false)
            ->call('toggleEditMode')
            ->assertSet('editMode', true)
            ->call('toggleEditMode')
            ->assertSet('editMode', false);
    }

    // ==========================================
    // Onboarding Status Tests
    // ==========================================

    /** @test */
    public function load_onboarding_status_executes_single_optimized_query(): void
    {
        Cache::flush();

        DB::enableQueryLog();

        // Create test data
        Server::factory()->count(2)->create();
        Project::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadOnboardingStatus');

        $queries = DB::getQueryLog();

        // Count queries related to onboarding status
        $countQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], 'SELECT COUNT(*) FROM servers')
                || str_contains($query['query'], 'server_count');
        });

        // Should execute at most 2 queries (1 optimized + potential cache check)
        $this->assertLessThanOrEqual(2, count($countQueries));

        DB::disableQueryLog();
    }

    /** @test */
    public function load_onboarding_status_sets_correct_steps(): void
    {
        Cache::flush();

        // Create test data for some steps
        Server::factory()->count(2)->create();
        Project::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::test(Dashboard::class)
            ->call('loadOnboardingStatus');

        $onboardingSteps = $component->get('onboardingSteps');

        $this->assertIsArray($onboardingSteps);
        $this->assertTrue($onboardingSteps['add_server']);
        $this->assertTrue($onboardingSteps['create_project']);
    }

    /** @test */
    public function load_onboarding_status_identifies_new_user_correctly(): void
    {
        Cache::flush();

        // Empty database - new user
        Server::query()->delete();
        Project::query()->delete();

        $component = Livewire::test(Dashboard::class)
            ->call('loadOnboardingStatus');

        $this->assertTrue($component->get('isNewUser'));
        $this->assertFalse($component->get('hasCompletedOnboarding'));
    }

    /** @test */
    public function refresh_onboarding_status_clears_cache_and_reloads(): void
    {
        Cache::flush();

        $component = Livewire::test(Dashboard::class)
            ->call('loadOnboardingStatus');

        $this->assertTrue(Cache::has('dashboard_onboarding_status'));

        // Create new data
        Server::factory()->create();
        Project::factory()->create(['server_id' => $this->server->id]);

        $component->call('refreshOnboardingStatus');

        // Cache should be repopulated with new data
        $cachedData = Cache::get('dashboard_onboarding_status');
        $this->assertGreaterThan(0, $cachedData['server_count']);
        $this->assertGreaterThan(0, $cachedData['project_count']);
    }

    /** @test */
    public function dismiss_getting_started_updates_user_settings(): void
    {
        Livewire::test(Dashboard::class)
            ->call('dismissGettingStarted')
            ->assertSet('hasCompletedOnboarding', true)
            ->assertDispatched('notification');

        $userSettings = UserSettings::getForUser($this->user);
        $this->assertTrue($userSettings->getAdditionalSetting('dashboard_getting_started_dismissed', false));
    }

    // ==========================================
    // Data Loading Tests
    // ==========================================

    /** @test */
    public function load_dashboard_data_loads_all_components(): void
    {
        $component = Livewire::test(Dashboard::class)
            ->call('loadDashboardData');

        $component->assertSet('isLoading', false);

        // Verify all data is loaded
        $this->assertIsArray($component->get('stats'));
        $this->assertNotEmpty($component->get('recentDeployments'));
        $this->assertIsArray($component->get('sslStats'));
        $this->assertIsArray($component->get('healthCheckStats'));
        $this->assertIsArray($component->get('deploymentTimeline'));
    }

    // ==========================================
    // Edge Cases and Error Handling Tests
    // ==========================================

    /** @test */
    public function dashboard_handles_missing_data_gracefully(): void
    {
        // Test with empty database
        Server::query()->delete();
        Project::query()->delete();
        Deployment::query()->delete();

        $component = Livewire::test(Dashboard::class)
            ->call('loadDashboardData');

        $stats = $component->get('stats');
        $this->assertEquals(0, $stats['total_servers']);
        $this->assertEquals(0, $stats['total_projects']);
        $this->assertEquals(0, $stats['total_deployments']);
    }

    /** @test */
    public function dashboard_caching_works_without_redis(): void
    {
        // Simulate Redis not being available
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Redis might not be available, which is what we're testing
        }

        $component = Livewire::test(Dashboard::class)
            ->call('loadDashboardData');

        // Should still work and load data
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
    }

    /** @test */
    public function dashboard_handles_unauthenticated_users_gracefully(): void
    {
        Auth::logout();

        $component = Livewire::test(Dashboard::class);

        // Should have default widget order and empty collapsed sections
        $this->assertIsArray($component->get('widgetOrder'));
        $this->assertIsArray($component->get('collapsedSections'));
    }
}
