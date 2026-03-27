<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;

/**
 * Checks DNS resolution health for domains and subdomains.
 *
 * Used to verify wildcard DNS and specific subdomain records
 * resolve to the expected server IP.
 */
final class DnsHealthService
{
    /**
     * Check if a wildcard domain resolves correctly by using a random test subdomain.
     *
     * @return array{hostname: string, resolved: bool, actual_ip: string|null, expected_ip: string, matches: bool, records: array<int, array<string, mixed>>}
     */
    public function checkWildcardResolution(string $baseDomain, string $expectedIp): array
    {
        $testSubdomain = '_devflow-check-'.substr(md5((string) time()), 0, 8).'.'.$baseDomain;

        return $this->checkResolution($testSubdomain, $expectedIp);
    }

    /**
     * Check if a specific hostname resolves to the expected IP address.
     *
     * @return array{hostname: string, resolved: bool, actual_ip: string|null, expected_ip: string, matches: bool, records: array<int, array<string, mixed>>}
     */
    public function checkResolution(string $hostname, string $expectedIp): array
    {
        try {
            $records = @dns_get_record($hostname, DNS_A | DNS_AAAA | DNS_CNAME);
            $resolved = $records !== false && count($records) > 0;

            $actualIp = null;

            if ($resolved && $records !== false) {
                foreach ($records as $record) {
                    if (isset($record['ip'])) {
                        $actualIp = $record['ip'];
                        break;
                    }

                    if (isset($record['ipv6'])) {
                        $actualIp = $record['ipv6'];
                        break;
                    }
                }
            }

            return [
                'hostname' => $hostname,
                'resolved' => $resolved,
                'actual_ip' => $actualIp,
                'expected_ip' => $expectedIp,
                'matches' => $actualIp === $expectedIp,
                'records' => ($records ?: []),
            ];
        } catch (\Exception $e) {
            Log::warning('DnsHealthService: failed to resolve hostname', [
                'hostname' => $hostname,
                'error' => $e->getMessage(),
            ]);

            return [
                'hostname' => $hostname,
                'resolved' => false,
                'actual_ip' => null,
                'expected_ip' => $expectedIp,
                'matches' => false,
                'records' => [],
            ];
        }
    }

    /**
     * Get a health summary for a domain infrastructure including wildcard and specific subdomains.
     *
     * @param  array<int, string>  $subdomainsToCheck  Subdomain labels (without the base domain)
     * @return array{status: string, issues: array<int, string>, wildcard: array<string, mixed>, subdomains: array<string, array<string, mixed>>, checked_at: string}
     */
    public function getHealthSummary(string $baseDomain, string $expectedIp, array $subdomainsToCheck = []): array
    {
        $issues = [];

        // Check wildcard
        $wildcard = $this->checkWildcardResolution($baseDomain, $expectedIp);

        if (! $wildcard['resolved']) {
            $issues[] = "Wildcard *.{$baseDomain} does not resolve";
        } elseif (! $wildcard['matches']) {
            $issues[] = "Wildcard resolves to {$wildcard['actual_ip']} instead of {$expectedIp}";
        }

        // Check specific subdomains
        $subdomainResults = [];

        foreach ($subdomainsToCheck as $sub) {
            $result = $this->checkResolution($sub.'.'.$baseDomain, $expectedIp);
            $subdomainResults[$sub] = $result;

            if (! $result['resolved']) {
                $issues[] = "{$sub}.{$baseDomain} does not resolve";
            } elseif (! $result['matches']) {
                $issues[] = "{$sub}.{$baseDomain} resolves to {$result['actual_ip']} instead of {$expectedIp}";
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'critical',
            'issues' => $issues,
            'wildcard' => $wildcard,
            'subdomains' => $subdomainResults,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
