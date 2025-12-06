<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Server;
use App\Models\SSLCertificate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SSLManagementService
{
    /**
     * Issue a new SSL certificate using Let's Encrypt
     */
    public function issueCertificate(Domain $domain): bool
    {
        $server = $domain->project?->server;

        if (! $server) {
            throw new \RuntimeException('Domain has no associated server');
        }

        try {
            // Check if certbot is installed
            $this->ensureCertbotInstalled($server);

            // Get email from user or use default
            $email = $server->user->email ?? config('mail.from.address', 'admin@devflow.pro');

            // Issue certificate
            $command = sprintf(
                'certbot certonly --nginx -d %s --non-interactive --agree-tos -m %s',
                $domain->domain,
                $email
            );

            $output = $this->executeSSHCommand($server, $command);

            // Parse certificate paths
            $certPath = "/etc/letsencrypt/live/{$domain->domain}";

            // Get certificate expiry date
            $expiryCommand = "openssl x509 -enddate -noout -in {$certPath}/cert.pem | cut -d= -f2";
            $expiryDate = $this->executeSSHCommand($server, $expiryCommand);
            $expiresAt = Carbon::parse($expiryDate);

            // Create or update SSL certificate record
            $certificate = SSLCertificate::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->domain,
                ],
                [
                    'provider' => 'letsencrypt',
                    'status' => 'issued',
                    'certificate_path' => $certPath.'/cert.pem',
                    'private_key_path' => $certPath.'/privkey.pem',
                    'chain_path' => $certPath.'/chain.pem',
                    'issued_at' => now(),
                    'expires_at' => $expiresAt,
                    'auto_renew' => true,
                ]
            );

            // Update domain
            $domain->update([
                'ssl_enabled' => true,
                'ssl_provider' => 'letsencrypt',
                'ssl_issued_at' => now(),
                'ssl_expires_at' => $expiresAt,
                'auto_renew_ssl' => true,
                'status' => 'active',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to issue SSL certificate', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            // Update certificate status to failed
            SSLCertificate::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->domain,
                ],
                [
                    'status' => 'failed',
                    'renewal_error' => $e->getMessage(),
                    'last_renewal_attempt' => now(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Renew an existing SSL certificate
     */
    public function renewCertificate(Domain $domain): bool
    {
        $server = $domain->project?->server;

        if (! $server) {
            throw new \RuntimeException('Domain has no associated server');
        }

        $certificate = SSLCertificate::where('domain_id', $domain->id)
            ->where('server_id', $server->id)
            ->first();

        if (! $certificate) {
            throw new \RuntimeException('No SSL certificate found for domain');
        }

        try {
            $certificate->update([
                'last_renewal_attempt' => now(),
            ]);

            // Renew certificate
            $command = sprintf(
                'certbot renew --cert-name %s --non-interactive',
                $domain->domain
            );

            $output = $this->executeSSHCommand($server, $command);

            // Get updated expiry date
            $certPath = "/etc/letsencrypt/live/{$domain->domain}";
            $expiryCommand = "openssl x509 -enddate -noout -in {$certPath}/cert.pem | cut -d= -f2";
            $expiryDate = $this->executeSSHCommand($server, $expiryCommand);
            $expiresAt = Carbon::parse($expiryDate);

            // Update certificate
            $certificate->update([
                'status' => 'issued',
                'issued_at' => now(),
                'expires_at' => $expiresAt,
                'renewal_error' => null,
            ]);

            // Update domain
            $domain->update([
                'ssl_issued_at' => now(),
                'ssl_expires_at' => $expiresAt,
            ]);

            // Send notification
            if ($server->user) {
                $server->user->notify(
                    new \App\Notifications\SSLCertificateRenewed($certificate)
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to renew SSL certificate', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            $certificate->update([
                'status' => 'failed',
                'renewal_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check certificate expiry date
     */
    public function checkExpiry(Domain $domain): ?Carbon
    {
        $server = $domain->project?->server;

        if (! $server || ! $domain->ssl_enabled) {
            return null;
        }

        try {
            $certPath = "/etc/letsencrypt/live/{$domain->domain}/cert.pem";
            $command = "openssl x509 -enddate -noout -in {$certPath} | cut -d= -f2";

            $expiryDate = $this->executeSSHCommand($server, $command);

            return Carbon::parse($expiryDate);

        } catch (\Exception $e) {
            Log::warning('Failed to check SSL certificate expiry', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Setup auto-renewal cron job
     */
    public function setupAutoRenewal(Server $server): bool
    {
        try {
            // Check if cron job already exists
            $checkCommand = 'crontab -l 2>/dev/null | grep -q "certbot renew" && echo "exists" || echo "not_found"';
            $result = $this->executeSSHCommand($server, $checkCommand);

            if (trim($result) === 'exists') {
                return true; // Already configured
            }

            // Add cron job for daily renewal check at 3 AM
            $cronJob = '0 3 * * * /usr/bin/certbot renew --quiet --post-hook "systemctl reload nginx"';
            $command = sprintf(
                '(crontab -l 2>/dev/null; echo "%s") | crontab -',
                $cronJob
            );

            $this->executeSSHCommand($server, $command);

            Log::info('SSL auto-renewal cron job configured', [
                'server_id' => $server->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to setup SSL auto-renewal', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get certificates expiring within threshold
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\SSLCertificate>
     */
    public function getExpiringCertificates(int $daysThreshold = 30): Collection
    {
        $thresholdDate = now()->addDays($daysThreshold);

        return SSLCertificate::where('status', 'issued')
            ->where('auto_renew', true)
            ->where(function ($query) use ($thresholdDate) {
                $query->where('expires_at', '<=', $thresholdDate)
                    ->orWhereNull('expires_at');
            })
            ->with(['domain', 'server'])
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Bulk renew expiring certificates
     */
    public function renewExpiringCertificates(int $daysThreshold = 30): array
    {
        $expiringCertificates = $this->getExpiringCertificates($daysThreshold);
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($expiringCertificates as $certificate) {
            try {
                if ($certificate->domain) {
                    $this->renewCertificate($certificate->domain);
                    $results['success'][] = [
                        'domain' => $certificate->domain_name,
                        'certificate_id' => $certificate->id,
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'domain' => $certificate->domain_name,
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Revoke an SSL certificate
     */
    public function revokeCertificate(Domain $domain): bool
    {
        $server = $domain->project?->server;

        if (! $server) {
            throw new \RuntimeException('Domain has no associated server');
        }

        try {
            $command = sprintf(
                'certbot revoke --cert-name %s --non-interactive',
                $domain->domain
            );

            $this->executeSSHCommand($server, $command);

            // Update certificate status
            SSLCertificate::where('domain_id', $domain->id)
                ->where('server_id', $server->id)
                ->update([
                    'status' => 'revoked',
                ]);

            // Update domain
            $domain->update([
                'ssl_enabled' => false,
                'status' => 'inactive',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to revoke SSL certificate', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get SSL certificate info
     */
    public function getCertificateInfo(Domain $domain): ?array
    {
        $server = $domain->project?->server;

        if (! $server || ! $domain->ssl_enabled) {
            return null;
        }

        try {
            $certPath = "/etc/letsencrypt/live/{$domain->domain}/cert.pem";

            // Get certificate details
            $command = "openssl x509 -in {$certPath} -text -noout";
            $output = $this->executeSSHCommand($server, $command);

            // Parse relevant information
            $info = [
                'domain' => $domain->domain,
                'issuer' => $this->extractFromCertificate($output, 'Issuer:'),
                'subject' => $this->extractFromCertificate($output, 'Subject:'),
                'valid_from' => $this->extractFromCertificate($output, 'Not Before:'),
                'valid_until' => $this->extractFromCertificate($output, 'Not After :'),
                'serial_number' => $this->extractFromCertificate($output, 'Serial Number:'),
            ];

            return $info;

        } catch (\Exception $e) {
            Log::warning('Failed to get SSL certificate info', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ensure certbot is installed on the server
     */
    protected function ensureCertbotInstalled(Server $server): void
    {
        try {
            // Check if certbot is installed
            $checkCommand = 'command -v certbot >/dev/null 2>&1 && echo "installed" || echo "not_installed"';
            $result = $this->executeSSHCommand($server, $checkCommand);

            if (trim($result) === 'not_installed') {
                // Install certbot
                $installCommands = [
                    'DEBIAN_FRONTEND=noninteractive apt-get update',
                    'DEBIAN_FRONTEND=noninteractive apt-get install -y certbot python3-certbot-nginx',
                ];

                foreach ($installCommands as $command) {
                    $this->executeSSHCommand($server, $command);
                }

                Log::info('Certbot installed on server', [
                    'server_id' => $server->id,
                ]);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to install certbot: '.$e->getMessage());
        }
    }

    /**
     * Extract information from certificate output
     */
    protected function extractFromCertificate(string $output, string $field): ?string
    {
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (str_contains($line, $field)) {
                return trim(str_replace($field, '', $line));
            }
        }

        return null;
    }

    /**
     * Execute SSH command
     */
    protected function executeSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=30',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);
            $command = sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand)
            );
        } else {
            $sshOptions[] = '-o BatchMode=yes';

            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i '.$keyFile;
            }

            $command = sprintf(
                'ssh %s %s@%s "%s" 2>&1',
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand)
            );
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException("SSH command failed: {$remoteCommand}\nError: {$process->getErrorOutput()}");
        }

        return trim($process->getOutput());
    }
}
