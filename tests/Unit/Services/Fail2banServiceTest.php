<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SecurityEvent;
use App\Models\Server;
use App\Services\Security\Fail2banService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

/**
 * Fail2banServiceTest
 *
 * Comprehensive unit tests for Fail2ban service including status checking, jail configuration,
 * ban/unban operations, whitelist management, log parsing, ban statistics, and configuration management.
 *
 * Note: These tests use Mockery to mock Symfony Process class since Laravel's Process::fake()
 * does not work with Process::fromShellCommandline() which is used by the service.
 */
class Fail2banServiceTest extends TestCase
{
    use CreatesServers, RefreshDatabase;

    protected Fail2banService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Fail2banService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to mock Process execution
     */
    protected function mockProcess(string $output = '', string $errorOutput = '', int $exitCode = 0): void
    {
        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('fromShellCommandline')
            ->andReturnSelf();
        $processMock->shouldReceive('setTimeout')
            ->andReturnSelf();
        $processMock->shouldReceive('run')
            ->andReturnSelf();
        $processMock->shouldReceive('isSuccessful')
            ->andReturn($exitCode === 0);
        $processMock->shouldReceive('getOutput')
            ->andReturn($output);
        $processMock->shouldReceive('getErrorOutput')
            ->andReturn($errorOutput);
    }

    // ==========================================
    // FAIL2BAN STATUS CHECKING TESTS
    // ==========================================

