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

        // Debug: log the ssh credentials state
        logger()->info('ServerEdit loadServerData', [
            'server_id' => $this->server->id,
            'has_ssh_key' => !empty($this->server->ssh_key),
            'ssh_key_length' => strlen($this->server->ssh_key ?? ''),
            'has_ssh_password' => !empty($this->server->ssh_password),
        ]);

        // Use empty() for more reliable check (catches empty strings, null, etc.)
        $this->auth_method = !empty($this->server->ssh_key) ? 'key' : 'password';
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

        // Debug logging - remove after fixing
        logger()->info('ServerEdit updateServer called', [
            'auth_method' => $this->auth_method,
            'ssh_password_length' => strlen($this->ssh_password),
            'ssh_password_empty' => empty($this->ssh_password),
            'ssh_password_trimmed_length' => strlen(trim($this->ssh_password)),
            'ssh_password_is_string' => is_string($this->ssh_password),
            'ssh_password_ord' => strlen($this->ssh_password) > 0 ? ord($this->ssh_password[0]) : 'empty',
            'ssh_key_length' => strlen($this->ssh_key),
        ]);

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
        // Use strlen() > 0 instead of truthiness to handle edge cases like "0"
        if ($this->auth_method === 'password' && strlen($this->ssh_password) > 0) {
            $updateData['ssh_password'] = $this->ssh_password;
            $updateData['ssh_key'] = null;
        } elseif ($this->auth_method === 'key' && strlen($this->ssh_key) > 0) {
            $updateData['ssh_key'] = $this->ssh_key;
            $updateData['ssh_password'] = null;
        }

        // Debug: Log what we're about to save
        logger()->info('ServerEdit about to update', [
            'update_data_has_password' => isset($updateData['ssh_password']),
            'update_data_keys' => array_keys($updateData),
        ]);

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
