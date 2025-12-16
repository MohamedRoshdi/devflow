<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Logs\NotificationLogs;
use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationLogsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private NotificationChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->channel = NotificationChannel::factory()->create(['name' => 'Main Channel']);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('channelFilter', '')
            ->assertSet('eventTypeFilter', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('showDetails', false)
            ->assertSet('selectedLog', []);
    }

    // ==================== LOGS DISPLAY TESTS ====================

    public function test_displays_logs(): void
    {
        NotificationLog::factory()->count(5)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $logs = $component->viewData('logs');
        $this->assertCount(5, $logs);
    }

    public function test_logs_have_pagination(): void
    {
        NotificationLog::factory()->count(30)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $logs = $component->viewData('logs');
        $this->assertEquals(20, $logs->perPage());
    }

    public function test_logs_are_ordered_by_created_at_descending(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'old.event',
            'created_at' => now()->subHour(),
        ]);

        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'new.event',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $logs = $component->viewData('logs');
        $this->assertEquals('new.event', $logs->first()->event_type);
    }

    // ==================== SEARCH TESTS ====================

    public function test_can_search_by_event_type(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'backup.started',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('search', 'deployment');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('deployment', $logs->first()->event_type);
    }

    public function test_can_search_by_error_message(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'failed',
            'error_message' => 'Connection timeout occurred',
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'failed',
            'error_message' => 'Invalid webhook URL',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('search', 'timeout');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_search_resets_pagination(): void
    {
        NotificationLog::factory()->count(25)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('page', 2)
            ->set('search', 'test')
            ->assertSet('page', 1);
    }

    // ==================== STATUS FILTER TESTS ====================

    public function test_can_filter_by_status(): void
    {
        NotificationLog::factory()->count(3)->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'success',
        ]);
        NotificationLog::factory()->count(2)->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'failed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('statusFilter', 'success');

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    public function test_status_filter_resets_pagination(): void
    {
        NotificationLog::factory()->count(25)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('page', 2)
            ->set('statusFilter', 'failed')
            ->assertSet('page', 1);
    }

    // ==================== CHANNEL FILTER TESTS ====================

    public function test_can_filter_by_channel(): void
    {
        $otherChannel = NotificationChannel::factory()->create(['name' => 'Other Channel']);

        NotificationLog::factory()->count(4)->create([
            'notification_channel_id' => $this->channel->id,
        ]);
        NotificationLog::factory()->count(2)->create([
            'notification_channel_id' => $otherChannel->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('channelFilter', (string) $this->channel->id);

        $logs = $component->viewData('logs');
        $this->assertCount(4, $logs);
    }

    public function test_channel_filter_resets_pagination(): void
    {
        NotificationLog::factory()->count(25)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('page', 2)
            ->set('channelFilter', (string) $this->channel->id)
            ->assertSet('page', 1);
    }

    // ==================== EVENT TYPE FILTER TESTS ====================

    public function test_can_filter_by_event_type(): void
    {
        NotificationLog::factory()->count(3)->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
        ]);
        NotificationLog::factory()->count(2)->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'backup.started',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('eventTypeFilter', 'deployment.completed');

        $logs = $component->viewData('logs');
        $this->assertCount(3, $logs);
    }

    // ==================== DATE FILTER TESTS ====================

    public function test_can_filter_by_date_from(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now()->subDays(5),
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('dateFrom', now()->subDay()->format('Y-m-d'));

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_can_filter_by_date_to(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now()->subDays(5),
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('dateTo', now()->subDays(3)->format('Y-m-d'));

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    public function test_can_filter_by_date_range(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now()->subDays(10),
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now()->subDays(5),
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
            ->set('dateTo', now()->subDays(3)->format('Y-m-d'));

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
    }

    // ==================== CLEAR FILTERS TESTS ====================

    public function test_can_clear_all_filters(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('search', 'test')
            ->set('statusFilter', 'failed')
            ->set('channelFilter', (string) $this->channel->id)
            ->set('eventTypeFilter', 'deployment.completed')
            ->set('dateFrom', now()->subWeek()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('channelFilter', '')
            ->assertSet('eventTypeFilter', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    public function test_clear_filters_resets_pagination(): void
    {
        NotificationLog::factory()->count(25)->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('page', 2)
            ->call('clearFilters')
            ->assertSet('page', 1);
    }

    // ==================== VIEW DETAILS TESTS ====================

    public function test_can_view_log_details(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
            'status' => 'success',
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', $log->id)
            ->assertSet('showDetails', true);
    }

    public function test_view_details_populates_selected_log(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.failed',
            'status' => 'failed',
            'error_message' => 'Test error',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', $log->id);

        $selectedLog = $component->get('selectedLog');
        $this->assertEquals($log->id, $selectedLog['id']);
        $this->assertEquals('deployment.failed', $selectedLog['event_type']);
        $this->assertEquals('failed', $selectedLog['status']);
        $this->assertEquals('Test error', $selectedLog['error_message']);
        $this->assertEquals('Main Channel', $selectedLog['channel']);
    }

    public function test_view_details_handles_non_existent_log(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', 999999)
            ->assertSet('showDetails', false)
            ->assertSet('selectedLog', []);
    }

    public function test_can_close_details(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', $log->id)
            ->assertSet('showDetails', true)
            ->call('closeDetails')
            ->assertSet('showDetails', false)
            ->assertSet('selectedLog', []);
    }

    // ==================== COMPUTED PROPERTIES TESTS ====================

    public function test_channels_property_returns_all_channels(): void
    {
        NotificationChannel::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $channels = $component->viewData('channels');
        $this->assertCount(4, $channels);
    }

    public function test_channels_are_ordered_by_name(): void
    {
        NotificationChannel::factory()->create(['name' => 'Zebra Channel']);
        NotificationChannel::factory()->create(['name' => 'Alpha Channel']);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $channels = $component->viewData('channels');
        $this->assertEquals('Alpha Channel', $channels->first()->name);
    }

    public function test_event_types_property_returns_distinct_types(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
        ]);
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'backup.started',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $eventTypes = $component->viewData('eventTypes');
        $this->assertCount(2, $eventTypes);
    }

    // ==================== STATS TESTS ====================

    public function test_stats_property_returns_counts(): void
    {
        NotificationLog::factory()->count(5)->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'success',
        ]);
        NotificationLog::factory()->count(3)->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'failed',
        ]);
        NotificationLog::factory()->count(2)->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(5, $stats['success']);
        $this->assertEquals(3, $stats['failed']);
        $this->assertEquals(2, $stats['pending']);
    }

    public function test_stats_empty_when_no_logs(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $stats = $component->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['success']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['pending']);
    }

    // ==================== COMBINED FILTERS TESTS ====================

    public function test_can_apply_multiple_filters(): void
    {
        $log = NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
            'status' => 'success',
            'created_at' => now(),
        ]);

        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'deployment.completed',
            'status' => 'failed',
            'created_at' => now(),
        ]);

        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'event_type' => 'backup.started',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('statusFilter', 'success')
            ->set('eventTypeFilter', 'deployment.completed');

        $logs = $component->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertEquals($log->id, $logs->first()->id);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_logs(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $logs = $component->viewData('logs');
        $this->assertCount(0, $logs);
    }

    public function test_handles_no_matching_logs(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
            'status' => 'success',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->set('statusFilter', 'failed');

        $logs = $component->viewData('logs');
        $this->assertCount(0, $logs);
    }

    // ==================== LOG WITH CHANNEL RELATIONSHIP TESTS ====================

    public function test_logs_include_channel_relationship(): void
    {
        NotificationLog::factory()->create([
            'notification_channel_id' => $this->channel->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class);

        $logs = $component->viewData('logs');
        $this->assertTrue($logs->first()->relationLoaded('channel'));
        $this->assertEquals('Main Channel', $logs->first()->channel->name);
    }

    public function test_view_details_shows_channel_info(): void
    {
        $channel = NotificationChannel::factory()->create([
            'name' => 'Slack Alerts',
            'type' => 'slack',
        ]);

        $log = NotificationLog::factory()->create([
            'notification_channel_id' => $channel->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(NotificationLogs::class)
            ->call('viewDetails', $log->id);

        $selectedLog = $component->get('selectedLog');
        $this->assertEquals('Slack Alerts', $selectedLog['channel']);
        $this->assertEquals('slack', $selectedLog['channel_type']);
    }
}
