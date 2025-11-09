<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerConnectivityService;
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

    public function addCurrentServer()
    {
        try {
            // Get current server IP
            $currentIP = $this->getCurrentServerIP();
            
            // Check if already added
            $exists = Server::where('user_id', auth()->id())
                ->where('ip_address', $currentIP)
                ->exists();
            
            if ($exists) {
                session()->flash('error', 'This server is already added!');
                return;
            }
            
            // Create server for current VPS
            $server = Server::create([
                'user_id' => auth()->id(),
                'name' => 'Current VPS Server',
                'hostname' => gethostname() ?: 'localhost',
                'ip_address' => $currentIP,
                'port' => 22,
                'username' => 'root',
                'status' => 'online', // It's the current server, so it's definitely online
                'last_ping_at' => now(),
            ]);

            // Get server info
            $connectivityService = app(ServerConnectivityService::class);
            $serverInfo = $connectivityService->getServerInfo($server);
            
            if (!empty($serverInfo)) {
                $server->update([
                    'os' => $serverInfo['os'] ?? null,
                    'cpu_cores' => $serverInfo['cpu_cores'] ?? null,
                    'memory_gb' => $serverInfo['memory_gb'] ?? null,
                    'disk_gb' => $serverInfo['disk_gb'] ?? null,
                ]);
            }

            session()->flash('message', 'Current server added successfully!');
            $this->dispatch('server-created');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add current server: ' . $e->getMessage());
        }
    }

    public function deleteServer($serverId)
    {
        $server = Server::where('id', $serverId)->where('user_id', auth()->id())->first();
        
        if ($server) {
            $server->delete();
            session()->flash('message', 'Server deleted successfully');
        }
    }

    protected function getCurrentServerIP(): string
    {
        // Try multiple methods to get the server's IP
        
        // Method 1: Check SERVER_ADDR
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }

        // Method 2: Get from hostname
        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip !== $hostname && $ip !== '127.0.0.1') {
            return $ip;
        }

        // Method 3: Try to get public IP
        try {
            $publicIP = trim(file_get_contents('http://api.ipify.org'));
            if ($publicIP) {
                return $publicIP;
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Fallback to localhost
        return '127.0.0.1';
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

