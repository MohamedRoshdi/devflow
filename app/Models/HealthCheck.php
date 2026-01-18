<?php

declare(strict_types=1);

namespace App\Models;

use App\Mappers\HealthScoreMapper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $server_id
 * @property string $check_type
 * @property string|null $target_url
 * @property int $expected_status
 * @property int $interval_minutes
 * @property int $timeout_seconds
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_check_at
 * @property \Illuminate\Support\Carbon|null $last_success_at
 * @property \Illuminate\Support\Carbon|null $last_failure_at
 * @property int $consecutive_failures
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $display_name
 * @property-read string $status_color
 * @property-read string $status_icon
 * @property-read Project|null $project
 * @property-read Server|null $server
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HealthCheckResult> $results
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationChannel> $notificationChannels
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HealthCheckResult> $recentResults
 */
class HealthCheck extends Model
{
    /** @use HasFactory<\Database\Factories\HealthCheckFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'server_id',
        'check_type',
        'target_url',
        'expected_status',
        'interval_minutes',
        'timeout_seconds',
        'is_active',
        'last_check_at',
        'last_success_at',
        'last_failure_at',
        'consecutive_failures',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_check_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'consecutive_failures' => 'integer',
        'expected_status' => 'integer',
        'interval_minutes' => 'integer',
        'timeout_seconds' => 'integer',
    ];

    /**
     * @return BelongsTo<Project, HealthCheck>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Server, HealthCheck>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<HealthCheckResult, HealthCheck>
     */
    public function results(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class);
    }

    /**
     * @return BelongsToMany<NotificationChannel, HealthCheck>
     */
    public function notificationChannels(): BelongsToMany
    {
        return $this->belongsToMany(NotificationChannel::class, 'health_check_notifications')
            ->withPivot('notify_on_failure', 'notify_on_recovery');
    }

    /**
     * @return HasMany<HealthCheckResult, HealthCheck>
     */
    public function recentResults(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class)
            ->orderBy('checked_at', 'desc')
            ->limit(10);
    }

    public function isDue(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->last_check_at) {
            return true;
        }

        return $this->last_check_at->addMinutes($this->interval_minutes)->isPast();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->project) {
            return $this->project->name.' - '.ucfirst($this->check_type);
        }

        if ($this->server) {
            return $this->server->name.' - '.ucfirst($this->check_type);
        }

        return $this->target_url ?? 'Health Check #'.$this->id;
    }

    public function getStatusColorAttribute(): string
    {
        return HealthScoreMapper::statusToColor($this->status);
    }

    public function getStatusIconAttribute(): string
    {
        return HealthScoreMapper::statusToIcon($this->status);
    }
}
