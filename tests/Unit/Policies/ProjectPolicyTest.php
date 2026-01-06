<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;
    private User $owner;
    private User $teamMember;
    private User $nonMember;
    private Project $project;
    private Server $server;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProjectPolicy();
        $this->owner = User::factory()->create();
        $this->teamMember = User::factory()->create();
        $this->nonMember = User::factory()->create();
        $this->server = Server::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->owner->id]);

        // Add team member
        $this->team->members()->create([
            'user_id' => $this->teamMember->id,
            'role' => 'member',
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->owner->id,
            'server_id' => $this->server->id,
            'team_id' => $this->team->id,
        ]);

        // Give permissions to users
        $this->owner->givePermissionTo([
            'view-projects',
            'create-projects',
            'edit-projects',
            'delete-projects',
            'deploy-projects',
        ]);

        $this->teamMember->givePermissionTo([
            'view-projects',
            'edit-projects',
            'deploy-projects',
        ]);

        $this->nonMember->givePermissionTo([
            'view-projects',
        ]);
    }

    // ==================== manageEnvironment TESTS ====================

    public function test_owner_can_manage_environment(): void
    {
        $this->assertTrue($this->policy->manageEnvironment($this->owner, $this->project));
    }

    public function test_team_member_with_edit_permission_can_manage_environment(): void
    {
        $this->assertTrue($this->policy->manageEnvironment($this->teamMember, $this->project));
    }

    public function test_non_member_cannot_manage_environment(): void
    {
        $this->assertFalse($this->policy->manageEnvironment($this->nonMember, $this->project));
    }

    public function test_user_without_edit_permission_cannot_manage_environment(): void
    {
        $userWithoutEditPermission = User::factory()->create();
        $userWithoutEditPermission->givePermissionTo('view-projects');

        $this->assertFalse($this->policy->manageEnvironment($userWithoutEditPermission, $this->project));
    }

    // ==================== manageServerEnvironment TESTS ====================

    public function test_owner_with_deploy_permission_can_manage_server_environment(): void
    {
        $this->assertTrue($this->policy->manageServerEnvironment($this->owner, $this->project));
    }

    public function test_team_member_with_deploy_permission_can_manage_server_environment(): void
    {
        $this->assertTrue($this->policy->manageServerEnvironment($this->teamMember, $this->project));
    }

    public function test_user_without_deploy_permission_cannot_manage_server_environment(): void
    {
        $userWithoutDeployPermission = User::factory()->create();
        $userWithoutDeployPermission->givePermissionTo(['view-projects', 'edit-projects']);

        $this->assertFalse($this->policy->manageServerEnvironment($userWithoutDeployPermission, $this->project));
    }

    public function test_non_member_cannot_manage_server_environment(): void
    {
        $this->nonMember->givePermissionTo('deploy-projects');

        $this->assertFalse($this->policy->manageServerEnvironment($this->nonMember, $this->project));
    }

    // ==================== manageSensitiveEnvironment TESTS ====================

    public function test_owner_with_delete_permission_can_manage_sensitive_environment(): void
    {
        $this->assertTrue($this->policy->manageSensitiveEnvironment($this->owner, $this->project));
    }

    public function test_user_without_delete_permission_cannot_manage_sensitive_environment(): void
    {
        // teamMember has deploy but not delete permission
        $this->assertFalse($this->policy->manageSensitiveEnvironment($this->teamMember, $this->project));
    }

    public function test_non_member_with_delete_permission_can_access_due_to_global_access(): void
    {
        // Users with delete permission have global access
        $this->nonMember->givePermissionTo('delete-projects');

        $this->assertTrue($this->policy->manageSensitiveEnvironment($this->nonMember, $this->project));
    }

    // ==================== hasOwnershipAccess TESTS ====================

    public function test_owner_has_ownership_access(): void
    {
        $this->assertTrue($this->policy->view($this->owner, $this->project));
    }

    public function test_team_member_has_ownership_access(): void
    {
        $this->assertTrue($this->policy->view($this->teamMember, $this->project));
    }

    public function test_non_member_does_not_have_ownership_access(): void
    {
        $this->assertFalse($this->policy->view($this->nonMember, $this->project));
    }

    public function test_user_with_delete_permission_has_global_access(): void
    {
        $adminUser = User::factory()->create();
        $adminUser->givePermissionTo(['view-projects', 'delete-projects']);

        $this->assertTrue($this->policy->view($adminUser, $this->project));
    }

    // ==================== EDGE CASES ====================

    public function test_project_without_team_only_allows_owner(): void
    {
        $projectWithoutTeam = Project::factory()->create([
            'user_id' => $this->owner->id,
            'server_id' => $this->server->id,
            'team_id' => null,
        ]);

        $this->assertTrue($this->policy->view($this->owner, $projectWithoutTeam));
        $this->assertFalse($this->policy->view($this->teamMember, $projectWithoutTeam));
    }
}
