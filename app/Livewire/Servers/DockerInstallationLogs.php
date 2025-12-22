<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class DockerInstallationLogs extends Component
{
    public Server $server;

    public bool $isVisible = false;

    /** @var array<int, string> */
    public array $logs = [];

    public string $status = 'idle';

    public int $progress = 0;

    public string $currentStep = '';

    public ?string $errorMessage = null;

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->refreshStatus();
    }

    /**
     * Refresh installation status and logs
     */
    public function refreshStatus(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $status = Cache::get($cacheKey);

        if ($status) {
            $this->status = $status['status'] ?? 'idle';
            $this->progress = $status['progress'] ?? 0;
            $this->currentStep = $status['current_step'] ?? '';
            $this->errorMessage = $status['error'] ?? null;
            $this->isVisible = $this->status === 'installing';
        } else {
            $this->status = 'idle';
            $this->progress = 0;
            $this->isVisible = false;
        }

        // Fetch logs
        $logsKey = "docker_install_logs_{$this->server->id}";
        $this->logs = Cache::get($logsKey, []);
    }

    /**
     * Get the latest logs (called via polling)
     */
    public function pollLogs(): void
    {
        $this->refreshStatus();

        // Auto-hide when completed or failed after a delay
        if (in_array($this->status, ['completed', 'failed'])) {
            // Keep visible for user to see final result
        }
    }

    /**
     * Show the log viewer
     */
    #[On('docker-installation-started')]
    public function show(): void
    {
        $this->refreshStatus();
        $this->isVisible = true;
    }

    /**
     * Hide the log viewer
     */
    public function hide(): void
    {
        $this->isVisible = false;
    }

    /**
     * Clear logs and close
     */
    public function clearAndClose(): void
    {
        $cacheKey = "docker_install_{$this->server->id}";
        $logsKey = "docker_install_logs_{$this->server->id}";

        Cache::forget($cacheKey);
        Cache::forget($logsKey);

        $this->logs = [];
        $this->status = 'idle';
        $this->progress = 0;
        $this->isVisible = false;

        $this->dispatch('docker-installation-cleared');
    }

    public function render(): View
    {
        return view('livewire.servers.docker-installation-logs');
    }
}
