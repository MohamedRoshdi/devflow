<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Logs\WebhookLogs;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WebhookLogsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $server = Server::factory()->create();
        $this->project = Project::factory()->create([
            'server_id' => $server->id,
            'name' => 'Main Project',
        ]);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('providerFilter', '')
            ->assertSet('projectFilter', '')
            ->assertSet('eventTypeFilter', '')
            ->assertSet('showDetails', false)
            ->assertSet('selectedDelivery', []);
    }

    // ==================== DELIVERIES DISPLAY TESTS ====================

    public function test_displays_webhook_deliveries(): void
    {
        WebhookDelivery::factory()->count(5)->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(5, $deliveries);
    }

    public function test_deliveries_have_pagination(): void
    {
        WebhookDelivery::factory()->count(30)->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertEquals(20, $deliveries->perPage());
    }

    public function test_deliveries_are_ordered_by_created_at_descending(): void
    {
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'old.event',
            'created_at' => now()->subHour(),
        ]);

        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'new.event',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertEquals('new.event', $deliveries->first()->event_type);
    }

    // ==================== SEARCH TESTS ====================

    public function test_can_search_by_event_type(): void
    {
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'pull_request',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('search', 'push');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(1, $deliveries);
        $this->assertEquals('push', $deliveries->first()->event_type);
    }

    public function test_can_search_by_response(): void
    {
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'response' => 'Webhook processed successfully',
        ]);
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'response' => 'Invalid signature',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('search', 'signature');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(1, $deliveries);
    }

    public function test_search_resets_pagination(): void
    {
        WebhookDelivery::factory()->count(25)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('page', 2)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    // ==================== STATUS FILTER TESTS ====================

    public function test_can_filter_by_status(): void
    {
        WebhookDelivery::factory()->count(3)->success()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->failed()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('statusFilter', 'success');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(3, $deliveries);
    }

    public function test_can_filter_by_failed_status(): void
    {
        WebhookDelivery::factory()->count(3)->success()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->failed()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('statusFilter', 'failed');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(2, $deliveries);
    }

    public function test_can_filter_by_ignored_status(): void
    {
        WebhookDelivery::factory()->count(2)->success()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(3)->ignored()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('statusFilter', 'ignored');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(3, $deliveries);
    }

    // ==================== PROVIDER FILTER TESTS ====================

    public function test_can_filter_by_provider(): void
    {
        WebhookDelivery::factory()->count(4)->github()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->gitlab()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('providerFilter', 'github');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(4, $deliveries);
    }

    public function test_can_filter_by_gitlab_provider(): void
    {
        WebhookDelivery::factory()->count(4)->github()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->gitlab()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('providerFilter', 'gitlab');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(2, $deliveries);
    }

    // ==================== PROJECT FILTER TESTS ====================

    public function test_can_filter_by_project(): void
    {
        $otherProject = Project::factory()->create();

        WebhookDelivery::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->create([
            'project_id' => $otherProject->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('projectFilter', (string) $this->project->id);

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(3, $deliveries);
    }

    // ==================== EVENT TYPE FILTER TESTS ====================

    public function test_can_filter_by_event_type(): void
    {
        WebhookDelivery::factory()->count(4)->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);
        WebhookDelivery::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'event_type' => 'release',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('eventTypeFilter', 'push');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(4, $deliveries);
    }

    // ==================== CLEAR FILTERS TESTS ====================

    public function test_can_clear_all_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('search', 'test')
            ->set('statusFilter', 'success')
            ->set('providerFilter', 'github')
            ->set('projectFilter', (string) $this->project->id)
            ->set('eventTypeFilter', 'push')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('providerFilter', '')
            ->assertSet('projectFilter', '')
            ->assertSet('eventTypeFilter', '');
    }

    public function test_clear_filters_resets_pagination(): void
    {
        WebhookDelivery::factory()->count(25)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('page', 2)
            ->call('clearFilters')
            ->assertSet('page', 1);
    }

    // ==================== VIEW DETAILS TESTS ====================

    public function test_can_view_delivery_details(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', $delivery->id)
            ->assertSet('showDetails', true);
    }

    public function test_view_details_populates_selected_delivery(): void
    {
        $delivery = WebhookDelivery::factory()->github()->success()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
            'signature' => 'sha256=abc123',
            'response' => 'Webhook processed successfully',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', $delivery->id);

        $selected = $component->get('selectedDelivery');
        $this->assertEquals($delivery->id, $selected['id']);
        $this->assertEquals('github', $selected['provider']);
        $this->assertEquals('push', $selected['event_type']);
        $this->assertEquals('success', $selected['status']);
        $this->assertEquals('sha256=abc123', $selected['signature']);
        $this->assertEquals('Main Project', $selected['project']);
    }

    public function test_view_details_handles_non_existent_delivery(): void
    {
        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', 999999)
            ->assertSet('showDetails', false)
            ->assertSet('selectedDelivery', []);
    }

    public function test_view_details_includes_deployment_id(): void
    {
        $server = Server::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $server->id,
        ]);

        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', $delivery->id);

        $selected = $component->get('selectedDelivery');
        $this->assertEquals($deployment->id, $selected['deployment_id']);
    }

    public function test_can_close_details(): void
    {
        $delivery = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->call('viewDetails', $delivery->id)
            ->assertSet('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false)
            ->assertSet('selectedDelivery', []);
    }

    // ==================== COMPUTED PROPERTIES TESTS ====================

    public function test_projects_property_returns_all_projects(): void
    {
        Project::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $projects = $component->viewData('projects');
        $this->assertCount(4, $projects);
    }

    public function test_projects_are_ordered_by_name(): void
    {
        Project::factory()->create(['name' => 'Zebra Project']);
        Project::factory()->create(['name' => 'Alpha Project']);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $projects = $component->viewData('projects');
        $this->assertEquals('Alpha Project', $projects->first()->name);
    }

    public function test_event_types_property_returns_distinct_types(): void
    {
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'event_type' => 'release',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $eventTypes = $component->viewData('eventTypes');
        $this->assertCount(2, $eventTypes);
    }

    // ==================== STATS TESTS ====================

    public function test_stats_property_returns_counts(): void
    {
        WebhookDelivery::factory()->count(5)->success()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(3)->failed()->create([
            'project_id' => $this->project->id,
        ]);
        WebhookDelivery::factory()->count(2)->ignored()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['success']);
        $this->assertEquals(3, $stats['failed']);
        $this->assertEquals(2, $stats['ignored']);
    }

    public function test_stats_empty_when_no_deliveries(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['success']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['ignored']);
    }

    // ==================== COMBINED FILTERS TESTS ====================

    public function test_can_apply_multiple_filters(): void
    {
        $delivery = WebhookDelivery::factory()->github()->success()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);

        WebhookDelivery::factory()->github()->failed()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);

        WebhookDelivery::factory()->gitlab()->success()->create([
            'project_id' => $this->project->id,
            'event_type' => 'push',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('statusFilter', 'success')
            ->set('providerFilter', 'github')
            ->set('eventTypeFilter', 'push');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(1, $deliveries);
        $this->assertEquals($delivery->id, $deliveries->first()->id);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_deliveries(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(0, $deliveries);
    }

    public function test_handles_no_matching_deliveries(): void
    {
        WebhookDelivery::factory()->success()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class)
            ->set('statusFilter', 'failed');

        $deliveries = $component->viewData('deliveries');
        $this->assertCount(0, $deliveries);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_deliveries_include_project_relationship(): void
    {
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertTrue($deliveries->first()->relationLoaded('project'));
        $this->assertEquals('Main Project', $deliveries->first()->project->name);
    }

    public function test_deliveries_include_deployment_relationship(): void
    {
        $server = Server::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $server->id,
        ]);

        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'deployment_id' => $deployment->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WebhookLogs::class);

        $deliveries = $component->viewData('deliveries');
        $this->assertTrue($deliveries->first()->relationLoaded('deployment'));
        $this->assertEquals($deployment->id, $deliveries->first()->deployment->id);
    }
}
