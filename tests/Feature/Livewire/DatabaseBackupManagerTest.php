<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\DatabaseBackupManager;
use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DatabaseBackupManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
    }

    // =========================================================================
    // Component Loading Tests
    // =========================================================================

    public function test_component_renders_for_authenticated_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->assertOk()
            ->assertSet('project.id', $this->project->id);
    }

    public function test_component_loads_backups_for_project(): void
    {
        DatabaseBackup::factory()->completed()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        // Create backup for another project (should not appear)
        DatabaseBackup::factory()->completed()->create();

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertEquals(3, $backups->count());
    }

    public function test_component_loads_schedules_for_project(): void
    {
        BackupSchedule::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        // Create schedule for another project (should not appear)
        BackupSchedule::factory()->create();

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $schedules = $component->viewData('schedules');
        $this->assertEquals(2, $schedules->count());
    }

    public function test_component_calculates_stats_correctly(): void
    {
        DatabaseBackup::factory()->completed()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 1024 * 1024, // 1 MB each
        ]);

        BackupSchedule::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $stats = $component->viewData('stats');
        $this->assertEquals(3, $stats['total_backups']);
        $this->assertEquals(2, $stats['scheduled_backups']);
        $this->assertEquals('3 MB', $stats['total_size']);
    }

    public function test_stats_shows_never_when_no_backups(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $stats = $component->viewData('stats');
        $this->assertEquals('Never', $stats['last_backup']);
    }

    // =========================================================================
    // Backup Creation Tests
    // =========================================================================

    public function test_open_create_backup_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->assertSet('showCreateBackupModal', true)
            ->assertSet('databaseName', '')
            ->assertSet('databaseType', 'mysql');
    }

    public function test_can_create_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) use ($backup) {
            $mock->shouldReceive('createBackup')
                ->zeroOrMoreTimes()
                ->andReturn($backup);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'test_database')
            ->set('databaseType', 'mysql')
            ->call('createBackup')
            ->assertSet('showCreateBackupModal', false)
            ->assertDispatched('notification');
    }

    public function test_create_backup_validates_database_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', '')
            ->set('databaseType', 'mysql')
            ->call('createBackup')
            ->assertHasErrors(['databaseName' => 'required']);
    }

    public function test_create_backup_validates_database_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'test_db')
            ->set('databaseType', 'invalid_type')
            ->call('createBackup')
            ->assertHasErrors(['databaseType' => 'in']);
    }

    public function test_create_backup_handles_service_error(): void
    {
        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createBackup')
                ->zeroOrMoreTimes()
                ->andThrow(new \Exception('Backup failed'));
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'test_database')
            ->set('databaseType', 'mysql')
            ->call('createBackup')
            ->assertDispatched('notification');
    }

    // =========================================================================
    // Backup Deletion Tests
    // =========================================================================

    public function test_confirm_delete_backup_opens_modal(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmDeleteBackup', $backup->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('backupIdToDelete', $backup->id);
    }

    public function test_can_delete_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('deleteBackup')
                ->zeroOrMoreTimes()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmDeleteBackup', $backup->id)
            ->call('deleteBackup')
            ->assertSet('showDeleteModal', false)
            ->assertSet('backupIdToDelete', null)
            ->assertDispatched('notification');
    }

    public function test_cannot_delete_backup_from_another_project(): void
    {
        $otherProject = Project::factory()->create();
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $otherProject->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmDeleteBackup', $backup->id)
            ->call('deleteBackup')
            ->assertDispatched('notification');
    }

    public function test_delete_backup_does_nothing_without_backup_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->set('backupIdToDelete', null)
            ->call('deleteBackup')
            ->assertNotDispatched('notification');
    }

    // =========================================================================
    // Backup Restoration Tests
    // =========================================================================

    public function test_confirm_restore_backup_opens_modal(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmRestoreBackup', $backup->id)
            ->assertSet('showRestoreModal', true)
            ->assertSet('backupIdToRestore', $backup->id);
    }

    public function test_can_restore_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('restoreBackup')
                ->zeroOrMoreTimes()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmRestoreBackup', $backup->id)
            ->call('restoreBackup')
            ->assertSet('showRestoreModal', false)
            ->assertSet('backupIdToRestore', null)
            ->assertDispatched('notification');
    }

    public function test_cannot_restore_backup_from_another_project(): void
    {
        $otherProject = Project::factory()->create();
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $otherProject->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmRestoreBackup', $backup->id)
            ->call('restoreBackup')
            ->assertDispatched('notification');
    }

    public function test_restore_backup_does_nothing_without_backup_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->set('backupIdToRestore', null)
            ->call('restoreBackup')
            ->assertNotDispatched('notification');
    }

    // =========================================================================
    // Backup Verification Tests
    // =========================================================================

    public function test_confirm_verify_backup_opens_modal(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmVerifyBackup', $backup->id)
            ->assertSet('showVerifyModal', true)
            ->assertSet('backupIdToVerify', $backup->id);
    }

    public function test_can_verify_backup_success(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyBackup')
                ->zeroOrMoreTimes()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmVerifyBackup', $backup->id)
            ->call('verifyBackup')
            ->assertSet('showVerifyModal', false)
            ->assertSet('backupIdToVerify', null)
            ->assertDispatched('notification');
    }

    public function test_verify_backup_shows_error_on_checksum_mismatch(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyBackup')
                ->zeroOrMoreTimes()
                ->andReturn(false);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmVerifyBackup', $backup->id)
            ->call('verifyBackup')
            ->assertDispatched('notification');
    }

    public function test_cannot_verify_backup_from_another_project(): void
    {
        $otherProject = Project::factory()->create();
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $otherProject->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('confirmVerifyBackup', $backup->id)
            ->call('verifyBackup')
            ->assertDispatched('notification');
    }

    public function test_verify_backup_does_nothing_without_backup_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->set('backupIdToVerify', null)
            ->call('verifyBackup')
            ->assertNotDispatched('notification');
    }

    // =========================================================================
    // Schedule Creation Tests
    // =========================================================================

    public function test_open_schedule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->assertSet('showScheduleModal', true)
            ->assertSet('scheduleDatabase', '')
            ->assertSet('frequency', 'daily')
            ->assertSet('time', '02:00');
    }

    public function test_can_create_daily_schedule(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'app_database')
            ->set('scheduleDatabaseType', 'mysql')
            ->set('frequency', 'daily')
            ->set('time', '03:00')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertSet('showScheduleModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('backup_schedules', [
            'project_id' => $this->project->id,
            'database_name' => 'app_database',
            'database_type' => 'mysql',
            'frequency' => 'daily',
            'time' => '03:00:00',
            'is_active' => true,
        ]);
    }

    public function test_can_create_weekly_schedule(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'weekly_db')
            ->set('scheduleDatabaseType', 'postgresql')
            ->set('frequency', 'weekly')
            ->set('time', '04:00')
            ->set('dayOfWeek', 1) // Monday
            ->set('retentionDays', 60)
            ->set('storageDisk', 's3')
            ->call('createSchedule');

        $this->assertDatabaseHas('backup_schedules', [
            'project_id' => $this->project->id,
            'database_name' => 'weekly_db',
            'frequency' => 'weekly',
            'day_of_week' => 1,
        ]);
    }

    public function test_can_create_monthly_schedule(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'monthly_db')
            ->set('scheduleDatabaseType', 'mysql')
            ->set('frequency', 'monthly')
            ->set('time', '05:00')
            ->set('dayOfMonth', 15)
            ->set('retentionDays', 90)
            ->set('storageDisk', 'local')
            ->call('createSchedule');

        $this->assertDatabaseHas('backup_schedules', [
            'project_id' => $this->project->id,
            'database_name' => 'monthly_db',
            'frequency' => 'monthly',
            'day_of_month' => 15,
        ]);
    }

    public function test_schedule_validates_database_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', '')
            ->set('frequency', 'daily')
            ->set('time', '02:00')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDatabase' => 'required']);
    }

    public function test_schedule_validates_frequency(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'test_db')
            ->set('frequency', 'invalid')
            ->set('time', '02:00')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertHasErrors(['frequency' => 'in']);
    }

    public function test_schedule_validates_time_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'test_db')
            ->set('frequency', 'daily')
            ->set('time', 'invalid_time')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertHasErrors(['time' => 'date_format']);
    }

    public function test_schedule_validates_retention_days_min(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'test_db')
            ->set('frequency', 'daily')
            ->set('time', '02:00')
            ->set('retentionDays', 0)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays' => 'min']);
    }

    public function test_schedule_validates_retention_days_max(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'test_db')
            ->set('frequency', 'daily')
            ->set('time', '02:00')
            ->set('retentionDays', 400)
            ->set('storageDisk', 'local')
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays' => 'max']);
    }

    public function test_schedule_validates_storage_disk(): void
    {
        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('scheduleDatabase', 'test_db')
            ->set('frequency', 'daily')
            ->set('time', '02:00')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'azure')
            ->call('createSchedule')
            ->assertHasErrors(['storageDisk' => 'in']);
    }

    // =========================================================================
    // Schedule Toggle Tests
    // =========================================================================

    public function test_can_toggle_schedule_on(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('toggleSchedule', $schedule->id)
            ->assertDispatched('notification');

        $schedule->refresh();
        $this->assertTrue($schedule->is_active);
    }

    public function test_can_toggle_schedule_off(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('toggleSchedule', $schedule->id)
            ->assertDispatched('notification');

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
    }

    public function test_cannot_toggle_schedule_from_another_project(): void
    {
        $otherProject = Project::factory()->create();
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('toggleSchedule', $schedule->id)
            ->assertDispatched('notification');
    }

    // =========================================================================
    // Schedule Deletion Tests
    // =========================================================================

    public function test_can_delete_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('deleteSchedule', $schedule->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_cannot_delete_schedule_from_another_project(): void
    {
        $otherProject = Project::factory()->create();
        $schedule = BackupSchedule::factory()->create([
            'project_id' => $otherProject->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('deleteSchedule', $schedule->id)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    // =========================================================================
    // Database Type Tests
    // =========================================================================

    public function test_can_create_mysql_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) use ($backup) {
            $mock->shouldReceive('createBackup')->zeroOrMoreTimes()->andReturn($backup);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'mysql_db')
            ->set('databaseType', 'mysql')
            ->call('createBackup')
            ->assertSet('showCreateBackupModal', false);
    }

    public function test_can_create_postgresql_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) use ($backup) {
            $mock->shouldReceive('createBackup')->zeroOrMoreTimes()->andReturn($backup);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'postgres_db')
            ->set('databaseType', 'postgresql')
            ->call('createBackup')
            ->assertSet('showCreateBackupModal', false);
    }

    public function test_can_create_sqlite_backup(): void
    {
        $backup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $this->mock(DatabaseBackupService::class, function (MockInterface $mock) use ($backup) {
            $mock->shouldReceive('createBackup')->zeroOrMoreTimes()->andReturn($backup);
        });

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project])
            ->call('openCreateBackupModal')
            ->set('databaseName', 'sqlite_db')
            ->set('databaseType', 'sqlite')
            ->call('createBackup')
            ->assertSet('showCreateBackupModal', false);
    }

    // =========================================================================
    // Pagination Tests
    // =========================================================================

    public function test_backups_are_paginated(): void
    {
        DatabaseBackup::factory()->completed()->count(15)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertEquals(10, $backups->count()); // First page
        $this->assertEquals(15, $backups->total());
    }

    // =========================================================================
    // Format Bytes Tests
    // =========================================================================

    public function test_format_bytes_displays_zero(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $stats = $component->viewData('stats');
        $this->assertEquals('0 B', $stats['total_size']);
    }

    public function test_format_bytes_displays_kilobytes(): void
    {
        DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 2048, // 2 KB
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $stats = $component->viewData('stats');
        $this->assertEquals('2 KB', $stats['total_size']);
    }

    public function test_format_bytes_displays_megabytes(): void
    {
        DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 5 * 1024 * 1024, // 5 MB
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $stats = $component->viewData('stats');
        $this->assertEquals('5 MB', $stats['total_size']);
    }

    // =========================================================================
    // Backups Ordering Tests
    // =========================================================================

    public function test_backups_are_ordered_by_created_at_desc(): void
    {
        $oldBackup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(2),
        ]);

        $newBackup = DatabaseBackup::factory()->completed()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertEquals($newBackup->id, $backups->first()->id);
        $this->assertEquals($oldBackup->id, $backups->last()->id);
    }
}
