<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Servers\ServerBackupManager;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Models\User;
use App\Services\ServerBackupService;

use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Comprehensive unit tests for the ServerBackupManager Livewire component
 *
 * Tests cover:
 * - Component rendering and initialization
 * - Backup creation (full, incremental, snapshot)
 * - Backup listing and pagination
 * - Backup restoration process
 * - Backup scheduling (create, edit, delete schedules)
 * - Backup deletion with confirmation
 * - Authorization checks
 * - Storage provider selection (local, S3)
 * - Backup status updates
 * - Error handling for failed backups
 * - Upload to S3 functionality
 * - Schedule toggling (activate/deactivate)
 * - Modal state management
 * */
#[CoversClass(\App\Livewire\Servers\ServerBackupManager::class)]
class ServerBackupManagerTest extends TestCase
{
    

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user and server
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();

        $this->actingAs($this->user);
    }

    // ==========================================
    // Component Rendering Tests
    // ==========================================

    #[Test]
    public function component_renders_successfully_with_server(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.server-backup-manager')
            ->assertSet('server.id', $this->server->id);
    }

    #[Test]
    public function component_initializes_with_default_values(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
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

    #[Test]
    public function component_displays_backups_for_server(): void
    {
        $backups = ServerBackup::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('backups', function ($viewBackups) use ($backups) {
                return $viewBackups->count() === 3;
            });
    }

    #[Test]
    public function component_displays_schedules_for_server(): void
    {
        $schedules = ServerBackupSchedule::factory()->count(2)->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('schedules', function ($viewSchedules) use ($schedules) {
                return $viewSchedules->count() === 2;
            });
    }

    #[Test]
    public function component_paginates_backups(): void
    {
        ServerBackup::factory()->count(15)->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('backups', function ($backups) {
                return $backups->total() === 15 && $backups->perPage() === 10;
            });
    }

    // ==========================================
    // Backup Creation Tests
    // ==========================================

    #[Test]
    public function can_create_full_backup(): void
    {
        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->with(Mockery::on(fn ($server) => $server->id === $this->server->id))
            ->andReturn(ServerBackup::factory()->create());

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'full')
            ->set('storageDriver', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertSessionHas('message', 'Backup started successfully. This may take several minutes.')
            ->assertSet('backupType', 'full')
            ->assertSet('storageDriver', 'local');
    }

    #[Test]
    public function can_create_incremental_backup(): void
    {
        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createIncrementalBackup')
            ->once()
            ->with(Mockery::on(fn ($server) => $server->id === $this->server->id))
            ->andReturn(ServerBackup::factory()->incremental()->create());

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'incremental')
            ->set('storageDriver', 's3')
            ->call('createBackup')
            ->assertSessionHas('message');
    }

    #[Test]
    public function can_create_snapshot_backup(): void
    {
        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createSnapshot')
            ->once()
            ->with(Mockery::on(fn ($server) => $server->id === $this->server->id))
            ->andReturn(ServerBackup::factory()->snapshot()->create());

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'snapshot')
            ->set('storageDriver', 'local')
            ->call('createBackup')
            ->assertSessionHas('message');
    }

    #[Test]
    public function create_backup_validates_backup_type(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['backupType' => 'in']);
    }

    #[Test]
    public function create_backup_validates_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('storageDriver', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['storageDriver' => 'in']);
    }

    #[Test]
    public function create_backup_handles_service_exceptions(): void
    {
        Log::shouldReceive('error')->once();

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->andThrow(new \Exception('Backup service unavailable'));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'full')
            ->call('createBackup')
            ->assertSessionHas('error');
    }

    #[Test]
    public function create_backup_resets_form_on_success(): void
    {
        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->andReturn(ServerBackup::factory()->create());

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('backupType', 'incremental')
            ->set('storageDriver', 's3')
            ->call('createBackup')
            ->assertSet('backupType', 'full')
            ->assertSet('storageDriver', 'local');
    }

    // ==========================================
    // Backup Deletion Tests
    // ==========================================

    #[Test]
    public function can_delete_backup(): void
    {
        $backup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('deleteBackup')
            ->once()
            ->with(Mockery::on(fn ($b) => $b->id === $backup->id));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('message', 'Backup deleted successfully.');
    }

    #[Test]
    public function cannot_delete_backup_from_different_server(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Log::shouldReceive('error')->once();

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function delete_backup_handles_not_found(): void
    {
        Log::shouldReceive('error')->once();

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', 99999)
            ->assertSessionHas('error');
    }

    #[Test]
    public function delete_backup_handles_service_exceptions(): void
    {
        $backup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Log::shouldReceive('error')->once();

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('deleteBackup')
            ->once()
            ->andThrow(new \Exception('Cannot delete backup'));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteBackup', $backup->id)
            ->assertSessionHas('error');
    }

    // ==========================================
    // Backup Restoration Tests
    // ==========================================

    #[Test]
    public function can_restore_completed_backup(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('restoreBackup')
            ->once()
            ->with(Mockery::on(fn ($b) => $b->id === $backup->id));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('info', 'Backup restoration started. This may take several minutes and will require a server reboot.');
    }

    #[Test]
    public function cannot_restore_incomplete_backup(): void
    {
        $backup = ServerBackup::factory()->running()->create([
            'server_id' => $this->server->id,
        ]);

        Log::shouldReceive('error')->once();

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function cannot_restore_backup_from_different_server(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $otherServer->id,
        ]);

        Log::shouldReceive('error')->once();

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function restore_backup_handles_service_exceptions(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        Log::shouldReceive('error')->once();

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('restoreBackup')
            ->once()
            ->andThrow(new \Exception('Restoration failed'));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('restoreBackup', $backup->id)
            ->assertSessionHas('error');
    }

    // ==========================================
    // Schedule Creation Tests
    // ==========================================

    #[Test]
    public function can_create_daily_schedule(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'full')
            ->set('scheduleFrequency', 'daily')
            ->set('scheduleTime', '03:00')
            ->set('retentionDays', 15)
            ->set('scheduleStorageDriver', 'local')
            ->call('createSchedule')
            ->assertSessionHas('message', 'Backup schedule created successfully.')
            ->assertSet('showScheduleModal', false);

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'type' => 'full',
            'frequency' => 'daily',
            'time' => '03:00',
            'retention_days' => 15,
            'storage_driver' => 'local',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function can_create_weekly_schedule(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'incremental')
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleTime', '02:00')
            ->set('scheduleDayOfWeek', 0) // Sunday
            ->set('retentionDays', 30)
            ->set('scheduleStorageDriver', 's3')
            ->call('createSchedule')
            ->assertSessionHas('message', 'Backup schedule created successfully.');

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'type' => 'incremental',
            'frequency' => 'weekly',
            'day_of_week' => 0,
            'retention_days' => 30,
        ]);
    }

    #[Test]
    public function can_create_monthly_schedule(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'snapshot')
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleTime', '01:00')
            ->set('scheduleDayOfMonth', 15)
            ->set('retentionDays', 60)
            ->set('scheduleStorageDriver', 'local')
            ->call('createSchedule')
            ->assertSessionHas('message', 'Backup schedule created successfully.');

        $this->assertDatabaseHas('server_backup_schedules', [
            'server_id' => $this->server->id,
            'type' => 'snapshot',
            'frequency' => 'monthly',
            'day_of_month' => 15,
            'retention_days' => 60,
        ]);
    }

    #[Test]
    public function create_schedule_validates_required_fields(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', '')
            ->set('scheduleFrequency', '')
            ->set('scheduleTime', '')
            ->set('retentionDays', '')
            ->call('createSchedule')
            ->assertHasErrors([
                'scheduleType' => 'required',
                'scheduleFrequency' => 'required',
                'scheduleTime' => 'required',
                'retentionDays' => 'required',
            ]);
    }

    #[Test]
    public function create_schedule_validates_type(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleType' => 'in']);
    }

    #[Test]
    public function create_schedule_validates_frequency(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleFrequency' => 'in']);
    }

    #[Test]
    public function create_schedule_validates_time_format(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleTime', '25:99')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleTime' => 'regex']);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleTime', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleTime' => 'regex']);
    }

    #[Test]
    public function create_schedule_validates_retention_days(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('retentionDays', 0)
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays' => 'min']);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('retentionDays', 500)
            ->call('createSchedule')
            ->assertHasErrors(['retentionDays' => 'max']);
    }

    #[Test]
    public function create_schedule_requires_day_of_week_for_weekly(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleTime', '02:00')
            ->set('retentionDays', 30)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek' => 'required']);
    }

    #[Test]
    public function create_schedule_validates_day_of_week_range(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleDayOfWeek', 7)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek' => 'max']);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleDayOfWeek', -1)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfWeek' => 'min']);
    }

    #[Test]
    public function create_schedule_requires_day_of_month_for_monthly(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleTime', '02:00')
            ->set('retentionDays', 30)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth' => 'required']);
    }

    #[Test]
    public function create_schedule_validates_day_of_month_range(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleDayOfMonth', 0)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth' => 'min']);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleFrequency', 'monthly')
            ->set('scheduleDayOfMonth', 32)
            ->call('createSchedule')
            ->assertHasErrors(['scheduleDayOfMonth' => 'max']);
    }

    #[Test]
    public function create_schedule_validates_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleStorageDriver', 'invalid')
            ->call('createSchedule')
            ->assertHasErrors(['scheduleStorageDriver' => 'in']);
    }

    #[Test]
    public function create_schedule_resets_form_on_success(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleType', 'incremental')
            ->set('scheduleFrequency', 'weekly')
            ->set('scheduleTime', '05:00')
            ->set('scheduleDayOfWeek', 3)
            ->set('retentionDays', 45)
            ->set('scheduleStorageDriver', 's3')
            ->call('createSchedule')
            ->assertSet('scheduleType', 'full')
            ->assertSet('scheduleFrequency', 'daily')
            ->assertSet('scheduleTime', '02:00')
            ->assertSet('retentionDays', 30)
            ->assertSet('scheduleStorageDriver', 'local');
    }

    #[Test]
    public function create_schedule_handles_exceptions(): void
    {
        Log::shouldReceive('error')->once();

        // Force an exception by using an invalid server_id through reflection
        $component = Livewire::test(ServerBackupManager::class, ['server' => $this->server]);
        $server = new Server();
        $server->id = 99999; // Non-existent ID
        $component->set('server', $server);

        $component->call('createSchedule')
            ->assertSessionHas('error');
    }

    // ==========================================
    // Schedule Management Tests
    // ==========================================

    #[Test]
    public function can_toggle_schedule_activation(): void
    {
        $schedule = ServerBackupSchedule::factory()->active()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule deactivated successfully.');

        $this->assertDatabaseHas('server_backup_schedules', [
            'id' => $schedule->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function can_toggle_schedule_deactivation(): void
    {
        $schedule = ServerBackupSchedule::factory()->inactive()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule activated successfully.');

        $this->assertDatabaseHas('server_backup_schedules', [
            'id' => $schedule->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function cannot_toggle_schedule_from_different_server(): void
    {
        $otherServer = Server::factory()->create();
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', $schedule->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function toggle_schedule_handles_not_found(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('toggleSchedule', 99999)
            ->assertSessionHas('error');
    }

    #[Test]
    public function can_delete_schedule(): void
    {
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', $schedule->id)
            ->assertSessionHas('message', 'Schedule deleted successfully.');

        $this->assertDatabaseMissing('server_backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    #[Test]
    public function cannot_delete_schedule_from_different_server(): void
    {
        $otherServer = Server::factory()->create();
        $schedule = ServerBackupSchedule::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', $schedule->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('server_backup_schedules', [
            'id' => $schedule->id,
        ]);
    }

    #[Test]
    public function delete_schedule_handles_not_found(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('deleteSchedule', 99999)
            ->assertSessionHas('error');
    }

    // ==========================================
    // Upload to S3 Tests
    // ==========================================

    #[Test]
    public function can_upload_backup_to_s3(): void
    {
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $this->server->id,
        ]);

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('uploadToS3')
            ->once()
            ->with(Mockery::on(fn ($b) => $b->id === $backup->id));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('message', 'Backup uploaded to S3 successfully.');
    }

    #[Test]
    public function cannot_upload_backup_from_different_server_to_s3(): void
    {
        $otherServer = Server::factory()->create();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function upload_to_s3_handles_service_exceptions(): void
    {
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $mockService = Mockery::mock(ServerBackupService::class);
        $mockService->shouldReceive('uploadToS3')
            ->once()
            ->andThrow(new \Exception('S3 upload failed'));

        $this->app->instance(ServerBackupService::class, $mockService);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', $backup->id)
            ->assertSessionHas('error');
    }

    #[Test]
    public function upload_to_s3_handles_not_found(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->call('uploadToS3', 99999)
            ->assertSessionHas('error');
    }

    // ==========================================
    // Modal State Management Tests
    // ==========================================

    #[Test]
    public function can_toggle_create_modal(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertSet('showCreateModal', false)
            ->set('showCreateModal', true)
            ->assertSet('showCreateModal', true)
            ->set('showCreateModal', false)
            ->assertSet('showCreateModal', false);
    }

    #[Test]
    public function can_toggle_schedule_modal(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertSet('showScheduleModal', false)
            ->set('showScheduleModal', true)
            ->assertSet('showScheduleModal', true)
            ->set('showScheduleModal', false)
            ->assertSet('showScheduleModal', false);
    }

    // ==========================================
    // Event Handling Tests
    // ==========================================

    #[Test]
    public function component_listens_to_backup_created_event(): void
    {
        $component = Livewire::test(ServerBackupManager::class, ['server' => $this->server]);

        $listeners = $component->get('listeners');

        $this->assertArrayHasKey('backupCreated', $listeners);
        $this->assertEquals('$refresh', $listeners['backupCreated']);
    }

    // ==========================================
    // Storage Provider Tests
    // ==========================================

    #[Test]
    public function can_select_local_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('storageDriver', 'local')
            ->assertSet('storageDriver', 'local');
    }

    #[Test]
    public function can_select_s3_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('storageDriver', 's3')
            ->assertSet('storageDriver', 's3');
    }

    #[Test]
    public function can_select_local_schedule_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleStorageDriver', 'local')
            ->assertSet('scheduleStorageDriver', 'local');
    }

    #[Test]
    public function can_select_s3_schedule_storage_driver(): void
    {
        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->set('scheduleStorageDriver', 's3')
            ->assertSet('scheduleStorageDriver', 's3');
    }

    // ==========================================
    // Edge Cases and Error Handling Tests
    // ==========================================

    #[Test]
    public function component_only_shows_backups_for_current_server(): void
    {
        $otherServer = Server::factory()->create();

        ServerBackup::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        ServerBackup::factory()->count(5)->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('backups', function ($backups) {
                return $backups->count() === 3;
            });
    }

    #[Test]
    public function component_only_shows_schedules_for_current_server(): void
    {
        $otherServer = Server::factory()->create();

        ServerBackupSchedule::factory()->count(2)->create([
            'server_id' => $this->server->id,
        ]);

        ServerBackupSchedule::factory()->count(4)->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('schedules', function ($schedules) {
                return $schedules->count() === 2;
            });
    }

    #[Test]
    public function backups_are_ordered_by_created_at_desc(): void
    {
        $backup1 = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(2),
        ]);

        $backup2 = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDay(),
        ]);

        $backup3 = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('backups', function ($backups) use ($backup1, $backup2, $backup3) {
                $ids = $backups->pluck('id')->toArray();

                return $ids[0] === $backup3->id &&
                       $ids[1] === $backup2->id &&
                       $ids[2] === $backup1->id;
            });
    }

    #[Test]
    public function schedules_are_ordered_by_created_at_desc(): void
    {
        $schedule1 = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(2),
        ]);

        $schedule2 = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDay(),
        ]);

        $schedule3 = ServerBackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        Livewire::test(ServerBackupManager::class, ['server' => $this->server])
            ->assertViewHas('schedules', function ($schedules) use ($schedule1, $schedule2, $schedule3) {
                $ids = $schedules->pluck('id')->toArray();

                return $ids[0] === $schedule3->id &&
                       $ids[1] === $schedule2->id &&
                       $ids[2] === $schedule1->id;
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
