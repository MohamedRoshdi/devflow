<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\Project;
use App\Models\Deployment;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public $stats = [];
    public $recentDeployments = [];
    public $serverMetrics = [];
    public $projects = [];

    public function mount()
    {
        $this->loadStats();
        $this->loadRecentDeployments();
        $this->loadProjects();
    }

    #[On('refresh-dashboard')]
    public function loadStats()
    {
        // All resources are shared across all users
        $this->stats = [
            'total_servers' => Server::count(),
            'online_servers' => Server::where('status', 'online')->count(),
            'total_projects' => Project::count(),
            'running_projects' => Project::where('status', 'running')->count(),
            'total_deployments' => Deployment::count(),
            'successful_deployments' => Deployment::where('status', 'success')->count(),
            'failed_deployments' => Deployment::where('status', 'failed')->count(),
        ];
    }

    public function loadRecentDeployments()
    {
        // All deployments are shared
        $this->recentDeployments = Deployment::with(['project', 'server'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function loadProjects()
    {
        // All projects are shared
        $this->projects = Project::with(['server', 'domains'])
            ->latest()
            ->take(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

