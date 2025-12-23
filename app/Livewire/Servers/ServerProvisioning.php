<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\ServerProvisioningService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ServerProvisioning extends Component
{
    public Server $server;

    // Package selection
    public bool $installNginx = true;

    public bool $installMySQL = false;

    public bool $installPHP = true;

    public bool $installComposer = true;

    public bool $installNodeJS = true;

    public bool $configureFirewall = true;

    public bool $setupSwap = true;

    public bool $secureSSH = true;

    // Configuration options
    public string $phpVersion = '8.4';

    public string $nodeVersion = '20';

    public string $mysqlPassword = '';

    public int $swapSizeGB = 2;

    /** @var array<int, int> */
    public array $firewallPorts = [22, 80, 443];

    public bool $showProvisioningModal = false;

    public bool $isProvisioning = false;

    public function mount(Server $server): void
    {
        $this->server = $server;

        // Pre-fill MySQL password if not set
        if (empty($this->mysqlPassword)) {
            $this->mysqlPassword = bin2hex(random_bytes(16));
        }
    }

    #[Computed]
    public function provisioningLogs()
    {
        return $this->server->provisioningLogs()
            ->latest()
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function latestLog()
    {
        return $this->server->latestProvisioningLog;
    }

    #[Computed]
    public function provisioningProgress(): array
    {
        if ($this->server->provision_status !== 'provisioning') {
            return [
                'percentage' => $this->server->provision_status === 'completed' ? 100 : 0,
                'current_step' => 0,
                'total_steps' => 0,
                'current_task' => null,
                'estimated_time_remaining' => null,
            ];
        }

        $logs = $this->provisioningLogs;
        $totalSteps = $logs->count();
        $completedSteps = $logs->where('status', 'completed')->count();
        $failedSteps = $logs->where('status', 'failed')->count();
        $runningLog = $logs->where('status', 'running')->first();

        $currentStep = $completedSteps + $failedSteps + ($runningLog ? 1 : 0);
        $percentage = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;

        // Calculate estimated time remaining
        $estimatedTimeRemaining = null;
        if ($completedSteps > 0 && $runningLog) {
            $avgDuration = $logs->where('status', 'completed')
                ->where('duration_seconds', '>', 0)
                ->avg('duration_seconds');

            if ($avgDuration) {
                $remainingSteps = $totalSteps - $currentStep;
                $estimatedTimeRemaining = (int) round($avgDuration * $remainingSteps);
            }
        }

        return [
            'percentage' => min($percentage, 100),
            'current_step' => $currentStep,
            'total_steps' => $totalSteps,
            'current_task' => $runningLog?->task,
            'estimated_time_remaining' => $estimatedTimeRemaining,
        ];
    }

    public function openProvisioningModal(): void
    {
        $this->showProvisioningModal = true;
    }

    public function closeProvisioningModal(): void
    {
        $this->showProvisioningModal = false;
    }

    public function startProvisioning(): void
    {
        $this->validate([
            'phpVersion' => 'required|in:8.1,8.2,8.3,8.4',
            'nodeVersion' => 'required|in:18,20,22',
            'mysqlPassword' => 'required_if:installMySQL,true|min:8',
            'swapSizeGB' => 'required|integer|min:1|max:32',
            'firewallPorts' => 'required|array|min:1',
        ]);

        $this->isProvisioning = true;

        try {
            $options = [
                'update_system' => true,
                'install_nginx' => $this->installNginx,
                'install_mysql' => $this->installMySQL,
                'install_php' => $this->installPHP,
                'install_composer' => $this->installComposer,
                'install_nodejs' => $this->installNodeJS,
                'configure_firewall' => $this->configureFirewall,
                'setup_swap' => $this->setupSwap,
                'secure_ssh' => $this->secureSSH,
                'php_version' => $this->phpVersion,
                'node_version' => $this->nodeVersion,
                'mysql_root_password' => $this->mysqlPassword,
                'swap_size_gb' => $this->swapSizeGB,
                'firewall_ports' => $this->firewallPorts,
            ];

            dispatch(function () use ($options) {
                $service = app(ServerProvisioningService::class);
                $service->provisionServer($this->server, $options);
            })->afterResponse();

            $this->dispatch('provisioning-started');
            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Server provisioning started in background. This may take several minutes.',
            ]);

            $this->showProvisioningModal = false;

            // Refresh server status
            $this->dispatch('refresh-server-status');

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to start provisioning: '.$e->getMessage(),
            ]);
        } finally {
            $this->isProvisioning = false;
        }
    }

    public function downloadProvisioningScript(): ?\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $options = [
                'update_system' => true,
                'install_nginx' => $this->installNginx,
                'install_mysql' => $this->installMySQL,
                'install_php' => $this->installPHP,
                'install_composer' => $this->installComposer,
                'install_nodejs' => $this->installNodeJS,
                'configure_firewall' => $this->configureFirewall,
                'setup_swap' => $this->setupSwap,
                'php_version' => $this->phpVersion,
                'node_version' => $this->nodeVersion,
                'swap_size_gb' => $this->swapSizeGB,
                'firewall_ports' => $this->firewallPorts,
            ];

            $service = app(ServerProvisioningService::class);
            $script = $service->getProvisioningScript($options);

            return response()->streamDownload(
                fn () => print ($script),
                'provision-server-'.$this->server->slug.'.sh',
                ['Content-Type' => 'text/plain']
            );

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to generate script: '.$e->getMessage(),
            ]);

            return null;
        }
    }

    #[On('refresh-server-status')]
    public function refreshServerStatus(): void
    {
        $this->server->refresh();
        unset($this->provisioningLogs);
        unset($this->latestLog);
        unset($this->provisioningProgress);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.server-provisioning');
    }
}
