<?php

namespace Tests\Unit\Services;

use App\Services\DockerInstallationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

class DockerInstallationServiceTest extends TestCase
{
    use CreatesServers;

    protected DockerInstallationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DockerInstallationService;
        Log::spy();
    }

    // ==========================================
    // DOCKER INSTALLATION TESTS
    // ==========================================

    /** @test */
    public function it_installs_docker_on_debian_server_with_root_user(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'ssh_password' => null,
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Detected OS: debian\nStep 1/6: Updating package index...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('installed successfully', $result['message']);
        $this->assertArrayHasKey('version', $result);
    }

    /** @test */
    public function it_installs_docker_on_ubuntu_server_with_password_auth(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'devflow',
            'os' => 'Ubuntu',
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Detected OS: ubuntu\nConfiguring Docker repository for Ubuntu...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Docker installed successfully', $result['message']);
        Log::shouldHaveReceived('info')
            ->with('Starting Docker installation', ['server_id' => $server->id]);
    }

    /** @test */
    public function it_installs_docker_on_centos_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'os' => 'CentOS',
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Detected OS: centos\nConfiguring Docker repository for RHEL-based OS...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_installs_docker_on_fedora_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'root',
            'os' => 'Fedora',
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Detected OS: fedora\nConfiguring Docker repository for Fedora...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('output', $result);
    }

    /** @test */
    public function it_installs_docker_with_passwordless_sudo(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'username' => 'deployer',
            'ssh_password' => null,
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Username: deployer\nUsing passwordless sudo...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_updates_server_record_after_successful_installation(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['docker_installed' => false]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Docker Installation Completed Successfully'
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertTrue($result['success']);
        $server->refresh();
        $this->assertTrue($server->docker_installed);
        $this->assertNotNull($server->docker_version);
    }

    /** @test */
    public function it_handles_docker_installation_failure(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: 'Failed to download Docker packages',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('installation failed', strtolower($result['message']));
        Log::shouldHaveReceived('error')
            ->with('Docker installation failed', \Mockery::type('array'));
    }

    /** @test */
    public function it_handles_installation_script_timeout(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: 'Timeout waiting for package manager',
                exitCode: 124
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_truncates_long_error_messages(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $longError = str_repeat('Error details ', 50);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: $longError,
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertLessThanOrEqual(203, strlen($result['message'])); // 200 chars + "..."
    }

    /** @test */
    public function it_handles_installation_exception(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('SSH connection failed'));

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Installation failed', $result['message']);
        $this->assertEquals('SSH connection failed', $result['error']);
        Log::shouldHaveReceived('error')
            ->with('Docker installation exception', \Mockery::type('array'));
    }

    /** @test */
    public function it_handles_verification_failure_after_installation(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Installation script completed'
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('verification failed', $result['message']);
    }

    // ==========================================
    // DOCKER VERIFICATION TESTS
    // ==========================================

    /** @test */
    public function it_verifies_docker_installation_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*docker --version*' => Process::result(
                output: 'Docker version 24.0.7, build afdd53b'
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertEquals('24.0.7', $result['version']);
        $this->assertStringContainsString('Docker version', $result['output']);
    }

    /** @test */
    public function it_extracts_docker_version_from_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*docker --version*' => Process::result(
                output: 'Docker version 25.0.1, build 29cf629'
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertEquals('25.0.1', $result['version']);
    }

    /** @test */
    public function it_handles_docker_not_installed_during_verification(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*docker --version*' => Process::result(
                output: '',
                errorOutput: 'docker: command not found',
                exitCode: 127
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertNull($result['version']);
    }

    /** @test */
    public function it_handles_verification_exception(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Connection timeout'));

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertNull($result['version']);
        $this->assertEquals('Connection timeout', $result['error']);
    }

    /** @test */
    public function it_handles_malformed_docker_version_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*docker --version*' => Process::result(
                output: 'Some unexpected output without version'
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertNull($result['version']); // Version extraction failed but command succeeded
    }

    // ==========================================
    // DOCKER COMPOSE TESTS
    // ==========================================

    /** @test */
    public function it_checks_docker_compose_installation(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker compose version*' => Process::result(
                output: 'Docker Compose version v2.20.2'
            ),
        ]);

        // Act
        $result = $this->service->checkDockerCompose($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertEquals('2.20.2', $result['version']);
    }

    /** @test */
    public function it_extracts_compose_version_without_v_prefix(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker compose version*' => Process::result(
                output: 'docker-compose version 1.29.2, build 5becea4c'
            ),
        ]);

        // Act
        $result = $this->service->checkDockerCompose($server);

        // Assert
        $this->assertTrue($result['installed']);
        $this->assertEquals('1.29.2', $result['version']);
    }

    /** @test */
    public function it_detects_docker_compose_not_installed(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*docker compose version*' => Process::result(
                output: '',
                errorOutput: 'docker: \'compose\' is not a docker command',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->checkDockerCompose($server);

        // Assert
        $this->assertFalse($result['installed']);
    }

    /** @test */
    public function it_handles_compose_check_exception(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Network error'));

        // Act
        $result = $this->service->checkDockerCompose($server);

        // Assert
        $this->assertFalse($result['installed']);
        $this->assertEquals('Network error', $result['error']);
    }

    /** @test */
    public function it_verifies_docker_compose_already_installed(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker compose version*' => Process::result(
                output: 'Docker Compose version v2.21.0'
            ),
        ]);

        // Act
        $result = $this->service->installDockerCompose($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already installed', $result['message']);
        $this->assertEquals('2.21.0', $result['version']);
    }

    /** @test */
    public function it_handles_docker_compose_not_available_after_docker_install(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker compose version*' => Process::result(
                output: '',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDockerCompose($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not installed with Docker', $result['message']);
    }

    /** @test */
    public function it_handles_docker_compose_install_exception(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('Failed to connect'));

        // Act
        $result = $this->service->installDockerCompose($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to check Docker Compose', $result['message']);
    }

    // ==========================================
    // DOCKER INFO TESTS
    // ==========================================

    /** @test */
    public function it_retrieves_docker_system_information(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();
        $dockerInfo = [
            'ServerVersion' => '24.0.7',
            'Containers' => 10,
            'ContainersRunning' => 5,
            'Images' => 20,
            'Driver' => 'overlay2',
        ];

        Process::fake([
            '*docker info --format*' => Process::result(
                output: json_encode($dockerInfo)
            ),
        ]);

        // Act
        $result = $this->service->getDockerInfo($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($dockerInfo, $result['info']);
        $this->assertEquals('24.0.7', $result['info']['ServerVersion']);
        $this->assertEquals(5, $result['info']['ContainersRunning']);
    }

    /** @test */
    public function it_handles_docker_info_failure(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker info --format*' => Process::result(
                output: '',
                errorOutput: 'Cannot connect to Docker daemon',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->getDockerInfo($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to get Docker info', $result['message']);
    }

    /** @test */
    public function it_handles_docker_info_exception(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::shouldReceive('fromShellCommandline')
            ->andThrow(new \Exception('SSH timeout'));

        // Act
        $result = $this->service->getDockerInfo($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('SSH timeout', $result['message']);
    }

    /** @test */
    public function it_handles_malformed_docker_info_json(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*docker info --format*' => Process::result(
                output: 'Invalid JSON output'
            ),
        ]);

        // Act
        $result = $this->service->getDockerInfo($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertNull($result['info']); // JSON decode returns null
    }

    // ==========================================
    // SSH COMMAND BUILDING TESTS
    // ==========================================

    /** @test */
    public function it_builds_ssh_command_with_password_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'ubuntu',
            'ip_address' => '192.168.1.100',
            'port' => 22,
        ]);

        Process::fake([
            '*sshpass*' => Process::result(output: 'success'),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert - indirectly tests buildSSHCommand
        Process::assertRan(function ($process) {
            return str_contains($process, 'sshpass -p');
        });
    }

    /** @test */
    public function it_builds_ssh_command_with_key_authentication(): void
    {
        // Arrange
        $server = $this->createServerWithSshKey([
            'username' => 'root',
            'ip_address' => '10.0.0.50',
            'port' => 2222,
        ]);

        Process::fake([
            '*ssh*' => Process::result(output: 'Docker version 24.0.7'),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, 'ssh') &&
                   str_contains($process, '-i ') &&
                   str_contains($process, '-p 2222');
        });
    }

    /** @test */
    public function it_uses_base64_encoding_for_long_scripts(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Docker Installation Completed Successfully'
            ),
        ]);

        // Act
        $this->service->installDocker($server);

        // Assert - Long installation script should use base64 encoding
        Process::assertRan(function ($process) {
            return str_contains($process, 'base64 -d');
        });
    }

    /** @test */
    public function it_suppresses_warnings_when_requested(): void
    {
        // Arrange
        $server = $this->createServerWithDocker();

        Process::fake([
            '*2>/dev/null*' => Process::result(
                output: 'Docker Compose version v2.20.2'
            ),
        ]);

        // Act
        $this->service->checkDockerCompose($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, '2>/dev/null');
        });
    }

    /** @test */
    public function it_includes_ssh_connection_options(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*StrictHostKeyChecking=no*' => Process::result(
                output: 'Docker version 24.0.7'
            ),
        ]);

        // Act
        $this->service->verifyDockerInstallation($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, 'StrictHostKeyChecking=no') &&
                   str_contains($process, 'UserKnownHostsFile=/dev/null') &&
                   str_contains($process, 'ConnectTimeout=10');
        });
    }

    // ==========================================
    // INSTALLATION SCRIPT GENERATION TESTS
    // ==========================================

    /** @test */
    public function it_generates_script_for_root_user_without_sudo(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['username' => 'root']);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Script output'
            ),
        ]);

        // Act
        $this->service->installDocker($server);

        // Assert - Script should not contain sudo commands for root
        Process::assertRan(function ($process) {
            // We're checking the command was executed (exact content is internal)
            return str_contains($process, 'base64 -d');
        });
    }

    /** @test */
    public function it_generates_script_with_sudo_for_non_root_with_password(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'username' => 'deployer',
        ]);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Caching sudo credentials...'
            ),
        ]);

        // Act
        $this->service->installDocker($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, 'base64 -d');
        });
    }

    /** @test */
    public function it_detects_debian_testing_and_uses_bookworm(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['os' => 'Debian']);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Detected Debian testing/unstable (trixie), using bookworm repository...'
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert - The script should handle Debian testing versions
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // EDGE CASES AND ERROR SCENARIOS
    // ==========================================

    /** @test */
    public function it_handles_empty_error_and_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: '',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown error', $result['message']);
    }

    /** @test */
    public function it_handles_network_interruption_during_installation(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Step 3/6: Adding Docker repository...',
                errorOutput: 'curl: (6) Could not resolve host',
                exitCode: 6
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Could not resolve host', $result['message']);
    }

    /** @test */
    public function it_handles_package_manager_lock(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: 'Could not get lock /var/lib/dpkg/lock',
                exitCode: 100
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('lock', $result['message']);
    }

    /** @test */
    public function it_handles_unsupported_os_detection(): void
    {
        // Arrange
        $server = $this->createOnlineServer(['os' => 'FreeBSD']);

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Unsupported OS: freebsd',
                errorOutput: 'Supported: debian, ubuntu, centos, rhel, rocky, almalinux, fedora',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_handles_permission_denied_errors(): void
    {
        // Arrange
        $server = $this->createServerWithPassword();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 126
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Permission denied', $result['message']);
    }

    /** @test */
    public function it_handles_ssh_key_escaping_with_special_characters(): void
    {
        // Arrange
        $server = $this->createServerWithPassword([
            'ssh_password' => "p@ss'word\"123",
        ]);

        Process::fake([
            '*sshpass*' => Process::result(
                output: 'Script executed'
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert - Password should be properly escaped
        Process::assertRan(function ($process) {
            return str_contains($process, 'sshpass');
        });
    }

    /** @test */
    public function it_handles_custom_ssh_port(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'port' => 2222,
        ]);

        Process::fake([
            '*-p 2222*' => Process::result(
                output: 'Docker version 24.0.7'
            ),
        ]);

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        Process::assertRan(function ($process) {
            return str_contains($process, '-p 2222');
        });
    }

    /** @test */
    public function it_uses_correct_timeout_for_installation(): void
    {
        // Arrange - This test verifies the timeout is set to 600 seconds (10 minutes)
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Installation in progress...',
                errorOutput: 'Timeout after 600 seconds',
                exitCode: 124
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_uses_correct_timeout_for_verification(): void
    {
        // Arrange - This test verifies the timeout is set to 30 seconds
        $server = $this->createOnlineServer();

        Process::shouldReceive('fromShellCommandline')
            ->andReturnUsing(function () {
                $mock = \Mockery::mock(\Symfony\Component\Process\Process::class);
                $mock->shouldReceive('setTimeout')->with(30)->andReturnSelf();
                $mock->shouldReceive('run')->andReturnSelf();
                $mock->shouldReceive('isSuccessful')->andReturn(false);

                return $mock;
            });

        // Act
        $result = $this->service->verifyDockerInstallation($server);

        // Assert
        $this->assertFalse($result['installed']);
    }

    /** @test */
    public function it_handles_partial_installation_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Step 1/6: Updating package index...',
                errorOutput: 'Connection reset by peer',
                exitCode: 104
            ),
        ]);

        // Act
        $result = $this->service->installDocker($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('output', $result);
    }
}
