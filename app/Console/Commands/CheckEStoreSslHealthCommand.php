<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Monitoring\SSLCertMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Checks SSL certificate health for the estore origin server and known public subdomains.
 *
 * Scheduled daily at 04:00 via routes/console.php.
 */
class CheckEStoreSslHealthCommand extends Command
{
    protected $signature = 'estore:check-ssl
                            {--server= : Server ID to check origin cert on (required unless --skip-origin)}
                            {--skip-origin : Skip origin cert check, only check public subdomains}
                            {--alert : Send alert notifications on failure (default: only log)}';

    protected $description = 'Check SSL certificate expiry for the estore origin server and public subdomains';

    /** @var array<int, string> Fully-qualified hostnames to check via openssl s_client */
    private const PUBLIC_SUBDOMAINS = [
        'store-eg.com',
        'demo.store-eg.com',
        'laptop.store-eg.com',
        'mobile.store-eg.com',
        'nora-accessories.store-eg.com',
        'promax-tech.store-eg.com',
    ];

    public function __construct(private readonly SSLCertMonitorService $sslMonitor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $serverId = $this->option('server');
        $skipOrigin = (bool) $this->option('skip-origin');

        $server = null;

        if (! $skipOrigin) {
            if ($serverId === null) {
                // Fall back to first online server
                $server = Server::where('status', 'online')->first();

                if ($server === null) {
                    $this->warn('No online servers found. Pass --server=ID or --skip-origin.');

                    return self::FAILURE;
                }
            } else {
                $server = Server::where('status', 'online')->find((int) $serverId);

                if ($server === null) {
                    $this->error("Server #{$serverId} not found or is not online.");

                    return self::FAILURE;
                }
            }
        }

        $this->info('Checking SSL certificate health...');
        $this->newLine();

        if ($server !== null) {
            $summary = $this->sslMonitor->getHealthSummary($server, self::PUBLIC_SUBDOMAINS);
        } else {
            // Skip origin — check only public subdomains
            $summary = [
                'status' => 'healthy',
                'issues' => [],
                'origin_cert' => ['is_valid' => true, 'is_expiring_soon' => false, 'error' => null, 'days_remaining' => null],
                'subdomains' => [],
                'checked_at' => now()->toIso8601String(),
            ];

            foreach (self::PUBLIC_SUBDOMAINS as $hostname) {
                $result = $this->sslMonitor->checkSubdomainSsl($hostname);
                $summary['subdomains'][$hostname] = $result;

                if (! $result['is_valid']) {
                    $summary['issues'][] = "{$hostname}: SSL invalid — ".($result['error'] ?? 'unknown');
                    $summary['status'] = 'critical';
                } elseif ($result['is_expiring_soon']) {
                    $summary['issues'][] = "{$hostname}: expires in {$result['days_remaining']} day(s)";
                    $summary['status'] = 'critical';
                }
            }
        }

        $this->renderOriginResult($summary['origin_cert']);
        $this->renderSubdomainResults($summary['subdomains']);

        if ($summary['status'] === 'healthy') {
            $this->newLine();
            $this->info('SSL health: all certificates are valid.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('SSL health: issues detected:');

        foreach ($summary['issues'] as $issue) {
            $this->line("  <fg=red>✗</> {$issue}");
        }

        Log::warning('CheckEStoreSslHealthCommand: SSL issues detected', [
            'server_id' => $server?->id,
            'issues' => $summary['issues'],
        ]);

        if ($this->option('alert')) {
            $this->sendAlert($summary['issues'], $server?->name ?? 'unknown');
        }

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $cert
     */
    private function renderOriginResult(array $cert): void
    {
        if ($cert['days_remaining'] === null && $cert['error'] === null) {
            return; // Skipped
        }

        $label = '  Origin cert';

        if (! $cert['is_valid']) {
            $this->line("{$label}: <fg=red>✗ invalid — {$cert['error']}</>");
        } elseif ($cert['is_expiring_soon']) {
            $this->line("{$label}: <fg=yellow>⚠ expires in {$cert['days_remaining']} day(s) ({$cert['expiry_date']})</>");
        } else {
            $this->line("{$label}: <fg=green>✓ valid — expires {$cert['expiry_date']} ({$cert['days_remaining']} days remaining)</>");
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $subdomainResults
     */
    private function renderSubdomainResults(array $subdomainResults): void
    {
        foreach ($subdomainResults as $hostname => $result) {
            $label = "  {$hostname}";

            if (! $result['is_valid']) {
                $this->line("{$label}: <fg=red>✗ invalid — {$result['error']}</>");
            } elseif ($result['is_expiring_soon']) {
                $this->line("{$label}: <fg=yellow>⚠ expires in {$result['days_remaining']} day(s) ({$result['expiry_date']})</>");
            } else {
                $this->line("{$label}: <fg=green>✓ valid — expires {$result['expiry_date']} ({$result['days_remaining']} days remaining)</>");
            }
        }
    }

    /**
     * @param  array<int, string>  $issues
     */
    private function sendAlert(array $issues, string $serverName): void
    {
        $issueLines = array_map(fn (string $i): string => "  - {$i}", $issues);

        $body = implode("\n", [
            "SSL Certificate Alert — {$serverName}",
            '',
            'The following SSL issues were detected:',
            implode("\n", $issueLines),
            '',
            'Please review your SSL certificates in DevFlow Pro or renew via: php artisan ssl:renew',
        ]);

        $adminEmail = config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        try {
            Mail::raw($body, function ($message) use ($adminEmail, $serverName): void {
                $message->to($adminEmail)
                    ->subject("[DevFlow] SSL Alert — {$serverName}: certificate issues detected");
            });
        } catch (\Exception $e) {
            Log::error('CheckEStoreSslHealthCommand: failed to send alert email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
