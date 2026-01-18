<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TeamInvitation as TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TeamService
{
    /**
     * Create a new team with the user as owner
     */
    public function createTeam(User $user, array $data): Team
    {
        return DB::transaction(function () use ($user, $data) {
            $team = Team::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'owner_id' => $user->id,
                'description' => $data['description'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'is_personal' => $data['is_personal'] ?? false,
            ]);

            // Add owner as team member
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            // Set as current team if user has no current team
            if (! $user->current_team_id) {
                $user->update(['current_team_id' => $team->id]);
            }

            $freshTeam = $team->fresh();
            if ($freshTeam === null) {
                throw new \RuntimeException('Failed to refresh team after creation');
            }

            return $freshTeam;
        });
    }

    /**
     * Invite a new member to the team
     */
    public function inviteMember(Team $team, string $email, string $role, User $inviter): TeamInvitation
    {
        // Check if user already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $team->hasMember($existingUser)) {
            throw new \InvalidArgumentException('User is already a member of this team.');
        }

        // Check if there's already a pending invitation
        $existingInvitation = TeamInvitation::where('team_id', $team->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            throw new \InvalidArgumentException('An invitation has already been sent to this email.');
        }

        // Create invitation
        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
        ]);

        // Send invitation email
        Mail::to($email)->send(new TeamInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept a team invitation
     */
    public function acceptInvitation(string $token): Team
    {
        $invitation = TeamInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            throw new \InvalidArgumentException('This invitation has expired.');
        }

        $user = User::where('email', $invitation->email)->firstOrFail();

        return DB::transaction(function () use ($invitation, $user) {
            // Add user to team
            TeamMember::create([
                'team_id' => $invitation->team_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'invited_by' => $invitation->invited_by,
                'joined_at' => now(),
            ]);

            // Mark invitation as accepted
            $invitation->update(['accepted_at' => now()]);

            // Set as current team if user has no current team
            if (! $user->current_team_id) {
                $user->update(['current_team_id' => $invitation->team_id]);
            }

            return $invitation->team;
        });
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(Team $team, User $user): void
    {
        if ($team->isOwner($user)) {
            throw new \InvalidArgumentException('Cannot remove the team owner. Transfer ownership first.');
        }

        DB::transaction(function () use ($team, $user) {
            TeamMember::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->delete();

            // If this was the user's current team, unset it
            if ($user->current_team_id === $team->id) {
                $newCurrentTeam = $user->teams()->first();
                $user->update(['current_team_id' => $newCurrentTeam?->id]);
            }
        });
    }

    /**
     * Update a member's role
     */
    public function updateRole(Team $team, User $user, string $role): void
    {
        if ($team->isOwner($user)) {
            throw new \InvalidArgumentException('Cannot change the role of the team owner.');
        }

        if (! in_array($role, ['admin', 'member', 'viewer'])) {
            throw new \InvalidArgumentException('Invalid role.');
        }

        TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->update(['role' => $role]);
    }

    /**
     * Transfer team ownership to another member
     */
    public function transferOwnership(Team $team, User $newOwner): void
    {
        if (! $team->hasMember($newOwner)) {
            throw new \InvalidArgumentException('New owner must be a team member.');
        }

        DB::transaction(function () use ($team, $newOwner) {
            $oldOwner = $team->owner;

            // Update team owner
            $team->update(['owner_id' => $newOwner->id]);

            // Update old owner to admin
            if ($oldOwner?->id !== null) {
                TeamMember::where('team_id', $team->id)
                    ->where('user_id', $oldOwner->id)
                    ->update(['role' => 'admin']);
            }

            // Update new owner role
            TeamMember::where('team_id', $team->id)
                ->where('user_id', $newOwner->id)
                ->update(['role' => 'owner']);
        });
    }

    /**
     * Check if user has permission on team
     */
    public function canAccess(User $user, Team $team, string $permission): bool
    {
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return false;
        }

        return $member->hasPermission($permission);
    }

    /**
     * Delete a team
     */
    public function deleteTeam(Team $team): void
    {
        DB::transaction(function () use ($team) {
            // Remove team as current team from all users
            User::where('current_team_id', $team->id)->update(['current_team_id' => null]);

            // Delete team (cascades to members and invitations)
            $team->delete();
        });
    }

    /**
     * Cancel a team invitation
     */
    public function cancelInvitation(TeamInvitation $invitation): void
    {
        $invitation->delete();
    }

    /**
     * Resend a team invitation
     */
    public function resendInvitation(TeamInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw new \InvalidArgumentException('This invitation has already been accepted.');
        }

        // Update expiration
        $invitation->update(['expires_at' => now()->addDays(7)]);

        // Resend email
        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));
    }
}
