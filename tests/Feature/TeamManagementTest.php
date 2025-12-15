<?php

declare(strict_types=1);

namespace Tests\Feature;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Teams\TeamGeneralSettings;
use App\Livewire\Teams\TeamInvitations;
use App\Livewire\Teams\TeamList;
use App\Livewire\Teams\TeamMemberManager;
use App\Livewire\Teams\TeamSettings;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->team = Team::factory()->create([
            'name' => 'Test Team',
            'owner_id' => $this->owner->id,
        ]);

        // Make owner a member of the team
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
        ]);
    }

    // ==================== Team Creation Tests ====================

    #[Test]
    public function user_can_create_team(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamList::class)
            ->set('name', 'New Team')
            ->set('description', 'A new team description')
            ->call('createTeam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teams', [
            'name' => 'New Team',
            'owner_id' => $this->owner->id,
        ]);
    }

    #[Test]
    public function team_creation_requires_name(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamList::class)
            ->set('name', '')
            ->set('description', 'Description without name')
            ->call('createTeam')
            ->assertHasErrors('name');
    }

    // ==================== Team Update Tests ====================

    #[Test]
    public function owner_can_update_team(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Updated Team Name')
            ->call('updateTeam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Updated Team Name',
        ]);
    }

    #[Test]
    public function non_owner_cannot_update_team(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        // Member can access settings but not update (only owner/admin can)
        Livewire::test(TeamGeneralSettings::class, ['team' => $this->team])
            ->set('name', 'Hacked Name')
            ->call('updateTeam')
            ->assertDispatched('notification');

        $this->team->refresh();
        $this->assertNotEquals('Hacked Name', $this->team->name);
    }

    // ==================== Team Deletion Tests ====================

    #[Test]
    public function owner_can_delete_team(): void
    {
        $this->actingAs($this->owner);
        $teamId = $this->team->id;

        Livewire::test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Test Team')
            ->call('deleteTeam');

        // Team model uses SoftDeletes, so check deleted_at is set
        $this->assertSoftDeleted('teams', [
            'id' => $teamId,
        ]);
    }

    #[Test]
    public function non_owner_cannot_delete_team(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'admin', // Even admin cannot delete
        ]);

        $this->actingAs($member);
        $teamId = $this->team->id;

        Livewire::test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Test Team')
            ->call('deleteTeam');

        // Team should still exist
        $this->assertDatabaseHas('teams', [
            'id' => $teamId,
        ]);
    }

    // ==================== Team Member Management Tests ====================

    #[Test]
    public function owner_can_invite_member(): void
    {
        Mail::fake();
        Notification::fake();

        $this->actingAs($this->owner);

        Livewire::test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'newmember@example.com')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $this->team->id,
            'email' => 'newmember@example.com',
        ]);
    }

    #[Test]
    public function invited_user_can_accept_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'invited@example.com',
            'role' => 'member',
            'token' => 'test-token-123',
        ]);

        $invitedUser = User::factory()->create([
            'email' => 'invited@example.com',
        ]);

        $this->actingAs($invitedUser);

        $response = $this->post('/invitations/' . $invitation->token . '/accept');

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 302
        );
    }

    #[Test]
    public function owner_can_remove_member(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($this->owner);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $member->id);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member->id,
        ]);
    }

    #[Test]
    public function member_cannot_remove_other_members(): void
    {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member1->id,
            'role' => 'member',
        ]);

        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member2->id,
            'role' => 'member',
        ]);

        $this->actingAs($member1);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $member2->id);

        // Member2 should still exist
        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member2->id,
        ]);
    }

    // ==================== Role Management Tests ====================

    #[Test]
    public function owner_can_change_member_role(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($this->owner);

        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('updateRole', $member->id, 'admin');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function member_cannot_leave_as_sole_owner(): void
    {
        // Owner tries to leave their own team without transferring ownership
        $this->actingAs($this->owner);

        // Owner should not be able to remove themselves
        Livewire::test(TeamMemberManager::class, ['team' => $this->team])
            ->call('removeMember', $this->owner->id);

        // Owner should still be a member
        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
        ]);
    }

    // ==================== Team Visibility Tests ====================

    #[Test]
    public function user_can_only_see_their_teams(): void
    {
        $otherUser = User::factory()->create();
        $otherTeam = Team::factory()->create([
            'name' => 'Other Team',
            'owner_id' => $otherUser->id,
        ]);

        $this->actingAs($this->owner);

        $response = $this->get('/teams');

        $response->assertOk()
            ->assertSee('Test Team')
            ->assertDontSee('Other Team');
    }

    #[Test]
    public function team_member_can_see_shared_team(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $response = $this->get('/teams/' . $this->team->id . '/settings');

        $response->assertOk();
    }
}
