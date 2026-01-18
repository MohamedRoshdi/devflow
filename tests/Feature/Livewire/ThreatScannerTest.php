<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\ThreatScanner;
use App\Models\SecurityIncident;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\IncidentResponseService;
use App\Services\Security\ThreatDetectionService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithPermissions;

class ThreatScannerTest extends TestCase
{
    use WithPermissions;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUserWithAllPermissions();
        $this->server = Server::factory()->create(['user_id' => $this->user->id]);
    }

    // ==================== COMPONENT RENDERING ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.security.threat-scanner');
    }

    public function test_component_requires_authentication(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(ThreatScanner::class, ['server' => $this->server]);
    }

    public function test_component_requires_authorization(): void
    {
        $this->withoutExceptionHandling();

        // Create user with permissions but not owner of this server
        $otherUser = $this->createUserWithPermissions(['view-servers']);

        $this->expectException(AuthorizationException::class);

        Livewire::actingAs($otherUser)
            ->test(ThreatScanner::class, ['server' => $this->server]);
    }

    public function test_component_initializes_with_default_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->assertSet('isScanning', false)
            ->assertSet('threats', [])
            ->assertSet('createdIncidents', [])
            ->assertSet('flashMessage', null)
            ->assertSet('flashType', null);
    }

    // ==================== RUN THREAT SCAN - SUCCESS ====================

    public function test_run_threat_scan_no_threats_found(): void
    {
        $this->mock(ThreatDetectionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('scanServer')
                ->once()
                ->with(Mockery::on(fn (Server $s) => $s->id === $this->server->id))
                ->andReturn([
                    'threats' => [],
                    'scan_time' => 5.2,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('flashType', 'success')
            ->assertSee('No threats detected');
    }

    public function test_run_threat_scan_threats_found(): void
    {
        $threats = [
            [
                'type' => 'backdoor_user',
                'severity' => 'critical',
                'title' => 'Backdoor user detected',
                'description' => 'User with UID 0 found',
            ],
        ];

        $incident = SecurityIncident::factory()->backdoorUser()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(ThreatDetectionService::class, function (MockInterface $mock) use ($threats, $incident): void {
            $mock->shouldReceive('scanServer')
                ->once()
                ->andReturn([
                    'threats' => $threats,
                    'scan_time' => 8.5,
                ]);

            $mock->shouldReceive('createIncidentsFromThreats')
                ->once()
                ->andReturn([$incident]);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('flashType', 'warning')
            ->assertSee('1 threat(s) detected');
    }

    public function test_run_threat_scan_updates_scan_time(): void
    {
        $this->mock(ThreatDetectionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('scanServer')
                ->once()
                ->andReturn([
                    'threats' => [],
                    'scan_time' => 12.34,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('scanTime', 12.34);
    }

    // ==================== RUN THREAT SCAN - FAILURE ====================

    public function test_run_threat_scan_failure_shows_error(): void
    {
        $this->mock(ThreatDetectionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('scanServer')
                ->once()
                ->andThrow(new \Exception('SSH connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('flashType', 'error')
            ->assertSee('Scan failed: SSH connection failed');
    }

    public function test_run_threat_scan_failure_resets_scanning_state(): void
    {
        $this->mock(ThreatDetectionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('scanServer')
                ->once()
                ->andThrow(new \Exception('Timeout'));
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('isScanning', false);
    }

    // ==================== REMEDIATION ACTIONS ====================

    public function test_remediate_kill_process(): void
    {
        $incident = SecurityIncident::factory()->suspiciousProcess()->create([
            'server_id' => $this->server->id,
            'affected_items' => ['pid' => 12345],
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('killProcess')
                ->once()
                ->with(Mockery::type(Server::class), 12345)
                ->andReturn(['success' => true, 'message' => 'Process killed']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'kill_process', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee('Process killed');
    }

    public function test_remediate_remove_directory(): void
    {
        $incident = SecurityIncident::factory()->malware()->create([
            'server_id' => $this->server->id,
            'affected_items' => ['path' => '/tmp/.malware'],
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeDirectory')
                ->once()
                ->with(Mockery::type(Server::class), '/tmp/.malware')
                ->andReturn(['success' => true, 'message' => 'Directory removed']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'remove_directory', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee('Directory removed');
    }

    public function test_remediate_block_outbound_ssh(): void
    {
        $incident = SecurityIncident::factory()->outboundAttack()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('blockOutboundSSH')
                ->once()
                ->with(Mockery::type(Server::class))
                ->andReturn(['success' => true, 'message' => 'Outbound SSH blocked']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'block_outbound_ssh', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee('Outbound SSH blocked');
    }

    public function test_remediate_incident_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'kill_process', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    public function test_remediate_incident_wrong_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);
        $incident = SecurityIncident::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'kill_process', $incident->id)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    public function test_remediate_unknown_action(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'unknown_action', $incident->id)
            ->assertSet('flashType', 'error')
            ->assertSee('Unknown action');
    }

    // ==================== RESOLVE INCIDENT ====================

    public function test_resolve_incident_success(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('resolveIncident', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee('Incident marked as resolved');

        $this->assertEquals(
            SecurityIncident::STATUS_RESOLVED,
            $incident->fresh()->status
        );
    }

    public function test_resolve_incident_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('resolveIncident', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    // ==================== MARK FALSE POSITIVE ====================

    public function test_mark_false_positive_success(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('markFalsePositive', $incident->id)
            ->assertSet('flashType', 'info')
            ->assertSee('Incident marked as false positive');

        $this->assertEquals(
            SecurityIncident::STATUS_FALSE_POSITIVE,
            $incident->fresh()->status
        );
    }

    public function test_mark_false_positive_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('markFalsePositive', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    // ==================== TOGGLE AUTO REMEDIATION ====================

    public function test_toggle_auto_remediation_enables(): void
    {
        $this->server->update(['auto_remediation_enabled' => false]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('toggleAutoRemediation')
            ->assertSet('flashType', 'info')
            ->assertSee('Auto-remediation enabled');

        $this->assertTrue($this->server->fresh()->auto_remediation_enabled);
    }

    public function test_toggle_auto_remediation_disables(): void
    {
        $this->server->update(['auto_remediation_enabled' => true]);

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('toggleAutoRemediation')
            ->assertSet('flashType', 'info')
            ->assertSee('Auto-remediation disabled');

        $this->assertFalse($this->server->fresh()->auto_remediation_enabled);
    }

    // ==================== LOCKDOWN MODE ====================

    public function test_enable_lockdown_success(): void
    {
        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enableLockdownMode')
                ->once()
                ->with(Mockery::type(Server::class))
                ->andReturn(['success' => true, 'message' => 'Lockdown enabled']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('enableLockdown')
            ->assertSet('flashType', 'warning')
            ->assertSee('Lockdown mode enabled');
    }

    public function test_enable_lockdown_failure(): void
    {
        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enableLockdownMode')
                ->once()
                ->andReturn(['success' => false, 'message' => 'UFW not installed']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('enableLockdown')
            ->assertSet('flashType', 'error')
            ->assertSee('Failed to enable lockdown');
    }

    public function test_disable_lockdown_success(): void
    {
        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('disableLockdownMode')
                ->once()
                ->with(Mockery::type(Server::class))
                ->andReturn(['success' => true, 'message' => 'Lockdown disabled']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('disableLockdown')
            ->assertSet('flashType', 'success')
            ->assertSee('Lockdown mode disabled');
    }

    public function test_disable_lockdown_failure(): void
    {
        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('disableLockdownMode')
                ->once()
                ->andReturn(['success' => false, 'message' => 'Command failed']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('disableLockdown')
            ->assertSet('flashType', 'error')
            ->assertSee('Failed to disable lockdown');
    }

    // ==================== AUTO REMEDIATE ALL ====================

    public function test_auto_remediate_all_success(): void
    {
        SecurityIncident::factory()->critical()->create([
            'server_id' => $this->server->id,
        ]);

        SecurityIncident::factory()->high()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('autoRemediate')
                ->twice()
                ->andReturn(['success' => true, 'message' => 'Remediated']);
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('autoRemediateAll')
            ->assertSet('flashType', 'success')
            ->assertSee('Auto-remediation complete: 2 succeeded, 0 failed');
    }

    public function test_auto_remediate_all_partial_failure(): void
    {
        SecurityIncident::factory()->critical()->create([
            'server_id' => $this->server->id,
        ]);

        SecurityIncident::factory()->high()->create([
            'server_id' => $this->server->id,
        ]);

        $callCount = 0;
        $this->mock(IncidentResponseService::class, function (MockInterface $mock) use (&$callCount): void {
            $mock->shouldReceive('autoRemediate')
                ->twice()
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;

                    return $callCount === 1
                        ? ['success' => true, 'message' => 'Success']
                        : ['success' => false, 'message' => 'Failed'];
                });
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('autoRemediateAll')
            ->assertSet('flashType', 'warning')
            ->assertSee('Auto-remediation complete: 1 succeeded, 1 failed');
    }

    // ==================== ACTIVE INCIDENTS PROPERTY ====================

    public function test_active_incidents_property_returns_server_incidents(): void
    {
        SecurityIncident::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        SecurityIncident::factory()->resolved()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server]);

        $activeIncidents = $component->instance()->activeIncidents;
        $this->assertCount(3, $activeIncidents);
    }

    public function test_active_incidents_excludes_other_servers(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        SecurityIncident::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        SecurityIncident::factory()->count(5)->create([
            'server_id' => $otherServer->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server]);

        $activeIncidents = $component->instance()->activeIncidents;
        $this->assertCount(2, $activeIncidents);
    }

    public function test_active_incidents_ordered_by_detected_at_desc(): void
    {
        $older = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
            'detected_at' => now()->subDays(2),
        ]);

        $newer = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
            'detected_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server]);

        $activeIncidents = $component->instance()->activeIncidents;
        $this->assertEquals($newer->id, $activeIncidents->first()->id);
    }

    // ==================== EDGE CASES ====================

    public function test_handles_exception_in_remediation(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'affected_items' => ['pid' => 12345],
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('killProcess')
                ->once()
                ->andThrow(new \Exception('Connection lost'));
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('remediate', 'kill_process', $incident->id)
            ->assertSet('flashType', 'error')
            ->assertSee('Error: Connection lost');
    }

    public function test_handles_exception_in_lockdown(): void
    {
        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('enableLockdownMode')
                ->once()
                ->andThrow(new \Exception('Firewall error'));
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('enableLockdown')
            ->assertSet('flashType', 'error')
            ->assertSee('Error: Firewall error');
    }

    public function test_multiple_threat_scans(): void
    {
        $this->mock(ThreatDetectionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('scanServer')
                ->twice()
                ->andReturn(
                    ['threats' => [], 'scan_time' => 3.0],
                    ['threats' => [], 'scan_time' => 4.0]
                );
        });

        Livewire::actingAs($this->user)
            ->test(ThreatScanner::class, ['server' => $this->server])
            ->call('runThreatScan')
            ->assertSet('scanTime', 3.0)
            ->call('runThreatScan')
            ->assertSet('scanTime', 4.0);
    }
}
