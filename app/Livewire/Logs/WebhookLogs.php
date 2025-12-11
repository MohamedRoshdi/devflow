<?php

namespace App\Livewire\Logs;

use App\Models\Project;
use App\Models\WebhookDelivery;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $providerFilter = '';

    public string $projectFilter = '';

    public string $eventTypeFilter = '';

    /** @var array<string, mixed> */
    public array $selectedDelivery = [];

    public bool $showDetails = false;

    /** @var array<string, array{except: string}> */
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'providerFilter' => ['except' => ''],
        'projectFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'providerFilter', 'projectFilter', 'eventTypeFilter']);
        $this->resetPage();
    }

    public function viewDetails(int $deliveryId): void
    {
        $delivery = WebhookDelivery::with('project', 'deployment')->find($deliveryId);

        if ($delivery) {
            $this->selectedDelivery = [
                'id' => $delivery->id,
                'project' => $delivery->project?->name ?? 'N/A',
                'provider' => $delivery->provider,
                'event_type' => $delivery->event_type,
                'status' => $delivery->status,
                'signature' => $delivery->signature,
                'payload' => $delivery->payload,
                'response' => $delivery->response,
                'deployment_id' => $delivery->deployment_id,
                'created_at' => $delivery->created_at->format('Y-m-d H:i:s'),
            ];
            $this->showDetails = true;
        }
    }

    public function closeDetails(): void
    {
        $this->showDetails = false;
        $this->selectedDelivery = [];
    }

    public function getProjectsProperty()
    {
        return Project::orderBy('name')->get();
    }

    public function getEventTypesProperty()
    {
        return WebhookDelivery::distinct()->pluck('event_type')->filter()->sort()->values();
    }

    public function getStatsProperty(): array
    {
        return [
            'total' => WebhookDelivery::count(),
            'success' => WebhookDelivery::where('status', 'success')->count(),
            'failed' => WebhookDelivery::where('status', 'failed')->count(),
            'ignored' => WebhookDelivery::where('status', 'ignored')->count(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        $deliveries = WebhookDelivery::query()
            ->with(['project', 'deployment'])
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('event_type', 'like', "%{$this->search}%")
                    ->orWhere('response', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->providerFilter, fn ($q) => $q->where('provider', $this->providerFilter))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->eventTypeFilter, fn ($q) => $q->where('event_type', $this->eventTypeFilter))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.logs.webhook-logs', [
            'deliveries' => $deliveries,
        ]);
    }
}
