<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

/**
 * Domain Policy
 *
 * Domains are owned through their parent project.
 * Users can manage domains if they have access to the project.
 */
class DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by project ownership
    }

    public function view(User $user, Domain $domain): bool
    {
        return $this->hasAccess($user, $domain);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create domains for their projects
    }

    public function update(User $user, Domain $domain): bool
    {
        return $this->hasAccess($user, $domain);
    }

    public function delete(User $user, Domain $domain): bool
    {
        // Only project owner can delete domains
        $project = $domain->project;
        return $project && $project->user_id === $user->id;
    }

    public function renewSsl(User $user, Domain $domain): bool
    {
        return $this->hasAccess($user, $domain);
    }

    public function configureDns(User $user, Domain $domain): bool
    {
        return $this->hasAccess($user, $domain);
    }

    /**
     * Check if user has access to the domain through the project
     */
    private function hasAccess(User $user, Domain $domain): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        $project = $domain->project;
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

        return false;
    }
}
