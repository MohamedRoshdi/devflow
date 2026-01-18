<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAlert extends Model
{
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
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the user who created this alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get alerts for a specific server.
     */
    public function scopeForServer($query, int $serverId)
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
