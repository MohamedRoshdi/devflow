<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\{CreatesServers, MocksSSH};
use App\Services\DatabaseBackupService;
use App\Models\{Server, DatabaseBackup, BackupSchedule, Project};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupServiceTest extends TestCase
{
    use RefreshDatabase, CreatesServers, MocksSSH;

    protected DatabaseBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DatabaseBackupService();

        // Create storage disk for testing
        Storage::fake('local');
        Storage::fake('s3');
    }

    /** @test */
    public function it_creates_mysql_backup_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'mysql',
            'database_name' => 'test_db',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('mysql', $backup->database_type);
        $this->assertEquals('test_db', $backup->database_name);
        $this->assertNotNull($backup->file_name);
        $this->assertNotNull($backup->checksum);
    }

    /** @test */
    public function it_generates_correct_backup_filename(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'mysql',
            'database_name' => 'myapp_production',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertStringContainsString('myapp_production', $backup->file_name);
        $this->assertStringContainsString('.sql.gz', $backup->file_name);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}_\d{6}/', $backup->file_name);
    }

    /** @test */
    public function it_handles_backup_failure_gracefully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'mysql',
            'database_name' => 'test_db',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(false);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $backup = $this->service->createBackup($schedule);
    }

    /** @test */
    public function it_calculates_checksum_correctly(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'mysql',
            'database_name' => 'test_db',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertNotEmpty($backup->checksum);
        $this->assertEquals(64, strlen($backup->checksum)); // SHA-256 produces 64 character hex string
    }

    /** @test */
    public function it_verifies_backup_integrity(): void
    {
        // Arrange
        $backup = DatabaseBackup::factory()->create([
            'status' => 'completed',
            'file_path' => storage_path('app/test_backup.sql.gz'),
            'checksum' => hash_file('sha256', __FILE__), // Use this file for testing
            'storage_disk' => 'local',
        ]);

        // Create a temporary file with same content
        $tempFile = storage_path('app/test_backup.sql.gz');
        copy(__FILE__, $tempFile);

        // Act
        $isValid = $this->service->verifyBackup($backup);

        // Assert
        $this->assertTrue($isValid);

        // Cleanup
        @unlink($tempFile);
    }

    /** @test */
    public function it_applies_retention_policy(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_name' => 'test_db',
            'retention_daily' => 2,
            'retention_weekly' => 1,
            'retention_monthly' => 1,
        ]);

        // Create old backups (should be deleted)
        DatabaseBackup::factory()->count(5)->create([
            'project_id' => $project->id,
            'database_name' => 'test_db',
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        // Create recent backups (should be kept)
        DatabaseBackup::factory()->count(2)->create([
            'project_id' => $project->id,
            'database_name' => 'test_db',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Act
        $deletedCount = $this->service->cleanupOldBackups($schedule);

        // Assert
        $this->assertGreaterThan(0, $deletedCount);
        $remainingBackups = DatabaseBackup::where('project_id', $project->id)->count();
        $this->assertLessThanOrEqual(7, $remainingBackups); // 2 daily + some weekly/monthly
    }

    /** @test */
    public function it_supports_postgresql_backups(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'postgresql',
            'database_name' => 'test_db',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('postgresql', $backup->database_type);
    }

    /** @test */
    public function it_supports_sqlite_backups(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'sqlite',
            'database_name' => '/var/www/database.sqlite',
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('sqlite', $backup->database_type);
    }

    /** @test */
    public function it_deletes_backup_and_file(): void
    {
        // Arrange
        $tempFile = storage_path('app/backups/test_backup.sql.gz');
        mkdir(dirname($tempFile), 0755, true);
        file_put_contents($tempFile, 'test content');

        $backup = DatabaseBackup::factory()->create([
            'file_path' => $tempFile,
            'storage_disk' => 'local',
        ]);

        // Act
        $this->service->deleteBackup($backup);

        // Assert
        $this->assertDatabaseMissing('database_backups', ['id' => $backup->id]);
        $this->assertFileDoesNotExist($tempFile);

        // Cleanup
        @rmdir(dirname($tempFile));
    }

    /** @test */
    public function it_restores_database_from_backup(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $backup = DatabaseBackup::factory()->create([
            'server_id' => $server->id,
            'status' => 'completed',
            'database_type' => 'mysql',
            'database_name' => 'test_db',
            'file_path' => __FILE__, // Use this file for testing
            'storage_disk' => 'local',
        ]);

        $this->mockDatabaseBackup(true);

        // Act & Assert - Should not throw exception
        $this->service->restoreBackup($backup);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_prevents_restoring_incomplete_backup(): void
    {
        // Arrange
        $backup = DatabaseBackup::factory()->create([
            'status' => 'failed',
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot restore incomplete backup');

        $this->service->restoreBackup($backup);
    }

    /** @test */
    public function it_uploads_backup_to_s3(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $schedule = BackupSchedule::factory()->create([
            'server_id' => $server->id,
            'project_id' => $project->id,
            'database_type' => 'mysql',
            'database_name' => 'test_db',
            'storage_disk' => 's3',
        ]);

        $this->mockDatabaseBackup(true);

        // Act
        $backup = $this->service->createBackup($schedule);

        // Assert
        $this->assertEquals('s3', $backup->storage_disk);
        $this->assertStringContainsString('backups/', $backup->file_path);
    }
}
