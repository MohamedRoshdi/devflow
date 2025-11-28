<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCheck extends Model
{
    use HasFactory;

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class);
    }

    public function notificationChannels(): BelongsToMany
    {
        return $this->belongsToMany(NotificationChannel::class, 'health_check_notifications')
            ->withPivot('notify_on_failure', 'notify_on_recovery');
    }

    public function recentResults(): HasMany
    {
        return $this->hasMany(HealthCheckResult::class)
            ->orderBy('checked_at', 'desc')
            ->limit(10);
    }

    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_check_at) {
            return true;
        }

        return $this->last_check_at->addMinutes($this->interval_minutes)->isPast();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->project) {
            return $this->project->name . ' - ' . ucfirst($this->check_type);
        }

        if ($this->server) {
            return $this->server->name . ' - ' . ucfirst($this->check_type);
        }

        return $this->target_url ?? 'Health Check #' . $this->id;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'down' => 'red',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'healthy' => 'check-circle',
            'degraded' => 'exclamation-triangle',
            'down' => 'x-circle',
            default => 'question-mark-circle',
        };
    }
}
