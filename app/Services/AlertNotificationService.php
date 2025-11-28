<?php

namespace App\Services;

use App\Models\ResourceAlert;
use App\Models\AlertHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class AlertNotificationService
{
    /**
     * Send alert notification to all configured channels
     */
    public function send(ResourceAlert $alert, AlertHistory $history): array
    {
        $results = [];
        $channels = $alert->notification_channels ?? [];

        foreach ($channels as $channel => $config) {
            if (!is_array($config) || empty($config)) {
                continue;
            }

            try {
                $result = match($channel) {
                    'email' => $this->sendEmail($config, $alert, $history),
                    'slack' => $this->sendSlack($config, $alert, $history),
                    'discord' => $this->sendDiscord($config, $alert, $history),
                    default => ['success' => false, 'message' => "Unknown channel: {$channel}"],
                };

                $results[$channel] = $result;

            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} notification", [
                    'alert_id' => $alert->id,
                    'history_id' => $history->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$channel] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Send email notification
     */
    public function sendEmail(array $config, ResourceAlert $alert, AlertHistory $history): array
    {
        $email = $config['email'] ?? null;

        if (!$email) {
            return ['success' => false, 'message' => 'No email address configured'];
        }

        try {
            Mail::raw(
                $this->formatEmailMessage($alert, $history),
                function ($message) use ($email, $alert, $history) {
                    $message->to($email)
                        ->subject($this->getEmailSubject($alert, $history));
                }
            );

            return ['success' => true, 'message' => 'Email sent'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Slack notification
     */
    public function sendSlack(array $config, ResourceAlert $alert, AlertHistory $history): array
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return ['success' => false, 'message' => 'No Slack webhook URL configured'];
        }

        try {
            $response = Http::post($webhookUrl, [
                'text' => $history->message,
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => $this->getAlertEmoji($history) . ' ' . $this->getNotificationTitle($alert, $history),
                        ],
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Server:*\n{$alert->server->name}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Resource:*\n{$alert->resource_type_label}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Current Value:*\n{$this->formatValue($history->current_value, $alert->resource_type)}",
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Threshold:*\n{$alert->threshold_display}",
                            ],
                        ],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "Time: {$history->created_at->format('Y-m-d H:i:s')}",
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Slack notification sent'];
            }

            return ['success' => false, 'message' => 'Slack API error: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Discord notification
     */
    public function sendDiscord(array $config, ResourceAlert $alert, AlertHistory $history): array
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return ['success' => false, 'message' => 'No Discord webhook URL configured'];
        }

        try {
            $color = $history->status === 'triggered' ? 15158332 : 3066993; // Red or Green

            $response = Http::post($webhookUrl, [
                'embeds' => [
                    [
                        'title' => $this->getAlertEmoji($history) . ' ' . $this->getNotificationTitle($alert, $history),
                        'description' => $history->message,
                        'color' => $color,
                        'fields' => [
                            [
                                'name' => 'Server',
                                'value' => $alert->server->name,
                                'inline' => true,
                            ],
                            [
                                'name' => 'Resource',
                                'value' => $alert->resource_type_label,
                                'inline' => true,
                            ],
                            [
                                'name' => 'Current Value',
                                'value' => $this->formatValue($history->current_value, $alert->resource_type),
                                'inline' => true,
                            ],
                            [
                                'name' => 'Threshold',
                                'value' => $alert->threshold_display,
                                'inline' => true,
                            ],
                        ],
                        'timestamp' => $history->created_at->toIso8601String(),
                        'footer' => [
                            'text' => 'DevFlow Pro - Resource Alert',
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Discord notification sent'];
            }

            return ['success' => false, 'message' => 'Discord API error: ' . $response->body()];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format email message
     */
    protected function formatEmailMessage(ResourceAlert $alert, AlertHistory $history): string
    {
        $lines = [
            $this->getNotificationTitle($alert, $history),
            '',
            $history->message,
            '',
            'Details:',
            "- Server: {$alert->server->name}",
            "- IP Address: {$alert->server->ip_address}",
            "- Resource: {$alert->resource_type_label}",
            "- Current Value: {$this->formatValue($history->current_value, $alert->resource_type)}",
            "- Threshold: {$alert->threshold_display}",
            "- Time: {$history->created_at->format('Y-m-d H:i:s')}",
            '',
            '--',
            'DevFlow Pro - Automated Server Monitoring',
        ];

        return implode("\n", $lines);
    }

    /**
     * Get email subject
     */
    protected function getEmailSubject(ResourceAlert $alert, AlertHistory $history): string
    {
        $prefix = $history->status === 'triggered' ? '[ALERT]' : '[RESOLVED]';

        return "{$prefix} {$alert->resource_type_label} on {$alert->server->name}";
    }

    /**
     * Get notification title
     */
    protected function getNotificationTitle(ResourceAlert $alert, AlertHistory $history): string
    {
        if ($history->status === 'triggered') {
            return "Resource Alert Triggered";
        }

        return "Resource Alert Resolved";
    }

    /**
     * Get alert emoji based on status
     */
    protected function getAlertEmoji(AlertHistory $history): string
    {
        return $history->status === 'triggered' ? '🚨' : '✅';
    }

    /**
     * Format value with appropriate unit
     */
    protected function formatValue(float $value, string $resourceType): string
    {
        $unit = in_array($resourceType, ['cpu', 'memory', 'disk']) ? '%' : '';

        return number_format($value, 2) . $unit;
    }
}
