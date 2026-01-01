<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Models\ServerCommandHistory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ServerCommandHistoryList extends Component
{
    use WithPagination;

    public Server $server;
    public string $statusFilter = '';
    public string $actionFilter = '';
    public string $executionTypeFilter = '';
    public ?int $expandedId = null;

    public function mount(Server $server): void
    {
        $this->server = $server;
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, ServerCommandHistory>
     */
    #[Computed]
    public function commandHistory(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return ServerCommandHistory::where('server_id', $this->server->id)
            ->with('user:id,name')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->actionFilter, fn ($q) => $q->where('action', $this->actionFilter))
            ->when($this->executionTypeFilter, fn ($q) => $q->where('execution_type', $this->executionTypeFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function actionCounts(): array
    {
        return ServerCommandHistory::where('server_id', $this->server->id)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->pluck('count', 'action')
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return ServerCommandHistory::where('server_id', $this->server->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->actionFilter = '';
        $this->executionTypeFilter = '';
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExecutionTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.servers.server-command-history-list');
    }
}
