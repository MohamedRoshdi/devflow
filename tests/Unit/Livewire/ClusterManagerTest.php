<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Kubernetes\ClusterManager;
use App\Models\KubernetesCluster;
use App\Models\Project;
use App\Models\User;
use App\Services\Kubernetes\KubernetesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ClusterManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected KubernetesCluster $cluster;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->cluster = KubernetesCluster::factory()->create([
            'name' => 'Test Cluster',
            'api_server_url' => 'https://kubernetes.example.com',
            'namespace' => 'default',
            'is_active' => true,
        ]);
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'framework' => 'laravel',
        ]);
    }

    /** @test */
    public function component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.kubernetes.cluster-manager')
            ->assertSet('showAddClusterModal', false)
            ->assertSet('showDeployModal', false);
    }

    /** @test */
    public function component_displays_cluster_listing(): void
    {
        KubernetesCluster::factory()->create([
            'name' => 'Production Cluster',
            'api_server_url' => 'https://prod.k8s.example.com',
        ]);

        KubernetesCluster::factory()->create([
            'name' => 'Staging Cluster',
            'api_server_url' => 'https://staging.k8s.example.com',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertSee('Production Cluster')
            ->assertSee('Staging Cluster')
            ->assertSee('Test Cluster');
    }

    /** @test */
    public function component_displays_projects_list(): void
    {
        Project::factory()->create([
            'name' => 'Project Alpha',
            'slug' => 'project-alpha',
            'framework' => 'laravel',
        ]);

        Project::factory()->create([
            'name' => 'Project Beta',
            'slug' => 'project-beta',
            'framework' => 'symfony',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() >= 2 &&
                       $projects->contains('name', 'Project Alpha') &&
                       $projects->contains('name', 'Project Beta');
            });
    }

    /** @test */
    public function component_paginates_clusters(): void
    {
        KubernetesCluster::factory()->count(15)->create();

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertViewHas('clusters', function ($clusters) {
                return $clusters->count() === 10;
            });
    }

    /** @test */
    public function add_cluster_opens_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->assertSet('showAddClusterModal', true)
            ->assertSet('name', '')
            ->assertSet('endpoint', '')
            ->assertSet('kubeconfig', '')
            ->assertSet('namespace', '')
            ->assertSet('isDefault', false)
            ->assertSet('editingCluster', null);
    }

    /** @test */
    public function edit_cluster_loads_cluster_data(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'name' => 'Edit Test Cluster',
            'api_server_url' => 'https://edit.k8s.example.com',
            'namespace' => 'production',
            'kubeconfig' => encrypt('test-kubeconfig-content'),
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('editCluster', $cluster->id)
            ->assertSet('showAddClusterModal', true)
            ->assertSet('name', 'Edit Test Cluster')
            ->assertSet('endpoint', 'https://edit.k8s.example.com')
            ->assertSet('namespace', 'production')
            ->assertSet('isDefault', true); // isDefault maps to is_active
    }

    /** @test */
    public function edit_cluster_handles_null_namespace(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'namespace' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('editCluster', $cluster->id)
            ->assertSet('namespace', 'default');
    }

    /** @test */
    public function save_cluster_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', '')
            ->set('endpoint', '')
            ->set('kubeconfig', '')
            ->call('saveCluster')
            ->assertHasErrors(['name', 'endpoint', 'kubeconfig']);
    }

    /** @test */
    public function save_cluster_validates_name_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', str_repeat('a', 256))
            ->set('endpoint', 'https://k8s.example.com')
            ->set('kubeconfig', 'test-kubeconfig')
            ->call('saveCluster')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function save_cluster_validates_endpoint_is_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'Test Cluster')
            ->set('endpoint', 'invalid-url')
            ->set('kubeconfig', 'test-kubeconfig')
            ->call('saveCluster')
            ->assertHasErrors(['endpoint']);
    }

    /** @test */
    public function save_cluster_validates_namespace_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'Test Cluster')
            ->set('endpoint', 'https://k8s.example.com')
            ->set('kubeconfig', 'test-kubeconfig')
            ->set('namespace', str_repeat('a', 64))
            ->call('saveCluster')
            ->assertHasErrors(['namespace']);
    }

    /** @test */
    public function save_cluster_creates_new_cluster_with_connection_test(): void
    {
        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'New Cluster')
            ->set('endpoint', 'https://new.k8s.example.com')
            ->set('kubeconfig', 'valid-kubeconfig-content')
            ->set('namespace', 'staging')
            ->set('isDefault', false)
            ->call('saveCluster')
            ->assertSet('showAddClusterModal', false)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Cluster added successfully';
            });

        $this->assertDatabaseHas('kubernetes_clusters', [
            'name' => 'New Cluster',
            'api_server_url' => 'https://new.k8s.example.com',
            'namespace' => 'staging',
        ]);
    }

    /** @test */
    public function save_cluster_fails_with_invalid_connection(): void
    {
        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn(false);
        });

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'Invalid Cluster')
            ->set('endpoint', 'https://invalid.k8s.example.com')
            ->set('kubeconfig', 'invalid-kubeconfig')
            ->call('saveCluster')
            ->assertSet('showAddClusterModal', true)
            ->assertHasErrors(['kubeconfig']);
    }

    /** @test */
    public function save_cluster_defaults_namespace_to_default(): void
    {
        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'Default NS Cluster')
            ->set('endpoint', 'https://default.k8s.example.com')
            ->set('kubeconfig', 'valid-kubeconfig')
            ->set('namespace', '')
            ->call('saveCluster');

        $this->assertDatabaseHas('kubernetes_clusters', [
            'name' => 'Default NS Cluster',
            'namespace' => 'default',
        ]);
    }

    /** @test */
    public function save_cluster_encrypts_kubeconfig(): void
    {
        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn(true);
        });

        $kubeconfigContent = 'sensitive-kubeconfig-data';

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'Secure Cluster')
            ->set('endpoint', 'https://secure.k8s.example.com')
            ->set('kubeconfig', $kubeconfigContent)
            ->call('saveCluster');

        $cluster = KubernetesCluster::where('name', 'Secure Cluster')->first();
        $this->assertNotNull($cluster);
        $this->assertNotEquals($kubeconfigContent, $cluster->getRawOriginal('kubeconfig'));
        $this->assertEquals($kubeconfigContent, $cluster->kubeconfig);
    }

    /** @test */
    public function save_cluster_sets_is_default_and_clears_others(): void
    {
        KubernetesCluster::factory()->create([
            'name' => 'Old Default',
            'is_active' => true,
        ]);

        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn(true);
        });

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('addCluster')
            ->set('name', 'New Default')
            ->set('endpoint', 'https://newdefault.k8s.example.com')
            ->set('kubeconfig', 'valid-kubeconfig')
            ->set('isDefault', true)
            ->call('saveCluster');

        $this->assertDatabaseHas('kubernetes_clusters', [
            'name' => 'New Default',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('kubernetes_clusters', [
            'name' => 'Old Default',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function save_cluster_updates_existing_cluster(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'name' => 'Original Name',
            'api_server_url' => 'https://original.k8s.example.com',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('editCluster', $cluster->id)
            ->set('name', 'Updated Name')
            ->set('endpoint', 'https://updated.k8s.example.com')
            ->call('saveCluster')
            ->assertSet('showAddClusterModal', false)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Cluster updated successfully';
            });

        $this->assertDatabaseHas('kubernetes_clusters', [
            'id' => $cluster->id,
            'name' => 'Updated Name',
            'api_server_url' => 'https://updated.k8s.example.com',
        ]);
    }

    /** @test */
    public function delete_cluster_removes_cluster_without_projects(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'name' => 'Deletable Cluster',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('deleteCluster', $cluster->id)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Cluster deleted successfully';
            });

        $this->assertDatabaseMissing('kubernetes_clusters', [
            'id' => $cluster->id,
        ]);
    }

    /** @test */
    public function delete_cluster_prevents_deletion_when_in_use(): void
    {
        $cluster = KubernetesCluster::factory()->create();

        // Mock the projects relationship
        $mockCluster = Mockery::mock(KubernetesCluster::class)->makePartial();
        $mockCluster->shouldReceive('getAttribute')->with('id')->andReturn($cluster->id);
        $mockCluster->shouldReceive('projects')->andReturnSelf();
        $mockCluster->shouldReceive('exists')->andReturn(true);

        KubernetesCluster::where('id', $cluster->id)->update(['name' => 'In Use Cluster']);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('deleteCluster', $mockCluster->id)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'Cannot delete cluster that is in use by projects');
            });

        $this->assertDatabaseHas('kubernetes_clusters', [
            'id' => $cluster->id,
        ]);
    }

    /** @test */
    public function test_cluster_connection_success(): void
    {
        $this->partialMock(ClusterManager::class, function ($mock) {
            $mock->shouldReceive('shell_exec')
                ->once()
                ->andReturn('Kubernetes control plane is running at...');
        });

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('testClusterConnection', $this->cluster->id)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Connection successful!';
            });
    }

    /** @test */
    public function test_cluster_connection_failure(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('testClusterConnection', $this->cluster->id)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'error';
            });
    }

    /** @test */
    public function test_cluster_connection_handles_exception(): void
    {
        $cluster = KubernetesCluster::factory()->create([
            'kubeconfig' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('testClusterConnection', $cluster->id)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'Connection test failed');
            });
    }

    /** @test */
    public function show_deploy_to_cluster_opens_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->assertSet('selectedCluster', function ($selectedCluster) {
                return $selectedCluster->id === $this->cluster->id;
            })
            ->assertSet('showDeployModal', true);
    }

    /** @test */
    public function deploy_to_kubernetes_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', null)
            ->call('deployToKubernetes')
            ->assertHasErrors(['deploymentProject']);
    }

    /** @test */
    public function deploy_to_kubernetes_validates_project_exists(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', 99999)
            ->call('deployToKubernetes')
            ->assertHasErrors(['deploymentProject']);
    }

    /** @test */
    public function deploy_to_kubernetes_validates_replicas_range(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 0)
            ->call('deployToKubernetes')
            ->assertHasErrors(['replicas']);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 101)
            ->call('deployToKubernetes')
            ->assertHasErrors(['replicas']);
    }

    /** @test */
    public function deploy_to_kubernetes_validates_resource_requirements(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('cpuRequest', '')
            ->call('deployToKubernetes')
            ->assertHasErrors(['cpuRequest']);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('memoryLimit', '')
            ->call('deployToKubernetes')
            ->assertHasErrors(['memoryLimit']);
    }

    /** @test */
    public function deploy_to_kubernetes_successfully_deploys_project(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->with(
                Mockery::type(Project::class),
                Mockery::on(function ($options) {
                    return $options['replicas'] === 5 &&
                           $options['enable_autoscaling'] === true &&
                           $options['min_replicas'] === 3 &&
                           $options['max_replicas'] === 15 &&
                           $options['cpu_request'] === '200m' &&
                           $options['cpu_limit'] === '1000m' &&
                           $options['memory_request'] === '512Mi' &&
                           $options['memory_limit'] === '1Gi' &&
                           $options['service_type'] === 'LoadBalancer';
                })
            )
            ->andReturn(['success' => true]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 5)
            ->set('enableAutoscaling', true)
            ->set('minReplicas', 3)
            ->set('maxReplicas', 15)
            ->set('cpuRequest', '200m')
            ->set('cpuLimit', '1000m')
            ->set('memoryRequest', '512Mi')
            ->set('memoryLimit', '1Gi')
            ->set('serviceType', 'LoadBalancer')
            ->call('deployToKubernetes')
            ->assertSet('showDeployModal', false)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], 'Deployment to Kubernetes started');
            });
    }

    /** @test */
    public function deploy_to_kubernetes_handles_deployment_failure(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->andReturn(['success' => false]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 3)
            ->set('cpuRequest', '100m')
            ->set('cpuLimit', '500m')
            ->set('memoryRequest', '256Mi')
            ->set('memoryLimit', '512Mi')
            ->call('deployToKubernetes')
            ->assertSet('showDeployModal', true)
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'Deployment failed');
            });
    }

    /** @test */
    public function deploy_to_kubernetes_handles_exception(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->andThrow(new \Exception('Kubernetes API error'));

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 3)
            ->set('cpuRequest', '100m')
            ->set('cpuLimit', '500m')
            ->set('memoryRequest', '256Mi')
            ->set('memoryLimit', '512Mi')
            ->call('deployToKubernetes')
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'Deployment failed: Kubernetes API error');
            });
    }

    /** @test */
    public function deploy_to_kubernetes_resets_deployment_form_on_success(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->andReturn(['success' => true]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('replicas', 5)
            ->set('enableAutoscaling', true)
            ->set('minReplicas', 4)
            ->set('maxReplicas', 20)
            ->call('deployToKubernetes')
            ->assertSet('deploymentProject', null)
            ->assertSet('replicas', 3)
            ->assertSet('enableAutoscaling', false)
            ->assertSet('minReplicas', 2)
            ->assertSet('maxReplicas', 10);
    }

    /** @test */
    public function refresh_clusters_resets_pagination(): void
    {
        KubernetesCluster::factory()->count(15)->create();

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->set('page', 2)
            ->dispatch('refresh-clusters')
            ->assertSet('page', 1);
    }

    /** @test */
    public function component_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertSet('showAddClusterModal', false)
            ->assertSet('showDeployModal', false)
            ->assertSet('editingCluster', null)
            ->assertSet('selectedCluster', null)
            ->assertSet('selectedProject', null)
            ->assertSet('name', '')
            ->assertSet('endpoint', '')
            ->assertSet('kubeconfig', '')
            ->assertSet('namespace', '')
            ->assertSet('isDefault', false)
            ->assertSet('deploymentProject', null)
            ->assertSet('replicas', 3)
            ->assertSet('enableAutoscaling', false)
            ->assertSet('minReplicas', 2)
            ->assertSet('maxReplicas', 10)
            ->assertSet('cpuRequest', '100m')
            ->assertSet('cpuLimit', '500m')
            ->assertSet('memoryRequest', '256Mi')
            ->assertSet('memoryLimit', '512Mi')
            ->assertSet('serviceType', 'ClusterIP');
    }

    /** @test */
    public function component_handles_deployment_with_default_resource_settings(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->with(
                Mockery::type(Project::class),
                Mockery::on(function ($options) {
                    return $options['replicas'] === 3 &&
                           $options['enable_autoscaling'] === false &&
                           $options['cpu_request'] === '100m' &&
                           $options['cpu_limit'] === '500m' &&
                           $options['memory_request'] === '256Mi' &&
                           $options['memory_limit'] === '512Mi' &&
                           $options['service_type'] === 'ClusterIP';
                })
            )
            ->andReturn(['success' => true]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->call('deployToKubernetes')
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success';
            });
    }

    /** @test */
    public function component_includes_autoscaling_settings_in_deployment(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->with(
                Mockery::type(Project::class),
                Mockery::on(function ($options) {
                    return $options['enable_autoscaling'] === true &&
                           $options['min_replicas'] === 5 &&
                           $options['max_replicas'] === 25;
                })
            )
            ->andReturn(['success' => true]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->set('enableAutoscaling', true)
            ->set('minReplicas', 5)
            ->set('maxReplicas', 25)
            ->call('deployToKubernetes');
    }

    /** @test */
    public function reset_form_clears_all_cluster_fields(): void
    {
        $cluster = KubernetesCluster::factory()->create();

        $component = Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('editCluster', $cluster->id);

        // Verify fields are populated
        $component->assertSet('editingCluster', function ($editing) use ($cluster) {
            return $editing !== null && $editing->id === $cluster->id;
        });

        // Call addCluster which triggers resetForm
        $component->call('addCluster')
            ->assertSet('name', '')
            ->assertSet('endpoint', '')
            ->assertSet('kubeconfig', '')
            ->assertSet('namespace', '')
            ->assertSet('isDefault', false)
            ->assertSet('editingCluster', null);
    }

    /** @test */
    public function component_validates_boolean_fields_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->set('isDefault', 'not-a-boolean')
            ->call('saveCluster')
            ->assertHasErrors(['isDefault']);
    }

    /** @test */
    public function save_cluster_includes_project_name_in_success_message(): void
    {
        $mockService = Mockery::mock(KubernetesService::class);
        $mockService->shouldReceive('deployToKubernetes')
            ->once()
            ->andReturn(['success' => true]);

        $this->app->instance(KubernetesService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->call('showDeployToCluster', $this->cluster->id)
            ->set('deploymentProject', $this->project->id)
            ->call('deployToKubernetes')
            ->assertDispatched('notify', function (array $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], $this->project->name);
            });
    }

    /** @test */
    public function component_supports_different_service_types(): void
    {
        $serviceTypes = ['ClusterIP', 'NodePort', 'LoadBalancer'];

        foreach ($serviceTypes as $serviceType) {
            $mockService = Mockery::mock(KubernetesService::class);
            $mockService->shouldReceive('deployToKubernetes')
                ->once()
                ->with(
                    Mockery::type(Project::class),
                    Mockery::on(function ($options) use ($serviceType) {
                        return $options['service_type'] === $serviceType;
                    })
                )
                ->andReturn(['success' => true]);

            $this->app->instance(KubernetesService::class, $mockService);

            Livewire::actingAs($this->user)
                ->test(ClusterManager::class)
                ->call('showDeployToCluster', $this->cluster->id)
                ->set('deploymentProject', $this->project->id)
                ->set('serviceType', $serviceType)
                ->call('deployToKubernetes')
                ->assertDispatched('notify', function (array $data) {
                    return $data['type'] === 'success';
                });
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
