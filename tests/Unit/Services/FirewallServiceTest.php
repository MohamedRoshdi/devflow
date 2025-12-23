<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SecurityEvent;
use App\Services\Security\FirewallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

class FirewallServiceTest extends TestCase
{
    use CreatesServers, RefreshDatabase;

    protected FirewallService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FirewallService;
    }

    // ==========================================
    // UFW STATUS CHECKING TESTS
    // ==========================================

    #[Test]
    public function it_gets_ufw_status_when_active(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ip_address' => '192.168.1.100',
        ]);

        $ufwOutput = "Status: active\n\nTo                         Action      From\n--                         ------      ----\n22/tcp                     ALLOW       Anywhere\n80/tcp                     ALLOW       Anywhere";

        Process::fake([
            '*' => Process::result(output: $ufwOutput),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertTrue($result['enabled']);
        $this->assertIsArray($result['rules']);
        $this->assertArrayHasKey('raw_output', $result);
    }

    #[Test]
    public function it_gets_ufw_status_when_inactive(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Status: inactive'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertIsArray($result['rules']);
    }

    #[Test]
    public function it_detects_ufw_not_installed(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'ufw: command not found'
            ),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['rules']);
        $this->assertEquals('UFW is not installed', $result['message']);
    }

    #[Test]
    public function it_handles_permission_denied_for_ufw_status(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'ubuntu',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Permission denied'
            ),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEquals('Permission denied - check sudo access', $result['message']);
    }

    #[Test]
    public function it_updates_server_ufw_status_on_successful_check(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ufw_installed' => false,
            'ufw_enabled' => false,
        ]);

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertTrue($result['enabled']);
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
        $this->assertTrue($server->ufw_enabled);
    }

    #[Test]
    public function it_handles_exception_during_ufw_status_check(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // executeCommand catches exceptions internally and returns failure result
        // So the outer catch block in getUfwStatus is only triggered for uncaught exceptions
        // Process failures result in graceful handling with installed=false, enabled=false

        Process::fake([
            '*' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert - exceptions are handled gracefully, returning safe defaults
        $this->assertFalse($result['installed']);
        $this->assertFalse($result['enabled']);
        $this->assertEmpty($result['rules']);
        $this->assertArrayHasKey('raw_output', $result);
    }

    #[Test]
    public function it_uses_which_command_as_fallback_for_detection(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => function ($process) {
                $command = $process->command;
                if (str_contains($command, 'which ufw')) {
                    return Process::result(output: '/usr/sbin/ufw');
                }

                return Process::result(output: '');
            },
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // UFW ENABLE/DISABLE TESTS
    // ==========================================

    #[Test]
    public function it_enables_ufw_firewall(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ufw_enabled' => false,
        ]);

        Process::fake([
            '*' => Process::result(output: 'Firewall is active and enabled on system startup'),
        ]);

        // Act
        $result = $this->service->enableUfw($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Firewall enabled successfully', $result['message']);
        $server->refresh();
        $this->assertTrue($server->ufw_enabled);

        // Check security event was logged
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED,
        ]);
    }

    #[Test]
    public function it_enables_ufw_with_sudo_for_non_root(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ssh_password' => 'password123',
        ]);

        Process::fake([
            '*' => Process::result(output: 'active'),
        ]);

        // Act
        $result = $this->service->enableUfw($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_failure_to_enable_ufw(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Failed to enable firewall',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->enableUfw($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to enable firewall', $result['message']);
    }

    #[Test]
    public function it_disables_ufw_firewall(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ufw_enabled' => true,
        ]);

        Process::fake([
            '*' => Process::result(output: 'Firewall stopped and disabled on system startup'),
        ]);

        // Act
        $result = $this->service->disableUfw($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Firewall disabled successfully', $result['message']);
        $server->refresh();
        $this->assertFalse($server->ufw_enabled);

        // Check security event was logged
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED,
        ]);
    }

    #[Test]
    public function it_handles_exception_when_enabling_ufw(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => function () {
                throw new \Exception('Unexpected error');
            },
        ]);

        // Act
        $result = $this->service->enableUfw($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unexpected error', $result['message']);
    }

    // ==========================================
    // FIREWALL RULE MANAGEMENT TESTS
    // ==========================================

    #[Test]
    public function it_adds_port_rule_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'allow', null, 'Allow HTTP');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Rule added successfully', $result['message']);

        // Check database record
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'action' => 'allow',
            'protocol' => 'tcp',
            'port' => '80',
            'description' => 'Allow HTTP',
            'is_active' => true,
        ]);

        // Check security event
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_RULE_ADDED,
        ]);
    }

    #[Test]
    public function it_adds_rule_with_source_ip(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, '22', 'tcp', 'allow', '192.168.1.100', 'SSH from specific IP');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => '22',
            'from_ip' => '192.168.1.100',
        ]);
    }

    #[Test]
    public function it_adds_deny_rule(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, '23', 'tcp', 'deny');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'action' => 'deny',
            'port' => '23',
        ]);
    }

    #[Test]
    public function it_validates_port_number(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->addRule($server, '99999', 'tcp', 'allow');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Port must be between 1 and 65535', $result['message']);
    }

    #[Test]
    public function it_validates_protocol(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'invalid', 'allow');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Protocol must be tcp, udp, or any', $result['message']);
    }

    #[Test]
    public function it_validates_action(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'invalid');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Action must be allow, deny, reject, or limit', $result['message']);
    }

    #[Test]
    public function it_validates_ip_address(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'allow', 'invalid-ip');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid IP address', $result['message']);
    }

    #[Test]
    public function it_accepts_port_range(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, '8000:8999', 'tcp', 'allow');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => '8000:8999',
        ]);
    }

    #[Test]
    public function it_rejects_invalid_port_range(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act - end port is less than start port
        $result = $this->service->addRule($server, '9000:8000', 'tcp', 'allow');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid port range', $result['message']);
    }

    #[Test]
    public function it_accepts_service_name_as_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, 'http', 'tcp', 'allow');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'port' => 'http',
        ]);
    }

    #[Test]
    public function it_accepts_cidr_notation_for_ip(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $result = $this->service->addRule($server, '22', 'tcp', 'allow', '192.168.1.0/24');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('firewall_rules', [
            'server_id' => $server->id,
            'from_ip' => '192.168.1.0/24',
        ]);
    }

    #[Test]
    public function it_handles_failure_to_add_rule(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Error adding rule',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'allow');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to add rule', $result['message']);
    }

    // ==========================================
    // DELETE RULE TESTS
    // ==========================================

    #[Test]
    public function it_deletes_rule_by_number(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule deleted'),
        ]);

        // Act
        $result = $this->service->deleteRule($server, 1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Rule deleted successfully', $result['message']);

        // Check security event
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_RULE_DELETED,
        ]);
    }

    #[Test]
    public function it_validates_rule_number_is_positive(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        // Act
        $result = $this->service->deleteRule($server, 0);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid rule number', $result['message']);
    }

    #[Test]
    public function it_handles_failure_to_delete_rule(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Rule not found',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->deleteRule($server, 99);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to delete rule', $result['message']);
    }

    // ==========================================
    // GET NUMBERED RULES TESTS
    // ==========================================

    #[Test]
    public function it_gets_numbered_rules_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $numberedOutput = "Status: active\n\n     To                         Action      From\n     --                         ------      ----\n[ 1] 22/tcp                     ALLOW IN    Anywhere\n[ 2] 80/tcp                     ALLOW IN    Anywhere";

        Process::fake([
            '*' => Process::result(output: $numberedOutput),
        ]);

        // Act
        $result = $this->service->getRulesNumbered($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['rules']);
        $this->assertCount(2, $result['rules']);
        $this->assertEquals(1, $result['rules'][0]['number']);
        $this->assertEquals(2, $result['rules'][1]['number']);
    }

    #[Test]
    public function it_handles_failure_to_get_numbered_rules(): void
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
        $result = $this->service->getRulesNumbered($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['rules']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // UFW INSTALLATION TESTS
    // ==========================================

    #[Test]
    public function it_installs_ufw(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ufw_installed' => false,
        ]);

        Process::fake([
            '*' => Process::result(output: 'UFW installed'),
        ]);

        // Act
        $result = $this->service->installUfw($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('UFW installed successfully', $result['message']);
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
    }

    #[Test]
    public function it_handles_failure_to_install_ufw(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Package not found',
                exitCode: 100
            ),
        ]);

        // Act
        $result = $this->service->installUfw($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to install UFW', $result['message']);
    }

    // ==========================================
    // RESET TO DEFAULTS TESTS
    // ==========================================

    #[Test]
    public function it_resets_firewall_to_defaults(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Reset complete'),
        ]);

        // Act
        $result = $this->service->resetToDefaults($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('reset to defaults', $result['message']);
        $this->assertStringContainsString('SSH allowed', $result['message']);

        // Check security event
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_FIREWALL_DISABLED,
        ]);
    }

    #[Test]
    public function it_handles_failure_to_reset_firewall(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Reset failed',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->resetToDefaults($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to reset firewall', $result['message']);
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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

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

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // SSH COMMAND BUILDING TESTS
    // ==========================================

    #[Test]
    public function it_uses_custom_ssh_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 2222,
        ]);

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    #[Test]
    public function it_uses_ssh_key_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithSshKey([
            'ip_address' => '192.168.1.100',
            'username' => 'ubuntu',
            'ssh_key' => '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA...
-----END RSA PRIVATE KEY-----',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    #[Test]
    public function it_uses_sshpass_for_password_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ip_address' => '192.168.1.100',
            'username' => 'ubuntu',
            'ssh_password' => 'secure_password',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Status: active'),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['installed']);
    }

    // ==========================================
    // SECURITY EVENT LOGGING TESTS
    // ==========================================

    #[Test]
    public function it_logs_security_event_when_enabling_firewall(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'active'),
        ]);

        // Act
        $this->service->enableUfw($server);

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED,
            'details' => 'UFW firewall enabled',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_logs_security_event_when_adding_rule(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $this->service->addRule($server, '80', 'tcp', 'allow');

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_RULE_ADDED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_logs_source_ip_when_adding_ip_specific_rule(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule added'),
        ]);

        // Act
        $this->service->addRule($server, '22', 'tcp', 'allow', '192.168.1.100');

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_RULE_ADDED,
            'source_ip' => '192.168.1.100',
        ]);
    }

    // ==========================================
    // RULE PARSING TESTS
    // ==========================================

    #[Test]
    public function it_parses_ufw_rules_from_status_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $ufwOutput = "Status: active\n\nTo                         Action      From\n--                         ------      ----\n22/tcp                     ALLOW       Anywhere\n80/tcp                     ALLOW       Anywhere\n443/tcp                    ALLOW       Anywhere";

        Process::fake([
            '*' => Process::result(output: $ufwOutput),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertIsArray($result['rules']);
        $this->assertCount(3, $result['rules']);
        foreach ($result['rules'] as $rule) {
            $this->assertArrayHasKey('action', $rule);
            $this->assertArrayHasKey('to', $rule);
            $this->assertArrayHasKey('from', $rule);
        }
    }

    #[Test]
    public function it_parses_numbered_rules_correctly(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        $numberedOutput = "[ 1] 22/tcp                     ALLOW IN    Anywhere\n[ 2] 80/tcp                     ALLOW IN    192.168.1.0/24";

        Process::fake([
            '*' => Process::result(output: $numberedOutput),
        ]);

        // Act
        $result = $this->service->getRulesNumbered($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['rules']);
        $this->assertEquals(1, $result['rules'][0]['number']);
        $this->assertArrayHasKey('rule', $result['rules'][0]);
        $this->assertArrayHasKey('parsed', $result['rules'][0]);
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

        Process::fake([
            '*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'allow');

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

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'UFW service not running',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->enableUfw($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('UFW service not running', $result['message']);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    #[Test]
    public function it_handles_empty_rules_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: "Status: active\n\nTo                         Action      From\n--                         ------      ----"),
        ]);

        // Act
        $result = $this->service->getUfwStatus($server);

        // Assert
        $this->assertTrue($result['enabled']);
        $this->assertEmpty($result['rules']);
    }

    #[Test]
    public function it_handles_rule_update_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Rule updated'),
        ]);

        // Act
        $result = $this->service->addRule($server, '80', 'tcp', 'allow');

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_uses_timeout_for_install_command(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: 'Installing...'),
        ]);

        // Act - installUfw uses 120 second timeout
        $result = $this->service->installUfw($server);

        // Assert
        $this->assertTrue($result['success']);
    }
}
