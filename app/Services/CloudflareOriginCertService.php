<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

class CloudflareOriginCertService
{
    use ExecutesRemoteCommands;

    /**
     * Upload a Cloudflare origin certificate and key to the server.
     *
     * Paths align with NginxConfigService::resolveSSLPaths() cloudflare mapping:
     *   certificate -> /etc/ssl/cloudflare/{domain}.pem
     *   private_key -> /etc/ssl/cloudflare/{domain}.key
     *
     * @param Server $server
     * @param Domain $domain
     * @param string $certContent PEM-encoded certificate
     * @param string $keyContent PEM-encoded private key
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function uploadOriginCert(Server $server, Domain $domain, string $certContent, string $keyContent): bool
    {
        $domainName = $domain->domain;
        $certPath = "/etc/ssl/cloudflare/{$domainName}.pem";
        $keyPath = "/etc/ssl/cloudflare/{$domainName}.key";

        Log::info('Uploading Cloudflare origin certificate', [
            'server' => $server->name,
            'domain' => $domainName,
        ]);

        // Create directory
        $this->executeRemoteCommand($server, 'mkdir -p /etc/ssl/cloudflare');

        // Write certificate
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$certPath} > /dev/null",
            $certContent
        );

        // Write private key
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$keyPath} > /dev/null",
            $keyContent
        );

        // Set restrictive permissions
        $this->executeRemoteCommand($server, "chmod 600 {$certPath} {$keyPath}");

        // Reload nginx to pick up the new cert
        $this->executeRemoteCommand($server, 'systemctl reload nginx', false);

        // Update domain record with SSL paths
        $domain->update([
            'ssl_certificate' => $certPath,
            'ssl_private_key' => $keyPath,
            'ssl_issued_at' => now(),
        ]);

        Log::info('Cloudflare origin certificate uploaded successfully', [
            'server' => $server->name,
            'domain' => $domainName,
        ]);

        return true;
    }

    /**
     * Remove a Cloudflare origin certificate from the server.
     *
     * @param Server $server
     * @param Domain $domain
     * @return bool
     */
    public function removeCert(Server $server, Domain $domain): bool
    {
        $domainName = $domain->domain;
        $certPath = "/etc/ssl/cloudflare/{$domainName}.pem";
        $keyPath = "/etc/ssl/cloudflare/{$domainName}.key";

        Log::info('Removing Cloudflare origin certificate', [
            'server' => $server->name,
            'domain' => $domainName,
        ]);

        $this->executeRemoteCommand($server, "rm -f {$certPath} {$keyPath}", false);
        $this->executeRemoteCommand($server, 'systemctl reload nginx', false);

        return true;
    }

    /**
     * Check if a Cloudflare origin certificate exists on the server.
     *
     * @param Server $server
     * @param Domain $domain
     * @return bool
     */
    public function certExists(Server $server, Domain $domain): bool
    {
        $domainName = $domain->domain;
        $certPath = "/etc/ssl/cloudflare/{$domainName}.pem";

        $result = $this->executeRemoteCommand($server, "test -f {$certPath} && echo 'exists'", false);

        return str_contains($result->output(), 'exists');
    }
}
