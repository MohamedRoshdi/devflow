<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SSLManagementService;
use App\Notifications\SSLCertificateExpiring;
use Illuminate\Console\Command;

class CheckSSLExpiry extends Command
{
    protected $signature = 'ssl:check-expiry
                            {--days=30 : Check for certificates expiring within this many days}
                            {--renew : Automatically renew expiring certificates}';

    protected $description = 'Check for expiring SSL certificates and optionally renew them';

    public function __construct(
        protected SSLManagementService $sslService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');
        $autoRenew = $this->option('renew');

        $this->info("Checking for SSL certificates expiring within {$daysThreshold} days...");
        $this->newLine();

        $expiringCertificates = $this->sslService->getExpiringCertificates($daysThreshold);

        if ($expiringCertificates->isEmpty()) {
            $this->info('✓ No expiring certificates found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$expiringCertificates->count()} expiring certificate(s):");
        $this->newLine();

        $tableData = $expiringCertificates->map(function ($certificate) {
            $daysLeft = $certificate->daysUntilExpiry();
            $status = match (true) {
                $daysLeft === 0 => '🔴 EXPIRED',
                $daysLeft <= 7 => '🟠 CRITICAL',
                $daysLeft <= 14 => '🟡 WARNING',
                default => '🟢 OK',
            };

            return [
                $certificate->domain_name,
                $certificate->server->name ?? 'N/A',
                $certificate->expires_at?->format('Y-m-d H:i') ?? 'Unknown',
                $daysLeft !== null ? "{$daysLeft} days" : 'Unknown',
                $status,
                $certificate->auto_renew ? 'Yes' : 'No',
            ];
        })->toArray();

        $this->table(
            ['Domain', 'Server', 'Expires At', 'Days Left', 'Status', 'Auto-Renew'],
            $tableData
        );

        $this->newLine();

        // Send notifications for critical certificates (< 7 days)
        $criticalCertificates = $expiringCertificates->filter(function ($cert) {
            $daysLeft = $cert->daysUntilExpiry();
            return $daysLeft !== null && $daysLeft <= 7;
        });

        if ($criticalCertificates->isNotEmpty()) {
            foreach ($criticalCertificates as $certificate) {
                if ($certificate->server && $certificate->server->user) {
                    try {
                        $certificate->server->user->notify(
                            new SSLCertificateExpiring($certificate)
                        );
                    } catch (\Exception $e) {
                        $this->warn("Failed to send notification for {$certificate->domain_name}: {$e->getMessage()}");
                    }
                }
            }
            $this->info("Sent notifications for {$criticalCertificates->count()} critical certificate(s).");
        }

        // Auto-renew if requested
        if ($autoRenew) {
            $this->newLine();
            $this->info('Starting automatic renewal...');
            $this->newLine();

            $results = $this->sslService->renewExpiringCertificates($daysThreshold);

            if (!empty($results['success'])) {
                $this->info("✓ Successfully renewed {$results['success']->count()} certificate(s):");
                foreach ($results['success'] as $result) {
                    $this->line("  - {$result['domain']}");
                }
            }

            if (!empty($results['failed'])) {
                $this->newLine();
                $this->error("✗ Failed to renew {$results['failed']->count()} certificate(s):");
                foreach ($results['failed'] as $result) {
                    $this->line("  - {$result['domain']}: {$result['error']}");
                }
            }
        } else {
            $this->comment('Tip: Use --renew to automatically renew expiring certificates');
        }

        return self::SUCCESS;
    }
}
