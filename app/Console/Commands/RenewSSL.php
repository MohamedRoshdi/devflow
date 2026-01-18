<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\SSLManagementService;
use Illuminate\Console\Command;

class RenewSSL extends Command
{
    protected $signature = 'ssl:renew
                            {domain? : The domain to renew (omit to renew all expiring)}
                            {--all : Renew all certificates regardless of expiry}
                            {--force : Force renewal even if not expiring soon}';

    protected $description = 'Renew SSL certificate(s)';

    public function __construct(
        protected SSLManagementService $sslService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $domainName = $this->argument('domain');
        $renewAll = $this->option('all');
        $force = $this->option('force');

        if ($domainName) {
            return $this->renewSingleDomain($domainName, (bool) $force);
        }

        if ($renewAll) {
            return $this->renewAllCertificates();
        }

        return $this->renewExpiringCertificates();
    }

    protected function renewSingleDomain(string $domainName, bool $force): int
    {
        $this->info("Renewing SSL certificate for: {$domainName}");

        $domain = Domain::where('domain', $domainName)->first();

        if (! $domain) {
            $this->error("Domain not found: {$domainName}");

            return self::FAILURE;
        }

        if (! $domain->ssl_enabled && ! $force) {
            $this->error("SSL is not enabled for domain: {$domainName}");
            $this->comment('Use --force to attempt renewal anyway');

            return self::FAILURE;
        }

        try {
            $this->info('Starting renewal...');
            $this->sslService->renewCertificate($domain);
            $this->info('✓ Certificate renewed successfully!');

            // Display new expiry date
            $domain->refresh();
            if ($domain->ssl_expires_at) {
                $this->info("New expiry date: {$domain->ssl_expires_at->format('Y-m-d H:i')}");
                $this->info("Days until expiry: {$domain->ssl_expires_at->diffInDays(now())}");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Renewal failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    protected function renewAllCertificates(): int
    {
        $this->warn('Renewing ALL SSL certificates...');
        $this->newLine();

        $domains = Domain::where('ssl_enabled', true)
            ->whereNotNull('ssl_expires_at')
            ->with('project.server')
            ->get();

        if ($domains->isEmpty()) {
            $this->info('No SSL certificates found to renew.');

            return self::SUCCESS;
        }

        $this->info("Found {$domains->count()} certificate(s) to renew.");
        $this->newLine();

        if (! $this->confirm('Do you want to proceed?', true)) {
            $this->warn('Renewal cancelled.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($domains->count());
        $progressBar->start();

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($domains as $domain) {
            try {
                $this->sslService->renewCertificate($domain);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info("✓ Successfully renewed: {$success}");
        if ($failed > 0) {
            $this->error("✗ Failed to renew: {$failed}");
            $this->newLine();
            $this->error('Failed domains:');
            foreach ($errors as $error) {
                $this->line("  - {$error['domain']}: {$error['error']}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function renewExpiringCertificates(): int
    {
        $this->info('Renewing expiring SSL certificates (within 30 days)...');
        $this->newLine();

        $results = $this->sslService->renewExpiringCertificates(30);

        $successCount = count($results['success']);
        $failedCount = count($results['failed']);

        if ($successCount === 0 && $failedCount === 0) {
            $this->info('No expiring certificates found to renew.');

            return self::SUCCESS;
        }

        if ($successCount > 0) {
            $this->info("✓ Successfully renewed {$successCount} certificate(s):");
            foreach ($results['success'] as $result) {
                $this->line("  - {$result['domain']}");
            }
        }

        if ($failedCount > 0) {
            $this->newLine();
            $this->error("✗ Failed to renew {$failedCount} certificate(s):");
            foreach ($results['failed'] as $result) {
                $this->line("  - {$result['domain']}: {$result['error']}");
            }
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
