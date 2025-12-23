<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\SSLCertificate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SSLService
{
    /**
     * Check if certbot is installed on the server
     */
    public function checkCertbotInstalled(Server $server): bool
    {
        try {
            $command = $this->buildSSHCommand($server, 'which certbot', true);
            $result = Process::timeout(10)->run($command);

            return $result->successful();
        } catch (\Exception $e) {
            Log::error('Failed to check certbot installation', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Install certbot on the server
     */
    public function installCertbot(Server $server): array
    {
        try {
            Log::info('Installing certbot', ['server_id' => $server->id]);

            // Check if already installed
            if ($this->checkCertbotInstalled($server)) {
                return [
                    'success' => true,
                    'message' => 'Certbot is already installed',
                ];
            }

            $installScript = $this->getCertbotInstallScript($server);
            $command = $this->buildSSHCommand($server, $installScript);

            $result = Process::timeout(300)->run($command); // 5 minutes timeout

            if ($result->successful() && $this->checkCertbotInstalled($server)) {
                Log::info('Certbot installed successfully', ['server_id' => $server->id]);

                return [
                    'success' => true,
                    'message' => 'Certbot installed successfully',
                    'output' => $result->output(),
                ];
            }

            $errorMessage = $result->errorOutput() ?: $result->output();

            Log::error('Certbot installation failed', [
                'server_id' => $server->id,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to install certbot: '.substr($errorMessage, 0, 200),
                'error' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('Certbot installation exception', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Installation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Issue a new SSL certificate using Let's Encrypt
     */
    public function issueCertificate(Server $server, string $domain, string $email): array
    {
        try {
            Log::info('Issuing SSL certificate', [
                'server_id' => $server->id,
                'domain' => $domain,
            ]);

            // Check if certbot is installed
            if (! $this->checkCertbotInstalled($server)) {
                $installResult = $this->installCertbot($server);
                if (! $installResult['success']) {
                    return $installResult;
                }
            }

            // Issue certificate using certbot
            $isRoot = strtolower($server->username) === 'root';
            $sudoPrefix = $this->getSudoPrefix($server);

            $certbotCommand = "{$sudoPrefix}certbot certonly --standalone --non-interactive --agree-tos --email {$email} -d {$domain} --preferred-challenges http";

            $command = $this->buildSSHCommand($server, $certbotCommand);
            $result = Process::timeout(120)->run($command);

            $output = $result->output();
            $errorOutput = $result->errorOutput();

            if ($result->successful() || str_contains($output, 'Successfully received certificate')) {
                // Get certificate information
                $certInfo = $this->getCertificateInfo($server, $domain);

                // Create or update certificate record
                $certificate = SSLCertificate::updateOrCreate(
                    [
                        'server_id' => $server->id,
                        'domain_name' => $domain,
                    ],
                    [
                        'provider' => 'letsencrypt',
                        'status' => 'issued',
                        'certificate_path' => "/etc/letsencrypt/live/{$domain}/fullchain.pem",
                        'private_key_path' => "/etc/letsencrypt/live/{$domain}/privkey.pem",
                        'chain_path' => "/etc/letsencrypt/live/{$domain}/chain.pem",
                        'issued_at' => now(),
                        'expires_at' => $certInfo['expires_at'] ?? now()->addDays(90),
                        'auto_renew' => true,
                        'renewal_error' => null,
                    ]
                );

                Log::info('SSL certificate issued successfully', [
                    'server_id' => $server->id,
                    'domain' => $domain,
                    'certificate_id' => $certificate->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'SSL certificate issued successfully',
                    'certificate' => $certificate,
                    'output' => $output,
                ];
            }

            // Certificate issuance failed
            $errorMessage = ! empty($errorOutput) ? $errorOutput : $output;

            // Try to create a failed certificate record
            try {
                SSLCertificate::updateOrCreate(
                    [
                        'server_id' => $server->id,
                        'domain_name' => $domain,
                    ],
                    [
                        'provider' => 'letsencrypt',
                        'status' => 'failed',
                        'renewal_error' => $errorMessage,
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create failed certificate record', [
                    'error' => $e->getMessage(),
                ]);
            }

            Log::error('SSL certificate issuance failed', [
                'server_id' => $server->id,
                'domain' => $domain,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to issue certificate: '.substr($errorMessage, 0, 300),
                'error' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('SSL certificate issuance exception', [
                'server_id' => $server->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Certificate issuance failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Renew an existing SSL certificate
     */
    public function renewCertificate(SSLCertificate $certificate): array
    {
        try {
            Log::info('Renewing SSL certificate', [
                'certificate_id' => $certificate->id,
                'domain' => $certificate->domain_name,
            ]);

            $certificate->update([
                'last_renewal_attempt' => now(),
            ]);

            $server = $certificate->server;
            $sudoPrefix = $this->getSudoPrefix($server);

            // Renew using certbot
            $renewCommand = "{$sudoPrefix}certbot renew --cert-name {$certificate->domain_name} --non-interactive";

            $command = $this->buildSSHCommand($server, $renewCommand);
            $result = Process::timeout(120)->run($command);

            $output = $result->output();
            $errorOutput = $result->errorOutput();

            if ($result->successful() || str_contains($output, 'successfully renewed') || str_contains($output, 'not yet due for renewal')) {
                // Get updated certificate information
                $certInfo = $this->getCertificateInfo($server, $certificate->domain_name);

                $certificate->update([
                    'status' => 'issued',
                    'issued_at' => now(),
                    'expires_at' => $certInfo['expires_at'] ?? now()->addDays(90),
                    'renewal_error' => null,
                ]);

                Log::info('SSL certificate renewed successfully', [
                    'certificate_id' => $certificate->id,
                    'domain' => $certificate->domain_name,
                ]);

                return [
                    'success' => true,
                    'message' => 'Certificate renewed successfully',
                    'output' => $output,
                ];
            }

            // Renewal failed
            $errorMessage = ! empty($errorOutput) ? $errorOutput : $output;

            $certificate->update([
                'status' => 'failed',
                'renewal_error' => $errorMessage,
            ]);

            Log::error('SSL certificate renewal failed', [
                'certificate_id' => $certificate->id,
                'domain' => $certificate->domain_name,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to renew certificate: '.substr($errorMessage, 0, 300),
                'error' => $errorMessage,
            ];

        } catch (\Exception $e) {
            $certificate->update([
                'status' => 'failed',
                'renewal_error' => $e->getMessage(),
            ]);

            Log::error('SSL certificate renewal exception', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Renewal failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Revoke an SSL certificate
     */
    public function revokeCertificate(SSLCertificate $certificate): array
    {
        try {
            Log::info('Revoking SSL certificate', [
                'certificate_id' => $certificate->id,
                'domain' => $certificate->domain_name,
            ]);

            $server = $certificate->server;
            $sudoPrefix = $this->getSudoPrefix($server);

            $revokeCommand = "{$sudoPrefix}certbot revoke --cert-name {$certificate->domain_name} --non-interactive";

            $command = $this->buildSSHCommand($server, $revokeCommand);
            $result = Process::timeout(60)->run($command);

            if ($result->successful()) {
                $certificate->update([
                    'status' => 'revoked',
                ]);

                Log::info('SSL certificate revoked successfully', [
                    'certificate_id' => $certificate->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Certificate revoked successfully',
                ];
            }

            $errorMessage = $result->errorOutput() ?: $result->output();

            Log::error('SSL certificate revocation failed', [
                'certificate_id' => $certificate->id,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to revoke certificate: '.substr($errorMessage, 0, 300),
                'error' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error('SSL certificate revocation exception', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Revocation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check certificate status
     */
    public function checkCertificateStatus(SSLCertificate $certificate): array
    {
        try {
            $certInfo = $this->getCertificateInfo($certificate->server, $certificate->domain_name);

            if (isset($certInfo['expires_at'])) {
                $certificate->update([
                    'expires_at' => $certInfo['expires_at'],
                    'status' => $certInfo['expires_at']->isPast() ? 'expired' : 'issued',
                ]);

                return [
                    'success' => true,
                    'valid' => ! $certInfo['expires_at']->isPast(),
                    'expires_at' => $certInfo['expires_at'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Certificate not found or invalid',
            ];

        } catch (\Exception $e) {
            Log::error('Certificate status check failed', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check certificate status: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get certificate information using openssl
     */
    public function getCertificateInfo(Server $server, string $domain): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";

            $opensslCommand = "{$sudoPrefix}openssl x509 -in {$certPath} -noout -enddate 2>/dev/null || echo 'NOT_FOUND'";

            $command = $this->buildSSHCommand($server, $opensslCommand, true);
            $result = Process::timeout(10)->run($command);

            $output = trim($result->output());

            if (str_contains($output, 'NOT_FOUND') || ! $result->successful()) {
                return [
                    'found' => false,
                ];
            }

            // Parse expiry date: notAfter=Nov 28 12:00:00 2026 GMT
            if (preg_match('/notAfter=(.+)/', $output, $matches)) {
                $expiryDate = Carbon::parse($matches[1]);

                return [
                    'found' => true,
                    'expires_at' => $expiryDate,
                    'days_until_expiry' => $expiryDate->diffInDays(now()),
                ];
            }

            return [
                'found' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get certificate info', [
                'server_id' => $server->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'found' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Setup automatic renewal cron job
     */
    public function setupAutoRenewal(Server $server): array
    {
        try {
            Log::info('Setting up auto-renewal for certificates', ['server_id' => $server->id]);

            $sudoPrefix = $this->getSudoPrefix($server);

            // Certbot usually sets up auto-renewal during installation
            // We'll verify and ensure it's configured
            $cronCheckCommand = "{$sudoPrefix}systemctl status certbot.timer 2>/dev/null || crontab -l | grep certbot || echo 'NOT_CONFIGURED'";

            $command = $this->buildSSHCommand($server, $cronCheckCommand, true);
            $result = Process::timeout(10)->run($command);

            $output = $result->output();

            if (str_contains($output, 'active') || str_contains($output, 'certbot renew')) {
                return [
                    'success' => true,
                    'message' => 'Auto-renewal is already configured',
                ];
            }

            // Setup cron job if not configured
            $cronCommand = '0 2 * * * certbot renew --quiet';
            $setupCronCommand = "(crontab -l 2>/dev/null | grep -v certbot; echo '{$cronCommand}') | crontab -";

            $command = $this->buildSSHCommand($server, $setupCronCommand);
            $result = Process::timeout(30)->run($command);

            if ($result->successful()) {
                Log::info('Auto-renewal cron job configured', ['server_id' => $server->id]);

                return [
                    'success' => true,
                    'message' => 'Auto-renewal configured successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to configure auto-renewal',
                'error' => $result->errorOutput(),
            ];

        } catch (\Exception $e) {
            Log::error('Auto-renewal setup failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to setup auto-renewal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get certbot installation script
     */
    protected function getCertbotInstallScript(Server $server): string
    {
        $sudoPrefix = $this->getSudoPrefix($server);

        return <<<BASH
#!/bin/bash
set -e

echo "Installing certbot..."

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=\$ID
fi

# Install based on OS
if [ "\$OS" = "debian" ] || [ "\$OS" = "ubuntu" ]; then
    {$sudoPrefix}apt-get update -qq
    {$sudoPrefix}apt-get install -y certbot
elif [ "\$OS" = "centos" ] || [ "\$OS" = "rhel" ] || [ "\$OS" = "rocky" ] || [ "\$OS" = "almalinux" ]; then
    {$sudoPrefix}dnf install -y certbot 2>/dev/null || {$sudoPrefix}yum install -y certbot
elif [ "\$OS" = "fedora" ]; then
    {$sudoPrefix}dnf install -y certbot
else
    echo "Unsupported OS: \$OS"
    exit 1
fi

certbot --version
echo "Certbot installed successfully"
BASH;
    }

    /**
     * Get sudo prefix for commands
     */
    protected function getSudoPrefix(Server $server): string
    {
        $isRoot = strtolower($server->username) === 'root';

        if ($isRoot) {
            return '';
        } elseif ($server->ssh_password) {
            $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);

            return "echo '{$escapedPassword}' | sudo -S ";
        } else {
            return 'sudo ';
        }
    }

    /**
     * Build SSH command for remote execution
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand, bool $suppressWarnings = false): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        // For long/complex scripts, use base64 encoding
        $isLongScript = strlen($remoteCommand) > 500 || str_contains($remoteCommand, '$(');

        if ($isLongScript) {
            $encodedScript = base64_encode($remoteCommand);
            $executeCommand = "echo {$encodedScript} | base64 -d | /bin/bash";
        } else {
            $executeCommand = '/bin/bash -c '.escapeshellarg($remoteCommand);
        }

        // Check if password authentication should be used
        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($executeCommand),
                $stderrRedirect
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s %s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($executeCommand),
            $stderrRedirect
        );
    }
}
