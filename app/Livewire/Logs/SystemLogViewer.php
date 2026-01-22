<?php

declare(strict_types=1);

namespace App\Livewire\Logs;

use App\Models\Server;
use App\Models\SystemLog;
use App\Services\LogExportService;
use App\Services\SystemLogService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogViewer extends Component
{
    use WithPagination;

    public ?int $serverId = null;
    public string $logType = 'all';
    public string $logLevel = 'all';
    public string $search = '';
    public string $timeRange = '24h';
    public int $perPage = 50;
    public bool $autoRefresh = false;
    public bool $showCollectModal = false;

    /** @var array<string, array<string, string>> */
    protected array $queryString = [
        'serverId' => ['as' => 'server'],
        'logType' => ['as' => 'type'],
        'logLevel' => ['as' => 'level'],
        'search' => ['as' => 'q'],
        'timeRange' => ['as' => 'time'],
    ];

    public function mount(?int $serverId = null): void
    {
        $this->serverId = $serverId;
    }

    #[Computed]
    public function servers()
    {
        return Server::query()
            ->where('status', 'online')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function logs()
    {
        $query = SystemLog::query()
            ->with(['server', 'user'])
            ->orderBy('logged_at', 'desc');

        // Apply server filter
        if ($this->serverId) {
            $query->where('server_id', $this->serverId);
        }

        // Apply log type filter
        if ($this->logType !== 'all') {
            $query->where('log_type', $this->logType);
        }

        // Apply log level filter
        if ($this->logLevel !== 'all') {
            $query->where('level', $this->logLevel);
        }

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('message', 'like', "%{$this->search}%")
                  ->orWhere('source', 'like', "%{$this->search}%");
            });
        }

        // Apply time range filter
        $query = $this->applyTimeRangeFilter($query);

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function statistics()
    {
        $query = SystemLog::query();

        if ($this->serverId) {
            $query->where('server_id', $this->serverId);
        }

        $query = $this->applyTimeRangeFilter($query);

        return [
            'total' => $query->count(),
            'critical' => $query->clone()->whereIn('level', [
                SystemLog::LEVEL_EMERGENCY,
                SystemLog::LEVEL_ALERT,
                SystemLog::LEVEL_CRITICAL,
            ])->count(),
            'errors' => $query->clone()->where('level', SystemLog::LEVEL_ERROR)->count(),
            'warnings' => $query->clone()->where('level', SystemLog::LEVEL_WARNING)->count(),
            'info' => $query->clone()->where('level', SystemLog::LEVEL_INFO)->count(),
        ];
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<SystemLog> $query
     * @return \Illuminate\Database\Eloquent\Builder<SystemLog>
     */
    protected function applyTimeRangeFilter(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return match ($this->timeRange) {
            '1h' => $query->where('logged_at', '>=', now()->subHour()),
            '6h' => $query->where('logged_at', '>=', now()->subHours(6)),
            '24h' => $query->where('logged_at', '>=', now()->subDay()),
            '7d' => $query->where('logged_at', '>=', now()->subDays(7)),
            '30d' => $query->where('logged_at', '>=', now()->subDays(30)),
            default => $query,
        };
    }

    public function updatedServerId(): void
    {
        $this->resetPage();
    }

    public function updatedLogType(): void
    {
        $this->resetPage();
    }

    public function updatedLogLevel(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTimeRange(): void
    {
        $this->resetPage();
    }

    public function collectLogs(): void
    {
        try {
            if (!$this->serverId) {
                $this->dispatch('notification', type: 'error', message: 'Please select a server first');
                return;
            }

            $server = Server::findOrFail($this->serverId);
            $logService = app(SystemLogService::class);

            $logs = $logService->collectLogsFromServer($server, 100);
            $stored = $logService->storeLogs($logs);

            $this->dispatch('notification', type: 'success', message: "Collected and stored {$stored} log entries");
            $this->showCollectModal = false;
            $this->resetPage();

            // Clear computed properties cache
            unset($this->logs, $this->statistics);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to collect logs: {$e->getMessage()}");
        }
    }

    public function deleteLog(int $logId): void
    {
        try {
            SystemLog::findOrFail($logId)->delete();
            $this->dispatch('notification', type: 'success', message: 'Log entry deleted');
            unset($this->logs, $this->statistics);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to delete log entry');
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['serverId', 'logType', 'logLevel', 'search', 'timeRange']);
        $this->resetPage();
    }

    public function exportLogs(string $format = 'csv'): StreamedResponse
    {
        $query = SystemLog::query()
            ->with(['server', 'user'])
            ->orderBy('logged_at', 'desc');

        // Apply same filters as the view
        if ($this->serverId) {
            $query->where('server_id', $this->serverId);
        }

        if ($this->logType !== 'all') {
            $query->where('log_type', $this->logType);
        }

        if ($this->logLevel !== 'all') {
            $query->where('level', $this->logLevel);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('message', 'like', "%{$this->search}%")
                  ->orWhere('source', 'like', "%{$this->search}%");
            });
        }

        $query = $this->applyTimeRangeFilter($query);

        $logs = $query->get();

        $exportService = app(LogExportService::class);
        return $exportService->export($logs, $format);
    }

    #[On('refresh-logs')]
    public function refreshLogs(): void
    {
        unset($this->logs, $this->statistics);
    }

    public function render(): View
    {
        return view('livewire.logs.system-log-viewer', [
            'logTypes' => SystemLog::getLogTypes(),
            'logLevels' => SystemLog::getLogLevels(),
        ]);
    }
}
