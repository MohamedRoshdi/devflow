<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SecurityIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityThreatNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SecurityIncident $incident
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityEmoji = match ($this->incident->severity) {
            SecurityIncident::SEVERITY_CRITICAL => 'CRITICAL',
            SecurityIncident::SEVERITY_HIGH => 'HIGH',
            SecurityIncident::SEVERITY_MEDIUM => 'MEDIUM',
            default => 'LOW',
        };

        return (new MailMessage)
            ->subject("[{$severityEmoji}] Security Threat: {$this->incident->title}")
            ->line("A security threat has been detected on server **{$this->incident->server->name}**.")
            ->line("**Type:** ".(SecurityIncident::getIncidentTypes()[$this->incident->incident_type] ?? $this->incident->incident_type))
            ->line("**Severity:** {$severityEmoji}")
            ->line("**Details:** {$this->incident->description}")
            ->line($this->incident->auto_remediated
                ? 'Auto-remediation has been applied.'
                : '**Action required** - Please investigate immediately.')
            ->action('View Incident', route('security.incidents'))
            ->line('This is an automated security alert from DevFlow Pro Security Guardian.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'security_threat',
            'incident_id' => $this->incident->id,
            'server_id' => $this->incident->server_id,
            'server_name' => $this->incident->server->name,
            'incident_type' => $this->incident->incident_type,
            'severity' => $this->incident->severity,
            'title' => $this->incident->title,
            'auto_remediated' => $this->incident->auto_remediated,
            'detected_at' => $this->incident->detected_at,
        ];
    }
}
