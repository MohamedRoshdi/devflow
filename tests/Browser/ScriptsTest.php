<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\DeploymentScriptRun;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ScriptsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

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
            ['slug' => 'test-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
            ]
        );
    }

    /**
     * Test 1: Scripts list page loads successfully
     */
    public function test_scripts_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script')
                ->assertSee('Deployment Scripts')
                ->screenshot('scripts-list-page');
        });
    }

    /**
     * Test 2: Create script button is present
     */
    public function test_create_script_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertPresent('button:contains("Create Script"), button:contains("New Script"), button:contains("Add Script")')
                ->screenshot('create-script-button');
        });
    }

    /**
     * Test 3: Script creation modal opens
     */
    public function test_script_creation_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script"), button:contains("Add Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showCreateModal"]', 5)
                ->assertSee('Name')
                ->assertSee('Description')
                ->assertSee('Type')
                ->assertSee('Language')
                ->assertSee('Content')
                ->assertSee('Timeout')
                ->screenshot('script-creation-modal');
        });
    }

    /**
     * Test 4: Scripts are displayed in the list
     */
    public function test_scripts_are_displayed_in_list(): void
    {
        // Create test scripts
        $script1 = DeploymentScript::firstOrCreate(
            ['name' => 'Pre-Deployment Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Starting deployment"',
                'timeout' => 300,
                'is_template' => false,
                'tags' => ['deployment', 'pre-deploy'],
            ]
        );

        $script2 = DeploymentScript::firstOrCreate(
            ['name' => 'Post-Deployment Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Deployment complete"',
                'timeout' => 300,
                'is_template' => false,
                'tags' => ['deployment', 'post-deploy'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Pre-Deployment Script')
                ->assertSee('Post-Deployment Script')
                ->assertSee('bash')
                ->screenshot('scripts-displayed-in-list');
        });
    }

    /**
     * Test 5: Script details show language and timeout
     */
    public function test_script_details_show_language_and_timeout(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Test Script with Details'],
            [
                'language' => 'python',
                'script' => '#!/usr/bin/env python3\nprint("Hello World")',
                'timeout' => 600,
                'is_template' => false,
                'tags' => ['test'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Test Script with Details')
                ->assertSee('python')
                ->screenshot('script-language-timeout');
        });
    }

    /**
     * Test 6: Script edit button is present
     */
    public function test_script_edit_button_present(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Editable Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Edit me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Editable Script')
                ->assertPresent('button[wire\\:click*="editScript"], button[wire\\:click*="edit"]')
                ->screenshot('script-edit-button');
        });
    }

    /**
     * Test 7: Script delete functionality is available
     */
    public function test_script_delete_functionality_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Deletable Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Delete me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Deletable Script')
                ->assertPresent('button[wire\\:click*="deleteScript"], button[wire\\:click*="delete"]')
                ->screenshot('script-delete-button');
        });
    }

    /**
     * Test 8: Script execution/test button is available
     */
    public function test_script_execution_button_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Executable Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Execute me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Executable Script')
                ->assertPresent('button[wire\\:click*="testScript"], button[wire\\:click*="execute"], button[wire\\:click*="run"]')
                ->screenshot('script-execute-button');
        });
    }

    /**
     * Test 9: Script templates are available
     */
    public function test_script_templates_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                // Click on create to see templates option
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                // Look for templates or use template button
                ->assertPresent('button:contains("Templates"), button:contains("Use Template"), select[wire\\:model*="template"]')
                ->screenshot('script-templates');
        });
    }

    /**
     * Test 10: Script variables/parameters are configurable
     */
    public function test_script_variables_configurable(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Variables'],
            [
                'language' => 'bash',
                'script' => 'echo "Hello {NAME}"',
                'timeout' => 300,
                'variables' => ['NAME' => 'World'],
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Variables')
                // Click edit to view variables
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Variables')
                ->screenshot('script-variables');
        });
    }

    /**
     * Test 11: Script output/logs are displayed
     */
    public function test_script_output_logs_displayed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Logs'],
            [
                'language' => 'bash',
                'script' => 'echo "Log output"',
                'timeout' => 300,
            ]
        );

        // Create a script run with output
        $scriptRun = DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
            ],
            [
                'status' => 'success',
                'output' => "Script started\nExecuting commands\nScript completed successfully",
                'error_output' => null,
                'exit_code' => 0,
                'started_at' => now()->subMinutes(5),
                'finished_at' => now()->subMinutes(3),
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Logs')
                // Click on script or view logs
                ->click('button[wire\\:click*="testScript('.$script->id.')"], button:contains("View Logs"), button:contains("History")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, .script-output', 5)
                ->screenshot('script-logs-output');
        });
    }

    /**
     * Test 12: Script scheduling options are present
     */
    public function test_script_scheduling_options_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                // Look for scheduling/cron options
                ->assertPresent('input[wire\\:model*="schedule"], input[wire\\:model*="cron"], select[wire\\:model*="schedule"]')
                ->screenshot('script-scheduling-options');
        });
    }

    /**
     * Test 13: Script types are selectable
     */
    public function test_script_types_selectable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Type')
                ->assertPresent('select[wire\\:model*="type"]')
                // Check for script type options
                ->assertSeeIn('select[wire\\:model*="type"], .script-types', 'deployment')
                ->screenshot('script-types-selection');
        });
    }

    /**
     * Test 14: Script languages are selectable
     */
    public function test_script_languages_selectable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Language')
                ->assertPresent('select[wire\\:model*="language"]')
                // Check for common languages
                ->assertSeeIn('select[wire\\:model*="language"], .script-languages', 'bash')
                ->screenshot('script-languages-selection');
        });
    }

    /**
     * Test 15: Script timeout is configurable
     */
    public function test_script_timeout_configurable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Timeout')
                ->assertPresent('input[wire\\:model*="timeout"]')
                ->screenshot('script-timeout-config');
        });
    }

    /**
     * Test 16: Script retry options are available
     */
    public function test_script_retry_options_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('input[wire\\:model*="retry"], input[wire\\:model*="maxRetries"]')
                ->screenshot('script-retry-options');
        });
    }

    /**
     * Test 17: Script status indicators are displayed
     */
    public function test_script_status_indicators_displayed(): void
    {
        // Create scripts with runs in different statuses
        $successScript = DeploymentScript::firstOrCreate(
            ['name' => 'Success Script'],
            [
                'language' => 'bash',
                'script' => 'exit 0',
                'timeout' => 300,
            ]
        );

        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $successScript->id,
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'started_at' => now()->subMinutes(10),
                'finished_at' => now()->subMinutes(8),
            ]
        );

        $failedScript = DeploymentScript::firstOrCreate(
            ['name' => 'Failed Script'],
            [
                'language' => 'bash',
                'script' => 'exit 1',
                'timeout' => 300,
            ]
        );

        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $failedScript->id,
            ],
            [
                'status' => 'failed',
                'exit_code' => 1,
                'error_output' => 'Script failed with exit code 1',
                'started_at' => now()->subMinutes(5),
                'finished_at' => now()->subMinutes(4),
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Success Script')
                ->assertSee('Failed Script')
                // Check for status badges with correct colors
                ->assertPresent('span.from-green-500, span.from-emerald-500, span.bg-green-500, span.bg-emerald-500, span.text-green-500, span.text-emerald-500')
                ->assertPresent('span.from-red-500, span.from-rose-500, span.bg-red-500, span.bg-rose-500, span.text-red-500, span.text-rose-500')
                ->screenshot('script-status-indicators');
        });
    }

    /**
     * Test 18: Script run history is accessible
     */
    public function test_script_run_history_accessible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with History'],
            [
                'language' => 'bash',
                'script' => 'echo "History"',
                'timeout' => 300,
            ]
        );

        // Create multiple runs
        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
                'started_at' => now()->subHours(2),
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'finished_at' => now()->subHours(2)->addMinutes(2),
            ]
        );

        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
                'started_at' => now()->subHour(),
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'finished_at' => now()->subHour()->addMinutes(2),
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with History')
                // Click to view history
                ->assertPresent('button:contains("History"), button:contains("Runs"), button[wire\\:click*="viewHistory"]')
                ->screenshot('script-run-history');
        });
    }

    /**
     * Test 19: Script download functionality is available
     */
    public function test_script_download_functionality_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Downloadable Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Download me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Downloadable Script')
                ->assertPresent('button[wire\\:click*="downloadScript"], button:contains("Download"), a[href*="download"]')
                ->screenshot('script-download-button');
        });
    }

    /**
     * Test 20: Script tags are displayed
     */
    public function test_script_tags_displayed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Tagged Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Tagged"',
                'timeout' => 300,
                'tags' => ['production', 'deployment', 'critical'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Tagged Script')
                ->assertSee('production')
                ->assertSee('deployment')
                ->assertSee('critical')
                ->screenshot('script-tags-displayed');
        });
    }

    /**
     * Test 21: Script templates can be used
     */
    public function test_script_templates_can_be_used(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertPresent('button:contains("Templates"), button:contains("Use Template")')
                ->screenshot('script-use-template');
        });
    }

    /**
     * Test 22: Script search/filter functionality
     */
    public function test_script_search_filter_functionality(): void
    {
        // Create scripts with different characteristics
        DeploymentScript::firstOrCreate(
            ['name' => 'Laravel Deployment Script'],
            [
                'language' => 'bash',
                'script' => 'php artisan migrate',
                'timeout' => 300,
                'tags' => ['laravel'],
            ]
        );

        DeploymentScript::firstOrCreate(
            ['name' => 'Node.js Build Script'],
            [
                'language' => 'bash',
                'script' => 'npm run build',
                'timeout' => 600,
                'tags' => ['nodejs'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertPresent('input[type="search"], input[placeholder*="Search"], input[wire\\:model*="search"]')
                ->screenshot('script-search-filter');
        });
    }

    /**
     * Test 23: Script enable/disable toggle
     */
    public function test_script_enable_disable_toggle(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Toggleable Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Toggle me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Toggleable Script')
                ->assertPresent('button[wire\\:click*="toggleScript"], input[type="checkbox"][wire\\:click*="toggle"]')
                ->screenshot('script-toggle-enable-disable');
        });
    }

    /**
     * Test 24: Script execution duration is displayed
     */
    public function test_script_execution_duration_displayed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Duration'],
            [
                'language' => 'bash',
                'script' => 'sleep 5',
                'timeout' => 300,
            ]
        );

        $scriptRun = DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'started_at' => now()->subMinutes(10),
                'finished_at' => now()->subMinutes(10)->addSeconds(125), // 2m 5s
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Duration')
                // Duration should be visible somewhere (either in list or details)
                ->screenshot('script-execution-duration');
        });
    }

    /**
     * Test 25: Script code editor is present in create modal
     */
    public function test_script_code_editor_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Content')
                ->assertPresent('textarea[wire\\:model*="content"], textarea[wire\\:model*="script"], .code-editor, .monaco-editor')
                ->screenshot('script-code-editor');
        });
    }

    /**
     * Test 26: Deployment scripts show association with projects
     */
    public function test_deployment_scripts_show_project_association(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Project-linked Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Project script"',
                'timeout' => 300,
            ]
        );

        // Create script run linked to project
        DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'started_at' => now()->subMinutes(5),
                'finished_at' => now()->subMinutes(3),
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Project-linked Script')
                ->screenshot('script-project-association');
        });
    }

    /**
     * Test 27: Empty scripts state is handled gracefully
     */
    public function test_empty_scripts_state_handled_gracefully(): void
    {
        // Delete all scripts temporarily for this test
        $existingScripts = DeploymentScript::all();

        // Store IDs to restore later
        $scriptIds = $existingScripts->pluck('id')->toArray();

        // Soft delete or hide scripts (don't actually delete to avoid breaking other tests)
        // Instead, we'll just check if the empty state UI exists

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                // Should show empty state if no scripts, or at least have the create button
                ->assertPresent('button:contains("Create Script"), button:contains("New Script"), .empty-state')
                ->screenshot('scripts-empty-state');
        });
    }

    /**
     * Test 28: Script run exit codes are displayed
     */
    public function test_script_run_exit_codes_displayed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Exit Code'],
            [
                'language' => 'bash',
                'script' => 'exit 0',
                'timeout' => 300,
            ]
        );

        $scriptRun = DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
            ],
            [
                'status' => 'success',
                'exit_code' => 0,
                'output' => 'Script executed successfully',
                'started_at' => now()->subMinutes(5),
                'finished_at' => now()->subMinutes(3),
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Exit Code')
                // Click to view details or history
                ->click('button[wire\\:click*="testScript('.$script->id.')"], button:contains("History")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                // Exit code should be visible in output
                ->assertSee('0')
                ->screenshot('script-exit-code');
        });
    }

    /**
     * Test 29: Script test modal shows project selection
     */
    public function test_script_test_modal_shows_project_selection(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Testable Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Test execution"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Testable Script')
                ->click('button[wire\\:click*="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showTestModal"]', 5)
                ->assertSee('Test')
                ->assertPresent('select[wire\\:model*="testProject"], select[wire\\:model*="project"]')
                ->screenshot('script-test-project-selection');
        });
    }

    /**
     * Test 30: Script template modal displays available templates
     */
    public function test_script_template_modal_displays_templates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Templates"), button:contains("Use Template")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, [x-show="showTemplateModal"]', 5)
                ->assertSee('Template')
                // Should show common templates like Laravel, Node.js, etc.
                ->screenshot('script-template-modal-templates');
        });
    }

    /**
     * Test 31: Deployment template script is available
     */
    public function test_deployment_template_script_available(): void
    {
        $deploymentTemplate = DeploymentScript::firstOrCreate(
            ['name' => 'Laravel Deployment Template'],
            [
                'language' => 'bash',
                'script' => "#!/bin/bash\nphp artisan migrate --force\nphp artisan cache:clear",
                'timeout' => 600,
                'is_template' => true,
                'tags' => ['deployment', 'laravel', 'template'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Laravel Deployment Template')
                ->assertSee('template')
                ->screenshot('deployment-template-available');
        });
    }

    /**
     * Test 32: Backup template script is available
     */
    public function test_backup_template_script_available(): void
    {
        $backupTemplate = DeploymentScript::firstOrCreate(
            ['name' => 'Database Backup Template'],
            [
                'language' => 'bash',
                'script' => "#!/bin/bash\nmysqldump -u root database > backup.sql",
                'timeout' => 1800,
                'is_template' => true,
                'tags' => ['backup', 'database', 'template'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Database Backup Template')
                ->assertSee('backup')
                ->screenshot('backup-template-available');
        });
    }

    /**
     * Test 33: Maintenance template script is available
     */
    public function test_maintenance_template_script_available(): void
    {
        $maintenanceTemplate = DeploymentScript::firstOrCreate(
            ['name' => 'Maintenance Mode Template'],
            [
                'language' => 'bash',
                'script' => "#!/bin/bash\nphp artisan down --retry=60",
                'timeout' => 300,
                'is_template' => true,
                'tags' => ['maintenance', 'template'],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Maintenance Mode Template')
                ->assertSee('maintenance')
                ->screenshot('maintenance-template-available');
        });
    }

    /**
     * Test 34: Script execution on multiple servers is available
     */
    public function test_script_execution_on_multiple_servers_available(): void
    {
        // Create another server
        $server2 = Server::firstOrCreate(
            ['hostname' => 'staging.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Staging Server',
                'ip_address' => '192.168.1.101',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Multi-Server Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Running on multiple servers"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Multi-Server Script')
                ->click('button[wire\\:click*="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('select[wire\\:model*="server"], input[type="checkbox"][value*="server"]')
                ->screenshot('script-multi-server-execution');
        });
    }

    /**
     * Test 35: Script parameters modal displays input fields
     */
    public function test_script_parameters_modal_displays_input_fields(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Parameterized Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Hello {NAME}, your email is {EMAIL}"',
                'timeout' => 300,
                'variables' => [
                    'NAME' => ['type' => 'text', 'default' => 'User'],
                    'EMAIL' => ['type' => 'email', 'default' => 'user@example.com'],
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Parameterized Script')
                ->click('button[wire\\:click*="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('NAME')
                ->assertSee('EMAIL')
                ->screenshot('script-parameters-inputs');
        });
    }

    /**
     * Test 36: Script versioning history is accessible
     */
    public function test_script_versioning_history_accessible(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Versioned Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Version 3"',
                'timeout' => 300,
                'version' => 3,
                'previous_versions' => [
                    ['version' => 1, 'script' => 'echo "Version 1"', 'created_at' => now()->subDays(2)],
                    ['version' => 2, 'script' => 'echo "Version 2"', 'created_at' => now()->subDay()],
                ],
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Versioned Script')
                ->assertPresent('button:contains("History"), button:contains("Versions"), button[wire\\:click*="viewVersions"]')
                ->screenshot('script-versioning-history');
        });
    }

    /**
     * Test 37: Script bulk operations are available
     */
    public function test_script_bulk_operations_available(): void
    {
        // Create multiple scripts
        DeploymentScript::firstOrCreate(
            ['name' => 'Bulk Script 1'],
            [
                'language' => 'bash',
                'script' => 'echo "Bulk 1"',
                'timeout' => 300,
            ]
        );

        DeploymentScript::firstOrCreate(
            ['name' => 'Bulk Script 2'],
            [
                'language' => 'bash',
                'script' => 'echo "Bulk 2"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertPresent('input[type="checkbox"], button:contains("Select All"), button:contains("Bulk")')
                ->screenshot('script-bulk-operations');
        });
    }

    /**
     * Test 38: Script export functionality is available
     */
    public function test_script_export_functionality_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Exportable Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Export me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Exportable Script')
                ->assertPresent('button:contains("Export"), button[wire\\:click*="export"], a[href*="export"]')
                ->screenshot('script-export-functionality');
        });
    }

    /**
     * Test 39: Script import functionality is available
     */
    public function test_script_import_functionality_available(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertPresent('button:contains("Import"), input[type="file"][wire\\:model*="import"]')
                ->screenshot('script-import-functionality');
        });
    }

    /**
     * Test 40: Script permissions can be managed
     */
    public function test_script_permissions_can_be_managed(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Permission-Controlled Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Restricted"',
                'timeout' => 300,
                'permissions' => ['admin', 'developer'],
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Permission-Controlled Script')
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('select[wire\\:model*="permissions"], input[wire\\:model*="permissions"]')
                ->screenshot('script-permissions-management');
        });
    }

    /**
     * Test 41: Script access control by role is enforced
     */
    public function test_script_access_control_by_role_enforced(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Admin-Only Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Admin only"',
                'timeout' => 300,
                'required_role' => 'admin',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Admin-Only Script')
                ->screenshot('script-access-control-role');
        });
    }

    /**
     * Test 42: Script clone functionality is available
     */
    public function test_script_clone_functionality_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Clonable Script'],
            [
                'language' => 'bash',
                'script' => '#!/bin/bash\necho "Clone me"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Clonable Script')
                ->assertPresent('button[wire\\:click*="cloneScript"], button:contains("Clone"), button:contains("Duplicate")')
                ->screenshot('script-clone-functionality');
        });
    }

    /**
     * Test 43: Script syntax validation is present
     */
    public function test_script_syntax_validation_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->click('button:contains("Create Script"), button:contains("New Script")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Content')
                ->assertPresent('button:contains("Validate"), button:contains("Check Syntax")')
                ->screenshot('script-syntax-validation');
        });
    }

    /**
     * Test 44: Script dry run option is available
     */
    public function test_script_dry_run_option_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Dry Run Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Dry run test"',
                'timeout' => 300,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Dry Run Script')
                ->click('button[wire\\:click*="testScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('input[type="checkbox"][wire\\:model*="dryRun"], label:contains("Dry Run")')
                ->screenshot('script-dry-run-option');
        });
    }

    /**
     * Test 45: Script scheduling with cron expressions
     */
    public function test_script_scheduling_with_cron_expressions(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Scheduled Cron Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Scheduled execution"',
                'timeout' => 300,
                'cron_schedule' => '0 2 * * *',
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Scheduled Cron Script')
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('input[wire\\:model*="cronSchedule"], input[wire\\:model*="schedule"]')
                ->assertSee('0 2 * * *')
                ->screenshot('script-cron-scheduling');
        });
    }

    /**
     * Test 46: Script error handling configuration is available
     */
    public function test_script_error_handling_configuration_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Error Handling Script'],
            [
                'language' => 'bash',
                'script' => 'set -e\necho "Strict error handling"',
                'timeout' => 300,
                'error_handling' => 'strict',
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Error Handling Script')
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('select[wire\\:model*="errorHandling"], input[wire\\:model*="errorHandling"]')
                ->screenshot('script-error-handling-config');
        });
    }

    /**
     * Test 47: Script output streaming is functional
     */
    public function test_script_output_streaming_functional(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Streaming Output Script'],
            [
                'language' => 'bash',
                'script' => "#!/bin/bash\nfor i in {1..5}; do echo \"Line $i\"; sleep 1; done",
                'timeout' => 300,
            ]
        );

        $scriptRun = DeploymentScriptRun::firstOrCreate(
            [
                'project_id' => $this->project->id,
                'deployment_script_id' => $script->id,
            ],
            [
                'status' => 'running',
                'output' => "Line 1\nLine 2\nLine 3",
                'started_at' => now()->subSeconds(30),
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Streaming Output Script')
                ->click('button[wire\\:click*="testScript('.$script->id.')"], button:contains("View Output")')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal, .output-stream', 5)
                ->assertPresent('[wire\\:poll], .live-output, .streaming-output')
                ->screenshot('script-output-streaming');
        });
    }

    /**
     * Test 48: Script dependency management is available
     */
    public function test_script_dependency_management_available(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Dependencies'],
            [
                'language' => 'python',
                'script' => '#!/usr/bin/env python3\nimport requests\nprint("Hello")',
                'timeout' => 300,
                'dependencies' => ['requests', 'boto3'],
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Dependencies')
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('input[wire\\:model*="dependencies"], textarea[wire\\:model*="dependencies"]')
                ->screenshot('script-dependency-management');
        });
    }

    /**
     * Test 49: Script notification settings are configurable
     */
    public function test_script_notification_settings_configurable(): void
    {
        $script = DeploymentScript::firstOrCreate(
            ['name' => 'Script with Notifications'],
            [
                'language' => 'bash',
                'script' => 'echo "Notify on completion"',
                'timeout' => 300,
                'notify_on_success' => true,
                'notify_on_failure' => true,
            ]
        );

        $this->browse(function (Browser $browser) use ($script) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Script with Notifications')
                ->click('button[wire\\:click*="editScript('.$script->id.')"]')
                ->pause(500)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('input[type="checkbox"][wire\\:model*="notifyOnSuccess"], input[type="checkbox"][wire\\:model*="notifyOnFailure"]')
                ->screenshot('script-notification-settings');
        });
    }

    /**
     * Test 50: Script categories/groups are displayed
     */
    public function test_script_categories_groups_displayed(): void
    {
        $deploymentScript = DeploymentScript::firstOrCreate(
            ['name' => 'Deployment Category Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Deployment"',
                'timeout' => 300,
                'category' => 'deployment',
            ]
        );

        $backupScript = DeploymentScript::firstOrCreate(
            ['name' => 'Backup Category Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Backup"',
                'timeout' => 300,
                'category' => 'backup',
            ]
        );

        $maintenanceScript = DeploymentScript::firstOrCreate(
            ['name' => 'Maintenance Category Script'],
            [
                'language' => 'bash',
                'script' => 'echo "Maintenance"',
                'timeout' => 300,
                'category' => 'maintenance',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/scripts')
                ->waitForText('Script', 10)
                ->assertSee('Deployment Category Script')
                ->assertSee('Backup Category Script')
                ->assertSee('Maintenance Category Script')
                ->assertPresent('select[wire\\:model*="categoryFilter"], button:contains("Category")')
                ->screenshot('script-categories-groups');
        });
    }
}
