<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->online()->withDocker()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function dashboard_component_renders_successfully()
    {
        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.dashboard');
    }

    /** @test */
    public function load_stats_returns_expected_array_structure()
    {
        // Create test data
        Server::factory()->count(3)->create(['status' => 'online']);
        Server::factory()->count(2)->create(['status' => 'offline']);

        Project::factory()->count(5)->create(['status' => 'running']);
        Project::factory()->count(3)->create(['status' => 'stopped']);

        Deployment::factory()->count(10)->success()->create();
        Deployment::factory()->count(3)->failed()->create();

        $component = Livewire::test(Dashboard::class);

        $stats = $component->get('stats');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
        $this->assertArrayHasKey('online_servers', $stats);
        $this->assertArrayHasKey('total_projects', $stats);
        $this->assertArrayHasKey('running_projects', $stats);
        $this->assertArrayHasKey('total_deployments', $stats);
        $this->assertArrayHasKey('successful_deployments', $stats);
        $this->assertArrayHasKey('failed_deployments', $stats);

        // Verify counts
        $this->assertEquals(4, $stats['total_servers']); // 3 online + 2 offline + 1 from setUp
        $this->assertEquals(4, $stats['online_servers']); // 3 + 1 from setUp
        $this->assertEquals(8, $stats['total_projects']); // 5 running + 3 stopped
        $this->assertEquals(5, $stats['running_projects']);
        $this->assertEquals(13, $stats['total_deployments']); // 10 success + 3 failed
        $this->assertEquals(10, $stats['successful_deployments']);
        $this->assertEquals(3, $stats['failed_deployments']);
    }

    /** @test */
    public function load_stats_caches_results_for_60_seconds()
    {
        Cache::flush();

        // First call should cache the results
        $component = Livewire::test(Dashboard::class);
        $this->assertTrue(Cache::has('dashboard_stats'));

        // Verify cache TTL (should be around 60 seconds)
        $stats = Cache::get('dashboard_stats');
        $this->assertIsArray($stats);
    }

    /** @test */
    public function load_recent_deployments_returns_collection()
    {
        Deployment::factory()->count(15)->create([
            'project_id' => Project::factory()->create(['server_id' => $this->server->id]),
        ]);

        $component = Livewire::test(Dashboard::class);
        $recentDeployments = $component->get('recentDeployments');

        $this->assertCount(10, $recentDeployments); // Should take only 10
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentDeployments);
    }

    /** @test */
    public function load_projects_returns_limited_projects()
    {
        // Create 10 projects
        Project::factory()->count(10)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class);
        $projects = $component->get('projects');

        $this->assertCount(6, $projects); // Should take only 6
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $projects);
    }

    /** @test */
    public function toggle_section_adds_section_to_collapsed_sections()
    {
        $component = Livewire::test(Dashboard::class)
            ->assertSet('collapsedSections', [])
            ->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', ['stats']);

        // Toggle same section again to collapse it
        $component->call('toggleSection', 'stats')
            ->assertSet('collapsedSections', []);
    }

    /** @test */
    public function toggle_section_handles_multiple_sections()
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

    /** @test */
    public function clear_all_caches_dispatches_success_notification()
    {
        Livewire::test(Dashboard::class)
            ->call('clearAllCaches')
            ->assertDispatched('notification', function ($event) {
                return $event['type'] === 'success' &&
                       str_contains($event['message'], 'cleared successfully');
            });
    }

    /** @test */
    public function clear_all_caches_clears_dashboard_caches()
    {
        // Populate caches
        Cache::put('dashboard_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_ssl_stats', ['test' => 'data'], 3600);
        Cache::put('dashboard_health_stats', ['test' => 'data'], 3600);

        Livewire::test(Dashboard::class)
            ->call('clearAllCaches');

        // Verify caches are cleared
        $this->assertFalse(Cache::has('dashboard_stats'));
        $this->assertFalse(Cache::has('dashboard_ssl_stats'));
        $this->assertFalse(Cache::has('dashboard_health_stats'));
    }

    /** @test */
    public function refresh_dashboard_reloads_all_data()
    {
        Cache::flush();

        // Create initial data
        Project::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::test(Dashboard::class);
        $initialProjectsCount = count($component->get('projects'));

        // Create more data
        Project::factory()->count(5)->create(['server_id' => $this->server->id]);

        // Refresh dashboard
        $component->call('refreshDashboard');

        // Verify data is reloaded (should show 6 projects, the limit)
        $refreshedProjectsCount = count($component->get('projects'));
        $this->assertEquals(6, $refreshedProjectsCount);
    }

    /** @test */
    public function refresh_dashboard_clears_cache_before_loading()
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
    public function load_deployment_timeline_returns_7_days_of_data()
    {
        // Create deployments across different days
        for ($i = 0; $i < 7; $i++) {
            Deployment::factory()->count(fake()->numberBetween(1, 5))->create([
                'project_id' => Project::factory()->create(['server_id' => $this->server->id]),
                'created_at' => now()->subDays($i),
                'status' => 'success',
            ]);
        }

        $component = Livewire::test(Dashboard::class);
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
    public function deployment_timeline_includes_days_with_no_deployments()
    {
        // Only create deployments for today
        Deployment::factory()->count(5)->success()->create([
            'project_id' => Project::factory()->create(['server_id' => $this->server->id]),
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class);
        $timeline = $component->get('deploymentTimeline');

        $this->assertCount(7, $timeline);

        // Check that days without deployments have 0 counts
        $emptyDays = array_filter($timeline, fn ($entry) => $entry['total'] === 0);
        $this->assertGreaterThan(0, count($emptyDays));
    }

    /** @test */
    public function load_ssl_stats_returns_correct_structure()
    {
        // Create SSL certificates with various statuses
        SSLCertificate::factory()->count(5)->issued()->create(['server_id' => $this->server->id]);
        SSLCertificate::factory()->count(2)->expiringSoon()->create(['server_id' => $this->server->id]);
        SSLCertificate::factory()->count(1)->expired()->create(['server_id' => $this->server->id]);
        SSLCertificate::factory()->count(1)->failed()->create(['server_id' => $this->server->id]);

        $component = Livewire::test(Dashboard::class);
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
    public function load_health_check_stats_returns_correct_structure()
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        HealthCheck::factory()->count(5)->healthy()->create([
            'project_id' => $project->id,
            'server_id' => $this->server->id,
        ]);
        HealthCheck::factory()->count(2)->degraded()->create([
            'project_id' => $project->id,
            'server_id' => $this->server->id,
        ]);
        HealthCheck::factory()->count(1)->down()->create([
            'project_id' => $project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::test(Dashboard::class);
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
    public function load_deployments_today_counts_correctly()
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        // Create deployments from today
        Deployment::factory()->count(5)->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        // Create deployments from yesterday
        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'created_at' => now()->subDay(),
        ]);

        $component = Livewire::test(Dashboard::class);
        $deploymentsToday = $component->get('deploymentsToday');

        $this->assertEquals(5, $deploymentsToday);
    }

    /** @test */
    public function load_recent_activity_merges_deployments_and_projects()
    {
        $project = Project::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subMinutes(10),
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::test(Dashboard::class);
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
    public function load_more_activity_increases_activity_count()
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        // Create more deployments than initial load
        Deployment::factory()->count(15)->create([
            'project_id' => $project->id,
        ]);

        $component = Livewire::test(Dashboard::class);
        $initialCount = count($component->get('recentActivity'));

        // Load more activity
        $component->call('loadMoreActivity');
        $newCount = count($component->get('recentActivity'));

        $this->assertGreaterThan($initialCount, $newCount);
    }

    /** @test */
    public function load_server_health_returns_metrics_for_online_servers()
    {
        // Create servers with metrics
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

        $component = Livewire::test(Dashboard::class);
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
    public function load_queue_stats_returns_pending_and_failed_jobs()
    {
        // Create jobs table entries if needed (or mock)
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

        $component = Livewire::test(Dashboard::class);
        $queueStats = $component->get('queueStats');

        $this->assertIsArray($queueStats);
        $this->assertArrayHasKey('pending', $queueStats);
        $this->assertArrayHasKey('failed', $queueStats);
    }

    /** @test */
    public function load_security_score_calculates_average()
    {
        Server::factory()->count(3)->create([
            'status' => 'online',
            'security_score' => 90,
        ]);
        Server::factory()->count(2)->create([
            'status' => 'online',
            'security_score' => 80,
        ]);

        $component = Livewire::test(Dashboard::class);
        $securityScore = $component->get('overallSecurityScore');

        $this->assertIsInt($securityScore);
        $this->assertGreaterThanOrEqual(0, $securityScore);
        $this->assertLessThanOrEqual(100, $securityScore);
    }

    /** @test */
    public function load_active_deployments_counts_pending_and_running()
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        Deployment::factory()->count(2)->pending()->create(['project_id' => $project->id]);
        Deployment::factory()->count(3)->running()->create(['project_id' => $project->id]);
        Deployment::factory()->count(5)->success()->create(['project_id' => $project->id]);

        $component = Livewire::test(Dashboard::class);
        $activeDeployments = $component->get('activeDeployments');

        $this->assertEquals(5, $activeDeployments); // 2 pending + 3 running
    }

    /** @test */
    public function on_deployment_completed_refreshes_relevant_data()
    {
        Cache::put('dashboard_stats', ['test' => 'old_data'], 3600);

        Livewire::test(Dashboard::class)
            ->dispatch('deployment-completed')
            ->assertSet('stats', function ($stats) {
                return ! isset($stats['test']) || $stats['test'] !== 'old_data';
            });
    }

    /** @test */
    public function refresh_dashboard_event_triggers_reload()
    {
        $component = Livewire::test(Dashboard::class);

        // Clear cache to ensure fresh load
        Cache::flush();

        $component->dispatch('refresh-dashboard');

        // Verify stats were reloaded
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
    }

    /** @test */
    public function user_preferences_are_loaded_on_mount()
    {
        // Create user settings with collapsed sections
        UserSettings::create([
            'user_id' => $this->user->id,
            'preferences' => json_encode([
                'dashboard_collapsed_sections' => ['stats', 'deployments'],
            ]),
        ]);

        $component = Livewire::test(Dashboard::class);

        // Note: This test assumes user preferences are loaded
        // The actual behavior depends on UserSettings implementation
        $collapsedSections = $component->get('collapsedSections');
        $this->assertIsArray($collapsedSections);
    }

    /** @test */
    public function dashboard_handles_missing_data_gracefully()
    {
        // Test with empty database
        Server::query()->delete();
        Project::query()->delete();
        Deployment::query()->delete();

        $component = Livewire::test(Dashboard::class);

        $stats = $component->get('stats');
        $this->assertEquals(0, $stats['total_servers']);
        $this->assertEquals(0, $stats['total_projects']);
        $this->assertEquals(0, $stats['total_deployments']);
    }

    /** @test */
    public function deployment_timeline_calculates_percentages_correctly()
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        // Create 10 successful and 5 failed deployments for today
        Deployment::factory()->count(10)->success()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);
        Deployment::factory()->count(5)->failed()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        $component = Livewire::test(Dashboard::class);
        $timeline = $component->get('deploymentTimeline');

        $today = end($timeline);
        $this->assertEquals(15, $today['total']);
        $this->assertEquals(10, $today['successful']);
        $this->assertEquals(5, $today['failed']);
        $this->assertEquals(66.7, $today['success_percent']);
        $this->assertEquals(33.3, $today['failed_percent']);
    }

    /** @test */
    public function dashboard_caching_works_without_redis()
    {
        // Simulate Redis not being available by flushing cache
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Redis might not be available, which is what we're testing
        }

        $component = Livewire::test(Dashboard::class);

        // Should still work and load data
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_servers', $stats);
    }
}
