<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

/**
 * Shared deployment filtering for list components.
 *
 * Provides common filter properties, pagination reset hooks, and project dropdown data.
 * Components using this trait should implement WithPagination for pagination reset to work.
 */
trait WithDeploymentFiltering
{
    public string $search = '';

    public string $statusFilter = '';

    public string|int|null $projectFilter = null;

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when status filter changes
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when project filter changes
     */
    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters and reset pagination
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->projectFilter = null;
        $this->resetPage();
    }

    /**
     * Get available projects for the filter dropdown
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function filterProjects(): Collection
    {
        $user = auth()->user();
        if ($user === null) {
            return collect();
        }

        $userId = $user->id;

        // Cache as array of stdClass objects to avoid serialization issues with Eloquent models
        $cached = Cache::remember('projects_dropdown_list_user_'.$userId, 600, function () use ($userId) {
            return Project::where('user_id', $userId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
                ->toArray();
        });

        // Convert back to objects for consistent access in blade templates
        return collect($cached)->map(fn ($p) => (object) $p);
    }

    /**
     * Get available deployment statuses for the filter dropdown
     *
     * @return array<string, string>
     */
    public function getDeploymentStatusesProperty(): array
    {
        return [
            '' => 'All Statuses',
            'pending' => 'Pending',
            'running' => 'Running',
            'success' => 'Success',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'rolled_back' => 'Rolled Back',
        ];
    }

    /**
     * Check if any filters are active
     */
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->statusFilter !== ''
            || $this->projectFilter !== null && $this->projectFilter !== '';
    }

    /**
     * Apply common deployment search filter to a query
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>
     */
    protected function applyDeploymentSearch(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->search === '') {
            return $query;
        }

        return $query->where(function ($q) {
            $q->where('commit_message', 'like', '%'.$this->search.'%')
                ->orWhere('branch', 'like', '%'.$this->search.'%')
                ->orWhereHas('project', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%'));
        });
    }

    /**
     * Apply status filter to a deployment query
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>
     */
    protected function applyDeploymentStatusFilter(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->statusFilter === '') {
            return $query;
        }

        return $query->where('status', $this->statusFilter);
    }

    /**
     * Apply project filter to a deployment query
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>
     */
    protected function applyDeploymentProjectFilter(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->projectFilter === null || $this->projectFilter === '') {
            return $query;
        }

        return $query->where('project_id', $this->projectFilter);
    }

    /**
     * Apply all deployment filters to a query
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Deployment>
     */
    protected function applyAllDeploymentFilters(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->applyDeploymentSearch($query);
        $query = $this->applyDeploymentStatusFilter($query);
        $query = $this->applyDeploymentProjectFilter($query);

        return $query;
    }
}
