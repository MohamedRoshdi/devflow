<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{HealthCheck, HealthCheckResult, NotificationChannel, Deployment, NotificationLog};
use Illuminate\Support\Facades\{Http, Log, Mail, Queue};

class NotificationService
{
    public function sendEmail(string $email, string $subject, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Email notification failed", [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendSlack(string $webhookUrl, string $message): bool
    {
        try {
            $response = Http::post($webhookUrl, [
                'text' => $message,
                'mrkdwn' => true,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Slack notification failed", [
                'webhook_url' => substr($webhookUrl, 0, 30) . '...',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send rich Slack notification using Block Kit
     */
    public function sendToSlack(NotificationChannel $channel, string $message, array $data = []): bool
    {
        try {
            $webhookUrl = $channel->config['webhook_url'] ?? $channel->webhook_url ?? '';

            if (empty($webhookUrl)) {
                throw new \Exception('Webhook URL not configured');
            }

            $blocks = $this->buildSlackBlocks($message, $data);

            $response = Http::post($webhookUrl, [
                'text' => $message,
                'blocks' => $blocks,
            ]);

            $this->logNotification($channel, $data['event'] ?? 'unknown', $response->successful());

            return $response->successful();
        } catch (\Exception $e) {
            $this->logNotification($channel, $data['event'] ?? 'unknown', false, $e->getMessage());
            Log::error("Slack notification failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendDiscord(string $webhookUrl, string $message): bool
    {
        try {
            $response = Http::post($webhookUrl, [
                'content' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Discord notification failed", [
                'webhook_url' => substr($webhookUrl, 0, 30) . '...',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyHealthCheckFailure(HealthCheck $check, HealthCheckResult $result): void
    {
        $channels = $check->notificationChannels()
            ->where('is_active', true)
            ->wherePivot('notify_on_failure', true)
            ->get();

        $subject = "Health Check Failed: {$check->display_name}";
        $message = $this->buildFailureMessage($check, $result);

        foreach ($channels as $channel) {
            $this->sendNotification($channel, $subject, $message);
        }
    }

    public function notifyHealthCheckRecovery(HealthCheck $check): void
    {
        $channels = $check->notificationChannels()
            ->where('is_active', true)
            ->wherePivot('notify_on_recovery', true)
            ->get();

        $subject = "Health Check Recovered: {$check->display_name}";
        $message = $this->buildRecoveryMessage($check);

        foreach ($channels as $channel) {
            $this->sendNotification($channel, $subject, $message);
        }
    }

    public function sendTestNotification(NotificationChannel $channel): bool
    {
        $subject = "DevFlow Pro - Test Notification";
        $message = "This is a test notification from DevFlow Pro. Your notification channel is working correctly!";

        return $this->sendNotification($channel, $subject, $message);
    }

    private function sendNotification(NotificationChannel $channel, string $subject, string $message): bool
    {
        $type = $channel->type ?? $channel->provider;

        try {
            $success = match ($type) {
                'email' => $this->sendEmail(
                    $channel->config['email'] ?? '',
                    $subject,
                    $message
                ),
                'slack' => $this->sendSlack(
                    $channel->config['webhook_url'] ?? $channel->webhook_url ?? '',
                    $this->formatSlackMessage($subject, $message)
                ),
                'discord' => $this->sendDiscord(
                    $channel->config['webhook_url'] ?? $channel->webhook_url ?? '',
                    $this->formatDiscordMessage($subject, $message)
                ),
                default => false,
            };

            if ($success) {
                Log::info("Notification sent successfully", [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'type' => $type,
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Notification failed", [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function buildFailureMessage(HealthCheck $check, HealthCheckResult $result): string
    {
        $message = "Health Check Alert: FAILURE\n\n";
        $message .= "Check: {$check->display_name}\n";
        $message .= "Type: " . strtoupper($check->check_type) . "\n";
        $message .= "Target: {$check->target_url}\n";
        $message .= "Status: {$check->status}\n";
        $message .= "Consecutive Failures: {$check->consecutive_failures}\n\n";
        $message .= "Result Details:\n";
        $message .= "Status: " . strtoupper($result->status) . "\n";

        if ($result->response_time_ms) {
            $message .= "Response Time: {$result->response_time_ms}ms\n";
        }

        if ($result->status_code) {
            $message .= "Status Code: {$result->status_code}\n";
        }

        if ($result->error_message) {
            $message .= "Error: {$result->error_message}\n";
        }

        $message .= "\nTimestamp: " . $result->checked_at->format('Y-m-d H:i:s') . "\n";

        return $message;
    }

    private function buildRecoveryMessage(HealthCheck $check): string
    {
        $message = "Health Check Alert: RECOVERY\n\n";
        $message .= "Check: {$check->display_name}\n";
        $message .= "Type: " . strtoupper($check->check_type) . "\n";
        $message .= "Target: {$check->target_url}\n";
        $message .= "Status: HEALTHY\n\n";
        $message .= "The health check has recovered and is now functioning normally.\n";
        $message .= "\nTimestamp: " . now()->format('Y-m-d H:i:s') . "\n";

        return $message;
    }

    private function formatSlackMessage(string $subject, string $message): string
    {
        $color = str_contains($subject, 'Failed') ? 'danger' : 'good';
        $emoji = str_contains($subject, 'Failed') ? ':x:' : ':white_check_mark:';

        return "{$emoji} *{$subject}*\n\n```{$message}```";
    }

    private function formatDiscordMessage(string $subject, string $message): string
    {
        $emoji = str_contains($subject, 'Failed') ? ':x:' : ':white_check_mark:';

        return "{$emoji} **{$subject}**\n\n```\n{$message}\n```";
    }

    /**
     * Send rich Discord notification using embeds
     */
    public function sendToDiscord(NotificationChannel $channel, string $message, array $data = []): bool
    {
        try {
            $webhookUrl = $channel->config['webhook_url'] ?? $channel->webhook_url ?? '';

            if (empty($webhookUrl)) {
                throw new \Exception('Webhook URL not configured');
            }

            $embed = $this->buildDiscordEmbed($message, $data);

            $response = Http::post($webhookUrl, [
                'content' => $message,
                'embeds' => [$embed],
            ]);

            $this->logNotification($channel, $data['event'] ?? 'unknown', $response->successful());

            return $response->successful();
        } catch (\Exception $e) {
            $this->logNotification($channel, $data['event'] ?? 'unknown', false, $e->getMessage());
            Log::error("Discord notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send webhook notification
     */
    public function sendWebhook(NotificationChannel $channel, array $payload): bool
    {
        try {
            $webhookUrl = $channel->config['webhook_url'] ?? $channel->webhook_url ?? '';

            if (empty($webhookUrl)) {
                throw new \Exception('Webhook URL not configured');
            }

            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Add signature if webhook secret is configured
            if ($secret = $channel->webhook_secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)->post($webhookUrl, $payload);

            $this->logNotification($channel, $payload['event'] ?? 'unknown', $response->successful());

            return $response->successful();
        } catch (\Exception $e) {
            $this->logNotification($channel, $payload['event'] ?? 'unknown', false, $e->getMessage());
            Log::error("Webhook notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify deployment event to all configured channels
     */
    public function notifyDeploymentEvent(Deployment $deployment, string $event): void
    {
        // Get channels configured for this event
        $query = NotificationChannel::where('enabled', true)
            ->where(function ($q) use ($event) {
                $q->whereJsonContains('events', $event)
                  ->orWhereJsonContains('events', 'deployment.*');
            });

        // Filter by project if channels are project-specific
        if ($deployment->project_id) {
            $query->where(function ($q) use ($deployment) {
                $q->whereNull('project_id')
                  ->orWhere('project_id', $deployment->project_id);
            });
        }

        $channels = $query->get();

        $data = $this->buildDeploymentData($deployment, $event);

        foreach ($channels as $channel) {
            // Queue notification to avoid blocking
            Queue::push(function () use ($channel, $data, $event) {
                $this->sendDeploymentNotification($channel, $data, $event);
            });
        }
    }

    /**
     * Send deployment notification to a channel
     */
    private function sendDeploymentNotification(NotificationChannel $channel, array $data, string $event): void
    {
        $type = $channel->type ?? $channel->provider;

        match ($type) {
            'slack' => $this->sendToSlack($channel, $data['message'], $data),
            'discord' => $this->sendToDiscord($channel, $data['message'], $data),
            'webhook' => $this->sendWebhook($channel, array_merge($data, ['event' => $event])),
            'email' => $this->sendEmail(
                $channel->config['email'] ?? '',
                $data['subject'],
                $data['message']
            ),
            default => null,
        };
    }

    /**
     * Build deployment data for notifications
     */
    private function buildDeploymentData(Deployment $deployment, string $event): array
    {
        $project = $deployment->project;
        $user = $deployment->user;

        $eventName = str_replace('.', ' ', ucfirst($event));
        $statusEmoji = match ($deployment->status) {
            'success' => '✅',
            'failed' => '❌',
            'running' => '🔄',
            'pending' => '⏳',
            default => 'ℹ️',
        };

        $message = "{$statusEmoji} **{$eventName}**\n\n";
        $message .= "**Project:** {$project->name}\n";
        $message .= "**Branch:** {$deployment->branch}\n";
        $message .= "**Status:** " . ucfirst($deployment->status) . "\n";

        if ($user) {
            $message .= "**Deployed by:** {$user->name}\n";
        }

        if ($deployment->commit_hash) {
            $message .= "**Commit:** " . substr($deployment->commit_hash, 0, 7) . "\n";
        }

        if ($deployment->commit_message) {
            $message .= "**Message:** {$deployment->commit_message}\n";
        }

        if ($deployment->duration_seconds) {
            $message .= "**Duration:** {$deployment->duration_seconds}s\n";
        }

        return [
            'event' => $event,
            'subject' => "{$eventName}: {$project->name}",
            'message' => $message,
            'deployment' => [
                'id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'project_name' => $project->name,
                'status' => $deployment->status,
                'branch' => $deployment->branch,
                'commit_hash' => $deployment->commit_hash,
                'commit_message' => $deployment->commit_message,
                'user_name' => $user?->name,
                'started_at' => $deployment->started_at?->toIso8601String(),
                'completed_at' => $deployment->completed_at?->toIso8601String(),
                'duration_seconds' => $deployment->duration_seconds,
            ],
        ];
    }

    /**
     * Build Slack Block Kit blocks
     */
    private function buildSlackBlocks(string $message, array $data): array
    {
        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ],
        ];

        // Add deployment details if available
        if (isset($data['deployment'])) {
            $deployment = $data['deployment'];
            $fields = [];

            if (isset($deployment['branch'])) {
                $fields[] = [
                    'type' => 'mrkdwn',
                    'text' => "*Branch:*\n{$deployment['branch']}",
                ];
            }

            if (isset($deployment['status'])) {
                $fields[] = [
                    'type' => 'mrkdwn',
                    'text' => "*Status:*\n" . ucfirst($deployment['status']),
                ];
            }

            if (!empty($fields)) {
                $blocks[] = [
                    'type' => 'section',
                    'fields' => $fields,
                ];
            }
        }

        return $blocks;
    }

    /**
     * Build Discord embed
     */
    private function buildDiscordEmbed(string $message, array $data): array
    {
        $color = match ($data['deployment']['status'] ?? 'unknown') {
            'success' => 0x00FF00,  // Green
            'failed' => 0xFF0000,   // Red
            'running' => 0xFFFF00,  // Yellow
            'pending' => 0x0000FF,  // Blue
            default => 0x808080,    // Gray
        };

        $embed = [
            'title' => $data['subject'] ?? 'Deployment Notification',
            'description' => $message,
            'color' => $color,
            'timestamp' => now()->toIso8601String(),
        ];

        // Add deployment fields if available
        if (isset($data['deployment'])) {
            $deployment = $data['deployment'];
            $fields = [];

            if (isset($deployment['commit_hash'])) {
                $fields[] = [
                    'name' => 'Commit',
                    'value' => substr($deployment['commit_hash'], 0, 7),
                    'inline' => true,
                ];
            }

            if (isset($deployment['duration_seconds'])) {
                $fields[] = [
                    'name' => 'Duration',
                    'value' => "{$deployment['duration_seconds']}s",
                    'inline' => true,
                ];
            }

            if (!empty($fields)) {
                $embed['fields'] = $fields;
            }
        }

        return $embed;
    }

    /**
     * Log notification attempt
     */
    private function logNotification(
        NotificationChannel $channel,
        string $event,
        bool $success,
        ?string $errorMessage = null
    ): void {
        NotificationLog::create([
            'notification_channel_id' => $channel->id,
            'event_type' => $event,
            'payload' => [],
            'status' => $success ? 'sent' : 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
