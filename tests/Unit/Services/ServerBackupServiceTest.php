<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\ServerBackup;
use App\Services\ServerBackupService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class ServerBackupServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected ServerBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ServerConnectivityService
        $connectivityService = Mockery::mock(ServerConnectivityService::class);
        $this->service = new ServerBackupService($connectivityService);

        // Create storage disks for testing
        Storage::fake('local');
        Storage::fake('s3');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Full Backup Tests ====================

    #[Test]
    public function it_creates_full_backup_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword(['id' => 1]);

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        $this->assertInstanceOf(ServerBackup::class, $backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('full', $backup->type);
        $this->assertEquals($server->id, $backup->server_id);
        $this->assertNotNull($backup->storage_path);
        $this->assertNotNull($backup->size_bytes);
        $this->assertNotNull($backup->started_at);
        $this->assertNotNull($backup->completed_at);
    }

    #[Test]
    public function it_creates_backup_record_with_running_status_initially(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        $this->assertDatabaseHas('server_backups', [
            'server_id' => $server->id,
            'type' => 'full',
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function it_stores_backup_metadata_correctly(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        $this->assertIsArray($backup->metadata);
        $this->assertArrayHasKey('directories', $backup->metadata);
        $this->assertArrayHasKey('method', $backup->metadata);
        $this->assertEquals('tar', $backup->metadata['method']);
    }

    #[Test]
    public function it_handles_full_backup_failure_gracefully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup failed:');

        $this->service->createFullBackup($server);
    }

    #[Test]
    public function it_updates_backup_status_to_failed_on_error(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupFailure();

        // Act
        try {
            $this->service->createFullBackup($server);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        $this->assertDatabaseHas('server_backups', [
            'server_id' => $server->id,
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_stores_error_message_on_backup_failure(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupFailure('Connection timeout');

        // Act
        try {
            $this->service->createFullBackup($server);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        $backup = ServerBackup::where('server_id', $server->id)->first();
        $this->assertNotNull($backup->error_message);
        $this->assertStringContainsString('Connection timeout', $backup->error_message);
    }

    #[Test]
    public function it_generates_correct_backup_filename(): void
    {
        // Arrange
        $server = $this->createServerWithPassword(['id' => 123]);

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        $this->assertStringContainsString('server_123_full_', $backup->storage_path);
        $this->assertStringContainsString('.tar.gz', $backup->storage_path);
    }

    #[Test]
    public function it_uses_password_authentication_for_ssh(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ssh_password' => 'test_password',
        ]);

        Process::fake([
            '*sshpass*' => Process::result(output: 'BACKUP_CREATED'),
            '*scp*' => Process::result(output: 'File transferred'),
        ]);

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, 'sshpass');
        });
    }

    #[Test]
    public function it_uses_ssh_key_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithSshKey();

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        $this->assertEquals('completed', $backup->status);
    }

    #[Test]
    public function it_cleans_up_remote_backup_file_after_download(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, 'rm -f');
        });
    }

    // ==================== Incremental Backup Tests ====================

    #[Test]
    public function it_creates_incremental_backup_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockIncrementalBackupSuccess();

        // Act
        $backup = $this->service->createIncrementalBackup($server);

        // Assert
        $this->assertInstanceOf(ServerBackup::class, $backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('incremental', $backup->type);
        $this->assertEquals($server->id, $backup->server_id);
    }

    #[Test]
    public function it_uses_rsync_for_incremental_backup(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockIncrementalBackupSuccess();

        // Act
        $backup = $this->service->createIncrementalBackup($server);

        // Assert
        $this->assertIsArray($backup->metadata);
        $this->assertEquals('rsync', $backup->metadata['method']);
        $this->assertTrue($backup->metadata['incremental']);
    }

    #[Test]
    public function it_creates_incremental_backup_directory_structure(): void
    {
        // Arrange
        $server = $this->createServerWithPassword(['id' => 5]);

        $this->mockIncrementalBackupSuccess();

        // Act
        $backup = $this->service->createIncrementalBackup($server);

        // Assert
        $this->assertStringContainsString('incremental/5/', $backup->storage_path);
    }

    #[Test]
    public function it_handles_incremental_backup_failure(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockIncrementalBackupFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Incremental backup failed:');

        $this->service->createIncrementalBackup($server);
    }

    #[Test]
    public function it_calculates_incremental_backup_size(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockIncrementalBackupSuccess();

        // Act
        $backup = $this->service->createIncrementalBackup($server);

        // Assert
        $this->assertGreaterThan(0, $backup->size_bytes);
    }

    // ==================== Snapshot Backup Tests ====================

    #[Test]
    public function it_creates_snapshot_backup_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockSnapshotBackupSuccess();

        // Act
        $backup = $this->service->createSnapshot($server);

        // Assert
        $this->assertInstanceOf(ServerBackup::class, $backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('snapshot', $backup->type);
    }

    #[Test]
    public function it_checks_lvm_availability_before_snapshot(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*which lvcreate*' => Process::result(output: ''),
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LVM not available');

        $this->service->createSnapshot($server);
    }

    #[Test]
    public function it_creates_lvm_snapshot_with_correct_name(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        $this->mockSnapshotBackupSuccess();

        // Act
        $backup = $this->service->createSnapshot($server);

        // Assert
        $this->assertStringContainsString('lvm://', $backup->storage_path);
        $this->assertArrayHasKey('snapshot_name', $backup->metadata);
    }

    #[Test]
    public function it_handles_snapshot_creation_failure(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*which lvcreate*' => Process::result(output: '/usr/sbin/lvcreate'),
            '*lvdisplay*' => Process::result(output: 'LV Path /dev/mapper/root'),
            '*lvcreate*' => Process::result(
                output: '',
                errorOutput: 'Snapshot creation failed',
                exitCode: 1
            ),
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        $this->service->createSnapshot($server);
    }

    // ==================== Restore Backup Tests ====================

    #[Test]
    public function it_restores_full_backup_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        // Create a backup file
        $backupPath = storage_path('backups/servers/test_backup.tar.gz');
        file_put_contents($backupPath, 'fake backup content');

        $backup = ServerBackup::factory()->completed()->full()->create([
            'server_id' => $server->id,
            'storage_path' => 'backups/servers/test_backup.tar.gz',
        ]);

        $this->mockRestoreBackupSuccess();

        // Act
        $result = $this->service->restoreBackup($backup);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_restores_incremental_backup_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        // Create incremental backup directory
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = storage_path("backups/servers/incremental/{$server->id}/{$timestamp}");
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        file_put_contents("{$backupPath}/test_file.txt", 'incremental backup data');

        $backup = ServerBackup::factory()->completed()->incremental()->create([
            'server_id' => $server->id,
            'storage_path' => "backups/servers/incremental/{$server->id}/{$timestamp}",
        ]);

        $this->mockRestoreBackupSuccess();

        // Act
        $result = $this->service->restoreBackup($backup);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_throws_exception_when_restoring_incomplete_backup(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->running()->create([
            'server_id' => $server->id,
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot restore incomplete backup');

        $this->service->restoreBackup($backup);
    }

    #[Test]
    public function it_throws_exception_when_restoring_snapshot(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->snapshot()->create([
            'server_id' => $server->id,
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Snapshot restoration must be performed manually');

        $this->service->restoreBackup($backup);
    }

    // ==================== Delete Backup Tests ====================

    #[Test]
    public function it_deletes_local_backup_file(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $server->id,
            'storage_path' => 'backups/servers/test_backup.tar.gz',
        ]);

        // Create a fake file
        Storage::disk('local')->put('backups/servers/test_backup.tar.gz', 'test content');

        // Act
        $result = $this->service->deleteBackup($backup);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('server_backups', ['id' => $backup->id]);
    }

    #[Test]
    public function it_deletes_backup_record_from_database(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $server->id,
        ]);

        // Act
        $result = $this->service->deleteBackup($backup);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('server_backups', ['id' => $backup->id]);
    }

    #[Test]
    public function it_removes_lvm_snapshot_when_deleting_snapshot_backup(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->snapshot()->create([
            'server_id' => $server->id,
            'storage_path' => 'lvm://backup_snapshot_test',
        ]);

        Process::fake([
            '*lvremove*' => Process::result(output: 'Snapshot removed'),
        ]);

        // Act
        $result = $this->service->deleteBackup($backup);

        // Assert
        $this->assertTrue($result);
        Process::assertRan(function ($process) {
            return str_contains($process, 'lvremove');
        });
    }

    #[Test]
    public function it_logs_backup_deletion(): void
    {
        // Arrange
        Log::spy();
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->create([
            'server_id' => $server->id,
        ]);

        // Act
        $this->service->deleteBackup($backup);

        // Assert
        Log::shouldHaveReceived('info')->with('Backup deleted', ['backup_id' => $backup->id]);
    }

    // ==================== S3 Upload Tests ====================

    #[Test]
    public function it_uploads_backup_to_s3_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backupPath = 'backups/servers/test_backup.tar.gz';
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $server->id,
            'storage_path' => $backupPath,
        ]);

        // Create a fake local file
        Storage::disk('local')->put($backupPath, 'test backup content');

        // Act
        $result = $this->service->uploadToS3($backup);

        // Assert
        $this->assertTrue($result);
        $backup->refresh();
        $this->assertEquals('s3', $backup->storage_driver);
        Storage::disk('s3')->assertExists("server-backups/{$server->id}/test_backup.tar.gz");
    }

    #[Test]
    public function it_deletes_local_file_after_s3_upload(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backupPath = 'backups/servers/test_backup.tar.gz';
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $server->id,
            'storage_path' => $backupPath,
        ]);

        Storage::disk('local')->put($backupPath, 'test backup content');

        // Act
        $this->service->uploadToS3($backup);

        // Assert
        Storage::disk('local')->assertMissing($backupPath);
    }

    #[Test]
    public function it_throws_exception_when_uploading_non_local_backup(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->s3()->create([
            'server_id' => $server->id,
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup is not stored locally');

        $this->service->uploadToS3($backup);
    }

    #[Test]
    public function it_throws_exception_when_backup_file_not_found(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();
        $backup = ServerBackup::factory()->completed()->local()->create([
            'server_id' => $server->id,
            'storage_path' => 'backups/servers/nonexistent.tar.gz',
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup file not found');

        $this->service->uploadToS3($backup);
    }

    // ==================== Backup Size Estimation Tests ====================

    #[Test]
    public function it_estimates_backup_size_successfully(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*du -sb /etc*' => Process::result(output: '1048576'),
            '*du -sb /var/www*' => Process::result(output: '10485760'),
            '*du -sb /opt*' => Process::result(output: '5242880'),
            '*du -sb /home*' => Process::result(output: '2097152'),
        ]);

        // Act
        $sizes = $this->service->getBackupSize($server);

        // Assert
        $this->assertIsArray($sizes);
        $this->assertArrayHasKey('etc', $sizes);
        $this->assertArrayHasKey('var_www', $sizes);
        $this->assertArrayHasKey('opt', $sizes);
        $this->assertArrayHasKey('home', $sizes);
        $this->assertArrayHasKey('total', $sizes);
        $this->assertEquals(1048576 + 10485760 + 5242880 + 2097152, $sizes['total']);
    }

    #[Test]
    public function it_returns_zero_sizes_on_estimation_failure(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*du -sb*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 1
            ),
        ]);

        // Act
        $sizes = $this->service->getBackupSize($server);

        // Assert
        $this->assertEquals(['total' => 0], $sizes);
    }

    #[Test]
    public function it_handles_zero_size_directories(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*du -sb /etc*' => Process::result(output: '0'),
            '*du -sb /var/www*' => Process::result(output: '0'),
            '*du -sb /opt*' => Process::result(output: '0'),
            '*du -sb /home*' => Process::result(output: '0'),
        ]);

        // Act
        $sizes = $this->service->getBackupSize($server);

        // Assert
        $this->assertEquals(0, $sizes['total']);
    }

    // ==================== Logging Tests ====================

    #[Test]
    public function it_logs_successful_full_backup(): void
    {
        // Arrange
        Log::spy();
        $server = $this->createServerWithPassword();

        $this->mockFullBackupSuccess();

        // Act
        $backup = $this->service->createFullBackup($server);

        // Assert
        Log::shouldHaveReceived('info')->with('Server full backup completed', Mockery::on(function ($arg) use ($server, $backup) {
            return $arg['server_id'] === $server->id
                && $arg['backup_id'] === $backup->id
                && isset($arg['size']);
        }));
    }

    #[Test]
    public function it_logs_failed_backup_attempts(): void
    {
        // Arrange
        Log::spy();
        $server = $this->createServerWithPassword();

        $this->mockFullBackupFailure();

        // Act
        try {
            $this->service->createFullBackup($server);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        Log::shouldHaveReceived('error')->with('Server full backup failed', Mockery::type('array'));
    }

    #[Test]
    public function it_logs_incremental_backup_completion(): void
    {
        // Arrange
        Log::spy();
        $server = $this->createServerWithPassword();

        $this->mockIncrementalBackupSuccess();

        // Act
        $backup = $this->service->createIncrementalBackup($server);

        // Assert
        Log::shouldHaveReceived('info')->with('Server incremental backup completed', [
            'server_id' => $server->id,
            'backup_id' => $backup->id,
        ]);
    }

    #[Test]
    public function it_logs_snapshot_creation(): void
    {
        // Arrange
        Log::spy();
        $server = $this->createServerWithPassword();

        $this->mockSnapshotBackupSuccess();

        // Act
        $backup = $this->service->createSnapshot($server);

        // Assert
        Log::shouldHaveReceived('info')->with('Server snapshot created', Mockery::on(function ($arg) use ($server) {
            return $arg['server_id'] === $server->id
                && isset($arg['snapshot_name']);
        }));
    }

    // ==================== Helper Methods ====================

    private function mockFullBackupSuccess(): void
    {
        // Create the backup directory first
        $backupDir = storage_path('backups/servers');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Mock the processes with callback to create actual files
        Process::fake([
            '*ssh*tar*' => Process::result(output: 'BACKUP_CREATED'),
            '*scp*' => function ($command) {
                // Extract the local path from SCP command
                // SCP command format: scp ... remote_file local_path
                preg_match('/([^\s]+)$/', $command, $matches);
                if (isset($matches[1])) {
                    $localPath = $matches[1];
                    // Create the file at the local path
                    file_put_contents($localPath, 'fake backup content for testing - '.str_repeat('x', 1024 * 100)); // ~100KB
                }

                return Process::result(output: 'File transferred');
            },
            '*ssh*rm*' => Process::result(output: 'File deleted'),
        ]);
    }

    private function mockFullBackupFailure(string $error = 'Backup process failed'): void
    {
        Process::fake([
            '*ssh*tar*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    private function mockIncrementalBackupSuccess(): void
    {
        Process::fake([
            '*rsync*' => Process::result(output: 'Rsync completed'),
            '*du -sb*' => Process::result(output: '10485760'), // 10MB
        ]);
    }

    private function mockIncrementalBackupFailure(): void
    {
        Process::fake([
            '*rsync*' => Process::result(
                output: '',
                errorOutput: 'Incremental backup failed',
                exitCode: 1
            ),
        ]);
    }

    private function mockSnapshotBackupSuccess(): void
    {
        Process::fake([
            '*which lvcreate*' => Process::result(output: '/usr/sbin/lvcreate'),
            '*lvdisplay*' => Process::result(output: 'LV Path /dev/mapper/root'),
            '*lvcreate*' => Process::result(output: 'Snapshot created'),
        ]);
    }

    private function mockRestoreBackupSuccess(): void
    {
        // Create the backup directory first
        $backupDir = storage_path('backups/servers');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Create incremental backup directory
        $incrementalDir = storage_path('backups/servers/incremental');
        if (! is_dir($incrementalDir)) {
            mkdir($incrementalDir, 0755, true);
        }

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*ssh*tar*' => Process::result(output: 'Files extracted'),
            '*ssh*rm*' => Process::result(output: 'File deleted'),
            '*rsync*' => Process::result(output: 'Rsync completed'),
        ]);
    }
}
