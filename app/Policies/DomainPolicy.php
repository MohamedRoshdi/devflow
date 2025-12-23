<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

/**
 * Domain Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permissions: view-domains, create-domains, edit-domains, delete-domains
 */
class DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-domains');
    }

    public function view(User $user, Domain $domain): bool
    {
        return $user->can('view-domains') && $this->hasOwnershipAccess($user, $domain);
    }

    public function create(User $user): bool
    {
        return $user->can('create-domains');
    }

    public function update(User $user, Domain $domain): bool
    {
        return $user->can('edit-domains') && $this->hasOwnershipAccess($user, $domain);
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $user->can('delete-domains') && $this->hasOwnershipAccess($user, $domain);
    }

    public function renewSsl(User $user, Domain $domain): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $domain);
    }

    public function configureDns(User $user, Domain $domain): bool
    {
        return $user->can('edit-domains') && $this->hasOwnershipAccess($user, $domain);
    }

    /**
     * Check if user has ownership access to the domain
     */
    private function hasOwnershipAccess(User $user, Domain $domain): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-domains')) {
            return true;
        }

        $project = $domain->project;
        if (! $project) {
            return false;
        }

        // Project owner
        if ($project->user_id === $user->id) {
            return true;
        }

        // Team members
        if ($project->team_id) {
            $team = $project->team;
            if ($team && $team->hasMember($user)) {
                return true;
            }
        }

        return false;
    }
}
