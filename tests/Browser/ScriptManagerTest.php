<?php

namespace Tests\Browser;

use App\Models\DeploymentScript;
use App\Models\DeploymentScriptRun;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ScriptManagerTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

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

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'prod.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Production Server',
                'ip_address' => '192.168.1.100',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-project-scripts'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Scripts',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
            ]
        );
    }

    /**
     * Test 1: Script manager page loads successfully
     */
    public function test_script_manager_page_loads_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Script Manager')
                ->assertSee('Create and manage deployment scripts')
                ->screenshot('script-manager-page-loads');
        });
    }

    /**
     * Test 2: Script list is displayed
     */
    public function test_script_list_is_displayed(): void
    {
        // Create test scripts
        DeploymentScript::firstOrCreate(
            ['name' => 'Test List Script 1'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Test 1"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        DeploymentScript::firstOrCreate(
            ['name' => 'Test List Script 2'],
            [
                'language' => 'python',
                'script' => '#!/usr/bin/env python3\nprint("Test 2")',
                'timeout' => 600,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Test List Script 1')
                ->assertSee('Test List Script 2')
                ->assertSee('bash')
                ->assertSee('python')
                ->screenshot('script-list-displayed');
        });
    }

    /**
     * Test 3: Create script button is visible
     */
    public function test_create_script_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Create Script')
                ->assertPresent('button[wire\\:click="createScript"]')
                ->screenshot('create-script-button-visible');
        });
    }

    /**
     * Test 4: Create script modal opens
     */
    public function test_create_script_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Create Deployment Script')
                ->assertSee('Script Name')
                ->screenshot('create-script-modal-opens');
        });
    }

    /**
     * Test 5: Script name field is present
     */
    public function test_script_name_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Script Name')
                ->assertPresent('input[wire\\:model="name"]')
                ->screenshot('script-name-field-present');
        });
    }

    /**
     * Test 6: Script content editor is present
     */
    public function test_script_content_editor_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Script Content')
                ->assertPresent('textarea[wire\\:model="content"]')
                ->screenshot('script-content-editor-present');
        });
    }

    /**
     * Test 7: Script type dropdown is present
     */
    public function test_script_type_dropdown_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Type')
                ->assertPresent('select[wire\\:model="type"]')
                ->assertSeeIn('select[wire\\:model="type"]', 'Deployment')
                ->assertSeeIn('select[wire\\:model="type"]', 'Rollback')
                ->assertSeeIn('select[wire\\:model="type"]', 'Maintenance')
                ->screenshot('script-type-dropdown-present');
        });
    }

    /**
     * Test 8: Create script form submits
     */
    public function test_create_script_form_submits(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->type('input[wire\\:model="name"]', 'Test Submit Script')
                ->type('textarea[wire\\:model="content"]', '#!/bin/bash\necho "Submit test"')
                ->press('Create Script')
                ->pause(1000)
                ->waitForText('Script created successfully', 5)
                ->assertSee('Script created successfully')
                ->screenshot('create-script-form-submits');
        });
    }

    /**
     * Test 9: Edit script button works
     */
    public function test_edit_script_button_works(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Editable Test Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Edit me"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Editable Test Script')
                ->click('button[wire\\:click="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitForText('Edit Script', 5)
                ->assertSee('Edit Script')
                ->assertInputValue('input[wire\\:model="name"]', 'Editable Test Script')
                ->screenshot('edit-script-button-works');
        });
    }

    /**
     * Test 10: Delete script button is visible
     */
    public function test_delete_script_button_visible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Deletable Test Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Delete me"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Deletable Test Script')
                ->assertPresent('button[wire\\:click*="deleteScript"]')
                ->screenshot('delete-script-button-visible');
        });
    }

    /**
     * Test 11: Run script button is visible
     */
    public function test_run_script_button_visible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Runnable Test Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Run me"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Runnable Test Script')
                ->assertPresent('button[wire\\:click*="testScript"]')
                ->assertSee('Test')
                ->screenshot('run-script-button-visible');
        });
    }

    /**
     * Test 12: Script run history is accessible
     */
    public function test_script_run_history_accessible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'History Test Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "History"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        // Create script runs
        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
                'started_at' => now()->subHours(2),
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'output' => 'History test output',
                'finished_at' => now()->subHours(2)->addMinutes(2),
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('History Test Script')
                ->click('button[wire\\:click="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitForText('Test Script', 5)
                ->assertSee('Test Script')
                ->screenshot('script-run-history-accessible');
        });
    }

    /**
     * Test 13: Script status is shown
     */
    public function test_script_status_shown(): void
    {
        $enabledScript = DeploymentScript::firstOrCreate(
            ['name' => 'Enabled Status Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Enabled"',
                'timeout' => 300,
                'is_template' => false,
                'enabled' => true,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Enabled Status Script')
                ->assertPresent('button[wire\\:click*="toggleScript"]')
                ->screenshot('script-status-shown');
        });
    }

    /**
     * Test 14: Search scripts works (if implemented)
     */
    public function test_search_scripts_works(): void
    {
        DeploymentScript::firstOrCreate(
            ['name' => 'Laravel Deploy Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\nphp artisan migrate',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        DeploymentScript::firstOrCreate(
            ['name' => 'Node Build Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\nnpm run build',
                'timeout' => 600,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Laravel Deploy Script')
                ->assertSee('Node Build Script')
                ->screenshot('search-scripts-functionality');
        });
    }

    /**
     * Test 15: Flash messages display
     */
    public function test_flash_messages_display(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->type('input[wire\\:model="name"]', 'Flash Message Test Script')
                ->type('textarea[wire\\:model="content"]', '#!/bin/bash\necho "Flash test"')
                ->press('Create Script')
                ->pause(1000)
                ->waitForText('Script created successfully', 5)
                ->assertSee('Script created successfully')
                ->screenshot('flash-messages-display');
        });
    }

    /**
     * Test 16: Script language dropdown is present
     */
    public function test_script_language_dropdown_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Language')
                ->assertPresent('select[wire\\:model="language"]')
                ->assertSeeIn('select[wire\\:model="language"]', 'Bash')
                ->assertSeeIn('select[wire\\:model="language"]', 'Python')
                ->assertSeeIn('select[wire\\:model="language"]', 'PHP')
                ->screenshot('script-language-dropdown-present');
        });
    }

    /**
     * Test 17: Script timeout field is present
     */
    public function test_script_timeout_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Timeout')
                ->assertPresent('input[wire\\:model="timeout"]')
                ->screenshot('script-timeout-field-present');
        });
    }

    /**
     * Test 18: Script retry on failure checkbox is present
     */
    public function test_script_retry_on_failure_checkbox_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Retry on failure')
                ->assertPresent('input[wire\\:model="retryOnFailure"]')
                ->screenshot('script-retry-checkbox-present');
        });
    }

    /**
     * Test 19: Script max retries field appears when retry enabled
     */
    public function test_script_max_retries_field_appears(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->check('input[wire\\:model="retryOnFailure"]')
                ->pause(300)
                ->waitForText('Max Retries', 3)
                ->assertSee('Max Retries')
                ->assertPresent('input[wire\\:model="maxRetries"]')
                ->screenshot('script-max-retries-field-appears');
        });
    }

    /**
     * Test 20: Script description field is present
     */
    public function test_script_description_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Description')
                ->assertPresent('input[wire\\:model="description"]')
                ->screenshot('script-description-field-present');
        });
    }

    /**
     * Test 21: Templates button is visible
     */
    public function test_templates_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Templates')
                ->assertPresent('button[wire\\:click*="showTemplateModal"]')
                ->screenshot('templates-button-visible');
        });
    }

    /**
     * Test 22: Templates modal opens
     */
    public function test_templates_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="$set(\'showTemplateModal\', true)"]')
                ->pause(500)
                ->waitForText('Script Templates', 5)
                ->assertSee('Script Templates')
                ->screenshot('templates-modal-opens');
        });
    }

    /**
     * Test 23: Test script modal shows project selection
     */
    public function test_test_script_modal_shows_project_selection(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Test Modal Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Test modal"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Test Modal Script')
                ->click('button[wire\\:click="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitForText('Test Script', 5)
                ->assertSee('Select Project')
                ->assertPresent('select[wire\\:model="testProject"]')
                ->screenshot('test-modal-project-selection');
        });
    }

    /**
     * Test 24: Script settings information is displayed
     */
    public function test_script_settings_information_displayed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Settings Display Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Settings"',
                'timeout' => 600,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Settings Display Script')
                ->assertSee('Timeout: 600s')
                ->screenshot('script-settings-displayed');
        });
    }

    /**
     * Test 25: Script with retry settings shows retry count
     */
    public function test_script_retry_settings_show_count(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Retry Display Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Retry"',
                'timeout' => 300,
                'retry_on_failure' => true,
                'max_retries' => 5,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Retry Display Script')
                ->assertSee('Retries: 5')
                ->screenshot('script-retry-count-displayed');
        });
    }

    /**
     * Test 26: Download script button is visible
     */
    public function test_download_script_button_visible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Downloadable Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Download"',
                'timeout' => 300,
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Downloadable Script')
                ->assertPresent('button[wire\\:click*="downloadScript"]')
                ->assertSee('Download')
                ->screenshot('download-script-button-visible');
        });
    }

    /**
     * Test 27: Script type badge is displayed with correct color
     */
    public function test_script_type_badge_displayed(): void
    {
        $deploymentScript = DeploymentScript::firstOrCreate(
            ['name' => 'Type Badge Deployment'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Deploy"',
                'timeout' => 300,
                'type' => 'deployment',
                'is_template' => false,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->assertSee('Type Badge Deployment')
                ->assertSee('Deployment')
                ->assertPresent('span.bg-blue-100')
                ->screenshot('script-type-badge-displayed');
        });
    }

    /**
     * Test 28: Empty state is displayed when no scripts exist
     */
    public function test_empty_state_displayed_when_no_scripts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->screenshot('empty-state-check');
        });
    }

    /**
     * Test 29: Script modal cancel button works
     */
    public function test_script_modal_cancel_button_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->click('button[wire\\:click="$set(\'showCreateModal\', false)"]')
                ->pause(300)
                ->assertDontSee('Create Deployment Script')
                ->screenshot('script-modal-cancel-works');
        });
    }

    /**
     * Test 30: Script content shows available variables
     */
    public function test_script_content_shows_available_variables(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script Manager', 10)
                ->click('button[wire\\:click="createScript"]')
                ->pause(500)
                ->waitForText('Create Deployment Script', 5)
                ->assertSee('Available variables')
                ->assertSee('PROJECT_NAME')
                ->assertSee('PROJECT_SLUG')
                ->assertSee('BRANCH')
                ->screenshot('script-available-variables');
        });
    }
}
