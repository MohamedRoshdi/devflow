<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AlertHistory;
use App\Models\ResourceAlert;
use App\Models\Server;
use Tests\TestCase;

class AlertModelsTest extends TestCase
{
    // ========================
    // AlertHistory Model Tests
    // ========================

    /** @test */
    public function alert_history_can_be_created_with_factory(): void
    {
        $alertHistory = AlertHistory::factory()->create();

        $this->assertInstanceOf(AlertHistory::class, $alertHistory);
        $this->assertDatabaseHas('alert_history', [
            'id' => $alertHistory->id,
        ]);
    }

    /** @test */
    public function alert_history_belongs_to_resource_alert(): void
    {
        $resourceAlert = ResourceAlert::factory()->create();
        $alertHistory = AlertHistory::factory()->create(['resource_alert_id' => $resourceAlert->id]);

        $this->assertInstanceOf(ResourceAlert::class, $alertHistory->resourceAlert);
        $this->assertEquals($resourceAlert->id, $alertHistory->resourceAlert->id);
    }

    /** @test */
    public function alert_history_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $alertHistory = AlertHistory::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $alertHistory->server);
        $this->assertEquals($server->id, $alertHistory->server->id);
    }

    /** @test */
    public function alert_history_casts_decimal_attributes_correctly(): void
    {
        $alertHistory = AlertHistory::factory()->create([
            'current_value' => 85.50,
            'threshold_value' => 80.00,
        ]);

        $this->assertEquals('85.50', $alertHistory->current_value);
        $this->assertEquals('80.00', $alertHistory->threshold_value);
    }

    /** @test */
    public function alert_history_casts_notified_at_as_datetime(): void
    {
        $alertHistory = AlertHistory::factory()->create([
            'notified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $alertHistory->notified_at);
    }

    /** @test */
    public function alert_history_is_triggered_returns_true_when_triggered(): void
    {
        $alertHistory = AlertHistory::factory()->create(['status' => 'triggered']);

        $this->assertTrue($alertHistory->isTriggered());
    }

    /** @test */
    public function alert_history_is_triggered_returns_false_when_not_triggered(): void
    {
        $alertHistory = AlertHistory::factory()->create(['status' => 'resolved']);

        $this->assertFalse($alertHistory->isTriggered());
    }

    /** @test */
    public function alert_history_is_resolved_returns_true_when_resolved(): void
    {
        $alertHistory = AlertHistory::factory()->create(['status' => 'resolved']);

        $this->assertTrue($alertHistory->isResolved());
    }

    /** @test */
    public function alert_history_is_resolved_returns_false_when_not_resolved(): void
    {
        $alertHistory = AlertHistory::factory()->create(['status' => 'triggered']);

        $this->assertFalse($alertHistory->isResolved());
    }

    /** @test */
    public function alert_history_status_color_returns_correct_colors(): void
    {
        $triggered = AlertHistory::factory()->create(['status' => 'triggered']);
        $this->assertEquals('red', $triggered->status_color);

        $resolved = AlertHistory::factory()->create(['status' => 'resolved']);
        $this->assertEquals('green', $resolved->status_color);

        $unknown = AlertHistory::factory()->create(['status' => 'unknown']);
        $this->assertEquals('gray', $unknown->status_color);
    }

    /** @test */
    public function alert_history_resource_type_icon_returns_correct_icons(): void
    {
        $cpu = AlertHistory::factory()->create(['resource_type' => 'cpu']);
        $this->assertEquals('heroicon-o-cpu-chip', $cpu->resource_type_icon);

        $memory = AlertHistory::factory()->create(['resource_type' => 'memory']);
        $this->assertEquals('heroicon-o-circle-stack', $memory->resource_type_icon);

        $disk = AlertHistory::factory()->create(['resource_type' => 'disk']);
        $this->assertEquals('heroicon-o-server-stack', $disk->resource_type_icon);

        $load = AlertHistory::factory()->create(['resource_type' => 'load']);
        $this->assertEquals('heroicon-o-chart-bar', $load->resource_type_icon);

        $unknown = AlertHistory::factory()->create(['resource_type' => 'unknown']);
        $this->assertEquals('heroicon-o-bell-alert', $unknown->resource_type_icon);
    }

    /** @test */
    public function alert_history_scope_triggered_filters_triggered_alerts(): void
    {
        AlertHistory::factory()->create(['status' => 'triggered']);
        AlertHistory::factory()->create(['status' => 'triggered']);
        AlertHistory::factory()->create(['status' => 'resolved']);

        $triggered = AlertHistory::triggered()->get();
        $this->assertCount(2, $triggered);
    }

    /** @test */
    public function alert_history_scope_resolved_filters_resolved_alerts(): void
    {
        AlertHistory::factory()->create(['status' => 'resolved']);
        AlertHistory::factory()->create(['status' => 'triggered']);
        AlertHistory::factory()->create(['status' => 'resolved']);

        $resolved = AlertHistory::resolved()->get();
        $this->assertCount(2, $resolved);
    }

    /** @test */
    public function alert_history_scope_for_server_filters_by_server_id(): void
    {
        $server = Server::factory()->create();
        AlertHistory::factory()->count(3)->create(['server_id' => $server->id]);
        AlertHistory::factory()->create(['server_id' => Server::factory()->create()->id]);

        $serverAlerts = AlertHistory::forServer($server->id)->get();
        $this->assertCount(3, $serverAlerts);
    }

    /** @test */
    public function alert_history_scope_recent_filters_by_hours(): void
    {
        AlertHistory::factory()->create(['created_at' => now()->subHours(48)]);
        AlertHistory::factory()->create(['created_at' => now()->subHours(12)]);
        AlertHistory::factory()->create(['created_at' => now()->subHours(6)]);

        $recent = AlertHistory::recent(24)->get();
        $this->assertCount(2, $recent);
    }

    // ========================
    // ResourceAlert Model Tests
    // ========================

    /** @test */
    public function resource_alert_can_be_created_with_factory(): void
    {
        $resourceAlert = ResourceAlert::factory()->create();

        $this->assertInstanceOf(ResourceAlert::class, $resourceAlert);
        $this->assertDatabaseHas('resource_alerts', [
            'id' => $resourceAlert->id,
        ]);
    }

    /** @test */
    public function resource_alert_belongs_to_server(): void
    {
        $server = Server::factory()->create();
        $resourceAlert = ResourceAlert::factory()->create(['server_id' => $server->id]);

        $this->assertInstanceOf(Server::class, $resourceAlert->server);
        $this->assertEquals($server->id, $resourceAlert->server->id);
    }

    /** @test */
    public function resource_alert_has_many_alert_history_records(): void
    {
        $resourceAlert = ResourceAlert::factory()->create();
        AlertHistory::factory()->count(3)->create(['resource_alert_id' => $resourceAlert->id]);

        $this->assertCount(3, $resourceAlert->history);
        $this->assertInstanceOf(AlertHistory::class, $resourceAlert->history->first());
    }

    /** @test */
    public function resource_alert_has_latest_history_relationship(): void
    {
        $resourceAlert = ResourceAlert::factory()->create();
        $oldest = AlertHistory::factory()->create([
            'resource_alert_id' => $resourceAlert->id,
            'created_at' => now()->subHours(2),
        ]);
        $latest = AlertHistory::factory()->create([
            'resource_alert_id' => $resourceAlert->id,
            'created_at' => now(),
        ]);

        $this->assertNotNull($resourceAlert->latestHistory);
        $this->assertEquals($latest->id, $resourceAlert->latestHistory->id);
    }

    /** @test */
    public function resource_alert_casts_notification_channels_as_array(): void
    {
        $channels = ['email', 'slack', 'discord'];
        $resourceAlert = ResourceAlert::factory()->create(['notification_channels' => $channels]);

        $this->assertIsArray($resourceAlert->notification_channels);
        $this->assertEquals($channels, $resourceAlert->notification_channels);
    }

    /** @test */
    public function resource_alert_casts_boolean_attributes_correctly(): void
    {
        $resourceAlert = ResourceAlert::factory()->create(['is_active' => true]);

        $this->assertTrue($resourceAlert->is_active);
        $this->assertIsBool($resourceAlert->is_active);
    }

    /** @test */
    public function resource_alert_casts_threshold_value_as_decimal(): void
    {
        $resourceAlert = ResourceAlert::factory()->create(['threshold_value' => 85.75]);

        $this->assertEquals('85.75', $resourceAlert->threshold_value);
    }

    /** @test */
    public function resource_alert_casts_cooldown_minutes_as_integer(): void
    {
        $resourceAlert = ResourceAlert::factory()->create(['cooldown_minutes' => 30]);

        $this->assertIsInt($resourceAlert->cooldown_minutes);
        $this->assertEquals(30, $resourceAlert->cooldown_minutes);
    }

    /** @test */
    public function resource_alert_casts_last_triggered_at_as_datetime(): void
    {
        $resourceAlert = ResourceAlert::factory()->create(['last_triggered_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $resourceAlert->last_triggered_at);
    }

    /** @test */
    public function resource_alert_resource_type_icon_returns_correct_icons(): void
    {
        $cpu = ResourceAlert::factory()->create(['resource_type' => 'cpu']);
        $this->assertEquals('heroicon-o-cpu-chip', $cpu->resource_type_icon);

        $memory = ResourceAlert::factory()->create(['resource_type' => 'memory']);
        $this->assertEquals('heroicon-o-circle-stack', $memory->resource_type_icon);

        $disk = ResourceAlert::factory()->create(['resource_type' => 'disk']);
        $this->assertEquals('heroicon-o-server-stack', $disk->resource_type_icon);

        $load = ResourceAlert::factory()->create(['resource_type' => 'load']);
        $this->assertEquals('heroicon-o-chart-bar', $load->resource_type_icon);
    }

    /** @test */
    public function resource_alert_resource_type_label_returns_correct_labels(): void
    {
        $cpu = ResourceAlert::factory()->create(['resource_type' => 'cpu']);
        $this->assertEquals('CPU Usage', $cpu->resource_type_label);

        $memory = ResourceAlert::factory()->create(['resource_type' => 'memory']);
        $this->assertEquals('Memory Usage', $memory->resource_type_label);

        $disk = ResourceAlert::factory()->create(['resource_type' => 'disk']);
        $this->assertEquals('Disk Usage', $disk->resource_type_label);

        $load = ResourceAlert::factory()->create(['resource_type' => 'load']);
        $this->assertEquals('Load Average', $load->resource_type_label);
    }

    /** @test */
    public function resource_alert_threshold_display_returns_formatted_string_for_above(): void
    {
        $cpuAlert = ResourceAlert::factory()->create([
            'resource_type' => 'cpu',
            'threshold_type' => 'above',
            'threshold_value' => 85.00,
        ]);

        $this->assertEquals('> 85.00%', $cpuAlert->threshold_display);
    }

    /** @test */
    public function resource_alert_threshold_display_returns_formatted_string_for_below(): void
    {
        $cpuAlert = ResourceAlert::factory()->create([
            'resource_type' => 'cpu',
            'threshold_type' => 'below',
            'threshold_value' => 20.00,
        ]);

        $this->assertEquals('< 20.00%', $cpuAlert->threshold_display);
    }

    /** @test */
    public function resource_alert_threshold_display_handles_load_without_percentage(): void
    {
        $loadAlert = ResourceAlert::factory()->create([
            'resource_type' => 'load',
            'threshold_type' => 'above',
            'threshold_value' => 5.00,
        ]);

        $this->assertEquals('> 5.00', $loadAlert->threshold_display);
    }

    /** @test */
    public function resource_alert_is_in_cooldown_returns_true_when_in_cooldown(): void
    {
        $resourceAlert = ResourceAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue($resourceAlert->isInCooldown());
    }

    /** @test */
    public function resource_alert_is_in_cooldown_returns_false_when_not_in_cooldown(): void
    {
        $resourceAlert = ResourceAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(90),
        ]);

        $this->assertFalse($resourceAlert->isInCooldown());
    }

    /** @test */
    public function resource_alert_is_in_cooldown_returns_false_when_never_triggered(): void
    {
        $resourceAlert = ResourceAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => null,
        ]);

        $this->assertFalse($resourceAlert->isInCooldown());
    }

    /** @test */
    public function resource_alert_cooldown_remaining_minutes_returns_correct_value(): void
    {
        $resourceAlert = ResourceAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(30),
        ]);

        $remaining = $resourceAlert->cooldown_remaining_minutes;
        $this->assertIsInt($remaining);
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(30, $remaining);
    }

    /** @test */
    public function resource_alert_cooldown_remaining_minutes_returns_null_when_not_in_cooldown(): void
    {
        $resourceAlert = ResourceAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(90),
        ]);

        $this->assertNull($resourceAlert->cooldown_remaining_minutes);
    }

    /** @test */
    public function resource_alert_scope_active_filters_active_alerts(): void
    {
        ResourceAlert::factory()->create(['is_active' => true]);
        ResourceAlert::factory()->create(['is_active' => true]);
        ResourceAlert::factory()->create(['is_active' => false]);

        $active = ResourceAlert::active()->get();
        $this->assertCount(2, $active);
    }

    /** @test */
    public function resource_alert_scope_for_server_filters_by_server_id(): void
    {
        $server = Server::factory()->create();
        ResourceAlert::factory()->count(3)->create(['server_id' => $server->id]);
        ResourceAlert::factory()->create(['server_id' => Server::factory()->create()->id]);

        $serverAlerts = ResourceAlert::forServer($server->id)->get();
        $this->assertCount(3, $serverAlerts);
    }

    /** @test */
    public function resource_alert_scope_for_resource_type_filters_by_type(): void
    {
        ResourceAlert::factory()->count(2)->create(['resource_type' => 'cpu']);
        ResourceAlert::factory()->create(['resource_type' => 'memory']);
        ResourceAlert::factory()->create(['resource_type' => 'disk']);

        $cpuAlerts = ResourceAlert::forResourceType('cpu')->get();
        $this->assertCount(2, $cpuAlerts);
    }
}
