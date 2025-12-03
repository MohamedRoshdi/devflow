<?php

namespace App\Livewire\Logs;

use App\Models\SecurityEvent;
use App\Models\Server;
use Livewire\Component;
use Livewire\WithPagination;

class SecurityAuditLog extends Component
{
    use WithPagination;

    public string $search = '';
    public string $serverFilter = '';
    public string $eventTypeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public array $selectedEvent = [];
    public bool $showDetails = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'serverFilter' => ['except' => ''],
        'eventTypeFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'serverFilter', 'eventTypeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function viewDetails(int $eventId): void
    {
        $event = SecurityEvent::with(['server', 'user'])->find($eventId);

        if ($event) {
            $this->selectedEvent = [
                'id' => $event->id,
                'server' => $event->server?->name ?? 'N/A',
                'event_type' => $event->event_type,
                'event_type_label' => $event->getEventTypeLabel(),
                'source_ip' => $event->source_ip,
                'details' => $event->details,
                'metadata' => $event->metadata,
                'user' => $event->user?->name ?? 'System',
                'created_at' => $event->created_at->format('Y-m-d H:i:s'),
            ];
            $this->showDetails = true;
        }
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedEvent = [];
    }

    public function getServersProperty()
    {
        return Server::orderBy('name')->get();
    }

    public function getEventTypesProperty(): array
    {
        return [
            SecurityEvent::TYPE_FIREWALL_ENABLED => 'Firewall Enabled',
            SecurityEvent::TYPE_FIREWALL_DISABLED => 'Firewall Disabled',
            SecurityEvent::TYPE_RULE_ADDED => 'Rule Added',
            SecurityEvent::TYPE_RULE_DELETED => 'Rule Deleted',
            SecurityEvent::TYPE_IP_BANNED => 'IP Banned',
            SecurityEvent::TYPE_IP_UNBANNED => 'IP Unbanned',
            SecurityEvent::TYPE_SSH_CONFIG_CHANGED => 'SSH Config Changed',
            SecurityEvent::TYPE_SECURITY_SCAN => 'Security Scan',
        ];
    }

    public function getStatsProperty(): array
    {
        $today = now()->startOfDay();

        return [
            'total' => SecurityEvent::count(),
            'today' => SecurityEvent::where('created_at', '>=', $today)->count(),
            'firewall_events' => SecurityEvent::whereIn('event_type', [
                SecurityEvent::TYPE_FIREWALL_ENABLED,
                SecurityEvent::TYPE_FIREWALL_DISABLED,
                SecurityEvent::TYPE_RULE_ADDED,
                SecurityEvent::TYPE_RULE_DELETED,
            ])->count(),
            'ip_bans' => SecurityEvent::where('event_type', SecurityEvent::TYPE_IP_BANNED)->count(),
        ];
    }

    public function render()
    {
        $events = SecurityEvent::query()
            ->with(['server', 'user'])
            ->when($this->search, fn($q) => $q->where(function ($query) {
                $query->where('details', 'like', "%{$this->search}%")
                    ->orWhere('source_ip', 'like', "%{$this->search}%");
            }))
            ->when($this->serverFilter, fn($q) => $q->where('server_id', $this->serverFilter))
            ->when($this->eventTypeFilter, fn($q) => $q->where('event_type', $this->eventTypeFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.logs.security-audit-log', [
            'events' => $events,
        ]);
    }
}
