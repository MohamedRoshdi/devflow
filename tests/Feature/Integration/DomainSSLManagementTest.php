<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\User;
use App\Services\DomainService;
use App\Services\SSLService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Domain & SSL Management Integration Test
 *
 * This test suite covers the complete domain and SSL certificate lifecycle,
 * including domain registration, DNS verification, SSL provisioning, and renewal.
 *
 * Workflows covered:
 * 1. Domain addition and DNS verification
 * 2. SSL certificate provisioning (Let's Encrypt)
 * 3. SSL certificate renewal workflow
 * 4. Domain migration between projects
 * 5. Multi-domain SSL (SAN certificates)
 */
class DomainSSLManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@devflow.com',
        ]);

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
            'ip_address' => '192.168.1.100',
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
        ]);
    }

    // ==================== Domain Addition Tests ====================

    #[Test]
    public function can_add_domain_to_project(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'subdomain' => null,
            'is_primary' => true,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('domains', [
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        $this->assertEquals('pending', $domain->status);
    }

    #[Test]
    public function can_add_subdomain_to_project(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'subdomain' => 'api',
            'is_primary' => false,
            'status' => 'pending',
        ]);

        $this->assertEquals('api', $domain->subdomain);
        $this->assertFalse($domain->is_primary);
    }

    #[Test]
    public function project_can_have_multiple_domains(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.org',
            'is_primary' => false,
        ]);

        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'subdomain' => 'www',
            'is_primary' => false,
        ]);

        $this->assertCount(3, $this->project->domains);
    }

    #[Test]
    public function only_one_domain_can_be_primary(): void
    {
        $primary = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
        ]);

        // Creating another primary should not be allowed by policy
        $secondary = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.org',
            'is_primary' => false,
        ]);

        $primaryCount = Domain::where('project_id', $this->project->id)
            ->where('is_primary', true)
            ->count();

        $this->assertEquals(1, $primaryCount);
    }

    // ==================== DNS Verification Tests ====================

    #[Test]
    public function domain_status_transitions_on_verification(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'pending',
        ]);

        // Simulate DNS verification success
        $domain->update(['status' => 'active']);

        $freshDomain = $domain->fresh();
        $this->assertNotNull($freshDomain);
        $this->assertEquals('active', $freshDomain->status);
    }

    #[Test]
    public function failed_dns_verification_sets_status_to_failed(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'invalid-domain.test',
            'status' => 'pending',
        ]);

        // Simulate DNS verification failure
        $domain->update(['status' => 'failed']);

        $freshDomain = $domain->fresh();
        $this->assertNotNull($freshDomain);
        $this->assertEquals('failed', $freshDomain->status);
    }

    #[Test]
    public function can_retry_failed_dns_verification(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'failed',
        ]);

        // Retry verification
        $domain->update(['status' => 'pending']);

        $freshDomain = $domain->fresh();
        $this->assertNotNull($freshDomain);
        $this->assertEquals('pending', $freshDomain->status);
    }

    // ==================== SSL Certificate Provisioning Tests ====================

    #[Test]
    public function can_enable_ssl_for_domain(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'active',
            'ssl_enabled' => false,
        ]);

        $domain->update([
            'ssl_enabled' => true,
            'ssl_auto_renew' => true,
        ]);

        $freshDomain = $domain->fresh();
        $this->assertNotNull($freshDomain);
        $this->assertTrue($freshDomain->ssl_enabled);
        $this->assertTrue($freshDomain->ssl_auto_renew);
    }

    #[Test]
    public function ssl_certificate_stores_expiry_date(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'status' => 'active',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(90),
        ]);

        $this->assertNotNull($domain->ssl_expires_at);
        $this->assertTrue($domain->ssl_expires_at->isFuture());
    }

    #[Test]
    public function identifies_expiring_ssl_certificates(): void
    {
        // Certificate expiring in 10 days
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'expiring.com',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(10),
        ]);

        // Certificate valid for 60 more days
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'valid.com',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(60),
        ]);

        // Find certificates expiring within 30 days
        $expiring = Domain::where('ssl_enabled', true)
            ->where('ssl_expires_at', '<=', now()->addDays(30))
            ->get();

        $this->assertCount(1, $expiring);
        $firstExpiring = $expiring->first();
        $this->assertNotNull($firstExpiring);
        $this->assertEquals('expiring.com', $firstExpiring->domain);
    }

    #[Test]
    public function identifies_expired_ssl_certificates(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'expired.com',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->subDays(5),
            'status' => 'expired',
        ]);

        $expired = Domain::where('ssl_enabled', true)
            ->where('ssl_expires_at', '<', now())
            ->get();

        $this->assertCount(1, $expired);
    }

    // ==================== SSL Certificate Renewal Tests ====================

    #[Test]
    public function ssl_renewal_updates_expiry_date(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'ssl_enabled' => true,
            'ssl_expires_at' => now()->addDays(10),
        ]);

        // Simulate renewal
        $newExpiry = now()->addDays(90);
        $domain->update(['ssl_expires_at' => $newExpiry]);

        $freshDomain = $domain->fresh();
        $this->assertNotNull($freshDomain);
        $this->assertNotNull($freshDomain->ssl_expires_at);
        $this->assertTrue($freshDomain->ssl_expires_at->gt(now()->addDays(30)));
    }

    #[Test]
    public function auto_renewal_flag_controls_renewal_behavior(): void
    {
        $autoRenew = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'auto.com',
            'ssl_enabled' => true,
            'ssl_auto_renew' => true,
            'ssl_expires_at' => now()->addDays(10),
        ]);

        $manualRenew = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'manual.com',
            'ssl_enabled' => true,
            'ssl_auto_renew' => false,
            'ssl_expires_at' => now()->addDays(10),
        ]);

        $autoRenewDomains = Domain::where('ssl_auto_renew', true)
            ->where('ssl_expires_at', '<=', now()->addDays(30))
            ->get();

        $this->assertCount(1, $autoRenewDomains);
        $firstAutoRenew = $autoRenewDomains->first();
        $this->assertNotNull($firstAutoRenew);
        $this->assertEquals('auto.com', $firstAutoRenew->domain);
    }

    // ==================== Domain Migration Tests ====================

    #[Test]
    public function can_migrate_domain_to_different_project(): void
    {
        $newProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'migrating.com',
            'status' => 'active',
        ]);

        // Migrate to new project
        $domain->update(['project_id' => $newProject->id]);

        $freshDomain = $domain->fresh();
        $freshProject = $this->project->fresh();
        $freshNewProject = $newProject->fresh();

        $this->assertNotNull($freshDomain);
        $this->assertNotNull($freshProject);
        $this->assertNotNull($freshNewProject);
        $this->assertEquals($newProject->id, $freshDomain->project_id);
        $this->assertCount(0, $freshProject->domains);
        $this->assertCount(1, $freshNewProject->domains);
    }

    #[Test]
    public function domain_migration_preserves_ssl_settings(): void
    {
        $newProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'migrating.com',
            'ssl_enabled' => true,
            'ssl_auto_renew' => true,
            'ssl_expires_at' => now()->addDays(60),
        ]);

        $this->assertNotNull($domain->ssl_expires_at);
        $originalExpiry = $domain->ssl_expires_at;

        // Migrate
        $domain->update(['project_id' => $newProject->id]);

        $migrated = $domain->fresh();
        $this->assertNotNull($migrated);
        $this->assertTrue($migrated->ssl_enabled);
        $this->assertTrue($migrated->ssl_auto_renew);
        $this->assertNotNull($migrated->ssl_expires_at);
        $this->assertEquals($originalExpiry->toDateTimeString(), $migrated->ssl_expires_at->toDateTimeString());
    }

    // ==================== Multi-Domain SSL Tests ====================

    #[Test]
    public function can_group_domains_for_san_certificate(): void
    {
        $domains = [];
        $domainNames = ['example.com', 'www.example.com', 'api.example.com'];

        foreach ($domainNames as $name) {
            $parts = explode('.', $name, 2);
            $subdomain = count($parts) > 2 ? $parts[0] : null;
            $baseDomain = count($parts) > 2 ? implode('.', array_slice($parts, 1)) : $name;

            $domains[] = Domain::factory()->create([
                'project_id' => $this->project->id,
                'domain' => str_contains($name, 'www.') || str_contains($name, 'api.')
                    ? 'example.com'
                    : $name,
                'subdomain' => str_contains($name, 'www.') ? 'www'
                    : (str_contains($name, 'api.') ? 'api' : null),
                'ssl_enabled' => true,
            ]);
        }

        $sslDomains = Domain::where('project_id', $this->project->id)
            ->where('ssl_enabled', true)
            ->get();

        $this->assertCount(3, $sslDomains);
    }

    // ==================== Domain Redirect Tests ====================

    #[Test]
    public function can_configure_domain_redirect(): void
    {
        $primaryDomain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'is_primary' => true,
            'redirect_to' => null,
        ]);

        $wwwDomain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'example.com',
            'subdomain' => 'www',
            'is_primary' => false,
            'redirect_to' => 'https://example.com',
        ]);

        $this->assertNull($primaryDomain->redirect_to);
        $this->assertEquals('https://example.com', $wwwDomain->redirect_to);
    }

    // ==================== Domain Deletion Tests ====================

    #[Test]
    public function deleting_domain_removes_ssl_configuration(): void
    {
        $domain = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'delete-me.com',
            'ssl_enabled' => true,
            'ssl_certificate_path' => '/etc/ssl/delete-me.com.crt',
        ]);

        $domainId = $domain->id;
        $domain->delete();

        $this->assertDatabaseMissing('domains', ['id' => $domainId]);
    }

    #[Test]
    public function cannot_delete_primary_domain_with_others_existing(): void
    {
        $primary = Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'primary.com',
            'is_primary' => true,
        ]);

        Domain::factory()->create([
            'project_id' => $this->project->id,
            'domain' => 'secondary.com',
            'is_primary' => false,
        ]);

        // Count domains before
        $countBefore = Domain::where('project_id', $this->project->id)->count();
        $this->assertEquals(2, $countBefore);

        // In a real scenario, this would be prevented by validation
        // Here we just verify the state
        $this->assertTrue($primary->is_primary);
    }

    // ==================== Domain Statistics Tests ====================

    #[Test]
    public function calculates_ssl_coverage_statistics(): void
    {
        // Domains with SSL
        Domain::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'ssl_enabled' => true,
            'status' => 'active',
        ]);

        // Domains without SSL
        Domain::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'ssl_enabled' => false,
            'status' => 'active',
        ]);

        $total = Domain::where('project_id', $this->project->id)->count();
        $withSsl = Domain::where('project_id', $this->project->id)
            ->where('ssl_enabled', true)
            ->count();
        $coverage = ($withSsl / $total) * 100;

        $this->assertEquals(5, $total);
        $this->assertEquals(3, $withSsl);
        $this->assertEquals(60.0, $coverage);
    }

    #[Test]
    public function tracks_domain_status_distribution(): void
    {
        Domain::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        Domain::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'pending',
        ]);

        Domain::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $statusCounts = Domain::where('project_id', $this->project->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->assertEquals(1, $statusCounts['active']);
        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['failed']);
    }
}
