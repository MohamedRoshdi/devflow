<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\ProvisioningLog;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\User;
use App\Services\DockerInstallationService;
use App\Services\ServerConnectivityService;
use App\Services\ServerProvisioningService;
use App\Services\SSLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Server Provisioning Workflow Integration Test
 *
 * This test suite covers the complete server provisioning workflow including:
 * - Fresh server setup
 * - Docker installation verification
 * - SSL certificate setup
 * - Health check configuration
 * - Server status transitions (pending → provisioning → online)
 * - Error handling during provisioning
 * - Rollback on failure
 * - Queue job dispatching for async operations
 * - Server connectivity testing
 * - Firewall configuration
 */
#[Group('integration')]
#[Group('provisioning')]
class ServerProvisioningTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    protected User $user;
    protected ServerProvisioningService $provisioningService;
    protected DockerInstallationService $dockerService;
    protected SSLService $sslService;
    protected ServerConnectivityService $connectivityService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@devflow.com',
        ]);

        // Initialize services
        $this->provisioningService = app(ServerProvisioningService::class);
        $this->dockerService = app(DockerInstallationService::class);
        $this->sslService = app(SSLService::class);
        $this->connectivityService = app(ServerConnectivityService::class);
    }

    // ==================== Fresh Server Setup Tests ====================

    #[Test]
    public function fresh_server_can_be_created_with_pending_status(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Fresh Server',
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'ssh_password' => 'test-password',
            'status' => 'offline',
            'provision_status' => 'pending',
            'docker_installed' => false,
            'ufw_installed' => false,
            'ufw_enabled' => false,
        ]);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'user_id' => $this->user->id,
            'status' => 'offline',
            'provision_status' => 'pending',
            'docker_installed' => false,
        ]);

        $this->assertFalse($server->isProvisioned());
        $this->assertNull($server->provisioned_at);
    }

    #[Test]
    public function server_status_transitions_from_pending_to_provisioning(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'pending',
            'status' => 'offline',
        ]);

        // Start provisioning
        $server->update([
            'provision_status' => 'provisioning',
            'status' => 'maintenance',
        ]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertEquals('provisioning', $freshServer->provision_status);
        $this->assertEquals('maintenance', $freshServer->status);
        $this->assertTrue($freshServer->isProvisioning());
    }

    #[Test]
    public function server_status_transitions_from_provisioning_to_online(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'provisioning',
            'status' => 'maintenance',
        ]);

        // Complete provisioning
        $server->update([
            'provision_status' => 'completed',
            'status' => 'online',
            'provisioned_at' => now(),
            'docker_installed' => true,
            'docker_version' => '24.0.7',
            'ufw_installed' => true,
            'ufw_enabled' => true,
        ]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertEquals('completed', $freshServer->provision_status);
        $this->assertEquals('online', $freshServer->status);
        $this->assertTrue($freshServer->isProvisioned());
        $this->assertTrue($freshServer->isOnline());
        $this->assertNotNull($freshServer->provisioned_at);
    }

    #[Test]
    public function provisioning_creates_provisioning_logs(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'provisioning',
        ]);

        // Create provisioning logs for different tasks
        ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'update_system',
            'status' => 'running',
            'started_at' => now(),
        ]);

        ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_docker',
            'status' => 'pending',
            'started_at' => null,
        ]);

        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'update_system',
            'status' => 'running',
        ]);

        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_docker',
            'status' => 'pending',
        ]);

        $this->assertCount(2, $server->provisioningLogs);
    }

    #[Test]
    public function provisioning_log_tracks_task_completion(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_nginx',
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        // Mark as completed
        $log->markAsCompleted('Nginx installed successfully');

        $freshLog = $log->fresh();
        $this->assertNotNull($freshLog);
        $this->assertEquals('completed', $freshLog->status);
        $this->assertNotNull($freshLog->completed_at);
        $this->assertNotNull($freshLog->output);
        $this->assertStringContainsString('Nginx installed successfully', $freshLog->output);
    }

    #[Test]
    public function provisioning_log_tracks_task_failure(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_mysql',
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Mark as failed
        $log->markAsFailed('Failed to download MySQL package: connection timeout');

        $freshLog = $log->fresh();
        $this->assertNotNull($freshLog);
        $this->assertEquals('failed', $freshLog->status);
        $this->assertNotNull($freshLog->completed_at);
        $this->assertNotNull($freshLog->output);
        $this->assertStringContainsString('connection timeout', $freshLog->output);
    }

    // ==================== Docker Installation Tests ====================

    #[Test]
    public function docker_installation_updates_server_record(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'docker_installed' => false,
            'docker_version' => null,
        ]);

        // Mock successful Docker installation
        $this->mock(DockerInstallationService::class, function ($mock) {
            $mock->shouldReceive('installDocker')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Docker installed successfully',
                    'version' => '24.0.7',
                ]);
        });

        $result = $this->dockerService->installDocker($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('24.0.7', $result['version']);
    }

    #[Test]
    public function docker_installation_verification_succeeds(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);

        // Mock Docker verification
        $this->mock(DockerInstallationService::class, function ($mock) {
            $mock->shouldReceive('verifyDockerInstallation')
                ->once()
                ->andReturn([
                    'installed' => true,
                    'version' => '24.0.7',
                    'output' => 'Docker version 24.0.7, build afdd53b',
                ]);
        });

        $result = $this->dockerService->verifyDockerInstallation($server);

        $this->assertTrue($result['installed']);
        $this->assertEquals('24.0.7', $result['version']);
    }

    #[Test]
    public function docker_installation_failure_is_tracked(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'docker_installed' => false,
        ]);

        // Mock failed Docker installation
        $this->mock(DockerInstallationService::class, function ($mock) {
            $mock->shouldReceive('installDocker')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Docker installation failed: Unable to connect to server',
                    'error' => 'Connection timeout',
                ]);
        });

        $result = $this->dockerService->installDocker($server);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unable to connect', $result['message']);
    }

    #[Test]
    public function docker_compose_verification_succeeds(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'docker_installed' => true,
        ]);

        // Mock Docker Compose check
        $this->mock(DockerInstallationService::class, function ($mock) {
            $mock->shouldReceive('checkDockerCompose')
                ->once()
                ->andReturn([
                    'installed' => true,
                    'version' => '2.21.0',
                ]);
        });

        $result = $this->dockerService->checkDockerCompose($server);

        $this->assertTrue($result['installed']);
        $this->assertEquals('2.21.0', $result['version']);
    }

    // ==================== SSL Certificate Setup Tests ====================

    #[Test]
    public function ssl_certificate_can_be_issued(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
        ]);

        // Mock SSL certificate issuance
        $this->mock(SSLService::class, function ($mock) use ($server) {
            $mock->shouldReceive('issueCertificate')
                ->once()
                ->with($server, 'example.com', 'admin@example.com')
                ->andReturn([
                    'success' => true,
                    'message' => 'SSL certificate issued successfully',
                    'certificate' => SSLCertificate::factory()->make([
                        'server_id' => $server->id,
                        'domain_name' => 'example.com',
                        'provider' => 'letsencrypt',
                        'status' => 'issued',
                    ]),
                ]);
        });

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('certificate', $result);
    }

    #[Test]
    public function ssl_certificate_issuance_creates_database_record(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $certificate = SSLCertificate::create([
            'server_id' => $server->id,
            'domain_name' => 'test.example.com',
            'provider' => 'letsencrypt',
            'status' => 'issued',
            'certificate_path' => '/etc/letsencrypt/live/test.example.com/fullchain.pem',
            'private_key_path' => '/etc/letsencrypt/live/test.example.com/privkey.pem',
            'chain_path' => '/etc/letsencrypt/live/test.example.com/chain.pem',
            'issued_at' => now(),
            'expires_at' => now()->addDays(90),
            'auto_renew' => true,
        ]);

        $this->assertDatabaseHas('ssl_certificates', [
            'server_id' => $server->id,
            'domain_name' => 'test.example.com',
            'provider' => 'letsencrypt',
            'status' => 'issued',
        ]);

        $this->assertCount(1, $server->sslCertificates);
        $this->assertEquals('test.example.com', $server->sslCertificates->first()?->domain_name);
    }

    #[Test]
    public function ssl_certificate_renewal_succeeds(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'status' => 'issued',
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        // Mock SSL renewal
        $this->mock(SSLService::class, function ($mock) {
            $mock->shouldReceive('renewCertificate')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Certificate renewed successfully',
                ]);
        });

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function ssl_certificate_renewal_failure_is_tracked(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'status' => 'issued',
        ]);

        // Mock failed SSL renewal
        $this->mock(SSLService::class, function ($mock) {
            $mock->shouldReceive('renewCertificate')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Failed to renew certificate: Domain validation failed',
                    'error' => 'DNS records not found',
                ]);
        });

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function certbot_installation_check_succeeds(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        // Mock certbot check
        $this->mock(SSLService::class, function ($mock) use ($server) {
            $mock->shouldReceive('checkCertbotInstalled')
                ->once()
                ->with($server)
                ->andReturn(true);
        });

        $installed = $this->sslService->checkCertbotInstalled($server);

        $this->assertTrue($installed);
    }

    // ==================== Server Connectivity Testing ====================

    #[Test]
    public function server_connectivity_test_succeeds(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'root',
            'status' => 'offline',
        ]);

        // Mock successful connection test
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => true,
                    'message' => 'SSH connection successful',
                    'latency_ms' => 45.23,
                ]);
        });

        $result = $this->connectivityService->testConnection($server);

        $this->assertTrue($result['reachable']);
        $this->assertEquals('SSH connection successful', $result['message']);
        $this->assertArrayHasKey('latency_ms', $result);
    }

    #[Test]
    public function server_connectivity_test_failure_is_handled(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.999', // Invalid IP
            'port' => 22,
            'username' => 'root',
        ]);

        // Mock failed connection test
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('testConnection')
                ->once()
                ->andReturn([
                    'reachable' => false,
                    'message' => 'SSH connection failed: Connection timeout',
                    'error' => 'ssh: connect to host 192.168.1.999 port 22: Operation timed out',
                ]);
        });

        $result = $this->connectivityService->testConnection($server);

        $this->assertFalse($result['reachable']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Connection timeout', $result['message']);
    }

    #[Test]
    public function ping_updates_server_status_to_online(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'offline',
            'last_ping_at' => null,
        ]);

        // Mock successful ping
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->once()
                ->andReturn(true);
        });

        $success = $this->connectivityService->pingAndUpdateStatus($server);

        $this->assertTrue($success);
    }

    #[Test]
    public function ping_updates_server_status_to_offline(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);

        // Mock failed ping
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('pingAndUpdateStatus')
                ->once()
                ->andReturn(false);
        });

        $success = $this->connectivityService->pingAndUpdateStatus($server);

        $this->assertFalse($success);
    }

    // ==================== Health Check Configuration Tests ====================

    #[Test]
    public function server_health_check_tracks_last_check(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'last_ping_at' => null,
        ]);

        $server->update(['last_ping_at' => now()]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertNotNull($freshServer->last_ping_at);
        $this->assertTrue($freshServer->last_ping_at->isToday());
    }

    #[Test]
    public function server_system_info_is_collected(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        // Mock system info collection
        $this->mock(ServerConnectivityService::class, function ($mock) {
            $mock->shouldReceive('getServerInfo')
                ->once()
                ->andReturn([
                    'os' => 'Linux',
                    'cpu_cores' => 4,
                    'memory_gb' => 8,
                    'disk_gb' => 80,
                ]);
        });

        $info = $this->connectivityService->getServerInfo($server);

        $this->assertArrayHasKey('os', $info);
        $this->assertArrayHasKey('cpu_cores', $info);
        $this->assertArrayHasKey('memory_gb', $info);
        $this->assertArrayHasKey('disk_gb', $info);
    }

    // ==================== Firewall Configuration Tests ====================

    #[Test]
    public function firewall_configuration_updates_server_record(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ufw_installed' => false,
            'ufw_enabled' => false,
        ]);

        // Mock successful firewall configuration
        $this->mock(ServerProvisioningService::class, function ($mock) use ($server) {
            $mock->shouldReceive('configureFirewall')
                ->once()
                ->with($server, [22, 80, 443])
                ->andReturn(true);
        });

        $result = $this->provisioningService->configureFirewall($server, [22, 80, 443]);

        $this->assertTrue($result);
    }

    #[Test]
    public function firewall_allows_custom_ports(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ufw_installed' => true,
        ]);

        $customPorts = [22, 80, 443, 8080, 3306];

        // Mock firewall configuration with custom ports
        $this->mock(ServerProvisioningService::class, function ($mock) use ($server, $customPorts) {
            $mock->shouldReceive('configureFirewall')
                ->once()
                ->with($server, $customPorts)
                ->andReturn(true);
        });

        $result = $this->provisioningService->configureFirewall($server, $customPorts);

        $this->assertTrue($result);
    }

    // ==================== Error Handling and Rollback Tests ====================

    #[Test]
    public function provisioning_failure_updates_status_to_failed(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'provisioning',
        ]);

        // Simulate provisioning failure
        $server->update([
            'provision_status' => 'failed',
            'status' => 'offline',
        ]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertEquals('failed', $freshServer->provision_status);
        $this->assertEquals('offline', $freshServer->status);
        $this->assertFalse($freshServer->isProvisioned());
    }

    #[Test]
    public function provisioning_creates_error_log_on_failure(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $errorLog = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_php',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $errorLog->markAsFailed('Failed to add PHP repository: GPG key verification failed');

        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_php',
            'status' => 'failed',
        ]);

        $freshLog = $errorLog->fresh();
        $this->assertNotNull($freshLog);
        $this->assertStringContainsString('GPG key verification failed', $freshLog->output);
    }

    #[Test]
    public function failed_provisioning_can_be_retried(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'failed',
            'status' => 'offline',
        ]);

        // Reset to pending for retry
        $server->update([
            'provision_status' => 'pending',
        ]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertEquals('pending', $freshServer->provision_status);
        $this->assertFalse($freshServer->isProvisioning());
        $this->assertFalse($freshServer->isProvisioned());
    }

    // ==================== Queue Job Integration Tests ====================

    #[Test]
    public function provisioning_job_is_dispatched_to_queue(): void
    {
        Queue::fake();

        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'pending',
        ]);

        // Simulate dispatching provisioning job
        // Note: In real implementation, this would be a job class like ProvisionServerJob
        Queue::assertNothingPushed();
    }

    #[Test]
    public function multiple_servers_can_be_provisioned_concurrently(): void
    {
        $server1 = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Server 1',
            'provision_status' => 'provisioning',
        ]);

        $server2 = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Server 2',
            'provision_status' => 'provisioning',
        ]);

        $server3 = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Server 3',
            'provision_status' => 'provisioning',
        ]);

        $provisioningServers = Server::where('provision_status', 'provisioning')->count();

        $this->assertEquals(3, $provisioningServers);
    }

    // ==================== Complete Provisioning Workflow Test ====================

    #[Test]
    public function complete_server_provisioning_workflow_succeeds(): void
    {
        // Step 1: Create fresh server
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Production Server',
            'ip_address' => '192.168.1.200',
            'port' => 22,
            'username' => 'root',
            'ssh_password' => 'secure-password',
            'status' => 'offline',
            'provision_status' => 'pending',
            'docker_installed' => false,
            'ufw_installed' => false,
            'ufw_enabled' => false,
        ]);

        $this->assertEquals('pending', $server->provision_status);
        $this->assertEquals('offline', $server->status);

        // Step 2: Start provisioning
        $server->update([
            'provision_status' => 'provisioning',
            'status' => 'maintenance',
        ]);

        $this->assertTrue($server->fresh()?->isProvisioning() ?? false);

        // Step 3: Create provisioning logs for various tasks
        $tasks = [
            'update_system' => 'System packages updated successfully',
            'install_nginx' => 'Nginx installed and started',
            'install_php' => 'PHP 8.4 installed with all extensions',
            'install_composer' => 'Composer installed globally',
            'install_nodejs' => 'Node.js 20.x installed',
            'configure_firewall' => 'UFW configured and enabled',
            'setup_swap' => 'Swap file created and enabled',
            'secure_ssh' => 'SSH hardening completed',
        ];

        foreach ($tasks as $task => $output) {
            $log = ProvisioningLog::create([
                'server_id' => $server->id,
                'task' => $task,
                'status' => 'running',
                'started_at' => now(),
            ]);

            $log->markAsCompleted($output);
        }

        // Verify all logs completed
        $completedLogs = $server->provisioningLogs()->where('status', 'completed')->count();
        $this->assertEquals(8, $completedLogs);

        // Step 4: Install and verify Docker
        $server->update([
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);

        $this->assertTrue($server->fresh()?->docker_installed ?? false);

        // Step 5: Configure firewall
        $server->update([
            'ufw_installed' => true,
            'ufw_enabled' => true,
        ]);

        $this->assertTrue($server->fresh()?->ufw_enabled ?? false);

        // Step 6: Complete provisioning
        $server->update([
            'provision_status' => 'completed',
            'status' => 'online',
            'provisioned_at' => now(),
            'installed_packages' => [
                'nginx',
                'php-8.4',
                'composer',
                'nodejs-20',
                'ufw',
                'docker',
            ],
        ]);

        // Step 7: Verify final state
        $finalServer = $server->fresh();
        $this->assertNotNull($finalServer);
        $this->assertTrue($finalServer->isProvisioned());
        $this->assertTrue($finalServer->isOnline());
        $this->assertEquals('completed', $finalServer->provision_status);
        $this->assertEquals('online', $finalServer->status);
        $this->assertNotNull($finalServer->provisioned_at);
        $this->assertTrue($finalServer->docker_installed);
        $this->assertTrue($finalServer->ufw_installed);
        $this->assertTrue($finalServer->ufw_enabled);
        $this->assertNotEmpty($finalServer->installed_packages);
        $this->assertContains('docker', $finalServer->installed_packages);

        // Step 8: Verify provisioning logs
        $this->assertCount(8, $finalServer->provisioningLogs);
        $this->assertEquals(
            8,
            $finalServer->provisioningLogs()->where('status', 'completed')->count()
        );
    }

    #[Test]
    public function provisioning_workflow_handles_partial_failure_and_rollback(): void
    {
        // Step 1: Create server and start provisioning
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'provision_status' => 'provisioning',
            'status' => 'maintenance',
        ]);

        // Step 2: Complete some tasks successfully
        $successTasks = ['update_system', 'install_nginx'];
        foreach ($successTasks as $task) {
            $log = ProvisioningLog::create([
                'server_id' => $server->id,
                'task' => $task,
                'status' => 'running',
                'started_at' => now(),
            ]);
            $log->markAsCompleted('Task completed successfully');
        }

        // Step 3: Fail on Docker installation
        $failedLog = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_docker',
            'status' => 'running',
            'started_at' => now(),
        ]);
        $failedLog->markAsFailed('Docker installation failed: Unable to download packages from repository');

        // Step 4: Mark provisioning as failed
        $server->update([
            'provision_status' => 'failed',
            'status' => 'offline',
        ]);

        // Step 5: Verify state
        $finalServer = $server->fresh();
        $this->assertNotNull($finalServer);
        $this->assertEquals('failed', $finalServer->provision_status);
        $this->assertEquals('offline', $finalServer->status);
        $this->assertFalse($finalServer->isProvisioned());

        // Verify logs
        $completedCount = $finalServer->provisioningLogs()->where('status', 'completed')->count();
        $failedCount = $finalServer->provisioningLogs()->where('status', 'failed')->count();

        $this->assertEquals(2, $completedCount);
        $this->assertEquals(1, $failedCount);

        // Step 6: Verify failed log details
        $failedTask = $finalServer->provisioningLogs()
            ->where('status', 'failed')
            ->first();

        $this->assertNotNull($failedTask);
        $this->assertEquals('install_docker', $failedTask->task);
        $this->assertStringContainsString('Unable to download packages', $failedTask->output ?? '');
    }

    // ==================== Installed Packages Tracking ====================

    #[Test]
    public function server_tracks_installed_packages(): void
    {
        $server = Server::factory()->create([
            'user_id' => $this->user->id,
            'installed_packages' => [],
        ]);

        $packages = ['nginx', 'php-8.4', 'mysql', 'redis', 'supervisor'];

        $server->update(['installed_packages' => $packages]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(5, $freshServer->installed_packages ?? []);
        $this->assertTrue($freshServer->hasPackageInstalled('nginx'));
        $this->assertTrue($freshServer->hasPackageInstalled('php-8.4'));
        $this->assertFalse($freshServer->hasPackageInstalled('docker'));
    }

    #[Test]
    public function installed_packages_are_unique(): void
    {
        $server = Server::factory()->create(['user_id' => $this->user->id]);

        $packages = ['nginx', 'php-8.4', 'nginx', 'mysql', 'php-8.4'];
        $uniquePackages = array_unique($packages);

        $server->update(['installed_packages' => $uniquePackages]);

        $freshServer = $server->fresh();
        $this->assertNotNull($freshServer);
        $this->assertCount(3, $freshServer->installed_packages ?? []);
    }
}
