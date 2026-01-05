<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthCheckStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unknown = 'unknown';

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
            self::Healthy => 'Healthy',
            self::Degraded => 'Degraded',
            self::Unhealthy => 'Unhealthy',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Healthy => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Degraded => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::Unhealthy => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::Unknown => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        };
    }

    /**
     * Check if status requires attention
     */
    public function requiresAttention(): bool
    {
        return in_array($this, [self::Degraded, self::Unhealthy], true);
    }

    /**
     * Get severity level (0-3, higher is worse)
     */
    public function severity(): int
    {
        return match ($this) {
            self::Healthy => 0,
            self::Unknown => 1,
            self::Degraded => 2,
            self::Unhealthy => 3,
        };
    }
}
