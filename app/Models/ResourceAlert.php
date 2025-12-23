<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $server_id
 * @property string $resource_type
 * @property string $threshold_type
 * @property float $threshold_value
 * @property array<int, string> $notification_channels
 * @property bool $is_active
 * @property int $cooldown_minutes
 * @property \Illuminate\Support\Carbon|null $last_triggered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AlertHistory> $history
 * @property-read AlertHistory|null $latestHistory
 * @property-read string $resource_type_icon
 * @property-read string $resource_type_label
 * @property-read string $threshold_display
 * @property-read int|null $cooldown_remaining_minutes
 *
 * @method static Builder<ResourceAlert> active()
 * @method static Builder<ResourceAlert> forServer(int $serverId)
 * @method static Builder<ResourceAlert> forResourceType(string $resourceType)
 */
class ResourceAlert extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceAlertFactory> */
    use HasFactory;

    /** @var array<int, string> */
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

    /**
     * @return array<string, string>
     */
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
    /**
     * @return BelongsTo<Server, ResourceAlert>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return HasMany<AlertHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(AlertHistory::class)->latest('created_at');
    }

    /**
     * @return HasOne<AlertHistory, $this>
     */
    public function latestHistory(): HasOne
    {
        return $this->hasOne(AlertHistory::class)->latestOfMany();
    }

    // Helpers
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

    public function getResourceTypeLabelAttribute(): string
    {
        return match ($this->resource_type) {
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
        if (! $this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isFuture();
    }

    public function getCooldownRemainingMinutesAttribute(): ?int
    {
        if (! $this->isInCooldown() || $this->last_triggered_at === null) {
            return null;
        }

        return (int) now()->diffInMinutes($this->last_triggered_at->addMinutes($this->cooldown_minutes));
    }

    /**
     * @param  Builder<ResourceAlert>  $query
     * @return Builder<ResourceAlert>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ResourceAlert>  $query
     * @return Builder<ResourceAlert>
     */
    public function scopeForServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * @param  Builder<ResourceAlert>  $query
     * @return Builder<ResourceAlert>
     */
    public function scopeForResourceType(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }
}
