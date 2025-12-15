<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\LogSource;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class LogSourceManagerTest extends DuskTestCase
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
            ['hostname' => 'test-log-server.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Test Log Server',
                'ip_address' => '192.168.1.150',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-log-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Log Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-log-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-log-project',
            ]
        );
    }

    /**
     * Test log source manager page loads successfully
     *
     */

    #[Test]
    public function log_source_manager_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Log Sources')
                ->assertSee($this->server->name)
                ->assertPresent('button:contains("Add Log Source")')
                ->screenshot('log-source-manager-page');
        });
    }

    /**
     * Test opening add log source modal
     *
     */

    #[Test]
    public function opening_add_log_source_modal_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertSee('Add Log Source')
                ->assertSee('Name')
                ->assertSee('Type')
                ->assertSee('Path')
                ->assertSee('Project')
                ->screenshot('add-log-source-modal-open');
        });
    }

    /**
     * Test creating new file log source
     *
     */

    #[Test]
    public function creating_new_file_log_source_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Application Error Logs')
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/log/app/error.log')
                ->pause(500)
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertSee('Application Error Logs')
                ->screenshot('log-source-created-file');
        });
    }

    /**
     * Test creating docker log source
     *
     */

    #[Test]
    public function creating_docker_log_source_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Docker App Container')
                ->select('select[wire\\:model="type"]', 'docker')
                ->type('input[wire\\:model="path"]', 'devflow_app_container')
                ->pause(500)
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertSee('Docker App Container')
                ->screenshot('log-source-created-docker');
        });
    }

    /**
     * Test creating journald log source
     *
     */

    #[Test]
    public function creating_journald_log_source_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'System Journal Logs')
                ->select('select[wire\\:model="type"]', 'journald')
                ->type('input[wire\\:model="path"]', 'nginx.service')
                ->pause(500)
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertSee('System Journal Logs')
                ->screenshot('log-source-created-journald');
        });
    }

    /**
     * Test creating log source with project association
     *
     */

    #[Test]
    public function creating_log_source_with_project_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Project Specific Logs')
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/www/project/logs/app.log')
                ->select('select[wire\\:model="project_id"]', (string) $this->project->id)
                ->pause(500)
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertSee('Project Specific Logs')
                ->assertSee($this->project->name)
                ->screenshot('log-source-with-project');
        });
    }

    /**
     * Test validation error for empty name
     *
     */

    #[Test]
    public function validation_error_for_empty_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/log/test.log')
                ->press('Add Source')
                ->pause(500)
                ->assertSee('The name field is required')
                ->screenshot('validation-error-empty-name');
        });
    }

    /**
     * Test validation error for empty path
     *
     */

    #[Test]
    public function validation_error_for_empty_path(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Test Log Source')
                ->select('select[wire\\:model="type"]', 'file')
                ->press('Add Source')
                ->pause(500)
                ->assertSee('The path field is required')
                ->screenshot('validation-error-empty-path');
        });
    }

    /**
     * Test using predefined Laravel template
     *
     */

    #[Test]
    public function using_laravel_template_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertSee('Templates')
                ->click('button:contains("Laravel")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', 'Laravel Application Logs')
                ->assertSelected('select[wire\\:model="type"]', 'file')
                ->assertInputValue('input[wire\\:model="path"]', '/var/www/*/storage/logs/laravel.log')
                ->screenshot('template-laravel-applied');
        });
    }

    /**
     * Test using predefined Nginx access template
     *
     */

    #[Test]
    public function using_nginx_access_template_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->click('button:contains("Nginx Access")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', 'Nginx Access Logs')
                ->assertSelected('select[wire\\:model="type"]', 'file')
                ->assertInputValue('input[wire\\:model="path"]', '/var/log/nginx/access.log')
                ->screenshot('template-nginx-access-applied');
        });
    }

    /**
     * Test using predefined Nginx error template
     *
     */

    #[Test]
    public function using_nginx_error_template_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->click('button:contains("Nginx Error")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', 'Nginx Error Logs')
                ->assertSelected('select[wire\\:model="type"]', 'file')
                ->assertInputValue('input[wire\\:model="path"]', '/var/log/nginx/error.log')
                ->screenshot('template-nginx-error-applied');
        });
    }

    /**
     * Test using predefined Docker template
     *
     */

    #[Test]
    public function using_docker_template_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->click('button:contains("Docker")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', 'Docker Container Logs')
                ->assertSelected('select[wire\\:model="type"]', 'docker')
                ->assertInputValue('input[wire\\:model="path"]', 'container_name')
                ->screenshot('template-docker-applied');
        });
    }

    /**
     * Test using predefined MySQL template
     *
     */

    #[Test]
    public function using_mysql_template_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->click('button:contains("MySQL")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', 'MySQL Error Logs')
                ->assertSelected('select[wire\\:model="type"]', 'file')
                ->assertInputValue('input[wire\\:model="path"]', '/var/log/mysql/error.log')
                ->screenshot('template-mysql-applied');
        });
    }

    /**
     * Test closing add modal without saving
     *
     */

    #[Test]
    public function closing_modal_without_saving_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Temporary Source')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->assertDontSee('Add Log Source')
                ->assertDontSee('Temporary Source')
                ->screenshot('modal-closed-without-saving');
        });
    }

    /**
     * Test editing existing log source
     *
     */

    #[Test]
    public function editing_existing_log_source_works(): void
    {
        // Create a log source first
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => null,
            'name' => 'Original Name',
            'type' => 'file',
            'path' => '/var/log/original.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Original Name')
                ->click('button[wire\\:click="editSource('.$logSource->id.')"]')
                ->pause(500)
                ->assertSee('Edit Log Source')
                ->assertInputValue('input[wire\\:model="name"]', 'Original Name')
                ->clear('input[wire\\:model="name"]')
                ->type('input[wire\\:model="name"]', 'Updated Name')
                ->clear('input[wire\\:model="path"]')
                ->type('input[wire\\:model="path"]', '/var/log/updated.log')
                ->press('Update Source')
                ->pause(1000)
                ->waitForText('Log source updated successfully')
                ->assertSee('Updated Name')
                ->assertDontSee('Original Name')
                ->screenshot('log-source-edited');
        });

        $logSource->delete();
    }

    /**
     * Test editing log source type
     *
     */

    #[Test]
    public function editing_log_source_type_works(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => null,
            'name' => 'Test Source',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button[wire\\:click="editSource('.$logSource->id.')"]')
                ->pause(500)
                ->select('select[wire\\:model="type"]', 'docker')
                ->clear('input[wire\\:model="path"]')
                ->type('input[wire\\:model="path"]', 'container_name')
                ->press('Update Source')
                ->pause(1000)
                ->waitForText('Log source updated successfully')
                ->screenshot('log-source-type-changed');
        });

        $logSource->delete();
    }

    /**
     * Test toggling log source active status
     *
     */

    #[Test]
    public function toggling_log_source_status_works(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => null,
            'name' => 'Toggle Test Source',
            'type' => 'file',
            'path' => '/var/log/toggle.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Toggle Test Source')
                ->click('button[wire\\:click="toggleSource('.$logSource->id.')"]')
                ->pause(1000)
                ->waitForText('Log source disabled')
                ->screenshot('log-source-disabled')
                ->click('button[wire\\:click="toggleSource('.$logSource->id.')"]')
                ->pause(1000)
                ->waitForText('Log source enabled')
                ->screenshot('log-source-enabled');
        });

        $logSource->delete();
    }

    /**
     * Test active status indicator shows correctly
     *
     */

    #[Test]
    public function active_status_indicator_shows_correctly(): void
    {
        $activeSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Active Source',
            'type' => 'file',
            'path' => '/var/log/active.log',
            'is_active' => true,
        ]);

        $inactiveSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Inactive Source',
            'type' => 'file',
            'path' => '/var/log/inactive.log',
            'is_active' => false,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Active Source')
                ->assertSee('Inactive Source')
                ->assertPresent('.bg-green-100, .bg-emerald-100')
                ->assertPresent('.bg-gray-100')
                ->screenshot('status-indicators');
        });

        $activeSource->delete();
        $inactiveSource->delete();
    }

    /**
     * Test deleting log source
     *
     */

    #[Test]
    public function deleting_log_source_works(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => null,
            'name' => 'Source to Delete',
            'type' => 'file',
            'path' => '/var/log/delete.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Source to Delete')
                ->click('button[wire\\:click="removeSource('.$logSource->id.')"]')
                ->pause(1000)
                ->waitForText('Log source removed successfully')
                ->assertDontSee('Source to Delete')
                ->screenshot('log-source-deleted');
        });
    }

    /**
     * Test testing log source connection
     *
     */

    #[Test]
    public function testing_log_source_connection_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Test Connection Source')
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/log/syslog')
                ->click('button:contains("Test Connection")')
                ->pause(2000)
                ->screenshot('test-connection-result');
        });
    }

    /**
     * Test log source list shows all sources
     *
     */

    #[Test]
    public function log_source_list_shows_all_sources(): void
    {
        $sources = [];
        for ($i = 1; $i <= 5; $i++) {
            $sources[] = LogSource::create([
                'server_id' => $this->server->id,
                'name' => "Log Source {$i}",
                'type' => 'file',
                'path' => "/var/log/source{$i}.log",
                'is_active' => true,
            ]);
        }

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Log Source 1')
                ->assertSee('Log Source 2')
                ->assertSee('Log Source 3')
                ->assertSee('Log Source 4')
                ->assertSee('Log Source 5')
                ->screenshot('log-source-list-all');
        });

        foreach ($sources as $source) {
            $source->delete();
        }
    }

    /**
     * Test log source shows correct type badge
     *
     */

    #[Test]
    public function log_source_shows_correct_type_badge(): void
    {
        $fileSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'File Source',
            'type' => 'file',
            'path' => '/var/log/file.log',
            'is_active' => true,
        ]);

        $dockerSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Docker Source',
            'type' => 'docker',
            'path' => 'container',
            'is_active' => true,
        ]);

        $journaldSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Journald Source',
            'type' => 'journald',
            'path' => 'service',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('File Source')
                ->assertSee('Docker Source')
                ->assertSee('Journald Source')
                ->screenshot('type-badges');
        });

        $fileSource->delete();
        $dockerSource->delete();
        $journaldSource->delete();
    }

    /**
     * Test log source displays path correctly
     *
     */

    #[Test]
    public function log_source_displays_path_correctly(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Path Display Test',
            'type' => 'file',
            'path' => '/var/www/app/storage/logs/laravel.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Path Display Test')
                ->assertSee('/var/www/app/storage/logs/laravel.log')
                ->screenshot('path-display');
        });

        $logSource->delete();
    }

    /**
     * Test syncing log source
     *
     */

    #[Test]
    public function syncing_log_source_works(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Sync Test Source',
            'type' => 'file',
            'path' => '/var/log/test.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Sync Test Source')
                ->click('button[wire\\:click="syncSource('.$logSource->id.')"]')
                ->pause(2000)
                ->screenshot('log-source-synced');
        });

        $logSource->delete();
    }

    /**
     * Test log source with project shows project name
     *
     */

    #[Test]
    public function log_source_with_project_shows_project_name(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => $this->project->id,
            'name' => 'Project Log Source',
            'type' => 'file',
            'path' => '/var/www/project/logs/app.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Project Log Source')
                ->assertSee($this->project->name)
                ->screenshot('log-source-with-project-name');
        });

        $logSource->delete();
    }

    /**
     * Test empty state when no log sources exist
     *
     */

    #[Test]
    public function empty_state_displays_when_no_sources(): void
    {
        // Clean up any existing sources
        LogSource::where('server_id', $this->server->id)->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('No log sources configured')
                ->assertPresent('button:contains("Add Log Source")')
                ->screenshot('log-sources-empty-state');
        });
    }

    /**
     * Test log source card shows all information
     *
     */

    #[Test]
    public function log_source_card_shows_all_information(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'project_id' => $this->project->id,
            'name' => 'Complete Info Source',
            'type' => 'file',
            'path' => '/var/www/logs/complete.log',
            'is_active' => true,
            'last_synced_at' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Complete Info Source')
                ->assertSee('/var/www/logs/complete.log')
                ->assertSee($this->project->name)
                ->screenshot('log-source-complete-info');
        });

        $logSource->delete();
    }

    /**
     * Test last sync time displays correctly
     *
     */

    #[Test]
    public function last_sync_time_displays_correctly(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Sync Time Test',
            'type' => 'file',
            'path' => '/var/log/sync.log',
            'is_active' => true,
            'last_synced_at' => now()->subMinutes(30),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Sync Time Test')
                ->assertSee('30 minutes ago')
                ->screenshot('last-sync-time');
        });

        $logSource->delete();
    }

    /**
     * Test multiple log sources of different types
     *
     */

    #[Test]
    public function multiple_log_sources_different_types_display(): void
    {
        $sources = [
            LogSource::create([
                'server_id' => $this->server->id,
                'name' => 'File Type Source',
                'type' => 'file',
                'path' => '/var/log/file.log',
                'is_active' => true,
            ]),
            LogSource::create([
                'server_id' => $this->server->id,
                'name' => 'Docker Type Source',
                'type' => 'docker',
                'path' => 'container_name',
                'is_active' => true,
            ]),
            LogSource::create([
                'server_id' => $this->server->id,
                'name' => 'Journald Type Source',
                'type' => 'journald',
                'path' => 'service.name',
                'is_active' => true,
            ]),
        ];

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('File Type Source')
                ->assertSee('Docker Type Source')
                ->assertSee('Journald Type Source')
                ->screenshot('multiple-types-display');
        });

        foreach ($sources as $source) {
            $source->delete();
        }
    }

    /**
     * Test log source actions menu exists
     *
     */

    #[Test]
    public function log_source_actions_menu_exists(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Actions Menu Test',
            'type' => 'file',
            'path' => '/var/log/actions.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee('Actions Menu Test')
                ->assertPresent('button[wire\\:click="editSource('.$logSource->id.')"]')
                ->assertPresent('button[wire\\:click="toggleSource('.$logSource->id.')"]')
                ->assertPresent('button[wire\\:click="syncSource('.$logSource->id.')"]')
                ->assertPresent('button[wire\\:click="removeSource('.$logSource->id.')"]')
                ->screenshot('actions-menu');
        });

        $logSource->delete();
    }

    /**
     * Test creating log source from Laravel template and saving
     *
     */

    #[Test]
    public function creating_from_laravel_template_and_saving_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->click('button:contains("Laravel")')
                ->pause(500)
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertSee('Laravel Application Logs')
                ->screenshot('laravel-template-saved');
        });
    }

    /**
     * Test form resets after successful creation
     *
     */

    #[Test]
    public function form_resets_after_successful_creation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Reset Test Source')
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/log/reset.log')
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertInputValue('input[wire\\:model="name"]', '')
                ->assertInputValue('input[wire\\:model="path"]', '')
                ->screenshot('form-reset-after-creation');
        });
    }

    /**
     * Test modal closes after successful creation
     *
     */

    #[Test]
    public function modal_closes_after_successful_creation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Auto Close Test')
                ->select('select[wire\\:model="type"]', 'file')
                ->type('input[wire\\:model="path"]', '/var/log/autoclose.log')
                ->press('Add Source')
                ->pause(1000)
                ->waitForText('Log source added successfully')
                ->assertDontSee('Add Log Source')
                ->screenshot('modal-closed-after-creation');
        });
    }

    /**
     * Test modal closes after successful edit
     *
     */

    #[Test]
    public function modal_closes_after_successful_edit(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Edit Close Test',
            'type' => 'file',
            'path' => '/var/log/editclose.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button[wire\\:click="editSource('.$logSource->id.')"]')
                ->pause(500)
                ->clear('input[wire\\:model="name"]')
                ->type('input[wire\\:model="name"]', 'Updated Close Test')
                ->press('Update Source')
                ->pause(1000)
                ->waitForText('Log source updated successfully')
                ->assertDontSee('Edit Log Source')
                ->screenshot('modal-closed-after-edit');
        });

        $logSource->delete();
    }

    /**
     * Test notification appears for successful operations
     *
     */

    #[Test]
    public function notification_appears_for_successful_operations(): void
    {
        $logSource = LogSource::create([
            'server_id' => $this->server->id,
            'name' => 'Notification Test',
            'type' => 'file',
            'path' => '/var/log/notification.log',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($logSource) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button[wire\\:click="toggleSource('.$logSource->id.')"]')
                ->pause(1000)
                ->assertSee('Log source disabled')
                ->screenshot('success-notification');
        });

        $logSource->delete();
    }

    /**
     * Test page shows server name in header
     *
     */

    #[Test]
    public function page_shows_server_name_in_header(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->assertSee($this->server->name)
                ->assertSee('Log Sources')
                ->screenshot('server-name-in-header');
        });
    }

    /**
     * Test log source type dropdown has all options
     *
     */

    #[Test]
    public function type_dropdown_has_all_options(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertSelectHasOptions('select[wire\\:model="type"]', ['file', 'docker', 'journald'])
                ->screenshot('type-dropdown-options');
        });
    }

    /**
     * Test project dropdown shows available projects
     *
     */

    #[Test]
    public function project_dropdown_shows_available_projects(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertSee('Project')
                ->assertSelectHasOption('select[wire\\:model="project_id"]', (string) $this->project->id)
                ->screenshot('project-dropdown');
        });
    }

    /**
     * Test all template buttons are visible
     *
     */

    #[Test]
    public function all_template_buttons_are_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit("/servers/{$this->server->id}/log-sources")
                ->click('button:contains("Add Log Source")')
                ->pause(500)
                ->assertSee('Templates')
                ->assertSee('Laravel')
                ->assertSee('Nginx Access')
                ->assertSee('Nginx Error')
                ->assertSee('Docker')
                ->assertSee('MySQL')
                ->screenshot('all-templates-visible');
        });
    }
}
