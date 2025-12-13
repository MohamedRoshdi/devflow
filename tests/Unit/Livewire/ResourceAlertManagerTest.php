<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Servers\ResourceAlertManager;
use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\User;
use App\Services\ResourceAlertService;
use App\Services\ServerMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ResourceAlertManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ==========================================
    // Component Rendering & Initialization Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    /** @test */
    public function resource_alert_manager_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_type', 'above')
            ->assertSet('threshold_value', 80.00)
            ->assertSet('cooldown_minutes', 15)
            ->assertSet('is_active', true)
            ->assertSet('enable_email', false)
            ->assertSet('enable_slack', false)
            ->assertSet('enable_discord', false)
            ->assertSet('showCreateModal', false)
            ->assertSet('showEditModal', false);
    }

    /** @test */
    public function resource_alert_manager_loads_server_correctly(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $this->assertEquals($this->server->id, $component->get('server')->id);
    }

    // ==========================================
    // Alert Listing & Display Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_displays_all_alerts_for_server(): void
    {
        ResourceAlert::factory()->count(3)->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $alerts = $component->get('alerts');
        $this->assertCount(3, $alerts);
    }

    /** @test */
    public function resource_alert_manager_only_shows_alerts_for_specific_server(): void
    {
        $otherServer = Server::factory()->create();

        ResourceAlert::factory()->count(2)->create(['server_id' => $this->server->id]);
        ResourceAlert::factory()->count(3)->create(['server_id' => $otherServer->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $alerts = $component->get('alerts');
        $this->assertCount(2, $alerts);
    }

    /** @test */
    public function resource_alert_manager_loads_alerts_with_latest_history(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(3)->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $alerts = $component->get('alerts');
        $this->assertNotNull($alerts->first()->latestHistory);
    }

    // ==========================================
    // Create Alert Modal Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    }

    /** @test */
    public function resource_alert_manager_can_close_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    /** @test */
    public function resource_alert_manager_resets_form_when_opening_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'memory')
            ->set('threshold_value', 90.0)
            ->call('openCreateModal')
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_value', 80.00);
    }

    /** @test */
    public function resource_alert_manager_clears_validation_errors_when_closing_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', -10) // Invalid value
            ->call('createAlert')
            ->assertHasErrors(['threshold_value'])
            ->call('closeCreateModal')
            ->assertHasNoErrors();
    }

    // ==========================================
    // Alert Creation Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_create_alert_with_valid_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 85.0)
            ->set('cooldown_minutes', 20)
            ->set('is_active', true)
            ->call('createAlert')
            ->assertDispatched('alert-created')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 85.0,
            'cooldown_minutes' => 20,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_for_memory(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'memory')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 90.0)
            ->set('cooldown_minutes', 15)
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'memory',
            'threshold_value' => 90.0,
        ]);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_for_disk(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'disk')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 95.0)
            ->set('cooldown_minutes', 30)
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'disk',
            'threshold_value' => 95.0,
        ]);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_for_load(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'load')
            ->set('threshold_type', 'above')
            ->set('threshold_value', 5.0)
            ->set('cooldown_minutes', 10)
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'resource_type' => 'load',
            'threshold_value' => 5.0,
        ]);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_with_below_threshold(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_type', 'below')
            ->set('threshold_value', 20.0)
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $this->assertDatabaseHas('resource_alerts', [
            'server_id' => $this->server->id,
            'threshold_type' => 'below',
            'threshold_value' => 20.0,
        ]);
    }

    /** @test */
    public function resource_alert_manager_closes_modal_after_creating_alert(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->call('createAlert')
            ->assertSet('showCreateModal', false);
    }

    // ==========================================
    // Alert Creation with Notification Channels Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_create_alert_with_email_notification(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->set('enable_email', true)
            ->set('email_address', 'admin@example.com')
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $alert = ResourceAlert::where('server_id', $this->server->id)->first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('email', $alert->notification_channels);
        $this->assertEquals('admin@example.com', $alert->notification_channels['email']['email']);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_with_slack_notification(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->set('enable_slack', true)
            ->set('slack_webhook', 'https://hooks.slack.com/services/xxx')
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $alert = ResourceAlert::where('server_id', $this->server->id)->first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('slack', $alert->notification_channels);
        $this->assertEquals('https://hooks.slack.com/services/xxx', $alert->notification_channels['slack']['webhook_url']);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_with_discord_notification(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->set('enable_discord', true)
            ->set('discord_webhook', 'https://discord.com/api/webhooks/xxx')
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $alert = ResourceAlert::where('server_id', $this->server->id)->first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('discord', $alert->notification_channels);
        $this->assertEquals('https://discord.com/api/webhooks/xxx', $alert->notification_channels['discord']['webhook_url']);
    }

    /** @test */
    public function resource_alert_manager_can_create_alert_with_multiple_notification_channels(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->set('enable_email', true)
            ->set('email_address', 'admin@example.com')
            ->set('enable_slack', true)
            ->set('slack_webhook', 'https://hooks.slack.com/services/xxx')
            ->set('enable_discord', true)
            ->set('discord_webhook', 'https://discord.com/api/webhooks/xxx')
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $alert = ResourceAlert::where('server_id', $this->server->id)->first();
        $this->assertNotNull($alert);
        $this->assertArrayHasKey('email', $alert->notification_channels);
        $this->assertArrayHasKey('slack', $alert->notification_channels);
        $this->assertArrayHasKey('discord', $alert->notification_channels);
    }

    /** @test */
    public function resource_alert_manager_does_not_include_disabled_notification_channels(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->set('enable_email', false)
            ->set('email_address', 'admin@example.com')
            ->call('createAlert')
            ->assertDispatched('alert-created');

        $alert = ResourceAlert::where('server_id', $this->server->id)->first();
        $this->assertNotNull($alert);
        $this->assertEmpty($alert->notification_channels);
    }

    // ==========================================
    // Alert Creation Validation Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_validates_resource_type_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', '')
            ->call('createAlert')
            ->assertHasErrors(['resource_type']);
    }

    /** @test */
    public function resource_alert_manager_validates_resource_type_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('resource_type', 'invalid')
            ->call('createAlert')
            ->assertHasErrors(['resource_type']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_type_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_type', '')
            ->call('createAlert')
            ->assertHasErrors(['threshold_type']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_type_is_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_type', 'invalid')
            ->call('createAlert')
            ->assertHasErrors(['threshold_type']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_value_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', '')
            ->call('createAlert')
            ->assertHasErrors(['threshold_value']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_value_is_numeric(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', 'not-a-number')
            ->call('createAlert')
            ->assertHasErrors(['threshold_value']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_value_minimum(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', -10)
            ->call('createAlert')
            ->assertHasErrors(['threshold_value']);
    }

    /** @test */
    public function resource_alert_manager_validates_threshold_value_maximum(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('threshold_value', 150)
            ->call('createAlert')
            ->assertHasErrors(['threshold_value']);
    }

    /** @test */
    public function resource_alert_manager_validates_cooldown_minutes_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('cooldown_minutes', '')
            ->call('createAlert')
            ->assertHasErrors(['cooldown_minutes']);
    }

    /** @test */
    public function resource_alert_manager_validates_cooldown_minutes_is_integer(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('cooldown_minutes', 'not-an-integer')
            ->call('createAlert')
            ->assertHasErrors(['cooldown_minutes']);
    }

    /** @test */
    public function resource_alert_manager_validates_cooldown_minutes_minimum(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('cooldown_minutes', 0)
            ->call('createAlert')
            ->assertHasErrors(['cooldown_minutes']);
    }

    /** @test */
    public function resource_alert_manager_validates_cooldown_minutes_maximum(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('cooldown_minutes', 2000)
            ->call('createAlert')
            ->assertHasErrors(['cooldown_minutes']);
    }

    /** @test */
    public function resource_alert_manager_validates_email_address_when_enabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_email', true)
            ->set('email_address', '')
            ->call('createAlert')
            ->assertHasErrors(['email_address']);
    }

    /** @test */
    public function resource_alert_manager_validates_email_address_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_email', true)
            ->set('email_address', 'invalid-email')
            ->call('createAlert')
            ->assertHasErrors(['email_address']);
    }

    /** @test */
    public function resource_alert_manager_validates_slack_webhook_when_enabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_slack', true)
            ->set('slack_webhook', '')
            ->call('createAlert')
            ->assertHasErrors(['slack_webhook']);
    }

    /** @test */
    public function resource_alert_manager_validates_slack_webhook_is_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_slack', true)
            ->set('slack_webhook', 'not-a-url')
            ->call('createAlert')
            ->assertHasErrors(['slack_webhook']);
    }

    /** @test */
    public function resource_alert_manager_validates_discord_webhook_when_enabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_discord', true)
            ->set('discord_webhook', '')
            ->call('createAlert')
            ->assertHasErrors(['discord_webhook']);
    }

    /** @test */
    public function resource_alert_manager_validates_discord_webhook_is_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->set('enable_discord', true)
            ->set('discord_webhook', 'not-a-url')
            ->call('createAlert')
            ->assertHasErrors(['discord_webhook']);
    }

    // ==========================================
    // Edit Alert Modal Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_open_edit_modal(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingAlert.id', $alert->id);
    }

    /** @test */
    public function resource_alert_manager_can_close_edit_modal(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->call('closeEditModal')
            ->assertSet('showEditModal', false)
            ->assertSet('editingAlert', null);
    }

    /** @test */
    public function resource_alert_manager_loads_alert_data_when_opening_edit_modal(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'memory',
            'threshold_type' => 'above',
            'threshold_value' => 75.0,
            'cooldown_minutes' => 30,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('resource_type', 'memory')
            ->assertSet('threshold_type', 'above')
            ->assertSet('threshold_value', 75.0)
            ->assertSet('cooldown_minutes', 30)
            ->assertSet('is_active', false);
    }

    /** @test */
    public function resource_alert_manager_loads_email_notification_channel_when_editing(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'email' => ['email' => 'test@example.com'],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('enable_email', true)
            ->assertSet('email_address', 'test@example.com');
    }

    /** @test */
    public function resource_alert_manager_loads_slack_notification_channel_when_editing(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'slack' => ['webhook_url' => 'https://hooks.slack.com/services/xxx'],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('enable_slack', true)
            ->assertSet('slack_webhook', 'https://hooks.slack.com/services/xxx');
    }

    /** @test */
    public function resource_alert_manager_loads_discord_notification_channel_when_editing(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'discord' => ['webhook_url' => 'https://discord.com/api/webhooks/xxx'],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('enable_discord', true)
            ->assertSet('discord_webhook', 'https://discord.com/api/webhooks/xxx');
    }

    // ==========================================
    // Update Alert Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_update_alert(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('threshold_value', 90.0)
            ->call('updateAlert')
            ->assertDispatched('alert-updated')
            ->assertDispatched('notify', type: 'success');

        $alert->refresh();
        $this->assertEquals(90.0, $alert->threshold_value);
    }

    /** @test */
    public function resource_alert_manager_can_update_alert_resource_type(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('resource_type', 'memory')
            ->call('updateAlert')
            ->assertDispatched('alert-updated');

        $alert->refresh();
        $this->assertEquals('memory', $alert->resource_type);
    }

    /** @test */
    public function resource_alert_manager_can_update_alert_threshold_type(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'threshold_type' => 'above',
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('threshold_type', 'below')
            ->call('updateAlert')
            ->assertDispatched('alert-updated');

        $alert->refresh();
        $this->assertEquals('below', $alert->threshold_type);
    }

    /** @test */
    public function resource_alert_manager_can_update_alert_cooldown(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'cooldown_minutes' => 15,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('cooldown_minutes', 60)
            ->call('updateAlert')
            ->assertDispatched('alert-updated');

        $alert->refresh();
        $this->assertEquals(60, $alert->cooldown_minutes);
    }

    /** @test */
    public function resource_alert_manager_can_update_alert_notification_channels(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('enable_email', true)
            ->set('email_address', 'new@example.com')
            ->call('updateAlert')
            ->assertDispatched('alert-updated');

        $alert->refresh();
        $this->assertArrayHasKey('email', $alert->notification_channels);
        $this->assertEquals('new@example.com', $alert->notification_channels['email']['email']);
    }

    /** @test */
    public function resource_alert_manager_closes_modal_after_updating_alert(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->call('updateAlert')
            ->assertSet('showEditModal', false);
    }

    /** @test */
    public function resource_alert_manager_does_not_update_without_editing_alert(): void
    {
        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('updateAlert')
            ->assertNotDispatched('alert-updated');
    }

    // ==========================================
    // Delete Alert Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_delete_alert(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('deleteAlert', $alert->id)
            ->assertDispatched('alert-deleted')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseMissing('resource_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function resource_alert_manager_deletes_alert_with_history(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(3)->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('deleteAlert', $alert->id)
            ->assertDispatched('alert-deleted');

        $this->assertDatabaseMissing('resource_alerts', ['id' => $alert->id]);
    }

    // ==========================================
    // Toggle Alert Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_toggle_alert_from_active_to_inactive(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertDispatched('notify', type: 'success');

        $alert->refresh();
        $this->assertFalse($alert->is_active);
    }

    /** @test */
    public function resource_alert_manager_can_toggle_alert_from_inactive_to_active(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertDispatched('notify', type: 'success');

        $alert->refresh();
        $this->assertTrue($alert->is_active);
    }

    /** @test */
    public function resource_alert_manager_shows_enabled_message_when_toggling_on(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertDispatched('notify', message: 'Alert enabled successfully!');
    }

    /** @test */
    public function resource_alert_manager_shows_disabled_message_when_toggling_off(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id)
            ->assertDispatched('notify', message: 'Alert disabled successfully!');
    }

    // ==========================================
    // Test Alert Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_can_test_alert_successfully(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        ServerMetric::factory()->create(['server_id' => $this->server->id]);

        $mockAlertService = Mockery::mock(ResourceAlertService::class);
        $mockAlertService->shouldReceive('testAlert')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $alert->id))
            ->andReturn(['success' => true, 'message' => 'Test notification sent successfully!']);

        $this->app->instance(ResourceAlertService::class, $mockAlertService);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('testAlert', $alert->id)
            ->assertDispatched('notify', type: 'success', message: 'Test notification sent successfully!');
    }

    /** @test */
    public function resource_alert_manager_handles_test_alert_failure(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        ServerMetric::factory()->create(['server_id' => $this->server->id]);

        $mockAlertService = Mockery::mock(ResourceAlertService::class);
        $mockAlertService->shouldReceive('testAlert')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $alert->id))
            ->andReturn(['success' => false, 'message' => 'Notification service error']);

        $this->app->instance(ResourceAlertService::class, $mockAlertService);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('testAlert', $alert->id)
            ->assertDispatched('notify', type: 'error');
    }

    // ==========================================
    // Current Metrics Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_displays_current_metrics_when_available(): void
    {
        ServerMetric::factory()->create([
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.8,
            'load_average_1' => 2.5,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $metrics = $component->get('currentMetrics');
        $this->assertEquals(45.5, $metrics['cpu']);
        $this->assertEquals(60.2, $metrics['memory']);
        $this->assertEquals(75.8, $metrics['disk']);
        $this->assertEquals(2.5, $metrics['load']);
    }

    /** @test */
    public function resource_alert_manager_displays_zero_metrics_when_unavailable(): void
    {
        $mockMetricsService = Mockery::mock(ServerMetricsService::class);
        $mockMetricsService->shouldReceive('getLatestMetrics')
            ->andReturn(null);

        $this->app->instance(ServerMetricsService::class, $mockMetricsService);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $metrics = $component->get('currentMetrics');
        $this->assertEquals(0, $metrics['cpu']);
        $this->assertEquals(0, $metrics['memory']);
        $this->assertEquals(0, $metrics['disk']);
        $this->assertEquals(0, $metrics['load']);
    }

    /** @test */
    public function resource_alert_manager_can_refresh_metrics(): void
    {
        $mockMetricsService = Mockery::mock(ServerMetricsService::class);
        $mockMetricsService->shouldReceive('collectMetrics')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $this->server->id))
            ->andReturn(true);

        $this->app->instance(ServerMetricsService::class, $mockMetricsService);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('refreshMetrics')
            ->assertDispatched('notify', type: 'success', message: 'Metrics refreshed!');
    }

    // ==========================================
    // Alert History Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_displays_alert_history(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(5)->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->get('alertHistory');
        $this->assertCount(5, $history);
    }

    /** @test */
    public function resource_alert_manager_paginates_alert_history(): void
    {
        AlertHistory::factory()->count(25)->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->get('alertHistory');
        $this->assertEquals(10, $history->perPage());
    }

    /** @test */
    public function resource_alert_manager_orders_history_by_created_at_desc(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        $oldHistory = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);

        $newHistory = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->get('alertHistory');
        $this->assertEquals($newHistory->id, $history->first()->id);
    }

    /** @test */
    public function resource_alert_manager_only_shows_history_for_specific_server(): void
    {
        $otherServer = Server::factory()->create();

        AlertHistory::factory()->count(3)->create(['server_id' => $this->server->id]);
        AlertHistory::factory()->count(5)->create(['server_id' => $otherServer->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $history = $component->get('alertHistory');
        $this->assertCount(3, $history);
    }

    // ==========================================
    // Component Integration Tests
    // ==========================================

    /** @test */
    public function resource_alert_manager_refreshes_alerts_after_creation(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $initialAlertsCount = $component->get('alerts')->count();

        $component
            ->set('resource_type', 'cpu')
            ->set('threshold_value', 80.0)
            ->call('createAlert');

        $this->assertEquals($initialAlertsCount + 1, ResourceAlert::count());
    }

    /** @test */
    public function resource_alert_manager_refreshes_alerts_after_update(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'threshold_value' => 80.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->set('threshold_value', 90.0)
            ->call('updateAlert');

        $alert->refresh();
        $this->assertEquals(90.0, $alert->threshold_value);
    }

    /** @test */
    public function resource_alert_manager_refreshes_alerts_after_deletion(): void
    {
        $alert = ResourceAlert::factory()->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server]);

        $initialCount = $component->get('alerts')->count();

        $component->call('deleteAlert', $alert->id);

        $this->assertEquals($initialCount - 1, ResourceAlert::count());
    }

    /** @test */
    public function resource_alert_manager_refreshes_alerts_after_toggle(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('toggleAlert', $alert->id);

        $alert->refresh();
        $this->assertFalse($alert->is_active);
    }

    /** @test */
    public function resource_alert_manager_clears_form_after_closing_edit_modal(): void
    {
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'memory',
            'threshold_value' => 90.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ResourceAlertManager::class, ['server' => $this->server])
            ->call('openEditModal', $alert->id)
            ->assertSet('resource_type', 'memory')
            ->call('closeEditModal')
            ->assertSet('resource_type', 'cpu')
            ->assertSet('threshold_value', 80.00);
    }
}
