<?php

declare(strict_types=1);

namespace App\Livewire\Logs;

use App\Models\LogEntry;
use App\Models\Project;
use App\Models\Server;
use App\Services\LogAggregationService;
use Livewire\Component;
use Livewire\Attributes\{Computed, On};
use Livewire\WithPagination;

class LogViewer extends Component
{
    use WithPagination;

    public ?int $server_id = null;
    public ?int $project_id = null;
    public string $source = 'all';
    public string $level = 'all';
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $expandedLogId = null;
    public bool $autoRefresh = false;

    protected $queryString = [
        'server_id' => ['except' => null],
        'project_id' => ['except' => null],
        'source' => ['except' => 'all'],
        'level' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->subHours(24)->format('Y-m-d\TH:i');
        $this->dateTo = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function servers()
    {
        return Server::where('status', 'online')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function projects()
    {
        $query = Project::query();

        if ($this->server_id) {
            $query->where('server_id', $this->server_id);
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function logs()
    {
        $query = LogEntry::query()
            ->with(['server', 'project']);

        if ($this->server_id) {
            $query->byServer($this->server_id);
        }

        if ($this->project_id) {
            $query->byProject($this->project_id);
        }

        if ($this->source !== 'all') {
            $query->bySource($this->source);
        }

        if ($this->level !== 'all') {
            $query->byLevel($this->level);
        }

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->dateFrom) {
            $query->where('logged_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('logged_at', '<=', $this->dateTo);
        }

        return $query->recent()->paginate(50);
    }

    #[Computed]
    public function statistics()
    {
        $query = LogEntry::query();

        if ($this->server_id) {
            $query->byServer($this->server_id);
        }

        if ($this->project_id) {
            $query->byProject($this->project_id);
        }

        if ($this->dateFrom) {
            $query->where('logged_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('logged_at', '<=', $this->dateTo);
        }

        return [
            'total' => $query->count(),
            'error' => (clone $query)->where('level', 'error')->count(),
            'warning' => (clone $query)->where('level', 'warning')->count(),
            'critical' => (clone $query)->whereIn('level', ['critical', 'alert', 'emergency'])->count(),
        ];
    }

    public function syncNow(): void
    {
        if (!$this->server_id) {
            $this->dispatch('notification', type: 'error', message: 'Please select a server first');
            return;
        }

        $server = Server::findOrFail($this->server_id);

        try {
            $logService = app(LogAggregationService::class);
            $results = $logService->syncLogs($server);

            $message = "Synced {$results['total_entries']} log entries from {$results['success']} sources";
            if ($results['failed'] > 0) {
                $message .= " ({$results['failed']} sources failed)";
            }

            $this->dispatch('notification', type: 'success', message: $message);
            $this->resetPage();
            unset($this->logs);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Sync failed: {$e->getMessage()}");
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['server_id', 'project_id', 'source', 'level', 'search']);
        $this->dateFrom = now()->subHours(24)->format('Y-m-d\TH:i');
        $this->dateTo = now()->format('Y-m-d\TH:i');
        $this->resetPage();
    }

    public function exportLogs(): void
    {
        $logService = app(LogAggregationService::class);
        $logs = $logService->searchLogs([
            'server_id' => $this->server_id,
            'project_id' => $this->project_id,
            'source' => $this->source !== 'all' ? $this->source : null,
            'level' => $this->level !== 'all' ? $this->level : null,
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);

        $filename = 'logs_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // CSV headers
        fputcsv($handle, ['Timestamp', 'Server', 'Project', 'Source', 'Level', 'Message', 'Location']);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->logged_at->format('Y-m-d H:i:s'),
                $log->server->name,
                $log->project?->name ?? 'N/A',
                $log->source,
                $log->level,
                $log->message,
                $log->location ?? 'N/A',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->dispatch('download', [
            'filename' => $filename,
            'content' => base64_encode($csv),
        ]);

        $this->dispatch('notification', type: 'success', message: 'Logs exported successfully');
    }

    public function toggleExpand(int $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    public function updatedServerId(): void
    {
        $this->project_id = null;
        unset($this->projects);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
    }

    #[On('refresh-logs')]
    public function refresh(): void
    {
        unset($this->logs);
        unset($this->statistics);
    }

    public function render()
    {
        return view('livewire.logs.log-viewer')
            ->title('Logs - DevFlow Pro');
    }
}
