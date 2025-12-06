<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SystemSettingsService
{
    private const CACHE_KEY = 'system_settings:all';

    private const CACHE_TTL = 3600;

    /**
     * Get a setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return SystemSetting::get($key, $default);
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, ?string $type = null): SystemSetting
    {
        $this->clearCache();

        return SystemSetting::set($key, $value, $type);
    }

    /**
     * Get settings by group.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SystemSetting>
     */
    public function getByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return SystemSetting::getByGroup($group);
    }

    /**
     * Get all settings grouped.
     *
     * @return Collection<string, Collection<int, SystemSetting>>
     */
    public function getAllGrouped(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::orderBy('group')
                ->orderBy('key')
                ->get()
                ->groupBy('group');
        });
    }

    /**
     * Check if registration is enabled.
     */
    public function isRegistrationOpen(): bool
    {
        return SystemSetting::isRegistrationEnabled();
    }

    /**
     * Check if a feature is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) $this->get("features.{$feature}", true);
    }

    /**
     * Get all available groups.
     *
     * @return array<string, string>
     */
    public function getGroups(): array
    {
        return [
            'general' => 'General Settings',
            'auth' => 'Authentication',
            'features' => 'Features',
            'mail' => 'Mail Configuration',
            'security' => 'Security',
        ];
    }

    /**
     * Get default settings for seeding.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDefaultSettings(): array
    {
        return [
            // General
            [
                'key' => 'app.name',
                'value' => config('app.name', 'DevFlow Pro'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Application Name',
                'description' => 'The name of your application',
                'is_public' => true,
            ],
            [
                'key' => 'app.url',
                'value' => config('app.url', 'http://localhost'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Application URL',
                'description' => 'The base URL of your application',
                'is_public' => true,
            ],
            [
                'key' => 'app.debug',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Debug Mode',
                'description' => 'Enable detailed error messages (disable in production)',
                'is_public' => false,
            ],
            [
                'key' => 'app.timezone',
                'value' => config('app.timezone', 'UTC'),
                'type' => 'string',
                'group' => 'general',
                'label' => 'Timezone',
                'description' => 'Default timezone for the application',
                'is_public' => true,
            ],

            // Auth
            [
                'key' => 'auth.registration_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => 'Allow Registration',
                'description' => 'Allow new users to register',
                'is_public' => true,
            ],
            [
                'key' => 'auth.email_verification',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => 'Email Verification',
                'description' => 'Require email verification for new accounts',
                'is_public' => false,
            ],
            [
                'key' => 'auth.password_min_length',
                'value' => '8',
                'type' => 'integer',
                'group' => 'auth',
                'label' => 'Minimum Password Length',
                'description' => 'Minimum required password length',
                'is_public' => false,
            ],
            [
                'key' => 'auth.session_lifetime',
                'value' => '120',
                'type' => 'integer',
                'group' => 'auth',
                'label' => 'Session Lifetime (minutes)',
                'description' => 'How long sessions should last',
                'is_public' => false,
            ],

            // Features
            [
                'key' => 'features.api_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'API Access',
                'description' => 'Enable API endpoints for external access',
                'is_public' => false,
            ],
            [
                'key' => 'features.webhooks_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Webhooks',
                'description' => 'Enable webhook integrations',
                'is_public' => false,
            ],
            [
                'key' => 'features.public_portfolio',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Public Portfolio',
                'description' => 'Show public portfolio page',
                'is_public' => true,
            ],
            [
                'key' => 'features.teams_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Teams',
                'description' => 'Enable team collaboration features',
                'is_public' => false,
            ],
            [
                'key' => 'features.auto_deploy',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Auto Deploy',
                'description' => 'Enable automatic deployments on push',
                'is_public' => false,
            ],

            // Mail
            [
                'key' => 'mail.from_address',
                'value' => config('mail.from.address', 'noreply@devflow.pro'),
                'type' => 'string',
                'group' => 'mail',
                'label' => 'From Address',
                'description' => 'Email address used as sender',
                'is_public' => false,
            ],
            [
                'key' => 'mail.from_name',
                'value' => config('mail.from.name', 'DevFlow Pro'),
                'type' => 'string',
                'group' => 'mail',
                'label' => 'From Name',
                'description' => 'Name used as email sender',
                'is_public' => false,
            ],

            // Security
            [
                'key' => 'security.rate_limit',
                'value' => '60',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Rate Limit (per minute)',
                'description' => 'Maximum API requests per minute',
                'is_public' => false,
            ],
            [
                'key' => 'security.force_https',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Force HTTPS',
                'description' => 'Redirect all traffic to HTTPS',
                'is_public' => false,
            ],
            [
                'key' => 'security.two_factor_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Two-Factor Authentication',
                'description' => 'Enable 2FA for user accounts',
                'is_public' => false,
            ],
        ];
    }

    /**
     * Update a value in the .env file.
     */
    public function updateEnvFile(string $key, string $value): bool
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return false;
        }

        $envContent = File::get($envPath);
        $envKey = strtoupper(str_replace('.', '_', $key));

        // Check if the key exists
        $pattern = "/^{$envKey}=.*/m";

        if (preg_match($pattern, $envContent)) {
            // Update existing key
            $envContent = preg_replace(
                $pattern,
                "{$envKey}=\"{$value}\"",
                $envContent
            );
        } else {
            // Add new key
            $envContent .= "\n{$envKey}=\"{$value}\"";
        }

        File::put($envPath, $envContent);

        return true;
    }

    /**
     * Sync settings with .env file.
     *
     * @param  array<string>  $keys
     */
    public function syncWithEnv(array $keys = []): void
    {
        $envMapping = [
            'app.name' => 'APP_NAME',
            'app.url' => 'APP_URL',
            'app.debug' => 'APP_DEBUG',
            'app.timezone' => 'APP_TIMEZONE',
            'mail.from_address' => 'MAIL_FROM_ADDRESS',
            'mail.from_name' => 'MAIL_FROM_NAME',
        ];

        $keysToSync = empty($keys) ? array_keys($envMapping) : $keys;

        foreach ($keysToSync as $key) {
            if (isset($envMapping[$key])) {
                $value = $this->get($key);
                if ($value !== null) {
                    $this->updateEnvFile($key, (string) $value);
                }
            }
        }
    }

    /**
     * Bulk update settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function bulkUpdate(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting) {
                SystemSetting::set($key, $value, $setting->type);
            }
        }

        $this->clearCache();
    }

    /**
     * Clear all settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        SystemSetting::clearCache();
    }

    /**
     * Export settings to array.
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return SystemSetting::all()
            ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $setting->getTypedValue()])
            ->toArray();
    }

    /**
     * Import settings from array.
     *
     * @param  array<string, mixed>  $settings
     */
    public function import(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $existing = SystemSetting::where('key', $key)->first();
            if ($existing) {
                SystemSetting::set($key, $value, $existing->type);
            }
        }

        $this->clearCache();
    }
}
