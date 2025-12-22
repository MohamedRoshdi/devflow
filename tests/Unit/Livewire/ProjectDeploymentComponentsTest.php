<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Jobs\DeployProjectJob;
use App\Jobs\ProcessProjectSetupJob;
use App\Livewire\Deployments\DeploymentApprovals;
use App\Livewire\Deployments\DeploymentComments;
use App\Livewire\Deployments\DeploymentList;
use App\Livewire\Deployments\DeploymentRollback;
use App\Livewire\Deployments\DeploymentShow;
use App\Livewire\Deployments\ScheduledDeployments;
use App\Livewire\Projects\DatabaseBackupManager;
use App\Livewire\Projects\FileBackupManager;
use App\Livewire\Projects\GitHubRepoPicker;
use App\Livewire\Projects\PipelineSettings;
use App\Livewire\Projects\ProjectConfiguration;
use App\Livewire\Projects\ProjectCreate;
use App\Livewire\Projects\ProjectDockerManagement;
use App\Livewire\Projects\ProjectEdit;
use App\Livewire\Projects\ProjectEnvironment;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectLogs;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Projects\ProjectWebhookSettings;
use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\DeploymentComment;
use App\Models\FileBackup;
use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\PipelineConfig;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ScheduledDeployment;
use App\Models\Server;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\DeploymentApprovalService;
use App\Services\DockerService;
use App\Services\FileBackupService;
use App\Services\GitHubService;
use App\Services\GitService;
use App\Services\ProjectSetupService;
use App\Services\RollbackService;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectDeploymentComponentsTest extends TestCase
{
    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Http::fake();
        Queue::fake();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);

        // Grant necessary permissions for project tests
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-projects', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'create-projects', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit-projects', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'delete-projects', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'deploy-projects', 'guard_name' => 'web']);
        $this->user->givePermissionTo([
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'deploy-projects',
        ]);
    }

    /**
     * Mock services required for ProjectShow component boot() and mount()
     */
    protected function mockProjectShowServices(): void
    {
        $dockerServiceMock = \Mockery::mock(DockerService::class);
        $dockerServiceMock->shouldReceive('getContainerStatus')->andReturn([]);
        $dockerServiceMock->shouldReceive('startContainer')->andReturn(['success' => true]);
        $dockerServiceMock->shouldReceive('stopContainer')->andReturn(['success' => true]);
        $this->instance(DockerService::class, $dockerServiceMock);

        $gitServiceMock = \Mockery::mock(GitService::class);
        $gitServiceMock->shouldReceive('checkForUpdates')
            ->andReturn([
                'success' => true,
                'up_to_date' => true,
                'local_commit' => 'abc1234',
                'remote_commit' => 'abc1234',
                'commits_behind' => 0,
            ]);
        $this->instance(GitService::class, $gitServiceMock);
    }

    // ========================
    // ProjectList Component Tests
    // ========================

    #[Test]
    public function project_list_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-list');
    }

    #[Test]
    public function project_list_displays_projects(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->assertSee('Test Project');
    }

    #[Test]
    public function project_list_can_search_projects(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel App',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React App',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', 'Laravel')
            ->assertSee('Laravel App')
            ->assertDontSee('React App');
    }

    #[Test]
    public function project_list_can_filter_by_status(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'name' => 'Running Project',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
            'name' => 'Stopped Project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('statusFilter', 'running')
            ->assertSee('Running Project')
            ->assertDontSee('Stopped Project');
    }

    #[Test]
    public function project_list_can_delete_project(): void
    {
        // Grant the delete permission to the user
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'delete-projects', 'guard_name' => 'web']);
        $this->user->givePermissionTo('delete-projects');

        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->call('deleteProject', $project->id);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    #[Test]
    public function project_list_refreshes_on_project_created_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->dispatch('project-created')
            ->assertStatus(200);
    }

    // ========================
    // ProjectShow Component Tests
    // ========================

    #[Test]
    public function project_show_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-show');
    }

    #[Test]
    public function project_show_can_switch_tabs(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('setActiveTab', 'git')
            ->assertSet('activeTab', 'git');
    }

    #[Test]
    public function project_show_can_deploy_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('deploy');

        Queue::assertPushed(DeployProjectJob::class);
        $this->assertDatabaseHas('deployments', [
            'project_id' => $project->id,
            'triggered_by' => 'manual',
        ]);
    }

    #[Test]
    public function project_show_can_start_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('startProject');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'running',
        ]);
    }

    #[Test]
    public function project_show_can_stop_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('stopProject');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'stopped',
        ]);
    }

    #[Test]
    public function project_show_checks_for_updates(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Mock services needed for mount() which calls preloadUpdateStatus()
        $this->mockProjectShowServices();

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('checkForUpdates')
            ->assertSet('updateStatusLoaded', true);
    }

    // ========================
    // ProjectCreate Component Tests
    // ========================

    #[Test]
    public function project_create_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-create');
    }

    #[Test]
    public function project_create_auto_generates_slug_from_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->set('name', 'My Laravel App')
            ->assertSet('slug', 'my-laravel-app');
    }

    #[Test]
    public function project_create_validates_required_fields(): void
    {
        // Note: 'branch' has a default value of 'main', so it won't fail validation
        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->call('createProject')
            ->assertHasErrors(['name', 'slug', 'server_id', 'repository_url']);
    }

    #[Test]
    public function project_create_can_navigate_wizard_steps(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->assertSet('currentStep', 1)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    #[Test]
    public function project_create_can_create_project(): void
    {
        $this->instance(
            ProjectSetupService::class,
            \Mockery::mock(ProjectSetupService::class, function ($mock) {
                $mock->shouldReceive('initializeSetup')->andReturn(null);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('server_id', $this->server->id)
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->set('framework', 'laravel')
            ->set('php_version', '8.3')
            ->set('root_directory', '/')
            ->call('createProject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'server_id' => $this->server->id,
        ]);
    }

    #[Test]
    public function project_create_can_select_template(): void
    {
        $template = ProjectTemplate::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
            'node_version' => '20',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->call('selectTemplate', $template->id)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('framework', 'laravel')
            ->assertSet('php_version', '8.3');
    }

    #[Test]
    public function project_create_can_refresh_server_status(): void
    {
        $this->instance(
            ServerConnectivityService::class,
            \Mockery::mock(ServerConnectivityService::class, function ($mock) {
                $mock->shouldReceive('pingAndUpdateStatus')->andReturn(true);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectCreate::class)
            ->call('refreshServerStatus', $this->server->id)
            ->assertHasNoErrors();
    }

    // ========================
    // ProjectEdit Component Tests
    // ========================

    #[Test]
    public function project_edit_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-edit');
    }

    #[Test]
    public function project_edit_loads_project_data(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $project])
            ->assertSet('name', 'Test Project')
            ->assertSet('slug', 'test-project');
    }

    #[Test]
    public function project_edit_can_update_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $project])
            ->set('name', 'Updated Project')
            ->set('slug', 'updated-project')
            ->call('updateProject')
            ->assertSessionHas('message', 'Project updated successfully!');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
            'slug' => 'updated-project',
        ]);
    }

    #[Test]
    public function project_edit_validates_unique_slug(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'slug' => 'existing-slug',
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEdit::class, ['project' => $project2])
            ->set('name', 'Test')
            ->set('slug', 'existing-slug')
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->set('branch', 'main')
            ->call('updateProject')
            ->assertHasErrors('slug');
    }

    // ========================
    // ProjectConfiguration Component Tests
    // ========================

    #[Test]
    public function project_configuration_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectConfiguration::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-configuration');
    }

    #[Test]
    public function project_configuration_can_save_configuration(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectConfiguration::class, ['project' => $project])
            ->set('name', 'Updated Name')
            ->set('framework', 'laravel')
            ->set('php_version', '8.4')
            ->call('saveConfiguration')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'framework' => 'laravel',
            'php_version' => '8.4',
        ]);
    }

    // ========================
    // ProjectEnvironment Component Tests
    // ========================

    #[Test]
    public function project_environment_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-environment');
    }

    #[Test]
    public function project_environment_can_update_environment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'environment' => 'development',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $project])
            ->set('environment', 'production')
            ->call('updateEnvironment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'environment' => 'production',
        ]);
    }

    #[Test]
    public function project_environment_can_add_env_variable(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $project])
            ->set('newEnvKey', 'APP_URL')
            ->set('newEnvValue', 'https://example.com')
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertEquals('https://example.com', $project->env_variables['APP_URL']);
    }

    #[Test]
    public function project_environment_can_delete_env_variable(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'env_variables' => ['APP_URL' => 'https://example.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $project])
            ->call('deleteEnvVariable', 'APP_URL')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertArrayNotHasKey('APP_URL', $project->env_variables ?? []);
    }

    // ========================
    // ProjectLogs Component Tests
    // ========================

    #[Test]
    public function project_logs_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $mockDockerService = $this->mock(DockerService::class);
        $mockDockerService->shouldReceive('getLaravelLogs')
            ->once()
            ->andReturn(['success' => true, 'logs' => 'Test logs']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-logs');
    }

    #[Test]
    public function project_logs_can_refresh_logs(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $mockDockerService = $this->mock(DockerService::class);
        $mockDockerService->shouldReceive('getLaravelLogs')
            ->twice()
            ->andReturn(['success' => true, 'logs' => 'Test logs']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $project])
            ->call('refreshLogs')
            ->assertSet('loading', false);
    }

    #[Test]
    public function project_logs_can_clear_logs(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->instance(
            DockerService::class,
            \Mockery::mock(DockerService::class, function ($mock) {
                $mock->shouldReceive('getLaravelLogs')
                    ->andReturn(['success' => true, 'logs' => 'Test logs']);
                $mock->shouldReceive('clearLaravelLogs')
                    ->andReturn(['success' => true]);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $project])
            ->call('clearLogs')
            ->assertHasNoErrors();
    }

    // ========================
    // ProjectDockerManagement Component Tests
    // ========================

    #[Test]
    public function project_docker_management_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectDockerManagement::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-docker-management');
    }

    #[Test]
    public function project_docker_management_can_load_docker_info(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $this->instance(
            DockerService::class,
            \Mockery::mock(DockerService::class, function ($mock) {
                $mock->shouldReceive('listProjectImages')
                    ->andReturn(['success' => true, 'images' => []]);
                $mock->shouldReceive('getContainerStatus')
                    ->andReturn(['success' => true, 'exists' => false]);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectDockerManagement::class, ['project' => $project])
            ->call('loadDockerInfo')
            ->assertSet('loading', false);
    }

    #[Test]
    public function project_docker_management_can_start_container(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'stopped',
        ]);

        $this->instance(
            DockerService::class,
            \Mockery::mock(DockerService::class, function ($mock) {
                $mock->shouldReceive('startContainer')
                    ->andReturn(['success' => true]);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectDockerManagement::class, ['project' => $project])
            ->call('startContainer')
            ->assertHasNoErrors();
    }

    #[Test]
    public function project_docker_management_can_stop_container(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        $this->instance(
            DockerService::class,
            \Mockery::mock(DockerService::class, function ($mock) {
                $mock->shouldReceive('stopContainer')
                    ->andReturn(['success' => true]);
            })
        );

        Livewire::actingAs($this->user)
            ->test(ProjectDockerManagement::class, ['project' => $project])
            ->call('stopContainer')
            ->assertHasNoErrors();
    }

    // ========================
    // ProjectWebhookSettings Component Tests
    // ========================

    #[Test]
    public function project_webhook_settings_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-webhook-settings');
    }

    #[Test]
    public function project_webhook_settings_can_toggle_webhook(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'webhook_enabled' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $project])
            ->call('toggleWebhook')
            ->assertSet('webhookEnabled', true);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'webhook_enabled' => true,
        ]);
    }

    #[Test]
    public function project_webhook_settings_can_regenerate_secret(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'webhook_secret' => 'old-secret',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $project])
            ->call('regenerateSecret');

        $project->refresh();
        $this->assertNotEquals('old-secret', $project->webhook_secret);
    }

    // ========================
    // DatabaseBackupManager Component Tests
    // ========================

    #[Test]
    public function database_backup_manager_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.database-backup-manager');
    }

    #[Test]
    public function database_backup_manager_can_create_backup(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $mockService = $this->mock(DatabaseBackupService::class);
        $mockService->shouldReceive('createBackup')
            ->once();

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $project])
            ->set('databaseName', 'test_db')
            ->set('databaseType', 'mysql')
            ->call('createBackup');
    }

    #[Test]
    public function database_backup_manager_can_create_schedule(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $project])
            ->set('scheduleDatabase', 'test_db')
            ->set('scheduleDatabaseType', 'mysql')
            ->set('frequency', 'daily')
            ->set('time', '02:00')
            ->set('retentionDays', 30)
            ->set('storageDisk', 'local')
            ->call('createSchedule');

        $this->assertDatabaseHas('backup_schedules', [
            'project_id' => $project->id,
            'database_name' => 'test_db',
            'frequency' => 'daily',
        ]);
    }

    #[Test]
    public function database_backup_manager_can_toggle_schedule(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $schedule = BackupSchedule::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $project])
            ->call('toggleSchedule', $schedule->id);

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function database_backup_manager_can_delete_backup(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $backup = DatabaseBackup::factory()->create([
            'project_id' => $project->id,
        ]);

        $mockService = $this->mock(DatabaseBackupService::class);
        $mockService->shouldReceive('deleteBackup')
            ->once();

        Livewire::actingAs($this->user)
            ->test(DatabaseBackupManager::class, ['project' => $project])
            ->call('confirmDeleteBackup', $backup->id)
            ->call('deleteBackup');
    }

    // ========================
    // FileBackupManager Component Tests
    // ========================

    #[Test]
    public function file_backup_manager_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.file-backup-manager');
    }

    #[Test]
    public function file_backup_manager_can_create_full_backup(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $mockService = $this->mock(FileBackupService::class);
        $mockService->shouldReceive('createFullBackup')
            ->once()
            ->andReturn(FileBackup::factory()->make());

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $project])
            ->set('backupType', 'full')
            ->set('storageDisk', 'local')
            ->call('createBackup', $mockService);
    }

    #[Test]
    public function file_backup_manager_can_add_exclude_pattern(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'metadata' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $project])
            ->set('newExcludePattern', 'node_modules/*')
            ->call('addExcludePattern')
            ->assertSet('excludePatterns', ['node_modules/*']);
    }

    // ========================
    // GitHubRepoPicker Component Tests
    // ========================

    #[Test]
    public function github_repo_picker_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(GitHubRepoPicker::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.github-repo-picker');
    }

    #[Test]
    public function github_repo_picker_can_open_modal(): void
    {
        $connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(GitHubRepoPicker::class)
            ->call('open')
            ->assertSet('isOpen', true);
    }

    #[Test]
    public function github_repo_picker_can_select_repository(): void
    {
        $connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
        ]);

        $mockService = $this->mock(GitHubService::class);
        $mockService->shouldReceive('listBranches')
            ->once()
            ->andReturn([
                ['name' => 'main', 'protected' => false],
            ]);

        Livewire::actingAs($this->user)
            ->test(GitHubRepoPicker::class)
            ->call('selectRepository', $repo->id)
            ->assertSet('step', 'select-branch');
    }

    #[Test]
    public function github_repo_picker_can_confirm_selection(): void
    {
        $connection = GitHubConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $repo = GitHubRepository::factory()->create([
            'github_connection_id' => $connection->id,
            'clone_url' => 'https://github.com/user/repo.git',
        ]);

        Livewire::actingAs($this->user)
            ->test(GitHubRepoPicker::class)
            ->set('selectedRepoId', $repo->id)
            ->set('selectedBranch', 'main')
            ->call('confirmSelection')
            ->assertSet('repositoryUrl', 'https://github.com/user/repo.git')
            ->assertSet('branch', 'main')
            ->assertDispatched('repository-selected');
    }

    // ========================
    // PipelineSettings Component Tests
    // ========================

    #[Test]
    public function pipeline_settings_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineSettings::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.pipeline-settings');
    }

    #[Test]
    public function pipeline_settings_can_toggle_enabled(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        PipelineConfig::create([
            'project_id' => $project->id,
            'enabled' => false,
            'auto_deploy_branches' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineSettings::class, ['project' => $project])
            ->call('toggleEnabled');

        $this->assertDatabaseHas('pipeline_configs', [
            'project_id' => $project->id,
            'enabled' => true,
        ]);
    }

    #[Test]
    public function pipeline_settings_can_add_branch(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineSettings::class, ['project' => $project])
            ->set('newBranch', 'develop')
            ->call('addBranch')
            ->assertSet('auto_deploy_branches', [$project->branch, 'develop']);
    }

    #[Test]
    public function pipeline_settings_can_remove_branch(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineSettings::class, ['project' => $project])
            ->set('auto_deploy_branches', ['main', 'develop'])
            ->call('removeBranch', 1);
    }

    #[Test]
    public function pipeline_settings_can_generate_webhook_secret(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        // Create an existing pipeline config for the test
        PipelineConfig::create([
            'project_id' => $project->id,
            'enabled' => false,
            'auto_deploy_branches' => ['main'],
            'webhook_secret' => 'old-secret',
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineSettings::class, ['project' => $project])
            ->call('generateWebhookSecret')
            ->assertSet('showRegenerateConfirm', false);

        // Verify the webhook secret was updated (not empty)
        $config = PipelineConfig::where('project_id', $project->id)->first();
        $this->assertNotNull($config);
        $this->assertNotEquals('old-secret', $config->webhook_secret);
    }

    // ========================
    // DeploymentList Component Tests
    // ========================

    #[Test]
    public function deployment_list_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-list');
    }

    #[Test]
    public function deployment_list_can_filter_by_status(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->assertStatus(200);
    }

    #[Test]
    public function deployment_list_can_search_deployments(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix login bug',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'login')
            ->assertStatus(200);
    }

    // ========================
    // DeploymentShow Component Tests
    // ========================

    #[Test]
    public function deployment_show_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-show');
    }

    #[Test]
    public function deployment_show_can_refresh_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->call('refresh')
            ->assertStatus(200);
    }

    #[Test]
    public function deployment_show_analyzes_progress_from_logs(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'output_log' => '=== Cloning Repository ===',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentShow::class, ['deployment' => $deployment])
            ->assertSet('currentStep', 'Cloning repository')
            ->assertSet('progress', 10);
    }

    // ========================
    // DeploymentApprovals Component Tests
    // ========================

    #[Test]
    public function deployment_approvals_renders_successfully(): void
    {
        $mockService = $this->mock(DeploymentApprovalService::class);
        $mockService->shouldReceive('getPendingApprovals')
            ->andReturn(collect());
        $mockService->shouldReceive('getApprovalStats')
            ->andReturn([]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-approvals');
    }

    #[Test]
    public function deployment_approvals_can_approve_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $mockService = $this->mock(DeploymentApprovalService::class);
        $mockService->shouldReceive('getPendingApprovals')->andReturn(collect());
        $mockService->shouldReceive('getApprovalStats')->andReturn([]);
        $mockService->shouldReceive('approve')->once();

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', 'Looks good')
            ->call('approve');
    }

    #[Test]
    public function deployment_approvals_can_reject_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $mockService = $this->mock(DeploymentApprovalService::class);
        $mockService->shouldReceive('getPendingApprovals')->andReturn(collect());
        $mockService->shouldReceive('getApprovalStats')->andReturn([]);
        $mockService->shouldReceive('reject')->once();

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Needs more testing')
            ->call('reject');
    }

    // ========================
    // DeploymentComments Component Tests
    // ========================

    #[Test]
    public function deployment_comments_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentComments::class, ['deployment' => $deployment])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-comments');
    }

    #[Test]
    public function deployment_comments_can_add_comment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentComments::class, ['deployment' => $deployment])
            ->set('newComment', 'This deployment looks great!')
            ->call('addComment');

        $this->assertDatabaseHas('deployment_comments', [
            'deployment_id' => $deployment->id,
            'user_id' => $this->user->id,
            'content' => 'This deployment looks great!',
        ]);
    }

    #[Test]
    public function deployment_comments_can_edit_comment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $deployment->id,
            'user_id' => $this->user->id,
            'content' => 'Original comment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentComments::class, ['deployment' => $deployment])
            ->call('startEditing', $comment->id)
            ->set('editingContent', 'Updated comment')
            ->call('updateComment');

        $this->assertDatabaseHas('deployment_comments', [
            'id' => $comment->id,
            'content' => 'Updated comment',
        ]);
    }

    #[Test]
    public function deployment_comments_can_delete_comment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $comment = DeploymentComment::factory()->create([
            'deployment_id' => $deployment->id,
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentComments::class, ['deployment' => $deployment])
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('deployment_comments', [
            'id' => $comment->id,
        ]);
    }

    // ========================
    // DeploymentRollback Component Tests
    // ========================

    #[Test]
    public function deployment_rollback_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $mockService = $this->mock(RollbackService::class);
        $mockService->shouldReceive('getRollbackPoints')
            ->once()
            ->andReturn([]);

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-rollback');
    }

    #[Test]
    public function deployment_rollback_can_select_deployment_for_rollback(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        $mockService = $this->mock(RollbackService::class);
        $mockService->shouldReceive('getRollbackPoints')
            ->once()
            ->andReturn([
                [
                    'id' => $deployment->id,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => $deployment->commit_message,
                    'deployed_at' => $deployment->started_at,
                    'deployed_by' => $this->user->name,
                    'status' => $deployment->status,
                    'can_rollback' => true,
                ],
            ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $project])
            ->call('selectForRollback', $deployment->id)
            ->assertSet('showRollbackModal', true);
    }

    #[Test]
    public function deployment_rollback_can_confirm_rollback(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        $mockRollbackService = $this->mock(RollbackService::class);
        $mockRollbackService->shouldReceive('getRollbackPoints')
            ->once()
            ->andReturn([]);

        $newDeployment = Deployment::factory()->make();
        $mockRollbackService->shouldReceive('rollbackToDeployment')
            ->once()
            ->andReturn(['success' => true, 'deployment' => $newDeployment]);

        Livewire::actingAs($this->user)
            ->test(DeploymentRollback::class, ['project' => $project])
            ->set('selectedDeployment', ['id' => $deployment->id, 'can_rollback' => true])
            ->call('confirmRollback');
    }

    // ========================
    // ScheduledDeployments Component Tests
    // ========================

    #[Test]
    public function scheduled_deployments_renders_successfully(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $project])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.scheduled-deployments');
    }

    #[Test]
    public function scheduled_deployments_can_schedule_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('timezone', 'UTC')
            ->set('notes', 'Scheduled maintenance deployment')
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $project->id,
            'branch' => 'main',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function scheduled_deployments_validates_future_time(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', now()->subDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertHasErrors('scheduledDate');
    }

    #[Test]
    public function scheduled_deployments_can_cancel_scheduled_deployment(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $scheduledDeployment = ScheduledDeployment::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $project])
            ->call('cancelScheduledDeployment', $scheduledDeployment->id);

        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduledDeployment->id,
            'status' => 'cancelled',
        ]);
    }
}
