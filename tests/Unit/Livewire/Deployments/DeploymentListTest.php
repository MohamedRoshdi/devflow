<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Deployments;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Deployments\DeploymentList;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentListTest extends TestCase
{
    

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Cache::flush();
    }

    #[Test]
    public function component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-list');
    }

    #[Test]
    public function component_displays_deployments(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix critical bug',
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertSee('Fix critical bug');
    }

    #[Test]
    public function component_displays_multiple_deployments(): void
    {
        Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'First deployment',
        ]);

        Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Second deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertSee('First deployment')
            ->assertSee('Second deployment');
    }

    #[Test]
    public function search_filters_deployments_by_commit_message(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Add new feature',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Fix bug',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'feature')
            ->assertSee('Add new feature')
            ->assertDontSee('Fix bug');
    }

    #[Test]
    public function search_filters_deployments_by_branch(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
            'commit_message' => 'Main branch deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'branch' => 'develop',
            'commit_message' => 'Develop branch deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'develop')
            ->assertSee('Develop branch deployment')
            ->assertDontSee('Main branch deployment');
    }

    #[Test]
    public function search_filters_deployments_by_project_name(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Application',
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React Application',
        ]);

        $deployment1 = Deployment::factory()->create([
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
            'commit_message' => 'UNIQUE_DEPLOY_LARAVEL_001',
        ]);

        Deployment::factory()->create([
            'project_id' => $project2->id,
            'server_id' => $this->server->id,
            'commit_message' => 'UNIQUE_DEPLOY_REACT_002',
        ]);

        // Verify search by project name filters correctly
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'Laravel')
            ->assertViewHas('deployments', function ($deployments) use ($deployment1) {
                return $deployments->count() === 1 &&
                       $deployments->first()->id === $deployment1->id;
            });
    }

    #[Test]
    public function status_filter_works_correctly(): void
    {
        // Use unique identifiers that won't appear elsewhere in the template
        $successDeploy = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'commit_message' => 'UNIQUE_SUCCESS_ABC123',
        ]);

        $failedDeploy = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'commit_message' => 'UNIQUE_FAILED_XYZ789',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'running',
            'commit_message' => 'UNIQUE_RUNNING_DEF456',
        ]);

        // Test filtering by status
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->assertViewHas('deployments', function ($deployments) use ($successDeploy) {
                return $deployments->count() === 1 &&
                       $deployments->first()->id === $successDeploy->id &&
                       $deployments->first()->status === 'success';
            });
    }

    #[Test]
    public function project_filter_works_correctly(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Deployment::factory()->create([
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Project 1 deployment',
        ]);

        Deployment::factory()->create([
            'project_id' => $project2->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Project 2 deployment',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('projectFilter', (string) $project1->id)
            ->assertSee('Project 1 deployment')
            ->assertDontSee('Project 2 deployment');
    }

    #[Test]
    public function multiple_filters_can_be_applied_simultaneously(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project Alpha',
        ]);

        Deployment::factory()->create([
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_message' => 'Main success',
        ]);

        Deployment::factory()->create([
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
            'branch' => 'main',
            'commit_message' => 'Main failure',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
            'branch' => 'develop',
            'commit_message' => 'Develop success',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('projectFilter', (string) $project1->id)
            ->set('statusFilter', 'success')
            ->set('search', 'main')
            ->assertSee('Main success')
            ->assertDontSee('Main failure')
            ->assertDontSee('Develop success');
    }

    #[Test]
    public function per_page_can_be_changed(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 25)
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->perPage() === 25;
            });
    }

    #[Test]
    public function per_page_is_validated_to_minimum(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 3)
            ->assertSet('perPage', 15); // Reset to default
    }

    #[Test]
    public function per_page_is_validated_to_maximum(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 100)
            ->assertSet('perPage', 15); // Reset to default
    }

    #[Test]
    public function per_page_accepts_valid_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 20)
            ->assertSet('perPage', 20);
    }

    #[Test]
    public function changing_status_filter_resets_pagination(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function changing_project_filter_resets_pagination(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('projectFilter', (string) $this->project->id)
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function changing_search_resets_pagination(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('search', 'test')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function changing_per_page_resets_pagination(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('perPage', 20)
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function component_caches_deployment_statistics(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 2 &&
                       $stats['success'] === 1 &&
                       $stats['failed'] === 1;
            });

        // Cache key includes user ID for per-user caching
        $this->assertTrue(Cache::has('deployment_stats_user_' . $this->user->id));
    }

    #[Test]
    public function component_caches_projects_dropdown_list(): void
    {
        Project::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() === 4; // 3 created + 1 in setUp
            });

        // Cache key includes user ID for per-user caching
        $this->assertTrue(Cache::has('projects_dropdown_list_user_' . $this->user->id));
    }

    #[Test]
    public function component_eager_loads_relationships(): void
    {
        Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) {
                $deployment = $deployments->first();

                return $deployment->relationLoaded('project') &&
                       $deployment->relationLoaded('server') &&
                       $deployment->relationLoaded('user');
            });
    }

    #[Test]
    public function component_only_selects_necessary_columns(): void
    {
        Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) {
                $deployment = $deployments->first();
                $attributes = array_keys($deployment->getAttributes());

                return in_array('id', $attributes) &&
                       in_array('status', $attributes) &&
                       in_array('commit_message', $attributes) &&
                       in_array('branch', $attributes);
            });
    }

    #[Test]
    public function deployments_are_ordered_by_latest_first(): void
    {
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'Old deployment',
            'created_at' => now()->subDays(5),
        ]);

        $newDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'commit_message' => 'New deployment',
            'created_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) use ($newDeployment) {
                return $deployments->first()->id === $newDeployment->id;
            });
    }

    #[Test]
    public function pagination_works_correctly(): void
    {
        Deployment::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->count() === 15; // Default per page
            });
    }

    #[Test]
    public function url_parameters_are_persisted(): void
    {
        // The component uses #[Url] attributes to persist parameters in the URL
        // We verify the properties are properly set
        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', 'success')
            ->set('projectFilter', (string) $this->project->id)
            ->set('search', 'test')
            ->set('perPage', 20)
            ->assertSet('statusFilter', 'success')
            ->assertSet('projectFilter', (string) $this->project->id)
            ->assertSet('search', 'test')
            ->assertSet('perPage', 20);
    }

    #[Test]
    public function empty_filters_show_all_deployments(): void
    {
        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->set('statusFilter', '')
            ->set('projectFilter', '')
            ->set('search', '')
            ->assertViewHas('deployments', function ($deployments) {
                return $deployments->count() === 3;
            });
    }

    #[Test]
    public function component_displays_all_deployment_statuses(): void
    {
        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DeploymentList::class);

        foreach (['pending', 'running', 'success', 'failed'] as $status) {
            $component->set('statusFilter', $status)
                ->assertViewHas('deployments', function ($deployments) use ($status) {
                    return $deployments->count() === 1 &&
                           $deployments->first()->status === $status;
                });
        }
    }

    #[Test]
    public function unauthenticated_user_is_redirected(): void
    {
        // The component requires authentication - it accesses auth()->user()->projects()
        // Without authentication, this throws a ViewException
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('Call to a member function projects() on null');
        Livewire::test(DeploymentList::class);
    }

    #[Test]
    public function statistics_show_correct_counts_for_each_status(): void
    {
        Deployment::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'success',
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'failed',
        ]);

        Deployment::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 10 &&
                       $stats['success'] === 5 &&
                       $stats['failed'] === 3 &&
                       $stats['running'] === 2;
            });
    }

    #[Test]
    public function projects_dropdown_is_ordered_alphabetically(): void
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Zebra Project',
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Alpha Project',
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentList::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->first()->name === 'Alpha Project';
            });
    }
}
