<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int|null $user_id
 * @property string $incident_type
 * @property string $severity
 * @property string $status
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $findings
 * @property array<string, mixed>|null $affected_items
 * @property array<int, array<string, mixed>>|null $remediation_actions
 * @property \Illuminate\Support\Carbon $detected_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property bool $auto_remediated
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 * @property-read User|null $user
 * @property-read string $severity_color
 * @property-read string $status_color
 * @property-read string $severity_icon
 * @property-read int|null $resolution_time
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> resolved()
 * @method static \Illuminate\Database\Eloquent\Builder<static> critical()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forServer(int $serverId)
 */
class SecurityIncident extends Model
{
    /** @use HasFactory<\Database\Factories\SecurityIncidentFactory> */
    use HasFactory;

    // Incident Types
    public const TYPE_MALWARE = 'malware';

    public const TYPE_BACKDOOR_USER = 'backdoor_user';

    public const TYPE_SUSPICIOUS_PROCESS = 'suspicious_process';

    public const TYPE_OUTBOUND_ATTACK = 'outbound_attack';

    public const TYPE_UNAUTHORIZED_SSH_KEY = 'unauthorized_ssh_key';

    public const TYPE_MALICIOUS_CRON = 'malicious_cron';

    public const TYPE_ROOTKIT = 'rootkit';

    public const TYPE_BRUTE_FORCE = 'brute_force';

    public const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';

    public const TYPE_FILE_INTEGRITY = 'file_integrity';

    public const TYPE_HIDDEN_DIRECTORY = 'hidden_directory';

    // Severity Levels
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_LOW = 'low';

    // Status Values
    public const STATUS_DETECTED = 'detected';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_MITIGATING = 'mitigating';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_FALSE_POSITIVE = 'false_positive';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'user_id',
        'incident_type',
        'severity',
        'status',
        'title',
        'description',
        'findings',
        'affected_items',
        'remediation_actions',
        'detected_at',
        'resolved_at',
        'auto_remediated',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'affected_items' => 'array',
            'remediation_actions' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
            'auto_remediated' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Server, self>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_FALSE_POSITIVE]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'red',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_LOW => 'blue',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DETECTED => 'red',
            self::STATUS_INVESTIGATING => 'yellow',
            self::STATUS_MITIGATING => 'orange',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_FALSE_POSITIVE => 'gray',
            default => 'gray',
        };
    }

    public function getSeverityIconAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'exclamation-triangle',
            self::SEVERITY_HIGH => 'exclamation-circle',
            self::SEVERITY_MEDIUM => 'information-circle',
            self::SEVERITY_LOW => 'shield-check',
            default => 'question-mark-circle',
        };
    }

    public function getResolutionTimeAttribute(): ?int
    {
        if ($this->detected_at && $this->resolved_at) {
            return (int) $this->detected_at->diffInMinutes($this->resolved_at);
        }

        return null;
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_FALSE_POSITIVE], true);
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function addRemediationAction(string $action, bool $success, ?string $details = null): void
    {
        $actions = $this->remediation_actions ?? [];
        $actions[] = [
            'action' => $action,
            'success' => $success,
            'details' => $details,
            'performed_at' => now()->toIso8601String(),
        ];
        $this->update(['remediation_actions' => $actions]);
    }

    public function resolve(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'user_id' => $userId ?? $this->user_id,
        ]);

        // Decrement active incidents count on server
        $this->server->decrement('active_incidents_count');
    }

    public function markAsFalsePositive(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_FALSE_POSITIVE,
            'resolved_at' => now(),
            'user_id' => $userId ?? $this->user_id,
        ]);

        // Decrement active incidents count on server
        $this->server->decrement('active_incidents_count');
    }

    /**
     * @return array<string, string>
     */
    public static function getIncidentTypes(): array
    {
        return [
            self::TYPE_MALWARE => 'Malware Detected',
            self::TYPE_BACKDOOR_USER => 'Backdoor User Account',
            self::TYPE_SUSPICIOUS_PROCESS => 'Suspicious Process',
            self::TYPE_OUTBOUND_ATTACK => 'Outbound Attack',
            self::TYPE_UNAUTHORIZED_SSH_KEY => 'Unauthorized SSH Key',
            self::TYPE_MALICIOUS_CRON => 'Malicious Cron Job',
            self::TYPE_ROOTKIT => 'Rootkit Detected',
            self::TYPE_BRUTE_FORCE => 'Brute Force Attack',
            self::TYPE_UNAUTHORIZED_ACCESS => 'Unauthorized Access',
            self::TYPE_FILE_INTEGRITY => 'File Integrity Violation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getSeverityLevels(): array
    {
        return [
            self::SEVERITY_CRITICAL => 'Critical',
            self::SEVERITY_HIGH => 'High',
            self::SEVERITY_MEDIUM => 'Medium',
            self::SEVERITY_LOW => 'Low',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DETECTED => 'Detected',
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_MITIGATING => 'Mitigating',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_FALSE_POSITIVE => 'False Positive',
        ];
    }
}
