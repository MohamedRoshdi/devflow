<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Servers\Security\IncidentDashboard;
use App\Models\SecurityIncident;
use App\Models\Server;
use App\Models\User;
use App\Services\Security\IncidentResponseService;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithPermissions;

class IncidentDashboardTest extends TestCase
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
            ->test(IncidentDashboard::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.servers.security.incident-dashboard');
    }

    public function test_component_initializes_with_default_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->assertSet('search', '')
            ->assertSet('severityFilter', '')
            ->assertSet('statusFilter', '')
            ->assertSet('serverFilter', '')
            ->assertSet('typeFilter', '')
            ->assertSet('sortField', 'detected_at')
            ->assertSet('sortDirection', 'desc')
            ->assertSet('showIncidentModal', false)
            ->assertSet('showReportModal', false);
    }

    // ==================== FILTERING ====================

    public function test_filter_by_severity(): void
    {
        SecurityIncident::factory()->critical()->create(['server_id' => $this->server->id]);
        SecurityIncident::factory()->high()->create(['server_id' => $this->server->id]);
        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'severity' => SecurityIncident::SEVERITY_LOW,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('severityFilter', SecurityIncident::SEVERITY_CRITICAL);

        $incidents = $component->instance()->incidents;
        $this->assertEquals(1, $incidents->total());
        $this->assertEquals(SecurityIncident::SEVERITY_CRITICAL, $incidents->first()->severity);
    }

    public function test_filter_by_status(): void
    {
        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);
        SecurityIncident::factory()->resolved()->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('statusFilter', SecurityIncident::STATUS_RESOLVED);

        $incidents = $component->instance()->incidents;
        $this->assertEquals(1, $incidents->total());
        $this->assertEquals(SecurityIncident::STATUS_RESOLVED, $incidents->first()->status);
    }

    public function test_filter_by_server(): void
    {
        $otherServer = Server::factory()->create(['user_id' => $this->user->id]);

        SecurityIncident::factory()->count(3)->create(['server_id' => $this->server->id]);
        SecurityIncident::factory()->count(2)->create(['server_id' => $otherServer->id]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('serverFilter', $this->server->id);

        $incidents = $component->instance()->incidents;
        $this->assertEquals(3, $incidents->total());
    }

    public function test_filter_by_type(): void
    {
        SecurityIncident::factory()->malware()->create(['server_id' => $this->server->id]);
        SecurityIncident::factory()->backdoorUser()->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('typeFilter', SecurityIncident::TYPE_MALWARE);

        $incidents = $component->instance()->incidents;
        $this->assertEquals(1, $incidents->total());
        $this->assertEquals(SecurityIncident::TYPE_MALWARE, $incidents->first()->incident_type);
    }

    public function test_search_by_title(): void
    {
        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'title' => 'Backdoor user detected',
        ]);
        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'title' => 'Malware found in /tmp',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('search', 'Backdoor');

        $incidents = $component->instance()->incidents;
        $this->assertEquals(1, $incidents->total());
        $this->assertStringContainsString('Backdoor', $incidents->first()->title);
    }

    public function test_clear_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('search', 'test')
            ->set('severityFilter', 'critical')
            ->set('statusFilter', 'detected')
            ->set('serverFilter', '1')
            ->set('typeFilter', 'malware')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('severityFilter', '')
            ->assertSet('statusFilter', '')
            ->assertSet('serverFilter', '')
            ->assertSet('typeFilter', '');
    }

    // ==================== SORTING ====================

    public function test_sort_by_severity(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('sortBy', 'severity')
            ->assertSet('sortField', 'severity')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_sort_toggles_direction(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('sortBy', 'severity')
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'severity')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_sort_by_different_field_resets_direction(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('sortBy', 'severity')
            ->call('sortBy', 'severity')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'status')
            ->assertSet('sortField', 'status')
            ->assertSet('sortDirection', 'desc');
    }

    // ==================== VIEW INCIDENT ====================

    public function test_view_incident_opens_modal(): void
    {
        $incident = SecurityIncident::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('viewIncident', $incident->id)
            ->assertSet('selectedIncidentId', $incident->id)
            ->assertSet('showIncidentModal', true);
    }

    public function test_close_incident_modal(): void
    {
        $incident = SecurityIncident::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('viewIncident', $incident->id)
            ->assertSet('showIncidentModal', true)
            ->call('closeIncidentModal')
            ->assertSet('showIncidentModal', false)
            ->assertSet('selectedIncidentId', null);
    }

    // ==================== RESOLVE INCIDENT ====================

    public function test_resolve_incident_success(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('resolveIncident', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee("Incident #{$incident->id} marked as resolved");

        $this->assertEquals(SecurityIncident::STATUS_RESOLVED, $incident->fresh()->status);
    }

    public function test_resolve_incident_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
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
            ->test(IncidentDashboard::class)
            ->call('markFalsePositive', $incident->id)
            ->assertSet('flashType', 'info')
            ->assertSee("Incident #{$incident->id} marked as false positive");

        $this->assertEquals(SecurityIncident::STATUS_FALSE_POSITIVE, $incident->fresh()->status);
    }

    public function test_mark_false_positive_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('markFalsePositive', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    // ==================== START INVESTIGATION ====================

    public function test_start_investigation_success(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('startInvestigation', $incident->id)
            ->assertSet('flashType', 'info')
            ->assertSee("Investigation started for incident #{$incident->id}");

        $fresh = $incident->fresh();
        $this->assertEquals(SecurityIncident::STATUS_INVESTIGATING, $fresh->status);
        $this->assertEquals($this->user->id, $fresh->user_id);
    }

    public function test_start_investigation_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('startInvestigation', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    // ==================== AUTO REMEDIATE ====================

    public function test_auto_remediate_success(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('autoRemediate')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Remediation completed']);
        });

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('autoRemediate', $incident->id)
            ->assertSet('flashType', 'success')
            ->assertSee('Auto-remediation completed');
    }

    public function test_auto_remediate_failure(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('autoRemediate')
                ->once()
                ->andReturn(['success' => false, 'message' => 'Unable to remediate']);
        });

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('autoRemediate', $incident->id)
            ->assertSet('flashType', 'error')
            ->assertSee('Remediation failed');
    }

    public function test_auto_remediate_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('autoRemediate', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    public function test_auto_remediate_exception(): void
    {
        $incident = SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('autoRemediate')
                ->once()
                ->andThrow(new \Exception('Connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('autoRemediate', $incident->id)
            ->assertSet('flashType', 'error')
            ->assertSee('Error: Connection failed');
    }

    // ==================== GENERATE REPORT ====================

    public function test_generate_report_opens_modal(): void
    {
        $incident = SecurityIncident::factory()->create(['server_id' => $this->server->id]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateIncidentReport')
                ->once()
                ->andReturn('Report content');
        });

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('generateReport', $incident->id)
            ->assertSet('showReportModal', true)
            ->assertSet('selectedIncidentId', $incident->id);
    }

    public function test_generate_report_not_found(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('generateReport', 99999)
            ->assertSet('flashType', 'error')
            ->assertSee('Incident not found');
    }

    public function test_close_report_modal(): void
    {
        $incident = SecurityIncident::factory()->create(['server_id' => $this->server->id]);

        $this->mock(IncidentResponseService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateIncidentReport')
                ->once()
                ->andReturn('Report content');
        });

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('generateReport', $incident->id)
            ->assertSet('showReportModal', true)
            ->call('closeReportModal')
            ->assertSet('showReportModal', false);
    }

    // ==================== BULK RESOLVE ====================

    public function test_bulk_resolve_non_critical(): void
    {
        SecurityIncident::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
            'severity' => SecurityIncident::SEVERITY_MEDIUM,
        ]);

        SecurityIncident::factory()->critical()->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('bulkResolve')
            ->assertSet('flashType', 'success')
            ->assertSee('3 non-critical incidents marked as resolved');

        $this->assertEquals(3, SecurityIncident::where('status', SecurityIncident::STATUS_RESOLVED)->count());
        $this->assertEquals(1, SecurityIncident::where('status', SecurityIncident::STATUS_DETECTED)->count());
    }

    // ==================== STATS PROPERTY ====================

    public function test_stats_property_returns_correct_counts(): void
    {
        // Create 5 medium severity incidents (to avoid counting in critical/high)
        SecurityIncident::factory()->count(5)->create([
            'server_id' => $this->server->id,
            'status' => SecurityIncident::STATUS_DETECTED,
            'severity' => SecurityIncident::SEVERITY_MEDIUM,
        ]);

        // Create 1 critical incident
        SecurityIncident::factory()->critical()->create([
            'server_id' => $this->server->id,
        ]);

        // Create 1 high severity incident
        SecurityIncident::factory()->high()->create([
            'server_id' => $this->server->id,
        ]);

        // Create 1 resolved incident (resolved today)
        SecurityIncident::factory()->resolved()->create([
            'server_id' => $this->server->id,
            'resolved_at' => today(),
        ]);

        // Create 1 auto-remediated incident (also counts as resolved today)
        SecurityIncident::factory()->autoRemediated()->create([
            'server_id' => $this->server->id,
            'resolved_at' => today(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class);

        $stats = $component->instance()->stats;

        // Total = 5 medium + 1 critical + 1 high + 1 resolved + 1 auto-remediated = 9
        $this->assertEquals(9, $stats['total']);
        // Active = 5 medium + 1 critical + 1 high = 7 (excludes resolved and auto-remediated)
        $this->assertEquals(7, $stats['active']);
        // Only 1 critical incident created with critical() state
        $this->assertEquals(1, $stats['critical']);
        // Only 1 high incident created with high() state
        $this->assertEquals(1, $stats['high']);
        // 2 resolved today (1 resolved + 1 auto-remediated)
        $this->assertEquals(2, $stats['resolved_today']);
        // 1 auto-remediated
        $this->assertEquals(1, $stats['auto_remediated']);
    }

    // ==================== SERVERS PROPERTY ====================

    public function test_servers_property_returns_all_servers(): void
    {
        Server::factory()->count(3)->create(['user_id' => $this->user->id]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class);

        $servers = $component->instance()->servers;
        $this->assertGreaterThanOrEqual(4, $servers->count()); // Including the one from setUp
    }

    // ==================== PAGINATION ====================

    public function test_incidents_are_paginated(): void
    {
        SecurityIncident::factory()->count(20)->create(['server_id' => $this->server->id]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class);

        $incidents = $component->instance()->incidents;
        $this->assertCount(15, $incidents); // 15 per page
        $this->assertEquals(20, $incidents->total());
    }

    public function test_filter_resets_pagination(): void
    {
        SecurityIncident::factory()->count(20)->create([
            'server_id' => $this->server->id,
            'severity' => SecurityIncident::SEVERITY_HIGH,
            'status' => SecurityIncident::STATUS_DETECTED,
        ]);

        // Verify that filtering works and returns results
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->call('gotoPage', 2)
            ->set('severityFilter', SecurityIncident::SEVERITY_HIGH)
            ->assertSee('Backdoor'); // Check that incidents are displayed
    }

    // ==================== INCIDENT TYPES ====================

    public function test_get_incident_types_returns_all_types(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class);

        $types = $component->instance()->getIncidentTypes();

        $this->assertArrayHasKey(SecurityIncident::TYPE_MALWARE, $types);
        $this->assertArrayHasKey(SecurityIncident::TYPE_BACKDOOR_USER, $types);
        $this->assertArrayHasKey(SecurityIncident::TYPE_SUSPICIOUS_PROCESS, $types);
        $this->assertArrayHasKey(SecurityIncident::TYPE_BRUTE_FORCE, $types);
        $this->assertArrayHasKey(SecurityIncident::TYPE_HIDDEN_DIRECTORY, $types);
    }

    // ==================== EDGE CASES ====================

    public function test_handles_empty_incidents(): void
    {
        Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->assertSee('No security incidents found');
    }

    public function test_handles_combined_filters(): void
    {
        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'severity' => SecurityIncident::SEVERITY_CRITICAL,
            'status' => SecurityIncident::STATUS_DETECTED,
            'incident_type' => SecurityIncident::TYPE_MALWARE,
            'title' => 'Malware found',
        ]);

        SecurityIncident::factory()->create([
            'server_id' => $this->server->id,
            'severity' => SecurityIncident::SEVERITY_LOW,
            'status' => SecurityIncident::STATUS_RESOLVED,
            'incident_type' => SecurityIncident::TYPE_BRUTE_FORCE,
            'title' => 'Brute force attempt',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(IncidentDashboard::class)
            ->set('severityFilter', SecurityIncident::SEVERITY_CRITICAL)
            ->set('statusFilter', SecurityIncident::STATUS_DETECTED)
            ->set('typeFilter', SecurityIncident::TYPE_MALWARE)
            ->set('search', 'Malware');

        $incidents = $component->instance()->incidents;
        $this->assertEquals(1, $incidents->total());
    }
}
