<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStageRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_run_id',
        'pipeline_stage_id',
        'status',
        'output',
        'error_message',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Relationship: Stage run belongs to a pipeline run
     */
    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class);
    }

    /**
     * Relationship: Stage run belongs to a pipeline stage
     */
    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    /**
     * Mark stage as running
     */
    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark stage as success
     */
    public function markSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);
    }

    /**
     * Mark stage as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);
    }

    /**
     * Mark stage as skipped
     */
    public function markSkipped(): void
    {
        $this->update([
            'status' => 'skipped',
            'completed_at' => now(),
        ]);
    }

    /**
     * Append output line
     */
    public function appendOutput(string $line): void
    {
        $currentOutput = $this->output ?? '';
        $this->update([
            'output' => $currentOutput . $line . "\n",
        ]);
    }

    /**
     * Check if stage is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if stage is success
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if stage is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if stage is skipped
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
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
            'skipped' => 'gray',
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
            'skipped' => 'minus-circle',
            default => 'question-mark-circle',
        };
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }
}
