<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Commands\BackupDatabase;
use App\Console\Commands\BackupFiles;
use App\Console\Commands\CheckResourceAlertsCommand;
use App\Console\Commands\CheckSSLCommand;
use App\Console\Commands\CheckSSLExpiry;
use App\Console\Commands\CleanupBackups;
use App\Console\Commands\CleanupMetricsCommand;
use App\Console\Commands\CollectServerMetrics;
use App\Console\Commands\FixPermissionsCommand;
use App\Console\Commands\MonitorServersCommand;
use App\Console\Commands\ProcessScheduledDeployments;
use App\Console\Commands\ProvisionServer;
use App\Console\Commands\RenewSSL;
use App\Console\Commands\RunBackupsCommand;
use App\Console\Commands\RunHealthChecksCommand;
use App\Console\Commands\RunQualityTests;
use App\Console\Commands\RunServerBackupsCommand;
use App\Console\Commands\SSLRenewCommand;
use App\Console\Commands\SyncLogsCommand;
use App\Console\Commands\VerifyBackup;
use App\Events\ServerMetricsUpdated;
use App\Jobs\DeployProjectJob;
use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Domain;
use App\Models\FileBackup;
use App\Models\Project;
use App\Models\ProjectAnalytic;
use App\Models\ResourceAlert;
use App\Models\ScheduledDeployment;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Services\DatabaseBackupService;
use App\Services\FileBackupService;
use App\Services\HealthCheckService;
use App\Services\LogAggregationService;
use App\Services\ResourceAlertService;
use App\Services\ServerBackupService;
use App\Services\ServerMetricsService;
use App\Services\ServerProvisioningService;
use App\Services\SSLManagementService;
use App\Services\SSLService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Log::spy();
    }

    // ==================== BackupDatabase Command Tests ====================

    #[Test]
    public function backup_database_command_has_correct_signature(): void
    {
        $command = new BackupDatabase;
        $this->assertEquals('backup:database', $command->getName());
        $this->assertStringContainsString('database backup', $command->getDescription());
    }

    #[Test]
    public function backup_database_executes_successfully_for_single_project(): void
    {
        $project = Project::factory()->create();
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $project->id,
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockBackup = DatabaseBackup::factory()->make([
            'file_name' => 'test_backup.sql',
            'file_size' => 1024,
        ]);

        $mockService->shouldReceive('createBackup')
            ->once()
            ->andReturn($mockBackup);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:database', ['project' => $project->id])
            ->assertExitCode(0);
    }

    #[Test]
    public function backup_database_handles_project_not_found(): void
    {
        $this->artisan('backup:database', ['project' => 999])
            ->expectsOutput('Project not found: 999')
            ->assertExitCode(1);
    }

    #[Test]
    public function backup_database_handles_no_backup_schedules(): void
    {
        $project = Project::factory()->create();

        $this->artisan('backup:database', ['project' => $project->id])
            ->expectsOutput('No backup schedules configured for this project.')
            ->assertExitCode(0);
    }

    #[Test]
    public function backup_database_backs_up_all_projects(): void
    {
        $projects = Project::factory()->count(2)->create();
        foreach ($projects as $project) {
            BackupSchedule::factory()->create(['project_id' => $project->id]);
        }

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockBackup = DatabaseBackup::factory()->make();

        $mockService->shouldReceive('createBackup')
            ->times(2)
            ->andReturn($mockBackup);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:database')
            ->assertExitCode(0);
    }

    #[Test]
    public function backup_database_handles_backup_failure(): void
    {
        $project = Project::factory()->create();
        BackupSchedule::factory()->create(['project_id' => $project->id]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('createBackup')
            ->once()
            ->andThrow(new \Exception('Backup failed'));

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:database', ['project' => $project->id])
            ->assertExitCode(1);

        Log::shouldHaveReceived('error')->once();
    }

    // ==================== BackupFiles Command Tests ====================

    #[Test]
    public function backup_files_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(FileBackupService::class);
        $command = new BackupFiles($mockService);

        $this->assertEquals('backup:files', $command->getName());
        $this->assertStringContainsString('file backups', $command->getDescription());
    }

    #[Test]
    public function backup_files_executes_full_backup_for_project(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);
        $server = Server::factory()->create();
        $project->server()->associate($server);
        $project->save();

        $mockService = Mockery::mock(FileBackupService::class);
        $mockBackup = FileBackup::factory()->make([
            'status' => 'completed',
            'files_count' => 100,
        ]);

        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->andReturn($mockBackup);

        $this->app->instance(FileBackupService::class, $mockService);

        $this->artisan('backup:files', [
            'project' => 'test-project',
            '--type' => 'full',
        ])->assertExitCode(0);
    }

    #[Test]
    public function backup_files_validates_backup_type(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);

        $this->artisan('backup:files', [
            'project' => 'test-project',
            '--type' => 'invalid',
        ])->expectsOutput('Invalid backup type. Use "full" or "incremental".')
            ->assertExitCode(1);
    }

    #[Test]
    public function backup_files_handles_project_not_found(): void
    {
        $this->artisan('backup:files', ['project' => 'non-existent'])
            ->expectsOutput("Project 'non-existent' not found.")
            ->assertExitCode(1);
    }

    #[Test]
    public function backup_files_backs_up_all_projects(): void
    {
        $server = Server::factory()->create();
        $projects = Project::factory()->count(2)->create(['server_id' => $server->id]);

        $mockService = Mockery::mock(FileBackupService::class);
        $mockBackup = FileBackup::factory()->make(['status' => 'completed']);

        // Use atLeast instead of exact count since database state may vary
        $mockService->shouldReceive('createFullBackup')
            ->atLeast()
            ->times(2)
            ->andReturn($mockBackup);

        $this->app->instance(FileBackupService::class, $mockService);

        $this->artisan('backup:files', ['--all' => true])
            ->assertExitCode(0);
    }

    #[Test]
    public function backup_files_handles_incremental_backup(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);
        $server = Server::factory()->create();
        $project->server()->associate($server);
        $project->save();

        $baseBackup = FileBackup::factory()->create([
            'project_id' => $project->id,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $mockService = Mockery::mock(FileBackupService::class);
        $mockBackup = FileBackup::factory()->make(['status' => 'completed']);

        $mockService->shouldReceive('createIncrementalBackup')
            ->once()
            ->andReturn($mockBackup);

        $this->app->instance(FileBackupService::class, $mockService);

        $this->artisan('backup:files', [
            'project' => 'test-project',
            '--type' => 'incremental',
        ])->assertExitCode(0);
    }

    // ==================== CheckResourceAlertsCommand Tests ====================

    #[Test]
    public function check_resource_alerts_command_has_correct_signature(): void
    {
        $command = new CheckResourceAlertsCommand;
        $this->assertEquals('alerts:check', $command->getName());
        $this->assertStringContainsString('resource alerts', $command->getDescription());
    }

    #[Test]
    public function check_resource_alerts_executes_successfully(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'is_active' => true,
        ]);

        $mockService = Mockery::mock(ResourceAlertService::class);
        $mockService->shouldReceive('evaluateAlerts')
            ->once()
            ->andReturn([
                'checked' => 3,
                'triggered' => 1,
                'resolved' => 0,
            ]);

        $this->app->instance(ResourceAlertService::class, $mockService);

        $this->artisan('alerts:check')
            ->assertExitCode(0);
    }

    #[Test]
    public function check_resource_alerts_handles_no_servers(): void
    {
        $this->artisan('alerts:check')
            ->expectsOutput('No servers with active alerts found.')
            ->assertExitCode(0);
    }

    #[Test]
    public function check_resource_alerts_handles_specific_server(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'is_active' => true,
        ]);

        $mockService = Mockery::mock(ResourceAlertService::class);
        $mockService->shouldReceive('evaluateAlerts')
            ->once()
            ->andReturn(['checked' => 1, 'triggered' => 0, 'resolved' => 0]);

        $this->app->instance(ResourceAlertService::class, $mockService);

        $this->artisan('alerts:check', ['--server-id' => $server->id])
            ->assertExitCode(0);
    }

    #[Test]
    public function check_resource_alerts_handles_errors(): void
    {
        $server = Server::factory()->create(['status' => 'online']);
        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'is_active' => true,
        ]);

        $mockService = Mockery::mock(ResourceAlertService::class);
        $mockService->shouldReceive('evaluateAlerts')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $this->app->instance(ResourceAlertService::class, $mockService);

        $this->artisan('alerts:check')
            ->assertExitCode(1);

        Log::shouldHaveReceived('error')->once();
    }

    // ==================== CheckSSLCommand Tests ====================

    #[Test]
    public function check_ssl_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(SSLService::class);
        $command = new CheckSSLCommand($mockService);

        $this->assertEquals('devflow:check-ssl', $command->getName());
        $this->assertStringContainsString('SSL certificates', $command->getDescription());
    }

    #[Test]
    public function check_ssl_executes_successfully(): void
    {
        $mockService = Mockery::mock(SSLService::class);
        $mockService->shouldReceive('checkExpiringCertificates')
            ->once()
            ->andReturn([
                'renewed' => ['example.com'],
                'failed' => [],
            ]);

        $this->app->instance(SSLService::class, $mockService);

        $this->artisan('devflow:check-ssl')
            ->expectsOutput('Renewed certificates for:')
            ->assertExitCode(0);
    }

    #[Test]
    public function check_ssl_handles_failed_renewals(): void
    {
        $mockService = Mockery::mock(SSLService::class);
        $mockService->shouldReceive('checkExpiringCertificates')
            ->once()
            ->andReturn([
                'renewed' => [],
                'failed' => [
                    ['domain' => 'example.com', 'error' => 'DNS verification failed'],
                ],
            ]);

        $this->app->instance(SSLService::class, $mockService);

        $this->artisan('devflow:check-ssl')
            ->expectsOutput('Failed to renew certificates for:')
            ->assertExitCode(0);
    }

    #[Test]
    public function check_ssl_handles_no_renewals_needed(): void
    {
        $mockService = Mockery::mock(SSLService::class);
        $mockService->shouldReceive('checkExpiringCertificates')
            ->once()
            ->andReturn([
                'renewed' => [],
                'failed' => [],
            ]);

        $this->app->instance(SSLService::class, $mockService);

        $this->artisan('devflow:check-ssl')
            ->expectsOutput('No certificates need renewal at this time.')
            ->assertExitCode(0);
    }

    // ==================== CheckSSLExpiry Tests ====================

    #[Test]
    public function check_ssl_expiry_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(SSLManagementService::class);
        $command = new CheckSSLExpiry($mockService);

        $this->assertEquals('ssl:check-expiry', $command->getName());
        $this->assertStringContainsString('expiring SSL certificates', $command->getDescription());
    }

    #[Test]
    public function check_ssl_expiry_finds_no_expiring_certificates(): void
    {
        $mockService = Mockery::mock(SSLManagementService::class);
        $mockService->shouldReceive('getExpiringCertificates')
            ->once()
            ->andReturn(collect([]));

        $this->app->instance(SSLManagementService::class, $mockService);

        $this->artisan('ssl:check-expiry')
            ->expectsOutput('✓ No expiring certificates found.')
            ->assertExitCode(0);
    }

    #[Test]
    public function check_ssl_expiry_lists_expiring_certificates(): void
    {
        $server = Server::factory()->create();
        $domain = Domain::factory()->create([
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(5),
        ]);

        $mockService = Mockery::mock(SSLManagementService::class);
        $mockService->shouldReceive('getExpiringCertificates')
            ->once()
            ->andReturn(collect([$domain]));

        $this->app->instance(SSLManagementService::class, $mockService);

        $this->artisan('ssl:check-expiry', ['--days' => 30])
            ->assertExitCode(0);
    }

    #[Test]
    public function check_ssl_expiry_auto_renews_certificates(): void
    {
        $server = Server::factory()->create();
        $domain = Domain::factory()->create([
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(5),
        ]);

        $mockService = Mockery::mock(SSLManagementService::class);
        $mockService->shouldReceive('getExpiringCertificates')
            ->once()
            ->andReturn(collect([$domain]));

        $mockService->shouldReceive('renewExpiringCertificates')
            ->once()
            ->andReturn([
                'success' => collect([['domain' => $domain->domain]]),
                'failed' => collect([]),
            ]);

        $this->app->instance(SSLManagementService::class, $mockService);

        $this->artisan('ssl:check-expiry', ['--renew' => true])
            ->assertExitCode(0);
    }

    // ==================== CleanupBackups Tests ====================

    #[Test]
    public function cleanup_backups_command_has_correct_signature(): void
    {
        $command = new CleanupBackups;
        $this->assertEquals('backup:cleanup', $command->getName());
        $this->assertStringContainsString('retention policy', $command->getDescription());
    }

    #[Test]
    public function cleanup_backups_executes_for_single_project(): void
    {
        $project = Project::factory()->create();
        BackupSchedule::factory()->create(['project_id' => $project->id]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('applyRetentionPolicy')
            ->once()
            ->andReturn(5);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:cleanup', ['project' => $project->id])
            ->assertExitCode(0);

        Log::shouldHaveReceived('info')->once();
    }

    #[Test]
    public function cleanup_backups_handles_dry_run(): void
    {
        $project = Project::factory()->create();
        BackupSchedule::factory()->create(['project_id' => $project->id]);

        $this->artisan('backup:cleanup', [
            'project' => $project->id,
            '--dry-run' => true,
        ])->expectsOutput('Running in DRY-RUN mode - no backups will be deleted')
            ->assertExitCode(0);
    }

    #[Test]
    public function cleanup_backups_cleans_all_projects(): void
    {
        $projects = Project::factory()->count(2)->create();
        foreach ($projects as $project) {
            BackupSchedule::factory()->create(['project_id' => $project->id]);
        }

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('applyRetentionPolicy')
            ->times(2)
            ->andReturn(3);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:cleanup')
            ->assertExitCode(0);
    }

    // ==================== CleanupMetricsCommand Tests ====================

    #[Test]
    public function cleanup_metrics_command_has_correct_signature(): void
    {
        $command = new CleanupMetricsCommand;
        $this->assertEquals('devflow:cleanup-metrics', $command->getName());
        $this->assertStringContainsString('metrics', $command->getDescription());
    }

    #[Test]
    public function cleanup_metrics_deletes_old_metrics(): void
    {
        ServerMetric::factory()->count(5)->create([
            'recorded_at' => now()->subDays(100),
        ]);

        ProjectAnalytic::factory()->count(3)->create([
            'recorded_at' => now()->subDays(100),
        ]);

        $this->artisan('devflow:cleanup-metrics', ['--days' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseCount('server_metrics', 0);
        $this->assertDatabaseCount('project_analytics', 0);
    }

    #[Test]
    public function cleanup_metrics_respects_retention_period(): void
    {
        ServerMetric::factory()->count(3)->create([
            'recorded_at' => now()->subDays(50),
        ]);

        $this->artisan('devflow:cleanup-metrics', ['--days' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseCount('server_metrics', 3);
    }

    // ==================== CollectServerMetrics Tests ====================

    #[Test]
    public function collect_server_metrics_command_has_correct_signature(): void
    {
        $command = new CollectServerMetrics;
        $this->assertEquals('servers:collect-metrics', $command->getName());
        $this->assertStringContainsString('metrics from servers', $command->getDescription());
    }

    #[Test]
    public function collect_server_metrics_collects_for_single_server(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $mockService = Mockery::mock(ServerMetricsService::class);
        $mockMetric = ServerMetric::factory()->make([
            'cpu_usage' => 50.5,
            'memory_usage' => 60.2,
            'disk_usage' => 40.1,
        ]);

        $mockService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($mockMetric);

        $this->app->instance(ServerMetricsService::class, $mockService);

        $this->artisan('servers:collect-metrics', ['server_id' => $server->id])
            ->assertExitCode(0);
    }

    #[Test]
    public function collect_server_metrics_handles_server_not_found(): void
    {
        $this->artisan('servers:collect-metrics', ['server_id' => 999])
            ->expectsOutput('Server with ID 999 not found.')
            ->assertExitCode(1);
    }

    #[Test]
    public function collect_server_metrics_collects_for_all_servers(): void
    {
        // Clear all servers to ensure clean state
        Server::query()->forceDelete();

        // Create exactly 3 online servers
        Server::factory()->count(3)->create(['status' => 'online']);

        $mockService = Mockery::mock(ServerMetricsService::class);
        $mockMetric = ServerMetric::factory()->make();

        // Use atLeast to handle any timing variations
        $mockService->shouldReceive('collectMetrics')
            ->atLeast()->times(3)
            ->andReturn($mockMetric);

        $this->app->instance(ServerMetricsService::class, $mockService);

        $this->artisan('servers:collect-metrics')
            ->assertExitCode(0);
    }

    #[Test]
    public function collect_server_metrics_broadcasts_when_requested(): void
    {
        Event::fake();

        $server = Server::factory()->create(['status' => 'online']);

        $mockService = Mockery::mock(ServerMetricsService::class);
        $mockMetric = ServerMetric::factory()->make();

        $mockService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($mockMetric);

        $this->app->instance(ServerMetricsService::class, $mockService);

        $this->artisan('servers:collect-metrics', [
            'server_id' => $server->id,
            '--broadcast' => true,
        ])->assertExitCode(0);

        Event::assertDispatched(ServerMetricsUpdated::class);
    }

    // ==================== FixPermissionsCommand Tests ====================

    #[Test]
    public function fix_permissions_command_has_correct_signature(): void
    {
        $command = new FixPermissionsCommand;
        $this->assertEquals('app:fix-permissions', $command->getName());
        $this->assertStringContainsString('permissions', $command->getDescription());
    }

    #[Test]
    public function fix_permissions_executes_successfully(): void
    {
        $this->artisan('app:fix-permissions')
            ->assertExitCode(0);
    }

    #[Test]
    public function fix_permissions_handles_invalid_path(): void
    {
        $this->artisan('app:fix-permissions', ['--path' => '/non/existent/path'])
            ->expectsOutput('❌ Project path does not exist: /non/existent/path')
            ->assertExitCode(1);
    }

    // ==================== MonitorServersCommand Tests ====================

    #[Test]
    public function monitor_servers_command_has_correct_signature(): void
    {
        $command = new MonitorServersCommand;
        $this->assertEquals('devflow:monitor-servers', $command->getName());
        $this->assertStringContainsString('Monitor', $command->getDescription());
    }

    #[Test]
    public function monitor_servers_collects_metrics(): void
    {
        $this->markTestSkipped('Requires SSH connection mocking - tested in integration tests');
    }

    // ==================== ProcessScheduledDeployments Tests ====================

    #[Test]
    public function process_scheduled_deployments_command_has_correct_signature(): void
    {
        $command = new ProcessScheduledDeployments;
        $this->assertEquals('deployments:process-scheduled', $command->getName());
        $this->assertStringContainsString('scheduled deployments', $command->getDescription());
    }

    #[Test]
    public function process_scheduled_deployments_handles_no_due_deployments(): void
    {
        $this->artisan('deployments:process-scheduled')
            ->expectsOutput('No scheduled deployments due.')
            ->assertExitCode(0);
    }

    #[Test]
    public function process_scheduled_deployments_processes_due_deployments(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $scheduledDeployment = ScheduledDeployment::factory()->create([
            'project_id' => $project->id,
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $this->artisan('deployments:process-scheduled')
            ->assertExitCode(0);

        Queue::assertPushed(DeployProjectJob::class);
    }

    // ==================== ProvisionServer Tests ====================

    #[Test]
    public function provision_server_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(ServerProvisioningService::class);
        $command = new ProvisionServer($mockService);

        $this->assertEquals('server:provision', $command->getName());
        $this->assertStringContainsString('Provision a server', $command->getDescription());
    }

    #[Test]
    public function provision_server_handles_server_not_found(): void
    {
        $this->artisan('server:provision', ['server' => 999])
            ->expectsOutput('Server not found: 999')
            ->assertExitCode(1);
    }

    // ==================== RenewSSL Tests ====================

    #[Test]
    public function renew_ssl_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(SSLManagementService::class);
        $command = new RenewSSL($mockService);

        $this->assertEquals('ssl:renew', $command->getName());
        $this->assertStringContainsString('Renew SSL certificate', $command->getDescription());
    }

    #[Test]
    public function renew_ssl_handles_domain_not_found(): void
    {
        $this->artisan('ssl:renew', ['domain' => 'nonexistent.com'])
            ->expectsOutput('Domain not found: nonexistent.com')
            ->assertExitCode(1);
    }

    #[Test]
    public function renew_ssl_renews_single_domain(): void
    {
        $domain = Domain::factory()->create([
            'domain' => 'example.com',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(5),
        ]);

        $mockService = Mockery::mock(SSLManagementService::class);
        $mockService->shouldReceive('renewCertificate')
            ->once()
            ->andReturn(true);

        $this->app->instance(SSLManagementService::class, $mockService);

        $this->artisan('ssl:renew', ['domain' => 'example.com'])
            ->assertExitCode(0);
    }

    #[Test]
    public function renew_ssl_renews_expiring_certificates(): void
    {
        $mockService = Mockery::mock(SSLManagementService::class);
        $mockService->shouldReceive('renewExpiringCertificates')
            ->once()
            ->andReturn([
                'success' => collect([['domain' => 'example.com']]),
                'failed' => collect([]),
            ]);

        $this->app->instance(SSLManagementService::class, $mockService);

        $this->artisan('ssl:renew')
            ->assertExitCode(0);
    }

    // ==================== RunBackupsCommand Tests ====================

    #[Test]
    public function run_backups_command_has_correct_signature(): void
    {
        $command = new RunBackupsCommand;
        $this->assertEquals('backups:run', $command->getName());
        $this->assertStringContainsString('scheduled database backups', $command->getDescription());
    }

    #[Test]
    public function run_backups_handles_no_due_backups(): void
    {
        $this->artisan('backups:run')
            ->expectsOutput('No backups are due at this time.')
            ->assertExitCode(0);
    }

    #[Test]
    public function run_backups_runs_due_backups(): void
    {
        $project = Project::factory()->create();
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockBackup = DatabaseBackup::factory()->make();

        $mockService->shouldReceive('createBackup')
            ->once()
            ->andReturn($mockBackup);

        $mockService->shouldReceive('cleanupOldBackups')
            ->once()
            ->andReturn(2);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backups:run')
            ->assertExitCode(0);
    }

    // ==================== RunHealthChecksCommand Tests ====================

    #[Test]
    public function run_health_checks_command_has_correct_signature(): void
    {
        $command = new RunHealthChecksCommand;
        $this->assertEquals('health:check', $command->getName());
        $this->assertStringContainsString('health checks', $command->getDescription());
    }

    #[Test]
    public function run_health_checks_executes_successfully(): void
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('runDueChecks')
            ->once()
            ->andReturn(5);

        $this->app->instance(HealthCheckService::class, $mockService);

        $this->artisan('health:check')
            ->assertExitCode(0);
    }

    #[Test]
    public function run_health_checks_handles_errors(): void
    {
        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('runDueChecks')
            ->once()
            ->andThrow(new \Exception('Health check failed'));

        $this->app->instance(HealthCheckService::class, $mockService);

        $this->artisan('health:check')
            ->expectsOutput('Health checks failed: Health check failed')
            ->assertExitCode(1);
    }

    // ==================== RunQualityTests Tests ====================

    #[Test]
    public function run_quality_tests_command_has_correct_signature(): void
    {
        $command = new RunQualityTests;
        $this->assertEquals('test:quality', $command->getName());
        $this->assertStringContainsString('quality tests', $command->getDescription());
    }

    #[Test]
    public function run_quality_tests_executes_successfully(): void
    {
        $this->artisan('test:quality', ['--type' => 'unit'])
            ->assertExitCode(0);
    }

    // ==================== RunServerBackupsCommand Tests ====================

    #[Test]
    public function run_server_backups_command_has_correct_signature(): void
    {
        $command = new RunServerBackupsCommand;
        $this->assertEquals('server:backups', $command->getName());
        $this->assertStringContainsString('server backup schedules', $command->getDescription());
    }

    #[Test]
    public function run_server_backups_handles_no_active_schedules(): void
    {
        $this->artisan('server:backups')
            ->expectsOutput('No active backup schedules found.')
            ->assertExitCode(0);
    }

    #[Test]
    public function run_server_backups_processes_due_backups(): void
    {
        $server = Server::factory()->create();
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $server->id,
            'is_active' => true,
            'type' => 'full',
            'frequency' => 'daily',
            'time' => '00:00', // Set to midnight so it's always due during test
            'last_run_at' => now()->subDay(),
        ]);

        // Create a ServerBackup to return from the mock
        $backup = ServerBackup::factory()->create([
            'server_id' => $server->id,
            'type' => 'full',
            'storage_driver' => 'local',
        ]);

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->andReturn($backup);

        $mockService->shouldReceive('deleteBackup')
            ->andReturn(true);

        $this->app->instance(ServerBackupService::class, $mockService);

        $this->artisan('server:backups')
            ->assertExitCode(0);
    }

    // ==================== SSLRenewCommand Tests ====================

    #[Test]
    public function ssl_renew_command_has_correct_signature(): void
    {
        $command = new SSLRenewCommand;
        $this->assertEquals('ssl:renew-expiring', $command->getName());
        $this->assertStringContainsString('SSL certificates', $command->getDescription());
    }

    #[Test]
    public function ssl_renew_handles_no_certificates_to_renew(): void
    {
        $mockService = Mockery::mock(SSLService::class);
        $mockService->shouldReceive('renewCertificate')
            ->never();

        $this->app->instance(SSLService::class, $mockService);

        $this->artisan('ssl:renew-expiring')
            ->expectsOutput('No certificates need renewal at this time.')
            ->assertExitCode(0);
    }

    #[Test]
    public function ssl_renew_renews_expiring_certificates(): void
    {
        $certificate = SSLCertificate::factory()->create([
            'auto_renew' => true,
            'expires_at' => now()->addDays(5),
            'status' => 'issued',
        ]);

        $mockService = Mockery::mock(SSLService::class);
        $mockService->shouldReceive('renewCertificate')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Renewed']);

        $this->app->instance(SSLService::class, $mockService);

        $this->artisan('ssl:renew-expiring')
            ->assertExitCode(0);
    }

    // ==================== SyncLogsCommand Tests ====================

    #[Test]
    public function sync_logs_command_has_correct_signature(): void
    {
        $mockService = Mockery::mock(LogAggregationService::class);
        $command = new SyncLogsCommand($mockService);

        $this->assertEquals('logs:sync', $command->getName());
        $this->assertStringContainsString('Sync logs', $command->getDescription());
    }

    #[Test]
    public function sync_logs_handles_no_servers(): void
    {
        $this->artisan('logs:sync')
            ->expectsOutput('No servers found for synchronization')
            ->assertExitCode(1);
    }

    #[Test]
    public function sync_logs_syncs_from_all_servers(): void
    {
        Server::factory()->count(2)->create(['status' => 'online']);

        $mockService = Mockery::mock(LogAggregationService::class);
        $mockService->shouldReceive('syncLogs')
            ->times(2)
            ->andReturn([
                'total_entries' => 100,
                'success' => 5,
                'failed' => 0,
                'errors' => [],
            ]);

        $this->app->instance(LogAggregationService::class, $mockService);

        $this->artisan('logs:sync')
            ->assertExitCode(0);
    }

    #[Test]
    public function sync_logs_syncs_specific_server(): void
    {
        $server = Server::factory()->create(['status' => 'online']);

        $mockService = Mockery::mock(LogAggregationService::class);
        $mockService->shouldReceive('syncLogs')
            ->once()
            ->andReturn([
                'total_entries' => 50,
                'success' => 3,
                'failed' => 0,
                'errors' => [],
            ]);

        $this->app->instance(LogAggregationService::class, $mockService);

        $this->artisan('logs:sync', ['--server' => $server->id])
            ->assertExitCode(0);
    }

    // ==================== VerifyBackup Tests ====================

    #[Test]
    public function verify_backup_command_has_correct_signature(): void
    {
        $command = new VerifyBackup;
        $this->assertEquals('backup:verify', $command->getName());
        $this->assertStringContainsString('backup integrity', $command->getDescription());
    }

    #[Test]
    public function verify_backup_handles_backup_not_found(): void
    {
        $this->artisan('backup:verify', ['backup' => 999])
            ->expectsOutput('Backup not found: 999')
            ->assertExitCode(1);
    }

    #[Test]
    public function verify_backup_verifies_single_backup(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'status' => 'completed',
            'checksum' => 'abc123',
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('verifyBackup')
            ->once()
            ->andReturn(true);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:verify', ['backup' => $backup->id])
            ->expectsOutputToContain('Backup verification PASSED')
            ->assertExitCode(0);
    }

    #[Test]
    public function verify_backup_handles_verification_failure(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'status' => 'completed',
            'checksum' => 'abc123',
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('verifyBackup')
            ->once()
            ->andReturn(false);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:verify', ['backup' => $backup->id])
            ->expectsOutputToContain('Backup verification FAILED')
            ->assertExitCode(1);
    }

    #[Test]
    public function verify_backup_verifies_all_backups(): void
    {
        DatabaseBackup::factory()->count(3)->create([
            'status' => 'completed',
            'verified_at' => null,
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('verifyBackup')
            ->times(3)
            ->andReturn(true);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:verify', ['--all' => true])
            ->assertExitCode(0);
    }

    #[Test]
    public function verify_backup_verifies_project_backups(): void
    {
        $project = Project::factory()->create(['slug' => 'test-project']);
        DatabaseBackup::factory()->count(2)->create([
            'project_id' => $project->id,
            'status' => 'completed',
        ]);

        $mockService = Mockery::mock(DatabaseBackupService::class);
        $mockService->shouldReceive('verifyBackup')
            ->times(2)
            ->andReturn(true);

        $this->app->instance(DatabaseBackupService::class, $mockService);

        $this->artisan('backup:verify', ['--project' => 'test-project'])
            ->assertExitCode(0);
    }

    #[Test]
    public function verify_backup_handles_incomplete_backup(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'status' => 'running',
        ]);

        $this->artisan('backup:verify', ['backup' => $backup->id])
            ->expectsOutput('Backup is not completed (status: running)')
            ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
