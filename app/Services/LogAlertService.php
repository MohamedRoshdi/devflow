<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogAlert;
use App\Models\SystemLog;
use App\Notifications\LogAlertTriggered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LogAlertService
{
    /**
     * Process all active alerts against recent logs.
     */
    public function processAlerts(?int $serverId = null): array
    {
        $alerts = LogAlert::active();

        if ($serverId) {
            $alerts->where('server_id', $serverId);
        }

        $alerts = $alerts->get();
        $results = [];

        foreach ($alerts as $alert) {
            try {
                $result = $this->processAlert($alert);
                $results[] = [
                    'alert_id' => $alert->id,
                    'alert_name' => $alert->name,
                    'status' => $result['triggered'] ? 'triggered' : 'ok',
                    'match_count' => $result['match_count'],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to process alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'alert_id' => $alert->id,
                    'alert_name' => $alert->name,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single alert.
     */
    public function processAlert(LogAlert $alert): array
    {
        // Get recent logs within time window
        $logs = $this->getRecentLogs($alert);

        // Count matches
        $matchingLogs = $logs->filter(function (SystemLog $log) use ($alert) {
            return $this->logMatchesAlert($log, $alert);
        });

        $matchCount = $matchingLogs->count();

        // Check if threshold is met
        if ($alert->shouldTrigger($matchCount)) {
            $this->triggerAlert($alert, $matchingLogs);

            return [
                'triggered' => true,
                'match_count' => $matchCount,
                'logs' => $matchingLogs,
            ];
        }

        return [
            'triggered' => false,
            'match_count' => $matchCount,
        ];
    }

    /**
     * Get recent logs for alert evaluation.
     *
     * @return Collection<int, SystemLog>
     */
    protected function getRecentLogs(LogAlert $alert): Collection
    {
        $query = SystemLog::where('logged_at', '>=', now()->subSeconds($alert->time_window));

        if ($alert->server_id) {
            $query->where('server_id', $alert->server_id);
        }

        if ($alert->log_type) {
            $query->where('log_type', $alert->log_type);
        }

        if ($alert->log_level) {
            $query->where('level', $alert->log_level);
        }

        return $query->orderBy('logged_at', 'desc')->get();
    }

    /**
     * Check if a log matches an alert pattern.
     */
    protected function logMatchesAlert(SystemLog $log, LogAlert $alert): bool
    {
        return $alert->matches($log->message);
    }

    /**
     * Trigger alert and send notifications.
     *
     * @param Collection<int, SystemLog> $matchingLogs
     */
    protected function triggerAlert(LogAlert $alert, Collection $matchingLogs): void
    {
        // Record trigger
        $alert->recordTrigger();

        // Send notifications
        foreach ($alert->getNotificationChannels() as $channel) {
            try {
                match ($channel) {
                    'email' => $this->sendEmailNotification($alert, $matchingLogs),
                    'slack' => $this->sendSlackNotification($alert, $matchingLogs),
                    'webhook' => $this->sendWebhookNotification($alert, $matchingLogs),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error('Failed to send alert notification', [
                    'alert_id' => $alert->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send email notification.
     *
     * @param Collection<int, SystemLog> $logs
     */
    protected function sendEmailNotification(LogAlert $alert, Collection $logs): void
    {
        $email = $alert->notification_config['email'] ?? $alert->user->email;

        Notification::route('mail', $email)
            ->notify(new LogAlertTriggered($alert, $logs));
    }

    /**
     * Send Slack notification.
     *
     * @param Collection<int, SystemLog> $logs
     */
    protected function sendSlackNotification(LogAlert $alert, Collection $logs): void
    {
        $webhookUrl = $alert->notification_config['slack_webhook_url'] ?? config('services.slack.webhook_url');

        if (!$webhookUrl) {
            throw new \RuntimeException('Slack webhook URL not configured');
        }

        $message = $this->buildSlackMessage($alert, $logs);

        Http::post($webhookUrl, $message);
    }

    /**
     * Build Slack message payload.
     *
     * @param Collection<int, SystemLog> $logs
     * @return array<string, mixed>
     */
    protected function buildSlackMessage(LogAlert $alert, Collection $logs): array
    {
        $serverName = $alert->server?->name ?? 'All Servers';
        $logSample = $logs->take(3)->pluck('message')->implode("\n");

        return [
            'text' => "🚨 Log Alert: {$alert->name}",
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => "🚨 {$alert->name}",
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Server:*\n{$serverName}",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Matches:*\n{$logs->count()} logs",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Pattern:*\n`{$alert->pattern}`",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Triggered:*\n" . now()->toDateTimeString(),
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Sample Logs:*\n```{$logSample}```",
                    ],
                ],
            ],
        ];
    }

    /**
     * Send webhook notification.
     *
     * @param Collection<int, SystemLog> $logs
     */
    protected function sendWebhookNotification(LogAlert $alert, Collection $logs): void
    {
        $webhookUrl = $alert->notification_config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            throw new \RuntimeException('Webhook URL not configured');
        }

        $payload = [
            'alert' => [
                'id' => $alert->id,
                'name' => $alert->name,
                'description' => $alert->description,
                'pattern' => $alert->pattern,
                'server_id' => $alert->server_id,
                'server_name' => $alert->server?->name,
            ],
            'matches' => $logs->map(function (SystemLog $log) {
                return [
                    'id' => $log->id,
                    'message' => $log->message,
                    'level' => $log->level,
                    'log_type' => $log->log_type,
                    'source' => $log->source,
                    'logged_at' => $log->logged_at?->toIso8601String(),
                ];
            })->toArray(),
            'triggered_at' => now()->toIso8601String(),
        ];

        $headers = $alert->notification_config['webhook_headers'] ?? [];

        Http::withHeaders($headers)->post($webhookUrl, $payload);
    }

    /**
     * Test an alert pattern against sample logs.
     */
    public function testAlert(LogAlert $alert, ?int $sampleSize = 10): array
    {
        $logs = SystemLog::query()
            ->when($alert->server_id, fn($q) => $q->where('server_id', $alert->server_id))
            ->when($alert->log_type, fn($q) => $q->where('log_type', $alert->log_type))
            ->when($alert->log_level, fn($q) => $q->where('level', $alert->log_level))
            ->latest()
            ->limit(100)
            ->get();

        $matches = $logs->filter(function (SystemLog $log) use ($alert) {
            return $alert->matches($log->message);
        })->take($sampleSize);

        return [
            'total_logs_checked' => $logs->count(),
            'matches_found' => $matches->count(),
            'would_trigger' => $alert->shouldTrigger($matches->count()),
            'sample_matches' => $matches->map(fn($log) => [
                'id' => $log->id,
                'message' => $log->message,
                'logged_at' => $log->logged_at?->toDateTimeString(),
            ])->toArray(),
        ];
    }

    /**
     * Get alert statistics.
     */
    public function getAlertStatistics(): array
    {
        return [
            'total_alerts' => LogAlert::count(),
            'active_alerts' => LogAlert::active()->count(),
            'total_triggers' => LogAlert::sum('trigger_count'),
            'recently_triggered' => LogAlert::whereNotNull('last_triggered_at')
                ->where('last_triggered_at', '>=', now()->subDay())
                ->count(),
            'alerts_by_server' => LogAlert::selectRaw('server_id, COUNT(*) as count')
                ->groupBy('server_id')
                ->get()
                ->pluck('count', 'server_id')
                ->toArray(),
        ];
    }
}
