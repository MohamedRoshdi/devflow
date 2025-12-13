<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\Teams\TeamSettings;
use App\Mail\TeamInvitation as TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeamSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $member;

    protected User $viewer;

    protected Team $team;

    protected TeamService $teamService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teamService = app(TeamService::class);

        // Create owner and team
        $this->owner = User::factory()->create(['name' => 'Team Owner']);
        $this->team = Team::factory()->create([
            'name' => 'Test Team',
            'owner_id' => $this->owner->id,
            'description' => 'Test team description',
        ]);

        // Create owner as team member
        TeamMember::factory()->owner()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'joined_at' => now(),
        ]);

        // Create other team members with different roles
        $this->admin = User::factory()->create(['name' => 'Team Admin']);
        TeamMember::factory()->admin()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->admin->id,
            'invited_by' => $this->owner->id,
            'joined_at' => now(),
        ]);

        $this->member = User::factory()->create(['name' => 'Team Member']);
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'invited_by' => $this->owner->id,
            'joined_at' => now(),
        ]);

        $this->viewer = User::factory()->create(['name' => 'Team Viewer']);
        TeamMember::factory()->viewer()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->viewer->id,
            'invited_by' => $this->owner->id,
            'joined_at' => now(),
        ]);
    }

    /** @test */
    public function component_renders_successfully_for_authenticated_team_members(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertStatus(200)
            ->assertViewIs('livewire.teams.team-settings')
            ->assertSet('name', 'Test Team')
            ->assertSet('description', 'Test team description');
    }

    /** @test */
    public function component_blocks_non_team_members(): void
    {
        $outsider = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You do not have access to this team.');

        Livewire::actingAs($outsider)
            ->test(TeamSettings::class, ['team' => $this->team]);
    }

    /** @test */
    public function owner_can_update_team_name(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', 'Updated Team Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Team updated successfully!';
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Updated Team Name',
        ]);
    }

    /** @test */
    public function admin_can_update_team_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', 'Admin Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Admin Updated Name',
        ]);
    }

    /** @test */
    public function member_cannot_update_team_settings(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', 'Member Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission');
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Test Team',
        ]);
    }

    /** @test */
    public function viewer_cannot_update_team_settings(): void
    {
        Livewire::actingAs($this->viewer)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', 'Viewer Updated Name')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission');
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Test Team',
        ]);
    }

    /** @test */
    public function owner_can_update_team_description(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('description', 'Updated description')
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function team_name_validation_requires_name(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', '')
            ->call('updateTeam')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function team_name_cannot_exceed_max_length(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('name', str_repeat('a', 256))
            ->call('updateTeam')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function team_description_cannot_exceed_max_length(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('description', str_repeat('a', 501))
            ->call('updateTeam')
            ->assertHasErrors(['description']);
    }

    /** @test */
    public function owner_can_upload_team_avatar(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('avatar', $file)
            ->call('updateTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->team->refresh();
        $this->assertNotNull($this->team->avatar);
        Storage::disk('public')->assertExists($this->team->avatar);
    }

    /** @test */
    public function component_displays_all_team_members(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertSee('Team Owner')
            ->assertSee('Team Admin')
            ->assertSee('Team Member')
            ->assertSee('Team Viewer');
    }

    /** @test */
    public function members_computed_property_returns_correct_data(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('members', function ($members) {
                return $members->count() === 4 &&
                       $members->firstWhere('user_id', $this->owner->id)['role'] === 'owner' &&
                       $members->firstWhere('user_id', $this->admin->id)['role'] === 'admin' &&
                       $members->firstWhere('user_id', $this->member->id)['role'] === 'member' &&
                       $members->firstWhere('user_id', $this->viewer->id)['role'] === 'viewer';
            });
    }

    /** @test */
    public function owner_cannot_be_edited_in_member_list(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('members', function ($members) {
                $ownerMember = $members->firstWhere('user_id', $this->owner->id);

                return $ownerMember['is_owner'] === true && $ownerMember['can_edit'] === false;
            });
    }

    /** @test */
    public function non_owner_members_can_be_edited(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('members', function ($members) {
                $adminMember = $members->firstWhere('user_id', $this->admin->id);

                return $adminMember['can_edit'] === true;
            });
    }

    /** @test */
    public function owner_can_open_invite_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', true);
    }

    /** @test */
    public function admin_can_open_invite_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', true);
    }

    /** @test */
    public function member_cannot_open_invite_modal(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', false)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission to invite');
            });
    }

    /** @test */
    public function viewer_cannot_open_invite_modal(): void
    {
        Livewire::actingAs($this->viewer)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', false)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error';
            });
    }

    /** @test */
    public function close_invite_modal_resets_fields(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', 'test@example.com')
            ->set('inviteRole', 'admin')
            ->set('showInviteModal', true)
            ->call('closeInviteModal')
            ->assertSet('showInviteModal', false)
            ->assertSet('inviteEmail', '')
            ->assertSet('inviteRole', 'member');
    }

    /** @test */
    public function owner_can_invite_new_member(): void
    {
        Mail::fake();

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', 'newmember@example.com')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Invitation sent successfully!';
            });

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $this->team->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'invited_by' => $this->owner->id,
        ]);

        Mail::assertSent(TeamInvitationMail::class, function (TeamInvitationMail $mail) {
            return $mail->hasTo('newmember@example.com');
        });
    }

    /** @test */
    public function admin_can_invite_new_member(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', 'newadmin@example.com')
            ->set('inviteRole', 'admin')
            ->call('inviteMember')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $this->team->id,
            'email' => 'newadmin@example.com',
            'role' => 'admin',
            'invited_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function invitation_requires_valid_email(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', 'invalid-email')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertHasErrors(['inviteEmail']);
    }

    /** @test */
    public function invitation_requires_valid_role(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', 'test@example.com')
            ->set('inviteRole', 'invalid-role')
            ->call('inviteMember')
            ->assertHasErrors(['inviteRole']);
    }

    /** @test */
    public function cannot_invite_existing_team_member(): void
    {
        Mail::fake();

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('inviteEmail', $this->member->email)
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'already a member');
            });

        Mail::assertNotSent(TeamInvitationMail::class);
    }

    /** @test */
    public function component_displays_pending_invitations(): void
    {
        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'pending1@example.com',
            'role' => 'member',
            'invited_by' => $this->owner->id,
        ]);

        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'pending2@example.com',
            'role' => 'admin',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertSee('pending1@example.com')
            ->assertSee('pending2@example.com');
    }

    /** @test */
    public function invitations_computed_property_filters_expired_invitations(): void
    {
        TeamInvitation::factory()->expired()->create([
            'team_id' => $this->team->id,
            'email' => 'expired@example.com',
            'invited_by' => $this->owner->id,
        ]);

        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'valid@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('invitations', function ($invitations) {
                return $invitations->count() === 1 &&
                       $invitations->first()['email'] === 'valid@example.com';
            });
    }

    /** @test */
    public function invitations_computed_property_filters_accepted_invitations(): void
    {
        TeamInvitation::factory()->accepted()->create([
            'team_id' => $this->team->id,
            'email' => 'accepted@example.com',
            'invited_by' => $this->owner->id,
        ]);

        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'pending@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('invitations', function ($invitations) {
                return $invitations->count() === 1 &&
                       $invitations->first()['email'] === 'pending@example.com';
            });
    }

    /** @test */
    public function owner_can_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'tocancel@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Invitation cancelled.';
            });

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    /** @test */
    public function admin_can_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'tocancel@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    /** @test */
    public function member_cannot_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'tocancel@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission');
            });

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    /** @test */
    public function cannot_cancel_invitation_from_different_team(): void
    {
        $otherTeam = Team::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $otherTeam->id,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id);
    }

    /** @test */
    public function owner_can_resend_invitation(): void
    {
        Mail::fake();

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'resend@example.com',
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Invitation resent!';
            });

        Mail::assertSent(TeamInvitationMail::class);
    }

    /** @test */
    public function owner_can_remove_non_owner_member(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Member removed successfully.';
            });

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
        ]);
    }

    /** @test */
    public function admin_can_remove_non_owner_member(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('removeMember', $this->member->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
        ]);
    }

    /** @test */
    public function member_cannot_remove_other_members(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('removeMember', $this->viewer->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission');
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->viewer->id,
        ]);
    }

    /** @test */
    public function cannot_remove_team_owner(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('removeMember', $this->owner->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Cannot remove the team owner');
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function owner_can_update_member_role(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'admin')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Role updated successfully.';
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function admin_can_update_member_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('updateRole', $this->member->id, 'viewer')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->member->id,
            'role' => 'viewer',
        ]);
    }

    /** @test */
    public function member_cannot_update_roles(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('updateRole', $this->viewer->id, 'member')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'do not have permission');
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->viewer->id,
            'role' => 'viewer',
        ]);
    }

    /** @test */
    public function cannot_change_owner_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('updateRole', $this->owner->id, 'admin')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Cannot change the role of the team owner');
            });

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
        ]);
    }

    /** @test */
    public function only_owner_can_open_transfer_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openTransferModal')
            ->assertSet('showTransferModal', true);
    }

    /** @test */
    public function admin_cannot_open_transfer_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openTransferModal')
            ->assertSet('showTransferModal', false)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Only the team owner can transfer ownership');
            });
    }

    /** @test */
    public function close_transfer_modal_resets_fields(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('newOwnerId', $this->admin->id)
            ->set('showTransferModal', true)
            ->call('closeTransferModal')
            ->assertSet('showTransferModal', false)
            ->assertSet('newOwnerId', null);
    }

    /** @test */
    public function owner_can_transfer_ownership(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('newOwnerId', $this->admin->id)
            ->call('transferOwnership')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Ownership transferred successfully.';
            });

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'owner_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->admin->id,
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function non_owner_cannot_transfer_ownership(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('newOwnerId', $this->member->id)
            ->call('transferOwnership');

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'owner_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function transfer_ownership_requires_new_owner(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('newOwnerId', null)
            ->call('transferOwnership')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Please select a new owner');
            });
    }

    /** @test */
    public function potential_owners_excludes_current_owner(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertViewHas('potentialOwners', function ($owners) {
                return $owners->doesntContain('id', $this->owner->id) &&
                       $owners->contains('id', $this->admin->id) &&
                       $owners->contains('id', $this->member->id);
            });
    }

    /** @test */
    public function only_owner_can_open_delete_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openDeleteModal')
            ->assertSet('showDeleteModal', true);
    }

    /** @test */
    public function admin_cannot_open_delete_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Only the team owner can delete the team');
            });
    }

    /** @test */
    public function member_cannot_open_delete_modal(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->call('openDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error';
            });
    }

    /** @test */
    public function close_delete_modal_resets_fields(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Test Team')
            ->set('showDeleteModal', true)
            ->call('closeDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deleteConfirmation', '');
    }

    /** @test */
    public function owner_can_delete_team_with_correct_confirmation(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Test Team')
            ->call('deleteTeam')
            ->assertRedirect(route('teams.index'))
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Team deleted successfully.';
            });

        $this->assertSoftDeleted('teams', [
            'id' => $this->team->id,
        ]);
    }

    /** @test */
    public function delete_team_requires_exact_name_match(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Wrong Name')
            ->call('deleteTeam')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Please type the team name to confirm deletion');
            })
            ->assertNoRedirect();

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function non_owner_cannot_delete_team(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->set('deleteConfirmation', 'Test Team')
            ->call('deleteTeam');

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function active_tab_can_be_changed(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertSet('activeTab', 'general')
            ->call('setActiveTab', 'members')
            ->assertSet('activeTab', 'members')
            ->call('setActiveTab', 'danger')
            ->assertSet('activeTab', 'danger');
    }

    /** @test */
    public function component_initializes_with_team_data(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team])
            ->assertSet('name', 'Test Team')
            ->assertSet('description', 'Test team description')
            ->assertSet('activeTab', 'general')
            ->assertSet('showInviteModal', false)
            ->assertSet('showTransferModal', false)
            ->assertSet('showDeleteModal', false);
    }

    /** @test */
    public function component_handles_team_without_description(): void
    {
        $teamWithoutDescription = Team::factory()->create([
            'owner_id' => $this->owner->id,
            'description' => null,
        ]);

        TeamMember::factory()->owner()->create([
            'team_id' => $teamWithoutDescription->id,
            'user_id' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $teamWithoutDescription])
            ->assertSet('description', '');
    }

    /** @test */
    public function update_team_handles_exceptions_gracefully(): void
    {
        // Simulate an error by using invalid data that would cause an exception
        $component = Livewire::actingAs($this->owner)
            ->test(TeamSettings::class, ['team' => $this->team]);

        // Mock the team update to throw an exception
        $this->mock(Team::class, function ($mock) {
            $mock->shouldReceive('update')->andThrow(new \Exception('Database error'));
        });

        $component
            ->set('name', 'Valid Name')
            ->call('updateTeam');
    }
}
