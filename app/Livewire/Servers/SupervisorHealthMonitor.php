<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\Monitoring\SupervisorHealthService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Embeddable widget that shows supervisor process health for a given server.
 *
 * Displays all processes with color-coded status indicators and allows
 * one-click restart of FATAL/STOPPED/BACKOFF processes.
 */
class SupervisorHealthMonitor extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public Server $server;

    /** @var array<int, array{name: string, status: string, pid: string|null, uptime: string|null}> */
    public array $processes = [];

    public bool $loading = false;

    public string $lastCheckedAt = '';

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->refresh();
    }

    /**
     * Reload process status from the server via SSH.
     */
    public function refresh(): void
    {
        $this->loading = true;

        try {
            $service = app(SupervisorHealthService::class);
            $this->processes = $service->checkProcesses($this->server);
            $this->lastCheckedAt = now()->format('H:i:s');
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Restart a specific process or group.
     */
    public function restart(string $processName): void
    {
        $this->authorize('update', $this->server);

        // Validate against loaded process names to prevent injection
        $knownNames = array_column($this->processes, 'name');
        if (! in_array($processName, $knownNames, true)) {
            session()->flash('error', "Unknown process: {$processName}");

            return;
        }

        try {
            $service = app(SupervisorHealthService::class);
            $success = $service->restartProcess($this->server, $processName);

            if ($success) {
                session()->flash('message', "Process '{$processName}' restart initiated.");
            } else {
                session()->flash('error', "Failed to restart '{$processName}'. Check server logs.");
            }

            $this->refresh();
        } catch (\Exception $e) {
            session()->flash('error', "Error restarting '{$processName}': {$e->getMessage()}");
        }
    }

    /**
     * Whether any process is in an unhealthy state.
     */
    public function hasUnhealthy(): bool
    {
        return collect($this->processes)->contains(
            fn (array $p): bool => in_array($p['status'], SupervisorHealthService::UNHEALTHY_STATUSES, true)
        );
    }

    public function render(): View
    {
        return view('livewire.servers.supervisor-health-monitor');
    }
}
