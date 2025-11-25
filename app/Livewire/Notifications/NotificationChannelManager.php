<?php

namespace App\Livewire\Notifications;

use App\Models\NotificationChannel;
use App\Services\Notifications\SlackDiscordNotificationService;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationChannelManager extends Component
{
    use WithPagination;

    public $showAddChannelModal = false;
    public $editingChannel = null;

    // Channel form fields
    public $name = '';
    public $provider = 'slack';
    public $webhookUrl = '';
    public $webhookSecret = '';
    public $enabled = true;
    public $events = [];

    // Test notification
    public $testMessage = '';
    public $testingChannel = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'provider' => 'required|in:slack,discord,teams,webhook',
        'webhookUrl' => 'required|url',
        'webhookSecret' => 'nullable|string',
        'enabled' => 'boolean',
        'events' => 'required|array|min:1',
    ];

    public $availableEvents = [
        'deployment_started' => 'Deployment Started',
        'deployment_completed' => 'Deployment Completed',
        'deployment_failed' => 'Deployment Failed',
        'rollback_completed' => 'Rollback Completed',
        'health_check_failed' => 'Health Check Failed',
        'ssl_expiring' => 'SSL Certificate Expiring',
        'storage_warning' => 'Storage Warning',
        'security_alert' => 'Security Alert',
        'backup_completed' => 'Backup Completed',
        'system_error' => 'System Error',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.notifications.channel-manager', [
            'channels' => NotificationChannel::paginate(10),
        ]);
    }

    public function addChannel()
    {
        $this->resetForm();
        $this->showAddChannelModal = true;
    }

    public function editChannel(NotificationChannel $channel)
    {
        $this->editingChannel = $channel;
        $this->name = $channel->name;
        $this->provider = $channel->provider;
        $this->webhookUrl = $channel->webhook_url;
        $this->webhookSecret = $channel->webhook_secret;
        $this->enabled = $channel->enabled;
        $this->events = $channel->events ?? [];
        $this->showAddChannelModal = true;
    }

    public function saveChannel()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'provider' => $this->provider,
            'webhook_url' => $this->webhookUrl,
            'webhook_secret' => $this->webhookSecret ? encrypt($this->webhookSecret) : null,
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
    }

    public function deleteChannel(NotificationChannel $channel)
    {
        $channel->delete();
        $this->dispatch('notify', type: 'success', message: 'Notification channel deleted successfully');
    }

    public function toggleChannel(NotificationChannel $channel)
    {
        $channel->update(['enabled' => !$channel->enabled]);
        $this->dispatch('notify', type: 'success', message: $channel->enabled ? 'Channel enabled' : 'Channel disabled');
    }

    public function testChannel(NotificationChannel $channel)
    {
        try {
            $service = app(SlackDiscordNotificationService::class);

            if ($service->testChannel($channel)) {
                $this->dispatch('notify', type: 'success', message: 'Test notification sent successfully!');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Failed to send test notification');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }

    public function toggleEvent($event)
    {
        if (in_array($event, $this->events)) {
            $this->events = array_values(array_diff($this->events, [$event]));
        } else {
            $this->events[] = $event;
        }
    }

    private function resetForm()
    {
        $this->name = '';
        $this->provider = 'slack';
        $this->webhookUrl = '';
        $this->webhookSecret = '';
        $this->enabled = true;
        $this->events = ['deployment_completed', 'deployment_failed'];
        $this->editingChannel = null;
    }
}