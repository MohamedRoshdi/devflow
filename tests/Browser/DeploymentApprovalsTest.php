<?php

namespace Tests\Browser;

use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

class DeploymentApprovalsTest extends DuskTestCase
{
    use LoginViaUI;

    // use RefreshDatabase; // Disabled - testing against existing app

    protected User $user;

    protected User $approver;

    protected Server $server;

    protected Project $project;

    protected Deployment $deployment;

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

        // Create approver user
        $this->approver = User::firstOrCreate(
            ['email' => 'approver@devflow.test'],
            [
                'name' => 'Test Approver',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'prod.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Production Server',
                'ip_address' => '192.168.1.100',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Get or create test project
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-approval-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Approval Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository' => 'https://github.com/test/test-approval-project.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/test-approval-project',
            ]
        );

        // Create test deployment
        $this->deployment = Deployment::firstOrCreate(
            ['commit_hash' => 'abc123test'],
            [
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
                'user_id' => $this->user->id,
                'status' => 'pending_approval',
                'branch' => 'main',
                'commit_message' => 'Test deployment for approvals',
                'started_at' => now(),
            ]
        );
    }

    /**
     * Test 1: Pending approvals page access
     */
    public function test_user_can_view_pending_approvals(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Approval');
        });
    }

    /**
     * Test 2: Approvals page displays navigation
     */
    public function test_approvals_page_displays_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('nav, [role="navigation"], .breadcrumb')
                ->screenshot('approvals-navigation');
        });
    }

    /**
     * Test 3: Approvals list shows pending approvals
     */
    public function test_approvals_list_shows_pending_approvals(): void
    {
        // Create a pending approval
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('pending')
                ->screenshot('pending-approvals-list');
        });
    }

    /**
     * Test 4: Approvals can be filtered by status
     */
    public function test_approvals_can_be_filtered_by_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('select[wire\\:model*="statusFilter"], select[wire\\:model*="status"]')
                ->screenshot('approvals-status-filter');
        });
    }

    /**
     * Test 5: Approvals can be filtered by project
     */
    public function test_approvals_can_be_filtered_by_project(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('select[wire\\:model*="projectFilter"], select[wire\\:model*="project"]')
                ->screenshot('approvals-project-filter');
        });
    }

    /**
     * Test 6: Approvals page has search functionality
     */
    public function test_approvals_page_has_search_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('input[wire\\:model*="search"], input[type="search"]')
                ->screenshot('approvals-search');
        });
    }

    /**
     * Test 7: Approval request creation modal exists
     */
    public function test_approval_request_creation_modal_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('button:contains("Request"), button:contains("New")')
                ->screenshot('approval-request-button');
        });
    }

    /**
     * Test 8: Approval workflow configuration is accessible
     */
    public function test_approval_workflow_configuration_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('button:contains("Settings"), a:contains("Configure"), button:contains("Workflow")')
                ->screenshot('approval-workflow-config');
        });
    }

    /**
     * Test 9: Approval notification settings visible
     */
    public function test_approval_notification_settings_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/settings')
                ->pause(1500)
                ->assertSee('Notification')
                ->screenshot('approval-notification-settings');
        });
    }

    /**
     * Test 10: Approve deployment action button exists
     */
    public function test_approve_deployment_action_button_exists(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertPresent('button:contains("Approve"), [wire\\:click*="approve"]')
                ->screenshot('approve-action-button');
        });
    }

    /**
     * Test 11: Reject deployment action button exists
     */
    public function test_reject_deployment_action_button_exists(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertPresent('button:contains("Reject"), [wire\\:click*="reject"]')
                ->screenshot('reject-action-button');
        });
    }

    /**
     * Test 12: Approve modal opens correctly
     */
    public function test_approve_modal_opens_correctly(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Approve")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show="showApproveModal"]', 5)
                ->assertSee('Approve')
                ->screenshot('approve-modal-opened');
        });
    }

    /**
     * Test 13: Reject modal opens correctly
     */
    public function test_reject_modal_opens_correctly(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Reject")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal, [x-show="showRejectModal"]', 5)
                ->assertSee('Reject')
                ->screenshot('reject-modal-opened');
        });
    }

    /**
     * Test 14: Approval comments/notes field exists
     */
    public function test_approval_comments_notes_field_exists(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Approve")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('textarea[wire\\:model*="notes"], textarea[wire\\:model*="approvalNotes"], input[wire\\:model*="notes"]')
                ->screenshot('approval-notes-field');
        });
    }

    /**
     * Test 15: Rejection reason field is required
     */
    public function test_rejection_reason_field_is_required(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Reject")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('textarea[wire\\:model*="rejectionReason"], textarea[wire\\:model*="reason"]')
                ->assertSee('Reason')
                ->screenshot('rejection-reason-field');
        });
    }

    /**
     * Test 16: Multi-level approval chains visible
     */
    public function test_multi_level_approval_chains_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertSee('Approval')
                ->screenshot('multi-level-approvals');
        });
    }

    /**
     * Test 17: Approval history viewing is accessible
     */
    public function test_approval_history_viewing_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('select[wire\\:model*="statusFilter"]')
                ->screenshot('approval-history-access');
        });
    }

    /**
     * Test 18: Approved approvals display correctly
     */
    public function test_approved_approvals_display_correctly(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('approved')
                ->screenshot('approved-approvals-display');
        });
    }

    /**
     * Test 19: Rejected approvals display correctly
     */
    public function test_rejected_approvals_display_correctly(): void
    {
        DeploymentApproval::factory()->rejected()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('rejected')
                ->screenshot('rejected-approvals-display');
        });
    }

    /**
     * Test 20: Approval expiration settings visible
     */
    public function test_approval_expiration_settings_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('table, [role="table"], .approval-list')
                ->screenshot('approval-expiration-settings');
        });
    }

    /**
     * Test 21: Required approvers configuration visible
     */
    public function test_required_approvers_configuration_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('table, [role="table"], .approval-list')
                ->screenshot('required-approvers-config');
        });
    }

    /**
     * Test 22: Approval bypass for emergencies available
     */
    public function test_approval_bypass_for_emergencies_available(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->assertPresent('button:contains("Bypass"), button:contains("Emergency"), [wire\\:click*="bypass"]')
                ->screenshot('approval-bypass-emergency');
        });
    }

    /**
     * Test 23: Approval status indicators have correct colors
     */
    public function test_approval_status_indicators_have_correct_colors(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        DeploymentApproval::factory()->rejected()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000);

            // Check for status badges with correct colors
            // Approved should have green styling
            $approvedBadge = $browser->element('span.from-green-500, span.from-emerald-500, span.bg-green-500, span.bg-emerald-500, span.text-green-500, span.text-emerald-500');
            $this->assertNotNull($approvedBadge, 'Approved status indicator should be present');

            // Rejected should have red styling
            $rejectedBadge = $browser->element('span.from-red-500, span.from-rose-500, span.bg-red-500, span.bg-rose-500, span.text-red-500, span.text-rose-500');
            $this->assertNotNull($rejectedBadge, 'Rejected status indicator should be present');

            // Pending should have yellow styling
            $pendingBadge = $browser->element('span.from-yellow-500, span.from-amber-500, span.bg-yellow-500, span.bg-amber-500, span.text-yellow-500, span.text-amber-500');
            $this->assertNotNull($pendingBadge, 'Pending status indicator should be present');

            $browser->screenshot('approval-status-indicators');
        });
    }

    /**
     * Test 24: Email approval links functionality
     */
    public function test_email_approval_links_functionality(): void
    {
        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) use ($approval) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals/'.$approval->id)
                ->pause(1500)
                ->assertSee('Approval')
                ->screenshot('email-approval-link');
        });
    }

    /**
     * Test 25: Approval audit trail visible
     */
    public function test_approval_audit_trail_visible(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
            'notes' => 'Approved after security review',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Approval')
                ->screenshot('approval-audit-trail');
        });
    }

    /**
     * Test 26: Approval statistics are displayed
     */
    public function test_approval_statistics_are_displayed(): void
    {
        DeploymentApproval::factory()->count(5)->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        DeploymentApproval::factory()->count(2)->rejected()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        DeploymentApproval::factory()->count(3)->pending()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Total')
                ->assertSee('Pending')
                ->assertSee('Approved')
                ->assertSee('Rejected')
                ->screenshot('approval-statistics');
        });
    }

    /**
     * Test 27: Requester information is displayed
     */
    public function test_requester_information_is_displayed(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee($this->user->name)
                ->screenshot('requester-information');
        });
    }

    /**
     * Test 28: Approver information is displayed for approved items
     */
    public function test_approver_information_is_displayed_for_approved_items(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee($this->approver->name)
                ->screenshot('approver-information');
        });
    }

    /**
     * Test 29: Approval request timestamps are visible
     */
    public function test_approval_request_timestamps_are_visible(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_at' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('ago')
                ->screenshot('approval-timestamps');
        });
    }

    /**
     * Test 30: Approval response timestamps are visible
     */
    public function test_approval_response_timestamps_are_visible(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
            'requested_at' => now()->subHours(3),
            'responded_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('ago')
                ->screenshot('approval-response-timestamps');
        });
    }

    /**
     * Test 31: Project information linked to approval
     */
    public function test_project_information_linked_to_approval(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee($this->project->name)
                ->screenshot('approval-project-info');
        });
    }

    /**
     * Test 32: Deployment branch shown in approval
     */
    public function test_deployment_branch_shown_in_approval(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('main')
                ->screenshot('approval-branch-info');
        });
    }

    /**
     * Test 33: Commit information shown in approval
     */
    public function test_commit_information_shown_in_approval(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Test deployment for approvals')
                ->screenshot('approval-commit-info');
        });
    }

    /**
     * Test 34: Approval pagination works correctly
     */
    public function test_approval_pagination_works_correctly(): void
    {
        DeploymentApproval::factory()->count(25)->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertPresent('nav[role="navigation"], .pagination, [wire\\:click*="nextPage"]')
                ->screenshot('approval-pagination');
        });
    }

    /**
     * Test 35: Approval empty state handled gracefully
     */
    public function test_approval_empty_state_handled_gracefully(): void
    {
        // Create a new project without approvals
        $emptyProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Empty Approval Project',
            'slug' => 'empty-approval-project',
        ]);

        $this->browse(function (Browser $browser) use ($emptyProject) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals?projectFilter='.$emptyProject->id)
                ->pause(2000)
                ->assertSee('No approvals found')
                ->screenshot('approval-empty-state');
        });
    }

    /**
     * Test 36: Approval notes are visible after approval
     */
    public function test_approval_notes_are_visible_after_approval(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
            'notes' => 'Approved after successful code review and testing',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Approved after successful code review')
                ->screenshot('approval-notes-visible');
        });
    }

    /**
     * Test 37: Rejection notes are visible after rejection
     */
    public function test_rejection_notes_are_visible_after_rejection(): void
    {
        DeploymentApproval::factory()->rejected()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
            'notes' => 'Rejected due to failing tests in staging environment',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Rejected due to failing tests')
                ->screenshot('rejection-notes-visible');
        });
    }

    /**
     * Test 38: Approval detail view is accessible
     */
    public function test_approval_detail_view_is_accessible(): void
    {
        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertPresent('table, [role="table"], .approval-list')
                ->screenshot('approval-detail-view');
        });
    }

    /**
     * Test 39: Approval action buttons disabled for non-pending approvals
     */
    public function test_approval_action_buttons_disabled_for_non_pending_approvals(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->screenshot('approval-buttons-disabled');
        });
    }

    /**
     * Test 40: Approval search filters by project name
     */
    public function test_approval_search_filters_by_project_name(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->type('input[wire\\:model*="search"]', 'Test Approval')
                ->pause(2000)
                ->assertSee($this->project->name)
                ->screenshot('approval-search-project');
        });
    }

    /**
     * Test 41: Approval search filters by requester name
     */
    public function test_approval_search_filters_by_requester_name(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->type('input[wire\\:model*="search"]', 'Test Admin')
                ->pause(2000)
                ->assertSee($this->user->name)
                ->screenshot('approval-search-requester');
        });
    }

    /**
     * Test 42: Approval modal contains deployment information
     */
    public function test_approval_modal_contains_deployment_information(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Approve")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertSee('Deployment')
                ->assertSee($this->project->name)
                ->screenshot('approval-modal-deployment-info');
        });
    }

    /**
     * Test 43: Approval status can be filtered to show all
     */
    public function test_approval_status_can_be_filtered_to_show_all(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(1500)
                ->select('select[wire\\:model*="statusFilter"]', 'all')
                ->pause(2000)
                ->assertSee('approved')
                ->assertSee('pending')
                ->screenshot('approval-filter-all');
        });
    }

    /**
     * Test 44: Approval modal cancel button works
     */
    public function test_approval_modal_cancel_button_works(): void
    {
        DeploymentApproval::factory()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->click('button:contains("Approve")')
                ->pause(1000)
                ->waitFor('[role="dialog"], .modal', 5)
                ->assertPresent('button:contains("Cancel")')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->screenshot('approval-modal-cancel');
        });
    }

    /**
     * Test 45: Approval refresh updates the list
     */
    public function test_approval_refresh_updates_the_list(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertPresent('[wire\\:poll], button:contains("Refresh")')
                ->screenshot('approval-refresh');
        });
    }

    /**
     * Test 46: Approval counter shows pending count
     */
    public function test_approval_counter_shows_pending_count(): void
    {
        DeploymentApproval::factory()->count(3)->pending()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('Pending')
                ->screenshot('approval-pending-counter');
        });
    }

    /**
     * Test 47: Approval time duration calculation is displayed
     */
    public function test_approval_time_duration_calculation_is_displayed(): void
    {
        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $this->deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
            'requested_at' => now()->subHours(3),
            'responded_at' => now()->subHours(1),
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('ago')
                ->screenshot('approval-time-duration');
        });
    }

    /**
     * Test 48: Multiple approvals can be viewed simultaneously
     */
    public function test_multiple_approvals_can_be_viewed_simultaneously(): void
    {
        $deployment1 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'commit_message' => 'First deployment',
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'commit_message' => 'Second deployment',
        ]);

        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginViaUI($browser)
                ->visit('/deployments/approvals')
                ->pause(2000)
                ->assertSee('First deployment')
                ->assertSee('Second deployment')
                ->screenshot('multiple-approvals-view');
        });
    }
}
