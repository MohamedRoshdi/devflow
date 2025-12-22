<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Team Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-teams, create-teams, edit-teams, delete-teams, manage-team-members
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-teams');
    }

    public function view(User $user, Team $team): bool
    {
        if (! $user->can('view-teams')) {
            return false;
        }

        // Global access or ownership/membership
        return $user->can('edit-teams')
            || $team->owner_id === $user->id
            || $team->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->can('create-teams');
    }

    public function update(User $user, Team $team): bool
    {
        if (! $user->can('edit-teams')) {
            return false;
        }

        // Global access or owner
        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        if (! $user->can('delete-teams')) {
            return false;
        }

        // Cannot delete personal teams
        if ($team->is_personal) {
            return false;
        }

        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function addMember(User $user, Team $team): bool
    {
        if (! $user->can('manage-team-members')) {
            return false;
        }

        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function removeMember(User $user, Team $team): bool
    {
        if (! $user->can('manage-team-members')) {
            return false;
        }

        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function updateMemberRole(User $user, Team $team): bool
    {
        if (! $user->can('manage-team-members')) {
            return false;
        }

        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function transferOwnership(User $user, Team $team): bool
    {
        // Cannot transfer personal teams
        if ($team->is_personal) {
            return false;
        }

        // Must have delete permission (highest) or be owner
        return $user->can('delete-teams') || $team->owner_id === $user->id;
    }

    public function inviteMembers(User $user, Team $team): bool
    {
        if (! $user->can('manage-team-members')) {
            return false;
        }

        return $user->can('delete-teams') || $team->owner_id === $user->id;
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
