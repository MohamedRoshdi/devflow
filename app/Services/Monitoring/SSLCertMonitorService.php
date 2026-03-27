<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Monitors SSL certificate expiry via SSH (origin certs) and HTTP (public endpoints).
 *
 * Uses `openssl x509 -enddate` for origin cert inspection on the server
 * and `openssl s_client` for verifying publicly-facing subdomains.
 */
final class SSLCertMonitorService
{
    use ExecutesRemoteCommands;

    /**
     * Days remaining before a certificate is considered "expiring soon".
     */
    public const EXPIRY_WARNING_DAYS = 14;

    /**
     * Check SSL certificate expiry for a cert file on the origin server via SSH.
     *
     * @return array{path: string|null, expiry_date: string|null, days_remaining: int|null, is_valid: bool, is_expiring_soon: bool, error: string|null}
     */
    public function checkOriginCertExpiry(Server $server, string $certPath = '/etc/letsencrypt/live/*/fullchain.pem'): array
    {
        try {
            $output = $this->getRemoteOutput(
                $server,
                "sudo openssl x509 -enddate -noout -in {$certPath} 2>/dev/null",
                false
            );

            if (empty(trim($output))) {
                // Try to find the first available cert if wildcard path used
                $findOutput = $this->getRemoteOutput(
                    $server,
                    'sudo ls /etc/letsencrypt/live/*/fullchain.pem 2>/dev/null | head -1',
                    false
                );

                $resolvedPath = trim($findOutput);

                if (empty($resolvedPath)) {
                    return $this->errorResult(null, 'No certificate found on server');
                }

                $output = $this->getRemoteOutput(
                    $server,
                    "sudo openssl x509 -enddate -noout -in {$resolvedPath} 2>/dev/null",
                    false
                );

                $certPath = $resolvedPath;
            }

            return $this->parseOpenSslEnddate($output, $certPath);
        } catch (\Exception $e) {
            Log::warning('SSLCertMonitorService: failed to check origin cert', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResult($certPath, $e->getMessage());
        }
    }

    /**
     * Check SSL certificate for a publicly accessible subdomain via openssl s_client.
     *
     * @return array{hostname: string, expiry_date: string|null, days_remaining: int|null, is_valid: bool, is_expiring_soon: bool, error: string|null}
     */
    public function checkSubdomainSsl(string $subdomain): array
    {
        try {
            $safeHost = escapeshellarg($subdomain.':443');
            $command = "echo | openssl s_client -connect {$safeHost} -servername {$safeHost} 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null";
            $output = shell_exec($command);

            $result = $this->parseOpenSslEnddate($output ?? '', $subdomain);
            $result['hostname'] = $subdomain;

            return $result;
        } catch (\Exception $e) {
            Log::warning('SSLCertMonitorService: failed to check subdomain SSL', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage(),
            ]);

            return array_merge(
                $this->errorResult($subdomain, $e->getMessage()),
                ['hostname' => $subdomain]
            );
        }
    }

    /**
     * Get a health summary for a server's origin cert and a list of public subdomains.
     *
     * @param  array<int, string>  $subdomains  Fully-qualified hostnames to check via HTTP
     * @return array{status: string, issues: array<int, string>, origin_cert: array<string, mixed>, subdomains: array<string, array<string, mixed>>, checked_at: string}
     */
    public function getHealthSummary(Server $server, array $subdomains = []): array
    {
        $issues = [];

        // Check origin cert on the server
        $originCert = $this->checkOriginCertExpiry($server);

        if (! $originCert['is_valid']) {
            $issues[] = 'Origin cert is invalid or missing: '.($originCert['error'] ?? 'unknown error');
        } elseif ($originCert['is_expiring_soon']) {
            $days = $originCert['days_remaining'];
            $issues[] = "Origin cert expires in {$days} day(s) — renewal required";
        }

        // Check each public subdomain
        $subdomainResults = [];

        foreach ($subdomains as $hostname) {
            $result = $this->checkSubdomainSsl($hostname);
            $subdomainResults[$hostname] = $result;

            if (! $result['is_valid']) {
                $issues[] = "{$hostname}: SSL invalid or not reachable — ".($result['error'] ?? 'unknown');
            } elseif ($result['is_expiring_soon']) {
                $days = $result['days_remaining'];
                $issues[] = "{$hostname}: SSL cert expires in {$days} day(s)";
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'critical',
            'issues' => $issues,
            'origin_cert' => $originCert,
            'subdomains' => $subdomainResults,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Parse the output of `openssl x509 -enddate -noout` into a structured result.
     *
     * Expected format: `notAfter=Dec 31 23:59:59 2025 GMT`
     *
     * @return array{path: string|null, expiry_date: string|null, days_remaining: int|null, is_valid: bool, is_expiring_soon: bool, error: string|null}
     */
    private function parseOpenSslEnddate(string $output, string $path): array
    {
        $output = trim($output);

        if (empty($output) || ! str_contains($output, 'notAfter=')) {
            return $this->errorResult($path, 'Could not parse certificate expiry date');
        }

        preg_match('/notAfter=(.+)/', $output, $matches);

        if (! isset($matches[1])) {
            return $this->errorResult($path, 'Could not extract expiry date from openssl output');
        }

        $expiryString = trim($matches[1]);

        try {
            $expiryDate = \Carbon\Carbon::parse($expiryString);
            $daysRemaining = (int) now()->diffInDays($expiryDate, false);
            $isExpiringSoon = $daysRemaining <= self::EXPIRY_WARNING_DAYS;
            $isValid = $daysRemaining > 0;

            return [
                'path' => $path,
                'expiry_date' => $expiryDate->toDateTimeString(),
                'days_remaining' => $daysRemaining,
                'is_valid' => $isValid,
                'is_expiring_soon' => $isExpiringSoon,
                'error' => null,
            ];
        } catch (\Exception $e) {
            return $this->errorResult($path, "Failed to parse date '{$expiryString}': {$e->getMessage()}");
        }
    }

    /**
     * @return array{path: string|null, expiry_date: string|null, days_remaining: int|null, is_valid: bool, is_expiring_soon: bool, error: string|null}
     */
    private function errorResult(?string $path, string $error): array
    {
        return [
            'path' => $path,
            'expiry_date' => null,
            'days_remaining' => null,
            'is_valid' => false,
            'is_expiring_soon' => false,
            'error' => $error,
        ];
    }
}
