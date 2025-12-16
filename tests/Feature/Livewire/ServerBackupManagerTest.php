<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ServerBackupManager;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Models\User;
use App\Services\ServerBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ServerBackupManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.server-backup-manager');
    }

    public function test_component_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->assertSet('showCreateModal', false)
            ->assertSet('showScheduleModal', false)
            ->assertSet('backupType', 'full')
            ->assertSet('storageDriver', 'local')
            ->assertSet('scheduleType', 'full')
            ->assertSet('scheduleFrequency', 'daily')
            ->assertSet('scheduleTime', '02:00')
            ->assertSet('retentionDays', 30)
            ->assertSet('scheduleStorageDriver', 'local');
    }

    public function test_component_displays_backups_list(): void
    {
        ServerBackup::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $backups = $component->viewData('backups');
        $this->assertEquals(3, $backups->total());
    }

    public function test_component_displays_schedules_list(): void
    {
        ServerBackupSchedule::factory()->count(2)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $schedules = $component->viewData('schedules');
        $this->assertCount(2, $schedules);
    }

    // ==================== CREATE BACKUP ====================

    public function test_create_backup_validates_backup_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['backupType']);
    }

    public function test_create_backup_validates_storage_driver(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('storageDriver', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['storageDriver']);
    }

    public function test_create_full_backup_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->set('backupType', 'full')
            ->set('storageDriver', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertSessionHas('message', 'Backup started successfully. This may take several minutes.');
    }

    public function test_create_incremental_backup_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->set('backupType', 'incremental')
            ->set('storageDriver', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertSessionHas('message');
    }

    public function test_create_snapshot_backup_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->set('backupType', 'snapshot')
            ->set('storageDriver', 's3')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertSessionHas('message');
    }

    public function test_create_backup_resets_form_after_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'incremental')
            ->set('storageDriver', 's3')
            ->call('createBackup')
            ->assertSet('backupType', 'full')
            ->assertSet('storageDriver', 'local');
    }

    // ==================== DELETE BACKUP ====================

    public function test_delete_backup_success(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(ServerBackupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteBackup')
                ->once()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('message', 'Backup deleted successfully.');
    }

    public function test_delete_backup_fails_for_other_server(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('error');
    }

    public function test_delete_backup_handles_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', 99999)
            ->assertSessionHas('error');
    }

    public function test_delete_backup_handles_service_exception(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(ServerBackupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteBackup')
                ->once()
                ->andThrow(new \Exception('Delete failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('error');
    }

    // ==================== RESTORE BACKUP ====================

    public function test_restore_backup_success(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('info');
    }

    public function test_restore_backup_fails_for_incomplete_backup(): void
    {
        $backup = ServerBackup::factory()->running()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error', 'Failed to restore backup: Cannot restore incomplete backup');
    }

    public function test_restore_backup_fails_for_other_server(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error');
    }

    public function test_restore_backup_handles_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', 99999)
            ->assertSessionHas('error');
    }

    public function test_restore_failed_backup_shows_error(): void
    {
        $backup = ServerBackup::factory()->failed()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error');
    }

    // ==================== CREATE SCHEDULE ====================

    public function test_create_daily_schedule_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showScheduleModal', true)
            ->set('scheduleType', 'full')
            ->set('scheduleFrequency', 'daily')
            ->set('scheduleTime', '03:00')
            ->set('retentionDays', 30)
            ->set('scheduleStorageDriver', 'local')
            ->call('createSchedule')
            ->assertSet('showScheduleModal', false)
            ->assertSessionHas('message', 'Backup schedule created successfully.');

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'type' => 'full',
            'frequency' => 'daily',
            'time' => '03:00',
            'retention_days' => 30,
            'is_active' => true,
        ]);
    }

    public function test_create_weekly_schedule_validates_day_of_week(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleDayOfWeek', null)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek']);
    }

    public function test_create_weekly_schedule_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showScheduleModal', true)
            ->set('scheduleType', 'incremental')
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleTime', '04:00')
            ->set('scheduleDayOfWeek', 1)
            ->set('retentionDays', 14)
            ->set('scheduleStorageDriver', 's3')
            ->call('createSchedule')
            ->assertSet('showScheduleModal', false)
            ->assertSessionHas('message');

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'frequency' => 'weekly',
            'day_of_week' => 1,
        ]);
    }

    public function test_create_monthly_schedule_validates_day_of_month(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleDayOfMonth', null)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth']);
    }

    public function test_create_monthly_schedule_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showScheduleModal', true)
            ->set('scheduleType', 'snapshot')
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleTime', '01:00')
            ->set('scheduleDayOfMonth', 15)
            ->set('retentionDays', 90)
            ->set('scheduleStorageDriver', 'local')
            ->call('createSchedule')
            ->assertSet('showScheduleModal', false)
            ->assertSessionHas('message');

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'frequency' => 'monthly',
            'day_of_month' => 15,
        ]);
    }

    public function test_create_schedule_validates_schedule_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleType']);
    }

    public function test_create_schedule_validates_frequency(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleFrequency']);
    }

    public function test_create_schedule_validates_time_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleTime', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleTime']);
    }

    public function test_create_schedule_validates_retention_days_min(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('retentionDays', 0)
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays']);
    }

    public function test_create_schedule_validates_retention_days_max(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('retentionDays', 400)
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays']);
    }

    public function test_create_schedule_validates_storage_driver(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleStorageDriver', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleStorageDriver']);
    }

    public function test_create_schedule_resets_form_after_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'snapshot')
            ->set('scheduleFrequency', 'daily')
            ->set('scheduleTime', '05:00')
            ->set('retentionDays', 60)
            ->set('scheduleStorageDriver', 's3')
            ->call('createSchedule')
            ->assertSet('scheduleType', 'full')
            ->assertSet('scheduleFrequency', 'daily')
            ->assertSet('scheduleTime', '02:00')
            ->assertSet('retentionDays', 30)
            ->assertSet('scheduleStorageDriver', 'local');
    }

    // ==================== TOGGLE SCHEDULE ====================

    public function test_toggle_schedule_activates(): void
    {
        $schedule = ServerBackupSchedule::factory()->inactive()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule activated successfully.');

        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule);
        $this->assertTrue($freshSchedule->is_active);
    }

    public function test_toggle_schedule_deactivates(): void
    {
        $schedule = ServerBackupSchedule::factory()->active()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule deactivated successfully.');

        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule);
        $this->assertFalse($freshSchedule->is_active);
    }

    public function test_toggle_schedule_fails_for_other_server(): void
    {
        $otherServer = Server::factory()->create();
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('error');
    }

    public function test_toggle_schedule_handles_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', 99999)
            ->assertSessionHas('error');
    }

    // ==================== DELETE SCHEDULE ====================

    public function test_delete_schedule_success(): void
    {
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule deleted successfully.');

        $this->assertDatabaseMissing('server_backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_delete_schedule_fails_for_other_server(): void
    {
        $otherServer = Server::factory()->create();
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', $schedule->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('server_backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_delete_schedule_handles_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', 99999)
            ->assertSessionHas('error');
    }

    // ==================== UPLOAD TO S3 ====================

    public function test_upload_to_s3_success(): void
    {
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(ServerBackupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('uploadToS3')
                ->once()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('message', 'Backup uploaded to S3 successfully.');
    }

    public function test_upload_to_s3_fails_for_other_server(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('error');
    }

    public function test_upload_to_s3_handles_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', 99999)
            ->assertSessionHas('error');
    }

    public function test_upload_to_s3_handles_service_exception(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(ServerBackupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('uploadToS3')
                ->once()
                ->andThrow(new \Exception('S3 upload failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('error', 'Failed to upload backup: S3 upload failed');
    }

    // ==================== PAGINATION ====================

    public function test_backups_are_paginated(): void
    {
        ServerBackup::factory()->count(15)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $backups = $component->viewData('backups');
        $this->assertCount(10, $backups);
        $this->assertEquals(15, $backups->total());
    }

    public function test_backups_ordered_by_created_at_desc(): void
    {
        $oldBackup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);

        $newBackup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $backups = $component->viewData('backups');
        $this->assertEquals($newBackup->id, $backups->first()->id);
    }

    // ==================== MODAL STATES ====================

    public function test_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->assertSet('showCreateModal', true);
    }

    public function test_close_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->set('showCreateModal', false)
            ->assertSet('showCreateModal', false);
    }

    public function test_open_schedule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showScheduleModal', true)
            ->assertSet('showScheduleModal', true);
    }

    public function test_close_schedule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('showScheduleModal', true)
            ->set('showScheduleModal', false)
            ->assertSet('showScheduleModal', false);
    }

    // ==================== EDGE CASES ====================

    public function test_handles_empty_backups_list(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $backups = $component->viewData('backups');
        $this->assertEquals(0, $backups->total());
    }

    public function test_handles_empty_schedules_list(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $schedules = $component->viewData('schedules');
        $this->assertCount(0, $schedules);
    }

    public function test_only_shows_backups_for_current_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        ServerBackup::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        ServerBackup::factory()->count(5)->create([
            'server_id' => $otherServer->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $backups = $component->viewData('backups');
        $this->assertEquals(3, $backups->total());
    }

    public function test_only_shows_schedules_for_current_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        ServerBackupSchedule::factory()->count(2)->create([
            'server_id' => $this->server->id,
        ]);

        ServerBackupSchedule::factory()->count(4)->create([
            'server_id' => $otherServer->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server]);

        $schedules = $component->viewData('schedules');
        $this->assertCount(2, $schedules);
    }

    public function test_day_of_week_validation_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleDayOfWeek', 7)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek']);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleDayOfWeek', -1)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek']);
    }

    public function test_day_of_month_validation_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleDayOfMonth', 32)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth']);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleDayOfMonth', 0)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth']);
    }

    public function test_time_format_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleTime', '25:00')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleTime']);

        Livewire::actingAs($this->user)
            ->test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleTime', '12:60')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleTime']);
    }

    public function test_backup_types_coverage(): void
    {
        // Test all three backup types work
        foreach (['full', 'incremental', 'snapshot'] as $type) {
            Livewire::actingAs($this->user)
                ->test(ServerBackupManager::class, ['server' => $this->server])
                ->set('backupType', $type)
                ->set('storageDriver', 'local')
                ->call('createBackup')
                ->assertHasNoErrors();
        }
    }

    public function test_storage_drivers_coverage(): void
    {
        // Test both storage drivers work
        foreach (['local', 's3'] as $driver) {
            Livewire::actingAs($this->user)
                ->test(ServerBackupManager::class, ['server' => $this->server])
                ->set('backupType', 'full')
                ->set('storageDriver', $driver)
                ->call('createBackup')
                ->assertHasNoErrors();
        }
    }
}
