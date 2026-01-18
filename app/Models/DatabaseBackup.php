<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $server_id
 * @property string $database_type
 * @property string $database_name
 * @property string $type
 * @property string $file_name
 * @property string $file_path
 * @property int|null $file_size
 * @property string|null $checksum
 * @property string $storage_disk
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property string|null $error_message
 * @property array<string, mixed>|null $metadata
 * @property-read Project $project
 * @property-read Server|null $server
 * @property-read string $file_size_human
 * @property-read string|null $duration
 * @property-read string $status_color
 * @property-read string $status_icon
 */
class DatabaseBackup extends Model
{
    /** @use HasFactory<\Database\Factories\DatabaseBackupFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'project_id',
        'server_id',
        'database_type',
        'database_name',
        'type',
        'file_name',
        'file_path',
        'file_size',
        'checksum',
        'storage_disk',
        'status',
        'started_at',
        'completed_at',
        'verified_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        $seconds = $this->completed_at->diffInSeconds($this->started_at);

        if ($seconds < 60) {
            return $seconds.'s';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1).'m';
        } else {
            return round($seconds / 3600, 1).'h';
        }
    }

    /**
     * @param  Builder<DatabaseBackup>  $query
     * @return Builder<DatabaseBackup>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * @param  Builder<DatabaseBackup>  $query
     * @return Builder<DatabaseBackup>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * @param  Builder<DatabaseBackup>  $query
     * @return Builder<DatabaseBackup>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * @param  Builder<DatabaseBackup>  $query
     * @return Builder<DatabaseBackup>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * @param  Builder<DatabaseBackup>  $query
     * @return Builder<DatabaseBackup>
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'running' => 'blue',
            'failed' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'fa-check-circle',
            'running' => 'fa-spinner fa-spin',
            'failed' => 'fa-exclamation-circle',
            'pending' => 'fa-clock',
            default => 'fa-question-circle',
        };
    }
}
