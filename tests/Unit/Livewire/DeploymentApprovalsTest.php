<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Deployments\DeploymentApprovals;
use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DeploymentApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeploymentApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $approver;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->approver = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'requires_approval' => true,
        ]);

        // Create permissions
        Permission::create(['name' => 'approve_deployments']);
        Permission::create(['name' => 'approve_all_deployments']);
    }

    /** @test */
    public function component_renders_successfully_for_authenticated_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.deployments.deployment-approvals');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_component(): void
    {
        Livewire::test(DeploymentApprovals::class)
            ->assertUnauthorized();
    }

    /** @test */
    public function component_displays_pending_approvals_list(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) use ($approval) {
                return $approvals->contains('id', $approval->id);
            });
    }

    /** @test */
    public function component_displays_multiple_pending_approvals(): void
    {
        $deployment1 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
            'commit_message' => 'First deployment',
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
            'commit_message' => 'Second deployment',
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) {
                return $approvals->count() === 2;
            });
    }

    /** @test */
    public function approve_modal_opens_correctly(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
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

    /** @test */
    public function reject_modal_opens_correctly(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
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

    /** @test */
    public function deployment_can_be_approved_successfully(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', 'Looks good to deploy')
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], 'approved successfully');
            })
            ->assertSet('showApproveModal', false);

        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'approved_by' => $this->approver->id,
            'notes' => 'Looks good to deploy',
        ]);

        $freshApproval = $approval->fresh();
        $this->assertNotNull($freshApproval);
        $this->assertNotNull($freshApproval->responded_at);
    }

    /** @test */
    public function deployment_can_be_approved_without_notes(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', '')
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'approved_by' => $this->approver->id,
        ]);
    }

    /** @test */
    public function deployment_can_be_rejected_with_reason(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Tests are failing')
            ->call('reject')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success' &&
                       str_contains($data['message'], 'rejected');
            })
            ->assertSet('showRejectModal', false);

        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
            'approved_by' => $this->approver->id,
            'notes' => 'Tests are failing',
        ]);

        $freshApproval = $approval->fresh();
        $this->assertNotNull($freshApproval);
        $this->assertNotNull($freshApproval->responded_at);
    }

    /** @test */
    public function rejection_requires_reason(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', '')
            ->call('reject')
            ->assertHasErrors(['rejectionReason' => 'required']);
    }

    /** @test */
    public function approval_notes_are_optional_and_limited_to_1000_characters(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $longNotes = str_repeat('a', 1001);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', $longNotes)
            ->call('approve')
            ->assertHasErrors(['approvalNotes' => 'max']);
    }

    /** @test */
    public function rejection_reason_is_limited_to_1000_characters(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        $longReason = str_repeat('a', 1001);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', $longReason)
            ->call('reject')
            ->assertHasErrors(['rejectionReason' => 'max']);
    }

    /** @test */
    public function user_can_only_approve_deployments_for_their_projects(): void
    {
        // Create a user with limited approval permissions
        $limitedApprover = User::factory()->create();
        $limitedApprover->givePermissionTo('approve_deployments');

        // Create a project owned by another user
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $otherProject->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($limitedApprover)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', 'Approve')
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'error';
            });

        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    /** @test */
    public function user_with_approve_all_permission_can_approve_any_deployment(): void
    {
        $globalApprover = User::factory()->create();
        $globalApprover->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($globalApprover)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', 'Global approval')
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'approved_by' => $globalApprover->id,
        ]);
    }

    /** @test */
    public function approval_notification_is_dispatched_on_successful_approval(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success' &&
                       $data['message'] === 'Deployment approved successfully';
            });
    }

    /** @test */
    public function rejection_notification_is_dispatched_on_successful_rejection(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Not ready')
            ->call('reject')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'success' &&
                       $data['message'] === 'Deployment rejected';
            });
    }

    /** @test */
    public function status_updates_to_approved_after_approval(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->call('approve');

        $freshApproval = $approval->fresh();
        $this->assertNotNull($freshApproval);
        $this->assertEquals('approved', $freshApproval->status);
        $this->assertEquals($this->approver->id, $freshApproval->approved_by);
    }

    /** @test */
    public function status_updates_to_rejected_after_rejection(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Failed tests')
            ->call('reject');

        $freshApproval = $approval->fresh();
        $this->assertNotNull($freshApproval);
        $this->assertEquals('rejected', $freshApproval->status);
        $this->assertEquals($this->approver->id, $freshApproval->approved_by);
    }

    /** @test */
    public function empty_state_shows_when_no_pending_approvals(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) {
                return $approvals->count() === 0;
            });
    }

    /** @test */
    public function status_filter_works_correctly(): void
    {
        $deployment1 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $pendingApproval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        $approvedApproval = DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'pending')
            ->assertViewHas('approvals', function ($approvals) use ($pendingApproval, $approvedApproval) {
                return $approvals->contains('id', $pendingApproval->id) &&
                       !$approvals->contains('id', $approvedApproval->id);
            });

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'approved')
            ->assertViewHas('approvals', function ($approvals) use ($pendingApproval, $approvedApproval) {
                return !$approvals->contains('id', $pendingApproval->id) &&
                       $approvals->contains('id', $approvedApproval->id);
            });
    }

    /** @test */
    public function project_filter_works_correctly(): void
    {
        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);

        $deployment1 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project2->id,
            'server_id' => $this->server->id,
        ]);

        $approval1 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        $approval2 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('projectFilter', $this->project->id)
            ->assertViewHas('approvals', function ($approvals) use ($approval1, $approval2) {
                return $approvals->contains('id', $approval1->id) &&
                       !$approvals->contains('id', $approval2->id);
            });
    }

    /** @test */
    public function search_filters_approvals_by_project_name(): void
    {
        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Laravel Application',
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'React Application',
        ]);

        $deployment1 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project1->id,
            'server_id' => $this->server->id,
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $project2->id,
            'server_id' => $this->server->id,
        ]);

        $approval1 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        $approval2 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('search', 'Laravel')
            ->assertViewHas('approvals', function ($approvals) use ($approval1, $approval2) {
                return $approvals->contains('id', $approval1->id) &&
                       !$approvals->contains('id', $approval2->id);
            });
    }

    /** @test */
    public function search_filters_approvals_by_requester_name(): void
    {
        $requester1 = User::factory()->create(['name' => 'John Doe']);
        $requester2 = User::factory()->create(['name' => 'Jane Smith']);

        $deployment1 = Deployment::factory()->create([
            'user_id' => $requester1->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $requester2->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval1 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $requester1->id,
        ]);

        $approval2 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $requester2->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('search', 'John')
            ->assertViewHas('approvals', function ($approvals) use ($approval1, $approval2) {
                return $approvals->contains('id', $approval1->id) &&
                       !$approvals->contains('id', $approval2->id);
            });
    }

    /** @test */
    public function search_filters_approvals_by_branch_name(): void
    {
        $deployment1 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'branch' => 'main',
        ]);

        $deployment2 = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'branch' => 'develop',
        ]);

        $approval1 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment1->id,
            'requested_by' => $this->user->id,
        ]);

        $approval2 = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment2->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('search', 'develop')
            ->assertViewHas('approvals', function ($approvals) use ($approval1, $approval2) {
                return !$approvals->contains('id', $approval1->id) &&
                       $approvals->contains('id', $approval2->id);
            });
    }

    /** @test */
    public function changing_status_filter_resets_pagination(): void
    {
        DeploymentApproval::factory()->count(25)->pending()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('statusFilter', 'approved')
            ->assertSet('paginators.page', 1);
    }

    /** @test */
    public function changing_project_filter_resets_pagination(): void
    {
        DeploymentApproval::factory()->count(25)->pending()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('projectFilter', $this->project->id)
            ->assertSet('paginators.page', 1);
    }

    /** @test */
    public function changing_search_resets_pagination(): void
    {
        DeploymentApproval::factory()->count(25)->pending()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->set('search', 'test')
            ->assertSet('paginators.page', 1);
    }

    /** @test */
    public function component_listens_to_approval_requested_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->dispatch('approval-requested')
            ->assertNotSet('approvals')
            ->assertNotSet('pendingApprovals')
            ->assertNotSet('stats');
    }

    /** @test */
    public function approvals_are_eager_loaded_with_relationships(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) {
                $approval = $approvals->first();

                return $approval !== null &&
                       $approval->relationLoaded('deployment') &&
                       $approval->relationLoaded('requester') &&
                       $approval->deployment->relationLoaded('project') &&
                       $approval->deployment->relationLoaded('user');
            });
    }

    /** @test */
    public function approvals_are_ordered_by_latest_first(): void
    {
        $oldDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $newDeployment = Deployment::factory()->create([
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $oldApproval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $oldDeployment->id,
            'requested_by' => $this->user->id,
            'requested_at' => now()->subDays(5),
        ]);

        $newApproval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $newDeployment->id,
            'requested_by' => $this->user->id,
            'requested_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) use ($newApproval) {
                return $approvals->first() !== null &&
                       $approvals->first()->id === $newApproval->id;
            });
    }

    /** @test */
    public function pagination_works_correctly(): void
    {
        DeploymentApproval::factory()->count(25)->pending()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('approvals', function ($approvals) {
                return $approvals->count() === 20; // Default per page
            });
    }

    /** @test */
    public function user_with_approve_all_permission_sees_all_projects(): void
    {
        $globalApprover = User::factory()->create();
        $globalApprover->givePermissionTo('approve_all_deployments');

        $project1 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project 1',
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Project 2',
        ]);

        Livewire::actingAs($globalApprover)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->count() >= 2;
            });
    }

    /** @test */
    public function user_with_limited_permission_sees_only_their_projects(): void
    {
        $limitedApprover = User::factory()->create();
        $limitedApprover->givePermissionTo('approve_deployments');

        $userProject = Project::factory()->create([
            'user_id' => $limitedApprover->id,
            'server_id' => $this->server->id,
            'name' => 'User Project',
        ]);

        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Other Project',
        ]);

        Livewire::actingAs($limitedApprover)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('projects', function ($projects) use ($userProject, $otherProject) {
                return $projects->contains('id', $userProject->id) &&
                       !$projects->contains('id', $otherProject->id);
            });
    }

    /** @test */
    public function approval_clears_selected_data_after_successful_approval(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('approvalNotes', 'Test notes')
            ->call('approve')
            ->assertSet('selectedApprovalId', null)
            ->assertSet('approvalNotes', '');
    }

    /** @test */
    public function rejection_clears_selected_data_after_successful_rejection(): void
    {
        $this->approver->givePermissionTo('approve_all_deployments');

        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
            'status' => 'pending',
        ]);

        $approval = DeploymentApproval::factory()->pending()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Test reason')
            ->call('reject')
            ->assertSet('selectedApprovalId', null)
            ->assertSet('rejectionReason', '');
    }

    /** @test */
    public function error_notification_is_dispatched_on_approval_failure(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->approved()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->call('approve')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'error';
            });
    }

    /** @test */
    public function error_notification_is_dispatched_on_rejection_failure(): void
    {
        $deployment = Deployment::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'server_id' => $this->server->id,
        ]);

        $approval = DeploymentApproval::factory()->rejected()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->approver)
            ->test(DeploymentApprovals::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('rejectionReason', 'Test')
            ->call('reject')
            ->assertDispatched('notification', function ($event, $data) {
                return $data['type'] === 'error';
            });
    }

    /** @test */
    public function component_displays_approval_statistics(): void
    {
        DeploymentApproval::factory()->count(3)->pending()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        DeploymentApproval::factory()->count(2)->approved()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        DeploymentApproval::factory()->count(1)->rejected()->create([
            'deployment_id' => Deployment::factory()->create([
                'project_id' => $this->project->id,
                'server_id' => $this->server->id,
            ]),
            'requested_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(DeploymentApprovals::class)
            ->assertViewHas('stats', function ($stats) {
                return is_array($stats) &&
                       isset($stats['pending']) &&
                       isset($stats['approved']) &&
                       isset($stats['rejected']);
            });
    }
}
