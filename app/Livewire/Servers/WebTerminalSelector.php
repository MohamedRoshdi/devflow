<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use Livewire\Component;

class WebTerminalSelector extends Component
{
    public ?int $selectedServerId = null;

    public function mount(): void
    {
        // Auto-select first server if only one exists
        $servers = Server::query()
            ->where('status', '!=', 'deleted')
            ->get();

        if ($servers->count() === 1) {
            $this->selectedServerId = $servers->first()->id;
        }
    }

    public function selectServer(int $serverId): void
    {
        $this->selectedServerId = $serverId;
    }

    public function clearSelection(): void
    {
        $this->selectedServerId = null;
    }

    public function render(): \Illuminate\View\View
    {
        $servers = Server::query()
            ->where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get();

        $selectedServer = $this->selectedServerId
            ? Server::find($this->selectedServerId)
            : null;

        return view('livewire.servers.web-terminal-selector', [
            'servers' => $servers,
            'selectedServer' => $selectedServer,
        ]);
    }
}
