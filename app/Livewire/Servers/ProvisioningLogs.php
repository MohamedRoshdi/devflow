<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\ProvisioningLog;
use App\Models\Server;
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

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
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

    public function render()
    {
        return view('livewire.servers.provisioning-logs');
    }
}
