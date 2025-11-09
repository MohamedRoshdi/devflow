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
        $this->stats = [
            'total_servers' => Server::where('user_id', auth()->id())->count(),
            'online_servers' => Server::where('user_id', auth()->id())->where('status', 'online')->count(),
            'total_projects' => Project::where('user_id', auth()->id())->count(),
            'running_projects' => Project::where('user_id', auth()->id())->where('status', 'running')->count(),
            'total_deployments' => Deployment::where('user_id', auth()->id())->count(),
            'successful_deployments' => Deployment::where('user_id', auth()->id())->where('status', 'success')->count(),
            'failed_deployments' => Deployment::where('user_id', auth()->id())->where('status', 'failed')->count(),
        ];
    }

    public function loadRecentDeployments()
    {
        $this->recentDeployments = Deployment::with(['project', 'server'])
            ->where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();
    }

    public function loadProjects()
    {
        $this->projects = Project::with(['server', 'domains'])
            ->where('user_id', auth()->id())
            ->latest()
            ->take(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

