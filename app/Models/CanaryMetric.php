<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanaryMetric extends Model
{
    /** @use HasFactory<\Database\Factories\CanaryMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'canary_release_id',
        'version_type',
        'error_rate',
        'avg_response_time_ms',
        'p95_response_time_ms',
        'p99_response_time_ms',
        'request_count',
        'error_count',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'error_rate' => 'decimal:4',
            'recorded_at' => 'datetime',
            'avg_response_time_ms' => 'integer',
            'p95_response_time_ms' => 'integer',
            'p99_response_time_ms' => 'integer',
            'request_count' => 'integer',
            'error_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CanaryRelease, $this>
     */
    public function canaryRelease(): BelongsTo
    {
        return $this->belongsTo(CanaryRelease::class);
    }
}
