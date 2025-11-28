<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use App\Services\DockerService;
use App\Services\DockerInstallationService;
use App\Jobs\InstallDockerJob;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class ServerShow extends Component
{
    public Server $server;
    public $recentMetrics = [];
    public bool $dockerInstalling = false;
    public ?array $dockerInstallStatus = null;

    public function mount(Server $server)
    {
        // Check if server belongs to current user
        if ($server->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this server.');
        }

        $this->server = $server;
        $this->loadMetrics();
        $this->checkDockerInstallProgress();
    }

    /**
     * Check Docker installation progress (called by polling)
     */
    public function checkDockerInstallProgress(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        if ($status) {
            $this->dockerInstallStatus = $status;
            $this->dockerInstalling = ($status['status'] === 'installing');

            // If completed or failed, show message and clear after viewing
            if ($status['status'] === 'completed') {
                $this->server->refresh();
                session()->flash('message', $status['message'] . (isset($status['version']) ? ' Version: ' . $status['version'] : ''));
                // Keep in cache briefly so user sees it
            } elseif ($status['status'] === 'failed') {
                session()->flash('error', $status['message']);
            }
        } else {
            $this->dockerInstalling = false;
            $this->dockerInstallStatus = null;
        }
    }

    /**
     * Clear Docker installation status
     */
    public function clearDockerInstallStatus(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        Cache::forget($cacheKey);
        $this->dockerInstalling = false;
        $this->dockerInstallStatus = null;
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
            // Check if already installing
            $cacheKey = "docker_install_{$this->server->id}";
            $existingStatus = Cache::get($cacheKey);

            if ($existingStatus && $existingStatus['status'] === 'installing') {
                session()->flash('info', 'Docker installation is already in progress...');
                return;
            }

            // Set initial status
            Cache::put($cacheKey, [
                'status' => 'installing',
                'message' => 'Starting Docker installation...',
                'progress' => 5,
                'started_at' => now()->toISOString(),
            ], 3600);

            $this->dockerInstalling = true;
            $this->dockerInstallStatus = Cache::get($cacheKey);

            // Dispatch the job to run in background
            // Use sync driver if queue is not configured (will still work but blocks)
            if (config('queue.default') === 'sync') {
                // For sync queue, run directly but without blocking the request
                // by using dispatchAfterResponse
                InstallDockerJob::dispatchAfterResponse($this->server);
                session()->flash('info', 'Docker installation started! Please wait while installation completes...');
            } else {
                InstallDockerJob::dispatch($this->server);
                session()->flash('info', 'Docker installation started! This runs in the background and may take several minutes. The page will update automatically when complete.');
            }

        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            $this->dockerInstalling = false;
            session()->flash('error', 'Failed to start Docker installation: ' . $e->getMessage());
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

