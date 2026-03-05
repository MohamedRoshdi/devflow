<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Projects\InfrastructureManager;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\CronConfigService;
use App\Services\NginxConfigService;
use App\Services\PhpFpmPoolService;
use App\Services\SupervisorConfigService;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\MocksSSH;

class InfrastructureManagerTest extends TestCase
{
    use MocksSSH;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAllProcesses();

        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->server = Server::factory()->online()->create([
            'user_id' => $this->user->id,
            'username' => 'testuser',
            'ip_address' => '192.168.1.100',
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'php_version' => '8.4',
        ]);
        $this->domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'test-project.example.com',
            'ssl_enabled' => true,
        ]);
    }

    // ---------------------------------------------------------------
    // Component Rendering & Mount
    // ---------------------------------------------------------------

    #[Test]
    public function component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.infrastructure-manager');
    }

    #[Test]
    public function component_sets_project_properties_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id)
            ->assertSet('statusLoaded', false)
            ->assertSet('nginxInstalled', false)
            ->assertSet('phpFpmInstalled', false)
            ->assertSet('supervisorInstalled', false)
            ->assertSet('cronInstalled', false)
            ->assertSet('supervisorWorkers', []);
    }

    #[Test]
    public function component_uses_locked_attribute_for_project_id(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $component->set('projectId', 999);
    }

    #[Test]
    public function component_displays_project_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('Test Project');
    }

    // ---------------------------------------------------------------
    // No Server Assigned
    // ---------------------------------------------------------------

    #[Test]
    public function component_shows_warning_when_no_server_assigned(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->assertSee('No server assigned');
    }

    #[Test]
    public function install_nginx_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('installNginx')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function install_php_fpm_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('installPhpFpm')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function install_supervisor_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('installSupervisor')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function install_cron_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('installCron')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    // ---------------------------------------------------------------
    // Load Status
    // ---------------------------------------------------------------

    #[Test]
    public function load_status_sets_status_loaded_flag(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSet('statusLoaded', false)
            ->call('loadStatus')
            ->assertSet('statusLoaded', true);
    }

    #[Test]
    public function load_status_handles_service_check_failures_gracefully(): void
    {
        // The mock catches all processes, so even if a check fails the component
        // should set statusLoaded to true and default the flags to false
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('loadStatus')
            ->assertSet('statusLoaded', true)
            ->assertHasNoErrors();
    }

    #[Test]
    public function load_status_works_when_no_server_assigned(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('loadStatus')
            ->assertSet('statusLoaded', true)
            ->assertSet('nginxInstalled', false)
            ->assertSet('phpFpmInstalled', false)
            ->assertSet('supervisorInstalled', false)
            ->assertSet('cronInstalled', false);
    }

    // ---------------------------------------------------------------
    // Nginx Operations
    // ---------------------------------------------------------------

    #[Test]
    public function install_nginx_succeeds_with_valid_domain(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('installNginx')
            ->assertSet('nginxInstalled', true)
            ->assertDispatched('notification', type: 'success', message: 'Nginx vhost installed successfully.');
    }

    #[Test]
    public function install_nginx_fails_when_no_domain_configured(): void
    {
        // Remove the domain
        $this->domain->delete();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('installNginx')
            ->assertSet('nginxInstalled', false)
            ->assertDispatched('notification', type: 'error', message: 'No domain configured for this project. Please add a domain first.');
    }

    #[Test]
    public function remove_nginx_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->set('nginxInstalled', true)
            ->call('removeNginx')
            ->assertSet('nginxInstalled', false)
            ->assertDispatched('notification', type: 'success', message: 'Nginx vhost removed successfully.');
    }

    #[Test]
    public function remove_nginx_fails_when_no_domain(): void
    {
        $this->domain->delete();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('removeNginx')
            ->assertDispatched('notification', type: 'error', message: 'No domain found for this project.');
    }

    #[Test]
    public function remove_nginx_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('removeNginx')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function test_nginx_config_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('testNginxConfig')
            ->assertDispatched('notification');
    }

    #[Test]
    public function test_nginx_config_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('testNginxConfig')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    // ---------------------------------------------------------------
    // PHP-FPM Operations
    // ---------------------------------------------------------------

    #[Test]
    public function install_php_fpm_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('installPhpFpm')
            ->assertSet('phpFpmInstalled', true)
            ->assertDispatched('notification', type: 'success', message: 'PHP-FPM pool installed successfully.');
    }

    #[Test]
    public function remove_php_fpm_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->set('phpFpmInstalled', true)
            ->call('removePhpFpm')
            ->assertSet('phpFpmInstalled', false)
            ->assertDispatched('notification', type: 'success', message: 'PHP-FPM pool removed successfully.');
    }

    #[Test]
    public function remove_php_fpm_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('removePhpFpm')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function reload_php_fpm_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('reloadPhpFpm')
            ->assertDispatched('notification', type: 'success', message: 'PHP-FPM reloaded successfully.');
    }

    #[Test]
    public function reload_php_fpm_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('reloadPhpFpm')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    // ---------------------------------------------------------------
    // Supervisor Operations
    // ---------------------------------------------------------------

    #[Test]
    public function install_supervisor_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('installSupervisor')
            ->assertSet('supervisorInstalled', true)
            ->assertDispatched('notification', type: 'success', message: 'Supervisor workers installed successfully.');
    }

    #[Test]
    public function remove_supervisor_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->set('supervisorInstalled', true)
            ->call('removeSupervisor')
            ->assertSet('supervisorInstalled', false)
            ->assertSet('supervisorWorkers', [])
            ->assertDispatched('notification', type: 'success', message: 'Supervisor workers removed successfully.');
    }

    #[Test]
    public function remove_supervisor_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('removeSupervisor')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    #[Test]
    public function restart_supervisor_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->set('supervisorInstalled', true)
            ->call('restartSupervisor')
            ->assertDispatched('notification', type: 'success', message: 'Supervisor workers restarted successfully.');
    }

    #[Test]
    public function restart_supervisor_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('restartSupervisor')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    // ---------------------------------------------------------------
    // Cron Operations
    // ---------------------------------------------------------------

    #[Test]
    public function install_cron_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('installCron')
            ->assertSet('cronInstalled', true)
            ->assertDispatched('notification', type: 'success', message: 'Cron scheduler installed successfully.');
    }

    #[Test]
    public function remove_cron_succeeds(): void
    {
        $this->mockSuccessfulCommand();

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->set('cronInstalled', true)
            ->call('removeCron')
            ->assertSet('cronInstalled', false)
            ->assertDispatched('notification', type: 'success', message: 'Cron scheduler removed successfully.');
    }

    #[Test]
    public function remove_cron_dispatches_error_when_no_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $projectWithoutServer])
            ->call('removeCron')
            ->assertDispatched('notification', type: 'error', message: 'No server assigned to this project.');
    }

    // ---------------------------------------------------------------
    // View Rendering Assertions
    // ---------------------------------------------------------------

    #[Test]
    public function view_displays_load_status_button_when_not_loaded(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('Load Service Status');
    }

    #[Test]
    public function view_displays_all_four_service_cards(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('Nginx Web Server')
            ->assertSee('PHP-FPM Pool')
            ->assertSee('Supervisor Workers')
            ->assertSee('Cron Scheduler');
    }

    #[Test]
    public function view_shows_unknown_status_before_loading(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('Unknown');
    }

    #[Test]
    public function view_shows_php_version_info_in_fpm_card(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('PHP 8.4');
    }

    #[Test]
    public function view_shows_cron_config_path(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('/etc/cron.d/test-project-scheduler');
    }

    #[Test]
    public function view_shows_refresh_button_after_status_loaded(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->call('loadStatus')
            ->assertSee('Refresh Status');
    }

    #[Test]
    public function view_includes_back_to_project_link(): void
    {
        Livewire::actingAs($this->user)
            ->test(InfrastructureManager::class, ['project' => $this->project])
            ->assertSee('Back to Project');
    }
}
