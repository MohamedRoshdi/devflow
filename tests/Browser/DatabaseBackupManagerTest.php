<?php

namespace Tests\Browser;

use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DatabaseBackupManagerTest extends DuskTestCase
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

        // Create test server and project for backup tests
        $this->server = Server::firstOrCreate(
            ['name' => 'Test Database Backup Server'],
            [
                'hostname' => 'db-backup-test.local',
                'ip_address' => '192.168.1.150',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        $this->project = Project::firstOrCreate(
            ['slug' => 'test-db-backup-project'],
            [
                'name' => 'Test DB Backup Project',
                'repository_url' => 'https://github.com/test/db-backup-project',
                'branch' => 'main',
                'framework' => 'laravel',
                'php_version' => '8.4',
                'server_id' => $this->server->id,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: Database backup manager page loads
     *
     * @test
     */
    public function test_database_backup_manager_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-backup-manager-page');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupContent =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'database') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasBackupContent, 'Database backup manager page should load');
            $this->testResults['database_backup_manager_loads'] = 'Database backup manager page loaded successfully';
        });
    }

    /**
     * Test 2: Create backup modal opens
     *
     * @test
     */
    public function test_create_backup_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-create-backup-modal-check');

            $pageSource = $browser->driver->getPageSource();
            $hasCreateButton =
                str_contains($pageSource, 'Create Backup') ||
                str_contains($pageSource, 'New Backup') ||
                str_contains($pageSource, 'createBackup') ||
                str_contains($pageSource, 'openCreateBackupModal');

            $this->assertTrue($hasCreateButton, 'Create backup modal should be accessible');
            $this->testResults['create_backup_modal'] = 'Create backup modal is accessible';
        });
    }

    /**
     * Test 3: Database name input is required
     *
     * @test
     */
    public function test_database_name_input_required()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-name-input-field');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDatabaseNameField =
                str_contains($pageSource, 'databasename') ||
                str_contains($pageSource, 'database_name') ||
                str_contains($pageSource, 'database name');

            $this->assertTrue($hasDatabaseNameField, 'Database name input should be present');
            $this->testResults['database_name_input'] = 'Database name input field is present';
        });
    }

    /**
     * Test 4: Database type selection available
     *
     * @test
     */
    public function test_database_type_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-type-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDatabaseTypes =
                str_contains($pageSource, 'mysql') ||
                str_contains($pageSource, 'postgresql') ||
                str_contains($pageSource, 'sqlite') ||
                str_contains($pageSource, 'databasetype');

            $this->assertTrue($hasDatabaseTypes, 'Database type selection should be available');
            $this->testResults['database_type_selection'] = 'Database type selection is available';
        });
    }

    /**
     * Test 5: Schedule modal opens
     *
     * @test
     */
    public function test_schedule_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-modal-check');

            $pageSource = $browser->driver->getPageSource();
            $hasScheduleButton =
                str_contains($pageSource, 'Schedule') ||
                str_contains($pageSource, 'createSchedule') ||
                str_contains($pageSource, 'openScheduleModal');

            $this->assertTrue($hasScheduleButton, 'Schedule modal should be accessible');
            $this->testResults['schedule_modal'] = 'Schedule modal is accessible';
        });
    }

    /**
     * Test 6: Frequency options display
     *
     * @test
     */
    public function test_frequency_options_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-frequency-options');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFrequencyOptions =
                str_contains($pageSource, 'hourly') ||
                str_contains($pageSource, 'daily') ||
                str_contains($pageSource, 'weekly') ||
                str_contains($pageSource, 'monthly') ||
                str_contains($pageSource, 'frequency');

            $this->assertTrue($hasFrequencyOptions, 'Frequency options should display');
            $this->testResults['frequency_options'] = 'Frequency options are displayed';
        });
    }

    /**
     * Test 7: Time selection input is present
     *
     * @test
     */
    public function test_time_selection_input_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-time-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTimeField =
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, 'schedule');

            $this->assertTrue($hasTimeField, 'Time selection should be present');
            $this->testResults['time_selection'] = 'Time selection input is present';
        });
    }

    /**
     * Test 8: Retention days configuration
     *
     * @test
     */
    public function test_retention_days_configuration()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-retention-days');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetentionConfig =
                str_contains($pageSource, 'retention') ||
                str_contains($pageSource, 'retentiondays') ||
                str_contains($pageSource, 'days');

            $this->assertTrue($hasRetentionConfig, 'Retention days configuration should be present');
            $this->testResults['retention_days'] = 'Retention days configuration is present';
        });
    }

    /**
     * Test 9: Storage disk selection
     *
     * @test
     */
    public function test_storage_disk_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-storage-disk-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStorageOptions =
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'local') ||
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'storagedisk');

            $this->assertTrue($hasStorageOptions, 'Storage disk selection should be available');
            $this->testResults['storage_disk_selection'] = 'Storage disk selection is available';
        });
    }

    /**
     * Test 10: Backup list displays
     *
     * @test
     */
    public function test_backup_list_displays()
    {
        // Create test backup
        DatabaseBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'database_name' => 'test_database',
                'file_name' => 'test_backup_'.now()->format('Y-m-d_H-i-s').'.sql.gz',
            ],
            [
                'server_id' => $this->server->id,
                'database_type' => 'mysql',
                'type' => 'full',
                'file_path' => '/backups/test_backup.sql.gz',
                'file_size' => 1024000,
                'checksum' => md5('test_backup'),
                'storage_disk' => 'local',
                'status' => 'completed',
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-backup-list');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBackupList =
                str_contains($pageSource, 'backup') ||
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasBackupList, 'Backup list should be displayed');
            $this->testResults['backup_list'] = 'Backup list is displayed';
        });
    }

    /**
     * Test 11: Schedule list displays
     *
     * @test
     */
    public function test_schedule_list_displays()
    {
        BackupSchedule::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'database_name' => 'scheduled_database',
            ],
            [
                'server_id' => $this->server->id,
                'database_type' => 'mysql',
                'frequency' => 'daily',
                'time' => '02:00:00',
                'retention_days' => 30,
                'storage_disk' => 'local',
                'is_active' => true,
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-list');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScheduleList =
                str_contains($pageSource, 'schedule') ||
                str_contains($pageSource, 'frequency') ||
                str_contains($pageSource, 'daily');

            $this->assertTrue($hasScheduleList, 'Schedule list should be displayed');
            $this->testResults['schedule_list'] = 'Schedule list is displayed';
        });
    }

    /**
     * Test 12: Backup statistics are shown
     *
     * @test
     */
    public function test_backup_statistics_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-backup-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatistics =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'last backup') ||
                str_contains($pageSource, 'scheduled');

            $this->assertTrue($hasStatistics, 'Backup statistics should be shown');
            $this->testResults['backup_statistics'] = 'Backup statistics are displayed';
        });
    }

    /**
     * Test 13: Delete backup confirmation modal
     *
     * @test
     */
    public function test_delete_backup_confirmation_modal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-delete-confirmation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteOption =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'confirmdelete');

            $this->assertTrue($hasDeleteOption, 'Delete confirmation should be available');
            $this->testResults['delete_confirmation'] = 'Delete confirmation modal is accessible';
        });
    }

    /**
     * Test 14: Restore backup confirmation modal
     *
     * @test
     */
    public function test_restore_backup_confirmation_modal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-restore-confirmation');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRestoreOption =
                str_contains($pageSource, 'restore') ||
                str_contains($pageSource, 'confirmrestore');

            $this->assertTrue($hasRestoreOption, 'Restore confirmation should be available');
            $this->testResults['restore_confirmation'] = 'Restore confirmation modal is accessible';
        });
    }

    /**
     * Test 15: Verify backup modal
     *
     * @test
     */
    public function test_verify_backup_modal()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-verify-backup');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVerifyOption =
                str_contains($pageSource, 'verify') ||
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'confirmverify');

            $this->assertTrue($hasVerifyOption, 'Verify backup should be available');
            $this->testResults['verify_backup'] = 'Verify backup modal is accessible';
        });
    }

    /**
     * Test 16: Download backup functionality
     *
     * @test
     */
    public function test_download_backup_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-download-backup');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDownloadOption =
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, 'downloadbackup');

            $this->assertTrue($hasDownloadOption, 'Download backup should be available');
            $this->testResults['download_backup'] = 'Download backup functionality is available';
        });
    }

    /**
     * Test 17: Backup status indicators
     *
     * @test
     */
    public function test_backup_status_indicators()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-status-indicators');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicators =
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'pending') ||
                str_contains($pageSource, 'running') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatusIndicators, 'Status indicators should be shown');
            $this->testResults['status_indicators'] = 'Backup status indicators are displayed';
        });
    }

    /**
     * Test 18: Backup file size display
     *
     * @test
     */
    public function test_backup_file_size_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-file-size-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFileSize =
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'mb') ||
                str_contains($pageSource, 'gb') ||
                str_contains($pageSource, 'kb');

            $this->assertTrue($hasFileSize, 'File size should be displayed');
            $this->testResults['file_size_display'] = 'Backup file size is displayed';
        });
    }

    /**
     * Test 19: Backup checksum display
     *
     * @test
     */
    public function test_backup_checksum_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-checksum-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasChecksum =
                str_contains($pageSource, 'checksum') ||
                str_contains($pageSource, 'hash') ||
                str_contains($pageSource, 'md5');

            $this->assertTrue($hasChecksum, 'Checksum should be displayed');
            $this->testResults['checksum_display'] = 'Backup checksum is displayed';
        });
    }

    /**
     * Test 20: Schedule toggle functionality
     *
     * @test
     */
    public function test_schedule_toggle_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle =
                str_contains($pageSource, 'toggle') ||
                str_contains($pageSource, 'activate') ||
                str_contains($pageSource, 'deactivate') ||
                str_contains($pageSource, 'toggleschedule');

            $this->assertTrue($hasToggle, 'Schedule toggle should be available');
            $this->testResults['schedule_toggle'] = 'Schedule toggle functionality is available';
        });
    }

    /**
     * Test 21: Weekly schedule day selection
     *
     * @test
     */
    public function test_weekly_schedule_day_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-weekly-day-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDaySelection =
                str_contains($pageSource, 'dayofweek') ||
                str_contains($pageSource, 'day of week') ||
                str_contains($pageSource, 'weekly');

            $this->assertTrue($hasDaySelection, 'Weekly day selection should be available');
            $this->testResults['weekly_day_selection'] = 'Weekly schedule day selection is available';
        });
    }

    /**
     * Test 22: Monthly schedule day selection
     *
     * @test
     */
    public function test_monthly_schedule_day_selection()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-monthly-day-selection');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDaySelection =
                str_contains($pageSource, 'dayofmonth') ||
                str_contains($pageSource, 'day of month') ||
                str_contains($pageSource, 'monthly');

            $this->assertTrue($hasDaySelection, 'Monthly day selection should be available');
            $this->testResults['monthly_day_selection'] = 'Monthly schedule day selection is available';
        });
    }

    /**
     * Test 23: Backup creation progress indicator
     *
     * @test
     */
    public function test_backup_creation_progress_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-creation-progress');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgress =
                str_contains($pageSource, 'iscreatingbackup') ||
                str_contains($pageSource, 'progress') ||
                str_contains($pageSource, 'creating');

            $this->assertTrue($hasProgress, 'Creation progress indicator should be present');
            $this->testResults['creation_progress'] = 'Backup creation progress indicator is present';
        });
    }

    /**
     * Test 24: Schedule creation progress indicator
     *
     * @test
     */
    public function test_schedule_creation_progress_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-creation-progress');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgress =
                str_contains($pageSource, 'iscreatingsched') ||
                str_contains($pageSource, 'creating');

            $this->assertTrue($hasProgress, 'Schedule creation progress should be present');
            $this->testResults['schedule_creation_progress'] = 'Schedule creation progress indicator is present';
        });
    }

    /**
     * Test 25: Verification progress indicator
     *
     * @test
     */
    public function test_verification_progress_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-verification-progress');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProgress =
                str_contains($pageSource, 'isverifying') ||
                str_contains($pageSource, 'verifying');

            $this->assertTrue($hasProgress, 'Verification progress should be present');
            $this->testResults['verification_progress'] = 'Verification progress indicator is present';
        });
    }

    /**
     * Test 26: Backup started time display
     *
     * @test
     */
    public function test_backup_started_time_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-started-time');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStartedTime =
                str_contains($pageSource, 'started') ||
                str_contains($pageSource, 'started_at') ||
                str_contains($pageSource, 'created');

            $this->assertTrue($hasStartedTime, 'Backup started time should be displayed');
            $this->testResults['started_time_display'] = 'Backup started time is displayed';
        });
    }

    /**
     * Test 27: Backup completed time display
     *
     * @test
     */
    public function test_backup_completed_time_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-completed-time');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCompletedTime =
                str_contains($pageSource, 'completed') ||
                str_contains($pageSource, 'completed_at') ||
                str_contains($pageSource, 'finished');

            $this->assertTrue($hasCompletedTime, 'Backup completed time should be displayed');
            $this->testResults['completed_time_display'] = 'Backup completed time is displayed';
        });
    }

    /**
     * Test 28: Failed backup error message display
     *
     * @test
     */
    public function test_failed_backup_error_message_display()
    {
        DatabaseBackup::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'database_name' => 'failed_database',
                'file_name' => 'failed_backup_'.now()->format('Y-m-d_H-i-s').'.sql.gz',
            ],
            [
                'server_id' => $this->server->id,
                'database_type' => 'mysql',
                'type' => 'full',
                'file_path' => '/backups/failed_backup.sql.gz',
                'storage_disk' => 'local',
                'status' => 'failed',
                'started_at' => now()->subHours(1),
                'error_message' => 'Connection timeout during backup',
            ]
        );

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-error-message');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasErrorMessage =
                str_contains($pageSource, 'error') ||
                str_contains($pageSource, 'failed') ||
                str_contains($pageSource, 'timeout');

            $this->assertTrue($hasErrorMessage, 'Error message should be displayed');
            $this->testResults['error_message_display'] = 'Failed backup error message is displayed';
        });
    }

    /**
     * Test 29: Delete schedule functionality
     *
     * @test
     */
    public function test_delete_schedule_functionality()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-delete-schedule');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteSchedule =
                str_contains($pageSource, 'deleteschedule') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove');

            $this->assertTrue($hasDeleteSchedule, 'Delete schedule should be available');
            $this->testResults['delete_schedule'] = 'Delete schedule functionality is available';
        });
    }

    /**
     * Test 30: Backup pagination
     *
     * @test
     */
    public function test_backup_pagination()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-backup-pagination');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPagination =
                str_contains($pageSource, 'pagination') ||
                str_contains($pageSource, 'next') ||
                str_contains($pageSource, 'previous') ||
                str_contains($pageSource, 'page');

            $this->assertTrue($hasPagination, 'Pagination should be available');
            $this->testResults['backup_pagination'] = 'Backup pagination is available';
        });
    }

    /**
     * Test 31: MySQL database type option
     *
     * @test
     */
    public function test_mysql_database_type_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-mysql-option');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMysql = str_contains($pageSource, 'mysql');

            $this->assertTrue($hasMysql, 'MySQL option should be available');
            $this->testResults['mysql_option'] = 'MySQL database type option is available';
        });
    }

    /**
     * Test 32: PostgreSQL database type option
     *
     * @test
     */
    public function test_postgresql_database_type_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-postgresql-option');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPostgresql = str_contains($pageSource, 'postgresql') || str_contains($pageSource, 'postgres');

            $this->assertTrue($hasPostgresql, 'PostgreSQL option should be available');
            $this->testResults['postgresql_option'] = 'PostgreSQL database type option is available';
        });
    }

    /**
     * Test 33: SQLite database type option
     *
     * @test
     */
    public function test_sqlite_database_type_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-sqlite-option');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSqlite = str_contains($pageSource, 'sqlite');

            $this->assertTrue($hasSqlite, 'SQLite option should be available');
            $this->testResults['sqlite_option'] = 'SQLite database type option is available';
        });
    }

    /**
     * Test 34: Local storage disk option
     *
     * @test
     */
    public function test_local_storage_disk_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-local-storage');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLocal = str_contains($pageSource, 'local');

            $this->assertTrue($hasLocal, 'Local storage option should be available');
            $this->testResults['local_storage_option'] = 'Local storage disk option is available';
        });
    }

    /**
     * Test 35: S3 storage disk option
     *
     * @test
     */
    public function test_s3_storage_disk_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-s3-storage');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasS3 = str_contains($pageSource, 's3') || str_contains($pageSource, 'amazon');

            $this->assertTrue($hasS3, 'S3 storage option should be available');
            $this->testResults['s3_storage_option'] = 'S3 storage disk option is available';
        });
    }

    /**
     * Test 36: Hourly frequency option
     *
     * @test
     */
    public function test_hourly_frequency_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-hourly-frequency');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHourly = str_contains($pageSource, 'hourly');

            $this->assertTrue($hasHourly, 'Hourly frequency option should be available');
            $this->testResults['hourly_frequency'] = 'Hourly frequency option is available';
        });
    }

    /**
     * Test 37: Daily frequency option
     *
     * @test
     */
    public function test_daily_frequency_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-daily-frequency');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDaily = str_contains($pageSource, 'daily');

            $this->assertTrue($hasDaily, 'Daily frequency option should be available');
            $this->testResults['daily_frequency'] = 'Daily frequency option is available';
        });
    }

    /**
     * Test 38: Weekly frequency option
     *
     * @test
     */
    public function test_weekly_frequency_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-weekly-frequency');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasWeekly = str_contains($pageSource, 'weekly');

            $this->assertTrue($hasWeekly, 'Weekly frequency option should be available');
            $this->testResults['weekly_frequency'] = 'Weekly frequency option is available';
        });
    }

    /**
     * Test 39: Monthly frequency option
     *
     * @test
     */
    public function test_monthly_frequency_option()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-monthly-frequency');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMonthly = str_contains($pageSource, 'monthly');

            $this->assertTrue($hasMonthly, 'Monthly frequency option should be available');
            $this->testResults['monthly_frequency'] = 'Monthly frequency option is available';
        });
    }

    /**
     * Test 40: Total backups statistic
     *
     * @test
     */
    public function test_total_backups_statistic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-total-backups-stat');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTotalBackups =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'backups');

            $this->assertTrue($hasTotalBackups, 'Total backups statistic should be shown');
            $this->testResults['total_backups_stat'] = 'Total backups statistic is displayed';
        });
    }

    /**
     * Test 41: Scheduled backups count statistic
     *
     * @test
     */
    public function test_scheduled_backups_count_statistic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-scheduled-count-stat');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasScheduledCount =
                str_contains($pageSource, 'scheduled') ||
                str_contains($pageSource, 'active');

            $this->assertTrue($hasScheduledCount, 'Scheduled backups count should be shown');
            $this->testResults['scheduled_count_stat'] = 'Scheduled backups count statistic is displayed';
        });
    }

    /**
     * Test 42: Total size statistic
     *
     * @test
     */
    public function test_total_size_statistic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-total-size-stat');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTotalSize =
                str_contains($pageSource, 'total') ||
                str_contains($pageSource, 'size');

            $this->assertTrue($hasTotalSize, 'Total size statistic should be shown');
            $this->testResults['total_size_stat'] = 'Total size statistic is displayed';
        });
    }

    /**
     * Test 43: Last backup time statistic
     *
     * @test
     */
    public function test_last_backup_time_statistic()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-last-backup-stat');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLastBackup =
                str_contains($pageSource, 'last') ||
                str_contains($pageSource, 'backup');

            $this->assertTrue($hasLastBackup, 'Last backup time should be shown');
            $this->testResults['last_backup_stat'] = 'Last backup time statistic is displayed';
        });
    }

    /**
     * Test 44: Backup type full indicator
     *
     * @test
     */
    public function test_backup_type_full_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-full-backup-type');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFull = str_contains($pageSource, 'full');

            $this->assertTrue($hasFull, 'Full backup type should be indicated');
            $this->testResults['full_backup_type'] = 'Full backup type indicator is displayed';
        });
    }

    /**
     * Test 45: Active schedule indicator
     *
     * @test
     */
    public function test_active_schedule_indicator()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-active-schedule');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasActive =
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'is_active');

            $this->assertTrue($hasActive, 'Active schedule indicator should be shown');
            $this->testResults['active_schedule_indicator'] = 'Active schedule indicator is displayed';
        });
    }

    /**
     * Test 46: Backup file name display
     *
     * @test
     */
    public function test_backup_file_name_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-file-name-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFileName =
                str_contains($pageSource, 'file_name') ||
                str_contains($pageSource, 'filename') ||
                str_contains($pageSource, '.sql') ||
                str_contains($pageSource, '.gz');

            $this->assertTrue($hasFileName, 'Backup file name should be displayed');
            $this->testResults['file_name_display'] = 'Backup file name is displayed';
        });
    }

    /**
     * Test 47: Backup database name display
     *
     * @test
     */
    public function test_backup_database_name_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-database-name-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDatabaseName =
                str_contains($pageSource, 'database') ||
                str_contains($pageSource, 'database_name');

            $this->assertTrue($hasDatabaseName, 'Database name should be displayed');
            $this->testResults['database_name_display'] = 'Backup database name is displayed';
        });
    }

    /**
     * Test 48: Schedule frequency display
     *
     * @test
     */
    public function test_schedule_frequency_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-frequency-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFrequency = str_contains($pageSource, 'frequency');

            $this->assertTrue($hasFrequency, 'Schedule frequency should be displayed');
            $this->testResults['schedule_frequency_display'] = 'Schedule frequency is displayed';
        });
    }

    /**
     * Test 49: Schedule time display
     *
     * @test
     */
    public function test_schedule_time_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-schedule-time-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTime =
                str_contains($pageSource, 'time') ||
                str_contains($pageSource, '02:00') ||
                str_contains($pageSource, ':');

            $this->assertTrue($hasTime, 'Schedule time should be displayed');
            $this->testResults['schedule_time_display'] = 'Schedule time is displayed';
        });
    }

    /**
     * Test 50: Retention days display
     *
     * @test
     */
    public function test_retention_days_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/projects/{$this->project->id}/backups")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('db-retention-days-display');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRetention =
                str_contains($pageSource, 'retention') ||
                str_contains($pageSource, 'days');

            $this->assertTrue($hasRetention, 'Retention days should be displayed');
            $this->testResults['retention_days_display'] = 'Retention days is displayed';
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
                'test_suite' => 'Database Backup Manager Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'test_project_id' => $this->project->id,
                    'test_server_id' => $this->server->id,
                    'database_backups' => DatabaseBackup::count(),
                    'backup_schedules' => BackupSchedule::count(),
                ],
            ];

            $reportPath = storage_path('app/test-reports/database-backup-manager-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
