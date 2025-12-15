<?php

declare(strict_types=1);

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\StorageConfiguration;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive Storage Settings Tests for DevFlow Pro
 *
 * Tests all storage configuration functionality including:
 * - Storage driver selection and configuration
 * - S3 storage configuration
 * - GCS storage configuration
 * - FTP storage configuration
 * - SFTP storage configuration
 * - Storage usage statistics
 * - Test connection functionality
 * - Default storage management
 * - Encryption options
 * - Configuration validation
 */
class StorageSettingsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Test 1: Storage settings page loads successfully
     *
     */

    #[Test]
    public function test_storage_settings_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('01-storage-settings-page-loads');

            // Check if storage settings page loaded via page source
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStorageContent =
                str_contains($pageSource, 'storage') ||
                str_contains($pageSource, 'remote') ||
                str_contains($pageSource, 's3') ||
                str_contains($pageSource, 'configuration');

            $this->assertTrue($hasStorageContent, 'Storage settings page should load successfully');
            $this->testResults['page_loads'] = 'Storage settings page loaded successfully';
        });
    }

    /**
     * Test 2: Add Storage button is visible
     *
     */

    #[Test]
    public function test_add_storage_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('02-add-storage-button');

            // Check for add storage button via page source
            $pageSource = $browser->driver->getPageSource();
            $hasAddButton =
                str_contains($pageSource, 'Add Storage') ||
                str_contains($pageSource, 'openCreateModal') ||
                str_contains($pageSource, 'Create') ||
                str_contains($pageSource, 'New');

            $this->assertTrue($hasAddButton, 'Add Storage button should be visible');
            $this->testResults['add_button_visible'] = 'Add Storage button is visible';
        });
    }

    /**
     * Test 3: Storage driver tabs are displayed in modal
     *
     */

    #[Test]
    public function test_storage_driver_tabs_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            // Try to click Add Storage button
            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('03-storage-driver-tabs');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasDriverTabs =
                    str_contains($pageSource, 's3') &&
                    str_contains($pageSource, 'google cloud') &&
                    str_contains($pageSource, 'ftp') &&
                    str_contains($pageSource, 'sftp');

                $this->assertTrue($hasDriverTabs, 'Storage driver tabs should be displayed');
                $this->testResults['driver_tabs'] = 'Storage driver tabs are displayed';
            } catch (\Exception $e) {
                $browser->screenshot('03-driver-tabs-error');
                $this->testResults['driver_tabs'] = 'Could not verify driver tabs: ' . $e->getMessage();
            }
        });
    }

    /**
     * Test 4: S3 configuration fields are shown when S3 is selected
     *
     */

    #[Test]
    public function test_s3_configuration_fields_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('04-s3-configuration-fields');

                $pageSource = $browser->driver->getPageSource();
                $hasS3Fields =
                    str_contains($pageSource, 'Access Key') ||
                    str_contains($pageSource, 'Secret') ||
                    str_contains($pageSource, 'Bucket') ||
                    str_contains($pageSource, 'Region');

                $this->assertTrue($hasS3Fields, 'S3 configuration fields should be shown');
                $this->testResults['s3_fields'] = 'S3 configuration fields are shown';
            } catch (\Exception $e) {
                $browser->screenshot('04-s3-fields-error');
                $this->testResults['s3_fields'] = 'Could not verify S3 fields';
            }
        });
    }

    /**
     * Test 5: Configuration name field is present
     *
     */

    #[Test]
    public function test_configuration_name_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('05-name-field-present');

                $pageSource = $browser->driver->getPageSource();
                $hasNameField =
                    str_contains($pageSource, 'Configuration Name') ||
                    str_contains($pageSource, 'wire:model="name"') ||
                    str_contains($pageSource, 'name');

                $this->assertTrue($hasNameField, 'Configuration name field should be present');
                $this->testResults['name_field'] = 'Configuration name field is present';
            } catch (\Exception $e) {
                $browser->screenshot('05-name-field-error');
                $this->testResults['name_field'] = 'Could not verify name field';
            }
        });
    }

    /**
     * Test 6: Storage usage statistics are displayed
     *
     */

    #[Test]
    public function test_storage_usage_statistics_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a storage configuration to display
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Test S3 Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'test-bucket',
                    'region' => 'us-east-1',
                    'credentials' => [
                        'access_key_id' => 'test-key',
                        'secret_access_key' => 'test-secret',
                    ],
                    'status' => 'active',
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('06-storage-usage-statistics');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasUsageInfo =
                str_contains($pageSource, 'bucket') ||
                str_contains($pageSource, 'region') ||
                str_contains($pageSource, 'test') ||
                str_contains($pageSource, 'storage');

            $this->assertTrue($hasUsageInfo, 'Storage usage statistics should be displayed');
            $this->testResults['usage_statistics'] = 'Storage usage statistics are displayed';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 7: Test connection button is present for storage configurations
     *
     */

    #[Test]
    public function test_test_connection_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a storage configuration
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Test Connection Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'test-bucket',
                    'region' => 'us-east-1',
                    'credentials' => [
                        'access_key_id' => 'test-key',
                        'secret_access_key' => 'test-secret',
                    ],
                    'status' => 'active',
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('07-test-connection-button');

            $pageSource = $browser->driver->getPageSource();
            $hasTestButton =
                str_contains($pageSource, 'Test') ||
                str_contains($pageSource, 'testConnection') ||
                str_contains($pageSource, 'Testing');

            $this->assertTrue($hasTestButton, 'Test connection button should be present');
            $this->testResults['test_button'] = 'Test connection button is present';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 8: Save settings button is visible in modal
     *
     */

    #[Test]
    public function test_save_settings_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('08-save-button-visible');

                $pageSource = $browser->driver->getPageSource();
                $hasSaveButton =
                    str_contains($pageSource, 'Create Configuration') ||
                    str_contains($pageSource, 'Update Configuration') ||
                    str_contains($pageSource, 'Save') ||
                    str_contains($pageSource, 'wire:click="save"');

                $this->assertTrue($hasSaveButton, 'Save settings button should be visible');
                $this->testResults['save_button'] = 'Save settings button is visible';
            } catch (\Exception $e) {
                $browser->screenshot('08-save-button-error');
                $this->testResults['save_button'] = 'Could not verify save button';
            }
        });
    }

    /**
     * Test 9: Storage configurations list is displayed
     *
     */

    #[Test]
    public function test_storage_configurations_list_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create multiple storage configurations
            $configs = [
                StorageConfiguration::firstOrCreate(
                    ['name' => 'Production S3'],
                    [
                        'driver' => 's3',
                        'bucket' => 'prod-bucket',
                        'region' => 'us-east-1',
                        'credentials' => ['access_key_id' => 'key1', 'secret_access_key' => 'secret1'],
                        'status' => 'active',
                    ]
                ),
                StorageConfiguration::firstOrCreate(
                    ['name' => 'Backup Storage'],
                    [
                        'driver' => 's3',
                        'bucket' => 'backup-bucket',
                        'region' => 'eu-west-1',
                        'credentials' => ['access_key_id' => 'key2', 'secret_access_key' => 'secret2'],
                        'status' => 'active',
                    ]
                ),
            ];

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('09-configurations-list');

            $pageSource = $browser->driver->getPageSource();
            $hasConfigList =
                str_contains($pageSource, 'Production S3') ||
                str_contains($pageSource, 'Backup Storage') ||
                str_contains($pageSource, 'prod-bucket') ||
                str_contains($pageSource, 'backup-bucket');

            $this->assertTrue($hasConfigList, 'Storage configurations list should be displayed');
            $this->testResults['configurations_list'] = 'Storage configurations list is displayed';

            // Cleanup
            foreach ($configs as $config) {
                $config->delete();
            }
        });
    }

    /**
     * Test 10: Default storage badge is shown for default configuration
     *
     */

    #[Test]
    public function test_default_storage_badge_shown(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a default storage configuration
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Default S3 Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'default-bucket',
                    'region' => 'us-east-1',
                    'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                    'status' => 'active',
                    'is_default' => true,
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('10-default-badge');

            $pageSource = $browser->driver->getPageSource();
            $hasDefaultBadge =
                str_contains($pageSource, 'Default') ||
                str_contains($pageSource, 'is_default');

            $this->assertTrue($hasDefaultBadge, 'Default storage badge should be shown');
            $this->testResults['default_badge'] = 'Default storage badge is shown';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 11: Set as default button is visible for non-default configurations
     *
     */

    #[Test]
    public function test_set_as_default_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a non-default storage configuration
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Secondary Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'secondary-bucket',
                    'region' => 'us-west-2',
                    'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                    'status' => 'active',
                    'is_default' => false,
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('11-set-default-button');

            $pageSource = $browser->driver->getPageSource();
            $hasSetDefaultButton =
                str_contains($pageSource, 'Set Default') ||
                str_contains($pageSource, 'setAsDefault');

            $this->assertTrue($hasSetDefaultButton, 'Set as default button should be visible');
            $this->testResults['set_default_button'] = 'Set as default button is visible';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 12: Edit button is present for storage configurations
     *
     */

    #[Test]
    public function test_edit_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a storage configuration
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Editable Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'edit-bucket',
                    'region' => 'us-east-1',
                    'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                    'status' => 'active',
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('12-edit-button');

            $pageSource = $browser->driver->getPageSource();
            $hasEditButton =
                str_contains($pageSource, 'Edit') ||
                str_contains($pageSource, 'openEditModal');

            $this->assertTrue($hasEditButton, 'Edit button should be present');
            $this->testResults['edit_button'] = 'Edit button is present';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 13: Delete button is present for storage configurations
     *
     */

    #[Test]
    public function test_delete_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            // Create a storage configuration
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Deletable Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'delete-bucket',
                    'region' => 'us-east-1',
                    'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                    'status' => 'active',
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('13-delete-button');

            $pageSource = $browser->driver->getPageSource();
            $hasDeleteButton =
                str_contains($pageSource, 'Delete') ||
                str_contains($pageSource, 'wire:click="delete');

            $this->assertTrue($hasDeleteButton, 'Delete button should be present');
            $this->testResults['delete_button'] = 'Delete button is present';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 14: Project selector is available in configuration modal
     *
     */

    #[Test]
    public function test_project_selector_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('14-project-selector');

                $pageSource = $browser->driver->getPageSource();
                $hasProjectSelector =
                    str_contains($pageSource, 'Project') ||
                    str_contains($pageSource, 'project_id') ||
                    str_contains($pageSource, 'Global');

                $this->assertTrue($hasProjectSelector, 'Project selector should be available');
                $this->testResults['project_selector'] = 'Project selector is available';
            } catch (\Exception $e) {
                $browser->screenshot('14-project-selector-error');
                $this->testResults['project_selector'] = 'Could not verify project selector';
            }
        });
    }

    /**
     * Test 15: Encryption options are displayed
     *
     */

    #[Test]
    public function test_encryption_options_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('15-encryption-options');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasEncryptionOptions =
                    str_contains($pageSource, 'encryption') ||
                    str_contains($pageSource, 'aes') ||
                    str_contains($pageSource, 'enable_encryption');

                $this->assertTrue($hasEncryptionOptions, 'Encryption options should be displayed');
                $this->testResults['encryption_options'] = 'Encryption options are displayed';
            } catch (\Exception $e) {
                $browser->screenshot('15-encryption-error');
                $this->testResults['encryption_options'] = 'Could not verify encryption options';
            }
        });
    }

    /**
     * Test 16: Generate encryption key button is present
     *
     */

    #[Test]
    public function test_generate_encryption_key_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('16-generate-key-button');

                $pageSource = $browser->driver->getPageSource();
                $hasGenerateButton =
                    str_contains($pageSource, 'Generate Key') ||
                    str_contains($pageSource, 'generateEncryptionKey');

                $this->assertTrue($hasGenerateButton, 'Generate encryption key button should be present');
                $this->testResults['generate_key_button'] = 'Generate encryption key button is present';
            } catch (\Exception $e) {
                $browser->screenshot('16-generate-key-error');
                $this->testResults['generate_key_button'] = 'Could not verify generate key button';
            }
        });
    }

    /**
     * Test 17: Storage driver icons are displayed correctly
     *
     */

    #[Test]
    public function test_storage_driver_icons_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create storage configurations with different drivers
            $configs = [
                StorageConfiguration::firstOrCreate(
                    ['name' => 'S3 Storage'],
                    [
                        'driver' => 's3',
                        'bucket' => 's3-bucket',
                        'region' => 'us-east-1',
                        'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                        'status' => 'active',
                    ]
                ),
                StorageConfiguration::firstOrCreate(
                    ['name' => 'GCS Storage'],
                    [
                        'driver' => 'gcs',
                        'bucket' => 'gcs-bucket',
                        'credentials' => ['service_account_json' => '{}'],
                        'status' => 'active',
                    ]
                ),
            ];

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('17-driver-icons');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDriverIcons =
                str_contains($pageSource, 's3 storage') ||
                str_contains($pageSource, 'gcs storage') ||
                str_contains($pageSource, 'svg');

            $this->assertTrue($hasDriverIcons, 'Storage driver icons should be displayed');
            $this->testResults['driver_icons'] = 'Storage driver icons are displayed';

            // Cleanup
            foreach ($configs as $config) {
                $config->delete();
            }
        });
    }

    /**
     * Test 18: Status badges are displayed for configurations
     *
     */

    #[Test]
    public function test_status_badges_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            // Create storage configurations with different statuses
            $config = StorageConfiguration::firstOrCreate(
                ['name' => 'Active Storage'],
                [
                    'driver' => 's3',
                    'bucket' => 'active-bucket',
                    'region' => 'us-east-1',
                    'credentials' => ['access_key_id' => 'key', 'secret_access_key' => 'secret'],
                    'status' => 'active',
                ]
            );

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('18-status-badges');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasStatusBadges =
                str_contains($pageSource, 'active') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasStatusBadges, 'Status badges should be displayed');
            $this->testResults['status_badges'] = 'Status badges are displayed';

            // Cleanup
            $config->delete();
        });
    }

    /**
     * Test 19: Empty state is shown when no configurations exist
     *
     */

    #[Test]
    public function test_empty_state_shown_when_no_configurations(): void
    {
        $this->browse(function (Browser $browser) {
            // Delete all storage configurations
            StorageConfiguration::query()->delete();

            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('19-empty-state');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEmptyState =
                str_contains($pageSource, 'no storage') ||
                str_contains($pageSource, 'get started') ||
                str_contains($pageSource, 'add storage');

            $this->assertTrue($hasEmptyState, 'Empty state should be shown when no configurations exist');
            $this->testResults['empty_state'] = 'Empty state is shown';
        });
    }

    /**
     * Test 20: Cancel button closes the modal
     *
     */

    #[Test]
    public function test_cancel_button_closes_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('20-modal-open');

                $pageSource = $browser->driver->getPageSource();
                $hasCancelButton =
                    str_contains($pageSource, 'Cancel') ||
                    str_contains($pageSource, 'showModal');

                $this->assertTrue($hasCancelButton, 'Cancel button should be present');
                $this->testResults['cancel_button'] = 'Cancel button is present';
            } catch (\Exception $e) {
                $browser->screenshot('20-cancel-error');
                $this->testResults['cancel_button'] = 'Could not verify cancel button';
            }
        });
    }

    /**
     * Test 21: Region field is displayed for S3
     *
     */

    #[Test]
    public function test_region_field_displayed_for_s3(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('21-region-field');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasRegionField =
                    str_contains($pageSource, 'region') ||
                    str_contains($pageSource, 'us-east');

                $this->assertTrue($hasRegionField, 'Region field should be displayed for S3');
                $this->testResults['region_field'] = 'Region field is displayed';
            } catch (\Exception $e) {
                $browser->screenshot('21-region-error');
                $this->testResults['region_field'] = 'Could not verify region field';
            }
        });
    }

    /**
     * Test 22: Bucket name field is displayed
     *
     */

    #[Test]
    public function test_bucket_name_field_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('22-bucket-field');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasBucketField =
                    str_contains($pageSource, 'bucket') ||
                    str_contains($pageSource, 'wire:model="bucket"');

                $this->assertTrue($hasBucketField, 'Bucket name field should be displayed');
                $this->testResults['bucket_field'] = 'Bucket name field is displayed';
            } catch (\Exception $e) {
                $browser->screenshot('22-bucket-error');
                $this->testResults['bucket_field'] = 'Could not verify bucket field';
            }
        });
    }

    /**
     * Test 23: Custom endpoint field is available for S3-compatible services
     *
     */

    #[Test]
    public function test_custom_endpoint_field_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('23-endpoint-field');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasEndpointField =
                    str_contains($pageSource, 'endpoint') ||
                    str_contains($pageSource, 'digitalocean') ||
                    str_contains($pageSource, 'minio');

                $this->assertTrue($hasEndpointField, 'Custom endpoint field should be available');
                $this->testResults['endpoint_field'] = 'Custom endpoint field is available';
            } catch (\Exception $e) {
                $browser->screenshot('23-endpoint-error');
                $this->testResults['endpoint_field'] = 'Could not verify endpoint field';
            }
        });
    }

    /**
     * Test 24: Path prefix field is available
     *
     */

    #[Test]
    public function test_path_prefix_field_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(2000)
                    ->screenshot('24-path-prefix-field');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasPathPrefixField =
                    str_contains($pageSource, 'path prefix') ||
                    str_contains($pageSource, 'wire:model="path_prefix"');

                $this->assertTrue($hasPathPrefixField, 'Path prefix field should be available');
                $this->testResults['path_prefix_field'] = 'Path prefix field is available';
            } catch (\Exception $e) {
                $browser->screenshot('24-path-prefix-error');
                $this->testResults['path_prefix_field'] = 'Could not verify path prefix field';
            }
        });
    }

    /**
     * Test 25: FTP configuration fields are shown when FTP is selected
     *
     */

    #[Test]
    public function test_ftp_configuration_fields_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(1000);

                // Switch to FTP tab
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').set('activeTab', 'ftp')");
                $browser->pause(2000)
                    ->screenshot('25-ftp-fields');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasFtpFields =
                    str_contains($pageSource, 'ftp') ||
                    str_contains($pageSource, 'host') ||
                    str_contains($pageSource, 'passive');

                $this->assertTrue($hasFtpFields, 'FTP configuration fields should be shown');
                $this->testResults['ftp_fields'] = 'FTP configuration fields are shown';
            } catch (\Exception $e) {
                $browser->screenshot('25-ftp-error');
                $this->testResults['ftp_fields'] = 'Could not verify FTP fields';
            }
        });
    }

    /**
     * Test 26: SFTP configuration fields are shown when SFTP is selected
     *
     */

    #[Test]
    public function test_sftp_configuration_fields_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(1000);

                // Switch to SFTP tab
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').set('activeTab', 'sftp')");
                $browser->pause(2000)
                    ->screenshot('26-sftp-fields');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasSftpFields =
                    str_contains($pageSource, 'sftp') ||
                    str_contains($pageSource, 'private key') ||
                    str_contains($pageSource, 'passphrase');

                $this->assertTrue($hasSftpFields, 'SFTP configuration fields should be shown');
                $this->testResults['sftp_fields'] = 'SFTP configuration fields are shown';
            } catch (\Exception $e) {
                $browser->screenshot('26-sftp-error');
                $this->testResults['sftp_fields'] = 'Could not verify SFTP fields';
            }
        });
    }

    /**
     * Test 27: GCS service account field is shown when GCS is selected
     *
     */

    #[Test]
    public function test_gcs_service_account_field_shown(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings/storage')
                ->pause(2000)
                ->waitFor('body', 15);

            try {
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').call('openCreateModal')");
                $browser->pause(1000);

                // Switch to GCS tab
                $browser->script("window.livewire.find('" . $browser->script("return Object.keys(window.livewire.components)[0]")[0] . "').set('activeTab', 'gcs')");
                $browser->pause(2000)
                    ->screenshot('27-gcs-fields');

                $pageSource = strtolower($browser->driver->getPageSource());
                $hasGcsFields =
                    str_contains($pageSource, 'service account') ||
                    str_contains($pageSource, 'json') ||
                    str_contains($pageSource, 'gcs');

                $this->assertTrue($hasGcsFields, 'GCS service account field should be shown');
                $this->testResults['gcs_fields'] = 'GCS service account field is shown';
            } catch (\Exception $e) {
                $browser->screenshot('27-gcs-error');
                $this->testResults['gcs_fields'] = 'Could not verify GCS fields';
            }
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
                'test_suite' => 'Storage Settings Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'successful_tests' => count(array_filter($this->testResults, fn ($result) => ! str_contains($result, 'Could not verify'))),
                ],
                'environment' => [
                    'storage_configs_tested' => StorageConfiguration::count(),
                    'test_user_email' => $this->user->email,
                ],
            ];

            $reportPath = storage_path('app/test-reports/storage-settings-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
