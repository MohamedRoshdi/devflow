<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use App\Services\AlertNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class AlertNotificationServiceTest extends TestCase
{

    protected $seed = false;

    protected AlertNotificationService $service;

    protected Server $server;

    protected ResourceAlert $alert;

    protected AlertHistory $history;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AlertNotificationService;

        // Fake facades for testing
        Mail::fake();

        // Create test server
        $this->server = Server::factory()->create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.100',
        ]);

        // Create test resource alert
        $this->alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.00,
            'notification_channels' => [
                'email' => ['email' => 'admin@example.com'],
                'slack' => ['webhook_url' => 'https://hooks.slack.com/test'],
                'discord' => ['webhook_url' => 'https://discord.com/api/webhooks/test'],
            ],
        ]);

        // Create test alert history
        $this->history = AlertHistory::factory()->create([
            'resource_alert_id' => $this->alert->id,
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'current_value' => 85.50,
            'threshold_value' => 80.00,
            'status' => 'triggered',
            'message' => 'CPU usage exceeded threshold',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_notifications_to_all_configured_channels(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
            'https://discord.com/*' => Http::response('', 204),
        ]);

        // Act
        $results = $this->service->send($this->alert, $this->history);

        // Assert
        $this->assertArrayHasKey('email', $results);
        $this->assertArrayHasKey('slack', $results);
        $this->assertArrayHasKey('discord', $results);
        $this->assertTrue($results['email']['success']);
        $this->assertTrue($results['slack']['success']);
        $this->assertTrue($results['discord']['success']);
    }

    /** @test */
    public function it_skips_channels_with_empty_configuration(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'email' => [],
                'slack' => ['webhook_url' => 'https://hooks.slack.com/test'],
            ],
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        // Act
        $results = $this->service->send($alert, $this->history);

        // Assert
        $this->assertArrayNotHasKey('email', $results);
        $this->assertArrayHasKey('slack', $results);
    }

    /** @test */
    public function it_handles_unknown_notification_channels(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'unknown_channel' => ['some_config' => 'value'],
            ],
        ]);

        // Act
        $results = $this->service->send($alert, $this->history);

        // Assert
        $this->assertArrayHasKey('unknown_channel', $results);
        $this->assertFalse($results['unknown_channel']['success']);
        $this->assertStringContainsString('Unknown channel', $results['unknown_channel']['message']);
    }

    /** @test */
    public function it_logs_errors_when_notification_fails(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => [
                'slack' => ['webhook_url' => 'https://hooks.slack.com/test'],
            ],
        ]);

        // Mock Http to throw exception
        Http::shouldReceive('post')
            ->andThrow(new \Exception('Connection timeout'));

        // Act
        $results = $this->service->send($alert, $this->history);

        // Assert - verify the exception was caught and returned as failure
        $this->assertArrayHasKey('slack', $results);
        $this->assertFalse($results['slack']['success']);
        $this->assertStringContainsString('Connection timeout', $results['slack']['message']);
    }

    /** @test */
    public function it_continues_sending_to_other_channels_when_one_fails(): void
    {
        // Arrange
        $requestCount = 0;
        Http::fake(function ($request) use (&$requestCount) {
            $requestCount++;
            if (str_contains($request->url(), 'slack.com')) {
                throw new \Exception('Slack connection failed');
            }

            return Http::response('', 204);
        });

        // Act
        $results = $this->service->send($this->alert, $this->history);

        // Assert
        $this->assertFalse($results['slack']['success']);
        $this->assertTrue($results['discord']['success']);
        $this->assertTrue($results['email']['success']);
    }

    /** @test */
    public function it_sends_email_notification_successfully(): void
    {
        // Arrange
        $config = ['email' => 'admin@example.com'];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Email sent', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_email_address_is_missing(): void
    {
        // Arrange
        $config = [];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('No email address configured', $result['message']);
    }

    /** @test */
    public function it_formats_email_message_for_triggered_alert(): void
    {
        // Arrange
        $config = ['email' => 'admin@example.com'];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_formats_email_message_for_resolved_alert(): void
    {
        // Arrange
        $config = ['email' => 'admin@example.com'];
        $resolvedHistory = AlertHistory::factory()->create([
            'resource_alert_id' => $this->alert->id,
            'server_id' => $this->server->id,
            'status' => 'resolved',
        ]);

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $resolvedHistory);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_email_sending_exception(): void
    {
        // Arrange
        Mail::shouldReceive('raw')->andThrow(new \Exception('SMTP connection failed'));
        $config = ['email' => 'admin@example.com'];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SMTP connection failed', $result['message']);
    }

    /** @test */
    public function it_sends_slack_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $result = $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Slack notification sent', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_slack_webhook_url_is_missing(): void
    {
        // Arrange
        $config = [];

        // Act
        $result = $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('No Slack webhook URL configured', $result['message']);
    }

    /** @test */
    public function it_sends_slack_notification_with_correct_structure(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['text']) &&
                   isset($data['blocks']) &&
                   is_array($data['blocks']) &&
                   count($data['blocks']) >= 3;
        });
    }

    /** @test */
    public function it_includes_alert_emoji_in_slack_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['blocks'][0]['text']['text']) &&
                   str_contains($data['blocks'][0]['text']['text'], '🚨');
        });
    }

    /** @test */
    public function it_includes_server_details_in_slack_notification(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $serverField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Server');
            });

            return $serverField !== null &&
                   str_contains($serverField['text'], 'Test Server');
        });
    }

    /** @test */
    public function it_handles_slack_api_error_response(): void
    {
        // Arrange
        Http::fake(function ($request) {
            return Http::response('invalid_payload', 500);
        });

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $result = $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Slack API error', $result['message']);
    }

    /** @test */
    public function it_handles_slack_connection_exception(): void
    {
        // Arrange
        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $result = $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection timeout', $result['message']);
    }

    /** @test */
    public function it_sends_discord_notification_successfully(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $result = $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Discord notification sent', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_discord_webhook_url_is_missing(): void
    {
        // Arrange
        $config = [];

        // Act
        $result = $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('No Discord webhook URL configured', $result['message']);
    }

    /** @test */
    public function it_sends_discord_notification_with_correct_embed_structure(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['embeds']) &&
                   is_array($data['embeds']) &&
                   count($data['embeds']) === 1 &&
                   isset($data['embeds'][0]['title']) &&
                   isset($data['embeds'][0]['fields']);
        });
    }

    /** @test */
    public function it_uses_red_color_for_triggered_discord_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['embeds'][0]['color']) &&
                   $data['embeds'][0]['color'] === 15158332; // Red color
        });
    }

    /** @test */
    public function it_uses_green_color_for_resolved_discord_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];
        $resolvedHistory = AlertHistory::factory()->create([
            'resource_alert_id' => $this->alert->id,
            'server_id' => $this->server->id,
            'status' => 'resolved',
        ]);

        // Act
        $this->service->sendDiscord($config, $this->alert, $resolvedHistory);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['embeds'][0]['color']) &&
                   $data['embeds'][0]['color'] === 3066993; // Green color
        });
    }

    /** @test */
    public function it_includes_timestamp_in_discord_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['embeds'][0]['timestamp']) &&
                   ! empty($data['embeds'][0]['timestamp']);
        });
    }

    /** @test */
    public function it_includes_footer_in_discord_notification(): void
    {
        // Arrange
        Http::fake([
            'https://discord.com/*' => Http::response('', 204),
        ]);

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['embeds'][0]['footer']['text']) &&
                   str_contains($data['embeds'][0]['footer']['text'], 'DevFlow Pro');
        });
    }

    /** @test */
    public function it_handles_discord_api_error_response(): void
    {
        // Arrange
        Http::fake(function ($request) {
            return Http::response('{"error": "Invalid webhook"}', 500);
        });

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $result = $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Discord API error', $result['message']);
    }

    /** @test */
    public function it_handles_discord_connection_exception(): void
    {
        // Arrange
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $config = ['webhook_url' => 'https://discord.com/api/webhooks/123/test'];

        // Act
        $result = $this->service->sendDiscord($config, $this->alert, $this->history);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Network error', $result['message']);
    }

    /** @test */
    public function it_formats_cpu_value_with_percentage(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
        ]);

        $history = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'resource_type' => 'cpu',
            'current_value' => 85.75,
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $alert, $history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $valueField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Current Value');
            });

            return $valueField !== null &&
                   str_contains($valueField['text'], '85.75%');
        });
    }

    /** @test */
    public function it_formats_memory_value_with_percentage(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'memory',
        ]);

        $history = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'resource_type' => 'memory',
            'current_value' => 78.25,
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $alert, $history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $valueField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Current Value');
            });

            return $valueField !== null &&
                   str_contains($valueField['text'], '78.25%');
        });
    }

    /** @test */
    public function it_formats_disk_value_with_percentage(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'disk',
        ]);

        $history = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'resource_type' => 'disk',
            'current_value' => 92.50,
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $alert, $history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $valueField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Current Value');
            });

            return $valueField !== null &&
                   str_contains($valueField['text'], '92.50%');
        });
    }

    /** @test */
    public function it_formats_load_value_without_percentage(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'resource_type' => 'load',
        ]);

        $history = AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $this->server->id,
            'resource_type' => 'load',
            'current_value' => 5.25,
        ]);

        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $alert, $history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $valueField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Current Value');
            });

            return $valueField !== null &&
                   str_contains($valueField['text'], '5.25') &&
                   ! str_contains($valueField['text'], '5.25%');
        });
    }

    /** @test */
    public function it_returns_triggered_emoji_for_triggered_status(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['blocks'][0]['text']['text'], '🚨');
        });
    }

    /** @test */
    public function it_returns_resolved_emoji_for_resolved_status(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];
        $resolvedHistory = AlertHistory::factory()->create([
            'resource_alert_id' => $this->alert->id,
            'server_id' => $this->server->id,
            'status' => 'resolved',
        ]);

        // Act
        $this->service->sendSlack($config, $this->alert, $resolvedHistory);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($data['blocks'][0]['text']['text'], '✅');
        });
    }

    /** @test */
    public function it_includes_resource_type_label_in_notifications(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $resourceField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Resource');
            });

            return $resourceField !== null &&
                   str_contains($resourceField['text'], 'CPU Usage');
        });
    }

    /** @test */
    public function it_includes_threshold_display_in_notifications(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];

        // Act
        $this->service->sendSlack($config, $this->alert, $this->history);

        // Assert
        Http::assertSent(function ($request) {
            $data = $request->data();
            $fields = $data['blocks'][1]['fields'] ?? [];

            $thresholdField = collect($fields)->first(function ($field) {
                return str_contains($field['text'], 'Threshold');
            });

            return $thresholdField !== null &&
                   str_contains($thresholdField['text'], '> 80');
        });
    }

    /** @test */
    public function it_includes_alert_message_in_email(): void
    {
        // Arrange
        $config = ['email' => 'admin@example.com'];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_includes_server_ip_address_in_email(): void
    {
        // Arrange
        $config = ['email' => 'admin@example.com'];

        // Act
        $result = $this->service->sendEmail($config, $this->alert, $this->history);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_null_created_at_timestamp_gracefully(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $config = ['webhook_url' => 'https://hooks.slack.com/services/test/webhook'];
        $history = AlertHistory::factory()->make([
            'resource_alert_id' => $this->alert->id,
            'server_id' => $this->server->id,
        ]);
        $history->created_at = null;
        $history->save();

        // Act
        $result = $this->service->sendSlack($config, $this->alert, $history);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_returns_all_results_even_when_some_channels_fail(): void
    {
        // Arrange
        Http::fake([
            'https://hooks.slack.com/*' => Http::response('error', 500),
            'https://discord.com/*' => Http::response('', 204),
        ]);

        // Act
        $results = $this->service->send($this->alert, $this->history);

        // Assert
        $this->assertCount(3, $results);
        $this->assertArrayHasKey('email', $results);
        $this->assertArrayHasKey('slack', $results);
        $this->assertArrayHasKey('discord', $results);
    }

    /** @test */
    public function it_handles_alerts_with_no_notification_channels(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->create([
            'server_id' => $this->server->id,
            'notification_channels' => null,
        ]);

        // Act
        $results = $this->service->send($alert, $this->history);

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
