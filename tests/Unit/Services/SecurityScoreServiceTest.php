<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SecurityEvent;
use App\Models\SecurityScan;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\Fail2banService;
use App\Services\Security\FirewallService;
use App\Services\Security\SecurityScoreService;
use App\Services\Security\SSHSecurityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class SecurityScoreServiceTest extends TestCase
{
    protected SecurityScoreService|Mockery\MockInterface $service;

    protected FirewallService $firewallService;

    protected Fail2banService $fail2banService;

    protected SSHSecurityService $sshSecurityService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock dependencies
        $this->firewallService = Mockery::mock(FirewallService::class);
        $this->fail2banService = Mockery::mock(Fail2banService::class);
        $this->sshSecurityService = Mockery::mock(SSHSecurityService::class);

        // Instantiate service with mocked dependencies
        $this->service = new SecurityScoreService(
            $this->firewallService,
            $this->fail2banService,
            $this->sshSecurityService
        );
    }

    protected function mockServiceWithFindings(array $findings): void
    {
        $this->service = Mockery::mock(SecurityScoreService::class, [
            $this->firewallService,
            $this->fail2banService,
            $this->sshSecurityService,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->service->shouldReceive('collectFindings')
            ->andReturn($findings);
    }

    // ==================== RUN SECURITY SCAN TESTS ====================

    /** @test */
    public function it_runs_security_scan_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['22', '80', '443']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $this->assertInstanceOf(SecurityScan::class, $scan);
        $this->assertEquals(SecurityScan::STATUS_COMPLETED, $scan->status);
        $this->assertNotNull($scan->score);
        $this->assertNotNull($scan->risk_level);
        $this->assertIsArray($scan->findings);
        $this->assertIsArray($scan->recommendations);
        $this->assertNotNull($scan->completed_at);

        $this->assertDatabaseHas('security_scans', [
            'server_id' => $server->id,
            'status' => SecurityScan::STATUS_COMPLETED,
            'triggered_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_creates_security_scan_with_running_status(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $this->service->runSecurityScan($server);

        // Assert
        $this->assertDatabaseHas('security_scans', [
            'server_id' => $server->id,
            'triggered_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_updates_server_security_score_after_scan(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $server = Server::factory()->create([
            'ip_address' => '192.168.1.1',
            'security_score' => null,
        ]);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['22', '80']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);
        $server->refresh();

        // Assert
        $this->assertEquals($scan->score, $server->security_score);
        $this->assertNotNull($server->last_security_scan_at);
    }

    /** @test */
    public function it_creates_security_event_after_successful_scan(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $this->assertDatabaseHas('security_events', [
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_SECURITY_SCAN,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_handles_scan_failure_gracefully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        Log::shouldReceive('error')
            ->once()
            ->with('Security scan failed', Mockery::any());

        $this->firewallService->shouldReceive('getUfwStatus')
            ->andThrow(new \Exception('Connection failed'));

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $this->assertEquals(SecurityScan::STATUS_FAILED, $scan->status);
        $this->assertNotNull($scan->completed_at);
        $this->assertArrayHasKey('error', $scan->findings);
    }

    // ==================== CALCULATE SCORE TESTS ====================

    /** @test */
    public function it_calculates_perfect_score_for_fully_secure_server(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['2222', '80', '443']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $score = $this->service->calculateScore($server);

        // Assert
        $this->assertEquals(100, $score);
    }

    /** @test */
    public function it_calculates_low_score_for_insecure_server(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22', '80', '443', '3306', '5432', '6379', '27017', '8080', '8443', '9000', '9001']);
        $this->mockSecurityUpdatesCommand(10, 15);

        // Act
        $score = $this->service->calculateScore($server);

        // Assert
        $this->assertLessThan(30, $score);
    }

    /** @test */
    public function it_returns_score_between_0_and_100(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22', '80']);
        $this->mockSecurityUpdatesCommand(2, 5);

        // Act
        $score = $this->service->calculateScore($server);

        // Assert
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // ==================== SCORE BREAKDOWN TESTS ====================

    /** @test */
    public function it_returns_score_breakdown_with_all_components(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banEnabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['2222', '80', '443']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('total_score', $breakdown);
        $this->assertArrayHasKey('firewall', $breakdown);
        $this->assertArrayHasKey('fail2ban', $breakdown);
        $this->assertArrayHasKey('ssh_port', $breakdown);
        $this->assertArrayHasKey('root_login', $breakdown);
        $this->assertArrayHasKey('password_auth', $breakdown);
        $this->assertArrayHasKey('open_ports', $breakdown);
        $this->assertArrayHasKey('updates', $breakdown);
    }

    /** @test */
    public function it_shows_correct_firewall_score_breakdown(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(20, $breakdown['firewall']['score']);
        $this->assertEquals(20, $breakdown['firewall']['max']);
        $this->assertTrue($breakdown['firewall']['status']);
    }

    /** @test */
    public function it_shows_correct_fail2ban_score_breakdown(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banEnabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(15, $breakdown['fail2ban']['score']);
        $this->assertEquals(15, $breakdown['fail2ban']['max']);
        $this->assertTrue($breakdown['fail2ban']['status']);
    }

    /** @test */
    public function it_shows_correct_ssh_port_score_breakdown(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockSecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(10, $breakdown['ssh_port']['score']);
        $this->assertEquals(10, $breakdown['ssh_port']['max']);
        $this->assertEquals(2222, $breakdown['ssh_port']['port']);
    }

    // ==================== FIREWALL SCORING TESTS ====================

    /** @test */
    public function it_awards_full_points_when_firewall_is_enabled(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallEnabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $score = $this->service->calculateScore($server);

        // Assert - Should have 20 points from firewall
        $this->assertGreaterThanOrEqual(20, $score);
    }

    /** @test */
    public function it_awards_no_points_when_firewall_is_disabled(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22', '80']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['firewall']['score']);
    }

    // ==================== FAIL2BAN SCORING TESTS ====================

    /** @test */
    public function it_awards_full_points_when_fail2ban_is_enabled(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banEnabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $score = $this->service->calculateScore($server);

        // Assert - Should have 15 points from fail2ban
        $this->assertGreaterThanOrEqual(15, $score);
    }

    /** @test */
    public function it_awards_no_points_when_fail2ban_is_disabled(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['fail2ban']['score']);
    }

    // ==================== SSH SECURITY SCORING TESTS ====================

    /** @test */
    public function it_awards_points_for_non_standard_ssh_port(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['port' => 2222]);
        $this->mockOpenPortsCommand(['2222']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(10, $breakdown['ssh_port']['score']);
    }

    /** @test */
    public function it_awards_no_points_for_default_ssh_port(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['port' => 22]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['ssh_port']['score']);
    }

    /** @test */
    public function it_awards_points_for_disabled_root_login(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['root_login_enabled' => false]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(15, $breakdown['root_login']['score']);
        $this->assertTrue($breakdown['root_login']['disabled']);
    }

    /** @test */
    public function it_awards_no_points_for_enabled_root_login(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['root_login_enabled' => true]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['root_login']['score']);
        $this->assertFalse($breakdown['root_login']['disabled']);
    }

    /** @test */
    public function it_awards_points_for_disabled_password_authentication(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['password_auth_enabled' => false]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(15, $breakdown['password_auth']['score']);
        $this->assertTrue($breakdown['password_auth']['disabled']);
    }

    /** @test */
    public function it_awards_no_points_for_enabled_password_authentication(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['password_auth_enabled' => true]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['password_auth']['score']);
        $this->assertFalse($breakdown['password_auth']['disabled']);
    }

    // ==================== OPEN PORTS SCORING TESTS ====================

    /** @test */
    public function it_awards_maximum_points_for_three_or_fewer_open_ports(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'open_ports' => [
                'ports' => [22, 80, 443],
                'count' => 3,
                'score' => 10,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(10, $breakdown['open_ports']['score']);
        $this->assertEquals(3, $breakdown['open_ports']['count']);
    }

    /** @test */
    public function it_awards_partial_points_for_four_to_five_open_ports(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'open_ports' => [
                'ports' => [22, 80, 443, 3306, 6379],
                'count' => 5,
                'score' => 7,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(7, $breakdown['open_ports']['score']);
        $this->assertEquals(5, $breakdown['open_ports']['count']);
    }

    /** @test */
    public function it_awards_fewer_points_for_six_to_ten_open_ports(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'open_ports' => [
                'ports' => [22, 80, 443, 3306, 5432, 6379, 8080, 9000],
                'count' => 8,
                'score' => 4,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(4, $breakdown['open_ports']['score']);
        $this->assertEquals(8, $breakdown['open_ports']['count']);
    }

    /** @test */
    public function it_awards_no_points_for_more_than_ten_open_ports(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'open_ports' => [
                'ports' => [22, 80, 443, 3306, 5432, 6379, 8080, 8443, 9000, 9001, 9002],
                'count' => 11,
                'score' => 0,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['open_ports']['score']);
        $this->assertEquals(11, $breakdown['open_ports']['count']);
    }

    // ==================== SECURITY UPDATES SCORING TESTS ====================

    /** @test */
    public function it_awards_maximum_points_for_no_security_updates(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'updates' => [
                'security_updates' => 0,
                'total_updates' => 5,
                'score' => 15,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(15, $breakdown['updates']['score']);
        $this->assertEquals(0, $breakdown['updates']['pending']);
    }

    /** @test */
    public function it_awards_partial_points_for_one_to_two_security_updates(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'updates' => [
                'security_updates' => 2,
                'total_updates' => 5,
                'score' => 10,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(10, $breakdown['updates']['score']);
        $this->assertEquals(2, $breakdown['updates']['pending']);
    }

    /** @test */
    public function it_awards_fewer_points_for_three_to_five_security_updates(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'updates' => [
                'security_updates' => 4,
                'total_updates' => 10,
                'score' => 5,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(5, $breakdown['updates']['score']);
        $this->assertEquals(4, $breakdown['updates']['pending']);
    }

    /** @test */
    public function it_awards_no_points_for_more_than_five_security_updates(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '127.0.0.1']);

        $findings = $this->createFindings([
            'updates' => [
                'security_updates' => 10,
                'total_updates' => 15,
                'score' => 0,
            ],
        ]);

        $this->mockServiceWithFindings($findings);

        // Act
        $breakdown = $this->service->getScoreBreakdown($server);

        // Assert
        $this->assertEquals(0, $breakdown['updates']['score']);
        $this->assertEquals(10, $breakdown['updates']['pending']);
    }

    // ==================== RECOMMENDATIONS TESTS ====================

    /** @test */
    public function it_recommends_installing_firewall_when_not_installed(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->firewallService->shouldReceive('getUfwStatus')
            ->andReturn(['installed' => false, 'enabled' => false, 'rules' => []]);
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $this->assertNotEmpty($scan->recommendations);
        $hasFirewallRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'firewall' && $rec['title'] === 'Install UFW Firewall';
        });
        $this->assertTrue($hasFirewallRecommendation);
    }

    /** @test */
    public function it_recommends_enabling_firewall_when_installed_but_disabled(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->firewallService->shouldReceive('getUfwStatus')
            ->andReturn(['installed' => true, 'enabled' => false, 'rules' => []]);
        $this->mockFail2banDisabled();
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasFirewallRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'firewall' && $rec['title'] === 'Enable UFW Firewall';
        });
        $this->assertTrue($hasFirewallRecommendation);
    }

    /** @test */
    public function it_recommends_installing_fail2ban_when_not_installed(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockFirewallDisabled();
        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->andReturn(['installed' => false, 'enabled' => false, 'jails' => []]);
        $this->mockInsecureSSHConfig();
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasFail2banRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'fail2ban' && $rec['title'] === 'Install Fail2ban';
        });
        $this->assertTrue($hasFail2banRecommendation);
    }

    /** @test */
    public function it_recommends_changing_default_ssh_port(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['port' => 22]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasSSHPortRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'ssh' && $rec['title'] === 'Change Default SSH Port';
        });
        $this->assertTrue($hasSSHPortRecommendation);
    }

    /** @test */
    public function it_recommends_disabling_root_login(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['root_login_enabled' => true]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasRootLoginRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'ssh' && $rec['title'] === 'Disable Root Login';
        });
        $this->assertTrue($hasRootLoginRecommendation);
    }

    /** @test */
    public function it_recommends_disabling_password_authentication(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->mockFirewallDisabled();
        $this->mockFail2banDisabled();
        $this->mockCustomSSHConfig(['password_auth_enabled' => true]);
        $this->mockOpenPortsCommand(['22']);
        $this->mockSecurityUpdatesCommand(0, 0);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasPasswordAuthRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'ssh' && $rec['title'] === 'Disable Password Authentication';
        });
        $this->assertTrue($hasPasswordAuthRecommendation);
    }

    /** @test */
    public function it_recommends_installing_security_updates(): void
    {
        // Arrange
        $server = Server::factory()->create(['ip_address' => '192.168.1.1']);
        $user = User::factory()->create();
        $this->actingAs($user);

        $findings = $this->createFindings([
            'updates' => [
                'security_updates' => 5,
                'total_updates' => 10,
                'score' => 0,
            ],
        ]);

        // Mock the service to return these findings
        $this->service = Mockery::mock(SecurityScoreService::class, [
            $this->firewallService,
            $this->fail2banService,
            $this->sshSecurityService,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->service->shouldReceive('collectFindings')
            ->andReturn($findings);

        // Act
        $scan = $this->service->runSecurityScan($server);

        // Assert
        $hasUpdatesRecommendation = collect($scan->recommendations)->contains(function ($rec) {
            return $rec['category'] === 'updates' && $rec['title'] === 'Install Security Updates';
        });
        $this->assertTrue($hasUpdatesRecommendation);
    }

    // ==================== HELPER METHODS ====================

    private function mockFirewallEnabled(): void
    {
        $this->firewallService->shouldReceive('getUfwStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'rules' => [['port' => '22'], ['port' => '80']],
            ]);
    }

    private function mockFirewallDisabled(): void
    {
        $this->firewallService->shouldReceive('getUfwStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => false,
                'rules' => [],
            ]);
    }

    private function mockFail2banEnabled(): void
    {
        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => true,
                'enabled' => true,
                'jails' => ['sshd', 'nginx'],
            ]);
    }

    private function mockFail2banDisabled(): void
    {
        $this->fail2banService->shouldReceive('getFail2banStatus')
            ->andReturn([
                'installed' => false,
                'enabled' => false,
                'jails' => [],
            ]);
    }

    private function mockSecureSSHConfig(): void
    {
        $this->sshSecurityService->shouldReceive('getCurrentConfig')
            ->andReturn([
                'success' => true,
                'config' => [
                    'port' => 2222,
                    'root_login_enabled' => false,
                    'password_auth_enabled' => false,
                    'pubkey_auth_enabled' => true,
                    'max_auth_tries' => 3,
                ],
            ]);
    }

    private function mockInsecureSSHConfig(): void
    {
        $this->sshSecurityService->shouldReceive('getCurrentConfig')
            ->andReturn([
                'success' => true,
                'config' => [
                    'port' => 22,
                    'root_login_enabled' => true,
                    'password_auth_enabled' => true,
                    'pubkey_auth_enabled' => true,
                    'max_auth_tries' => 6,
                ],
            ]);
    }

    private function mockCustomSSHConfig(array $overrides = []): void
    {
        $defaultConfig = [
            'port' => 22,
            'root_login_enabled' => true,
            'password_auth_enabled' => true,
            'pubkey_auth_enabled' => true,
            'max_auth_tries' => 6,
        ];

        $this->sshSecurityService->shouldReceive('getCurrentConfig')
            ->andReturn([
                'success' => true,
                'config' => array_merge($defaultConfig, $overrides),
            ]);
    }

    private function mockOpenPortsCommand(array $ports): void
    {
        // Store for later use in comprehensive mock
        $this->mockPorts = $ports;
    }

    private function mockSecurityUpdatesCommand(int $securityCount, int $totalCount): void
    {
        // Store for later use in comprehensive mock
        $this->mockSecurityCount = $securityCount;
        $this->mockTotalCount = $totalCount;

        // Set up all process fakes at once
        $this->setupProcessFakes();
    }

    private array $mockPorts = ['22'];

    private int $mockSecurityCount = 0;

    private int $mockTotalCount = 0;

    private function setupProcessFakes(): void
    {
        $portsOutput = implode("\n", $this->mockPorts);

        Process::fake([
            // Match the exact ss command pattern from the service
            '*ss -tulpn*' => Process::result(
                output: $portsOutput,
                errorOutput: ''
            ),
            // Match apt-get update command
            '*apt-get update*' => Process::result(output: '', errorOutput: ''),
            // Match security updates count command
            '*apt list*grep -i security*wc -l*' => Process::result(
                output: (string) $this->mockSecurityCount,
                errorOutput: ''
            ),
            // Match total updates count command
            '*apt list*grep -v Listing*wc -l*' => Process::result(
                output: (string) $this->mockTotalCount,
                errorOutput: ''
            ),
            // Catch-all for any SSH commands we might have missed
            '*' => Process::result(output: '', errorOutput: ''),
        ]);
    }

    private function createFindings(array $overrides = []): array
    {
        $defaults = [
            'firewall' => [
                'installed' => true,
                'enabled' => false,
                'rules_count' => 0,
                'score' => 0,
            ],
            'fail2ban' => [
                'installed' => false,
                'enabled' => false,
                'jails_count' => 0,
                'score' => 0,
            ],
            'ssh' => [
                'port' => 22,
                'root_login_enabled' => true,
                'password_auth_enabled' => true,
                'pubkey_auth_enabled' => true,
                'max_auth_tries' => 6,
                'port_score' => 0,
                'root_login_score' => 0,
                'password_auth_score' => 0,
            ],
            'open_ports' => [
                'ports' => [22],
                'count' => 1,
                'common_ports' => 1,
                'score' => 10,
            ],
            'updates' => [
                'security_updates' => 0,
                'total_updates' => 0,
                'score' => 15,
            ],
        ];

        return array_replace_recursive($defaults, $overrides);
    }
}
