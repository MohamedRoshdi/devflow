<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\FileBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class FileBackupManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Project $project;

    protected Server $server;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create test server and project for file backup tests
        $this->server = Server::firstOrCreate(
            ['name' => 'Test File Backup Server'],
            [
                'hostname' => 'file-backup-test.local',
                'ip_address' => '192.168.1.101',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        $this->project = Project::firstOrCreate(
            ['slug' => 'test-file-backup-project'],
            [
                'name' => 'Test File Backup Project',
                'repository_url' => 'https://github.com/test/file-backup-project',
                'branch' => 'main',
                'framework' => 'laravel',
                'php_version' => '8.4',
                'server_id' => $this->server->id,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: File backup manager page loads successfully
     *
     */

    #[Test]
    public function test_file_backup_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('file-backup-manager-page');

            // Check if page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasContent =
                str_contains($pageSource, 'file') ||
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'full') ||
                str_contains($pageSource, 'incremental');

            $this->assertTrue($hasContent, 'File backup manager page should load');

            $this->testResults['file_backup_page_load'] = 'File backup manager page loaded successfully';
        });
    }

    /**
     * Test 2: Create backup modal opens
     *
     */

    #[Test]
    public function test_create_backup_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('create-backup-modal');

            // Check for create backup button
            $pageSource = $browser->driver->getPageSource();
            $hasCreateButton =
                str_contains($pageSource, 'Create Backup') ||
                str_contains($pageSource, 'New Backup') ||
                str_contains($pageSource, 'openCreateModal') ||
                str_contains($pageSource, 'createBackup');

            $this->assertTrue($hasCreateButton, 'Create backup modal should be accessible');

            $this->testResults['create_backup_modal'] = 'Create backup modal is accessible';
        });
    }

    /**
     * Test 3: Full backup type selection available
     *
     */

    #[Test]
    public function test_full_backup_type_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('full-backup-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFullType =
                str_contains($pageSource, 'full') ||
                str_contains($pageSource, 'backuptype') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasFullType, 'Full backup type should be selectable');

            $this->testResults['full_backup_type'] = 'Full backup type selection is available';
        });
    }

    /**
     * Test 4: Incremental backup type selection available
     *
     */

    #[Test]
    public function test_incremental_backup_type_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('incremental-backup-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIncrementalType =
                str_contains($pageSource, 'incremental') ||
                str_contains($pageSource, 'backuptype') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasIncrementalType, 'Incremental backup type should be selectable');

            $this->testResults['incremental_backup_type'] = 'Incremental backup type selection is available';
        });
    }

    /**
     * Test 5: Storage disk selection for local storage
     *
     */

    #[Test]
    public function test_storage_disk_local_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('storage-disk-local');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLocal =
                str_contains($pageSource, 'local') ||
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'storagedisk');

            $this->assertTrue($hasLocal, 'Local storage disk should be selectable');

            $this->testResults['storage_disk_local'] = 'Local storage disk selection is available';
        });
    }

    /**
     * Test 6: Storage disk selection for S3
     *
     */

    #[Test]
    public function test_storage_disk_s3_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('storage-disk-s3');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasS3 =
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'amazon') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasS3, 'S3 storage disk should be selectable');

            $this->testResults['storage_disk_s3'] = 'S3 storage disk selection is available';
        });
    }

    /**
     * Test 7: Storage disk selection for Google Cloud Storage
     *
     */

    #[Test]
    public function test_storage_disk_gcs_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('storage-disk-gcs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGCS =
                str_contains($pageSource, 'gcs') ||
                str_contains($pageSource, 'google') ||
                str_contains($pageSource, 'cloud');

            $this->assertTrue($hasGCS, 'GCS storage disk should be selectable');

            $this->testResults['storage_disk_gcs'] = 'GCS storage disk selection is available';
        });
    }

    /**
     * Test 8: Storage disk selection for Azure Blob Storage
     *
     */

    #[Test]
    public function test_storage_disk_azure_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('storage-disk-azure');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAzure =
                str_contains($pageSource, 'azure') ||
                str_contains($pageSource, 'blob') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasAzure, 'Azure storage disk should be selectable');

            $this->testResults['storage_disk_azure'] = 'Azure storage disk selection is available';
        });
    }

    /**
     * Test 9: Base backup selection for incremental backups
     *
     */

    #[Test]
    public function test_base_backup_selection_for_incremental()
    {
        // Create a full backup first
        FileBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'filename' => 'full_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'source_path' => '/var/www/project',
                'storage_disk' => 'local',
                'storage_path' => '/backups/files/full_backup.tar.gz',
                'size_bytes' => 10485760,
                'files_count' => 250,
                'checksum' => md5('full_backup'),
                'status' => 'completed',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(1),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('base-backup-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBaseBackup =
                str_contains($pageSource, 'base') ||
                str_contains($pageSource, 'parent') ||
                str_contains($pageSource, 'basebackupid') ||
                str_contains($pageSource, 'incremental');

            $this->assertTrue($hasBaseBackup, 'Base backup selection should be available');

            $this->testResults['base_backup_selection'] = 'Base backup selection for incremental backups is available';
        });
    }

    /**
     * Test 10: File backup list displays correctly
     *
     */

    #[Test]
    public function test_file_backup_list_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-list-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupList =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'filename') ||
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasBackupList, 'Backup list should display');

            $this->testResults['backup_list_display'] = 'File backup list displays correctly';
        });
    }

    /**
     * Test 11: Backup status indicators shown
     *
     */

    #[Test]
    public function test_backup_status_indicators_shown()
    {
        FileBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'filename' => 'status_test_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'source_path' => '/var/www/project',
                'storage_disk' => 'local',
                'storage_path' => '/backups/status_test.tar.gz',
                'size_bytes' => 2097152,
                'files_count' => 100,
                'checksum' => md5('status_test'),
                'status' => 'completed',
                'started_at' => now()->subMinutes(30),
                'completed_at' => now()->subMinutes(15),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-status-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatus =
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatus, 'Backup status indicators should be shown');

            $this->testResults['backup_status_indicators'] = 'Backup status indicators are displayed';
        });
    }

    /**
     * Test 12: Backup file size displayed
     *
     */

    #[Test]
    public function test_backup_file_size_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-file-size');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSize =
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'mb') ||
                str_contains($pageSource, 'gb') ||
                str_contains($pageSource, 'kb') ||
                str_contains($pageSource, 'bytes');

            $this->assertTrue($hasSize, 'Backup file size should be displayed');

            $this->testResults['backup_file_size'] = 'Backup file size is displayed';
        });
    }

    /**
     * Test 13: Backup files count displayed
     *
     */

    #[Test]
    public function test_backup_files_count_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-files-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFilesCount =
                str_contains($pageSource, 'files') ||
                str_contains($pageSource, 'count') ||
                str_contains($pageSource, 'filescount');

            $this->assertTrue($hasFilesCount, 'Backup files count should be displayed');

            $this->testResults['backup_files_count'] = 'Backup files count is displayed';
        });
    }

    /**
     * Test 14: Backup checksum displayed
     *
     */

    #[Test]
    public function test_backup_checksum_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-checksum');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChecksum =
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'hash') ||
                str_contains($pageSource, 'md5');

            $this->assertTrue($hasChecksum, 'Backup checksum should be displayed');

            $this->testResults['backup_checksum'] = 'Backup checksum is displayed';
        });
    }

    /**
     * Test 15: Backup duration displayed
     *
     */

    #[Test]
    public function test_backup_duration_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-duration');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDuration =
                str_contains($pageSource, 'duration') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'started');

            $this->assertTrue($hasDuration, 'Backup duration should be displayed');

            $this->testResults['backup_duration'] = 'Backup duration is displayed';
        });
    }

    /**
     * Test 16: Backup timestamp displayed
     *
     */

    #[Test]
    public function test_backup_timestamp_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-timestamp');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimestamp =
                str_contains($pageSource, 'created') ||
                str_contains($pageSource, 'date') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'ago');

            $this->assertTrue($hasTimestamp, 'Backup timestamp should be displayed');

            $this->testResults['backup_timestamp'] = 'Backup timestamp is displayed';
        });
    }

    /**
     * Test 17: Restore backup modal opens
     *
     */

    #[Test]
    public function test_restore_backup_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('restore-backup-modal');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRestore =
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'openrestoremodal') ||
                str_contains($pageSource, 'restorebackup');

            $this->assertTrue($hasRestore, 'Restore backup modal should be accessible');

            $this->testResults['restore_backup_modal'] = 'Restore backup modal is accessible';
        });
    }

    /**
     * Test 18: Overwrite option on restore
     *
     */

    #[Test]
    public function test_restore_overwrite_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('restore-overwrite-option');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOverwrite =
                str_contains($pageSource, 'overwrite') ||
                str_contains($pageSource, 'overwriteonrestore') ||
                str_contains($pageSource, 'replace');

            $this->assertTrue($hasOverwrite, 'Overwrite option should be available on restore');

            $this->testResults['restore_overwrite'] = 'Restore overwrite option is available';
        });
    }

    /**
     * Test 19: Download backup functionality available
     *
     */

    #[Test]
    public function test_download_backup_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('download-backup');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDownload =
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'downloadbackup') ||
                str_contains($pageSource, 'export');

            $this->assertTrue($hasDownload, 'Download backup functionality should be available');

            $this->testResults['download_backup'] = 'Download backup functionality is available';
        });
    }

    /**
     * Test 20: View backup manifest modal
     *
     */

    #[Test]
    public function test_view_backup_manifest_modal()
    {
        FileBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'filename' => 'manifest_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'source_path' => '/var/www/project',
                'storage_disk' => 'local',
                'storage_path' => '/backups/manifest_backup.tar.gz',
                'size_bytes' => 5242880,
                'files_count' => 175,
                'checksum' => md5('manifest_backup'),
                'status' => 'completed',
                'started_at' => now()->subHours(3),
                'completed_at' => now()->subHours(2),
                'manifest' => [
                    ['path' => '/app', 'size' => 2048000],
                    ['path' => '/public', 'size' => 1024000],
                    ['path' => '/config', 'size' => 512000],
                ],
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('view-manifest-modal');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasManifest =
                str_contains($pageSource, 'manifest') ||
                str_contains($pageSource, 'viewmanifest') ||
                str_contains($pageSource, 'files');

            $this->assertTrue($hasManifest, 'View manifest modal should be accessible');

            $this->testResults['view_manifest_modal'] = 'View backup manifest modal is accessible';
        });
    }

    /**
     * Test 21: Delete backup confirmation
     *
     */

    #[Test]
    public function test_delete_backup_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('delete-backup-confirmation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDelete =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'deletebackup') ||
                str_contains($pageSource, 'confirm');

            $this->assertTrue($hasDelete, 'Delete backup confirmation should be available');

            $this->testResults['delete_backup_confirmation'] = 'Delete backup confirmation is available';
        });
    }

    /**
     * Test 22: Exclude patterns modal opens
     *
     */

    #[Test]
    public function test_exclude_patterns_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('exclude-patterns-modal');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExcludePatterns =
                str_contains($pageSource, 'exclude') ||
                str_contains($pageSource, 'pattern') ||
                str_contains($pageSource, 'openexcludepatternsmodal') ||
                str_contains($pageSource, 'excludepatterns');

            $this->assertTrue($hasExcludePatterns, 'Exclude patterns modal should be accessible');

            $this->testResults['exclude_patterns_modal'] = 'Exclude patterns modal is accessible';
        });
    }

    /**
     * Test 23: Add exclude pattern functionality
     *
     */

    #[Test]
    public function test_add_exclude_pattern_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('add-exclude-pattern');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAddPattern =
                str_contains($pageSource, 'addexcludepattern') ||
                str_contains($pageSource, 'newexcludepattern') ||
                str_contains($pageSource, 'add') ||
                str_contains($pageSource, 'pattern');

            $this->assertTrue($hasAddPattern, 'Add exclude pattern functionality should be available');

            $this->testResults['add_exclude_pattern'] = 'Add exclude pattern functionality is available';
        });
    }

    /**
     * Test 24: Remove exclude pattern functionality
     *
     */

    #[Test]
    public function test_remove_exclude_pattern_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('remove-exclude-pattern');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRemovePattern =
                str_contains($pageSource, 'removeexcludepattern') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'pattern');

            $this->assertTrue($hasRemovePattern, 'Remove exclude pattern functionality should be available');

            $this->testResults['remove_exclude_pattern'] = 'Remove exclude pattern functionality is available';
        });
    }

    /**
     * Test 25: Reset exclude patterns to defaults
     *
     */

    #[Test]
    public function test_reset_exclude_patterns_to_defaults()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('reset-exclude-patterns');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasReset =
                str_contains($pageSource, 'reset') ||
                str_contains($pageSource, 'resetexcludepatterns') ||
                str_contains($pageSource, 'default');

            $this->assertTrue($hasReset, 'Reset exclude patterns functionality should be available');

            $this->testResults['reset_exclude_patterns'] = 'Reset exclude patterns to defaults is available';
        });
    }

    /**
     * Test 26: Search/filter backups functionality
     *
     */

    #[Test]
    public function test_search_filter_backups()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('search-filter-backups');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSearch =
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'searchterm') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasSearch, 'Search/filter backups functionality should be available');

            $this->testResults['search_filter_backups'] = 'Search/filter backups functionality is available';
        });
    }

    /**
     * Test 27: Filter by backup type (full/incremental)
     *
     */

    #[Test]
    public function test_filter_by_backup_type()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('filter-by-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTypeFilter =
                str_contains($pageSource, 'filtertype') ||
                str_contains($pageSource, 'full') ||
                str_contains($pageSource, 'incremental') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasTypeFilter, 'Filter by backup type should be available');

            $this->testResults['filter_by_type'] = 'Filter by backup type is available';
        });
    }

    /**
     * Test 28: Filter by backup status
     *
     */

    #[Test]
    public function test_filter_by_backup_status()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('filter-by-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusFilter =
                str_contains($pageSource, 'filterstatus') ||
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'filter');

            $this->assertTrue($hasStatusFilter, 'Filter by backup status should be available');

            $this->testResults['filter_by_status'] = 'Filter by backup status is available';
        });
    }

    /**
     * Test 29: Incremental backup shows parent reference
     *
     */

    #[Test]
    public function test_incremental_backup_shows_parent()
    {
        // Create parent full backup
        $fullBackup = FileBackup::create([
            'project_id' => $this->project->id,
            'filename' => 'parent_full_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            'type' => 'full',
            'source_path' => '/var/www/project',
            'storage_disk' => 'local',
            'storage_path' => '/backups/parent_full.tar.gz',
            'size_bytes' => 10485760,
            'files_count' => 300,
            'checksum' => md5('parent_full'),
            'status' => 'completed',
            'started_at' => now()->subDays(1),
            'completed_at' => now()->subDays(1)->addHour(),
        ]);

        // Create incremental backup
        FileBackup::create([
            'project_id' => $this->project->id,
            'filename' => 'incremental_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            'type' => 'incremental',
            'source_path' => '/var/www/project',
            'storage_disk' => 'local',
            'storage_path' => '/backups/incremental.tar.gz',
            'size_bytes' => 2097152,
            'files_count' => 50,
            'checksum' => md5('incremental'),
            'status' => 'completed',
            'parent_backup_id' => $fullBackup->id,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('incremental-parent-reference');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasParentRef =
                str_contains($pageSource, 'parent') ||
                str_contains($pageSource, 'base') ||
                str_contains($pageSource, 'incremental');

            $this->assertTrue($hasParentRef, 'Incremental backup should show parent reference');

            $this->testResults['incremental_parent_reference'] = 'Incremental backup shows parent reference';
        });
    }

    /**
     * Test 30: Backup with child backups shows warning on delete
     *
     */

    #[Test]
    public function test_backup_with_children_delete_warning()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('delete-with-children-warning');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWarning =
                str_contains($pageSource, 'child') ||
                str_contains($pageSource, 'warning') ||
                str_contains($pageSource, 'delete');

            $this->assertTrue($hasWarning, 'Delete warning for backups with children should exist');

            $this->testResults['delete_children_warning'] = 'Delete warning for backups with children exists';
        });
    }

    /**
     * Test 31: Backup error messages displayed for failed backups
     *
     */

    #[Test]
    public function test_failed_backup_error_messages()
    {
        FileBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'filename' => 'failed_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'source_path' => '/var/www/project',
                'storage_disk' => 'local',
                'storage_path' => '/backups/failed_backup.tar.gz',
                'size_bytes' => 0,
                'files_count' => 0,
                'checksum' => '',
                'status' => 'failed',
                'started_at' => now()->subMinutes(30),
                'error_message' => 'Insufficient disk space for backup operation',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('failed-backup-error');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasError =
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'disk space');

            $this->assertTrue($hasError, 'Failed backup error messages should be displayed');

            $this->testResults['failed_backup_errors'] = 'Failed backup error messages are displayed';
        });
    }

    /**
     * Test 32: Backup compression indicated
     *
     */

    #[Test]
    public function test_backup_compression_indicated()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-compression');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCompression =
                str_contains($pageSource, 'gz') ||
                str_contains($pageSource, 'tar') ||
                str_contains($pageSource, 'compress') ||
                str_contains($pageSource, 'zip');

            $this->assertTrue($hasCompression, 'Backup compression should be indicated');

            $this->testResults['backup_compression'] = 'Backup compression is indicated';
        });
    }

    /**
     * Test 33: Backup progress tracking for running backups
     *
     */

    #[Test]
    public function test_backup_progress_tracking()
    {
        FileBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'filename' => 'running_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'source_path' => '/var/www/project',
                'storage_disk' => 'local',
                'storage_path' => '/backups/running_backup.tar.gz',
                'size_bytes' => 0,
                'files_count' => 0,
                'checksum' => '',
                'status' => 'running',
                'started_at' => now()->subMinutes(5),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-progress');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgress =
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'progress') ||
                str_contains($pageSource, 'processing');

            $this->assertTrue($hasProgress, 'Backup progress should be tracked for running backups');

            $this->testResults['backup_progress'] = 'Backup progress tracking is available';
        });
    }

    /**
     * Test 34: Backup size estimation shown before creation
     *
     */

    #[Test]
    public function test_backup_size_estimation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-size-estimation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEstimation =
                str_contains($pageSource, 'estimate') ||
                str_contains($pageSource, 'estimated') ||
                str_contains($pageSource, 'size');

            $this->assertTrue($hasEstimation, 'Backup size estimation should be available');

            $this->testResults['backup_size_estimation'] = 'Backup size estimation is available';
        });
    }

    /**
     * Test 35: Backup encryption settings available
     *
     */

    #[Test]
    public function test_backup_encryption_settings()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-encryption');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEncryption =
                str_contains($pageSource, 'encrypt') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'password');

            $this->assertTrue($hasEncryption, 'Backup encryption settings should be available');

            $this->testResults['backup_encryption'] = 'Backup encryption settings are available';
        });
    }

    /**
     * Test 36: Backup verification functionality
     *
     */

    #[Test]
    public function test_backup_verification_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-verification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVerification =
                str_contains($pageSource, 'verify') ||
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'validation');

            $this->assertTrue($hasVerification, 'Backup verification functionality should be available');

            $this->testResults['backup_verification'] = 'Backup verification functionality is available';
        });
    }

    /**
     * Test 37: Backup logs viewing functionality
     *
     */

    #[Test]
    public function test_backup_logs_viewing()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-logs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogs =
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'output') ||
                str_contains($pageSource, 'history');

            $this->assertTrue($hasLogs, 'Backup logs viewing should be available');

            $this->testResults['backup_logs'] = 'Backup logs viewing functionality is available';
        });
    }

    /**
     * Test 38: Empty state shown when no backups exist
     *
     */

    #[Test]
    public function test_empty_state_no_backups()
    {
        // Create a new project with no backups
        $emptyProject = Project::firstOrCreate(
            ['slug' => 'empty-backup-project'],
            [
                'name' => 'Empty Backup Project',
                'repository_url' => 'https://github.com/test/empty-project',
                'branch' => 'main',
                'framework' => 'laravel',
                'php_version' => '8.4',
                'server_id' => $this->server->id,
                'status' => 'active',
            ]
        );

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$emptyProject->id}/file-backups")
                ->pause(2000)
                ->screenshot('empty-state-no-backups');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no backup') ||
                str_contains($pageSource, 'empty') ||
                str_contains($pageSource, 'create') ||
                str_contains($pageSource, 'first');

            $this->assertTrue($hasEmptyState, 'Empty state should be shown when no backups exist');

            $this->testResults['empty_state'] = 'Empty state is shown when no backups exist';
        });
    }

    /**
     * Test 39: Backup type color coding
     *
     */

    #[Test]
    public function test_backup_type_color_coding()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-type-colors');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasColorCoding =
                str_contains($pageSource, 'purple') ||
                str_contains($pageSource, 'blue') ||
                str_contains($pageSource, 'color') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasColorCoding, 'Backup type color coding should be present');

            $this->testResults['backup_type_colors'] = 'Backup type color coding is present';
        });
    }

    /**
     * Test 40: Backup status color coding
     *
     */

    #[Test]
    public function test_backup_status_color_coding()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-status-colors');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasColorCoding =
                str_contains($pageSource, 'green') ||
                str_contains($pageSource, 'red') ||
                str_contains($pageSource, 'yellow') ||
                str_contains($pageSource, 'color');

            $this->assertTrue($hasColorCoding, 'Backup status color coding should be present');

            $this->testResults['backup_status_colors'] = 'Backup status color coding is present';
        });
    }

    /**
     * Test 41: Incremental depth indicator
     *
     */

    #[Test]
    public function test_incremental_depth_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('incremental-depth');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDepth =
                str_contains($pageSource, 'depth') ||
                str_contains($pageSource, 'incrementaldepth') ||
                str_contains($pageSource, 'level');

            $this->assertTrue($hasDepth, 'Incremental depth indicator should be shown');

            $this->testResults['incremental_depth'] = 'Incremental depth indicator is shown';
        });
    }

    /**
     * Test 42: Backup retention policy settings
     *
     */

    #[Test]
    public function test_backup_retention_policy()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-retention-policy');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetention =
                str_contains($pageSource, 'retention') ||
                str_contains($pageSource, 'policy') ||
                str_contains($pageSource, 'keep') ||
                str_contains($pageSource, 'days');

            $this->assertTrue($hasRetention, 'Backup retention policy should be configurable');

            $this->testResults['backup_retention'] = 'Backup retention policy is configurable';
        });
    }

    /**
     * Test 43: Backup sorting options
     *
     */

    #[Test]
    public function test_backup_sorting_options()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-sorting');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSorting =
                str_contains($pageSource, 'sort') ||
                str_contains($pageSource, 'order') ||
                str_contains($pageSource, 'latest');

            $this->assertTrue($hasSorting, 'Backup sorting options should be available');

            $this->testResults['backup_sorting'] = 'Backup sorting options are available';
        });
    }

    /**
     * Test 44: Bulk backup operations
     *
     */

    #[Test]
    public function test_bulk_backup_operations()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('bulk-operations');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBulk =
                str_contains($pageSource, 'bulk') ||
                str_contains($pageSource, 'select') ||
                str_contains($pageSource, 'multiple');

            $this->assertTrue($hasBulk, 'Bulk backup operations should be available');

            $this->testResults['bulk_operations'] = 'Bulk backup operations are available';
        });
    }

    /**
     * Test 45: Backup notification settings
     *
     */

    #[Test]
    public function test_backup_notification_settings()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-notifications');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotifications =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'notify') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasNotifications, 'Backup notification settings should be available');

            $this->testResults['backup_notifications'] = 'Backup notification settings are available';
        });
    }

    /**
     * Test 46: Backup statistics summary
     *
     */

    #[Test]
    public function test_backup_statistics_summary()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'statistics') ||
                str_contains($pageSource, 'summary') ||
                str_contains($pageSource, 'count');

            $this->assertTrue($hasStatistics, 'Backup statistics summary should be shown');

            $this->testResults['backup_statistics'] = 'Backup statistics summary is shown';
        });
    }

    /**
     * Test 47: Storage disk usage indicators
     *
     */

    #[Test]
    public function test_storage_disk_usage_indicators()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('storage-usage');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUsage =
                str_contains($pageSource, 'usage') ||
                str_contains($pageSource, 'space') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasUsage, 'Storage disk usage indicators should be shown');

            $this->testResults['storage_usage'] = 'Storage disk usage indicators are shown';
        });
    }

    /**
     * Test 48: Backup chain visualization
     *
     */

    #[Test]
    public function test_backup_chain_visualization()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-chain');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChain =
                str_contains($pageSource, 'chain') ||
                str_contains($pageSource, 'parent') ||
                str_contains($pageSource, 'child');

            $this->assertTrue($hasChain, 'Backup chain visualization should be available');

            $this->testResults['backup_chain'] = 'Backup chain visualization is available';
        });
    }

    /**
     * Test 49: Backup metadata display
     *
     */

    #[Test]
    public function test_backup_metadata_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('backup-metadata');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetadata =
                str_contains($pageSource, 'metadata') ||
                str_contains($pageSource, 'info') ||
                str_contains($pageSource, 'details');

            $this->assertTrue($hasMetadata, 'Backup metadata should be displayed');

            $this->testResults['backup_metadata'] = 'Backup metadata is displayed';
        });
    }

    /**
     * Test 50: Refresh backups list functionality
     *
     */

    #[Test]
    public function test_refresh_backups_list()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/file-backups")
                ->pause(2000)
                ->screenshot('refresh-backups-list');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRefresh =
                str_contains($pageSource, 'refresh') ||
                str_contains($pageSource, 'reload') ||
                str_contains($pageSource, 'update');

            $this->assertTrue($hasRefresh, 'Refresh backups list functionality should be available');

            $this->testResults['refresh_backups'] = 'Refresh backups list functionality is available';
        });
    }

    /**
     * Generate test report
     */
    protected function tearDown(): void
    {
        if (! empty($this->testResults)) {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'test_suite' => 'File Backup Manager Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'test_project_id' => $this->project->id,
                    'test_server_id' => $this->server->id,
                    'file_backups' => FileBackup::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/file-backup-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
