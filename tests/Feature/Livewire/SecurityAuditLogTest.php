<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Logs\SecurityAuditLog;
use App\Models\SecurityEvent;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAuditLogTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['name' => 'Production Server']);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->assertSet('search', '')
            ->assertSet('serverFilter', '')
            ->assertSet('eventTypeFilter', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('showDetails', false)
            ->assertSet('selectedEvent', []);
    }

    // ==================== EVENTS DISPLAY TESTS ====================

    public function test_displays_security_events(): void
    {
        SecurityEvent::factory()->count(5)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertCount(5, $events);
    }

    public function test_events_have_pagination(): void
    {
        SecurityEvent::factory()->count(30)->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertEquals(20, $events->perPage());
    }

    public function test_events_are_ordered_by_created_at_descending(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'details' => 'Old event',
            'created_at' => now()->subHour(),
        ]);

        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'details' => 'New event',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertEquals('New event', $events->first()->details);
    }

    // ==================== SEARCH TESTS ====================

    public function test_can_search_by_details(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'details' => 'Firewall rule added for SSH',
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'details' => 'IP address banned',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('search', 'Firewall');

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
        $this->assertStringContainsString('Firewall', $events->first()->details);
    }

    public function test_can_search_by_source_ip(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'source_ip' => '192.168.1.100',
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'source_ip' => '10.0.0.50',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('search', '192.168');

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
        $this->assertEquals('192.168.1.100', $events->first()->source_ip);
    }

    public function test_search_resets_pagination(): void
    {
        SecurityEvent::factory()->count(25)->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('page', 2)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    // ==================== SERVER FILTER TESTS ====================

    public function test_can_filter_by_server(): void
    {
        $otherServer = Server::factory()->create(['name' => 'Staging Server']);

        SecurityEvent::factory()->count(4)->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(2)->create([
            'server_id' => $otherServer->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('serverFilter', (string) $this->server->id);

        $events = $component->viewData('events');
        $this->assertCount(4, $events);
    }

    // ==================== EVENT TYPE FILTER TESTS ====================

    public function test_can_filter_by_event_type(): void
    {
        SecurityEvent::factory()->count(3)->firewallEnabled()->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(2)->ipBanned()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('eventTypeFilter', SecurityEvent::TYPE_FIREWALL_ENABLED);

        $events = $component->viewData('events');
        $this->assertCount(3, $events);
    }

    public function test_can_filter_by_ip_banned_type(): void
    {
        SecurityEvent::factory()->count(3)->firewallEnabled()->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(2)->ipBanned()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('eventTypeFilter', SecurityEvent::TYPE_IP_BANNED);

        $events = $component->viewData('events');
        $this->assertCount(2, $events);
    }

    public function test_can_filter_by_security_scan_type(): void
    {
        SecurityEvent::factory()->count(2)->firewallEnabled()->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(4)->securityScan()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('eventTypeFilter', SecurityEvent::TYPE_SECURITY_SCAN);

        $events = $component->viewData('events');
        $this->assertCount(4, $events);
    }

    // ==================== DATE FILTER TESTS ====================

    public function test_can_filter_by_date_from(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('dateFrom', now()->subDay()->format('Y-m-d'));

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
    }

    public function test_can_filter_by_date_to(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('dateTo', now()->subDays(3)->format('Y-m-d'));

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
    }

    public function test_can_filter_by_date_range(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(10),
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(5),
        ]);
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
            ->set('dateTo', now()->subDays(3)->format('Y-m-d'));

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
    }

    // ==================== CLEAR FILTERS TESTS ====================

    public function test_can_clear_all_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('search', 'test')
            ->set('serverFilter', (string) $this->server->id)
            ->set('eventTypeFilter', SecurityEvent::TYPE_FIREWALL_ENABLED)
            ->set('dateFrom', now()->subWeek()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('serverFilter', '')
            ->assertSet('eventTypeFilter', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    public function test_clear_filters_resets_pagination(): void
    {
        SecurityEvent::factory()->count(25)->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('page', 2)
            ->call('clearFilters')
            ->assertSet('page', 1);
    }

    // ==================== VIEW DETAILS TESTS ====================

    public function test_can_view_event_details(): void
    {
        $event = SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->call('viewDetails', $event->id)
            ->assertSet('showDetails', true);
    }

    public function test_view_details_populates_selected_event(): void
    {
        $event = SecurityEvent::factory()->firewallEnabled()->create([
            'server_id' => $this->server->id,
            'source_ip' => '203.0.113.50',
            'user_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->call('viewDetails', $event->id);

        $selected = $component->get('selectedEvent');
        $this->assertEquals($event->id, $selected['id']);
        $this->assertEquals('Production Server', $selected['server']);
        $this->assertEquals(SecurityEvent::TYPE_FIREWALL_ENABLED, $selected['event_type']);
        $this->assertEquals('Firewall Enabled', $selected['event_type_label']);
        $this->assertEquals('203.0.113.50', $selected['source_ip']);
        $this->assertEquals($this->user->name, $selected['user']);
    }

    public function test_view_details_handles_non_existent_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->call('viewDetails', 999999)
            ->assertSet('showDetails', false)
            ->assertSet('selectedEvent', []);
    }

    public function test_view_details_shows_system_for_null_user(): void
    {
        $event = SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => null,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->call('viewDetails', $event->id);

        $selected = $component->get('selectedEvent');
        $this->assertEquals('System', $selected['user']);
    }

    public function test_can_close_details(): void
    {
        $event = SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->call('viewDetails', $event->id)
            ->assertSet('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false)
            ->assertSet('selectedEvent', []);
    }

    // ==================== COMPUTED PROPERTIES TESTS ====================

    public function test_servers_property_returns_all_servers(): void
    {
        Server::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $servers = $component->viewData('servers');
        $this->assertCount(4, $servers);
    }

    public function test_servers_are_ordered_by_name(): void
    {
        Server::factory()->create(['name' => 'Zebra Server']);
        Server::factory()->create(['name' => 'Alpha Server']);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $servers = $component->viewData('servers');
        $this->assertEquals('Alpha Server', $servers->first()->name);
    }

    public function test_event_types_property_returns_all_types(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $eventTypes = $component->viewData('eventTypes');
        $this->assertArrayHasKey(SecurityEvent::TYPE_FIREWALL_ENABLED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_FIREWALL_DISABLED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_RULE_ADDED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_RULE_DELETED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_IP_BANNED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_IP_UNBANNED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_SSH_CONFIG_CHANGED, $eventTypes);
        $this->assertArrayHasKey(SecurityEvent::TYPE_SECURITY_SCAN, $eventTypes);
    }

    // ==================== STATS TESTS ====================

    public function test_stats_property_returns_counts(): void
    {
        SecurityEvent::factory()->count(5)->firewallEnabled()->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(3)->ipBanned()->create([
            'server_id' => $this->server->id,
        ]);
        SecurityEvent::factory()->count(2)->securityScan()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['firewall_events']);
        $this->assertEquals(3, $stats['ip_bans']);
    }

    public function test_stats_today_counts_only_today(): void
    {
        SecurityEvent::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);
        SecurityEvent::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['today']);
    }

    public function test_stats_empty_when_no_events(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['today']);
        $this->assertEquals(0, $stats['firewall_events']);
        $this->assertEquals(0, $stats['ip_bans']);
    }

    // ==================== COMBINED FILTERS TESTS ====================

    public function test_can_apply_multiple_filters(): void
    {
        $event = SecurityEvent::factory()->firewallEnabled()->create([
            'server_id' => $this->server->id,
            'created_at' => now(),
        ]);

        SecurityEvent::factory()->firewallEnabled()->create([
            'server_id' => $this->server->id,
            'created_at' => now()->subDays(10),
        ]);

        $otherServer = Server::factory()->create();
        SecurityEvent::factory()->firewallEnabled()->create([
            'server_id' => $otherServer->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('serverFilter', (string) $this->server->id)
            ->set('eventTypeFilter', SecurityEvent::TYPE_FIREWALL_ENABLED)
            ->set('dateFrom', now()->subDay()->format('Y-m-d'));

        $events = $component->viewData('events');
        $this->assertCount(1, $events);
        $this->assertEquals($event->id, $events->first()->id);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_events(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertCount(0, $events);
    }

    public function test_handles_no_matching_events(): void
    {
        SecurityEvent::factory()->firewallEnabled()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class)
            ->set('eventTypeFilter', SecurityEvent::TYPE_IP_BANNED);

        $events = $component->viewData('events');
        $this->assertCount(0, $events);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_events_include_server_relationship(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertTrue($events->first()->relationLoaded('server'));
        $this->assertEquals('Production Server', $events->first()->server->name);
    }

    public function test_events_include_user_relationship(): void
    {
        SecurityEvent::factory()->create([
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(SecurityAuditLog::class);

        $events = $component->viewData('events');
        $this->assertTrue($events->first()->relationLoaded('user'));
        $this->assertEquals($this->user->name, $events->first()->user->name);
    }
}
