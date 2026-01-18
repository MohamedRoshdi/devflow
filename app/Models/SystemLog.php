<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'log_type',
        'level',
        'source',
        'message',
        'metadata',
        'ip_address',
        'logged_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'logged_at' => 'datetime',
    ];

    // Log type constants
    public const TYPE_SYSTEM = 'system';
    public const TYPE_AUTH = 'auth';
    public const TYPE_KERNEL = 'kern';
    public const TYPE_DOCKER = 'docker';
    public const TYPE_NGINX = 'nginx';
    public const TYPE_PHP = 'php';
    public const TYPE_MYSQL = 'mysql';
    public const TYPE_POSTGRES = 'postgres';
    public const TYPE_REDIS = 'redis';
    public const TYPE_APPLICATION = 'application';
    public const TYPE_SECURITY = 'security';
    public const TYPE_CRON = 'cron';

    // Log level constants (following RFC 5424)
    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_INFO = 'info';
    public const LEVEL_DEBUG = 'debug';

    /**
     * Get the server that owns the log.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the user associated with the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by log type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('log_type', $type);
    }

    /**
     * Scope to filter by log level.
     */
    public function scopeOfLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter by server.
     */
    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to get critical logs (emergency, alert, critical).
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('level', [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
        ]);
    }

    /**
     * Scope to get error logs (critical, error).
     */
    public function scopeErrors($query)
    {
        return $query->whereIn('level', [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR,
        ]);
    }

    /**
     * Scope to search logs by message content.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
              ->orWhere('source', 'like', "%{$search}%");
        });
    }

    /**
     * Get all available log types.
     *
     * @return array<string>
     */
    public static function getLogTypes(): array
    {
        return [
            self::TYPE_SYSTEM,
            self::TYPE_AUTH,
            self::TYPE_KERNEL,
            self::TYPE_DOCKER,
            self::TYPE_NGINX,
            self::TYPE_PHP,
            self::TYPE_MYSQL,
            self::TYPE_POSTGRES,
            self::TYPE_REDIS,
            self::TYPE_APPLICATION,
            self::TYPE_SECURITY,
            self::TYPE_CRON,
        ];
    }

    /**
     * Get all available log levels.
     *
     * @return array<string>
     */
    public static function getLogLevels(): array
    {
        return [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR,
            self::LEVEL_WARNING,
            self::LEVEL_NOTICE,
            self::LEVEL_INFO,
            self::LEVEL_DEBUG,
        ];
    }

    /**
     * Get badge color for log level.
     */
    public function getLevelColorAttribute(): string
    {
        return match ($this->level) {
            self::LEVEL_EMERGENCY, self::LEVEL_ALERT, self::LEVEL_CRITICAL => 'red',
            self::LEVEL_ERROR => 'orange',
            self::LEVEL_WARNING => 'yellow',
            self::LEVEL_NOTICE => 'blue',
            self::LEVEL_INFO => 'gray',
            self::LEVEL_DEBUG => 'purple',
            default => 'gray',
        };
    }

    /**
     * Check if log is critical.
     */
    public function isCritical(): bool
    {
        return in_array($this->level, [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
        ], true);
    }

    /**
     * Check if log is an error.
     */
    public function isError(): bool
    {
        return in_array($this->level, [
            self::LEVEL_EMERGENCY,
            self::LEVEL_ALERT,
            self::LEVEL_CRITICAL,
            self::LEVEL_ERROR,
        ], true);
    }
}
