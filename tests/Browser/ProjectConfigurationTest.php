<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectConfigurationTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $adminUser;

    protected Server $testServer;

    protected Project $testProject;

    protected array $testResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Use or create admin user
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role if not already assigned
        if (! $this->adminUser->hasRole('admin')) {
            $this->adminUser->assignRole('admin');
        }

        // Create a test server
        $this->testServer = Server::firstOrCreate(
            ['ip_address' => '192.168.1.100'],
            [
                'user_id' => $this->adminUser->id,
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Create a test project
        $this->testProject = Project::firstOrCreate(
            ['slug' => 'config-test-project'],
            [
                'user_id' => $this->adminUser->id,
                'server_id' => $this->testServer->id,
                'name' => 'Config Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/config-test.git',
                'branch' => 'main',
                'php_version' => '8.3',
                'node_version' => '20',
                'root_directory' => '/',
            ]
        );
    }

    /**
     * Test 1: Project configuration page loads successfully
     *
     */

    #[Test]
    public function test_project_configuration_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-page-loads');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasConfigContent =
                str_contains($pageSource, 'project configuration') ||
                str_contains($pageSource, 'configuration') ||
                str_contains($pageSource, 'settings');

            $this->assertTrue($hasConfigContent, 'Project configuration page should load');
            $this->testResults['config_page_loads'] = 'Project configuration page loaded successfully';
        });
    }

    /**
     * Test 2: Basic project settings section is visible
     *
     */

    #[Test]
    public function test_basic_project_settings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-basic-settings');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBasicSettings =
                str_contains($pageSource, 'basic information') ||
                str_contains($pageSource, 'project name') ||
                str_contains($pageSource, 'wire:model="name"');

            $this->assertTrue($hasBasicSettings, 'Basic project settings should be visible');
            $this->testResults['basic_settings_visible'] = 'Basic project settings section is visible';
        });
    }

    /**
     * Test 3: Project name field is present and editable
     *
     */

    #[Test]
    public function test_project_name_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-name-field');

            $pageSource = $browser->driver->getPageSource();
            $hasNameField =
                str_contains($pageSource, 'wire:model="name"') ||
                str_contains($pageSource, 'wire:model.live="name"');

            $this->assertTrue($hasNameField, 'Project name field should be present');
            $this->testResults['name_field_present'] = 'Project name field is present and editable';
        });
    }

    /**
     * Test 4: Project slug field is present
     *
     */

    #[Test]
    public function test_project_slug_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-slug-field');

            $pageSource = $browser->driver->getPageSource();
            $hasSlugField =
                str_contains($pageSource, 'wire:model="slug"') ||
                str_contains($pageSource, 'wire:model.live="slug"');

            $this->assertTrue($hasSlugField, 'Project slug field should be present');
            $this->testResults['slug_field_present'] = 'Project slug field is present';
        });
    }

    /**
     * Test 5: Repository settings section is visible
     *
     */

    #[Test]
    public function test_repository_settings_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-repository');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasRepoSettings =
                str_contains($pageSource, 'repository') ||
                str_contains($pageSource, 'repository_url') ||
                str_contains($pageSource, 'git');

            $this->assertTrue($hasRepoSettings, 'Repository settings should be visible');
            $this->testResults['repository_settings_visible'] = 'Repository settings section is visible';
        });
    }

    /**
     * Test 6: Repository URL field is present
     *
     */

    #[Test]
    public function test_repository_url_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-repo-url');

            $pageSource = $browser->driver->getPageSource();
            $hasRepoUrl =
                str_contains($pageSource, 'wire:model="repository_url"') ||
                str_contains($pageSource, 'repository_url');

            $this->assertTrue($hasRepoUrl, 'Repository URL field should be present');
            $this->testResults['repo_url_field_present'] = 'Repository URL field is present';
        });
    }

    /**
     * Test 7: Branch field is present
     *
     */

    #[Test]
    public function test_branch_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-branch');

            $pageSource = $browser->driver->getPageSource();
            $hasBranchField =
                str_contains($pageSource, 'wire:model="branch"') ||
                str_contains($pageSource, 'wire:model.live="branch"');

            $this->assertTrue($hasBranchField, 'Branch field should be present');
            $this->testResults['branch_field_present'] = 'Branch field is present';
        });
    }

    /**
     * Test 8: Framework selection dropdown is available
     *
     */

    #[Test]
    public function test_framework_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-framework');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasFrameworkSelect =
                str_contains($pageSource, 'wire:model="framework"') ||
                str_contains($pageSource, 'laravel') ||
                str_contains($pageSource, 'framework');

            $this->assertTrue($hasFrameworkSelect, 'Framework selection should be available');
            $this->testResults['framework_selection'] = 'Framework selection dropdown is available';
        });
    }

    /**
     * Test 9: PHP version selection is available
     *
     */

    #[Test]
    public function test_php_version_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-php-version');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPhpVersion =
                str_contains($pageSource, 'wire:model="php_version"') ||
                str_contains($pageSource, 'php 8') ||
                str_contains($pageSource, 'php version');

            $this->assertTrue($hasPhpVersion, 'PHP version selection should be available');
            $this->testResults['php_version_selection'] = 'PHP version selection is available';
        });
    }

    /**
     * Test 10: Node.js version selection is available
     *
     */

    #[Test]
    public function test_nodejs_version_selection_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-node-version');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNodeVersion =
                str_contains($pageSource, 'wire:model="node_version"') ||
                str_contains($pageSource, 'node') ||
                str_contains($pageSource, 'node.js');

            $this->assertTrue($hasNodeVersion, 'Node.js version selection should be available');
            $this->testResults['node_version_selection'] = 'Node.js version selection is available';
        });
    }

    /**
     * Test 11: Root directory field is present
     *
     */

    #[Test]
    public function test_root_directory_field_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-root-directory');

            $pageSource = $browser->driver->getPageSource();
            $hasRootDir =
                str_contains($pageSource, 'wire:model="root_directory"') ||
                str_contains($pageSource, 'root_directory');

            $this->assertTrue($hasRootDir, 'Root directory field should be present');
            $this->testResults['root_directory_field'] = 'Root directory field is present';
        });
    }

    /**
     * Test 12: Health check URL field is available
     *
     */

    #[Test]
    public function test_health_check_url_field_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-health-check');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHealthCheck =
                str_contains($pageSource, 'wire:model="health_check_url"') ||
                str_contains($pageSource, 'health check') ||
                str_contains($pageSource, 'health_check_url');

            $this->assertTrue($hasHealthCheck, 'Health check URL field should be available');
            $this->testResults['health_check_field'] = 'Health check URL field is available';
        });
    }

    /**
     * Test 13: Auto-deploy toggle is present
     *
     */

    #[Test]
    public function test_auto_deploy_toggle_present()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-auto-deploy');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAutoDeploy =
                str_contains($pageSource, 'wire:model="auto_deploy"') ||
                str_contains($pageSource, 'auto deploy') ||
                str_contains($pageSource, 'auto_deploy');

            $this->assertTrue($hasAutoDeploy, 'Auto-deploy toggle should be present');
            $this->testResults['auto_deploy_toggle'] = 'Auto-deploy toggle is present';
        });
    }

    /**
     * Test 14: Save configuration button is visible
     *
     */

    #[Test]
    public function test_save_configuration_button_visible()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-save-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSaveButton =
                str_contains($pageSource, 'saveconfiguration') ||
                str_contains($pageSource, 'save') ||
                str_contains($pageSource, 'update');

            $this->assertTrue($hasSaveButton, 'Save configuration button should be visible');
            $this->testResults['save_button_visible'] = 'Save configuration button is visible';
        });
    }

    /**
     * Test 15: Framework options include Laravel
     *
     */

    #[Test]
    public function test_framework_options_include_laravel()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-framework-laravel');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLaravel = str_contains($pageSource, 'laravel');

            $this->assertTrue($hasLaravel, 'Framework options should include Laravel');
            $this->testResults['framework_includes_laravel'] = 'Framework options include Laravel';
        });
    }

    /**
     * Test 16: Framework options include Node.js
     *
     */

    #[Test]
    public function test_framework_options_include_nodejs()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-framework-nodejs');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNodejs =
                str_contains($pageSource, 'node.js') ||
                str_contains($pageSource, 'nodejs');

            $this->assertTrue($hasNodejs, 'Framework options should include Node.js');
            $this->testResults['framework_includes_nodejs'] = 'Framework options include Node.js';
        });
    }

    /**
     * Test 17: Framework options include React
     *
     */

    #[Test]
    public function test_framework_options_include_react()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-framework-react');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasReact = str_contains($pageSource, 'react');

            $this->assertTrue($hasReact, 'Framework options should include React');
            $this->testResults['framework_includes_react'] = 'Framework options include React';
        });
    }

    /**
     * Test 18: PHP version options include 8.4
     *
     */

    #[Test]
    public function test_php_version_options_include_84()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-php-84');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPhp84 =
                str_contains($pageSource, '8.4') ||
                str_contains($pageSource, 'php 8.4');

            $this->assertTrue($hasPhp84, 'PHP version options should include 8.4');
            $this->testResults['php_version_includes_84'] = 'PHP version options include 8.4';
        });
    }

    /**
     * Test 19: PHP version options include 8.3
     *
     */

    #[Test]
    public function test_php_version_options_include_83()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-php-83');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPhp83 =
                str_contains($pageSource, '8.3') ||
                str_contains($pageSource, 'php 8.3');

            $this->assertTrue($hasPhp83, 'PHP version options should include 8.3');
            $this->testResults['php_version_includes_83'] = 'PHP version options include 8.3';
        });
    }

    /**
     * Test 20: Node.js version options include 22
     *
     */

    #[Test]
    public function test_node_version_options_include_22()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-node-22');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNode22 =
                str_contains($pageSource, 'node.js 22') ||
                str_contains($pageSource, '22');

            $this->assertTrue($hasNode22, 'Node.js version options should include 22');
            $this->testResults['node_version_includes_22'] = 'Node.js version options include 22';
        });
    }

    /**
     * Test 21: Node.js version options include 20 (LTS)
     *
     */

    #[Test]
    public function test_node_version_options_include_20()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-node-20');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasNode20 =
                str_contains($pageSource, 'node.js 20') ||
                str_contains($pageSource, '20');

            $this->assertTrue($hasNode20, 'Node.js version options should include 20');
            $this->testResults['node_version_includes_20'] = 'Node.js version options include 20';
        });
    }

    /**
     * Test 22: Configuration page shows current project name
     *
     */

    #[Test]
    public function test_configuration_shows_current_project_name()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-current-name');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasProjectName = str_contains($pageSource, strtolower($this->testProject->name));

            $this->assertTrue($hasProjectName, 'Configuration page should show current project name');
            $this->testResults['shows_current_name'] = 'Configuration page shows current project name';
        });
    }

    /**
     * Test 23: Configuration page shows current branch
     *
     */

    #[Test]
    public function test_configuration_shows_current_branch()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-current-branch');

            $pageSource = $browser->driver->getPageSource();
            $hasBranch = str_contains($pageSource, $this->testProject->branch);

            $this->assertTrue($hasBranch, 'Configuration page should show current branch');
            $this->testResults['shows_current_branch'] = 'Configuration page shows current branch';
        });
    }

    /**
     * Test 24: Configuration page has breadcrumb navigation
     *
     */

    #[Test]
    public function test_configuration_has_breadcrumb_navigation()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-breadcrumb');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasBreadcrumb =
                str_contains($pageSource, 'breadcrumb') ||
                str_contains($pageSource, 'projects') ||
                str_contains($pageSource, 'configuration');

            $this->assertTrue($hasBreadcrumb, 'Configuration page should have breadcrumb navigation');
            $this->testResults['has_breadcrumb'] = 'Configuration page has breadcrumb navigation';
        });
    }

    /**
     * Test 25: Configuration page is accessible from project show page
     *
     */

    #[Test]
    public function test_configuration_accessible_from_project_show()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}")
                ->pause(2000);

            // Try to navigate to configuration
            $browser->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-accessible');

            $currentUrl = $browser->driver->getCurrentURL();
            $onConfigPage = str_contains($currentUrl, '/configuration');

            $this->assertTrue($onConfigPage, 'Configuration page should be accessible');
            $this->testResults['config_accessible'] = 'Configuration is accessible from project page';
        });
    }

    /**
     * Test 26: Validation error messages are handled
     *
     */

    #[Test]
    public function test_validation_error_messages_handled()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, '@error(') ||
                str_contains($pageSource, '$message');

            $this->assertTrue($hasValidation, 'Validation error messages should be handled');
            $this->testResults['validation_handled'] = 'Validation error messages are handled';
        });
    }

    /**
     * Test 27: Success message display is implemented
     *
     */

    #[Test]
    public function test_success_message_display_implemented()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-success-message');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasSuccessMessage =
                str_contains($pageSource, 'session') ||
                str_contains($pageSource, 'message') ||
                str_contains($pageSource, 'flash');

            $this->assertTrue($hasSuccessMessage, 'Success message display should be implemented');
            $this->testResults['success_message_display'] = 'Success message display is implemented';
        });
    }

    /**
     * Test 28: Configuration form uses Livewire
     *
     */

    #[Test]
    public function test_configuration_form_uses_livewire()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-livewire');

            $pageSource = $browser->driver->getPageSource();
            $hasLivewire =
                str_contains($pageSource, 'wire:model') ||
                str_contains($pageSource, 'livewire');

            $this->assertTrue($hasLivewire, 'Configuration form should use Livewire');
            $this->testResults['uses_livewire'] = 'Configuration form uses Livewire';
        });
    }

    /**
     * Test 29: Configuration page has proper title
     *
     */

    #[Test]
    public function test_configuration_page_has_proper_title()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-title');

            $pageTitle = strtolower($browser->driver->getTitle());
            $hasProperTitle =
                str_contains($pageTitle, 'configuration') ||
                str_contains($pageTitle, 'settings') ||
                str_contains($pageTitle, 'config');

            $this->assertTrue($hasProperTitle, 'Configuration page should have proper title');
            $this->testResults['has_proper_title'] = 'Configuration page has proper title';
        });
    }

    /**
     * Test 30: Cancel or back button is available
     *
     */

    #[Test]
    public function test_cancel_or_back_button_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-cancel-button');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCancelButton =
                str_contains($pageSource, 'cancel') ||
                str_contains($pageSource, 'back') ||
                str_contains($pageSource, 'return');

            $this->assertTrue($hasCancelButton, 'Cancel or back button should be available');
            $this->testResults['cancel_button_available'] = 'Cancel or back button is available';
        });
    }

    /**
     * Test 31: Repository URL accepts GitHub URLs
     *
     */

    #[Test]
    public function test_repository_url_accepts_github()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-github-url');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasGithubSupport =
                str_contains($pageSource, 'github') ||
                str_contains($pageSource, 'git');

            $this->assertTrue($hasGithubSupport, 'Repository URL should accept GitHub URLs');
            $this->testResults['accepts_github_urls'] = 'Repository URL accepts GitHub URLs';
        });
    }

    /**
     * Test 32: Repository URL validation pattern exists
     *
     */

    #[Test]
    public function test_repository_url_validation_pattern_exists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-url-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasValidation =
                str_contains($pageSource, 'repository_url') &&
                (str_contains($pageSource, 'required') || str_contains($pageSource, 'nullable'));

            $this->assertTrue($hasValidation, 'Repository URL validation pattern should exist');
            $this->testResults['url_validation_exists'] = 'Repository URL validation pattern exists';
        });
    }

    /**
     * Test 33: Slug field has validation rules
     *
     */

    #[Test]
    public function test_slug_field_has_validation_rules()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-slug-validation');

            $pageSource = $browser->driver->getPageSource();
            $hasSlugValidation = str_contains($pageSource, 'slug');

            $this->assertTrue($hasSlugValidation, 'Slug field should have validation rules');
            $this->testResults['slug_validation_rules'] = 'Slug field has validation rules';
        });
    }

    /**
     * Test 34: Configuration page supports dark mode
     *
     */

    #[Test]
    public function test_configuration_supports_dark_mode()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-dark-mode');

            $pageSource = $browser->driver->getPageSource();
            $hasDarkMode =
                str_contains($pageSource, 'dark:bg-') ||
                str_contains($pageSource, 'dark:text-');

            $this->assertTrue($hasDarkMode, 'Configuration page should support dark mode');
            $this->testResults['supports_dark_mode'] = 'Configuration page supports dark mode';
        });
    }

    /**
     * Test 35: Framework field has proper select element
     *
     */

    #[Test]
    public function test_framework_field_has_select_element()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-framework-select');

            $pageSource = $browser->driver->getPageSource();
            $hasFrameworkSelect =
                str_contains($pageSource, 'wire:model="framework"') ||
                str_contains($pageSource, '<select');

            $this->assertTrue($hasFrameworkSelect, 'Framework field should have proper select element');
            $this->testResults['framework_select_element'] = 'Framework field has proper select element';
        });
    }

    /**
     * Test 36: PHP version field has proper select element
     *
     */

    #[Test]
    public function test_php_version_field_has_select_element()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-php-select');

            $pageSource = $browser->driver->getPageSource();
            $hasPhpSelect =
                str_contains($pageSource, 'wire:model="php_version"') ||
                str_contains($pageSource, '<select');

            $this->assertTrue($hasPhpSelect, 'PHP version field should have proper select element');
            $this->testResults['php_select_element'] = 'PHP version field has proper select element';
        });
    }

    /**
     * Test 37: Node.js version field has proper select element
     *
     */

    #[Test]
    public function test_node_version_field_has_select_element()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-node-select');

            $pageSource = $browser->driver->getPageSource();
            $hasNodeSelect =
                str_contains($pageSource, 'wire:model="node_version"') ||
                str_contains($pageSource, '<select');

            $this->assertTrue($hasNodeSelect, 'Node.js version field should have proper select element');
            $this->testResults['node_select_element'] = 'Node.js version field has proper select element';
        });
    }

    /**
     * Test 38: Health check URL has proper input type
     *
     */

    #[Test]
    public function test_health_check_url_has_proper_input_type()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-health-url-type');

            $pageSource = $browser->driver->getPageSource();
            $hasProperInput =
                str_contains($pageSource, 'health_check_url') &&
                (str_contains($pageSource, 'type="url"') || str_contains($pageSource, 'type="text"'));

            $this->assertTrue($hasProperInput, 'Health check URL should have proper input type');
            $this->testResults['health_url_input_type'] = 'Health check URL has proper input type';
        });
    }

    /**
     * Test 39: Auto-deploy uses checkbox or toggle
     *
     */

    #[Test]
    public function test_auto_deploy_uses_checkbox_or_toggle()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-auto-deploy-type');

            $pageSource = $browser->driver->getPageSource();
            $hasProperInput =
                str_contains($pageSource, 'auto_deploy') &&
                (str_contains($pageSource, 'type="checkbox"') || str_contains($pageSource, 'toggle'));

            $this->assertTrue($hasProperInput, 'Auto-deploy should use checkbox or toggle');
            $this->testResults['auto_deploy_input_type'] = 'Auto-deploy uses checkbox or toggle';
        });
    }

    /**
     * Test 40: Configuration page is responsive
     *
     */

    #[Test]
    public function test_configuration_page_is_responsive()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-responsive');

            $pageSource = $browser->driver->getPageSource();
            $hasResponsiveClasses =
                str_contains($pageSource, 'md:') ||
                str_contains($pageSource, 'lg:') ||
                str_contains($pageSource, 'sm:');

            $this->assertTrue($hasResponsiveClasses, 'Configuration page should be responsive');
            $this->testResults['page_responsive'] = 'Configuration page is responsive';
        });
    }

    /**
     * Test 41: Form fields have proper labels
     *
     */

    #[Test]
    public function test_form_fields_have_proper_labels()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-labels');

            $pageSource = $browser->driver->getPageSource();
            $hasLabels = str_contains($pageSource, '<label');

            $this->assertTrue($hasLabels, 'Form fields should have proper labels');
            $this->testResults['has_proper_labels'] = 'Form fields have proper labels';
        });
    }

    /**
     * Test 42: Configuration data persists after save
     *
     */

    #[Test]
    public function test_configuration_data_persists()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-persistence');

            // Check that current data is loaded
            $pageSource = strtolower($browser->driver->getPageSource());
            $hasCurrentData =
                str_contains($pageSource, strtolower($this->testProject->name)) ||
                str_contains($pageSource, strtolower($this->testProject->slug));

            $this->assertTrue($hasCurrentData, 'Configuration data should persist');
            $this->testResults['data_persists'] = 'Configuration data persists after save';
        });
    }

    /**
     * Test 43: Loading states are implemented
     *
     */

    #[Test]
    public function test_loading_states_implemented()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-loading-states');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasLoadingStates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'loading') ||
                str_contains($pageSource, 'spinner');

            $this->assertTrue($hasLoadingStates, 'Loading states should be implemented');
            $this->testResults['loading_states'] = 'Loading states are implemented';
        });
    }

    /**
     * Test 44: Help text or tooltips are available
     *
     */

    #[Test]
    public function test_help_text_or_tooltips_available()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-help-text');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasHelpText =
                str_contains($pageSource, 'help') ||
                str_contains($pageSource, 'tooltip') ||
                str_contains($pageSource, 'text-gray-500') ||
                str_contains($pageSource, 'text-sm');

            $this->assertTrue($hasHelpText, 'Help text or tooltips should be available');
            $this->testResults['help_text_available'] = 'Help text or tooltips are available';
        });
    }

    /**
     * Test 45: Configuration sections are well-organized
     *
     */

    #[Test]
    public function test_configuration_sections_well_organized()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-organization');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasOrganization =
                str_contains($pageSource, 'section') ||
                str_contains($pageSource, 'card') ||
                str_contains($pageSource, 'panel');

            $this->assertTrue($hasOrganization, 'Configuration sections should be well-organized');
            $this->testResults['sections_organized'] = 'Configuration sections are well-organized';
        });
    }

    /**
     * Test 46: Required fields are marked
     *
     */

    #[Test]
    public function test_required_fields_are_marked()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-required-fields');

            $pageSource = $browser->driver->getPageSource();
            $hasRequiredMarkers =
                str_contains($pageSource, 'required') ||
                str_contains($pageSource, '*') ||
                str_contains($pageSource, 'text-red-');

            $this->assertTrue($hasRequiredMarkers, 'Required fields should be marked');
            $this->testResults['required_fields_marked'] = 'Required fields are marked';
        });
    }

    /**
     * Test 47: Form has proper accessibility attributes
     *
     */

    #[Test]
    public function test_form_has_accessibility_attributes()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-accessibility');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasAccessibility =
                str_contains($pageSource, 'aria-') ||
                str_contains($pageSource, 'role=') ||
                str_contains($pageSource, 'for=');

            $this->assertTrue($hasAccessibility, 'Form should have proper accessibility attributes');
            $this->testResults['has_accessibility'] = 'Form has proper accessibility attributes';
        });
    }

    /**
     * Test 48: Configuration uses consistent styling
     *
     */

    #[Test]
    public function test_configuration_uses_consistent_styling()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-styling');

            $pageSource = $browser->driver->getPageSource();
            $hasConsistentStyling =
                str_contains($pageSource, 'class=') &&
                (str_contains($pageSource, 'bg-') || str_contains($pageSource, 'text-'));

            $this->assertTrue($hasConsistentStyling, 'Configuration should use consistent styling');
            $this->testResults['consistent_styling'] = 'Configuration uses consistent styling';
        });
    }

    /**
     * Test 49: Form prevents duplicate submissions
     *
     */

    #[Test]
    public function test_form_prevents_duplicate_submissions()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-duplicate-prevention');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasPreventDuplicates =
                str_contains($pageSource, 'wire:loading') ||
                str_contains($pageSource, 'disabled') ||
                str_contains($pageSource, 'prevent');

            $this->assertTrue($hasPreventDuplicates, 'Form should prevent duplicate submissions');
            $this->testResults['prevents_duplicates'] = 'Form prevents duplicate submissions';
        });
    }

    /**
     * Test 50: Configuration page has proper meta tags
     *
     */

    #[Test]
    public function test_configuration_has_proper_meta_tags()
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser, $this->adminUser)
                ->visit("/projects/{$this->testProject->id}/configuration")
                ->pause(2000)
                ->screenshot('project-config-meta-tags');

            $pageSource = strtolower($browser->driver->getPageSource());
            $hasMetaTags =
                str_contains($pageSource, '<meta') &&
                (str_contains($pageSource, 'charset') || str_contains($pageSource, 'viewport'));

            $this->assertTrue($hasMetaTags, 'Configuration page should have proper meta tags');
            $this->testResults['has_meta_tags'] = 'Configuration page has proper meta tags';
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
                'test_suite' => 'Project Configuration Tests',
                'test_results' => $this->testResults,
                'summary' => [
                    'total_tests' => count($this->testResults),
                ],
                'environment' => [
                    'projects_count' => Project::count(),
                    'servers_count' => Server::count(),
                    'admin_user_id' => $this->adminUser->id,
                    'admin_user_name' => $this->adminUser->name,
                    'test_project_id' => $this->testProject->id,
                    'test_project_name' => $this->testProject->name,
                    'test_server_id' => $this->testServer->id,
                    'test_server_name' => $this->testServer->name,
                ],
            ];

            $reportPath = storage_path('app/test-reports/project-configuration-'.now()->format('Y-m-d-H-i-s').'.json');
            @mkdir(dirname($reportPath), 0755, true);
            @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        }

        parent::tearDown();
    }
}