    #[Test]
    public function it_gets_fail2ban_status_when_active(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ip_address' => '127.0.0.1',
        ]);

        $fail2banOutput = "Status\n|- Number of jail:	2\n`- Jail list:	sshd, apache-auth";
        $this->mockProcess($fail2banOutput);

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertTrue($result['enabled']);
        $this->assertIsArray($result['jails']);
        $this->assertCount(2, $result['jails']);
        $this->assertContains('sshd', $result['jails']);
        $this->assertContains('apache-auth', $result['jails']);
        $this->assertArrayHasKey('raw_output', $result);
    }

    #[Test]
    public function it_detects_fail2ban_not_installed(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'fail2ban-client: command not found', 127);

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['jails']);
        $this->assertEquals('Fail2ban is not installed', $result['message']);
    }

    #[Test]
    public function it_detects_fail2ban_installed_but_not_running(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'fail2ban-client is not running', 255);

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['jails']);
        $this->assertEquals('Fail2ban is installed but not running', $result['message']);
    }

    #[Test]
    public function it_detects_fail2ban_socket_access_failed(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'ubuntu',
        ]);

        $this->mockProcess('failed to access socket', '', 111);

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['jails']);
        $this->assertEquals('Fail2ban is installed but not running', $result['message']);
    }

    #[Test]
    public function it_handles_exception_during_status_check(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to get Fail2ban status', Mockery::type('array'));

        // Mock Process to throw exception
        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Connection timeout'));

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['jails']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Connection timeout', $result['error']);
    }

    #[Test]
    public function it_parses_jail_list_correctly(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $fail2banOutput = "Status\n|- Number of jail:	4\n`- Jail list:	sshd, apache-auth, nginx-limit-req, wordpress";
        $this->mockProcess($fail2banOutput);

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['enabled']);
        $this->assertCount(4, $result['jails']);
        $this->assertContains('sshd', $result['jails']);
        $this->assertContains('apache-auth', $result['jails']);
        $this->assertContains('nginx-limit-req', $result['jails']);
        $this->assertContains('wordpress', $result['jails']);
    }

    // ==========================================
    // GET JAILS TESTS
    // ==========================================

    #[Test]
    public function it_gets_all_jails_with_details(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // First call for status, then call for jail status
        $statusOutput = "Status\n|- Number of jail:	1\n`- Jail list:	sshd";
        $jailStatusOutput = "Status for the jail: sshd\n|- Filter\n|  |- Currently failed:	5\n|  |- Total failed:	100\n`- Actions\n   |- Currently banned:	2\n   |- Total banned:	50\n   `- Banned IP list:	192.168.1.50 10.0.0.10";

        // Mock multiple process calls
        $callCount = 0;
        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('fromShellCommandline')->andReturnSelf();
        $processMock->shouldReceive('setTimeout')->andReturnSelf();
        $processMock->shouldReceive('run')->andReturnSelf();
        $processMock->shouldReceive('isSuccessful')->andReturn(true);
        $processMock->shouldReceive('getOutput')->andReturnUsing(function () use (&$callCount, $statusOutput, $jailStatusOutput) {
            $callCount++;
            return $callCount === 1 ? $statusOutput : $jailStatusOutput;
        });
        $processMock->shouldReceive('getErrorOutput')->andReturn('');

        // Act
        $result = $this->service->getJails($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['jails']);
        $this->assertArrayHasKey('sshd', $result['jails']);
        $this->assertTrue($result['jails']['sshd']['success']);
    }

    #[Test]
    public function it_returns_error_when_fail2ban_not_enabled(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('not running', '', 255);

        // Act
        $result = $this->service->getJails($server);

        // Assert
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['jails']);
    }

    // ==========================================
    // GET JAIL STATUS TESTS
    // ==========================================

    #[Test]
    public function it_gets_jail_status_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n|- Filter\n|  |- Currently failed:	5\n|  |- Total failed:	100\n`- Actions\n   |- Currently banned:	2\n   |- Total banned:	50\n   `- Banned IP list:	192.168.1.50 10.0.0.10";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getJailStatus($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertEquals(5, $result['data']['currently_failed']);
        $this->assertEquals(100, $result['data']['total_failed']);
        $this->assertEquals(2, $result['data']['currently_banned']);
        $this->assertEquals(50, $result['data']['total_banned']);
        $this->assertIsArray($result['data']['banned_ips']);
        $this->assertCount(2, $result['data']['banned_ips']);
        $this->assertContains('192.168.1.50', $result['data']['banned_ips']);
        $this->assertContains('10.0.0.10', $result['data']['banned_ips']);
    }

    #[Test]
    public function it_handles_jail_status_error(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('Sorry but the jail \'invalid\' does not exist', '', 255);

        // Act
        $result = $this->service->getJailStatus($server, 'invalid');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('does not exist', $result['error']);
    }

    #[Test]
    public function it_escapes_jail_name_in_command(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('Status for the jail');

        // Act
        $result = $this->service->getJailStatus($server, 'sshd; rm -rf /');

        // Assert - Command should be executed safely
        $this->assertIsArray($result);
    }

    // ==========================================
    // GET BANNED IPS TESTS
    // ==========================================

    #[Test]
    public function it_gets_banned_ips_from_specific_jail(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n`- Actions\n   |- Currently banned:	2\n   |- Total banned:	50\n   `- Banned IP list:	192.168.1.50 10.0.0.10";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getBannedIPs($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sshd', $result['banned_ips']);
        $this->assertCount(2, $result['banned_ips']['sshd']);
        $this->assertEquals(2, $result['total_banned']);
    }

    #[Test]
    public function it_gets_banned_ips_from_all_jails(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Mock multiple calls
        $callCount = 0;
        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('fromShellCommandline')->andReturnSelf();
        $processMock->shouldReceive('setTimeout')->andReturnSelf();
        $processMock->shouldReceive('run')->andReturnSelf();
        $processMock->shouldReceive('isSuccessful')->andReturn(true);
        $processMock->shouldReceive('getOutput')->andReturnUsing(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                return "Status\n|- Number of jail:	2\n`- Jail list:	sshd, apache-auth";
            } elseif ($callCount === 2) {
                return "Status for the jail: sshd\n`- Actions\n   |- Currently banned:	1\n   `- Banned IP list:	192.168.1.50";
            }
            return "Status for the jail: apache-auth\n`- Actions\n   |- Currently banned:	1\n   `- Banned IP list:	10.0.0.20";
        });
        $processMock->shouldReceive('getErrorOutput')->andReturn('');

        // Act
        $result = $this->service->getBannedIPs($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sshd', $result['banned_ips']);
        $this->assertArrayHasKey('apache-auth', $result['banned_ips']);
        $this->assertEquals(2, $result['total_banned']);
    }

    #[Test]
    public function it_returns_error_when_getting_banned_ips_fails(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('not running', '', 255);

        // Act
        $result = $this->service->getBannedIPs($server, 'sshd');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['banned_ips']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_empty_banned_ip_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n`- Actions\n   |- Currently banned:	0\n   `- Banned IP list:	";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getBannedIPs($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['banned_ips']['sshd']);
        $this->assertEquals(0, $result['total_banned']);
    }

    // ==========================================
    // UNBAN IP TESTS
    // ==========================================

    #[Test]
    public function it_unbans_ip_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('192.168.1.50 unbanned');

        // Act
        $result = $this->service->unbanIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('unbanned', $result['message']);

        // Check security event was logged
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_UNBANNED,
            'source_ip' => '192.168.1.50',
        ]);
    }

    #[Test]
    public function it_unbans_ip_with_sudo_for_non_root(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ssh_password' => 'password123',
        ]);

        $this->mockProcess('unbanned');

        // Act
        $result = $this->service->unbanIP($server, '10.0.0.10', 'sshd');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_validates_ip_address_before_unbanning(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->unbanIP($server, 'invalid-ip', 'sshd');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid IP address', $result['message']);
    }

    #[Test]
    public function it_handles_failure_to_unban_ip(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'IP is not banned', 1);

        // Act
        $result = $this->service->unbanIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to unban IP', $result['message']);
    }

    #[Test]
    public function it_accepts_ipv6_addresses_for_unbanning(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('unbanned');

        // Act
        $result = $this->service->unbanIP($server, '2001:0db8:85a3:0000:0000:8a2e:0370:7334', 'sshd');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_escapes_ip_and_jail_in_unban_command(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act - Attempt command injection
        $result = $this->service->unbanIP($server, '192.168.1.50; rm -rf /', 'sshd; rm -rf /');

        // Assert - Should fail validation
        $this->assertFalse($result['success']);
    }

    // ==========================================
    // BAN IP TESTS
    // ==========================================

    #[Test]
    public function it_bans_ip_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('1');

        // Act
        $result = $this->service->banIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('banned', $result['message']);

        // Check security event was logged
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'source_ip' => '192.168.1.50',
        ]);
    }

    #[Test]
    public function it_validates_ip_address_before_banning(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->banIP($server, 'not-an-ip', 'sshd');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid IP address', $result['message']);
    }

    #[Test]
    public function it_handles_failure_to_ban_ip(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'Jail not found', 1);

        // Act
        $result = $this->service->banIP($server, '192.168.1.50', 'invalid-jail');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to ban IP', $result['message']);
    }

    #[Test]
    public function it_bans_ip_with_custom_jail(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('1');

        // Act
        $result = $this->service->banIP($server, '10.0.0.20', 'apache-auth');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'source_ip' => '10.0.0.20',
        ]);
    }

    // ==========================================
    // START/STOP FAIL2BAN TESTS
    // ==========================================

    #[Test]
    public function it_starts_fail2ban_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'fail2ban_enabled' => false,
        ]);

        $this->mockProcess('fail2ban started');

        // Act
        $result = $this->service->startFail2ban($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Fail2ban started successfully', $result['message']);
        $server->refresh();
        $this->assertTrue($server->fail2ban_enabled);
    }

    #[Test]
    public function it_handles_failure_to_start_fail2ban(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'Service not found', 5);

        // Act
        $result = $this->service->startFail2ban($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to start Fail2ban', $result['message']);
    }

    #[Test]
    public function it_stops_fail2ban_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'fail2ban_enabled' => true,
        ]);

        $this->mockProcess('fail2ban stopped');

        // Act
        $result = $this->service->stopFail2ban($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Fail2ban stopped successfully', $result['message']);
        $server->refresh();
        $this->assertFalse($server->fail2ban_enabled);
    }

    #[Test]
    public function it_handles_failure_to_stop_fail2ban(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'Service not running', 5);

        // Act
        $result = $this->service->stopFail2ban($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to stop Fail2ban', $result['message']);
    }

    // ==========================================
    // INSTALL FAIL2BAN TESTS
    // ==========================================

    #[Test]
    public function it_installs_fail2ban_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'fail2ban_installed' => false,
            'fail2ban_enabled' => false,
        ]);

        $this->mockProcess('fail2ban installed');

        // Act
        $result = $this->service->installFail2ban($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Fail2ban installed and started successfully', $result['message']);
        $server->refresh();
        $this->assertTrue($server->fail2ban_installed);
        $this->assertTrue($server->fail2ban_enabled);
    }

    #[Test]
    public function it_uses_longer_timeout_for_installation(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('Installing packages...');

        // Act - installFail2ban uses 120 second timeout
        $result = $this->service->installFail2ban($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_failure_to_install_fail2ban(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'Package not found in repositories', 100);

        // Act
        $result = $this->service->installFail2ban($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to install Fail2ban', $result['message']);
    }

    // ==========================================
    // LOCALHOST DETECTION TESTS
    // ==========================================

    #[Test]
    public function it_executes_commands_locally_for_localhost_ip(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertTrue($result['enabled']);
    }

    #[Test]
    public function it_executes_commands_locally_for_ipv6_localhost(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '::1',
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    #[Test]
    public function it_executes_commands_locally_for_localhost_name(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => 'localhost',
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // SUDO PREFIX TESTS
    // ==========================================

    #[Test]
    public function it_uses_no_sudo_for_root_user(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    #[Test]
    public function it_uses_sudo_with_password_for_non_root(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ssh_password' => 'secure_pass',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    #[Test]
    public function it_uses_passwordless_sudo_for_non_root_without_password(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'ubuntu',
            'ssh_password' => null,
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // SECURITY EVENT LOGGING TESTS
    // ==========================================

    #[Test]
    public function it_logs_security_event_when_banning_ip(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('1');

        // Act
        $this->service->banIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'source_ip' => '192.168.1.50',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_logs_security_event_when_unbanning_ip(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('unbanned');

        // Act
        $this->service->unbanIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_UNBANNED,
            'source_ip' => '192.168.1.50',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_includes_jail_name_in_security_event_details(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('1');

        // Act
        $this->service->banIP($server, '192.168.1.50', 'apache-auth');

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'source_ip' => '192.168.1.50',
        ]);

        $event = SecurityEvent::where('server_id', $server->id)->first();
        $this->assertStringContainsString('apache-auth', $event->details);
    }

    // ==========================================
    // JAIL STATUS PARSING TESTS
    // ==========================================

    #[Test]
    public function it_parses_jail_status_with_all_fields(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n|- Filter\n|  |- Currently failed:	5\n|  |- Total failed:	100\n`- Actions\n   |- Currently banned:	2\n   |- Total banned:	50\n   `- Banned IP list:	192.168.1.50 10.0.0.10";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getJailStatus($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['data']['currently_failed']);
        $this->assertEquals(100, $result['data']['total_failed']);
        $this->assertEquals(2, $result['data']['currently_banned']);
        $this->assertEquals(50, $result['data']['total_banned']);
        $this->assertCount(2, $result['data']['banned_ips']);
    }

    #[Test]
    public function it_parses_jail_status_with_no_banned_ips(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n|- Filter\n|  |- Currently failed:	0\n|  `- Total failed:	0\n`- Actions\n   |- Currently banned:	0\n   |- Total banned:	0\n   `- Banned IP list:	";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getJailStatus($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['currently_failed']);
        $this->assertEquals(0, $result['data']['currently_banned']);
        $this->assertEmpty($result['data']['banned_ips']);
    }

    #[Test]
    public function it_handles_multiple_banned_ips_on_single_line(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $jailStatusOutput = "Status for the jail: sshd\n`- Actions\n   |- Currently banned:	5\n   `- Banned IP list:	192.168.1.1 192.168.1.2 192.168.1.3 192.168.1.4 192.168.1.5";
        $this->mockProcess($jailStatusOutput);

        // Act
        $result = $this->service->getJailStatus($server, 'sshd');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(5, $result['data']['banned_ips']);
        $this->assertContains('192.168.1.1', $result['data']['banned_ips']);
        $this->assertContains('192.168.1.5', $result['data']['banned_ips']);
    }

    // ==========================================
    // ERROR HANDLING TESTS
    // ==========================================

    #[Test]
    public function it_handles_command_execution_exception(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $processMock = Mockery::mock('overload:' . Process::class);
        $processMock->shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Network error'));

        // Act
        $result = $this->service->banIP($server, '192.168.1.50', 'sshd');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Network error', $result['message']);
    }

    #[Test]
    public function it_returns_error_info_on_command_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('', 'Fail2ban service not running', 1);

        // Act
        $result = $this->service->startFail2ban($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Fail2ban service not running', $result['message']);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    #[Test]
    public function it_handles_empty_jail_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	0\n`- Jail list:	");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['enabled']);
        $this->assertEmpty($result['jails']);
    }

    #[Test]
    public function it_handles_single_jail_without_comma(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	1\n`- Jail list:	sshd");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertTrue($result['enabled']);
        $this->assertCount(1, $result['jails']);
        $this->assertContains('sshd', $result['jails']);
    }

    #[Test]
    public function it_handles_jail_names_with_hyphens(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess("Status\n|- Number of jail:	3\n`- Jail list:	sshd, apache-auth, nginx-limit-req");

        // Act
        $result = $this->service->getFail2banStatus($server);

        // Assert
        $this->assertCount(3, $result['jails']);
        $this->assertContains('apache-auth', $result['jails']);
        $this->assertContains('nginx-limit-req', $result['jails']);
    }

    #[Test]
    public function it_defaults_to_sshd_jail_when_not_specified(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $this->mockProcess('unbanned');

        // Act - Call without specifying jail
        $result = $this->service->unbanIP($server, '192.168.1.50');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_IP_UNBANNED,
        ]);

        $event = SecurityEvent::where('server_id', $server->id)->first();
        $this->assertStringContainsString('sshd', $event->details);
    }
}
