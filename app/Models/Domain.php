<?php

declare(strict_types=1);

namespace App\Models;

use App\Mappers\HealthScoreMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, mixed>|null $metadata
 */
class Domain extends Model
{
    /** @use HasFactory<\Database\Factories\DomainFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['full_domain'];

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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
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

        return $this->ssl_expires_at->isBetween(now(), now()->addDays(30));
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
            return HealthScoreMapper::COLOR_RED;
        }

        if ($this->sslExpiresSoon()) {
            return HealthScoreMapper::COLOR_YELLOW;
        }

        return HealthScoreMapper::statusToColor($this->status);
    }

    /**
     * Get the full domain (subdomain + domain).
     */
    public function getFullDomainAttribute(): string
    {
        if ($this->subdomain) {
            return "{$this->subdomain}.{$this->domain}";
        }

        return $this->domain ?? '';
    }
}
