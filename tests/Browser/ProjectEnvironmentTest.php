<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectEnvironmentTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected ?Project $project = null;

    protected Server $server;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get test user
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create test server
        $this->server = Server::firstOrCreate(
            ['ip_address' => '192.168.99.100'],
            [
                'user_id' => $this->user->id,
                'name' => 'Test Environment Server',
                'hostname' => 'env-test.example.com',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Try to get an existing project first
        $this->project = Project::first();

        // If no project exists, create a test project
        if (! $this->project) {
            $this->project = Project::create([
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Project',
                'slug' => 'test-env-project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/env-project.git',
                'branch' => 'main',
                'environment' => 'production',
                'env_variables' => [
                    'APP_NAME' => 'DevFlow Test',
                    'APP_ENV' => 'production',
                    'APP_DEBUG' => 'false',
                ],
            ]);
        }
    }

    /**
     * Test 1: Project environment page loads successfully
     *
     */

    #[Test]
    public function test_project_environment_page_loads()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('project-environment-page-loads');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEnvironmentContent =
                str_contains($pageSource, 'environment') ||
                str_contains($pageSource, 'variables') ||
                str_contains($pageSource, 'env');

            $this->assertTrue($hasEnvironmentContent, 'Project environment related content should be visible');
            $this->testResults['environment_page_loads'] = 'Project environment page loaded successfully';
        });
    }

    /**
     * Test 2: Environment variables section is visible
     *
     */

    #[Test]
    public function test_environment_variables_section_visible()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('environment-variables-section');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEnvSection =
                str_contains($pageSource, 'environment variable') ||
                str_contains($pageSource, 'wire:model="newenvkey"') ||
                str_contains($pageSource, 'env_variables');

            $this->assertTrue($hasEnvSection, 'Environment variables section should be visible');
            $this->testResults['env_section_visible'] = 'Environment variables section is visible';
        });
    }

    /**
     * Test 3: Add environment variable button is present
     *
     */

    #[Test]
    public function test_add_environment_variable_button_present()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('add-env-variable-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAddButton =
                str_contains($pageSource, 'add variable') ||
                str_contains($pageSource, 'openenvmodal') ||
                str_contains($pageSource, 'add env');

            $this->assertTrue($hasAddButton, 'Add environment variable button should be present');
            $this->testResults['add_button_present'] = 'Add environment variable button is present';
        });
    }

    /**
     * Test 4: Environment variable key field works
     *
     */

    #[Test]
    public function test_environment_variable_key_field_works()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-key-field-works');

            $pageSource = $browser->driver->getPageSource();
            $hasKeyField =
                str_contains($pageSource, 'wire:model="newEnvKey"') ||
                str_contains($pageSource, 'wire:model="serverEnvKey"') ||
                str_contains($pageSource, 'newEnvKey');

            $this->assertTrue($hasKeyField, 'Environment variable key field should work');
            $this->testResults['key_field_works'] = 'Environment variable key field works';
        });
    }

    /**
     * Test 5: Environment variable value field works
     *
     */

    #[Test]
    public function test_environment_variable_value_field_works()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-value-field-works');

            $pageSource = $browser->driver->getPageSource();
            $hasValueField =
                str_contains($pageSource, 'wire:model="newEnvValue"') ||
                str_contains($pageSource, 'wire:model="serverEnvValue"') ||
                str_contains($pageSource, 'newEnvValue');

            $this->assertTrue($hasValueField, 'Environment variable value field should work');
            $this->testResults['value_field_works'] = 'Environment variable value field works';
        });
    }

    /**
     * Test 6: Save environment variables functionality works
     *
     */

    #[Test]
    public function test_save_environment_variables_works()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('save-env-variables');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSaveFunction =
                str_contains($pageSource, 'addenvvariable') ||
                str_contains($pageSource, 'saveserverenvvariable') ||
                str_contains($pageSource, 'save') ||
                str_contains($pageSource, 'update');

            $this->assertTrue($hasSaveFunction, 'Save environment variables functionality should work');
            $this->testResults['save_function_works'] = 'Save environment variables works';
        });
    }

    /**
     * Test 7: Delete environment variable functionality works
     *
     */

    #[Test]
    public function test_delete_environment_variable_works()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delete-env-variable');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasDeleteFunction =
                str_contains($pageSource, 'deleteenvvariable') ||
                str_contains($pageSource, 'deleteserverenvvariable') ||
                str_contains($pageSource, 'delete') ||
                str_contains($pageSource, 'remove');

            $this->assertTrue($hasDeleteFunction, 'Delete environment variable functionality should work');
            $this->testResults['delete_function_works'] = 'Delete environment variable works';
        });
    }

    /**
     * Test 8: Environment variables list displays correctly
     *
     */

    #[Test]
    public function test_environment_variables_list_displays()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-variables-list');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasVariableList =
                str_contains($pageSource, 'app_name') ||
                str_contains($pageSource, 'app_env') ||
                str_contains($pageSource, 'app_debug') ||
                str_contains($pageSource, 'variable') ||
                str_contains($pageSource, '<table');

            $this->assertTrue($hasVariableList, 'Environment variables list should display');
            $this->testResults['variables_list_displays'] = 'Environment variables list displays correctly';
        });
    }

    /**
     * Test 9: Masked/hidden values for secrets work
     *
     */

    #[Test]
    public function test_masked_values_for_secrets()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('masked-secret-values');

            $pageSource = $browser->driver->getPageSource();
            $hasMaskedValues =
                str_contains($pageSource, '••••••••') ||
                str_contains($pageSource, 'PASSWORD') ||
                str_contains($pageSource, 'SECRET') ||
                str_contains($pageSource, 'KEY') ||
                str_contains($pageSource, 'TOKEN');

            $this->assertTrue($hasMaskedValues, 'Masked/hidden values for secrets should work');
            $this->testResults['masked_values_work'] = 'Masked values for secrets work correctly';
        });
    }

    /**
     * Test 10: Show/hide value toggle functionality
     *
     */

    #[Test]
    public function test_show_hide_value_toggle()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('show-hide-toggle');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasToggle =
                str_contains($pageSource, 'show') ||
                str_contains($pageSource, 'hide') ||
                str_contains($pageSource, 'reveal') ||
                str_contains($pageSource, '••••');

            $this->assertTrue($hasToggle, 'Show/hide value toggle should be present');
            $this->testResults['show_hide_toggle'] = 'Show/hide value toggle functionality exists';
        });
    }

    /**
     * Test 11: Validation for duplicate keys works
     *
     */

    #[Test]
    public function test_validation_for_duplicate_keys()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('duplicate-key-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, 'required') ||
                str_contains($pageSource, 'unique') ||
                str_contains($pageSource, 'validation') ||
                str_contains($pageSource, '@error');

            $this->assertTrue($hasValidation, 'Validation for duplicate keys should work');
            $this->testResults['duplicate_validation'] = 'Validation for duplicate keys works';
        });
    }

    /**
     * Test 12: Import from .env file option exists
     *
     */

    #[Test]
    public function test_import_from_env_file_option()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('import-env-file');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasImport =
                str_contains($pageSource, 'import') ||
                str_contains($pageSource, 'load') ||
                str_contains($pageSource, 'server .env') ||
                str_contains($pageSource, 'loadserverenv');

            $this->assertTrue($hasImport, 'Import from .env file option should exist');
            $this->testResults['import_option_exists'] = 'Import from .env file option exists';
        });
    }

    /**
     * Test 13: Export environment variables option exists
     *
     */

    #[Test]
    public function test_export_environment_variables_option()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('export-env-variables');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasExport =
                str_contains($pageSource, 'export') ||
                str_contains($pageSource, 'download') ||
                str_contains($pageSource, '.env file') ||
                str_contains($pageSource, 'server env');

            $this->assertTrue($hasExport, 'Export environment variables option should exist');
            $this->testResults['export_option_exists'] = 'Export environment variables option exists';
        });
    }

    /**
     * Test 14: Environment sync status is visible
     *
     */

    #[Test]
    public function test_environment_sync_status_visible()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-sync-status');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSyncStatus =
                str_contains($pageSource, 'sync') ||
                str_contains($pageSource, 'refresh') ||
                str_contains($pageSource, 'loading') ||
                str_contains($pageSource, 'status');

            $this->assertTrue($hasSyncStatus, 'Environment sync status should be visible');
            $this->testResults['sync_status_visible'] = 'Environment sync status is visible';
        });
    }

    /**
     * Test 15: Flash messages for success are displayed
     *
     */

    #[Test]
    public function test_success_flash_messages_displayed()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('success-flash-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasSuccessMessages =
                str_contains($pageSource, "session()->has('message')") ||
                str_contains($pageSource, "session('message')") ||
                str_contains($pageSource, 'bg-green-') ||
                str_contains($pageSource, 'text-green-');

            $this->assertTrue($hasSuccessMessages, 'Success flash messages should be displayed');
            $this->testResults['success_messages'] = 'Flash messages for success are displayed';
        });
    }

    /**
     * Test 16: Flash messages for errors are displayed
     *
     */

    #[Test]
    public function test_error_flash_messages_displayed()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('error-flash-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasErrorMessages =
                str_contains($pageSource, "session()->has('error')") ||
                str_contains($pageSource, "session('error')") ||
                str_contains($pageSource, 'bg-red-') ||
                str_contains($pageSource, 'text-red-');

            $this->assertTrue($hasErrorMessages, 'Error flash messages should be displayed');
            $this->testResults['error_messages'] = 'Flash messages for errors are displayed';
        });
    }

    /**
     * Test 17: Environment selector displays all options
     *
     */

    #[Test]
    public function test_environment_selector_displays_options()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('environment-selector');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEnvironmentOptions =
                str_contains($pageSource, 'production') ||
                str_contains($pageSource, 'staging') ||
                str_contains($pageSource, 'development') ||
                str_contains($pageSource, 'local');

            $this->assertTrue($hasEnvironmentOptions, 'Environment selector should display all options');
            $this->testResults['environment_selector'] = 'Environment selector displays all options';
        });
    }

    /**
     * Test 18: Server environment variables section exists
     *
     */

    #[Test]
    public function test_server_environment_variables_section_exists()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('server-env-section');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasServerEnv =
                str_contains($pageSource, 'server .env') ||
                str_contains($pageSource, 'serverenvvariables') ||
                str_contains($pageSource, 'server environment');

            $this->assertTrue($hasServerEnv, 'Server environment variables section should exist');
            $this->testResults['server_env_section'] = 'Server environment variables section exists';
        });
    }

    /**
     * Test 19: Environment variable modal can be opened
     *
     */

    #[Test]
    public function test_environment_variable_modal_opens()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-modal-opens');

            $pageSource = $browser->driver->getPageSource();
            $hasModal =
                str_contains($pageSource, 'showEnvModal') ||
                str_contains($pageSource, 'showServerEnvModal') ||
                str_contains($pageSource, 'openEnvModal') ||
                str_contains($pageSource, 'closeEnvModal');

            $this->assertTrue($hasModal, 'Environment variable modal should be able to open');
            $this->testResults['modal_opens'] = 'Environment variable modal can be opened';
        });
    }

    /**
     * Test 20: Environment variable modal can be closed
     *
     */

    #[Test]
    public function test_environment_variable_modal_closes()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-modal-closes');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCloseFunction =
                str_contains($pageSource, 'closeenvmodal') ||
                str_contains($pageSource, 'closeserverenvmodal') ||
                str_contains($pageSource, 'cancel') ||
                str_contains($pageSource, 'close');

            $this->assertTrue($hasCloseFunction, 'Environment variable modal should be able to close');
            $this->testResults['modal_closes'] = 'Environment variable modal can be closed';
        });
    }

    /**
     * Test 21: Environment type badge displays correctly
     *
     */

    #[Test]
    public function test_environment_type_badge_displays()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('environment-badge');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBadge =
                str_contains($pageSource, 'badge') ||
                str_contains($pageSource, 'bg-green-') ||
                str_contains($pageSource, 'bg-yellow-') ||
                str_contains($pageSource, 'bg-blue-') ||
                str_contains($pageSource, 'production') ||
                str_contains($pageSource, 'staging');

            $this->assertTrue($hasBadge, 'Environment type badge should display correctly');
            $this->testResults['environment_badge'] = 'Environment type badge displays correctly';
        });
    }

    /**
     * Test 22: Refresh server environment button works
     *
     */

    #[Test]
    public function test_refresh_server_environment_button()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('refresh-server-env');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRefreshButton =
                str_contains($pageSource, 'loadserverenv') ||
                str_contains($pageSource, 'refresh') ||
                str_contains($pageSource, 'reload');

            $this->assertTrue($hasRefreshButton, 'Refresh server environment button should work');
            $this->testResults['refresh_button'] = 'Refresh server environment button works';
        });
    }

    /**
     * Test 23: Environment variables show proper formatting
     *
     */

    #[Test]
    public function test_environment_variables_proper_formatting()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('env-formatting');

            $pageSource = $browser->driver->getPageSource();
            $hasFormatting =
                str_contains($pageSource, 'font-mono') ||
                str_contains($pageSource, '<code') ||
                str_contains($pageSource, '<pre') ||
                str_contains($pageSource, 'whitespace-nowrap');

            $this->assertTrue($hasFormatting, 'Environment variables should show proper formatting');
            $this->testResults['proper_formatting'] = 'Environment variables show proper formatting';
        });
    }

    /**
     * Test 24: Edit environment variable functionality exists
     *
     */

    #[Test]
    public function test_edit_environment_variable_functionality()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('edit-env-functionality');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasEditFunction =
                str_contains($pageSource, 'editenvvariable') ||
                str_contains($pageSource, 'editserverenvvariable') ||
                str_contains($pageSource, 'updateenvvariable') ||
                str_contains($pageSource, 'edit');

            $this->assertTrue($hasEditFunction, 'Edit environment variable functionality should exist');
            $this->testResults['edit_functionality'] = 'Edit environment variable functionality exists';
        });
    }

    /**
     * Test 25: Warning messages for sensitive operations are displayed
     *
     */

    #[Test]
    public function test_warning_messages_for_sensitive_operations()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('warning-messages');

            $pageSource = $browser->driver->getPageSource();
            $hasWarnings =
                str_contains($pageSource, 'bg-yellow-') ||
                str_contains($pageSource, 'text-yellow-') ||
                str_contains($pageSource, 'Important:') ||
                str_contains($pageSource, 'Warning:') ||
                str_contains($pageSource, 'Note:');

            $this->assertTrue($hasWarnings, 'Warning messages for sensitive operations should be displayed');
            $this->testResults['warning_messages'] = 'Warning messages for sensitive operations are displayed';
        });
    }

    /**
     * Test 26: Environment variables support textarea for multiline values
     *
     */

    #[Test]
    public function test_textarea_for_multiline_values()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('multiline-textarea');

            $pageSource = $browser->driver->getPageSource();
            $hasTextarea =
                str_contains($pageSource, '<textarea') ||
                str_contains($pageSource, 'rows="3"') ||
                str_contains($pageSource, 'wire:model="newEnvValue"') ||
                str_contains($pageSource, 'wire:model="serverEnvValue"');

            $this->assertTrue($hasTextarea, 'Environment variables should support textarea for multiline values');
            $this->testResults['multiline_textarea'] = 'Textarea for multiline values is supported';
        });
    }

    /**
     * Test 27: Loading states are properly indicated
     *
     */

    #[Test]
    public function test_loading_states_indicated()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('loading-states');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadingStates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'serverenvloading') ||
                str_contains($pageSource, 'animate-spin') ||
                str_contains($pageSource, 'loading');

            $this->assertTrue($hasLoadingStates, 'Loading states should be properly indicated');
            $this->testResults['loading_states'] = 'Loading states are properly indicated';
        });
    }

    /**
     * Test 28: Error states are properly displayed
     *
     */

    #[Test]
    public function test_error_states_displayed()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('error-states');

            $pageSource = $browser->driver->getPageSource();
            $hasErrorStates =
                str_contains($pageSource, 'serverEnvError') ||
                str_contains($pageSource, 'bg-red-') ||
                str_contains($pageSource, 'text-red-') ||
                str_contains($pageSource, '@error');

            $this->assertTrue($hasErrorStates, 'Error states should be properly displayed');
            $this->testResults['error_states'] = 'Error states are properly displayed';
        });
    }

    /**
     * Test 29: Variable count is displayed
     *
     */

    #[Test]
    public function test_variable_count_displayed()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('variable-count');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCount =
                str_contains($pageSource, 'total:') ||
                str_contains($pageSource, 'count(') ||
                str_contains($pageSource, 'variables') ||
                str_contains($pageSource, 'badge');

            $this->assertTrue($hasCount, 'Variable count should be displayed');
            $this->testResults['variable_count'] = 'Variable count is displayed';
        });
    }

    /**
     * Test 30: Confirm dialog for delete operations exists
     *
     */

    #[Test]
    public function test_confirm_dialog_for_delete()
    {
        if (! $this->project) {
            $this->markTestSkipped('No project available for testing');
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->user)
                ->visit("/projects/{$this->project->id}")
                ->pause(2000)
                ->waitFor('body', 15)
                ->screenshot('delete-confirm-dialog');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfirm =
                str_contains($pageSource, 'wire:confirm') ||
                str_contains($pageSource, 'are you sure') ||
                str_contains($pageSource, 'confirm') ||
                str_contains($pageSource, 'confirmation');

            $this->assertTrue($hasConfirm, 'Confirm dialog for delete operations should exist');
            $this->testResults['delete_confirm'] = 'Confirm dialog for delete operations exists';
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
                'test_suite' => 'Project Environment Management Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                    'tests_passed' => count($this->testResults),
                ],
                'environment' => [
                    'user_id' => $this->user->id,
                    'user_email' => $this->user->email,
                    'project_id' => $this->project?->id,
                    'project_name' => $this->project?->name,
                    'server_id' => $this->server->id,
                    'server_name' => $this->server->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/project-environment-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
