<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'default_enable_ssl',
        'default_enable_webhooks',
        'default_enable_health_checks',
        'default_enable_backups',
        'default_enable_notifications',
        'default_enable_auto_deploy',
        'theme',
        'show_wizard_tips',
        'additional_settings',
    ];

    protected function casts(): array
    {
        return [
            'default_enable_ssl' => 'boolean',
            'default_enable_webhooks' => 'boolean',
            'default_enable_health_checks' => 'boolean',
            'default_enable_backups' => 'boolean',
            'default_enable_notifications' => 'boolean',
            'default_enable_auto_deploy' => 'boolean',
            'show_wizard_tips' => 'boolean',
            'additional_settings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default setup config for new projects
     */
    public function getDefaultSetupConfig(): array
    {
        return [
            'ssl' => $this->default_enable_ssl,
            'webhook' => $this->default_enable_webhooks,
            'health_check' => $this->default_enable_health_checks,
            'backup' => $this->default_enable_backups,
            'notifications' => $this->default_enable_notifications,
            'deployment' => $this->default_enable_auto_deploy,
        ];
    }

    /**
     * Get or create settings for a user
     */
    public static function getForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'default_enable_ssl' => true,
                'default_enable_webhooks' => true,
                'default_enable_health_checks' => true,
                'default_enable_backups' => true,
                'default_enable_notifications' => true,
                'default_enable_auto_deploy' => false,
                'theme' => 'dark',
                'show_wizard_tips' => true,
            ]
        );
    }

    /**
     * Update a specific setting
     */
    public function updateSetting(string $key, mixed $value): void
    {
        if ($this->isFillable($key)) {
            $this->update([$key => $value]);
        } else {
            $settings = $this->additional_settings ?? [];
            $settings[$key] = $value;
            $this->update(['additional_settings' => $settings]);
        }
    }

    /**
     * Get a specific additional setting
     */
    public function getAdditionalSetting(string $key, mixed $default = null): mixed
    {
        return $this->additional_settings[$key] ?? $default;
    }
}
