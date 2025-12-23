<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $pipeline_id
 * @property int $project_id
 * @property int|null $deployment_id
 * @property int $run_number
 * @property string $status
 * @property string $triggered_by
 * @property array<string, mixed>|null $trigger_data
 * @property string|null $branch
 * @property string|null $commit_sha
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property array<string, mixed>|null $logs
 * @property array<string, mixed>|null $artifacts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Pipeline $pipeline
 * @property-read Project $project
 * @property-read Deployment|null $deployment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PipelineStageRun> $stageRuns
 * @property-read int|null $stageRuns_count
 * @property-read int|null $duration
 * @property-read string $formatted_duration
 * @property-read string $status_color
 * @property-read string $status_icon
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PipelineRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PipelineRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PipelineRun query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<PipelineRun>
 */
class PipelineRun extends Model
{
    /** @use HasFactory<\Database\Factories\PipelineRunFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'pipeline_id',
        'project_id',
        'deployment_id',
        'run_number',
        'status',
        'triggered_by',
        'trigger_data',
        'branch',
        'commit_sha',
        'started_at',
        'finished_at',
        'logs',
        'artifacts',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'logs' => 'array',
        'artifacts' => 'array',
        'trigger_data' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Pipeline, PipelineRun>
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * @return BelongsTo<Project, PipelineRun>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Deployment, PipelineRun>
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * @return HasMany<PipelineStageRun, PipelineRun>
     */
    public function stageRuns(): HasMany
    {
        return $this->hasMany(PipelineStageRun::class)->orderBy('created_at', 'asc');
    }

    /**
     * Mark pipeline run as running
     */
    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark pipeline run as success
     */
    public function markSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark pipeline run as failed
     */
    public function markFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark pipeline run as cancelled
     */
    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'finished_at' => now(),
        ]);
    }

    /**
     * Get duration in seconds
     */
    public function duration(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }

    /**
     * Get duration attribute (for backwards compatibility)
     */
    public function getDurationAttribute(): ?int
    {
        return $this->duration();
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration();

        if (! $seconds) {
            return '-';
        }

        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    /**
     * Check if pipeline run is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['success', 'failed', 'cancelled']);
    }

    /**
     * Check if pipeline run is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if pipeline run is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'running' => 'yellow',
            'pending' => 'blue',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'success' => 'check-circle',
            'failed' => 'x-circle',
            'running' => 'arrow-path',
            'pending' => 'clock',
            'cancelled' => 'ban',
            default => 'question-mark-circle',
        };
    }
}
