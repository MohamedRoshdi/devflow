<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CanaryReleaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanaryRelease extends Model
{
    /** @use HasFactory<\Database\Factories\CanaryReleaseFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deployment_id',
        'stable_version',
        'canary_version',
        'status',
        'current_weight',
        'weight_schedule',
        'current_step',
        'error_rate_threshold',
        'response_time_threshold',
        'auto_promote',
        'auto_rollback',
        'started_at',
        'promoted_at',
        'rolled_back_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => CanaryReleaseStatus::class,
            'weight_schedule' => 'array',
            'metadata' => 'array',
            'auto_promote' => 'boolean',
            'auto_rollback' => 'boolean',
            'started_at' => 'datetime',
            'promoted_at' => 'datetime',
            'rolled_back_at' => 'datetime',
            'completed_at' => 'datetime',
            'current_weight' => 'integer',
            'current_step' => 'integer',
            'error_rate_threshold' => 'decimal:2',
            'response_time_threshold' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Deployment, $this>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return HasMany<CanaryMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(CanaryMetric::class);
    }

    public function isMonitoring(): bool
    {
        return $this->status === CanaryReleaseStatus::Monitoring->value
            || $this->status === CanaryReleaseStatus::Monitoring;
    }

    public function isCompleted(): bool
    {
        return $this->status === CanaryReleaseStatus::Completed->value
            || $this->status === CanaryReleaseStatus::Completed;
    }

    public function canAdvance(): bool
    {
        if (! $this->isMonitoring()) {
            return false;
        }

        /** @var array<mixed>|null $schedule */
        $schedule = $this->weight_schedule;

        if ($schedule === null || $schedule === []) {
            return false;
        }

        return $this->current_step < count($schedule);
    }
}
