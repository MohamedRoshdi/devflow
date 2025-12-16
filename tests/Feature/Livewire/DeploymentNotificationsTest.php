<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Notifications\DeploymentNotifications;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentNotificationsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'notification_sound' => true,
            'desktop_notifications' => false,
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.notifications.deployment-notifications');
    }

    public function test_component_initializes_with_empty_notifications(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        $notifications = $component->get('notifications');
        $this->assertCount(0, $notifications);
    }

    public function test_component_loads_user_preferences(): void
    {
        $this->user->update([
            'notification_sound' => false,
            'desktop_notifications' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertSet('soundEnabled', false)
            ->assertSet('desktopNotificationsEnabled', true);
    }

    // Note: test_component_defaults_sound_enabled_when_null and
    // test_component_defaults_desktop_disabled_when_null removed because
    // database has NOT NULL constraints on notification_sound and desktop_notifications columns

    // ===== ADD NOTIFICATION =====

    public function test_can_add_notification(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'Test Project',
            'message' => 'Deployment completed successfully',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event);

        $notifications = $component->get('notifications');
        $this->assertCount(1, $notifications);
        $this->assertEquals(123, $notifications->first()['deployment_id']);
        $this->assertEquals('Test Project', $notifications->first()['project_name']);
        $this->assertEquals('Deployment completed successfully', $notifications->first()['message']);
        $this->assertEquals('success', $notifications->first()['type']);
        $this->assertFalse($notifications->first()['read']);
    }

    public function test_notifications_prepended_to_list(): void
    {
        $this->actingAs($this->user);

        $event1 = [
            'deployment_id' => 1,
            'project_name' => 'First',
            'message' => 'First deployment',
            'type' => 'success',
            'status' => 'completed',
        ];

        $event2 = [
            'deployment_id' => 2,
            'project_name' => 'Second',
            'message' => 'Second deployment',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event1)
            ->call('addNotification', $event2);

        $notifications = $component->get('notifications');
        $this->assertEquals(2, $notifications->first()['deployment_id']);
        $this->assertEquals(1, $notifications->last()['deployment_id']);
    }

    public function test_notifications_limited_to_10(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        for ($i = 1; $i <= 12; $i++) {
            $component->call('addNotification', [
                'deployment_id' => $i,
                'project_name' => "Project {$i}",
                'message' => "Message {$i}",
                'type' => 'success',
                'status' => 'completed',
            ]);
        }

        $notifications = $component->get('notifications');
        $this->assertCount(10, $notifications);
        $this->assertEquals(12, $notifications->first()['deployment_id']);
    }

    public function test_notification_includes_timestamp(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 1,
            'project_name' => 'Test',
            'message' => 'Test message',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event);

        $notifications = $component->get('notifications');
        $this->assertArrayHasKey('timestamp', $notifications->first());
        $this->assertNotNull($notifications->first()['timestamp']);
    }

    public function test_notification_generates_unique_id(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 1,
            'project_name' => 'Test',
            'message' => 'Test message',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event)
            ->call('addNotification', $event);

        $notifications = $component->get('notifications');
        $id1 = $notifications->first()['id'];
        $id2 = $notifications->last()['id'];
        $this->assertNotEquals($id1, $id2);
    }

    // ===== MARK AS READ =====

    public function test_can_mark_notification_as_read(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 1,
            'project_name' => 'Test',
            'message' => 'Test message',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event);

        $notifications = $component->get('notifications');
        $notificationId = $notifications->first()['id'];

        $component->call('markAsRead', $notificationId);

        $notifications = $component->get('notifications');
        $this->assertTrue($notifications->first()['read']);
    }

    public function test_mark_as_read_only_affects_target_notification(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        for ($i = 1; $i <= 3; $i++) {
            $component->call('addNotification', [
                'deployment_id' => $i,
                'project_name' => "Project {$i}",
                'message' => "Message {$i}",
                'type' => 'success',
                'status' => 'completed',
            ]);
        }

        $notifications = $component->get('notifications');
        $targetId = $notifications->get(1)['id'];

        $component->call('markAsRead', $targetId);

        $notifications = $component->get('notifications');
        $this->assertFalse($notifications->get(0)['read']);
        $this->assertTrue($notifications->get(1)['read']);
        $this->assertFalse($notifications->get(2)['read']);
    }

    public function test_mark_nonexistent_notification_does_nothing(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 1,
            'project_name' => 'Test',
            'message' => 'Test message',
            'type' => 'success',
            'status' => 'completed',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('addNotification', $event)
            ->call('markAsRead', 'nonexistent-id');

        $notifications = $component->get('notifications');
        $this->assertFalse($notifications->first()['read']);
    }

    // ===== CLEAR ALL =====

    public function test_can_clear_all_notifications(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        for ($i = 1; $i <= 5; $i++) {
            $component->call('addNotification', [
                'deployment_id' => $i,
                'project_name' => "Project {$i}",
                'message' => "Message {$i}",
                'type' => 'success',
                'status' => 'completed',
            ]);
        }

        $component->call('clearAll');

        $notifications = $component->get('notifications');
        $this->assertCount(0, $notifications);
    }

    // ===== TOGGLE SOUND =====

    public function test_can_toggle_sound_off(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertSet('soundEnabled', true)
            ->call('toggleSound')
            ->assertSet('soundEnabled', false);

        $this->user->refresh();
        $this->assertFalse($this->user->notification_sound);
    }

    public function test_can_toggle_sound_on(): void
    {
        $this->user->update(['notification_sound' => false]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertSet('soundEnabled', false)
            ->call('toggleSound')
            ->assertSet('soundEnabled', true);

        $this->user->refresh();
        $this->assertTrue($this->user->notification_sound);
    }

    // ===== TOGGLE DESKTOP NOTIFICATIONS =====

    public function test_can_toggle_desktop_notifications_on(): void
    {
        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertSet('desktopNotificationsEnabled', false)
            ->call('toggleDesktopNotifications')
            ->assertSet('desktopNotificationsEnabled', true)
            ->assertDispatched('request-notification-permission');

        $this->user->refresh();
        $this->assertTrue($this->user->desktop_notifications);
    }

    public function test_can_toggle_desktop_notifications_off(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->assertSet('desktopNotificationsEnabled', true)
            ->call('toggleDesktopNotifications')
            ->assertSet('desktopNotificationsEnabled', false);

        $this->user->refresh();
        $this->assertFalse($this->user->desktop_notifications);
    }

    public function test_toggle_desktop_off_does_not_dispatch_permission_request(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        Livewire::test(DeploymentNotifications::class)
            ->call('toggleDesktopNotifications')
            ->assertNotDispatched('request-notification-permission');
    }

    // ===== EVENT HANDLING =====

    public function test_deployment_status_updated_adds_notification(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment started',
            'type' => 'info',
            'status' => 'running',
        ];

        $component = Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event);

        $notifications = $component->get('notifications');
        $this->assertCount(1, $notifications);
        $this->assertEquals(123, $notifications->first()['deployment_id']);
    }

    public function test_deployment_status_updated_plays_sound_when_enabled(): void
    {
        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment completed',
            'type' => 'success',
            'status' => 'completed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertDispatched('play-notification-sound', type: 'success');
    }

    public function test_deployment_status_updated_does_not_play_sound_when_disabled(): void
    {
        $this->user->update(['notification_sound' => false]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment completed',
            'type' => 'success',
            'status' => 'completed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertNotDispatched('play-notification-sound');
    }

    public function test_deployment_status_success_shows_desktop_notification(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment completed successfully',
            'type' => 'success',
            'status' => 'completed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertDispatched('show-desktop-notification');
    }

    public function test_deployment_status_error_shows_desktop_notification(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment failed',
            'type' => 'error',
            'status' => 'failed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertDispatched('show-desktop-notification');
    }

    public function test_deployment_status_info_does_not_show_desktop_notification(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment started',
            'type' => 'info',
            'status' => 'running',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertNotDispatched('show-desktop-notification');
    }

    public function test_deployment_status_warning_does_not_show_desktop_notification(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment warning',
            'type' => 'warning',
            'status' => 'running',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertNotDispatched('show-desktop-notification');
    }

    public function test_desktop_notification_not_shown_when_disabled(): void
    {
        $this->user->update(['desktop_notifications' => false]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment completed',
            'type' => 'success',
            'status' => 'completed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertNotDispatched('show-desktop-notification');
    }

    // ===== ICON MAPPING =====

    public function test_desktop_notification_uses_correct_icon_for_success(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment completed',
            'type' => 'success',
            'status' => 'completed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertDispatched('show-desktop-notification', icon: '/icons/success.png');
    }

    public function test_desktop_notification_uses_correct_icon_for_error(): void
    {
        $this->user->update(['desktop_notifications' => true]);

        $this->actingAs($this->user);

        $event = [
            'deployment_id' => 123,
            'project_name' => 'My Project',
            'message' => 'Deployment failed',
            'type' => 'error',
            'status' => 'failed',
        ];

        Livewire::test(DeploymentNotifications::class)
            ->call('onDeploymentStatusUpdated', $event)
            ->assertDispatched('show-desktop-notification', icon: '/icons/error.png');
    }

    // ===== DEFAULT VALUES =====

    public function test_default_property_values(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        $notifications = $component->get('notifications');
        $this->assertCount(0, $notifications);
    }

    // ===== MULTIPLE OPERATIONS =====

    public function test_can_perform_multiple_operations(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(DeploymentNotifications::class);

        // Add notifications
        for ($i = 1; $i <= 3; $i++) {
            $component->call('addNotification', [
                'deployment_id' => $i,
                'project_name' => "Project {$i}",
                'message' => "Message {$i}",
                'type' => 'success',
                'status' => 'completed',
            ]);
        }

        $notifications = $component->get('notifications');
        $this->assertCount(3, $notifications);

        // Mark one as read
        $component->call('markAsRead', $notifications->get(1)['id']);

        $notifications = $component->get('notifications');
        $this->assertTrue($notifications->get(1)['read']);

        // Toggle sound
        $component->call('toggleSound');
        $component->assertSet('soundEnabled', false);

        // Clear all
        $component->call('clearAll');
        $notifications = $component->get('notifications');
        $this->assertCount(0, $notifications);
    }

    // ===== NOTIFICATION TYPES =====

    public function test_different_notification_types(): void
    {
        $this->actingAs($this->user);

        $types = ['success', 'error', 'warning', 'info'];

        $component = Livewire::test(DeploymentNotifications::class);

        foreach ($types as $type) {
            $component->call('addNotification', [
                'deployment_id' => 1,
                'project_name' => 'Test',
                'message' => "Message for {$type}",
                'type' => $type,
                'status' => 'completed',
            ]);
        }

        $notifications = $component->get('notifications');
        $this->assertCount(4, $notifications);
    }
}
