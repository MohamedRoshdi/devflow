<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Enums\ServerRole;
use App\Models\Server;
use App\Services\DockerService;
use App\Services\ServerConnectivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;

class ServerCreate extends Component
{
    public string $name = '';

    public string $hostname = '';

    public string $ip_address = '';

    public int $port = 22;

    public string $username = 'root';

    public string $ssh_password = '';

    public string $ssh_key = '';

    public string $auth_method = 'host_key'; // 'host_key', 'password', or 'key'

    public string $role = 'general';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $location_name = '';

    /**
     * @return array<string, array<int, \Illuminate\Validation\Rules\Enum|string|\Closure>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'ip_address' => ['nullable', 'ip', function (string $attribute, mixed $value, \Closure $fail): void {
                if (empty($value) && empty($this->hostname)) {
                    $fail('Either an IP address or a hostname must be provided.');
                }
            }],
            'port' => 'required|integer|min:1|max:65535',
            // Username: alphanumeric, underscores, hyphens - no shell special chars
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'ssh_password' => 'nullable|string|required_if:auth_method,password',
            // SSH key is optional - required only for 'key' auth method
            'ssh_key' => 'nullable|string|required_if:auth_method,key',
            'auth_method' => 'required|in:host_key,password,key',
            'role' => ['required', new Enum(ServerRole::class)],
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Resolve hostname to IP address via DNS if no IP was provided.
     */
    protected function resolveHostnameToIp(): void
    {
        if (! empty($this->ip_address) || empty($this->hostname)) {
            return;
        }

        $resolved = gethostbyname($this->hostname);

        // gethostbyname returns the hostname unchanged if resolution fails
        if ($resolved === $this->hostname) {
            $this->addError('hostname', 'Could not resolve hostname to an IP address. Please provide an IP address manually.');

            return;
        }

        $this->ip_address = $resolved;
    }

    public function getLocation(): void
    {
        // This would be called from JavaScript to get GPS coordinates
        // For now, we'll just set a placeholder
        $this->location_name = 'Auto-detected location';
    }

    public function testConnection(): void
    {
        $this->validate();

        $this->resolveHostnameToIp();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            // Create temporary server object for testing
            $tempServer = new Server([
                'ip_address' => $this->ip_address,
                'port' => $this->port,
                'username' => $this->username,
                'ssh_password' => $this->auth_method === 'password' ? $this->ssh_password : null,
                'ssh_key' => $this->auth_method === 'key' ? $this->ssh_key : null,
            ]);

            $connectivityService = app(ServerConnectivityService::class);
            $result = $connectivityService->testConnection($tempServer);

            if ($result['reachable']) {
                session()->flash('connection_test', $result['message'].' (Latency: '.$result['latency_ms'].'ms)');
            } else {
                session()->flash('connection_error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('connection_error', 'Connection failed: '.$e->getMessage());
        }
    }

    public function createServer(): void
    {
        $this->validate();

        $this->resolveHostnameToIp();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $server = Server::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'hostname' => $this->hostname ?: null,
            'ip_address' => $this->ip_address ?: null,
            'port' => $this->port,
            'username' => $this->username,
            'ssh_password' => $this->auth_method === 'password' ? $this->ssh_password : null,
            'ssh_key' => $this->auth_method === 'key' ? $this->ssh_key : null,
            'role' => $this->role,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_name' => $this->location_name,
            'status' => 'offline',
        ]);

        // Test connectivity and update status
        $connectivityService = app(ServerConnectivityService::class);
        $isReachable = $connectivityService->pingAndUpdateStatus($server);

        // Get server information
        if ($isReachable) {
            $serverInfo = $connectivityService->getServerInfo($server);

            if (! empty($serverInfo)) {
                $server->update([
                    'os' => $serverInfo['os'] ?? null,
                    'cpu_cores' => isset($serverInfo['cpu_cores']) ? (int) $serverInfo['cpu_cores'] : null,
                    'memory_gb' => isset($serverInfo['memory_gb']) ? (int) round((float) $serverInfo['memory_gb']) : null,
                    'disk_gb' => isset($serverInfo['disk_gb']) ? (int) $serverInfo['disk_gb'] : null,
                ]);
            }
        }

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

        $message = $isReachable
            ? 'Server added successfully and is online!'
            : 'Server added but appears offline. Check SSH credentials.';

        $this->dispatch('toast', type: $isReachable ? 'success' : 'warning', message: $message);
        session()->flash($isReachable ? 'success' : 'warning', $message);

        $this->redirect(route('servers.show', $server), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.servers.server-create');
    }
}
