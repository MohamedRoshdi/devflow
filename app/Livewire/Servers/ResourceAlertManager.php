<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use App\Services\ResourceAlertService;
use App\Services\ServerMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ResourceAlertManager extends Component
{
    use WithPagination;

    public Server $server;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?ResourceAlert $editingAlert = null;

    // Form fields
    public string $resource_type = 'cpu';

    public string $threshold_type = 'above';

    public float $threshold_value = 80.00;

    public int $cooldown_minutes = 15;

    public bool $is_active = true;

    // Notification channels
    public bool $enable_email = false;

    public string $email_address = '';

    public bool $enable_slack = false;

    public string $slack_webhook = '';

    public bool $enable_discord = false;

    public string $discord_webhook = '';

    /** @var array<string, string> */
    protected array $rules = [
        'resource_type' => 'required|in:cpu,memory,disk,load',
        'threshold_type' => 'required|in:above,below',
        'threshold_value' => 'required|numeric|min:0|max:100',
        'cooldown_minutes' => 'required|integer|min:1|max:1440',
        'is_active' => 'boolean',
        'enable_email' => 'boolean',
        'email_address' => 'required_if:enable_email,true|email|nullable',
        'enable_slack' => 'boolean',
        'slack_webhook' => 'required_if:enable_slack,true|url|nullable',
        'enable_discord' => 'boolean',
        'discord_webhook' => 'required_if:enable_discord,true|url|nullable',
    ];

    public function mount(Server $server): void
    {
        $this->server = $server;
    }

    #[Computed]
    public function alerts()
    {
        return ResourceAlert::forServer($this->server->id)
            ->with(['latestHistory'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function currentMetrics()
    {
        $metricsService = app(ServerMetricsService::class);
        $metrics = $metricsService->getLatestMetrics($this->server);

        if (! $metrics) {
            return [
                'cpu' => 0,
                'memory' => 0,
                'disk' => 0,
                'load' => 0,
            ];
        }

        return [
            'cpu' => $metrics->cpu_usage,
            'memory' => $metrics->memory_usage,
            'disk' => $metrics->disk_usage,
            'load' => $metrics->load_average_1,
        ];
    }

    #[Computed]
    public function alertHistory()
    {
        return AlertHistory::forServer($this->server->id)
            ->with(['resourceAlert'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function openEditModal(int $alertId): void
    {
        $this->editingAlert = ResourceAlert::findOrFail($alertId);

        $this->resource_type = $this->editingAlert->resource_type;
        $this->threshold_type = $this->editingAlert->threshold_type;
        $this->threshold_value = $this->editingAlert->threshold_value;
        $this->cooldown_minutes = $this->editingAlert->cooldown_minutes;
        $this->is_active = $this->editingAlert->is_active;

        // Load notification channels
        $channels = $this->editingAlert->notification_channels ?? [];

        if (isset($channels['email'])) {
            $this->enable_email = true;
            $this->email_address = $channels['email']['email'] ?? '';
        }

        if (isset($channels['slack'])) {
            $this->enable_slack = true;
            $this->slack_webhook = $channels['slack']['webhook_url'] ?? '';
        }

        if (isset($channels['discord'])) {
            $this->enable_discord = true;
            $this->discord_webhook = $channels['discord']['webhook_url'] ?? '';
        }

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAlert = null;
        $this->resetForm();
        $this->resetValidation();
    }

    public function createAlert(): void
    {
        $this->validate();

        ResourceAlert::create([
            'server_id' => $this->server->id,
            'resource_type' => $this->resource_type,
            'threshold_type' => $this->threshold_type,
            'threshold_value' => $this->threshold_value,
            'cooldown_minutes' => $this->cooldown_minutes,
            'is_active' => $this->is_active,
            'notification_channels' => $this->buildNotificationChannels(),
        ]);

        $this->dispatch('alert-created');
        $this->dispatch('notify', type: 'success', message: 'Alert created successfully!');
        $this->closeCreateModal();
        unset($this->alerts);
    }

    public function updateAlert(): void
    {
        $this->validate();

        if ($this->editingAlert === null) {
            return;
        }

        $this->editingAlert->update([
            'resource_type' => $this->resource_type,
            'threshold_type' => $this->threshold_type,
            'threshold_value' => $this->threshold_value,
            'cooldown_minutes' => $this->cooldown_minutes,
            'is_active' => $this->is_active,
            'notification_channels' => $this->buildNotificationChannels(),
        ]);

        $this->dispatch('alert-updated');
        $this->dispatch('notify', type: 'success', message: 'Alert updated successfully!');
        $this->closeEditModal();
        unset($this->alerts);
    }

    public function deleteAlert(int $alertId): void
    {
        $alert = ResourceAlert::findOrFail($alertId);
        $alert->delete();

        $this->dispatch('alert-deleted');
        $this->dispatch('notify', type: 'success', message: 'Alert deleted successfully!');
        unset($this->alerts);
    }

    public function toggleAlert(int $alertId): void
    {
        $alert = ResourceAlert::findOrFail($alertId);
        $alert->update(['is_active' => ! $alert->is_active]);

        $status = $alert->is_active ? 'enabled' : 'disabled';
        $this->dispatch('notify', type: 'success', message: "Alert {$status} successfully!");
        unset($this->alerts);
    }

    public function testAlert(int $alertId): void
    {
        $alert = ResourceAlert::findOrFail($alertId);
        $alertService = app(ResourceAlertService::class);

        $result = $alertService->testAlert($alert);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: 'Test notification sent successfully!');
        } else {
            $this->dispatch('notify', type: 'error', message: 'Failed to send test notification: '.$result['message']);
        }
    }

    public function refreshMetrics(): void
    {
        $metricsService = app(ServerMetricsService::class);
        $metricsService->collectMetrics($this->server);

        unset($this->currentMetrics);
        $this->dispatch('notify', type: 'success', message: 'Metrics refreshed!');
    }

    protected function buildNotificationChannels(): array
    {
        $channels = [];

        if ($this->enable_email && $this->email_address) {
            $channels['email'] = [
                'email' => $this->email_address,
            ];
        }

        if ($this->enable_slack && $this->slack_webhook) {
            $channels['slack'] = [
                'webhook_url' => $this->slack_webhook,
            ];
        }

        if ($this->enable_discord && $this->discord_webhook) {
            $channels['discord'] = [
                'webhook_url' => $this->discord_webhook,
            ];
        }

        return $channels;
    }

    protected function resetForm(): void
    {
        $this->resource_type = 'cpu';
        $this->threshold_type = 'above';
        $this->threshold_value = 80.00;
        $this->cooldown_minutes = 15;
        $this->is_active = true;
        $this->enable_email = false;
        $this->email_address = '';
        $this->enable_slack = false;
        $this->slack_webhook = '';
        $this->enable_discord = false;
        $this->discord_webhook = '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.resource-alert-manager');
    }
}
