<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthCheckResult extends Model
{
    /** @use HasFactory<\Database\Factories\HealthCheckResultFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'health_check_id',
        'status',
        'response_time_ms',
        'status_code',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
    ];

    /**
     * @return BelongsTo<HealthCheck, $this>
     */
    public function healthCheck(): BelongsTo
    {
        return $this->belongsTo(HealthCheck::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailure(): bool
    {
        return in_array($this->status, ['failure', 'timeout']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'timeout' => 'yellow',
            'failure' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedResponseTimeAttribute(): string
    {
        if (! $this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms.'ms';
        }

        return round($this->response_time_ms / 1000, 2).'s';
    }
}
