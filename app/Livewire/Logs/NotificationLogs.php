<?php

namespace App\Livewire\Logs;

use App\Models\NotificationLog;
use App\Models\NotificationChannel;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationLogs extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $channelFilter = '';
    public string $eventTypeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public array $selectedLog = [];
    public bool $showDetails = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'channelFilter' => ['except' => ''],
        'eventTypeFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'channelFilter', 'eventTypeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function viewDetails(int $logId): void
    {
        $log = NotificationLog::with('channel')->find($logId);

        if ($log) {
            $this->selectedLog = [
                'id' => $log->id,
                'channel' => $log->channel?->name ?? 'N/A',
                'channel_type' => $log->channel?->type ?? 'N/A',
                'event_type' => $log->event_type,
                'status' => $log->status,
                'payload' => $log->payload,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
            $this->showDetails = true;
        }
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedLog = [];
    }

    public function getChannelsProperty()
    {
        return NotificationChannel::orderBy('name')->get();
    }

    public function getEventTypesProperty()
    {
        return NotificationLog::distinct()->pluck('event_type')->filter()->sort()->values();
    }

    public function getStatsProperty(): array
    {
        return [
            'total' => NotificationLog::count(),
            'success' => NotificationLog::where('status', 'success')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
            'pending' => NotificationLog::where('status', 'pending')->count(),
        ];
    }

    public function render()
    {
        $logs = NotificationLog::query()
            ->with('channel')
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('event_type', 'like', "%{$this->search}%")
                    ->orWhere('error_message', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->channelFilter, fn($q) => $q->where('notification_channel_id', $this->channelFilter))
            ->when($this->eventTypeFilter, fn($q) => $q->where('event_type', $this->eventTypeFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.logs.notification-logs', [
            'logs' => $logs,
        ]);
    }
}
