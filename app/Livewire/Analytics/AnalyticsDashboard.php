<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public string $selectedPeriod = '7days';

    public string $selectedProject = '';

    public string $selectedServer = '';

    public string $activeTab = 'overview';

    public function mount(): void
    {
        // Check if user has permission to view analytics
        $user = auth()->user();
        abort_unless(
            $user && $user->can('view-analytics'),
            403,
            'You do not have permission to view analytics.'
        );
    }

    public function updatedSelectedPeriod(): void
    {
        $this->dispatch('charts-updated');
    }

    public function updatedSelectedProject(): void
    {
        $this->dispatch('charts-updated');
    }

    public function updatedSelectedServer(): void
    {
        $this->dispatch('charts-updated');
    }

    public function render(): View
    {
        // All projects are shared across all users
        $projects = Project::select('id', 'name', 'slug', 'status', 'storage_used_mb')->orderBy('name')->get();

        $servers = Server::select('id', 'name', 'hostname', 'status')->orderBy('name')->get();

        $dateFrom = $this->getDateFrom();

        // Deployment Statistics
        $deploymentStats = $this->getDeploymentStats($dateFrom);

        // Server Performance Metrics
        $serverMetrics = $this->getServerMetrics($dateFrom);

        // Project Analytics
        $projectAnalytics = $this->getProjectAnalytics($dateFrom);

        // Chart Data (for charts tab)
        $chartData = $this->getChartData($dateFrom);

        // Cost Data (for costs tab)
        $costData = $this->getCostData($dateFrom);

        return view('livewire.analytics.analytics-dashboard', [
            'projects' => $projects,
            'servers' => $servers,
            'deploymentStats' => $deploymentStats,
            'serverMetrics' => $serverMetrics,
            'projectAnalytics' => $projectAnalytics,
            'chartData' => $chartData,
            'costData' => $costData,
            'activeTab' => $this->activeTab,
        ]);
    }

    protected function getDateFrom(): Carbon
    {
        return match ($this->selectedPeriod) {
            '24hours' => now()->subDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }

    /**
     * @return array{total: int, successful: int, failed: int, avg_duration: float|int}
     */
    protected function getDeploymentStats(Carbon $dateFrom): array
    {
        // All deployments are shared
        $query = Deployment::where('created_at', '>=', $dateFrom);

        if ($this->selectedProject !== '') {
            $query->where('project_id', $this->selectedProject);
        }

        return [
            'total' => $query->count(),
            'successful' => $query->clone()->where('status', 'success')->count(),
            'failed' => $query->clone()->where('status', 'failed')->count(),
            'avg_duration' => round((float) ($query->clone()->whereNotNull('duration_seconds')->avg('duration_seconds') ?? 0), 2),
        ];
    }

    protected function getServerMetrics(Carbon $dateFrom): ?ServerMetric
    {
        // All server metrics are shared
        $query = ServerMetric::where('recorded_at', '>=', $dateFrom);

        if ($this->selectedServer !== '') {
            $query->where('server_id', (int) $this->selectedServer);
        }

        return $query
            ->selectRaw('AVG(cpu_usage) as avg_cpu')
            ->selectRaw('AVG(memory_usage) as avg_memory')
            ->selectRaw('AVG(disk_usage) as avg_disk')
            ->first();
    }

    /**
     * @return array{total_projects: int, running: int, stopped: int, total_storage: int}
     */
    protected function getProjectAnalytics(Carbon $dateFrom): array
    {
        // All projects are shared
        $query = Project::query();

        if ($this->selectedProject !== '') {
            $query->where('id', $this->selectedProject);
        }

        return [
            'total_projects' => $query->count(),
            'running' => $query->clone()->where('status', 'running')->count(),
            'stopped' => $query->clone()->where('status', 'stopped')->count(),
            'total_storage' => (int) $query->sum('storage_used_mb'),
        ];
    }

    /**
     * Get all chart data from the AnalyticsService.
     *
     * @return array{
     *     successRate: array{labels: array<string>, success_rates: array<float>, total_counts: array<int>},
     *     statusDistribution: array{labels: array<string>, counts: array<int>, colors: array<string>},
     *     timeTrend: array{labels: array<string>, avg_durations: array<float>},
     *     resourceUsage: array{labels: array<string>, cpu: array<float>, memory: array<float>, disk: array<float>}
     * }
     */
    protected function getChartData(Carbon $dateFrom): array
    {
        $analyticsService = app(AnalyticsService::class);

        $projectId = $this->selectedProject !== '' ? (int) $this->selectedProject : null;
        $serverId = $this->selectedServer !== '' ? (int) $this->selectedServer : null;

        return [
            'successRate' => $analyticsService->getDeploymentSuccessRateOverTime($dateFrom, $projectId),
            'statusDistribution' => $analyticsService->getDeploymentStatusDistribution($dateFrom, $projectId),
            'timeTrend' => $analyticsService->getDeploymentTimeTrend($dateFrom, $projectId),
            'resourceUsage' => $analyticsService->getResourceUsageTrends($dateFrom, $serverId),
        ];
    }

    /**
     * Get cost estimation data from the AnalyticsService.
     *
     * @return array{total_cost: float, breakdown: array<int, array{resource: string, cost: float, usage: float, unit: string}>, currency: string, period_days: int}
     */
    protected function getCostData(Carbon $dateFrom): array
    {
        $analyticsService = app(AnalyticsService::class);

        $serverId = $this->selectedServer !== '' ? (int) $this->selectedServer : null;

        return $analyticsService->getCostEstimation($dateFrom, $serverId);
    }
}
