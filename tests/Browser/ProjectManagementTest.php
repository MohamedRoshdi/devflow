<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProjectManagementTest extends DuskTestCase
{
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
                'repository_url' => 'https://github.com/test/test-project.git',
                'branch' => 'main',
                'root_directory' => '/var/www/test-project',
            ]
        );
    }

    /**
     * Test 1: Projects list page loads with all projects visible
     */
    public function test_projects_list_page_loads_with_all_projects_visible(): void
    {
        // Create additional projects
        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Second Project',
            'slug' => 'second-project',
            'status' => 'stopped',
        ]);

        $project3 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Third Project',
            'slug' => 'third-project',
            'status' => 'running',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->assertSee('Projects')
                ->assertSee('Test Project')
                ->assertSee('Second Project')
                ->assertSee('Third Project')
                ->assertPresent('@project-list');
        });
    }

    /**
     * Test 2: Project cards display correct information
     */
    public function test_project_cards_display_correct_information(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Test Project')
                ->assertSee('Test Project')
                ->assertSee('running')
                ->assertSee('laravel')
                ->assertSee('Test Server')
                ->assertVisible('[data-project-slug="test-project"]');
        });
    }

    /**
     * Test 3: Create new project button navigates to creation form
     */
    public function test_create_new_project_button_navigates_to_creation_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects')
                ->click('@create-project-button')
                ->assertPathIs('/projects/create')
                ->assertSee('Create New Project');
        });
    }

    /**
     * Test 4: Project creation form has all required fields
     */
    public function test_project_creation_form_has_all_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->assertSee('Create New Project')
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="slug"]')
                ->assertPresent('select[name="server_id"]')
                ->assertPresent('input[name="repository_url"]')
                ->assertPresent('input[name="branch"]')
                ->assertPresent('select[name="framework"]')
                ->assertPresent('select[name="php_version"]')
                ->assertPresent('input[name="root_directory"]');
        });
    }

    /**
     * Test 5: Project creation validation works (required fields)
     */
    public function test_project_creation_validation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create New Project')
                // Try to submit without filling required fields in step 1
                ->press('Next')
                ->waitFor('.text-red-600', 5)
                ->assertSee('required');
        });
    }

    /**
     * Test 6: Click on project card navigates to project detail page
     */
    public function test_click_on_project_card_navigates_to_detail_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Test Project')
                ->click('[data-project-slug="test-project"]')
                ->waitForLocation('/projects/test-project')
                ->assertPathIs('/projects/test-project')
                ->assertSee('Test Project')
                ->assertSee('Overview');
        });
    }

    /**
     * Test 7: Project detail page shows all tabs
     */
    public function test_project_detail_page_shows_all_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->assertSee('Overview')
                ->assertSee('Docker')
                ->assertSee('Environment')
                ->assertSee('Git')
                ->assertSee('Deployments')
                ->assertPresent('@tab-overview')
                ->assertPresent('@tab-docker')
                ->assertPresent('@tab-environment')
                ->assertPresent('@tab-git')
                ->assertPresent('@tab-deployments');
        });
    }

    /**
     * Test 8: Switching tabs works correctly
     */
    public function test_switching_tabs_works_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                // Click Docker tab
                ->click('@tab-docker')
                ->pause(500)
                ->assertSee('Docker Containers')
                // Click Environment tab
                ->click('@tab-environment')
                ->pause(500)
                ->assertSee('Environment Variables')
                // Click Deployments tab
                ->click('@tab-deployments')
                ->pause(500)
                ->assertSee('Deployment History');
        });
    }

    /**
     * Test 9: Git tab shows commit history
     */
    public function test_git_tab_shows_commit_history(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->click('@tab-git')
                ->pause(1000) // Wait for git data to load
                ->assertSee('Git Repository')
                ->assertSee('Recent Commits');
        });
    }

    /**
     * Test 10: Git auto-refresh toggle works
     */
    public function test_git_auto_refresh_toggle_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->click('@tab-git')
                ->pause(1000)
                ->assertPresent('@auto-refresh-toggle')
                ->click('@auto-refresh-toggle')
                ->pause(300)
                // Toggle should change state (Livewire will handle the state change)
                ->assertPresent('@auto-refresh-toggle');
        });
    }

    /**
     * Test 11: Deploy button triggers deployment
     */
    public function test_deploy_button_triggers_deployment(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->assertPresent('@deploy-button')
                ->click('@deploy-button')
                ->waitFor('@deploy-modal', 3)
                ->assertSee('Confirm Deployment')
                ->press('Deploy')
                ->pause(1000)
                // Should redirect to deployment show page
                ->assertPathBeginsWith('/deployments/');
        });
    }

    /**
     * Test 12: Project configuration link works
     */
    public function test_project_configuration_link_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->assertPresent('@config-button')
                ->click('@config-button')
                ->waitForLocation('/projects/'.$this->project->slug.'/configuration')
                ->assertPathIs('/projects/'.$this->project->slug.'/configuration')
                ->assertSee('Project Configuration')
                ->assertSee('Basic Information');
        });
    }

    /**
     * Test 13: Environment variables can be added/edited
     */
    public function test_environment_variables_can_be_added(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->click('@tab-environment')
                ->pause(500)
                ->assertSee('Environment Variables')
                ->assertPresent('@add-env-button')
                ->click('@add-env-button')
                ->waitFor('@env-modal', 3)
                ->type('@env-key-input', 'TEST_KEY')
                ->type('@env-value-input', 'test_value')
                ->press('Add Variable')
                ->pause(1000)
                ->assertSee('TEST_KEY');
        });
    }

    /**
     * Test 14: Docker container status is displayed
     */
    public function test_docker_container_status_is_displayed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->click('@tab-docker')
                ->pause(500)
                ->assertSee('Docker Containers')
                ->assertPresent('@docker-status');
        });
    }

    /**
     * Test 15: Search/filter functionality on projects list
     */
    public function test_search_filter_functionality_works(): void
    {
        // Create additional projects
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel App',
            'slug' => 'laravel-app',
            'framework' => 'laravel',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React App',
            'slug' => 'react-app',
            'framework' => 'react',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects')
                ->assertSee('Laravel App')
                ->assertSee('React App')
                // Search for "Laravel"
                ->type('@search-input', 'Laravel')
                ->pause(1000) // Wait for Livewire to update
                ->assertSee('Laravel App')
                ->assertDontSee('React App')
                // Clear search
                ->clear('@search-input')
                ->pause(1000)
                ->assertSee('Laravel App')
                ->assertSee('React App');
        });
    }

    /**
     * Test 16: Project status badges display correctly
     */
    public function test_project_status_badges_display_correctly(): void
    {
        // Create projects with different statuses
        $runningProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Running Project',
            'slug' => 'running-project',
            'status' => 'running',
        ]);

        $stoppedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Stopped Project',
            'slug' => 'stopped-project',
            'status' => 'stopped',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects')
                ->assertSee('running')
                ->assertSee('stopped')
                ->assertPresent('[data-status="running"]')
                ->assertPresent('[data-status="stopped"]');
        });
    }

    /**
     * Test 17: Delete project shows confirmation
     */
    public function test_delete_project_shows_confirmation(): void
    {
        $projectToDelete = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project To Delete',
            'slug' => 'project-to-delete',
        ]);

        $this->browse(function (Browser $browser) use ($projectToDelete) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Project To Delete')
                ->click('[data-delete-project="'.$projectToDelete->id.'"]')
                ->waitFor('@delete-confirmation-modal', 3)
                ->assertSee('Are you sure')
                ->assertSee('delete')
                ->press('Cancel')
                ->pause(500)
                ->assertSee('Project To Delete');
        });
    }

    /**
     * Test project creation with wizard steps
     */
    public function test_project_creation_wizard_completes_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/create')
                ->waitForText('Create New Project')
                // Step 1: Basic Info
                ->type('input[name="name"]', 'New Test Project')
                ->pause(300) // Wait for slug auto-generation
                ->type('input[name="repository_url"]', 'https://github.com/test/new-project.git')
                ->select('select[name="server_id"]', $this->server->id)
                ->type('input[name="branch"]', 'main')
                ->press('Next')
                ->pause(1000)
                // Step 2: Framework & Build
                ->waitForText('Framework')
                ->select('select[name="framework"]', 'laravel')
                ->select('select[name="php_version"]', '8.4')
                ->type('input[name="root_directory"]', '/')
                ->press('Next')
                ->pause(1000)
                // Step 3: Setup Options
                ->waitForText('Setup Options')
                ->press('Next')
                ->pause(1000)
                // Step 4: Review and Create
                ->waitForText('Review')
                ->press('Create Project')
                ->pause(2000)
                ->waitForText('created successfully', 10)
                ->assertSee('created successfully');
        });
    }

    /**
     * Test Livewire component interactions on project list
     */
    public function test_livewire_status_filter_works(): void
    {
        // Create projects with different statuses
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'name' => 'Running Project Filter Test',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
            'name' => 'Stopped Project Filter Test',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects')
                ->select('@status-filter', 'running')
                ->pause(1000) // Wait for Livewire to filter
                ->assertSee('Running Project Filter Test')
                ->assertDontSee('Stopped Project Filter Test')
                ->select('@status-filter', 'stopped')
                ->pause(1000)
                ->assertSee('Stopped Project Filter Test')
                ->assertDontSee('Running Project Filter Test')
                ->select('@status-filter', '')
                ->pause(1000)
                ->assertSee('Running Project Filter Test')
                ->assertSee('Stopped Project Filter Test');
        });
    }

    /**
     * Test project detail page shows deployment history
     */
    public function test_project_detail_shows_deployment_history(): void
    {
        // Create deployments for the project
        $deployment1 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'branch' => 'main',
            'triggered_by' => 'manual',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2)->addMinutes(5),
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'branch' => 'main',
            'triggered_by' => 'webhook',
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1)->addMinutes(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                ->click('@tab-deployments')
                ->pause(500)
                ->assertSee('Deployment History')
                ->assertSee('success')
                ->assertSee('failed')
                ->assertSee('manual')
                ->assertSee('webhook');
        });
    }

    /**
     * Test project quick actions work
     */
    public function test_project_quick_actions_work(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug)
                ->waitForText('Test Project')
                // Test start/stop buttons
                ->assertPresent('@stop-project-button')
                ->click('@stop-project-button')
                ->pause(1000)
                ->waitForText('stopped', 5)
                ->assertPresent('@start-project-button')
                ->click('@start-project-button')
                ->pause(1000)
                ->waitForText('running', 5);
        });
    }

    /**
     * Test project pagination on list page
     */
    public function test_project_pagination_works(): void
    {
        // Create 15 projects to test pagination (default is 12 per page)
        Project::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects')
                ->waitForText('Projects')
                ->assertPresent('@pagination')
                ->assertSee('Next')
                ->click('[rel="next"]') // Click next page
                ->pause(1000)
                ->assertPresent('@project-list');
        });
    }
}
