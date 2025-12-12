<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Project;
use App\Models\SSLCertificate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class DomainService
{
    public function __construct(
        private readonly SSLService $sslService
    ) {}

    /**
     * Setup a new domain for a project
     *
     * @param Project $project
     * @param array<string, mixed> $domainData
     * @return Domain
     * @throws ValidationException
     * @throws \RuntimeException
     */
    public function setupDomain(Project $project, array $domainData): Domain
    {
        // Validate domain data
        $validated = $this->validateDomainData($domainData);

        // Check if domain is available (not already registered)
        if ($this->isDomainRegistered($validated['domain'])) {
            throw new \RuntimeException("Domain {$validated['domain']} is already registered in the system.");
        }

        // Validate domain format and DNS resolution
        if (!$this->isValidDomainFormat($validated['domain'])) {
            throw new \RuntimeException("Domain {$validated['domain']} has an invalid format.");
        }

        Log::info('Setting up domain for project', [
            'project_id' => $project->id,
            'domain' => $validated['domain'],
        ]);

        // If this is the first domain or marked as primary, ensure no other primary exists
        if ($validated['is_primary'] ?? false) {
            $this->clearPrimaryDomains($project);
        }

        // Create domain record with pending status
        $domain = Domain::create([
            'project_id' => $project->id,
            'domain' => $validated['domain'],
            'is_primary' => $validated['is_primary'] ?? false,
            'ssl_enabled' => $validated['ssl_enabled'] ?? true,
            'ssl_provider' => $validated['ssl_provider'] ?? 'letsencrypt',
            'auto_renew_ssl' => $validated['auto_renew_ssl'] ?? true,
            'dns_configured' => false,
            'status' => 'pending',
            'metadata' => $validated['metadata'] ?? [],
        ]);

        try {
            // Setup SSL if enabled
            if ($domain->ssl_enabled && $project->server) {
                $this->setupSSL($domain, $project);
            }

            // Configure DNS if provider specified
            if (isset($validated['dns_provider'])) {
                $this->configureDNS(
                    $domain,
                    $validated['dns_provider'],
                    $validated['dns_config'] ?? []
                );
            }

            // Verify DNS configuration
            $dnsStatus = $this->verifyDNS($domain);
            $domain->update([
                'dns_configured' => $dnsStatus['is_configured'] ?? false,
                'status' => ($dnsStatus['is_configured'] ?? false) ? 'active' : 'pending',
            ]);

            Log::info('Domain setup completed', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'status' => $domain->status,
            ]);

            $freshDomain = $domain->fresh();
            if (!$freshDomain) {
                throw new \RuntimeException('Failed to refresh domain after creation');
            }

            return $freshDomain;

        } catch (\Exception $e) {
            Log::error('Domain setup failed', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            $domain->update([
                'status' => 'failed',
                'metadata' => array_merge($domain->metadata ?? [], [
                    'setup_error' => $e->getMessage(),
                    'setup_failed_at' => now()->toDateTimeString(),
                ]),
            ]);

            throw $e;
        }
    }

    /**
     * Check domain health (DNS, SSL, accessibility)
     *
     * @param Project $project
     * @return array<int|string, array<string, mixed>>
     */
    public function checkDomainHealth(Project $project): array
    {
        $domains = $project->domains;
        $healthResults = [];

        foreach ($domains as $domain) {
            $healthResults[(string) $domain->id] = [
                'domain' => $domain->domain,
                'dns' => $this->verifyDNS($domain),
                'ssl' => $this->checkSSLHealth($domain),
                'http' => $this->checkHTTPAccessibility($domain),
                'overall_status' => 'unknown',
            ];

            // Determine overall status
            $domainKey = (string) $domain->id;
            $dns = $healthResults[$domainKey]['dns'];
            $ssl = $healthResults[$domainKey]['ssl'];
            $http = $healthResults[$domainKey]['http'];

            $healthResults[$domainKey]['overall_status'] = match (true) {
                !($dns['is_configured'] ?? false) => 'dns_failed',
                $domain->ssl_enabled && !($ssl['is_valid'] ?? false) => 'ssl_failed',
                !($http['is_accessible'] ?? false) => 'http_failed',
                default => 'healthy',
            };
        }

        return $healthResults;
    }

    /**
     * Verify domain DNS is properly configured
     *
     * @param Domain $domain
     * @return array<string, mixed>
     */
    public function verifyDNS(Domain $domain): array
    {
        try {
            Log::info('Verifying DNS for domain', ['domain' => $domain->domain]);

            $project = $domain->project;
            $server = $project?->server;

            if (!$server) {
                return [
                    'is_configured' => false,
                    'error' => 'No server associated with project',
                ];
            }

            // Perform DNS lookup
            $dnsRecords = $this->performDNSLookup($domain->domain);

            if (empty($dnsRecords)) {
                Log::warning('DNS records not found', ['domain' => $domain->domain]);

                return [
                    'is_configured' => false,
                    'error' => 'No DNS records found for domain',
                    'records' => [],
                ];
            }

            // Check if any record points to the server's IP
            $pointsToServer = false;
            foreach ($dnsRecords as $record) {
                if ($record === $server->ip_address) {
                    $pointsToServer = true;
                    break;
                }
            }

            Log::info('DNS verification result', [
                'domain' => $domain->domain,
                'points_to_server' => $pointsToServer,
                'records' => $dnsRecords,
            ]);

            return [
                'is_configured' => $pointsToServer,
                'records' => $dnsRecords,
                'expected_ip' => $server->ip_address,
                'matches_server' => $pointsToServer,
            ];

        } catch (\Exception $e) {
            Log::error('DNS verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'is_configured' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renew SSL certificate for domain
     *
     * @param Domain $domain
     * @return bool
     */
    public function renewSSL(Domain $domain): bool
    {
        try {
            Log::info('Renewing SSL for domain', ['domain_id' => $domain->id]);

            if (!$domain->ssl_enabled) {
                Log::warning('SSL not enabled for domain', ['domain_id' => $domain->id]);
                return false;
            }

            $project = $domain->project;
            $server = $project?->server;

            if (!$server) {
                throw new \RuntimeException('No server associated with project');
            }

            // Find the SSL certificate for this domain
            $certificate = SSLCertificate::where('server_id', $server->id)
                ->where('domain_name', $domain->domain)
                ->first();

            if (!$certificate) {
                Log::info('No existing certificate found, issuing new one', [
                    'domain' => $domain->domain,
                ]);

                return $this->setupSSL($domain, $project);
            }

            // Renew the certificate
            $result = $this->sslService->renewCertificate($certificate);

            if ($result['success']) {
                $freshCertificate = $certificate->fresh();
                if ($freshCertificate) {
                    $domain->update([
                        'ssl_issued_at' => now(),
                        'ssl_expires_at' => $freshCertificate->expires_at,
                    ]);
                }

                Log::info('SSL renewed successfully', [
                    'domain_id' => $domain->id,
                    'domain' => $domain->domain,
                ]);

                return true;
            }

            Log::error('SSL renewal failed', [
                'domain_id' => $domain->id,
                'error' => $result['message'] ?? 'Unknown error',
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('SSL renewal exception', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all domains for a project with their status
     *
     * @param Project $project
     * @return Collection<int, Domain>
     */
    public function getProjectDomains(Project $project): Collection
    {
        return $project->domains()
            ->get()
            ->map(function (Domain $domain) {
                $health = $this->checkDomainHealth($domain->project);

                return $domain->setAttribute(
                    'health_status',
                    $health[$domain->id] ?? ['overall_status' => 'unknown']
                );
            });
    }

    /**
     * Configure DNS for domain
     *
     * @param Domain $domain
     * @param string $provider
     * @param array<string, mixed> $config
     * @return bool
     */
    public function configureDNS(Domain $domain, string $provider, array $config): bool
    {
        try {
            Log::info('Configuring DNS', [
                'domain' => $domain->domain,
                'provider' => $provider,
            ]);

            $result = match ($provider) {
                'cloudflare' => $this->configureCloudflare($domain, $config),
                'route53' => $this->configureRoute53($domain, $config),
                'digitalocean' => $this->configureDigitalOcean($domain, $config),
                default => throw new \InvalidArgumentException("Unsupported DNS provider: {$provider}"),
            };

            if ($result) {
                $domain->update([
                    'dns_configured' => true,
                    'metadata' => array_merge($domain->metadata ?? [], [
                        'dns_provider' => $provider,
                        'dns_configured_at' => now()->toDateTimeString(),
                    ]),
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('DNS configuration failed', [
                'domain' => $domain->domain,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete domain and cleanup
     *
     * @param Domain $domain
     * @return bool
     */
    public function deleteDomain(Domain $domain): bool
    {
        try {
            Log::info('Deleting domain', ['domain_id' => $domain->id]);

            $project = $domain->project;
            $server = $project?->server;

            // Revoke SSL certificate if exists
            if ($domain->ssl_enabled && $server) {
                $certificate = SSLCertificate::where('server_id', $server->id)
                    ->where('domain_name', $domain->domain)
                    ->first();

                if ($certificate) {
                    $this->sslService->revokeCertificate($certificate);
                    $certificate->delete();
                }
            }

            // Remove DNS records if managed
            $metadata = $domain->metadata;
            if ($domain->dns_configured && is_array($metadata) && array_key_exists('dns_provider', $metadata)) {
                /** @var array<string, mixed> $metadata */
                $this->removeDNSRecords(
                    $domain,
                    (string) $metadata['dns_provider'],
                    is_array($metadata['dns_config'] ?? null) ? $metadata['dns_config'] : []
                );
            }

            // Delete domain record
            $domain->delete();

            Log::info('Domain deleted successfully', ['domain_id' => $domain->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Domain deletion failed', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Setup SSL certificate for domain
     *
     * @param Domain $domain
     * @param Project $project
     * @return bool
     */
    protected function setupSSL(Domain $domain, Project $project): bool
    {
        try {
            $server = $project->server;

            if (!$server) {
                throw new \RuntimeException('No server associated with project');
            }

            // Get email from project metadata or use default
            $email = $domain->metadata['ssl_email']
                ?? $project->metadata['admin_email']
                ?? config('mail.from.address', 'admin@devflow.local');

            Log::info('Issuing SSL certificate', [
                'domain' => $domain->domain,
                'email' => $email,
            ]);

            $result = $this->sslService->issueCertificate(
                $server,
                $domain->domain,
                $email
            );

            if ($result['success']) {
                $certificate = $result['certificate'];

                $domain->update([
                    'ssl_certificate' => $certificate->certificate_path,
                    'ssl_private_key' => $certificate->private_key_path,
                    'ssl_issued_at' => $certificate->issued_at,
                    'ssl_expires_at' => $certificate->expires_at,
                ]);

                return true;
            }

            throw new \RuntimeException($result['message'] ?? 'SSL issuance failed');

        } catch (\Exception $e) {
            Log::error('SSL setup failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check SSL health for domain
     *
     * @param Domain $domain
     * @return array<string, mixed>
     */
    protected function checkSSLHealth(Domain $domain): array
    {
        if (!$domain->ssl_enabled) {
            return [
                'is_valid' => true,
                'message' => 'SSL not enabled',
            ];
        }

        if (!$domain->ssl_expires_at) {
            return [
                'is_valid' => false,
                'message' => 'No SSL certificate found',
            ];
        }

        $daysUntilExpiry = $domain->daysUntilExpiry();
        $isExpired = $domain->sslIsExpired();
        $expiresSoon = $domain->sslExpiresSoon();

        return [
            'is_valid' => !$isExpired,
            'expires_at' => $domain->ssl_expires_at,
            'days_until_expiry' => $daysUntilExpiry,
            'is_expired' => $isExpired,
            'expires_soon' => $expiresSoon,
            'should_renew' => $expiresSoon || $isExpired,
        ];
    }

    /**
     * Check HTTP accessibility for domain
     *
     * @param Domain $domain
     * @return array<string, mixed>
     */
    protected function checkHTTPAccessibility(Domain $domain): array
    {
        try {
            $protocol = $domain->ssl_enabled ? 'https' : 'http';
            $url = "{$protocol}://{$domain->domain}";

            $ch = curl_init($url);
            if ($ch === false) {
                throw new \RuntimeException('Failed to initialize cURL');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_NOBODY => true,
            ]);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $isAccessible = $httpCode >= 200 && $httpCode < 400;

            return [
                'is_accessible' => $isAccessible,
                'http_code' => $httpCode,
                'url' => $url,
                'error' => $error ?: null,
            ];

        } catch (\Exception $e) {
            return [
                'is_accessible' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform DNS lookup for domain
     *
     * @param string $domain
     * @return array<int, string>
     */
    protected function performDNSLookup(string $domain): array
    {
        try {
            // Try to get A records
            $records = @dns_get_record($domain, DNS_A);

            if ($records === false || empty($records)) {
                // Try with dig command as fallback
                return $this->performDNSLookupWithDig($domain);
            }

            $ips = [];
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }

            return $ips;

        } catch (\Exception $e) {
            Log::warning('DNS lookup failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Perform DNS lookup using dig command
     *
     * @param string $domain
     * @return array<int, string>
     */
    protected function performDNSLookupWithDig(string $domain): array
    {
        try {
            $process = new Process(['dig', '+short', $domain, 'A']);
            $process->setTimeout(5);
            $process->run();

            if (!$process->isSuccessful()) {
                return [];
            }

            $output = trim($process->getOutput());
            if (empty($output)) {
                return [];
            }

            return array_filter(
                explode("\n", $output),
                fn($line) => filter_var(trim($line), FILTER_VALIDATE_IP) !== false
            );

        } catch (\Exception $e) {
            Log::warning('DNS lookup with dig failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Configure Cloudflare DNS
     *
     * @param Domain $domain
     * @param array<string, mixed> $config
     * @return bool
     */
    protected function configureCloudflare(Domain $domain, array $config): bool
    {
        // Placeholder for Cloudflare DNS configuration
        // This would use Cloudflare API to create DNS records
        Log::info('Cloudflare DNS configuration not yet implemented', [
            'domain' => $domain->domain,
        ]);

        return false;
    }

    /**
     * Configure Route53 DNS
     *
     * @param Domain $domain
     * @param array<string, mixed> $config
     * @return bool
     */
    protected function configureRoute53(Domain $domain, array $config): bool
    {
        // Placeholder for Route53 DNS configuration
        // This would use AWS SDK to create Route53 records
        Log::info('Route53 DNS configuration not yet implemented', [
            'domain' => $domain->domain,
        ]);

        return false;
    }

    /**
     * Configure DigitalOcean DNS
     *
     * @param Domain $domain
     * @param array<string, mixed> $config
     * @return bool
     */
    protected function configureDigitalOcean(Domain $domain, array $config): bool
    {
        // Placeholder for DigitalOcean DNS configuration
        // This would use DigitalOcean API to create DNS records
        Log::info('DigitalOcean DNS configuration not yet implemented', [
            'domain' => $domain->domain,
        ]);

        return false;
    }

    /**
     * Remove DNS records for domain
     *
     * @param Domain $domain
     * @param string $provider
     * @param array<string, mixed> $config
     * @return bool
     */
    protected function removeDNSRecords(Domain $domain, string $provider, array $config): bool
    {
        try {
            Log::info('Removing DNS records', [
                'domain' => $domain->domain,
                'provider' => $provider,
            ]);

            // Provider-specific DNS record removal would go here
            // For now, just log the action
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to remove DNS records', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate domain data
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    protected function validateDomainData(array $data): array
    {
        $validator = Validator::make($data, [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
            ],
            'is_primary' => ['nullable', 'boolean'],
            'ssl_enabled' => ['nullable', 'boolean'],
            'ssl_provider' => ['nullable', 'string', 'in:letsencrypt,custom,cloudflare'],
            'auto_renew_ssl' => ['nullable', 'boolean'],
            'dns_provider' => ['nullable', 'string', 'in:cloudflare,route53,digitalocean'],
            'dns_config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Check if domain is already registered
     *
     * @param string $domain
     * @return bool
     */
    protected function isDomainRegistered(string $domain): bool
    {
        return Domain::where('domain', strtolower($domain))->exists();
    }

    /**
     * Validate domain format
     *
     * @param string $domain
     * @return bool
     */
    protected function isValidDomainFormat(string $domain): bool
    {
        return (bool) preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
            $domain
        );
    }

    /**
     * Clear primary domain status for all other domains in project
     *
     * @param Project $project
     * @return void
     */
    protected function clearPrimaryDomains(Project $project): void
    {
        $project->domains()->where('is_primary', true)->update(['is_primary' => false]);
    }
}
