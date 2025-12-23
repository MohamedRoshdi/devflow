<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Livewire\Concerns\HasServerFormFields;
use App\Models\Server;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ServerEdit extends Component
{
    use AuthorizesRequests;
    use HasServerFormFields;

    public Server $server;

    public function mount(Server $server): void
    {
        $this->authorize('update', $server);

        $this->server = $server;
        $this->loadServerData();
    }

    protected function loadServerData(): void
    {
        $this->name = $this->server->name;
        $this->hostname = $this->server->hostname ?? '';
        $this->ip_address = $this->server->ip_address;
        $this->port = $this->server->port ?? 22;
        $this->username = $this->server->username ?? 'root';
        $this->latitude = $this->server->latitude !== null ? (float) $this->server->latitude : null;
        $this->longitude = $this->server->longitude !== null ? (float) $this->server->longitude : null;
        $this->location_name = $this->server->location_name ?? '';

        // Determine auth method based on stored credentials
        // Priority: key > password > host_key (default)
        if (! empty($this->server->ssh_key)) {
            $this->auth_method = 'key';
        } elseif (! empty($this->server->ssh_password)) {
            $this->auth_method = 'password';
        } else {
            $this->auth_method = 'host_key';
        }
    }

    /**
     * Override to use existing credentials if new ones not provided
     */
    protected function getPasswordForTest(): ?string
    {
        // Host key auth doesn't use password
        if ($this->auth_method === 'host_key') {
            return null;
        }

        if ($this->auth_method === 'password' && $this->ssh_password) {
            return $this->ssh_password;
        }

        return $this->server->ssh_password;
    }

    /**
     * Override to use existing credentials if new ones not provided
     */
    protected function getKeyForTest(): ?string
    {
        // Host key auth doesn't use custom key
        if ($this->auth_method === 'host_key') {
            return null;
        }

        if ($this->auth_method === 'key' && $this->ssh_key) {
            return $this->ssh_key;
        }

        return $this->server->ssh_key;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseServerRules(),
            ['username' => $this->usernameRule(withRegex: false)],
            $this->authRulesForEdit()
        );
    }

    public function updateServer(): void
    {
        $this->validate();

        /** @var array<string, mixed> */
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

        // Update credentials based on auth method
        if ($this->auth_method === 'host_key') {
            // Host key authentication - clear stored credentials
            $updateData['ssh_password'] = null;
            $updateData['ssh_key'] = null;
        } elseif ($this->auth_method === 'password' && strlen($this->ssh_password) > 0) {
            // Password auth with new password provided
            $updateData['ssh_password'] = $this->ssh_password;
            $updateData['ssh_key'] = null;
        } elseif ($this->auth_method === 'key' && strlen($this->ssh_key) > 0) {
            // Key auth with new key provided
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

            if (! empty($serverInfo)) {
                $this->server->update([
                    'os' => $serverInfo['os'] ?? $this->server->os,
                    'cpu_cores' => isset($serverInfo['cpu_cores']) ? (int) $serverInfo['cpu_cores'] : $this->server->cpu_cores,
                    'memory_gb' => isset($serverInfo['memory_gb']) ? (int) $serverInfo['memory_gb'] : $this->server->memory_gb,
                    'disk_gb' => isset($serverInfo['disk_gb']) ? (int) $serverInfo['disk_gb'] : $this->server->disk_gb,
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

        $this->redirect(route('servers.show', $this->server), navigate: true);
        session()->flash('message', $message);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.server-edit');
    }
}
