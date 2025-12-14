<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\ResourceAlertManager;
use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use App\Models\User;
use App\Services\ResourceAlertService;
use App\Services\ServerMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ResourceAlertManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'active']);
    }

    private function mockMetricsService(): void
    {
        $this->mock(ServerMetricsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getLatestMetrics')->andReturn(null);
            $mock->shouldReceive('collectMetrics')->andReturn(true);
        });
    }

    private function mockAlertService(bool $success = true): void
    {
        $this->mock(ResourceAlertService::class, function (MockInterface $mock) use ($success): void {
            $mock->shouldReceive('testAlert')->andReturn([
                'success' => $success,
                'message' => $success ? 'Test sent' : 'Failed to send',
            ]);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    public function test_component_loads_with_server(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertSet('server.id', $this->server->id);
    }

    public function test_component_shows_existing_alerts(): void
    {
        $this->mockMetricsService();
        ResourceAlert::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $alerts = $component->viewData('alerts');
        $this->assertCount(3, $alerts);
    }

    public function test_displays_empty_state_without_alerts(): void
    {
        $this->mockMetricsService();

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $alerts = $component->viewData('alerts');
        $this->assertCount(0, $alerts);
    }

    // ==================== CREATE ALERT TESTS ====================

    public function test_can_create_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->set('resource_type', 'cpu')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 85.0)
            ->set('cooldown_minutes', 30)
            ->set('is_active', true)
            ->call('createAlert')
            ->assertHasNoErrors()
            ->assertDispatched('alert-created')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 85.0,
            'cooldown_minutes' => 30,
        ]);
    }

    public function test_create_alert_with_email_notification(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->set('resource_type', 'memory')
            ->set('threshold_value', 90.0)
            ->set('enable_email', true)
            ->set('email_address', 'test@example.com')
            ->call('createAlert')
            ->assertHasNoErrors();

        $alert = ResourceAlert::first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('email', $alert->notification_channels);
    }

    public function test_create_alert_with_slack_notification(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->set('resource_type', 'disk')
            ->set('threshold_value', 80.0)
            ->set('enable_slack', true)
            ->set('slack_webhook', 'https://hooks.slack.com/services/test')
            ->call('createAlert')
            ->assertHasNoErrors();

        $alert = ResourceAlert::first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('slack', $alert->notification_channels);
    }

    public function test_create_alert_with_discord_notification(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->set('resource_type', 'load')
            ->set('threshold_value', 5.0)
            ->set('enable_discord', true)
            ->set('discord_webhook', 'https://discord.com/api/webhooks/test')
            ->call('createAlert')
            ->assertHasNoErrors();

        $alert = ResourceAlert::first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('discord', $alert->notification_channels);
    }

    public function test_create_alert_validates_resource_type(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'invalid')
            ->call('createAlert')
            ->assertHasErrors(['resource_type']);
    }

    public function test_create_alert_validates_threshold_type(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_type', 'invalid')
            ->call('createAlert')
            ->assertHasErrors(['threshold_type']);
    }

    public function test_create_alert_validates_threshold_value_range(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', 150.0)
            ->call('createAlert')
            ->assertHasErrors(['threshold_value']);
    }

    public function test_create_alert_validates_cooldown_range(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('cooldown_minutes', 0)
            ->call('createAlert')
            ->assertHasErrors(['cooldown_minutes']);
    }

    public function test_create_alert_requires_email_when_enabled(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_email', true)
            ->set('email_address', '')
            ->call('createAlert')
            ->assertHasErrors(['email_address']);
    }

    public function test_create_alert_requires_slack_webhook_when_enabled(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_slack', true)
            ->set('slack_webhook', '')
            ->call('createAlert')
            ->assertHasErrors(['slack_webhook']);
    }

    public function test_create_alert_requires_discord_webhook_when_enabled(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_discord', true)
            ->set('discord_webhook', '')
            ->call('createAlert')
            ->assertHasErrors(['discord_webhook']);
    }

    // ==================== EDIT ALERT TESTS ====================

    public function test_can_open_edit_modal(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingId', $alert->id)
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_value', 80.0);
    }

    public function test_can_update_alert(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('resource_type', 'memory')
            ->set('threshold_value', 90.0)
            ->call('updateAlert')
            ->assertHasNoErrors()
            ->assertDispatched('alert-updated')
            ->assertSet('showEditModal', false);

        $freshAlert = $alert->fresh();
        $this->assertNotNull($freshAlert);
        $this->assertEquals('memory', $freshAlert->resource_type);
        $this->assertEquals(90.0, (float) $freshAlert->threshold_value);
    }

    public function test_update_alert_loads_notification_channels(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'email' => ['email' => 'test@example.com'],
                'slack' => ['webhook_url' => 'https://slack.com/test'],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('enable_email', true)
            ->assertSet('email_address', 'test@example.com')
            ->assertSet('enable_slack', true)
            ->assertSet('slack_webhook', 'https://slack.com/test');
    }

    public function test_update_without_editing_id_does_nothing(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('updateAlert');

        $this->assertDatabaseCount('resource_alerts', 0);
    }

    // ==================== DELETE ALERT TESTS ====================

    public function test_can_delete_alert(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('deleteAlert', $alert->id)
            ->assertDispatched('alert-deleted')
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('resource_alerts', ['id' => $alert->id]);
    }

    // ==================== TOGGLE ALERT TESTS ====================

    public function test_can_toggle_alert_status(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertDispatched('notify');

        $freshAlert = $alert->fresh();
        $this->assertNotNull($freshAlert);
        $this->assertFalse($freshAlert->is_active);
    }

    public function test_toggle_enables_disabled_alert(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id);

        $freshAlert = $alert->fresh();
        $this->assertNotNull($freshAlert);
        $this->assertTrue($freshAlert->is_active);
    }

    // ==================== TEST ALERT TESTS ====================

    public function test_can_test_alert_successfully(): void
    {
        $this->mockMetricsService();
        $this->mockAlertService(true);
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('testAlert', $alert->id)
            ->assertDispatched('notify', function ($name, $data) {
                return $data['type'] === 'success';
            });
    }

    public function test_test_alert_shows_error_on_failure(): void
    {
        $this->mockMetricsService();
        $this->mockAlertService(false);
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('testAlert', $alert->id)
            ->assertDispatched('notify', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== METRICS TESTS ====================

    public function test_current_metrics_returns_zeros_when_no_data(): void
    {
        $this->mockMetricsService();

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $metrics = $component->viewData('currentMetrics');
        $this->assertEquals(0, $metrics['cpu']);
        $this->assertEquals(0, $metrics['memory']);
        $this->assertEquals(0, $metrics['disk']);
        $this->assertEquals(0, $metrics['load']);
    }

    public function test_can_refresh_metrics(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertDispatched('notify');
    }

    // ==================== ALERT HISTORY TESTS ====================

    public function test_shows_alert_history(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(5)->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->viewData('alertHistory');
        $this->assertEquals(5, $history->total());
    }

    public function test_alert_history_is_paginated(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(25)->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->viewData('alertHistory');
        $this->assertEquals(25, $history->total());
        $this->assertEquals(10, $history->perPage());
    }

    // ==================== MODAL TESTS ====================

    public function test_open_create_modal_resets_form(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'memory')
            ->set('threshold_value', 50.0)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_value', 80.0);
    }

    public function test_close_create_modal(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    public function test_close_edit_modal(): void
    {
        $this->mockMetricsService();
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('showEditModal', true)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('editingId', null);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_form_values(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_type', 'above')
            ->assertSet('threshold_value', 80.0)
            ->assertSet('cooldown_minutes', 15)
            ->assertSet('is_active', true)
            ->assertSet('enable_email', false)
            ->assertSet('enable_slack', false)
            ->assertSet('enable_discord', false);
    }

    // ==================== SERVER ISOLATION TESTS ====================

    public function test_alerts_are_server_specific(): void
    {
        $this->mockMetricsService();
        $server2 = Server::factory()->create(['status' => 'active']);
        ResourceAlert::factory()->count(3)->create(['server_id' => $this->server->id]);
        ResourceAlert::factory()->count(2)->create(['server_id' => $server2->id]);

        $component1 = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $component2 = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $server2]);

        $this->assertCount(3, $component1->viewData('alerts'));
        $this->assertCount(2, $component2->viewData('alerts'));
    }

    // ==================== RESOURCE TYPE TESTS ====================

    public function test_can_create_cpu_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->call('createAlert')
            ->assertHasNoErrors();
    }

    public function test_can_create_memory_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'memory')
            ->call('createAlert')
            ->assertHasNoErrors();
    }

    public function test_can_create_disk_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'disk')
            ->call('createAlert')
            ->assertHasNoErrors();
    }

    public function test_can_create_load_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'load')
            ->call('createAlert')
            ->assertHasNoErrors();
    }

    // ==================== THRESHOLD TYPE TESTS ====================

    public function test_can_create_above_threshold_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_type', 'above')
            ->call('createAlert')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('resource_alerts', ['threshold_type' => 'above']);
    }

    public function test_can_create_below_threshold_alert(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_type', 'below')
            ->call('createAlert')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('resource_alerts', ['threshold_type' => 'below']);
    }

    // ==================== MULTIPLE CHANNELS TESTS ====================

    public function test_can_create_alert_with_all_notification_channels(): void
    {
        $this->mockMetricsService();

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 90.0)
            ->set('enable_email', true)
            ->set('email_address', 'test@example.com')
            ->set('enable_slack', true)
            ->set('slack_webhook', 'https://hooks.slack.com/test')
            ->set('enable_discord', true)
            ->set('discord_webhook', 'https://discord.com/api/webhooks/test')
            ->call('createAlert')
            ->assertHasNoErrors();

        $alert = ResourceAlert::first();
        $this->assertNotNull($alert);
        $this->assertCount(3, $alert->notification_channels);
    }

    // ==================== WORKFLOW TESTS ====================

    public function test_full_alert_lifecycle(): void
    {
        $this->mockMetricsService();

        // Create
        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 85.0)
            ->call('createAlert')
            ->assertHasNoErrors();

        $alert = ResourceAlert::first();
        $this->assertNotNull($alert);

        // Toggle off
        $component->call('toggleAlert', $alert->id);
        $freshAlert = $alert->fresh();
        $this->assertNotNull($freshAlert);
        $this->assertFalse($freshAlert->is_active);

        // Edit
        $component->call('openEditModal', $alert->id)
            ->set('threshold_value', 90.0)
            ->call('updateAlert');

        $freshAlert = $alert->fresh();
        $this->assertNotNull($freshAlert);
        $this->assertEquals(90.0, (float) $freshAlert->threshold_value);

        // Delete
        $component->call('deleteAlert', $alert->id);
        $this->assertDatabaseMissing('resource_alerts', ['id' => $alert->id]);
    }
}
