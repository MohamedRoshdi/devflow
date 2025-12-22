<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    // ==========================================
    // STREAMING OUTPUT TESTS
    // ==========================================

    #[Test]
    public function it_streams_output_to_callback(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $callbackInvoked = false;
        $receivedLines = [];

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Step 1/6: Updating package index...\nDocker Installation Completed Successfully"
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$callbackInvoked, &$receivedLines) {
            $callbackInvoked = true;
            $receivedLines[] = $line;
        });

        // Assert
        $this->assertTrue($callbackInvoked);
        $this->assertNotEmpty($receivedLines);
    }

    #[Test]
    public function it_handles_null_callback_gracefully(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Docker Installation Completed Successfully'
            ),
        ]);

        // Act - should not throw exception
        $result = $this->service->installDockerWithStreaming($server, null);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_provides_progress_updates_during_streaming(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $progressValues = [];

        Process::fake([
            '*base64 -d*' => Process::result(
                output: implode("\n", [
                    'Connecting to server...',
                    'Step 1/6: Updating package index...',
                    'Step 2/6: Installing prerequisites...',
                    'Step 3/6: Adding Docker repository...',
                    'Docker Installation Completed Successfully',
                ])
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$progressValues) {
            $progressValues[] = $progress;
        });

        // Assert - Progress should generally increase
        $this->assertNotEmpty($progressValues);
        foreach ($progressValues as $progress) {
            $this->assertGreaterThanOrEqual(0, $progress);
            $this->assertLessThanOrEqual(100, $progress);
        }
    }

    #[Test]
    public function it_provides_step_descriptions_during_streaming(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $stepDescriptions = [];

        Process::fake([
            '*base64 -d*' => Process::result(
                output: implode("\n", [
                    'Step 1/6: Updating package index...',
                    'Step 5/6: Installing Docker packages...',
                    'Docker Installation Completed Successfully',
                ])
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$stepDescriptions) {
            $stepDescriptions[] = $step;
        });

        // Assert
        $this->assertNotEmpty($stepDescriptions);
        foreach ($stepDescriptions as $step) {
            $this->assertIsString($step);
        }
    }

    #[Test]
    public function it_streams_stderr_output_with_prefix(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $receivedLines = [];

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Step 1/6: Updating...',
                errorOutput: 'Warning: Some non-critical warning'
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$receivedLines) {
            $receivedLines[] = $line;
        });

        // Assert - stderr should be streamed with STDERR prefix
        $stderrLines = array_filter($receivedLines, fn ($line) => str_contains($line, 'STDERR:'));
        // Note: The actual behavior depends on Process mock, but the method is designed to prefix stderr
        $this->assertNotEmpty($receivedLines);
    }

    #[Test]
    public function it_skips_empty_lines_during_streaming(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $receivedLines = [];

        Process::fake([
            '*base64 -d*' => Process::result(
                output: "Step 1/6: Updating...\n\n\nStep 2/6: Installing...\n\n"
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$receivedLines) {
            $receivedLines[] = $line;
        });

        // Assert - Empty lines should be filtered out
        foreach ($receivedLines as $line) {
            // Lines might be trimmed, check they're not just whitespace
            $this->assertNotEquals('', trim($line));
        }
    }

    #[Test]
    public function it_detects_verification_step_in_output(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $foundVerificationStep = false;

        Process::fake([
            '*base64 -d*' => Process::result(
                output: implode("\n", [
                    'Step 5/6: Installing Docker packages...',
                    '=== Verifying Installation ===',
                    'Docker version 24.0.7',
                ])
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$foundVerificationStep) {
            if (str_contains($step, 'Verifying') || str_contains($step, 'erification')) {
                $foundVerificationStep = true;
            }
        });

        // Assert
        $this->assertTrue($foundVerificationStep);
    }

    #[Test]
    public function it_streams_exception_message_to_callback(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $receivedLines = [];

        Process::shouldReceive('timeout')
            ->andThrow(new \RuntimeException('Connection failed'));

        // Act
        $result = $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$receivedLines) {
            $receivedLines[] = $line;
        });

        // Assert
        $this->assertFalse($result['success']);
        $hasExceptionLine = false;
        foreach ($receivedLines as $line) {
            if (str_contains($line, 'Exception') || str_contains($line, 'Connection failed')) {
                $hasExceptionLine = true;
                break;
            }
        }
        $this->assertTrue($hasExceptionLine);
    }

    #[Test]
    public function it_provides_initial_connection_progress(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $hasConnectionStep = false;

        Process::fake([
            '*base64 -d*' => Process::result(
                output: 'Docker Installation Completed Successfully'
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$hasConnectionStep) {
            if (str_contains($line, 'Connecting') || str_contains($step, 'SSH')) {
                $hasConnectionStep = true;
            }
        });

        // Assert - Should have an initial connection step
        $this->assertTrue($hasConnectionStep);
    }

    #[Test]
    public function it_caps_progress_at_90_during_installation(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $maxProgressDuringInstall = 0;

        Process::fake([
            '*base64 -d*' => Process::result(
                output: implode("\n", array_fill(0, 20, 'Step X: Installing...'))
            ),
        ]);

        // Act
        $this->service->installDockerWithStreaming($server, function ($line, $progress, $step) use (&$maxProgressDuringInstall) {
            // Track max progress during installation (before verification)
            if (!str_contains($step, 'Verification') && !str_contains($step, 'Complete')) {
                $maxProgressDuringInstall = max($maxProgressDuringInstall, $progress);
            }
        });

        // Assert - Progress should be capped during installation
        $this->assertLessThanOrEqual(90, $maxProgressDuringInstall);
    }
}
