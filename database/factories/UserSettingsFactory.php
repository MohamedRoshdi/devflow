<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSettings>
 */
class UserSettingsFactory extends Factory
{
    protected $model = UserSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'default_enable_ssl' => true,
            'default_enable_webhooks' => true,
            'default_enable_health_checks' => true,
            'default_enable_backups' => true,
            'default_enable_notifications' => true,
            'default_enable_auto_deploy' => false,
            'theme' => 'dark',
            'show_wizard_tips' => true,
            'additional_settings' => [],
        ];
    }

    /**
     * Indicate that the user prefers light theme.
     */
    public function lightTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => 'light',
        ]);
    }

    /**
     * Indicate that wizard tips are disabled.
     */
    public function noWizardTips(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_wizard_tips' => false,
        ]);
    }

    /**
     * Indicate that auto deploy is enabled by default.
     */
    public function autoDeployEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_enable_auto_deploy' => true,
        ]);
    }
}
