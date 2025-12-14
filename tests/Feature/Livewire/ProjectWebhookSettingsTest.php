<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\ProjectWebhookSettings;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectWebhookSettingsTest extends TestCase
{
    use RefreshDatabase;

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
            'webhook_enabled' => false,
            'webhook_secret' => null,
        ]);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id);
    }

    public function test_loads_webhook_enabled_state(): void
    {
        $this->project->update(['webhook_enabled' => true, 'webhook_secret' => 'test-secret']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookEnabled', true);
    }

    public function test_loads_webhook_secret(): void
    {
        $this->project->update(['webhook_secret' => 'my-secret-key']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookSecret', 'my-secret-key');
    }

    // ==================== TOGGLE WEBHOOK TESTS ====================

    public function test_can_enable_webhook(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookEnabled', false)
            ->call('toggleWebhook')
            ->assertSet('webhookEnabled', true)
            ->assertDispatched('notification');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertTrue($freshProject->webhook_enabled);
    }

    public function test_can_disable_webhook(): void
    {
        $this->project->update(['webhook_enabled' => true, 'webhook_secret' => 'test-secret']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookEnabled', true)
            ->call('toggleWebhook')
            ->assertSet('webhookEnabled', false)
            ->assertDispatched('notification');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertFalse($freshProject->webhook_enabled);
    }

    public function test_generates_secret_when_enabling_without_one(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookSecret', null)
            ->call('toggleWebhook')
            ->assertSet('webhookSecret', function ($secret) {
                return $secret !== null && strlen($secret) > 0;
            });

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertNotNull($freshProject->webhook_secret);
    }

    public function test_generates_webhook_urls_when_enabling(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('toggleWebhook')
            ->assertSet('webhookUrl', function ($url) {
                return str_contains($url, 'webhooks');
            })
            ->assertSet('gitlabWebhookUrl', function ($url) {
                return str_contains($url, 'webhooks');
            });
    }

    // ==================== REGENERATE SECRET TESTS ====================

    public function test_can_regenerate_secret(): void
    {
        $oldSecret = 'old-secret-123';
        $this->project->update(['webhook_secret' => $oldSecret]);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('regenerateSecret')
            ->assertDispatched('notification');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertNotEquals($oldSecret, $freshProject->webhook_secret);
    }

    public function test_regenerate_closes_confirm_modal(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->set('showRegenerateConfirm', true)
            ->call('regenerateSecret')
            ->assertSet('showRegenerateConfirm', false);
    }

    public function test_regenerate_updates_webhook_urls(): void
    {
        $this->project->update(['webhook_secret' => 'old-secret']);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project]);

        $oldUrl = $component->get('webhookUrl');

        $component->call('regenerateSecret');

        $newUrl = $component->get('webhookUrl');
        $this->assertNotEquals($oldUrl, $newUrl);
    }

    // ==================== CONFIRM REGENERATE TESTS ====================

    public function test_can_open_regenerate_confirm(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('showRegenerateConfirm', false)
            ->call('confirmRegenerate')
            ->assertSet('showRegenerateConfirm', true);
    }

    public function test_can_cancel_regenerate_confirm(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('confirmRegenerate')
            ->assertSet('showRegenerateConfirm', true)
            ->call('cancelRegenerate')
            ->assertSet('showRegenerateConfirm', false);
    }

    // ==================== SECRET VISIBILITY TESTS ====================

    public function test_secret_hidden_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('showSecret', false);
    }

    public function test_can_toggle_secret_visibility(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('showSecret', false)
            ->call('toggleSecretVisibility')
            ->assertSet('showSecret', true)
            ->call('toggleSecretVisibility')
            ->assertSet('showSecret', false);
    }

    // ==================== RECENT DELIVERIES TESTS ====================

    public function test_shows_recent_deliveries(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);
        WebhookDelivery::factory()->count(5)->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project]);

        $deliveries = $component->viewData('recentDeliveries');
        $this->assertEquals(5, $deliveries->total());
    }

    public function test_deliveries_are_paginated(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);
        WebhookDelivery::factory()->count(25)->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project]);

        $deliveries = $component->viewData('recentDeliveries');
        $this->assertEquals(25, $deliveries->total());
        $this->assertEquals(10, $deliveries->perPage());
    }

    public function test_deliveries_are_ordered_by_date_desc(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);
        WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'created_at' => now()->subDay(),
        ]);
        $latest = WebhookDelivery::factory()->create([
            'project_id' => $this->project->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project]);

        $deliveries = $component->viewData('recentDeliveries');
        $this->assertEquals($latest->id, $deliveries->first()->id);
    }

    // ==================== STATUS BADGE TESTS ====================

    public function test_success_status_badge_is_green(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('getDeliveryStatusBadgeColor', 'success')
            ->assertReturned('green');
    }

    public function test_failed_status_badge_is_red(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('getDeliveryStatusBadgeColor', 'failed')
            ->assertReturned('red');
    }

    public function test_ignored_status_badge_is_gray(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('getDeliveryStatusBadgeColor', 'ignored')
            ->assertReturned('gray');
    }

    public function test_pending_status_badge_is_yellow(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('getDeliveryStatusBadgeColor', 'pending')
            ->assertReturned('yellow');
    }

    public function test_unknown_status_badge_is_gray(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->call('getDeliveryStatusBadgeColor', 'unknown')
            ->assertReturned('gray');
    }

    // ==================== URL GENERATION TESTS ====================

    public function test_webhook_url_not_set_without_secret(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookUrl', '');
    }

    public function test_webhook_url_set_with_secret(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookUrl', function ($url) {
                return str_contains($url, 'test-secret');
            });
    }

    public function test_gitlab_webhook_url_set_with_secret(): void
    {
        $this->project->update(['webhook_secret' => 'test-secret']);

        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('gitlabWebhookUrl', function ($url) {
                return str_contains($url, 'test-secret');
            });
    }

    // ==================== DIFFERENT PROJECTS TESTS ====================

    public function test_deliveries_are_project_specific(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'webhook_secret' => 'other-secret',
        ]);

        $this->project->update(['webhook_secret' => 'test-secret']);
        WebhookDelivery::factory()->count(3)->create(['project_id' => $this->project->id]);
        WebhookDelivery::factory()->count(5)->create(['project_id' => $otherProject->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project]);

        $deliveries = $component->viewData('recentDeliveries');
        $this->assertEquals(3, $deliveries->total());
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectWebhookSettings::class, ['project' => $this->project])
            ->assertSet('webhookEnabled', false)
            ->assertSet('showSecret', false)
            ->assertSet('showRegenerateConfirm', false);
    }
}
