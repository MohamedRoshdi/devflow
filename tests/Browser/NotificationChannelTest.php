<?php

namespace Tests\Browser;


use PHPUnit\Framework\Attributes\Test;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class NotificationChannelTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'test-notification-server.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Test Notification Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-notification-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Notification Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-notification-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-notification-project',
            ]
        );
    }

    /**
     * Test notification channel manager page loads successfully
     *
     */

    #[Test]
    public function notification_channel_manager_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Notification Channels')
                ->assertSee('Configure notification channels for alerts and updates')
                ->assertPresent('button:contains("Add Channel")')
                ->screenshot('notification-channel-manager-page');
        });
    }

    /**
     * Test channel list is displayed
     *
     */

    #[Test]
    public function channel_list_is_displayed(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Test Slack Channel',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success', 'deployment.failed'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Test Slack Channel')
                ->assertSee('Slack')
                ->screenshot('channel-list-displayed');
        });

        $channel->delete();
    }

    /**
     * Test add channel button visible
     *
     */

    #[Test]
    public function add_channel_button_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertPresent('button:contains("Add Channel")')
                ->assertVisible('button:contains("Add Channel")')
                ->screenshot('add-channel-button-visible');
        });
    }

    /**
     * Test add channel modal opens
     *
     */

    #[Test]
    public function add_channel_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Add Notification Channel')
                ->assertSee('Channel Name')
                ->assertSee('Provider')
                ->assertSee('Webhook URL')
                ->screenshot('add-channel-modal-open');
        });
    }

    /**
     * Test channel type dropdown present
     *
     */

    #[Test]
    public function channel_type_dropdown_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Provider')
                ->assertSee('Slack')
                ->assertSee('Discord')
                ->assertSee('Teams')
                ->assertSee('Webhook')
                ->screenshot('channel-type-dropdown-present');
        });
    }

    /**
     * Test channel name field present
     *
     */

    #[Test]
    public function channel_name_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Channel Name')
                ->assertPresent('input[wire\\:model="name"]')
                ->screenshot('channel-name-field-present');
        });
    }

    /**
     * Test webhook URL field for Slack/Discord
     *
     */

    #[Test]
    public function webhook_url_field_for_slack_discord(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Webhook URL')
                ->assertPresent('input[wire\\:model="webhookUrl"]')
                ->assertAttribute('input[wire\\:model="webhookUrl"]', 'type', 'url')
                ->screenshot('webhook-url-field-slack-discord');
        });
    }

    /**
     * Test create channel form submits
     *
     */

    #[Test]
    public function create_channel_form_submits(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Production Slack Channel')
                ->type('input[wire\\:model="webhookUrl"]', 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertSee('Production Slack Channel')
                ->screenshot('create-channel-form-submitted');
        });
    }

    /**
     * Test test channel button visible
     *
     */

    #[Test]
    public function test_channel_button_visible(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Test Button Channel',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Test Button Channel')
                ->assertPresent('button:contains("Test")')
                ->screenshot('test-channel-button-visible');
        });

        $channel->delete();
    }

    /**
     * Test enable/disable toggle works
     *
     */

    #[Test]
    public function enable_disable_toggle_works(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Toggle Test Channel',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Toggle Test Channel')
                ->click('button[wire\\:click="toggleChannel('.$channel->id.')"]')
                ->pause(1000)
                ->waitForText('Channel disabled')
                ->screenshot('channel-disabled')
                ->click('button[wire\\:click="toggleChannel('.$channel->id.')"]')
                ->pause(1000)
                ->waitForText('Channel enabled')
                ->screenshot('channel-enabled');
        });

        $channel->delete();
    }

    /**
     * Test delete channel button visible
     *
     */

    #[Test]
    public function delete_channel_button_visible(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Delete Button Test',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Delete Button Test')
                ->assertPresent('button:contains("Delete")')
                ->screenshot('delete-channel-button-visible');
        });

        $channel->delete();
    }

    /**
     * Test channel status indicators shown
     *
     */

    #[Test]
    public function channel_status_indicators_shown(): void
    {
        $enabledChannel = NotificationChannel::create([
            'name' => 'Enabled Channel',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $disabledChannel = NotificationChannel::create([
            'name' => 'Disabled Channel',
            'provider' => 'discord',
            'type' => 'discord',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST'],
            'webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST',
            'enabled' => false,
            'events' => ['deployment.failed'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Enabled Channel')
                ->assertSee('Disabled Channel')
                ->assertPresent('.bg-green-600')
                ->assertPresent('.bg-gray-300')
                ->screenshot('channel-status-indicators');
        });

        $enabledChannel->delete();
        $disabledChannel->delete();
    }

    /**
     * Test flash messages display
     *
     */

    #[Test]
    public function flash_messages_display(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Flash Message Test',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button[wire\\:click="toggleChannel('.$channel->id.')"]')
                ->pause(1000)
                ->assertSee('Channel disabled')
                ->screenshot('flash-message-displayed');
        });

        $channel->delete();
    }

    /**
     * Test creating Slack channel
     *
     */

    #[Test]
    public function creating_slack_channel_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Slack Production Alerts')
                ->click('label:has(input[value="slack"])')
                ->pause(300)
                ->type('input[wire\\:model="webhookUrl"]', 'https://hooks.slack.com/services/T123456/B123456/XXXXXXXXXXXXXXXX')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertSee('Slack Production Alerts')
                ->screenshot('slack-channel-created');
        });
    }

    /**
     * Test creating Discord channel
     *
     */

    #[Test]
    public function creating_discord_channel_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Discord Dev Notifications')
                ->click('label:has(input[value="discord"])')
                ->pause(300)
                ->type('input[wire\\:model="webhookUrl"]', 'https://discord.com/api/webhooks/123456789012345678/XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertSee('Discord Dev Notifications')
                ->screenshot('discord-channel-created');
        });
    }

    /**
     * Test creating Teams channel
     *
     */

    #[Test]
    public function creating_teams_channel_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Teams Corporate Alerts')
                ->click('label:has(input[value="teams"])')
                ->pause(300)
                ->type('input[wire\\:model="webhookUrl"]', 'https://outlook.office.com/webhook/XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertSee('Teams Corporate Alerts')
                ->screenshot('teams-channel-created');
        });
    }

    /**
     * Test creating webhook channel with secret
     *
     */

    #[Test]
    public function creating_webhook_channel_with_secret_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Custom Webhook')
                ->click('label:has(input[value="webhook"])')
                ->pause(300)
                ->type('input[wire\\:model="webhookUrl"]', 'https://example.com/webhook')
                ->type('input[wire\\:model="webhookSecret"]', 'super-secret-key-123')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertSee('Custom Webhook')
                ->screenshot('webhook-channel-with-secret');
        });
    }

    /**
     * Test notification events selection
     *
     */

    #[Test]
    public function notification_events_selection_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Notification Events')
                ->assertPresent('input[type="checkbox"]')
                ->screenshot('notification-events-selection');
        });
    }

    /**
     * Test editing existing channel
     *
     */

    #[Test]
    public function editing_existing_channel_works(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Original Channel Name',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Original Channel Name')
                ->click('button[wire\\:click="editChannel('.$channel->id.')"]')
                ->pause(500)
                ->assertSee('Edit Notification Channel')
                ->assertInputValue('input[wire\\:model="name"]', 'Original Channel Name')
                ->clear('input[wire\\:model="name"]')
                ->type('input[wire\\:model="name"]', 'Updated Channel Name')
                ->pause(500)
                ->press('Update Channel')
                ->pause(1000)
                ->waitForText('Notification channel updated successfully')
                ->assertSee('Updated Channel Name')
                ->assertDontSee('Original Channel Name')
                ->screenshot('channel-edited');
        });

        $channel->delete();
    }

    /**
     * Test deleting channel
     *
     */

    #[Test]
    public function deleting_channel_works(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Channel to Delete',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Channel to Delete')
                ->click('button[wire\\:click="deleteChannel('.$channel->id.')"]')
                ->pause(1000)
                ->waitForText('Notification channel deleted successfully')
                ->assertDontSee('Channel to Delete')
                ->screenshot('channel-deleted');
        });
    }

    /**
     * Test validation error for empty name
     *
     */

    #[Test]
    public function validation_error_for_empty_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="webhookUrl"]', 'https://hooks.slack.com/services/TEST/TEST/TEST')
                ->press('Add Channel')
                ->pause(500)
                ->assertSee('The name field is required')
                ->screenshot('validation-error-empty-name');
        });
    }

    /**
     * Test validation error for empty webhook URL
     *
     */

    #[Test]
    public function validation_error_for_empty_webhook_url(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Test Channel')
                ->click('label:has(input[value="slack"])')
                ->pause(300)
                ->press('Add Channel')
                ->pause(500)
                ->assertSee('The webhook url field is required')
                ->screenshot('validation-error-empty-webhook-url');
        });
    }

    /**
     * Test empty state displays when no channels exist
     *
     */

    #[Test]
    public function empty_state_displays_when_no_channels(): void
    {
        // Clean up any existing channels
        NotificationChannel::query()->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('No notification channels')
                ->assertPresent('button:contains("Add Channel")')
                ->screenshot('channels-empty-state');
        });
    }

    /**
     * Test channel displays events correctly
     *
     */

    #[Test]
    public function channel_displays_events_correctly(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Event Display Channel',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success', 'deployment.failed', 'server.down'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Event Display Channel')
                ->assertSee('Events:')
                ->screenshot('channel-events-display');
        });

        $channel->delete();
    }

    /**
     * Test multiple channels display correctly
     *
     */

    #[Test]
    public function multiple_channels_display_correctly(): void
    {
        $channels = [
            NotificationChannel::create([
                'name' => 'Slack Channel 1',
                'provider' => 'slack',
                'type' => 'slack',
                'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST1/TEST1/TEST1'],
                'webhook_url' => 'https://hooks.slack.com/services/TEST1/TEST1/TEST1',
                'enabled' => true,
                'events' => ['deployment.success'],
            ]),
            NotificationChannel::create([
                'name' => 'Discord Channel 2',
                'provider' => 'discord',
                'type' => 'discord',
                'config' => ['webhook_url' => 'https://discord.com/api/webhooks/TEST2/TEST2'],
                'webhook_url' => 'https://discord.com/api/webhooks/TEST2/TEST2',
                'enabled' => true,
                'events' => ['deployment.failed'],
            ]),
            NotificationChannel::create([
                'name' => 'Teams Channel 3',
                'provider' => 'teams',
                'type' => 'teams',
                'config' => ['webhook_url' => 'https://outlook.office.com/webhook/TEST3'],
                'webhook_url' => 'https://outlook.office.com/webhook/TEST3',
                'enabled' => false,
                'events' => ['server.down'],
            ]),
        ];

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Slack Channel 1')
                ->assertSee('Discord Channel 2')
                ->assertSee('Teams Channel 3')
                ->screenshot('multiple-channels-display');
        });

        foreach ($channels as $channel) {
            $channel->delete();
        }
    }

    /**
     * Test closing modal without saving
     *
     */

    #[Test]
    public function closing_modal_without_saving_works(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Temporary Channel')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->assertDontSee('Add Notification Channel')
                ->assertDontSee('Temporary Channel')
                ->screenshot('modal-closed-without-saving');
        });
    }

    /**
     * Test provider icons display correctly
     *
     */

    #[Test]
    public function provider_icons_display_correctly(): void
    {
        $slackChannel = NotificationChannel::create([
            'name' => 'Slack Icon Test',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $discordChannel = NotificationChannel::create([
            'name' => 'Discord Icon Test',
            'provider' => 'discord',
            'type' => 'discord',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST'],
            'webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.failed'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Slack Icon Test')
                ->assertSee('Discord Icon Test')
                ->assertPresent('.bg-purple-100')
                ->assertPresent('.bg-indigo-100')
                ->screenshot('provider-icons-display');
        });

        $slackChannel->delete();
        $discordChannel->delete();
    }

    /**
     * Test modal closes after successful creation
     *
     */

    #[Test]
    public function modal_closes_after_successful_creation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->type('input[wire\\:model="name"]', 'Auto Close Test')
                ->type('input[wire\\:model="webhookUrl"]', 'https://hooks.slack.com/services/AUTO/CLOSE/TEST')
                ->pause(500)
                ->press('Add Channel')
                ->pause(1000)
                ->waitForText('Notification channel added successfully')
                ->assertDontSee('Add Notification Channel')
                ->screenshot('modal-closed-after-creation');
        });
    }

    /**
     * Test webhook secret field only visible for webhook type
     *
     */

    #[Test]
    public function webhook_secret_field_only_visible_for_webhook_type(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->click('label:has(input[value="slack"])')
                ->pause(300)
                ->assertDontSee('Webhook Secret')
                ->click('label:has(input[value="webhook"])')
                ->pause(300)
                ->assertSee('Webhook Secret (optional)')
                ->assertPresent('input[wire\\:model="webhookSecret"]')
                ->screenshot('webhook-secret-conditional-display');
        });
    }

    /**
     * Test page header displays correctly
     *
     */

    #[Test]
    public function page_header_displays_correctly(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Notification Channels')
                ->assertSee('Configure notification channels for alerts and updates')
                ->assertPresent('svg')
                ->screenshot('page-header-display');
        });
    }

    /**
     * Test enabled checkbox default state
     *
     */

    #[Test]
    public function enabled_checkbox_default_state(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->click('button:contains("Add Channel")')
                ->pause(500)
                ->assertSee('Enable this channel immediately')
                ->assertPresent('input[wire\\:model="enabled"][type="checkbox"]')
                ->assertChecked('input[wire\\:model="enabled"]')
                ->screenshot('enabled-checkbox-default-state');
        });
    }

    /**
     * Test edit button is visible for each channel
     *
     */

    #[Test]
    public function edit_button_is_visible_for_each_channel(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Edit Button Test',
            'provider' => 'slack',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST'],
            'webhook_url' => 'https://hooks.slack.com/services/TEST/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) use ($channel) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Edit Button Test')
                ->assertPresent('button[wire\\:click="editChannel('.$channel->id.')"]')
                ->assertSee('Edit')
                ->screenshot('edit-button-visible');
        });

        $channel->delete();
    }

    /**
     * Test channel card shows provider correctly
     *
     */

    #[Test]
    public function channel_card_shows_provider_correctly(): void
    {
        $channel = NotificationChannel::create([
            'name' => 'Provider Display Test',
            'provider' => 'discord',
            'type' => 'discord',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST'],
            'webhook_url' => 'https://discord.com/api/webhooks/TEST/TEST',
            'enabled' => true,
            'events' => ['deployment.success'],
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/notifications')
                ->assertSee('Provider Display Test')
                ->assertSee('Discord')
                ->screenshot('provider-display-correct');
        });

        $channel->delete();
    }
}
