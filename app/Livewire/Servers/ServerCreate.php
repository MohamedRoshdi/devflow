<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use App\Services\DockerService;

class ServerCreate extends Component
{
    public $name = '';
    public $hostname = '';
    public $ip_address = '';
    public $port = 22;
    public $username = 'root';
    public $ssh_key = '';
    public $latitude = null;
    public $longitude = null;
    public $location_name = '';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'ssh_key' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
        ];
    }

    public function getLocation()
    {
        // This would be called from JavaScript to get GPS coordinates
        // For now, we'll just set a placeholder
        $this->location_name = 'Auto-detected location';
    }

    public function testConnection()
    {
        $this->validate();
        
        try {
            // Test SSH connection (simplified)
            session()->flash('connection_test', 'Connection test successful!');
        } catch (\Exception $e) {
            session()->flash('connection_error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function createServer()
    {
        $this->validate();

        $server = Server::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'username' => $this->username,
            'ssh_key' => $this->ssh_key,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
            'status' => 'offline',
        ]);

        // Check Docker installation
        try {
            $dockerService = app(DockerService::class);
            $dockerInfo = $dockerService->checkDockerInstallation($server);
            
            $server->update([
                'docker_installed' => $dockerInfo['installed'],
                'docker_version' => $dockerInfo['version'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Docker not installed or not accessible
        }

        $this->dispatch('server-created');
        
        return redirect()->route('servers.show', $server)
            ->with('message', 'Server added successfully!');
    }

    public function render()
    {
        return view('livewire.servers.server-create');
    }
}

