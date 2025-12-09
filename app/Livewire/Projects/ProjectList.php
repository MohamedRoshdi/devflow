<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    #[On('project-created')]
    public function refreshProjects()
    {
        $this->resetPage();
    }

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

    public function render()
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
            ->latest()
            ->paginate(12);

        return view('livewire.projects.project-list', [
            'projects' => $projects,
        ]);
    }
}
