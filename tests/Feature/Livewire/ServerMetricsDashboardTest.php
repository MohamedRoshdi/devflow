<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerMetricsDashboard;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use App\Services\ServerMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class ServerMetricsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->online()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertOk();
    }

    public function test_guest_cannot_access_component(): void
    {
        Livewire::test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertUnauthorized();
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('period', '1h')
            ->assertSet('liveMode', true)
            ->assertSet('processView', 'cpu')
            ->assertSet('isCollecting', false)
            ->assertSet('isLoadingProcesses', false);
    }

    public function test_component_displays_current_metrics(): void
    {
        $metric = ServerMetric::factory()->healthy()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 35.8,
            'load_average_1' => 1.5,
            'recorded_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('latestMetric.cpu_usage', '45.50')
            ->assertSet('latestMetric.memory_usage', '60.20')
            ->assertSet('latestMetric.disk_usage', '35.80')
            ->assertSet('latestMetric.load_average_1', '1.50');
    }

    public function test_chart_data_returns_formatted_metrics(): void
    {
        // Create metrics at different times
        $now = now();
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 40.5,
            'memory_usage' => 50.2,
            'disk_usage' => 30.8,
            'load_average_1' => 1.2,
            'recorded_at' => $now->copy()->subMinutes(30),
        ]);

        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 55.3,
            'memory_usage' => 65.4,
            'disk_usage' => 32.1,
            'load_average_1' => 2.1,
            'recorded_at' => $now->copy()->subMinutes(15),
        ]);

        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 45.7,
            'memory_usage' => 58.9,
            'disk_usage' => 33.5,
            'load_average_1' => 1.8,
            'recorded_at' => $now,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $chartData = $component->get('chartData');

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('cpu', $chartData);
        $this->assertArrayHasKey('memory', $chartData);
        $this->assertArrayHasKey('disk', $chartData);
        $this->assertArrayHasKey('load', $chartData);

        // Verify data is sorted by time and contains 3 metrics
        $this->assertCount(3, $chartData['labels']);
        $this->assertCount(3, $chartData['cpu']);
        $this->assertCount(3, $chartData['memory']);

        // Verify values are rounded correctly
        $this->assertEquals(40.5, $chartData['cpu'][0]);
        $this->assertEquals(55.3, $chartData['cpu'][1]);
        $this->assertEquals(45.7, $chartData['cpu'][2]);
    }

    public function test_alert_status_shows_healthy_when_metrics_are_normal(): void
    {
        ServerMetric::factory()->healthy()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 45.0,
            'memory_usage' => 50.0,
            'disk_usage' => 40.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('healthy', $alertStatus['status']);
        $this->assertEmpty($alertStatus['alerts']);
    }

    public function test_alert_status_shows_warning_for_high_cpu(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 85.0,
            'memory_usage' => 50.0,
            'disk_usage' => 40.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('warning', $alertStatus['status']);
        $this->assertCount(1, $alertStatus['alerts']);
        $this->assertEquals('warning', $alertStatus['alerts'][0]['type']);
        $this->assertEquals('CPU', $alertStatus['alerts'][0]['metric']);
        $this->assertEquals(85.0, $alertStatus['alerts'][0]['value']);
    }

    public function test_alert_status_shows_critical_for_very_high_cpu(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 95.0,
            'memory_usage' => 50.0,
            'disk_usage' => 40.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('critical', $alertStatus['status']);
        $this->assertCount(1, $alertStatus['alerts']);
        $this->assertEquals('critical', $alertStatus['alerts'][0]['type']);
        $this->assertEquals('CPU', $alertStatus['alerts'][0]['metric']);
    }

    public function test_alert_status_shows_warning_for_high_memory(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 78.0,
            'disk_usage' => 40.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('warning', $alertStatus['status']);
        $this->assertCount(1, $alertStatus['alerts']);
        $this->assertEquals('Memory', $alertStatus['alerts'][0]['metric']);
    }

    public function test_alert_status_shows_critical_for_high_disk(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 50.0,
            'memory_usage' => 50.0,
            'disk_usage' => 92.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('critical', $alertStatus['status']);
        $this->assertCount(1, $alertStatus['alerts']);
        $this->assertEquals('Disk', $alertStatus['alerts'][0]['metric']);
    }

    public function test_alert_status_shows_multiple_alerts(): void
    {
        ServerMetric::factory()->critical()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 95.0,
            'memory_usage' => 88.0,
            'disk_usage' => 93.0,
            'recorded_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('critical', $alertStatus['status']);
        $this->assertCount(3, $alertStatus['alerts']);

        // Verify all three metrics are in alerts
        $metrics = array_column($alertStatus['alerts'], 'metric');
        $this->assertContains('CPU', $metrics);
        $this->assertContains('Memory', $metrics);
        $this->assertContains('Disk', $metrics);
    }

    public function test_refresh_metrics_collects_new_metrics(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                    'cpu_usage' => 0.0,
                    'recorded_at' => now(),
                ]));

            $mock->shouldReceive('collectMetrics')
                ->once()
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                    'cpu_usage' => 55.0,
                    'recorded_at' => now(),
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertSet('isCollecting', false)
            ->assertDispatched('notification', type: 'success', message: 'Metrics collected successfully!')
            ->assertDispatched('metrics-chart-update');
    }

    public function test_refresh_metrics_handles_exceptions(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('collectMetrics')
                ->once()
                ->andThrow(new \Exception('SSH connection failed'));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertSet('isCollecting', false)
            ->assertDispatched('notification', type: 'error', message: 'Failed to collect metrics: SSH connection failed');
    }

    public function test_set_period_changes_time_range_and_updates_metrics(): void
    {
        $now = now();

        // Create metrics for different time periods
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => $now->copy()->subHours(2),
        ]);

        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => $now->copy()->subMinutes(30),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('period', '1h')
            ->call('setPeriod', '24h')
            ->assertSet('period', '24h')
            ->assertDispatched('metrics-chart-update');
    }

    public function test_toggle_live_mode_changes_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('liveMode', true)
            ->call('toggleLiveMode')
            ->assertSet('liveMode', false)
            ->call('toggleLiveMode')
            ->assertSet('liveMode', true);
    }

    public function test_load_top_processes_by_cpu(): void
    {
        $processData = [
            [
                'user' => 'root',
                'pid' => 1234,
                'cpu' => 45.5,
                'mem' => 12.3,
                'command' => 'php artisan queue:work',
                'full_command' => 'php artisan queue:work --sleep=3',
            ],
            [
                'user' => 'www-data',
                'pid' => 5678,
                'cpu' => 23.1,
                'mem' => 8.5,
                'command' => 'nginx: worker process',
                'full_command' => 'nginx: worker process',
            ],
        ];

        $this->mock(ServerMetricsService::class, function (MockInterface $mock) use ($processData): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->with($this->server, 10)
                ->once()
                ->andReturn($processData);
        });

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('processView', 'cpu')
            ->assertSet('isLoadingProcesses', false);

        $this->assertCount(2, $component->get('topProcesses'));
        $this->assertEquals(1234, $component->get('topProcesses')[0]['pid']);
        $this->assertEquals(45.5, $component->get('topProcesses')[0]['cpu']);
    }

    public function test_switch_process_view_to_memory(): void
    {
        $processData = [
            [
                'user' => 'mysql',
                'pid' => 9999,
                'cpu' => 15.0,
                'mem' => 55.8,
                'command' => 'mysqld',
                'full_command' => '/usr/sbin/mysqld',
            ],
        ];

        $this->mock(ServerMetricsService::class, function (MockInterface $mock) use ($processData): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);

            $mock->shouldReceive('getTopProcessesByMemory')
                ->with($this->server, 10)
                ->once()
                ->andReturn($processData);
        });

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('processView', 'cpu')
            ->call('switchProcessView', 'memory')
            ->assertSet('processView', 'memory')
            ->assertSet('isLoadingProcesses', false);
    }

    public function test_switch_process_view_ignores_invalid_view(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('processView', 'cpu')
            ->call('switchProcessView', 'invalid')
            ->assertSet('processView', 'cpu'); // Should remain unchanged
    }

    public function test_refresh_processes_reloads_process_list(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->twice()
                ->andReturn([]);
        });

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('refreshProcesses')
            ->assertDispatched('notification', type: 'success', message: 'Process list refreshed!');
    }

    public function test_load_top_processes_handles_exceptions(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->once()
                ->andThrow(new \Exception('Failed to get processes'));
        });

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('isLoadingProcesses', false);

        $this->assertEmpty($component->get('topProcesses'));
    }

    public function test_on_metrics_updated_event_reloads_metrics(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 50.0,
            'recorded_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->dispatch('metrics-updated')
            ->assertDispatched('metrics-chart-update');
    }

    public function test_handle_realtime_metrics_updates_data(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 50.0,
            'recorded_at' => now(),
        ]);

        $event = [
            'alerts' => [
                [
                    'type' => 'warning',
                    'message' => 'CPU usage is high',
                ],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('handleRealtimeMetrics', $event)
            ->assertDispatched('metrics-chart-update')
            ->assertDispatched('notification', type: 'warning', message: 'CPU usage is high');
    }

    public function test_handle_realtime_metrics_shows_critical_alerts_as_errors(): void
    {
        $event = [
            'alerts' => [
                [
                    'type' => 'critical',
                    'message' => 'CPU usage critical!',
                ],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->call('handleRealtimeMetrics', $event)
            ->assertDispatched('notification', type: 'error', message: 'CPU usage critical!');
    }

    public function test_empty_state_when_no_metrics_exist(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(ServerMetric::factory()->make([
                    'server_id' => $this->server->id,
                    'cpu_usage' => 0.0,
                    'memory_usage' => 0.0,
                    'disk_usage' => 0.0,
                    'load_average_1' => 0.0,
                ]));

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);
        });

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $chartData = $component->get('chartData');

        $this->assertEmpty($chartData['labels']);
        $this->assertEmpty($chartData['cpu']);
        $this->assertEmpty($chartData['memory']);
        $this->assertEmpty($chartData['disk']);
        $this->assertEmpty($chartData['load']);
    }

    public function test_alert_status_returns_unknown_when_no_latest_metric(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getMetricsHistory')
                ->andReturn(collect());

            $mock->shouldReceive('getLatestMetrics')
                ->andReturn(null);

            $mock->shouldReceive('getTopProcessesByCPU')
                ->andReturn([]);
        });

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $component->set('latestMetric', null);
        $alertStatus = $component->get('alertStatus');

        $this->assertEquals('unknown', $alertStatus['status']);
        $this->assertEmpty($alertStatus['alerts']);
    }

    public function test_metrics_are_loaded_on_mount(): void
    {
        ServerMetric::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'recorded_at' => now()->subMinutes(30),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server]);

        $this->assertCount(5, $component->get('metrics'));
        $this->assertNotNull($component->get('latestMetric'));
    }

    public function test_period_filtering_retrieves_correct_timeframe(): void
    {
        $now = now();

        // Create metrics outside the 1h window
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => $now->copy()->subHours(3),
        ]);

        // Create metrics within the 1h window
        $metric1 = ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => $now->copy()->subMinutes(30),
        ]);

        $metric2 = ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'recorded_at' => $now,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerMetricsDashboard::class, ['server' => $this->server])
            ->assertSet('period', '1h');

        // Should only have 2 metrics within 1h window
        $this->assertCount(2, $component->get('metrics'));
    }
}
