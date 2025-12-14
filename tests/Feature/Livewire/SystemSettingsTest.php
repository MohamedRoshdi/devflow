<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\SystemSettings;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createSettings(): void
    {
        SystemSetting::create([
            'key' => 'app_name',
            'value' => 'DevFlow Pro',
            'type' => 'string',
            'group' => 'general',
        ]);
        SystemSetting::create([
            'key' => 'enable_registration',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);
        SystemSetting::create([
            'key' => 'max_login_attempts',
            'value' => '5',
            'type' => 'integer',
            'group' => 'security',
        ]);
    }

    private function mockSystemSettingsService(): void
    {
        $this->mock(SystemSettingsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('bulkUpdate')->andReturn(true);
            $mock->shouldReceive('clearCache')->andReturn(true);
            $mock->shouldReceive('getDefaultSettings')->andReturn([
                ['key' => 'app_name', 'value' => 'Default App', 'type' => 'string'],
                ['key' => 'enable_registration', 'value' => 'true', 'type' => 'boolean'],
            ]);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->assertStatus(200);
    }

    public function test_loads_settings_on_mount(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $settings = $component->get('settings');
        $this->assertArrayHasKey('app_name', $settings);
        $this->assertEquals('DevFlow Pro', $settings['app_name']);
    }

    public function test_loads_boolean_setting_correctly(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $settings = $component->get('settings');
        $this->assertArrayHasKey('enable_registration', $settings);
        $this->assertTrue($settings['enable_registration']);
    }

    public function test_loads_integer_setting_correctly(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $settings = $component->get('settings');
        $this->assertArrayHasKey('max_login_attempts', $settings);
        $this->assertEquals(5, $settings['max_login_attempts']);
    }

    // ==================== GROUPED SETTINGS TESTS ====================

    public function test_groups_settings_by_group(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $grouped = $component->viewData('groupedSettings');
        $this->assertArrayHasKey('general', $grouped->toArray());
        $this->assertArrayHasKey('auth', $grouped->toArray());
        $this->assertArrayHasKey('security', $grouped->toArray());
    }

    public function test_has_predefined_groups(): void
    {
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $groups = $component->viewData('groups');
        $this->assertArrayHasKey('general', $groups);
        $this->assertArrayHasKey('auth', $groups);
        $this->assertArrayHasKey('features', $groups);
        $this->assertArrayHasKey('mail', $groups);
        $this->assertArrayHasKey('security', $groups);
    }

    // ==================== ACTIVE GROUP TESTS ====================

    public function test_default_active_group_is_general(): void
    {
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->assertSet('activeGroup', 'general');
    }

    public function test_can_set_active_group(): void
    {
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('setActiveGroup', 'security')
            ->assertSet('activeGroup', 'security');
    }

    public function test_can_switch_between_groups(): void
    {
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('setActiveGroup', 'auth')
            ->assertSet('activeGroup', 'auth')
            ->call('setActiveGroup', 'mail')
            ->assertSet('activeGroup', 'mail');
    }

    // ==================== TOGGLE SETTING TESTS ====================

    public function test_can_toggle_boolean_setting(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $initialValue = $component->get('settings')['enable_registration'];
        $this->assertTrue($initialValue);

        $component->call('toggleSetting', 'enable_registration');

        $newValue = $component->get('settings')['enable_registration'];
        $this->assertFalse($newValue);
    }

    public function test_toggle_nonexistent_key_does_nothing(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $settingsBefore = $component->get('settings');

        $component->call('toggleSetting', 'nonexistent_key');

        $settingsAfter = $component->get('settings');
        $this->assertEquals($settingsBefore, $settingsAfter);
    }

    // ==================== SAVE TESTS ====================

    public function test_can_save_settings(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->set('settings.app_name', 'New App Name')
            ->call('save')
            ->assertSet('isSaving', false)
            ->assertSet('saveSuccess', true)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });
    }

    public function test_save_shows_loading_state(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        // After save completes, isSaving should be false
        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('save')
            ->assertSet('isSaving', false);
    }

    public function test_save_handles_exception(): void
    {
        $this->createSettings();

        $this->mock(SystemSettingsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('bulkUpdate')
                ->andThrow(new \Exception('Database error'));
        });

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('save')
            ->assertSet('isSaving', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== RESET TO DEFAULTS TESTS ====================

    public function test_can_reset_to_defaults(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('resetToDefaults')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'reset');
            });
    }

    public function test_reset_reloads_settings(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->set('settings.app_name', 'Changed Name')
            ->call('resetToDefaults');

        // Settings should be reloaded from database
        $this->assertNotEmpty($component->get('settings'));
    }

    public function test_reset_handles_exception(): void
    {
        $this->createSettings();

        $this->mock(SystemSettingsService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getDefaultSettings')
                ->andThrow(new \Exception('Reset failed'));
        });

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('resetToDefaults')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== CLEAR CACHE TESTS ====================

    public function test_can_clear_cache(): void
    {
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->call('clearCache')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'cache');
            });
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->assertSet('isSaving', false)
            ->assertSet('saveSuccess', false)
            ->assertSet('activeGroup', 'general');
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_settings(): void
    {
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $settings = $component->get('settings');
        $this->assertEmpty($settings);
    }

    // ==================== LOAD SETTINGS TESTS ====================

    public function test_can_reload_settings(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        // Create new setting externally
        SystemSetting::create([
            'key' => 'new_setting',
            'value' => 'new_value',
            'type' => 'string',
            'group' => 'general',
        ]);

        $component->call('loadSettings');

        $settings = $component->get('settings');
        $this->assertArrayHasKey('new_setting', $settings);
    }

    // ==================== SETTINGS ORDERING TESTS ====================

    public function test_grouped_settings_ordered_by_group(): void
    {
        SystemSetting::create(['key' => 'z_setting', 'value' => '1', 'type' => 'string', 'group' => 'general']);
        SystemSetting::create(['key' => 'a_setting', 'value' => '2', 'type' => 'string', 'group' => 'auth']);
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $grouped = $component->viewData('groupedSettings');
        $keys = $grouped->keys()->toArray();

        // auth should come before general alphabetically
        $authIndex = array_search('auth', $keys);
        $generalIndex = array_search('general', $keys);
        $this->assertLessThan($generalIndex, $authIndex);
    }

    // ==================== UPDATE SETTINGS VIA SET TESTS ====================

    public function test_can_update_string_setting(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->set('settings.app_name', 'Updated Name')
            ->assertSet('settings.app_name', 'Updated Name');
    }

    public function test_can_update_integer_setting(): void
    {
        $this->createSettings();
        $this->mockSystemSettingsService();

        Livewire::actingAs($this->user)
            ->test(SystemSettings::class)
            ->set('settings.max_login_attempts', 10)
            ->assertSet('settings.max_login_attempts', 10);
    }

    // ==================== GROUP LABELS TESTS ====================

    public function test_groups_have_display_labels(): void
    {
        $this->mockSystemSettingsService();

        $component = Livewire::actingAs($this->user)
            ->test(SystemSettings::class);

        $groups = $component->viewData('groups');
        $this->assertEquals('General Settings', $groups['general']);
        $this->assertEquals('Authentication', $groups['auth']);
        $this->assertEquals('Features', $groups['features']);
        $this->assertEquals('Mail Configuration', $groups['mail']);
        $this->assertEquals('Security', $groups['security']);
    }
}
