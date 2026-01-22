<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LogAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LogAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var Collection<int, \App\Models\SystemLog> */
    public Collection $matchingLogs;

    /**
     * Create a new notification instance.
     *
     * @param Collection<int, \App\Models\SystemLog> $matchingLogs
     */
    public function __construct(
        public LogAlert $alert,
        Collection $matchingLogs
    ) {
        $this->matchingLogs = $matchingLogs;
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $serverName = $this->alert->server?->name ?? 'All Servers';
        $logCount = $this->matchingLogs->count();

        return (new MailMessage)
            ->error()
            ->subject("🚨 Log Alert: {$this->alert->name}")
            ->greeting("Alert Triggered: {$this->alert->name}")
            ->line("A log alert has been triggered on **{$serverName}**.")
            ->line("**Pattern:** `{$this->alert->pattern}`")
            ->line("**Matches:** {$logCount} logs in the last {$this->alert->time_window} seconds")
            ->line("**Threshold:** {$this->alert->threshold}")
            ->line('')
            ->line('**Sample Log Messages:**')
            ->lines($this->getSampleLogs())
            ->action('View System Logs', route('logs.system'))
            ->line('Please investigate this alert and take appropriate action.');
    }

    /**
     * Get sample log messages for the email.
     *
     * @return array<string>
     */
    protected function getSampleLogs(): array
    {
        return $this->matchingLogs
            ->take(5)
            ->map(function ($log) {
                $loggedAt = $log->logged_at?->format('Y-m-d H:i:s') ?? 'N/A';
                return sprintf(
                    '[%s] [%s] %s',
                    $loggedAt,
                    strtoupper($log->level),
                    $log->message
                );
            })
            ->toArray();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'alert_name' => $this->alert->name,
            'server_id' => $this->alert->server_id,
            'match_count' => $this->matchingLogs->count(),
            'triggered_at' => now()->toIso8601String(),
        ];
    }
}
