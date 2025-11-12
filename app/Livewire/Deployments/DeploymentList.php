<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use App\Models\Deployment;
use App\Models\Project;
use Livewire\WithPagination;

class DeploymentList extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $projectFilter = '';
    public string $search = '';
    public int $perPage = 15;

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'projectFilter' => ['except' => ''],
        'search' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    protected $paginationTheme = 'tailwind';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
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
        $baseQuery = Deployment::with(['project', 'server'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn ($query) => $query->where('project_id', $this->projectFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('commit_message', 'like', '%' . $this->search . '%')
                      ->orWhere('branch', 'like', '%' . $this->search . '%')
                      ->orWhereHas('project', fn ($project) => $project->where('name', 'like', '%' . $this->search . '%'));
                });
            });

        $statsQuery = clone $baseQuery;

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'success' => (clone $statsQuery)->where('status', 'success')->count(),
            'failed' => (clone $statsQuery)->where('status', 'failed')->count(),
            'running' => (clone $statsQuery)->where('status', 'running')->count(),
        ];

        $deployments = $baseQuery
            ->latest()
            ->paginate($this->perPage);

        $projects = Project::where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.deployments.deployment-list', [
            'deployments' => $deployments,
            'projects' => $projects,
            'stats' => $stats,
        ]);
    }
}

