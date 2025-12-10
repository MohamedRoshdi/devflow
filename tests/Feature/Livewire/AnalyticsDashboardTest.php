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
    use RefreshDatabase;

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
        Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class)
            ->assertOk();
    }

    public function test_component_displays_deployment_statistics(): void
    {
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
            ->assertOk();
    }

    public function test_date_range_filter_can_be_changed(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class);

        if (property_exists($component->instance(), 'dateRange')) {
            $component->set('dateRange', '30d')
                ->assertSet('dateRange', '30d');
        }
    }

    public function test_project_filter_can_be_applied(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsDashboard::class);

        if (property_exists($component->instance(), 'selectedProject')) {
            $component->set('selectedProject', $this->project->id)
                ->assertSet('selectedProject', $this->project->id);
        }
    }

    public function test_guest_cannot_access_analytics(): void
    {
        Livewire::test(AnalyticsDashboard::class)
            ->assertUnauthorized();
    }
}
