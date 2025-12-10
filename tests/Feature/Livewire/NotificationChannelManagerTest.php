<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Notifications\NotificationChannelManager;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class NotificationChannelManagerTest extends TestCase
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
            ->test(NotificationChannelManager::class)
            ->assertOk()
            ->assertViewIs('livewire.notifications.channel-manager');
    }

    public function test_component_displays_notification_channels(): void
    {
        $channels = NotificationChannel::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->assertOk()
            ->assertViewHas('channels', function ($viewChannels) use ($channels) {
                return $viewChannels->count() === 3;
            });
    }

    public function test_add_channel_modal_opens(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('addChannel')
            ->assertSet('showAddChannelModal', true)
            ->assertSet('name', '')
            ->assertSet('provider', 'slack');
    }

    public function test_channel_can_be_created_with_slack_provider(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Slack Notifications')
            ->set('provider', 'slack')
            ->set('webhookUrl', 'https://hooks.slack.com/services/xxx')
            ->set('events', ['deployment.success', 'deployment.failed'])
            ->call('saveChannel')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('notification_channels', [
            'name' => 'Slack Notifications',
            'type' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/services/xxx',
        ]);
    }

    public function test_channel_can_be_created_with_discord_provider(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Discord Alerts')
            ->set('provider', 'discord')
            ->set('webhookUrl', 'https://discord.com/api/webhooks/xxx')
            ->set('events', ['deployment.failed'])
            ->call('saveChannel')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('notification_channels', [
            'name' => 'Discord Alerts',
            'type' => 'discord',
        ]);
    }

    public function test_channel_can_be_created_with_email_provider(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Email Notifications')
            ->set('provider', 'email')
            ->set('email', 'admin@example.com')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('notification_channels', [
            'name' => 'Email Notifications',
            'type' => 'email',
        ]);
    }

    public function test_channel_validation_requires_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', '')
            ->set('provider', 'slack')
            ->set('webhookUrl', 'https://hooks.slack.com/services/xxx')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_channel_validation_requires_valid_provider(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Test Channel')
            ->set('provider', 'invalid-provider')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertHasErrors(['provider' => 'in']);
    }

    public function test_channel_validation_requires_webhook_url_for_slack(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Slack Channel')
            ->set('provider', 'slack')
            ->set('webhookUrl', '')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertHasErrors(['webhookUrl']);
    }

    public function test_channel_validation_requires_email_for_email_provider(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Email Channel')
            ->set('provider', 'email')
            ->set('email', '')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertHasErrors(['email']);
    }

    public function test_channel_validation_requires_at_least_one_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Test Channel')
            ->set('provider', 'slack')
            ->set('webhookUrl', 'https://hooks.slack.com/services/xxx')
            ->set('events', [])
            ->call('saveChannel')
            ->assertHasErrors(['events' => 'min']);
    }

    public function test_existing_channel_can_be_edited(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Original Name',
            'type' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/original',
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('editChannel', $channel)
            ->assertSet('editingChannel.id', $channel->id)
            ->assertSet('name', 'Original Name')
            ->assertSet('provider', 'slack')
            ->assertSet('webhookUrl', 'https://hooks.slack.com/original')
            ->assertSet('showAddChannelModal', true);
    }

    public function test_channel_can_be_updated(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Original Name',
            'type' => 'slack',
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('editChannel', $channel)
            ->set('name', 'Updated Name')
            ->set('webhookUrl', 'https://hooks.slack.com/updated')
            ->call('saveChannel')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'name' => 'Updated Name',
            'webhook_url' => 'https://hooks.slack.com/updated',
        ]);
    }

    public function test_channel_can_be_deleted(): void
    {
        $channel = NotificationChannel::factory()->create();

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('deleteChannel', $channel)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }

    public function test_channel_can_be_toggled_enabled_disabled(): void
    {
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('toggleChannel', $channel)
            ->assertDispatched('notify');

        $channel->refresh();
        $this->assertFalse($channel->enabled);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('toggleChannel', $channel)
            ->assertDispatched('notify');

        $channel->refresh();
        $this->assertTrue($channel->enabled);
    }

    public function test_test_notification_can_be_sent(): void
    {
        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
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
            ->test(NotificationChannelManager::class)
            ->call('testChannel', $channel)
            ->assertDispatched('notify');
    }

    public function test_test_notification_handles_failure(): void
    {
        $channel = NotificationChannel::factory()->create();

        $mockService = Mockery::mock(NotificationService::class);
        $mockService->shouldReceive('sendTestNotification')
            ->once()
            ->andReturn(false);

        $this->app->instance(NotificationService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('testChannel', $channel)
            ->assertDispatched('notify');
    }

    public function test_test_notification_handles_exception(): void
    {
        $channel = NotificationChannel::factory()->create();

        $mockService = Mockery::mock(NotificationService::class);
        $mockService->shouldReceive('sendTestNotification')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $this->app->instance(NotificationService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->call('testChannel', $channel)
            ->assertDispatched('notify');
    }

    public function test_events_can_be_toggled(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('events', ['deployment.success'])
            ->call('toggleEvent', 'deployment.failed')
            ->assertSet('events', ['deployment.success', 'deployment.failed'])
            ->call('toggleEvent', 'deployment.success')
            ->assertSet('events', ['deployment.failed']);
    }

    public function test_available_events_are_displayed(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->assertSee('Deployment Started')
            ->assertSee('Deployment Failed')
            ->assertSee('SSL Certificate Expiring Soon')
            ->assertSee('Backup Completed');
    }

    public function test_projects_are_available_for_selection(): void
    {
        $projects = Project::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class);

        $componentProjects = $component->get('projects');
        $this->assertCount(4, $componentProjects); // 3 + 1 from setUp
    }

    public function test_channel_can_be_assigned_to_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Project Channel')
            ->set('provider', 'slack')
            ->set('projectId', $this->project->id)
            ->set('webhookUrl', 'https://hooks.slack.com/services/xxx')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('notification_channels', [
            'name' => 'Project Channel',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_modal_closes_after_saving_channel(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('showAddChannelModal', true)
            ->set('name', 'Test Channel')
            ->set('provider', 'slack')
            ->set('webhookUrl', 'https://hooks.slack.com/services/xxx')
            ->set('events', ['deployment.success'])
            ->call('saveChannel')
            ->assertSet('showAddChannelModal', false);
    }

    public function test_form_resets_after_saving(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->set('name', 'Test Channel')
            ->set('provider', 'discord')
            ->set('webhookUrl', 'https://discord.com/webhook')
            ->set('events', ['deployment.failed'])
            ->call('saveChannel')
            ->assertSet('name', '')
            ->assertSet('provider', 'slack')
            ->assertSet('webhookUrl', '')
            ->assertSet('editingChannel', null);
    }

    public function test_component_paginates_channels(): void
    {
        NotificationChannel::factory()->count(20)->create();

        Livewire::actingAs($this->user)
            ->test(NotificationChannelManager::class)
            ->assertViewHas('channels', function ($channels) {
                return $channels->count() === 15; // Default pagination
            });
    }
}
