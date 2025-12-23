<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Models\NotificationChannel;

interface NotificationServiceInterface
{
    /**
     * Send email notification
     */
    public function sendEmail(string $email, string $subject, string $message): bool;

    /**
     * Send Slack notification
     */
    public function sendSlack(string $webhookUrl, string $message): bool;

    /**
     * Send Discord notification
     */
    public function sendDiscord(string $webhookUrl, string $message): bool;

    /**
     * Notify health check failure
     */
    public function notifyHealthCheckFailure(HealthCheck $check, HealthCheckResult $result): void;

    /**
     * Notify health check recovery
     */
    public function notifyHealthCheckRecovery(HealthCheck $check): void;

    /**
     * Send test notification
     */
    public function sendTestNotification(NotificationChannel $channel): bool;

    /**
     * Notify deployment event to all configured channels
     */
    public function notifyDeploymentEvent(Deployment $deployment, string $event): void;
}
