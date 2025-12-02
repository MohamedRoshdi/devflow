<?php

namespace App\Livewire\Home;

use App\Models\Project;
use Livewire\Component;

class HomePublic extends Component
{
    public $projects;

    public function mount()
    {
        // Get all running projects with domains only (security: no internal infrastructure exposed)
        // Domain info is in the separate domains table, use whereHas to filter
        $this->projects = Project::query()
            ->where('status', 'running')
            ->whereHas('domains', function ($query) {
                $query->where('is_primary', true)
                      ->whereNotNull('domain')
                      ->where('domain', '!=', '');
            })
            ->with(['domains' => function ($query) {
                $query->where('is_primary', true);
            }])
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.home.home-public')
            ->layout('layouts.marketing');
    }
}
