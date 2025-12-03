<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class StorageConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'driver',
        'is_default',
        'credentials',
        'bucket',
        'region',
        'endpoint',
        'path_prefix',
        'encryption_key',
        'status',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'last_tested_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get encrypted credentials as array
     */
    public function getCredentialsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set credentials with encryption
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        if (empty($value)) {
            $this->attributes['credentials'] = '';
        } else {
            $this->attributes['credentials'] = Crypt::encryptString($value);
        }
    }

    /**
     * Project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for default configuration
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific driver
     */
    public function scopeDriver($query, string $driver)
    {
        return $query->where('driver', $driver);
    }

    /**
     * Get Laravel filesystem disk configuration array
     */
    public function getDiskConfig(): array
    {
        $credentials = $this->credentials;

        return match($this->driver) {
            's3' => [
                'driver' => 's3',
                'key' => $credentials['access_key_id'] ?? '',
                'secret' => $credentials['secret_access_key'] ?? '',
                'region' => $this->region ?? 'us-east-1',
                'bucket' => $this->bucket ?? '',
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => !empty($this->endpoint),
                'throw' => true,
            ],
            'gcs' => [
                'driver' => 'gcs',
                'key_file' => $credentials['service_account_json'] ?? [],
                'bucket' => $this->bucket ?? '',
                'path_prefix' => $this->path_prefix,
                'throw' => true,
            ],
            'ftp' => [
                'driver' => 'ftp',
                'host' => $credentials['host'] ?? '',
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
                'port' => (int) ($credentials['port'] ?? 21),
                'root' => $credentials['path'] ?? '/',
                'passive' => (bool) ($credentials['passive'] ?? true),
                'ssl' => (bool) ($credentials['ssl'] ?? false),
                'timeout' => 30,
                'throw' => true,
            ],
            'sftp' => [
                'driver' => 'sftp',
                'host' => $credentials['host'] ?? '',
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? null,
                'privateKey' => $credentials['private_key'] ?? null,
                'passphrase' => $credentials['passphrase'] ?? null,
                'port' => (int) ($credentials['port'] ?? 22),
                'root' => $credentials['path'] ?? '/',
                'timeout' => 30,
                'throw' => true,
            ],
            default => [
                'driver' => 'local',
                'root' => storage_path('app/backups'),
                'throw' => true,
            ],
        };
    }

    /**
     * Get display name for the driver
     */
    public function getDriverNameAttribute(): string
    {
        return match($this->driver) {
            's3' => 'Amazon S3',
            'gcs' => 'Google Cloud Storage',
            'ftp' => 'FTP',
            'sftp' => 'SFTP',
            default => 'Local Storage',
        };
    }

    /**
     * Get icon class for the driver
     */
    public function getDriverIconAttribute(): string
    {
        return match($this->driver) {
            's3' => 'aws',
            'gcs' => 'google-cloud',
            'ftp' => 'ftp',
            'sftp' => 'sftp',
            default => 'local',
        };
    }

    /**
     * Check if configuration is properly configured
     */
    public function isConfigured(): bool
    {
        $credentials = $this->credentials;

        return match($this->driver) {
            's3' => !empty($credentials['access_key_id'])
                && !empty($credentials['secret_access_key'])
                && !empty($this->bucket),
            'gcs' => !empty($credentials['service_account_json'])
                && !empty($this->bucket),
            'ftp', 'sftp' => !empty($credentials['host'])
                && !empty($credentials['username']),
            default => true,
        };
    }
}
