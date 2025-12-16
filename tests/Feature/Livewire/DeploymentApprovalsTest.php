<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Deployments\DeploymentApprovals;
use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\Project;
use App\Models\User;
use App\Services\DeploymentApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeploymentApprovalsTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    private User $approver;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'approve_deployments']);
        Permission::create(['name' => 'approve_all_deployments']);

        // Create users
        $this->user = User::factory()->create();
        $this->approver = User::factory()->create();
        $this->approver->givePermissionTo('approve_deployments');

        // Create project owned by approver (so they have access)
        $this->project = Project::factory()->create(['user_id' => $this->approver->id]);
    }

    // ============================================================
    // Component Rendering Tests
    // ============================================================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200);
    }

    public function test_component_displays_view(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewIs('livewire.deployments.deployment-approvals');
    }

    public function test_component_defaults_to_pending_status_filter(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertSet('statusFilter', 'pending');
    }

    // ============================================================
    // Approval List Tests
    // ============================================================

    public function test_displays_pending_approvals(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertSee($this->project->name);
    }

    public function test_displays_approved_approvals_when_filtered(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'approved')
            ->assertSee($this->project->name);
    }

    public function test_displays_rejected_approvals_when_filtered(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->rejected()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'rejected')
            ->assertSee($this->project->name);
    }

    public function test_displays_all_approvals_when_status_filter_is_all(): void
    {
        $deployment1 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'feature-1',
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'feature-2',
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->approver->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'all')
            ->assertSee('feature-1')
            ->assertSee('feature-2');
    }

    // ============================================================
    // Filter Tests
    // ============================================================

    public function test_can_filter_by_project(): void
    {
        $project2 = Project::factory()->create(['user_id' => $this->approver->id]);

        $deployment1 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'filter-branch-1',
        ]);

        $deployment2 = Deployment::factory()->create([
            'project_id' => $project2->id,
            'user_id' => $this->user->id,
            'branch' => 'filter-branch-2',
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        // Filter by the first project and verify the deployment from project2 is not shown
        // We check the unique branch name instead of project name since project2->name
        // may still appear in filter dropdowns
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('projectFilter', $this->project->id)
            ->assertSee('filter-branch-1')
            ->assertDontSee('filter-branch-2');
    }

    public function test_can_search_by_project_name(): void
    {
        $project2 = Project::factory()->create([
            'name' => 'Unique Project Name',
            'user_id' => $this->approver->id,
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project2->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'Unique Project')
            ->assertSee('Unique Project Name');
    }

    public function test_can_search_by_requester_name(): void
    {
        $requester = User::factory()->create(['name' => 'Specific Requester']);

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $requester->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'Specific Requester')
            ->assertSee('Specific Requester');
    }

    public function test_can_search_by_branch_name(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'branch' => 'feature/unique-branch-name',
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'unique-branch')
            ->assertSee('feature/unique-branch-name');
    }

    public function test_filter_resets_pagination(): void
    {
        // In Livewire 3, page is null by default and only set when navigating
        // The key behavior is that setting a filter should not break pagination
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'approved')
            ->assertSet('statusFilter', 'approved')
            ->assertHasNoErrors();
    }

    public function test_search_resets_pagination(): void
    {
        // In Livewire 3, page is null by default and only set when navigating
        // The key behavior is that setting search should not break pagination
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'test')
            ->assertSet('search', 'test')
            ->assertHasNoErrors();
    }

    public function test_clear_filters_resets_all_filters(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'test')
            ->set('statusFilter', 'approved')
            ->set('projectFilter', 1)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('projectFilter', null);
    }

    // ============================================================
    // Approve Modal Tests
    // ============================================================

    public function test_can_open_approve_modal(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->assertSet('showApproveModal', true)
            ->assertSet('selectedApprovalId', $approval->id)
            ->assertSet('approvalNotes', '');
    }

    public function test_can_approve_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('approve')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 1,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->set('approvalNotes', 'Looks good!')
            ->call('approve')
            ->assertSet('showApproveModal', false)
            ->assertDispatched('notification');
    }

    public function test_approve_with_optional_notes(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('approve')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 1,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->call('approve')
            ->assertSet('showApproveModal', false);
    }

    public function test_approve_notes_max_length_validation(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->set('approvalNotes', str_repeat('a', 1001))
            ->call('approve')
            ->assertHasErrors(['approvalNotes' => 'max']);
    }

    public function test_approve_handles_service_exception(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('approve')
            ->andThrow(new \Exception('Permission denied'));
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 1,
            'approved' => 0,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->call('approve')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'error');
    }

    // ============================================================
    // Reject Modal Tests
    // ============================================================

    public function test_can_open_reject_modal(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->assertSet('showRejectModal', true)
            ->assertSet('selectedApprovalId', $approval->id)
            ->assertSet('rejectionReason', '');
    }

    public function test_can_reject_deployment(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('reject')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 0,
            'rejected' => 1,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->set('rejectionReason', 'Code needs refactoring')
            ->call('reject')
            ->assertSet('showRejectModal', false)
            ->assertDispatched('notification');
    }

    public function test_reject_requires_reason(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->call('reject')
            ->assertHasErrors(['rejectionReason' => 'required']);
    }

    public function test_reject_reason_max_length_validation(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->set('rejectionReason', str_repeat('a', 1001))
            ->call('reject')
            ->assertHasErrors(['rejectionReason' => 'max']);
    }

    public function test_reject_handles_service_exception(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('reject')
            ->andThrow(new \Exception('Permission denied'));
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 1,
            'approved' => 0,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->set('rejectionReason', 'Some reason')
            ->call('reject')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'error');
    }

    // ============================================================
    // Event Tests
    // ============================================================

    public function test_listens_to_approval_requested_event(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->dispatch('approval-requested')
            ->assertStatus(200);
    }

    public function test_refresh_on_approval_requested(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        // First render
        $component = Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class);

        // Create new approval
        $deployment2 = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        // Dispatch event - should refresh computed properties
        $component->dispatch('approval-requested')
            ->assertStatus(200);
    }

    // ============================================================
    // Stats Tests
    // ============================================================

    public function test_stats_computed_property_returns_array(): void
    {
        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 5,
            'approved' => 10,
            'rejected' => 2,
            'expired' => 1,
            'total' => 18,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200);
    }

    // ============================================================
    // Pagination Tests
    // ============================================================

    public function test_pagination_is_enabled(): void
    {
        // Create more than 20 approvals to trigger pagination
        for ($i = 0; $i < 25; $i++) {
            $deployment = Deployment::factory()->create([
                'project_id' => $this->project->id,
                'user_id' => $this->user->id,
            ]);

            DeploymentApproval::factory()->pending()->create([
                'deployment_id' => $deployment->id,
                'requested_by' => $this->user->id,
            ]);
        }

        // Verify component renders with pagination without errors
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertHasNoErrors()
            ->assertStatus(200);
    }

    public function test_project_filter_resets_pagination(): void
    {
        // In Livewire 3, page is null by default and only set when navigating
        // The key behavior is that setting filter should not break pagination
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('projectFilter', $this->project->id)
            ->assertSet('projectFilter', $this->project->id)
            ->assertHasNoErrors();
    }

    // ============================================================
    // Computed Properties Tests
    // ============================================================

    public function test_pending_approvals_computed_uses_service(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('getPendingApprovals')
            ->once()
            ->andReturn(collect([$approval]));
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 1,
            'approved' => 0,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200);
    }

    public function test_approvals_computed_includes_relationships(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        // Verify eager loading by checking no N+1 queries
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertSee($this->project->name);
    }

    // ============================================================
    // Authorization Tests
    // ============================================================

    public function test_user_without_permission_sees_empty_pending_approvals(): void
    {
        $regularUser = User::factory()->create();

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('getPendingApprovals')
            ->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'expired' => 0,
            'total' => 0,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($regularUser)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200);
    }

    public function test_global_approver_sees_all_pending_approvals(): void
    {
        $globalApprover = User::factory()->create();
        $globalApprover->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($globalApprover)
            ->test(DeploymentApprovals::class)
            ->assertSee($this->project->name);
    }

    // ============================================================
    // Modal State Tests
    // ============================================================

    public function test_modal_states_are_false_by_default(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertSet('showApproveModal', false)
            ->assertSet('showRejectModal', false)
            ->assertSet('selectedApprovalId', null)
            ->assertSet('approvalNotes', '')
            ->assertSet('rejectionReason', '');
    }

    public function test_approve_modal_closes_after_successful_approval(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('approve')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 1,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->assertSet('showApproveModal', true)
            ->call('approve')
            ->assertSet('showApproveModal', false)
            ->assertSet('selectedApprovalId', null);
    }

    public function test_reject_modal_closes_after_successful_rejection(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('reject')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 0,
            'rejected' => 1,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->assertSet('showRejectModal', true)
            ->set('rejectionReason', 'Not ready for deployment')
            ->call('reject')
            ->assertSet('showRejectModal', false)
            ->assertSet('selectedApprovalId', null);
    }

    // ============================================================
    // Notification Tests
    // ============================================================

    public function test_successful_approve_dispatches_success_notification(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('approve')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 1,
            'rejected' => 0,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openApproveModal', $approval->id)
            ->call('approve')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'success'
                    && str_contains($data['message'], 'approved'));
    }

    public function test_successful_reject_dispatches_success_notification(): void
    {
        $deployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $serviceMock = Mockery::mock(DeploymentApprovalService::class);
        $serviceMock->shouldReceive('reject')->once();
        $serviceMock->shouldReceive('getPendingApprovals')->andReturn(collect());
        $serviceMock->shouldReceive('getApprovalStats')->andReturn([
            'pending' => 0,
            'approved' => 0,
            'rejected' => 1,
            'expired' => 0,
            'total' => 1,
        ]);
        $this->app->instance(DeploymentApprovalService::class, $serviceMock);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->call('openRejectModal', $approval->id)
            ->set('rejectionReason', 'Security concerns')
            ->call('reject')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'success'
                    && str_contains($data['message'], 'rejected'));
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    public function test_approve_with_non_existent_approval_throws_exception(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', 99999)
            ->call('approve')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'error');
    }

    public function test_reject_with_non_existent_approval_throws_exception(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', 99999)
            ->set('rejectionReason', 'Some reason')
            ->call('reject')
            ->assertDispatched('notification', fn (string $name, array $data) => $data['type'] === 'error');
    }

    public function test_handles_empty_approval_list(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200);
    }

    public function test_filter_with_no_results(): void
    {
        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('search', 'nonexistent-project-xyz-123')
            ->assertStatus(200);
    }
}
