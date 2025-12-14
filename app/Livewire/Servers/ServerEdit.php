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
        $this->latitude = $this->server->latitude;
        $this->longitude = $this->server->longitude;
        $this->location_name = $this->server->location_name ?? '';
        $this->auth_method = $this->server->ssh_key ? 'key' : 'password';
    }

    /**
     * Override to use existing credentials if new ones not provided
     */
    protected function getPasswordForTest(): ?string
    {
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

            if (! empty($serverInfo)) {
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

        $this->redirect(route('servers.show', $this->server), navigate: true);
        session()->flash('message', $message);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.server-edit');
    }
}
