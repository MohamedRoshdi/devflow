<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $resource_alert_id
 * @property int $server_id
 * @property string $resource_type
 * @property float $current_value
 * @property float $threshold_value
 * @property string $status
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ResourceAlert $resourceAlert
 * @property-read Server $server
 * @property-read string $status_color
 * @property-read string $status_badge
 * @property-read string $resource_type_icon
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> triggered()
 * @method static \Illuminate\Database\Eloquent\Builder<static> resolved()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forServer(int $serverId)
 * @method static \Illuminate\Database\Eloquent\Builder<static> recent(int $hours = 24)
 */
class AlertHistory extends Model
{
    /** @use HasFactory<\Database\Factories\AlertHistoryFactory> */
    use HasFactory;

    /** @var array<int, string> */
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'notified_at' => 'datetime',
        ];
    }

    // Relationships
    /**
     * @return BelongsTo<ResourceAlert, AlertHistory>
     */
    public function resourceAlert(): BelongsTo
    {
        return $this->belongsTo(ResourceAlert::class);
    }

    /**
     * @return BelongsTo<Server, AlertHistory>
     */
    public function server(): BelongsTo
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
        return match ($this->status) {
            'triggered' => 'red',
            'resolved' => 'green',
            default => 'gray',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'triggered' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Triggered</span>',
            'resolved' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Resolved</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">Unknown</span>',
        };
    }

    public function getResourceTypeIconAttribute(): string
    {
        return match ($this->resource_type) {
            'cpu' => 'heroicon-o-cpu-chip',
            'memory' => 'heroicon-o-circle-stack',
            'disk' => 'heroicon-o-server-stack',
            'load' => 'heroicon-o-chart-bar',
            default => 'heroicon-o-bell-alert',
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AlertHistory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AlertHistory>
     */
    public function scopeTriggered($query)
    {
        return $query->where('status', 'triggered');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AlertHistory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AlertHistory>
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AlertHistory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AlertHistory>
     */
    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AlertHistory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AlertHistory>
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
