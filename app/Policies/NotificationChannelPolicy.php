<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotificationChannel;
use App\Models\User;

/**
 * NotificationChannel Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-notifications, manage-notification-channels
 */
class NotificationChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-notifications');
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $user->can('view-notifications') && $this->hasOwnershipAccess($user, $channel);
    }

    public function create(User $user): bool
    {
        return $user->can('manage-notification-channels');
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $user->can('manage-notification-channels') && $this->hasOwnershipAccess($user, $channel);
    }

    public function delete(User $user, NotificationChannel $channel): bool
    {
        return $user->can('manage-notification-channels') && $this->hasOwnershipAccess($user, $channel);
    }

    public function toggle(User $user, NotificationChannel $channel): bool
    {
        return $user->can('manage-notification-channels') && $this->hasOwnershipAccess($user, $channel);
    }

    public function test(User $user, NotificationChannel $channel): bool
    {
        return $user->can('manage-notification-channels') && $this->hasOwnershipAccess($user, $channel);
    }

    public function viewLogs(User $user, NotificationChannel $channel): bool
    {
        return $user->can('view-notifications') && $this->hasOwnershipAccess($user, $channel);
    }

    /**
     * Check if user has ownership access to the notification channel
     */
    private function hasOwnershipAccess(User $user, NotificationChannel $channel): bool
    {
        // Users with manage permission have global access
        if ($user->can('manage-notification-channels')) {
            return true;
        }

        // User-owned channels
        if ($channel->user_id && $channel->user_id === $user->id) {
            return true;
        }

        // Project-based channels
        if ($channel->project_id) {
            $project = $channel->project;
            if (! $project) {
                return false;
            }

            if ($project->user_id === $user->id) {
                return true;
            }

            if ($project->team_id) {
                $team = $project->team;
                if ($team && $team->hasMember($user)) {
                    return true;
                }
            }
        }

        return false;
    }
}
