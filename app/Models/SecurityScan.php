<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property string $status
 * @property int|null $score
 * @property string|null $risk_level
 * @property array<int, array<string, mixed>> $findings
 * @property array<int, string> $recommendations
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $triggered_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 * @property-read User|null $triggeredBy
 * @property-read int|null $duration
 * @property-read string $risk_level_color
 * @property-read string $score_color
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @method static \Illuminate\Database\Eloquent\Builder<static> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> newModelQuery()
 */
class SecurityScan extends Model
{
    /** @use HasFactory<\Database\Factories\SecurityScanFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const RISK_CRITICAL = 'critical';

    public const RISK_HIGH = 'high';

    public const RISK_MEDIUM = 'medium';

    public const RISK_LOW = 'low';

    public const RISK_SECURE = 'secure';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'status',
        'score',
        'risk_level',
        'findings',
        'recommendations',
        'started_at',
        'completed_at',
        'triggered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'findings' => 'array',
            'recommendations' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }

        return null;
    }

    public function getRiskLevelColorAttribute(): string
    {
        return match ($this->risk_level) {
            self::RISK_CRITICAL => 'red',
            self::RISK_HIGH => 'orange',
            self::RISK_MEDIUM => 'yellow',
            self::RISK_LOW => 'green',
            self::RISK_SECURE => 'emerald',
            default => 'gray',
        };
    }

    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->score >= 91 => 'emerald',
            $this->score >= 81 => 'green',
            $this->score >= 61 => 'yellow',
            $this->score >= 41 => 'orange',
            default => 'red',
        };
    }

    public static function getRiskLevelFromScore(int $score): string
    {
        return match (true) {
            $score >= 91 => self::RISK_SECURE,
            $score >= 81 => self::RISK_LOW,
            $score >= 61 => self::RISK_MEDIUM,
            $score >= 41 => self::RISK_HIGH,
            default => self::RISK_CRITICAL,
        };
    }
}
