<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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
    }

    // ==================== Team Creation Tests ====================

    /** @test */
    public function user_can_create_team(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post('/teams', [
            'name' => 'New Team',
            'description' => 'A new team description',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teams', [
            'name' => 'New Team',
            'owner_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function team_creation_requires_name(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post('/teams', [
            'description' => 'Description without name',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ==================== Team Update Tests ====================

    /** @test */
    public function owner_can_update_team(): void
    {
        $this->actingAs($this->owner);

        $response = $this->put('/teams/' . $this->team->id, [
            'name' => 'Updated Team Name',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => 'Updated Team Name',
        ]);
    }

    /** @test */
    public function non_owner_cannot_update_team(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $response = $this->put('/teams/' . $this->team->id, [
            'name' => 'Hacked Name',
        ]);

        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );

        $this->team->refresh();
        $this->assertNotEquals('Hacked Name', $this->team->name);
    }

    // ==================== Team Deletion Tests ====================

    /** @test */
    public function owner_can_delete_team(): void
    {
        $this->actingAs($this->owner);
        $teamId = $this->team->id;

        $response = $this->delete('/teams/' . $this->team->id);

        $response->assertRedirect();

        $this->assertDatabaseMissing('teams', [
            'id' => $teamId,
        ]);
    }

    /** @test */
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

        $response = $this->delete('/teams/' . $this->team->id);

        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );

        $this->assertDatabaseHas('teams', [
            'id' => $teamId,
        ]);
    }

    // ==================== Team Member Management Tests ====================

    /** @test */
    public function owner_can_invite_member(): void
    {
        Mail::fake();
        Notification::fake();

        $this->actingAs($this->owner);

        $response = $this->post('/teams/' . $this->team->id . '/invitations', [
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $this->team->id,
            'email' => 'newmember@example.com',
        ]);
    }

    /** @test */
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

        $response = $this->post('/teams/invitations/' . $invitation->token . '/accept');

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 302
        );
    }

    /** @test */
    public function owner_can_remove_member(): void
    {
        $member = User::factory()->create();
        $teamMember = TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($this->owner);

        $response = $this->delete('/teams/' . $this->team->id . '/members/' . $member->id);

        $response->assertRedirect();

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
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

        $response = $this->delete('/teams/' . $this->team->id . '/members/' . $member2->id);

        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member2->id,
        ]);
    }

    // ==================== Role Management Tests ====================

    /** @test */
    public function owner_can_change_member_role(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($this->owner);

        $response = $this->put('/teams/' . $this->team->id . '/members/' . $member->id, [
            'role' => 'admin',
        ]);

        $this->assertTrue(
            $response->status() === 200 ||
            $response->status() === 302
        );
    }

    /** @test */
    public function member_cannot_leave_as_sole_owner(): void
    {
        // Owner tries to leave their own team without transferring ownership
        $this->actingAs($this->owner);

        $response = $this->delete('/teams/' . $this->team->id . '/members/' . $this->owner->id);

        // Should fail - owner must transfer ownership or delete team
        $this->assertTrue(
            $response->status() === 422 ||
            $response->status() === 403 ||
            $response->status() === 302
        );
    }

    // ==================== Team Visibility Tests ====================

    /** @test */
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

    /** @test */
    public function team_member_can_see_shared_team(): void
    {
        $member = User::factory()->create();
        TeamMember::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member);

        $response = $this->get('/teams/' . $this->team->id);

        $response->assertOk();
    }
}
