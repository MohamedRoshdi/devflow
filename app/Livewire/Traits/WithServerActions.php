<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Facades\Cache;

/**
 * Server Actions Trait
 *
 * Provides reusable server action methods including ping, reboot, and server detection.
 * Used by components that need to interact with servers.
 */
trait WithServerActions
{
    public bool $isPingingAll = false;

    /**
     * Ping all servers to update their status (runs in background)
     *
     * Automatically runs after page load without user interaction.
     *
     * @return void
     */
    public function pingAllServersInBackground(): void
    {
        if ($this->accessibleServers->isEmpty()) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);

        foreach ($this->accessibleServers as $server) {
            // Quick ping to update status (async-like behavior with timeout)
            $connectivityService->pingAndUpdateStatus($server);
        }
    }

    /**
     * Ping all servers (manual trigger with loading indicator)
     *
     * User-initiated action that displays loading state and result summary.
     *
     * @return void
     */
    public function pingAllServers(): void
    {
        $this->isPingingAll = true;

        $connectivityService = app(ServerConnectivityService::class);

        $online = 0;
        $offline = 0;

        foreach ($this->accessibleServers as $server) {
            $result = $connectivityService->pingAndUpdateStatus($server);
            if ($result) {
                $online++;
            } else {
                $offline++;
            }
        }

        $this->isPingingAll = false;
        unset($this->accessibleServers); // Clear cache to get updated status
        session()->flash('message', "Status updated: {$online} online, {$offline} offline");
    }

    /**
     * Ping single server
     *
     * Tests connectivity to a specific server and updates its status.
     *
     * @param int $serverId The ID of the server to ping
     * @return void
     */
    public function pingServer(int $serverId): void
    {
        $server = Server::find($serverId);

        if (! $server) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);
        $result = $connectivityService->testConnection($server);

        $server->update([
            'status' => $result['reachable'] ? 'online' : 'offline',
            'last_ping_at' => now(),
        ]);

        // Clear caches after status update
        unset($this->accessibleServers, $this->serversQuery);

        if ($result['reachable']) {
            session()->flash('message', "{$server->name} is online");
        } else {
            session()->flash('error', "{$server->name} is offline: ".($result['message'] ?? 'Connection failed'));
        }
    }

    /**
     * Reboot a server
     *
     * Initiates a server reboot via SSH connection.
     *
     * @param int $serverId The ID of the server to reboot
     * @return void
     */
    public function rebootServer(int $serverId): void
    {
        $server = Server::find($serverId);

        if (! $server) {
            return;
        }

        $connectivityService = app(ServerConnectivityService::class);
        $result = $connectivityService->rebootServer($server);

        // Clear caches after reboot
        unset($this->accessibleServers, $this->serversQuery);

        if ($result['success']) {
            session()->flash('message', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
    }

    /**
     * Add the current VPS server to the server list
     *
     * Automatically detects current server IP and adds it to the system.
     * Useful for self-hosted deployments.
     *
     * @return void
     */
    public function addCurrentServer(): void
    {
        try {
            // Get current server IP
            $currentIP = $this->getCurrentServerIP();

            // Check if already added
            $exists = Server::where('ip_address', $currentIP)->exists();

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

            if (! empty($serverInfo)) {
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
            session()->flash('error', 'Failed to add current server: '.$e->getMessage());
        }
    }

    /**
     * Delete a server from the system
     *
     * Removes server and clears related caches.
     *
     * @param int $serverId The ID of the server to delete
     * @return void
     */
    public function deleteServer(int $serverId): void
    {
        $server = Server::find($serverId);

        if (! $server) {
            return;
        }

        $server->delete();

        // Clear caches after deletion
        unset($this->accessibleServers, $this->serversQuery);
        Cache::forget('server_tags_list');
        unset($this->allTags);

        session()->flash('message', 'Server deleted successfully');
    }

    /**
     * Get the current server's IP address
     *
     * Tries multiple methods to detect the server's public IP address.
     *
     * @return string The server IP address or '127.0.0.1' as fallback
     */
    protected function getCurrentServerIP(): string
    {
        // Try multiple methods to get the server's IP

        // Method 1: Check SERVER_ADDR
        if (! empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
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
}
