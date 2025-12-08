<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Services\Notifications\SlackDiscordNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SlackDiscordNotificationServiceTest extends TestCase
{
    protected SlackDiscordNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SlackDiscordNotificationService;
        Http::fake();
    }

    /** @test */
    public function it_sends_slack_deployment_started_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Test Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
            'commit_hash' => 'abc123def456',
            'triggered_by' => 'manual',
            'status' => 'running',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project, $deployment) {
            $data = $request->data();

            return str_contains($request->url(), 'hooks.slack.com') &&
                   $data['username'] === 'DevFlow Pro' &&
                   $data['icon_emoji'] === ':rocket:' &&
                   $data['attachments'][0]['title'] === "Deployment Started: {$project->name}" &&
                   $data['attachments'][0]['fields'][0]['value'] === $project->name &&
                   $data['attachments'][0]['fields'][1]['value'] === $deployment->branch;
        });
    }

    /** @test */
    public function it_sends_slack_deployment_completed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $domain = Domain::factory()->create(['full_domain' => 'example.com']);
        $project = Project::factory()->create(['name' => 'Test Project']);
        $project->domains()->save($domain);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'commit_hash' => 'abc123def456',
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_completed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project) {
            $data = $request->data();

            return $data['icon_emoji'] === ':white_check_mark:' &&
                   $data['attachments'][0]['color'] === '#36a64f' &&
                   str_contains($data['attachments'][0]['title'], 'Deployment Successful') &&
                   str_contains($data['attachments'][0]['title'], $project->name) &&
                   isset($data['attachments'][0]['actions']) &&
                   count($data['attachments'][0]['actions']) === 2;
        });
    }

    /** @test */
    public function it_sends_slack_deployment_failed_notification_with_error(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Failed Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
            'error_message' => 'Build failed: npm install error',
            'status' => 'failed',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_failed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($deployment) {
            $data = $request->data();

            return $data['icon_emoji'] === ':x:' &&
                   $data['attachments'][0]['color'] === '#f44336' &&
                   str_contains($data['attachments'][0]['text'], '<!channel>') &&
                   str_contains($data['attachments'][0]['fields'][2]['value'], $deployment->error_message);
        });
    }

    /** @test */
    public function it_sends_slack_rollback_completed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'commit_hash' => 'previouscommit123',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('rollback_completed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project) {
            $data = $request->data();

            return $data['icon_emoji'] === ':arrow_backward:' &&
                   $data['attachments'][0]['color'] === '#ff9800' &&
                   str_contains($data['attachments'][0]['title'], 'Rollback Completed') &&
                   str_contains($data['attachments'][0]['title'], $project->name);
        });
    }

    /** @test */
    public function it_sends_slack_health_check_failed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Unhealthy Project']);
        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $healthData = [
            'status' => 'unhealthy',
            'response_time' => 5000,
            'failed_checks' => ['database', 'redis'],
        ];

        // Act
        $this->service->sendNotification('health_check_failed', [
            'project' => $project,
            'health_data' => $healthData,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project, $healthData) {
            $data = $request->data();

            return $data['icon_emoji'] === ':hospital:' &&
                   $data['attachments'][0]['color'] === '#ff9800' &&
                   str_contains($data['attachments'][0]['title'], 'Health Check Failed') &&
                   str_contains($data['attachments'][0]['title'], $project->name) &&
                   $data['attachments'][0]['fields'][0]['value'] === $healthData['status'] &&
                   str_contains($data['attachments'][0]['fields'][2]['value'], 'database');
        });
    }

    /** @test */
    public function it_sends_slack_ssl_expiring_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $domain = Domain::factory()->create([
            'full_domain' => 'example.com',
            'ssl_expires_at' => now()->addDays(5),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('ssl_expiring', [
            'domain' => $domain,
            'days_until_expiry' => 5,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($domain) {
            $data = $request->data();

            return $data['icon_emoji'] === ':lock:' &&
                   $data['attachments'][0]['color'] === '#f44336' && // Red for <= 7 days
                   str_contains($data['attachments'][0]['title'], 'SSL Certificate Expiring Soon') &&
                   str_contains($data['attachments'][0]['text'], $domain->full_domain) &&
                   str_contains($data['attachments'][0]['text'], '5 days');
        });
    }

    /** @test */
    public function it_sends_slack_storage_warning_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Storage Project']);
        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $storageData = [
            'used' => 9 * 1024 * 1024 * 1024, // 9GB
            'total' => 10 * 1024 * 1024 * 1024, // 10GB
        ];

        // Act
        $this->service->sendNotification('storage_warning', [
            'project' => $project,
            'storage_data' => $storageData,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project) {
            $data = $request->data();

            return $data['icon_emoji'] === ':floppy_disk:' &&
                   $data['attachments'][0]['color'] === '#f44336' && // Red for > 90%
                   str_contains($data['attachments'][0]['title'], 'Storage Warning') &&
                   str_contains($data['attachments'][0]['title'], $project->name) &&
                   str_contains($data['attachments'][0]['text'], '90%');
        });
    }

    /** @test */
    public function it_sends_slack_security_alert_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('security_alert', [
            'message' => 'Suspicious activity detected',
            'severity' => 'high',
            'timestamp' => now(),
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['icon_emoji'] === ':warning:' &&
                   $data['attachments'][0]['color'] === '#f44336' &&
                   $data['attachments'][0]['title'] === 'Security Alert' &&
                   str_contains($data['attachments'][0]['text'], 'Suspicious activity detected') &&
                   $data['attachments'][0]['fields'][0]['value'] === 'HIGH';
        });
    }

    /** @test */
    public function it_sends_discord_deployment_started_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Discord Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'production',
            'commit_hash' => 'xyz789abc123',
            'triggered_by' => 'webhook',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project, $deployment) {
            $data = $request->data();

            return str_contains($request->url(), 'discord.com') &&
                   $data['username'] === 'DevFlow Pro' &&
                   isset($data['embeds'][0]) &&
                   str_contains($data['embeds'][0]['title'], 'Deployment Started') &&
                   str_contains($data['embeds'][0]['title'], $project->name) &&
                   $data['embeds'][0]['color'] === 3447003 && // Blue
                   $data['embeds'][0]['fields'][0]['value'] === $project->name &&
                   $data['embeds'][0]['fields'][1]['value'] === $deployment->branch;
        });
    }

    /** @test */
    public function it_sends_discord_deployment_completed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $domain = Domain::factory()->create(['full_domain' => 'discord-app.com']);
        $project = Project::factory()->create(['name' => 'Discord App']);
        $project->domains()->save($domain);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'commit_hash' => 'success123',
            'started_at' => now()->subMinutes(3),
            'completed_at' => now(),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_completed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project) {
            $data = $request->data();
            $embed = $data['embeds'][0];

            return str_contains($embed['title'], 'Deployment Successful') &&
                   str_contains($embed['title'], $project->name) &&
                   $embed['color'] === 3066993 && // Green
                   isset($embed['description']) &&
                   isset($embed['timestamp']) &&
                   count($embed['fields']) === 5;
        });
    }

    /** @test */
    public function it_sends_discord_deployment_failed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Failed Discord']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'staging',
            'error_message' => 'Docker build failed: invalid Dockerfile syntax',
            'status' => 'failed',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_failed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($deployment) {
            $data = $request->data();
            $embed = $data['embeds'][0];

            return str_contains($embed['title'], 'Deployment Failed') &&
                   $embed['color'] === 15158332 && // Red
                   str_contains($embed['description'], '@everyone') &&
                   str_contains($embed['fields'][2]['value'], $deployment->error_message);
        });
    }

    /** @test */
    public function it_sends_discord_health_check_failed_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Health Project']);
        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        $healthData = [
            'status' => 'degraded',
            'response_time' => 3000,
            'failed_checks' => ['cache', 'queue'],
        ];

        // Act
        $this->service->sendNotification('health_check_failed', [
            'project' => $project,
            'health_data' => $healthData,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($healthData) {
            $embed = $request->data()['embeds'][0];

            return str_contains($embed['title'], 'Health Check Failed') &&
                   $embed['color'] === 16753920 && // Orange
                   $embed['fields'][1]['value'] === $healthData['status'] &&
                   str_contains($embed['fields'][2]['value'], '3000ms');
        });
    }

    /** @test */
    public function it_sends_discord_ssl_expiring_notification_with_urgency_levels(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $domain = Domain::factory()->create([
            'full_domain' => 'urgent-ssl.com',
            'ssl_expires_at' => now()->addDays(3),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('ssl_expiring', [
            'domain' => $domain,
            'days_until_expiry' => 3,
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0];

            return str_contains($embed['title'], 'SSL Certificate Expiring Soon') &&
                   $embed['color'] === 15158332 && // Red for <= 7 days
                   str_contains($embed['fields'][1]['value'], 'Critical');
        });
    }

    /** @test */
    public function it_sends_discord_storage_warning_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Storage Project']);
        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        $storageData = [
            'used' => 8.5 * 1024 * 1024 * 1024, // 8.5GB
            'total' => 10 * 1024 * 1024 * 1024, // 10GB
        ];

        // Act
        $this->service->sendNotification('storage_warning', [
            'project' => $project,
            'storage_data' => $storageData,
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0];

            return str_contains($embed['title'], 'Storage Warning') &&
                   isset($embed['description']) &&
                   str_contains($embed['description'], '85%') &&
                   count($embed['fields']) === 3;
        });
    }

    /** @test */
    public function it_sends_discord_security_alert_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('security_alert', [
            'message' => 'Brute force attack detected',
            'severity' => 'critical',
            'timestamp' => now(),
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0];

            return str_contains($embed['title'], 'Security Alert') &&
                   $embed['color'] === 15158332 && // Red
                   str_contains($embed['description'], 'Brute force attack detected') &&
                   $embed['fields'][0]['value'] === 'CRITICAL';
        });
    }

    /** @test */
    public function it_sends_teams_deployment_notification(): void
    {
        // Arrange
        Http::fake([
            'https://outlook.office.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create(['name' => 'Teams Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
            'commit_hash' => 'teams123',
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'teams',
            'enabled' => true,
            'webhook_url' => 'https://outlook.office.com/webhook/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) use ($project) {
            $data = $request->data();

            return str_contains($request->url(), 'outlook.office.com') &&
                   $data['@type'] === 'MessageCard' &&
                   str_contains($data['summary'], $project->name) &&
                   isset($data['sections'][0]['facts']) &&
                   isset($data['potentialAction']);
        });
    }

    /** @test */
    public function it_sends_webhook_notification_without_signature(): void
    {
        // Arrange
        Http::fake([
            'https://custom-webhook.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'webhook',
            'enabled' => true,
            'webhook_url' => 'https://custom-webhook.com/notify',
            'webhook_secret' => null,
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://custom-webhook.com/notify' &&
                   $data['event'] === 'deployment_started' &&
                   $data['source'] === 'DevFlow Pro' &&
                   isset($data['timestamp']) &&
                   ! $request->hasHeader('X-DevFlow-Signature');
        });
    }

    /** @test */
    public function it_sends_webhook_notification_with_signature(): void
    {
        // Arrange
        Http::fake([
            'https://custom-webhook.com/*' => Http::response(null, 200),
        ]);

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'webhook',
            'enabled' => true,
            'webhook_url' => 'https://custom-webhook.com/notify',
            'webhook_secret' => 'super-secret-key',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-DevFlow-Signature');
        });
    }

    /** @test */
    public function it_handles_exceptions_when_sending_notifications(): void
    {
        // Arrange
        Log::spy();

        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 500),
        ]);

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with(\Mockery::on(function ($message) use ($channel) {
                return str_contains($message, "Failed to send notification to {$channel->name}");
            }));
    }

    /** @test */
    public function it_skips_disabled_channels(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => false,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_started', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertNothingSent();
    }

    /** @test */
    public function it_notifies_deployment_started(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'running',
        ]);

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->notifyDeployment($deployment, 'started');

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com');
        });
    }

    /** @test */
    public function it_notifies_deployment_completed(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'success',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->notifyDeployment($deployment, 'completed');

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['attachments'][0]) &&
                   str_contains($data['attachments'][0]['title'], 'Successful');
        });
    }

    /** @test */
    public function it_notifies_deployment_failed(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'failed',
            'error_message' => 'Deployment error occurred',
        ]);

        NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->notifyDeployment($deployment, 'failed');

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];

            return str_contains($embed['title'] ?? '', 'Failed');
        });
    }

    /** @test */
    public function it_notifies_health_check_failure(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $healthData = [
            'status' => 'unhealthy',
            'response_time' => 6000,
            'failed_checks' => ['database'],
        ];

        // Act
        $this->service->notifyHealthCheck($project, $healthData);

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com');
        });
    }

    /** @test */
    public function it_does_not_notify_healthy_status(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $healthData = ['status' => 'healthy'];

        // Act
        $this->service->notifyHealthCheck($project, $healthData);

        // Assert
        Http::assertNothingSent();
    }

    /** @test */
    public function it_notifies_ssl_expiry(): void
    {
        // Arrange
        Http::fake();

        $domain = Domain::factory()->create([
            'ssl_expires_at' => now()->addDays(10),
        ]);

        NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->notifySSLExpiry($domain, 10);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];

            return str_contains($embed['title'] ?? '', 'SSL');
        });
    }

    /** @test */
    public function it_notifies_storage_warning(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        $storageData = [
            'used' => 9 * 1024 * 1024 * 1024,
            'total' => 10 * 1024 * 1024 * 1024,
        ];

        // Act
        $this->service->notifyStorageWarning($project, $storageData);

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com');
        });
    }

    /** @test */
    public function it_notifies_security_alert(): void
    {
        // Arrange
        Http::fake();

        NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->notifySecurityAlert('Unauthorized access attempt', 'high');

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['attachments'][0]['text'] ?? '', 'Unauthorized access');
        });
    }

    /** @test */
    public function it_tests_slack_channel_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $result = $this->service->testChannel($channel);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'Test notification');
        });
    }

    /** @test */
    public function it_tests_discord_channel_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $result = $this->service->testChannel($channel);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->data()['content'], 'Test notification');
        });
    }

    /** @test */
    public function it_tests_teams_channel_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://outlook.office.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'teams',
            'webhook_url' => 'https://outlook.office.com/webhook/test',
        ]);

        // Act
        $result = $this->service->testChannel($channel);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_test_channel_fails(): void
    {
        // Arrange
        Log::spy();

        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 500),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $result = $this->service->testChannel($channel);

        // Assert
        $this->assertFalse($result);
        Log::shouldHaveReceived('error')->once();
    }

    /** @test */
    public function it_formats_bytes_correctly(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Test different byte sizes
        $storageData = [
            'used' => 5368709120, // 5GB
            'total' => 10737418240, // 10GB
        ];

        // Act
        $this->service->notifyStorageWarning($project, $storageData);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['attachments'][0]['fields'] ?? [];

            return str_contains($fields[0]['value'], 'GB') &&
                   str_contains($fields[1]['value'], 'GB');
        });
    }

    /** @test */
    public function it_handles_generic_slack_notification_for_unknown_types(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Act
        $this->service->sendNotification('custom_event_type', [
            'custom_data' => 'test value',
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['text'] === 'Event: custom_event_type' &&
                   isset($data['attachments'][0]['text']);
        });
    }

    /** @test */
    public function it_handles_generic_discord_notification_for_unknown_types(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('custom_event_type', [
            'custom_data' => 'test value',
        ]);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0];

            return $embed['title'] === 'Custom Event Type' &&
                   isset($embed['description']) &&
                   str_contains($embed['description'], 'custom_data');
        });
    }

    /** @test */
    public function it_uses_correct_slack_colors_for_different_statuses(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'provider' => 'slack',
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/test',
        ]);

        // Test success color
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        $this->service->sendNotification('deployment_completed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        Http::assertSent(function ($request) {
            return $request->data()['attachments'][0]['color'] === '#36a64f'; // Green
        });
    }

    /** @test */
    public function it_uses_correct_discord_colors_for_different_statuses(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'error_message' => 'Error',
        ]);

        NotificationChannel::factory()->create([
            'provider' => 'discord',
            'enabled' => true,
            'webhook_url' => 'https://discord.com/api/webhooks/test',
        ]);

        // Act
        $this->service->sendNotification('deployment_failed', [
            'deployment' => $deployment,
            'project' => $project,
        ]);

        // Assert
        Http::assertSent(function ($request) {
            return $request->data()['embeds'][0]['color'] === 15158332; // Red
        });
    }
}
