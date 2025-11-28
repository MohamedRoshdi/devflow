<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'resource_type',
        'threshold_type',
        'threshold_value',
        'notification_channels',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'notification_channels' => 'array',
            'is_active' => 'boolean',
            'threshold_value' => 'decimal:2',
            'cooldown_minutes' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }

    // Relationships
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function history()
    {
        return $this->hasMany(AlertHistory::class)->latest('created_at');
    }

    public function latestHistory()
    {
        return $this->hasOne(AlertHistory::class)->latestOfMany();
    }

    // Helpers
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

    public function getResourceTypeLabelAttribute(): string
    {
        return match($this->resource_type) {
            'cpu' => 'CPU Usage',
            'memory' => 'Memory Usage',
            'disk' => 'Disk Usage',
            'load' => 'Load Average',
            default => $this->resource_type,
        };
    }

    public function getThresholdDisplayAttribute(): string
    {
        $operator = $this->threshold_type === 'above' ? '>' : '<';
        $unit = in_array($this->resource_type, ['cpu', 'memory', 'disk']) ? '%' : '';

        return "{$operator} {$this->threshold_value}{$unit}";
    }

    public function isInCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isFuture();
    }

    public function getCooldownRemainingMinutesAttribute(): ?int
    {
        if (!$this->isInCooldown()) {
            return null;
        }

        return now()->diffInMinutes($this->last_triggered_at->addMinutes($this->cooldown_minutes));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    public function scopeForResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }
}
