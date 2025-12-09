<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ServerMetric;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public string $selectedPeriod = '7days';

    public string $selectedProject = '';

    public function render(): View
    {
        // All projects are shared across all users
        $projects = Project::all();

        $dateFrom = $this->getDateFrom();

        // Deployment Statistics
        $deploymentStats = $this->getDeploymentStats($dateFrom);

        // Server Performance Metrics
        $serverMetrics = $this->getServerMetrics($dateFrom);

        // Project Analytics
        $projectAnalytics = $this->getProjectAnalytics($dateFrom);

        return view('livewire.analytics.analytics-dashboard', [
            'projects' => $projects,
            'deploymentStats' => $deploymentStats,
            'serverMetrics' => $serverMetrics,
            'projectAnalytics' => $projectAnalytics,
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
        return ServerMetric::where('recorded_at', '>=', $dateFrom)
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
}
