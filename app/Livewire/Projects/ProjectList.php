<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ProjectList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    #[On('project-created')]
    public function refreshProjects()
    {
        $this->resetPage();
    }

    public function deleteProject($projectId)
    {
        $project = Project::where('id', $projectId)->where('user_id', auth()->id())->first();
        
        if ($project) {
            $project->delete();
            session()->flash('message', 'Project deleted successfully');
        }
    }

    public function render()
    {
        $projects = Project::with(['server', 'domains'])
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->where(function($q) {
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

