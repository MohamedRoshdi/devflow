<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\On;
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
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $serverFilter = '';

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
     * Only project owners and team owners can delete projects.
     * Validates user permissions before deletion.
     *
     * @param int $projectId The ID of the project to delete
     * @return void
     */
    public function deleteProject(int $projectId): void
    {
        $project = Project::find($projectId);

        if (! $project) {
            session()->flash('error', 'Project not found');
            return;
        }

        // Authorization: Only project owner or team admin can delete
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        // Check if user owns the project
        if ($project->user_id !== $user->id) {
            // Check if user is a team owner/admin with access
            if ($project->team_id && $user->currentTeam && $user->currentTeam->id === $project->team_id) {
                // Team member - check if they are owner
                $teamMember = $user->currentTeam->members()->where('user_id', $user->id)->first();
                if (! $teamMember || $teamMember->pivot->role !== 'owner') {
                    session()->flash('error', 'You do not have permission to delete this project');
                    return;
                }
            } else {
                session()->flash('error', 'You do not have permission to delete this project');
                return;
            }
        }

        $project->delete();
        session()->flash('message', 'Project deleted successfully');
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
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
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

        // Get list of servers for the filter dropdown
        $servers = \App\Models\Server::select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('livewire.projects.project-list', [
            'projects' => $projects,
            'servers' => $servers,
        ]);
    }
}
