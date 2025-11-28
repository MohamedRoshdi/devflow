<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use App\Services\DockerService;
use App\Services\DockerInstallationService;
use Livewire\Attributes\On;

class ServerShow extends Component
{
    public Server $server;
    public $recentMetrics = [];

    public function mount(Server $server)
    {
        // Check if server belongs to current user
        if ($server->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this server.');
        }
        
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

            // Check Docker status
            $this->checkDockerStatus();

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

    public function checkDockerStatus()
    {
        try {
            $dockerService = app(DockerService::class);
            $dockerCheck = $dockerService->checkDockerInstallation($this->server);

            if ($dockerCheck['installed']) {
                $this->server->update([
                    'docker_installed' => true,
                    'docker_version' => $dockerCheck['version'] ?? 'unknown',
                ]);
                session()->flash('message', 'Docker detected! Version: ' . ($dockerCheck['version'] ?? 'unknown'));
            } else {
                $this->server->update([
                    'docker_installed' => false,
                    'docker_version' => null,
                ]);
                session()->flash('error', 'Docker not found on this server');
            }

            $this->server->refresh();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to check Docker: ' . $e->getMessage());
        }
    }

    public function installDocker()
    {
        try {
            session()->flash('info', 'Installing Docker... This may take a few minutes.');

            $installationService = app(DockerInstallationService::class);
            $result = $installationService->installDocker($this->server);

            // Clear the info message first
            session()->forget('info');

            if ($result['success']) {
                $this->server->refresh();
                session()->flash('message', $result['message'] . ' Version: ' . ($result['version'] ?? 'unknown'));
                $this->dispatch('docker-installed');
            } else {
                session()->flash('error', $result['message']);
            }

        } catch (\Exception $e) {
            session()->forget('info');
            session()->flash('error', 'Docker installation failed: ' . $e->getMessage());
        }
    }

    public function rebootServer()
    {
        try {
            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->rebootServer($this->server);

            if ($result['success']) {
                $this->server->refresh();
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Reboot failed: ' . $e->getMessage());
        }
    }

    public function restartService(string $service)
    {
        try {
            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->restartService($this->server, $service);

            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restart service: ' . $e->getMessage());
        }
    }

    public function clearSystemCache()
    {
        try {
            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->clearSystemCache($this->server);

            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function getServerStats(): array
    {
        $connectivityService = app(ServerConnectivityService::class);

        return [
            'uptime' => $connectivityService->getUptime($this->server),
            'disk' => $connectivityService->getDiskUsage($this->server),
            'memory' => $connectivityService->getMemoryUsage($this->server),
        ];
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

