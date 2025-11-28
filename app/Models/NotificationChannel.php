<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'config',
        'is_active',
        'provider',
        'webhook_url',
        'webhook_secret',
        'enabled',
        'events',
        'metadata',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'enabled' => 'boolean',
        'events' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    public function setWebhookSecretAttribute($value)
    {
        $this->attributes['webhook_secret'] = $value ? encrypt($value) : null;
    }

    public function getWebhookSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function healthChecks(): BelongsToMany
    {
        return $this->belongsToMany(HealthCheck::class, 'health_check_notifications')
            ->withPivot('notify_on_failure', 'notify_on_recovery');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function shouldNotifyForEvent(string $event): bool
    {
        return $this->enabled && in_array($event, $this->events ?? []);
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type ?? $this->provider) {
            'email' => 'envelope',
            'slack' => 'chat-bubble-left-right',
            'discord' => 'chat-bubble-bottom-center-text',
            default => 'bell',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type ?? $this->provider) {
            'email' => 'Email',
            'slack' => 'Slack',
            'discord' => 'Discord',
            default => 'Unknown',
        };
    }

    public function getConfigSummaryAttribute(): string
    {
        $type = $this->type ?? $this->provider;
        return match ($type) {
            'email' => $this->config['email'] ?? 'No email set',
            'slack' => 'Webhook: ' . substr($this->config['webhook_url'] ?? $this->webhook_url ?? '', 0, 30) . '...',
            'discord' => 'Webhook: ' . substr($this->config['webhook_url'] ?? $this->webhook_url ?? '', 0, 30) . '...',
            default => 'No configuration',
        };
    }
}