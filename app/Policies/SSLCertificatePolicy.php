<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SSLCertificate;
use App\Models\User;

/**
 * SSL Certificate Policy
 *
 * Uses permission-based authorization via Spatie Laravel Permission.
 * Permission: manage-ssl
 */
class SSLCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-ssl');
    }

    public function view(User $user, SSLCertificate $certificate): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    public function create(User $user): bool
    {
        return $user->can('manage-ssl');
    }

    public function update(User $user, SSLCertificate $certificate): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    public function delete(User $user, SSLCertificate $certificate): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    public function renew(User $user, SSLCertificate $certificate): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    public function revoke(User $user, SSLCertificate $certificate): bool
    {
        // Revoke is a critical operation
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    public function download(User $user, SSLCertificate $certificate): bool
    {
        return $user->can('manage-ssl') && $this->hasOwnershipAccess($user, $certificate);
    }

    /**
     * Check if user has ownership access to the SSL certificate
     */
    private function hasOwnershipAccess(User $user, SSLCertificate $certificate): bool
    {
        // Users with delete permission have global access
        if ($user->can('delete-domains') || $user->can('delete-servers')) {
            return true;
        }

        // Check server ownership
        if ($certificate->server_id) {
            $server = $certificate->server;
            if ($server) {
                if ($server->user_id === $user->id) {
                    return true;
                }
                if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
                    return true;
                }
            }
        }

        // Check domain ownership through project
        if ($certificate->domain_id) {
            $domain = $certificate->domain;
            if ($domain) {
                $project = $domain->project;
                if ($project) {
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
            }
        }

        return false;
    }
}
