<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\ScheduledDeployment;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class ScheduledDeploymentsTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'scheduled-deploy.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Scheduled Deploy Server',
                'ip_address' => '192.168.1.150',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-scheduled-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Scheduled Test Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/scheduled-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/scheduled-project',
            ]
        );
    }

    /**
     * Test 1: Scheduled deployments page loads successfully
     */
    public function test_scheduled_deployments_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Scheduled Deployments')
                ->screenshot('scheduled-deployments-page-loads');
        });
    }

    /**
     * Test 2: Schedule deployment button is visible
     */
    public function test_schedule_deployment_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Schedule Deployment')
                ->assertPresent('button:contains("Schedule Deployment")')
                ->screenshot('schedule-deployment-button-visible');
        });
    }

    /**
     * Test 3: Clicking schedule button opens modal
     */
    public function test_clicking_schedule_button_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertSee('Schedule Deployment')
                ->assertSee('Branch')
                ->assertSee('Date')
                ->assertSee('Time')
                ->assertSee('Timezone')
                ->screenshot('schedule-modal-opened');
        });
    }

    /**
     * Test 4: Schedule modal displays all form fields
     */
    public function test_schedule_modal_displays_all_form_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('select[wire\:model="selectedBranch"]')
                ->assertPresent('input[wire\:model="scheduledDate"]')
                ->assertPresent('input[wire\:model="scheduledTime"]')
                ->assertPresent('select[wire\:model="timezone"]')
                ->assertPresent('textarea[wire\:model="notes"]')
                ->assertPresent('input[wire\:model="notifyBefore"]')
                ->screenshot('schedule-modal-all-fields');
        });
    }

    /**
     * Test 5: Branch selector shows available branches
     */
    public function test_branch_selector_shows_available_branches(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('select[wire\:model="selectedBranch"]')
                ->assertSee('main')
                ->screenshot('branch-selector-available');
        });
    }

    /**
     * Test 6: Default branch is pre-selected
     */
    public function test_default_branch_is_preselected(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertSelected('select[wire\:model="selectedBranch"]', 'main')
                ->screenshot('default-branch-selected');
        });
    }

    /**
     * Test 7: Date field has minimum date set to today
     */
    public function test_date_field_has_minimum_date_today(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertAttribute('input[wire\:model="scheduledDate"]', 'min', now()->format('Y-m-d'))
                ->screenshot('date-minimum-today');
        });
    }

    /**
     * Test 8: Timezone selector displays timezone options
     */
    public function test_timezone_selector_displays_options(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('select[wire\:model="timezone"]')
                ->assertSeeIn('select[wire\:model="timezone"]', 'UTC')
                ->assertSeeIn('select[wire\:model="timezone"]', 'Eastern Time')
                ->assertSeeIn('select[wire\:model="timezone"]', 'Pacific Time')
                ->screenshot('timezone-options-visible');
        });
    }

    /**
     * Test 9: Notes field accepts text input
     */
    public function test_notes_field_accepts_text(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->type('textarea[wire\:model="notes"]', 'Test deployment scheduled for maintenance')
                ->pause(500)
                ->assertInputValue('textarea[wire\:model="notes"]', 'Test deployment scheduled for maintenance')
                ->screenshot('notes-field-text-input');
        });
    }

    /**
     * Test 10: Notify before checkbox is checked by default
     */
    public function test_notify_before_checkbox_default_checked(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertChecked('input[wire\:model="notifyBefore"]')
                ->screenshot('notify-checkbox-default-checked');
        });
    }

    /**
     * Test 11: Notify minutes selector appears when notify is enabled
     */
    public function test_notify_minutes_selector_appears_when_enabled(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('select[wire\:model="notifyMinutes"]')
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '15 minutes')
                ->screenshot('notify-minutes-selector-visible');
        });
    }

    /**
     * Test 12: Notify minutes selector has all time options
     */
    public function test_notify_minutes_selector_has_all_options(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '5 minutes')
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '10 minutes')
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '15 minutes')
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '30 minutes')
                ->assertSeeIn('select[wire\:model="notifyMinutes"]', '1 hour')
                ->screenshot('notify-minutes-all-options');
        });
    }

    /**
     * Test 13: Modal has cancel button
     */
    public function test_modal_has_cancel_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('button:contains("Cancel")')
                ->screenshot('modal-cancel-button-visible');
        });
    }

    /**
     * Test 14: Modal has schedule submit button
     */
    public function test_modal_has_schedule_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('button[type="submit"]:contains("Schedule")')
                ->screenshot('modal-schedule-button-visible');
        });
    }

    /**
     * Test 15: Clicking cancel closes the modal
     */
    public function test_clicking_cancel_closes_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('input[wire\:model="scheduledDate"]')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->assertMissing('input[wire\:model="scheduledDate"]')
                ->screenshot('modal-closed-after-cancel');
        });
    }

    /**
     * Test 16: Clicking X button closes the modal
     */
    public function test_clicking_x_button_closes_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('input[wire\:model="scheduledDate"]')
                ->click('button[wire\:click="closeScheduleModal"]')
                ->pause(500)
                ->assertMissing('input[wire\:model="scheduledDate"]')
                ->screenshot('modal-closed-after-x-button');
        });
    }

    /**
     * Test 17: Empty state shows when no scheduled deployments exist
     */
    public function test_empty_state_shows_when_no_scheduled_deployments(): void
    {
        // Ensure no scheduled deployments exist
        ScheduledDeployment::where('project_id', $this->project->id)->delete();

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('No scheduled deployments')
                ->assertSee('Schedule a deployment for off-peak hours')
                ->screenshot('empty-state-no-scheduled-deployments');
        });
    }

    /**
     * Test 18: Scheduled deployments list displays pending deployments
     */
    public function test_scheduled_deployments_list_displays_pending(): void
    {
        // Create a pending scheduled deployment
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'Test scheduled deployment',
            'notify_before' => true,
            'notify_minutes' => 15,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Pending')
                ->assertSee('main')
                ->assertSee('Test scheduled deployment')
                ->screenshot('pending-deployment-visible');
        });
    }

    /**
     * Test 19: Pending deployment shows status badge
     */
    public function test_pending_deployment_shows_status_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertPresent('.bg-yellow-100, .bg-yellow-900')
                ->assertSee('Pending')
                ->screenshot('pending-status-badge');
        });
    }

    /**
     * Test 20: Scheduled deployment shows branch badge
     */
    public function test_scheduled_deployment_shows_branch_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'staging',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('staging')
                ->assertPresent('.bg-gray-100, .bg-gray-700')
                ->screenshot('branch-badge-visible');
        });
    }

    /**
     * Test 21: Scheduled deployment displays scheduled time
     */
    public function test_scheduled_deployment_displays_scheduled_time(): void
    {
        $scheduledTime = now()->addDay()->setTime(14, 30);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => $scheduledTime,
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($scheduledTime) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee($scheduledTime->format('M d, Y'))
                ->screenshot('scheduled-time-displayed');
        });
    }

    /**
     * Test 22: Scheduled deployment displays timezone
     */
    public function test_scheduled_deployment_displays_timezone(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'America/New_York',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('America/New_York')
                ->screenshot('timezone-displayed');
        });
    }

    /**
     * Test 23: Scheduled deployment displays notes
     */
    public function test_scheduled_deployment_displays_notes(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'Critical security patch deployment',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Critical security patch deployment')
                ->screenshot('notes-displayed');
        });
    }

    /**
     * Test 24: Scheduled deployment shows who scheduled it
     */
    public function test_scheduled_deployment_shows_scheduled_by(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Scheduled by '.$this->user->name)
                ->screenshot('scheduled-by-user-displayed');
        });
    }

    /**
     * Test 25: Scheduled deployment shows notification settings
     */
    public function test_scheduled_deployment_shows_notification_settings(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notify_before' => true,
            'notify_minutes' => 30,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Notify 30min before')
                ->screenshot('notification-settings-displayed');
        });
    }

    /**
     * Test 26: Pending deployment shows time until deployment
     */
    public function test_pending_deployment_shows_time_until(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addHours(2),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('in 2 hours')
                ->screenshot('time-until-displayed');
        });
    }

    /**
     * Test 27: Cancel button appears for pending deployments
     */
    public function test_cancel_button_appears_for_pending(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertPresent('button[wire\:click*="cancelScheduledDeployment"]')
                ->screenshot('cancel-button-visible');
        });
    }

    /**
     * Test 28: Cancel button does not appear for completed deployments
     */
    public function test_cancel_button_not_visible_for_completed(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subDay(),
            'timezone' => 'UTC',
            'status' => 'completed',
            'executed_at' => now()->subHour(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertMissing('button[wire\:click*="cancelScheduledDeployment"]')
                ->screenshot('cancel-button-not-visible-completed');
        });
    }

    /**
     * Test 29: Completed deployment shows green status badge
     */
    public function test_completed_deployment_shows_green_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subDay(),
            'timezone' => 'UTC',
            'status' => 'completed',
            'executed_at' => now()->subHour(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Completed')
                ->assertPresent('.bg-green-100, .bg-green-900')
                ->screenshot('completed-status-badge');
        });
    }

    /**
     * Test 30: Failed deployment shows red status badge
     */
    public function test_failed_deployment_shows_red_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subDay(),
            'timezone' => 'UTC',
            'status' => 'failed',
            'executed_at' => now()->subHour(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Failed')
                ->assertPresent('.bg-red-100, .bg-red-900')
                ->screenshot('failed-status-badge');
        });
    }

    /**
     * Test 31: Running deployment shows blue badge with spinner
     */
    public function test_running_deployment_shows_blue_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subMinutes(5),
            'timezone' => 'UTC',
            'status' => 'running',
            'executed_at' => now()->subMinutes(5),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Running')
                ->assertPresent('.bg-blue-100, .bg-blue-900')
                ->assertPresent('.animate-spin')
                ->screenshot('running-status-badge-spinner');
        });
    }

    /**
     * Test 32: Cancelled deployment shows grey badge
     */
    public function test_cancelled_deployment_shows_grey_badge(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'cancelled',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Cancelled')
                ->assertPresent('.bg-gray-100, .bg-gray-900')
                ->screenshot('cancelled-status-badge');
        });
    }

    /**
     * Test 33: View deployment link appears when deployment_id exists
     */
    public function test_view_deployment_link_appears_when_deployment_exists(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => 'success',
        ]);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subHour(),
            'timezone' => 'UTC',
            'status' => 'completed',
            'deployment_id' => $deployment->id,
            'executed_at' => now()->subHour(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertPresent('a[href*="deployments"]')
                ->screenshot('view-deployment-link-visible');
        });
    }

    /**
     * Test 34: Multiple scheduled deployments are sorted by scheduled time
     */
    public function test_multiple_scheduled_deployments_sorted_by_time(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDays(3),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'Third deployment',
        ]);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'First deployment',
        ]);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDays(2),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'Second deployment',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('First deployment')
                ->assertSee('Second deployment')
                ->assertSee('Third deployment')
                ->screenshot('multiple-deployments-sorted');
        });
    }

    /**
     * Test 35: Can select different branch from dropdown
     */
    public function test_can_select_different_branch(): void
    {
        // Add multiple branches to the project
        $this->project->update(['available_branches' => ['main', 'staging', 'develop']]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->select('select[wire\:model="selectedBranch"]', 'staging')
                ->pause(500)
                ->assertSelected('select[wire\:model="selectedBranch"]', 'staging')
                ->screenshot('different-branch-selected');
        });
    }

    /**
     * Test 36: Can select different timezone
     */
    public function test_can_select_different_timezone(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->select('select[wire\:model="timezone"]', 'America/New_York')
                ->pause(500)
                ->assertSelected('select[wire\:model="timezone"]', 'America/New_York')
                ->screenshot('different-timezone-selected');
        });
    }

    /**
     * Test 37: Can change notification time
     */
    public function test_can_change_notification_time(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->select('select[wire\:model="notifyMinutes"]', '30')
                ->pause(500)
                ->assertSelected('select[wire\:model="notifyMinutes"]', '30')
                ->screenshot('notification-time-changed');
        });
    }

    /**
     * Test 38: Can uncheck notify before checkbox
     */
    public function test_can_uncheck_notify_before(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->uncheck('input[wire\:model="notifyBefore"]')
                ->pause(500)
                ->assertNotChecked('input[wire\:model="notifyBefore"]')
                ->screenshot('notify-before-unchecked');
        });
    }

    /**
     * Test 39: Notify minutes selector hides when notify is disabled
     */
    public function test_notify_minutes_hides_when_notify_disabled(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertPresent('select[wire\:model="notifyMinutes"]')
                ->uncheck('input[wire\:model="notifyBefore"]')
                ->pause(500)
                ->waitUntilMissing('select[wire\:model="notifyMinutes"]')
                ->screenshot('notify-minutes-hidden');
        });
    }

    /**
     * Test 40: Can set future date in date picker
     */
    public function test_can_set_future_date(): void
    {
        $futureDate = now()->addDays(7)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($futureDate) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->clear('input[wire\:model="scheduledDate"]')
                ->type('input[wire\:model="scheduledDate"]', $futureDate)
                ->pause(500)
                ->assertInputValue('input[wire\:model="scheduledDate"]', $futureDate)
                ->screenshot('future-date-set');
        });
    }

    /**
     * Test 41: Can set time in time picker
     */
    public function test_can_set_time(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->clear('input[wire\:model="scheduledTime"]')
                ->type('input[wire\:model="scheduledTime"]', '14:30')
                ->pause(500)
                ->assertInputValue('input[wire\:model="scheduledTime"]', '14:30')
                ->screenshot('time-set');
        });
    }

    /**
     * Test 42: Default time is set to 3 AM for off-peak
     */
    public function test_default_time_is_3am(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertInputValue('input[wire\:model="scheduledTime"]', '03:00')
                ->screenshot('default-time-3am');
        });
    }

    /**
     * Test 43: Default date is tomorrow
     */
    public function test_default_date_is_tomorrow(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertInputValue('input[wire\:model="scheduledDate"]', now()->addDay()->format('Y-m-d'))
                ->screenshot('default-date-tomorrow');
        });
    }

    /**
     * Test 44: Modal closes and list refreshes after successful scheduling
     */
    public function test_modal_closes_after_successful_schedule(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->type('textarea[wire\:model="notes"]', 'Auto-scheduled deployment')
                ->pause(500)
                ->press('Schedule')
                ->pause(2000)
                ->waitUntilMissing('input[wire\:model="scheduledDate"]')
                ->assertSee('Auto-scheduled deployment')
                ->screenshot('modal-closed-after-schedule');
        });
    }

    /**
     * Test 45: Success notification appears after scheduling
     */
    public function test_success_notification_after_scheduling(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->press('Schedule')
                ->pause(2000)
                ->assertSee('Deployment scheduled successfully')
                ->screenshot('success-notification-visible');
        });
    }

    /**
     * Test 46: Pending status badge has pulse animation
     */
    public function test_pending_badge_has_pulse_animation(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertPresent('.animate-pulse')
                ->screenshot('pending-badge-pulse-animation');
        });
    }

    /**
     * Test 47: Overdue deployment shows "Overdue" status
     */
    public function test_overdue_deployment_shows_overdue_status(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->subHours(2),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Overdue')
                ->screenshot('overdue-deployment-status');
        });
    }

    /**
     * Test 48: Form fields reset when modal is reopened after closing
     */
    public function test_form_resets_when_modal_reopened(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->type('textarea[wire\:model="notes"]', 'Custom notes')
                ->select('select[wire\:model="timezone"]', 'America/New_York')
                ->pause(500)
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->click('button:contains("Schedule Deployment")')
                ->pause(500)
                ->assertInputValue('textarea[wire\:model="notes"]', '')
                ->assertSelected('select[wire\:model="timezone"]', 'UTC')
                ->screenshot('form-reset-after-reopen');
        });
    }

    /**
     * Test 49: Multiple status types can be displayed together
     */
    public function test_multiple_status_types_displayed_together(): void
    {
        // Create deployments with different statuses
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
        ]);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'staging',
            'scheduled_at' => now()->subDay(),
            'timezone' => 'UTC',
            'status' => 'completed',
            'executed_at' => now()->subDay(),
        ]);

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'develop',
            'scheduled_at' => now()->subDays(2),
            'timezone' => 'UTC',
            'status' => 'cancelled',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertSee('Pending')
                ->assertSee('Completed')
                ->assertSee('Cancelled')
                ->screenshot('multiple-status-types-together');
        });
    }

    /**
     * Test 50: Scheduled deployments list has proper responsive design
     */
    public function test_scheduled_deployments_responsive_design(): void
    {
        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'main',
            'scheduled_at' => now()->addDay(),
            'timezone' => 'UTC',
            'status' => 'pending',
            'notes' => 'Responsive test deployment',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/projects/'.$this->project->id)
                ->waitForText($this->project->name, 10)
                ->assertPresent('.rounded-xl')
                ->assertPresent('.shadow-sm')
                ->assertPresent('.border')
                ->assertSee('Responsive test deployment')
                ->screenshot('responsive-design-desktop');

            // Test mobile viewport
            $browser->resize(375, 667)
                ->pause(500)
                ->assertSee('Responsive test deployment')
                ->screenshot('responsive-design-mobile');
        });
    }
}
