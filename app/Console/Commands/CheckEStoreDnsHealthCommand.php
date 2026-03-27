<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Monitoring\DnsHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Checks DNS health for the estore domain — wildcard and known subdomains.
 *
 * Scheduled every 10 minutes via routes/console.php.
 */
class CheckEStoreDnsHealthCommand extends Command
{
    protected $signature = 'estore:check-dns
                            {--server= : Check against a specific server ID (uses its IP as expected)}
                            {--domain=store-eg.com : Base domain to check}
                            {--ip= : Expected IP address (overrides server lookup)}
                            {--alert : Send alert notifications on failure (default: only log)}';

    protected $description = 'Check DNS resolution health for the estore domain and known subdomains';

    /** @var array<int, string> Known subdomains to verify */
    private const KNOWN_SUBDOMAINS = [
        'laptop',
        'mobile',
        'nora-accessories',
        'promax-tech',
        'demo',
    ];

    public function __construct(private readonly DnsHealthService $dnsHealth)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $baseDomain = (string) $this->option('domain');
        $expectedIp = $this->resolveExpectedIp($baseDomain);

        if ($expectedIp === null) {
            $this->error('Could not determine expected IP. Pass --ip=x.x.x.x or --server=ID.');

            return self::FAILURE;
        }

        $this->info("Checking DNS health for {$baseDomain} (expected: {$expectedIp})...");
        $this->newLine();

        $summary = $this->dnsHealth->getHealthSummary($baseDomain, $expectedIp, self::KNOWN_SUBDOMAINS);

        $this->renderWildcardResult($baseDomain, $summary['wildcard']);
        $this->renderSubdomainResults($baseDomain, $summary['subdomains']);

        if ($summary['status'] === 'healthy') {
            $this->newLine();
            $this->info('DNS health: all checks passed.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('DNS health: issues detected:');

        foreach ($summary['issues'] as $issue) {
            $this->line("  <fg=red>✗</> {$issue}");
        }

        Log::warning('CheckEStoreDnsHealthCommand: DNS issues detected', [
            'domain' => $baseDomain,
            'expected_ip' => $expectedIp,
            'issues' => $summary['issues'],
        ]);

        if ($this->option('alert')) {
            $this->sendAlert($baseDomain, $summary['issues']);
        }

        return self::FAILURE;
    }

    private function resolveExpectedIp(string $baseDomain): ?string
    {
        $ip = $this->option('ip');

        if ($ip !== null) {
            return (string) $ip;
        }

        $serverId = $this->option('server');

        if ($serverId !== null) {
            $server = Server::find((int) $serverId);

            return $server?->ip_address;
        }

        // Fall back to config or env
        $configIp = config('services.estore.server_ip');

        if ($configIp) {
            return (string) $configIp;
        }

        // Try to resolve base domain itself as a fallback reference
        $records = @dns_get_record($baseDomain, DNS_A);

        if ($records !== false && isset($records[0]['ip'])) {
            return $records[0]['ip'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function renderWildcardResult(string $baseDomain, array $result): void
    {
        $label = "  *.{$baseDomain}";

        if (! $result['resolved']) {
            $this->line("{$label}: <fg=red>✗ does not resolve</>");
        } elseif (! $result['matches']) {
            $this->line("{$label}: <fg=yellow>⚠ resolves to {$result['actual_ip']} (expected {$result['expected_ip']})</>");
        } else {
            $this->line("{$label}: <fg=green>✓ {$result['actual_ip']}</>");
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $subdomainResults
     */
    private function renderSubdomainResults(string $baseDomain, array $subdomainResults): void
    {
        foreach ($subdomainResults as $sub => $result) {
            $label = "  {$sub}.{$baseDomain}";

            if (! $result['resolved']) {
                $this->line("{$label}: <fg=red>✗ does not resolve</>");
            } elseif (! $result['matches']) {
                $this->line("{$label}: <fg=yellow>⚠ resolves to {$result['actual_ip']} (expected {$result['expected_ip']})</>");
            } else {
                $this->line("{$label}: <fg=green>✓ {$result['actual_ip']}</>");
            }
        }
    }

    /**
     * @param  array<int, string>  $issues
     */
    private function sendAlert(string $baseDomain, array $issues): void
    {
        $issueLines = array_map(fn (string $i): string => "  - {$i}", $issues);

        $body = implode("\n", [
            "DNS Health Alert — {$baseDomain}",
            '',
            'The following DNS issues were detected:',
            implode("\n", $issueLines),
            '',
            'Please review your DNS configuration and nginx wildcard setup in DevFlow Pro.',
        ]);

        $adminEmail = config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        try {
            Mail::raw($body, function ($message) use ($adminEmail, $baseDomain): void {
                $message->to($adminEmail)
                    ->subject("[DevFlow] DNS Alert — {$baseDomain}: resolution issues detected");
            });
        } catch (\Exception $e) {
            Log::error('CheckEStoreDnsHealthCommand: failed to send alert email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
