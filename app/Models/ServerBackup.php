<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'type',
        'status',
        'size_bytes',
        'storage_path',
        'storage_driver',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size_bytes) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size_bytes;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;

        if (!$duration) {
            return 'N/A';
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }
        if ($seconds > 0 || empty($parts)) {
            $parts[] = "{$seconds}s";
        }

        return implode(' ', $parts);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'running' => 'blue',
            'failed' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }
}
