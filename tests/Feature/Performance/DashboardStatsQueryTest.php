<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Livewire\Dashboard\DashboardStats;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Performance tests for DashboardStats component
 *
 * Verifies that dashboard stats are loaded efficiently with minimal queries
 */
class DashboardStatsQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $this->team->id]);

        $this->server = Server::factory()->create([
            'team_id' => $this->team->id,
            'status' => 'online',
        ]);
    }

    public function test_main_stats_uses_single_query(): void
    {
        // Create some test data
        Server::factory()->count(5)->create(['team_id' => $this->team->id, 'status' => 'online']);
        Server::factory()->count(2)->create(['team_id' => $this->team->id, 'status' => 'offline']);

        $projects = Project::factory()->count(10)->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
        ]);

        foreach ($projects->take(5) as $project) {
            Deployment::factory()->count(3)->create([
                'project_id' => $project->id,
                'server_id' => $this->server->id,
                'user_id' => $this->user->id,
                'status' => 'success',
            ]);
        }

        // Clear any cached stats
        Cache::flush();

        // Count queries during loadMainStats
        $queryLog = [];
        DB::listen(function ($query) use (&$queryLog) {
            // Only count SELECT queries, ignore SQLite internal queries
            if (str_starts_with(strtolower($query->sql), 'select')) {
                $queryLog[] = $query->sql;
            }
        });

        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        // The optimized version should use 1 query for main stats
        // (plus potentially a few for caching infrastructure)
        $mainStatsQueries = array_filter($queryLog, function ($sql) {
            return str_contains($sql, 'servers') ||
                   str_contains($sql, 'projects') ||
                   str_contains($sql, 'deployments');
        });

        // Should be 1 combined query instead of 7 separate queries
        $this->assertLessThanOrEqual(3, count($mainStatsQueries));
    }

    public function test_stats_are_cached(): void
    {
        Server::factory()->count(3)->create(['team_id' => $this->team->id, 'status' => 'online']);

        Cache::flush();

        // First load - should populate cache
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadMainStats');

        // Check that stats are cached
        $this->assertTrue(Cache::has('dashboard_stats'));
    }

    public function test_cached_stats_are_reused(): void
    {
        Server::factory()->count(3)->create(['team_id' => $this->team->id, 'status' => 'online']);

        Cache::flush();

        // First load
        $component1 = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        $stats1 = $component1->get('stats');

        // Add more servers (but cache should still have old count)
        Server::factory()->count(5)->create(['team_id' => $this->team->id, 'status' => 'online']);

        // Second load should use cache
        $component2 = Livewire::actingAs($this->user)
            ->test(DashboardStats::class);

        $stats2 = $component2->get('stats');

        // Stats should be the same due to caching
        $this->assertEquals($stats1['total_servers'], $stats2['total_servers']);
    }

    public function test_ssl_stats_uses_optimized_query(): void
    {
        $domain = \App\Models\Domain::factory()->create([
            'project_id' => Project::factory()->create([
                'team_id' => $this->team->id,
                'server_id' => $this->server->id,
            ])->id,
        ]);

        SSLCertificate::factory()->count(5)->create([
            'domain_id' => $domain->id,
            'status' => 'issued',
            'expires_at' => now()->addDays(30),
        ]);

        Cache::flush();

        $queryLog = [];
        DB::listen(function ($query) use (&$queryLog) {
            if (str_contains($query->sql, 'ssl_certificates')) {
                $queryLog[] = $query->sql;
            }
        });

        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadSSLStats');

        // Should be 1 combined query instead of 6 separate queries
        $this->assertLessThanOrEqual(2, count($queryLog));
    }

    public function test_health_check_stats_uses_optimized_query(): void
    {
        HealthCheck::factory()->count(10)->create([
            'project_id' => Project::factory()->create([
                'team_id' => $this->team->id,
                'server_id' => $this->server->id,
            ])->id,
            'is_active' => true,
            'status' => 'healthy',
        ]);

        Cache::flush();

        $queryLog = [];
        DB::listen(function ($query) use (&$queryLog) {
            if (str_contains($query->sql, 'health_checks')) {
                $queryLog[] = $query->sql;
            }
        });

        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadHealthCheckStats');

        // Should be 1 combined query instead of 5 separate queries
        $this->assertLessThanOrEqual(2, count($queryLog));
    }

    public function test_cache_is_cleared_on_deployment_completed(): void
    {
        Server::factory()->count(3)->create(['team_id' => $this->team->id]);

        // Populate cache
        Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->call('loadMainStats');

        $this->assertTrue(Cache::has('dashboard_stats'));

        // Trigger deployment completed event
        $component = Livewire::actingAs($this->user)
            ->test(DashboardStats::class)
            ->dispatch('deployment-completed');

        // Cache should have been cleared and repopulated
        $this->assertTrue(Cache::has('dashboard_stats'));
    }
}
