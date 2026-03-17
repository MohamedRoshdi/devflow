<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\ProvisioningLog;
use App\Models\Server;
use App\Services\ServerProvisioningService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ProvisioningLogs extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Server $server;

    public string $statusFilter = 'all';

    public string $dateRange = 'all';

    public ?int $expandedLogId = null;

    // Provisioning wizard visibility
    public bool $showProvisioningForm = false;

    // Package selection
    public bool $installNginx = true;

    public bool $installMySQL = false;

    public bool $installPostgreSQL = false;

    public bool $installRedis = false;

    public bool $installPHP = true;

    public bool $installComposer = true;

    public bool $installNodeJS = true;

    public bool $configureFirewall = true;

    public bool $setupSwap = true;

    public bool $secureSSH = true;

    // Configuration options
    public string $phpVersion = '8.4';

    public string $nodeVersion = '22';

    public string $mysqlPassword = '';

    public string $postgresqlPassword = '';

    public string $postgresqlDatabases = '';

    public string $redisPassword = '';

    public int $redisMaxMemoryMB = 512;

    public int $swapSizeGB = 2;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;

        // Auto-show form when server has never been provisioned
        $hasLogs = ProvisioningLog::where('server_id', $server->id)->exists();
        $this->showProvisioningForm = ! $hasLogs;

        // Pre-fill database passwords
        if (empty($this->mysqlPassword)) {
            $this->mysqlPassword = bin2hex(random_bytes(16));
        }
        if (empty($this->postgresqlPassword)) {
            $this->postgresqlPassword = bin2hex(random_bytes(16));
        }
    }

    #[Computed]
    public function logs()
    {
        return ProvisioningLog::query()
            ->where('server_id', $this->server->id)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateRange !== 'all', function ($q) {
                $date = match ($this->dateRange) {
                    'today' => now()->startOfDay(),
                    'week' => now()->subWeek(),
                    'month' => now()->subMonth(),
                    default => null,
                };

                if ($date) {
                    $q->where('created_at', '>=', $date);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    #[Computed]
    public function stats(): array
    {
        $allLogs = ProvisioningLog::where('server_id', $this->server->id);

        return [
            'total' => (clone $allLogs)->count(),
            'completed' => (clone $allLogs)->where('status', 'completed')->count(),
            'failed' => (clone $allLogs)->where('status', 'failed')->count(),
            'running' => (clone $allLogs)->where('status', 'running')->count(),
            'pending' => (clone $allLogs)->where('status', 'pending')->count(),
            'avg_duration' => (clone $allLogs)
                ->where('status', 'completed')
                ->whereNotNull('duration_seconds')
                ->avg('duration_seconds'),
        ];
    }

    public function toggleLogExpansion(int $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'all';
        $this->dateRange = 'all';
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateRange(): void
    {
        $this->resetPage();
    }

    public function startProvisioning(): void
    {
        $this->validate([
            'phpVersion' => 'required|in:8.1,8.2,8.3,8.4',
            'nodeVersion' => 'required|in:18,20,22',
            'mysqlPassword' => 'required_if:installMySQL,true|min:8',
            'postgresqlPassword' => 'required_if:installPostgreSQL,true|min:8',
            'redisMaxMemoryMB' => 'required_if:installRedis,true|integer|min:64|max:8192',
            'swapSizeGB' => 'required|integer|min:1|max:32',
        ]);

        $server = $this->server;

        $options = [
            'update_system' => true,
            'install_nginx' => $this->installNginx,
            'install_mysql' => $this->installMySQL,
            'install_postgresql' => $this->installPostgreSQL,
            'install_redis' => $this->installRedis,
            'install_php' => $this->installPHP,
            'install_composer' => $this->installComposer,
            'install_nodejs' => $this->installNodeJS,
            'configure_firewall' => $this->configureFirewall,
            'setup_swap' => $this->setupSwap,
            'secure_ssh' => $this->secureSSH,
            'php_version' => $this->phpVersion,
            'node_version' => $this->nodeVersion,
            'mysql_root_password' => $this->mysqlPassword,
            'postgresql_password' => $this->postgresqlPassword,
            'postgresql_databases' => array_filter(array_map('trim', explode(',', $this->postgresqlDatabases))),
            'redis_password' => $this->redisPassword !== '' ? $this->redisPassword : null,
            'redis_max_memory_mb' => $this->redisMaxMemoryMB,
            'swap_size_gb' => $this->swapSizeGB,
            'firewall_ports' => [22, 80, 443],
        ];

        dispatch(function () use ($server, $options) {
            app(ServerProvisioningService::class)->provisionServer($server, $options);
        })->afterResponse();

        $this->showProvisioningForm = false;
        unset($this->stats);
        unset($this->logs);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Server provisioning started in the background. This may take several minutes.',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.provisioning-logs');
    }
}
