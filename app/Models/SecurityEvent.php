<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory;

    public const TYPE_FIREWALL_ENABLED = 'firewall_enabled';
    public const TYPE_FIREWALL_DISABLED = 'firewall_disabled';
    public const TYPE_RULE_ADDED = 'rule_added';
    public const TYPE_RULE_DELETED = 'rule_deleted';
    public const TYPE_IP_BANNED = 'ip_banned';
    public const TYPE_IP_UNBANNED = 'ip_unbanned';
    public const TYPE_SSH_CONFIG_CHANGED = 'ssh_config_changed';
    public const TYPE_SECURITY_SCAN = 'security_scan';

    protected $fillable = [
        'server_id',
        'event_type',
        'source_ip',
        'details',
        'metadata',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventTypeLabel(): string
    {
        return match ($this->event_type) {
            self::TYPE_FIREWALL_ENABLED => 'Firewall Enabled',
            self::TYPE_FIREWALL_DISABLED => 'Firewall Disabled',
            self::TYPE_RULE_ADDED => 'Rule Added',
            self::TYPE_RULE_DELETED => 'Rule Deleted',
            self::TYPE_IP_BANNED => 'IP Banned',
            self::TYPE_IP_UNBANNED => 'IP Unbanned',
            self::TYPE_SSH_CONFIG_CHANGED => 'SSH Config Changed',
            self::TYPE_SECURITY_SCAN => 'Security Scan',
            default => ucfirst(str_replace('_', ' ', $this->event_type)),
        };
    }

    public function getEventTypeColorAttribute(): string
    {
        return match ($this->event_type) {
            self::TYPE_FIREWALL_ENABLED, self::TYPE_RULE_ADDED => 'green',
            self::TYPE_FIREWALL_DISABLED, self::TYPE_RULE_DELETED => 'red',
            self::TYPE_IP_BANNED => 'orange',
            self::TYPE_IP_UNBANNED => 'yellow',
            self::TYPE_SSH_CONFIG_CHANGED => 'blue',
            self::TYPE_SECURITY_SCAN => 'purple',
            default => 'gray',
        };
    }
}
