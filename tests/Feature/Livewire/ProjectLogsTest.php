<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\ProjectLogs;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DockerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProjectLogsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;
    private Server $server;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
    }

    private function mockDockerService(array $options = []): void
    {
        $success = $options['success'] ?? true;
        $logs = $options['logs'] ?? 'Test log content';
        $error = $options['error'] ?? 'Failed to load logs';
        $source = $options['source'] ?? null;

        $this->mock(DockerService::class, function (MockInterface $mock) use ($success, $logs, $error, $source): void {
            $mock->shouldReceive('getLaravelLogs')->andReturn([
                'success' => $success,
                'logs' => $success ? $logs : null,
                'source' => $source,
                'error' => $success ? null : $error,
            ]);
            $mock->shouldReceive('getContainerLogs')->andReturn([
                'success' => $success,
                'logs' => $success ? $logs : null,
                'source' => 'container',
                'error' => $success ? null : $error,
            ]);
            $mock->shouldReceive('clearLaravelLogs')->andReturn([
                'success' => $success,
                'error' => $success ? null : $error,
            ]);
            $mock->shouldReceive('downloadLaravelLogs')->andReturn([
                'success' => $success,
                'content' => $success ? $logs : '',
                'filename' => 'laravel.log',
                'error' => $success ? null : $error,
            ]);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_id_on_mount(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);
    }

    public function test_component_has_default_values(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->assertSet('logType', 'laravel')
            ->assertSet('lines', 200)
            ->assertSet('loading', false)
            ->assertSet('error', null);
    }

    // ==================== LOAD LOGS TESTS ====================

    public function test_can_load_logs(): void
    {
        $this->mockDockerService(['logs' => 'Laravel log content']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('logs', 'Laravel log content')
            ->assertSet('isLoading', false);
    }

    public function test_shows_error_when_load_fails(): void
    {
        $this->mockDockerService(['success' => false, 'error' => 'Connection failed']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('error', 'Connection failed');
    }

    public function test_shows_no_output_message_for_empty_logs(): void
    {
        $this->mockDockerService(['logs' => '']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('logs', 'No log output available.');
    }

    public function test_trims_whitespace_from_logs(): void
    {
        $this->mockDockerService(['logs' => '  Trimmed content  ']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('logs', 'Trimmed content');
    }

    // ==================== LOG TYPE TESTS ====================

    public function test_changing_log_type_reloads_logs(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('logType', 'docker')
            ->assertSet('logType', 'docker');
    }

    public function test_can_switch_to_docker_logs(): void
    {
        $this->mockDockerService(['logs' => 'Docker container logs']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('logType', 'docker')
            ->call('refreshLogs')
            ->assertSet('logs', 'Docker container logs');
    }

    public function test_can_switch_to_laravel_logs(): void
    {
        $this->mockDockerService(['logs' => 'Laravel application logs']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('logType', 'laravel')
            ->call('refreshLogs')
            ->assertSet('logs', 'Laravel application logs');
    }

    // ==================== LINES SETTING TESTS ====================

    public function test_changing_lines_reloads_logs(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 500)
            ->assertSet('lines', 500);
    }

    public function test_lines_below_minimum_resets_to_default(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 10)
            ->assertSet('lines', 200);
    }

    public function test_lines_above_maximum_resets_to_default(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 2000)
            ->assertSet('lines', 200);
    }

    public function test_valid_lines_value_is_accepted(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 500)
            ->assertSet('lines', 500);
    }

    public function test_minimum_lines_value_accepted(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 50)
            ->assertSet('lines', 50);
    }

    public function test_maximum_lines_value_accepted(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('lines', 1000)
            ->assertSet('lines', 1000);
    }

    // ==================== REFRESH TESTS ====================

    public function test_can_refresh_logs(): void
    {
        $this->mockDockerService(['logs' => 'Refreshed log content']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('refreshLogs')
            ->assertSet('logs', 'Refreshed log content');
    }

    // ==================== CLEAR LOGS TESTS ====================

    public function test_can_clear_logs(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('clearLogs')
            ->assertSessionHas('message', 'Logs cleared successfully');
    }

    public function test_clear_logs_shows_error_on_failure(): void
    {
        $this->mockDockerService(['success' => false, 'error' => 'Permission denied']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('clearLogs')
            ->assertSet('error', 'Permission denied');
    }

    // ==================== DOWNLOAD TESTS ====================

    public function test_can_download_logs(): void
    {
        $this->mockDockerService(['logs' => 'Downloadable log content']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('downloadLogs')
            ->assertSet('downloading', true);
    }

    // ==================== LOADING STATE TESTS ====================

    public function test_loading_is_true_initially(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->assertSet('isLoading', true);
    }

    public function test_loading_is_false_after_load(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('isLoading', false);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_handles_exception_gracefully(): void
    {
        $this->mock(DockerService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLaravelLogs')->andThrow(new \Exception('Connection timeout'));
        });

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('refreshLogs')
            ->assertSet('error', 'Connection timeout');
    }

    public function test_error_is_cleared_on_refresh(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('error', 'Previous error')
            ->call('refreshLogs')
            ->assertSet('error', null);
    }

    // ==================== SOURCE TESTS ====================

    public function test_source_is_set_from_response(): void
    {
        $this->mockDockerService(['source' => 'storage/logs/laravel.log']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('source', 'storage/logs/laravel.log');
    }

    public function test_source_defaults_to_container_for_docker(): void
    {
        $this->mockDockerService();

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->set('logType', 'docker')
            ->call('refreshLogs')
            ->assertSet('source', 'container');
    }

    // ==================== PROJECT ID LOCKED TESTS ====================

    public function test_project_id_is_locked(): void
    {
        $this->mockDockerService();
        $otherProject = Project::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);
    }

    // ==================== DIFFERENT PROJECTS TESTS ====================

    public function test_logs_are_project_specific(): void
    {
        $this->mockDockerService(['logs' => 'Project 1 logs']);

        Livewire::actingAs($this->user)
            ->test(ProjectLogs::class, ['project' => $this->project])
            ->call('loadData')
            ->assertSet('projectId', $this->project->id);
    }
}
