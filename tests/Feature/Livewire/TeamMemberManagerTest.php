<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Teams\TeamMemberManager;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class TeamMemberManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $owner;

    private User $admin;

    private User $member;

    private User $viewer;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->member = User::factory()->create();
        $this->viewer = User::factory()->create();

        $this->team = Team::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'Test Team',
        ]);

        // Add owner as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Add admin as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Add member as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Add viewer as team member
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->viewer->id,
            'role' => 'viewer',
            'joined_at' => now(),
        ]);
    }

    // ===== COMPONENT RENDERING =====

    public function test_component_renders_for_owner(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->assertStatus(200)
            ->assertViewIs('livewire.teams.team-member-manager');
    }

    public function test_component_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    public function test_component_renders_for_member(): void
    {
        $this->actingAs($this->member);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    public function test_component_renders_for_viewer(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    // ===== MEMBERS DISPLAY =====

    public function test_displays_all_team_members(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $this->assertCount(4, $members);
    }

    public function test_members_contain_expected_data_structure(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $firstMember = $members->first();

        $this->assertArrayHasKey('id', $firstMember);
        $this->assertArrayHasKey('user_id', $firstMember);
        $this->assertArrayHasKey('name', $firstMember);
        $this->assertArrayHasKey('email', $firstMember);
        $this->assertArrayHasKey('avatar', $firstMember);
        $this->assertArrayHasKey('role', $firstMember);
        $this->assertArrayHasKey('joined_at', $firstMember);
        $this->assertArrayHasKey('is_owner', $firstMember);
        $this->assertArrayHasKey('can_edit', $firstMember);
    }

    public function test_members_shows_correct_owner_flag(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $ownerMember = $members->firstWhere('user_id', $this->owner->id);
        $adminMember = $members->firstWhere('user_id', $this->admin->id);

        $this->assertTrue($ownerMember['is_owner']);
        $this->assertFalse($adminMember['is_owner']);
    }

    public function test_can_edit_is_false_for_owner_member(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $ownerMember = $members->firstWhere('user_id', $this->owner->id);

        $this->assertFalse($ownerMember['can_edit']);
    }

    public function test_can_edit_is_true_for_non_owner_when_user_is_owner(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $adminMember = $members->firstWhere('user_id', $this->admin->id);
        $normalMember = $members->firstWhere('user_id', $this->member->id);

        $this->assertTrue($adminMember['can_edit']);
        $this->assertTrue($normalMember['can_edit']);
    }

    public function test_can_edit_is_true_for_non_owner_when_user_is_admin(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $normalMember = $members->firstWhere('user_id', $this->member->id);

        $this->assertTrue($normalMember['can_edit']);
    }

    public function test_can_edit_is_false_for_all_when_user_is_member(): void
    {
        $this->actingAs($this->member);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');

        foreach ($members as $member) {
            $this->assertFalse($member['can_edit']);
        }
    }

    public function test_can_edit_is_false_for_all_when_user_is_viewer(): void
    {
        $this->actingAs($this->viewer);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');

        foreach ($members as $member) {
            $this->assertFalse($member['can_edit']);
        }
    }

    public function test_members_avatar_uses_ui_avatars_when_no_avatar(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $firstMember = $members->first();

        $this->assertStringContains('ui-avatars.com', $firstMember['avatar']);
    }

    // ===== REMOVE MEMBER =====

    public function test_owner_can_remove_member(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeMember')
                ->once()
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' && str_contains($data['message'], 'removed'));
    }

    public function test_admin_can_remove_member(): void
    {
        $this->actingAs($this->admin);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeMember')
                ->once()
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->viewer->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');
    }

    public function test_member_cannot_remove_other_members(): void
    {
        $this->actingAs($this->member);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->viewer->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'permission'));
    }

    public function test_viewer_cannot_remove_members(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_remove_member_handles_service_exception(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeMember')
                ->once()
                ->andThrow(new \InvalidArgumentException('Cannot remove the team owner.'));
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->owner->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'Cannot remove the team owner'));
    }

    public function test_remove_member_refreshes_members_list(): void
    {
        $this->actingAs($this->owner);

        $newMember = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $newMember->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeMember')
                ->once()
                ->andReturnUsing(function ($team, $user): void {
                    TeamMember::where('team_id', $team->id)
                        ->where('user_id', $user->id)
                        ->delete();
                });
        });

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $membersBefore = $component->viewData('members');
        $this->assertCount(5, $membersBefore);

        $component->call('removeMember', $newMember->id);

        // Re-get the component to check refreshed members
        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);
        $membersAfter = $component->viewData('members');
        $this->assertCount(4, $membersAfter);
    }

    // ===== UPDATE ROLE =====

    public function test_owner_can_update_member_role(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->with($this->team, \Mockery::type(User::class), 'admin')
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success' && str_contains($data['message'], 'Role updated'));
    }

    public function test_admin_can_update_member_role(): void
    {
        $this->actingAs($this->admin);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->viewer->id, 'member')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');
    }

    public function test_member_cannot_update_roles(): void
    {
        $this->actingAs($this->member);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->viewer->id, 'admin')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'permission'));
    }

    public function test_viewer_cannot_update_roles(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'viewer')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_update_role_handles_service_exception(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->andThrow(new \InvalidArgumentException('Cannot change the role of the team owner.'));
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->owner->id, 'admin')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'Cannot change the role'));
    }

    public function test_update_role_handles_invalid_role(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->andThrow(new \InvalidArgumentException('Invalid role.'));
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'invalid_role')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error' && str_contains($data['message'], 'Invalid role'));
    }

    public function test_update_role_refreshes_members_list(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->andReturnUsing(function ($team, $user, $role): void {
                    TeamMember::where('team_id', $team->id)
                        ->where('user_id', $user->id)
                        ->update(['role' => $role]);
                });
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'success');

        // Verify role was updated in database
        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'admin',
        ]);
    }

    // ===== AUTHORIZATION EDGE CASES =====

    public function test_unauthenticated_user_cannot_access(): void
    {
        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_non_team_member_cannot_manage_members(): void
    {
        $outsider = User::factory()->create();
        $this->actingAs($outsider);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    public function test_non_team_member_cannot_update_roles(): void
    {
        $outsider = User::factory()->create();
        $this->actingAs($outsider);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', fn (array $data): bool => $data['type'] === 'error');
    }

    // ===== MEMBER DATA DISPLAY =====

    public function test_displays_member_name_correctly(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $ownerMember = $members->firstWhere('user_id', $this->owner->id);

        $this->assertEquals($this->owner->name, $ownerMember['name']);
    }

    public function test_displays_member_email_correctly(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $adminMember = $members->firstWhere('user_id', $this->admin->id);

        $this->assertEquals($this->admin->email, $adminMember['email']);
    }

    public function test_displays_member_role_correctly(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');

        $ownerMember = $members->firstWhere('user_id', $this->owner->id);
        $adminMember = $members->firstWhere('user_id', $this->admin->id);
        $normalMember = $members->firstWhere('user_id', $this->member->id);
        $viewerMember = $members->firstWhere('user_id', $this->viewer->id);

        $this->assertEquals('owner', $ownerMember['role']);
        $this->assertEquals('admin', $adminMember['role']);
        $this->assertEquals('member', $normalMember['role']);
        $this->assertEquals('viewer', $viewerMember['role']);
    }

    public function test_displays_joined_at_formatted(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $ownerMember = $members->firstWhere('user_id', $this->owner->id);

        // Should be formatted as "M d, Y"
        $this->assertNotNull($ownerMember['joined_at']);
        $this->assertMatchesRegularExpression('/[A-Z][a-z]{2} \d{2}, \d{4}/', $ownerMember['joined_at']);
    }

    public function test_handles_null_joined_at(): void
    {
        $newMember = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $newMember->id,
            'role' => 'member',
            'joined_at' => null,
        ]);

        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');
        $newMemberData = $members->firstWhere('user_id', $newMember->id);

        $this->assertNull($newMemberData['joined_at']);
    }

    // ===== EMPTY STATE =====

    public function test_handles_team_with_only_owner(): void
    {
        $newOwner = User::factory()->create();
        $newTeam = Team::factory()->create([
            'owner_id' => $newOwner->id,
            'name' => 'Solo Team',
        ]);

        TeamMember::factory()->create([
            'team_id' => $newTeam->id,
            'user_id' => $newOwner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($newOwner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $newTeam]);

        $members = $component->viewData('members');
        $this->assertCount(1, $members);
        $this->assertTrue($members->first()['is_owner']);
    }

    // ===== COMPUTED PROPERTY CACHING =====

    public function test_members_computed_property_returns_collection(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(TeamMemberManager::class, ['team' => $this->team]);

        $members = $component->viewData('members');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $members);
    }

    // ===== NOTIFICATION MESSAGES =====

    public function test_remove_member_success_notification(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeMember')
                ->once()
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', function (array $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Member removed successfully');
            });
    }

    public function test_update_role_success_notification(): void
    {
        $this->actingAs($this->owner);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateRole')
                ->once()
                ->andReturnNull();
        });

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', function (array $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Role updated successfully');
            });
    }

    public function test_permission_denied_notification(): void
    {
        $this->actingAs($this->viewer);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', function (array $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'permission');
            });
    }

    /**
     * Helper method for string contains assertion.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
