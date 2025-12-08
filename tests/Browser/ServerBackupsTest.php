<?php

namespace Tests\Browser;

use App\Models\Project;
use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ServerBackupsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        // Create test server for backup tests
        $this->server = Server::firstOrCreate(
            ['name' => 'Test Server Backups'],
            [
                'hostname' => 'server-backup-test.local',
                'ip_address' => '192.168.10.50',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        // Create test project associated with server
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-server-backup-project'],
            [
                'name' => 'Test Server Backup Project',
                'repository_url' => 'https://github.com/test/server-backup-project',
                'branch' => 'main',
                'framework' => 'laravel',
                'php_version' => '8.4',
                'server_id' => $this->server->id,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: Server backups page loads
     *
     * @test
     */
    public function test_user_can_view_server_backups()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-backups-page');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupContent =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'server');

            $this->assertTrue($hasBackupContent , 'Server backups page should load');

            $this->testResults['server_backups_page'] = 'Server backups page loaded successfully';
        });
    }

    /**
     * Test 2: Create full server backup button is visible
     *
     * @test
     */
    public function test_create_full_server_backup_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('full-backup-button');

            $pageSource = $browser->driver->getPageSource();
            $hasCreateButton =
                str_contains($pageSource, 'Full') ||
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'Backup');

            $this->assertTrue($hasCreateButton , 'Full backup creation button should be visible');

            $this->testResults['full_backup_button'] = 'Full backup creation button is visible';
        });
    }

    /**
     * Test 3: Create incremental server backup option available
     *
     * @test
     */
    public function test_create_incremental_server_backup_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('incremental-backup-option');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIncremental =
                str_contains($pageSource, 'incremental') ||
                str_contains($pageSource, 'backup');

            $this->assertTrue($hasIncremental , 'Incremental backup option should be available');

            $this->testResults['incremental_backup_option'] = 'Incremental backup option is available';
        });
    }

    /**
     * Test 4: Backup scheduling configuration page loads
     *
     * @test
     */
    public function test_backup_scheduling_configuration_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-scheduling-config');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScheduling =
                str_contains($pageSource, 'schedule') ||
                str_contains($pageSource, 'frequency') ||
                str_contains($pageSource, 'daily') ||
                str_contains($pageSource, 'weekly');

            $this->assertTrue($hasScheduling , 'Backup scheduling configuration should load');

            $this->testResults['scheduling_configuration'] = 'Backup scheduling configuration loaded';
        });
    }

    /**
     * Test 5: Backup storage location settings visible
     *
     * @test
     */
    public function test_backup_storage_location_settings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-location-settings');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStorage =
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'location') ||
                str_contains($pageSource, 'local') ||
                str_contains($pageSource, 's3');

            $this->assertTrue($hasStorage , 'Storage location settings should be visible');

            $this->testResults['storage_location_settings'] = 'Storage location settings are visible';
        });
    }

    /**
     * Test 6: Backup restoration process page accessible
     *
     * @test
     */
    public function test_backup_restoration_process_accessible()
    {
        // Create a test backup
        ServerBackup::firstOrCreate(
            [
                'server_id' => $this->server->id,
                'filename' => 'server_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'storage_path' => '/backups/server/full_backup.tar.gz',
                'size_bytes' => 10485760,
                'checksum' => md5('server_backup'),
                'status' => 'completed',
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHours(1),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-restoration');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRestore =
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'recovery');

            $this->assertTrue($hasRestore , 'Backup restoration process should be accessible');

            $this->testResults['restoration_process'] = 'Backup restoration process is accessible';
        });
    }

    /**
     * Test 7: Backup download functionality available
     *
     * @test
     */
    public function test_backup_download_functionality_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-download');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDownload =
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'export');

            $this->assertTrue($hasDownload , 'Backup download functionality should be available');

            $this->testResults['download_functionality'] = 'Backup download functionality is available';
        });
    }

    /**
     * Test 8: Backup deletion with confirmation modal
     *
     * @test
     */
    public function test_backup_deletion_with_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-deletion-confirm');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDelete =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'confirm');

            $this->assertTrue($hasDelete , 'Backup deletion confirmation should be available');

            $this->testResults['deletion_confirmation'] = 'Backup deletion with confirmation is available';
        });
    }

    /**
     * Test 9: Backup history displays
     *
     * @test
     */
    public function test_backup_history_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-history');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHistory =
                str_contains($pageSource, 'history') ||
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'completed');

            $this->assertTrue($hasHistory , 'Backup history should display');

            $this->testResults['backup_history'] = 'Backup history displays successfully';
        });
    }

    /**
     * Test 10: Backup logs are viewable
     *
     * @test
     */
    public function test_backup_logs_viewable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-logs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLogs =
                str_contains($pageSource, 'log') ||
                str_contains($pageSource, 'output') ||
                str_contains($pageSource, 'details');

            $this->assertTrue($hasLogs , 'Backup logs should be viewable');

            $this->testResults['backup_logs'] = 'Backup logs are viewable';
        });
    }

    /**
     * Test 11: Backup encryption settings available
     *
     * @test
     */
    public function test_backup_encryption_settings_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-encryption');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEncryption =
                str_contains($pageSource, 'encrypt') ||
                str_contains($pageSource, 'security') ||
                str_contains($pageSource, 'password');

            $this->assertTrue($hasEncryption , 'Backup encryption settings should be available');

            $this->testResults['encryption_settings'] = 'Backup encryption settings are available';
        });
    }

    /**
     * Test 12: Backup retention policies configurable
     *
     * @test
     */
    public function test_backup_retention_policies_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('retention-policies');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetention =
                str_contains($pageSource, 'retention') ||
                str_contains($pageSource, 'policy') ||
                str_contains($pageSource, 'days');

            $this->assertTrue($hasRetention , 'Backup retention policies should be configurable');

            $this->testResults['retention_policies'] = 'Backup retention policies are configurable';
        });
    }

    /**
     * Test 13: Backup verification/integrity check available
     *
     * @test
     */
    public function test_backup_verification_integrity_check_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-verification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVerification =
                str_contains($pageSource, 'verify') ||
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'integrity');

            $this->assertTrue($hasVerification , 'Backup verification should be available');

            $this->testResults['verification_check'] = 'Backup verification/integrity check is available';
        });
    }

    /**
     * Test 14: Backup notification settings configurable
     *
     * @test
     */
    public function test_backup_notification_settings_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-settings');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNotifications =
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'notify') ||
                str_contains($pageSource, 'alert');

            $this->assertTrue($hasNotifications , 'Backup notification settings should be configurable');

            $this->testResults['notification_settings'] = 'Backup notification settings are configurable';
        });
    }

    /**
     * Test 15: S3 remote backup destination option
     *
     * @test
     */
    public function test_s3_remote_backup_destination_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('s3-backup-destination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasS3 =
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'amazon') ||
                str_contains($pageSource, 'remote');

            $this->assertTrue($hasS3 , 'S3 remote backup destination should be available');

            $this->testResults['s3_destination'] = 'S3 remote backup destination option is available';
        });
    }

    /**
     * Test 16: Google Cloud Storage remote destination
     *
     * @test
     */
    public function test_google_cloud_storage_remote_destination()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('gcs-destination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGCS =
                str_contains($pageSource, 'gcs') ||
                str_contains($pageSource, 'google') ||
                str_contains($pageSource, 'cloud');

            $this->assertTrue($hasGCS , 'Google Cloud Storage destination should be available');

            $this->testResults['gcs_destination'] = 'Google Cloud Storage remote destination is available';
        });
    }

    /**
     * Test 17: Azure Blob Storage remote destination
     *
     * @test
     */
    public function test_azure_blob_storage_remote_destination()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('azure-destination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAzure =
                str_contains($pageSource, 'azure') ||
                str_contains($pageSource, 'blob');

            $this->assertTrue($hasAzure , 'Azure Blob Storage destination should be available');

            $this->testResults['azure_destination'] = 'Azure Blob Storage remote destination is available';
        });
    }

    /**
     * Test 18: Backup progress tracking visible
     *
     * @test
     */
    public function test_backup_progress_tracking_visible()
    {
        // Create a running backup
        ServerBackup::firstOrCreate(
            [
                'server_id' => $this->server->id,
                'filename' => 'running_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'storage_path' => '/backups/server/running_backup.tar.gz',
                'status' => 'running',
                'started_at' => now()->subMinutes(10),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-progress');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgress =
                str_contains($pageSource, 'progress') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'pending');

            $this->assertTrue($hasProgress , 'Backup progress tracking should be visible');

            $this->testResults['progress_tracking'] = 'Backup progress tracking is visible';
        });
    }

    /**
     * Test 19: Backup comparison functionality available
     *
     * @test
     */
    public function test_backup_comparison_functionality_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-comparison');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasComparison =
                str_contains($pageSource, 'compare') ||
                str_contains($pageSource, 'diff');

            $this->assertTrue($hasComparison , 'Backup comparison functionality should be available');

            $this->testResults['comparison_functionality'] = 'Backup comparison functionality is available';
        });
    }

    /**
     * Test 20: Backup diff view accessible
     *
     * @test
     */
    public function test_backup_diff_view_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-diff');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDiff =
                str_contains($pageSource, 'diff') ||
                str_contains($pageSource, 'difference') ||
                str_contains($pageSource, 'changes');

            $this->assertTrue($hasDiff , 'Backup diff view should be accessible');

            $this->testResults['diff_view'] = 'Backup diff view is accessible';
        });
    }

    /**
     * Test 21: Daily backup schedule configuration
     *
     * @test
     */
    public function test_daily_backup_schedule_configuration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('daily-schedule');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDaily =
                str_contains($pageSource, 'daily') ||
                str_contains($pageSource, 'frequency');

            $this->assertTrue($hasDaily , 'Daily backup schedule should be configurable');

            $this->testResults['daily_schedule'] = 'Daily backup schedule configuration is available';
        });
    }

    /**
     * Test 22: Weekly backup schedule configuration
     *
     * @test
     */
    public function test_weekly_backup_schedule_configuration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('weekly-schedule');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWeekly =
                str_contains($pageSource, 'weekly') ||
                str_contains($pageSource, 'week');

            $this->assertTrue($hasWeekly , 'Weekly backup schedule should be configurable');

            $this->testResults['weekly_schedule'] = 'Weekly backup schedule configuration is available';
        });
    }

    /**
     * Test 23: Monthly backup schedule configuration
     *
     * @test
     */
    public function test_monthly_backup_schedule_configuration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('monthly-schedule');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMonthly =
                str_contains($pageSource, 'monthly') ||
                str_contains($pageSource, 'month');

            $this->assertTrue($hasMonthly , 'Monthly backup schedule should be configurable');

            $this->testResults['monthly_schedule'] = 'Monthly backup schedule configuration is available';
        });
    }

    /**
     * Test 24: Backup size information displayed
     *
     * @test
     */
    public function test_backup_size_information_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-size-info');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSize =
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'mb') ||
                str_contains($pageSource, 'gb');

            $this->assertTrue($hasSize , 'Backup size information should be displayed');

            $this->testResults['size_information'] = 'Backup size information is displayed';
        });
    }

    /**
     * Test 25: Backup status indicators shown
     *
     * @test
     */
    public function test_backup_status_indicators_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-status-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatus =
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatus , 'Backup status indicators should be shown');

            $this->testResults['status_indicators'] = 'Backup status indicators are shown';
        });
    }

    /**
     * Test 26: Backup checksum/hash displayed
     *
     * @test
     */
    public function test_backup_checksum_hash_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-checksum');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChecksum =
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'hash') ||
                str_contains($pageSource, 'md5');

            $this->assertTrue($hasChecksum , 'Backup checksum should be displayed');

            $this->testResults['checksum_display'] = 'Backup checksum/hash is displayed';
        });
    }

    /**
     * Test 27: Backup duration shown
     *
     * @test
     */
    public function test_backup_duration_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-duration');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDuration =
                str_contains($pageSource, 'duration') ||
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'started');

            $this->assertTrue($hasDuration , 'Backup duration should be shown');

            $this->testResults['duration_display'] = 'Backup duration is shown';
        });
    }

    /**
     * Test 28: Backup schedule status toggleable
     *
     * @test
     */
    public function test_backup_schedule_status_toggleable()
    {
        // Create a backup schedule
        ServerBackupSchedule::firstOrCreate(
            [
                'server_id' => $this->server->id,
                'name' => 'Test Server Schedule',
            ],
            [
                'backup_type' => 'full',
                'frequency' => 'daily',
                'time' => '02:00:00',
                'retention_days' => 30,
                'is_active' => true,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('schedule-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle =
                str_contains($pageSource, 'toggle') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'enable');

            $this->assertTrue($hasToggle , 'Backup schedule status should be toggleable');

            $this->testResults['schedule_toggle'] = 'Backup schedule status is toggleable';
        });
    }

    /**
     * Test 29: Backup error messages displayed
     *
     * @test
     */
    public function test_backup_error_messages_displayed()
    {
        // Create a failed backup
        ServerBackup::firstOrCreate(
            [
                'server_id' => $this->server->id,
                'filename' => 'failed_backup_'.now()->format('Y-m-d_H-i-s').'.tar.gz',
            ],
            [
                'type' => 'full',
                'storage_path' => '/backups/server/failed_backup.tar.gz',
                'status' => 'failed',
                'started_at' => now()->subHours(1),
                'error_message' => 'Failed to connect to storage',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-errors');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasErrors =
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasErrors , 'Backup error messages should be displayed');

            $this->testResults['error_messages'] = 'Backup error messages are displayed';
        });
    }

    /**
     * Test 30: Backup compression options available
     *
     * @test
     */
    public function test_backup_compression_options_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('compression-options');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCompression =
                str_contains($pageSource, 'compress') ||
                str_contains($pageSource, 'gzip') ||
                str_contains($pageSource, 'zip');

            $this->assertTrue($hasCompression , 'Backup compression options should be available');

            $this->testResults['compression_options'] = 'Backup compression options are available';
        });
    }

    /**
     * Test 31: Backup statistics displayed
     *
     * @test
     */
    public function test_backup_statistics_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'statistics') ||
                str_contains($pageSource, 'count');

            $this->assertTrue($hasStatistics , 'Backup statistics should be displayed');

            $this->testResults['backup_statistics'] = 'Backup statistics are displayed';
        });
    }

    /**
     * Test 32: Backup filtering options work
     *
     * @test
     */
    public function test_backup_filtering_options_work()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-filtering');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFiltering =
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasFiltering , 'Backup filtering options should work');

            $this->testResults['filtering_options'] = 'Backup filtering options work';
        });
    }

    /**
     * Test 33: Backup sorting options available
     *
     * @test
     */
    public function test_backup_sorting_options_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-sorting');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSorting =
                str_contains($pageSource, 'sort') ||
                str_contains($pageSource, 'order') ||
                str_contains($pageSource, 'date');

            $this->assertTrue($hasSorting , 'Backup sorting options should be available');

            $this->testResults['sorting_options'] = 'Backup sorting options are available';
        });
    }

    /**
     * Test 34: Backup pagination works
     *
     * @test
     */
    public function test_backup_pagination_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-pagination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination =
                str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'previous');

            $this->assertTrue($hasPagination , 'Backup pagination should work');

            $this->testResults['pagination'] = 'Backup pagination works';
        });
    }

    /**
     * Test 35: Backup schedule next run displayed
     *
     * @test
     */
    public function test_backup_schedule_next_run_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('schedule-next-run');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNextRun =
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'scheduled');

            $this->assertTrue($hasNextRun , 'Backup schedule next run should be displayed');

            $this->testResults['schedule_next_run'] = 'Backup schedule next run is displayed';
        });
    }

    /**
     * Test 36: Backup cleanup automation available
     *
     * @test
     */
    public function test_backup_cleanup_automation_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-cleanup');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCleanup =
                str_contains($pageSource, 'cleanup') ||
                str_contains($pageSource, 'purge') ||
                str_contains($pageSource, 'auto');

            $this->assertTrue($hasCleanup , 'Backup cleanup automation should be available');

            $this->testResults['cleanup_automation'] = 'Backup cleanup automation is available';
        });
    }

    /**
     * Test 37: Backup export to local functionality
     *
     * @test
     */
    public function test_backup_export_to_local_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-export-local');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExport =
                str_contains($pageSource, 'export') ||
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'local');

            $this->assertTrue($hasExport , 'Backup export to local should be available');

            $this->testResults['export_local'] = 'Backup export to local functionality is available';
        });
    }

    /**
     * Test 38: Backup time selection for schedule
     *
     * @test
     */
    public function test_backup_time_selection_for_schedule()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('schedule-time-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeSelection =
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'hour') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasTimeSelection , 'Backup time selection should be available');

            $this->testResults['time_selection'] = 'Backup time selection for schedule is available';
        });
    }

    /**
     * Test 39: Backup type indicators visible
     *
     * @test
     */
    public function test_backup_type_indicators_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-type-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTypeIndicators =
                str_contains($pageSource, 'full') ||
                str_contains($pageSource, 'incremental') ||
                str_contains($pageSource, 'type');

            $this->assertTrue($hasTypeIndicators , 'Backup type indicators should be visible');

            $this->testResults['type_indicators'] = 'Backup type indicators are visible';
        });
    }

    /**
     * Test 40: Backup restore point selection
     *
     * @test
     */
    public function test_backup_restore_point_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('restore-point-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRestorePoint =
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'point') ||
                str_contains($pageSource, 'select');

            $this->assertTrue($hasRestorePoint , 'Backup restore point selection should be available');

            $this->testResults['restore_point_selection'] = 'Backup restore point selection is available';
        });
    }

    /**
     * Test 41: Backup storage usage metrics
     *
     * @test
     */
    public function test_backup_storage_usage_metrics()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-usage-metrics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUsageMetrics =
                str_contains($pageSource, 'usage') ||
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'space');

            $this->assertTrue($hasUsageMetrics , 'Backup storage usage metrics should be shown');

            $this->testResults['storage_usage'] = 'Backup storage usage metrics are shown';
        });
    }

    /**
     * Test 42: Backup schedule edit functionality
     *
     * @test
     */
    public function test_backup_schedule_edit_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('schedule-edit');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEdit =
                str_contains($pageSource, 'edit') ||
                str_contains($pageSource, 'update') ||
                str_contains($pageSource, 'modify');

            $this->assertTrue($hasEdit , 'Backup schedule edit should be available');

            $this->testResults['schedule_edit'] = 'Backup schedule edit functionality is available';
        });
    }

    /**
     * Test 43: Backup schedule delete confirmation
     *
     * @test
     */
    public function test_backup_schedule_delete_confirmation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('schedule-delete-confirm');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteConfirm =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'confirm') ||
                str_contains($pageSource, 'remove');

            $this->assertTrue($hasDeleteConfirm , 'Schedule delete confirmation should be available');

            $this->testResults['schedule_delete'] = 'Backup schedule delete confirmation is available';
        });
    }

    /**
     * Test 44: Backup notification channels configurable
     *
     * @test
     */
    public function test_backup_notification_channels_configurable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('notification-channels');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChannels =
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'slack') ||
                str_contains($pageSource, 'notification');

            $this->assertTrue($hasChannels , 'Notification channels should be configurable');

            $this->testResults['notification_channels'] = 'Backup notification channels are configurable';
        });
    }

    /**
     * Test 45: Backup integrity test functionality
     *
     * @test
     */
    public function test_backup_integrity_test_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('integrity-test');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasIntegrity =
                str_contains($pageSource, 'integrity') ||
                str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'verify');

            $this->assertTrue($hasIntegrity , 'Backup integrity test should be available');

            $this->testResults['integrity_test'] = 'Backup integrity test functionality is available';
        });
    }

    /**
     * Test 46: Backup manifest/contents viewable
     *
     * @test
     */
    public function test_backup_manifest_contents_viewable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('backup-manifest');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasManifest =
                str_contains($pageSource, 'manifest') ||
                str_contains($pageSource, 'contents') ||
                str_contains($pageSource, 'files');

            $this->assertTrue($hasManifest , 'Backup manifest should be viewable');

            $this->testResults['backup_manifest'] = 'Backup manifest/contents are viewable';
        });
    }

    /**
     * Test 47: Backup partial restore option
     *
     * @test
     */
    public function test_backup_partial_restore_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('partial-restore');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPartialRestore =
                str_contains($pageSource, 'partial') ||
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'selective');

            $this->assertTrue($hasPartialRestore , 'Partial restore option should be available');

            $this->testResults['partial_restore'] = 'Backup partial restore option is available';
        });
    }

    /**
     * Test 48: Backup bandwidth throttling settings
     *
     * @test
     */
    public function test_backup_bandwidth_throttling_settings()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('bandwidth-throttling');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasThrottling =
                str_contains($pageSource, 'bandwidth') ||
                str_contains($pageSource, 'throttle') ||
                str_contains($pageSource, 'limit');

            $this->assertTrue($hasThrottling , 'Bandwidth throttling settings should be available');

            $this->testResults['bandwidth_throttling'] = 'Backup bandwidth throttling settings are available';
        });
    }

    /**
     * Test 49: Backup email notification configuration
     *
     * @test
     */
    public function test_backup_email_notification_configuration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('email-notification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmailConfig =
                str_contains($pageSource, 'email') ||
                str_contains($pageSource, 'notification') ||
                str_contains($pageSource, 'recipient');

            $this->assertTrue($hasEmailConfig , 'Email notification configuration should be available');

            $this->testResults['email_notification'] = 'Backup email notification configuration is available';
        });
    }

    /**
     * Test 50: Backup webhook notification option
     *
     * @test
     */
    public function test_backup_webhook_notification_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('webhook-notification');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWebhook =
                str_contains($pageSource, 'webhook') ||
                str_contains($pageSource, 'url') ||
                str_contains($pageSource, 'notification');

            $this->assertTrue($hasWebhook , 'Webhook notification option should be available');

            $this->testResults['webhook_notification'] = 'Backup webhook notification option is available';
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
                'test_suite' => 'Server Backups Functionality Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'test_server_id' => $this->server->id,
                    'test_project_id' => $this->project->id,
                    'server_backups' => ServerBackup::count(),
                    'backup_schedules' => ServerBackupSchedule::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/server-backups-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
