<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Jobs\ProcessProjectSetupJob;
use App\Livewire\Projects\ProjectCreate;
use App\Models\Domain;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Models\User;
use App\Models\UserSettings;
use App\Services\ProjectSetupService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->online()->withDocker()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function component_renders_successfully_for_authenticated_users(): void
    {
        Livewire::test(ProjectCreate::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-create');
    }

    /** @test */
    public function component_loads_servers_and_templates_on_mount(): void
    {
        $onlineServer = Server::factory()->online()->create();
        $offlineServer = Server::factory()->offline()->create();
        $template = ProjectTemplate::factory()->active()->create();

        $component = Livewire::test(ProjectCreate::class);

        $servers = $component->get('servers');
        $templates = $component->get('templates');

        $this->assertGreaterThan(0, $servers->count());
        $this->assertGreaterThan(0, $templates->count());
        $this->assertTrue($templates->contains($template));
    }

    /** @test */
    public function component_loads_user_default_settings_on_mount(): void
    {
        UserSettings::create([
            'user_id' => $this->user->id,
            'default_enable_ssl' => false,
            'default_enable_webhooks' => false,
            'default_enable_health_checks' => false,
            'default_enable_backups' => false,
            'default_enable_notifications' => false,
            'default_enable_auto_deploy' => true,
        ]);

        $component = Livewire::test(ProjectCreate::class);

        $this->assertFalse($component->get('enableSsl'));
        $this->assertFalse($component->get('enableWebhooks'));
        $this->assertFalse($component->get('enableHealthChecks'));
        $this->assertFalse($component->get('enableBackups'));
        $this->assertFalse($component->get('enableNotifications'));
        $this->assertTrue($component->get('enableAutoDeploy'));
    }

    /** @test */
    public function wizard_starts_at_step_1(): void
    {
        Livewire::test(ProjectCreate::class)
            ->assertSet('currentStep', 1)
            ->assertSet('totalSteps', 4);
    }

    /** @test */
    public function next_step_advances_wizard_when_validation_passes(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function next_step_does_not_advance_when_validation_fails(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', '')
            ->call('nextStep')
            ->assertSet('currentStep', 1)
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function next_step_does_not_exceed_total_steps(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->set('framework', 'laravel')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    }

    /** @test */
    public function previous_step_moves_wizard_back(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function previous_step_does_not_go_below_step_1(): void
    {
        Livewire::test(ProjectCreate::class)
            ->assertSet('currentStep', 1)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function go_to_step_navigates_to_valid_step(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('goToStep', 1)
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function go_to_step_does_not_navigate_to_future_steps(): void
    {
        Livewire::test(ProjectCreate::class)
            ->assertSet('currentStep', 1)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function step_1_validates_required_name(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', '')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasErrors(['name' => 'required']);
    }

    /** @test */
    public function step_1_validates_unique_slug(): void
    {
        Project::factory()->create(['slug' => 'existing-project']);

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'existing-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasErrors(['slug' => 'unique']);
    }

    /** @test */
    public function step_1_validates_server_exists(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', '99999')
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasErrors(['server_id' => 'exists']);
    }

    /** @test */
    public function step_1_validates_repository_url_format(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'invalid-url')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasErrors(['repository_url' => 'regex']);
    }

    /** @test */
    public function step_1_accepts_https_repository_urls(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repository.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasNoErrors(['repository_url'])
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function step_1_accepts_ssh_repository_urls(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'git@github.com:user/repository.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertHasNoErrors(['repository_url'])
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function step_1_validates_branch_name_format(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main; rm -rf /')
            ->call('nextStep')
            ->assertHasErrors(['branch' => 'regex']);
    }

    /** @test */
    public function step_1_accepts_valid_branch_names(): void
    {
        $validBranches = ['main', 'develop', 'feature/new-feature', 'release-v1.0', 'fix_bug-123'];

        foreach ($validBranches as $branch) {
            Livewire::test(ProjectCreate::class)
                ->set('name', 'Test Project')
                ->set('slug', 'test-project-' . str_replace(['/', '_', '-'], '', $branch))
                ->set('server_id', (string) $this->server->id)
                ->set('repository_url', 'https://github.com/user/repo.git')
                ->set('branch', $branch)
                ->call('nextStep')
                ->assertHasNoErrors(['branch']);
        }
    }

    /** @test */
    public function updated_name_automatically_generates_slug(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'My Awesome Project')
            ->assertSet('slug', 'my-awesome-project');
    }

    /** @test */
    public function step_2_validates_deployment_method(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->set('deployment_method', 'invalid')
            ->set('root_directory', '/')
            ->call('nextStep')
            ->assertHasErrors(['deployment_method' => 'in']);
    }

    /** @test */
    public function step_2_accepts_docker_deployment_method(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('nextStep')
            ->assertHasNoErrors(['deployment_method'])
            ->assertSet('currentStep', 3);
    }

    /** @test */
    public function step_2_accepts_standard_deployment_method(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->set('deployment_method', 'standard')
            ->set('root_directory', '/')
            ->call('nextStep')
            ->assertHasNoErrors(['deployment_method'])
            ->assertSet('currentStep', 3);
    }

    /** @test */
    public function step_2_validates_root_directory_is_required(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '')
            ->call('nextStep')
            ->assertHasErrors(['root_directory' => 'required']);
    }

    /** @test */
    public function step_3_has_no_required_validation(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->call('nextStep')
            ->assertSet('currentStep', 4);
    }

    /** @test */
    public function select_template_applies_template_settings(): void
    {
        $template = ProjectTemplate::factory()->create([
            'framework' => 'laravel',
            'default_branch' => 'develop',
            'php_version' => '8.4',
            'node_version' => '22',
            'install_commands' => ['composer install', 'npm install'],
            'build_commands' => ['npm run build', 'php artisan optimize'],
            'post_deploy_commands' => ['php artisan migrate --force'],
        ]);

        Livewire::test(ProjectCreate::class)
            ->call('selectTemplate', $template->id)
            ->assertSet('framework', 'laravel')
            ->assertSet('branch', 'develop')
            ->assertSet('php_version', '8.4')
            ->assertSet('node_version', '22')
            ->assertSet('install_commands', ['composer install', 'npm install'])
            ->assertSet('build_commands', ['npm run build', 'php artisan optimize'])
            ->assertSet('post_deploy_commands', ['php artisan migrate --force'])
            ->assertSet('build_command', 'npm run build');
    }

    /** @test */
    public function select_template_with_null_clears_template(): void
    {
        $template = ProjectTemplate::factory()->create([
            'framework' => 'laravel',
            'default_branch' => 'develop',
        ]);

        Livewire::test(ProjectCreate::class)
            ->call('selectTemplate', $template->id)
            ->assertSet('framework', 'laravel')
            ->call('selectTemplate', null)
            ->assertSet('selectedTemplateId', null);
    }

    /** @test */
    public function clear_template_resets_template_fields(): void
    {
        $template = ProjectTemplate::factory()->create([
            'framework' => 'laravel',
            'default_branch' => 'develop',
            'php_version' => '8.4',
            'node_version' => '22',
            'install_commands' => ['composer install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => ['php artisan migrate'],
        ]);

        Livewire::test(ProjectCreate::class)
            ->call('selectTemplate', $template->id)
            ->assertSet('framework', 'laravel')
            ->call('clearTemplate')
            ->assertSet('selectedTemplateId', null)
            ->assertSet('framework', '')
            ->assertSet('branch', 'main')
            ->assertSet('php_version', '8.3')
            ->assertSet('node_version', '20')
            ->assertSet('install_commands', [])
            ->assertSet('build_commands', [])
            ->assertSet('post_deploy_commands', [])
            ->assertSet('build_command', '');
    }

    /** @test */
    public function refresh_server_status_updates_server(): void
    {
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->once()
                ->andReturnTrue();
        });

        Livewire::test(ProjectCreate::class)
            ->call('refreshServerStatus', $this->server->id)
            ->assertSessionHas('server_status_updated', 'Server status refreshed');
    }

    /** @test */
    public function create_project_validates_all_required_fields(): void
    {
        Livewire::test(ProjectCreate::class)
            ->call('createProject')
            ->assertHasErrors([
                'name',
                'slug',
                'server_id',
                'repository_url',
                'branch',
                'deployment_method',
                'root_directory',
            ]);
    }

    /** @test */
    public function create_project_successfully_creates_project_with_valid_data(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('framework', 'laravel')
            ->set('deployment_method', 'docker')
            ->set('php_version', '8.4')
            ->set('node_version', '20')
            ->set('root_directory', '/')
            ->set('build_command', 'npm run build')
            ->set('start_command', 'npm start')
            ->set('auto_deploy', false)
            ->set('enableSsl', true)
            ->set('enableWebhooks', true)
            ->set('enableHealthChecks', true)
            ->set('enableBackups', true)
            ->set('enableNotifications', true)
            ->set('enableAutoDeploy', false)
            ->call('createProject')
            ->assertSessionHas('message')
            ->assertDispatched('project-created');

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $this->server->id,
            'repository_url' => 'https://github.com/user/repo.git',
            'branch' => 'main',
            'framework' => 'laravel',
            'deployment_method' => 'docker',
            'php_version' => '8.4',
            'node_version' => '20',
            'status' => 'stopped',
            'setup_status' => 'pending',
        ]);
    }

    /** @test */
    public function create_project_assigns_next_available_port(): void
    {
        Project::factory()->create([
            'server_id' => $this->server->id,
            'port' => 8001,
        ]);
        Project::factory()->create([
            'server_id' => $this->server->id,
            'port' => 8002,
        ]);

        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);
        $this->assertEquals(8003, $project->port);
    }

    /** @test */
    public function create_project_creates_default_domains(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);

        $domains = Domain::where('project_id', $project->id)->get();
        $this->assertGreaterThan(0, $domains->count());

        $primaryDomain = $domains->where('is_primary', true)->first();
        $this->assertNotNull($primaryDomain);
        $this->assertStringContainsString('test-project', $primaryDomain->domain);
    }

    /** @test */
    public function create_project_dispatches_setup_job_when_setup_enabled(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('enableSsl', true)
            ->call('createProject');

        Queue::assertPushed(ProcessProjectSetupJob::class);
    }

    /** @test */
    public function create_project_stores_setup_config(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('enableSsl', true)
            ->set('enableWebhooks', false)
            ->set('enableHealthChecks', true)
            ->set('enableBackups', false)
            ->set('enableNotifications', true)
            ->set('enableAutoDeploy', true)
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);

        $setupConfig = $project->setup_config;
        $this->assertTrue($setupConfig['ssl']);
        $this->assertFalse($setupConfig['webhook']);
        $this->assertTrue($setupConfig['health_check']);
        $this->assertFalse($setupConfig['backup']);
        $this->assertTrue($setupConfig['notifications']);
        $this->assertTrue($setupConfig['deployment']);
    }

    /** @test */
    public function create_project_sets_show_progress_modal(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject')
            ->assertSet('showProgressModal', true);
    }

    /** @test */
    public function create_project_stores_template_commands(): void
    {
        Queue::fake();

        $template = ProjectTemplate::factory()->create([
            'install_commands' => ['composer install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => ['php artisan migrate'],
        ]);

        Livewire::test(ProjectCreate::class)
            ->call('selectTemplate', $template->id)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);
        $this->assertEquals(['composer install'], $project->install_commands);
        $this->assertEquals(['npm run build'], $project->build_commands);
        $this->assertEquals(['php artisan migrate'], $project->post_deploy_commands);
    }

    /** @test */
    public function close_progress_and_redirect_navigates_to_project(): void
    {
        Queue::fake();

        $component = Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject')
            ->assertSet('showProgressModal', true);

        $createdProjectId = $component->get('createdProjectId');
        $this->assertNotNull($createdProjectId);

        $component->call('closeProgressAndRedirect')
            ->assertSet('showProgressModal', false)
            ->assertRedirect(route('projects.show', $createdProjectId));
    }

    /** @test */
    public function get_frameworks_property_returns_expected_frameworks(): void
    {
        $component = Livewire::test(ProjectCreate::class);

        $frameworks = $component->viewData('frameworks');

        $this->assertIsArray($frameworks);
        $this->assertArrayHasKey('laravel', $frameworks);
        $this->assertArrayHasKey('react', $frameworks);
        $this->assertArrayHasKey('vue', $frameworks);
        $this->assertArrayHasKey('nextjs', $frameworks);
        $this->assertArrayHasKey('static', $frameworks);
    }

    /** @test */
    public function get_php_versions_property_returns_expected_versions(): void
    {
        $component = Livewire::test(ProjectCreate::class);

        $phpVersions = $component->viewData('phpVersions');

        $this->assertIsArray($phpVersions);
        $this->assertArrayHasKey('8.4', $phpVersions);
        $this->assertArrayHasKey('8.3', $phpVersions);
        $this->assertArrayHasKey('8.2', $phpVersions);
        $this->assertArrayHasKey('8.1', $phpVersions);
    }

    /** @test */
    public function create_project_validates_latitude_range(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('latitude', 91.0)
            ->call('createProject')
            ->assertHasErrors(['latitude' => 'between']);
    }

    /** @test */
    public function create_project_validates_longitude_range(): void
    {
        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('longitude', 181.0)
            ->call('createProject')
            ->assertHasErrors(['longitude' => 'between']);
    }

    /** @test */
    public function create_project_accepts_valid_coordinates(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('latitude', 40.7128)
            ->set('longitude', -74.0060)
            ->call('createProject')
            ->assertHasNoErrors(['latitude', 'longitude']);

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);
        $this->assertEquals(40.7128, (float) $project->latitude);
        $this->assertEquals(-74.0060, (float) $project->longitude);
    }

    /** @test */
    public function servers_are_ordered_by_status_priority(): void
    {
        Server::factory()->offline()->create(['name' => 'Offline Server']);
        Server::factory()->create(['name' => 'Maintenance Server', 'status' => 'maintenance']);
        Server::factory()->online()->create(['name' => 'Online Server 2']);

        $component = Livewire::test(ProjectCreate::class);

        $servers = $component->get('servers');

        $statuses = $servers->pluck('status')->toArray();
        $onlineCount = count(array_filter($statuses, fn($s) => $s === 'online'));
        $this->assertGreaterThan(0, $onlineCount);
    }

    /** @test */
    public function get_next_available_port_handles_full_range(): void
    {
        for ($i = 8001; $i <= 9000; $i++) {
            Project::factory()->create([
                'server_id' => $this->server->id,
                'port' => $i,
            ]);
        }

        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);
        $this->assertGreaterThan(9000, $project->port);
    }

    /** @test */
    public function component_requires_authentication(): void
    {
        auth()->logout();

        Livewire::test(ProjectCreate::class)
            ->assertStatus(200);
    }

    /** @test */
    public function create_project_associates_with_current_user(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject');

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project);
        $this->assertEquals($this->user->id, $project->user_id);
    }

    /** @test */
    public function create_project_does_not_dispatch_job_when_no_setup_options_enabled(): void
    {
        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->set('enableSsl', false)
            ->set('enableWebhooks', false)
            ->set('enableHealthChecks', false)
            ->set('enableBackups', false)
            ->set('enableNotifications', false)
            ->set('enableAutoDeploy', false)
            ->call('createProject');

        Queue::assertNotPushed(ProcessProjectSetupJob::class);
    }

    /** @test */
    public function slug_uniqueness_ignores_soft_deleted_projects(): void
    {
        $deletedProject = Project::factory()->create(['slug' => 'test-project']);
        $deletedProject->delete();

        Queue::fake();

        Livewire::test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', (string) $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('deployment_method', 'docker')
            ->set('root_directory', '/')
            ->call('createProject')
            ->assertHasNoErrors(['slug']);

        $this->assertEquals(2, Project::withTrashed()->where('slug', 'test-project')->count());
    }
}
