<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Team Policy
 *
 * Teams are owned by users. The owner has full control.
 * Team members can view and perform limited operations based on their role.
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership/membership
    }

    public function view(User $user, Team $team): bool
    {
        // Super admin or admin can view all teams
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Team owner can view
        if ($team->owner_id === $user->id) {
            return true;
        }

        // Team members can view
        return $team->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create teams
    }

    public function update(User $user, Team $team): bool
    {
        // Super admin or admin can update all teams
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can update team settings
        return $team->owner_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        // Super admin or admin can delete all teams
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can delete the team
        // Cannot delete personal teams
        if ($team->is_personal) {
            return false;
        }

        return $team->owner_id === $user->id;
    }

    public function addMember(User $user, Team $team): bool
    {
        // Super admin or admin can add members to any team
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can add members
        return $team->owner_id === $user->id;
    }

    public function removeMember(User $user, Team $team): bool
    {
        // Super admin or admin can remove members from any team
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can remove members
        return $team->owner_id === $user->id;
    }

    public function updateMemberRole(User $user, Team $team): bool
    {
        // Super admin or admin can update member roles in any team
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can update member roles
        return $team->owner_id === $user->id;
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        // Super admin can transfer ownership of any team
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Only current owner can transfer ownership
        // Cannot transfer personal teams
        if ($team->is_personal) {
            return false;
        }

        return $team->owner_id === $user->id;
    }

    public function inviteMembers(User $user, Team $team): bool
    {
        // Super admin or admin can invite to any team
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Only team owner can send invitations
        return $team->owner_id === $user->id;
    }

    public function leave(User $user, Team $team): bool
    {
        // Owner cannot leave their own team
        if ($team->owner_id === $user->id) {
            return false;
        }

        // Member can leave if they are part of the team
        return $team->hasMember($user);
    }
}
