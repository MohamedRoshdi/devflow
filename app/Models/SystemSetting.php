<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 * @property string|null $label
 * @property string|null $description
 * @property bool $is_public
 * @property bool $is_encrypted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SystemSetting extends Model
{
    /** @use HasFactory<\Database\Factories\SystemSettingFactory> */
    use HasFactory;

    private const CACHE_PREFIX = 'system_setting:';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
        'is_encrypted',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return $setting->getTypedValue();
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, ?string $type = null): self
    {
        $setting = self::firstOrNew(['key' => $key]);

        if ($type) {
            $setting->type = $type;
        }

        // Convert value to string for storage
        $stringValue = match ($setting->type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => is_string($value) ? $value : (json_encode($value) ?: '{}'),
            'integer' => (string) $value,
            default => (string) $value,
        };

        // Encrypt if needed
        if ($setting->is_encrypted) {
            $stringValue = Crypt::encryptString($stringValue);
        }

        $setting->value = $stringValue;
        $setting->save();

        // Clear cache
        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    /**
     * Get settings by group.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function getByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('group', $group)->orderBy('key')->get();
    }

    /**
     * Get all settings grouped.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, self>>
     */
    public static function getAllGrouped(): \Illuminate\Support\Collection
    {
        return self::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');
    }

    /**
     * Check if registration is enabled.
     */
    public static function isRegistrationEnabled(): bool
    {
        return (bool) self::get('auth.registration_enabled', true);
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $settings = self::all();

        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX.$setting->key);
        }
    }

    /**
     * Get the typed value based on the type field.
     */
    public function getTypedValue(): mixed
    {
        $value = $this->value;

        // Decrypt if encrypted
        if ($this->is_encrypted && $value !== null) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception) {
                // If decryption fails, return the raw value
            }
        }

        return match ($this->type) {
            'boolean' => $value === 'true' || $value === '1',
            'integer' => (int) $value,
            'json' => json_decode($value ?? '{}', true),
            default => $value,
        };
    }

    /**
     * Get the raw value (encrypted or not).
     */
    public function getRawValue(): ?string
    {
        return $this->value;
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Delete a setting by key.
     */
    public static function remove(string $key): bool
    {
        Cache::forget(self::CACHE_PREFIX.$key);

        return (bool) self::where('key', $key)->delete();
    }

    /**
     * Get the display label.
     */
    public function getDisplayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        // Generate label from key
        $parts = explode('.', $this->key);
        $lastPart = end($parts);

        return ucwords(str_replace('_', ' ', $lastPart));
    }

    /**
     * Get the group display name.
     */
    public function getGroupDisplayName(): string
    {
        return match ($this->group) {
            'general' => 'General Settings',
            'auth' => 'Authentication',
            'features' => 'Features',
            'mail' => 'Mail Configuration',
            'security' => 'Security',
            default => ucfirst($this->group),
        };
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when a setting is saved or deleted
        static::saved(function (self $setting) {
            Cache::forget(self::CACHE_PREFIX.$setting->key);
        });

        static::deleted(function (self $setting) {
            Cache::forget(self::CACHE_PREFIX.$setting->key);
        });
    }
}
