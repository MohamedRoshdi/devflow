<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Server;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class SSLService
{
    protected $email;
    protected $staging;

    public function __construct()
    {
        $this->email = config('app.ssl_email', 'admin@example.com');
        $this->staging = config('app.ssl_staging', false);
    }

    public function installCertbot(Server $server): array
    {
        try {
            $script = <<<'BASH'
            apt-get update && \
            apt-get install -y certbot python3-certbot-nginx || \
            yum install -y certbot python3-certbot-nginx
            BASH;

            $command = $this->buildSSHCommand($server, $script);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function obtainCertificate(Domain $domain): array
    {
        try {
            $server = $domain->project->server;
            
            if (!$server) {
                return ['success' => false, 'error' => 'No server associated with this domain'];
            }

            $stagingFlag = $this->staging ? '--staging' : '';
            
            $certbotCommand = sprintf(
                "certbot certonly --nginx -d %s --non-interactive --agree-tos --email %s %s",
                $domain->domain,
                $this->email,
                $stagingFlag
            );

            $command = $this->buildSSHCommand($server, $certbotCommand);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(180);
            $process->run();

            if ($process->isSuccessful()) {
                // Retrieve certificate details
                $certPath = "/etc/letsencrypt/live/{$domain->domain}/fullchain.pem";
                $keyPath = "/etc/letsencrypt/live/{$domain->domain}/privkey.pem";
                
                $domain->update([
                    'ssl_enabled' => true,
                    'ssl_provider' => 'letsencrypt',
                    'ssl_issued_at' => now(),
                    'ssl_expires_at' => now()->addDays(90),
                    'status' => 'active',
                ]);

                return [
                    'success' => true,
                    'output' => $process->getOutput(),
                    'cert_path' => $certPath,
                    'key_path' => $keyPath,
                ];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function renewCertificate(Domain $domain): array
    {
        try {
            $server = $domain->project->server;
            
            $command = $this->buildSSHCommand($server, "certbot renew --nginx --non-interactive");
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(180);
            $process->run();

            if ($process->isSuccessful()) {
                $domain->update([
                    'ssl_expires_at' => now()->addDays(90),
                ]);

                return [
                    'success' => true,
                    'output' => $process->getOutput(),
                ];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkExpiringCertificates(): array
    {
        // Get domains with certificates expiring in 30 days
        $expiringDomains = Domain::where('ssl_enabled', true)
            ->where('ssl_expires_at', '<=', now()->addDays(30))
            ->where('ssl_expires_at', '>', now())
            ->where('auto_renew_ssl', true)
            ->get();

        $renewed = [];
        $failed = [];

        foreach ($expiringDomains as $domain) {
            $result = $this->renewCertificate($domain);
            
            if ($result['success']) {
                $renewed[] = $domain->domain;
            } else {
                $failed[] = [
                    'domain' => $domain->domain,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return [
            'renewed' => $renewed,
            'failed' => $failed,
        ];
    }

    public function revokeCertificate(Domain $domain): array
    {
        try {
            $server = $domain->project->server;
            
            $command = $this->buildSSHCommand(
                $server, 
                "certbot revoke --cert-path /etc/letsencrypt/live/{$domain->domain}/cert.pem --non-interactive"
            );
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $domain->update([
                    'ssl_enabled' => false,
                    'ssl_provider' => null,
                    'ssl_issued_at' => null,
                    'ssl_expires_at' => null,
                ]);

                return ['success' => true];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p ' . $server->port,
        ];

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "sudo %s"',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand)
        );
    }
}

