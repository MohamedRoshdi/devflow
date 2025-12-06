<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogEntry extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'project_id',
        'source',
        'level',
        'message',
        'context',
        'file_path',
        'line_number',
        'logged_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'line_number' => 'integer',
        'logged_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Server, LogEntry>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Project, LogEntry>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeByLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeByServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeByProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
                ->orWhere('file_path', 'like', "%{$search}%");
        });
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @param  string|\DateTimeInterface|null  $from
     * @param  string|\DateTimeInterface|null  $to
     * @return Builder<LogEntry>
     */
    public function scopeDateRange(Builder $query, $from = null, $to = null): Builder
    {
        if ($from) {
            $query->where('logged_at', '>=', $from);
        }
        if ($to) {
            $query->where('logged_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  Builder<LogEntry>  $query
     * @return Builder<LogEntry>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('logged_at', 'desc');
    }

    public function getLevelColorAttribute(): string
    {
        return match ($this->level) {
            'debug' => 'gray',
            'info', 'notice' => 'blue',
            'warning' => 'yellow',
            'error' => 'red',
            'critical', 'alert', 'emergency' => 'purple',
            default => 'gray',
        };
    }

    public function getSourceBadgeColorAttribute(): string
    {
        return match ($this->source) {
            'nginx' => 'green',
            'php' => 'indigo',
            'laravel' => 'red',
            'mysql' => 'blue',
            'system' => 'gray',
            'docker' => 'cyan',
            default => 'slate',
        };
    }

    public function getTruncatedMessageAttribute(): string
    {
        return strlen($this->message) > 150
            ? substr($this->message, 0, 150).'...'
            : $this->message;
    }

    public function getLocationAttribute(): ?string
    {
        if ($this->file_path && $this->line_number) {
            return "{$this->file_path}:{$this->line_number}";
        }

        return $this->file_path;
    }
}
