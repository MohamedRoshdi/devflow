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
        $this->projects = Project::query()
            ->where('status', 'running')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.home.home-public')
            ->layout('layouts.marketing');
    }
}
