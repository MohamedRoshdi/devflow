<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationChannelManagerTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function user_can_view_notification_channels_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->assertSee('Notification Channels')
                ->assertPresent('[wire\\:id]');
        });
    }

    /** @test */
    public function user_can_open_add_channel_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->assertSee('Add Notification Channel');
        });
    }

    /** @test */
    public function user_can_create_slack_channel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->type('@channel-name', 'My Slack Channel')
                ->select('@channel-provider', 'slack')
                ->type('@webhook-url', 'https://hooks.slack.com/services/test')
                ->check('@event-deployment-success')
                ->click('@save-channel-button')
                ->waitForText('created successfully')
                ->assertSee('My Slack Channel');
        });
    }

    /** @test */
    public function user_can_create_discord_channel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->type('@channel-name', 'My Discord Channel')
                ->select('@channel-provider', 'discord')
                ->type('@webhook-url', 'https://discord.com/api/webhooks/test')
                ->check('@event-deployment-failed')
                ->click('@save-channel-button')
                ->waitForText('created successfully')
                ->assertSee('My Discord Channel');
        });
    }

    /** @test */
    public function user_can_edit_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Original Channel',
            'type' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/services/original',
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click("@edit-channel-{$channel->id}")
                ->waitFor('[x-show]')
                ->clear('@channel-name')
                ->type('@channel-name', 'Updated Channel')
                ->click('@save-channel-button')
                ->waitForText('updated successfully')
                ->assertSee('Updated Channel');
        });
    }

    /** @test */
    public function user_can_delete_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Channel To Delete',
            'type' => 'slack',
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->assertSee('Channel To Delete')
                ->click("@delete-channel-{$channel->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('deleted successfully')
                ->assertDontSee('Channel To Delete');
        });
    }

    /** @test */
    public function user_can_toggle_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Toggle Channel',
            'type' => 'slack',
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click("@toggle-channel-{$channel->id}")
                ->waitForText('disabled')
                ->assertSee('disabled');
        });
    }

    /** @test */
    public function user_can_test_channel(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Test Channel',
            'type' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click("@test-channel-{$channel->id}")
                ->waitFor('.notification');
        });
    }

    /** @test */
    public function form_validates_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->click('@save-channel-button')
                ->waitForText('required')
                ->assertSee('required');
        });
    }

    /** @test */
    public function form_validates_webhook_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->type('@channel-name', 'Test Channel')
                ->select('@channel-provider', 'slack')
                ->type('@webhook-url', 'not-a-url')
                ->click('@save-channel-button')
                ->waitForText('valid URL')
                ->assertSee('valid URL');
        });
    }

    /** @test */
    public function events_selection_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->click('@add-channel-button')
                ->waitFor('[x-show]')
                ->check('@event-deployment-success')
                ->check('@event-deployment-failed')
                ->check('@event-server-down')
                ->assertChecked('@event-deployment-success')
                ->assertChecked('@event-deployment-failed')
                ->assertChecked('@event-server-down');
        });
    }
}
