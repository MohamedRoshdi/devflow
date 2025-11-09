<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerConnectivityService;
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
        $connectivityService = app(ServerConnectivityService::class);
        $result = $connectivityService->testConnection($this->server);

        if ($result['reachable']) {
            $this->server->update([
                'last_ping_at' => now(),
                'status' => 'online',
            ]);

            // Try to update server info
            $serverInfo = $connectivityService->getServerInfo($this->server);
            if (!empty($serverInfo)) {
                $this->server->update([
                    'os' => $serverInfo['os'] ?? $this->server->os,
                    'cpu_cores' => $serverInfo['cpu_cores'] ?? $this->server->cpu_cores,
                    'memory_gb' => $serverInfo['memory_gb'] ?? $this->server->memory_gb,
                    'disk_gb' => $serverInfo['disk_gb'] ?? $this->server->disk_gb,
                ]);
            }

            session()->flash('message', 'Server is online! ' . $result['message']);
        } else {
            $this->server->update([
                'last_ping_at' => now(),
                'status' => 'offline',
            ]);

            session()->flash('error', 'Server appears offline: ' . $result['message']);
        }

        // Refresh server data
        $this->server->refresh();
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

