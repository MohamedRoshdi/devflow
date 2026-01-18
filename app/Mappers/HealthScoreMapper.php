<?php

declare(strict_types=1);

namespace App\Mappers;

/**
 * Health Score Mapper
 *
 * Centralized mapper for converting between health statuses, numeric scores,
 * colors, icons, and badge classes throughout the DevFlow Pro application.
 *
 * This class provides consistent mapping logic for health-related UI components
 * and eliminates duplicate status mapping code across models and services.
 *
 * @phpstan-type HealthStatus 'healthy'|'warning'|'critical'|'unknown'
 */
final class HealthScoreMapper
{
    /**
     * Health status constants
     */
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Additional status aliases for compatibility
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_RUNNING = 'running';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ONLINE = 'online';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_DOWN = 'down';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ERROR = 'error';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_MAINTENANCE = 'maintenance';

    /**
     * Health score thresholds
     */
    public const SCORE_HEALTHY_MIN = 80;
    public const SCORE_WARNING_MIN = 50;
    public const SCORE_CRITICAL_MIN = 0;
    public const SCORE_MAX = 100;

    /**
     * Color constants (matching Tailwind CSS color names)
     */
    public const COLOR_GREEN = 'green';
    public const COLOR_YELLOW = 'yellow';
    public const COLOR_RED = 'red';
    public const COLOR_GRAY = 'gray';
    public const COLOR_BLUE = 'blue';
    public const COLOR_ORANGE = 'orange';

    /**
     * Icon name constants (Heroicons compatible)
     */
    public const ICON_CHECK_CIRCLE = 'check-circle';
    public const ICON_EXCLAMATION_TRIANGLE = 'exclamation-triangle';
    public const ICON_X_CIRCLE = 'x-circle';
    public const ICON_QUESTION_MARK_CIRCLE = 'question-mark-circle';
    public const ICON_CLOCK = 'clock';
    public const ICON_ARROW_PATH = 'arrow-path';

    /**
     * Private constructor to prevent instantiation
     * This is a static utility class
     */
    private function __construct()
    {
        // Prevent instantiation
    }

    /**
     * Convert health status string to numeric score (0-100)
     *
     * @param string $status The health status (e.g., 'healthy', 'warning', 'critical')
     * @return int Health score between 0 and 100
     */
    public static function statusToScore(string $status): int
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            self::STATUS_HEALTHY,
            self::STATUS_SUCCESS,
            self::STATUS_RUNNING,
            self::STATUS_ACTIVE,
            self::STATUS_ONLINE => 95,

            self::STATUS_WARNING,
            self::STATUS_DEGRADED,
            self::STATUS_MAINTENANCE => 65,

            self::STATUS_CRITICAL,
            self::STATUS_DOWN,
            self::STATUS_FAILED,
            self::STATUS_ERROR,
            self::STATUS_OFFLINE => 25,

            self::STATUS_STOPPED => 40,

