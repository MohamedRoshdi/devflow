<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Mail\TeamInvitation as TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{

    protected TeamService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TeamService;

        // Fake mail for invitation testing
        Mail::fake();
    }

    #[Test]
    public function it_creates_team_with_owner(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Test Team',
            'description' => 'A test team',
        ]);

        // Assert
        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Test Team', $team->name);
        $this->assertEquals('A test team', $team->description);
        $this->assertEquals($user->id, $team->owner_id);
        $this->assertFalse($team->is_personal);
    }

    #[Test]
    public function it_creates_team_with_custom_slug(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Test Team',
            'slug' => 'custom-slug',
        ]);

        // Assert
        $this->assertEquals('custom-slug', $team->slug);
    }

    #[Test]
    public function it_creates_personal_team(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Personal Team',
            'is_personal' => true,
        ]);

        // Assert
        $this->assertTrue($team->is_personal);
    }

    #[Test]
    public function it_adds_owner_as_team_member(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Test Team',
        ]);

        // Assert
        $this->assertTrue($team->hasMember($user));
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($member);
        $this->assertEquals('owner', $member->role);
        $this->assertNotNull($member->joined_at);
    }

    #[Test]
    public function it_sets_team_as_current_when_user_has_no_current_team(): void
    {
        // Arrange
        $user = User::factory()->create(['current_team_id' => null]);

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Test Team',
        ]);

        // Assert
        $user->refresh();
        $this->assertEquals($team->id, $user->current_team_id);
    }

    #[Test]
    public function it_does_not_override_current_team_if_already_set(): void
    {
        // Arrange
        $existingTeam = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $existingTeam->id]);

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Test Team',
        ]);

        // Assert
        $user->refresh();
        $this->assertEquals($existingTeam->id, $user->current_team_id);
    }

    #[Test]
    public function it_invites_new_member_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Act
        $invitation = $this->service->inviteMember(
            $team,
            'newuser@example.com',
            'member',
            $owner
        );

        // Assert
        $this->assertInstanceOf(TeamInvitation::class, $invitation);
        $this->assertEquals($team->id, $invitation->team_id);
        $this->assertEquals('newuser@example.com', $invitation->email);
        $this->assertEquals('member', $invitation->role);
        $this->assertEquals($owner->id, $invitation->invited_by);
        $this->assertNotEmpty($invitation->token);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    #[Test]
    public function it_sends_invitation_email(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Act
        $invitation = $this->service->inviteMember(
            $team,
            'newuser@example.com',
            'member',
            $owner
        );

        // Assert
        Mail::assertSent(TeamInvitationMail::class, function ($mail) use ($invitation) {
            return $mail->hasTo('newuser@example.com') &&
                   $mail->invitation->id === $invitation->id;
        });
    }

    #[Test]
    public function it_prevents_inviting_existing_team_member(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $existingMember = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $existingMember->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User is already a member of this team.');

        $this->service->inviteMember($team, 'member@example.com', 'admin', $owner);
    }

    #[Test]
    public function it_prevents_duplicate_pending_invitations(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'pending@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An invitation has already been sent to this email.');

        $this->service->inviteMember($team, 'pending@example.com', 'admin', $owner);
    }

    #[Test]
    public function it_allows_reinviting_if_previous_invitation_expired(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'expired@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->subDays(1),
        ]);

        // Act
        $invitation = $this->service->inviteMember($team, 'expired@example.com', 'admin', $owner);

        // Assert
        $this->assertInstanceOf(TeamInvitation::class, $invitation);
        $this->assertEquals('admin', $invitation->role);
    }

    #[Test]
    public function it_allows_reinviting_if_previous_invitation_accepted(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'accepted@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        // Act
        $invitation = $this->service->inviteMember($team, 'accepted@example.com', 'admin', $owner);

        // Assert
        $this->assertInstanceOf(TeamInvitation::class, $invitation);
    }

    #[Test]
    public function it_accepts_invitation_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $newMember = User::factory()->create(['email' => 'newmember@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'admin',
            'invited_by' => $owner->id,
            'token' => 'test-token',
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        $resultTeam = $this->service->acceptInvitation('test-token');

        // Assert
        $this->assertEquals($team->id, $resultTeam->id);

        // Verify member was added
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $newMember->id)
            ->first();

        $this->assertNotNull($member);
        $this->assertEquals('admin', $member->role);
        $this->assertEquals($owner->id, $member->invited_by);
        $this->assertNotNull($member->joined_at);

        // Verify invitation was marked as accepted
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    #[Test]
    public function it_sets_accepted_team_as_current_if_user_has_none(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $newMember = User::factory()->create([
            'email' => 'newmember@example.com',
            'current_team_id' => null,
        ]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'token' => 'test-token',
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        $this->service->acceptInvitation('test-token');

        // Assert
        $newMember->refresh();
        $this->assertEquals($team->id, $newMember->current_team_id);
    }

    #[Test]
    public function it_does_not_override_current_team_when_accepting_invitation(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $existingTeam = Team::factory()->create();
        $newMember = User::factory()->create([
            'email' => 'newmember@example.com',
            'current_team_id' => $existingTeam->id,
        ]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'token' => 'test-token',
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        $this->service->acceptInvitation('test-token');

        // Assert
        $newMember->refresh();
        $this->assertEquals($existingTeam->id, $newMember->current_team_id);
    }

    #[Test]
    public function it_prevents_accepting_expired_invitation(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $newMember = User::factory()->create(['email' => 'newmember@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'token' => 'expired-token',
            'expires_at' => now()->subDays(1),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This invitation has expired.');

        $this->service->acceptInvitation('expired-token');
    }

    #[Test]
    public function it_removes_member_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Act
        $this->service->removeMember($team, $member);

        // Assert
        $this->assertFalse($team->hasMember($member));
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    }

    #[Test]
    public function it_prevents_removing_team_owner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove the team owner. Transfer ownership first.');

        $this->service->removeMember($team, $owner);
    }

    #[Test]
    public function it_unsets_current_team_when_removing_member(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $member->update(['current_team_id' => $team->id]);

        // Act
        $this->service->removeMember($team, $member);

        // Assert
        $member->refresh();
        $this->assertNull($member->current_team_id);
    }

    #[Test]
    public function it_switches_to_another_team_when_removing_current_team(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team1 = Team::factory()->create(['owner_id' => $owner->id]);
        $team2 = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team1->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        TeamMember::create([
            'team_id' => $team2->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $member->update(['current_team_id' => $team1->id]);

        // Act
        $this->service->removeMember($team1, $member);

        // Assert
        $member->refresh();
        $this->assertEquals($team2->id, $member->current_team_id);
    }

    #[Test]
    public function it_updates_member_role_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Act
        $this->service->updateRole($team, $member, 'admin');

        // Assert
        $updatedMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($updatedMember);
        $this->assertEquals('admin', $updatedMember->role);
    }

    #[Test]
    public function it_prevents_changing_owner_role(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change the role of the team owner.');

        $this->service->updateRole($team, $owner, 'admin');
    }

    #[Test]
    public function it_validates_role_when_updating(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role.');

        $this->service->updateRole($team, $member, 'invalid-role');
    }

    #[Test]
    public function it_allows_valid_roles(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Act & Assert - Test each valid role
        $this->service->updateRole($team, $member, 'admin');
        $this->assertEquals('admin', $member->fresh()->teams()->first()?->pivot?->role);

        $this->service->updateRole($team, $member, 'member');
        $this->assertEquals('member', $member->fresh()->teams()->first()?->pivot?->role);

        $this->service->updateRole($team, $member, 'viewer');
        $this->assertEquals('viewer', $member->fresh()->teams()->first()?->pivot?->role);
    }

    #[Test]
    public function it_transfers_ownership_successfully(): void
    {
        // Arrange
        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $oldOwner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $oldOwner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $newOwner->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Act
        $this->service->transferOwnership($team, $newOwner);

        // Assert
        $team->refresh();
        $this->assertEquals($newOwner->id, $team->owner_id);

        $oldOwnerMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $oldOwner->id)
            ->first();
        $this->assertEquals('admin', $oldOwnerMember->role);

        $newOwnerMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $newOwner->id)
            ->first();
        $this->assertEquals('owner', $newOwnerMember->role);
    }

    #[Test]
    public function it_prevents_transferring_ownership_to_non_member(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('New owner must be a team member.');

        $this->service->transferOwnership($team, $nonMember);
    }

    #[Test]
    public function it_checks_user_access_with_permission(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Act & Assert
        $this->assertTrue($this->service->canAccess($member, $team, 'view_projects'));
        $this->assertTrue($this->service->canAccess($member, $team, 'manage_members'));
    }

    #[Test]
    public function it_denies_access_for_non_members(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        // Act & Assert
        $this->assertFalse($this->service->canAccess($nonMember, $team, 'view_projects'));
    }

    #[Test]
    public function it_deletes_team_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Act
        $this->service->deleteTeam($team);

        // Assert
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    #[Test]
    public function it_unsets_current_team_for_all_users_when_deleting(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $owner->update(['current_team_id' => $team->id]);
        $member->update(['current_team_id' => $team->id]);

        // Act
        $this->service->deleteTeam($team);

        // Assert
        $owner->refresh();
        $member->refresh();
        $this->assertNull($owner->current_team_id);
        $this->assertNull($member->current_team_id);
    }

    #[Test]
    public function it_cancels_invitation_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        $this->service->cancelInvitation($invitation);

        // Assert
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    #[Test]
    public function it_resends_invitation_successfully(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(1),
        ]);

        // Act
        $this->service->resendInvitation($invitation);

        // Assert
        $invitation->refresh();
        $this->assertTrue($invitation->expires_at->isAfter(now()->addDays(6)));

        Mail::assertSent(TeamInvitationMail::class, function ($mail) use ($invitation) {
            return $mail->hasTo('invited@example.com') &&
                   $mail->invitation->id === $invitation->id;
        });
    }

    #[Test]
    public function it_prevents_resending_accepted_invitation(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This invitation has already been accepted.');

        $this->service->resendInvitation($invitation);
    }

    #[Test]
    public function it_uses_database_transaction_for_team_creation(): void
    {
        // Arrange
        $user = User::factory()->create();

        DB::beginTransaction();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Transaction Test Team',
        ]);

        // Assert
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Transaction Test Team',
        ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        DB::rollBack();
    }

    #[Test]
    public function it_uses_database_transaction_for_accepting_invitation(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $newMember = User::factory()->create(['email' => 'newmember@example.com']);
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'invited_by' => $owner->id,
            'token' => 'test-token',
            'expires_at' => now()->addDays(7),
        ]);

        DB::beginTransaction();

        // Act
        $this->service->acceptInvitation('test-token');

        // Assert
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $newMember->id,
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);

        DB::rollBack();
    }

    #[Test]
    public function it_uses_database_transaction_for_removing_member(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        DB::beginTransaction();

        // Act
        $this->service->removeMember($team, $member);

        // Assert
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);

        DB::rollBack();
    }

    #[Test]
    public function it_uses_database_transaction_for_transferring_ownership(): void
    {
        // Arrange
        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $oldOwner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $oldOwner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $newOwner->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        DB::beginTransaction();

        // Act
        $this->service->transferOwnership($team, $newOwner);

        // Assert
        $team->refresh();
        $this->assertEquals($newOwner->id, $team->owner_id);

        DB::rollBack();
    }

    #[Test]
    public function it_uses_database_transaction_for_deleting_team(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        DB::beginTransaction();

        // Act
        $this->service->deleteTeam($team);

        // Assert
        $this->assertSoftDeleted('teams', ['id' => $team->id]);

        DB::rollBack();
    }

    #[Test]
    public function it_creates_team_with_all_optional_fields(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $team = $this->service->createTeam($user, [
            'name' => 'Full Team',
            'slug' => 'full-team',
            'description' => 'A complete team',
            'avatar' => 'avatars/team.png',
            'is_personal' => true,
        ]);

        // Assert
        $this->assertEquals('Full Team', $team->name);
        $this->assertEquals('full-team', $team->slug);
        $this->assertEquals('A complete team', $team->description);
        $this->assertEquals('avatars/team.png', $team->avatar);
        $this->assertTrue($team->is_personal);
    }
}
