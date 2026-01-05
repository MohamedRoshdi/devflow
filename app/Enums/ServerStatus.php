<?php

declare(strict_types=1);

namespace App\Enums;

enum ServerStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Maintenance = 'maintenance';
    case Provisioning = 'provisioning';
    case Error = 'error';

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
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Maintenance => 'Maintenance',
            self::Provisioning => 'Provisioning',
            self::Error => 'Error',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Online => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Offline => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
            self::Maintenance => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::Provisioning => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::Error => 'bg-red-500/20 text-red-400 border-red-500/30',
        };
    }

    /**
     * Check if server is available for operations
     */
    public function isAvailable(): bool
    {
        return $this === self::Online;
    }

    /**
     * Check if server needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Offline, self::Error], true);
    }
}
