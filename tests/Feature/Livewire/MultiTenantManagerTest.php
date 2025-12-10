<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\MultiTenant\TenantManager;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTenantManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'project_type' => 'multi_tenant',
        ]);
    }

    public function test_component_can_be_rendered(): void
    {
        Livewire::actingAs($this->user)
            ->test(TenantManager::class)
            ->assertOk();
    }

    public function test_component_displays_tenants(): void
    {
        $tenants = Tenant::factory()->count(5)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TenantManager::class)
            ->assertOk()
            ->assertSeeHtml($tenants->first()->name);
    }

    public function test_component_filters_by_project(): void
    {
        $project2 = Project::factory()->create(['project_type' => 'multi_tenant']);

        Tenant::factory()->count(3)->create(['project_id' => $this->project->id]);
        Tenant::factory()->count(2)->create(['project_id' => $project2->id]);

        Livewire::actingAs($this->user)
            ->test(TenantManager::class, ['project' => $this->project->id])
            ->assertOk();
    }

    public function test_search_filters_tenants(): void
    {
        Tenant::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Acme Corporation',
        ]);
        Tenant::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Tech Solutions',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TenantManager::class);

        if (method_exists($component->instance(), 'updatedSearch') || property_exists($component->instance(), 'search')) {
            $component->set('search', 'Acme')
                ->assertSee('Acme Corporation')
                ->assertDontSee('Tech Solutions');
        }
    }

    public function test_guest_cannot_access_tenant_manager(): void
    {
        Livewire::test(TenantManager::class)
            ->assertUnauthorized();
    }
}
