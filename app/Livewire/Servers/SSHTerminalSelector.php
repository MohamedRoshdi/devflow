<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use Livewire\Component;

class SSHTerminalSelector extends Component
{
    public ?int $selectedServerId = null;

    public function selectServer(int $serverId): void
    {
        $this->selectedServerId = $serverId;
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

        return view('livewire.servers.s-s-h-terminal-selector', [
            'servers' => $servers,
            'selectedServer' => $selectedServer,
        ]);
    }
}
