<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class DeploymentNotifications extends Component
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    public Collection $notifications;

    public bool $soundEnabled = true;

    public bool $desktopNotificationsEnabled = false;

    public function mount(): void
    {
        $this->notifications = collect();
        $this->soundEnabled = auth()->user()->notification_sound ?? true;
        $this->desktopNotificationsEnabled = auth()->user()->desktop_notifications ?? false;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    #[On('echo-private:user.{userId},deployment.status.updated')]
    public function onDeploymentStatusUpdated(array $event): void
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

    /**
     * @param  array<string, mixed>  $event
     */
    public function addNotification(array $event): void
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

    public function markAsRead(string $notificationId): void
    {
        $this->notifications = $this->notifications->map(function (array $notification) use ($notificationId): array {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
            }

            return $notification;
        });
    }

    public function clearAll(): void
    {
        $this->notifications = collect();
    }

    public function toggleSound(): void
    {
        $this->soundEnabled = ! $this->soundEnabled;
        $user = auth()->user();
        if ($user !== null) {
            $user->update(['notification_sound' => $this->soundEnabled]);
        }
    }

    public function toggleDesktopNotifications(): void
    {
        $this->desktopNotificationsEnabled = ! $this->desktopNotificationsEnabled;
        $user = auth()->user();
        if ($user !== null) {
            $user->update(['desktop_notifications' => $this->desktopNotificationsEnabled]);
        }

        if ($this->desktopNotificationsEnabled) {
            $this->dispatch('request-notification-permission');
        }
    }

    private function shouldShowDesktopNotification(string $type): bool
    {
        return in_array($type, ['success', 'error'], true);
    }

    private function getIconForType(string $type): string
    {
        return match ($type) {
            'success' => '/icons/success.png',
            'error' => '/icons/error.png',
            'warning' => '/icons/warning.png',
            default => '/icons/info.png',
        };
    }

    public function render(): View
    {
        return view('livewire.notifications.deployment-notifications');
    }
}
