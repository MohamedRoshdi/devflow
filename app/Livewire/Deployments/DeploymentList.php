<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Deployment List Component
 *
 * Displays a paginated list of deployments with advanced filtering options.
 * Features search, status filtering, project filtering, and cached statistics.
 * URL parameters are persisted for easy sharing and bookmarking.
 *
 * @property string $statusFilter Filter deployments by status (success, failed, running, pending)
 * @property string $projectFilter Filter deployments by project ID
 * @property string $search Search term for filtering by commit message or branch
 * @property int $perPage Number of deployments per page (5-50)
 */
class DeploymentList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $projectFilter = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 15)]
    public int $perPage = 15;

    protected string $paginationTheme = 'tailwind';

    /**
     * Reset pagination when status filter changes
     *
     * @return void
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when project filter changes
     *
     * @return void
     */
    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when search term changes
     *
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Validate and update per-page value
     *
     * Ensures the value is within acceptable range (5-50).
     *
     * @param mixed $value The new per-page value
     * @return void
     */
    public function updatedPerPage(mixed $value): void
    {
        $value = (int) $value;

        if ($value < 5 || $value > 50) {
            $this->perPage = 15;
        } else {
            $this->perPage = $value;
        }

        $this->resetPage();
    }

    /**
     * Render the deployment list view with pagination and statistics
     *
     * Eager loads relationships, applies filters, and caches statistics.
     * Returns paginated deployment results with project dropdown data.
     *
     * @return \Illuminate\View\View
     */
    public function render(): \Illuminate\View\View
    {
        // Optimized: Cache stats for 2 minutes (works with all cache drivers)
        $stats = Cache::remember('deployment_stats', 120, function () {
            return [
                'total' => Deployment::count(),
                'success' => Deployment::where('status', 'success')->count(),
                'failed' => Deployment::where('status', 'failed')->count(),
                'running' => Deployment::where('status', 'running')->count(),
            ];
        });

        // Optimized: Eager load with specific columns
        $deployments = Deployment::with([
            'project:id,name,slug',
            'server:id,name',
            'user:id,name',
        ])
            ->select([
                'id', 'project_id', 'server_id', 'user_id', 'status', 'branch',
                'commit_message', 'commit_hash', 'started_at', 'completed_at',
                'triggered_by', 'created_at', 'updated_at',
            ])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn ($query) => $query->where('project_id', $this->projectFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('commit_message', 'like', '%'.$this->search.'%')
                        ->orWhere('branch', 'like', '%'.$this->search.'%')
                        ->orWhereHas('project', fn ($project) => $project->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        // Optimized: Cache projects list for 10 minutes
        $projects = Cache::remember('projects_dropdown_list', 600, function () {
            return Project::orderBy('name')->get(['id', 'name']);
        });

        return view('livewire.deployments.deployment-list', [
            'deployments' => $deployments,
            'projects' => $projects,
            'stats' => $stats,
        ]);
    }
}
