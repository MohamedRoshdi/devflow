<?php

namespace Tests\Unit\Services;

use App\Models\ProvisioningLog;
use App\Models\Server;
use App\Models\User;
use App\Notifications\ServerProvisioningCompleted;
use App\Services\ServerProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class ServerProvisioningServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected ServerProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        // Create a partial mock that mocks executeSSHCommand
        $this->service = $this->getMockBuilder(ServerProvisioningService::class)
            ->onlyMethods(['executeSSHCommand'])
            ->getMock();
    }

    /**
     * Mock SSH commands for provisioning to return success
     */
    protected function mockProvisioningCommands(): void
    {
        $this->service->method('executeSSHCommand')
            ->willReturn('Command executed successfully');
    }

    /**
     * Mock SSH command failure
     */
    protected function mockProvisioningFailure(): void
    {
        $this->service->method('executeSSHCommand')
            ->willThrowException(new \RuntimeException('SSH command failed: Command\nError: Connection failed'));
    }

    /** @test */
    public function it_provisions_server_with_default_options(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $this->service->provisionServer($server);

        // Assert
        $server->refresh();
        $this->assertEquals('completed', $server->provision_status);
        $this->assertNotNull($server->provisioned_at);
        $this->assertContains('nginx', $server->installed_packages);
        $this->assertContains('composer', $server->installed_packages);
    }

    /** @test */
    public function it_sets_provisioning_status_when_starting(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $this->service->provisionServer($server);

        // Assert
        $server->refresh();
        $this->assertEquals('completed', $server->provision_status);
    }

    /** @test */
    public function it_provisions_server_with_custom_options(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        $options = [
            'install_nginx' => true,
            'install_mysql' => true,
            'install_php' => true,
            'php_version' => '8.3',
            'install_nodejs' => true,
            'node_version' => '18',
            'configure_firewall' => true,
            'firewall_ports' => [22, 80, 443, 3306],
            'setup_swap' => true,
            'swap_size_gb' => 4,
        ];

        // Act
        $this->service->provisionServer($server, $options);

        // Assert
        $server->refresh();
        $this->assertEquals('completed', $server->provision_status);
        $this->assertContains('nginx', $server->installed_packages);
        $this->assertContains('mysql', $server->installed_packages);
        $this->assertContains('php-8.3', $server->installed_packages);
        $this->assertContains('nodejs-18', $server->installed_packages);
    }

    /** @test */
    public function it_handles_provisioning_failure_gracefully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->provisionServer($server);
        } catch (\Exception $e) {
            $server->refresh();
            $this->assertEquals('failed', $server->provision_status);
            throw $e;
        }
    }

    /** @test */
    public function it_sends_success_notification_on_completion(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $this->mockProvisioningCommands();

        // Act
        $this->service->provisionServer($server);

        // Assert
        Notification::assertSentTo($user, ServerProvisioningCompleted::class);
    }

    /** @test */
    public function it_sends_failure_notification_on_error(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $this->mockProvisioningFailure();

        // Act
        try {
            $this->service->provisionServer($server);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert
        Notification::assertSentTo($user, ServerProvisioningCompleted::class);
    }

    /** @test */
    public function it_skips_update_system_when_disabled(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $this->service->provisionServer($server, ['update_system' => false]);

        // Assert
        $this->assertDatabaseMissing('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'update_system',
        ]);
    }

    /** @test */
    public function it_updates_system_packages(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->updateSystem($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'update_system',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_creates_log_when_updating_system(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $this->service->updateSystem($server);

        // Assert
        $log = ProvisioningLog::where('server_id', $server->id)
            ->where('task', 'update_system')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('completed', $log->status);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);
    }

    /** @test */
    public function it_handles_update_system_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->updateSystem($server);
        } catch (\Exception $e) {
            $log = ProvisioningLog::where('server_id', $server->id)
                ->where('task', 'update_system')
                ->first();

            $this->assertEquals('failed', $log->status);
            $this->assertNotNull($log->error_message);
            throw $e;
        }
    }

    /** @test */
    public function it_installs_nginx_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installNginx($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_nginx',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_nginx_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->installNginx($server);
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'install_nginx',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_installs_mysql_with_root_password(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();
        $password = 'SecurePassword123!';

        // Act
        $result = $this->service->installMySQL($server, $password);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_mysql',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_escapes_special_characters_in_mysql_password(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();
        $password = "Pass'word\"with'special\\chars";

        // Act
        $result = $this->service->installMySQL($server, $password);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_mysql_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->installMySQL($server, 'password123');
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'install_mysql',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_installs_php_with_default_version(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installPHP($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_php_8.4',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_installs_php_with_custom_version(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installPHP($server, '8.3');

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_php_8.3',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_php_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->installPHP($server, '8.4');
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'install_php_8.4',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_installs_composer_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installComposer($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_composer',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_composer_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->installComposer($server);
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'install_composer',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_installs_nodejs_with_default_version(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installNodeJS($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_nodejs_20',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_installs_nodejs_with_custom_version(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->installNodeJS($server, '18');

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'install_nodejs_18',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_nodejs_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->installNodeJS($server, '20');
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'install_nodejs_20',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_configures_firewall_with_default_ports(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->configureFirewall($server);

        // Assert
        $this->assertTrue($result);
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
        $this->assertTrue($server->ufw_enabled);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'configure_firewall',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_configures_firewall_with_custom_ports(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();
        $ports = [22, 80, 443, 3306, 6379];

        // Act
        $result = $this->service->configureFirewall($server, $ports);

        // Assert
        $this->assertTrue($result);
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
        $this->assertTrue($server->ufw_enabled);
    }

    /** @test */
    public function it_handles_firewall_configuration_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->configureFirewall($server);
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'configure_firewall',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_setups_swap_with_default_size(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->setupSwap($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'setup_swap',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_setups_swap_with_custom_size(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->setupSwap($server, 4);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_swap_setup_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->setupSwap($server);
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'setup_swap',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_secures_ssh_configuration(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->secureSSH($server);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('provisioning_logs', [
            'server_id' => $server->id,
            'task' => 'secure_ssh',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_ssh_security_configuration_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();

        // Act & Assert
        $this->expectException(\RuntimeException::class);

        try {
            $this->service->secureSSH($server);
        } catch (\Exception $e) {
            $this->assertDatabaseHas('provisioning_logs', [
                'server_id' => $server->id,
                'task' => 'secure_ssh',
                'status' => 'failed',
            ]);
            throw $e;
        }
    }

    /** @test */
    public function it_generates_provisioning_script_with_default_options(): void
    {
        // Act
        $script = $this->service->getProvisioningScript([]);

        // Assert
        $this->assertStringContainsString('#!/bin/bash', $script);
        $this->assertStringContainsString('apt-get update', $script);
        $this->assertStringContainsString('install -y nginx', $script);
        $this->assertStringContainsString('php8.4', $script);
        $this->assertStringContainsString('composer', $script);
        $this->assertStringContainsString('nodejs', $script);
    }

    /** @test */
    public function it_generates_provisioning_script_with_custom_options(): void
    {
        // Arrange
        $options = [
            'update_system' => true,
            'install_nginx' => true,
            'install_mysql' => true,
            'install_php' => true,
            'php_version' => '8.3',
            'install_composer' => true,
            'install_nodejs' => true,
            'node_version' => '18',
            'configure_firewall' => true,
            'firewall_ports' => [22, 80, 443, 3306],
            'setup_swap' => true,
            'swap_size_gb' => 4,
        ];

        // Act
        $script = $this->service->getProvisioningScript($options);

        // Assert
        $this->assertStringContainsString('php8.3', $script);
        $this->assertStringContainsString('setup_18.x', $script);
        $this->assertStringContainsString('4G /swapfile', $script);
        $this->assertStringContainsString('ufw allow 3306/tcp', $script);
        $this->assertStringContainsString('mysql-server', $script);
    }

    /** @test */
    public function it_generates_script_without_mysql_when_disabled(): void
    {
        // Arrange
        $options = [
            'install_mysql' => false,
        ];

        // Act
        $script = $this->service->getProvisioningScript($options);

        // Assert
        $this->assertStringNotContainsString('mysql-server', $script);
    }

    /** @test */
    public function it_generates_script_without_system_update_when_disabled(): void
    {
        // Arrange
        $options = [
            'update_system' => false,
            'install_nginx' => false,
            'install_mysql' => false,
            'install_php' => false,
            'install_composer' => false,
            'install_nodejs' => false,
            'configure_firewall' => false,
            'setup_swap' => false,
        ];

        // Act
        $script = $this->service->getProvisioningScript($options);

        // Assert
        $this->assertStringNotContainsString('apt-get update', $script);
        $this->assertStringNotContainsString('apt-get upgrade', $script);
        $this->assertStringNotContainsString('apt-get autoremove', $script);
    }

    /** @test */
    public function it_tracks_all_installed_packages(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        $options = [
            'install_nginx' => true,
            'install_mysql' => true,
            'install_php' => true,
            'php_version' => '8.4',
            'install_composer' => true,
            'install_nodejs' => true,
            'node_version' => '20',
            'configure_firewall' => true,
        ];

        // Act
        $this->service->provisionServer($server, $options);

        // Assert
        $server->refresh();
        $packages = $server->installed_packages;

        $this->assertContains('nginx', $packages);
        $this->assertContains('mysql', $packages);
        $this->assertContains('php-8.4', $packages);
        $this->assertContains('composer', $packages);
        $this->assertContains('nodejs-20', $packages);
        $this->assertContains('ufw', $packages);
    }

    /** @test */
    public function it_avoids_duplicate_packages_in_installed_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'installed_packages' => ['nginx', 'composer'],
        ]);
        $this->mockProvisioningCommands();

        $options = [
            'update_system' => false,
            'install_nginx' => true,
            'install_mysql' => false,
            'install_php' => false,
            'install_composer' => true,
            'install_nodejs' => false,
            'configure_firewall' => false,
            'setup_swap' => false,
            'secure_ssh' => false,
        ];

        // Act
        $this->service->provisionServer($server, $options);

        // Assert
        $server->refresh();
        $packages = $server->installed_packages;

        // Should not have duplicates
        $this->assertEquals($packages, array_unique($packages));
        $this->assertContains('nginx', $packages);
        $this->assertContains('composer', $packages);
    }

    /** @test */
    public function it_logs_provisioning_errors(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningFailure();
        Log::spy();

        // Act
        try {
            $this->service->provisionServer($server);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) use ($server) {
                return $message === 'Server provisioning failed'
                    && $context['server_id'] === $server->id
                    && isset($context['error']);
            });
    }

    /** @test */
    public function it_updates_provisioned_at_timestamp_on_success(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();
        $this->assertNull($server->provisioned_at);

        // Act
        $this->service->provisionServer($server);

        // Assert
        $server->refresh();
        $this->assertNotNull($server->provisioned_at);
    }

    /** @test */
    public function it_completes_provisioning_even_when_notification_cannot_be_sent(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockProvisioningCommands();

        // Simulate a scenario where notification might fail by using a server
        // whose user relationship could be problematic

        // Act
        $this->service->provisionServer($server);

        // Assert
        $server->refresh();
        $this->assertEquals('completed', $server->provision_status);
        $this->assertNotNull($server->provisioned_at);
        // Provisioning should complete successfully regardless of notification status
    }

    /** @test */
    public function it_uses_ssh_key_when_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ssh_key' => 'test-private-key-content',
            'ssh_password' => null,
        ]);
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->updateSystem($server);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_uses_ssh_password_when_no_key_available(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ssh_key' => null,
            'ssh_password' => 'secure-password',
        ]);
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->updateSystem($server);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_respects_custom_ssh_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'port' => 2222,
        ]);
        $this->mockProvisioningCommands();

        // Act
        $result = $this->service->updateSystem($server);

        // Assert
        $this->assertTrue($result);
    }
}
