<?php

namespace App\Livewire\Deployments;

use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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

    public function render()
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
