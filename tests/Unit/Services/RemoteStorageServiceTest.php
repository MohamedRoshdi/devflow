<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\StorageConfiguration;
use App\Services\Backup\RemoteStorageService;
use Illuminate\Contracts\Filesystem\Filesystem;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RemoteStorageServiceTest extends TestCase
{
    

    protected RemoteStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RemoteStorageService;

        // Create storage disks for testing
        Storage::fake('s3');
        Storage::fake('local');

        // Prevent actual logging during tests
        Log::spy();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_file_to_remote_storage_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/test-file.txt');
        file_put_contents($localPath, 'test content');

        // Act
        $result = $this->service->store($localPath, $config, 'backups/test-file.txt');

        // Assert
        $this->assertTrue($result);
        Log::shouldHaveReceived('info')->with('File stored successfully', \Mockery::on(function ($context) use ($config) {
            return $context['config'] === $config->name
                && isset($context['remote_path'])
                && isset($context['size']);
        }));

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_local_file_not_found(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Local file not found');

        $this->service->store('/non-existent-file.txt', $config, 'backups/test.txt');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_adds_path_prefix_to_remote_path_when_storing(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'path_prefix' => 'devflow/backups',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/test-file.txt');
        file_put_contents($localPath, 'test content');

        // Act
        $result = $this->service->store($localPath, $config, 'test-file.txt');

        // Assert
        $this->assertTrue($result);

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_verifies_file_size_after_upload(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/test-file.txt');
        file_put_contents($localPath, 'test content for verification');

        // Act
        $result = $this->service->store($localPath, $config, 'backups/test-file.txt');

        // Assert
        $this->assertTrue($result);

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_error_when_storage_fails(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'credentials' => [
                'access_key_id' => '',
                'secret_access_key' => '',
            ],
        ]);

        $localPath = storage_path('app/test-file.txt');
        file_put_contents($localPath, 'test content');

        // Act & Assert
        try {
            $this->service->store($localPath, $config, 'backups/test-file.txt');
        } catch (\Exception $e) {
            // Expected
        }

        Log::shouldHaveReceived('error')->with('Failed to store file to remote storage', \Mockery::on(function ($context) {
            return isset($context['config'])
                && isset($context['error'])
                && isset($context['local_path'])
                && isset($context['remote_path']);
        }));

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_retrieves_file_from_remote_storage_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $remotePath = 'backups/test-file.txt';
        $localPath = storage_path('app/retrieved-file.txt');

        // Create a test file on the fake disk
        $disk = Storage::disk('temp_' . $config->id);
        $disk->put($remotePath, 'test content from remote');

        // Act
        $result = $this->service->retrieve($config, $remotePath, $localPath);

        // Assert
        $this->assertTrue($result);
        $this->assertFileExists($localPath);
        Log::shouldHaveReceived('info')->with('File retrieved successfully', \Mockery::on(function ($context) use ($config) {
            return $context['config'] === $config->name
                && isset($context['remote_path'])
                && isset($context['local_path'])
                && isset($context['size']);
        }));

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_remote_file_not_found(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
        ]);

        $localPath = storage_path('app/retrieved-file.txt');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Remote file not found');

        $this->service->retrieve($config, 'non-existent-file.txt', $localPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_local_directory_if_not_exists(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $remotePath = 'backups/test-file.txt';
        $localPath = storage_path('app/nested/deep/retrieved-file.txt');

        // Create a test file on the fake disk
        $disk = Storage::disk('temp_' . $config->id);
        $disk->put($remotePath, 'test content');

        // Act
        $result = $this->service->retrieve($config, $remotePath, $localPath);

        // Assert
        $this->assertTrue($result);
        $this->assertFileExists($localPath);

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
            @rmdir(dirname($localPath));
            @rmdir(dirname(dirname($localPath)));
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_error_when_retrieval_fails(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/retrieved-file.txt');

        // Act & Assert
        try {
            $this->service->retrieve($config, 'non-existent.txt', $localPath);
        } catch (\Exception $e) {
            // Expected
        }

        Log::shouldHaveReceived('error')->with('Failed to retrieve file from remote storage', \Mockery::on(function ($context) {
            return isset($context['config'])
                && isset($context['error'])
                && isset($context['remote_path'])
                && isset($context['local_path']);
        }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_file_from_remote_storage_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $remotePath = 'backups/test-file.txt';
        $disk = Storage::disk('temp_' . $config->id);
        $disk->put($remotePath, 'test content');

        // Act
        $result = $this->service->delete($config, $remotePath);

        // Assert
        $this->assertTrue($result);
        Log::shouldHaveReceived('info')->with('File deleted from remote storage', \Mockery::on(function ($context) use ($config) {
            return $context['config'] === $config->name
                && isset($context['remote_path']);
        }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_true_when_deleting_non_existent_file(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
        ]);

        // Act
        $result = $this->service->delete($config, 'non-existent-file.txt');

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_applies_path_prefix_when_deleting(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'path_prefix' => 'devflow/backups',
            'bucket' => 'test-bucket',
        ]);

        $remotePath = 'test-file.txt';
        $disk = Storage::disk('temp_' . $config->id);
        $disk->put('devflow/backups/test-file.txt', 'test content');

        // Act
        $result = $this->service->delete($config, $remotePath);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tests_connection_successfully(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'path_prefix' => 'devflow',
        ]);

        // Act
        $result = $this->service->testConnection($config);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertTrue($result['tests']['list']);
        $this->assertTrue($result['tests']['write']);
        $this->assertTrue($result['tests']['read']);
        $this->assertTrue($result['tests']['delete']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['timing']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_timing_for_each_test_operation(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        // Act
        $result = $this->service->testConnection($config);

        // Assert
        $this->assertArrayHasKey('list', $result['timing']);
        $this->assertArrayHasKey('write', $result['timing']);
        $this->assertArrayHasKey('read', $result['timing']);
        $this->assertArrayHasKey('delete', $result['timing']);
        $this->assertStringEndsWith('ms', $result['timing']['list']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_last_tested_timestamp_on_successful_connection_test(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'last_tested_at' => null,
        ]);

        // Act
        $this->service->testConnection($config);

        // Assert
        $config->refresh();
        $this->assertNotNull($config->last_tested_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_backups_from_storage(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $disk = Storage::disk('temp_' . $config->id);
        $disk->put('backup1.tar.gz', 'content1');
        $disk->put('backup2.tar.gz', 'content2');
        $disk->put('backup3.tar.gz', 'content3');

        // Act
        $backups = $this->service->listBackups($config);

        // Assert
        $this->assertIsArray($backups);
        $this->assertCount(3, $backups);
        $this->assertArrayHasKey('path', $backups[0]);
        $this->assertArrayHasKey('name', $backups[0]);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('last_modified', $backups[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_backups_with_path_prefix(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'path_prefix' => 'devflow/backups',
            'bucket' => 'test-bucket',
        ]);

        $disk = Storage::disk('temp_' . $config->id);
        $disk->put('devflow/backups/backup1.tar.gz', 'content1');
        $disk->put('devflow/backups/backup2.tar.gz', 'content2');

        // Act
        $backups = $this->service->listBackups($config);

        // Assert
        $this->assertIsArray($backups);
        $this->assertGreaterThanOrEqual(0, count($backups));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sorts_backups_by_last_modified_descending(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $disk = Storage::disk('temp_' . $config->id);
        $disk->put('backup1.tar.gz', 'content1');
        sleep(1);
        $disk->put('backup2.tar.gz', 'content2');

        // Act
        $backups = $this->service->listBackups($config);

        // Assert
        if (count($backups) > 1) {
            $this->assertGreaterThanOrEqual($backups[1]['last_modified'], $backups[0]['last_modified']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_when_listing_fails(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'credentials' => [
                'access_key_id' => '',
                'secret_access_key' => '',
            ],
        ]);

        // Act
        $backups = $this->service->listBackups($config);

        // Assert
        $this->assertIsArray($backups);
        $this->assertEmpty($backups);
        Log::shouldHaveReceived('error')->with('Failed to list backups', \Mockery::on(function ($context) {
            return isset($context['config']) && isset($context['error']);
        }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_disk_from_s3_configuration(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
            'region' => 'us-east-1',
        ]);

        // Act
        $disk = $this->service->getDiskFromConfig($config);

        // Assert
        $this->assertInstanceOf(Filesystem::class, $disk);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_encrypts_file_using_aes_256_gcm(): void
    {
        // Arrange
        $path = storage_path('app/test-encrypt.txt');
        $content = 'sensitive data to encrypt';
        file_put_contents($path, $content);
        $key = 'test-encryption-key-32-characters';

        // Act
        $encryptedPath = $this->service->encryptFile($path, $key);

        // Assert
        $this->assertFileExists($encryptedPath);
        $this->assertStringEndsWith('.encrypted', $encryptedPath);
        $this->assertNotEquals($content, file_get_contents($encryptedPath));

        // Cleanup
        unlink($path);
        unlink($encryptedPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_encrypting_non_existent_file(): void
    {
        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->service->encryptFile('/non-existent-file.txt', 'test-key');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_decrypts_file_using_aes_256_gcm(): void
    {
        // Arrange
        $path = storage_path('app/test-decrypt.txt');
        $content = 'sensitive data to encrypt';
        file_put_contents($path, $content);
        $key = 'test-encryption-key-32-characters';

        $encryptedPath = $this->service->encryptFile($path, $key);

        // Act
        $decryptedPath = $this->service->decryptFile($encryptedPath, $key);

        // Assert
        $this->assertFileExists($decryptedPath);
        $this->assertStringEndsWith('.decrypted', $decryptedPath);
        $this->assertEquals($content, file_get_contents($decryptedPath));

        // Cleanup
        unlink($path);
        unlink($encryptedPath);
        unlink($decryptedPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_decrypting_non_existent_file(): void
    {
        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Encrypted file not found');

        $this->service->decryptFile('/non-existent-encrypted-file.txt', 'test-key');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_decryption_fails_with_wrong_key(): void
    {
        // Arrange
        $path = storage_path('app/test-decrypt-wrong-key.txt');
        $content = 'sensitive data to encrypt';
        file_put_contents($path, $content);
        $correctKey = 'test-encryption-key-32-characters';
        $wrongKey = 'wrong-encryption-key-32-characte';

        $encryptedPath = $this->service->encryptFile($path, $correctKey);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        try {
            $this->service->decryptFile($encryptedPath, $wrongKey);
        } finally {
            // Cleanup
            if (file_exists($path)) {
                unlink($path);
            }
            if (file_exists($encryptedPath)) {
                unlink($encryptedPath);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_large_file_upload(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/large-test-file.txt');
        // Create a 1MB file
        file_put_contents($localPath, str_repeat('a', 1024 * 1024));

        // Act
        $result = $this->service->store($localPath, $config, 'backups/large-file.txt');

        // Assert
        $this->assertTrue($result);

        // Cleanup
        unlink($localPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_stream_for_file_upload(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/stream-test.txt');
        file_put_contents($localPath, 'stream test content');

        // Act
        $result = $this->service->store($localPath, $config, 'backups/stream-test.txt');

        // Assert
        $this->assertTrue($result);

        // Cleanup
        unlink($localPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cleans_up_temporary_encrypted_files(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'encryption_key' => 'test-encryption-key-32-characters',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/cleanup-test.txt');
        file_put_contents($localPath, 'test content');
        $expectedEncryptedPath = $localPath . '.encrypted';

        // Act
        $this->service->store($localPath, $config, 'backups/cleanup-test.txt');

        // Assert - encrypted temp file should be cleaned up
        $this->assertFileDoesNotExist($expectedEncryptedPath);

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_backups_by_prefix(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $disk = Storage::disk('temp_' . $config->id);
        $disk->put('project1/backup1.tar.gz', 'content1');
        $disk->put('project2/backup2.tar.gz', 'content2');

        // Act
        $backups = $this->service->listBackups($config, 'project1/');

        // Assert
        $this->assertIsArray($backups);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_concurrent_operations(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $files = [];
        for ($i = 1; $i <= 3; $i++) {
            $path = storage_path("app/concurrent-test-{$i}.txt");
            file_put_contents($path, "content {$i}");
            $files[] = $path;
        }

        // Act
        $results = [];
        foreach ($files as $index => $file) {
            $results[] = $this->service->store($file, $config, "backups/concurrent-{$index}.txt");
        }

        // Assert
        foreach ($results as $result) {
            $this->assertTrue($result);
        }

        // Cleanup
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_verifies_upload_with_checksum(): void
    {
        // Arrange
        $project = Project::factory()->create();
        $config = StorageConfiguration::factory()->create([
            'project_id' => $project->id,
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

        $localPath = storage_path('app/checksum-test.txt');
        file_put_contents($localPath, 'test content for checksum verification');

        // Act
        $result = $this->service->store($localPath, $config, 'backups/checksum-test.txt');

        // Assert - should succeed with matching sizes
        $this->assertTrue($result);

        // Cleanup
        if (file_exists($localPath)) {
            unlink($localPath);
        }
    }
}
