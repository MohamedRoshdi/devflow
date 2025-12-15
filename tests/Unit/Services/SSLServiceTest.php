<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Services\SSLService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SSLServiceTest extends TestCase
{
    protected SSLService $sslService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sslService = new SSLService;
        Log::spy();
    }

    // ========================================
    // Certbot Installation Tests
    // ========================================

    #[Test]
    public function it_can_check_if_certbot_is_installed(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
        ]);

        $result = $this->sslService->checkCertbotInstalled($server);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_certbot_is_not_installed(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => Process::result('', 1),
        ]);

        $result = $this->sslService->checkCertbotInstalled($server);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_certbot_check_exception_gracefully(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $result = $this->sslService->checkCertbotInstalled($server);

        $this->assertFalse($result);
        Log::shouldHaveReceived('error')->once()->with(
            'Failed to check certbot installation',
            \Mockery::on(fn ($context) => $context['server_id'] === $server->id)
        );
    }

    #[Test]
    public function it_can_install_certbot_on_ubuntu_server(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*which certbot*' => Process::sequence()
                ->push(Process::result('', 1)) // First check: not installed
                ->push(Process::result('/usr/bin/certbot', 0)), // Second check: installed
            '*apt-get update*' => Process::result('Updated', 0),
            '*apt-get install*' => Process::result('Installed certbot', 0),
            '*certbot --version*' => Process::result('certbot 2.7.4', 0),
        ]);

        $result = $this->sslService->installCertbot($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('Certbot installed successfully', $result['message']);
        $this->assertArrayHasKey('output', $result);
    }

    #[Test]
    public function it_returns_success_when_certbot_is_already_installed(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
        ]);

        $result = $this->sslService->installCertbot($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('Certbot is already installed', $result['message']);
    }

    #[Test]
    public function it_handles_certbot_installation_failure(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*which certbot*' => Process::result('', 1),
            '*' => Process::result('Installation failed: Permission denied', 1),
        ]);

        $result = $this->sslService->installCertbot($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to install certbot', $result['message']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_certbot_installation_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $result = $this->sslService->installCertbot($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Installation failed', $result['message']);
    }

    // ========================================
    // Certificate Issuance Tests
    // ========================================

    #[Test]
    public function it_can_issue_ssl_certificate_successfully(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $domain = 'example.com';
        $email = 'admin@example.com';

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
            '*certbot certonly*' => Process::result('Successfully received certificate', 0),
            '*openssl x509*' => Process::result('notAfter=Nov 28 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->issueCertificate($server, $domain, $email);

        $this->assertTrue($result['success']);
        $this->assertEquals('SSL certificate issued successfully', $result['message']);
        $this->assertInstanceOf(SSLCertificate::class, $result['certificate']);
        $this->assertEquals($domain, $result['certificate']->domain_name);
        $this->assertEquals('letsencrypt', $result['certificate']->provider);
        $this->assertEquals('issued', $result['certificate']->status);
        $this->assertTrue($result['certificate']->auto_renew);

        $this->assertDatabaseHas('ssl_certificates', [
            'server_id' => $server->id,
            'domain_name' => $domain,
            'status' => 'issued',
            'provider' => 'letsencrypt',
        ]);
    }

    #[Test]
    public function it_installs_certbot_before_issuing_certificate_if_not_installed(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*which certbot*' => Process::sequence()
                ->push(Process::result('', 1)) // Not installed
                ->push(Process::result('/usr/bin/certbot', 0)) // After install
                ->push(Process::result('/usr/bin/certbot', 0)), // Before certonly
            '*apt-get*' => Process::result('Installed', 0),
            '*certbot --version*' => Process::result('certbot 2.7.4', 0),
            '*certbot certonly*' => Process::result('Successfully received certificate', 0),
            '*openssl x509*' => Process::result('notAfter=Nov 28 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_returns_error_when_certbot_installation_fails_before_issuance(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*which certbot*' => Process::result('', 1),
            '*' => Process::result('Installation error', 1),
        ]);

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to install certbot', $result['message']);
    }

    #[Test]
    public function it_handles_certificate_issuance_failure(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
            '*certbot certonly*' => Process::result('Error: Domain validation failed', 1),
        ]);

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to issue certificate', $result['message']);
        $this->assertArrayHasKey('error', $result);

        // Should create a failed certificate record
        $this->assertDatabaseHas('ssl_certificates', [
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_updates_existing_certificate_on_reissuance(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $domain = 'example.com';

        // Create existing certificate
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => $domain,
            'status' => 'expired',
        ]);

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
            '*certbot certonly*' => Process::result('Successfully received certificate', 0),
            '*openssl x509*' => Process::result('notAfter=Nov 28 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->issueCertificate($server, $domain, 'admin@example.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('issued', $result['certificate']->status);

        // Should only have one certificate record for this domain
        $this->assertEquals(1, SSLCertificate::where('domain_name', $domain)->count());
    }

    #[Test]
    public function it_uses_sudo_for_non_root_users(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'deploy',
            'ssh_password' => null,
        ]);

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
            '*sudo certbot certonly*' => Process::result('Successfully received certificate', 0),
            '*sudo openssl x509*' => Process::result('notAfter=Nov 28 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_certificate_issuance_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*which certbot*' => function () {
                throw new \Exception('Unexpected error');
            },
        ]);

        $result = $this->sslService->issueCertificate($server, 'example.com', 'admin@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Certificate issuance failed', $result['message']);
    }

    // ========================================
    // Certificate Renewal Tests
    // ========================================

    #[Test]
    public function it_can_renew_ssl_certificate_successfully(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'expires_at' => now()->addDays(10),
            'status' => 'issued',
        ]);

        Process::fake([
            '*certbot renew*' => Process::result('Certificate successfully renewed', 0),
            '*openssl x509*' => Process::result('notAfter=Apr 01 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertTrue($result['success']);
        $this->assertEquals('Certificate renewed successfully', $result['message']);

        $certificate->refresh();
        $this->assertEquals('issued', $certificate->status);
        $this->assertNotNull($certificate->last_renewal_attempt);
        $this->assertNull($certificate->renewal_error);
    }

    #[Test]
    public function it_handles_certificate_not_yet_due_for_renewal(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'expires_at' => now()->addDays(60),
            'status' => 'issued',
        ]);

        Process::fake([
            '*certbot renew*' => Process::result('Cert not yet due for renewal', 0),
            '*openssl x509*' => Process::result('notAfter=Apr 01 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Certificate renewed successfully', $result['message']);
    }

    #[Test]
    public function it_handles_certificate_renewal_failure(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
        ]);

        Process::fake([
            '*certbot renew*' => Process::result('Renewal failed: DNS validation error', 1),
        ]);

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to renew certificate', $result['message']);

        $certificate->refresh();
        $this->assertEquals('failed', $certificate->status);
        $this->assertNotNull($certificate->renewal_error);
    }

    #[Test]
    public function it_updates_last_renewal_attempt_timestamp(): void
    {
        Carbon::setTestNow('2026-01-15 14:30:00');

        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'last_renewal_attempt' => null,
        ]);

        Process::fake([
            '*certbot renew*' => Process::result('Certificate successfully renewed', 0),
            '*openssl x509*' => Process::result('notAfter=Apr 01 12:00:00 2026 GMT', 0),
        ]);

        $this->sslService->renewCertificate($certificate);

        $certificate->refresh();
        $this->assertNotNull($certificate->last_renewal_attempt);
        $this->assertEquals('2026-01-15 14:30:00', $certificate->last_renewal_attempt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_certificate_renewal_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
        ]);

        Process::fake([
            '*certbot renew*' => function () {
                throw new \Exception('Connection lost');
            },
        ]);

        $result = $this->sslService->renewCertificate($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Renewal failed', $result['message']);

        $certificate->refresh();
        $this->assertEquals('failed', $certificate->status);
        $this->assertStringContainsString('Connection lost', $certificate->renewal_error);
    }

    // ========================================
    // Certificate Revocation Tests
    // ========================================

    #[Test]
    public function it_can_revoke_ssl_certificate_successfully(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'status' => 'issued',
        ]);

        Process::fake([
            '*certbot revoke*' => Process::result('Certificate revoked successfully', 0),
        ]);

        $result = $this->sslService->revokeCertificate($certificate);

        $this->assertTrue($result['success']);
        $this->assertEquals('Certificate revoked successfully', $result['message']);

        $certificate->refresh();
        $this->assertEquals('revoked', $certificate->status);
    }

    #[Test]
    public function it_handles_certificate_revocation_failure(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
        ]);

        Process::fake([
            '*certbot revoke*' => Process::result('Error: Certificate not found', 1),
        ]);

        $result = $this->sslService->revokeCertificate($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to revoke certificate', $result['message']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_uses_sudo_for_non_root_users_on_revocation(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'deploy',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
        ]);

        Process::fake([
            '*sudo certbot revoke*' => Process::result('Certificate revoked', 0),
        ]);

        $result = $this->sslService->revokeCertificate($certificate);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_certificate_revocation_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
        ]);

        Process::fake([
            '*certbot revoke*' => function () {
                throw new \Exception('Process timeout');
            },
        ]);

        $result = $this->sslService->revokeCertificate($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Revocation failed', $result['message']);
    }

    // ========================================
    // Certificate Status Check Tests
    // ========================================

    #[Test]
    public function it_can_check_certificate_status_and_update_expiry(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => 'example.com',
            'status' => 'issued',
            'expires_at' => now()->addDays(30),
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('notAfter=Mar 01 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->checkCertificateStatus($certificate);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['valid']);
        $this->assertInstanceOf(Carbon::class, $result['expires_at']);

        $certificate->refresh();
        $this->assertEquals('issued', $certificate->status);
    }

    #[Test]
    public function it_marks_certificate_as_expired_when_checking_status(): void
    {
        Carbon::setTestNow('2026-06-01 12:00:00');

        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('notAfter=Apr 01 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->checkCertificateStatus($certificate);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['valid']);

        $certificate->refresh();
        $this->assertEquals('expired', $certificate->status);
    }

    #[Test]
    public function it_handles_certificate_not_found_during_status_check(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('NOT_FOUND', 1),
        ]);

        $result = $this->sslService->checkCertificateStatus($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Certificate not found or invalid', $result['message']);
    }

    #[Test]
    public function it_handles_certificate_status_check_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
        ]);

        Process::fake([
            '*openssl x509*' => function () {
                throw new \Exception('SSH connection failed');
            },
        ]);

        $result = $this->sslService->checkCertificateStatus($certificate);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to check certificate status', $result['message']);
    }

    // ========================================
    // Certificate Info Retrieval Tests
    // ========================================

    #[Test]
    public function it_can_get_certificate_info_with_expiry_date(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('notAfter=Nov 28 12:00:00 2026 GMT', 0),
        ]);

        $result = $this->sslService->getCertificateInfo($server, 'example.com');

        $this->assertTrue($result['found']);
        $this->assertInstanceOf(Carbon::class, $result['expires_at']);
        $this->assertArrayHasKey('days_until_expiry', $result);
    }

    #[Test]
    public function it_returns_not_found_when_certificate_does_not_exist(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('NOT_FOUND', 1),
        ]);

        $result = $this->sslService->getCertificateInfo($server, 'nonexistent.com');

        $this->assertFalse($result['found']);
    }

    #[Test]
    public function it_handles_invalid_certificate_format(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*openssl x509*' => Process::result('Invalid certificate format', 0),
        ]);

        $result = $this->sslService->getCertificateInfo($server, 'example.com');

        $this->assertFalse($result['found']);
    }

    #[Test]
    public function it_handles_get_certificate_info_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*openssl x509*' => function () {
                throw new \Exception('Connection error');
            },
        ]);

        $result = $this->sslService->getCertificateInfo($server, 'example.com');

        $this->assertFalse($result['found']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Connection error', $result['error']);
    }

    // ========================================
    // Auto-Renewal Setup Tests
    // ========================================

    #[Test]
    public function it_detects_auto_renewal_already_configured_via_systemd(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl status certbot.timer*' => Process::result('active (running)', 0),
        ]);

        $result = $this->sslService->setupAutoRenewal($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('Auto-renewal is already configured', $result['message']);
    }

    #[Test]
    public function it_detects_auto_renewal_already_configured_via_cron(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl status*' => Process::result('NOT_CONFIGURED', 1),
            '*crontab -l*' => Process::result('0 2 * * * certbot renew --quiet', 0),
        ]);

        $result = $this->sslService->setupAutoRenewal($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('Auto-renewal is already configured', $result['message']);
    }

    #[Test]
    public function it_can_setup_auto_renewal_cron_job(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl status*' => Process::result('NOT_CONFIGURED', 1),
            '*crontab -l*' => Process::result('# Some other cron', 0),
            '*(crontab -l*' => Process::result('Crontab updated', 0),
        ]);

        $result = $this->sslService->setupAutoRenewal($server);

        $this->assertTrue($result['success']);
        $this->assertEquals('Auto-renewal configured successfully', $result['message']);
    }

    #[Test]
    public function it_handles_auto_renewal_setup_failure(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);

        Process::fake([
            '*systemctl status*' => Process::result('NOT_CONFIGURED', 1),
            '*crontab -l*' => Process::result('', 0),
            '*(crontab -l*' => Process::result('Permission denied', 1),
        ]);

        $result = $this->sslService->setupAutoRenewal($server);

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to configure auto-renewal', $result['message']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_handles_auto_renewal_setup_exception(): void
    {
        $server = Server::factory()->withSshKey()->create();

        Process::fake([
            '*systemctl status*' => function () {
                throw new \Exception('Timeout error');
            },
        ]);

        $result = $this->sslService->setupAutoRenewal($server);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to setup auto-renewal', $result['message']);
    }

    // ========================================
    // SSH Command Building Tests
    // ========================================

    #[Test]
    public function it_builds_ssh_command_with_password_authentication(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'deploy',
            'port' => 22,
            'ssh_password' => 'secret123',
            'ssh_key' => null,
        ]);

        Process::fake([
            '*sshpass*' => Process::result('Command executed', 0),
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $command = $method->invoke($this->sslService, $server, 'echo test');

        $this->assertStringContainsString('sshpass', $command);
        $this->assertStringContainsString('deploy@192.168.1.100', $command);
        $this->assertStringContainsString('-p 22', $command);
    }

    #[Test]
    public function it_builds_ssh_command_with_key_authentication(): void
    {
        $server = Server::factory()->withSshKey()->create([
            'ip_address' => '192.168.1.100',
            'username' => 'deploy',
            'port' => 2222,
            'ssh_password' => null,
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $command = $method->invoke($this->sslService, $server, 'echo test');

        $this->assertStringNotContainsString('sshpass', $command);
        $this->assertStringContainsString('deploy@192.168.1.100', $command);
        $this->assertStringContainsString('-p 2222', $command);
        $this->assertStringContainsString('BatchMode=yes', $command);
    }

    #[Test]
    public function it_uses_base64_encoding_for_long_scripts(): void
    {
        $server = Server::factory()->withSshKey()->create();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        // Create a long script (over 500 characters)
        $longScript = str_repeat('echo "test" && ', 50);

        $command = $method->invoke($this->sslService, $server, $longScript);

        $this->assertStringContainsString('base64', $command);
    }

    #[Test]
    public function it_suppresses_warnings_when_requested(): void
    {
        $server = Server::factory()->withSshKey()->create();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('buildSSHCommand');
        $method->setAccessible(true);

        $command = $method->invoke($this->sslService, $server, 'echo test', true);

        $this->assertStringContainsString('2>/dev/null', $command);
    }

    // ========================================
    // Sudo Prefix Tests
    // ========================================

    #[Test]
    public function it_returns_empty_sudo_prefix_for_root_user(): void
    {
        $server = Server::factory()->create([
            'username' => 'root',
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('getSudoPrefix');
        $method->setAccessible(true);

        $prefix = $method->invoke($this->sslService, $server);

        $this->assertEquals('', $prefix);
    }

    #[Test]
    public function it_returns_sudo_with_password_for_non_root_user_with_password(): void
    {
        $server = Server::factory()->create([
            'username' => 'deploy',
            'ssh_password' => 'secret123',
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('getSudoPrefix');
        $method->setAccessible(true);

        $prefix = $method->invoke($this->sslService, $server);

        $this->assertStringContainsString('sudo -S', $prefix);
        $this->assertStringContainsString('echo', $prefix);
    }

    #[Test]
    public function it_returns_plain_sudo_for_non_root_user_without_password(): void
    {
        $server = Server::factory()->create([
            'username' => 'deploy',
            'ssh_password' => null,
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('getSudoPrefix');
        $method->setAccessible(true);

        $prefix = $method->invoke($this->sslService, $server);

        $this->assertEquals('sudo ', $prefix);
    }

    #[Test]
    public function it_escapes_special_characters_in_sudo_password(): void
    {
        $server = Server::factory()->create([
            'username' => 'deploy',
            'ssh_password' => "pass'word",
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->sslService);
        $method = $reflection->getMethod('getSudoPrefix');
        $method->setAccessible(true);

        $prefix = $method->invoke($this->sslService, $server);

        $this->assertStringContainsString('sudo -S', $prefix);
    }

    // ========================================
    // Integration Tests
    // ========================================

    #[Test]
    public function it_handles_complete_certificate_lifecycle(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $server = Server::factory()->withSshKey()->create([
            'username' => 'root',
        ]);
        $domain = 'example.com';
        $email = 'admin@example.com';

        Process::fake([
            '*which certbot*' => Process::result('/usr/bin/certbot', 0),
            '*certbot certonly*' => Process::result('Successfully received certificate', 0),
            '*openssl x509*' => Process::sequence()
                ->push(Process::result('notAfter=Apr 01 12:00:00 2026 GMT', 0))
                ->push(Process::result('notAfter=Jul 01 12:00:00 2026 GMT', 0))
                ->push(Process::result('notAfter=Jul 01 12:00:00 2026 GMT', 0)),
            '*certbot renew*' => Process::result('Certificate successfully renewed', 0),
            '*certbot revoke*' => Process::result('Certificate revoked', 0),
        ]);

        // Issue certificate
        $issueResult = $this->sslService->issueCertificate($server, $domain, $email);
        $this->assertTrue($issueResult['success']);
        $certificate = $issueResult['certificate'];

        // Check status
        $statusResult = $this->sslService->checkCertificateStatus($certificate);
        $this->assertTrue($statusResult['success']);
        $this->assertTrue($statusResult['valid']);

        // Renew certificate
        $renewResult = $this->sslService->renewCertificate($certificate);
        $this->assertTrue($renewResult['success']);

        // Revoke certificate
        $revokeResult = $this->sslService->revokeCertificate($certificate);
        $this->assertTrue($revokeResult['success']);

        $certificate->refresh();
        $this->assertEquals('revoked', $certificate->status);
    }
}
