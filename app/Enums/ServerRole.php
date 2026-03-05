<?php

declare(strict_types=1);

namespace App\Enums;

enum ServerRole: string
{
    case General = 'general';
    case App = 'app';
    case Database = 'database';
    case Control = 'control';

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
     * Get display label for the role
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::App => 'Application',
            self::Database => 'Database',
            self::Control => 'Control Panel',
        };
    }

    /**
     * Get CSS color class for the role
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::General => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
            self::App => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::Database => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            self::Control => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        };
    }
}
