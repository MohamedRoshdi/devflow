<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServerEdit extends Component
{
    use AuthorizesRequests;

    public Server $server;

    public $name = '';
    public $hostname = '';
    public $ip_address = '';
    public $port = 22;
    public $username = 'root';
    public $ssh_password = '';
    public $ssh_key = '';
    public $auth_method = 'password';
    public $latitude = null;
    public $longitude = null;
    public $location_name = '';

    public function mount(Server $server): void
    {
        $this->authorize('update', $server);

        $this->server = $server;
        $this->name = $server->name;
        $this->hostname = $server->hostname ?? '';
        $this->ip_address = $server->ip_address;
        $this->port = $server->port ?? 22;
        $this->username = $server->username ?? 'root';
        $this->latitude = $server->latitude;
        $this->longitude = $server->longitude;
        $this->location_name = $server->location_name ?? '';

        // Determine auth method based on existing data
        $this->auth_method = $server->ssh_key ? 'key' : 'password';
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'ssh_password' => 'nullable|string',
            'ssh_key' => 'nullable|string',
            'auth_method' => 'required|in:password,key',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
        ];
    }

    public function testConnection()
    {
        $this->validate();

        try {
            $tempServer = new Server([
                'ip_address' => $this->ip_address,
                'port' => $this->port,
                'username' => $this->username,
                'ssh_password' => $this->auth_method === 'password' && $this->ssh_password
                    ? $this->ssh_password
                    : $this->server->ssh_password,
                'ssh_key' => $this->auth_method === 'key' && $this->ssh_key
                    ? $this->ssh_key
                    : $this->server->ssh_key,
            ]);

            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->testConnection($tempServer);

            if ($result['reachable']) {
                session()->flash('connection_test', $result['message'] . ' (Latency: ' . $result['latency_ms'] . 'ms)');
            } else {
                session()->flash('connection_error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('connection_error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function updateServer()
    {
        $this->validate();

        $updateData = [
            'name' => $this->name,
            'hostname' => $this->hostname ?: null,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'username' => $this->username,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
        ];

        // Only update credentials if new ones are provided
        if ($this->auth_method === 'password' && $this->ssh_password) {
            $updateData['ssh_password'] = $this->ssh_password;
            $updateData['ssh_key'] = null;
        } elseif ($this->auth_method === 'key' && $this->ssh_key) {
            $updateData['ssh_key'] = $this->ssh_key;
            $updateData['ssh_password'] = null;
        }

        $this->server->update($updateData);

        // Test connectivity and update status
        $connectivityService = app(ServerConnectivityService::class);
        $isReachable = $connectivityService->pingAndUpdateStatus($this->server);

        // Get server information
        if ($isReachable) {
            $serverInfo = $connectivityService->getServerInfo($this->server);

            if (!empty($serverInfo)) {
                $this->server->update([
                    'os' => $serverInfo['os'] ?? $this->server->os,
                    'cpu_cores' => $serverInfo['cpu_cores'] ?? $this->server->cpu_cores,
                    'memory_gb' => $serverInfo['memory_gb'] ?? $this->server->memory_gb,
                    'disk_gb' => $serverInfo['disk_gb'] ?? $this->server->disk_gb,
                ]);
            }
        }

        // Check Docker installation
        try {
            $dockerService = app(DockerService::class);
            $dockerInfo = $dockerService->checkDockerInstallation($this->server);

            $this->server->update([
                'docker_installed' => $dockerInfo['installed'],
                'docker_version' => $dockerInfo['version'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Docker not installed or not accessible
        }

        $message = $isReachable
            ? 'Server updated successfully and is online!'
            : 'Server updated but appears offline. Check SSH credentials.';

        return redirect()->route('servers.show', $this->server)
            ->with('message', $message);
    }

    public function render()
    {
        return view('livewire.servers.server-edit');
    }
}
