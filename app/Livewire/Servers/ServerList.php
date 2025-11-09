<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ServerList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    #[On('server-created')]
    public function refreshServers()
    {
        $this->resetPage();
    }

    public function deleteServer($serverId)
    {
        $server = Server::where('id', $serverId)->where('user_id', auth()->id())->first();
        
        if ($server) {
            $server->delete();
            session()->flash('message', 'Server deleted successfully');
        }
    }

    public function render()
    {
        $servers = Server::where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('hostname', 'like', '%'.$this->search.'%')
                      ->orWhere('ip_address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.servers.server-list', [
            'servers' => $servers,
        ]);
    }
}

