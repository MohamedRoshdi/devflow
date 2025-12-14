<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Policies\DeploymentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ServerPolicy;
use Tests\TestCase;

/**
 * Comprehensive Policy Tests
 *
 * Tests all authorization policies in DevFlow Pro.
 * Note: Current implementation uses ownership-based authorization model where
 * users can only access their own resources (unless admin or team member).
 */
class PoliciesTest extends TestCase
{

    // ========================================
    // ServerPolicy Tests
    // ========================================

    /** @test */
    public function server_policy_allows_any_authenticated_user_to_view_any_servers(): void
    {
        $user = User::factory()->create();
        $policy = new ServerPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function server_policy_allows_user_to_view_own_server(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ServerPolicy;

        $this->assertTrue($policy->view($user, $server));
    }

    /** @test */
    public function server_policy_denies_user_to_view_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Users can only view their own servers (unless admin or team member)
        $this->assertFalse($policy->view($user, $server));
    }

    /** @test */
    public function server_policy_allows_any_authenticated_user_to_create_server(): void
    {
        $user = User::factory()->create();
        $policy = new ServerPolicy;

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function server_policy_allows_user_to_update_own_server(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ServerPolicy;

        $this->assertTrue($policy->update($user, $server));
    }

    /** @test */
    public function server_policy_denies_user_to_update_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Users can only update their own servers (unless admin or team member)
        $this->assertFalse($policy->update($user, $server));
    }

    /** @test */
    public function server_policy_allows_user_to_delete_own_server(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ServerPolicy;

        $this->assertTrue($policy->delete($user, $server));
    }

