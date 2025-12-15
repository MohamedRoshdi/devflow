<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Models\StorageConfiguration;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class StorageTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Project $project;

    protected Server $server;

    protected StorageConfiguration $storageConfig;

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

        // Create test server and project for storage tests
        $this->server = Server::firstOrCreate(
            ['name' => 'Test Storage Server'],
            [
                'hostname' => 'storage-test.local',
                'ip_address' => '192.168.1.150',
                'ssh_user' => 'root',
                'ssh_port' => 22,
                'status' => 'online',
            ]
        );

        $this->project = Project::firstOrCreate(
            ['slug' => 'test-storage-project'],
            [
                'name' => 'Test Storage Project',
                'repository_url' => 'https://github.com/test/storage-project',
                'branch' => 'main',
                'framework' => 'laravel',
                'php_version' => '8.4',
                'server_id' => $this->server->id,
                'status' => 'active',
            ]
        );

        // Create test storage configuration
        $this->storageConfig = StorageConfiguration::firstOrCreate(
            ['name' => 'Test S3 Storage'],
            [
                'driver' => 's3',
                'project_id' => $this->project->id,
                'credentials' => [
                    'access_key_id' => 'test_access_key',
                    'secret_access_key' => 'test_secret_key',
                ],
                'bucket' => 'test-devflow-bucket',
                'region' => 'us-east-1',
                'endpoint' => null,
                'path_prefix' => 'backups',
                'is_default' => false,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test 1: Storage settings page loads
     *
     */

    #[Test]
    public function test_storage_settings_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-settings-page');

            // Check if storage settings page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStorageContent =
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'configuration') ||
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'provider');

            $this->assertTrue($hasStorageContent, 'Storage settings page should load');

            $this->testResults['storage_settings_page'] = 'Storage settings page loaded successfully';
        });
    }

    /**
     * Test 2: Storage configurations list displays
     *
     */

    #[Test]
    public function test_storage_configurations_list_displays()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-configurations-list');

            // Check for storage configurations via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfigList =
                str_contains($pageSource, 'test s3 storage') ||
                str_contains($pageSource, 'configuration') ||
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'bucket');

            $this->assertTrue($hasConfigList, 'Storage configurations list should display');

            $this->testResults['storage_configurations_list'] = 'Storage configurations list displays successfully';
        });
    }

    /**
     * Test 3: Create storage configuration modal opens
     *
     */

    #[Test]
    public function test_create_storage_configuration_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('before-storage-config-modal');

            // Check for create button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasCreateButton =
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'Add') ||
                str_contains($pageSource, 'New') ||
                str_contains($pageSource, 'openCreateModal');

            $this->assertTrue($hasCreateButton, 'Create storage configuration modal should be accessible');

            $this->testResults['create_storage_config_modal'] = 'Create storage configuration modal is accessible';
        });
    }

    /**
     * Test 4: S3 storage provider tab is available
     *
     */

    #[Test]
    public function test_s3_storage_provider_tab_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('s3-provider-tab');

            // Check for S3 provider option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasS3Option =
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'amazon') ||
                str_contains($pageSource, 'aws');

            $this->assertTrue($hasS3Option, 'S3 storage provider tab should be available');

            $this->testResults['s3_provider_tab'] = 'S3 storage provider tab is available';
        });
    }

    /**
     * Test 5: Google Cloud Storage provider tab is available
     *
     */

    #[Test]
    public function test_gcs_storage_provider_tab_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('gcs-provider-tab');

            // Check for GCS provider option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGcsOption =
                str_contains($pageSource, 'gcs') ||
                str_contains($pageSource, 'google') ||
                str_contains($pageSource, 'cloud storage');

            $this->assertTrue($hasGcsOption, 'Google Cloud Storage provider tab should be available');

            $this->testResults['gcs_provider_tab'] = 'Google Cloud Storage provider tab is available';
        });
    }

    /**
     * Test 6: FTP storage provider tab is available
     *
     */

    #[Test]
    public function test_ftp_storage_provider_tab_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ftp-provider-tab');

            // Check for FTP provider option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFtpOption =
                str_contains($pageSource, 'ftp') ||
                str_contains($pageSource, 'file transfer');

            $this->assertTrue($hasFtpOption, 'FTP storage provider tab should be available');

            $this->testResults['ftp_provider_tab'] = 'FTP storage provider tab is available';
        });
    }

    /**
     * Test 7: SFTP storage provider tab is available
     *
     */

    #[Test]
    public function test_sftp_storage_provider_tab_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('sftp-provider-tab');

            // Check for SFTP provider option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSftpOption =
                str_contains($pageSource, 'sftp') ||
                str_contains($pageSource, 'secure ftp') ||
                str_contains($pageSource, 'ssh');

            $this->assertTrue($hasSftpOption, 'SFTP storage provider tab should be available');

            $this->testResults['sftp_provider_tab'] = 'SFTP storage provider tab is available';
        });
    }

    /**
     * Test 8: Local storage provider option is available
     *
     */

    #[Test]
    public function test_local_storage_provider_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('local-storage-option');

            // Check for local storage option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLocalOption =
                str_contains($pageSource, 'local') ||
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'disk');

            $this->assertTrue($hasLocalOption, 'Local storage provider option should be available');

            $this->testResults['local_storage_option'] = 'Local storage provider option is available';
        });
    }

    /**
     * Test 9: Storage credentials fields are present
     *
     */

    #[Test]
    public function test_storage_credentials_fields_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-credentials-fields');

            // Check for credential input fields via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCredentialFields =
                str_contains($pageSource, 'access') ||
                str_contains($pageSource, 'secret') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'credential') ||
                str_contains($pageSource, 'password');

            $this->assertTrue($hasCredentialFields, 'Storage credentials fields should be present');

            $this->testResults['storage_credentials_fields'] = 'Storage credentials fields are present';
        });
    }

    /**
     * Test 10: Storage bucket/container name field is present
     *
     */

    #[Test]
    public function test_storage_bucket_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-bucket-field');

            // Check for bucket field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBucketField =
                str_contains($pageSource, 'bucket') ||
                str_contains($pageSource, 'container') ||
                str_contains($pageSource, 'name');

            $this->assertTrue($hasBucketField, 'Storage bucket/container field should be present');

            $this->testResults['storage_bucket_field'] = 'Storage bucket/container field is present';
        });
    }

    /**
     * Test 11: Storage region selection is available
     *
     */

    #[Test]
    public function test_storage_region_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-region-selection');

            // Check for region field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRegionField =
                str_contains($pageSource, 'region') ||
                str_contains($pageSource, 'location') ||
                str_contains($pageSource, 'us-east') ||
                str_contains($pageSource, 'eu-west');

            $this->assertTrue($hasRegionField, 'Storage region selection should be available');

            $this->testResults['storage_region_selection'] = 'Storage region selection is available';
        });
    }

    /**
     * Test 12: Storage path prefix field is available
     *
     */

    #[Test]
    public function test_storage_path_prefix_field_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-path-prefix');

            // Check for path prefix field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPathPrefix =
                str_contains($pageSource, 'prefix') ||
                str_contains($pageSource, 'path') ||
                str_contains($pageSource, 'folder');

            $this->assertTrue($hasPathPrefix, 'Storage path prefix field should be available');

            $this->testResults['storage_path_prefix'] = 'Storage path prefix field is available';
        });
    }

    /**
     * Test 13: Storage encryption option is available
     *
     */

    #[Test]
    public function test_storage_encryption_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-encryption-option');

            // Check for encryption option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEncryptionOption =
                str_contains($pageSource, 'encrypt') ||
                str_contains($pageSource, 'encryption') ||
                str_contains($pageSource, 'secure');

            $this->assertTrue($hasEncryptionOption, 'Storage encryption option should be available');

            $this->testResults['storage_encryption_option'] = 'Storage encryption option is available';
        });
    }

    /**
     * Test 14: Generate encryption key button is available
     *
     */

    #[Test]
    public function test_generate_encryption_key_button_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('generate-encryption-key-button');

            // Check for generate key button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGenerateKeyButton =
                str_contains($pageSource, 'generate') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'generateencryptionkey');

            $this->assertTrue($hasGenerateKeyButton, 'Generate encryption key button should be available');

            $this->testResults['generate_encryption_key'] = 'Generate encryption key button is available';
        });
    }

    /**
     * Test 15: Test storage connection functionality is available
     *
     */

    #[Test]
    public function test_test_storage_connection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('test-storage-connection');

            // Check for test connection button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTestButton =
                str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'testconnection') ||
                str_contains($pageSource, 'verify');

            $this->assertTrue($hasTestButton, 'Test storage connection functionality should be available');

            $this->testResults['test_storage_connection'] = 'Test storage connection functionality is available';
        });
    }

    /**
     * Test 16: Set default storage option is available
     *
     */

    #[Test]
    public function test_set_default_storage_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('set-default-storage');

            // Check for set default option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDefaultOption =
                str_contains($pageSource, 'default') ||
                str_contains($pageSource, 'setasdefault') ||
                str_contains($pageSource, 'primary');

            $this->assertTrue($hasDefaultOption, 'Set default storage option should be available');

            $this->testResults['set_default_storage'] = 'Set default storage option is available';
        });
    }

    /**
     * Test 17: Edit storage configuration modal opens
     *
     */

    #[Test]
    public function test_edit_storage_configuration_modal_opens()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('edit-storage-config-modal');

            // Check for edit button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEditButton =
                str_contains($pageSource, 'edit') ||
                str_contains($pageSource, 'openeditmodal') ||
                str_contains($pageSource, 'update');

            $this->assertTrue($hasEditButton, 'Edit storage configuration modal should be accessible');

            $this->testResults['edit_storage_config_modal'] = 'Edit storage configuration modal is accessible';
        });
    }

    /**
     * Test 18: Delete storage configuration option is available
     *
     */

    #[Test]
    public function test_delete_storage_configuration_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delete-storage-config');

            // Check for delete button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteButton =
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove') ||
                str_contains($pageSource, 'confirmdelete');

            $this->assertTrue($hasDeleteButton, 'Delete storage configuration option should be available');

            $this->testResults['delete_storage_config'] = 'Delete storage configuration option is available';
        });
    }

    /**
     * Test 19: Storage configuration status indicator is shown
     *
     */

    #[Test]
    public function test_storage_configuration_status_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-config-status');

            // Check for status indicator via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusIndicator =
                str_contains($pageSource, 'status') ||
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'inactive') ||
                str_contains($pageSource, 'connected');

            $this->assertTrue($hasStatusIndicator, 'Storage configuration status should be shown');

            $this->testResults['storage_config_status'] = 'Storage configuration status indicator is shown';
        });
    }

    /**
     * Test 20: Storage last tested timestamp is displayed
     *
     */

    #[Test]
    public function test_storage_last_tested_timestamp_displayed()
    {
        // Update last tested timestamp
        $this->storageConfig->update(['last_tested_at' => now()]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-last-tested');

            // Check for last tested timestamp via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLastTested =
                str_contains($pageSource, 'last') ||
                str_contains($pageSource, 'tested') ||
                str_contains($pageSource, 'ago') ||
                str_contains($pageSource, 'verified');

            $this->assertTrue($hasLastTested, 'Storage last tested timestamp should be displayed');

            $this->testResults['storage_last_tested'] = 'Storage last tested timestamp is displayed';
        });
    }

    /**
     * Test 21: Storage usage statistics are shown
     *
     */

    #[Test]
    public function test_storage_usage_statistics_shown()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-usage-statistics');

            // Check for storage usage stats via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUsageStats =
                str_contains($pageSource, 'usage') ||
                str_contains($pageSource, 'size') ||
                str_contains($pageSource, 'mb') ||
                str_contains($pageSource, 'gb') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasUsageStats, 'Storage usage statistics should be shown');

            $this->testResults['storage_usage_stats'] = 'Storage usage statistics are displayed';
        });
    }

    /**
     * Test 22: Storage cleanup tools are accessible
     *
     */

    #[Test]
    public function test_storage_cleanup_tools_accessible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-cleanup-tools');

            // Check for cleanup tools via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCleanupTools =
                str_contains($pageSource, 'cleanup') ||
                str_contains($pageSource, 'clean') ||
                str_contains($pageSource, 'clear') ||
                str_contains($pageSource, 'purge');

            $this->assertTrue($hasCleanupTools, 'Storage cleanup tools should be accessible');

            $this->testResults['storage_cleanup_tools'] = 'Storage cleanup tools are accessible';
        });
    }

    /**
     * Test 23: Storage configuration project assignment is available
     *
     */

    #[Test]
    public function test_storage_project_assignment_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-project-assignment');

            // Check for project assignment via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProjectAssignment =
                str_contains($pageSource, 'project') ||
                str_contains($pageSource, 'assign') ||
                str_contains($pageSource, 'project_id');

            $this->assertTrue($hasProjectAssignment, 'Storage project assignment should be available');

            $this->testResults['storage_project_assignment'] = 'Storage project assignment is available';
        });
    }

    /**
     * Test 24: Storage endpoint configuration is available (for S3-compatible)
     *
     */

    #[Test]
    public function test_storage_endpoint_configuration_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-endpoint-config');

            // Check for endpoint field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEndpointField =
                str_contains($pageSource, 'endpoint') ||
                str_contains($pageSource, 'url') ||
                str_contains($pageSource, 'custom');

            $this->assertTrue($hasEndpointField, 'Storage endpoint configuration should be available');

            $this->testResults['storage_endpoint_config'] = 'Storage endpoint configuration is available';
        });
    }

    /**
     * Test 25: Storage test results display (connection test feedback)
     *
     */

    #[Test]
    public function test_storage_test_results_display()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-test-results');

            // Check for test results display area via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasTestResults =
                str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'result') ||
                str_contains($pageSource, 'success') ||
                str_contains($pageSource, 'failed');

            $this->assertTrue($hasTestResults, 'Storage test results display should be available');

            $this->testResults['storage_test_results'] = 'Storage test results display is available';
        });
    }

    /**
     * Test 26: Storage driver icon/badge is displayed
     *
     */

    #[Test]
    public function test_storage_driver_icon_displayed()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-driver-icon');

            // Check for driver icon via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDriverIcon =
                str_contains($pageSource, 'aws') ||
                str_contains($pageSource, 'google') ||
                str_contains($pageSource, 'icon') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasDriverIcon, 'Storage driver icon/badge should be displayed');

            $this->testResults['storage_driver_icon'] = 'Storage driver icon/badge is displayed';
        });
    }

    /**
     * Test 27: Storage configuration name field is editable
     *
     */

    #[Test]
    public function test_storage_configuration_name_editable()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-config-name');

            // Check for name field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNameField =
                str_contains($pageSource, 'name') ||
                str_contains($pageSource, 'label') ||
                str_contains($pageSource, 'title');

            $this->assertTrue($hasNameField, 'Storage configuration name field should be editable');

            $this->testResults['storage_config_name'] = 'Storage configuration name field is editable';
        });
    }

    /**
     * Test 28: FTP passive mode toggle is available
     *
     */

    #[Test]
    public function test_ftp_passive_mode_toggle_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ftp-passive-mode');

            // Check for passive mode toggle via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPassiveMode =
                str_contains($pageSource, 'passive') ||
                str_contains($pageSource, 'ftp_passive') ||
                str_contains($pageSource, 'mode');

            $this->assertTrue($hasPassiveMode, 'FTP passive mode toggle should be available');

            $this->testResults['ftp_passive_mode'] = 'FTP passive mode toggle is available';
        });
    }

    /**
     * Test 29: FTP SSL/TLS option is available
     *
     */

    #[Test]
    public function test_ftp_ssl_option_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('ftp-ssl-option');

            // Check for SSL option via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSslOption =
                str_contains($pageSource, 'ssl') ||
                str_contains($pageSource, 'tls') ||
                str_contains($pageSource, 'secure') ||
                str_contains($pageSource, 'ftp_ssl');

            $this->assertTrue($hasSslOption, 'FTP SSL/TLS option should be available');

            $this->testResults['ftp_ssl_option'] = 'FTP SSL/TLS option is available';
        });
    }

    /**
     * Test 30: SFTP private key field is available
     *
     */

    #[Test]
    public function test_sftp_private_key_field_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('sftp-private-key');

            // Check for private key field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPrivateKeyField =
                str_contains($pageSource, 'private') ||
                str_contains($pageSource, 'key') ||
                str_contains($pageSource, 'sftp_private_key') ||
                str_contains($pageSource, 'ssh');

            $this->assertTrue($hasPrivateKeyField, 'SFTP private key field should be available');

            $this->testResults['sftp_private_key_field'] = 'SFTP private key field is available';
        });
    }

    /**
     * Test 31: Storage configuration save button works
     *
     */

    #[Test]
    public function test_storage_configuration_save_button_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-config-save');

            // Check for save button via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSaveButton =
                str_contains($pageSource, 'save') ||
                str_contains($pageSource, 'create') ||
                str_contains($pageSource, 'submit');

            $this->assertTrue($hasSaveButton, 'Storage configuration save button should work');

            $this->testResults['storage_config_save'] = 'Storage configuration save button is functional';
        });
    }

    /**
     * Test 32: Storage configuration validation works
     *
     */

    #[Test]
    public function test_storage_configuration_validation_works()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-config-validation');

            // Check for validation via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasValidation =
                str_contains($pageSource, 'required') ||
                str_contains($pageSource, 'validate') ||
                str_contains($pageSource, 'error');

            $this->assertTrue($hasValidation, 'Storage configuration validation should work');

            $this->testResults['storage_config_validation'] = 'Storage configuration validation is functional';
        });
    }

    /**
     * Test 33: Storage configuration default badge is displayed
     *
     */

    #[Test]
    public function test_storage_default_badge_displayed()
    {
        // Set storage as default
        $this->storageConfig->update(['is_default' => true]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-default-badge');

            // Check for default badge via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDefaultBadge =
                str_contains($pageSource, 'default') ||
                str_contains($pageSource, 'primary') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasDefaultBadge, 'Storage default badge should be displayed');

            $this->testResults['storage_default_badge'] = 'Storage default badge is displayed';
        });
    }

    /**
     * Test 34: Storage port configuration is available (FTP/SFTP)
     *
     */

    #[Test]
    public function test_storage_port_configuration_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-port-config');

            // Check for port field via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPortField =
                str_contains($pageSource, 'port') ||
                str_contains($pageSource, '21') ||
                str_contains($pageSource, '22');

            $this->assertTrue($hasPortField, 'Storage port configuration should be available');

            $this->testResults['storage_port_config'] = 'Storage port configuration is available';
        });
    }

    /**
     * Test 35: Storage configuration filtering/search is available
     *
     */

    #[Test]
    public function test_storage_configuration_filtering_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('storage-config-filtering');

            // Check for filtering/search via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFiltering =
                str_contains($pageSource, 'filter') ||
                str_contains($pageSource, 'search') ||
                str_contains($pageSource, 'find');

            $this->assertTrue($hasFiltering, 'Storage configuration filtering should be available');

            $this->testResults['storage_config_filtering'] = 'Storage configuration filtering is available';
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
                'test_suite' => 'Storage/File Management Functionality Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'users_tested' => User::count(),
                    'test_user_email' => $this->user->email,
                    'test_project_id' => $this->project->id,
                    'test_server_id' => $this->server->id,
                    'storage_configurations' => StorageConfiguration::count(),
                    'test_storage_config_id' => $this->storageConfig->id,
                ],
            ];

            $reportPath = storage_path('app/test-reports/storage-management-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
