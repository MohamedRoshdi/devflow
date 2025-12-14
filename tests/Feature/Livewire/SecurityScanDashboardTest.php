<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\SecurityScanDashboard;
use App\Models\SecurityScan;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\SecurityScoreService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SecurityScanDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.security.security-scan-dashboard');
    }

    public function test_component_requires_authentication(): void
    {
        $this->expectException(AuthorizationException::class);

        Livewire::test(SecurityScanDashboard::class, ['server' => $this->server]);
    }

    public function test_component_requires_authorization(): void
    {
        $otherUser = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        Livewire::actingAs($otherUser)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);
    }

    public function test_component_initializes_with_default_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->assertSet('isScanning', false)
            ->assertSet('showDetails', false)
            ->assertSet('selectedScan', null)
            ->assertSet('flashMessage', null)
            ->assertSet('flashType', null);
    }

    // ==================== RUN SCAN - SUCCESS ====================

    public function test_run_scan_success(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 85,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->with(Mockery::on(fn (Server $s) => $s->id === $this->server->id))
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'success')
            ->assertSee('Security scan completed. Score: 85/100');
    }

    public function test_run_scan_shows_loading_state(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 75,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('isScanning', false); // After completion
    }

    public function test_run_scan_refreshes_server_data(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 90,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'success');
    }

    public function test_run_scan_with_high_score(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 95,
            'risk_level' => SecurityScan::RISK_SECURE,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Security scan completed. Score: 95/100');
    }

    public function test_run_scan_with_low_score(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 25,
            'risk_level' => SecurityScan::RISK_CRITICAL,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Security scan completed. Score: 25/100');
    }

    // ==================== RUN SCAN - FAILURE ====================

    public function test_run_scan_failure_shows_error(): void
    {
        $this->mock(SecurityScoreService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andThrow(new \Exception('Connection timeout'));
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'error')
            ->assertSee('Scan failed: Connection timeout');
    }

    public function test_run_scan_failure_resets_loading_state(): void
    {
        $this->mock(SecurityScoreService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andThrow(new \Exception('SSH error'));
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('isScanning', false);
    }

    public function test_run_scan_failure_with_network_error(): void
    {
        $this->mock(SecurityScoreService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andThrow(new \Exception('Network unreachable'));
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'error')
            ->assertSee('Scan failed: Network unreachable');
    }

    public function test_run_scan_failure_with_permission_error(): void
    {
        $this->mock(SecurityScoreService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andThrow(new \Exception('Permission denied'));
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'error')
            ->assertSee('Scan failed: Permission denied');
    }

    // ==================== VIEW SCAN DETAILS ====================

    public function test_view_scan_details_opens_modal(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 80,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('showDetails', true)
            ->assertSet('selectedScan.id', $scan->id);
    }

    public function test_view_scan_details_loads_scan_data(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 72,
            'risk_level' => SecurityScan::RISK_MEDIUM,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('selectedScan.score', 72)
            ->assertSet('selectedScan.risk_level', SecurityScan::RISK_MEDIUM);
    }

    public function test_view_scan_details_with_findings(): void
    {
        $findings = [
            ['category' => 'Firewall', 'severity' => 'high', 'message' => 'UFW disabled'],
            ['category' => 'SSH', 'severity' => 'medium', 'message' => 'Root login enabled'],
        ];

        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'findings' => $findings,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('selectedScan.findings', $findings);
    }

    public function test_view_scan_details_with_recommendations(): void
    {
        $recommendations = [
            ['priority' => 'high', 'title' => 'Enable Firewall'],
            ['priority' => 'medium', 'title' => 'Disable Root Login'],
        ];

        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'recommendations' => $recommendations,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('selectedScan.recommendations', $recommendations);
    }

    public function test_view_scan_details_with_nonexistent_scan(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', 99999)
            ->assertSet('selectedScan', null)
            ->assertSet('showDetails', true);
    }

    // ==================== CLOSE DETAILS ====================

    public function test_close_details_closes_modal(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false)
            ->assertSet('selectedScan', null);
    }

    public function test_close_details_clears_selected_scan(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertNotNull('selectedScan')
            ->call('closeDetails')
            ->assertSet('selectedScan', null);
    }

    // ==================== SCANS PROPERTY (PAGINATION) ====================

    public function test_scans_property_returns_paginated_results(): void
    {
        SecurityScan::factory()->count(15)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertCount(10, $scans);
        $this->assertEquals(15, $scans->total());
    }

    public function test_scans_property_orders_by_created_at_desc(): void
    {
        $oldScan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);

        $newScan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals($newScan->id, $scans->first()->id);
    }

    public function test_scans_property_only_shows_server_scans(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        SecurityScan::factory()->count(5)->create([
            'server_id' => $this->server->id,
        ]);

        SecurityScan::factory()->count(3)->create([
            'server_id' => $otherServer->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(5, $scans->total());
    }

    public function test_scans_property_with_no_scans(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(0, $scans->total());
    }

    // ==================== LATEST SCAN PROPERTY ====================

    public function test_latest_scan_property_returns_most_recent(): void
    {
        SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(2),
            'score' => 60,
        ]);

        $latestScan = SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
            'score' => 85,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $latest = $component->viewData('latestScan');
        $this->assertEquals($latestScan->id, $latest->id);
        $this->assertEquals(85, $latest->score);
    }

    public function test_latest_scan_property_returns_null_when_no_scans(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $latest = $component->viewData('latestScan');
        $this->assertNull($latest);
    }

    // ==================== FLASH MESSAGES ====================

    public function test_flash_message_cleared_on_new_scan(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 70,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->twice()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSet('flashType', 'success')
            ->call('runScan')
            ->assertSet('flashType', 'success');
    }

    public function test_flash_message_shows_score(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 88,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Score: 88/100');
    }

    // ==================== SCAN STATUS VARIATIONS ====================

    public function test_displays_pending_scans(): void
    {
        SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityScan::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::STATUS_PENDING, $scans->first()->status);
    }

    public function test_displays_running_scans(): void
    {
        SecurityScan::factory()->running()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::STATUS_RUNNING, $scans->first()->status);
    }

    public function test_displays_failed_scans(): void
    {
        SecurityScan::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityScan::STATUS_FAILED,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::STATUS_FAILED, $scans->first()->status);
    }

    public function test_displays_completed_scans(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::STATUS_COMPLETED, $scans->first()->status);
    }

    // ==================== RISK LEVEL VARIATIONS ====================

    public function test_displays_critical_risk_scan(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 20,
            'risk_level' => SecurityScan::RISK_CRITICAL,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::RISK_CRITICAL, $scans->first()->risk_level);
    }

    public function test_displays_high_risk_scan(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 45,
            'risk_level' => SecurityScan::RISK_HIGH,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::RISK_HIGH, $scans->first()->risk_level);
    }

    public function test_displays_medium_risk_scan(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 65,
            'risk_level' => SecurityScan::RISK_MEDIUM,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::RISK_MEDIUM, $scans->first()->risk_level);
    }

    public function test_displays_low_risk_scan(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 85,
            'risk_level' => SecurityScan::RISK_LOW,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::RISK_LOW, $scans->first()->risk_level);
    }

    public function test_displays_secure_risk_scan(): void
    {
        SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 95,
            'risk_level' => SecurityScan::RISK_SECURE,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server]);

        $scans = $component->viewData('scans');
        $this->assertEquals(SecurityScan::RISK_SECURE, $scans->first()->risk_level);
    }

    // ==================== EDGE CASES ====================

    public function test_handles_zero_score(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 0,
            'risk_level' => SecurityScan::RISK_CRITICAL,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Score: 0/100');
    }

    public function test_handles_perfect_score(): void
    {
        $mockScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 100,
            'risk_level' => SecurityScan::RISK_SECURE,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($mockScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Score: 100/100');
    }

    public function test_handles_empty_findings(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'findings' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('selectedScan.findings', []);
    }

    public function test_handles_empty_recommendations(): void
    {
        $scan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'recommendations' => [],
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $scan->id)
            ->assertSet('selectedScan.recommendations', []);
    }

    public function test_multiple_rapid_scans(): void
    {
        $mockScan1 = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 70,
        ]);

        $mockScan2 = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 75,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($mockScan1, $mockScan2): void {
            $mock->shouldReceive('runSecurityScan')
                ->twice()
                ->andReturn($mockScan1, $mockScan2);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('runScan')
            ->assertSee('Score: 70/100')
            ->call('runScan')
            ->assertSee('Score: 75/100');
    }

    public function test_view_details_then_run_new_scan(): void
    {
        $existingScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 65,
        ]);

        $newScan = SecurityScan::factory()->completed()->create([
            'server_id' => $this->server->id,
            'score' => 80,
        ]);

        $this->mock(SecurityScoreService::class, function (MockInterface $mock) use ($newScan): void {
            $mock->shouldReceive('runSecurityScan')
                ->once()
                ->andReturn($newScan);
        });

        Livewire::actingAs($this->user)
            ->test(SecurityScanDashboard::class, ['server' => $this->server])
            ->call('viewScanDetails', $existingScan->id)
            ->assertSet('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false)
            ->call('runScan')
            ->assertSee('Score: 80/100');
    }
}