    /** @test */
    public function server_policy_denies_user_to_delete_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Users can only delete their own servers (unless admin)
        $this->assertFalse($policy->delete($user, $server));
    }

    /** @test */
    public function server_policy_works_with_different_server_statuses(): void
    {
        $user = User::factory()->create();
        $policy = new ServerPolicy;

        // Create servers owned by the user to test status doesn't affect authorization
        $onlineServer = Server::factory()->create(['user_id' => $user->id, 'status' => 'online']);
        $offlineServer = Server::factory()->create(['user_id' => $user->id, 'status' => 'offline']);
        $maintenanceServer = Server::factory()->create(['user_id' => $user->id, 'status' => 'maintenance']);

        $this->assertTrue($policy->view($user, $onlineServer));
        $this->assertTrue($policy->view($user, $offlineServer));
        $this->assertTrue($policy->view($user, $maintenanceServer));
        $this->assertTrue($policy->update($user, $onlineServer));
        $this->assertTrue($policy->delete($user, $offlineServer));
    }

    // ========================================
    // ProjectPolicy Tests
    // ========================================

    /** @test */
    public function project_policy_allows_any_authenticated_user_to_view_any_projects(): void
    {
        $user = User::factory()->create();
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function project_policy_allows_user_to_view_own_project(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->view($user, $project));
    }

    /** @test */
    public function project_policy_denies_user_to_view_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Users can only view their own projects (unless admin or team member)
        $this->assertFalse($policy->view($user, $project));
    }

    /** @test */
    public function project_policy_allows_any_authenticated_user_to_create_project(): void
    {
        $user = User::factory()->create();
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function project_policy_allows_user_to_update_own_project(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->update($user, $project));
    }

    /** @test */
    public function project_policy_denies_user_to_update_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Users can only update their own projects (unless admin or team member)
        $this->assertFalse($policy->update($user, $project));
    }

    /** @test */
    public function project_policy_allows_user_to_delete_own_project(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->delete($user, $project));
    }

    /** @test */
    public function project_policy_denies_user_to_delete_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Users can only delete their own projects (unless admin)
        $this->assertFalse($policy->delete($user, $project));
    }

    /** @test */
    public function project_policy_allows_user_to_deploy_own_project(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        $this->assertTrue($policy->deploy($user, $project));
    }

    /** @test */
    public function project_policy_denies_user_to_deploy_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Users can only deploy their own projects (unless admin or team member)
        $this->assertFalse($policy->deploy($user, $project));
    }

    /** @test */
    public function project_policy_works_with_different_project_statuses(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ProjectPolicy;

        // Create projects owned by user to test status doesn't affect authorization
        $runningProject = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'status' => 'running',
        ]);
        $stoppedProject = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'status' => 'stopped',
        ]);
        $buildingProject = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'status' => 'building',
        ]);

        $this->assertTrue($policy->view($user, $runningProject));
        $this->assertTrue($policy->view($user, $stoppedProject));
        $this->assertTrue($policy->view($user, $buildingProject));
        $this->assertTrue($policy->deploy($user, $runningProject));
        $this->assertTrue($policy->delete($user, $stoppedProject));
    }

    /** @test */
    public function project_policy_works_with_different_project_frameworks(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ProjectPolicy;

        // Create projects owned by user to test framework doesn't affect authorization
        $laravelProject = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);
        $nodeProject = Project::factory()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'framework' => 'nodejs',
        ]);

        $this->assertTrue($policy->view($user, $laravelProject));
        $this->assertTrue($policy->view($user, $nodeProject));
        $this->assertTrue($policy->deploy($user, $laravelProject));
        $this->assertTrue($policy->deploy($user, $nodeProject));
    }

    // ========================================
    // DeploymentPolicy Tests
    // ========================================

    /** @test */
    public function deployment_policy_allows_any_authenticated_user_to_view_any_deployments(): void
    {
        $user = User::factory()->create();
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function deployment_policy_allows_user_to_view_own_deployment(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->view($user, $deployment));
    }

    /** @test */
    public function deployment_policy_denies_user_to_view_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $project = Project::factory()->create(['user_id' => $otherUser->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Users can only view their own deployments (unless admin or team member)
        $this->assertFalse($policy->view($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_any_authenticated_user_to_create_deployment(): void
    {
        $user = User::factory()->create();
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function deployment_policy_allows_user_to_cancel_own_deployment(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->cancel($user, $deployment));
    }

    /** @test */
    public function deployment_policy_denies_user_to_cancel_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $project = Project::factory()->create(['user_id' => $otherUser->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Users can only cancel their own deployments (unless admin or team member)
        $this->assertFalse($policy->cancel($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_user_to_rollback_own_deployment(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->rollback($user, $deployment));
    }

    /** @test */
    public function deployment_policy_denies_user_to_rollback_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $project = Project::factory()->create(['user_id' => $otherUser->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Users can only rollback their own deployments (unless admin or team member)
        $this->assertFalse($policy->rollback($user, $deployment));
    }

    /** @test */
    public function deployment_policy_works_with_different_deployment_statuses(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $policy = new DeploymentPolicy;

        // Create deployments owned by the user to test status doesn't affect authorization
        $successDeployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'success',
        ]);
        $failedDeployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'failed',
        ]);
        $runningDeployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'running',
        ]);

        $this->assertTrue($policy->view($user, $successDeployment));
        $this->assertTrue($policy->view($user, $failedDeployment));
        $this->assertTrue($policy->view($user, $runningDeployment));
        $this->assertTrue($policy->cancel($user, $runningDeployment));
        $this->assertTrue($policy->rollback($user, $successDeployment));
    }

    /** @test */
    public function deployment_policy_works_with_deployments_on_different_branches(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $policy = new DeploymentPolicy;

        // Create deployments owned by the user to test branch doesn't affect authorization
        $mainDeployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
            'branch' => 'main',
        ]);
        $developDeployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
            'branch' => 'develop',
        ]);

        $this->assertTrue($policy->view($user, $mainDeployment));
        $this->assertTrue($policy->view($user, $developDeployment));
        $this->assertTrue($policy->rollback($user, $mainDeployment));
        $this->assertTrue($policy->rollback($user, $developDeployment));
    }

    // ========================================
    // Cross-Policy Integration Tests
    // ========================================

    /** @test */
    public function owner_can_manage_server_but_others_cannot(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $server = Server::factory()->create(['user_id' => $owner->id]);
        $policy = new ServerPolicy;

        // Owner can view, update, delete
        $this->assertTrue($policy->view($owner, $server));
        $this->assertTrue($policy->update($owner, $server));
        $this->assertTrue($policy->delete($owner, $server));

        // Other user cannot view, update, delete
        $this->assertFalse($policy->view($otherUser, $server));
        $this->assertFalse($policy->update($otherUser, $server));
        $this->assertFalse($policy->delete($otherUser, $server));
    }

    /** @test */
    public function owner_can_manage_project_but_others_cannot(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $server = Server::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Owner can view, deploy, update
        $this->assertTrue($policy->view($owner, $project));
        $this->assertTrue($policy->deploy($owner, $project));
        $this->assertTrue($policy->update($owner, $project));

        // Other user cannot view, deploy, update
        $this->assertFalse($policy->view($otherUser, $project));
        $this->assertFalse($policy->deploy($otherUser, $project));
        $this->assertFalse($policy->update($otherUser, $project));
    }

    /** @test */
    public function owner_can_manage_deployment_but_others_cannot(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $server = Server::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create(['user_id' => $owner->id, 'server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $owner->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Owner can view, cancel, rollback
        $this->assertTrue($policy->view($owner, $deployment));
        $this->assertTrue($policy->cancel($owner, $deployment));
        $this->assertTrue($policy->rollback($owner, $deployment));

        // Other user cannot view, cancel, rollback
        $this->assertFalse($policy->view($otherUser, $deployment));
        $this->assertFalse($policy->cancel($otherUser, $deployment));
        $this->assertFalse($policy->rollback($otherUser, $deployment));
    }

    /** @test */
    public function each_user_can_only_access_their_own_resources(): void
    {
        // Create multiple users with their own resources
        $users = User::factory()->count(3)->create();

        $serverPolicy = new ServerPolicy;
        $projectPolicy = new ProjectPolicy;
        $deploymentPolicy = new DeploymentPolicy;

        // Each user should have access to their own resources only
        foreach ($users as $user) {
            // Create resources owned by this user
            $server = Server::factory()->create(['user_id' => $user->id]);
            $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
            $deployment = Deployment::factory()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'server_id' => $server->id,
            ]);

            // All users can viewAny and create
            $this->assertTrue($serverPolicy->viewAny($user));
            $this->assertTrue($serverPolicy->create($user));
            $this->assertTrue($projectPolicy->viewAny($user));
            $this->assertTrue($projectPolicy->create($user));
            $this->assertTrue($deploymentPolicy->viewAny($user));
            $this->assertTrue($deploymentPolicy->create($user));

            // Owner can access their own resources
            $this->assertTrue($serverPolicy->view($user, $server));
            $this->assertTrue($serverPolicy->update($user, $server));
            $this->assertTrue($serverPolicy->delete($user, $server));

            $this->assertTrue($projectPolicy->view($user, $project));
            $this->assertTrue($projectPolicy->update($user, $project));
            $this->assertTrue($projectPolicy->delete($user, $project));
            $this->assertTrue($projectPolicy->deploy($user, $project));

            $this->assertTrue($deploymentPolicy->view($user, $deployment));
            $this->assertTrue($deploymentPolicy->cancel($user, $deployment));
            $this->assertTrue($deploymentPolicy->rollback($user, $deployment));
        }
    }

    /** @test */
    public function policies_maintain_authorization_with_soft_deleted_projects(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['user_id' => $user->id, 'server_id' => $server->id]);
        $policy = new ProjectPolicy;

        // Before soft delete - owner can access
        $this->assertTrue($policy->view($user, $project));

        // After soft delete - owner can still access
        $project->delete();
        $this->assertTrue($policy->view($user, $project));
        $this->assertTrue($policy->update($user, $project));
    }

    /** @test */
    public function policies_maintain_authorization_with_soft_deleted_servers(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $user->id]);
        $policy = new ServerPolicy;

        // Before soft delete - owner can access
        $this->assertTrue($policy->view($user, $server));

        // After soft delete - owner can still access
        $server->delete();
        $this->assertTrue($policy->view($user, $server));
        $this->assertTrue($policy->update($user, $server));
    }
}
