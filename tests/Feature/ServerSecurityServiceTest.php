<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use App\Services\Security\Fail2banService;
use App\Services\Security\FirewallService;
use App\Services\Security\SecurityScoreService;
use App\Services\Security\ServerSecurityService;
use App\Services\Security\SSHSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServerSecurityServiceTest extends TestCase
{
    protected Server $server;

    protected User $user;

    protected ServerSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'status' => 'online',
            'ip_address' => '127.0.0.1',
            'username' => 'root',
        ]);

        $this->service = app(ServerSecurityService::class);
    }

    #[Test]
    public function it_gets_security_overview(): void
    {
        $overview = $this->service->getSecurityOverview($this->server);

        $this->assertArrayHasKey('ufw', $overview);
        $this->assertArrayHasKey('fail2ban', $overview);
        $this->assertArrayHasKey('ssh', $overview);
        $this->assertArrayHasKey('open_ports', $overview);
        $this->assertArrayHasKey('php_optimization', $overview);
        $this->assertArrayHasKey('security_score', $overview);
        $this->assertArrayHasKey('risk_level', $overview);
    }

    #[Test]
    public function it_gets_php_optimization_status(): void
    {
        $status = $this->service->getPhpOptimizationStatus($this->server);

        $this->assertArrayHasKey('success', $status);

        if ($status['success']) {
            $this->assertArrayHasKey('php_version', $status);
            $this->assertArrayHasKey('opcache', $status);
            $this->assertArrayHasKey('jit', $status);
            $this->assertArrayHasKey('security_settings', $status);
            $this->assertArrayHasKey('is_optimized', $status);
        }
    }

    #[Test]
    public function it_checks_security_tools_status(): void
    {
        $status = $this->service->checkSecurityToolsStatus($this->server);

        $this->assertArrayHasKey('ufw', $status);
        $this->assertArrayHasKey('fail2ban', $status);
    }

    #[Test]
    public function it_gets_open_ports(): void
    {
        $ports = $this->service->getOpenPorts($this->server);

        $this->assertArrayHasKey('success', $ports);
        $this->assertArrayHasKey('ports', $ports);
    }

    #[Test]
    public function it_logs_security_events(): void
    {
        $this->actingAs($this->user);

        $event = $this->service->logSecurityEvent(
            $this->server,
            'test_event',
            'Test security event',
            '192.168.1.1',
            ['key' => 'value']
        );

        $this->assertDatabaseHas('security_events', [
            'server_id' => $this->server->id,
            'event_type' => 'test_event',
            'details' => 'Test security event',
            'source_ip' => '192.168.1.1',
        ]);
    }

    #[Test]
    public function it_gets_recent_security_events(): void
    {
        $this->actingAs($this->user);

        $this->service->logSecurityEvent($this->server, 'event_1', 'First event');
        $this->service->logSecurityEvent($this->server, 'event_2', 'Second event');
        $this->service->logSecurityEvent($this->server, 'event_3', 'Third event');

        $events = $this->service->getRecentEvents($this->server, 2);

        $this->assertCount(2, $events);
    }

    #[Test]
    public function it_runs_security_audit(): void
    {
        $audit = $this->service->runSecurityAudit($this->server);

        $this->assertArrayHasKey('overview', $audit);
        $this->assertArrayHasKey('issues_count', $audit);
        $this->assertArrayHasKey('issues', $audit);
        $this->assertArrayHasKey('recommendations', $audit);
        $this->assertArrayHasKey('is_secure', $audit);
        $this->assertArrayHasKey('scanned_at', $audit);

        $this->server->refresh();
        $this->assertNotNull($this->server->last_security_scan_at);
    }

    #[Test]
    public function it_identifies_security_issues(): void
    {
        $mockFirewallService = Mockery::mock(FirewallService::class);
        $mockFirewallService->shouldReceive('getUfwStatus')->andReturn([
            'installed' => true,
            'enabled' => false,
            'rules' => [],
        ]);

        $mockFail2banService = Mockery::mock(Fail2banService::class);
        $mockFail2banService->shouldReceive('getFail2banStatus')->andReturn([
            'installed' => true,
            'enabled' => false,
            'jails' => [],
        ]);

        $mockSSHService = Mockery::mock(SSHSecurityService::class);
        $mockSSHService->shouldReceive('getCurrentConfig')->andReturn([]);

        $mockScoreService = Mockery::mock(SecurityScoreService::class);

        $service = new ServerSecurityService(
            $mockFirewallService,
            $mockFail2banService,
            $mockSSHService,
            $mockScoreService
        );

        $audit = $service->runSecurityAudit($this->server);

        $this->assertGreaterThan(0, $audit['issues_count']);
        $this->assertContains('UFW firewall is not enabled', $audit['issues']);
        $this->assertContains('Fail2ban is not running', $audit['issues']);
    }

    #[Test]
    public function php_optimization_status_returns_error_on_failure(): void
    {
        $server = Server::factory()->create([
            'ip_address' => '192.168.255.255',
            'username' => 'nonexistent',
        ]);

        $status = $this->service->getPhpOptimizationStatus($server);

        $this->assertArrayHasKey('success', $status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
