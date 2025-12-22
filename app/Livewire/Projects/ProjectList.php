<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Project List Component
 *
 * Displays a paginated list of projects with search and filter capabilities.
 * Provides project management actions including deletion with proper authorization checks.
 *
 * @property string $search Search term for filtering projects by name or slug
 * @property string $statusFilter Filter projects by status (active, inactive, etc.)
 * @property string $serverFilter Filter projects by server ID
 */
class ProjectList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $serverFilter = '';

    // Injected services
    protected CacheRepository $cache;

    /**
     * Boot method for Livewire 3 dependency injection
     */
    public function boot(CacheRepository $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Refresh the project list when a new project is created
     *
     * @return void
     */
    #[On('project-created')]
    public function refreshProjects()
    {
        $this->resetPage();
    }

    /**
     * Delete a project with proper authorization checks
     *
     * Only project owners can delete projects.
     * Validates user permissions via ProjectPolicy before deletion.
     *
     * @param int $projectId The ID of the project to delete
     * @return void
     */
    public function deleteProject(int $projectId): void
    {
        try {
            $project = Project::with(['server', 'user', 'domains'])->find($projectId);

            if (! $project) {
                session()->flash('error', 'Project not found');
                return;
            }

            $this->authorize('delete', $project);

            $projectName = $project->name;
            $project->delete();
            session()->flash('message', "Project '{$projectName}' deleted successfully");
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', 'You do not have permission to delete this project');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to delete project: ' . $e->getMessage());
            report($e);
        }
    }

    /**
     * Get list of servers for the filter dropdown (cached for 5 minutes)
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Server>
     */
    #[Computed]
    public function servers()
    {
        return $this->cache->remember('servers_list', 300, function () {
            return \App\Models\Server::select(['id', 'name'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Render the project list view with pagination
     *
     * Eager loads relationships and applies search/filter criteria.
     * Returns paginated results with optimized queries.
     *
     * @return \Illuminate\View\View
     */
    public function render(): \Illuminate\View\View
    {
        // Optimized: Eager load relationships and select specific columns to reduce memory
        $projects = Project::with([
            'server:id,name,status',
            'domains:id,project_id,domain',
            'user:id,name',
        ])
            ->select(['id', 'name', 'slug', 'status', 'server_id', 'user_id', 'framework', 'created_at', 'updated_at'])
            ->when($this->search, function ($query) {
                $searchTerm = '%'.strtolower($this->search).'%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(slug) LIKE ?', [$searchTerm]);
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->serverFilter, function ($query) {
                $query->where('server_id', $this->serverFilter);
            })
            ->latest()
            ->paginate(12);

        return view('livewire.projects.project-list', [
            'projects' => $projects,
        ]);
    }
}
