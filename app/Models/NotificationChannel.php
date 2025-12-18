<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $project_id
 * @property string $type
 * @property string $name
 * @property array<string, mixed> $config
 * @property bool $is_active
 * @property string|null $webhook_url
 * @property string|null $webhook_secret
 * @property bool $enabled
 * @property array<int, string> $events
 * @property array<string, mixed> $metadata
 * @property string $type_icon
 * @property string $type_label
 * @property string $config_summary
 */
class NotificationChannel extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationChannelFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'project_id',
        'type',
        'name',
        'config',
        'is_active',
        'webhook_url',
        'webhook_secret',
        'enabled',
        'events',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'enabled' => 'boolean',
        'events' => 'array',
        'metadata' => 'array',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * @param  string|null  $value
     */
    public function setWebhookSecretAttribute($value): void
    {
        $this->attributes['webhook_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * @param  string|null  $value
     */
    public function getWebhookSecretAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * @return BelongsTo<User, NotificationChannel>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, NotificationChannel>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<HealthCheck, $this>
     */
    public function healthChecks(): BelongsToMany
    {
        return $this->belongsToMany(HealthCheck::class, 'health_check_notifications')
            ->withPivot('notify_on_failure', 'notify_on_recovery');
    }

    /**
     * @return HasMany<NotificationLog, $this>
     */
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
        return match ($this->type) {
            'email' => 'envelope',
            'slack' => 'chat-bubble-left-right',
            'discord' => 'chat-bubble-bottom-center-text',
            default => 'bell',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'email' => 'Email',
            'slack' => 'Slack',
            'discord' => 'Discord',
            default => 'Unknown',
        };
    }

    public function getConfigSummaryAttribute(): string
    {
        $type = $this->type;

        return match ($type) {
            'email' => $this->config['email'] ?? 'No email set',
            'slack' => 'Webhook: '.substr($this->config['webhook_url'] ?? $this->webhook_url ?? '', 0, 30).'...',
            'discord' => 'Webhook: '.substr($this->config['webhook_url'] ?? $this->webhook_url ?? '', 0, 30).'...',
            default => 'No configuration',
        };
    }
}
