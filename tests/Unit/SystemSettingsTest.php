<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Settings\SystemSettings;
use App\Models\SystemSetting;
use App\Services\SystemSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{

    protected SystemSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SystemSettingsService;
    }

    // ==================== Model Tests ====================

    public function test_can_create_system_setting(): void
    {
        $setting = SystemSetting::create([
            'key' => 'test.setting',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'general',
            'label' => 'Test Setting',
            'description' => 'A test setting',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'test.setting',
            'value' => 'test_value',
        ]);
    }

    public function test_can_get_setting_by_key(): void
    {
        SystemSetting::create([
            'key' => 'app.name',
            'value' => 'DevFlow Pro',
            'type' => 'string',
            'group' => 'general',
        ]);

        $value = SystemSetting::get('app.name');

        $this->assertEquals('DevFlow Pro', $value);
    }

    public function test_returns_default_when_setting_not_found(): void
    {
        $value = SystemSetting::get('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_can_set_setting_value(): void
    {
        SystemSetting::create([
            'key' => 'test.key',
            'value' => 'old_value',
            'type' => 'string',
            'group' => 'general',
        ]);

        SystemSetting::set('test.key', 'new_value');

        $this->assertEquals('new_value', SystemSetting::get('test.key'));
    }

    public function test_can_create_new_setting_via_set(): void
    {
        SystemSetting::set('new.setting', 'value', 'string');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'new.setting',
        ]);
    }

    public function test_boolean_type_casting(): void
    {
        SystemSetting::create([
            'key' => 'feature.enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'features',
        ]);

        $value = SystemSetting::get('feature.enabled');

        $this->assertTrue($value);
        $this->assertIsBool($value);
    }

    public function test_boolean_false_type_casting(): void
    {
        SystemSetting::create([
            'key' => 'feature.disabled',
            'value' => 'false',
            'type' => 'boolean',
            'group' => 'features',
        ]);

        $value = SystemSetting::get('feature.disabled');

        $this->assertFalse($value);
    }

    public function test_integer_type_casting(): void
    {
        SystemSetting::create([
            'key' => 'rate.limit',
            'value' => '60',
            'type' => 'integer',
            'group' => 'security',
        ]);

        $value = SystemSetting::get('rate.limit');

        $this->assertEquals(60, $value);
        $this->assertIsInt($value);
    }

    public function test_json_type_casting(): void
    {
        SystemSetting::create([
            'key' => 'app.config',
            'value' => '{"key":"value","nested":{"a":1}}',
            'type' => 'json',
            'group' => 'general',
        ]);

        $value = SystemSetting::get('app.config');

        $this->assertIsArray($value);
        $this->assertEquals('value', $value['key']);
        $this->assertEquals(1, $value['nested']['a']);
    }

    public function test_get_settings_by_group(): void
    {
        SystemSetting::create(['key' => 'auth.a', 'value' => '1', 'type' => 'string', 'group' => 'auth']);
        SystemSetting::create(['key' => 'auth.b', 'value' => '2', 'type' => 'string', 'group' => 'auth']);
        SystemSetting::create(['key' => 'general.a', 'value' => '3', 'type' => 'string', 'group' => 'general']);

        $authSettings = SystemSetting::getByGroup('auth');

        $this->assertCount(2, $authSettings);
        $this->assertTrue($authSettings->every(fn ($s) => $s->group === 'auth'));
    }

    public function test_get_all_settings_grouped(): void
    {
        SystemSetting::create(['key' => 'auth.a', 'value' => '1', 'type' => 'string', 'group' => 'auth']);
        SystemSetting::create(['key' => 'general.a', 'value' => '2', 'type' => 'string', 'group' => 'general']);

        $grouped = SystemSetting::getAllGrouped();

        $this->assertArrayHasKey('auth', $grouped->toArray());
        $this->assertArrayHasKey('general', $grouped->toArray());
    }

    public function test_is_registration_enabled(): void
    {
        SystemSetting::create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        $this->assertTrue(SystemSetting::isRegistrationEnabled());
    }

    public function test_is_registration_disabled(): void
    {
        SystemSetting::create([
            'key' => 'auth.registration_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        $this->assertFalse(SystemSetting::isRegistrationEnabled());
    }

    public function test_has_method_returns_true_when_exists(): void
    {
        SystemSetting::create(['key' => 'test.key', 'value' => 'val', 'type' => 'string', 'group' => 'general']);

        $this->assertTrue(SystemSetting::has('test.key'));
    }

    public function test_has_method_returns_false_when_not_exists(): void
    {
        $this->assertFalse(SystemSetting::has('nonexistent.key'));
    }

    public function test_remove_setting(): void
    {
        SystemSetting::create(['key' => 'to.remove', 'value' => 'val', 'type' => 'string', 'group' => 'general']);

        SystemSetting::remove('to.remove');

        $this->assertDatabaseMissing('system_settings', ['key' => 'to.remove']);
    }

    public function test_get_display_label_from_property(): void
    {
        $setting = SystemSetting::create([
            'key' => 'test.key',
            'value' => 'val',
            'type' => 'string',
            'group' => 'general',
            'label' => 'Custom Label',
        ]);

        $this->assertEquals('Custom Label', $setting->getDisplayLabel());
    }

    public function test_get_display_label_generated_from_key(): void
    {
        $setting = SystemSetting::create([
            'key' => 'auth.password_min_length',
            'value' => '8',
            'type' => 'integer',
            'group' => 'auth',
        ]);

        $this->assertEquals('Password Min Length', $setting->getDisplayLabel());
    }

    public function test_get_group_display_name(): void
    {
        $setting = SystemSetting::create([
            'key' => 'auth.test',
            'value' => 'val',
            'type' => 'string',
            'group' => 'auth',
        ]);

        $this->assertEquals('Authentication', $setting->getGroupDisplayName());
    }

    public function test_encrypted_value_storage(): void
    {
        $setting = SystemSetting::create([
            'key' => 'secret.api_key',
            'value' => Crypt::encryptString('my-secret-key'),
            'type' => 'string',
            'group' => 'security',
            'is_encrypted' => true,
        ]);

        $this->assertEquals('my-secret-key', $setting->getTypedValue());
    }

    // ==================== Cache Tests ====================

    public function test_setting_is_cached(): void
    {
        SystemSetting::create([
            'key' => 'cached.setting',
            'value' => 'cached_value',
            'type' => 'string',
            'group' => 'general',
        ]);

        // First call populates cache
        SystemSetting::get('cached.setting');

        // Check cache is populated
        $this->assertTrue(Cache::has('system_setting:cached.setting'));
    }

    public function test_cache_is_cleared_on_update(): void
    {
        $setting = SystemSetting::create([
            'key' => 'cache.test',
            'value' => 'old',
            'type' => 'string',
            'group' => 'general',
        ]);

        // Populate cache
        SystemSetting::get('cache.test');
        $this->assertTrue(Cache::has('system_setting:cache.test'));

        // Update setting
        $setting->update(['value' => 'new']);

        // Cache should be cleared
        $this->assertFalse(Cache::has('system_setting:cache.test'));
    }

    public function test_clear_all_cache(): void
    {
        SystemSetting::create(['key' => 'test.a', 'value' => '1', 'type' => 'string', 'group' => 'general']);
        SystemSetting::create(['key' => 'test.b', 'value' => '2', 'type' => 'string', 'group' => 'general']);

        SystemSetting::get('test.a');
        SystemSetting::get('test.b');

        SystemSetting::clearCache();

        $this->assertFalse(Cache::has('system_setting:test.a'));
        $this->assertFalse(Cache::has('system_setting:test.b'));
    }

    // ==================== Service Tests ====================

    public function test_service_get_method(): void
    {
        SystemSetting::create([
            'key' => 'service.test',
            'value' => 'service_value',
            'type' => 'string',
            'group' => 'general',
        ]);

        $value = $this->service->get('service.test');

        $this->assertEquals('service_value', $value);
    }

    public function test_service_set_method(): void
    {
        SystemSetting::create([
            'key' => 'service.set',
            'value' => 'old',
            'type' => 'string',
            'group' => 'general',
        ]);

        $this->service->set('service.set', 'new');

        $this->assertEquals('new', $this->service->get('service.set'));
    }

    public function test_service_get_by_group(): void
    {
        SystemSetting::create(['key' => 'features.a', 'value' => '1', 'type' => 'boolean', 'group' => 'features']);
        SystemSetting::create(['key' => 'features.b', 'value' => '0', 'type' => 'boolean', 'group' => 'features']);

        $settings = $this->service->getByGroup('features');

        $this->assertCount(2, $settings);
    }

    public function test_service_get_all_grouped(): void
    {
        SystemSetting::create(['key' => 'auth.test', 'value' => '1', 'type' => 'string', 'group' => 'auth']);
        SystemSetting::create(['key' => 'general.test', 'value' => '2', 'type' => 'string', 'group' => 'general']);

        $grouped = $this->service->getAllGrouped();

        $this->assertCount(2, $grouped);
    }

    public function test_service_is_registration_open(): void
    {
        SystemSetting::create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        $this->assertTrue($this->service->isRegistrationOpen());
    }

    public function test_service_is_feature_enabled(): void
    {
        SystemSetting::create([
            'key' => 'features.api_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'features',
        ]);

        $this->assertTrue($this->service->isFeatureEnabled('api_enabled'));
    }

    public function test_service_get_groups(): void
    {
        $groups = $this->service->getGroups();

        $this->assertArrayHasKey('general', $groups);
        $this->assertArrayHasKey('auth', $groups);
        $this->assertArrayHasKey('features', $groups);
        $this->assertArrayHasKey('mail', $groups);
        $this->assertArrayHasKey('security', $groups);
    }

    public function test_service_get_default_settings(): void
    {
        $defaults = $this->service->getDefaultSettings();

        $this->assertIsArray($defaults);
        $this->assertNotEmpty($defaults);

        // Check structure
        $first = $defaults[0];
        $this->assertArrayHasKey('key', $first);
        $this->assertArrayHasKey('value', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('group', $first);
    }

    public function test_service_bulk_update(): void
    {
        SystemSetting::create(['key' => 'bulk.a', 'value' => 'old_a', 'type' => 'string', 'group' => 'general']);
        SystemSetting::create(['key' => 'bulk.b', 'value' => 'old_b', 'type' => 'string', 'group' => 'general']);

        $this->service->bulkUpdate([
            'bulk.a' => 'new_a',
            'bulk.b' => 'new_b',
        ]);

        $this->assertEquals('new_a', $this->service->get('bulk.a'));
        $this->assertEquals('new_b', $this->service->get('bulk.b'));
    }

    public function test_service_clear_cache(): void
    {
        SystemSetting::create(['key' => 'cache.svc', 'value' => '1', 'type' => 'string', 'group' => 'general']);

        $this->service->get('cache.svc');
        $this->assertTrue(Cache::has('system_setting:cache.svc'));

        $this->service->clearCache();

        $this->assertFalse(Cache::has('system_setting:cache.svc'));
    }

    public function test_service_export(): void
    {
        SystemSetting::create(['key' => 'export.a', 'value' => 'val_a', 'type' => 'string', 'group' => 'general']);
        SystemSetting::create(['key' => 'export.b', 'value' => 'val_b', 'type' => 'string', 'group' => 'general']);

        $exported = $this->service->export();

        $this->assertArrayHasKey('export.a', $exported);
        $this->assertArrayHasKey('export.b', $exported);
        $this->assertEquals('val_a', $exported['export.a']);
    }

    public function test_service_import(): void
    {
        SystemSetting::create(['key' => 'import.a', 'value' => 'old', 'type' => 'string', 'group' => 'general']);

        $this->service->import(['import.a' => 'imported']);

        $this->assertEquals('imported', $this->service->get('import.a'));
    }

    // ==================== Livewire Component Tests ====================

    public function test_livewire_component_renders(): void
    {
        $user = \App\Models\User::factory()->create();

        Livewire::actingAs($user)
            ->test(SystemSettings::class)
            ->assertStatus(200)
            ->assertSee('System Settings');
    }

    public function test_livewire_shows_grouped_settings(): void
    {
        $user = \App\Models\User::factory()->create();

        SystemSetting::create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
            'label' => 'Allow Registration',
        ]);

        Livewire::actingAs($user)
            ->test(SystemSettings::class)
            ->assertSee('Authentication');
    }

    public function test_livewire_toggle_setting(): void
    {
        $user = \App\Models\User::factory()->create();

        SystemSetting::create([
            'key' => 'test.toggle',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'general',
        ]);

        Livewire::actingAs($user)
            ->test(SystemSettings::class)
            ->call('toggleSetting', 'test.toggle')
            ->assertSet('settings.test.toggle', false);
    }

    public function test_livewire_save_settings(): void
    {
        $user = \App\Models\User::factory()->create();

        SystemSetting::create([
            'key' => 'save.test',
            'value' => 'old',
            'type' => 'string',
            'group' => 'general',
        ]);

        // Clear cache before testing
        Cache::flush();

        $component = Livewire::actingAs($user)
            ->test(SystemSettings::class);

        // The settings array uses dot notation keys
        $component->set('settings', array_merge($component->get('settings'), ['save.test' => 'new']))
            ->call('save')
            ->assertDispatched('notification');

        // Clear cache and check fresh value
        Cache::flush();
        $this->assertEquals('new', SystemSetting::where('key', 'save.test')->first()->getTypedValue());
    }

    public function test_livewire_clear_cache(): void
    {
        $user = \App\Models\User::factory()->create();

        Livewire::actingAs($user)
            ->test(SystemSettings::class)
            ->call('clearCache')
            ->assertDispatched('notification');
    }

    public function test_livewire_set_active_group(): void
    {
        $user = \App\Models\User::factory()->create();

        Livewire::actingAs($user)
            ->test(SystemSettings::class)
            ->call('setActiveGroup', 'auth')
            ->assertSet('activeGroup', 'auth');
    }

    public function test_livewire_groups_computed_property(): void
    {
        $user = \App\Models\User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(SystemSettings::class);

        // Access the groups computed property directly from the component instance
        $groups = $component->instance()->groups;

        $this->assertArrayHasKey('general', $groups);
        $this->assertArrayHasKey('auth', $groups);
    }

    // ==================== Seeder Tests ====================

    public function test_seeder_creates_default_settings(): void
    {
        $this->seed(\Database\Seeders\SystemSettingsSeeder::class);

        $this->assertDatabaseHas('system_settings', ['key' => 'app.name']);
        $this->assertDatabaseHas('system_settings', ['key' => 'auth.registration_enabled']);
        $this->assertDatabaseHas('system_settings', ['key' => 'features.api_enabled']);
    }

    public function test_seeder_updates_existing_settings(): void
    {
        SystemSetting::create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
            'group' => 'general',
        ]);

        $this->seed(\Database\Seeders\SystemSettingsSeeder::class);

        // Should update, not duplicate
        $this->assertEquals(1, SystemSetting::where('key', 'app.name')->count());
    }

    // ==================== Integration Tests ====================

    public function test_full_workflow(): void
    {
        // Seed defaults
        $this->seed(\Database\Seeders\SystemSettingsSeeder::class);

        // Get initial value
        $initial = SystemSetting::get('auth.registration_enabled');
        $this->assertTrue($initial);

        // Update via service
        $this->service->set('auth.registration_enabled', false);

        // Verify change
        $this->assertFalse(SystemSetting::get('auth.registration_enabled'));
        $this->assertFalse($this->service->isRegistrationOpen());

        // Clear cache and verify persistence
        $this->service->clearCache();
        $this->assertFalse(SystemSetting::get('auth.registration_enabled'));
    }

    public function test_setting_type_enforcement(): void
    {
        // Boolean setting
        SystemSetting::create(['key' => 'bool.test', 'value' => 'true', 'type' => 'boolean', 'group' => 'test']);
        $this->assertIsBool(SystemSetting::get('bool.test'));

        // Integer setting
        SystemSetting::create(['key' => 'int.test', 'value' => '42', 'type' => 'integer', 'group' => 'test']);
        $this->assertIsInt(SystemSetting::get('int.test'));

        // String setting
        SystemSetting::create(['key' => 'str.test', 'value' => 'hello', 'type' => 'string', 'group' => 'test']);
        $this->assertIsString(SystemSetting::get('str.test'));
    }
}
