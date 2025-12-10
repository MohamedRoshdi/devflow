<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Kubernetes\ClusterManager;
use App\Models\KubernetesCluster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KubernetesClusterManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertOk();
    }

    public function test_component_displays_clusters(): void
    {
        $clusters = KubernetesCluster::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertOk()
            ->assertSee($clusters->first()->name);
    }

    public function test_cluster_status_is_displayed(): void
    {
        KubernetesCluster::factory()->create([
            'name' => 'Production Cluster',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(ClusterManager::class)
            ->assertSee('Production Cluster')
            ->assertSee('active');
    }

    public function test_guest_cannot_access_kubernetes_manager(): void
    {
        Livewire::test(ClusterManager::class)
            ->assertUnauthorized();
    }
}
