<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BulkServerActionService;
use App\Services\DockerInstallationService;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

class BulkServerActionServiceTest extends TestCase
{
    use CreatesServers;

    protected BulkServerActionService $service;

    protected ServerConnectivityService $connectivityService;

    protected DockerInstallationService $dockerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectivityService = Mockery::mock(ServerConnectivityService::class);
        $this->dockerService = Mockery::mock(DockerInstallationService::class);

        $this->service = new BulkServerActionService(
            $this->connectivityService,
            $this->dockerService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // PING SERVERS TESTS
    // ==========================================

    /** @test */
    public function it_pings_multiple_servers_successfully(): void
    {
        // Arrange
        $server1 = $this->createOfflineServer(['name' => 'Server 1']);
        $server2 = $this->createOfflineServer(['name' => 'Server 2']);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server1)
            ->andReturn([
                'reachable' => true,
                'message' => 'Connection successful',
                'latency_ms' => 45.2,
            ]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server2)
            ->andReturn([
                'reachable' => true,
                'message' => 'Connection successful',
                'latency_ms' => 38.7,
            ]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertTrue($results[$server2->id]['success']);
        $this->assertEquals('Server 1', $results[$server1->id]['server_name']);
        $this->assertEquals('Server 2', $results[$server2->id]['server_name']);
        $this->assertEquals(45.2, $results[$server1->id]['latency_ms']);
        $this->assertEquals(38.7, $results[$server2->id]['latency_ms']);
    }

    /** @test */
    public function it_updates_server_status_to_online_after_successful_ping(): void
    {
        // Arrange
        $server = $this->createOfflineServer(['status' => 'offline']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server)
            ->andReturn([
                'reachable' => true,
                'message' => 'Connection successful',
                'latency_ms' => 50.0,
            ]);

        // Act
        $this->service->pingServers($servers);

        // Assert
        $this->assertEquals('online', $server->fresh()->status);
        $this->assertNotNull($server->fresh()->last_ping_at);
    }

    /** @test */
    public function it_updates_server_status_to_offline_after_failed_ping(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['status' => 'online']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server)
            ->andReturn([
                'reachable' => false,
                'message' => 'Connection failed',
            ]);

        // Act
        $this->service->pingServers($servers);

        // Assert
        $this->assertEquals('offline', $server->fresh()->status);
        $this->assertNotNull($server->fresh()->last_ping_at);
    }

    /** @test */
    public function it_handles_mixed_success_and_failure_ping_results(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Online Server']);
        $server2 = $this->createOfflineServer(['name' => 'Offline Server']);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server1)
            ->andReturn([
                'reachable' => true,
                'message' => 'Connection successful',
                'latency_ms' => 25.0,
            ]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server2)
            ->andReturn([
                'reachable' => false,
                'message' => 'Connection refused',
            ]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertFalse($results[$server2->id]['success']);
        $this->assertEquals('online', $server1->fresh()->status);
        $this->assertEquals('offline', $server2->fresh()->status);
    }

    /** @test */
    public function it_handles_ping_exception_and_logs_error(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        Log::shouldReceive('error')
            ->once()
            ->with('Bulk ping failed for server', Mockery::type('array'));

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server)
            ->andThrow(new \Exception('Network error'));

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertEquals('Test Server', $results[$server->id]['server_name']);
        $this->assertStringContainsString('Network error', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_empty_server_collection_for_ping(): void
    {
        // Arrange
        $servers = collect([]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_includes_latency_only_when_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->once()
            ->with($server)
            ->andReturn([
                'reachable' => true,
                'message' => 'Connection successful',
            ]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertArrayHasKey('latency_ms', $results[$server->id]);
        $this->assertNull($results[$server->id]['latency_ms']);
    }

    /** @test */
    public function it_updates_last_ping_at_timestamp_for_all_servers(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['last_ping_at' => null]);
        $server2 = $this->createOnlineServer(['last_ping_at' => null]);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->twice()
            ->andReturn([
                'reachable' => true,
                'message' => 'Success',
                'latency_ms' => 10.0,
            ]);

        // Act
        $this->service->pingServers($servers);

        // Assert
        $this->assertNotNull($server1->fresh()->last_ping_at);
        $this->assertNotNull($server2->fresh()->last_ping_at);
    }

    // ==========================================
    // REBOOT SERVERS TESTS
    // ==========================================

    /** @test */
    public function it_reboots_multiple_servers_successfully(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Server 1']);
        $server2 = $this->createOnlineServer(['name' => 'Server 2']);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server1)
            ->andReturn([
                'success' => true,
                'message' => 'Server reboot initiated',
            ]);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server2)
            ->andReturn([
                'success' => true,
                'message' => 'Server reboot initiated',
            ]);

        Log::shouldReceive('info')
            ->twice()
            ->with('Bulk reboot successful', Mockery::type('array'));

        // Act
        $results = $this->service->rebootServers($servers);

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertTrue($results[$server2->id]['success']);
        $this->assertEquals('Server 1', $results[$server1->id]['server_name']);
        $this->assertEquals('Server 2', $results[$server2->id]['server_name']);
    }

