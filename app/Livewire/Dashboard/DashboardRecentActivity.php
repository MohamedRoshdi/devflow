<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Deployment;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Recent Activity Component
 *
 * Displays a feed of recent system activity including deployments and project creations.
 * Supports lazy loading with "load more" functionality.
 *
 * @property array<int, array<string, mixed>> $recentActivity Recent system activity feed
 * @property int $activityPerPage Number of activity items per page
 * @property bool $loadingMoreActivity Loading state for activity feed pagination
 */
class DashboardRecentActivity extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $recentActivity = [];

    public int $activityPerPage = 5;

    public bool $loadingMoreActivity = false;

    public bool $isLoading = false;

    public bool $hasError = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->loadRecentActivity();
    }

    /**
     * Load initial recent activity data
     */
    public function loadRecentActivity(): void
    {
        try {
            $this->hasError = false;
            $this->errorMessage = '';
            $this->doLoadRecentActivity();
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load recent activity.';
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Retry loading activity after an error
     */
    public function retryLoad(): void
    {
        $this->isLoading = true;
        $this->loadRecentActivity();
    }

    /**
     * Internal method to load recent activity data
     */
    private function doLoadRecentActivity(): void
    {
        $deploymentsLimit = 4;
        $projectsLimit = 1;

        $recentDeployments = Deployment::query()
            ->select(['id', 'project_id', 'user_id', 'branch', 'status', 'triggered_by', 'created_at'])
            ->with([
                'project:id,name',
                'user:id,name'
            ])
            ->latest()
            ->take($deploymentsLimit)
            ->get()
            ->map(function ($deployment) {
                return [
                    'type' => 'deployment',
                    'id' => $deployment->id,
                    'title' => 'Deployment: '.($deployment->project?->name ?? 'Unknown'),
                    'description' => "Deployment on branch {$deployment->branch} - {$deployment->status}",
                    'status' => $deployment->status,
                    'user' => $deployment->user?->name ?? 'System',
                    'timestamp' => $deployment->created_at,
                    'triggered_by' => $deployment->triggered_by,
                ];
            });

        $recentProjects = Project::query()
            ->select(['id', 'name', 'framework', 'status', 'user_id', 'server_id', 'created_at'])
            ->with([
                'user:id,name',
                'server:id,name'
            ])
            ->latest()
            ->take($projectsLimit)
            ->get()
            ->map(function ($project) {
                return [
                    'type' => 'project_created',
                    'id' => $project->id,
                    'title' => "Project Created: {$project->name}",
                    'description' => "New {$project->framework} project on ".($project->server?->name ?? 'Unknown Server'),
                    'status' => $project->status,
                    'user' => $project->user?->name ?? 'System',
                    'timestamp' => $project->created_at,
                    'framework' => $project->framework,
                ];
            });

        $this->recentActivity = collect()
            ->merge($recentDeployments)
            ->merge($recentProjects)
            ->sortByDesc('timestamp')
            ->take($this->activityPerPage)
            ->values()
            ->all();
    }

    /**
     * Load more activity items for pagination
     */
    public function loadMoreActivity(): void
    {
        $this->loadingMoreActivity = true;

        $currentCount = count($this->recentActivity);
        $maxItems = 20;

        if ($currentCount >= $maxItems) {
            $this->loadingMoreActivity = false;

            return;
        }

        $itemsToLoad = min($this->activityPerPage, $maxItems - $currentCount);

        $deploymentsToLoad = (int) ceil($itemsToLoad * 0.8);
        $projectsToLoad = (int) ceil($itemsToLoad * 0.2);

        $recentDeployments = Deployment::query()
            ->select(['id', 'project_id', 'user_id', 'branch', 'status', 'triggered_by', 'created_at'])
            ->with([
                'project:id,name',
                'user:id,name'
            ])
            ->latest()
            ->skip($currentCount)
            ->take($deploymentsToLoad)
            ->get()
            ->map(function ($deployment) {
                return [
                    'type' => 'deployment',
                    'id' => $deployment->id,
                    'title' => 'Deployment: '.($deployment->project?->name ?? 'Unknown'),
                    'description' => "Deployment on branch {$deployment->branch} - {$deployment->status}",
                    'status' => $deployment->status,
                    'user' => $deployment->user?->name ?? 'System',
                    'timestamp' => $deployment->created_at,
                    'triggered_by' => $deployment->triggered_by,
                ];
            });

        $currentProjectsCount = collect($this->recentActivity)
            ->where('type', 'project_created')
            ->count();

        $recentProjects = Project::query()
            ->select(['id', 'name', 'framework', 'status', 'user_id', 'server_id', 'created_at'])
            ->with([
                'user:id,name',
                'server:id,name'
            ])
            ->latest()
            ->skip($currentProjectsCount)
            ->take($projectsToLoad)
            ->get()
            ->map(function ($project) {
                return [
                    'type' => 'project_created',
                    'id' => $project->id,
                    'title' => "Project Created: {$project->name}",
                    'description' => "New {$project->framework} project on ".($project->server?->name ?? 'Unknown Server'),
                    'status' => $project->status,
                    'user' => $project->user?->name ?? 'System',
                    'timestamp' => $project->created_at,
                    'framework' => $project->framework,
                ];
            });

        $this->recentActivity = collect($this->recentActivity)
            ->merge($recentDeployments)
            ->merge($recentProjects)
            ->sortByDesc('timestamp')
            ->take($maxItems)
            ->values()
            ->all();

        $this->loadingMoreActivity = false;
    }

    #[On('deployment-completed')]
    #[On('refresh-activity')]
    public function refreshActivity(): void
    {
        $this->loadRecentActivity();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.dashboard-recent-activity');
    }
}
