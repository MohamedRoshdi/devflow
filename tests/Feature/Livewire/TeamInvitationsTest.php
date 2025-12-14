<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Teams\TeamInvitations;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class TeamInvitationsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $member;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->member = User::factory()->create();

        $this->team = Team::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'Test Team',
        ]);

        // Add admin to team
        $this->team->members()->attach($this->admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Add member to team
        $this->team->members()->attach($this->member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->assertSet('showInviteModal', false)
            ->assertSet('inviteEmail', '')
            ->assertSet('inviteRole', 'member');
    }

    // ==================== INVITATIONS DISPLAY TESTS ====================

    public function test_displays_pending_invitations(): void
    {
        TeamInvitation::factory()->count(3)->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'expires_at' => now()->addDays(5),
            'accepted_at' => null,
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $this->assertCount(3, $invitations);
    }

    public function test_excludes_accepted_invitations(): void
    {
        TeamInvitation::factory()->count(2)->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'expires_at' => now()->addDays(5),
            'accepted_at' => null,
        ]);

        TeamInvitation::factory()->accepted()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $this->assertCount(2, $invitations);
    }

    public function test_excludes_expired_invitations(): void
    {
        TeamInvitation::factory()->count(2)->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'expires_at' => now()->addDays(5),
            'accepted_at' => null,
        ]);

        TeamInvitation::factory()->expired()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $this->assertCount(2, $invitations);
    }

    public function test_invitations_are_ordered_by_latest(): void
    {
        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'email' => 'older@example.com',
            'expires_at' => now()->addDays(5),
            'created_at' => now()->subHour(),
        ]);

        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'email' => 'newer@example.com',
            'expires_at' => now()->addDays(5),
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $this->assertEquals('newer@example.com', $invitations->first()['email']);
    }

    public function test_invitation_data_structure(): void
    {
        TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
            'email' => 'test@example.com',
            'role' => 'admin',
            'expires_at' => now()->addDays(5),
        ]);

        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $invitation = $invitations->first();

        $this->assertArrayHasKey('id', $invitation);
        $this->assertArrayHasKey('email', $invitation);
        $this->assertArrayHasKey('role', $invitation);
        $this->assertArrayHasKey('invited_by', $invitation);
        $this->assertArrayHasKey('created_at', $invitation);
        $this->assertArrayHasKey('expires_at', $invitation);
        $this->assertEquals('test@example.com', $invitation['email']);
        $this->assertEquals('Admin', $invitation['role']);
        $this->assertEquals($this->owner->name, $invitation['invited_by']);
    }

    // ==================== INVITE MODAL TESTS ====================

    public function test_owner_can_open_invite_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', true);
    }

    public function test_admin_can_open_invite_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', true);
    }

    public function test_member_cannot_open_invite_modal(): void
    {
        Livewire::actingAs($this->member)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', false)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'permission');
            });
    }

    public function test_can_close_invite_modal(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', true)
            ->call('closeInviteModal')
            ->assertSet('showInviteModal', false);
    }

    public function test_close_modal_resets_form(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'test@example.com')
            ->set('inviteRole', 'admin')
            ->call('closeInviteModal')
            ->assertSet('inviteEmail', '')
            ->assertSet('inviteRole', 'member');
    }

    // ==================== INVITE MEMBER TESTS ====================

    public function test_can_invite_member(): void
    {
        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inviteMember')
                ->once()
                ->andReturn(TeamInvitation::factory()->make([
                    'team_id' => $this->team->id,
                    'email' => 'newmember@example.com',
                ]));
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'newmember@example.com')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertSet('showInviteModal', false)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'sent successfully');
            });
    }

    public function test_can_invite_admin(): void
    {
        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inviteMember')
                ->once()
                ->andReturn(TeamInvitation::factory()->admin()->make());
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'admin@example.com')
            ->set('inviteRole', 'admin')
            ->call('inviteMember')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success';
            });
    }

    public function test_can_invite_viewer(): void
    {
        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inviteMember')
                ->once()
                ->andReturn(TeamInvitation::factory()->viewer()->make());
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'viewer@example.com')
            ->set('inviteRole', 'viewer')
            ->call('inviteMember')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success';
            });
    }

    public function test_invite_validates_email_required(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', '')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertHasErrors(['inviteEmail']);
    }

    public function test_invite_validates_email_format(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'invalid-email')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertHasErrors(['inviteEmail']);
    }

    public function test_invite_validates_role(): void
    {
        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'test@example.com')
            ->set('inviteRole', 'invalid_role')
            ->call('inviteMember')
            ->assertHasErrors(['inviteRole']);
    }

    public function test_invite_handles_service_exception(): void
    {
        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inviteMember')
                ->once()
                ->andThrow(new \Exception('User is already a member'));
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->set('inviteEmail', 'existing@example.com')
            ->set('inviteRole', 'member')
            ->call('inviteMember')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'already a member');
            });
    }

    // ==================== CANCEL INVITATION TESTS ====================

    public function test_owner_can_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cancelInvitation')
                ->once();
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'cancelled');
            });
    }

    public function test_admin_can_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cancelInvitation')
                ->once();
        });

        Livewire::actingAs($this->admin)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success';
            });
    }

    public function test_member_cannot_cancel_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->member)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'permission');
            });
    }

    public function test_cannot_cancel_invitation_from_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $otherTeam->id,
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertStatus(403);
    }

    public function test_cancel_invitation_handles_exception(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cancelInvitation')
                ->once()
                ->andThrow(new \Exception('Failed to cancel'));
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('cancelInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error';
            });
    }

    // ==================== RESEND INVITATION TESTS ====================

    public function test_owner_can_resend_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resendInvitation')
                ->once();
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'resent');
            });
    }

    public function test_admin_can_resend_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resendInvitation')
                ->once();
        });

        Livewire::actingAs($this->admin)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success';
            });
    }

    public function test_member_cannot_resend_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->member)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertNotDispatched('notification');
    }

    public function test_cannot_resend_invitation_from_other_team(): void
    {
        $otherTeam = Team::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $otherTeam->id,
            'invited_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertStatus(403);
    }

    public function test_resend_invitation_handles_exception(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'invited_by' => $this->owner->id,
        ]);

        $this->mock(TeamService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resendInvitation')
                ->once()
                ->andThrow(new \Exception('Already accepted'));
        });

        Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('resendInvitation', $invitation->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error';
            });
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_invitations(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(TeamInvitations::class, ['team' => $this->team]);

        $invitations = $component->viewData('invitations');
        $this->assertCount(0, $invitations);
    }

    // ==================== AUTHORIZATION TESTS ====================

    public function test_non_team_member_cannot_manage(): void
    {
        $outsider = User::factory()->create();

        Livewire::actingAs($outsider)
            ->test(TeamInvitations::class, ['team' => $this->team])
            ->call('openInviteModal')
            ->assertSet('showInviteModal', false);
    }
}
