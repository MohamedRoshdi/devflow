<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\SystemSetting;
use App\Services\SystemSettingsService;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemSettingsServiceTest extends TestCase
{
    

    protected SystemSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SystemSettingsService();
    }

    #[Test]
    public function it_can_get_a_setting_value(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow Pro',
            'type' => 'string',
        ]);

        $value = $this->service->get('app.name');

        $this->assertEquals('DevFlow Pro', $value);
    }

    #[Test]
    public function it_returns_default_when_setting_not_found(): void
    {
        $value = $this->service->get('nonexistent.key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    #[Test]
    public function it_can_set_a_setting_value(): void
    {
        $setting = $this->service->set('app.name', 'My App', 'string');

        $this->assertInstanceOf(SystemSetting::class, $setting);
        $this->assertEquals('app.name', $setting->key);
        $this->assertEquals('My App', $setting->value);
        $this->assertEquals('string', $setting->type);
    }

    #[Test]
    public function it_can_update_existing_setting(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        $this->service->set('app.name', 'New Name', 'string');

        $this->assertEquals('New Name', $this->service->get('app.name'));
    }

    #[Test]
    public function it_can_get_settings_by_group(): void
    {
        SystemSetting::factory()->create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        SystemSetting::factory()->create([
            'key' => 'auth.email_verification',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow',
            'type' => 'string',
            'group' => 'general',
        ]);

        $authSettings = $this->service->getByGroup('auth');

        $this->assertCount(2, $authSettings);
        $this->assertTrue($authSettings->every(fn ($s) => $s->group === 'auth'));
    }

    #[Test]
    public function it_can_get_all_settings_grouped(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow',
            'type' => 'string',
            'group' => 'general',
        ]);

        SystemSetting::factory()->create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        $grouped = $this->service->getAllGrouped();

        $this->assertCount(2, $grouped);
        $this->assertTrue($grouped->has('general'));
        $this->assertTrue($grouped->has('auth'));
    }

    #[Test]
    public function it_caches_all_grouped_settings(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow',
            'type' => 'string',
            'group' => 'general',
        ]);

        // First call should hit database
        $this->service->getAllGrouped();

        // Second call should use cache
        $this->assertTrue(Cache::has('system_settings:all'));
    }

    #[Test]
    public function it_can_check_if_registration_is_open(): void
    {
        SystemSetting::factory()->create([
            'key' => 'auth.registration_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'auth',
        ]);

        $isOpen = $this->service->isRegistrationOpen();

        $this->assertTrue($isOpen);
    }

    #[Test]
    public function it_can_check_if_feature_is_enabled(): void
    {
        SystemSetting::factory()->create([
            'key' => 'features.api_enabled',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'features',
        ]);

        $isEnabled = $this->service->isFeatureEnabled('api_enabled');

        $this->assertTrue($isEnabled);
    }

    #[Test]
    public function it_returns_true_for_missing_feature_by_default(): void
    {
        $isEnabled = $this->service->isFeatureEnabled('nonexistent_feature');

        $this->assertTrue($isEnabled);
    }

    #[Test]
    public function it_can_get_all_available_groups(): void
    {
        $groups = $this->service->getGroups();

        $this->assertIsArray($groups);
        $this->assertArrayHasKey('general', $groups);
        $this->assertArrayHasKey('auth', $groups);
        $this->assertArrayHasKey('features', $groups);
        $this->assertArrayHasKey('mail', $groups);
        $this->assertArrayHasKey('security', $groups);
    }

    #[Test]
    public function it_can_get_default_settings(): void
    {
        $defaults = $this->service->getDefaultSettings();

        $this->assertIsArray($defaults);
        $this->assertNotEmpty($defaults);

        // Check structure of first setting
        $firstSetting = $defaults[0];
        $this->assertArrayHasKey('key', $firstSetting);
        $this->assertArrayHasKey('value', $firstSetting);
        $this->assertArrayHasKey('type', $firstSetting);
        $this->assertArrayHasKey('group', $firstSetting);
        $this->assertArrayHasKey('label', $firstSetting);
        $this->assertArrayHasKey('description', $firstSetting);
        $this->assertArrayHasKey('is_public', $firstSetting);
    }

    #[Test]
    public function default_settings_include_all_required_keys(): void
    {
        $defaults = $this->service->getDefaultSettings();
        $keys = array_column($defaults, 'key');

        $requiredKeys = [
            'app.name',
            'app.url',
            'app.debug',
            'auth.registration_enabled',
            'features.api_enabled',
            'mail.from_address',
            'security.rate_limit',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertContains($key, $keys, "Missing required key: {$key}");
        }
    }

    #[Test]
    public function it_can_bulk_update_settings(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        SystemSetting::factory()->create([
            'key' => 'app.url',
            'value' => 'http://old.com',
            'type' => 'string',
        ]);

        $this->service->bulkUpdate([
            'app.name' => 'New Name',
            'app.url' => 'http://new.com',
        ]);

        $this->assertEquals('New Name', $this->service->get('app.name'));
        $this->assertEquals('http://new.com', $this->service->get('app.url'));
    }

    #[Test]
    public function bulk_update_clears_cache(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        // Populate cache
        $this->service->getAllGrouped();
        $this->assertTrue(Cache::has('system_settings:all'));

        // Bulk update should clear cache
        $this->service->bulkUpdate(['app.name' => 'New Name']);

        $this->assertFalse(Cache::has('system_settings:all'));
    }

    #[Test]
    public function it_can_clear_cache(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow',
            'type' => 'string',
        ]);

        // Populate cache
        $this->service->get('app.name');
        $this->service->getAllGrouped();

        $this->service->clearCache();

        $this->assertFalse(Cache::has('system_settings:all'));
    }

    #[Test]
    public function set_clears_cache(): void
    {
        // Populate cache
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old',
            'type' => 'string',
        ]);
        $this->service->getAllGrouped();

        $this->assertTrue(Cache::has('system_settings:all'));

        $this->service->set('app.name', 'New', 'string');

        $this->assertFalse(Cache::has('system_settings:all'));
    }

    #[Test]
    public function it_can_export_settings_to_array(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'DevFlow Pro',
            'type' => 'string',
        ]);

        SystemSetting::factory()->create([
            'key' => 'app.debug',
            'value' => 'true',
            'type' => 'boolean',
        ]);

        $exported = $this->service->export();

        $this->assertIsArray($exported);
        $this->assertArrayHasKey('app.name', $exported);
        $this->assertArrayHasKey('app.debug', $exported);
        $this->assertEquals('DevFlow Pro', $exported['app.name']);
        $this->assertTrue($exported['app.debug']); // Should be boolean
    }

    #[Test]
    public function it_can_import_settings_from_array(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        SystemSetting::factory()->create([
            'key' => 'app.url',
            'value' => 'http://old.com',
            'type' => 'string',
        ]);

        $this->service->import([
            'app.name' => 'Imported Name',
            'app.url' => 'http://imported.com',
        ]);

        $this->assertEquals('Imported Name', $this->service->get('app.name'));
        $this->assertEquals('http://imported.com', $this->service->get('app.url'));
    }

    #[Test]
    public function import_clears_cache(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old',
            'type' => 'string',
        ]);

        // Populate cache
        $this->service->getAllGrouped();
        $this->assertTrue(Cache::has('system_settings:all'));

        $this->service->import(['app.name' => 'New']);

        $this->assertFalse(Cache::has('system_settings:all'));
    }

    #[Test]
    public function it_can_update_env_file(): void
    {
        $envPath = base_path('.env');
        $originalContent = File::exists($envPath) ? File::get($envPath) : '';

        try {
            // Create test env file
            File::put($envPath, "APP_NAME=\"Old Name\"\nAPP_URL=http://old.com");

            $result = $this->service->updateEnvFile('app.name', 'New Name');

            $this->assertTrue($result);

            $content = File::get($envPath);
            $this->assertStringContainsString('APP_NAME="New Name"', $content);
        } finally {
            // Restore original content
            if ($originalContent) {
                File::put($envPath, $originalContent);
            }
        }
    }

    #[Test]
    public function it_adds_new_key_to_env_file_if_not_exists(): void
    {
        $envPath = base_path('.env');
        $originalContent = File::exists($envPath) ? File::get($envPath) : '';

        try {
            // Create test env file without the key
            File::put($envPath, "APP_NAME=\"Test App\"");

            $result = $this->service->updateEnvFile('app.url', 'http://test.com');

            $this->assertTrue($result);

            $content = File::get($envPath);
            $this->assertStringContainsString('APP_URL="http://test.com"', $content);
        } finally {
            // Restore original content
            if ($originalContent) {
                File::put($envPath, $originalContent);
            }
        }
    }

    #[Test]
    public function update_env_file_returns_false_when_file_not_exists(): void
    {
        $nonExistentPath = base_path('.env.nonexistent');

        // Mock File facade temporarily
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(false);

        $result = $this->service->updateEnvFile('app.name', 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_boolean_type_casting_in_set(): void
    {
        $setting = $this->service->set('app.debug', true, 'boolean');

        $this->assertEquals('true', $setting->value);

        $value = $this->service->get('app.debug');
        $this->assertTrue($value);
    }

    #[Test]
    public function it_handles_integer_type_casting_in_set(): void
    {
        $setting = $this->service->set('security.rate_limit', 100, 'integer');

        $this->assertEquals('100', $setting->value);

        $value = $this->service->get('security.rate_limit');
        $this->assertSame(100, $value);
    }

    #[Test]
    public function it_handles_json_type_casting_in_set(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        $setting = $this->service->set('custom.json', $data, 'json');

        $value = $this->service->get('custom.json');
        $this->assertIsArray($value);
        $this->assertEquals($data, $value);
    }

    #[Test]
    public function it_can_sync_settings_with_env(): void
    {
        $envPath = base_path('.env');
        $originalContent = File::exists($envPath) ? File::get($envPath) : '';

        try {
            // Create test settings
            SystemSetting::factory()->create([
                'key' => 'app.name',
                'value' => 'DevFlow Pro',
                'type' => 'string',
            ]);

            SystemSetting::factory()->create([
                'key' => 'app.url',
                'value' => 'http://devflow.com',
                'type' => 'string',
            ]);

            // Create env file
            File::put($envPath, "APP_NAME=\"Old\"\nAPP_URL=\"http://old.com\"");

            // Sync specific keys
            $this->service->syncWithEnv(['app.name', 'app.url']);

            $content = File::get($envPath);
            $this->assertStringContainsString('APP_NAME="DevFlow Pro"', $content);
            $this->assertStringContainsString('APP_URL="http://devflow.com"', $content);
        } finally {
            // Restore original content
            if ($originalContent) {
                File::put($envPath, $originalContent);
            }
        }
    }

    #[Test]
    public function sync_with_env_handles_empty_keys_array(): void
    {
        $envPath = base_path('.env');
        $originalContent = File::exists($envPath) ? File::get($envPath) : '';

        try {
            // Create mappable settings
            SystemSetting::factory()->create([
                'key' => 'app.name',
                'value' => 'DevFlow Pro',
                'type' => 'string',
            ]);

            File::put($envPath, "APP_NAME=\"Old\"");

            // Empty array should sync all mappable keys
            $this->service->syncWithEnv([]);

            $content = File::get($envPath);
            $this->assertStringContainsString('APP_NAME="DevFlow Pro"', $content);
        } finally {
            if ($originalContent) {
                File::put($envPath, $originalContent);
            }
        }
    }

    #[Test]
    public function it_does_not_update_non_existent_settings_in_bulk_update(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        // This should not create a new setting
        $this->service->bulkUpdate([
            'app.name' => 'New Name',
            'nonexistent.key' => 'Some Value',
        ]);

        $this->assertEquals('New Name', $this->service->get('app.name'));
        $this->assertNull($this->service->get('nonexistent.key'));
    }

    #[Test]
    public function it_does_not_import_non_existent_settings(): void
    {
        SystemSetting::factory()->create([
            'key' => 'app.name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        $this->service->import([
            'app.name' => 'Imported Name',
            'nonexistent.key' => 'Some Value',
        ]);

        $this->assertEquals('Imported Name', $this->service->get('app.name'));
        $this->assertNull($this->service->get('nonexistent.key'));
    }
}
