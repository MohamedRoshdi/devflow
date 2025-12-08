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
 * Note: Current implementation uses shared authorization model where
 * all authenticated users can access all resources.
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
    public function server_policy_allows_user_to_view_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Shared authorization model - any authenticated user can view any server
        $this->assertTrue($policy->view($user, $server));
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
    public function server_policy_allows_user_to_update_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Shared authorization model - any authenticated user can update any server
        $this->assertTrue($policy->update($user, $server));
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
    public function server_policy_allows_user_to_delete_others_server(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ServerPolicy;

        // Shared authorization model - any authenticated user can delete any server
        $this->assertTrue($policy->delete($user, $server));
    }

    /** @test */
    public function server_policy_works_with_different_server_statuses(): void
    {
        $user = User::factory()->create();
        $policy = new ServerPolicy;

        $onlineServer = Server::factory()->create(['status' => 'online']);
        $offlineServer = Server::factory()->create(['status' => 'offline']);
        $maintenanceServer = Server::factory()->create(['status' => 'maintenance']);

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
    public function project_policy_allows_user_to_view_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Shared authorization model - any authenticated user can view any project
        $this->assertTrue($policy->view($user, $project));
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
    public function project_policy_allows_user_to_update_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Shared authorization model - any authenticated user can update any project
        $this->assertTrue($policy->update($user, $project));
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
    public function project_policy_allows_user_to_delete_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Shared authorization model - any authenticated user can delete any project
        $this->assertTrue($policy->delete($user, $project));
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
    public function project_policy_allows_user_to_deploy_others_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Shared authorization model - any authenticated user can deploy any project
        $this->assertTrue($policy->deploy($user, $project));
    }

    /** @test */
    public function project_policy_works_with_different_project_statuses(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $policy = new ProjectPolicy;

        $runningProject = Project::factory()->create([
            'server_id' => $server->id,
            'status' => 'running',
        ]);
        $stoppedProject = Project::factory()->create([
            'server_id' => $server->id,
            'status' => 'stopped',
        ]);
        $buildingProject = Project::factory()->create([
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
        $server = Server::factory()->create();
        $policy = new ProjectPolicy;

        $laravelProject = Project::factory()->create([
            'server_id' => $server->id,
            'framework' => 'laravel',
        ]);
        $nodeProject = Project::factory()->create([
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
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->view($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_user_to_view_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Shared authorization model - any authenticated user can view any deployment
        $this->assertTrue($policy->view($user, $deployment));
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
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->cancel($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_user_to_cancel_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Shared authorization model - any authenticated user can cancel any deployment
        $this->assertTrue($policy->cancel($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_user_to_rollback_own_deployment(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        $this->assertTrue($policy->rollback($user, $deployment));
    }

    /** @test */
    public function deployment_policy_allows_user_to_rollback_others_deployment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Shared authorization model - any authenticated user can rollback any deployment
        $this->assertTrue($policy->rollback($user, $deployment));
    }

    /** @test */
    public function deployment_policy_works_with_different_deployment_statuses(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $policy = new DeploymentPolicy;

        $successDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'success',
        ]);
        $failedDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'status' => 'failed',
        ]);
        $runningDeployment = Deployment::factory()->create([
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
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $policy = new DeploymentPolicy;

        $mainDeployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
            'branch' => 'main',
        ]);
        $developDeployment = Deployment::factory()->create([
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
    public function different_users_can_manage_same_server(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $server = Server::factory()->create(['user_id' => $user1->id]);
        $policy = new ServerPolicy;

        // Both users can view
        $this->assertTrue($policy->view($user1, $server));
        $this->assertTrue($policy->view($user2, $server));

        // Both users can update
        $this->assertTrue($policy->update($user1, $server));
        $this->assertTrue($policy->update($user2, $server));

        // Both users can delete
        $this->assertTrue($policy->delete($user1, $server));
        $this->assertTrue($policy->delete($user2, $server));
    }

    /** @test */
    public function different_users_can_manage_same_project(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $server = Server::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user1->id,
            'server_id' => $server->id,
        ]);
        $policy = new ProjectPolicy;

        // Both users can view
        $this->assertTrue($policy->view($user1, $project));
        $this->assertTrue($policy->view($user2, $project));

        // Both users can deploy
        $this->assertTrue($policy->deploy($user1, $project));
        $this->assertTrue($policy->deploy($user2, $project));

        // Both users can update
        $this->assertTrue($policy->update($user1, $project));
        $this->assertTrue($policy->update($user2, $project));
    }

    /** @test */
    public function different_users_can_manage_same_deployment(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'user_id' => $user1->id,
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);
        $policy = new DeploymentPolicy;

        // Both users can view
        $this->assertTrue($policy->view($user1, $deployment));
        $this->assertTrue($policy->view($user2, $deployment));

        // Both users can cancel
        $this->assertTrue($policy->cancel($user1, $deployment));
        $this->assertTrue($policy->cancel($user2, $deployment));

        // Both users can rollback
        $this->assertTrue($policy->rollback($user1, $deployment));
        $this->assertTrue($policy->rollback($user2, $deployment));
    }

    /** @test */
    public function policies_work_consistently_across_multiple_users(): void
    {
        // Create multiple users
        $users = User::factory()->count(3)->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'server_id' => $server->id,
        ]);

        $serverPolicy = new ServerPolicy;
        $projectPolicy = new ProjectPolicy;
        $deploymentPolicy = new DeploymentPolicy;

        // All users should have same access rights
        foreach ($users as $user) {
            $this->assertTrue($serverPolicy->viewAny($user));
            $this->assertTrue($serverPolicy->view($user, $server));
            $this->assertTrue($serverPolicy->create($user));
            $this->assertTrue($serverPolicy->update($user, $server));
            $this->assertTrue($serverPolicy->delete($user, $server));

            $this->assertTrue($projectPolicy->viewAny($user));
            $this->assertTrue($projectPolicy->view($user, $project));
            $this->assertTrue($projectPolicy->create($user));
            $this->assertTrue($projectPolicy->update($user, $project));
            $this->assertTrue($projectPolicy->delete($user, $project));
            $this->assertTrue($projectPolicy->deploy($user, $project));

            $this->assertTrue($deploymentPolicy->viewAny($user));
            $this->assertTrue($deploymentPolicy->view($user, $deployment));
            $this->assertTrue($deploymentPolicy->create($user));
            $this->assertTrue($deploymentPolicy->cancel($user, $deployment));
            $this->assertTrue($deploymentPolicy->rollback($user, $deployment));
        }
    }

    /** @test */
    public function policies_maintain_authorization_with_soft_deleted_projects(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $project = Project::factory()->create(['server_id' => $server->id]);
        $policy = new ProjectPolicy;

        // Before soft delete
        $this->assertTrue($policy->view($user, $project));

        // After soft delete
        $project->delete();
        $this->assertTrue($policy->view($user, $project));
        $this->assertTrue($policy->update($user, $project));
    }

    /** @test */
    public function policies_maintain_authorization_with_soft_deleted_servers(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create();
        $policy = new ServerPolicy;

        // Before soft delete
        $this->assertTrue($policy->view($user, $server));

        // After soft delete
        $server->delete();
        $this->assertTrue($policy->view($user, $server));
        $this->assertTrue($policy->update($user, $server));
    }
}
