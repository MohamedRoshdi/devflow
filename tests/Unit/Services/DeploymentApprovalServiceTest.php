<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\Project;
use App\Models\User;
use App\Notifications\DeploymentApprovalRequested;
use App\Services\AuditService;
use App\Services\DeploymentApprovalService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeploymentApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeploymentApprovalService $service;

    /** @var AuditService&MockInterface */
    protected $auditService;

    /** @var NotificationService&MockInterface */
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->auditService = Mockery::mock(AuditService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        // Create service with mocked dependencies
        $this->service = new DeploymentApprovalService(
            $this->auditService,
            $this->notificationService
        );

        // Fake notifications
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_false_when_project_does_not_require_approval(): void
    {
        // Arrange
        $project = Project::factory()->create(['requires_approval' => false]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertFalse($requiresApproval);
    }

    /** @test */
    public function it_returns_true_when_project_requires_approval(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'approval_settings' => [],
        ]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertTrue($requiresApproval);
    }

    /** @test */
    public function it_checks_environment_specific_approval_requirements(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'environment' => 'production',
            'approval_settings' => [
                'environments' => ['production', 'staging'],
            ],
        ]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertTrue($requiresApproval);
    }

    /** @test */
    public function it_skips_approval_for_non_matching_environments(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'environment' => 'development',
            'approval_settings' => [
                'environments' => ['production', 'staging'],
            ],
        ]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertFalse($requiresApproval);
    }

    /** @test */
    public function it_checks_branch_specific_approval_requirements(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'approval_settings' => [
                'branches' => ['main', 'master', 'production'],
            ],
        ]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertTrue($requiresApproval);
    }

    /** @test */
    public function it_skips_approval_for_non_matching_branches(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'approval_settings' => [
                'branches' => ['main', 'master'],
            ],
        ]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'feature/test',
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertFalse($requiresApproval);
    }

    /** @test */
    public function it_creates_approval_request_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->auditService->shouldReceive('log')
            ->once()
            ->with('deployment.approval_requested', $deployment, null, Mockery::type('array'));

        // Act
        $approval = $this->service->requestApproval($deployment, $user);

        // Assert
        $this->assertInstanceOf(DeploymentApproval::class, $approval);
        $this->assertEquals($deployment->id, $approval->deployment_id);
        $this->assertEquals($user->id, $approval->requested_by);
        $this->assertEquals('pending', $approval->status);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $approval->requested_at);
    }

    /** @test */
    public function it_updates_deployment_status_when_requesting_approval(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $this->auditService->shouldReceive('log')->once();

        // Act
        $this->service->requestApproval($deployment, $user);

        // Assert
        $freshDeployment = $deployment->fresh();
        $this->assertNotNull($freshDeployment);
        $this->assertEquals('pending_approval', $freshDeployment->status);
    }

    /** @test */
    public function it_logs_approval_request_in_audit(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->auditService->shouldReceive('log')
            ->once()
            ->with(
                'deployment.approval_requested',
                $deployment,
                null,
                Mockery::on(function ($data) use ($user) {
                    return isset($data['approval_id']) &&
                           $data['requested_by'] === $user->name;
                })
            );

        // Act
        $this->service->requestApproval($deployment, $user);

        // Assert - Expectations verified by Mockery (mock expectation is the assertion)
    }

    /** @test */
    public function it_approves_deployment_successfully(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
            'status' => 'pending_approval',
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        // Give approver permission
        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver, 'Looks good!');

        // Assert
        $approval->refresh();
        $this->assertEquals('approved', $approval->status);
        $this->assertEquals($approver->id, $approval->approved_by);
        $this->assertEquals('Looks good!', $approval->notes);
        $this->assertNotNull($approval->responded_at);
    }

    /** @test */
    public function it_updates_deployment_status_to_pending_when_approved(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
            'status' => 'pending_approval',
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver);

        // Assert
        $freshDeployment = $deployment->fresh();
        $this->assertNotNull($freshDeployment);
        $this->assertEquals('pending', $freshDeployment->status);
    }

    /** @test */
    public function it_prevents_approving_own_deployment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);

        $user->givePermissionTo('approve_all_deployments');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to approve this deployment');

        $this->service->approve($approval, $user);
    }

    /** @test */
    public function it_prevents_approving_without_permission(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to approve this deployment');

        $this->service->approve($approval, $approver);
    }

    /** @test */
    public function it_prevents_approving_already_processed_approval(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'approved',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This approval has already been processed');

        $this->service->approve($approval, $approver);
    }

    /** @test */
    public function it_rejects_deployment_successfully(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
            'status' => 'pending_approval',
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->reject($approval, $rejector, 'Security concerns');

        // Assert
        $approval->refresh();
        $this->assertEquals('rejected', $approval->status);
        $this->assertEquals($rejector->id, $approval->approved_by);
        $this->assertEquals('Security concerns', $approval->notes);
        $this->assertNotNull($approval->responded_at);
    }

    /** @test */
    public function it_updates_deployment_status_to_failed_when_rejected(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
            'status' => 'pending_approval',
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->reject($approval, $rejector, 'Not ready');

        // Assert
        $deployment->refresh();
        $this->assertEquals('failed', $deployment->status);
        $this->assertNotNull($deployment->error_message);
        $this->assertStringContainsString('Deployment rejected: Not ready', $deployment->error_message);
    }

    /** @test */
    public function it_prevents_rejecting_without_permission(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to reject this deployment');

        $this->service->reject($approval, $rejector, 'Rejected');
    }

    /** @test */
    public function it_prevents_rejecting_already_processed_approval(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'rejected',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This approval has already been processed');

        $this->service->reject($approval, $rejector, 'Rejected again');
    }

    /** @test */
    public function it_gets_pending_approvals_for_global_approver(): void
    {
        // Arrange
        $approver = User::factory()->create();
        $approver->givePermissionTo('approve_all_deployments');

        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment1 = Deployment::factory()->create(['project_id' => $project->id]);
        $deployment2 = Deployment::factory()->create(['project_id' => $project->id]);

        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment1->id,
            'status' => 'pending',
        ]);
        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment2->id,
            'status' => 'pending',
        ]);
        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment2->id,
            'status' => 'approved',
        ]);

        // Act
        $pendingApprovals = $this->service->getPendingApprovals($approver);

        // Assert
        $this->assertCount(2, $pendingApprovals);
        $this->assertTrue($pendingApprovals->every(fn ($a) => $a->status === 'pending'));
    }

    /** @test */
    public function it_gets_pending_approvals_for_project_approver(): void
    {
        // Arrange
        $approver = User::factory()->create();
        $approver->givePermissionTo('approve_deployments');

        $project1 = Project::factory()->create(['requires_approval' => true]);
        $project2 = Project::factory()->create(['requires_approval' => true]);

        // Associate approver with project1
        $approver->projects()->attach($project1->id);

        $deployment1 = Deployment::factory()->create(['project_id' => $project1->id]);
        $deployment2 = Deployment::factory()->create(['project_id' => $project2->id]);

        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment1->id,
            'status' => 'pending',
        ]);
        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment2->id,
            'status' => 'pending',
        ]);

        // Act
        $pendingApprovals = $this->service->getPendingApprovals($approver);

        // Assert
        $this->assertCount(1, $pendingApprovals);
        $firstApproval = $pendingApprovals->first();
        $this->assertNotNull($firstApproval);
        $this->assertEquals($deployment1->id, $firstApproval->deployment_id);
    }

    /** @test */
    public function it_returns_empty_collection_for_user_without_approval_permission(): void
    {
        // Arrange
        $user = User::factory()->create();

        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);

        // Act
        $pendingApprovals = $this->service->getPendingApprovals($user);

        // Assert
        $this->assertCount(0, $pendingApprovals);
    }

    /** @test */
    public function it_gets_approval_statistics_for_all_projects(): void
    {
        // Arrange
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        DeploymentApproval::factory()->count(3)->create([
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);
        DeploymentApproval::factory()->count(5)->create([
            'deployment_id' => $deployment->id,
            'status' => 'approved',
        ]);
        DeploymentApproval::factory()->count(2)->create([
            'deployment_id' => $deployment->id,
            'status' => 'rejected',
        ]);

        // Act
        $stats = $this->service->getApprovalStats();

        // Assert
        $this->assertEquals(3, $stats['pending']);
        $this->assertEquals(5, $stats['approved']);
        $this->assertEquals(2, $stats['rejected']);
        $this->assertEquals(10, $stats['total']);
    }

    /** @test */
    public function it_gets_approval_statistics_for_user_projects(): void
    {
        // Arrange
        $user = User::factory()->create();

        $project1 = Project::factory()->create(['requires_approval' => true]);
        $project2 = Project::factory()->create(['requires_approval' => true]);

        $user->projects()->attach($project1->id);

        $deployment1 = Deployment::factory()->create(['project_id' => $project1->id]);
        $deployment2 = Deployment::factory()->create(['project_id' => $project2->id]);

        DeploymentApproval::factory()->count(2)->create([
            'deployment_id' => $deployment1->id,
            'status' => 'pending',
        ]);
        DeploymentApproval::factory()->count(3)->create([
            'deployment_id' => $deployment2->id,
            'status' => 'pending',
        ]);

        // Act
        $stats = $this->service->getApprovalStats($user);

        // Assert
        $this->assertEquals(2, $stats['pending']);
    }

    /** @test */
    public function it_sends_notifications_when_requesting_approval(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver1 = User::factory()->create();
        $approver2 = User::factory()->create();

        $approver1->givePermissionTo('approve_all_deployments');
        $approver2->givePermissionTo('approve_deployments');

        $project = Project::factory()->create(['requires_approval' => true]);
        $approver2->projects()->attach($project->id);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->auditService->shouldReceive('log')->once();

        // Act
        $approval = $this->service->requestApproval($deployment, $requester);

        // Assert
        Notification::assertSentTo(
            [$approver1, $approver2],
            DeploymentApprovalRequested::class,
            function ($notification, $channels, $notifiable) use ($approval) {
                return $notification->approval->id === $approval->id;
            }
        );

        Notification::assertNotSentTo($requester, DeploymentApprovalRequested::class);
    }

    /** @test */
    public function it_sends_notification_when_deployment_is_approved(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')
            ->once()
            ->with($deployment, 'deployment.approved');

        // Act
        $this->service->approve($approval, $approver);

        // Assert - Expectations verified by Mockery (mock expectation is the assertion)
    }

    /** @test */
    public function it_sends_notification_when_deployment_is_rejected(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')
            ->once()
            ->with($deployment, 'deployment.rejected');

        // Act
        $this->service->reject($approval, $rejector, 'Failed tests');

        // Assert - Expectations verified by Mockery (mock expectation is the assertion)
    }

    /** @test */
    public function it_allows_user_with_project_permission_to_approve(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);

        $approver->givePermissionTo('approve_deployments');
        $approver->projects()->attach($project->id);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver);

        // Assert
        $approval->refresh();
        $this->assertEquals('approved', $approval->status);
    }

    /** @test */
    public function it_handles_approval_with_notes(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver, 'All checks passed successfully');

        // Assert
        $approval->refresh();
        $this->assertEquals('All checks passed successfully', $approval->notes);
    }

    /** @test */
    public function it_handles_approval_without_notes(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver);

        // Assert
        $approval->refresh();
        $this->assertNull($approval->notes);
    }

    /** @test */
    public function it_uses_database_transaction_for_approval_request(): void
    {
        // Arrange
        $user = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $this->auditService->shouldReceive('log')->once();

        DB::beginTransaction();

        // Act
        $approval = $this->service->requestApproval($deployment, $user);

        // Assert
        $this->assertDatabaseHas('deployment_approvals', [
            'deployment_id' => $deployment->id,
            'status' => 'pending',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_uses_database_transaction_for_approval(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        DB::beginTransaction();

        // Act
        $this->service->approve($approval, $approver);

        // Assert
        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_uses_database_transaction_for_rejection(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        DB::beginTransaction();

        // Act
        $this->service->reject($approval, $rejector, 'Test rejection');

        // Assert
        $this->assertDatabaseHas('deployment_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_records_approval_timestamp(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
            'responded_at' => null,
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver);

        // Assert
        $approval->refresh();
        $this->assertNotNull($approval->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $approval->responded_at);
    }

    /** @test */
    public function it_records_rejection_timestamp(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create();
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
            'responded_at' => null,
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->reject($approval, $rejector, 'Failed');

        // Assert
        $approval->refresh();
        $this->assertNotNull($approval->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $approval->responded_at);
    }

    /** @test */
    public function it_combines_environment_and_branch_approval_rules(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'environment' => 'production',
            'approval_settings' => [
                'environments' => ['production', 'staging'],
                'branches' => ['main', 'master'],
            ],
        ]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'main',
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertTrue($requiresApproval);
    }

    /** @test */
    public function it_skips_approval_when_branch_doesnt_match_combined_rules(): void
    {
        // Arrange
        $project = Project::factory()->create([
            'requires_approval' => true,
            'environment' => 'production',
            'approval_settings' => [
                'environments' => ['production'],
                'branches' => ['main', 'master'],
            ],
        ]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'branch' => 'develop',
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertFalse($requiresApproval);
    }

    /** @test */
    public function it_handles_null_project_gracefully(): void
    {
        // Arrange
        $deployment = Deployment::factory()->create(['project_id' => null]);

        // Act
        $requiresApproval = $this->service->requiresApproval($deployment);

        // Assert
        $this->assertFalse($requiresApproval);
    }

    /** @test */
    public function it_logs_approval_action_in_audit(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create(['name' => 'Jane Approver']);
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $approver->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')
            ->once()
            ->with(
                'deployment.approved',
                $deployment,
                ['status' => 'pending_approval'],
                Mockery::on(function ($data) {
                    return $data['status'] === 'approved' &&
                           $data['approved_by'] === 'Jane Approver';
                })
            );

        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->approve($approval, $approver);

        // Assert - Expectations verified by Mockery (mock expectation is the assertion)
    }

    /** @test */
    public function it_logs_rejection_action_in_audit(): void
    {
        // Arrange
        $requester = User::factory()->create();
        $rejector = User::factory()->create(['name' => 'John Rejector']);
        $project = Project::factory()->create(['requires_approval' => true]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $requester->id,
        ]);

        $approval = DeploymentApproval::factory()->create([
            'deployment_id' => $deployment->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $rejector->givePermissionTo('approve_all_deployments');

        $this->auditService->shouldReceive('log')
            ->once()
            ->with(
                'deployment.rejected',
                $deployment,
                ['status' => 'pending_approval'],
                Mockery::on(function ($data) {
                    return $data['status'] === 'rejected' &&
                           $data['rejected_by'] === 'John Rejector';
                })
            );

        $this->notificationService->shouldReceive('notifyDeploymentEvent')->once();

        // Act
        $this->service->reject($approval, $rejector, 'Security issue');

        // Assert - Expectations verified by Mockery (mock expectation is the assertion)
    }
}
