<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\SshConfiguration;
use App\Services\Security\SSHSecurityService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SSHSecurityServiceTest extends TestCase
{
    

    protected SSHSecurityService $service;

    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SSHSecurityService();
        $this->server = Server::factory()->withSshKey()->create([
            'ip_address' => '192.168.1.100',
            'port' => 22,
            'username' => 'testuser',
        ]);
    }

    #[Test]
    public function it_can_get_current_ssh_config_successfully(): void
    {
        $sshConfigOutput = <<<'SSH'
Port 22
PermitRootLogin no
PasswordAuthentication yes
PubkeyAuthentication yes
MaxAuthTries 6
X11Forwarding no
LoginGraceTime 120
SSH;

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('config', $result);
        $this->assertEquals(22, $result['config']['port']);
        $this->assertFalse($result['config']['root_login_enabled']);
        $this->assertTrue($result['config']['password_auth_enabled']);
        $this->assertTrue($result['config']['pubkey_auth_enabled']);
        $this->assertEquals(6, $result['config']['max_auth_tries']);
    }

    #[Test]
    public function it_creates_ssh_configuration_record_when_getting_config(): void
    {
        $sshConfigOutput = <<<'SSH'
Port 2222
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
SSH;

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $this->assertDatabaseMissing('ssh_configurations', [
            'server_id' => $this->server->id,
        ]);

        $this->service->getCurrentConfig($this->server);

        $this->assertDatabaseHas('ssh_configurations', [
            'server_id' => $this->server->id,
            'port' => 2222,
            'root_login_enabled' => false,
            'password_auth_enabled' => false,
            'pubkey_auth_enabled' => true,
            'max_auth_tries' => 3,
        ]);
    }

    #[Test]
    public function it_handles_failed_config_retrieval(): void
    {
        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 1
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Unable to read SSH configuration', $result['error']);
    }

    #[Test]
    public function it_can_update_ssh_port(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'Port 22'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['port' => 2222]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('changes', $result);
        $this->assertContains('Port=2222', $result['changes']);
    }

    #[Test]
    public function it_validates_port_range(): void
    {
        Process::fake();

        $result = $this->service->updateConfig($this->server, ['port' => 70000]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Port must be between 1 and 65535', $result['message']);
    }

    #[Test]
    public function it_validates_port_minimum_range(): void
    {
        Process::fake();

        $result = $this->service->updateConfig($this->server, ['port' => 0]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Port must be between 1 and 65535', $result['message']);
    }

    #[Test]
    public function it_validates_well_known_ports(): void
    {
        Process::fake();

        $result = $this->service->updateConfig($this->server, ['port' => 80]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Port must be 22 or above 1024', $result['message']);
    }

    #[Test]
    public function it_allows_port_22(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'Port 2222'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['port' => 22]);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_can_disable_root_login(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PermitRootLogin yes'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['root_login_enabled' => false]);

        $this->assertTrue($result['success']);
        $this->assertContains('PermitRootLogin=no', $result['changes']);
    }

    #[Test]
    public function it_can_enable_root_login(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PermitRootLogin no'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['root_login_enabled' => true]);

        $this->assertTrue($result['success']);
        $this->assertContains('PermitRootLogin=yes', $result['changes']);
    }

    #[Test]
    public function it_can_disable_password_authentication(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PasswordAuthentication yes'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['password_auth_enabled' => false]);

        $this->assertTrue($result['success']);
        $this->assertContains('PasswordAuthentication=no', $result['changes']);
    }

    #[Test]
    public function it_can_enable_password_authentication(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PasswordAuthentication no'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['password_auth_enabled' => true]);

        $this->assertTrue($result['success']);
        $this->assertContains('PasswordAuthentication=yes', $result['changes']);
    }

    #[Test]
    public function it_can_set_max_auth_tries(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'MaxAuthTries 6'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['max_auth_tries' => 3]);

        $this->assertTrue($result['success']);
        $this->assertContains('MaxAuthTries=3', $result['changes']);
    }

    #[Test]
    public function it_validates_max_auth_tries_range(): void
    {
        Process::fake();

        $result = $this->service->updateConfig($this->server, ['max_auth_tries' => 15]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('MaxAuthTries must be between 1 and 10', $result['message']);
    }

    #[Test]
    public function it_validates_max_auth_tries_minimum(): void
    {
        Process::fake();

        $result = $this->service->updateConfig($this->server, ['max_auth_tries' => 0]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('MaxAuthTries must be between 1 and 10', $result['message']);
    }

    #[Test]
    public function it_fails_update_when_config_validation_fails(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'Port 22'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(
                output: '',
                errorOutput: 'Configuration error: invalid syntax',
                exitCode: 1
            ),
        ]);

        $result = $this->service->updateConfig($this->server, ['port' => 2222]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SSH configuration validation failed', $result['message']);
    }

    #[Test]
    public function it_logs_security_event_when_config_updated(): void
    {
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        Process::fake([
            '*grep*' => Process::result(output: 'Port 22'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $this->service->updateConfig($this->server, ['port' => 2222]);

        $this->assertDatabaseHas('security_events', [
            'server_id' => $this->server->id,
            'event_type' => SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_can_change_ssh_port_directly(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'Port 22'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->changePort($this->server, 2222);

        $this->assertTrue($result['success']);
        $this->assertEquals(2222, $result['new_port']);
        $this->assertStringContainsString('Remember to update firewall rules', $result['message']);
    }

    #[Test]
    public function it_validates_port_when_changing_port_directly(): void
    {
        Process::fake();

        $result = $this->service->changePort($this->server, 80);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Port must be 22 or above 1024', $result['message']);
    }

    #[Test]
    public function it_can_toggle_root_login_off(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PermitRootLogin yes'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->toggleRootLogin($this->server, false);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
    }

    #[Test]
    public function it_can_toggle_root_login_on(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PermitRootLogin no'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->toggleRootLogin($this->server, true);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('enabled', $result['message']);
    }

    #[Test]
    public function it_can_toggle_password_auth_off(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PasswordAuthentication yes'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->togglePasswordAuth($this->server, false);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
    }

    #[Test]
    public function it_can_toggle_password_auth_on(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'PasswordAuthentication no'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->togglePasswordAuth($this->server, true);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('enabled', $result['message']);
    }

    #[Test]
    public function it_can_harden_ssh_configuration(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'found'),
            '*sed*' => Process::result(output: ''),
        ]);

        $result = $this->service->hardenSSH($this->server);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('changes', $result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertStringContainsString('SSH key access', $result['warning']);
    }

    #[Test]
    public function it_applies_all_hardening_rules(): void
    {
        $changes = [];
        Process::fake([
            '*grep*' => Process::result(output: 'found'),
            '*sed*' => Process::result(
                output: '',
                using: function ($command) use (&$changes) {
                    $changes[] = $command;

                    return Process::result(output: '');
                }
            ),
        ]);

        $result = $this->service->hardenSSH($this->server);

        $this->assertTrue($result['success']);
        // Should update at least 7 configuration values
        $this->assertCount(7, $result['changes']);
    }

    #[Test]
    public function it_can_restart_ssh_service(): void
    {
        Process::fake([
            '*sshd -t*' => Process::result(output: ''),
            '*systemctl restart*' => Process::result(output: 'Success'),
        ]);

        $result = $this->service->restartSSHService($this->server);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('restarted successfully', $result['message']);
    }

    #[Test]
    public function it_validates_config_before_restarting_ssh(): void
    {
        Process::fake([
            '*sshd -t*' => Process::result(
                output: '',
                errorOutput: 'Configuration error',
                exitCode: 1
            ),
        ]);

        $result = $this->service->restartSSHService($this->server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid', $result['message']);
    }

    #[Test]
    public function it_handles_failed_ssh_restart(): void
    {
        Process::fake([
            '*sshd -t*' => Process::result(output: ''),
            '*systemctl restart*' => Process::result(
                output: '',
                errorOutput: 'Failed to restart',
                exitCode: 1
            ),
        ]);

        $result = $this->service->restartSSHService($this->server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to restart', $result['message']);
    }

    #[Test]
    public function it_can_validate_ssh_configuration(): void
    {
        Process::fake([
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->validateConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('valid', $result['message']);
    }

    #[Test]
    public function it_detects_invalid_ssh_configuration(): void
    {
        Process::fake([
            '*sshd -t*' => Process::result(
                output: '',
                errorOutput: 'Syntax error on line 42',
                exitCode: 1
            ),
        ]);

        $result = $this->service->validateConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('errors', $result['message']);
    }

    #[Test]
    public function it_parses_ssh_config_with_default_values(): void
    {
        $sshConfigOutput = '';

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertEquals(22, $result['config']['port']);
        $this->assertTrue($result['config']['root_login_enabled']);
        $this->assertTrue($result['config']['password_auth_enabled']);
        $this->assertTrue($result['config']['pubkey_auth_enabled']);
        $this->assertEquals(6, $result['config']['max_auth_tries']);
    }

    #[Test]
    public function it_parses_ssh_config_with_prohibit_password_for_root(): void
    {
        $sshConfigOutput = 'PermitRootLogin prohibit-password';

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['config']['root_login_enabled']);
    }

    #[Test]
    public function it_parses_ssh_config_with_without_password_for_root(): void
    {
        $sshConfigOutput = 'PermitRootLogin without-password';

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['config']['root_login_enabled']);
    }

    #[Test]
    public function it_parses_ssh_config_ignoring_comments(): void
    {
        $sshConfigOutput = <<<'SSH'
# Port 2222
Port 22
# PermitRootLogin yes
PermitRootLogin no
SSH;

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertEquals(22, $result['config']['port']);
        $this->assertFalse($result['config']['root_login_enabled']);
    }

    #[Test]
    public function it_parses_x11_forwarding_and_login_grace_time(): void
    {
        $sshConfigOutput = <<<'SSH'
X11Forwarding yes
LoginGraceTime 60
SSH;

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: $sshConfigOutput,
                errorOutput: ''
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['config']['x11_forwarding']);
        $this->assertEquals(60, $result['config']['login_grace_time']);
    }

    #[Test]
    public function it_handles_exception_when_getting_config(): void
    {
        Log::shouldReceive('error')->once();

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(
                output: '',
                errorOutput: 'Connection timeout',
                exitCode: 255
            ),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_updates_multiple_config_values_at_once(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: 'found'),
            '*sed*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, [
            'port' => 2222,
            'root_login_enabled' => false,
            'password_auth_enabled' => false,
            'max_auth_tries' => 3,
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(4, $result['changes']);
    }

    #[Test]
    public function it_uses_sudo_for_non_root_user(): void
    {
        $this->server->update(['username' => 'ubuntu']);

        Process::fake([
            '*sudo cat*' => Process::result(output: 'Port 22'),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        Process::assertRan(fn ($process) => str_contains($process->command, 'sudo'));
    }

    #[Test]
    public function it_does_not_use_sudo_for_root_user(): void
    {
        $this->server->update(['username' => 'root']);

        Process::fake([
            '*cat*' => Process::result(output: 'Port 22'),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        // The command should not contain 'sudo'
        Process::assertRan(fn ($process) => ! str_contains($process->command, 'sudo'));
    }

    #[Test]
    public function it_handles_localhost_server_differently(): void
    {
        $this->server->update(['ip_address' => '127.0.0.1']);

        Process::fake([
            '*cat /etc/ssh/sshd_config*' => Process::result(output: 'Port 22'),
        ]);

        $result = $this->service->getCurrentConfig($this->server);

        // Should execute locally without SSH
        Process::assertRan(fn ($process) => ! str_contains($process->command, 'ssh'));
    }

    #[Test]
    public function it_appends_config_value_when_key_not_found(): void
    {
        Process::fake([
            '*grep*' => Process::result(output: '', exitCode: 1), // Key not found
            '*tee*' => Process::result(output: ''),
            '*sshd -t*' => Process::result(output: ''),
        ]);

        $result = $this->service->updateConfig($this->server, ['max_auth_tries' => 3]);

        $this->assertTrue($result['success']);
        Process::assertRan(fn ($process) => str_contains($process->command, 'tee'));
    }
}
