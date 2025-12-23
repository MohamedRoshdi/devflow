<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\SystemAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class SystemAdminTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'is_admin' => true,
        ]);

        // Set up default config for tests
        Config::set('devflow.system_admin.primary_server', [
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        Config::set('devflow.system_admin.paths', [
            'backup_log' => '/var/log/backup.log',
            'backup_dir' => '/backups',
            'monitor_log' => '/var/log/monitor.log',
            'optimization_log' => '/var/log/optimization.log',
        ]);

        Config::set('devflow.system_admin.scripts', [
            'backup' => '/scripts/backup.sh',
            'optimize' => '/scripts/optimize.sh',
        ]);
    }

    // =========================================================================
    // Component Rendering Tests
    // =========================================================================

    public function test_component_renders_for_authenticated_user(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertOk()
            ->assertSet('activeTab', 'overview')
            ->assertSet('isLoading', true);
    }

    public function test_component_initial_state(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertSet('backupLogs', [])
            ->assertSet('monitoringLogs', [])
            ->assertSet('optimizationLogs', [])
            ->assertSet('backupStats', [])
            ->assertSet('systemMetrics', [])
            ->assertSet('recentAlerts', []);
    }

    // =========================================================================
    // Load System Data Tests
    // =========================================================================

    public function test_load_system_data_sets_loading_to_false(): void
    {
        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertSet('isLoading', true)
            ->call('loadSystemData')
            ->assertSet('isLoading', false);
    }

    public function test_load_system_data_calls_all_load_methods(): void
    {
        Process::fake([
            '*' => Process::result(output: "2024-01-15 10:00:00 Backup completed\n"),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemData');

        // Should have attempted to load data
        $component->assertSet('isLoading', false);
    }

    // =========================================================================
    // Load Backup Stats Tests
    // =========================================================================

    public function test_load_backup_stats_success(): void
    {
        Process::fake([
            '*tail*backup*' => Process::result(
                output: "[2024-01-15 10:00:00] Backup completed successfully\n[2024-01-15 09:00:00] Starting backup"
            ),
            '*du*' => Process::result(output: "144K\t/backups/db1.sql.gz"),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats')
            ->assertSet('backupStats.status', 'success');
    }

    public function test_load_backup_stats_parses_logs(): void
    {
        Process::fake([
            '*tail*' => Process::result(
                output: "[2024-01-15 10:00:00] Backup completed\n[2024-01-15 09:00:00] Starting backup"
            ),
            '*du*' => Process::result(output: "144K\t/backups/db.sql.gz"),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');

        $backupLogs = $component->get('backupLogs');
        $this->assertCount(2, $backupLogs);
    }

    public function test_load_backup_stats_handles_empty_server_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats')
            ->assertSet('backupStats.status', 'error');
    }

    public function test_load_backup_stats_handles_ssh_error(): void
    {
        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Connection refused',
                exitCode: 1
            ),
        ]);

        // Should not throw exception, just set error status
        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');
    }

    // =========================================================================
    // Load System Metrics Tests
    // =========================================================================

    public function test_load_system_metrics_success(): void
    {
        Process::fake([
            '*tail*monitor*' => Process::result(
                output: "Disk usage: 45%\nMemory usage: 60%\nCPU usage: 25%\n5 containers running"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $metrics = $component->get('systemMetrics');
        $this->assertEquals('45', $metrics['disk_usage']);
        $this->assertEquals('60', $metrics['memory_usage']);
        $this->assertEquals('25', $metrics['cpu_usage']);
        $this->assertEquals('5', $metrics['containers_running']);
    }

    public function test_load_system_metrics_parses_monitoring_logs(): void
    {
        Process::fake([
            '*' => Process::result(
                output: "Line 1\nLine 2\nLine 3"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $logs = $component->get('monitoringLogs');
        $this->assertCount(3, $logs);
    }

    public function test_load_system_metrics_handles_empty_server_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $metrics = $component->get('systemMetrics');
        $this->assertArrayHasKey('error', $metrics);
    }

    public function test_load_system_metrics_returns_na_for_missing_metrics(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Some unrelated output'),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $metrics = $component->get('systemMetrics');
        $this->assertEquals('N/A', $metrics['disk_usage']);
    }

    // =========================================================================
    // Load Recent Alerts Tests
    // =========================================================================

    public function test_load_recent_alerts_success(): void
    {
        Process::fake([
            '*grep*' => Process::result(
                output: "[2024-01-15 10:00:00] [WARNING] High disk usage\n[2024-01-15 09:00:00] [ERROR] Connection failed"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts');

        $alerts = $component->get('recentAlerts');
        $this->assertCount(2, $alerts);
        $this->assertEquals('WARNING', $alerts[0]['level']);
        $this->assertEquals('ERROR', $alerts[1]['level']);
    }

    public function test_load_recent_alerts_extracts_timestamps(): void
    {
        Process::fake([
            '*grep*' => Process::result(
                output: "[2024-01-15 10:30:00] [CRITICAL] System overload"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts');

        $alerts = $component->get('recentAlerts');
        $this->assertEquals('2024-01-15 10:30:00', $alerts[0]['timestamp']);
        $this->assertEquals('CRITICAL', $alerts[0]['level']);
    }

    public function test_load_recent_alerts_handles_empty_server_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts')
            ->assertSet('recentAlerts', []);
    }

    public function test_load_recent_alerts_handles_no_alerts(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts')
            ->assertSet('recentAlerts', []);
    }

    // =========================================================================
    // Run Backup Now Tests
    // =========================================================================

    public function test_run_backup_now_success(): void
    {
        Process::fake([
            '*backup.sh*' => Process::result(output: 'Backup completed'),
            '*tail*' => Process::result(output: ''),
            '*du*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runBackupNow')
            ->assertSessionHas('message');
    }

    public function test_run_backup_now_handles_failure(): void
    {
        Process::fake([
            '*backup.sh*' => Process::result(
                output: '',
                errorOutput: 'Backup script failed',
                exitCode: 1
            ),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runBackupNow')
            ->assertSessionHas('error');
    }

    public function test_run_backup_now_handles_empty_server_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runBackupNow')
            ->assertSessionHas('error', 'Primary server not configured');
    }

    public function test_run_backup_now_handles_exception(): void
    {
        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'SSH connection failed',
                exitCode: 255
            )->throw(),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runBackupNow')
            ->assertSessionHas('error');
    }

    // =========================================================================
    // Run Optimization Now Tests
    // =========================================================================

    public function test_run_optimization_now_success(): void
    {
        Process::fake([
            '*optimize.sh*' => Process::result(output: 'Optimization completed'),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runOptimizationNow')
            ->assertSessionHas('message');
    }

    public function test_run_optimization_now_handles_failure(): void
    {
        Process::fake([
            '*optimize.sh*' => Process::result(
                output: '',
                errorOutput: 'Optimization failed',
                exitCode: 1
            ),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runOptimizationNow')
            ->assertSessionHas('error');
    }

    public function test_run_optimization_now_handles_empty_server_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('runOptimizationNow')
            ->assertSessionHas('error', 'Primary server not configured');
    }

    // =========================================================================
    // Tab Navigation Tests
    // =========================================================================

    public function test_view_backup_logs_switches_tab(): void
    {
        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertSet('activeTab', 'overview')
            ->call('viewBackupLogs')
            ->assertSet('activeTab', 'backup-logs');
    }

    public function test_view_monitoring_logs_switches_tab(): void
    {
        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertSet('activeTab', 'overview')
            ->call('viewMonitoringLogs')
            ->assertSet('activeTab', 'monitoring-logs');
    }

    public function test_view_optimization_logs_switches_tab(): void
    {
        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->assertSet('activeTab', 'overview')
            ->call('viewOptimizationLogs')
            ->assertSet('activeTab', 'optimization-logs');
    }

    public function test_view_backup_logs_loads_data(): void
    {
        Process::fake([
            '*tail*backup*' => Process::result(
                output: "Log line 1\nLog line 2"
            ),
            '*du*' => Process::result(output: ''),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewBackupLogs');

        $logs = $component->get('backupLogs');
        $this->assertNotEmpty($logs);
    }

    public function test_view_monitoring_logs_loads_data(): void
    {
        Process::fake([
            '*tail*monitor*' => Process::result(
                output: "Monitor log 1\nMonitor log 2\nMonitor log 3"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewMonitoringLogs');

        $logs = $component->get('monitoringLogs');
        $this->assertCount(3, $logs);
    }

    public function test_view_optimization_logs_loads_data(): void
    {
        Process::fake([
            '*cat*optimization*' => Process::result(
                output: "Optimization log 1\nOptimization log 2"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewOptimizationLogs');

        $logs = $component->get('optimizationLogs');
        $this->assertCount(2, $logs);
    }

    public function test_view_monitoring_logs_handles_empty_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewMonitoringLogs');

        $logs = $component->get('monitoringLogs');
        $this->assertContains('Error: Primary server not configured', $logs);
    }

    public function test_view_optimization_logs_handles_empty_config(): void
    {
        Config::set('devflow.system_admin.primary_server.ip_address', '');

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewOptimizationLogs');

        $logs = $component->get('optimizationLogs');
        $this->assertContains('Error: Primary server not configured', $logs);
    }

    // =========================================================================
    // Helper Method Tests (via integration)
    // =========================================================================

    public function test_extract_last_backup_time_from_logs(): void
    {
        Process::fake([
            '*tail*' => Process::result(
                output: "[2024-01-15 14:30:00] Backup completed\n[2024-01-15 10:00:00] Starting backup"
            ),
            '*du*' => Process::result(output: '144K'),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');

        $stats = $component->get('backupStats');
        $this->assertEquals('2024-01-15 14:30:00', $stats['last_backup']);
    }

    public function test_extract_last_backup_time_returns_unknown_when_no_match(): void
    {
        Process::fake([
            '*tail*' => Process::result(output: 'No timestamp here'),
            '*du*' => Process::result(output: '144K'),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');

        $stats = $component->get('backupStats');
        $this->assertEquals('Unknown', $stats['last_backup']);
    }

    public function test_alert_level_extraction(): void
    {
        Process::fake([
            '*grep*' => Process::result(
                output: "[2024-01-15 10:00:00] [WARNING] Test warning\n[2024-01-15 11:00:00] [ERROR] Test error\n[2024-01-15 12:00:00] [CRITICAL] Test critical"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts');

        $alerts = $component->get('recentAlerts');
        $this->assertEquals('WARNING', $alerts[0]['level']);
        $this->assertEquals('ERROR', $alerts[1]['level']);
        $this->assertEquals('CRITICAL', $alerts[2]['level']);
    }

    public function test_alert_message_extraction(): void
    {
        Process::fake([
            '*grep*' => Process::result(
                output: "[2024-01-15 10:00:00] [WARNING] High memory usage detected"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts');

        $alerts = $component->get('recentAlerts');
        $this->assertStringContainsString('High memory usage', $alerts[0]['message']);
    }

    // =========================================================================
    // Empty Logs Filtering Tests
    // =========================================================================

    public function test_empty_lines_are_filtered_from_backup_logs(): void
    {
        Process::fake([
            '*tail*' => Process::result(
                output: "Line 1\n\n\nLine 2\n\n"
            ),
            '*du*' => Process::result(output: ''),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');

        $logs = $component->get('backupLogs');
        $this->assertCount(2, $logs);
        $this->assertEquals('Line 1', $logs[0]);
        $this->assertEquals('Line 2', $logs[1]);
    }

    public function test_empty_lines_are_filtered_from_monitoring_logs(): void
    {
        Process::fake([
            '*' => Process::result(
                output: "Log A\n\nLog B\n\n\nLog C"
            ),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $logs = $component->get('monitoringLogs');
        $this->assertCount(3, $logs);
    }

    // =========================================================================
    // Exception Handling Tests
    // =========================================================================

    public function test_load_backup_stats_catches_exceptions(): void
    {
        Process::shouldReceive('timeout')
            ->andThrow(new \Exception('Connection timeout'));

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadBackupStats');

        $stats = $component->get('backupStats');
        $this->assertEquals('error', $stats['status']);
        $this->assertStringContainsString('Connection timeout', $stats['error']);
    }

    public function test_load_system_metrics_catches_exceptions(): void
    {
        Process::shouldReceive('timeout')
            ->andThrow(new \Exception('SSH failed'));

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadSystemMetrics');

        $metrics = $component->get('systemMetrics');
        $this->assertArrayHasKey('error', $metrics);
    }

    public function test_load_recent_alerts_catches_exceptions(): void
    {
        Process::shouldReceive('timeout')
            ->andThrow(new \Exception('Network error'));

        Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('loadRecentAlerts')
            ->assertSet('recentAlerts', []);
    }

    public function test_view_monitoring_logs_catches_exceptions(): void
    {
        Process::shouldReceive('timeout')
            ->andThrow(new \Exception('Read error'));

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewMonitoringLogs');

        $logs = $component->get('monitoringLogs');
        $this->assertContains('Error loading logs: Read error', $logs);
    }

    public function test_view_optimization_logs_catches_exceptions(): void
    {
        Process::shouldReceive('timeout')
            ->andThrow(new \Exception('File not found'));

        $component = Livewire::actingAs($this->adminUser)
            ->test(SystemAdmin::class)
            ->call('viewOptimizationLogs');

        $logs = $component->get('optimizationLogs');
        $this->assertContains('Error loading logs: File not found', $logs);
    }
}
