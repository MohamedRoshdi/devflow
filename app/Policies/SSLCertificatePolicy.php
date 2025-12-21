<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SSLCertificate;
use App\Models\User;

/**
 * SSL Certificate Policy
 *
 * SSL certificates can be associated with servers or domains.
 * Users can manage SSL certificates if they have access to the related server/domain.
 */
class SSLCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Can see the list, filtered by ownership
    }

    public function view(User $user, SSLCertificate $certificate): bool
    {
        return $this->hasAccess($user, $certificate);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create SSL certificates for their servers/domains
    }

    public function update(User $user, SSLCertificate $certificate): bool
    {
        return $this->hasAccess($user, $certificate);
    }

    public function delete(User $user, SSLCertificate $certificate): bool
    {
        return $this->hasAccess($user, $certificate);
    }

    public function renew(User $user, SSLCertificate $certificate): bool
    {
        // Renewal is allowed for users with access
        return $this->hasAccess($user, $certificate);
    }

    public function revoke(User $user, SSLCertificate $certificate): bool
    {
        // Revoke is a critical operation, requires ownership or admin role
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Check server ownership
        if ($certificate->server_id) {
            $server = $certificate->server;
            if ($server && $server->user_id === $user->id) {
                return true;
            }
        }

        // Check domain ownership through project
        if ($certificate->domain_id) {
            $domain = $certificate->domain;
            if ($domain) {
                $project = $domain->project;
                if ($project && $project->user_id === $user->id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function download(User $user, SSLCertificate $certificate): bool
    {
        return $this->hasAccess($user, $certificate);
    }

    /**
     * Check if user has access to the SSL certificate
     */
    private function hasAccess(User $user, SSLCertificate $certificate): bool
    {
        // Super admin or admin can access all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Check server access
        if ($certificate->server_id) {
            $server = $certificate->server;
            if ($server) {
                // Server owner can access
                if ($server->user_id === $user->id) {
                    return true;
                }

                // Team members can access team servers
                if ($server->team_id && $user->currentTeam && $user->currentTeam->id === $server->team_id) {
                    return true;
                }
            }
        }

        // Check domain access through project
        if ($certificate->domain_id) {
            $domain = $certificate->domain;
            if ($domain) {
                $project = $domain->project;
                if ($project) {
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
            }
        }

        return false;
    }
}