    /** @test */
    public function it_handles_reboot_failure_for_individual_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server)
            ->andReturn([
                'success' => false,
                'message' => 'Reboot command failed',
            ]);

        // Act
        $results = $this->service->rebootServers($servers);

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertEquals('Reboot command failed', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_reboot_exception_and_logs_error(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        Log::shouldReceive('error')
            ->once()
            ->with('Bulk reboot failed for server', Mockery::type('array'));

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server)
            ->andThrow(new \Exception('SSH connection timeout'));

        // Act
        $results = $this->service->rebootServers($servers);

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertStringContainsString('SSH connection timeout', $results[$server->id]['message']);
    }

    /** @test */
    public function it_logs_successful_reboot_operations(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server)
            ->andReturn([
                'success' => true,
                'message' => 'Reboot successful',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Bulk reboot successful', ['server_id' => $server->id]);

        // Act
        $this->service->rebootServers($servers);
    }

    /** @test */
    public function it_handles_empty_server_collection_for_reboot(): void
    {
        // Arrange
        $servers = collect([]);

        // Act
        $results = $this->service->rebootServers($servers);

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_continues_rebooting_remaining_servers_after_failure(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Server 1']);
        $server2 = $this->createOnlineServer(['name' => 'Server 2']);
        $server3 = $this->createOnlineServer(['name' => 'Server 3']);
        $servers = collect([$server1, $server2, $server3]);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server1)
            ->andReturn(['success' => true, 'message' => 'Success']);

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server2)
            ->andThrow(new \Exception('Failed'));

        $this->connectivityService
            ->shouldReceive('rebootServer')
            ->once()
            ->with($server3)
            ->andReturn(['success' => true, 'message' => 'Success']);

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->once();

        // Act
        $results = $this->service->rebootServers($servers);

        // Assert
        $this->assertCount(3, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertFalse($results[$server2->id]['success']);
        $this->assertTrue($results[$server3->id]['success']);
    }

    // ==========================================
    // INSTALL DOCKER ON SERVERS TESTS
    // ==========================================

    /** @test */
    public function it_installs_docker_on_multiple_servers_successfully(): void
    {
        // Arrange
        $server1 = $this->createServer(['name' => 'Server 1', 'docker_installed' => false]);
        $server2 = $this->createServer(['name' => 'Server 2', 'docker_installed' => false]);
        $servers = collect([$server1, $server2]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->twice()
            ->andReturn(['installed' => false]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server1)
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully',
                'version' => '24.0.7',
            ]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server2)
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully',
                'version' => '24.0.7',
            ]);

        Log::shouldReceive('info')
            ->twice()
            ->with('Bulk Docker installation successful', Mockery::type('array'));

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertTrue($results[$server2->id]['success']);
        $this->assertEquals('24.0.7', $results[$server1->id]['version']);
        $this->assertFalse($results[$server1->id]['already_installed']);
    }

    /** @test */
    public function it_skips_installation_when_docker_already_installed(): void
    {
        // Arrange
        $server = $this->createServerWithDocker(['name' => 'Server 1']);
        $servers = collect([$server]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->once()
            ->with($server)
            ->andReturn([
                'installed' => true,
                'version' => '24.0.7',
            ]);

        // installDocker should not be called
        $this->dockerService->shouldNotReceive('installDocker');

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertTrue($results[$server->id]['success']);
        $this->assertTrue($results[$server->id]['already_installed']);
        $this->assertStringContainsString('already installed', $results[$server->id]['message']);
        $this->assertStringContainsString('24.0.7', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_docker_installation_failure(): void
    {
        // Arrange
        $server = $this->createServer(['name' => 'Test Server', 'docker_installed' => false]);
        $servers = collect([$server]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->once()
            ->andReturn(['installed' => false]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server)
            ->andReturn([
                'success' => false,
                'message' => 'Installation failed: Permission denied',
            ]);

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertStringContainsString('Installation failed', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_docker_installation_exception_and_logs_error(): void
    {
        // Arrange
        $server = $this->createServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        Log::shouldReceive('error')
            ->once()
            ->with('Bulk Docker installation failed for server', Mockery::type('array'));

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertStringContainsString('Connection timeout', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_empty_server_collection_for_docker_installation(): void
    {
        // Arrange
        $servers = collect([]);

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_logs_successful_docker_installations_with_version(): void
    {
        // Arrange
        $server = $this->createServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->once()
            ->andReturn(['installed' => false]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server)
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed',
                'version' => '25.0.1',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Bulk Docker installation successful', [
                'server_id' => $server->id,
                'version' => '25.0.1',
            ]);

        // Act
        $this->service->installDockerOnServers($servers);
    }

    /** @test */
    public function it_handles_docker_verification_returning_unknown_version(): void
    {
        // Arrange
        $server = $this->createServerWithDocker(['name' => 'Server 1']);
        $servers = collect([$server]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->once()
            ->with($server)
            ->andReturn([
                'installed' => true,
                'version' => null,
            ]);

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertTrue($results[$server->id]['success']);
        $this->assertTrue($results[$server->id]['already_installed']);
        $this->assertStringContainsString('unknown', $results[$server->id]['message']);
    }

    /** @test */
    public function it_continues_installing_docker_on_remaining_servers_after_failure(): void
    {
        // Arrange
        $server1 = $this->createServer(['name' => 'Server 1']);
        $server2 = $this->createServer(['name' => 'Server 2']);
        $server3 = $this->createServer(['name' => 'Server 3']);
        $servers = collect([$server1, $server2, $server3]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->times(3)
            ->andReturn(['installed' => false]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server1)
            ->andReturn(['success' => true, 'message' => 'Success', 'version' => '24.0.7']);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server2)
            ->andThrow(new \Exception('Failed'));

        $this->dockerService
            ->shouldReceive('installDocker')
            ->once()
            ->with($server3)
            ->andReturn(['success' => true, 'message' => 'Success', 'version' => '24.0.7']);

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->once();

        // Act
        $results = $this->service->installDockerOnServers($servers);

        // Assert
        $this->assertCount(3, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertFalse($results[$server2->id]['success']);
        $this->assertTrue($results[$server3->id]['success']);
    }

    // ==========================================
    // RESTART SERVICE ON SERVERS TESTS
    // ==========================================

    /** @test */
    public function it_restarts_service_on_multiple_servers_successfully(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Server 1']);
        $server2 = $this->createOnlineServer(['name' => 'Server 2']);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server1, 'nginx')
            ->andReturn([
                'success' => true,
                'message' => 'Service restarted successfully',
            ]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server2, 'nginx')
            ->andReturn([
                'success' => true,
                'message' => 'Service restarted successfully',
            ]);

        Log::shouldReceive('info')
            ->twice()
            ->with('Bulk service restart successful', Mockery::type('array'));

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'nginx');

        // Assert
        $this->assertCount(2, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertTrue($results[$server2->id]['success']);
        $this->assertEquals('nginx', $results[$server1->id]['service']);
        $this->assertEquals('nginx', $results[$server2->id]['service']);
    }

    /** @test */
    public function it_restarts_different_services_on_servers(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server, 'docker')
            ->andReturn([
                'success' => true,
                'message' => 'Docker service restarted',
            ]);

        Log::shouldReceive('info')->once();

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'docker');

        // Assert
        $this->assertTrue($results[$server->id]['success']);
        $this->assertEquals('docker', $results[$server->id]['service']);
    }

    /** @test */
    public function it_handles_service_restart_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server, 'nginx')
            ->andReturn([
                'success' => false,
                'message' => 'Service restart failed',
            ]);

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'nginx');

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertEquals('Service restart failed', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_service_restart_exception_and_logs_error(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        Log::shouldReceive('error')
            ->once()
            ->with('Bulk service restart failed for server', Mockery::type('array'));

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server, 'nginx')
            ->andThrow(new \Exception('Service not found'));

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'nginx');

        // Assert
        $this->assertFalse($results[$server->id]['success']);
        $this->assertStringContainsString('Service not found', $results[$server->id]['message']);
    }

    /** @test */
    public function it_handles_empty_server_collection_for_service_restart(): void
    {
        // Arrange
        $servers = collect([]);

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'nginx');

        // Assert
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_logs_successful_service_restart_with_server_and_service_info(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['name' => 'Test Server']);
        $servers = collect([$server]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server, 'redis')
            ->andReturn([
                'success' => true,
                'message' => 'Redis restarted',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Bulk service restart successful', [
                'server_id' => $server->id,
                'service' => 'redis',
            ]);

        // Act
        $this->service->restartServiceOnServers($servers, 'redis');
    }

    /** @test */
    public function it_continues_restarting_service_on_remaining_servers_after_failure(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Server 1']);
        $server2 = $this->createOnlineServer(['name' => 'Server 2']);
        $server3 = $this->createOnlineServer(['name' => 'Server 3']);
        $servers = collect([$server1, $server2, $server3]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server1, 'nginx')
            ->andReturn(['success' => true, 'message' => 'Success']);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server2, 'nginx')
            ->andThrow(new \Exception('Failed'));

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server3, 'nginx')
            ->andReturn(['success' => true, 'message' => 'Success']);

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->once();

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'nginx');

        // Assert
        $this->assertCount(3, $results);
        $this->assertTrue($results[$server1->id]['success']);
        $this->assertFalse($results[$server2->id]['success']);
        $this->assertTrue($results[$server3->id]['success']);
    }

    /** @test */
    public function it_includes_service_name_in_all_results(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer(['name' => 'Server 1']);
        $server2 = $this->createOnlineServer(['name' => 'Server 2']);
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server1, 'mysql')
            ->andReturn(['success' => true, 'message' => 'Success']);

        $this->connectivityService
            ->shouldReceive('restartService')
            ->once()
            ->with($server2, 'mysql')
            ->andReturn(['success' => false, 'message' => 'Failed']);

        Log::shouldReceive('info')->once();

        // Act
        $results = $this->service->restartServiceOnServers($servers, 'mysql');

        // Assert
        $this->assertEquals('mysql', $results[$server1->id]['service']);
        $this->assertEquals('mysql', $results[$server2->id]['service']);
    }

    // ==========================================
    // GET SUMMARY STATS TESTS
    // ==========================================

    /** @test */
    public function it_calculates_summary_stats_for_all_successful_operations(): void
    {
        // Arrange
        $results = [
            1 => ['success' => true, 'message' => 'OK'],
            2 => ['success' => true, 'message' => 'OK'],
            3 => ['success' => true, 'message' => 'OK'],
        ];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(3, $stats['successful']);
        $this->assertEquals(0, $stats['failed']);
    }

    /** @test */
    public function it_calculates_summary_stats_for_all_failed_operations(): void
    {
        // Arrange
        $results = [
            1 => ['success' => false, 'message' => 'Failed'],
            2 => ['success' => false, 'message' => 'Failed'],
        ];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(0, $stats['successful']);
        $this->assertEquals(2, $stats['failed']);
    }

    /** @test */
    public function it_calculates_summary_stats_for_mixed_results(): void
    {
        // Arrange
        $results = [
            1 => ['success' => true, 'message' => 'OK'],
            2 => ['success' => false, 'message' => 'Failed'],
            3 => ['success' => true, 'message' => 'OK'],
            4 => ['success' => false, 'message' => 'Failed'],
            5 => ['success' => true, 'message' => 'OK'],
        ];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['successful']);
        $this->assertEquals(2, $stats['failed']);
    }

    /** @test */
    public function it_handles_empty_results_array_for_summary(): void
    {
        // Arrange
        $results = [];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['successful']);
        $this->assertEquals(0, $stats['failed']);
    }

    /** @test */
    public function it_returns_correct_structure_for_summary_stats(): void
    {
        // Arrange
        $results = [
            1 => ['success' => true, 'message' => 'OK'],
        ];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('successful', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertIsInt($stats['total']);
        $this->assertIsInt($stats['successful']);
        $this->assertIsInt($stats['failed']);
    }

    /** @test */
    public function it_validates_total_equals_sum_of_successful_and_failed(): void
    {
        // Arrange
        $results = [
            1 => ['success' => true],
            2 => ['success' => false],
            3 => ['success' => true],
            4 => ['success' => true],
            5 => ['success' => false],
            6 => ['success' => false],
        ];

        // Act
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals($stats['total'], $stats['successful'] + $stats['failed']);
        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['successful']);
        $this->assertEquals(3, $stats['failed']);
    }

    // ==========================================
    // INTEGRATION TESTS
    // ==========================================

    /** @test */
    public function it_can_ping_and_get_summary_stats(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer();
        $server2 = $this->createOnlineServer();
        $server3 = $this->createOfflineServer();
        $servers = collect([$server1, $server2, $server3]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->times(3)
            ->andReturn(
                ['reachable' => true, 'message' => 'Success', 'latency_ms' => 10.0],
                ['reachable' => true, 'message' => 'Success', 'latency_ms' => 15.0],
                ['reachable' => false, 'message' => 'Failed']
            );

        // Act
        $results = $this->service->pingServers($servers);
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['successful']);
        $this->assertEquals(1, $stats['failed']);
    }

    /** @test */
    public function it_can_install_docker_and_get_summary_stats(): void
    {
        // Arrange
        $server1 = $this->createServer();
        $server2 = $this->createServer();
        $servers = collect([$server1, $server2]);

        $this->dockerService
            ->shouldReceive('verifyDockerInstallation')
            ->twice()
            ->andReturn(['installed' => false]);

        $this->dockerService
            ->shouldReceive('installDocker')
            ->twice()
            ->andReturn(
                ['success' => true, 'message' => 'Installed', 'version' => '24.0.7'],
                ['success' => false, 'message' => 'Failed']
            );

        Log::shouldReceive('info')->once();

        // Act
        $results = $this->service->installDockerOnServers($servers);
        $stats = $this->service->getSummaryStats($results);

        // Assert
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['successful']);
        $this->assertEquals(1, $stats['failed']);
    }

    /** @test */
    public function it_processes_large_collection_of_servers_efficiently(): void
    {
        // Arrange
        $servers = collect();
        for ($i = 0; $i < 50; $i++) {
            $servers->push($this->createOnlineServer(['name' => "Server $i"]));
        }

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->times(50)
            ->andReturn(['reachable' => true, 'message' => 'Success', 'latency_ms' => 10.0]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertCount(50, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }
    }

    /** @test */
    public function it_preserves_server_id_as_array_key_in_results(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer();
        $server2 = $this->createOnlineServer();
        $servers = collect([$server1, $server2]);

        $this->connectivityService
            ->shouldReceive('testConnection')
            ->twice()
            ->andReturn(['reachable' => true, 'message' => 'Success', 'latency_ms' => 10.0]);

        // Act
        $results = $this->service->pingServers($servers);

        // Assert
        $this->assertArrayHasKey($server1->id, $results);
        $this->assertArrayHasKey($server2->id, $results);
        $this->assertEquals($server1->name, $results[$server1->id]['server_name']);
        $this->assertEquals($server2->name, $results[$server2->id]['server_name']);
    }
}
