<?php

namespace App\Livewire\Home;

use App\Models\Project;
use Livewire\Attributes\Url;
use Livewire\Component;

class HomePublic extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $framework = '';

    public function mount()
    {
        // Initial load handled by computed property
    }

    public function getProjectsProperty()
    {
        // Get all running projects with domains only (security: no internal infrastructure exposed)
        // Domain info is in the separate domains table, use whereHas to filter
        return Project::query()
            ->where('status', 'running')
            ->whereHas('domains', function ($query) {
                $query->where('is_primary', true)
                    ->whereNotNull('domain')
                    ->where('domain', '!=', '');
            })
            ->with(['domains' => function ($query) {
                $query->where('is_primary', true);
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('framework', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->framework, function ($query) {
                $query->where('framework', $this->framework);
            })
            ->orderBy('name')
            ->get();
    }

    public function getFrameworksProperty()
    {
        // Get unique frameworks from running projects with domains
        return Project::query()
            ->where('status', 'running')
            ->whereHas('domains', function ($query) {
                $query->where('is_primary', true)
                    ->whereNotNull('domain')
                    ->where('domain', '!=', '');
            })
            ->distinct()
            ->pluck('framework')
            ->filter()
            ->sort()
            ->values();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->framework = '';
    }

    public function render()
    {
        return view('livewire.home.home-public', [
            'projects' => $this->projects,
            'frameworks' => $this->frameworks,
        ])->layout('layouts.marketing');
    }
}
