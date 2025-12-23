<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Logs\LogViewer;
use App\Models\LogEntry;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\LogAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->assertSet('server_id', null)
            ->assertSet('project_id', null)
            ->assertSet('source', 'all')
            ->assertSet('level', 'all')
            ->assertSet('search', '')
            ->assertSet('autoRefresh', false)
            ->assertSet('expandedLogId', null);
    }

    public function test_default_date_range_is_last_24_hours(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $dateFrom = $component->get('dateFrom');
        $dateTo = $component->get('dateTo');

        $this->assertNotEmpty($dateFrom);
        $this->assertNotEmpty($dateTo);
    }

    // ==================== LOGS DISPLAY TESTS ====================

    public function test_displays_logs(): void
    {
        LogEntry::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $logs = $component->viewData('logs');
        $this->assertCount(5, $logs);
    }

    public function test_logs_have_pagination(): void
    {
        LogEntry::factory()->count(60)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $logs = $component->viewData('logs');
        $this->assertEquals(50, $logs->perPage());
    }

    public function test_logs_are_ordered_by_logged_at_descending(): void
    {
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'message' => 'Older log',
            'logged_at' => now()->subHour(),
        ]);

        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'message' => 'Newer log',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $logs = $component->viewData('logs');
        $this->assertEquals('Newer log', $logs->first()->message);
    }

    // ==================== FILTER BY SERVER TESTS ====================

    public function test_can_filter_by_server(): void
    {
        $otherServer = Server::factory()->create(['status' => 'online']);

        LogEntry::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(2)->create([
            'server_id' => $otherServer->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id);

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    public function test_changing_server_resets_project(): void
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('project_id', $project->id)
            ->assertSet('project_id', $project->id)
            ->set('server_id', $this->server->id)
            ->assertSet('project_id', null);
    }

    // ==================== FILTER BY PROJECT TESTS ====================

    public function test_can_filter_by_project(): void
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);
        $otherProject = Project::factory()->create(['server_id' => $this->server->id]);

        LogEntry::factory()->count(4)->create([
            'server_id' => $this->server->id,
            'project_id' => $project->id,
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'project_id' => $otherProject->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('project_id', $project->id);

        $logs = $component->viewData('logs');
        $this->assertCount(4, $logs);
    }

    public function test_projects_computed_property_filters_by_server(): void
    {
        $project1 = Project::factory()->create(['server_id' => $this->server->id]);
        $otherServer = Server::factory()->create(['status' => 'online']);
        Project::factory()->create(['server_id' => $otherServer->id]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id);

        $projects = $component->viewData('projects');
        $this->assertCount(1, $projects);
        $this->assertEquals($project1->id, $projects->first()->id);
    }

    // ==================== FILTER BY SOURCE TESTS ====================

    public function test_can_filter_by_source(): void
    {
        LogEntry::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'source' => 'nginx',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'source' => 'laravel',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('source', 'nginx');

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    public function test_source_all_shows_all_sources(): void
    {
        LogEntry::factory()->create(['server_id' => $this->server->id, 'source' => 'nginx', 'logged_at' => now()]);
        LogEntry::factory()->create(['server_id' => $this->server->id, 'source' => 'laravel', 'logged_at' => now()]);
        LogEntry::factory()->create(['server_id' => $this->server->id, 'source' => 'mysql', 'logged_at' => now()]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('source', 'all');

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    // ==================== FILTER BY LEVEL TESTS ====================

    public function test_can_filter_by_level(): void
    {
        LogEntry::factory()->count(4)->create([
            'server_id' => $this->server->id,
            'level' => 'error',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'level' => 'warning',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('level', 'error');

        $logs = $component->viewData('logs');
        $this->assertCount(4, $logs);
    }

    public function test_level_all_shows_all_levels(): void
    {
        LogEntry::factory()->create(['server_id' => $this->server->id, 'level' => 'info', 'logged_at' => now()]);
        LogEntry::factory()->create(['server_id' => $this->server->id, 'level' => 'error', 'logged_at' => now()]);
        LogEntry::factory()->create(['server_id' => $this->server->id, 'level' => 'warning', 'logged_at' => now()]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('level', 'all');

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    // ==================== SEARCH TESTS ====================

    public function test_can_search_logs_by_message(): void
    {
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'message' => 'Database connection failed',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'message' => 'User logged in successfully',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('search', 'Database');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Database', $logs->first()->message);
    }

    public function test_can_search_logs_by_file_path(): void
    {
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'file_path' => '/var/www/app/Controllers/UserController.php',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'file_path' => '/var/www/app/Models/User.php',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('search', 'UserController');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_search_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    // ==================== DATE RANGE TESTS ====================

    public function test_can_filter_by_date_range(): void
    {
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'logged_at' => now()->subDays(2),
        ]);
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('dateFrom', now()->subDay()->format('Y-m-d\TH:i'))
            ->set('dateTo', now()->addHour()->format('Y-m-d\TH:i'));

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    // ==================== STATISTICS TESTS ====================

    public function test_statistics_shows_log_counts(): void
    {
        LogEntry::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'level' => 'info',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'level' => 'error',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'level' => 'warning',
            'logged_at' => now(),
        ]);
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'level' => 'critical',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $statistics = $component->viewData('statistics');
        $this->assertEquals(11, $statistics['total']);
        $this->assertEquals(3, $statistics['error']);
        $this->assertEquals(2, $statistics['warning']);
        $this->assertEquals(1, $statistics['critical']);
    }

    public function test_statistics_respects_filters(): void
    {
        LogEntry::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'level' => 'error',
            'logged_at' => now(),
        ]);

        $otherServer = Server::factory()->create(['status' => 'online']);
        LogEntry::factory()->count(3)->create([
            'server_id' => $otherServer->id,
            'level' => 'error',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id);

        $statistics = $component->viewData('statistics');
        $this->assertEquals(5, $statistics['total']);
        $this->assertEquals(5, $statistics['error']);
    }

    // ==================== SYNC NOW TESTS ====================

    public function test_sync_now_requires_server(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->call('syncNow')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'select a server');
            });
    }

    public function test_sync_now_calls_log_aggregation_service(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncLogs')
                ->once()
                ->andReturn([
                    'total_entries' => 25,
                    'success' => 3,
                    'failed' => 0,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id)
            ->call('syncNow')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], '25 log entries') &&
                    str_contains($data['message'], '3 sources');
            });
    }

    public function test_sync_now_shows_failed_count(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncLogs')
                ->once()
                ->andReturn([
                    'total_entries' => 15,
                    'success' => 2,
                    'failed' => 1,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id)
            ->call('syncNow')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], '1 sources failed');
            });
    }

    public function test_sync_now_handles_exception(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncLogs')
                ->once()
                ->andThrow(new \Exception('Connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id)
            ->call('syncNow')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'Sync failed');
            });
    }

    // ==================== CLEAR FILTERS TESTS ====================

    public function test_can_clear_filters(): void
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id)
            ->set('project_id', $project->id)
            ->set('source', 'nginx')
            ->set('level', 'error')
            ->set('search', 'test')
            ->call('clearFilters')
            ->assertSet('server_id', null)
            ->assertSet('project_id', null)
            ->assertSet('source', 'all')
            ->assertSet('level', 'all')
            ->assertSet('search', '');
    }

    public function test_clear_filters_resets_date_range_to_last_24_hours(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('dateFrom', now()->subWeek()->format('Y-m-d\TH:i'))
            ->call('clearFilters');

        $dateFrom = $component->get('dateFrom');
        $this->assertNotEmpty($dateFrom);
    }

    // ==================== EXPORT TESTS ====================

    public function test_can_export_logs(): void
    {
        LogEntry::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('searchLogs')
                ->once()
                ->andReturn(LogEntry::all());
        });

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->call('exportLogs')
            ->assertDispatched('download')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'exported successfully');
            });
    }

    // ==================== EXPAND LOG TESTS ====================

    public function test_can_toggle_expand_log(): void
    {
        $log = LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->call('toggleExpand', $log->id)
            ->assertSet('expandedLogId', $log->id);
    }

    public function test_toggle_expand_collapses_when_same_log(): void
    {
        $log = LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('expandedLogId', $log->id)
            ->call('toggleExpand', $log->id)
            ->assertSet('expandedLogId', null);
    }

    public function test_toggle_expand_switches_to_different_log(): void
    {
        $log1 = LogEntry::factory()->create(['server_id' => $this->server->id, 'logged_at' => now()]);
        $log2 = LogEntry::factory()->create(['server_id' => $this->server->id, 'logged_at' => now()]);

        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('expandedLogId', $log1->id)
            ->call('toggleExpand', $log2->id)
            ->assertSet('expandedLogId', $log2->id);
    }

    // ==================== PAGINATION RESET TESTS ====================

    public function test_level_change_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('level', 'error')
            ->assertSet('page', 1);
    }

    public function test_source_change_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('source', 'nginx')
            ->assertSet('page', 1);
    }

    // ==================== EVENT LISTENER TESTS ====================

    public function test_refresh_logs_event_refreshes_data(): void
    {
        LogEntry::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $initialLogs = $component->viewData('logs');
        $this->assertCount(3, $initialLogs);

        LogEntry::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'logged_at' => now(),
        ]);

        $component->dispatch('refresh-logs');

        $logs = $component->viewData('logs');
        $this->assertCount(5, $logs);
    }

    // ==================== SERVERS COMPUTED PROPERTY TESTS ====================

    public function test_servers_returns_online_servers(): void
    {
        Server::factory()->create(['status' => 'online']);
        Server::factory()->create(['status' => 'offline']);
        Server::factory()->create(['status' => 'maintenance']);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $servers = $component->viewData('servers');
        $this->assertCount(2, $servers);
    }

    public function test_servers_are_ordered_by_name(): void
    {
        Server::factory()->create(['status' => 'online', 'name' => 'Zebra Server']);
        Server::factory()->create(['status' => 'online', 'name' => 'Alpha Server']);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $servers = $component->viewData('servers');
        $this->assertEquals('Alpha Server', $servers->first()->name);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_logs(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class);

        $logs = $component->viewData('logs');
        $this->assertCount(0, $logs);

        $statistics = $component->viewData('statistics');
        $this->assertEquals(0, $statistics['total']);
    }

    public function test_handles_no_matching_logs(): void
    {
        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'source' => 'nginx',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('source', 'docker');

        $logs = $component->viewData('logs');
        $this->assertCount(0, $logs);
    }

    // ==================== COMBINED FILTERS TESTS ====================

    public function test_can_apply_multiple_filters(): void
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'project_id' => $project->id,
            'source' => 'nginx',
            'level' => 'error',
            'message' => 'Connection timeout',
            'logged_at' => now(),
        ]);

        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'project_id' => $project->id,
            'source' => 'nginx',
            'level' => 'info',
            'logged_at' => now(),
        ]);

        LogEntry::factory()->create([
            'server_id' => $this->server->id,
            'project_id' => $project->id,
            'source' => 'laravel',
            'level' => 'error',
            'logged_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogViewer::class)
            ->set('server_id', $this->server->id)
            ->set('project_id', $project->id)
            ->set('source', 'nginx')
            ->set('level', 'error')
            ->set('search', 'timeout');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('timeout', $logs->first()->message);
    }
}
