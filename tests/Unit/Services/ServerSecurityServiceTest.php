<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\Fail2banService;
use App\Services\Security\FirewallService;
use App\Services\Security\SecurityScoreService;
use App\Services\Security\ServerSecurityService;
use App\Services\Security\SSHSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesServers;

class ServerSecurityServiceTest extends TestCase
{
    use CreatesServers, RefreshDatabase;

    protected ServerSecurityService $service;

    /** @var FirewallService&\Mockery\MockInterface */
    protected $firewallService;

    /** @var Fail2banService&\Mockery\MockInterface */
    protected $fail2banService;

    /** @var SSHSecurityService&\Mockery\MockInterface */
    protected $sshSecurityService;

    /** @var SecurityScoreService&\Mockery\MockInterface */
    protected $securityScoreService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        /** @phpstan-ignore-next-line */
        $this->firewallService = Mockery::mock(FirewallService::class);
        /** @phpstan-ignore-next-line */
        $this->fail2banService = Mockery::mock(Fail2banService::class);
        /** @phpstan-ignore-next-line */
        $this->sshSecurityService = Mockery::mock(SSHSecurityService::class);
        /** @phpstan-ignore-next-line */
        $this->securityScoreService = Mockery::mock(SecurityScoreService::class);

        // Instantiate service with mocked dependencies
        /** @phpstan-ignore-next-line */
        $this->service = new ServerSecurityService(
            $this->firewallService,
            $this->fail2banService,
            $this->sshSecurityService,
            $this->securityScoreService
        );
    }

    // ==========================================
    // SECURITY OVERVIEW TESTS
    // ==========================================

    #[Test]
    public function it_gets_security_overview_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'security_score' => 85,
            'last_security_scan_at' => now(),
        ]);

        $ufwStatus = ['installed' => true, 'enabled' => true, 'rules' => []];
        $fail2banStatus = ['installed' => true, 'enabled' => true, 'jails' => []];
        $sshConfig = ['permit_root_login' => 'no', 'password_authentication' => 'no'];
        $openPorts = ['success' => true, 'ports' => ['22', '80', '443']];

        $this->firewallService->shouldReceive('getUfwStatus')
            ->once()
            ->with($server)
            ->andReturn($ufwStatus);

        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->once()
            ->with($server)
            ->andReturn($fail2banStatus);

        $this->sshSecurityService->shouldReceive('getCurrentConfig')
            ->once()
            ->with($server)
            ->andReturn($sshConfig);

        Process::fake([
            '*' => Process::result(output: "22\n80\n443"),
        ]);

        // Act
        $result = $this->service->getSecurityOverview($server);

        // Assert
        $this->assertEquals($ufwStatus, $result['ufw']);
        $this->assertEquals($fail2banStatus, $result['fail2ban']);
        $this->assertEquals($sshConfig, $result['ssh']);
        $this->assertArrayHasKey('open_ports', $result);
        $this->assertEquals(85, $result['security_score']);
        $this->assertEquals('low', $result['risk_level']);
        $this->assertNotNull($result['last_scan_at']);
    }

    #[Test]
    public function it_gets_security_overview_with_high_risk_level(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'security_score' => 45,
        ]);

        $this->firewallService->shouldReceive('getUfwStatus')->andReturn([]);
        $this->fail2banService->shouldReceive('getFail2banStatus')->andReturn([]);
        $this->sshSecurityService->shouldReceive('getCurrentConfig')->andReturn([]);

        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->getSecurityOverview($server);

        // Assert
        $this->assertEquals(45, $result['security_score']);
        $this->assertEquals('high', $result['risk_level']);
    }

    #[Test]
    public function it_gets_security_overview_with_critical_risk_level(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'security_score' => 30,
        ]);

        $this->firewallService->shouldReceive('getUfwStatus')->andReturn([]);
        $this->fail2banService->shouldReceive('getFail2banStatus')->andReturn([]);
        $this->sshSecurityService->shouldReceive('getCurrentConfig')->andReturn([]);

        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->getSecurityOverview($server);

        // Assert
        $this->assertEquals(30, $result['security_score']);
        $this->assertEquals('critical', $result['risk_level']);
    }

    #[Test]
    public function it_gets_security_overview_with_secure_risk_level(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'security_score' => 95,
        ]);

        $this->firewallService->shouldReceive('getUfwStatus')->andReturn([]);
        $this->fail2banService->shouldReceive('getFail2banStatus')->andReturn([]);
        $this->sshSecurityService->shouldReceive('getCurrentConfig')->andReturn([]);

        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->getSecurityOverview($server);

        // Assert
        $this->assertEquals(95, $result['security_score']);
        $this->assertEquals('secure', $result['risk_level']);
    }

    // ==========================================
    // SECURITY TOOLS STATUS TESTS
    // ==========================================

    #[Test]
    public function it_checks_security_tools_status_and_updates_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ufw_installed' => false,
            'ufw_enabled' => false,
            'fail2ban_installed' => false,
            'fail2ban_enabled' => false,
        ]);

        $ufwStatus = ['installed' => true, 'enabled' => true];
        $fail2banStatus = ['installed' => true, 'enabled' => true];

        $this->firewallService->shouldReceive('getUfwStatus')
            ->once()
            ->with($server)
            ->andReturn($ufwStatus);

        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->once()
            ->with($server)
            ->andReturn($fail2banStatus);

        // Act
        $result = $this->service->checkSecurityToolsStatus($server);

        // Assert
        $this->assertEquals($ufwStatus, $result['ufw']);
        $this->assertEquals($fail2banStatus, $result['fail2ban']);

        // Verify server was updated
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
        $this->assertTrue($server->ufw_enabled);
        $this->assertTrue($server->fail2ban_installed);
        $this->assertTrue($server->fail2ban_enabled);
    }

    #[Test]
    public function it_checks_security_tools_status_when_not_installed(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ufw_installed' => true,
            'ufw_enabled' => true,
        ]);

        $ufwStatus = ['installed' => false, 'enabled' => false];
        $fail2banStatus = ['installed' => false, 'enabled' => false];

        $this->firewallService->shouldReceive('getUfwStatus')
            ->once()
            ->andReturn($ufwStatus);

        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->once()
            ->andReturn($fail2banStatus);

        // Act
        $result = $this->service->checkSecurityToolsStatus($server);

        // Assert
        $server->refresh();
        $this->assertFalse($server->ufw_installed);
        $this->assertFalse($server->ufw_enabled);
        $this->assertFalse($server->fail2ban_installed);
        $this->assertFalse($server->fail2ban_enabled);
    }

    #[Test]
    public function it_checks_security_tools_status_when_partially_configured(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        $ufwStatus = ['installed' => true, 'enabled' => false];
        $fail2banStatus = ['installed' => true, 'enabled' => true];

        $this->firewallService->shouldReceive('getUfwStatus')->andReturn($ufwStatus);
        $this->fail2banService->shouldReceive('getFail2banStatus')->andReturn($fail2banStatus);

        // Act
        $result = $this->service->checkSecurityToolsStatus($server);

        // Assert
        $server->refresh();
        $this->assertTrue($server->ufw_installed);
        $this->assertFalse($server->ufw_enabled);
        $this->assertTrue($server->fail2ban_installed);
        $this->assertTrue($server->fail2ban_enabled);
    }

    // ==========================================
    // OPEN PORTS TESTS
    // ==========================================

    #[Test]
    public function it_gets_open_ports_successfully(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
            'username' => 'root',
        ]);

        Process::fake([
            '*' => Process::result(output: "22\n80\n443\n3306\n6379"),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['ports']);
        $this->assertCount(5, $result['ports']);
        $this->assertContains('22', $result['ports']);
        $this->assertContains('80', $result['ports']);
        $this->assertContains('443', $result['ports']);
        $this->assertEquals(5, $result['count']);
    }

    #[Test]
    public function it_gets_open_ports_with_no_ports_open(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        Process::fake([
            '*' => Process::result(output: ''),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['ports']);
        $this->assertEmpty($result['ports']);
        $this->assertEquals(0, $result['count']);
    }

    #[Test]
    public function it_handles_error_when_getting_open_ports(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Permission denied',
                exitCode: 1
            ),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['ports']);
        $this->assertArrayHasKey('ports', $result);
    }

    #[Test]
    public function it_filters_non_numeric_ports(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        Process::fake([
            '*' => Process::result(output: "22\n80\ninvalid\n443\n\n3306"),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(4, $result['ports']);
        $this->assertNotContains('invalid', $result['ports']);
    }

    #[Test]
    public function it_gets_open_ports_for_remote_server(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '192.168.1.100',
            'username' => 'ubuntu',
            'port' => 22,
        ]);

        Process::fake([
            '*ssh*' => Process::result(output: "22\n80\n443"),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['ports']);
    }

    // ==========================================
    // SECURITY EVENT LOGGING TESTS
    // ==========================================

    #[Test]
    public function it_logs_security_event_with_all_parameters(): void
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);

        $server = $this->createOnlineServer();
        $eventType = SecurityEvent::TYPE_FIREWALL_ENABLED;
        $details = 'UFW firewall has been enabled';
        $sourceIp = '192.168.1.100';
        $metadata = ['action' => 'enable', 'rules' => ['22', '80']];

        // Act
        $event = $this->service->logSecurityEvent(
            $server,
            $eventType,
            $details,
            $sourceIp,
            $metadata
        );

        // Assert
        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals($server->id, $event->server_id);
        $this->assertEquals($eventType, $event->event_type);
        $this->assertEquals($details, $event->details);
        $this->assertEquals($sourceIp, $event->source_ip);
        $this->assertEquals($metadata, $event->metadata);
        $this->assertEquals($user->id, $event->user_id);
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => $eventType,
        ]);
    }

    #[Test]
    public function it_logs_security_event_with_minimal_parameters(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $eventType = SecurityEvent::TYPE_SECURITY_SCAN;

        // Act
        $event = $this->service->logSecurityEvent($server, $eventType);

        // Assert
        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals($server->id, $event->server_id);
        $this->assertEquals($eventType, $event->event_type);
        $this->assertNull($event->details);
        $this->assertNull($event->source_ip);
        $this->assertNull($event->metadata);
    }

    #[Test]
    public function it_logs_security_event_without_authenticated_user(): void
    {
        // Arrange
        Auth::logout();
        $server = $this->createOnlineServer();
        $eventType = SecurityEvent::TYPE_IP_BANNED;

        // Act
        $event = $this->service->logSecurityEvent($server, $eventType);

        // Assert
        $this->assertNull($event->user_id);
    }

    #[Test]
    public function it_logs_firewall_enabled_event(): void
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        $server = $this->createOnlineServer();

        // Act
        $event = $this->service->logSecurityEvent(
            $server,
            SecurityEvent::TYPE_FIREWALL_ENABLED,
            'Firewall activated',
            '127.0.0.1'
        );

        // Assert
        $this->assertEquals(SecurityEvent::TYPE_FIREWALL_ENABLED, $event->event_type);
    }

    #[Test]
    public function it_logs_ssh_config_changed_event(): void
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        $server = $this->createOnlineServer();
        $metadata = [
            'changes' => [
                'permit_root_login' => ['from' => 'yes', 'to' => 'no'],
            ],
        ];

        // Act
        $event = $this->service->logSecurityEvent(
            $server,
            SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
            'SSH configuration updated',
            null,
            $metadata
        );

        // Assert
        $this->assertEquals(SecurityEvent::TYPE_SSH_CONFIG_CHANGED, $event->event_type);
        $this->assertNotNull($event->metadata);
        $this->assertArrayHasKey('changes', $event->metadata);
    }

    #[Test]
    public function it_logs_ip_banned_event(): void
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        $server = $this->createOnlineServer();
        $bannedIp = '10.0.0.50';

        // Act
        $event = $this->service->logSecurityEvent(
            $server,
            SecurityEvent::TYPE_IP_BANNED,
            'IP address has been banned',
            $bannedIp,
            ['reason' => 'multiple_failed_attempts']
        );

        // Assert
        $this->assertEquals(SecurityEvent::TYPE_IP_BANNED, $event->event_type);
        $this->assertEquals($bannedIp, $event->source_ip);
    }

    // ==========================================
    // RECENT EVENTS TESTS
    // ==========================================

    #[Test]
    public function it_gets_recent_events_with_default_limit(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $user = User::factory()->create();
        Auth::login($user);

        // Create 15 security events
        for ($i = 0; $i < 15; $i++) {
            SecurityEvent::factory()->create([
                'server_id' => $server->id,
                'user_id' => $user->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        // Act
        $events = $this->service->getRecentEvents($server);

        // Assert
        $this->assertCount(10, $events);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $events);
    }

    #[Test]
    public function it_gets_recent_events_with_custom_limit(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $user = User::factory()->create();

        // Create 10 security events
        SecurityEvent::factory()->count(10)->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // Act
        $events = $this->service->getRecentEvents($server, 5);

        // Assert
        $this->assertCount(5, $events);
    }

    #[Test]
    public function it_gets_recent_events_ordered_by_created_at_desc(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $user = User::factory()->create();

        $oldEvent = SecurityEvent::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
            'event_type' => SecurityEvent::TYPE_FIREWALL_ENABLED,
            'created_at' => now()->subHours(2),
        ]);

        $newEvent = SecurityEvent::factory()->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
            'event_type' => SecurityEvent::TYPE_IP_BANNED,
            'created_at' => now()->subMinutes(5),
        ]);

        // Act
        $events = $this->service->getRecentEvents($server);

        // Assert
        $firstEvent = $events->first();
        $lastEvent = $events->last();
        $this->assertNotNull($firstEvent);
        $this->assertNotNull($lastEvent);
        $this->assertEquals($newEvent->id, $firstEvent->id);
        $this->assertEquals($oldEvent->id, $lastEvent->id);
    }

    #[Test]
    public function it_gets_recent_events_with_user_relation_loaded(): void
    {
        // Arrange
        $server = $this->createOnlineServer();
        $user = User::factory()->create();

        SecurityEvent::factory()->count(3)->create([
            'server_id' => $server->id,
            'user_id' => $user->id,
        ]);

        // Act
        $events = $this->service->getRecentEvents($server);

        // Assert
        $firstEvent = $events->first();
        $this->assertNotNull($firstEvent);
        $this->assertTrue($firstEvent->relationLoaded('user'));
        $this->assertNotNull($firstEvent->user);
        $this->assertEquals($user->id, $firstEvent->user->id);
    }

    #[Test]
    public function it_gets_recent_events_for_server_with_no_events(): void
    {
        // Arrange
        $server = $this->createOnlineServer();

        // Act
        $events = $this->service->getRecentEvents($server);

        // Assert
        $this->assertCount(0, $events);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $events);
    }

    #[Test]
    public function it_gets_recent_events_only_for_specific_server(): void
    {
        // Arrange
        $server1 = $this->createOnlineServer();
        $server2 = $this->createOnlineServer();
        $user = User::factory()->create();

        SecurityEvent::factory()->count(5)->create([
            'server_id' => $server1->id,
            'user_id' => $user->id,
        ]);

        SecurityEvent::factory()->count(3)->create([
            'server_id' => $server2->id,
            'user_id' => $user->id,
        ]);

        // Act
        $events = $this->service->getRecentEvents($server1);

        // Assert
        $this->assertCount(5, $events);
        $events->each(function ($event) use ($server1) {
            $this->assertEquals($server1->id, $event->server_id);
        });
    }

    // ==========================================
    // LOCALHOST DETECTION TESTS
    // ==========================================

    #[Test]
    public function it_detects_localhost_by_127_0_0_1(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '127.0.0.1',
        ]);

        Process::fake([
            '*' => Process::result(output: '22'),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert - Should execute locally without SSH
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_detects_localhost_by_ipv6_loopback(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => '::1',
        ]);

        Process::fake([
            '*' => Process::result(output: '22'),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_detects_localhost_by_hostname(): void
    {
        // Arrange
        $server = $this->createOnlineServer([
            'ip_address' => 'localhost',
        ]);

        Process::fake([
            '*' => Process::result(output: '22'),
        ]);

        // Act
        $result = $this->service->getOpenPorts($server);

        // Assert
        $this->assertTrue($result['success']);
    }
}
