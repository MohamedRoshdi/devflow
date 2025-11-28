<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use App\Services\BulkServerActionService;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ServerList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $tagFilter = [];
    public bool $isPingingAll = false;

    // Bulk action properties
    public array $selectedServers = [];
    public bool $selectAll = false;
    public bool $bulkActionInProgress = false;
    public array $bulkActionResults = [];
    public bool $showResultsModal = false;

    public function mount(): void
    {
        // Auto-ping all servers on page load to get current status
        $this->pingAllServersInBackground();
    }

    #[On('server-created')]
    public function refreshServers()
    {
        $this->resetPage();
    }

    /**
     * Ping all servers to update their status (runs in background)
     */
    public function pingAllServersInBackground(): void
    {
        $servers = Server::where('user_id', auth()->id())->get();

        if ($servers->isEmpty()) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);

        foreach ($servers as $server) {
            // Quick ping to update status (async-like behavior with timeout)
            $connectivityService->pingAndUpdateStatus($server);
        }
    }

    /**
     * Ping all servers (manual trigger with loading indicator)
     */
    public function pingAllServers(): void
    {
        $this->isPingingAll = true;

        $servers = Server::where('user_id', auth()->id())->get();
        $connectivityService = app(ServerConnectivityService::class);

        $online = 0;
        $offline = 0;

        foreach ($servers as $server) {
            $result = $connectivityService->pingAndUpdateStatus($server);
            if ($result) {
                $online++;
            } else {
                $offline++;
            }
        }

        $this->isPingingAll = false;
        session()->flash('message', "Status updated: {$online} online, {$offline} offline");
    }

    /**
     * Ping single server
     */
    public function pingServer(int $serverId): void
    {
        $server = Server::where('id', $serverId)->where('user_id', auth()->id())->first();

        if (!$server) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);
        $result = $connectivityService->testConnection($server);

        $server->update([
            'status' => $result['reachable'] ? 'online' : 'offline',
            'last_ping_at' => now(),
        ]);

        if ($result['reachable']) {
            session()->flash('message', "{$server->name} is online");
        } else {
            session()->flash('error', "{$server->name} is offline: " . ($result['message'] ?? 'Connection failed'));
        }
    }

    /**
     * Reboot a server
     */
    public function rebootServer(int $serverId): void
    {
        $server = Server::where('id', $serverId)->where('user_id', auth()->id())->first();

        if (!$server) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);
        $result = $connectivityService->rebootServer($server);

        if ($result['success']) {
            session()->flash('message', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
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

    /**
     * Toggle server selection
     */
    public function toggleServerSelection(int $serverId): void
    {
        if (in_array($serverId, $this->selectedServers)) {
            $this->selectedServers = array_values(array_diff($this->selectedServers, [$serverId]));
        } else {
            $this->selectedServers[] = $serverId;
        }

        // Update selectAll state based on current selection
        $totalServersOnPage = $this->getServersQuery()->pluck('id')->toArray();
        $this->selectAll = count($this->selectedServers) > 0 &&
                          count(array_intersect($totalServersOnPage, $this->selectedServers)) === count($totalServersOnPage);
    }

    /**
     * Toggle select all servers on current page
     */
    public function toggleSelectAll(): void
    {
        $this->selectAll = !$this->selectAll;

        if ($this->selectAll) {
            // Select all servers on current page
            $serverIds = $this->getServersQuery()->pluck('id')->toArray();
            $this->selectedServers = array_unique(array_merge($this->selectedServers, $serverIds));
        } else {
            // Deselect all servers
            $this->selectedServers = [];
        }
    }

    /**
     * Clear all selections
     */
    public function clearSelection(): void
    {
        $this->selectedServers = [];
        $this->selectAll = false;
        $this->bulkActionResults = [];
        $this->showResultsModal = false;
    }

    /**
     * Bulk ping selected servers
     */
    public function bulkPing(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');
            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)
            ->where('user_id', auth()->id())
            ->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->pingServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        session()->flash('message', "Bulk ping completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk reboot selected servers
     */
    public function bulkReboot(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');
            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)
            ->where('user_id', auth()->id())
            ->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->rebootServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        session()->flash('message', "Bulk reboot initiated: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk install Docker on selected servers
     */
    public function bulkInstallDocker(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');
            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)
            ->where('user_id', auth()->id())
            ->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->installDockerOnServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        session()->flash('message', "Bulk Docker installation completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk restart service on selected servers
     */
    public function bulkRestartService(string $service): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');
            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)
            ->where('user_id', auth()->id())
            ->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->restartServiceOnServers($servers, $service);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        session()->flash('message', "Bulk {$service} restart completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Close results modal
     */
    public function closeResultsModal(): void
    {
        $this->showResultsModal = false;
    }

    /**
     * Toggle tag filter
     */
    public function toggleTagFilter(int $tagId): void
    {
        if (in_array($tagId, $this->tagFilter)) {
            $this->tagFilter = array_diff($this->tagFilter, [$tagId]);
        } else {
            $this->tagFilter[] = $tagId;
        }
        $this->resetPage();
    }

    /**
     * Get the base servers query
     */
    protected function getServersQuery()
    {
        return Server::where('user_id', auth()->id())
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
            ->when(!empty($this->tagFilter), function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('server_tags.id', $this->tagFilter);
                });
            })
            ->latest();
    }

    public function render()
    {
        $servers = $this->getServersQuery()->with('tags')->paginate(10);

        // Get all tags for filtering
        $allTags = \App\Models\ServerTag::where('user_id', auth()->id())
            ->withCount('servers')
            ->orderBy('name')
            ->get();

        return view('livewire.servers.server-list', [
            'servers' => $servers,
            'allTags' => $allTags,
        ]);
    }
}

