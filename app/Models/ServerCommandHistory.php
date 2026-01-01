<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCommandHistory extends Model
{
    /** @use HasFactory<\Database\Factories\ServerCommandHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'server_id',
        'user_id',
        'action',
        'command',
        'execution_type',
        'status',
        'output',
        'error_output',
        'exit_code',
        'duration_ms',
        'metadata',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'exit_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if command was executed locally
     */
    public function isLocal(): bool
    {
        return $this->execution_type === 'local';
    }

    /**
     * Check if command was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if command failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get human-readable action name
     */
    public function getActionNameAttribute(): string
    {
        return match ($this->action) {
            'reboot' => 'Server Reboot',
            'restart_service' => 'Service Restart',
            'clear_cache' => 'Clear System Cache',
            'deploy' => 'Deployment',
            'health_check' => 'Health Check',
            'get_metrics' => 'Collect Metrics',
            'get_info' => 'Get Server Info',
            'ping' => 'Connectivity Test',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'running' => 'blue',
            'success' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get execution type badge color
     */
    public function getExecutionTypeColorAttribute(): string
    {
        return $this->execution_type === 'local' ? 'purple' : 'cyan';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms === null) {
            return '-';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms.'ms';
        }

        return round($this->duration_ms / 1000, 2).'s';
    }
}
