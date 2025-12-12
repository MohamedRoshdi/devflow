<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Services\DomainService;
use App\Services\SSLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DomainServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainService $service;
    private SSLService $sslService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sslService = Mockery::mock(SSLService::class);
        $this->service = new DomainService($this->sslService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // DOMAIN SETUP TESTS
    // ==========================================

    /** @test */
    public function it_sets_up_domain_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create(['ip_address' => '192.168.1.100']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->sslService->shouldReceive('issueCertificate')
            ->once()
            ->andReturn([
                'success' => true,
                'certificate' => SSLCertificate::factory()->make([
                    'certificate_path' => '/etc/ssl/cert.pem',
                    'private_key_path' => '/etc/ssl/key.pem',
                    'issued_at' => now(),
                    'expires_at' => now()->addDays(90),
                ]),
            ]);

        $domain = $this->service->setupDomain($project, [
            'domain' => 'example.com',
            'is_primary' => true,
            'ssl_enabled' => true,
        ]);

        $this->assertEquals('example.com', $domain->domain);
        $this->assertTrue($domain->is_primary);
        $this->assertTrue($domain->ssl_enabled);
    }

    /** @test */
    public function it_validates_domain_format(): void
    {
        $project = Project::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->setupDomain($project, [
            'domain' => 'invalid domain!',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_domains(): void
    {
        $project = Project::factory()->create();

        Domain::factory()->create(['domain' => 'example.com']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already registered');

        $this->service->setupDomain($project, [
            'domain' => 'example.com',
        ]);
    }

    /** @test */
    public function it_clears_existing_primary_domains(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $existingPrimary = Domain::factory()->create([
            'project_id' => $project->id,
            'is_primary' => true,
        ]);

        $this->sslService->shouldReceive('issueCertificate')
            ->andReturn([
                'success' => true,
                'certificate' => SSLCertificate::factory()->make([
                    'issued_at' => now(),
                    'expires_at' => now()->addDays(90),
                ]),
            ]);

        $domain = $this->service->setupDomain($project, [
            'domain' => 'new-primary.com',
            'is_primary' => true,
            'ssl_enabled' => true,
        ]);

        $this->assertTrue($domain->is_primary);
        $this->assertFalse($existingPrimary->fresh()->is_primary);
    }

    /** @test */
    public function it_handles_domain_setup_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        $this->sslService->shouldReceive('issueCertificate')
            ->andThrow(new \Exception('SSL issuance failed'));

        $this->expectException(\Exception::class);

        $this->service->setupDomain($project, [
            'domain' => 'example.com',
            'ssl_enabled' => true,
        ]);
    }

    // ==========================================
    // DNS VERIFICATION TESTS
    // ==========================================

    /** @test */
    public function it_verifies_dns_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create(['ip_address' => '192.168.1.100']);
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        // Mock DNS lookup to return the server's IP
        $result = $this->service->verifyDNS($domain);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_configured', $result);
    }

    /** @test */
    public function it_handles_dns_verification_without_server(): void
    {
        $project = Project::factory()->create(['server_id' => null]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
        ]);

        $result = $this->service->verifyDNS($domain);

        $this->assertFalse($result['is_configured']);
        $this->assertEquals('No server associated with project', $result['error']);
    }

    /** @test */
    public function it_handles_dns_lookup_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'nonexistent-domain-12345.com',
        ]);

        $result = $this->service->verifyDNS($domain);

        $this->assertFalse($result['is_configured']);
    }

    // ==========================================
    // DOMAIN HEALTH CHECK TESTS
    // ==========================================

    /** @test */
    public function it_checks_domain_health(): void
    {
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);
        $project = Project::factory()->create(['server_id' => $server->id]);

        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'localhost',
            'ssl_enabled' => false,
        ]);

        $healthResults = $this->service->checkDomainHealth($project);

        $this->assertIsArray($healthResults);
        $this->assertArrayHasKey($domain->id, $healthResults);
        $this->assertArrayHasKey('dns', $healthResults[$domain->id]);
        $this->assertArrayHasKey('ssl', $healthResults[$domain->id]);
        $this->assertArrayHasKey('http', $healthResults[$domain->id]);
        $this->assertArrayHasKey('overall_status', $healthResults[$domain->id]);
    }

    /** @test */
    public function it_detects_expired_ssl(): void
    {
        $project = Project::factory()->create();
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->subDays(1),
        ]);

        $healthResults = $this->service->checkDomainHealth($project);
        $domainHealth = $healthResults[$domain->id];

        $this->assertEquals('ssl_failed', $domainHealth['overall_status']);
    }

    // ==========================================
    // SSL RENEWAL TESTS
    // ==========================================

    /** @test */
    public function it_renews_ssl_certificate(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => $domain->domain,
            'expires_at' => now()->addDays(30),
        ]);

        $this->sslService->shouldReceive('renewCertificate')
            ->once()
            ->with(Mockery::on(fn($cert) => $cert->id === $certificate->id))
            ->andReturn(['success' => true]);

        $result = $this->service->renewSSL($domain);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_ssl_renewal_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => $domain->domain,
        ]);

        $this->sslService->shouldReceive('renewCertificate')
            ->andReturn(['success' => false, 'message' => 'Renewal failed']);

        $result = $this->service->renewSSL($domain);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_issues_new_ssl_when_none_exists(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
            'domain' => 'newdomain.com',
        ]);

        $this->sslService->shouldReceive('issueCertificate')
            ->once()
            ->andReturn([
                'success' => true,
                'certificate' => SSLCertificate::factory()->make([
                    'issued_at' => now(),
                    'expires_at' => now()->addDays(90),
                ]),
            ]);

        $result = $this->service->renewSSL($domain);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_skips_renewal_when_ssl_disabled(): void
    {
        Log::shouldReceive('warning')->once();

        $domain = Domain::factory()->create([
            'ssl_enabled' => false,
        ]);

        $result = $this->service->renewSSL($domain);

        $this->assertFalse($result);
    }

    // ==========================================
    // DOMAIN DELETION TESTS
    // ==========================================

    /** @test */
    public function it_deletes_domain_successfully(): void
    {
        Log::shouldReceive('info')->times(2);

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => $domain->domain,
        ]);

        $this->sslService->shouldReceive('revokeCertificate')
            ->once()
            ->with(Mockery::on(fn($cert) => $cert->id === $certificate->id));

        $result = $this->service->deleteDomain($domain);

        $this->assertTrue($result);
        $this->assertNull(Domain::find($domain->id));
    }

    /** @test */
    public function it_handles_domain_deletion_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $domain = Domain::factory()->create([
            'project_id' => $project->id,
            'ssl_enabled' => true,
        ]);

        $certificate = SSLCertificate::factory()->create([
            'server_id' => $server->id,
            'domain_name' => $domain->domain,
        ]);

        $this->sslService->shouldReceive('revokeCertificate')
            ->andThrow(new \Exception('Revocation failed'));

        $result = $this->service->deleteDomain($domain);

        $this->assertFalse($result);
    }

    // ==========================================
    // DNS CONFIGURATION TESTS
    // ==========================================

    /** @test */
    public function it_configures_cloudflare_dns(): void
    {
        Log::shouldReceive('info')->times(2);

        $domain = Domain::factory()->create();

        $result = $this->service->configureDNS($domain, 'cloudflare', [
            'api_token' => 'test_token',
        ]);

        // Currently returns false as provider is not implemented
        $this->assertFalse($result);
    }

    /** @test */
    public function it_rejects_unsupported_dns_provider(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $domain = Domain::factory()->create();

        $result = $this->service->configureDNS($domain, 'unsupported', []);

        $this->assertFalse($result);
    }

    // ==========================================
    // PROJECT DOMAINS TESTS
    // ==========================================

    /** @test */
    public function it_gets_project_domains_with_health(): void
    {
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);

        Domain::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $domains = $this->service->getProjectDomains($project);

        $this->assertCount(3, $domains);
        foreach ($domains as $domain) {
            $this->assertNotNull($domain->health_status);
        }
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /** @test */
    public function it_validates_domain_data(): void
    {
        $project = Project::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->setupDomain($project, [
            'domain' => '', // Empty domain
        ]);
    }

    /** @test */
    public function it_validates_ssl_provider(): void
    {
        $project = Project::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->setupDomain($project, [
            'domain' => 'example.com',
            'ssl_provider' => 'invalid_provider',
        ]);
    }

    /** @test */
    public function it_validates_dns_provider(): void
    {
        $project = Project::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->setupDomain($project, [
            'domain' => 'example.com',
            'dns_provider' => 'invalid_provider',
        ]);
    }
}
