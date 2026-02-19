<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CostRate;
use App\Models\Deployment;
use App\Models\ServerMetric;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get deployment success rate over time (grouped by day).
     *
     * @return array{labels: array<string>, success_rates: array<float>, total_counts: array<int>}
     */
    public function getDeploymentSuccessRateOverTime(Carbon $dateFrom, ?int $projectId = null): array
    {
        $query = Deployment::query()
            ->where('created_at', '>=', $dateFrom)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful")
            ->groupBy('date')
            ->orderBy('date');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $results = $query->get();

        $labels = [];
        $successRates = [];
        $totalCounts = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $total = (int) $row->total;
            $successful = (int) $row->successful;
            $successRates[] = $total > 0 ? round(($successful / $total) * 100, 1) : 0.0;
            $totalCounts[] = $total;
        }

        return [
            'labels' => $labels,
            'success_rates' => $successRates,
            'total_counts' => $totalCounts,
        ];
    }

    /**
     * Get deployment status distribution (pie chart data).
     *
     * @return array{labels: array<string>, counts: array<int>, colors: array<string>}
     */
    public function getDeploymentStatusDistribution(Carbon $dateFrom, ?int $projectId = null): array
    {
        $query = Deployment::query()
            ->where('created_at', '>=', $dateFrom)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $results = $query->get();

        $statusColors = [
            'success' => '#10b981',
            'failed' => '#ef4444',
            'running' => '#3b82f6',
            'pending' => '#f59e0b',
            'cancelled' => '#6b7280',
            'rolled_back' => '#8b5cf6',
            'scheduled' => '#06b6d4',
        ];

        $labels = [];
        $counts = [];
        $colors = [];

        foreach ($results as $row) {
            $labels[] = ucfirst((string) $row->status);
            $counts[] = (int) $row->count;
            $colors[] = $statusColors[$row->status] ?? '#6b7280';
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'colors' => $colors,
        ];
    }

    /**
     * Get deployment duration trend over time.
     *
     * @return array{labels: array<string>, avg_durations: array<float>}
     */
    public function getDeploymentTimeTrend(Carbon $dateFrom, ?int $projectId = null): array
    {
        $query = Deployment::query()
            ->where('created_at', '>=', $dateFrom)
            ->whereNotNull('duration_seconds')
            ->where('status', 'success')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('AVG(duration_seconds) as avg_duration')
            ->groupBy('date')
            ->orderBy('date');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $results = $query->get();

        $labels = [];
        $avgDurations = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $avgDurations[] = round((float) $row->avg_duration, 1);
        }

        return [
            'labels' => $labels,
            'avg_durations' => $avgDurations,
        ];
    }

    /**
     * Get resource usage trends from server metrics.
     *
     * @return array{labels: array<string>, cpu: array<float>, memory: array<float>, disk: array<float>}
     */
    public function getResourceUsageTrends(Carbon $dateFrom, ?int $serverId = null): array
    {
        $query = ServerMetric::query()
            ->where('recorded_at', '>=', $dateFrom)
            ->selectRaw('DATE(recorded_at) as date')
            ->selectRaw('AVG(cpu_usage) as avg_cpu')
            ->selectRaw('AVG(memory_usage) as avg_memory')
            ->selectRaw('AVG(disk_usage) as avg_disk')
            ->groupBy('date')
            ->orderBy('date');

        if ($serverId !== null) {
            $query->where('server_id', $serverId);
        }

        $results = $query->get();

        $labels = [];
        $cpu = [];
        $memory = [];
        $disk = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $cpu[] = round((float) ($row->avg_cpu ?? 0), 1);
            $memory[] = round((float) ($row->avg_memory ?? 0), 1);
            $disk[] = round((float) ($row->avg_disk ?? 0), 1);
        }

        return [
            'labels' => $labels,
            'cpu' => $cpu,
            'memory' => $memory,
            'disk' => $disk,
        ];
    }

    /**
     * Get cost estimation based on server metrics and cost rates.
     *
     * @return array{total_cost: float, breakdown: array<int, array{resource: string, cost: float, usage: float, unit: string}>, currency: string, period_days: int}
     */
    public function getCostEstimation(Carbon $dateFrom, ?int $serverId = null): array
    {
        $periodDays = max(1, (int) $dateFrom->diffInDays(now()));

        // Get average resource usage
        $metricsQuery = ServerMetric::query()->where('recorded_at', '>=', $dateFrom);
        if ($serverId !== null) {
            $metricsQuery->where('server_id', $serverId);
        }

        $avgMetrics = $metricsQuery
            ->selectRaw('AVG(cpu_usage) as avg_cpu')
            ->selectRaw('AVG(memory_usage) as avg_memory')
            ->selectRaw('AVG(disk_usage) as avg_disk')
            ->first();

        // Get cost rates (use defaults if none configured)
        $ratesQuery = CostRate::where('is_active', true);
        if ($serverId !== null) {
            $ratesQuery->where(function ($q) use ($serverId): void {
                $q->where('server_id', $serverId)->orWhereNull('server_id');
            });
        }
        $ratesList = $ratesQuery->get();

        $defaultRates = [
            'cpu' => ['rate' => 0.05, 'unit' => 'per_core_hour'],
            'memory' => ['rate' => 0.01, 'unit' => 'per_gb_hour'],
            'disk' => ['rate' => 0.001, 'unit' => 'per_gb_month'],
        ];

        /** @var array<int, array{resource: string, cost: float, usage: float, unit: string}> $breakdown */
        $breakdown = [];
        $totalCost = 0.0;

        foreach (['cpu', 'memory', 'disk'] as $resource) {
            $rate = $ratesList->firstWhere('resource_type', $resource);
            $ratePerUnit = $rate !== null ? (float) $rate->rate_per_unit : $defaultRates[$resource]['rate'];
            $unit = $rate !== null ? (string) $rate->unit : $defaultRates[$resource]['unit'];

            $usage = match ($resource) {
                'cpu' => (float) ($avgMetrics?->avg_cpu ?? 0),
                'memory' => (float) ($avgMetrics?->avg_memory ?? 0),
                'disk' => (float) ($avgMetrics?->avg_disk ?? 0),
                default => 0.0,
            };

            $hours = $periodDays * 24;
            $cost = match ($unit) {
                'per_core_hour' => $ratePerUnit * ($usage / 100) * $hours,
                'per_gb_hour' => $ratePerUnit * ($usage / 100) * $hours,
                'per_gb_month' => $ratePerUnit * ($usage / 100) * ($periodDays / 30),
                default => 0.0,
            };

            $cost = round($cost, 2);
            $totalCost += $cost;

            $breakdown[] = [
                'resource' => ucfirst($resource),
                'cost' => $cost,
                'usage' => round($usage, 1),
                'unit' => str_replace('_', ' ', $unit),
            ];
        }

        return [
            'total_cost' => round($totalCost, 2),
            'breakdown' => $breakdown,
            'currency' => 'USD',
            'period_days' => $periodDays,
        ];
    }
}
