<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class ServerConnectivityServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected ServerConnectivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServerConnectivityService;
    }

    // ==========================================
    // SSH CONNECTION TESTING
    // ==========================================

    #[Test]
    public function it_tests_successful_ssh_connection(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
        ]);

        Process::fake([
            '*ssh*echo "CONNECTION_TEST"*' => Process::result(
                output: 'CONNECTION_TEST'
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
        $this->assertEquals('SSH connection successful', $result['message']);
        $this->assertArrayHasKey('latency_ms', $result);
        $this->assertGreaterThanOrEqual(0, $result['latency_ms']);
    }

    #[Test]
    public function it_tests_failed_ssh_connection(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Connection refused',
                exitCode: 255
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
        $this->assertStringContainsString('SSH connection failed', $result['message']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_tests_connection_with_timeout(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Connection timed out',
                exitCode: 255
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
        $this->assertStringContainsString('timed out', $result['error']);
    }

    #[Test]
    public function it_handles_connection_test_exception(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Server connectivity test failed', \Mockery::type('array'));

        Process::fake([
            '*ssh*' => function () {
                throw new \Exception('Unexpected error');
            },
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
        $this->assertStringContainsString('Unexpected error', $result['message']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_measures_connection_latency(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*ssh*echo "CONNECTION_TEST"*' => Process::result(
                output: 'CONNECTION_TEST'
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
        $this->assertIsNumeric($result['latency_ms']);
        $this->assertGreaterThanOrEqual(0, $result['latency_ms']);
    }

    // ==========================================
    // PASSWORD AUTHENTICATION TESTS
    // ==========================================

    #[Test]
    public function it_connects_with_password_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ip_address' => '192.168.1.100',
            'ssh_password' => 'secure_password',
        ]);

        Process::fake([
            '*sshpass*' => Process::result(
                output: 'CONNECTION_TEST'
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
    }

    #[Test]
    public function it_handles_invalid_password(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ip_address' => '192.168.1.100',
            'ssh_password' => 'wrong_password',
        ]);

        Process::fake([
            '*sshpass*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 5
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
        $this->assertStringContainsString('Permission denied', $result['error']);
    }

    // ==========================================
    // SSH KEY AUTHENTICATION TESTS
    // ==========================================

    #[Test]
    public function it_connects_with_ssh_key_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithSshKey([
            'ip_address' => '192.168.1.100',
            'ssh_key' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
-----END RSA PRIVATE KEY-----',
        ]);

        Process::fake([
            '*ssh*-i*' => Process::result(
                output: 'CONNECTION_TEST'
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
    }

    #[Test]
    public function it_handles_invalid_ssh_key(): void
    {
        // Arrange
        $server = $this->createServerWithSshKey([
            'ip_address' => '192.168.1.100',
            'ssh_key' => 'invalid_key_content',
        ]);

        Process::fake([
            '*ssh*-i*' => Process::result(
                output: '',
                errorOutput: 'Load key: invalid format',
                exitCode: 255
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
        $this->assertStringContainsString('invalid format', $result['error']);
    }

    // ==========================================
    // LOCALHOST DETECTION TESTS
    // ==========================================

    #[Test]
    public function it_detects_localhost_by_127_0_0_1(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
        $this->assertEquals('Localhost connection available', $result['message']);
        $this->assertEquals(0, $result['latency_ms']);
    }

    #[Test]
    public function it_detects_localhost_by_ipv6(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '::1',
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
        $this->assertEquals('Localhost connection available', $result['message']);
        $this->assertEquals(0, $result['latency_ms']);
    }

    #[Test]
    public function it_detects_localhost_by_name(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => 'localhost',
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
        $this->assertEquals('Localhost connection available', $result['message']);
    }

    // ==========================================
    // SERVER STATUS UPDATE TESTS
    // ==========================================

    #[Test]
    public function it_updates_server_status_to_online_on_successful_ping(): void
    {
        // Arrange
        $server = $this->createOfflineServer([
            'ip_address' => '192.168.1.100',
            'status' => 'offline',
        ]);

        Process::fake([
            '*ssh*' => Process::result(output: 'CONNECTION_TEST'),
        ]);

        // Act
        $result = $this->service->pingAndUpdateStatus($server);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('online', $server->fresh()->status);
        $this->assertNotNull($server->fresh()->last_ping_at);
    }

    #[Test]
    public function it_updates_server_status_to_offline_on_failed_ping(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'status' => 'online',
        ]);

        Process::fake([
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Connection refused',
                exitCode: 255
            ),
        ]);

        // Act
        $result = $this->service->pingAndUpdateStatus($server);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('offline', $server->fresh()->status);
        $this->assertNotNull($server->fresh()->last_ping_at);
    }

    #[Test]
    public function it_updates_last_ping_timestamp(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
            'last_ping_at' => null,
        ]);

        // Act
        $this->service->pingAndUpdateStatus($server);

        // Assert
        $this->assertNotNull($server->fresh()->last_ping_at);
    }

    // ==========================================
    // SERVER INFO RETRIEVAL TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_server_info_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*uname -s*' => Process::result(output: 'Linux'),
            '*nproc*' => Process::result(output: '8'),
            '*free -m*' => Process::result(output: '16.0'),
            '*df -BG*' => Process::result(output: '500'),
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertArrayHasKey('os', $result);
        $this->assertArrayHasKey('cpu_cores', $result);
        $this->assertArrayHasKey('memory_gb', $result);
        $this->assertArrayHasKey('disk_gb', $result);
        $this->assertEquals('Linux', $result['os']);
        $this->assertEquals(8, $result['cpu_cores']);
    }

    #[Test]
    public function it_retrieves_local_server_info(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        Process::fake([
            '*nproc*' => Process::result(output: '4'),
            '*free -g*' => Process::result(output: '8'),
            '*df -BG*' => Process::result(output: '250'),
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertArrayHasKey('os', $result);
        $this->assertEquals(PHP_OS, $result['os']);
        $this->assertArrayHasKey('cpu_cores', $result);
        $this->assertArrayHasKey('memory_gb', $result);
    }

    #[Test]
    public function it_extracts_numeric_values_from_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*uname -s*' => Process::result(output: 'Linux'),
            '*nproc*' => Process::result(output: "Warning: some warning\n16"),
            '*free -m*' => Process::result(output: '32.5'),
            '*df -BG*' => Process::result(output: 'Filesystem: 1000'),
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertEquals(16, $result['cpu_cores']);
        $this->assertEquals(32.5, $result['memory_gb']);
        $this->assertEquals(1000, $result['disk_gb']);
    }

    #[Test]
    public function it_handles_server_info_retrieval_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to get server info', \Mockery::type('array'));

        Process::fake([
            '*uname*' => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertEmpty($result);
    }

    // ==========================================
    // SERVER REBOOT TESTS
    // ==========================================

    #[Test]
    public function it_reboots_server_as_root_user(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        Process::fake([
            '*ssh*reboot*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->rebootServer($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('reboot initiated', $result['message']);
        $this->assertEquals('maintenance', $server->fresh()->status);
    }

    #[Test]
    public function it_reboots_server_with_password_sudo(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ip_address' => '192.168.1.100',
            'username' => 'ubuntu',
            'ssh_password' => 'secure_password',
        ]);

        Process::fake([
            '*sudo -S*reboot*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->rebootServer($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('maintenance', $server->fresh()->status);
    }

    #[Test]
    public function it_reboots_server_with_passwordless_sudo(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'ubuntu',
            'ssh_password' => null,
        ]);

        Process::fake([
            '*sudo*reboot*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->rebootServer($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_reboot_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Server reboot failed', \Mockery::type('array'));

        Process::fake([
            '*reboot*' => function () {
                throw new \Exception('Reboot failed');
            },
        ]);

        // Act
        $result = $this->service->rebootServer($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Reboot failed', $result['message']);
    }

    // ==========================================
    // SERVICE RESTART TESTS
    // ==========================================

    #[Test]
    public function it_restarts_nginx_service(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl restart nginx*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->restartService($server, 'nginx');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('restarted successfully', $result['message']);
    }

    #[Test]
    public function it_restarts_docker_service(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl restart docker*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->restartService($server, 'docker');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_restarts_php_fpm_service(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl restart php8.4-fpm*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->restartService($server, 'php8.4-fpm');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_rejects_disallowed_service(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        // Act
        $result = $this->service->restartService($server, 'malicious-service');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Service not allowed', $result['message']);
    }

    #[Test]
    public function it_restarts_service_with_sudo_password(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ssh_password' => 'secure_password',
        ]);

        Process::fake([
            '*sudo -S*systemctl restart nginx*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->restartService($server, 'nginx');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_service_restart_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl restart nginx*' => Process::result(
                output: '',
                errorOutput: 'Service failed to start',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->restartService($server, 'nginx');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Service failed to start', $result['message']);
    }

    // ==========================================
    // UPTIME RETRIEVAL TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_server_uptime(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*uptime -p*' => Process::result(output: 'up 5 days, 3 hours, 22 minutes'),
        ]);

        // Act
        $result = $this->service->getUptime($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('up 5 days, 3 hours, 22 minutes', $result['uptime']);
    }

    #[Test]
    public function it_handles_uptime_retrieval_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*uptime -p*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->getUptime($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['uptime']);
    }

    // ==========================================
    // DISK USAGE TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_disk_usage(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*df -h*' => Process::result(output: '75%'),
        ]);

        // Act
        $result = $this->service->getDiskUsage($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('75%', $result['usage']);
    }

    #[Test]
    public function it_handles_disk_usage_retrieval_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*df -h*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->getDiskUsage($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['usage']);
    }

    // ==========================================
    // MEMORY USAGE TESTS
    // ==========================================

    #[Test]
    public function it_retrieves_memory_usage(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*free*' => Process::result(output: '68.5'),
        ]);

        // Act
        $result = $this->service->getMemoryUsage($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('68.5%', $result['usage']);
    }

    #[Test]
    public function it_handles_memory_usage_retrieval_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*free*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->getMemoryUsage($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['usage']);
    }

    // ==========================================
    // SYSTEM CACHE CLEARING TESTS
    // ==========================================

    #[Test]
    public function it_clears_system_cache_as_root(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*sync*drop_caches*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->clearSystemCache($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('cleared successfully', $result['message']);
    }

    #[Test]
    public function it_clears_system_cache_with_sudo(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ssh_password' => 'secure_password',
        ]);

        Process::fake([
            '*sync*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->clearSystemCache($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_cache_clearing_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->clearSystemCache($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to clear cache', $result['message']);
    }

    // ==========================================
    // PORT CONNECTIVITY TESTS
    // ==========================================

    #[Test]
    public function it_uses_custom_ssh_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'port' => 2222,
        ]);

        Process::fake([
            '*' => Process::result(output: 'CONNECTION_TEST'),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertTrue($result['reachable']);
    }

    #[Test]
    public function it_handles_connection_to_non_standard_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'port' => 22222,
        ]);

        Process::fake([
            '*-p 22222*' => Process::result(
                output: '',
                errorOutput: 'Connection refused',
                exitCode: 255
            ),
        ]);

        // Act
        $result = $this->service->testConnection($server);

        // Assert
        $this->assertFalse($result['reachable']);
    }

    // ==========================================
    // NUMERIC VALUE EXTRACTION TESTS
    // ==========================================

    #[Test]
    public function it_extracts_integer_from_mixed_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*' => function ($command) {
                if (str_contains($command, 'uname -s')) {
                    return Process::result(output: 'Linux');
                }
                if (str_contains($command, 'nproc')) {
                    return Process::result(output: "Warning: deprecated\n8\nSome other text");
                }
                if (str_contains($command, 'free -m')) {
                    return Process::result(output: '16.0');
                }
                if (str_contains($command, 'df -BG')) {
                    return Process::result(output: '500');
                }

                return Process::result(output: '');
            },
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertArrayHasKey('cpu_cores', $result);
        $this->assertEquals(8, $result['cpu_cores']);
        $this->assertIsInt($result['cpu_cores']);
    }

    #[Test]
    public function it_extracts_float_from_mixed_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*' => function ($command) {
                if (str_contains($command, 'uname -s')) {
                    return Process::result(output: 'Linux');
                }
                if (str_contains($command, 'nproc')) {
                    return Process::result(output: '8');
                }
                if (str_contains($command, 'free -m')) {
                    return Process::result(output: 'Total memory: 32.5 GB');
                }
                if (str_contains($command, 'df -BG')) {
                    return Process::result(output: '500');
                }

                return Process::result(output: '');
            },
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        $this->assertArrayHasKey('memory_gb', $result);
        $this->assertEquals(32.5, $result['memory_gb']);
        $this->assertIsFloat($result['memory_gb']);
    }

    #[Test]
    public function it_returns_empty_array_for_non_numeric_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
        ]);

        Process::fake([
            '*' => function ($command) {
                if (str_contains($command, 'uname -s')) {
                    return Process::result(output: 'Linux');
                }
                if (str_contains($command, 'nproc')) {
                    return Process::result(output: 'Not available');
                }
                if (str_contains($command, 'free -m')) {
                    return Process::result(output: 'Not available');
                }
                if (str_contains($command, 'df -BG')) {
                    return Process::result(output: 'Not available');
                }

                return Process::result(output: '');
            },
        ]);

        // Act
        $result = $this->service->getServerInfo($server);

        // Assert
        // When all numeric values fail to extract, only 'os' is added
        $this->assertArrayHasKey('os', $result);
        $this->assertArrayNotHasKey('cpu_cores', $result);
        $this->assertArrayNotHasKey('memory_gb', $result);
        $this->assertArrayNotHasKey('disk_gb', $result);
    }
}
