<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class DeploymentNotifications extends Component
{
    public $notifications = [];
    public $soundEnabled = true;
    public $desktopNotificationsEnabled = false;

    public function mount()
    {
        $this->notifications = collect();
        $this->soundEnabled = auth()->user()->notification_sound ?? true;
        $this->desktopNotificationsEnabled = auth()->user()->desktop_notifications ?? false;
    }

    #[On('echo-private:user.{userId},deployment.status.updated')]
    public function onDeploymentStatusUpdated($event)
    {
        $this->addNotification($event);

        if ($this->soundEnabled) {
            $this->dispatch('play-notification-sound', type: $event['type']);
        }

        if ($this->desktopNotificationsEnabled && $this->shouldShowDesktopNotification($event['type'])) {
            $this->dispatch('show-desktop-notification',
                title: "Deployment {$event['status']}",
                body: $event['message'],
                icon: $this->getIconForType($event['type'])
            );
        }
    }

    public function addNotification($event)
    {
        $this->notifications->prepend([
            'id' => uniqid(),
            'deployment_id' => $event['deployment_id'],
            'project_name' => $event['project_name'],
            'message' => $event['message'],
            'type' => $event['type'],
            'status' => $event['status'],
            'timestamp' => now(),
            'read' => false,
        ]);

        // Keep only last 10 notifications
        if ($this->notifications->count() > 10) {
            $this->notifications = $this->notifications->take(10);
        }
    }

    public function markAsRead($notificationId)
    {
        $this->notifications = $this->notifications->map(function ($notification) use ($notificationId) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
            }
            return $notification;
        });
    }

    public function clearAll()
    {
        $this->notifications = collect();
    }

    public function toggleSound()
    {
        $this->soundEnabled = !$this->soundEnabled;
        auth()->user()->update(['notification_sound' => $this->soundEnabled]);
    }

    public function toggleDesktopNotifications()
    {
        $this->desktopNotificationsEnabled = !$this->desktopNotificationsEnabled;
        auth()->user()->update(['desktop_notifications' => $this->desktopNotificationsEnabled]);

        if ($this->desktopNotificationsEnabled) {
            $this->dispatch('request-notification-permission');
        }
    }

    private function shouldShowDesktopNotification($type)
    {
        return in_array($type, ['success', 'error']);
    }

    private function getIconForType($type)
    {
        return match($type) {
            'success' => '/icons/success.png',
            'error' => '/icons/error.png',
            'warning' => '/icons/warning.png',
            default => '/icons/info.png',
        };
    }

    public function render()
    {
        return view('livewire.notifications.deployment-notifications');
    }
}