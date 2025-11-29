<?php

namespace App\Livewire\Deployments;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Deployment;
use App\Models\Project;
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

    protected $paginationTheme = 'tailwind';

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
        // Stats query - all deployments are shared
        $stats = [
            'total' => Deployment::count(),
            'success' => Deployment::where('status', 'success')->count(),
            'failed' => Deployment::where('status', 'failed')->count(),
            'running' => Deployment::where('status', 'running')->count(),
        ];

        // Filtered query for the list - all deployments are shared
        $deployments = Deployment::with(['project', 'server', 'user'])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn ($query) => $query->where('project_id', $this->projectFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('commit_message', 'like', '%' . $this->search . '%')
                      ->orWhere('branch', 'like', '%' . $this->search . '%')
                      ->orWhereHas('project', fn ($project) => $project->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        // All projects are shared
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('livewire.deployments.deployment-list', [
            'deployments' => $deployments,
            'projects' => $projects,
            'stats' => $stats,
        ]);
    }
}

