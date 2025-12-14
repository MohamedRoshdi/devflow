<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\DefaultSetupPreferences;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefaultSetupPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['show_inline_help' => true]);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertStatus(200);
    }

    public function test_loads_default_values_when_no_settings_exist(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('defaultEnableSsl', true)
            ->assertSet('defaultEnableWebhooks', true)
            ->assertSet('defaultEnableHealthChecks', true)
            ->assertSet('defaultEnableBackups', true)
            ->assertSet('defaultEnableNotifications', true)
            ->assertSet('defaultEnableAutoDeploy', false)
            ->assertSet('theme', 'dark')
            ->assertSet('showWizardTips', true);
    }

    public function test_creates_user_settings_on_mount_if_not_exists(): void
    {
        $this->assertDatabaseMissing('user_settings', ['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class);

        $this->assertDatabaseHas('user_settings', ['user_id' => $this->user->id]);
    }

    // ==================== LOAD EXISTING SETTINGS TESTS ====================

    public function test_loads_existing_settings_on_mount(): void
    {
        UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'default_enable_ssl' => false,
            'default_enable_webhooks' => false,
            'default_enable_health_checks' => true,
            'default_enable_backups' => false,
            'default_enable_notifications' => true,
            'default_enable_auto_deploy' => true,
            'theme' => 'light',
            'show_wizard_tips' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('defaultEnableSsl', false)
            ->assertSet('defaultEnableWebhooks', false)
            ->assertSet('defaultEnableHealthChecks', true)
            ->assertSet('defaultEnableBackups', false)
            ->assertSet('defaultEnableNotifications', true)
            ->assertSet('defaultEnableAutoDeploy', true)
            ->assertSet('theme', 'light')
            ->assertSet('showWizardTips', false);
    }

    public function test_loads_user_show_inline_help_preference(): void
    {
        $this->user->update(['show_inline_help' => false]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('showInlineHelp', false);
    }

    public function test_defaults_show_inline_help_to_true_when_null(): void
    {
        $this->user->update(['show_inline_help' => null]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('showInlineHelp', true);
    }

    // ==================== TOGGLE SETTINGS TESTS ====================

    public function test_can_toggle_ssl_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('defaultEnableSsl', true)
            ->set('defaultEnableSsl', false)
            ->assertSet('defaultEnableSsl', false);
    }

    public function test_can_toggle_webhooks_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableWebhooks', false)
            ->assertSet('defaultEnableWebhooks', false);
    }

    public function test_can_toggle_health_checks_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableHealthChecks', false)
            ->assertSet('defaultEnableHealthChecks', false);
    }

    public function test_can_toggle_backups_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableBackups', false)
            ->assertSet('defaultEnableBackups', false);
    }

    public function test_can_toggle_notifications_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableNotifications', false)
            ->assertSet('defaultEnableNotifications', false);
    }

    public function test_can_toggle_auto_deploy_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableAutoDeploy', true)
            ->assertSet('defaultEnableAutoDeploy', true);
    }

    public function test_can_toggle_wizard_tips_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('showWizardTips', false)
            ->assertSet('showWizardTips', false);
    }

    public function test_can_toggle_inline_help_setting(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('showInlineHelp', false)
            ->assertSet('showInlineHelp', false);
    }

    // ==================== THEME TESTS ====================

    public function test_can_set_light_theme(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('theme', 'light')
            ->assertSet('theme', 'light');
    }

    public function test_can_set_dark_theme(): void
    {
        UserSettings::factory()->lightTheme()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('theme', 'light')
            ->set('theme', 'dark')
            ->assertSet('theme', 'dark');
    }

    public function test_can_set_system_theme(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('theme', 'system')
            ->assertSet('theme', 'system');
    }

    // ==================== SAVE TESTS ====================

    public function test_can_save_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableSsl', false)
            ->set('defaultEnableWebhooks', false)
            ->set('theme', 'light')
            ->call('save')
            ->assertSet('isSaving', false)
            ->assertSet('saveSuccess', true)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success';
            });

        $settings = UserSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($settings);
        $this->assertFalse($settings->default_enable_ssl);
        $this->assertFalse($settings->default_enable_webhooks);
        $this->assertEquals('light', $settings->theme);
    }

    public function test_save_updates_existing_settings(): void
    {
        UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'default_enable_ssl' => true,
            'theme' => 'dark',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableSsl', false)
            ->set('theme', 'light')
            ->call('save');

        $settings = UserSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($settings);
        $this->assertFalse($settings->default_enable_ssl);
        $this->assertEquals('light', $settings->theme);
    }

    public function test_save_updates_user_inline_help_preference(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('showInlineHelp', false)
            ->call('save');

        $this->user->refresh();
        $this->assertFalse($this->user->show_inline_help);
    }

    public function test_save_preserves_all_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableSsl', false)
            ->set('defaultEnableWebhooks', false)
            ->set('defaultEnableHealthChecks', false)
            ->set('defaultEnableBackups', false)
            ->set('defaultEnableNotifications', false)
            ->set('defaultEnableAutoDeploy', true)
            ->set('theme', 'light')
            ->set('showWizardTips', false)
            ->call('save');

        $settings = UserSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($settings);
        $this->assertFalse($settings->default_enable_ssl);
        $this->assertFalse($settings->default_enable_webhooks);
        $this->assertFalse($settings->default_enable_health_checks);
        $this->assertFalse($settings->default_enable_backups);
        $this->assertFalse($settings->default_enable_notifications);
        $this->assertTrue($settings->default_enable_auto_deploy);
        $this->assertEquals('light', $settings->theme);
        $this->assertFalse($settings->show_wizard_tips);
    }

    public function test_save_dispatches_success_notification(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->call('save')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'saved successfully');
            });
    }

    // ==================== LOADING STATE TESTS ====================

    public function test_initial_saving_state_is_false(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('isSaving', false);
    }

    public function test_initial_save_success_state_is_false(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('saveSuccess', false);
    }

    public function test_save_resets_saving_state_on_completion(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->call('save')
            ->assertSet('isSaving', false)
            ->assertSet('saveSuccess', true);
    }

    // ==================== USER ISOLATION TESTS ====================

    public function test_settings_are_user_isolated(): void
    {
        $otherUser = User::factory()->create();

        UserSettings::factory()->create([
            'user_id' => $otherUser->id,
            'theme' => 'light',
            'default_enable_ssl' => false,
        ]);

        // Current user should have defaults, not other user's settings
        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->assertSet('theme', 'dark')
            ->assertSet('defaultEnableSsl', true);
    }

    public function test_save_only_affects_current_user(): void
    {
        $otherUser = User::factory()->create();
        UserSettings::factory()->create([
            'user_id' => $otherUser->id,
            'theme' => 'dark',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('theme', 'light')
            ->call('save');

        // Other user's settings should be unchanged
        $otherSettings = UserSettings::where('user_id', $otherUser->id)->first();
        $this->assertNotNull($otherSettings);
        $this->assertEquals('dark', $otherSettings->theme);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_handles_multiple_saves(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class);

        $component->set('theme', 'light')
            ->call('save')
            ->assertSet('saveSuccess', true);

        $component->set('theme', 'dark')
            ->call('save')
            ->assertSet('saveSuccess', true);

        $settings = UserSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($settings);
        $this->assertEquals('dark', $settings->theme);
    }

    public function test_can_reset_all_to_defaults(): void
    {
        UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'default_enable_ssl' => false,
            'default_enable_webhooks' => false,
            'default_enable_health_checks' => false,
            'theme' => 'light',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefaultSetupPreferences::class)
            ->set('defaultEnableSsl', true)
            ->set('defaultEnableWebhooks', true)
            ->set('defaultEnableHealthChecks', true)
            ->set('theme', 'dark')
            ->call('save');

        $settings = UserSettings::where('user_id', $this->user->id)->first();
        $this->assertNotNull($settings);
        $this->assertTrue($settings->default_enable_ssl);
        $this->assertTrue($settings->default_enable_webhooks);
        $this->assertTrue($settings->default_enable_health_checks);
        $this->assertEquals('dark', $settings->theme);
    }

    // ==================== DEFAULT SETUP CONFIG TESTS ====================

    public function test_get_default_setup_config_returns_correct_structure(): void
    {
        $settings = UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'default_enable_ssl' => true,
            'default_enable_webhooks' => false,
            'default_enable_health_checks' => true,
            'default_enable_backups' => false,
            'default_enable_notifications' => true,
            'default_enable_auto_deploy' => false,
        ]);

        $config = $settings->getDefaultSetupConfig();

        $this->assertArrayHasKey('ssl', $config);
        $this->assertTrue($config['ssl']);
        $this->assertFalse($config['webhook']);
        $this->assertTrue($config['health_check']);
        $this->assertFalse($config['backup']);
        $this->assertTrue($config['notifications']);
        $this->assertFalse($config['deployment']);
    }

    // ==================== ADDITIONAL SETTINGS TESTS ====================

    public function test_update_setting_for_fillable_field(): void
    {
        $settings = UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'theme' => 'dark',
        ]);

        $settings->updateSetting('theme', 'light');

        $settings->refresh();
        $this->assertEquals('light', $settings->theme);
    }

    public function test_update_setting_for_additional_settings(): void
    {
        $settings = UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'additional_settings' => [],
        ]);

        $settings->updateSetting('custom_key', 'custom_value');

        $settings->refresh();
        $this->assertEquals('custom_value', $settings->getAdditionalSetting('custom_key'));
    }

    public function test_get_additional_setting_returns_default_when_not_found(): void
    {
        $settings = UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'additional_settings' => [],
        ]);

        $value = $settings->getAdditionalSetting('nonexistent', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_get_additional_setting_returns_stored_value(): void
    {
        $settings = UserSettings::factory()->create([
            'user_id' => $this->user->id,
            'additional_settings' => ['my_key' => 'my_value'],
        ]);

        $value = $settings->getAdditionalSetting('my_key');
        $this->assertEquals('my_value', $value);
    }
}
