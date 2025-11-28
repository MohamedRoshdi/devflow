<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
}
