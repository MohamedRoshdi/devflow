<?php

namespace App\Policies;

use App\Models\NotificationChannel;
use App\Models\User;

/**
 * NotificationChannel Policy
 *
 * Notification channels are owned by users or projects.
 * Users can manage their own notification channels.
 * Project-based channels are accessible through project ownership.
 */
class NotificationChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership
    }

    public function view(User $user, NotificationChannel $channel): bool
    {
        return $this->hasAccess($user, $channel);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create notification channels
    }

    public function update(User $user, NotificationChannel $channel): bool
    {
        return $this->hasAccess($user, $channel);
    }

    public function delete(User $user, NotificationChannel $channel): bool
    {
        return $this->hasAccess($user, $channel);
    }

    public function toggle(User $user, NotificationChannel $channel): bool
    {
        // Toggle enabled/disabled status
        return $this->hasAccess($user, $channel);
    }

    public function test(User $user, NotificationChannel $channel): bool
    {
        // Test notification sending
        return $this->hasAccess($user, $channel);
    }

    public function viewLogs(User $user, NotificationChannel $channel): bool
    {
        // View notification delivery logs
        return $this->hasAccess($user, $channel);
    }

    /**
     * Check if user has access to the notification channel
     */
    private function hasAccess(User $user, NotificationChannel $channel): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // User-owned channels
        if ($channel->user_id && $channel->user_id === $user->id) {
            return true;
        }

        // Project-based channels
        if ($channel->project_id) {
            $project = $channel->project;
            if (!$project) {
                return false;
            }

            // Project owner can access
            if ($project->user_id === $user->id) {
                return true;
            }

            // Team members can access team projects
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
