<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\HealthCheckManager;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\HealthCheckService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class HealthCheckManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->server = Server::factory()->create();
    }

    public function test_component_renders_for_authenticated_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->assertOk()
            ->assertViewIs('livewire.settings.health-check-manager');
    }

    public function test_component_displays_health_checks_list(): void
    {
        $checks = HealthCheck::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->assertOk()
            ->assertViewHas('healthChecks', function ($viewChecks) use ($checks) {
                return $viewChecks->count() === 3;
            });
    }

    public function test_component_displays_health_stats(): void
    {
        HealthCheck::factory()->healthy()->count(2)->create();
        HealthCheck::factory()->degraded()->count(1)->create();
        HealthCheck::factory()->down()->count(1)->create();

        $component = Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class);

        $stats = $component->get('healthStats');
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['healthy']);
        $this->assertEquals(1, $stats['degraded']);
        $this->assertEquals(1, $stats['down']);
    }

    public function test_open_create_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('editingCheckId', null)
            ->assertSet('check_type', 'http')
            ->assertSet('expected_status', 200)
            ->assertSet('interval_minutes', 5)
            ->assertSet('timeout_seconds', 30)
            ->assertSet('is_active', true);
    }

    public function test_close_create_modal_hides_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('showCreateModal', true)
            ->call('closeCreateModal')
            ->assertSet('showCreateModal', false);
    }

    public function test_http_health_check_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('project_id', $this->project->id)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('expected_status', 200)
            ->set('interval_minutes', 10)
            ->set('timeout_seconds', 30)
            ->set('is_active', true)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'project_id' => $this->project->id,
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'interval_minutes' => 10,
            'timeout_seconds' => 30,
            'is_active' => true,
        ]);
    }

    public function test_tcp_health_check_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('server_id', $this->server->id)
            ->set('check_type', 'tcp')
            ->set('target_url', 'example.com:3306')
            ->set('expected_status', 200)
            ->set('interval_minutes', 5)
            ->set('timeout_seconds', 15)
            ->set('is_active', true)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'server_id' => $this->server->id,
            'check_type' => 'tcp',
            'target_url' => 'example.com:3306',
        ]);
    }

    public function test_ping_health_check_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('server_id', $this->server->id)
            ->set('check_type', 'ping')
            ->set('target_url', '8.8.8.8')
            ->set('expected_status', 200)
            ->set('interval_minutes', 5)
            ->set('timeout_seconds', 10)
            ->set('is_active', true)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'check_type' => 'ping',
            'target_url' => '8.8.8.8',
        ]);
    }

    public function test_ssl_expiry_health_check_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('project_id', $this->project->id)
            ->set('check_type', 'ssl_expiry')
            ->set('target_url', 'https://secure.example.com')
            ->set('expected_status', 200)
            ->set('interval_minutes', 60)
            ->set('timeout_seconds', 30)
            ->set('is_active', true)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'check_type' => 'ssl_expiry',
            'target_url' => 'https://secure.example.com',
        ]);
    }

    public function test_health_check_validation_requires_check_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', '')
            ->set('target_url', 'https://example.com')
            ->call('saveCheck')
            ->assertHasErrors(['check_type' => 'required']);
    }

    public function test_health_check_validation_requires_valid_check_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'invalid_type')
            ->set('target_url', 'https://example.com')
            ->call('saveCheck')
            ->assertHasErrors(['check_type' => 'in']);
    }

    public function test_health_check_validation_requires_target_url(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', '')
            ->call('saveCheck')
            ->assertHasErrors(['target_url' => 'required']);
    }

    public function test_health_check_validation_requires_valid_expected_status(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('expected_status', 99) // Below minimum
            ->call('saveCheck')
            ->assertHasErrors(['expected_status' => 'min']);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('expected_status', 600) // Above maximum
            ->call('saveCheck')
            ->assertHasErrors(['expected_status' => 'max']);
    }

    public function test_health_check_validation_requires_valid_interval_minutes(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('interval_minutes', 0) // Below minimum
            ->call('saveCheck')
            ->assertHasErrors(['interval_minutes' => 'min']);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('interval_minutes', 1500) // Above maximum
            ->call('saveCheck')
            ->assertHasErrors(['interval_minutes' => 'max']);
    }

    public function test_health_check_validation_requires_valid_timeout_seconds(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('timeout_seconds', 2) // Below minimum
            ->call('saveCheck')
            ->assertHasErrors(['timeout_seconds' => 'min']);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('timeout_seconds', 400) // Above maximum
            ->call('saveCheck')
            ->assertHasErrors(['timeout_seconds' => 'max']);
    }

    public function test_health_check_validation_validates_project_id_exists(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('project_id', 99999) // Non-existent project
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->call('saveCheck')
            ->assertHasErrors(['project_id' => 'exists']);
    }

    public function test_health_check_validation_validates_server_id_exists(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('server_id', 99999) // Non-existent server
            ->set('check_type', 'tcp')
            ->set('target_url', 'example.com:3306')
            ->call('saveCheck')
            ->assertHasErrors(['server_id' => 'exists']);
    }

    public function test_existing_health_check_can_be_edited(): void
    {
        $check = HealthCheck::factory()->create([
            'project_id' => $this->project->id,
            'check_type' => 'http',
            'target_url' => 'https://original.com',
            'expected_status' => 200,
            'interval_minutes' => 5,
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('editCheck', $check->id)
            ->assertSet('editingCheckId', $check->id)
            ->assertSet('project_id', $check->project_id)
            ->assertSet('check_type', 'http')
            ->assertSet('target_url', 'https://original.com')
            ->assertSet('expected_status', 200)
            ->assertSet('showCreateModal', true);
    }

    public function test_health_check_can_be_updated(): void
    {
        $check = HealthCheck::factory()->create([
            'project_id' => $this->project->id,
            'target_url' => 'https://original.com',
            'interval_minutes' => 5,
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('editCheck', $check->id)
            ->set('target_url', 'https://updated.com')
            ->set('interval_minutes', 15)
            ->call('saveCheck')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('health_checks', [
            'id' => $check->id,
            'target_url' => 'https://updated.com',
            'interval_minutes' => 15,
        ]);
    }

    public function test_health_check_can_be_deleted(): void
    {
        $check = HealthCheck::factory()->create();

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('deleteCheck', $check->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('health_checks', [
            'id' => $check->id,
        ]);
    }

    public function test_health_check_can_be_toggled_active_inactive(): void
    {
        $check = HealthCheck::factory()->create([
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('toggleCheckActive', $check->id)
            ->assertDispatched('notification');

        $check->refresh();
        $this->assertFalse($check->is_active);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('toggleCheckActive', $check->id)
            ->assertDispatched('notification');

        $check->refresh();
        $this->assertTrue($check->is_active);
    }

    public function test_manual_health_check_can_be_triggered(): void
    {
        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
        ]);

        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('runCheck')
            ->with(Mockery::on(function ($arg) use ($check) {
                return $arg->id === $check->id;
            }))
            ->once()
            ->andReturn(HealthCheckResult::factory()->make());

        $this->app->instance(HealthCheckService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('runCheck', $check->id)
            ->assertDispatched('notification');
    }

    public function test_manual_health_check_handles_exception(): void
    {
        $check = HealthCheck::factory()->create();

        $mockService = Mockery::mock(HealthCheckService::class);
        $mockService->shouldReceive('runCheck')
            ->once()
            ->andThrow(new \Exception('Check failed'));

        $this->app->instance(HealthCheckService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('runCheck', $check->id)
            ->assertDispatched('notification');
    }

    public function test_health_check_results_modal_can_be_opened(): void
    {
        $check = HealthCheck::factory()->create();

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('openResultsModal', $check->id)
            ->assertSet('showResultsModal', true)
            ->assertSet('viewingResultsCheckId', $check->id);
    }

    public function test_health_check_results_modal_can_be_closed(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('showResultsModal', true)
            ->set('viewingResultsCheckId', 1)
            ->call('closeResultsModal')
            ->assertSet('showResultsModal', false)
            ->assertSet('viewingResultsCheckId', null);
    }

    public function test_notification_channels_can_be_assigned_to_health_check(): void
    {
        $channels = NotificationChannel::factory()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('project_id', $this->project->id)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->set('selectedChannels', $channels->pluck('id')->toArray())
            ->call('saveCheck')
            ->assertDispatched('notification');

        $check = HealthCheck::latest()->first();
        $this->assertNotNull($check);
        $this->assertCount(3, $check->notificationChannels);
    }

    public function test_notification_channels_can_be_updated_for_existing_health_check(): void
    {
        $check = HealthCheck::factory()->create();
        $oldChannels = NotificationChannel::factory()->count(2)->create();
        $newChannels = NotificationChannel::factory()->count(3)->create();

        $check->notificationChannels()->attach($oldChannels->pluck('id')->toArray(), [
            'notify_on_failure' => true,
            'notify_on_recovery' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('editCheck', $check->id)
            ->set('selectedChannels', $newChannels->pluck('id')->toArray())
            ->call('saveCheck')
            ->assertDispatched('notification');

        $check->refresh();
        $this->assertCount(3, $check->notificationChannels);
    }

    public function test_notification_channel_modal_can_be_opened(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('openChannelModal')
            ->assertSet('showChannelModal', true)
            ->assertSet('channel_type', 'email')
            ->assertSet('channel_is_active', true);
    }

    public function test_notification_channel_modal_can_be_closed(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('showChannelModal', true)
            ->call('closeChannelModal')
            ->assertSet('showChannelModal', false);
    }

    public function test_email_notification_channel_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'email')
            ->set('channel_name', 'Email Notifications')
            ->set('channel_email', 'admin@example.com')
            ->set('channel_is_active', true)
            ->call('saveChannel')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('notification_channels', [
            'type' => 'email',
            'name' => 'Email Notifications',
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $channel = NotificationChannel::where('name', 'Email Notifications')->first();
        $this->assertNotNull($channel);
        $this->assertEquals('admin@example.com', $channel->config['email']);
    }

    public function test_slack_notification_channel_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'slack')
            ->set('channel_name', 'Slack Alerts')
            ->set('channel_slack_webhook', 'https://hooks.slack.com/services/xxx')
            ->set('channel_is_active', true)
            ->call('saveChannel')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('notification_channels', [
            'type' => 'slack',
            'name' => 'Slack Alerts',
        ]);

        $channel = NotificationChannel::where('name', 'Slack Alerts')->first();
        $this->assertNotNull($channel);
        $this->assertEquals('https://hooks.slack.com/services/xxx', $channel->config['webhook_url']);
    }

    public function test_discord_notification_channel_can_be_created(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'discord')
            ->set('channel_name', 'Discord Notifications')
            ->set('channel_discord_webhook', 'https://discord.com/api/webhooks/xxx')
            ->set('channel_is_active', true)
            ->call('saveChannel')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('notification_channels', [
            'type' => 'discord',
            'name' => 'Discord Notifications',
        ]);

        $channel = NotificationChannel::where('name', 'Discord Notifications')->first();
        $this->assertNotNull($channel);
        $this->assertEquals('https://discord.com/api/webhooks/xxx', $channel->config['webhook_url']);
    }

    public function test_notification_channel_validation_requires_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'email')
            ->set('channel_name', '')
            ->set('channel_email', 'admin@example.com')
            ->call('saveChannel')
            ->assertHasErrors(['channel_name' => 'required']);
    }

    public function test_notification_channel_validation_requires_valid_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'invalid_type')
            ->set('channel_name', 'Test Channel')
            ->call('saveChannel')
            ->assertHasErrors(['channel_type' => 'in']);
    }

    public function test_notification_channel_validation_requires_email_for_email_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'email')
            ->set('channel_name', 'Email Channel')
            ->set('channel_email', '')
            ->call('saveChannel')
            ->assertHasErrors(['channel_email']);
    }

    public function test_notification_channel_validation_requires_valid_email(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'email')
            ->set('channel_name', 'Email Channel')
            ->set('channel_email', 'not-an-email')
            ->call('saveChannel')
            ->assertHasErrors(['channel_email' => 'email']);
    }

    public function test_notification_channel_validation_requires_webhook_for_slack(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'slack')
            ->set('channel_name', 'Slack Channel')
            ->set('channel_slack_webhook', '')
            ->call('saveChannel')
            ->assertHasErrors(['channel_slack_webhook']);
    }

    public function test_notification_channel_validation_requires_valid_url_for_slack(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'slack')
            ->set('channel_name', 'Slack Channel')
            ->set('channel_slack_webhook', 'not-a-url')
            ->call('saveChannel')
            ->assertHasErrors(['channel_slack_webhook' => 'url']);
    }

    public function test_notification_channel_validation_requires_webhook_for_discord(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'discord')
            ->set('channel_name', 'Discord Channel')
            ->set('channel_discord_webhook', '')
            ->call('saveChannel')
            ->assertHasErrors(['channel_discord_webhook']);
    }

    public function test_notification_channel_can_be_edited(): void
    {
        $channel = NotificationChannel::factory()->email()->create([
            'name' => 'Original Name',
            'config' => ['email' => 'original@example.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('editChannel', $channel->id)
            ->assertSet('editingChannelId', $channel->id)
            ->assertSet('channel_type', 'email')
            ->assertSet('channel_name', 'Original Name')
            ->assertSet('channel_email', 'original@example.com')
            ->assertSet('showChannelModal', true);
    }

    public function test_notification_channel_can_be_updated(): void
    {
        $channel = NotificationChannel::factory()->email()->create([
            'name' => 'Original Name',
            'config' => ['email' => 'original@example.com'],
        ]);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('editChannel', $channel->id)
            ->set('channel_name', 'Updated Name')
            ->set('channel_email', 'updated@example.com')
            ->call('saveChannel')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'name' => 'Updated Name',
        ]);

        $channel->refresh();
        $this->assertEquals('updated@example.com', $channel->config['email']);
    }

    public function test_notification_channel_can_be_deleted(): void
    {
        $channel = NotificationChannel::factory()->create();

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('deleteChannel', $channel->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }

    public function test_notification_channel_test_can_be_sent(): void
    {
        $channel = NotificationChannel::factory()->slack()->create([
            'is_active' => true,
        ]);

        $mockService = Mockery::mock(NotificationService::class);
        $mockService->shouldReceive('sendTestNotification')
            ->with(Mockery::on(function ($arg) use ($channel) {
                return $arg->id === $channel->id;
            }))
            ->once()
            ->andReturn(true);

        $this->app->instance(NotificationService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('testChannel', $channel->id)
            ->assertDispatched('notification');
    }

    public function test_notification_channel_test_handles_failure(): void
    {
        $channel = NotificationChannel::factory()->create();

        $mockService = Mockery::mock(NotificationService::class);
        $mockService->shouldReceive('sendTestNotification')
            ->once()
            ->andReturn(false);

        $this->app->instance(NotificationService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('testChannel', $channel->id)
            ->assertDispatched('notification');
    }

    public function test_notification_channel_test_handles_exception(): void
    {
        $channel = NotificationChannel::factory()->create();

        $mockService = Mockery::mock(NotificationService::class);
        $mockService->shouldReceive('sendTestNotification')
            ->once()
            ->andThrow(new \Exception('Test failed'));

        $this->app->instance(NotificationService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('testChannel', $channel->id)
            ->assertDispatched('notification');
    }

    public function test_component_displays_projects_for_selection(): void
    {
        $projects = Project::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class);

        $componentProjects = $component->get('projects');
        $this->assertCount(4, $componentProjects); // 3 + 1 from setUp
    }

    public function test_component_displays_servers_for_selection(): void
    {
        $servers = Server::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class);

        $componentServers = $component->get('servers');
        $this->assertCount(4, $componentServers); // 3 + 1 from setUp
    }

    public function test_component_displays_notification_channels(): void
    {
        $channels = NotificationChannel::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class);

        $componentChannels = $component->get('notificationChannels');
        $this->assertCount(3, $componentChannels);
    }

    public function test_refresh_health_checks_event_refreshes_data(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->dispatch('refresh-health-checks')
            ->assertOk();
    }

    public function test_modal_closes_after_saving_health_check(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('showCreateModal', true)
            ->set('project_id', $this->project->id)
            ->set('check_type', 'http')
            ->set('target_url', 'https://example.com')
            ->call('saveCheck')
            ->assertSet('showCreateModal', false);
    }

    public function test_modal_closes_after_saving_notification_channel(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('showChannelModal', true)
            ->set('channel_type', 'email')
            ->set('channel_name', 'Test Channel')
            ->set('channel_email', 'test@example.com')
            ->call('saveChannel')
            ->assertSet('showChannelModal', false);
    }

    public function test_form_resets_after_saving_health_check(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('project_id', $this->project->id)
            ->set('check_type', 'tcp')
            ->set('target_url', 'example.com:3306')
            ->set('interval_minutes', 30)
            ->call('saveCheck')
            ->assertSet('editingCheckId', null)
            ->assertSet('check_type', 'http')
            ->assertSet('expected_status', 200)
            ->assertSet('interval_minutes', 5);
    }

    public function test_form_resets_after_saving_notification_channel(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('channel_type', 'slack')
            ->set('channel_name', 'Test Channel')
            ->set('channel_slack_webhook', 'https://hooks.slack.com/services/xxx')
            ->call('saveChannel')
            ->assertSet('editingChannelId', null)
            ->assertSet('channel_type', 'email')
            ->assertSet('channel_name', '')
            ->assertSet('channel_is_active', true);
    }

    public function test_health_check_with_recent_results_relationship_loads(): void
    {
        $check = HealthCheck::factory()->create();
        HealthCheckResult::factory()->count(5)->create([
            'health_check_id' => $check->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class);

        $healthChecks = $component->get('healthChecks');
        $this->assertCount(1, $healthChecks);
        $this->assertTrue($healthChecks->first()->relationLoaded('recentResults'));
    }

    public function test_delete_health_check_handles_exception(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('deleteCheck', 99999) // Non-existent ID
            ->assertDispatched('notification');
    }

    public function test_delete_notification_channel_handles_exception(): void
    {
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->call('deleteChannel', 99999) // Non-existent ID
            ->assertDispatched('notification');
    }

    public function test_save_health_check_handles_exception(): void
    {
        // Intentionally invalid data that will cause an exception
        Livewire::actingAs($this->user)
            ->test(HealthCheckManager::class)
            ->set('check_type', 'http')
            ->set('target_url', str_repeat('a', 600)) // Exceeds max length
            ->call('saveCheck')
            ->assertDispatched('notification');
    }
}
