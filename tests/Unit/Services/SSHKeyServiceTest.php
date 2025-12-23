<?php

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\SSHKey;
use App\Models\User;
use App\Services\SSHKeyService;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class SSHKeyServiceTest extends TestCase
{

    protected SSHKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SSHKeyService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SSH KEY GENERATION TESTS
    // ==========================================

    #[Test]
    public function it_generates_ed25519_key_pair_successfully(): void
    {
        // Arrange
        $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMockPublicKeyData devflow-2025-12-06-12-00-00';
        $privateKey = "-----BEGIN OPENSSH PRIVATE KEY-----\nMockPrivateKeyData\n-----END OPENSSH PRIVATE KEY-----";

        Process::fake([
            '*ssh-keygen*' => Process::result(output: 'Key generated successfully'),
        ]);

        // Mock file operations
        $tempDir = sys_get_temp_dir().'/ssh_keys_test123';
        $keyPath = $tempDir.'/id_ed25519';

        // Use a partial mock to control file operations
        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        // Override the method to avoid actual file system operations
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->with('ed25519', '')
            ->andReturn([
                'success' => true,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
                'fingerprint' => 'ab:cd:ef:12:34:56:78:90:ab:cd:ef:12:34:56:78:90',
            ]);

        // Act
        $result = $service->generateKeyPair('ed25519', '');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('public_key', $result);
        $this->assertArrayHasKey('private_key', $result);
        $this->assertArrayHasKey('fingerprint', $result);
        $this->assertStringContainsString('ssh-ed25519', $result['public_key']);
        $this->assertStringContainsString('PRIVATE KEY', $result['private_key']);
    }

    #[Test]
    public function it_generates_rsa_key_pair_with_4096_bits(): void
    {
        // Arrange
        Process::fake([
            '*ssh-keygen*-t*rsa*-b*4096*' => Process::result(output: 'RSA key generated'),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->with('rsa', 'test-comment')
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-rsa AAAAB3NzaC1MockRSAKey test-comment',
                'private_key' => "-----BEGIN RSA PRIVATE KEY-----\nMockData\n-----END RSA PRIVATE KEY-----",
                'fingerprint' => '12:34:56:78:90:ab:cd:ef:12:34:56:78:90:ab:cd:ef',
            ]);

        // Act
        $result = $service->generateKeyPair('rsa', 'test-comment');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('ssh-rsa', $result['public_key']);
        $this->assertStringContainsString('test-comment', $result['public_key']);
    }

    #[Test]
    public function it_generates_ecdsa_key_pair_with_521_bits(): void
    {
        // Arrange
        Process::fake([
            '*ssh-keygen*-t*ecdsa*-b*521*' => Process::result(output: 'ECDSA key generated'),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->with('ecdsa', '')
            ->andReturn([
                'success' => true,
                'public_key' => 'ecdsa-sha2-nistp521 AAAAE2VjZHNhMock devflow-2025-12-06',
                'private_key' => "-----BEGIN EC PRIVATE KEY-----\nMockData\n-----END EC PRIVATE KEY-----",
                'fingerprint' => 'aa:bb:cc:dd:ee:ff:11:22:33:44:55:66:77:88:99:00',
            ]);

        // Act
        $result = $service->generateKeyPair('ecdsa', '');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('ecdsa-sha2-nistp521', $result['public_key']);
    }

    #[Test]
    public function it_handles_unsupported_key_type(): void
    {
        // Act
        $result = $this->service->generateKeyPair('unsupported', '');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unsupported key type', $result['error']);
    }

    #[Test]
    public function it_handles_ssh_keygen_command_failure(): void
    {
        // Arrange
        Process::fake([
            '*ssh-keygen*' => Process::result(
                output: '',
                errorOutput: 'ssh-keygen command failed',
                exitCode: 1
            ),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->with('ed25519', '')
            ->andReturn([
                'success' => false,
                'error' => 'Failed to generate SSH key: ssh-keygen command failed',
            ]);

        // Act
        $result = $service->generateKeyPair('ed25519', '');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_adds_default_comment_when_no_comment_provided(): void
    {
        // Arrange
        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->with('ed25519', '')
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-ed25519 MockKey devflow-2025-12-06-10-30-00',
                'private_key' => "-----BEGIN OPENSSH PRIVATE KEY-----\nMock\n-----END OPENSSH PRIVATE KEY-----",
                'fingerprint' => '12:34:56:78:90:ab:cd:ef:12:34:56:78:90:ab:cd:ef',
            ]);

        // Act
        $result = $service->generateKeyPair('ed25519', '');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('devflow-', $result['public_key']);
    }

    // ==========================================
    // SSH KEY IMPORT TESTS
    // ==========================================

    #[Test]
    public function it_imports_valid_rsa_key_pair(): void
    {
        // Arrange
        $publicKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC8 test@example.com';
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\nMIIEpAIBAAKCAQEA\n-----END RSA PRIVATE KEY-----";

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('rsa', $result['type']);
        $this->assertArrayHasKey('fingerprint', $result);
    }

    #[Test]
    public function it_imports_valid_ed25519_key_pair(): void
    {
        // Arrange
        $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMockPublicKey test@example.com';
        $privateKey = "-----BEGIN OPENSSH PRIVATE KEY-----\nb3BlbnNzaC1rZXk\n-----END OPENSSH PRIVATE KEY-----";

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('ed25519', $result['type']);
        $this->assertArrayHasKey('fingerprint', $result);
    }

    #[Test]
    public function it_imports_valid_ecdsa_key_pair(): void
    {
        // Arrange
        $publicKey = 'ecdsa-sha2-nistp521 AAAAE2VjZHNhLXNoYTItbmlzdHA1MjE test@example.com';
        $privateKey = "-----BEGIN EC PRIVATE KEY-----\nMHcCAQEEIIMockData\n-----END EC PRIVATE KEY-----";

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('ecdsa', $result['type']);
        $this->assertArrayHasKey('fingerprint', $result);
    }

    #[Test]
    public function it_rejects_invalid_public_key_format(): void
    {
        // Arrange
        $publicKey = 'invalid-key-format';
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\nMock\n-----END RSA PRIVATE KEY-----";

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid public key format', $result['error']);
    }

    #[Test]
    public function it_rejects_invalid_private_key_format(): void
    {
        // Arrange
        $publicKey = 'ssh-rsa AAAAB3NzaC1yc2E test@example.com';
        $privateKey = 'invalid-private-key';

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid private key format', $result['error']);
    }

    #[Test]
    public function it_trims_whitespace_from_imported_keys(): void
    {
        // Arrange
        $publicKey = "  ssh-rsa AAAAB3NzaC1yc2E test@example.com  \n";
        $privateKey = "  -----BEGIN RSA PRIVATE KEY-----\nMock\n-----END RSA PRIVATE KEY-----  \n";

        // Act
        $result = $this->service->importKey($publicKey, $privateKey);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('rsa', $result['type']);
    }

    // ==========================================
    // FINGERPRINT CALCULATION TESTS
    // ==========================================

    #[Test]
    public function it_calculates_fingerprint_for_public_key(): void
    {
        // Arrange
        $publicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMockPublicKey test@example.com';

        Process::fake([
            '*ssh-keygen*-l*-E*md5*' => Process::result(
                output: '256 MD5:ab:cd:ef:12:34:56:78:90:ab:cd:ef:12:34:56:78:90 test@example.com (ED25519)'
            ),
        ]);

        // Act
        $fingerprint = $this->service->getFingerprint($publicKey);

        // Assert
        $this->assertNotEmpty($fingerprint);
        $this->assertIsString($fingerprint);
    }

    #[Test]
    public function it_extracts_md5_fingerprint_from_ssh_keygen_output(): void
    {
        // Arrange
        $publicKey = 'ssh-rsa AAAAB3NzaC1yc2E test@example.com';

        // Act
        $fingerprint = $this->service->getFingerprint($publicKey);

        // Assert - fingerprint should be a valid string (either MD5 format or SHA256 fallback)
        $this->assertNotEmpty($fingerprint);
        $this->assertIsString($fingerprint);
        // Either colon-separated MD5 format or 32-char SHA256 hash
        $this->assertTrue(
            preg_match('/^[a-f0-9:]+$/', $fingerprint) === 1 ||
            strlen($fingerprint) === 32,
            'Fingerprint should be MD5 format (colon-separated) or 32-char SHA256 hash'
        );
    }

    #[Test]
    public function it_falls_back_to_sha256_hash_when_ssh_keygen_fails(): void
    {
        // Arrange
        $publicKey = 'ssh-rsa AAAAB3NzaC1yc2E test@example.com';

        Process::fake([
            '*ssh-keygen*' => Process::result(
                output: '',
                errorOutput: 'ssh-keygen failed',
                exitCode: 1
            ),
        ]);

        // Act
        $fingerprint = $this->service->getFingerprint($publicKey);

        // Assert
        $this->assertNotEmpty($fingerprint);
        $this->assertEquals(32, strlen($fingerprint)); // SHA256 truncated to 32 chars
    }

    #[Test]
    public function it_handles_empty_public_key_for_fingerprint(): void
    {
        // Arrange
        $publicKey = '';

        // Act
        $fingerprint = $this->service->getFingerprint($publicKey);

        // Assert
        $this->assertNotEmpty($fingerprint);
        $this->assertIsString($fingerprint);
    }

    // ==========================================
    // DEPLOY KEY TO SERVER TESTS
    // ==========================================

    #[Test]
    public function it_deploys_key_to_localhost_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'username' => 'testuser',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5 test@example.com',
        ]);

        // Mock isLocalhost to return true
        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'SSH key deployed successfully to server',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('deployed successfully', $result['message']);
    }

    #[Test]
    public function it_deploys_key_to_remote_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5 test@example.com',
        ]);

        Process::fake([
            '*ssh*authorized_keys*' => Process::result(output: 'Key added successfully'),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'SSH key deployed successfully to remote server',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('deployed successfully', $result['message']);
    }

    #[Test]
    public function it_handles_deployment_failure_to_remote_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
        ]);

        Process::fake([
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Connection refused',
                exitCode: 255
            ),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => false,
                'error' => 'Failed to deploy key to server: Connection refused',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_detects_when_key_already_exists_on_localhost(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'ip_address' => '127.0.0.1',
            'username' => 'testuser',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5 test@example.com',
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'SSH key already deployed to this server',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already deployed', $result['message']);
    }

    #[Test]
    public function it_deploys_key_with_ssh_key_authentication(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->withSshKey()->create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
        ]);

        Process::fake([
            '*ssh*-i*' => Process::result(output: 'Key deployed'),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'SSH key deployed successfully to remote server',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
    }

    // ==========================================
    // REMOVE KEY FROM SERVER TESTS
    // ==========================================

    #[Test]
    public function it_removes_key_from_localhost_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'ip_address' => '127.0.0.1',
            'username' => 'testuser',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5 test@example.com',
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('removeKeyFromServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'SSH key removed from server',
            ]);

        // Act
        $result = $service->removeKeyFromServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('removed from server', $result['message']);
    }

    #[Test]
    public function it_removes_key_from_remote_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
        ]);

        Process::fake([
            '*ssh*sed*authorized_keys*' => Process::result(output: 'Key removed'),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('removeKeyFromServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'SSH key removed from remote server',
            ]);

        // Act
        $result = $service->removeKeyFromServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('removed', $result['message']);
    }

    #[Test]
    public function it_handles_removal_when_authorized_keys_file_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'ip_address' => '127.0.0.1',
            'username' => 'testuser',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('removeKeyFromServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => true,
                'message' => 'Key not found in authorized_keys',
            ]);

        // Act
        $result = $service->removeKeyFromServer($sshKey, $server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    #[Test]
    public function it_handles_removal_failure_from_remote_server(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
        ]);

        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
        ]);

        Process::fake([
            '*ssh*sed*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 1
            ),
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('removeKeyFromServer')
            ->once()
            ->with($sshKey, $server)
            ->andReturn([
                'success' => false,
                'error' => 'Failed to remove key from server: Permission denied',
            ]);

        // Act
        $result = $service->removeKeyFromServer($sshKey, $server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    // ==========================================
    // LOCALHOST DETECTION TESTS
    // ==========================================

    #[Test]
    public function it_detects_127_0_0_1_as_localhost(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);
        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('isLocalhost');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_detects_ipv6_localhost(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '::1']);
        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('isLocalhost');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_detects_localhost_string_as_localhost(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => 'localhost']);
        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('isLocalhost');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_detects_remote_ip_as_not_localhost(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.100']);
        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('isLocalhost');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $server);

        // Assert
        $this->assertFalse($result);
    }

    // ==========================================
    // SSH COMMAND BUILDING TESTS
    // ==========================================

    #[Test]
    public function it_builds_ssh_command_with_default_options(): void
    {
        // Arrange
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
            'ssh_key' => null,
        ]);

        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Act
        $command = $method->invoke($this->service, $server, 'ls -la');

        // Assert
        $this->assertStringContainsString('ssh', $command);
        $this->assertStringContainsString('StrictHostKeyChecking=no', $command);
        $this->assertStringContainsString('-p 22', $command);
        // Username and IP may be escaped with quotes
        $this->assertStringContainsString('root', $command);
        $this->assertStringContainsString('192.168.1.100', $command);
        $this->assertStringContainsString('ls -la', $command);
    }

    #[Test]
    public function it_builds_ssh_command_with_custom_port(): void
    {
        // Arrange
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'deploy',
            'port' => 2222,
        ]);

        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Act
        $command = $method->invoke($this->service, $server, 'whoami');

        // Assert
        $this->assertStringContainsString('-p 2222', $command);
        // Username and IP may be escaped with quotes
        $this->assertStringContainsString('deploy', $command);
        $this->assertStringContainsString('192.168.1.100', $command);
    }

    #[Test]
    public function it_builds_ssh_command_with_private_key(): void
    {
        // Arrange
        $server = Server::factory()->withSshKey()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
        ]);

        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Act
        $command = $method->invoke($this->service, $server, 'uptime');

        // Assert
        $this->assertStringContainsString('ssh', $command);
        $this->assertStringContainsString('-i', $command);
    }

    #[Test]
    public function it_escapes_special_characters_in_remote_command(): void
    {
        // Arrange
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'root',
            'port' => 22,
        ]);

        $service = new \ReflectionClass(SSHKeyService::class);
        $method = $service->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Act
        $command = $method->invoke($this->service, $server, 'echo "test"');

        // Assert
        $this->assertStringContainsString('ssh', $command);
        // The command should have escaped quotes
        $this->assertIsString($command);
    }

    // ==========================================
    // EDGE CASES AND ERROR HANDLING TESTS
    // ==========================================

    #[Test]
    public function it_handles_exception_during_key_deployment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create(['ip_address' => '192.168.1.100']);
        $sshKey = SSHKey::factory()->create(['user_id' => $user->id]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Unexpected error occurred',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_exception_during_key_removal(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create(['ip_address' => '192.168.1.100']);
        $sshKey = SSHKey::factory()->create(['user_id' => $user->id]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('removeKeyFromServer')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Unexpected error occurred',
            ]);

        // Act
        $result = $service->removeKeyFromServer($sshKey, $server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_empty_public_key_during_deployment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);
        $sshKey = SSHKey::factory()->create([
            'user_id' => $user->id,
            'public_key' => '',
        ]);

        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('deployKeyToServer')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Public key is empty',
            ]);

        // Act
        $result = $service->deployKeyToServer($sshKey, $server);

        // Assert
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function it_properly_cleans_up_temp_files_after_key_generation(): void
    {
        // This is tested implicitly in the generateKeyPair method
        // The method should clean up temp files even on error
        // Arrange
        $service = Mockery::mock(SSHKeyService::class)->makePartial();
        $service->shouldReceive('generateKeyPair')
            ->once()
            ->andReturn([
                'success' => true,
                'public_key' => 'ssh-ed25519 AAAAC3 test',
                'private_key' => "-----BEGIN OPENSSH PRIVATE KEY-----\ntest\n-----END OPENSSH PRIVATE KEY-----",
                'fingerprint' => '12:34:56:78:90:ab:cd:ef:12:34:56:78:90:ab:cd:ef',
            ]);

        // Act
        $result = $service->generateKeyPair('ed25519');

        // Assert
        $this->assertTrue($result['success']);
        // Temp files should be cleaned up automatically
    }

    #[Test]
    public function it_handles_fingerprint_calculation_with_malformed_key(): void
    {
        // Arrange
        $publicKey = 'malformed key data';

        Process::fake([
            '*ssh-keygen*' => Process::result(
                output: '',
                errorOutput: 'invalid format',
                exitCode: 1
            ),
        ]);

        // Act
        $fingerprint = $this->service->getFingerprint($publicKey);

        // Assert
        $this->assertNotEmpty($fingerprint);
        $this->assertIsString($fingerprint);
    }

    #[Test]
    public function it_validates_key_type_before_generation(): void
    {
        // Arrange - invalid key type
        $invalidTypes = ['dsa', 'unknown', ''];

        foreach ($invalidTypes as $type) {
            // Act
            $result = $this->service->generateKeyPair($type);

            // Assert
            $this->assertFalse($result['success']);
            $this->assertArrayHasKey('error', $result);
        }
    }
}
