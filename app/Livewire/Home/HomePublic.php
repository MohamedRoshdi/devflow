<?php

namespace App\Livewire\Home;

use App\Models\Project;
use Livewire\Component;

class HomePublic extends Component
{
    public $projects;

    public function mount()
    {
        // Get all running projects with their servers
        $this->projects = Project::with('server')
            ->where('status', 'running')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.home.home-public')
            ->layout('layouts.guest'); // Use guest layout (no sidebar/nav)
    }
}
