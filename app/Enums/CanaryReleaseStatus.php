<?php

declare(strict_types=1);

namespace App\Enums;

enum CanaryReleaseStatus: string
{
    case Pending = 'pending';
    case Deploying = 'deploying';
    case Monitoring = 'monitoring';
    case Promoting = 'promoting';
    case RollingBack = 'rolling_back';
    case Completed = 'completed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';

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
            self::Pending => 'Pending',
            self::Deploying => 'Deploying',
            self::Monitoring => 'Monitoring',
            self::Promoting => 'Promoting',
            self::RollingBack => 'Rolling Back',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::RolledBack => 'Rolled Back',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
            self::Deploying => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::Monitoring => 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
            self::Promoting => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::RollingBack => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            self::Completed => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Failed => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::RolledBack => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        };
    }

    /**
     * Check if release is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::RolledBack], true);
    }

    /**
     * Check if release is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this, [self::Deploying, self::Monitoring, self::Promoting, self::RollingBack], true);
    }
}
