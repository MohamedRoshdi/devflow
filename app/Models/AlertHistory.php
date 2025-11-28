<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_alert_id',
        'server_id',
        'resource_type',
        'current_value',
        'threshold_value',
        'status',
        'message',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }

    // Relationships
    public function resourceAlert()
    {
        return $this->belongsTo(ResourceAlert::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    // Helpers
    public function isTriggered(): bool
    {
        return $this->status === 'triggered';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'triggered' => 'red',
            'resolved' => 'green',
            default => 'gray',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'triggered' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Triggered</span>',
            'resolved' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Resolved</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">Unknown</span>',
        };
    }

    public function getResourceTypeIconAttribute(): string
    {
        return match($this->resource_type) {
            'cpu' => 'heroicon-o-cpu-chip',
            'memory' => 'heroicon-o-circle-stack',
            'disk' => 'heroicon-o-server-stack',
            'load' => 'heroicon-o-chart-bar',
            default => 'heroicon-o-bell-alert',
        };
    }

    public function scopeTriggered($query)
    {
        return $query->where('status', 'triggered');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