            default => 50, // Unknown status defaults to warning range
        };
    }

    /**
     * Convert numeric score to health status string
     *
     * @param int $score Health score (0-100)
     * @return string Health status ('healthy', 'warning', 'critical')
     */
    public static function scoreToStatus(int $score): string
    {
        // Clamp score to valid range
        $score = max(self::SCORE_CRITICAL_MIN, min(self::SCORE_MAX, $score));

        if ($score >= self::SCORE_HEALTHY_MIN) {
            return self::STATUS_HEALTHY;
        }

        if ($score >= self::SCORE_WARNING_MIN) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_CRITICAL;
    }

    /**
     * Map health status to Tailwind color class name
     *
     * @param string $status The health status
     * @return string Color name (e.g., 'green', 'yellow', 'red')
     */
    public static function statusToColor(string $status): string
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            self::STATUS_HEALTHY,
            self::STATUS_SUCCESS,
            self::STATUS_RUNNING,
            self::STATUS_ACTIVE,
            self::STATUS_ONLINE => self::COLOR_GREEN,

            self::STATUS_WARNING,
            self::STATUS_DEGRADED,
            self::STATUS_MAINTENANCE => self::COLOR_YELLOW,

            self::STATUS_CRITICAL,
            self::STATUS_DOWN,
            self::STATUS_FAILED,
            self::STATUS_ERROR,
            self::STATUS_OFFLINE => self::COLOR_RED,

            self::STATUS_STOPPED => self::COLOR_ORANGE,

            default => self::COLOR_GRAY,
        };
    }

    /**
     * Map health status to icon name (Heroicons compatible)
     *
     * @param string $status The health status
     * @return string Icon name (e.g., 'check-circle', 'exclamation-triangle')
     */
    public static function statusToIcon(string $status): string
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            self::STATUS_HEALTHY,
            self::STATUS_SUCCESS,
            self::STATUS_ONLINE,
            self::STATUS_ACTIVE => self::ICON_CHECK_CIRCLE,

            self::STATUS_WARNING,
            self::STATUS_DEGRADED => self::ICON_EXCLAMATION_TRIANGLE,

            self::STATUS_CRITICAL,
            self::STATUS_DOWN,
            self::STATUS_FAILED,
            self::STATUS_ERROR,
            self::STATUS_OFFLINE => self::ICON_X_CIRCLE,

            self::STATUS_RUNNING => self::ICON_ARROW_PATH,
            self::STATUS_STOPPED => self::ICON_CLOCK,

            default => self::ICON_QUESTION_MARK_CIRCLE,
        };
    }

    /**
     * Get complete badge CSS classes for a given status
     *
     * Returns a string of Tailwind CSS classes for badge styling including
     * background, text color, and border styles.
     *
     * @param string $status The health status
     * @return string Complete CSS class string for badge
     */
    public static function statusToBadgeClass(string $status): string
    {
        $color = self::statusToColor($status);

        return match ($color) {
            self::COLOR_GREEN => 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-md shadow-emerald-500/30',
            self::COLOR_YELLOW => 'bg-gradient-to-r from-yellow-500 to-amber-500 text-white shadow-md shadow-yellow-500/30',
            self::COLOR_RED => 'bg-gradient-to-r from-red-500 to-rose-500 text-white shadow-md shadow-red-500/30',
            self::COLOR_ORANGE => 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/30',
            self::COLOR_BLUE => 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-md shadow-blue-500/30',
            default => 'bg-gradient-to-r from-gray-400 to-slate-500 text-white shadow-md shadow-gray-500/30',
        };
    }

    /**
     * Get simpler badge CSS classes without gradients (for minimal UI)
     *
     * @param string $status The health status
     * @return string Simple CSS class string for badge
     */
    public static function statusToSimpleBadgeClass(string $status): string
    {
        $color = self::statusToColor($status);

        return match ($color) {
            self::COLOR_GREEN => 'bg-green-100 text-green-800 border border-green-200',
            self::COLOR_YELLOW => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            self::COLOR_RED => 'bg-red-100 text-red-800 border border-red-200',
            self::COLOR_ORANGE => 'bg-orange-100 text-orange-800 border border-orange-200',
            self::COLOR_BLUE => 'bg-blue-100 text-blue-800 border border-blue-200',
            default => 'bg-gray-100 text-gray-800 border border-gray-200',
        };
    }

    /**
     * Get text color class for a given status
     *
     * @param string $status The health status
     * @return string Tailwind text color class
     */
    public static function statusToTextColor(string $status): string
    {
        $color = self::statusToColor($status);

        return match ($color) {
            self::COLOR_GREEN => 'text-green-600',
            self::COLOR_YELLOW => 'text-yellow-600',
            self::COLOR_RED => 'text-red-600',
            self::COLOR_ORANGE => 'text-orange-600',
            self::COLOR_BLUE => 'text-blue-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get background color class for a given status
     *
     * @param string $status The health status
     * @return string Tailwind background color class
     */
    public static function statusToBackgroundColor(string $status): string
    {
        $color = self::statusToColor($status);

        return match ($color) {
            self::COLOR_GREEN => 'bg-green-50',
            self::COLOR_YELLOW => 'bg-yellow-50',
            self::COLOR_RED => 'bg-red-50',
            self::COLOR_ORANGE => 'bg-orange-50',
            self::COLOR_BLUE => 'bg-blue-50',
            default => 'bg-gray-50',
        };
    }

    /**
     * Check if a status is considered healthy
     *
     * @param string $status The health status
     * @return bool True if status is healthy
     */
    public static function isHealthy(string $status): bool
    {
        $normalizedStatus = strtolower(trim($status));

        return in_array($normalizedStatus, [
            self::STATUS_HEALTHY,
            self::STATUS_SUCCESS,
            self::STATUS_RUNNING,
            self::STATUS_ACTIVE,
            self::STATUS_ONLINE,
        ], true);
    }

    /**
     * Check if a status is considered critical
     *
     * @param string $status The health status
     * @return bool True if status is critical
     */
    public static function isCritical(string $status): bool
    {
        $normalizedStatus = strtolower(trim($status));

        return in_array($normalizedStatus, [
            self::STATUS_CRITICAL,
            self::STATUS_DOWN,
            self::STATUS_FAILED,
            self::STATUS_ERROR,
            self::STATUS_OFFLINE,
        ], true);
    }

    /**
     * Get all valid health statuses
     *
     * @return array<int, string>
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_HEALTHY,
            self::STATUS_WARNING,
            self::STATUS_CRITICAL,
            self::STATUS_UNKNOWN,
        ];
    }

    /**
     * Get SVG icon markup for a status
     *
     * @param string $status The health status
     * @param string $cssClass Additional CSS classes for the SVG element
     * @return string SVG icon HTML markup
     */
    public static function statusToIconSvg(string $status, string $cssClass = 'w-5 h-5'): string
    {
        $icon = self::statusToIcon($status);

        return match ($icon) {
            self::ICON_CHECK_CIRCLE => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                $cssClass
            ),
            self::ICON_EXCLAMATION_TRIANGLE => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                $cssClass
            ),
            self::ICON_X_CIRCLE => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                $cssClass
            ),
            self::ICON_CLOCK => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                $cssClass
            ),
            self::ICON_ARROW_PATH => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>',
                $cssClass
            ),
            default => sprintf(
                '<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                $cssClass
            ),
        };
    }

    /**
     * Calculate health score from multiple check results
     *
     * Calculates an aggregate health score based on individual check scores.
     * Uses weighted average if weights are provided.
     *
     * @param array<string, int> $checks Array of check name => score pairs
     * @param array<string, float>|null $weights Optional weights for each check (0.0-1.0)
     * @return int Overall health score (0-100)
     */
    public static function calculateAggregateScore(array $checks, ?array $weights = null): int
    {
        if (empty($checks)) {
            return 0;
        }

        if ($weights === null) {
            // Simple average
            $total = array_sum($checks);
            $count = count($checks);

            return (int) round($total / $count);
        }

        // Weighted average
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($checks as $checkName => $score) {
            $weight = $weights[$checkName] ?? 1.0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0.0) {
            return 0;
        }

        return (int) round($weightedSum / $totalWeight);
    }
}
