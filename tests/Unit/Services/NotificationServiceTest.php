<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NotificationService;

        // Fake facades for testing
        Mail::fake();
        Http::fake();
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_email_successfully(): void
    {
        // Arrange
        $email = 'test@example.com';
        $subject = 'Test Subject';
        $message = 'Test message content';

        // Act
        $result = $this->service->sendEmail($email, $subject, $message);

        // Assert
        $this->assertTrue($result);
        Mail::assertSent(function ($mail) {
            return true;
        });
    }

    /** @test */
    public function it_returns_false_when_email_fails(): void
    {
        // Arrange
        Mail::shouldReceive('raw')->andThrow(new \Exception('SMTP connection failed'));

        // Act
        $result = $this->service->sendEmail('test@example.com', 'Subject', 'Message');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_error_when_email_fails(): void
    {
        // Arrange
        Log::spy();
        Mail::shouldReceive('raw')->andThrow(new \Exception('SMTP error'));

        // Act
        $this->service->sendEmail('test@example.com', 'Subject', 'Message');

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Email notification failed', Mockery::on(function ($context) {
                return $context['email'] === 'test@example.com' &&
                       str_contains($context['error'], 'SMTP error');
            }));
    }

    /** @test */
    public function it_sends_slack_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        // Act
        $result = $this->service->sendSlack(
            'https://hooks.slack.com/services/test',
            'Test message'
        );

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://hooks.slack.com/services/test' &&
                   $body['text'] === 'Test message' &&
                   $body['mrkdwn'] === true;
        });
    }

    /** @test */
    public function it_returns_false_when_slack_request_fails(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 500),
        ]);

        // Act
        $result = $this->service->sendSlack(
            'https://hooks.slack.com/services/test',
            'Test message'
        );

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_error_when_slack_notification_fails(): void
    {
        // Arrange
        Log::spy();
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 500),
        ]);

        // Act
        $this->service->sendSlack('https://hooks.slack.com/services/test', 'Message');

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Slack notification failed', Mockery::type('array'));
    }

    /** @test */
    public function it_sends_rich_slack_notification_with_blocks(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => [
                'webhook_url' => 'https://hooks.slack.com/services/test',
            ],
        ]);

        $data = [
            'event' => 'deployment.started',
            'deployment' => [
                'branch' => 'main',
                'status' => 'running',
            ],
        ];

        // Act
        $result = $this->service->sendToSlack($channel, 'Deployment started', $data);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return isset($body['blocks']) &&
                   is_array($body['blocks']) &&
                   count($body['blocks']) > 0;
        });
    }

    /** @test */
    public function it_fails_slack_notification_when_webhook_url_missing(): void
    {
        // Arrange
        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => [],
        ]);

        // Act
        $result = $this->service->sendToSlack($channel, 'Test message');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_slack_notification_attempt(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => [
                'webhook_url' => 'https://hooks.slack.com/services/test',
            ],
        ]);

        // Act
        $this->service->sendToSlack($channel, 'Test message', ['event' => 'test']);

        // Assert
        $this->assertDatabaseHas('notification_logs', [
            'notification_channel_id' => $channel->id,
            'event_type' => 'test',
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_logs_failed_slack_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 500),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => [
                'webhook_url' => 'https://hooks.slack.com/services/test',
            ],
        ]);

        // Act
        $this->service->sendToSlack($channel, 'Test message', ['event' => 'test']);

        // Assert
        $this->assertDatabaseHas('notification_logs', [
            'notification_channel_id' => $channel->id,
            'event_type' => 'test',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_sends_discord_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        // Act
        $result = $this->service->sendDiscord(
            'https://discord.com/api/webhooks/test',
            'Test message'
        );

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'discord.com') &&
                   $body['content'] === 'Test message';
        });
    }

    /** @test */
    public function it_returns_false_when_discord_request_fails(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 500),
        ]);

        // Act
        $result = $this->service->sendDiscord(
            'https://discord.com/api/webhooks/test',
            'Test message'
        );

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_error_when_discord_notification_fails(): void
    {
        // Arrange
        Log::spy();
        Http::fake([
            'https://discord.com/*' => Http::response(null, 500),
        ]);

        // Act
        $this->service->sendDiscord('https://discord.com/api/webhooks/test', 'Message');

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Discord notification failed', Mockery::type('array'));
    }

    /** @test */
    public function it_sends_rich_discord_notification_with_embeds(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'discord',
            'config' => [
                'webhook_url' => 'https://discord.com/api/webhooks/test',
            ],
        ]);

        $data = [
            'event' => 'deployment.success',
            'subject' => 'Deployment Successful',
            'deployment' => [
                'status' => 'success',
                'commit_hash' => 'abc123def456',
                'duration_seconds' => 120,
            ],
        ];

        // Act
        $result = $this->service->sendToDiscord($channel, 'Deployment successful', $data);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return isset($body['embeds']) &&
                   is_array($body['embeds']) &&
                   count($body['embeds']) > 0 &&
                   isset($body['embeds'][0]['color']) &&
                   isset($body['embeds'][0]['timestamp']);
        });
    }

    /** @test */
    public function it_uses_correct_color_for_discord_embed_status(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'discord',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/test'],
        ]);

        // Act & Assert - Success (Green)
        $this->service->sendToDiscord($channel, 'Success', [
            'deployment' => ['status' => 'success'],
        ]);

        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];

            return ($embed['color'] ?? null) === 0x00FF00; // Green
        });

        // Act & Assert - Failed (Red)
        $this->service->sendToDiscord($channel, 'Failed', [
            'deployment' => ['status' => 'failed'],
        ]);

        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];

            return ($embed['color'] ?? null) === 0xFF0000; // Red
        });
    }

    /** @test */
    public function it_fails_discord_notification_when_webhook_url_missing(): void
    {
        // Arrange
        $channel = NotificationChannel::factory()->create([
            'type' => 'discord',
            'config' => [],
        ]);

        // Act
        $result = $this->service->sendToDiscord($channel, 'Test message');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_sends_webhook_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://example.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'webhook_url' => 'https://example.com/webhook',
        ]);

        $payload = [
            'event' => 'deployment.started',
            'data' => ['project_id' => 1],
        ];

        // Act
        $result = $this->service->sendWebhook($channel, $payload);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($payload) {
            return str_contains($request->url(), 'example.com/webhook') &&
                   $request->data() === $payload;
        });
    }

    /** @test */
    public function it_includes_signature_in_webhook_when_secret_configured(): void
    {
        // Arrange
        Http::fake([
            'https://example.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_secret' => 'test-secret',
        ]);

        $payload = ['event' => 'test'];

        // Act
        $this->service->sendWebhook($channel, $payload);

        // Assert
        Http::assertSent(function ($request) use ($payload) {
            $expectedSignature = hash_hmac('sha256', json_encode($payload), 'test-secret');

            return $request->hasHeader('X-Webhook-Signature', $expectedSignature);
        });
    }

    /** @test */
    public function it_sends_webhook_without_signature_when_no_secret(): void
    {
        // Arrange
        Http::fake([
            'https://example.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_secret' => null,
        ]);

        $payload = ['event' => 'test'];

        // Act
        $this->service->sendWebhook($channel, $payload);

        // Assert
        Http::assertSent(function ($request) {
            return ! $request->hasHeader('X-Webhook-Signature');
        });
    }

    /** @test */
    public function it_fails_webhook_notification_when_url_missing(): void
    {
        // Arrange
        $channel = NotificationChannel::factory()->create([
            'type' => 'webhook',
            'webhook_url' => null,
        ]);

        // Act
        $result = $this->service->sendWebhook($channel, ['event' => 'test']);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_notifies_health_check_failure(): void
    {
        // Arrange
        Http::fake();

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'status' => 'down',
            'consecutive_failures' => 3,
        ]);

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'response_time_ms' => 5000,
            'status_code' => 500,
            'error_message' => 'Internal Server Error',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
            'notify_on_recovery' => false,
        ]);

        // Act
        $this->service->notifyHealthCheckFailure($check, $result);

        // Assert
        Http::assertSent(function ($request) use ($check) {
            return str_contains($request->data()['text'], 'Health Check Failed') &&
                   str_contains($request->data()['text'], $check->display_name);
        });
    }

    /** @test */
    public function it_skips_inactive_channels_for_health_check_failure(): void
    {
        // Arrange
        Http::fake();

        $check = HealthCheck::factory()->create();
        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $check->id,
            'status' => 'failure',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'is_active' => false,
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
        ]);

        // Act
        $this->service->notifyHealthCheckFailure($check, $result);

        // Assert
        Http::assertNothingSent();
    }

    /** @test */
    public function it_notifies_health_check_recovery(): void
    {
        // Arrange
        Http::fake();

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'status' => 'healthy',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => false,
            'notify_on_recovery' => true,
        ]);

        // Act
        $this->service->notifyHealthCheckRecovery($check);

        // Assert
        Http::assertSent(function ($request) use ($check) {
            return str_contains($request->data()['text'], 'Health Check Recovered') &&
                   str_contains($request->data()['text'], $check->display_name);
        });
    }

    /** @test */
    public function it_sends_test_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $result = $this->service->sendTestNotification($channel);

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'Test Notification');
        });
    }

    /** @test */
    public function it_sends_test_notification_via_email(): void
    {
        // Arrange
        $channel = NotificationChannel::factory()->create([
            'type' => 'email',
            'config' => ['email' => 'test@example.com'],
        ]);

        // Act
        $result = $this->service->sendTestNotification($channel);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_notifies_deployment_event_to_configured_channels(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create();
        $user = User::factory()->create(['name' => 'John Doe']);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'success',
            'branch' => 'main',
            'commit_hash' => 'abc123',
            'commit_message' => 'Initial commit',
            'duration_seconds' => 120,
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Assert
        Queue::assertPushed(function ($job) {
            return true;
        });
    }

    /** @test */
    public function it_filters_channels_by_project_for_deployment_events(): void
    {
        // Arrange
        Http::fake();

        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $deployment = Deployment::factory()->create([
            'project_id' => $project1->id,
        ]);

        // Global channel (no project)
        $globalChannel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'project_id' => null,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/global'],
        ]);

        // Project-specific channel
        $projectChannel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'project_id' => $project1->id,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/project1'],
        ]);

        // Different project channel (should be excluded)
        NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'project_id' => $project2->id,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/project2'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Assert - Should queue 2 notifications (global + project1)
        Queue::assertPushed(\Closure::class, 2);
    }

    /** @test */
    public function it_supports_wildcard_event_matching(): void
    {
        // Arrange
        Http::fake();

        $deployment = Deployment::factory()->create();

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'events' => ['deployment.*'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.started');

        // Assert
        Queue::assertPushed(\Closure::class);
    }

    /** @test */
    public function it_skips_disabled_channels_for_deployment_events(): void
    {
        // Arrange
        $deployment = Deployment::factory()->create();

        NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => false,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Assert
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_builds_comprehensive_deployment_message(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create(['name' => 'My Project']);
        $user = User::factory()->create(['name' => 'Jane Smith']);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'success',
            'branch' => 'production',
            'commit_hash' => 'abc123def456',
            'commit_message' => 'Fix critical bug',
            'duration_seconds' => 240,
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Process queued job
        Queue::assertPushed(\Closure::class, function ($job) {
            $job();

            return true;
        });

        // Assert
        Http::assertSent(function ($request) {
            $message = $request->data()['text'] ?? '';

            return str_contains($message, 'My Project') &&
                   str_contains($message, 'production') &&
                   str_contains($message, 'success') &&
                   str_contains($message, 'Jane Smith') &&
                   str_contains($message, 'abc123') &&
                   str_contains($message, 'Fix critical bug') &&
                   str_contains($message, '240s');
        });
    }

    /** @test */
    public function it_formats_slack_message_with_emoji_for_failure(): void
    {
        // Arrange
        Http::fake();

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->sendTestNotification($channel);

        // Assert
        Http::assertSent(function ($request) {
            $message = $request->data()['text'] ?? '';

            return str_contains($message, ':white_check_mark:') ||
                   str_contains($message, '*');
        });
    }

    /** @test */
    public function it_handles_deployment_without_user(): void
    {
        // Arrange
        Http::fake();

        $project = Project::factory()->create(['name' => 'Test Project']);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'status' => 'success',
            'branch' => 'main',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Process queued job
        Queue::assertPushed(\Closure::class, function ($job) {
            $job();

            return true;
        });

        // Assert - Should not fail
        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'Test Project');
        });
    }

    /** @test */
    public function it_handles_deployment_without_project(): void
    {
        // Arrange
        Http::fake();

        $deployment = Deployment::factory()->create([
            'project_id' => null,
            'status' => 'success',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
            'events' => ['deployment.success'],
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->notifyDeploymentEvent($deployment, 'deployment.success');

        // Process queued job
        Queue::assertPushed(\Closure::class, function ($job) {
            $job();

            return true;
        });

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->data()['text'], 'Unknown Project');
        });
    }

    /** @test */
    public function it_includes_slack_blocks_with_deployment_fields(): void
    {
        // Arrange
        Http::fake();

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $data = [
            'deployment' => [
                'branch' => 'main',
                'status' => 'success',
            ],
        ];

        // Act
        $this->service->sendToSlack($channel, 'Test message', $data);

        // Assert
        Http::assertSent(function ($request) {
            $blocks = $request->data()['blocks'] ?? [];
            if (empty($blocks)) {
                return false;
            }

            $hasFields = false;
            foreach ($blocks as $block) {
                if (isset($block['fields']) && is_array($block['fields'])) {
                    $hasFields = true;
                    break;
                }
            }

            return $hasFields;
        });
    }

    /** @test */
    public function it_includes_discord_embed_fields(): void
    {
        // Arrange
        Http::fake();

        $channel = NotificationChannel::factory()->create([
            'type' => 'discord',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/test'],
        ]);

        $data = [
            'subject' => 'Test',
            'deployment' => [
                'status' => 'success',
                'commit_hash' => 'abc123def456',
                'duration_seconds' => 60,
            ],
        ];

        // Act
        $this->service->sendToDiscord($channel, 'Test', $data);

        // Assert
        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];

            return isset($embed['fields']) &&
                   is_array($embed['fields']) &&
                   count($embed['fields']) > 0;
        });
    }

    /** @test */
    public function it_logs_notification_success(): void
    {
        // Arrange
        Log::spy();
        Http::fake([
            'https://hooks.slack.com/*' => Http::response(null, 200),
        ]);

        $channel = NotificationChannel::factory()->create([
            'name' => 'Test Channel',
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        // Act
        $this->service->sendTestNotification($channel);

        // Assert
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Notification sent successfully', Mockery::on(function ($context) use ($channel) {
                return $context['channel_id'] === $channel->id &&
                       $context['channel_name'] === 'Test Channel' &&
                       $context['type'] === 'slack';
            }));
    }

    /** @test */
    public function it_handles_unknown_channel_type_gracefully(): void
    {
        // Arrange
        $channel = NotificationChannel::factory()->create([
            'type' => 'unknown_type',
            'config' => ['test' => 'value'],
        ]);

        // Act
        $result = $this->service->sendTestNotification($channel);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_truncates_webhook_url_in_error_logs(): void
    {
        // Arrange
        Log::spy();
        Http::fake([
            'https://very-long-webhook-url.com/*' => Http::response(null, 500),
        ]);

        // Act
        $this->service->sendSlack(
            'https://very-long-webhook-url.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
            'Test'
        );

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Slack notification failed', Mockery::on(function ($context) {
                $url = $context['webhook_url'] ?? '';

                return strlen($url) <= 33; // 30 chars + '...'
            }));
    }

    /** @test */
    public function it_uses_webhook_url_from_config_or_attribute(): void
    {
        // Arrange
        Http::fake();

        // Test with webhook_url in config
        $channel1 = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/config'],
            'webhook_url' => null,
        ]);

        $this->service->sendToSlack($channel1, 'Test');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/config';
        });

        // Test with webhook_url as attribute
        $channel2 = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => [],
            'webhook_url' => 'https://hooks.slack.com/attribute',
        ]);

        $this->service->sendToSlack($channel2, 'Test');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/attribute';
        });
    }

    /** @test */
    public function it_builds_failure_message_with_all_details(): void
    {
        // Arrange
        Http::fake();

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'status' => 'down',
            'consecutive_failures' => 5,
        ]);

        $result = HealthCheckResult::factory()->create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'response_time_ms' => 3000,
            'status_code' => 503,
            'error_message' => 'Service Unavailable',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
        ]);

        // Act
        $this->service->notifyHealthCheckFailure($check, $result);

        // Assert
        Http::assertSent(function ($request) {
            $message = $request->data()['text'] ?? '';

            return str_contains($message, 'FAILURE') &&
                   str_contains($message, 'HTTP') &&
                   str_contains($message, 'https://example.com') &&
                   str_contains($message, '5') && // consecutive failures
                   str_contains($message, '3000ms') &&
                   str_contains($message, '503') &&
                   str_contains($message, 'Service Unavailable');
        });
    }

    /** @test */
    public function it_builds_recovery_message_correctly(): void
    {
        // Arrange
        Http::fake();

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'status' => 'healthy',
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/test'],
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_recovery' => true,
        ]);

        // Act
        $this->service->notifyHealthCheckRecovery($check);

        // Assert
        Http::assertSent(function ($request) {
            $message = $request->data()['text'] ?? '';

            return str_contains($message, 'RECOVERY') &&
                   str_contains($message, 'HEALTHY') &&
                   str_contains($message, 'recovered') &&
                   str_contains($message, 'functioning normally');
        });
    }
}
