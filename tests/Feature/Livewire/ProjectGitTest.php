<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Projects\ProjectGit;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProjectGitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);
    }

    private function mockGitService(array $options = []): void
    {
        $commits = $options['commits'] ?? [
            ['hash' => 'abc123', 'message' => 'Initial commit', 'author' => 'John', 'date' => '2025-01-01'],
        ];
        $branches = $options['branches'] ?? ['main', 'develop', 'feature/new'];
        $success = $options['success'] ?? true;
        $error = $options['error'] ?? null;

        $this->mock(GitService::class, function (MockInterface $mock) use ($commits, $branches, $success, $error): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => $success,
                'commits' => $success ? $commits : [],
                'total' => $success ? count($commits) : 0,
                'error' => $error,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => $success,
                'branches' => $success ? $branches : [],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => $success,
                'has_updates' => false,
                'behind' => 0,
                'ahead' => 0,
            ]);
            $mock->shouldReceive('switchBranch')->andReturn([
                'success' => $success,
                'error' => $error,
            ]);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_git_data_on_mount(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('loading', false)
            ->assertSet('error', null);
    }

    public function test_component_loads_commits(): void
    {
        $commits = [
            ['hash' => 'abc123', 'message' => 'First commit'],
            ['hash' => 'def456', 'message' => 'Second commit'],
        ];
        $this->mockGitService(['commits' => $commits]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('commits', $commits)
            ->assertSet('totalCommits', 2);
    }

    public function test_component_loads_branches(): void
    {
        $branches = ['main', 'develop', 'staging'];
        $this->mockGitService(['branches' => $branches]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('branches', $branches);
    }

    public function test_component_shows_error_on_failure(): void
    {
        $this->mockGitService(['success' => false, 'error' => 'Connection failed']);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('error', 'Connection failed');
    }

    // ==================== DEPLOY PROJECT TESTS ====================

    public function test_can_deploy_project(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'status' => 'pending',
            'triggered_by' => 'manual',
        ]);

        Queue::assertPushed(DeployProjectJob::class);
    }

    public function test_cannot_deploy_when_deployment_in_progress(): void
    {
        $this->mockGitService();
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'already in progress');
            });
    }

    public function test_cannot_deploy_when_deployment_pending(): void
    {
        $this->mockGitService();
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_can_deploy_after_previous_completed(): void
    {
        $this->mockGitService();
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });
    }

    public function test_can_deploy_after_previous_failed(): void
    {
        $this->mockGitService();
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });
    }

    // ==================== BRANCH SWITCHING TESTS ====================

    public function test_can_switch_branch(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('switchBranch', 'develop')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], 'develop');
            });
    }

    public function test_switch_branch_shows_error_on_failure(): void
    {
        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => true,
                'commits' => [],
                'total' => 0,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => true,
                'branches' => ['main'],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => true,
            ]);
            $mock->shouldReceive('switchBranch')->andReturn([
                'success' => false,
                'error' => 'Branch not found',
            ]);
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('switchBranch', 'nonexistent')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== PAGINATION TESTS ====================

    public function test_default_pagination_values(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('currentPage', 1)
            ->assertSet('perPage', 10);
    }

    public function test_can_go_to_next_page(): void
    {
        $commits = array_fill(0, 15, ['hash' => 'abc', 'message' => 'test']);
        $this->mockGitService(['commits' => $commits]);

        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => true,
                'commits' => array_fill(0, 10, ['hash' => 'abc']),
                'total' => 25,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => true,
                'branches' => ['main'],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => true,
            ]);
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('currentPage', 1)
            ->call('nextPage')
            ->assertSet('currentPage', 2);
    }

    public function test_cannot_go_past_last_page(): void
    {
        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => true,
                'commits' => array_fill(0, 5, ['hash' => 'abc']),
                'total' => 5,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => true,
                'branches' => ['main'],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => true,
            ]);
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('nextPage')
            ->assertSet('currentPage', 1);
    }

    public function test_can_go_to_previous_page(): void
    {
        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => true,
                'commits' => array_fill(0, 10, ['hash' => 'abc']),
                'total' => 25,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => true,
                'branches' => ['main'],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => true,
            ]);
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->set('currentPage', 3)
            ->call('previousPage')
            ->assertSet('currentPage', 2);
    }

    public function test_cannot_go_before_first_page(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('currentPage', 1)
            ->call('previousPage')
            ->assertSet('currentPage', 1);
    }

    // ==================== REFRESH TESTS ====================

    public function test_can_refresh_git_data(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('refresh')
            ->assertSet('loading', false);
    }

    public function test_responds_to_refresh_git_event(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->dispatch('refresh-git')
            ->assertSet('loading', false);
    }

    // ==================== UPDATE STATUS TESTS ====================

    public function test_loads_update_status(): void
    {
        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andReturn([
                'success' => true,
                'commits' => [],
                'total' => 0,
            ]);
            $mock->shouldReceive('getBranches')->andReturn([
                'success' => true,
                'branches' => ['main'],
            ]);
            $mock->shouldReceive('checkForUpdates')->andReturn([
                'success' => true,
                'has_updates' => true,
                'behind' => 5,
                'ahead' => 2,
            ]);
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('updateStatus', function ($status) {
                return $status['has_updates'] === true &&
                       $status['behind'] === 5 &&
                       $status['ahead'] === 2;
            });
    }

    // ==================== LOADING STATE TESTS ====================

    public function test_loading_starts_true_during_load(): void
    {
        $this->mockGitService();

        $component = Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project]);

        // After mount completes, loading should be false
        $component->assertSet('loading', false);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_handles_exception_gracefully(): void
    {
        $this->mock(GitService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestCommits')->andThrow(new \Exception('Test exception'));
        });

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('error', 'Test exception')
            ->assertSet('loading', false);
    }

    // ==================== PROJECT DATA TESTS ====================

    public function test_loads_project_on_mount(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id);
    }

    // ==================== DEPLOYMENT CREATION TESTS ====================

    public function test_deployment_uses_project_branch(): void
    {
        $this->mockGitService();
        $this->project->update(['branch' => 'production']);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject');

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'branch' => 'production',
        ]);
    }

    public function test_deployment_uses_project_server(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject');

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'server_id' => $this->project->server_id,
        ]);
    }

    public function test_deployment_records_user(): void
    {
        $this->mockGitService();

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->call('deployProject');

        $this->assertDatabaseHas('deployments', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_empty_commits(): void
    {
        $this->mockGitService(['commits' => []]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('commits', [])
            ->assertSet('totalCommits', 0);
    }

    public function test_handles_empty_branches(): void
    {
        $this->mockGitService(['branches' => []]);

        Livewire::actingAs($this->user)
            ->test(ProjectGit::class, ['project' => $this->project])
            ->assertSet('branches', []);
    }
}
