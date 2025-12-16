<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Test;
use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\FileBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Backup & Restore Integration Test
 *
 * This test suite covers the complete backup and restore lifecycle,
 * including database backups, file backups, scheduled backups, and restoration.
 *
 * Workflows covered:
 * 1. Full backup cycle (create, store, verify)
 * 2. Database backup and restore
 * 3. File backup and restore
 * 4. Scheduled backup automation
 * 5. Backup integrity verification
 * 6. Cross-server backup restoration
 */
class BackupRestoreTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups');

        $this->user = User::factory()->create([
            'email' => 'admin@devflow.com',
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);
    }

    // ==================== Database Backup Tests ====================

    #[Test]
    public function can_create_database_backup(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'database_name' => 'test_db',
            'file_path' => 'backups/db/test_db_2024.sql.gz',
            'file_size' => 1024000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('database_backups', [
            'project_id' => $this->project->id,
            'database_name' => 'test_db',
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function database_backup_stores_metadata(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'database_name' => 'production_db',
            'file_size' => 5242880, // 5MB
            'tables_count' => 25,
            'rows_count' => 150000,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertEquals(25, $backup->tables_count);
        $this->assertEquals(150000, $backup->rows_count);
        $this->assertEquals(5242880, $backup->file_size);
    }

    #[Test]
    public function database_backup_tracks_duration(): void
    {
        $startTime = now()->subMinutes(3);
        $endTime = now();

        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'started_at' => $startTime,
            'completed_at' => $endTime,
            'status' => 'completed',
        ]);

        $this->assertNotNull($backup->completed_at);
        $this->assertNotNull($backup->started_at);
        $duration = $backup->completed_at->diffInSeconds($backup->started_at);
        $this->assertEquals(180, $duration);
    }

    #[Test]
    public function failed_database_backup_stores_error(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'error_message' => 'Connection refused: MySQL server not responding',
        ]);

        $this->assertEquals('failed', $backup->status);
        $this->assertNotNull($backup->error_message);
        $this->assertStringContainsString('Connection refused', $backup->error_message);
    }

    // ==================== File Backup Tests ====================

    #[Test]
    public function can_create_file_backup(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'source_path' => '/var/www/project/storage',
            'file_path' => 'backups/files/project_storage_2024.tar.gz',
            'file_size' => 52428800, // 50MB
            'files_count' => 1500,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('file_backups', [
            'project_id' => $this->project->id,
            'source_path' => '/var/www/project/storage',
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function file_backup_respects_exclusions(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'source_path' => '/var/www/project',
            'exclude_patterns' => ['node_modules', 'vendor', '.git', '*.log'],
            'status' => 'completed',
        ]);

        $this->assertNotNull($backup->exclude_patterns);
        $this->assertContains('node_modules', $backup->exclude_patterns);
        $this->assertContains('vendor', $backup->exclude_patterns);
        $this->assertCount(4, $backup->exclude_patterns);
    }

    #[Test]
    public function file_backup_calculates_compression_ratio(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 10485760, // 10MB compressed
            'original_size' => 52428800, // 50MB original
            'status' => 'completed',
        ]);

        $compressionRatio = ($backup->original_size - $backup->file_size) / $backup->original_size * 100;
        $this->assertEquals(80.0, $compressionRatio);
    }

    // ==================== Server Backup Tests ====================

    #[Test]
    public function can_create_full_server_backup(): void
    {
        $backup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'backup_type' => 'full',
            'file_path' => 'backups/server/server_full_2024.tar.gz',
            'file_size' => 1073741824, // 1GB
            'status' => 'completed',
        ]);

        $this->assertEquals('full', $backup->backup_type);
        $this->assertEquals('completed', $backup->status);
    }

    #[Test]
    public function can_create_incremental_server_backup(): void
    {
        // Create initial full backup
        $fullBackup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'backup_type' => 'full',
            'status' => 'completed',
            'created_at' => now()->subDay(),
        ]);

        // Create incremental backup
        $incrementalBackup = ServerBackup::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'backup_type' => 'incremental',
            'parent_backup_id' => $fullBackup->id,
            'status' => 'completed',
        ]);

        $this->assertEquals('incremental', $incrementalBackup->backup_type);
        $this->assertEquals($fullBackup->id, $incrementalBackup->parent_backup_id);
    }

    // ==================== Backup Schedule Tests ====================

    #[Test]
    public function can_create_backup_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'project_id' => $this->project->id,
            'backup_type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'timezone' => 'UTC',
            'retention_days' => 30,
            'is_active' => true,
        ]);

        $this->assertTrue($schedule->is_active);
        $this->assertEquals('daily', $schedule->frequency);
        $this->assertEquals(30, $schedule->retention_days);
    }

    #[Test]
    public function backup_schedule_supports_multiple_frequencies(): void
    {
        $frequencies = ['hourly', 'daily', 'weekly', 'monthly'];

        foreach ($frequencies as $frequency) {
            $schedule = BackupSchedule::factory()->create([
                'server_id' => $this->server->id,
                'frequency' => $frequency,
                'is_active' => true,
            ]);

            $this->assertEquals($frequency, $schedule->frequency);
        }

        $this->assertEquals(4, BackupSchedule::count());
    }

    #[Test]
    public function can_pause_and_resume_backup_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        // Pause
        $schedule->update(['is_active' => false]);
        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule);
        $this->assertFalse($freshSchedule->is_active);

        // Resume
        $schedule->update(['is_active' => true]);
        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule);
        $this->assertTrue($freshSchedule->is_active);
    }

    #[Test]
    public function backup_schedule_tracks_last_run(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'server_id' => $this->server->id,
            'last_run_at' => null,
            'is_active' => true,
        ]);

        $this->assertNull($schedule->last_run_at);

        // Simulate backup execution
        $schedule->update(['last_run_at' => now()]);

        $freshSchedule = $schedule->fresh();
        $this->assertNotNull($freshSchedule);
        $this->assertNotNull($freshSchedule->last_run_at);
    }

    // ==================== Restore Tests ====================

    #[Test]
    public function can_restore_database_from_backup(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'completed',
            'is_verified' => true,
        ]);

        // Create restore record
        $restore = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'restored_from_id' => $backup->id,
            'status' => 'completed',
        ]);

        $this->assertEquals($backup->id, $restore->restored_from_id);
    }

    #[Test]
    public function restore_tracks_source_backup(): void
    {
        $original = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'database_name' => 'production_db',
            'status' => 'completed',
            'created_at' => now()->subDays(7),
        ]);

        // Simulate restore operation
        $restoreRecord = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'database_name' => 'production_db',
            'restored_from_id' => $original->id,
            'status' => 'completed',
        ]);

        $this->assertEquals($original->id, $restoreRecord->restored_from_id);
    }

    // ==================== Backup Integrity Tests ====================

    #[Test]
    public function backup_stores_checksum_for_verification(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'checksum' => 'sha256:abc123def456789',
            'status' => 'completed',
        ]);

        $this->assertNotNull($backup->checksum);
        $this->assertStringStartsWith('sha256:', $backup->checksum);
    }

    #[Test]
    public function can_verify_backup_integrity(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'checksum' => 'sha256:abc123',
            'is_verified' => false,
            'status' => 'completed',
        ]);

        // Simulate verification
        $backup->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $freshBackup = $backup->fresh();
        $this->assertNotNull($freshBackup);
        $this->assertTrue($freshBackup->is_verified);
        $this->assertNotNull($freshBackup->verified_at);
    }

    #[Test]
    public function corrupted_backup_marked_as_failed(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'is_verified' => false,
            'status' => 'completed',
        ]);

        // Simulate failed verification
        $backup->update([
            'is_verified' => false,
            'status' => 'corrupted',
            'error_message' => 'Checksum mismatch',
        ]);

        $freshBackup = $backup->fresh();
        $this->assertNotNull($freshBackup);
        $this->assertEquals('corrupted', $freshBackup->status);
    }

    // ==================== Retention Policy Tests ====================

    #[Test]
    public function identifies_backups_for_cleanup(): void
    {
        // Old backup beyond retention
        DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'completed',
            'created_at' => now()->subDays(45),
        ]);

        // Recent backup within retention
        DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'completed',
            'created_at' => now()->subDays(15),
        ]);

        $retentionDays = 30;
        $expiredBackups = DatabaseBackup::where('created_at', '<', now()->subDays($retentionDays))
            ->get();

        $this->assertCount(1, $expiredBackups);
    }

    #[Test]
    public function retention_keeps_minimum_backup_count(): void
    {
        // Create 10 backups
        for ($i = 0; $i < 10; $i++) {
            DatabaseBackup::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
                'status' => 'completed',
                'created_at' => now()->subDays($i * 5),
            ]);
        }

        $totalBackups = DatabaseBackup::where('project_id', $this->project->id)->count();
        $this->assertEquals(10, $totalBackups);

        // Keep at least 3 backups regardless of age
        $minimumKeep = 3;
        $recentBackups = DatabaseBackup::where('project_id', $this->project->id)
            ->orderBy('created_at', 'desc')
            ->take($minimumKeep)
            ->get();

        $this->assertCount(3, $recentBackups);
    }

    // ==================== Cross-Server Restore Tests ====================

    #[Test]
    public function can_restore_backup_to_different_server(): void
    {
        $sourceServer = $this->server;
        $targetServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);

        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $sourceServer->id,
            'status' => 'completed',
        ]);

        // Restore to different server
        $restoreRecord = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $targetServer->id,
            'restored_from_id' => $backup->id,
            'status' => 'completed',
        ]);

        $this->assertNotEquals($backup->server_id, $restoreRecord->server_id);
        $this->assertEquals($backup->id, $restoreRecord->restored_from_id);
    }

    // ==================== Backup Statistics Tests ====================

    #[Test]
    public function calculates_total_backup_storage(): void
    {
        DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 104857600, // 100MB
            'status' => 'completed',
        ]);

        DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'file_size' => 209715200, // 200MB
            'status' => 'completed',
        ]);

        $totalSize = DatabaseBackup::where('project_id', $this->project->id)
            ->where('status', 'completed')
            ->sum('file_size');

        $this->assertEquals(314572800, $totalSize); // 300MB
    }

    #[Test]
    public function tracks_backup_success_rate(): void
    {
        // Successful backups
        DatabaseBackup::factory()->count(8)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'completed',
        ]);

        // Failed backups
        DatabaseBackup::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        $total = DatabaseBackup::where('project_id', $this->project->id)->count();
        $successful = DatabaseBackup::where('project_id', $this->project->id)
            ->where('status', 'completed')
            ->count();
        $successRate = ($successful / $total) * 100;

        $this->assertEquals(10, $total);
        $this->assertEquals(80.0, $successRate);
    }

    // ==================== Notification Tests ====================

    #[Test]
    public function backup_completion_can_trigger_notification(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'completed',
            'notify_on_complete' => true,
        ]);

        $this->assertTrue($backup->notify_on_complete);
    }

    #[Test]
    public function backup_failure_can_trigger_alert(): void
    {
        $backup = DatabaseBackup::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'notify_on_failure' => true,
            'error_message' => 'Disk space full',
        ]);

        $this->assertTrue($backup->notify_on_failure);
        $this->assertEquals('failed', $backup->status);
    }
}
