<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<\Database\Factories\FileBackupFactory>
 *
 * @property int $id
 * @property int $project_id
 * @property string $filename
 * @property string $type
 * @property string $source_path
 * @property string $storage_disk
 * @property string $storage_path
 * @property int $size_bytes
 * @property int $files_count
 * @property string $checksum
 * @property string $status
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property array<string, mixed>|null $manifest
 * @property array<int, string>|null $exclude_patterns
 * @property int|null $parent_backup_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read FileBackup|null $parentBackup
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FileBackup> $childBackups
 * @property-read string $formatted_size
 * @property-read int|null $duration
 * @property-read string|null $formatted_duration
 * @property-read string $status_color
 * @property-read string $type_color
 */
class FileBackup extends Model
{
    /** @use HasFactory<\Database\Factories\FileBackupFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'filename',
        'type',
        'source_path',
        'storage_disk',
        'storage_path',
        'size_bytes',
        'files_count',
        'checksum',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'manifest',
        'exclude_patterns',
        'parent_backup_id',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'files_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'manifest' => 'array',
            'exclude_patterns' => 'array',
        ];
    }

    // Relationships
    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<FileBackup, $this>
     */
    public function parentBackup(): BelongsTo
    {
        return $this->belongsTo(FileBackup::class, 'parent_backup_id');
    }

    /**
     * @return HasMany<FileBackup, $this>
     */
    public function childBackups(): HasMany
    {
        return $this->hasMany(FileBackup::class, 'parent_backup_id');
    }

    // Scopes
    /**
     * @param  Builder<FileBackup>  $query
     * @return Builder<FileBackup>
     */
    public function scopeFull(Builder $query): Builder
    {
        return $query->where('type', 'full');
    }

    /**
     * @param  Builder<FileBackup>  $query
     * @return Builder<FileBackup>
     */
    public function scopeIncremental(Builder $query): Builder
    {
        return $query->where('type', 'incremental');
    }

    /**
     * @param  Builder<FileBackup>  $query
     * @return Builder<FileBackup>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * @param  Builder<FileBackup>  $query
     * @return Builder<FileBackup>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * @param  Builder<FileBackup>  $query
     * @return Builder<FileBackup>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    // Helpers
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isFull(): bool
    {
        return $this->type === 'full';
    }

    public function isIncremental(): bool
    {
        return $this->type === 'incremental';
    }

    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->size_bytes);
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    public function getFormattedDurationAttribute(): ?string
    {
        $duration = $this->duration;

        if ($duration === null) {
            return null;
        }

        if ($duration < 60) {
            return "{$duration}s";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$seconds}s";
        }

        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        return "{$hours}h {$minutes}m";
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'running' => 'blue',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'full' => 'purple',
            'incremental' => 'blue',
            default => 'gray',
        };
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Get all backups in the chain (for incremental backups)
     *
     * @return array<int, FileBackup>
     */
    public function getBackupChain(): array
    {
        $chain = [$this];

        // If this is an incremental backup, traverse up to the full backup
        $current = $this;
        while ($current->parent_backup_id && $current->parentBackup !== null) {
            $current = $current->parentBackup;
            array_unshift($chain, $current);
        }

        return $chain;
    }

    /**
     * Get the root full backup
     */
    public function getRootBackup(): FileBackup
    {
        $current = $this;
        while ($current->parent_backup_id && $current->parentBackup !== null) {
            $current = $current->parentBackup;
        }

        return $current;
    }

    /**
     * Get incremental depth (how many incremental backups from the full backup)
     */
    public function getIncrementalDepth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent_backup_id && $current->parentBackup !== null) {
            $depth++;
            $current = $current->parentBackup;
        }

        return $depth;
    }
}
