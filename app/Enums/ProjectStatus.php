<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string
{
    case Running = 'running';
    case Stopped = 'stopped';
    case Building = 'building';
    case Error = 'error';
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
            self::Running => 'Running',
            self::Stopped => 'Stopped',
            self::Building => 'Building',
            self::Error => 'Error',
            self::Maintenance => 'Maintenance',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Running => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Stopped => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
            self::Building => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::Error => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::Maintenance => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        };
    }

    /**
     * Check if project is active
     */
    public function isActive(): bool
    {
        return $this === self::Running;
    }

    /**
     * Check if project is in a transitional state
     */
    public function isTransitional(): bool
    {
        return $this === self::Building;
    }

    /**
     * Check if project has issues
     */
    public function hasIssues(): bool
    {
        return $this === self::Error;
    }
}
