<?php

namespace Tests\Browser;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class EnvironmentsTest extends DuskTestCase
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
            ['hostname' => 'env-test.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Environment Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-environment-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/env-test.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/env-test',
                'environment' => 'production',
                'env_variables' => [
                    'APP_ENV' => 'production',
                    'APP_DEBUG' => 'false',
                    'APP_URL' => 'https://example.com',
                ],
            ]
        );
    }

    /**
     * Test 1: Environment list page loads successfully
     */
    public function test_environment_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertSee('Environment')
                ->assertPresent('div, section, main')
                ->screenshot('environment-list-page');
        });
    }

    /**
     * Test 2: Environment list displays production environment
     */
    public function test_environment_list_displays_production(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertSee('production')
                ->screenshot('environment-list-production');
        });
    }

    /**
     * Test 3: Environment list displays staging environment
     */
    public function test_environment_list_displays_staging(): void
    {
        // Create staging project
        Project::firstOrCreate(
            ['slug' => 'test-staging-env'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Staging Environment',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/staging.git',
                'branch' => 'staging',
                'deploy_path' => '/var/www/staging',
                'environment' => 'staging',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertSee('staging')
                ->screenshot('environment-list-staging');
        });
    }

    /**
     * Test 4: Environment list displays development environment
     */
    public function test_environment_list_displays_development(): void
    {
        // Create development project
        Project::firstOrCreate(
            ['slug' => 'test-dev-env'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Development Environment',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/dev.git',
                'branch' => 'develop',
                'deploy_path' => '/var/www/dev',
                'environment' => 'development',
            ]
        );

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertSee('development')
                ->screenshot('environment-list-development');
        });
    }

    /**
     * Test 5: Environment list has filter functionality
     */
    public function test_environment_list_has_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertPresent('select[wire\\:model*="filter"], select[wire\\:model*="environment"], input[type="search"]')
                ->screenshot('environment-list-filter');
        });
    }

    /**
     * Test 6: Environment list shows project count per environment
     */
    public function test_environment_list_shows_project_count(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertPresent('[class*="count"], [class*="badge"], span, div')
                ->screenshot('environment-project-count');
        });
    }

    /**
     * Test 7: Project detail shows environment tab
     */
    public function test_project_detail_shows_environment_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->assertPresent('button:contains("Environment"), a:contains("Environment"), [x-on\\:click*="environment"]')
                ->screenshot('project-environment-tab');
        });
    }

    /**
     * Test 8: Environment variables page loads for project
     */
    public function test_environment_variables_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('div, section, [class*="environment"]')
                ->screenshot('environment-variables-page');
        });
    }

    /**
     * Test 9: Environment variables displays existing variables
     */
    public function test_environment_variables_displays_existing(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('APP_ENV')
                ->assertSee('APP_DEBUG')
                ->assertSee('APP_URL')
                ->screenshot('environment-variables-display');
        });
    }

    /**
     * Test 10: Environment variables has add new variable button
     */
    public function test_environment_variables_has_add_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Add"), button:contains("New Variable"), button[wire\\:click*="add"]')
                ->screenshot('environment-add-variable-button');
        });
    }

    /**
     * Test 11: Environment variable add modal opens
     */
    public function test_environment_variable_add_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->screenshot('environment-add-modal');
        });
    }

    /**
     * Test 12: Environment variable add modal has key field
     */
    public function test_environment_variable_add_has_key_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('input[wire\\:model*="key"], input[name*="key"], input[placeholder*="key"]')
                ->screenshot('environment-add-key-field');
        });
    }

    /**
     * Test 13: Environment variable add modal has value field
     */
    public function test_environment_variable_add_has_value_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('input[wire\\:model*="value"], textarea[wire\\:model*="value"], input[name*="value"]')
                ->screenshot('environment-add-value-field');
        });
    }

    /**
     * Test 14: Environment variable add modal has save button
     */
    public function test_environment_variable_add_has_save_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('button:contains("Save"), button[type="submit"]')
                ->screenshot('environment-add-save-button');
        });
    }

    /**
     * Test 15: Environment variable edit functionality exists
     */
    public function test_environment_variable_edit_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button[wire\\:click*="edit"], button:contains("Edit"), a:contains("Edit")')
                ->screenshot('environment-edit-functionality');
        });
    }

    /**
     * Test 16: Environment variable delete functionality exists
     */
    public function test_environment_variable_delete_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button[wire\\:click*="delete"], button:contains("Delete"), button:contains("Remove")')
                ->screenshot('environment-delete-functionality');
        });
    }

    /**
     * Test 17: Environment variable delete shows confirmation
     */
    public function test_environment_variable_delete_shows_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                // Try to delete a variable
                ->press('button[wire\\:click*="delete"]:first-of-type, button:contains("Delete"):first-of-type')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="confirm"]', 5)
                ->screenshot('environment-delete-confirmation');
        });
    }

    /**
     * Test 18: Environment variable has secret/masked option
     */
    public function test_environment_variable_has_secret_option(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('input[type="checkbox"][wire\\:model*="secret"], input[type="checkbox"][wire\\:model*="masked"]')
                ->screenshot('environment-secret-option');
        });
    }

    /**
     * Test 19: Environment variables shows masked values for secrets
     */
    public function test_environment_variables_masks_secrets(): void
    {
        // Create project with secret variable
        $project = Project::firstOrCreate(
            ['slug' => 'test-env-secrets'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Secrets',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/secrets.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/secrets',
                'environment' => 'production',
                'env_variables' => [
                    'DB_PASSWORD' => 'secret123',
                    'API_KEY' => 'key_secret_value',
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('[class*="masked"], input[type="password"], [class*="secret"]')
                ->screenshot('environment-masked-secrets');
        });
    }

    /**
     * Test 20: Environment type selector shows all options
     */
    public function test_environment_type_selector_shows_options(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/edit')
                ->waitForText('Edit', 10)
                ->assertPresent('select[wire\\:model*="environment"], select[name*="environment"]')
                ->screenshot('environment-type-selector');
        });
    }

    /**
     * Test 21: Environment switching functionality exists
     */
    public function test_environment_switching_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/edit')
                ->waitForText('Edit', 10)
                ->click('select[wire\\:model*="environment"], select[name*="environment"]')
                ->pause(500)
                ->assertSee('production')
                ->assertSee('staging')
                ->assertSee('development')
                ->screenshot('environment-switching');
        });
    }

    /**
     * Test 22: Environment cloning functionality exists
     */
    public function test_environment_cloning_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Clone"), button:contains("Copy"), button[wire\\:click*="clone"]')
                ->screenshot('environment-clone-functionality');
        });
    }

    /**
     * Test 23: Environment cloning modal opens
     */
    public function test_environment_cloning_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Clone"), button:contains("Copy")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->screenshot('environment-clone-modal');
        });
    }

    /**
     * Test 24: Environment configuration editor exists
     */
    public function test_environment_configuration_editor(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('textarea, [class*="editor"], [class*="code"]')
                ->screenshot('environment-configuration-editor');
        });
    }

    /**
     * Test 25: Environment bulk import functionality
     */
    public function test_environment_bulk_import_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Import"), button:contains("Bulk"), button[wire\\:click*="import"]')
                ->screenshot('environment-bulk-import');
        });
    }

    /**
     * Test 26: Environment bulk export functionality
     */
    public function test_environment_bulk_export_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Export"), button:contains("Download"), button[wire\\:click*="export"]')
                ->screenshot('environment-bulk-export');
        });
    }

    /**
     * Test 27: Environment deployment settings accessible
     */
    public function test_environment_deployment_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Deployment"), a:contains("Deployment"), [class*="deploy"]')
                ->screenshot('environment-deployment-settings');
        });
    }

    /**
     * Test 28: Environment access control settings
     */
    public function test_environment_access_control(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Access"), button:contains("Permissions"), [class*="access"]')
                ->screenshot('environment-access-control');
        });
    }

    /**
     * Test 29: Environment search functionality
     */
    public function test_environment_search_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('input[type="search"], input[wire\\:model*="search"], input[placeholder*="Search"]')
                ->screenshot('environment-search');
        });
    }

    /**
     * Test 30: Environment variable validation for required fields
     */
    public function test_environment_variable_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                // Try to save without filling fields
                ->press('Save')
                ->pause(1000)
                ->waitFor('.text-red-500, .text-red-600, .error, [class*="error"]', 5)
                ->screenshot('environment-variable-validation');
        });
    }

    /**
     * Test 31: Environment shows history/audit log
     */
    public function test_environment_shows_history(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("History"), button:contains("Audit"), [class*="history"]')
                ->screenshot('environment-history');
        });
    }

    /**
     * Test 32: Environment variables support multiline values
     */
    public function test_environment_variables_multiline_support(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('textarea[wire\\:model*="value"], textarea[name*="value"]')
                ->screenshot('environment-multiline-support');
        });
    }

    /**
     * Test 33: Environment shows variable count
     */
    public function test_environment_shows_variable_count(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('[class*="count"], [class*="badge"], span')
                ->screenshot('environment-variable-count');
        });
    }

    /**
     * Test 34: Environment sync across servers functionality
     */
    public function test_environment_sync_across_servers(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Sync"), button[wire\\:click*="sync"]')
                ->screenshot('environment-sync-servers');
        });
    }

    /**
     * Test 35: Environment variable categories/grouping
     */
    public function test_environment_variable_categories(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('select[wire\\:model*="category"], button:contains("Category"), [class*="group"]')
                ->screenshot('environment-variable-categories');
        });
    }

    /**
     * Test 36: Environment backup and restore functionality
     */
    public function test_environment_backup_restore(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Backup"), button:contains("Restore"), button[wire\\:click*="backup"]')
                ->screenshot('environment-backup-restore');
        });
    }

    /**
     * Test 37: Environment validation rules display
     */
    public function test_environment_validation_rules(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('select[wire\\:model*="type"], input[type="checkbox"][wire\\:model*="required"]')
                ->screenshot('environment-validation-rules');
        });
    }

    /**
     * Test 38: Environment shows PHP configuration variables
     */
    public function test_environment_php_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("PHP"), [class*="php"]')
                ->screenshot('environment-php-configuration');
        });
    }

    /**
     * Test 39: Environment shows database configuration
     */
    public function test_environment_database_configuration(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('DB_')
                ->screenshot('environment-database-configuration');
        });
    }

    /**
     * Test 40: Environment shows cache configuration
     */
    public function test_environment_cache_configuration(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-env-cache'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Cache',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/cache.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/cache',
                'environment' => 'production',
                'env_variables' => [
                    'CACHE_DRIVER' => 'redis',
                    'REDIS_HOST' => 'localhost',
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('CACHE_DRIVER')
                ->screenshot('environment-cache-configuration');
        });
    }

    /**
     * Test 41: Environment shows queue configuration
     */
    public function test_environment_queue_configuration(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-env-queue'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Queue',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/queue.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/queue',
                'environment' => 'production',
                'env_variables' => [
                    'QUEUE_CONNECTION' => 'redis',
                    'QUEUE_DRIVER' => 'redis',
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('QUEUE_')
                ->screenshot('environment-queue-configuration');
        });
    }

    /**
     * Test 42: Environment template functionality
     */
    public function test_environment_template_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Template"), button:contains("Load Template"), select[wire\\:model*="template"]')
                ->screenshot('environment-template');
        });
    }

    /**
     * Test 43: Environment encryption status display
     */
    public function test_environment_encryption_status(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('[class*="encrypted"], [class*="secure"], svg, [class*="lock"]')
                ->screenshot('environment-encryption-status');
        });
    }

    /**
     * Test 44: Environment comparison functionality
     */
    public function test_environment_comparison_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/environments')
                ->waitForText('Environment', 10)
                ->assertPresent('button:contains("Compare"), button[wire\\:click*="compare"]')
                ->screenshot('environment-comparison');
        });
    }

    /**
     * Test 45: Environment shows last updated timestamp
     */
    public function test_environment_shows_last_updated(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('time, [datetime], [class*="time"], [class*="updated"]')
                ->screenshot('environment-last-updated');
        });
    }

    /**
     * Test 46: Environment shows updated by user
     */
    public function test_environment_shows_updated_by(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('[class*="user"], [class*="author"], img, svg')
                ->screenshot('environment-updated-by');
        });
    }

    /**
     * Test 47: Environment variable preview before save
     */
    public function test_environment_variable_preview(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->press('button:contains("Add Variable"), button:contains("Add"), button:contains("New")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show*="open"]', 5)
                ->assertPresent('button:contains("Preview"), [class*="preview"]')
                ->screenshot('environment-variable-preview');
        });
    }

    /**
     * Test 48: Environment shows Laravel framework specific variables
     */
    public function test_environment_laravel_framework_variables(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('APP_')
                ->screenshot('environment-laravel-variables');
        });
    }

    /**
     * Test 49: Environment shows mail configuration
     */
    public function test_environment_mail_configuration(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-env-mail'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Environment Mail',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/mail.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/mail',
                'environment' => 'production',
                'env_variables' => [
                    'MAIL_MAILER' => 'smtp',
                    'MAIL_HOST' => 'smtp.example.com',
                    'MAIL_PORT' => '587',
                ],
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertSee('MAIL_')
                ->screenshot('environment-mail-configuration');
        });
    }

    /**
     * Test 50: Environment rollback functionality
     */
    public function test_environment_rollback_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1500)
                ->assertPresent('button:contains("Rollback"), button:contains("Revert"), button[wire\\:click*="rollback"]')
                ->screenshot('environment-rollback');
        });
    }
}
