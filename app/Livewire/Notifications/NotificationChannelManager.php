<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\NotificationChannel;
use App\Models\Project;
use App\Services\NotificationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationChannelManager extends Component
{
    use WithPagination;

    public bool $showAddChannelModal = false;

    public ?NotificationChannel $editingChannel = null;

    // Channel form fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:slack,discord,email,webhook,teams')]
    public string $provider = 'slack';

    #[Validate('nullable|exists:projects,id')]
    public ?int $projectId = null;

    public string $webhookUrl = '';

    public string $webhookSecret = '';

    public string $email = '';

    public bool $enabled = true;

    /**
     * @var array<int, string>
     */
    #[Validate('required|array|min:1')]
    public array $events = [];

    // Test notification
    public string $testMessage = '';

    public ?NotificationChannel $testingChannel = null;

    private NotificationService $notificationService;

    public function boot(NotificationService $notificationService): void
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    #[Computed]
    public function projects(): \Illuminate\Database\Eloquent\Collection
    {
        return Project::orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableEvents(): array
    {
        return [
            'deployment.started' => 'Deployment Started',
            'deployment.success' => 'Deployment Successful',
            'deployment.failed' => 'Deployment Failed',
            'deployment.approved' => 'Deployment Approved',
            'deployment.rejected' => 'Deployment Rejected',
            'deployment.rolled_back' => 'Deployment Rolled Back',
            'server.down' => 'Server Down',
            'server.recovered' => 'Server Recovered',
            'health_check.failed' => 'Health Check Failed',
            'health_check.recovered' => 'Health Check Recovered',
            'ssl.expiring_soon' => 'SSL Certificate Expiring Soon',
            'ssl.expired' => 'SSL Certificate Expired',
            'storage.warning' => 'Storage Warning',
            'backup.completed' => 'Backup Completed',
            'backup.failed' => 'Backup Failed',
        ];
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, NotificationChannel>
     */
    #[Computed]
    public function channels(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return NotificationChannel::with('project')
            ->latest()
            ->paginate(15);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.notifications.channel-manager', [
            'channels' => $this->channels,
        ]);
    }

    public function addChannel(): void
    {
        $this->resetForm();
        $this->showAddChannelModal = true;
    }

    public function editChannel(NotificationChannel $channel): void
    {
        $this->editingChannel = $channel;
        $this->name = $channel->name;
        $this->provider = $channel->type ?? $channel->provider;
        $this->projectId = $channel->project_id;
        $this->enabled = $channel->enabled;
        $this->events = $channel->events ?? [];

        // Load type-specific config
        $config = $channel->config ?? [];
        if ($this->provider === 'email') {
            $this->email = $config['email'] ?? '';
        } else {
            $this->webhookUrl = $config['webhook_url'] ?? $channel->webhook_url ?? '';
        }

        $this->webhookSecret = $channel->webhook_secret ?? '';
        $this->showAddChannelModal = true;
    }

    public function saveChannel(): void
    {
        // Custom validation based on provider
        $rules = [
            'name' => 'required|string|max:255',
            'provider' => 'required|in:slack,discord,email,webhook,teams',
            'projectId' => 'nullable|exists:projects,id',
            'events' => 'required|array|min:1',
        ];

        if ($this->provider === 'email') {
            $rules['email'] = 'required|email';
        } else {
            $rules['webhookUrl'] = 'required|url';
            if ($this->provider === 'webhook') {
                $rules['webhookSecret'] = 'nullable|string';
            }
        }

        $this->validate($rules);

        // Build config based on type
        $config = match ($this->provider) {
            'slack', 'discord', 'webhook', 'teams' => ['webhook_url' => $this->webhookUrl],
            'email' => ['email' => $this->email],
            default => [],
        };

        $data = [
            'name' => $this->name,
            'type' => $this->provider,
            'provider' => $this->provider, // Keep for backward compatibility
            'project_id' => $this->projectId,
            'config' => $config,
            'webhook_url' => $this->webhookUrl ?: null,
            'webhook_secret' => $this->webhookSecret ?: null,
            'enabled' => $this->enabled,
            'events' => $this->events,
        ];

        if ($this->editingChannel) {
            $this->editingChannel->update($data);
            $this->dispatch('notify', type: 'success', message: 'Notification channel updated successfully');
        } else {
            NotificationChannel::create($data);
            $this->dispatch('notify', type: 'success', message: 'Notification channel added successfully');
        }

        $this->showAddChannelModal = false;
        $this->resetForm();
        unset($this->channels);
    }

    public function deleteChannel(NotificationChannel $channel): void
    {
        $channel->delete();
        $this->dispatch('notify', type: 'success', message: 'Notification channel deleted successfully');
        unset($this->channels);
    }

    public function toggleChannel(NotificationChannel $channel): void
    {
        $channel->update(['enabled' => ! $channel->enabled]);
        $this->dispatch('notify', type: 'success', message: $channel->enabled ? 'Channel enabled' : 'Channel disabled');
        unset($this->channels);
    }

    public function testChannel(NotificationChannel $channel): void
    {
        try {
            $success = $this->notificationService->sendTestNotification($channel);

            if ($success) {
                $this->dispatch('notify', type: 'success', message: 'Test notification sent successfully! Check your channel.');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Failed to send test notification. Check the logs for details.');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Test failed: '.$e->getMessage());
        }
    }

    public function toggleEvent(string $event): void
    {
        if (in_array($event, $this->events)) {
            $this->events = array_values(array_diff($this->events, [$event]));
        } else {
            $this->events[] = $event;
        }
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->provider = 'slack';
        $this->projectId = null;
        $this->webhookUrl = '';
        $this->webhookSecret = '';
        $this->email = '';
        $this->enabled = true;
        $this->events = ['deployment.success', 'deployment.failed'];
        $this->editingChannel = null;
    }
}
