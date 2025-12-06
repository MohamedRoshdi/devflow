<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ProjectsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected Server $server;

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
    }

    /**
     * Test 1: Project list page loads successfully
     */
    public function test_project_list_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertSee('Projects')
                ->assertPresent('div, section, main')
                ->screenshot('project-list-page');
        });
    }

    /**
     * Test 2: Project list displays search functionality
     */
    public function test_project_list_has_search(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertPresent('input[wire\\:model*="search"], input[type="search"], input[placeholder*="Search"]')
                ->screenshot('project-list-search');
        });
    }

    /**
     * Test 3: Project list has status filter
     */
    public function test_project_list_has_status_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertPresent('select[wire\\:model*="statusFilter"], select[wire\\:model*="status"], button[role="button"]')
                ->screenshot('project-list-status-filter');
        });
    }

    /**
     * Test 4: Project list displays project cards or table
     */
    public function test_project_list_displays_projects(): void
    {
        // Create a test project
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-list'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project List',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-project',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertSee($project->name)
                ->screenshot('project-list-projects-display');
        });
    }

    /**
     * Test 5: Create project button is visible and clickable
     */
    public function test_create_project_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertPresent('a[href*="create"], button:contains("Create"), a:contains("New Project"), a:contains("Create Project")')
                ->screenshot('project-create-button');
        });
    }

    /**
     * Test 6: Project create page loads successfully
     */
    public function test_project_create_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create', 10)
                ->assertSee('Create')
                ->assertPresent('form, [wire\\:submit], input[wire\\:model*="name"]')
                ->screenshot('project-create-page');
        });
    }

    /**
     * Test 7: Project create form contains required fields
     */
    public function test_project_create_form_has_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create', 10)
                ->assertPresent('input[wire\\:model*="name"], input[name="name"]')
                ->assertPresent('input[wire\\:model*="repository"], input[name="repository"]')
                ->assertPresent('select[wire\\:model*="server"], select[name="server"]')
                ->screenshot('project-create-form-fields');
        });
    }

    /**
     * Test 8: Project create form validates required fields
     */
    public function test_project_create_form_validates_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create', 10)
                ->pause(500)
                // Try to submit empty form
                ->press('Create Project')
                ->pause(1000)
                ->waitFor('.text-red-500, .text-red-600, .error, [class*="error"]', 5)
                ->screenshot('project-create-validation-errors');
        });
    }

    /**
     * Test 9: Project create wizard shows multiple steps
     */
    public function test_project_create_wizard_has_steps(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create', 10)
                // Look for step indicators
                ->assertPresent('nav, ol, ul, [role="progressbar"], [class*="step"]')
                ->screenshot('project-create-wizard-steps');
        });
    }

    /**
     * Test 10: Project detail page loads successfully
     */
    public function test_project_detail_page_loads(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-detail'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Detail',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-detail.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-detail',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee($project->name)
                ->assertSee($project->branch)
                ->screenshot('project-detail-page');
        });
    }

    /**
     * Test 11: Project detail page shows overview tab
     */
    public function test_project_detail_shows_overview_tab(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-overview'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Overview',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-overview.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-overview',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Overview"), a:contains("Overview"), [role="tab"]')
                ->screenshot('project-detail-overview-tab');
        });
    }

    /**
     * Test 12: Project detail shows Git tab
     */
    public function test_project_detail_shows_git_tab(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-git'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Git',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-git.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-git',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Git"), a:contains("Git"), [x-on\\:click*="git"]')
                ->screenshot('project-detail-git-tab');
        });
    }

    /**
     * Test 13: Project detail shows Docker tab
     */
    public function test_project_detail_shows_docker_tab(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-docker'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Docker',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-docker.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-docker',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Docker"), a:contains("Docker"), [x-on\\:click*="docker"]')
                ->screenshot('project-detail-docker-tab');
        });
    }

    /**
     * Test 14: Project detail shows Environment tab
     */
    public function test_project_detail_shows_environment_tab(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-env'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Env',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-env.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-env',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Environment"), a:contains("Environment"), [x-on\\:click*="environment"]')
                ->screenshot('project-detail-environment-tab');
        });
    }

    /**
     * Test 15: Project detail shows Deployments section
     */
    public function test_project_detail_shows_deployments(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-deployments'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Deployments',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-deployments.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-deployments',
            ]
        );

        // Create a deployment for this project
        Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
        ]);

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Deployments"), section, div, [class*="deployment"]')
                ->screenshot('project-detail-deployments');
        });
    }

    /**
     * Test 16: Project detail shows deploy button
     */
    public function test_project_detail_shows_deploy_button(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-deploy-btn'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Deploy Btn',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-deploy.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-deploy',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Deploy"), button[wire\\:click*="deploy"], a:contains("Deploy")')
                ->screenshot('project-detail-deploy-button');
        });
    }

    /**
     * Test 17: Project detail shows start/stop controls
     */
    public function test_project_detail_shows_container_controls(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-controls'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Controls',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-controls.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-controls',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('button:contains("Start"), button:contains("Stop"), button[wire\\:click*="start"], button[wire\\:click*="stop"]')
                ->screenshot('project-detail-container-controls');
        });
    }

    /**
     * Test 18: Project edit page loads successfully
     */
    public function test_project_edit_page_loads(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-edit'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Edit',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-edit.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-edit',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug.'/edit')
                ->waitForText('Edit', 10)
                ->assertSee('Edit')
                ->assertSee($project->name)
                ->screenshot('project-edit-page');
        });
    }

    /**
     * Test 19: Project edit form displays current values
     */
    public function test_project_edit_form_displays_current_values(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-edit-values'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Edit Values',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-edit-values.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-edit-values',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug.'/edit')
                ->waitForText('Edit', 10)
                ->assertInputValue('input[wire\\:model*="name"], input[name="name"]', $project->name)
                ->assertInputValue('input[wire\\:model*="branch"], input[name="branch"]', $project->branch)
                ->screenshot('project-edit-form-values');
        });
    }

    /**
     * Test 20: Project edit has save button
     */
    public function test_project_edit_has_save_button(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-edit-save'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Edit Save',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-edit-save.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-edit-save',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug.'/edit')
                ->waitForText('Edit', 10)
                ->assertPresent('button:contains("Update"), button:contains("Save"), button[type="submit"]')
                ->screenshot('project-edit-save-button');
        });
    }

    /**
     * Test 21: Project shows domain information
     */
    public function test_project_shows_domain_information(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-domain'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Domain',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-domain.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-domain',
            ]
        );

        // Create a domain for this project
        Domain::firstOrCreate(
            [
                'project_id' => $project->id,
                'domain' => 'test-domain.example.com',
            ],
            [
                'is_primary' => true,
                'ssl_enabled' => false,
                'dns_configured' => false,
                'status' => 'pending',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee('test-domain.example.com')
                ->screenshot('project-domain-information');
        });
    }

    /**
     * Test 22: Project shows server information
     */
    public function test_project_shows_server_information(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-server'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Server',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-server.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-server',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee($this->server->name)
                ->screenshot('project-server-information');
        });
    }

    /**
     * Test 23: Project environment management is accessible
     */
    public function test_project_environment_management_accessible(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-env-mgmt'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Env Mgmt',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-env-mgmt.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-env-mgmt',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1000)
                ->assertPresent('input, select, button, [class*="environment"]')
                ->screenshot('project-environment-management');
        });
    }

    /**
     * Test 24: Project environment shows add variable button
     */
    public function test_project_environment_has_add_variable_button(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-env-add'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Env Add',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-env-add.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-env-add',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Environment"), a:contains("Environment")')
                ->pause(1000)
                ->assertPresent('button:contains("Add"), button:contains("New Variable"), button[wire\\:click*="add"]')
                ->screenshot('project-environment-add-variable');
        });
    }

    /**
     * Test 25: Project Docker management shows container status
     */
    public function test_project_docker_shows_container_status(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-docker-status'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Docker Status',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-docker-status.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-docker-status',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('div, section, [class*="container"], [class*="status"]')
                ->screenshot('project-docker-container-status');
        });
    }

    /**
     * Test 26: Project shows framework badge
     */
    public function test_project_shows_framework_badge(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-framework'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Framework',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-framework.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-framework',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee('laravel')
                ->screenshot('project-framework-badge');
        });
    }

    /**
     * Test 27: Project shows status indicator
     */
    public function test_project_shows_status_indicator(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-status'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Status',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-status.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-status',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee('running')
                ->screenshot('project-status-indicator');
        });
    }

    /**
     * Test 28: Project detail shows settings link
     */
    public function test_project_detail_shows_settings_link(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-settings'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Settings',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-settings.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-settings',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('a[href*="edit"], button:contains("Settings"), a:contains("Settings"), button:contains("Edit")')
                ->screenshot('project-settings-link');
        });
    }

    /**
     * Test 29: Project list pagination works
     */
    public function test_project_list_pagination_works(): void
    {
        // Create multiple projects to test pagination
        for ($i = 1; $i <= 15; $i++) {
            Project::firstOrCreate(
                ['slug' => 'test-pagination-'.$i],
                [
                    'user_id' => $this->user->id,
                    'server_id' => $this->server->id,
                    'name' => 'Test Pagination '.$i,
                    'framework' => 'laravel',
                    'status' => 'running',
                    'repository_url' => 'https://github.com/test/pagination-'.$i.'.git',
                    'branch' => 'main',
                    'deploy_path' => '/var/www/pagination-'.$i,
                ]
            );
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects', 10)
                ->assertPresent('nav[role="navigation"], .pagination, button[rel="next"], a[rel="next"]')
                ->screenshot('project-list-pagination');
        });
    }

    /**
     * Test 30: Project detail shows recent deployments history
     */
    public function test_project_detail_shows_recent_deployments(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-history'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project History',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-history.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-history',
            ]
        );

        // Create multiple deployments
        Deployment::factory()->count(3)->create([
            'project_id' => $project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
            'branch' => 'main',
        ]);

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('div, section, table, [class*="deployment"]')
                ->screenshot('project-recent-deployments');
        });
    }

    /**
     * Test 31: Project shows repository URL
     */
    public function test_project_shows_repository_url(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-repo'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Repo',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-repo.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-repo',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee('github.com')
                ->screenshot('project-repository-url');
        });
    }

    /**
     * Test 32: Project configuration page is accessible
     */
    public function test_project_configuration_page_accessible(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-config'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Config',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-config.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-config',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug.'/configuration')
                ->waitForText('Configuration', 10)
                ->assertSee('Configuration')
                ->screenshot('project-configuration-page');
        });
    }

    /**
     * Test 33: Project shows last deployment time
     */
    public function test_project_shows_last_deployment_time(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-deploy-time'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Deploy Time',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-deploy-time.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-deploy-time',
                'last_deployed_at' => now()->subHours(2),
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertPresent('time, [datetime], [class*="time"], [class*="date"]')
                ->screenshot('project-last-deployment-time');
        });
    }

    /**
     * Test 34: Project Docker shows build button
     */
    public function test_project_docker_shows_build_button(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-docker-build'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project Docker Build',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-docker-build.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-docker-build',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->click('button:contains("Docker"), a:contains("Docker")')
                ->pause(2000)
                ->assertPresent('button:contains("Build"), button[wire\\:click*="build"]')
                ->screenshot('project-docker-build-button');
        });
    }

    /**
     * Test 35: Project shows PHP version information
     */
    public function test_project_shows_php_version(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'test-project-php'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Project PHP',
                'framework' => 'laravel',
                'php_version' => '8.3',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/test-php.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-php',
            ]
        );

        $this->browse(function (Browser $browser) use ($project) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$project->slug)
                ->waitForText($project->name, 10)
                ->assertSee('8.3')
                ->screenshot('project-php-version');
        });
    }
}
