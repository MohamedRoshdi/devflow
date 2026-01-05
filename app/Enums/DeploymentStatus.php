<?php

declare(strict_types=1);

namespace App\Enums;

enum DeploymentStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
    case Cancelled = 'cancelled';

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
            self::Running => 'Running',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::RolledBack => 'Rolled Back',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get CSS color class for the status
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            self::Running => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
            self::Success => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
            self::Failed => 'bg-red-500/20 text-red-400 border-red-500/30',
            self::RolledBack => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            self::Cancelled => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        };
    }

    /**
     * Check if deployment is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed, self::RolledBack, self::Cancelled], true);
    }

    /**
     * Check if deployment is in progress
     */
    public function isInProgress(): bool
    {
        return $this === self::Running;
    }

    /**
     * Check if deployment is successful
     */
    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    /**
     * Check if deployment failed
     */
    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
