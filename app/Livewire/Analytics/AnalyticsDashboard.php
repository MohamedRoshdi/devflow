<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\ServerMetric;
use Carbon\Carbon;

class AnalyticsDashboard extends Component
{
    public $selectedPeriod = '7days';
    public $selectedProject = '';

    public function render()
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

    protected function getDateFrom()
    {
        return match($this->selectedPeriod) {
            '24hours' => now()->subDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }

    protected function getDeploymentStats($dateFrom)
    {
        // All deployments are shared
        $query = Deployment::where('created_at', '>=', $dateFrom);

        if ($this->selectedProject) {
            $query->where('project_id', $this->selectedProject);
        }

        return [
            'total' => $query->count(),
            'successful' => $query->clone()->where('status', 'success')->count(),
            'failed' => $query->clone()->where('status', 'failed')->count(),
            'avg_duration' => round($query->clone()->whereNotNull('duration_seconds')->avg('duration_seconds'), 2),
        ];
    }

    protected function getServerMetrics($dateFrom)
    {
        // All server metrics are shared
        return ServerMetric::where('recorded_at', '>=', $dateFrom)
            ->selectRaw('AVG(cpu_usage) as avg_cpu')
            ->selectRaw('AVG(memory_usage) as avg_memory')
            ->selectRaw('AVG(disk_usage) as avg_disk')
            ->first();
    }

    protected function getProjectAnalytics($dateFrom)
    {
        // All projects are shared
        $query = Project::query();

        if ($this->selectedProject) {
            $query->where('id', $this->selectedProject);
        }

        return [
            'total_projects' => $query->count(),
            'running' => $query->clone()->where('status', 'running')->count(),
            'stopped' => $query->clone()->where('status', 'stopped')->count(),
            'total_storage' => $query->sum('storage_used_mb'),
        ];
    }
}

