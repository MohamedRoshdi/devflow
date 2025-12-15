<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Deployments;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Deployments\ScheduledDeployments;
use App\Models\Project;
use App\Models\ScheduledDeployment;
use App\Models\Server;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduledDeploymentsTest extends TestCase
{

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'timezone' => 'UTC',
        ]);

        $this->server = Server::factory()->create(['status' => 'online']);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);
    }

    #[Test]
    public function component_renders_successfully_for_authenticated_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.scheduled-deployments');
    }

    #[Test]
    public function component_initializes_with_correct_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('selectedBranch', 'main')
            ->assertSet('timezone', 'UTC')
            ->assertSet('scheduledTime', '03:00')
            ->assertSet('notifyBefore', true)
            ->assertSet('notifyMinutes', 15)
            ->assertSet('showScheduleModal', false);
    }

    #[Test]
    public function component_uses_user_timezone_if_available(): void
    {
        $userWithTimezone = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        Livewire::actingAs($userWithTimezone)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('timezone', 'America/New_York');
    }

    #[Test]
    public function component_loads_available_branches_from_project(): void
    {
        // Component defaults to using project's branch when available_branches is not set
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('branches', ['main']);
    }

    #[Test]
    public function component_displays_scheduled_deployments_list(): void
    {
        $scheduledDeployment = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $this->assertTrue($deployments->contains('id', $scheduledDeployment->id));
    }

    #[Test]
    public function component_displays_multiple_scheduled_deployments(): void
    {
        ScheduledDeployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $this->assertEquals(3, $deployments->count());
    }

    #[Test]
    public function scheduled_deployments_are_ordered_by_scheduled_at_ascending(): void
    {
        $futureDeployment = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'pending',
        ]);

        $soonDeployment = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $this->assertEquals($soonDeployment->id, $deployments->first()->id);
    }

    #[Test]
    public function open_schedule_modal_sets_show_schedule_modal_to_true(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->assertSet('showScheduleModal', true);
    }

    #[Test]
    public function close_schedule_modal_sets_show_schedule_modal_to_false(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('showScheduleModal', true)
            ->call('closeScheduleModal')
            ->assertSet('showScheduleModal', false);
    }

    #[Test]
    public function close_schedule_modal_resets_form_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'develop')
            ->set('notes', 'Test notes')
            ->set('notifyMinutes', 30)
            ->call('closeScheduleModal')
            ->assertSet('selectedBranch', 'main')
            ->assertSet('notes', '')
            ->assertSet('notifyMinutes', 15);
    }

    #[Test]
    public function can_create_new_scheduled_deployment(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('timezone', 'UTC')
            ->set('notes', 'Deploy new features')
            ->set('notifyBefore', true)
            ->set('notifyMinutes', 30)
            ->call('scheduleDeployment')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'timezone' => 'UTC',
            'notes' => 'Deploy new features',
            'notify_before' => true,
            'notify_minutes' => 30,
            'status' => 'pending',
        ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function scheduled_deployment_time_is_stored_in_utc(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('timezone', 'America/New_York') // UTC-5
            ->call('scheduleDeployment');

        $scheduled = ScheduledDeployment::first();
        $this->assertNotNull($scheduled);

        // 15:00 EST = 20:00 UTC
        $this->assertEquals('2025-01-02 20:00:00', $scheduled->scheduled_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    #[Test]
    public function schedule_deployment_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', '')
            ->set('scheduledDate', '')
            ->set('scheduledTime', '')
            ->set('timezone', '')
            ->call('scheduleDeployment')
            ->assertHasErrors(['selectedBranch', 'scheduledDate', 'scheduledTime', 'timezone']);
    }

    #[Test]
    public function scheduled_date_must_be_today_or_future(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->subDay()->format('Y-m-d'))
            ->set('scheduledTime', '15:00')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledDate']);
    }

    #[Test]
    public function scheduled_time_must_be_valid_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', 'invalid-time')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledTime']);
    }

    #[Test]
    public function notes_are_optional_with_max_length(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('notes', str_repeat('a', 501))
            ->call('scheduleDeployment')
            ->assertHasErrors(['notes']);

        Carbon::setTestNow();
    }

    #[Test]
    public function notify_minutes_must_be_between_5_and_60(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('notifyMinutes', 3)
            ->call('scheduleDeployment')
            ->assertHasErrors(['notifyMinutes']);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('notifyMinutes', 65)
            ->call('scheduleDeployment')
            ->assertHasErrors(['notifyMinutes']);

        Carbon::setTestNow();
    }

    #[Test]
    public function scheduled_time_must_be_in_future(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-01')
            ->set('scheduledTime', '10:00') // 2 hours in the past
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledTime']);

        Carbon::setTestNow();
    }

    #[Test]
    public function successful_scheduling_closes_modal_and_shows_notification(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->call('scheduleDeployment')
            ->assertSet('showScheduleModal', false)
            ->assertDispatched('notification', type: 'success');

        Carbon::setTestNow();
    }

    #[Test]
    public function can_cancel_pending_scheduled_deployment(): void
    {
        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $scheduled->id)
            ->assertDispatched('notification', type: 'success');

        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduled->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function cannot_cancel_scheduled_deployment_from_different_project(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $scheduled->id);

        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduled->id,
            'status' => 'pending', // Should remain unchanged
        ]);
    }

    #[Test]
    public function component_eager_loads_user_relationship(): void
    {
        ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $deployment = $deployments->first();
        $this->assertNotNull($deployment);
        $this->assertTrue($deployment->relationLoaded('user'));
    }

    #[Test]
    public function timezone_options_property_returns_correct_timezones(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $timezones = $component->get('timezoneOptions');

        $this->assertIsArray($timezones);
        $this->assertArrayHasKey('UTC', $timezones);
        $this->assertArrayHasKey('America/New_York', $timezones);
        $this->assertArrayHasKey('Europe/London', $timezones);
        $this->assertArrayHasKey('Asia/Tokyo', $timezones);
        $this->assertArrayHasKey('Africa/Cairo', $timezones);
    }

    #[Test]
    public function refresh_list_method_exists_and_handles_deployment_completed_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->dispatch('deployment-completed')
            ->assertOk();
    }

    #[Test]
    public function component_only_shows_scheduled_deployments_for_specific_project(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $thisProjectScheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $otherProjectScheduled = ScheduledDeployment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $this->assertTrue($deployments->contains('id', $thisProjectScheduled->id));
        $this->assertFalse($deployments->contains('id', $otherProjectScheduled->id));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_component(): void
    {
        // Authentication is handled at the route middleware level, not the component level.
        // This test should be a Feature test that tests the route with middleware.
        $this->markTestSkipped('Authentication is enforced at route middleware level, not component level.');
    }

    #[Test]
    public function scheduled_date_defaults_to_tomorrow(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('scheduledDate', '2025-01-02');

        Carbon::setTestNow();
    }

    #[Test]
    public function handles_timezone_conversion_correctly_for_multiple_timezones(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $timezones = [
            'UTC' => '2025-01-02 15:00:00',
            'America/New_York' => '2025-01-02 20:00:00', // UTC-5
            'Europe/Paris' => '2025-01-02 14:00:00', // UTC+1
            'Asia/Tokyo' => '2025-01-02 06:00:00', // UTC+9
        ];

        foreach ($timezones as $timezone => $expectedUtc) {
            ScheduledDeployment::query()->delete();

            Livewire::actingAs($this->user)
                ->test(ScheduledDeployments::class, ['project' => $this->project])
                ->set('scheduledDate', '2025-01-02')
                ->set('scheduledTime', '15:00')
                ->set('timezone', $timezone)
                ->call('scheduleDeployment');

            $scheduled = ScheduledDeployment::first();
            $this->assertNotNull($scheduled);

            $this->assertEquals(
                $expectedUtc,
                $scheduled->scheduled_at->format('Y-m-d H:i:s'),
                "Failed for timezone: {$timezone}"
            );
        }

        Carbon::setTestNow();
    }

    #[Test]
    public function successful_deployment_includes_formatted_time_in_notification(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('timezone', 'America/New_York')
            ->call('scheduleDeployment')
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], 'Jan 02, 2025 15:00') &&
                       str_contains($data['message'], 'America/New_York');
            });

        Carbon::setTestNow();
    }

    #[Test]
    public function component_handles_project_without_available_branches(): void
    {
        $projectWithoutBranches = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'branch' => 'master',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $projectWithoutBranches])
            ->assertSet('branches', ['master']);
    }

    #[Test]
    public function form_reset_sets_scheduled_date_to_tomorrow(): void
    {
        Carbon::setTestNow('2025-01-15 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-20')
            ->call('closeScheduleModal')
            ->assertSet('scheduledDate', '2025-01-16');

        Carbon::setTestNow();
    }

    #[Test]
    public function all_scheduled_deployment_statuses_are_displayed(): void
    {
        // Valid statuses: pending, running, completed, failed, cancelled
        $statuses = ['pending', 'completed', 'cancelled', 'failed'];

        foreach ($statuses as $status) {
            ScheduledDeployment::factory()->create([
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
                'status' => $status,
            ]);
        }

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        $deployments = $component->instance()->scheduledDeployments;
        $this->assertEquals(4, $deployments->count());
    }

    #[Test]
    public function can_schedule_deployment_for_different_branches(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $branches = ['main', 'develop', 'staging'];

        foreach ($branches as $branch) {
            Livewire::actingAs($this->user)
                ->test(ScheduledDeployments::class, ['project' => $this->project])
                ->set('selectedBranch', $branch)
                ->set('scheduledDate', '2025-01-02')
                ->set('scheduledTime', '15:00')
                ->call('scheduleDeployment');

            $this->assertDatabaseHas('scheduled_deployments', [
                'project_id' => $this->project->id,
                'branch' => $branch,
            ]);
        }

        Carbon::setTestNow();
    }

    #[Test]
    public function notification_settings_are_stored_correctly(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('notifyBefore', false)
            ->set('notifyMinutes', 45)
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'notify_before' => false,
            'notify_minutes' => 45,
        ]);

        Carbon::setTestNow();
    }

    #[Test]
    public function handles_invalid_date_time_format_gracefully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', 'invalid-date')
            ->set('scheduledTime', '15:00')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledDate']);
    }

    #[Test]
    public function cancel_only_affects_cancellable_deployments(): void
    {
        $completedScheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $completedScheduled->id);

        // Should remain unchanged because completed deployments can't be cancelled
        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $completedScheduled->id,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function component_initializes_correct_scheduled_time_for_user_timezone(): void
    {
        $userInTokyo = User::factory()->create([
            'timezone' => 'Asia/Tokyo',
        ]);

        Livewire::actingAs($userInTokyo)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('timezone', 'Asia/Tokyo')
            ->assertSet('scheduledTime', '03:00');
    }

    #[Test]
    public function empty_notes_are_saved_as_empty_string(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-02')
            ->set('scheduledTime', '15:00')
            ->set('notes', '')
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'notes' => '',
        ]);

        Carbon::setTestNow();
    }
}
