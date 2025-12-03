<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineRun extends Model
{
    use HasFactory;

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

    protected $casts = [
        'logs' => 'array',
        'artifacts' => 'array',
        'trigger_data' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

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
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }

    /**
     * Get duration attribute (for backwards compatibility)
     */
    public function getDurationAttribute()
    {
        return $this->duration();
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration();

        if (!$seconds) {
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
        return match($this->status) {
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
        return match($this->status) {
            'success' => 'check-circle',
            'failed' => 'x-circle',
            'running' => 'arrow-path',
            'pending' => 'clock',
            'cancelled' => 'ban',
            default => 'question-mark-circle',
        };
    }
}