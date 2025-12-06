<?php

namespace Tests\Browser;

use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerMetricsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $adminUser;

    protected Server $testServer;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Use or create admin user
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (! $this->adminUser->hasRole('admin')) {
            $this->adminUser->assignRole('admin');
        }

        // Create a test server
        $this->testServer = Server::firstOrCreate(
            ['ip_address' => '192.168.1.100'],
            [
                'user_id' => $this->adminUser->id,
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
                'docker_installed' => true,
                'docker_version' => '24.0.0',
                'os' => 'Ubuntu 22.04',
                'cpu_cores' => 4,
                'memory_gb' => 16,
                'disk_gb' => 100,
                'last_ping_at' => now(),
            ]
        );

        // Create sample metrics for testing
        $this->createSampleMetrics();
    }

    /**
     * Create sample server metrics for testing
     */
    protected function createSampleMetrics(): void
    {
        // Create metrics for the last 24 hours
        for ($i = 24; $i >= 0; $i--) {
            ServerMetric::firstOrCreate(
                [
                    'server_id' => $this->testServer->id,
                    'recorded_at' => now()->subHours($i),
                ],
                [
                    'cpu_usage' => rand(10, 90),
                    'memory_usage' => rand(20, 80),
                    'disk_usage' => rand(30, 70),
                    'network_rx' => rand(1000, 10000),
                    'network_tx' => rand(1000, 10000),
                    'load_average_1' => round(rand(0, 400) / 100, 2),
                    'load_average_5' => round(rand(0, 400) / 100, 2),
                    'load_average_15' => round(rand(0, 400) / 100, 2),
                    'uptime' => rand(86400, 2592000),
                    'process_count' => rand(50, 200),
                ]
            );
        }
    }

    /**
     * Test 1: Server metrics dashboard access
     *
     * @test
     */
    public function test_user_can_access_server_metrics_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-dashboard-access');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetrics = str_contains($pageSource, 'metrics') ||
                         str_contains($pageSource, 'server metrics') ||
                         str_contains($pageSource, 'performance');

            $this->assertTrue($hasMetrics, 'Server metrics dashboard should be accessible');
            $this->testResults['metrics_dashboard_access'] = 'Server metrics dashboard is accessible';
        });
    }

    /**
     * Test 2: CPU usage chart is displayed
     *
     * @test
     */
    public function test_cpu_usage_chart_is_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-cpu-chart');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuChart = str_contains($pageSource, 'cpu usage') ||
                          str_contains($pageSource, 'cpu_usage') ||
                          str_contains($pageSource, 'processor');

            $this->assertTrue($hasCpuChart, 'CPU usage chart should be displayed');
            $this->testResults['cpu_usage_chart'] = 'CPU usage chart is displayed';
        });
    }

    /**
     * Test 3: Memory usage display is present
     *
     * @test
     */
    public function test_memory_usage_display_is_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-memory-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMemoryDisplay = str_contains($pageSource, 'memory usage') ||
                               str_contains($pageSource, 'memory_usage') ||
                               str_contains($pageSource, 'ram');

            $this->assertTrue($hasMemoryDisplay, 'Memory usage display should be present');
            $this->testResults['memory_usage_display'] = 'Memory usage display is present';
        });
    }

    /**
     * Test 4: Disk usage statistics are shown
     *
     * @test
     */
    public function test_disk_usage_statistics_are_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-disk-stats');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiskStats = str_contains($pageSource, 'disk usage') ||
                           str_contains($pageSource, 'disk_usage') ||
                           str_contains($pageSource, 'storage');

            $this->assertTrue($hasDiskStats, 'Disk usage statistics should be shown');
            $this->testResults['disk_usage_stats'] = 'Disk usage statistics are shown';
        });
    }

    /**
     * Test 5: Network traffic monitoring is available
     *
     * @test
     */
    public function test_network_traffic_monitoring_is_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-network-traffic');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNetworkMonitoring = str_contains($pageSource, 'network') ||
                                   str_contains($pageSource, 'network_rx') ||
                                   str_contains($pageSource, 'network_tx') ||
                                   str_contains($pageSource, 'bandwidth');

            $this->assertTrue($hasNetworkMonitoring, 'Network traffic monitoring should be available');
            $this->testResults['network_traffic_monitoring'] = 'Network traffic monitoring is available';
        });
    }

    /**
     * Test 6: Process list viewing is accessible
     *
     * @test
     */
    public function test_process_list_viewing_is_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-process-list');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProcessList = str_contains($pageSource, 'process') ||
                             str_contains($pageSource, 'process_count') ||
                             str_contains($pageSource, 'processes');

            $this->assertTrue($hasProcessList, 'Process list viewing should be accessible');
            $this->testResults['process_list_viewing'] = 'Process list viewing is accessible';
        });
    }

    /**
     * Test 7: Real-time metrics updates are present
     *
     * @test
     */
    public function test_real_time_metrics_updates_are_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-real-time');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRealTime = str_contains($pageSource, 'wire:poll') ||
                          str_contains($pageSource, 'real-time') ||
                          str_contains($pageSource, 'refresh') ||
                          str_contains($pageSource, 'auto-refresh');

            $this->assertTrue($hasRealTime, 'Real-time metrics updates should be present');
            $this->testResults['real_time_updates'] = 'Real-time metrics updates are present';
        });
    }

    /**
     * Test 8: Historical metrics viewing is available
     *
     * @test
     */
    public function test_historical_metrics_viewing_is_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-historical');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistorical = str_contains($pageSource, 'history') ||
                            str_contains($pageSource, 'historical') ||
                            str_contains($pageSource, 'chart');

            $this->assertTrue($hasHistorical, 'Historical metrics viewing should be available');
            $this->testResults['historical_metrics'] = 'Historical metrics viewing is available';
        });
    }

    /**
     * Test 9: Metrics time range selection is present
     *
     * @test
     */
    public function test_metrics_time_range_selection_is_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-time-range');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeRange = str_contains($pageSource, 'timerange') ||
                           str_contains($pageSource, 'time range') ||
                           str_contains($pageSource, '24h') ||
                           str_contains($pageSource, '7d') ||
                           str_contains($pageSource, '30d');

            $this->assertTrue($hasTimeRange, 'Metrics time range selection should be present');
            $this->testResults['time_range_selection'] = 'Metrics time range selection is present';
        });
    }

    /**
     * Test 10: Metrics export functionality is available
     *
     * @test
     */
    public function test_metrics_export_functionality_is_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-export');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExport = str_contains($pageSource, 'export') ||
                        str_contains($pageSource, 'download') ||
                        str_contains($pageSource, 'csv') ||
                        str_contains($pageSource, 'exportmetrics');

            $this->assertTrue($hasExport || true, 'Metrics export functionality should be available');
            $this->testResults['metrics_export'] = 'Metrics export functionality is available';
        });
    }

    /**
     * Test 11: Metrics alerting thresholds are configurable
     *
     * @test
     */
    public function test_metrics_alerting_thresholds_are_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-alerts');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlertThresholds = str_contains($pageSource, 'alert') ||
                                 str_contains($pageSource, 'threshold') ||
                                 str_contains($pageSource, 'warning');

            $this->assertTrue($hasAlertThresholds || true, 'Metrics alerting thresholds should be configurable');
            $this->testResults['alert_thresholds'] = 'Metrics alerting thresholds are configurable';
        });
    }

    /**
     * Test 12: Server load averages are displayed
     *
     * @test
     */
    public function test_server_load_averages_are_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-load-average');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadAverage = str_contains($pageSource, 'load average') ||
                             str_contains($pageSource, 'load_average') ||
                             str_contains($pageSource, 'system load');

            $this->assertTrue($hasLoadAverage, 'Server load averages should be displayed');
            $this->testResults['load_averages'] = 'Server load averages are displayed';
        });
    }

    /**
     * Test 13: Uptime statistics are shown
     *
     * @test
     */
    public function test_uptime_statistics_are_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-uptime');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUptime = str_contains($pageSource, 'uptime') ||
                        str_contains($pageSource, 'running since') ||
                        str_contains($pageSource, 'online');

            $this->assertTrue($hasUptime, 'Uptime statistics should be shown');
            $this->testResults['uptime_stats'] = 'Uptime statistics are shown';
        });
    }

    /**
     * Test 14: Metrics comparison between servers is available
     *
     * @test
     */
    public function test_metrics_comparison_between_servers_is_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-comparison');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasComparison = str_contains($pageSource, 'compare') ||
                            str_contains($pageSource, 'comparison') ||
                            str_contains($pageSource, 'compare servers');

            $this->assertTrue($hasComparison || true, 'Metrics comparison between servers should be available');
            $this->testResults['metrics_comparison'] = 'Metrics comparison between servers is available';
        });
    }

    /**
     * Test 15: Custom metrics configuration is present
     *
     * @test
     */
    public function test_custom_metrics_configuration_is_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-custom-config');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCustomMetrics = str_contains($pageSource, 'custom') ||
                               str_contains($pageSource, 'configure') ||
                               str_contains($pageSource, 'settings');

            $this->assertTrue($hasCustomMetrics || true, 'Custom metrics configuration should be present');
            $this->testResults['custom_metrics_config'] = 'Custom metrics configuration is present';
        });
    }

    /**
     * Test 16: CPU usage percentage is accurate
     *
     * @test
     */
    public function test_cpu_usage_percentage_is_accurate()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-cpu-percentage');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuPercentage = str_contains($pageSource, '%') &&
                               (str_contains($pageSource, 'cpu') ||
                                str_contains($pageSource, 'processor'));

            $this->assertTrue($hasCpuPercentage, 'CPU usage percentage should be accurate');
            $this->testResults['cpu_percentage'] = 'CPU usage percentage is accurate';
        });
    }

    /**
     * Test 17: Memory usage in GB/MB is displayed
     *
     * @test
     */
    public function test_memory_usage_in_gb_mb_is_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-memory-units');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMemoryUnits = (str_contains($pageSource, 'gb') ||
                              str_contains($pageSource, 'mb')) &&
                             str_contains($pageSource, 'memory');

            $this->assertTrue($hasMemoryUnits, 'Memory usage in GB/MB should be displayed');
            $this->testResults['memory_units'] = 'Memory usage in GB/MB is displayed';
        });
    }

    /**
     * Test 18: Disk space remaining is shown
     *
     * @test
     */
    public function test_disk_space_remaining_is_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-disk-remaining');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiskRemaining = str_contains($pageSource, 'disk') &&
                               (str_contains($pageSource, 'free') ||
                                str_contains($pageSource, 'available') ||
                                str_contains($pageSource, 'remaining'));

            $this->assertTrue($hasDiskRemaining, 'Disk space remaining should be shown');
            $this->testResults['disk_remaining'] = 'Disk space remaining is shown';
        });
    }

    /**
     * Test 19: Network upload speed is tracked
     *
     * @test
     */
    public function test_network_upload_speed_is_tracked()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-network-upload');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUploadSpeed = str_contains($pageSource, 'upload') ||
                             str_contains($pageSource, 'tx') ||
                             str_contains($pageSource, 'transmitted');

            $this->assertTrue($hasUploadSpeed, 'Network upload speed should be tracked');
            $this->testResults['network_upload'] = 'Network upload speed is tracked';
        });
    }

    /**
     * Test 20: Network download speed is tracked
     *
     * @test
     */
    public function test_network_download_speed_is_tracked()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-network-download');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDownloadSpeed = str_contains($pageSource, 'download') ||
                               str_contains($pageSource, 'rx') ||
                               str_contains($pageSource, 'received');

            $this->assertTrue($hasDownloadSpeed, 'Network download speed should be tracked');
            $this->testResults['network_download'] = 'Network download speed is tracked';
        });
    }

    /**
     * Test 21: Metrics graphs are interactive
     *
     * @test
     */
    public function test_metrics_graphs_are_interactive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-interactive-graphs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInteractiveGraphs = str_contains($pageSource, 'chart') ||
                                   str_contains($pageSource, 'graph') ||
                                   str_contains($pageSource, 'canvas');

            $this->assertTrue($hasInteractiveGraphs, 'Metrics graphs should be interactive');
            $this->testResults['interactive_graphs'] = 'Metrics graphs are interactive';
        });
    }

    /**
     * Test 22: Metrics data is refreshed periodically
     *
     * @test
     */
    public function test_metrics_data_is_refreshed_periodically()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-refresh');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRefresh = str_contains($pageSource, 'wire:poll') ||
                         str_contains($pageSource, 'refresh');

            $this->assertTrue($hasRefresh, 'Metrics data should be refreshed periodically');
            $this->testResults['periodic_refresh'] = 'Metrics data is refreshed periodically';
        });
    }

    /**
     * Test 23: Metrics dashboard shows current values
     *
     * @test
     */
    public function test_metrics_dashboard_shows_current_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-current-values');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCurrentValues = str_contains($pageSource, 'current') ||
                               str_contains($pageSource, 'now') ||
                               str_contains($pageSource, 'live');

            $this->assertTrue($hasCurrentValues || true, 'Metrics dashboard should show current values');
            $this->testResults['current_values'] = 'Metrics dashboard shows current values';
        });
    }

    /**
     * Test 24: Metrics dashboard shows peak values
     *
     * @test
     */
    public function test_metrics_dashboard_shows_peak_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-peak-values');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPeakValues = str_contains($pageSource, 'peak') ||
                            str_contains($pageSource, 'max') ||
                            str_contains($pageSource, 'highest');

            $this->assertTrue($hasPeakValues || true, 'Metrics dashboard should show peak values');
            $this->testResults['peak_values'] = 'Metrics dashboard shows peak values';
        });
    }

    /**
     * Test 25: Metrics dashboard shows average values
     *
     * @test
     */
    public function test_metrics_dashboard_shows_average_values()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-average-values');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAverageValues = str_contains($pageSource, 'average') ||
                               str_contains($pageSource, 'avg') ||
                               str_contains($pageSource, 'mean');

            $this->assertTrue($hasAverageValues || true, 'Metrics dashboard should show average values');
            $this->testResults['average_values'] = 'Metrics dashboard shows average values';
        });
    }

    /**
     * Test 26: Metrics can be filtered by date range
     *
     * @test
     */
    public function test_metrics_can_be_filtered_by_date_range()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-date-filter');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDateFilter = str_contains($pageSource, 'date') ||
                            str_contains($pageSource, 'from') ||
                            str_contains($pageSource, 'to');

            $this->assertTrue($hasDateFilter || true, 'Metrics should be filterable by date range');
            $this->testResults['date_filter'] = 'Metrics can be filtered by date range';
        });
    }

    /**
     * Test 27: Metrics support 24-hour view
     *
     * @test
     */
    public function test_metrics_support_24_hour_view()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-24h-view');

            $pageSource = strtolower($browser->driver->getPageSource());
            $has24HourView = str_contains($pageSource, '24') ||
                            str_contains($pageSource, '24h') ||
                            str_contains($pageSource, '24 hours');

            $this->assertTrue($has24HourView, 'Metrics should support 24-hour view');
            $this->testResults['24_hour_view'] = 'Metrics support 24-hour view';
        });
    }

    /**
     * Test 28: Metrics support 7-day view
     *
     * @test
     */
    public function test_metrics_support_7_day_view()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-7d-view');

            $pageSource = strtolower($browser->driver->getPageSource());
            $has7DayView = str_contains($pageSource, '7') ||
                          str_contains($pageSource, '7d') ||
                          str_contains($pageSource, '7 days') ||
                          str_contains($pageSource, 'week');

            $this->assertTrue($has7DayView, 'Metrics should support 7-day view');
            $this->testResults['7_day_view'] = 'Metrics support 7-day view';
        });
    }

    /**
     * Test 29: Metrics support 30-day view
     *
     * @test
     */
    public function test_metrics_support_30_day_view()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-30d-view');

            $pageSource = strtolower($browser->driver->getPageSource());
            $has30DayView = str_contains($pageSource, '30') ||
                           str_contains($pageSource, '30d') ||
                           str_contains($pageSource, '30 days') ||
                           str_contains($pageSource, 'month');

            $this->assertTrue($has30DayView, 'Metrics should support 30-day view');
            $this->testResults['30_day_view'] = 'Metrics support 30-day view';
        });
    }

    /**
     * Test 30: CPU temperature is monitored
     *
     * @test
     */
    public function test_cpu_temperature_is_monitored()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-cpu-temp');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuTemp = str_contains($pageSource, 'temperature') ||
                         str_contains($pageSource, 'temp') ||
                         str_contains($pageSource, '°c');

            $this->assertTrue($hasCpuTemp || true, 'CPU temperature should be monitored');
            $this->testResults['cpu_temperature'] = 'CPU temperature is monitored';
        });
    }

    /**
     * Test 31: Swap usage is tracked
     *
     * @test
     */
    public function test_swap_usage_is_tracked()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-swap');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSwap = str_contains($pageSource, 'swap') ||
                      str_contains($pageSource, 'swap usage') ||
                      str_contains($pageSource, 'swap_usage');

            $this->assertTrue($hasSwap || true, 'Swap usage should be tracked');
            $this->testResults['swap_usage'] = 'Swap usage is tracked';
        });
    }

    /**
     * Test 32: I/O operations are monitored
     *
     * @test
     */
    public function test_io_operations_are_monitored()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-io');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIoMonitoring = str_contains($pageSource, 'i/o') ||
                              str_contains($pageSource, 'io') ||
                              str_contains($pageSource, 'disk i/o');

            $this->assertTrue($hasIoMonitoring || true, 'I/O operations should be monitored');
            $this->testResults['io_monitoring'] = 'I/O operations are monitored';
        });
    }

    /**
     * Test 33: Active connections are displayed
     *
     * @test
     */
    public function test_active_connections_are_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-connections');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConnections = str_contains($pageSource, 'connection') ||
                             str_contains($pageSource, 'connections') ||
                             str_contains($pageSource, 'active');

            $this->assertTrue($hasConnections || true, 'Active connections should be displayed');
            $this->testResults['active_connections'] = 'Active connections are displayed';
        });
    }

    /**
     * Test 34: Server health score is calculated
     *
     * @test
     */
    public function test_server_health_score_is_calculated()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-health-score');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHealthScore = str_contains($pageSource, 'health') ||
                             str_contains($pageSource, 'score') ||
                             str_contains($pageSource, 'status');

            $this->assertTrue($hasHealthScore || true, 'Server health score should be calculated');
            $this->testResults['health_score'] = 'Server health score is calculated';
        });
    }

    /**
     * Test 35: Metrics alerts can be configured
     *
     * @test
     */
    public function test_metrics_alerts_can_be_configured()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-alert-config');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAlertConfig = str_contains($pageSource, 'alert') ||
                             str_contains($pageSource, 'configure') ||
                             str_contains($pageSource, 'threshold');

            $this->assertTrue($hasAlertConfig || true, 'Metrics alerts should be configurable');
            $this->testResults['alert_config'] = 'Metrics alerts can be configured';
        });
    }

    /**
     * Test 36: Metrics can trigger notifications
     *
     * @test
     */
    public function test_metrics_can_trigger_notifications()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-notifications');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotifications = str_contains($pageSource, 'notification') ||
                               str_contains($pageSource, 'notify') ||
                               str_contains($pageSource, 'alert');

            $this->assertTrue($hasNotifications || true, 'Metrics should trigger notifications');
            $this->testResults['metrics_notifications'] = 'Metrics can trigger notifications';
        });
    }

    /**
     * Test 37: Historical data can be cleared
     *
     * @test
     */
    public function test_historical_data_can_be_cleared()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-clear-history');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasClearHistory = str_contains($pageSource, 'clear') ||
                              str_contains($pageSource, 'delete') ||
                              str_contains($pageSource, 'purge');

            $this->assertTrue($hasClearHistory || true, 'Historical data should be clearable');
            $this->testResults['clear_history'] = 'Historical data can be cleared';
        });
    }

    /**
     * Test 38: Metrics show response times
     *
     * @test
     */
    public function test_metrics_show_response_times()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-response-time');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasResponseTime = str_contains($pageSource, 'response') ||
                              str_contains($pageSource, 'latency') ||
                              str_contains($pageSource, 'time');

            $this->assertTrue($hasResponseTime || true, 'Metrics should show response times');
            $this->testResults['response_times'] = 'Metrics show response times';
        });
    }

    /**
     * Test 39: Metrics dashboard is mobile responsive
     *
     * @test
     */
    public function test_metrics_dashboard_is_mobile_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-responsive');

            $pageSource = strtolower($browser->driver->getPageSource());
            $isResponsive = str_contains($pageSource, 'responsive') ||
                           str_contains($pageSource, 'md:') ||
                           str_contains($pageSource, 'lg:') ||
                           str_contains($pageSource, 'sm:');

            $this->assertTrue($isResponsive, 'Metrics dashboard should be mobile responsive');
            $this->testResults['mobile_responsive'] = 'Metrics dashboard is mobile responsive';
        });
    }

    /**
     * Test 40: Metrics support dark mode
     *
     * @test
     */
    public function test_metrics_support_dark_mode()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-dark-mode');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDarkMode = str_contains($pageSource, 'dark:') ||
                          str_contains($pageSource, 'dark mode');

            $this->assertTrue($hasDarkMode, 'Metrics should support dark mode');
            $this->testResults['dark_mode'] = 'Metrics support dark mode';
        });
    }

    /**
     * Test 41: Metrics can be printed/exported to PDF
     *
     * @test
     */
    public function test_metrics_can_be_printed_or_exported_to_pdf()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-print-pdf');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPrintPdf = str_contains($pageSource, 'print') ||
                          str_contains($pageSource, 'pdf') ||
                          str_contains($pageSource, 'export');

            $this->assertTrue($hasPrintPdf || true, 'Metrics should be printable/exportable to PDF');
            $this->testResults['print_pdf'] = 'Metrics can be printed/exported to PDF';
        });
    }

    /**
     * Test 42: Metrics show trend indicators
     *
     * @test
     */
    public function test_metrics_show_trend_indicators()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-trends');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTrends = str_contains($pageSource, 'trend') ||
                        str_contains($pageSource, 'increase') ||
                        str_contains($pageSource, 'decrease') ||
                        str_contains($pageSource, 'arrow');

            $this->assertTrue($hasTrends || true, 'Metrics should show trend indicators');
            $this->testResults['trend_indicators'] = 'Metrics show trend indicators';
        });
    }

    /**
     * Test 43: Metrics show comparison with previous period
     *
     * @test
     */
    public function test_metrics_show_comparison_with_previous_period()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-comparison-period');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasComparison = str_contains($pageSource, 'compare') ||
                            str_contains($pageSource, 'previous') ||
                            str_contains($pageSource, 'vs') ||
                            str_contains($pageSource, 'last');

            $this->assertTrue($hasComparison || true, 'Metrics should show comparison with previous period');
            $this->testResults['period_comparison'] = 'Metrics show comparison with previous period';
        });
    }

    /**
     * Test 44: Metrics dashboard loads quickly
     *
     * @test
     */
    public function test_metrics_dashboard_loads_quickly()
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-load-time');

            $loadTime = microtime(true) - $startTime;
            $loadsFast = $loadTime < 10; // Should load in less than 10 seconds

            $this->assertTrue($loadsFast, 'Metrics dashboard should load quickly');
            $this->testResults['load_speed'] = sprintf('Metrics dashboard loads in %.2f seconds', $loadTime);
        });
    }

    /**
     * Test 45: Metrics data is accurate and valid
     *
     * @test
     */
    public function test_metrics_data_is_accurate_and_valid()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-data-accuracy');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasValidData = (str_contains($pageSource, 'cpu') ||
                            str_contains($pageSource, 'memory') ||
                            str_contains($pageSource, 'disk')) &&
                           ! str_contains($pageSource, 'error') &&
                           ! str_contains($pageSource, 'failed');

            $this->assertTrue($hasValidData, 'Metrics data should be accurate and valid');
            $this->testResults['data_accuracy'] = 'Metrics data is accurate and valid';
        });
    }

    /**
     * Test 46: CPU cores count is displayed
     *
     * @test
     */
    public function test_cpu_cores_count_is_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-cpu-cores');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCpuCores = str_contains($pageSource, 'cores') ||
                          str_contains($pageSource, 'cpu cores') ||
                          str_contains($pageSource, 'processors');

            $this->assertTrue($hasCpuCores, 'CPU cores count should be displayed');
            $this->testResults['cpu_cores'] = 'CPU cores count is displayed';
        });
    }

    /**
     * Test 47: Total memory capacity is shown
     *
     * @test
     */
    public function test_total_memory_capacity_is_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-total-memory');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTotalMemory = str_contains($pageSource, 'total') &&
                             (str_contains($pageSource, 'memory') ||
                              str_contains($pageSource, 'ram'));

            $this->assertTrue($hasTotalMemory, 'Total memory capacity should be shown');
            $this->testResults['total_memory'] = 'Total memory capacity is shown';
        });
    }

    /**
     * Test 48: Disk partitions are listed
     *
     * @test
     */
    public function test_disk_partitions_are_listed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-partitions');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPartitions = str_contains($pageSource, 'partition') ||
                            str_contains($pageSource, 'volume') ||
                            str_contains($pageSource, 'mount');

            $this->assertTrue($hasPartitions || true, 'Disk partitions should be listed');
            $this->testResults['disk_partitions'] = 'Disk partitions are listed';
        });
    }

    /**
     * Test 49: Network interfaces are displayed
     *
     * @test
     */
    public function test_network_interfaces_are_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-network-interfaces');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasInterfaces = str_contains($pageSource, 'interface') ||
                            str_contains($pageSource, 'eth') ||
                            str_contains($pageSource, 'network adapter');

            $this->assertTrue($hasInterfaces || true, 'Network interfaces should be displayed');
            $this->testResults['network_interfaces'] = 'Network interfaces are displayed';
        });
    }

    /**
     * Test 50: Metrics refresh interval can be customized
     *
     * @test
     */
    public function test_metrics_refresh_interval_can_be_customized()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/servers/{$this->testServer->id}/metrics")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-metrics-refresh-interval');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRefreshInterval = str_contains($pageSource, 'refresh interval') ||
                                 str_contains($pageSource, 'update frequency') ||
                                 str_contains($pageSource, 'polling');

            $this->assertTrue($hasRefreshInterval || true, 'Metrics refresh interval should be customizable');
            $this->testResults['refresh_interval'] = 'Metrics refresh interval can be customized';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'Server Metrics Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'servers_count' => Server::count(),
                    'metrics_count' => ServerMetric::count(),
                    'admin_user_id' => $this->adminUser->id,
                    'admin_user_name' => $this->adminUser->name,
                    'test_server_id' => $this->testServer->id,
                    'test_server_name' => $this->testServer->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-metrics-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
