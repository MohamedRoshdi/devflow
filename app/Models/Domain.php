<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    /** @use HasFactory<\Database\Factories\DomainFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'domain',
        'is_primary',
        'ssl_enabled',
        'ssl_provider',
        'ssl_certificate',
        'ssl_private_key',
        'ssl_issued_at',
        'ssl_expires_at',
        'auto_renew_ssl',
        'dns_configured',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'ssl_enabled' => 'boolean',
            'auto_renew_ssl' => 'boolean',
            'dns_configured' => 'boolean',
            'ssl_issued_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Project, $this>
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // SSL Helpers
    public function hasSsl(): bool
    {
        return $this->ssl_enabled;
    }

    public function sslExpiresSoon(): bool
    {
        if (! $this->ssl_expires_at) {
            return false;
        }

        return $this->ssl_expires_at->diffInDays(now()) <= 30;
    }

    public function sslIsExpired(): bool
    {
        if (! $this->ssl_expires_at) {
            return false;
        }

        return $this->ssl_expires_at->isPast();
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->ssl_expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->ssl_expires_at, false);
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->sslIsExpired()) {
            return 'red';
        }

        if ($this->sslExpiresSoon()) {
            return 'yellow';
        }

        return match ($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
