<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Analytics\AnalyticsDashboard;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    public function test_component_can_be_rendered(): void
    {
        // Grant the view-analytics permission to the user
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-analytics']);
        $this->user->givePermissionTo($permission);

        Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class)
            ->assertOk();
    }

    public function test_component_displays_deployment_statistics(): void
    {
        // Grant the view-analytics permission to the user
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-analytics']);
        $this->user->givePermissionTo($permission);

        Deployment::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'status' => 'success',
        ]);

        Deployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class)
            ->assertOk()
            ->assertViewHas('deploymentStats');
    }

    public function test_date_range_filter_can_be_changed(): void
    {
        // Grant the view-analytics permission to the user
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-analytics']);
        $this->user->givePermissionTo($permission);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class);

        // The property is selectedPeriod, not dateRange
        $component->set('selectedPeriod', '30days')
            ->assertSet('selectedPeriod', '30days');
    }

    public function test_project_filter_can_be_applied(): void
    {
        // Grant the view-analytics permission to the user
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view-analytics']);
        $this->user->givePermissionTo($permission);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class);

        $component->set('selectedProject', (string) $this->project->id)
            ->assertSet('selectedProject', (string) $this->project->id);
    }

    public function test_guest_cannot_access_analytics(): void
    {
        // Component returns 403 Forbidden when no user is authenticated
        Livewire::test(AnalyticsDashboard::class)
            ->assertForbidden();
    }
}
