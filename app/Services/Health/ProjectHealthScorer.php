<?php

declare(strict_types=1);

namespace App\Services\Health;

use App\Mappers\HealthScoreMapper;
use App\Models\Project;

/**
 * Project Health Scorer
 *
 * Calculates health scores for projects based on various metrics.
 * This class follows the Single Responsibility Principle by focusing
 * exclusively on score calculation logic.
 */
class ProjectHealthScorer
{
    /**
     * Calculate overall health score for a project (0-100)
     *
     * @param array<string, array<string, mixed>> $checks Health check results
     */
    public function calculateOverallScore(array $checks, Project $project): int
    {
        $score = 100;

        // Deduct for HTTP issues
        $score -= $this->calculateHttpPenalty($checks['http'] ?? []);

        // Deduct for SSL issues
        $score -= $this->calculateSslPenalty($checks['ssl'] ?? []);

        // Deduct for Docker issues
        $score -= $this->calculateDockerPenalty($checks['docker'] ?? []);

        // Deduct for disk issues
        $score -= $this->calculateDiskPenalty($checks['disk'] ?? []);

        // Deduct for deployment issues
        $score -= $this->calculateDeploymentPenalty($checks['deployment'] ?? []);

        // Deduct for project status
        $score -= $this->calculateProjectStatusPenalty($project);

        return max(0, min(100, $score));
    }

    /**
     * Calculate HTTP health component score penalty
     *
     * @param array<string, mixed> $httpCheck
     */
    public function calculateHttpPenalty(array $httpCheck): int
    {
        $penalty = 0;

        $status = $httpCheck['status'] ?? 'unknown';

        // Major penalty for unreachable endpoints
        if ($status === 'unreachable') {
            $penalty += 40;
        } elseif ($status === 'unhealthy') {
            $penalty += 20;
        }

        // Additional penalty for slow response times
        $responseTime = $httpCheck['response_time'] ?? null;
        if ($responseTime !== null && $responseTime > 2000) {
            $penalty += 10;
        }

        return $penalty;
    }

    /**
     * Calculate SSL health component score penalty
     *
     * @param array<string, mixed> $sslCheck
     */
    public function calculateSslPenalty(array $sslCheck): int
    {
        $penalty = 0;

        $status = $sslCheck['status'] ?? 'unknown';

        if ($status === 'expired') {
            $penalty += 30;
        } elseif ($status === 'expiring_soon') {
            $penalty += 10;
        }

        return $penalty;
    }

    /**
     * Calculate Docker container health component score penalty
     *
     * @param array<string, mixed> $dockerCheck
     */
    public function calculateDockerPenalty(array $dockerCheck): int
    {
        $penalty = 0;

        $status = $dockerCheck['status'] ?? 'unknown';

        if ($status === 'stopped') {
            $penalty += 30;
        } elseif ($status === 'error') {
            $penalty += 20;
        }

        return $penalty;
    }

    /**
     * Calculate disk usage health component score penalty
     *
     * @param array<string, mixed> $diskCheck
     */
    public function calculateDiskPenalty(array $diskCheck): int
    {
        $penalty = 0;

        $status = $diskCheck['status'] ?? 'unknown';

        if ($status === 'critical') {
            $penalty += 20;
        } elseif ($status === 'warning') {
            $penalty += 10;
        }

        return $penalty;
    }

    /**
     * Calculate deployment health component score penalty
     *
     * @param array<string, mixed> $deploymentCheck
     */
    public function calculateDeploymentPenalty(array $deploymentCheck): int
    {
        $penalty = 0;

        $status = $deploymentCheck['status'] ?? 'unknown';

        if ($status === 'failed') {
            $penalty += 20;
        }

        return $penalty;
    }

    /**
     * Calculate project status penalty
     */
    public function calculateProjectStatusPenalty(Project $project): int
    {
        $penalty = 0;

        if ($project->status !== 'running') {
            $penalty += 30;
        }

        return $penalty;
    }

    /**
     * Calculate server health score (0-100)
     *
     * @param array<string, array<string, mixed>> $checks Server health check results
     */
    public function calculateServerHealthScore(array $checks, \App\Models\Server $server): int
    {
        $score = 100;

        // Deduct for connectivity issues
        $score -= $this->calculateServerConnectivityPenalty($checks['connectivity'] ?? []);

        // Deduct for resource issues
        $score -= $this->calculateServerResourcePenalty($checks['resources'] ?? []);

        // Deduct for Docker installation issues
        $score -= $this->calculateServerDockerPenalty($checks['docker'] ?? []);

        return max(0, min(100, (int) $score));
    }

    /**
     * Calculate server connectivity penalty
     *
     * @param array<string, mixed> $connectivityCheck
     */
    public function calculateServerConnectivityPenalty(array $connectivityCheck): int
    {
        $penalty = 0;

        $status = $connectivityCheck['status'] ?? 'unknown';

        if ($status === 'offline') {
            $penalty += 50;
        }

        return $penalty;
    }

