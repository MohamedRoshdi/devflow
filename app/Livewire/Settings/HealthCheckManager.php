<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\HealthCheck;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Services\HealthCheckService;
use App\Services\NotificationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class HealthCheckManager extends Component
{
    public bool $showCreateModal = false;
    public bool $showChannelModal = false;
    public bool $showResultsModal = false;
    public ?int $editingCheckId = null;
    public ?int $editingChannelId = null;
    public ?int $viewingResultsCheckId = null;

    // Health Check Form
    #[Validate('nullable|exists:projects,id')]
    public ?int $project_id = null;

    #[Validate('nullable|exists:servers,id')]
    public ?int $server_id = null;

    #[Validate('required|in:http,tcp,ping,ssl_expiry')]
    public string $check_type = 'http';

    #[Validate('required|string|max:500')]
    public string $target_url = '';

    #[Validate('required|integer|min:100|max:599')]
    public int $expected_status = 200;

    #[Validate('required|integer|min:1|max:1440')]
    public int $interval_minutes = 5;

    #[Validate('required|integer|min:5|max:300')]
    public int $timeout_seconds = 30;

    #[Validate('required|boolean')]
    public bool $is_active = true;

    public array $selectedChannels = [];

    // Notification Channel Form
    #[Validate('required|in:email,slack,discord')]
    public string $channel_type = 'email';

    #[Validate('required|string|max:255')]
    public string $channel_name = '';

    #[Validate('required_if:channel_type,email|email|nullable')]
    public ?string $channel_email = null;

    #[Validate('required_if:channel_type,slack|url|nullable')]
    public ?string $channel_slack_webhook = null;

    #[Validate('required_if:channel_type,discord|url|nullable')]
    public ?string $channel_discord_webhook = null;

    #[Validate('required|boolean')]
    public bool $channel_is_active = true;

    public function __construct(
        private readonly HealthCheckService $healthCheckService,
        private readonly NotificationService $notificationService
    ) {}

    #[Computed]
    public function healthChecks()
    {
        return HealthCheck::with(['project', 'server', 'notificationChannels', 'recentResults'])
            ->orderBy('status', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    #[Computed]
    public function notificationChannels()
    {
        return NotificationChannel::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('name')->get();
    }

    #[Computed]
    public function servers()
    {
        return Server::orderBy('name')->get();
    }

    #[Computed]
    public function healthStats()
    {
        $checks = $this->healthChecks;

        return [
            'total' => $checks->count(),
            'healthy' => $checks->where('status', 'healthy')->count(),
            'degraded' => $checks->where('status', 'degraded')->count(),
            'down' => $checks->where('status', 'down')->count(),
            'unknown' => $checks->where('status', 'unknown')->count(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetCheckForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCheckForm();
    }

    public function openChannelModal(): void
    {
        $this->resetChannelForm();
        $this->showChannelModal = true;
    }

    public function closeChannelModal(): void
    {
        $this->showChannelModal = false;
        $this->resetChannelForm();
    }

    public function openResultsModal(int $checkId): void
    {
        $this->viewingResultsCheckId = $checkId;
        $this->showResultsModal = true;
    }

    public function closeResultsModal(): void
    {
        $this->showResultsModal = false;
        $this->viewingResultsCheckId = null;
    }

    public function editCheck(int $checkId): void
    {
        $check = HealthCheck::findOrFail($checkId);

        $this->editingCheckId = $checkId;
        $this->project_id = $check->project_id;
        $this->server_id = $check->server_id;
        $this->check_type = $check->check_type;
        $this->target_url = $check->target_url;
        $this->expected_status = $check->expected_status;
        $this->interval_minutes = $check->interval_minutes;
        $this->timeout_seconds = $check->timeout_seconds;
        $this->is_active = $check->is_active;
        $this->selectedChannels = $check->notificationChannels->pluck('id')->toArray();

        $this->showCreateModal = true;
    }

    public function saveCheck(): void
    {
        $this->validate();

        try {
            $data = [
                'project_id' => $this->project_id,
                'server_id' => $this->server_id,
                'check_type' => $this->check_type,
                'target_url' => $this->target_url,
                'expected_status' => $this->expected_status,
                'interval_minutes' => $this->interval_minutes,
                'timeout_seconds' => $this->timeout_seconds,
                'is_active' => $this->is_active,
            ];

            if ($this->editingCheckId) {
                $check = HealthCheck::findOrFail($this->editingCheckId);
                $check->update($data);
            } else {
                $check = HealthCheck::create($data);
            }

            // Sync notification channels
            $syncData = [];
            foreach ($this->selectedChannels as $channelId) {
                $syncData[$channelId] = [
                    'notify_on_failure' => true,
                    'notify_on_recovery' => true,
                ];
            }
            $check->notificationChannels()->sync($syncData);

            $this->dispatch('notification', type: 'success', message: 'Health check saved successfully');
            $this->closeCreateModal();
            $this->reset(['healthChecks']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to save health check: ' . $e->getMessage());
        }
    }

    public function deleteCheck(int $checkId): void
    {
        try {
            HealthCheck::findOrFail($checkId)->delete();
            $this->dispatch('notification', type: 'success', message: 'Health check deleted successfully');
            $this->reset(['healthChecks']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to delete health check: ' . $e->getMessage());
        }
    }

    public function runCheck(int $checkId): void
    {
        try {
            $check = HealthCheck::findOrFail($checkId);
            $this->healthCheckService->runCheck($check);
            $this->dispatch('notification', type: 'success', message: 'Health check executed successfully');
            $this->reset(['healthChecks']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Health check failed: ' . $e->getMessage());
        }
    }

    public function toggleCheckActive(int $checkId): void
    {
        try {
            $check = HealthCheck::findOrFail($checkId);
            $check->update(['is_active' => !$check->is_active]);
            $this->dispatch('notification', type: 'success', message: 'Health check status updated');
            $this->reset(['healthChecks']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to update health check: ' . $e->getMessage());
        }
    }

    public function saveChannel(): void
    {
        $this->validate();

        try {
            $config = match ($this->channel_type) {
                'email' => ['email' => $this->channel_email],
                'slack' => ['webhook_url' => $this->channel_slack_webhook],
                'discord' => ['webhook_url' => $this->channel_discord_webhook],
            };

            $data = [
                'user_id' => auth()->id(),
                'type' => $this->channel_type,
                'name' => $this->channel_name,
                'config' => $config,
                'is_active' => $this->channel_is_active,
            ];

            if ($this->editingChannelId) {
                $channel = NotificationChannel::findOrFail($this->editingChannelId);
                $channel->update($data);
            } else {
                NotificationChannel::create($data);
            }

            $this->dispatch('notification', type: 'success', message: 'Notification channel saved successfully');
            $this->closeChannelModal();
            $this->reset(['notificationChannels']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to save notification channel: ' . $e->getMessage());
        }
    }

    public function editChannel(int $channelId): void
    {
        $channel = NotificationChannel::findOrFail($channelId);

        $this->editingChannelId = $channelId;
        $this->channel_type = $channel->type;
        $this->channel_name = $channel->name;
        $this->channel_is_active = $channel->is_active;

        match ($channel->type) {
            'email' => $this->channel_email = $channel->config['email'] ?? null,
            'slack' => $this->channel_slack_webhook = $channel->config['webhook_url'] ?? null,
            'discord' => $this->channel_discord_webhook = $channel->config['webhook_url'] ?? null,
        };

        $this->showChannelModal = true;
    }

    public function deleteChannel(int $channelId): void
    {
        try {
            NotificationChannel::findOrFail($channelId)->delete();
            $this->dispatch('notification', type: 'success', message: 'Notification channel deleted successfully');
            $this->reset(['notificationChannels']);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to delete notification channel: ' . $e->getMessage());
        }
    }

    public function testChannel(int $channelId): void
    {
        try {
            $channel = NotificationChannel::findOrFail($channelId);
            $success = $this->notificationService->sendTestNotification($channel);

            if ($success) {
                $this->dispatch('notification', type: 'success', message: 'Test notification sent successfully');
            } else {
                $this->dispatch('notification', type: 'error', message: 'Failed to send test notification');
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Test notification failed: ' . $e->getMessage());
        }
    }

    private function resetCheckForm(): void
    {
        $this->reset([
            'editingCheckId',
            'project_id',
            'server_id',
            'check_type',
            'target_url',
            'expected_status',
            'interval_minutes',
            'timeout_seconds',
            'is_active',
            'selectedChannels',
        ]);

        $this->check_type = 'http';
        $this->expected_status = 200;
        $this->interval_minutes = 5;
        $this->timeout_seconds = 30;
        $this->is_active = true;
    }

    private function resetChannelForm(): void
    {
        $this->reset([
            'editingChannelId',
            'channel_type',
            'channel_name',
            'channel_email',
            'channel_slack_webhook',
            'channel_discord_webhook',
            'channel_is_active',
        ]);

        $this->channel_type = 'email';
        $this->channel_is_active = true;
    }

    #[On('refresh-health-checks')]
    public function refreshHealthChecks(): void
    {
        $this->reset(['healthChecks', 'notificationChannels']);
    }

    public function render()
    {
        return view('livewire.settings.health-check-manager');
    }
}
