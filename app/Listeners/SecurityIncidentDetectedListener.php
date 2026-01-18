<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SecurityIncidentDetected;
use App\Models\NotificationChannel;
use App\Models\SecurityIncident;
use App\Services\NotificationService;
use App\Services\Security\IncidentResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SecurityIncidentDetectedListener implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService,
        protected IncidentResponseService $incidentResponseService
    ) {}

    public function handle(SecurityIncidentDetected $event): void
    {
        $incident = $event->incident;
        $server = $incident->server;

        Log::info('Security incident detected', [
            'incident_id' => $incident->id,
            'server_id' => $server->id,
            'type' => $incident->incident_type,
            'severity' => $incident->severity,
        ]);

        // Send notifications based on severity
        $this->sendNotifications($incident);

        // Auto-remediate if enabled and severity is critical/high
        if ($server->auto_remediation_enabled && in_array($incident->severity, [
            SecurityIncident::SEVERITY_CRITICAL,
            SecurityIncident::SEVERITY_HIGH,
        ], true)) {
            $result = $this->incidentResponseService->autoRemediate($incident);
            Log::info('Auto-remediation executed', [
                'incident_id' => $incident->id,
                'success' => $result['success'],
                'actions' => $result['actions'],
            ]);
        }
    }

    protected function sendNotifications(SecurityIncident $incident): void
    {
        $server = $incident->server;
        $severity = $incident->severity;

        // Determine which event to match
        $eventType = "security.incident.{$severity}";

        // Get all active notification channels that listen for security events
        $channels = NotificationChannel::where('is_active', true)
            ->where(function ($query) use ($eventType) {
                $query->whereJsonContains('events', $eventType)
                    ->orWhereJsonContains('events', 'security.incident.*')
                    ->orWhereJsonContains('events', 'security.*');
            })
            ->get();

        if ($channels->isEmpty()) {
            Log::info('No notification channels configured for security incidents');

            return;
        }

        $message = $this->buildNotificationMessage($incident);

        foreach ($channels as $channel) {
            try {
                $this->sendToChannel($channel, $incident, $message);
            } catch (\Exception $e) {
                Log::error('Failed to send security notification', [
                    'channel_id' => $channel->id,
                    'channel_type' => $channel->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function buildNotificationMessage(SecurityIncident $incident): string
    {
        $severity = strtoupper($incident->severity);
        $server = $incident->server;

        return <<<MSG
🚨 *SECURITY INCIDENT DETECTED*

*Severity:* {$severity}
*Server:* {$server->name} ({$server->ip_address})
*Type:* {$incident->title}

{$incident->description}

*Detected at:* {$incident->detected_at->format('Y-m-d H:i:s')}

Please investigate immediately.
MSG;
    }

    protected function sendToChannel(NotificationChannel $channel, SecurityIncident $incident, string $message): void
    {
        $data = [
            'incident_id' => $incident->id,
            'server_id' => $incident->server_id,
            'server_name' => $incident->server->name,
            'severity' => $incident->severity,
            'incident_type' => $incident->incident_type,
        ];

        switch ($channel->type) {
            case 'email':
                $this->notificationService->sendEmail(
                    $channel->config['email'] ?? '',
                    "[{$incident->severity}] Security Incident: {$incident->title}",
                    $message
                );

                break;

            case 'slack':
                $this->notificationService->sendToSlack($channel, $message, $data);

                break;

            case 'discord':
                $this->notificationService->sendToDiscord($channel, $message, $data);

                break;

            case 'webhook':
                $this->notificationService->sendWebhook($channel, [
                    'event' => 'security.incident.detected',
                    'incident' => $incident->toArray(),
                    'server' => $incident->server->toArray(),
                ]);

                break;
        }

        Log::info('Security notification sent', [
            'channel_id' => $channel->id,
            'channel_type' => $channel->type,
            'incident_id' => $incident->id,
        ]);
    }
}
