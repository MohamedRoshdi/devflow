<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private TeamInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->invitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'invitee@example.com',
            'accepted_at' => null,
        ]);
    }

    public function test_show_displays_valid_invitation(): void
    {
        $response = $this->get(route('invitations.show', $this->invitation->token));

        $response->assertOk();
        $response->assertViewIs('teams.invitation');
        $response->assertViewHas('invitation', function ($viewInvitation) {
            return $viewInvitation->id === $this->invitation->id;
        });
        $response->assertViewHas('expired', false);
    }

    public function test_show_displays_expired_invitation(): void
    {
        $expiredInvitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'expired@example.com',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('invitations.show', $expiredInvitation->token));

        $response->assertOk();
        $response->assertViewIs('teams.invitation');
        $response->assertViewHas('expired', true);
    }

    public function test_show_redirects_if_already_accepted(): void
    {
        $acceptedInvitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => 'accepted@example.com',
            'accepted_at' => now(),
        ]);

        $response = $this->get(route('invitations.show', $acceptedInvitation->token));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('error', 'This invitation has already been accepted.');
    }

    public function test_show_returns_404_for_invalid_token(): void
    {
        $response = $this->get(route('invitations.show', 'invalid-token'));

        $response->assertNotFound();
    }

    public function test_accept_requires_authentication(): void
    {
        $response = $this->post(route('invitations.accept', $this->invitation->token));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('message', 'Please log in to accept the invitation.');
    }

    public function test_authenticated_user_can_accept_invitation(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('invitations.accept', $this->invitation->token));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ]);

        $this->invitation->refresh();
        $this->assertNotNull($this->invitation->accepted_at);
    }

    public function test_accept_fails_for_expired_invitation(): void
    {
        $expiredInvitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => $this->user->email,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('invitations.accept', $expiredInvitation->token));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('error');
    }

    public function test_accept_fails_for_already_accepted_invitation(): void
    {
        $acceptedInvitation = TeamInvitation::factory()->create([
            'team_id' => $this->team->id,
            'email' => $this->user->email,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('invitations.accept', $acceptedInvitation->token));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('error');
    }

    public function test_accept_fails_for_invalid_token(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('invitations.accept', 'invalid-token'));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('error');
    }

    public function test_invitation_can_be_accepted_by_matching_email(): void
    {
        $user = User::factory()->create(['email' => 'invitee@example.com']);

        $response = $this->actingAs($user)
            ->post(route('invitations.accept', $this->invitation->token));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success');
    }
}
