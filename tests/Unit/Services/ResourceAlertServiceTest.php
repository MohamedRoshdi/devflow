<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\ServerMetric;
use App\Services\AlertNotificationService;
use App\Services\ResourceAlertService;
use App\Services\ServerMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

class ResourceAlertServiceTest extends TestCase
{
    use CreatesServers, RefreshDatabase;

    protected ResourceAlertService $service;

    protected ServerMetricsService $metricsService;

    protected AlertNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsService = Mockery::mock(ServerMetricsService::class);
        $this->notificationService = Mockery::mock(AlertNotificationService::class);

        $this->service = new ResourceAlertService(
            $this->metricsService,
            $this->notificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== checkServerResources Tests ====================

    #[Test]
    public function it_checks_server_resources_successfully_with_latest_metrics(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'disk_usage' => 75.3,
            'load_average_1' => 2.5,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->with($server)
            ->andReturn($metric);

        // Act
        $result = $this->service->checkServerResources($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(45.5, $result['cpu']);
        $this->assertEquals(60.2, $result['memory']);
        $this->assertEquals(75.3, $result['disk']);
        $this->assertEquals(2.5, $result['load']);
        $this->assertInstanceOf(ServerMetric::class, $result['metrics']);
    }

    #[Test]
    public function it_collects_fresh_metrics_when_latest_not_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 30.0,
            'memory_usage' => 50.0,
            'disk_usage' => 40.0,
            'load_average_1' => 1.5,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->with($server)
            ->andReturn(null);

        $this->metricsService->shouldReceive('collectMetrics')
            ->once()
            ->with($server)
            ->andReturn($metric);

        // Act
        $result = $this->service->checkServerResources($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(30.0, $result['cpu']);
    }

    #[Test]
    public function it_returns_failure_when_no_metrics_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->with($server)
            ->andReturn(null);

        $this->metricsService->shouldReceive('collectMetrics')
            ->once()
            ->with($server)
            ->andReturn(null);

        // Act
        $result = $this->service->checkServerResources($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to collect server metrics', $result['message']);
    }

    #[Test]
    public function it_handles_exception_during_resource_check(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->with($server)
            ->andThrow(new \Exception('Metrics service error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to check server resources', [
                'server_id' => $server->id,
                'error' => 'Metrics service error',
            ]);

        // Act
        $result = $this->service->checkServerResources($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Metrics service error', $result['message']);
    }

    // ==================== evaluateAlerts Tests ====================

    #[Test]
    public function it_evaluates_alerts_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 85.0,
            'memory_usage' => 70.0,
            'disk_usage' => 60.0,
            'load_average_1' => 3.5,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $cpuAlert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
            'cooldown_minutes' => 5,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['checked']);
        $this->assertEquals(1, $result['triggered']);
        $this->assertEquals(0, $result['resolved']);
    }

    #[Test]
    public function it_returns_error_when_resource_check_fails(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn(null);

        $this->metricsService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(null);

        Log::shouldReceive('error');

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(0, $result['checked']);
        $this->assertEquals(0, $result['triggered']);
        $this->assertEquals(0, $result['resolved']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_triggers_cpu_alert_when_threshold_exceeded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
            'memory_usage' => 50.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['triggered']);
        $this->assertDatabaseHas('alert_history', [
            'resource_alert_id' => $alert->id,
            'status' => 'triggered',
        ]);
    }

    #[Test]
    public function it_triggers_memory_alert_when_threshold_exceeded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 30.0,
            'memory_usage' => 92.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'memory',
            'threshold_type' => 'above',
            'threshold_value' => 85.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['triggered']);
    }

