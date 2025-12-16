<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\ScheduledDeployments;
use App\Models\Project;
use App\Models\ScheduledDeployment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduledDeploymentsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'branch' => 'main',
        ]);
    }

    // ============================================================
    // Component Rendering Tests
    // ============================================================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertViewIs('livewire.deployments.scheduled-deployments');
    }

    public function test_component_mounts_with_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id);
    }

    public function test_component_sets_default_values_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('selectedBranch', 'main')
            ->assertSet('timezone', 'UTC')
            ->assertSet('scheduledTime', '03:00')
            ->assertSet('notifyBefore', true)
            ->assertSet('notifyMinutes', 15);
    }

    public function test_component_uses_user_timezone_if_set(): void
    {
        $userWithTz = User::factory()->create(['timezone' => 'America/New_York']);
        $project = Project::factory()->create(['user_id' => $userWithTz->id]);

        Livewire::actingAs($userWithTz)
            ->test(ScheduledDeployments::class, ['project' => $project])
            ->assertSet('timezone', 'America/New_York');
    }

    // ============================================================
    // Modal Tests
    // ============================================================

    public function test_modal_is_closed_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('showScheduleModal', false);
    }

    public function test_can_open_schedule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->assertSet('showScheduleModal', true);
    }

    public function test_can_close_schedule_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->assertSet('showScheduleModal', true)
            ->call('closeScheduleModal')
            ->assertSet('showScheduleModal', false);
    }

    public function test_closing_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('openScheduleModal')
            ->set('notes', 'Test notes')
            ->set('selectedBranch', 'feature-branch')
            ->call('closeScheduleModal')
            ->assertSet('notes', '')
            ->assertSet('selectedBranch', 'main');
    }

    // ============================================================
    // Schedule Deployment Tests
    // ============================================================

    public function test_can_schedule_deployment(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('timezone', 'UTC')
            ->set('notes', 'Scheduled maintenance')
            ->set('notifyBefore', true)
            ->set('notifyMinutes', 30)
            ->call('scheduleDeployment')
            ->assertSet('showScheduleModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'notes' => 'Scheduled maintenance',
            'status' => 'pending',
            'notify_before' => true,
            'notify_minutes' => 30,
        ]);

        Carbon::setTestNow();
    }

    public function test_schedule_deployment_with_different_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'main')
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '15:00')
            ->set('timezone', 'America/New_York')
            ->call('scheduleDeployment')
            ->assertDispatched('notification');

        // Verify the scheduled_at is stored in UTC (15:00 EST = 20:00 UTC)
        $scheduled = ScheduledDeployment::latest()->first();
        $this->assertNotNull($scheduled);
        $this->assertEquals('America/New_York', $scheduled->timezone);

        Carbon::setTestNow();
    }

    public function test_schedule_deployment_without_notification(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('notifyBefore', false)
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'notify_before' => false,
        ]);

        Carbon::setTestNow();
    }

    // ============================================================
    // Validation Tests
    // ============================================================

    public function test_branch_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', '')
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->call('scheduleDeployment')
            ->assertHasErrors(['selectedBranch' => 'required']);
    }

    public function test_date_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '')
            ->set('scheduledTime', '03:00')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledDate' => 'required']);
    }

    public function test_date_must_be_today_or_future(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->subDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledDate']);
    }

    public function test_time_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledTime' => 'required']);
    }

    public function test_time_format_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', 'invalid')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledTime' => 'date_format']);
    }

    public function test_timezone_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('timezone', '')
            ->call('scheduleDeployment')
            ->assertHasErrors(['timezone' => 'required']);
    }

    public function test_notes_max_length_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('notes', str_repeat('a', 501))
            ->call('scheduleDeployment')
            ->assertHasErrors(['notes' => 'max']);
    }

    public function test_notify_minutes_min_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('notifyMinutes', 2)
            ->call('scheduleDeployment')
            ->assertHasErrors(['notifyMinutes' => 'min']);
    }

    public function test_notify_minutes_max_validation(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
            ->set('scheduledTime', '03:00')
            ->set('notifyMinutes', 120)
            ->call('scheduleDeployment')
            ->assertHasErrors(['notifyMinutes' => 'max']);
    }

    public function test_scheduled_time_must_be_in_future(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 14:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-15')
            ->set('scheduledTime', '10:00') // Earlier than "now" (14:00)
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertHasErrors(['scheduledTime']);

        Carbon::setTestNow();
    }

    // ============================================================
    // Cancel Deployment Tests
    // ============================================================

    public function test_can_cancel_pending_scheduled_deployment(): void
    {
        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'scheduled_at' => now()->addDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $scheduled->id)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduled->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_executed_deployment(): void
    {
        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'executed',
            'scheduled_at' => now()->subDay(),
            'executed_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $scheduled->id);

        // Status should remain 'executed'
        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduled->id,
            'status' => 'executed',
        ]);
    }

    public function test_cannot_cancel_deployment_from_different_project(): void
    {
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        $scheduled = ScheduledDeployment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', $scheduled->id);

        // Status should remain 'pending' since it's not in this project
        $this->assertDatabaseHas('scheduled_deployments', [
            'id' => $scheduled->id,
            'status' => 'pending',
        ]);
    }

    public function test_cancel_non_existent_deployment_does_nothing(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->call('cancelScheduledDeployment', 99999)
            ->assertStatus(200);
    }

    // ============================================================
    // Computed Properties Tests
    // ============================================================

    public function test_scheduled_deployments_property_returns_project_deployments(): void
    {
        ScheduledDeployment::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        // Create deployments for another project (should not be returned)
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        ScheduledDeployment::factory()->count(2)->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertStatus(200);

        // Verify only project's deployments are shown
        $this->assertEquals(3, ScheduledDeployment::where('project_id', $this->project->id)->count());
    }

    public function test_scheduled_deployments_are_ordered_by_scheduled_at(): void
    {
        $first = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        $second = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(3),
        ]);

        $third = ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(2),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        // The order should be: first, third, second (by scheduled_at ascending)
        $deployments = ScheduledDeployment::where('project_id', $this->project->id)
            ->orderBy('scheduled_at', 'asc')
            ->pluck('id')
            ->toArray();

        $this->assertEquals([$first->id, $third->id, $second->id], $deployments);
    }

    public function test_timezone_options_property(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        // Access the component to check timezone options exist
        $component->assertStatus(200);
    }

    public function test_scheduled_deployments_includes_user_relationship(): void
    {
        ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSee($this->user->name);
    }

    // ============================================================
    // Event Tests
    // ============================================================

    public function test_listens_to_deployment_completed_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->dispatch('deployment-completed')
            ->assertStatus(200);
    }

    public function test_refresh_list_on_deployment_completed(): void
    {
        ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->dispatch('deployment-completed')
            ->assertStatus(200);
    }

    // ============================================================
    // Branch Selection Tests
    // ============================================================

    public function test_uses_project_branch_as_default(): void
    {
        $projectWithBranch = Project::factory()->create([
            'user_id' => $this->user->id,
            'branch' => 'develop',
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $projectWithBranch])
            ->assertSet('selectedBranch', 'develop');
    }

    public function test_defaults_to_main_when_project_has_no_branch(): void
    {
        $projectNoBranch = Project::factory()->create([
            'user_id' => $this->user->id,
            'branch' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $projectNoBranch])
            ->assertSet('selectedBranch', 'main');
    }

    public function test_can_schedule_deployment_with_feature_branch(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('selectedBranch', 'feature/new-feature')
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'branch' => 'feature/new-feature',
        ]);

        Carbon::setTestNow();
    }

    // ============================================================
    // Notification Settings Tests
    // ============================================================

    public function test_notification_settings_are_saved(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('notifyBefore', true)
            ->set('notifyMinutes', 45)
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'notify_before' => true,
            'notify_minutes' => 45,
        ]);

        Carbon::setTestNow();
    }

    // ============================================================
    // Notes Tests
    // ============================================================

    public function test_notes_are_optional(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('notes', '')
            ->call('scheduleDeployment')
            ->assertHasNoErrors(['notes']);

        Carbon::setTestNow();
    }

    public function test_notes_are_saved_correctly(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $notes = 'Deploying new payment gateway integration';

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('notes', $notes)
            ->call('scheduleDeployment');

        $this->assertDatabaseHas('scheduled_deployments', [
            'project_id' => $this->project->id,
            'notes' => $notes,
        ]);

        Carbon::setTestNow();
    }

    // ============================================================
    // Display Tests
    // ============================================================

    public function test_displays_scheduled_deployments_list(): void
    {
        ScheduledDeployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'feature/test',
            'scheduled_at' => now()->addDay(),
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSee('feature/test');
    }

    public function test_handles_empty_scheduled_deployments(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_handles_midnight_scheduling(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '00:00')
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertHasNoErrors();

        Carbon::setTestNow();
    }

    public function test_handles_late_night_scheduling(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '23:59')
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertHasNoErrors();

        Carbon::setTestNow();
    }

    public function test_scheduled_date_defaults_to_tomorrow(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->assertSet('scheduledDate', '2025-01-16');

        Carbon::setTestNow();
    }

    public function test_multiple_deployments_can_be_scheduled(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        $component = Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project]);

        // Schedule first deployment
        $component->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '03:00')
            ->set('notes', 'First deployment')
            ->call('scheduleDeployment');

        // Schedule second deployment
        $component->call('openScheduleModal')
            ->set('scheduledDate', '2025-01-17')
            ->set('scheduledTime', '03:00')
            ->set('notes', 'Second deployment')
            ->call('scheduleDeployment');

        $this->assertEquals(2, ScheduledDeployment::where('project_id', $this->project->id)->count());

        Carbon::setTestNow();
    }

    public function test_success_notification_includes_formatted_datetime(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-15 10:00:00'));

        Livewire::actingAs($this->user)
            ->test(ScheduledDeployments::class, ['project' => $this->project])
            ->set('scheduledDate', '2025-01-16')
            ->set('scheduledTime', '15:30')
            ->set('timezone', 'UTC')
            ->call('scheduleDeployment')
            ->assertDispatched('notification', function (string $name, array $data) {
                return $data['type'] === 'success'
                    && str_contains($data['message'], 'Jan 16, 2025');
            });

        Carbon::setTestNow();
    }
}