    /**
     * Calculate server resource usage penalty
     *
     * @param array<string, mixed> $resourceCheck
     */
    public function calculateServerResourcePenalty(array $resourceCheck): int
    {
        $penalty = 0.0;

        $cpuUsage = $resourceCheck['cpu_usage'] ?? null;
        if ($cpuUsage !== null && $cpuUsage > 80) {
            $penalty += ($cpuUsage - 80) / 2;
        }

        $memoryUsage = $resourceCheck['memory_usage'] ?? null;
        if ($memoryUsage !== null && $memoryUsage > 80) {
            $penalty += ($memoryUsage - 80) / 2;
        }

        $diskUsage = $resourceCheck['disk_usage'] ?? null;
        if ($diskUsage !== null && $diskUsage > 80) {
            $penalty += ($diskUsage - 80) / 2;
        }

        return (int) $penalty;
    }

    /**
     * Calculate server Docker installation penalty
     *
     * @param array<string, mixed> $dockerCheck
     */
    public function calculateServerDockerPenalty(array $dockerCheck): int
    {
        $penalty = 0;

        $status = $dockerCheck['status'] ?? 'unknown';

        if ($status === 'not_installed') {
            $penalty += 20;
        }

        return $penalty;
    }

    /**
     * Determine overall status based on health score
     *
     * Maps a numeric health score to a categorical status.
     * Delegates to HealthScoreMapper for consistent status mapping.
     */
    public function determineOverallStatus(int $healthScore): string
    {
        return HealthScoreMapper::scoreToStatus($healthScore);
    }

    /**
     * Calculate HTTP uptime score (0-100)
     *
     * @param array<string, mixed> $httpCheck
     */
    public function calculateHttpUptimeScore(array $httpCheck): int
    {
        $status = $httpCheck['status'] ?? 'unknown';

        return match ($status) {
            'healthy' => 100,
            'unhealthy' => 50,
            'unreachable' => 0,
            default => 0,
        };
    }

    /**
     * Calculate response time score (0-100)
     *
     * Lower response times yield higher scores.
     *
     * @param array<string, mixed> $httpCheck
     */
    public function calculateResponseTimeScore(array $httpCheck): int
    {
        $responseTime = $httpCheck['response_time'] ?? null;

        if ($responseTime === null) {
            return 0;
        }

        // Excellent: < 500ms = 100
        // Good: 500-1000ms = 80-99
        // Fair: 1000-2000ms = 60-79
        // Poor: 2000-5000ms = 20-59
        // Critical: > 5000ms = 0-19

        if ($responseTime < 500) {
            return 100;
        } elseif ($responseTime < 1000) {
            return (int) (100 - (($responseTime - 500) / 500 * 20));
        } elseif ($responseTime < 2000) {
            return (int) (80 - (($responseTime - 1000) / 1000 * 20));
        } elseif ($responseTime < 5000) {
            return (int) (60 - (($responseTime - 2000) / 3000 * 40));
        } else {
            return 0;
        }
    }

    /**
     * Calculate deployment reliability score (0-100)
     *
     * @param array<string, mixed> $deploymentCheck
     */
    public function calculateDeploymentReliabilityScore(array $deploymentCheck): int
    {
        $status = $deploymentCheck['status'] ?? 'unknown';

        return match ($status) {
            'healthy' => 100,
            'in_progress' => 75,
            'failed' => 0,
            'none' => 50,
            default => 0,
        };
    }

    /**
     * Calculate SSL certificate health score (0-100)
     *
     * @param array<string, mixed> $sslCheck
     */
    public function calculateSslHealthScore(array $sslCheck): int
    {
        $status = $sslCheck['status'] ?? 'unknown';
        $daysRemaining = $sslCheck['days_remaining'] ?? null;

        if ($status === 'expired') {
            return 0;
        }

        if ($status === 'expiring_soon' && $daysRemaining !== null) {
            // Scale from 50-70 based on days remaining (1-7 days)
            return (int) (50 + ($daysRemaining / 7 * 20));
        }

        if ($status === 'valid') {
            return 100;
        }

        return 0;
    }

    /**
     * Calculate container availability score (0-100)
     *
     * @param array<string, mixed> $dockerCheck
     */
    public function calculateContainerAvailabilityScore(array $dockerCheck): int
    {
        $status = $dockerCheck['status'] ?? 'unknown';

        return match ($status) {
            'running' => 100,
            'stopped' => 0,
            'error' => 0,
            default => 0,
        };
    }

    /**
     * Calculate disk health score (0-100)
     *
     * @param array<string, mixed> $diskCheck
     */
    public function calculateDiskHealthScore(array $diskCheck): int
    {
        $usagePercent = $diskCheck['usage_percent'] ?? null;

        if ($usagePercent === null) {
            return 0;
        }

        // Invert the usage percentage to get a health score
        // 0% usage = 100 score
        // 100% usage = 0 score
        return (int) max(0, 100 - $usagePercent);
    }
}