    #[Test]
    public function it_triggers_disk_alert_when_threshold_exceeded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 30.0,
            'disk_usage' => 88.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'disk',
            'threshold_type' => 'above',
            'threshold_value' => 85.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['triggered']);
    }

    #[Test]
    public function it_triggers_load_alert_when_threshold_exceeded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 30.0,
            'load_average_1' => 10.5,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'load',
            'threshold_type' => 'above',
            'threshold_value' => 8.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['triggered']);
    }

    #[Test]
    public function it_resolves_alert_when_threshold_no_longer_exceeded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 60.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        // Create a previous triggered alert
        AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $server->id,
            'status' => 'triggered',
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(0, $result['triggered']);
        $this->assertEquals(1, $result['resolved']);
    }

    #[Test]
    public function it_does_not_trigger_inactive_alerts(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => false,
        ]);

        $this->notificationService->shouldNotReceive('send');

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(0, $result['checked']);
        $this->assertEquals(0, $result['triggered']);
    }

    #[Test]
    public function it_respects_cooldown_period(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
            'cooldown_minutes' => 10,
            'last_triggered_at' => now()->subMinutes(5), // Still in cooldown
        ]);

        $this->notificationService->shouldNotReceive('send');

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(0, $result['triggered']);
    }

    #[Test]
    public function it_triggers_after_cooldown_period_expires(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 95.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
            'cooldown_minutes' => 10,
            'last_triggered_at' => now()->subMinutes(15), // Cooldown expired
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['triggered']);
    }

    #[Test]
    public function it_evaluates_multiple_alerts_simultaneously(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 90.0,
            'memory_usage' => 85.0,
            'disk_usage' => 75.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'memory',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'disk',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldReceive('send')
            ->times(2); // CPU and memory should trigger, not disk

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(3, $result['checked']);
        $this->assertEquals(2, $result['triggered']);
    }

    #[Test]
    public function it_skips_alerts_with_null_current_value(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        // Create a metric with only cpu_usage set, leaving disk_usage as null
        $metric = ServerMetric::factory()->make([
            'server_id' => $server->id,
            'cpu_usage' => 90.0,
            'disk_usage' => null,
            'memory_usage' => 50.0,
            'load_average_1' => 2.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        // Create a disk alert, but metric has null disk_usage
        ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'disk',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
            'is_active' => true,
        ]);

        $this->notificationService->shouldNotReceive('send');

        // Act
        $result = $this->service->evaluateAlerts($server);

        // Assert
        $this->assertEquals(1, $result['checked']);
        $this->assertEquals(0, $result['triggered']);
    }

    // ==================== triggerAlert Tests ====================

    #[Test]
    public function it_triggers_alert_and_creates_history(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        Log::shouldReceive('info')
            ->once()
            ->with('Resource alert triggered', Mockery::any());

        // Act
        $history = $this->service->triggerAlert($alert, 95.0);

        // Assert
        $this->assertInstanceOf(AlertHistory::class, $history);
        $this->assertEquals($alert->id, $history->resource_alert_id);
        $this->assertEquals($server->id, $history->server_id);
        $this->assertEquals('cpu', $history->resource_type);
        $this->assertEquals(95.0, $history->current_value);
        $this->assertEquals(80.0, $history->threshold_value);
        $this->assertEquals('triggered', $history->status);
        $this->assertNotNull($history->message);
        $this->assertNotNull($history->notified_at);
    }

    #[Test]
    public function it_updates_last_triggered_at_when_alert_triggers(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'last_triggered_at' => null,
        ]);

        $this->notificationService->shouldReceive('send')->once();
        Log::shouldReceive('info');

        // Act
        $this->service->triggerAlert($alert, 95.0);

        // Assert
        $alert->refresh();
        $this->assertNotNull($alert->last_triggered_at);
    }

    #[Test]
    public function it_handles_notification_failure_gracefully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
        ]);

        $this->notificationService->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Notification failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send alert notification', Mockery::any());

        Log::shouldReceive('info')
            ->once();

        // Act
        $history = $this->service->triggerAlert($alert, 95.0);

        // Assert
        $this->assertInstanceOf(AlertHistory::class, $history);
        $this->assertEquals('triggered', $history->status);
    }

    #[Test]
    public function it_builds_correct_trigger_message_for_cpu_alert(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        $this->notificationService->shouldReceive('send')->once();
        Log::shouldReceive('info');

        // Act
        $history = $this->service->triggerAlert($alert, 95.5);

        // Assert
        $this->assertStringContainsString('CPU Usage', $history->message);
        $this->assertStringContainsString('Test Server', $history->message);
        $this->assertStringContainsString('above', $history->message);
        $this->assertStringContainsString('95.50', $history->message);
        $this->assertStringContainsString('80.00', $history->message);
        $this->assertStringContainsString('%', $history->message);
    }

    // ==================== resolveAlert Tests ====================

    #[Test]
    public function it_resolves_alert_and_creates_history(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        $this->notificationService->shouldReceive('send')
            ->once();

        Log::shouldReceive('info')
            ->once()
            ->with('Resource alert resolved', Mockery::any());

        // Act
        $history = $this->service->resolveAlert($alert, 60.0);

        // Assert
        $this->assertInstanceOf(AlertHistory::class, $history);
        $this->assertEquals($alert->id, $history->resource_alert_id);
        $this->assertEquals('resolved', $history->status);
        $this->assertEquals(60.0, $history->current_value);
    }

    #[Test]
    public function it_builds_correct_resolve_message_for_cpu_alert(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        $this->notificationService->shouldReceive('send')->once();
        Log::shouldReceive('info');

        // Act
        $history = $this->service->resolveAlert($alert, 65.5);

        // Assert
        $this->assertStringContainsString('CPU Usage', $history->message);
        $this->assertStringContainsString('Test Server', $history->message);
        $this->assertStringContainsString('returned to normal', $history->message);
        $this->assertStringContainsString('65.50', $history->message);
    }

    #[Test]
    public function it_handles_notification_failure_during_resolution(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
        ]);

        $this->notificationService->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Notification failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send resolution notification', Mockery::any());

        Log::shouldReceive('info')
            ->once();

        // Act
        $history = $this->service->resolveAlert($alert, 60.0);

        // Assert
        $this->assertInstanceOf(AlertHistory::class, $history);
        $this->assertEquals('resolved', $history->status);
    }

    // ==================== shouldTriggerAlert Tests ====================

    #[Test]
    public function it_determines_alert_should_trigger_when_above_threshold(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->make([
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        // Act & Assert
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('shouldTriggerAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, $alert, 85.0));
        $this->assertFalse($method->invoke($this->service, $alert, 75.0));
        $this->assertFalse($method->invoke($this->service, $alert, 80.0));
    }

    #[Test]
    public function it_determines_alert_should_trigger_when_below_threshold(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->make([
            'threshold_type' => 'below',
            'threshold_value' => 20.0,
        ]);

        // Act & Assert
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('shouldTriggerAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, $alert, 15.0));
        $this->assertFalse($method->invoke($this->service, $alert, 25.0));
        $this->assertFalse($method->invoke($this->service, $alert, 20.0));
    }

    // ==================== canTrigger Tests ====================

    #[Test]
    public function it_can_trigger_alert_when_not_in_cooldown(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->make([
            'cooldown_minutes' => 10,
            'last_triggered_at' => now()->subMinutes(15),
        ]);

        // Act
        $result = $this->service->canTrigger($alert);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_cannot_trigger_alert_when_in_cooldown(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->make([
            'cooldown_minutes' => 10,
            'last_triggered_at' => now()->subMinutes(5),
        ]);

        // Act
        $result = $this->service->canTrigger($alert);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_trigger_alert_when_never_triggered_before(): void
    {
        // Arrange
        $alert = ResourceAlert::factory()->make([
            'cooldown_minutes' => 10,
            'last_triggered_at' => null,
        ]);

        // Act
        $result = $this->service->canTrigger($alert);

        // Assert
        $this->assertTrue($result);
    }

    // ==================== wasAlertTriggered Tests ====================

    #[Test]
    public function it_detects_previously_triggered_alert(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
        ]);

        AlertHistory::factory()->create([
            'resource_alert_id' => $alert->id,
            'server_id' => $server->id,
            'status' => 'triggered',
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('wasAlertTriggered');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $alert);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_detects_alert_was_not_triggered(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('wasAlertTriggered');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $alert);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== buildAlertMessage Tests ====================

    #[Test]
    public function it_builds_triggered_message_for_cpu_above_threshold(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Production Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 92.5, 'triggered');

        // Assert
        $this->assertStringContainsString('CPU Usage', $message);
        $this->assertStringContainsString('Production Server', $message);
        $this->assertStringContainsString('above', $message);
        $this->assertStringContainsString('92.50%', $message);
        $this->assertStringContainsString('> 80.00%', $message);
    }

    #[Test]
    public function it_builds_triggered_message_for_memory_above_threshold(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Web Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'memory',
            'threshold_type' => 'above',
            'threshold_value' => 85.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 91.0, 'triggered');

        // Assert
        $this->assertStringContainsString('Memory Usage', $message);
        $this->assertStringContainsString('Web Server', $message);
        $this->assertStringContainsString('91.00%', $message);
    }

    #[Test]
    public function it_builds_triggered_message_for_disk_above_threshold(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'DB Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'disk',
            'threshold_type' => 'above',
            'threshold_value' => 90.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 95.5, 'triggered');

        // Assert
        $this->assertStringContainsString('Disk Usage', $message);
        $this->assertStringContainsString('DB Server', $message);
        $this->assertStringContainsString('95.50%', $message);
    }

    #[Test]
    public function it_builds_triggered_message_for_load_above_threshold(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'App Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'load',
            'threshold_type' => 'above',
            'threshold_value' => 5.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 8.25, 'triggered');

        // Assert
        $this->assertStringContainsString('Load Average', $message);
        $this->assertStringContainsString('App Server', $message);
        $this->assertStringContainsString('8.25', $message);
        $this->assertStringNotContainsString('%', $message); // Load doesn't use percentage
    }

    #[Test]
    public function it_builds_resolved_message_for_cpu_alert(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Production Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 80.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 65.0, 'resolved');

        // Assert
        $this->assertStringContainsString('CPU Usage', $message);
        $this->assertStringContainsString('Production Server', $message);
        $this->assertStringContainsString('returned to normal', $message);
        $this->assertStringContainsString('65.00%', $message);
    }

    #[Test]
    public function it_builds_message_for_below_threshold_type(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_type' => 'below',
            'threshold_value' => 10.0,
        ]);

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildAlertMessage');
        $method->setAccessible(true);
        $message = $method->invoke($this->service, $alert, 5.0, 'triggered');

        // Assert
        $this->assertStringContainsString('below', $message);
        $this->assertStringContainsString('< 10.00%', $message);
    }

    // ==================== testAlert Tests ====================

    #[Test]
    public function it_tests_alert_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);

        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 75.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $this->metricsService->shouldReceive('collectMetrics')
            ->never();

        $this->notificationService->shouldReceive('send')
            ->once()
            ->with($alert, Mockery::on(function ($history) {
                return $history instanceof AlertHistory &&
                       str_starts_with($history->message, '[TEST]');
            }));

        // Act
        $result = $this->service->testAlert($alert);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Test notification sent successfully', $result['message']);
        $this->assertEquals(75.0, $result['current_value']);
    }

    #[Test]
    public function it_tests_alert_with_fresh_metrics_when_latest_not_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
        ]);

        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 50.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn(null);

        $this->metricsService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn($metric);

        $this->notificationService->shouldReceive('send')->once();

        // Act
        $result = $this->service->testAlert($alert);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(50.0, $result['current_value']);
    }

    #[Test]
    public function it_returns_failure_when_testing_alert_without_metrics(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn(null);

        $this->metricsService->shouldReceive('collectMetrics')
            ->once()
            ->andReturn(null);

        Log::shouldReceive('error');

        $this->notificationService->shouldNotReceive('send');

        // Act
        $result = $this->service->testAlert($alert);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to get server metrics', $result['message']);
    }

    #[Test]
    public function it_handles_exception_during_test_alert(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andThrow(new \Exception('Test error'));

        // The exception will be caught in checkServerResources which logs the error
        Log::shouldReceive('error')
            ->with('Failed to check server resources', Mockery::any());

        // Act
        $result = $this->service->testAlert($alert);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to get server metrics', $result['message']);
        $this->assertStringContainsString('Test error', $result['message']);
    }

    #[Test]
    public function it_includes_test_prefix_in_test_alert_message(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $alert = ResourceAlert::factory()->create([
            'server_id' => $server->id,
            'resource_type' => 'cpu',
            'threshold_value' => 80.0,
        ]);

        $metric = ServerMetric::factory()->create([
            'server_id' => $server->id,
            'cpu_usage' => 45.0,
        ]);

        $this->metricsService->shouldReceive('getLatestMetrics')
            ->once()
            ->andReturn($metric);

        $capturedHistory = null;
        $this->notificationService->shouldReceive('send')
            ->once()
            ->with($alert, Mockery::capture($capturedHistory));

        // Act
        $result = $this->service->testAlert($alert);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('[TEST]', $capturedHistory->message);
    }
}
