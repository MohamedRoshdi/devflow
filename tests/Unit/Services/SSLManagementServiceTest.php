<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Domain;
use App\Models\Project;
use App\Models\SSLCertificate;
use App\Models\User;
use App\Notifications\SSLCertificateRenewed;
use App\Services\SSLManagementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Tests\Traits\CreatesServers;
use Tests\Traits\MocksSSH;

class SSLManagementServiceTest extends TestCase
{
    use CreatesServers, MocksSSH, RefreshDatabase;

    protected SSLManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SSLManagementService;
        Log::spy();
        Notification::fake();
    }

    #[Test]
    public function it_successfully_issues_new_ssl_certificate(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
            'ssl_enabled' => false,
        ]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $result = $this->service->issueCertificate($domain);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('ssl_certificates', [
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'domain_name' => 'example.com',
            'provider' => 'letsencrypt',
            'status' => 'issued',
        ]);
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'ssl_enabled' => true,
            'ssl_provider' => 'letsencrypt',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_installs_certbot_if_not_present(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'test.com',
        ]);

        $this->mockCertbotInstallation();

        // Act
        $result = $this->service->issueCertificate($domain);

        // Assert
        $this->assertTrue($result);
        Process::assertRan(function ($command) {
            return str_contains($command, 'command -v certbot');
        });
        Process::assertRan(function ($command) {
            return str_contains($command, 'apt-get install -y certbot python3-certbot-nginx');
        });
    }

    #[Test]
    public function it_uses_user_email_for_certificate_registration(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@domain.com']);
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'secure.com',
        ]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'certbot certonly') &&
                   str_contains($command, '-m user@domain.com');
        });
    }

    #[Test]
    public function it_parses_certificate_expiry_date_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        $expiryDate = now()->addDays(90)->format('M d H:i:s Y T');
        $this->mockSuccessfulCertificateIssuance($expiryDate);

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        $certificate = SSLCertificate::where('domain_id', $domain->id)->first();
        $this->assertNotNull($certificate);
        $this->assertNotNull($certificate->expires_at);
        $this->assertInstanceOf(Carbon::class, $certificate->expires_at);
    }

    #[Test]
    public function it_enables_auto_renewal_by_default(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        $this->assertDatabaseHas('ssl_certificates', [
            'domain_id' => $domain->id,
            'auto_renew' => true,
        ]);
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'auto_renew_ssl' => true,
        ]);
    }

    #[Test]
    public function it_throws_exception_when_domain_has_no_server(): void
    {
        // Arrange
        $domain = new Domain;
        $domain->domain = 'noserver.com';

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Domain has no associated server');
        $this->service->issueCertificate($domain);
    }

    #[Test]
    public function it_handles_certificate_issuance_failure_gracefully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'fail.com',
        ]);

        $this->mockFailedCertificateIssuance('Certificate validation failed');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->issueCertificate($domain);

        $this->assertDatabaseHas('ssl_certificates', [
            'domain_id' => $domain->id,
            'status' => 'failed',
        ]);
    }

    #[Test]
    public function it_logs_certificate_issuance_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockFailedCertificateIssuance('DNS validation failed');

        // Act
        try {
            $this->service->issueCertificate($domain);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to issue SSL certificate', \Mockery::on(function ($context) use ($domain) {
                return $context['domain_id'] === $domain->id &&
                       $context['domain'] === $domain->domain;
            }));
    }

    #[Test]
    public function it_successfully_renews_existing_certificate(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'renew.com',
            'ssl_enabled' => true,
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'domain_name' => 'renew.com',
            'status' => 'issued',
            'expires_at' => now()->addDays(20),
        ]);

        $newExpiryDate = now()->addDays(90)->format('M d H:i:s Y T');
        $this->mockSuccessfulCertificateRenewal($newExpiryDate);

        // Act
        $result = $this->service->renewCertificate($domain);

        // Assert
        $this->assertTrue($result);
        $certificate->refresh();
        $this->assertEquals('issued', $certificate->status);
        $this->assertNull($certificate->renewal_error);
    }

    #[Test]
    public function it_updates_last_renewal_attempt_timestamp(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'last_renewal_attempt' => null,
        ]);

        $this->mockSuccessfulCertificateRenewal();

        // Act
        $this->service->renewCertificate($domain);

        // Assert
        $certificate->refresh();
        $this->assertNotNull($certificate->last_renewal_attempt);
    }

    #[Test]
    public function it_sends_notification_after_successful_renewal(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
        ]);

        $this->mockSuccessfulCertificateRenewal();

        // Act
        $this->service->renewCertificate($domain);

        // Assert
        Notification::assertSentTo($user, SSLCertificateRenewed::class);
    }

    #[Test]
    public function it_throws_exception_when_renewing_nonexistent_certificate(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No SSL certificate found for domain');
        $this->service->renewCertificate($domain);
    }

    #[Test]
    public function it_handles_certificate_renewal_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
        ]);

        $this->mockFailedCertificateRenewal('Renewal rate limit exceeded');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->renewCertificate($domain);

        $certificate->refresh();
        $this->assertEquals('failed', $certificate->status);
        $this->assertNotNull($certificate->renewal_error);
    }

    #[Test]
    public function it_logs_certificate_renewal_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
        ]);

        $this->mockFailedCertificateRenewal('Server unreachable');

        // Act
        try {
            $this->service->renewCertificate($domain);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to renew SSL certificate', \Mockery::on(function ($context) use ($domain, $certificate) {
                return $context['domain_id'] === $domain->id &&
                       $context['certificate_id'] === $certificate->id;
            }));
    }

    #[Test]
    public function it_checks_certificate_expiry_date(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'check.com',
            'ssl_enabled' => true,
        ]);

        $expiryDate = now()->addDays(60)->format('M d H:i:s Y T');
        $this->mockCertificateExpiryCheck($expiryDate);

        // Act
        $result = $this->service->checkExpiry($domain);

        // Assert
        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEqualsWithDelta(60, $result->diffInDays(now()), 1);
    }

    #[Test]
    public function it_returns_null_when_checking_expiry_for_disabled_ssl(): void
    {
        // Arrange
        $domain = Domain::factory()->create(['ssl_enabled' => false]);

        // Act
        $result = $this->service->checkExpiry($domain);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_expiry_check_fails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $this->mockFailedExpiryCheck();

        // Act
        $result = $this->service->checkExpiry($domain);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_logs_warning_when_expiry_check_fails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $this->mockFailedExpiryCheck();

        // Act
        $this->service->checkExpiry($domain);

        // Assert
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Failed to check SSL certificate expiry', \Mockery::any());
    }

    #[Test]
    public function it_sets_up_auto_renewal_cron_job(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockAutoRenewalSetup();

        // Act
        $result = $this->service->setupAutoRenewal($server);

        // Assert
        $this->assertTrue($result);
        Process::assertRan(function ($command) {
            return str_contains($command, 'crontab -l');
        });
        Process::assertRan(function ($command) {
            return str_contains($command, '0 3 * * * /usr/bin/certbot renew');
        });
    }

    #[Test]
    public function it_skips_cron_setup_if_already_exists(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockExistingAutoRenewal();

        // Act
        $result = $this->service->setupAutoRenewal($server);

        // Assert
        $this->assertTrue($result);
        Process::assertRan(function ($command) {
            return str_contains($command, 'crontab -l');
        });
        Process::assertNotRan(function ($command) {
            return str_contains($command, 'echo') && str_contains($command, 'certbot renew');
        });
    }

    #[Test]
    public function it_throws_exception_when_auto_renewal_setup_fails(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $this->mockFailedAutoRenewalSetup();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->setupAutoRenewal($server);
    }

    #[Test]
    public function it_retrieves_expiring_certificates(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        // Expiring soon (25 days)
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(25),
        ]);

        // Expiring soon (15 days)
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(15),
        ]);

        // Not expiring soon (60 days)
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(60),
        ]);

        // Auto-renew disabled
        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => false,
            'expires_at' => now()->addDays(10),
        ]);

        // Act
        $expiringCerts = $this->service->getExpiringCertificates(30);

        // Assert
        $this->assertInstanceOf(Collection::class, $expiringCerts);
        $this->assertCount(2, $expiringCerts);
    }

    #[Test]
    public function it_includes_null_expiry_dates_in_expiring_certificates(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => null,
        ]);

        // Act
        $expiringCerts = $this->service->getExpiringCertificates(30);

        // Assert
        $this->assertCount(1, $expiringCerts);
    }

    #[Test]
    public function it_orders_expiring_certificates_by_expiry_date(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        $cert1 = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(25),
        ]);

        $cert2 = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(5),
        ]);

        // Act
        $expiringCerts = $this->service->getExpiringCertificates(30);

        // Assert
        $this->assertEquals($cert2->id, $expiringCerts->first()->id);
        $this->assertEquals($cert1->id, $expiringCerts->last()->id);
    }

    #[Test]
    public function it_bulk_renews_expiring_certificates_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain1 = Domain::factory()->create(['project_id' => $project->id]);
        $domain2 = Domain::factory()->create(['project_id' => $project->id]);

        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain1->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(20),
        ]);

        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain2->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(15),
        ]);

        $this->mockSuccessfulCertificateRenewal();

        // Act
        $results = $this->service->renewExpiringCertificates(30);

        // Assert
        $this->assertCount(2, $results['success']);
        $this->assertCount(0, $results['failed']);
    }

    #[Test]
    public function it_tracks_failed_renewals_in_bulk_operation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create(['project_id' => $project->id]);

        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'status' => 'issued',
            'auto_renew' => true,
            'expires_at' => now()->addDays(20),
        ]);

        $this->mockFailedCertificateRenewal('Rate limit exceeded');

        // Act
        $results = $this->service->renewExpiringCertificates(30);

        // Assert
        $this->assertCount(0, $results['success']);
        $this->assertCount(1, $results['failed']);
        $this->assertArrayHasKey('error', $results['failed'][0]);
    }

    #[Test]
    public function it_successfully_revokes_certificate(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'revoke.com',
            'ssl_enabled' => true,
        ]);
        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_id' => $domain->id,
            'status' => 'issued',
        ]);

        $this->mockSuccessfulCertificateRevocation();

        // Act
        $result = $this->service->revokeCertificate($domain);

        // Assert
        $this->assertTrue($result);
        $certificate->refresh();
        $this->assertEquals('revoked', $certificate->status);
        $domain->refresh();
        $this->assertFalse($domain->ssl_enabled);
        $this->assertEquals('inactive', $domain->status);
    }

    #[Test]
    public function it_throws_exception_when_revoking_certificate_fails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockFailedCertificateRevocation('Certificate not found');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->service->revokeCertificate($domain);
    }

    #[Test]
    public function it_logs_certificate_revocation_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockFailedCertificateRevocation('Revocation failed');

        // Act
        try {
            $this->service->revokeCertificate($domain);
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to revoke SSL certificate', \Mockery::any());
    }

    #[Test]
    public function it_retrieves_certificate_information(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'info.com',
            'ssl_enabled' => true,
        ]);

        $this->mockCertificateInfo();

        // Act
        $info = $this->service->getCertificateInfo($domain);

        // Assert
        $this->assertIsArray($info);
        $this->assertArrayHasKey('domain', $info);
        $this->assertArrayHasKey('issuer', $info);
        $this->assertArrayHasKey('subject', $info);
        $this->assertArrayHasKey('valid_from', $info);
        $this->assertArrayHasKey('valid_until', $info);
        $this->assertEquals('info.com', $info['domain']);
    }

    #[Test]
    public function it_returns_null_when_certificate_info_unavailable(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $this->mockFailedCertificateInfo();

        // Act
        $info = $this->service->getCertificateInfo($domain);

        // Assert
        $this->assertNull($info);
    }

    #[Test]
    public function it_uses_ssh_key_authentication_when_available(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer([
            'user_id' => $user->id,
            'ssh_key' => '-----BEGIN PRIVATE KEY-----test-----END PRIVATE KEY-----',
            'ssh_password' => null,
        ]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'ssh') && str_contains($command, '-i ');
        });
    }

    #[Test]
    public function it_uses_password_authentication_when_no_key_available(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer([
            'user_id' => $user->id,
            'ssh_key' => null,
            'ssh_password' => 'test_password',
        ]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'sshpass -p');
        });
    }

    #[Test]
    public function it_uses_correct_ssh_port(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer([
            'user_id' => $user->id,
            'port' => 2222,
        ]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, '-p 2222');
        });
    }

    #[Test]
    public function it_includes_ssh_security_options(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'StrictHostKeyChecking=no') &&
                   str_contains($command, 'UserKnownHostsFile=/dev/null');
        });
    }

    #[Test]
    public function it_handles_ssh_timeout_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'ConnectTimeout=30');
        });
    }

    #[Test]
    public function it_executes_certbot_with_nginx_plugin(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'nginx-test.com',
        ]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, 'certbot certonly --nginx');
        });
    }

    #[Test]
    public function it_uses_non_interactive_mode_for_certbot(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        Process::assertRan(function ($command) {
            return str_contains($command, '--non-interactive') &&
                   str_contains($command, '--agree-tos');
        });
    }

    #[Test]
    public function it_stores_certificate_paths_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'paths.com',
        ]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        $this->assertDatabaseHas('ssl_certificates', [
            'domain_name' => 'paths.com',
            'certificate_path' => '/etc/letsencrypt/live/paths.com/cert.pem',
            'private_key_path' => '/etc/letsencrypt/live/paths.com/privkey.pem',
            'chain_path' => '/etc/letsencrypt/live/paths.com/chain.pem',
        ]);
    }

    #[Test]
    public function it_updates_domain_ssl_timestamps(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_issued_at' => null,
            'ssl_expires_at' => null,
        ]);

        $this->mockSuccessfulCertificateIssuance();

        // Act
        $this->service->issueCertificate($domain);

        // Assert
        $domain->refresh();
        $this->assertNotNull($domain->ssl_issued_at);
        $this->assertNotNull($domain->ssl_expires_at);
    }

    #[Test]
    public function it_handles_certbot_installation_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $server = $this->createOnlineServer(['user_id' => $user->id]);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create(['project_id' => $project->id]);

        $this->mockFailedCertbotInstallation();

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to install certbot');
        $this->service->issueCertificate($domain);
    }

    #[Test]
    public function it_skips_domains_without_auto_renew_in_expiring_list(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'status' => 'issued',
            'auto_renew' => false,
            'expires_at' => now()->addDays(5),
        ]);

        // Act
        $expiringCerts = $this->service->getExpiringCertificates(30);

        // Assert
        $this->assertCount(0, $expiringCerts);
    }

    #[Test]
    public function it_extracts_certificate_field_information(): void
    {
        // Arrange
        $output = "Issuer: CN=Let's Encrypt Authority X3\nSubject: CN=example.com\nNot Before: Jan 1 00:00:00 2025 GMT";

        // Act (testing protected method via reflection)
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractFromCertificate');
        $method->setAccessible(true);

        $issuer = $method->invoke($this->service, $output, 'Issuer:');
        $subject = $method->invoke($this->service, $output, 'Subject:');

        // Assert
        $this->assertEquals("CN=Let's Encrypt Authority X3", $issuer);
        $this->assertEquals('CN=example.com', $subject);
    }

    #[Test]
    public function it_returns_null_when_field_not_found_in_certificate(): void
    {
        // Arrange
        $output = "Issuer: CN=Test\nSubject: CN=example.com";

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractFromCertificate');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $output, 'NotFound:');

        // Assert
        $this->assertNull($result);
    }

    /**
     * Mock successful certificate issuance
     */
    protected function mockSuccessfulCertificateIssuance(?string $expiryDate = null): void
    {
        if ($expiryDate === null) {
            $expiryDate = now()->addDays(90)->format('M d H:i:s Y T');
        }

        Process::fake([
            '*command -v certbot*' => Process::result(output: 'installed'),
            '*certbot certonly*' => Process::result(output: 'Certificate successfully obtained'),
            '*openssl x509 -enddate*' => Process::result(output: "notAfter={$expiryDate}"),
            '*ssh*' => Process::result(output: 'Success'),
        ]);
    }

    /**
     * Mock certbot installation
     */
    protected function mockCertbotInstallation(): void
    {
        Process::fake([
            '*command -v certbot*' => Process::result(output: 'not_installed'),
            '*apt-get update*' => Process::result(output: 'Updated'),
            '*apt-get install*' => Process::result(output: 'Installed'),
            '*certbot certonly*' => Process::result(output: 'Certificate obtained'),
            '*openssl x509*' => Process::result(output: 'notAfter='.now()->addDays(90)->format('M d H:i:s Y T')),
            '*ssh*' => Process::result(output: 'Success'),
        ]);
    }

    /**
     * Mock failed certificate issuance
     */
    protected function mockFailedCertificateIssuance(string $error = 'Issuance failed'): void
    {
        Process::fake([
            '*command -v certbot*' => Process::result(output: 'installed'),
            '*certbot certonly*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock successful certificate renewal
     */
    protected function mockSuccessfulCertificateRenewal(?string $expiryDate = null): void
    {
        if ($expiryDate === null) {
            $expiryDate = now()->addDays(90)->format('M d H:i:s Y T');
        }

        Process::fake([
            '*certbot renew*' => Process::result(output: 'Certificate renewed'),
            '*openssl x509 -enddate*' => Process::result(output: "notAfter={$expiryDate}"),
            '*ssh*' => Process::result(output: 'Success'),
        ]);
    }

    /**
     * Mock failed certificate renewal
     */
    protected function mockFailedCertificateRenewal(string $error = 'Renewal failed'): void
    {
        Process::fake([
            '*certbot renew*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock certificate expiry check
     */
    protected function mockCertificateExpiryCheck(string $expiryDate): void
    {
        Process::fake([
            '*openssl x509 -enddate*' => Process::result(output: "notAfter={$expiryDate}"),
            '*ssh*' => Process::result(output: "notAfter={$expiryDate}"),
        ]);
    }

    /**
     * Mock failed expiry check
     */
    protected function mockFailedExpiryCheck(): void
    {
        Process::fake([
            '*openssl x509*' => Process::result(
                output: '',
                errorOutput: 'Certificate not found',
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Certificate not found',
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock auto-renewal setup
     */
    protected function mockAutoRenewalSetup(): void
    {
        Process::fake([
            '*crontab -l*grep*' => Process::result(output: 'not_found'),
            '*crontab -l*echo*' => Process::result(output: 'Cron job added'),
            '*ssh*' => Process::result(output: 'not_found'),
        ]);
    }

    /**
     * Mock existing auto-renewal
     */
    protected function mockExistingAutoRenewal(): void
    {
        Process::fake([
            '*crontab -l*grep*' => Process::result(output: 'exists'),
            '*ssh*' => Process::result(output: 'exists'),
        ]);
    }

    /**
     * Mock failed auto-renewal setup
     */
    protected function mockFailedAutoRenewalSetup(): void
    {
        Process::fake([
            '*crontab*' => Process::result(
                output: '',
                errorOutput: 'Crontab update failed',
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Crontab update failed',
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock successful certificate revocation
     */
    protected function mockSuccessfulCertificateRevocation(): void
    {
        Process::fake([
            '*certbot revoke*' => Process::result(output: 'Certificate revoked'),
            '*ssh*' => Process::result(output: 'Certificate revoked'),
        ]);
    }

    /**
     * Mock failed certificate revocation
     */
    protected function mockFailedCertificateRevocation(string $error = 'Revocation failed'): void
    {
        Process::fake([
            '*certbot revoke*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock certificate information retrieval
     */
    protected function mockCertificateInfo(): void
    {
        $certOutput = "Issuer: CN=Let's Encrypt Authority X3\n".
                      "Subject: CN=info.com\n".
                      "Not Before: Jan 1 00:00:00 2025 GMT\n".
                      "Not After : Apr 1 23:59:59 2025 GMT\n".
                      'Serial Number: 123456789';

        Process::fake([
            '*openssl x509 -in*-text*' => Process::result(output: $certOutput),
            '*ssh*' => Process::result(output: $certOutput),
        ]);
    }

    /**
     * Mock failed certificate info retrieval
     */
    protected function mockFailedCertificateInfo(): void
    {
        Process::fake([
            '*openssl x509*' => Process::result(
                output: '',
                errorOutput: 'Certificate not found',
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Certificate not found',
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock failed certbot installation
     */
    protected function mockFailedCertbotInstallation(): void
    {
        Process::fake([
            '*command -v certbot*' => Process::result(output: 'not_installed'),
            '*apt-get*' => Process::result(
                output: '',
                errorOutput: 'Installation failed',
                exitCode: 1
            ),
            '*ssh*' => Process::result(
                output: '',
                errorOutput: 'Installation failed',
                exitCode: 1
            ),
        ]);
    }
}
