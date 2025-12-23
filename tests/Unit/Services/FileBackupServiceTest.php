<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\FileBackup;
use App\Models\Project;
use App\Services\FileBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class FileBackupServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected FileBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileBackupService;

        // Create storage disks for testing
        Storage::fake('local');
        Storage::fake('s3');

        // Prevent actual logging during tests
        Log::spy();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_full_backup_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']); // Use localhost
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-project']);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertEquals('full', $backup->type);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals($project->id, $backup->project_id);
        $this->assertNotNull($backup->filename);
        $this->assertStringContainsString('test-project_full_', $backup->filename);
        $this->assertStringEndsWith('.tar.gz', $backup->filename);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_backup_filename_for_full_backup(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'myapp']);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertStringContainsString('myapp_full_', $backup->filename);
        $this->assertMatchesRegularExpression('/myapp_full_\d{4}-\d{2}-\d{2}_\d{6}\.tar\.gz/', $backup->filename);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_correct_backup_filename_for_incremental_backup(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'myapp']);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project);

        // Act
        $incrementalBackup = $this->service->createIncrementalBackup($project, $fullBackup);

        // Assert
        $this->assertStringContainsString('myapp_incremental_', $incrementalBackup->filename);
        $this->assertMatchesRegularExpression('/myapp_incremental_\d{4}-\d{2}-\d{2}_\d{6}\.tar\.gz/', $incrementalBackup->filename);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_default_source_path_if_not_provided(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-app']);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertEquals('/var/www/test-app', $backup->source_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_custom_source_path_when_provided(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project, [
            'source_path' => '/custom/path/to/project',
        ]);

        // Assert
        $this->assertEquals('/custom/path/to/project', $backup->source_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_backup_on_local_disk_by_default(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertEquals('local', $backup->storage_disk);
        $this->assertStringContainsString('file-backups/', $backup->storage_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_backup_on_s3_when_specified(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project, [
            'storage_disk' => 's3',
        ]);

        // Assert
        $this->assertEquals('s3', $backup->storage_disk);
        $this->assertStringContainsString('file-backups/', $backup->storage_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_default_exclude_patterns(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertIsArray($backup->exclude_patterns);
        $this->assertContains('storage/logs/*', $backup->exclude_patterns);
        $this->assertContains('node_modules/*', $backup->exclude_patterns);
        $this->assertContains('vendor/*', $backup->exclude_patterns);
        $this->assertContains('.git/*', $backup->exclude_patterns);
        $this->assertContains('.env', $backup->exclude_patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_adds_custom_exclude_patterns(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project, [
            'exclude' => ['custom/path/*', '*.tmp'],
        ]);

        // Assert
        $this->assertContains('custom/path/*', $backup->exclude_patterns);
        $this->assertContains('*.tmp', $backup->exclude_patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_project_specific_excludes_from_metadata(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'metadata' => [
                'backup_excludes' => ['public/uploads/temp/*', 'storage/app/cache/*'],
            ],
        ]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertContains('public/uploads/temp/*', $backup->exclude_patterns);
        $this->assertContains('storage/app/cache/*', $backup->exclude_patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_backup_checksum(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertNotEmpty($backup->checksum);
        $this->assertEquals(64, strlen($backup->checksum)); // SHA-256 produces 64 character hex string
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_backup_manifest(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertIsArray($backup->manifest);
        $this->assertNotEmpty($backup->manifest);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_backup_file_count(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertGreaterThan(0, $backup->files_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_backup_size(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertGreaterThan(0, $backup->size_bytes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sets_backup_status_to_pending_initially(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*tar*' => function () {
                // Check initial status before processing
                $backup = FileBackup::latest()->first();
                $this->assertEquals('pending', $backup->status);

                return Process::result(output: 'Success');
            },
            '*scp*' => Process::result(output: 'Success'),
        ]);

        // Act
        $this->service->createFullBackup($project);

        // Assert - Already checked in the callback
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_backup_failure_gracefully(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*tar*' => Process::result(
                output: '',
                errorOutput: 'Backup creation failed',
                exitCode: 1
            ),
        ]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SSH command failed');

        $this->service->createFullBackup($project);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_marks_backup_as_failed_on_error(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*tar*' => Process::result(
                output: '',
                errorOutput: 'Backup creation failed',
                exitCode: 1
            ),
        ]);

        // Act
        try {
            $this->service->createFullBackup($project);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert
        $backup = FileBackup::latest()->first();
        $this->assertEquals('failed', $backup->status);
        $this->assertNotNull($backup->error_message);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_incremental_backup_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project);

        // Act
        $incrementalBackup = $this->service->createIncrementalBackup($project, $fullBackup);

        // Assert
        $this->assertEquals('incremental', $incrementalBackup->type);
        $this->assertEquals('completed', $incrementalBackup->status);
        $this->assertEquals($fullBackup->id, $incrementalBackup->parent_backup_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_if_base_backup_is_not_full(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project);
        $incrementalBackup = $this->service->createIncrementalBackup($project, $fullBackup);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base backup must be a full backup');

        $this->service->createIncrementalBackup($project, $incrementalBackup);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_if_base_backup_is_not_completed(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $incompleteBackup = FileBackup::factory()->create([
            'project_id' => $project->id,
            'type' => 'full',
            'status' => 'pending',
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base backup must be completed');

        $this->service->createIncrementalBackup($project, $incompleteBackup);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_base_backup_source_path_for_incremental(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project, [
            'source_path' => '/custom/backup/path',
        ]);

        // Act
        $incrementalBackup = $this->service->createIncrementalBackup($project, $fullBackup);

        // Assert
        $this->assertEquals('/custom/backup/path', $incrementalBackup->source_path);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_base_backup_storage_disk_for_incremental(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project, [
            'storage_disk' => 's3',
        ]);

        // Act
        $incrementalBackup = $this->service->createIncrementalBackup($project, $fullBackup);

        // Assert
        $this->assertEquals('s3', $incrementalBackup->storage_disk);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_backup_and_file(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        // Create the backup file
        Storage::disk('local')->put($backup->storage_path, 'test backup content');

        // Act
        $this->service->deleteBackup($backup);

        // Assert
        $this->assertDatabaseMissing('file_backups', ['id' => $backup->id]);
        $this->assertFalse(Storage::disk('local')->exists($backup->storage_path));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_child_backups_when_deleting_parent(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project);
        $incremental1 = $this->service->createIncrementalBackup($project, $fullBackup);
        $incremental2 = $this->service->createIncrementalBackup($project, $fullBackup);

        // Act
        $this->service->deleteBackup($fullBackup);

        // Assert
        $this->assertDatabaseMissing('file_backups', ['id' => $fullBackup->id]);
        $this->assertDatabaseMissing('file_backups', ['id' => $incremental1->id]);
        $this->assertDatabaseMissing('file_backups', ['id' => $incremental2->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_restores_backup_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*tar -xzf*' => Process::result(output: 'Extracted'),
            '*mkdir*' => Process::result(output: 'Directory created'),
            '*rm*' => Process::result(output: 'Removed'),
        ]);

        // Act
        $result = $this->service->restoreBackup($backup);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_restores_backup_with_overwrite_option(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*tar -xzf*--overwrite*' => Process::result(output: 'Extracted with overwrite'),
            '*mkdir*' => Process::result(output: 'Directory created'),
            '*rm*' => Process::result(output: 'Removed'),
        ]);

        // Act
        $result = $this->service->restoreBackup($backup, true);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_restores_backup_to_custom_target_path(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*tar -xzf*' => Process::result(output: 'Extracted'),
            '*mkdir*' => Process::result(output: 'Directory created'),
            '*rm*' => Process::result(output: 'Removed'),
        ]);

        // Act
        $result = $this->service->restoreBackup($backup, false, '/custom/restore/path');

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_restoring_incomplete_backup(): void
    {
        // Arrange
        $backup = FileBackup::factory()->create([
            'status' => 'failed',
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot restore incomplete backup');

        $this->service->restoreBackup($backup);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_restores_incremental_backup_chain(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        $fullBackup = $this->service->createFullBackup($project);
        $incremental1 = $this->service->createIncrementalBackup($project, $fullBackup);
        $incremental2 = $this->service->createIncrementalBackup($project, $fullBackup);

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*tar -xzf*' => Process::result(output: 'Extracted'),
            '*mkdir*' => Process::result(output: 'Directory created'),
            '*rm*' => Process::result(output: 'Removed'),
        ]);

        // Act
        $result = $this->service->restoreBackup($incremental2);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_gets_exclude_patterns_correctly(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        // Act
        $patterns = $this->service->getExcludePatterns($project);

        // Assert
        $this->assertIsArray($patterns);
        $this->assertContains('storage/logs/*', $patterns);
        $this->assertContains('vendor/*', $patterns);
        $this->assertContains('node_modules/*', $patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_merges_project_metadata_excludes(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'metadata' => [
                'backup_excludes' => ['custom1/*', 'custom2/*'],
            ],
        ]);

        // Act
        $patterns = $this->service->getExcludePatterns($project);

        // Assert
        $this->assertContains('custom1/*', $patterns);
        $this->assertContains('custom2/*', $patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_merges_additional_excludes(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        // Act
        $patterns = $this->service->getExcludePatterns($project, ['temp/*', 'cache/*']);

        // Assert
        $this->assertContains('temp/*', $patterns);
        $this->assertContains('cache/*', $patterns);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_unique_exclude_patterns(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create([
            'server_id' => $server->id,
            'metadata' => [
                'backup_excludes' => ['vendor/*', 'node_modules/*'], // Duplicates
            ],
        ]);

        // Act
        $patterns = $this->service->getExcludePatterns($project, ['vendor/*']); // More duplicates

        // Assert
        $this->assertEquals(count($patterns), count(array_unique($patterns)));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_successful_backup_completion(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-app']);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        Log::shouldHaveReceived('info')
            ->with('File backup completed', \Mockery::on(function ($context) use ($backup, $project) {
                return $context['backup_id'] === $backup->id
                    && $context['project'] === $project->slug
                    && isset($context['size'])
                    && isset($context['files_count']);
            }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_backup_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-app']);

        Process::fake([
            '*tar*' => Process::result(
                output: '',
                errorOutput: 'Backup failed',
                exitCode: 1
            ),
        ]);

        // Act
        try {
            $this->service->createFullBackup($project);
        } catch (\Exception $e) {
            // Expected
        }

        // Assert
        Log::shouldHaveReceived('error')
            ->with('File backup failed', \Mockery::on(function ($context) {
                return isset($context['backup_id'])
                    && isset($context['project'])
                    && isset($context['error']);
            }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_backup_deletion(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-app']);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        // Act
        $this->service->deleteBackup($backup);

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Backup deleted', \Mockery::on(function ($context) use ($backup, $project) {
                return $context['backup_id'] === $backup->id
                    && $context['project'] === $project->slug;
            }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_logs_restore_start_and_completion(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id, 'slug' => 'test-app']);

        $this->mockFileBackupCommands();

        $backup = $this->service->createFullBackup($project);

        Process::fake([
            '*scp*' => Process::result(output: 'File uploaded'),
            '*tar -xzf*' => Process::result(output: 'Extracted'),
            '*mkdir*' => Process::result(output: 'Directory created'),
            '*rm*' => Process::result(output: 'Removed'),
        ]);

        // Act
        $this->service->restoreBackup($backup);

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Starting backup restore', \Mockery::on(function ($context) use ($backup, $project) {
                return $context['backup_id'] === $backup->id
                    && $context['project'] === $project->slug
                    && isset($context['target_path'])
                    && isset($context['overwrite']);
            }));

        Log::shouldHaveReceived('info')
            ->with('Backup restore completed', \Mockery::on(function ($context) use ($backup) {
                return $context['backup_id'] === $backup->id
                    && isset($context['backups_restored']);
            }));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_ssh_password_authentication(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ssh_password' => 'test-password',
            'ssh_key' => null,
        ]);
        $project = Project::factory()->create(['server_id' => $server->id]);

        Process::fake([
            '*sshpass*ssh*' => Process::result(output: 'Success'),
            '*sshpass*scp*' => Process::result(output: 'Success'),
            '*tar*' => Process::result(output: 'Archive created'),
        ]);

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertEquals('completed', $backup->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_ssh_key_authentication(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ssh_key' => 'test-ssh-key-content',
            'ssh_password' => null,
        ]);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertEquals('completed', $backup->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_started_at_timestamp(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertNotNull($backup->started_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backup->started_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_records_completed_at_timestamp(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertNotNull($backup->completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backup->completed_at);
        $this->assertGreaterThanOrEqual($backup->started_at, $backup->completed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_organizes_backups_in_dated_directories(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->mockFileBackupCommands();

        // Act
        $backup = $this->service->createFullBackup($project);

        // Assert
        $this->assertStringContainsString('file-backups/', $backup->storage_path);
        $this->assertMatchesRegularExpression('#file-backups/\d{4}/\d{2}/\d{2}/#', $backup->storage_path);
    }

    /**
     * Helper method to mock file backup commands
     */
    protected function mockFileBackupCommands(): void
    {
        Process::fake([
            '*tar -czf*' => Process::result(output: 'Archive created successfully'),
            '*tar -tzf*' => Process::result(output: "file1.php\nfile2.php\nfile3.js\nfile4.css\nfile5.json"),
            '*scp*' => Process::result(output: 'File transferred successfully'),
            '*ssh*mkdir*' => Process::result(output: 'Directory created'),
            '*ssh*rm*' => Process::result(output: 'File removed'),
            '*find*tar*' => Process::result(output: 'Incremental archive created'),
        ]);
    }
}
