<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $server_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $description
 * @property string $pattern
 * @property string|null $log_type
 * @property string|null $log_level
 * @property bool $is_regex
 * @property bool $case_sensitive
 * @property int $threshold
 * @property int $time_window
 * @property array<int, string>|null $notification_channels
 * @property array<string, mixed>|null $notification_config
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_triggered_at
 * @property int $trigger_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server|null $server
 * @property-read User|null $user
 */
class LogAlert extends Model
{
    /** @use HasFactory<\Database\Factories\LogAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'name',
        'description',
        'pattern',
        'log_type',
        'log_level',
        'is_regex',
        'case_sensitive',
        'threshold',
        'time_window',
        'notification_channels',
        'notification_config',
        'is_active',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'is_active' => 'boolean',
        'notification_channels' => 'array',
        'notification_config' => 'array',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the server for this alert.
     *
     * @return BelongsTo<Server, LogAlert>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the user who created this alert.
     *
     * @return BelongsTo<User, LogAlert>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active alerts.
     *
     * @param \Illuminate\Database\Eloquent\Builder<LogAlert> $query
     * @return \Illuminate\Database\Eloquent\Builder<LogAlert>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get alerts for a specific server.
     *
     * @param \Illuminate\Database\Eloquent\Builder<LogAlert> $query
     * @return \Illuminate\Database\Eloquent\Builder<LogAlert>
     */
    public function scopeForServer(\Illuminate\Database\Eloquent\Builder $query, int $serverId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Check if the pattern matches a log message.
     */
    public function matches(string $message): bool
    {
        if ($this->is_regex) {
            try {
                return (bool) preg_match($this->pattern, $message);
            } catch (\Exception $e) {
                return false;
            }
        }

        if ($this->case_sensitive) {
            return str_contains($message, $this->pattern);
        }

        return str_contains(strtolower($message), strtolower($this->pattern));
    }

    /**
     * Check if alert should trigger based on threshold and time window.
     */
    public function shouldTrigger(int $matchCount): bool
    {
        return $matchCount >= $this->threshold;
    }

    /**
     * Record alert trigger.
     */
    public function recordTrigger(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Get notification channels as array.
     *
     * @return array<string>
     */
    public function getNotificationChannels(): array
    {
        return $this->notification_channels ?? [];
    }

    /**
     * Check if alert uses email notifications.
     */
    public function hasEmailNotification(): bool
    {
        return in_array('email', $this->getNotificationChannels(), true);
    }

    /**
     * Check if alert uses Slack notifications.
     */
    public function hasSlackNotification(): bool
    {
        return in_array('slack', $this->getNotificationChannels(), true);
    }

    /**
     * Check if alert uses webhook notifications.
     */
    public function hasWebhookNotification(): bool
    {
        return in_array('webhook', $this->getNotificationChannels(), true);
    }
}
