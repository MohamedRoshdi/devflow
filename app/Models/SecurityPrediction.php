<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property string $prediction_type
 * @property string $severity
 * @property string $status
 * @property string $title
 * @property string|null $description
 * @property array<string, mixed>|null $evidence
 * @property array<string, mixed>|null $recommended_actions
 * @property float $confidence_score
 * @property \Illuminate\Support\Carbon|null $predicted_impact_at
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $acknowledged_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 * @property-read User|null $acknowledgedByUser
 * @property-read string $severity_color
 * @property-read string $status_color
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forServer(int $serverId)
 */
class SecurityPrediction extends Model
{
    /** @use HasFactory<\Database\Factories\SecurityPredictionFactory> */
    use HasFactory;

    public const TYPE_CPU_ANOMALY = 'cpu_anomaly';

    public const TYPE_MEMORY_EXHAUSTION = 'memory_exhaustion';

    public const TYPE_BRUTE_FORCE_ESCALATION = 'brute_force_escalation';

    public const TYPE_NEW_SERVICE_DETECTED = 'new_service_detected';

    public const TYPE_NEW_USER_DETECTED = 'new_user_detected';

    public const TYPE_NEW_CRONTAB_DETECTED = 'new_crontab_detected';

    public const TYPE_PORT_ANOMALY = 'port_anomaly';

    public const TYPE_DISK_EXHAUSTION = 'disk_exhaustion';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_EXPIRED = 'expired';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'prediction_type',
        'severity',
        'status',
        'title',
        'description',
        'evidence',
        'recommended_actions',
        'confidence_score',
        'predicted_impact_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'recommended_actions' => 'array',
            'confidence_score' => 'float',
            'predicted_impact_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
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
    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'red',
            self::STATUS_ACKNOWLEDGED => 'yellow',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    public function acknowledge(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return array<string, string>
     */
    public static function getPredictionTypes(): array
    {
        return [
            self::TYPE_CPU_ANOMALY => 'CPU Anomaly (Possible Miner)',
            self::TYPE_MEMORY_EXHAUSTION => 'Memory Exhaustion Predicted',
            self::TYPE_BRUTE_FORCE_ESCALATION => 'Brute Force Escalation',
            self::TYPE_NEW_SERVICE_DETECTED => 'New Service Detected',
            self::TYPE_NEW_USER_DETECTED => 'New User Detected',
            self::TYPE_NEW_CRONTAB_DETECTED => 'New Crontab Entry Detected',
            self::TYPE_PORT_ANOMALY => 'Port Anomaly',
            self::TYPE_DISK_EXHAUSTION => 'Disk Exhaustion Predicted',
        ];
    }
}
