<?php

declare(strict_types=1);

namespace App\Enums;

enum RegionStatus: string
{
    case Active = 'active';
    case Degraded = 'degraded';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    /**
     * Get all possible values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Degraded => 'Degraded',
            self::Offline => 'Offline',
            self::Maintenance => 'Maintenance',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Degraded => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::Offline => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::Maintenance => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        };
    }

    /**
     * Check if region is available for operations
     */
    public function isAvailable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if region needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Degraded, self::Offline], true);
    }
}
