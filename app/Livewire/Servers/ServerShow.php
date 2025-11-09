<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use Livewire\Attributes\On;

class ServerShow extends Component
{
    public Server $server;
    public $recentMetrics = [];

    public function mount(Server $server)
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadMetrics();
    }

    #[On('metrics-updated')]
    public function loadMetrics()
    {
        $this->recentMetrics = $this->server->metrics()
            ->latest('recorded_at')
            ->take(20)
            ->get();
    }

    public function pingServer()
    {
        $this->server->update([
            'last_ping_at' => now(),
            'status' => 'online',
        ]);

        session()->flash('message', 'Server pinged successfully');
    }

    public function render()
    {
        $projects = $this->server->projects()->latest()->take(5)->get();
        $deployments = $this->server->deployments()->latest()->take(5)->get();

        return view('livewire.servers.server-show', [
            'projects' => $projects,
            'deployments' => $deployments,
        ]);
    }
}

