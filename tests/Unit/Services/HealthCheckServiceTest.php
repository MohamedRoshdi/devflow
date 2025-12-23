<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\HealthCheck;
use App\Models\HealthCheckResult;
use App\Models\NotificationChannel;
use App\Models\Server;
use App\Services\HealthCheckService;
use App\Services\NotificationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{

    protected HealthCheckService $service;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->service = new HealthCheckService($this->notificationService);
    }

    #[Test]
    public function it_performs_http_check_with_success(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performHttpCheck($check);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertIsInt($result['response_time']);
    }

    #[Test]
    public function it_performs_http_check_with_wrong_status_code(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performHttpCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertEquals('Expected status 200, got 500', $result['error']);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_handles_http_connection_exception(): void
    {
        // Arrange
        Http::fake(function () {
            throw new ConnectionException('Connection failed');
        });

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performHttpCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertStringContainsString('Connection failed', $result['error']);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_handles_http_timeout(): void
    {
        // Arrange
        Http::fake(function () {
            usleep(6000000); // Sleep for 6 seconds
            throw new ConnectionException('Timeout');
        });

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://slow.example.com',
            'expected_status' => 200,
            'timeout_seconds' => 1,
        ]);

        // Act
        $result = $this->service->performHttpCheck($check);

        // Assert
        $this->assertContains($result['status'], ['timeout', 'failure']);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_handles_generic_http_exception(): void
    {
        // Arrange
        Http::fake(function () {
            throw new \Exception('Unexpected error');
        });

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performHttpCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals('Unexpected error', $result['error']);
    }

    #[Test]
    public function it_performs_tcp_check_successfully(): void
    {
        // Arrange - Can't easily test real TCP without a server, so we'll test the structure
        $check = HealthCheck::factory()->create([
            'check_type' => 'tcp',
            'target_url' => 'localhost:80',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performTcpCheck($check);

        // Assert
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertContains($result['status'], ['success', 'failure']);
    }

    #[Test]
    public function it_parses_tcp_url_with_port(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'check_type' => 'tcp',
            'target_url' => 'http://example.com:8080',
            'timeout_seconds' => 1,
        ]);

        // Act
        $result = $this->service->performTcpCheck($check);

        // Assert
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_handles_tcp_check_exceptions(): void
    {
        // Arrange - Use invalid port to trigger failure
        $check = HealthCheck::factory()->create([
            'check_type' => 'tcp',
            'target_url' => 'invalid-host-that-does-not-exist.local:9999',
            'timeout_seconds' => 1,
        ]);

        // Act
        $result = $this->service->performTcpCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_performs_ping_check_successfully(): void
    {
        // Arrange
        Process::fake([
            'ping*' => Process::result(
                output: "PING example.com (93.184.216.34) 56(84) bytes of data.\n64 bytes from example.com: icmp_seq=1 ttl=56 time=15.3 ms",
                exitCode: 0
            ),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'ping',
            'target_url' => 'example.com',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performPingCheck($check);

        // Assert
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(15, $result['response_time']);
    }

    #[Test]
    public function it_handles_ping_failure(): void
    {
        // Arrange
        Process::fake([
            'ping*' => Process::result(
                output: 'ping: unknown host',
                exitCode: 1
            ),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'ping',
            'target_url' => 'invalid-host.local',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performPingCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals('Ping failed: Host unreachable', $result['error']);
        $this->assertArrayHasKey('response_time', $result);
    }

    #[Test]
    public function it_parses_ping_url_correctly(): void
    {
        // Arrange
        Process::fake([
            'ping*' => Process::result(
                output: '64 bytes from example.com: time=25.5 ms',
                exitCode: 0
            ),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'ping',
            'target_url' => 'https://example.com/path',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performPingCheck($check);

        // Assert
        $this->assertEquals('success', $result['status']);
    }

    #[Test]
    public function it_handles_ping_check_exceptions(): void
    {
        // Arrange
        Process::fake(function () {
            throw new \Exception('Process execution failed');
        });

        $check = HealthCheck::factory()->create([
            'check_type' => 'ping',
            'target_url' => 'example.com',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performPingCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertEquals('Process execution failed', $result['error']);
    }

    #[Test]
    public function it_records_health_check_result(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create();

        // Act
        $this->service->recordResult(
            $check,
            'success',
            150,
            null,
            200
        );

        // Assert
        $this->assertDatabaseHas('health_check_results', [
            'health_check_id' => $check->id,
            'status' => 'success',
            'response_time_ms' => 150,
            'status_code' => 200,
            'error_message' => null,
        ]);

        $check->refresh();
        $this->assertNotNull($check->last_check_at);
    }

    #[Test]
    public function it_records_health_check_failure_with_error(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create();

        // Act
        $this->service->recordResult(
            $check,
            'failure',
            5000,
            'Connection timeout',
            null
        );

        // Assert
        $this->assertDatabaseHas('health_check_results', [
            'health_check_id' => $check->id,
            'status' => 'failure',
            'response_time_ms' => 5000,
            'error_message' => 'Connection timeout',
        ]);
    }

    #[Test]
    public function it_updates_check_status_to_healthy_on_success(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'degraded',
            'consecutive_failures' => 3,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'success',
            'response_time_ms' => 100,
            'checked_at' => now(),
        ]);

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals('healthy', $check->status);
        $this->assertEquals(0, $check->consecutive_failures);
        $this->assertNotNull($check->last_success_at);
    }

    #[Test]
    public function it_updates_check_status_to_degraded_on_first_failure(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'healthy',
            'consecutive_failures' => 0,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Connection refused',
            'checked_at' => now(),
        ]);

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals('degraded', $check->status);
        $this->assertEquals(1, $check->consecutive_failures);
        $this->assertNotNull($check->last_failure_at);
    }

    #[Test]
    public function it_updates_check_status_to_down_after_five_failures(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'degraded',
            'consecutive_failures' => 4,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Connection timeout',
            'checked_at' => now(),
        ]);

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals('down', $check->status);
        $this->assertEquals(5, $check->consecutive_failures);
    }

    #[Test]
    public function it_increments_consecutive_failures_correctly(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'degraded',
            'consecutive_failures' => 2,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'timeout',
            'error_message' => 'Request timed out',
            'checked_at' => now(),
        ]);

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals(3, $check->consecutive_failures);
    }

    #[Test]
    public function it_notifies_on_failure_when_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create(['status' => 'healthy']);
        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
            'notify_on_recovery' => false,
        ]);

        $result = HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Connection failed',
            'checked_at' => now(),
        ]);

        $this->notificationService
            ->expects($this->once())
            ->method('notifyHealthCheckFailure')
            ->with(
                $this->callback(fn ($c) => $c->id === $check->id),
                $this->callback(fn ($r) => $r->id === $result->id)
            );

        // Act
        $this->service->updateHealthCheckStatus($check);
    }

    #[Test]
    public function it_notifies_on_recovery_when_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'down',
            'consecutive_failures' => 5,
        ]);

        $channel = NotificationChannel::factory()->create([
            'type' => 'slack',
            'enabled' => true,
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => false,
            'notify_on_recovery' => true,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'success',
            'response_time_ms' => 100,
            'checked_at' => now(),
        ]);

        $this->notificationService
            ->expects($this->once())
            ->method('notifyHealthCheckRecovery')
            ->with($this->callback(fn ($c) => $c->id === $check->id));

        // Act
        $this->service->updateHealthCheckStatus($check);
    }

    #[Test]
    public function it_does_not_notify_when_channel_is_inactive(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create(['status' => 'healthy']);
        $channel = NotificationChannel::factory()->inactive()->create([
            'type' => 'slack',
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
            'notify_on_recovery' => false,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Connection failed',
            'checked_at' => now(),
        ]);

        $this->notificationService
            ->expects($this->never())
            ->method('notifyHealthCheckFailure');

        // Act
        $this->service->updateHealthCheckStatus($check);
    }

    #[Test]
    public function it_should_notify_returns_true_when_failure_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => true,
            'notify_on_recovery' => false,
        ]);

        // Act
        $result = $this->service->shouldNotify($check, 'failure');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_notify_returns_true_when_recovery_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => false,
            'notify_on_recovery' => true,
        ]);

        // Act
        $result = $this->service->shouldNotify($check, 'recovery');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_notify_returns_false_when_not_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'enabled' => true,
        ]);

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_failure' => false,
            'notify_on_recovery' => false,
        ]);

        // Act
        $resultFailure = $this->service->shouldNotify($check, 'failure');
        $resultRecovery = $this->service->shouldNotify($check, 'recovery');

        // Assert
        $this->assertFalse($resultFailure);
        $this->assertFalse($resultRecovery);
    }

    #[Test]
    public function it_logs_status_changes(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Health check status changed', $this->callback(function ($context) {
                return isset($context['check_id'])
                    && isset($context['check_name'])
                    && isset($context['previous_status'])
                    && isset($context['new_status']);
            }));

        $check = HealthCheck::factory()->create([
            'status' => 'healthy',
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Test failure',
            'checked_at' => now(),
        ]);

        // Act
        $this->service->updateHealthCheckStatus($check);
    }

    #[Test]
    public function it_runs_due_checks_successfully(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        // Create active checks that are due
        $dueCheck1 = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example1.com',
            'expected_status' => 200,
            'is_active' => true,
            'interval_minutes' => 5,
            'last_check_at' => now()->subMinutes(10),
        ]);

        $dueCheck2 = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example2.com',
            'expected_status' => 200,
            'is_active' => true,
            'interval_minutes' => 5,
            'last_check_at' => now()->subMinutes(6),
        ]);

        // Create check that is not due yet
        HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example3.com',
            'is_active' => true,
            'interval_minutes' => 10,
            'last_check_at' => now()->subMinutes(5),
        ]);

        // Create inactive check
        HealthCheck::factory()->create([
            'check_type' => 'http',
            'is_active' => false,
        ]);

        // Act
        $runCount = $this->service->runDueChecks();

        // Assert
        $this->assertEquals(2, $runCount);
        $this->assertDatabaseHas('health_check_results', [
            'health_check_id' => $dueCheck1->id,
        ]);
        $this->assertDatabaseHas('health_check_results', [
            'health_check_id' => $dueCheck2->id,
        ]);
    }

    #[Test]
    public function it_runs_check_with_never_checked_before(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'is_active' => true,
            'last_check_at' => null,
        ]);

        // Act
        $runCount = $this->service->runDueChecks();

        // Assert
        $this->assertEquals(1, $runCount);
        $check->refresh();
        $this->assertNotNull($check->last_check_at);
    }

    #[Test]
    public function it_handles_run_check_exceptions_gracefully(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('Health check failed', $this->callback(function ($context) {
                return isset($context['check_id']) && isset($context['error']);
            }));

        // Create a valid check
        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'is_active' => true,
            'last_check_at' => null,
        ]);

        // Mock Http to throw an unexpected exception
        Http::fake(function () {
            throw new \RuntimeException('Unexpected test error');
        });

        // Act
        $runCount = $this->service->runDueChecks();

        // Assert - Should continue processing despite error
        $this->assertEquals(0, $runCount);
    }

    #[Test]
    public function it_runs_check_and_returns_result(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->runCheck($check);

        // Assert
        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertEquals($check->id, $result->health_check_id);
        $this->assertEquals('success', $result->status);
        $this->assertNotNull($result->response_time_ms);
    }

    #[Test]
    public function it_throws_exception_for_unknown_check_type(): void
    {
        // Arrange - create a real HealthCheck model instance (not saved to DB)
        $check = new HealthCheck;
        $check->id = 1;
        $check->name = 'Test Check';
        $check->target_url = 'https://example.com';
        $check->timeout_seconds = 10;
        $check->status = 'healthy';
        // Set invalid check_type using forceFill to bypass validation
        $check->forceFill(['check_type' => 'invalid_type']);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown check type: invalid_type');

        // Act
        $this->service->runCheck($check);
    }

    #[Test]
    public function it_performs_ssl_expiry_check_structure(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'check_type' => 'ssl_expiry',
            'target_url' => 'https://example.com',
            'timeout_seconds' => 5,
        ]);

        // Act
        $result = $this->service->performSSLExpiryCheck($check);

        // Assert
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertContains($result['status'], ['success', 'failure']);
    }

    #[Test]
    public function it_handles_ssl_check_exceptions(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'check_type' => 'ssl_expiry',
            'target_url' => 'https://invalid-ssl-host.local',
            'timeout_seconds' => 1,
        ]);

        // Act
        $result = $this->service->performSSLExpiryCheck($check);

        // Assert
        $this->assertEquals('failure', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_runtime_exception_when_result_not_found(): void
    {
        // Arrange
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $check = HealthCheck::factory()->create([
            'check_type' => 'http',
            'target_url' => 'https://example.com',
            'expected_status' => 200,
        ]);

        // Note: This test verifies the exception is thrown when result retrieval fails
        // In normal operation, this shouldn't happen as the database insert will succeed
        // We test by immediately deleting results after creation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to retrieve health check result after execution');

        // Create a custom service that deletes results immediately
        $customService = new class($this->notificationService) extends HealthCheckService
        {
            public function recordResult(
                HealthCheck $check,
                string $status,
                ?int $responseTime = null,
                ?string $error = null,
                ?int $statusCode = null
            ): void {
                parent::recordResult($check, $status, $responseTime, $error, $statusCode);
                // Delete all results to simulate failure
                $check->results()->delete();
            }
        };

        // Act
        $customService->runCheck($check);
    }

    #[Test]
    public function it_does_not_notify_when_no_channels_configured(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create(['status' => 'healthy']);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'failure',
            'error_message' => 'Connection failed',
            'checked_at' => now(),
        ]);

        $this->notificationService
            ->expects($this->never())
            ->method('notifyHealthCheckFailure');

        // Act
        $this->service->updateHealthCheckStatus($check);
    }

    #[Test]
    public function it_does_not_update_status_when_no_results_exist(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'status' => 'healthy',
            'consecutive_failures' => 0,
        ]);

        $originalStatus = $check->status;
        $originalFailures = $check->consecutive_failures;

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals($originalStatus, $check->status);
        $this->assertEquals($originalFailures, $check->consecutive_failures);
    }

    #[Test]
    public function it_handles_unknown_status_in_check_correctly(): void
    {
        // Arrange - 'unknown' status is treated as healthy, so no recovery notification
        // when transitioning from unknown to healthy (success result)
        $check = HealthCheck::factory()->create([
            'status' => 'unknown',
            'consecutive_failures' => 0,
        ]);

        $channel = NotificationChannel::factory()->active()->create();

        $check->notificationChannels()->attach($channel->id, [
            'notify_on_recovery' => true,
        ]);

        HealthCheckResult::create([
            'health_check_id' => $check->id,
            'status' => 'success',
            'response_time_ms' => 100,
            'checked_at' => now(),
        ]);

        // 'unknown' is considered healthy, so no recovery notification expected
        $this->notificationService
            ->expects($this->never())
            ->method('notifyHealthCheckRecovery');

        // Act
        $this->service->updateHealthCheckStatus($check);

        // Assert
        $check->refresh();
        $this->assertEquals('healthy', $check->status);
    }

    #[Test]
    public function it_parses_url_for_ssl_check_with_port(): void
    {
        // Arrange
        $check = HealthCheck::factory()->create([
            'check_type' => 'ssl_expiry',
            'target_url' => 'https://example.com:8443',
            'timeout_seconds' => 1,
        ]);

        // Act
        $result = $this->service->performSSLExpiryCheck($check);

        // Assert
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
    }
}
