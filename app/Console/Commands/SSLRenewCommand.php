<?php

namespace App\Console\Commands;

use App\Models\SSLCertificate;
use App\Services\SSLService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SSLRenewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssl:renew-expiring {--days=7 : Number of days before expiry to renew}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew SSL certificates that are expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(SSLService $sslService): int
    {
        $days = (int) $this->option('days');

        $this->info("Looking for SSL certificates expiring within {$days} days...");

        // Find certificates that need renewal
        $certificates = SSLCertificate::where('auto_renew', true)
            ->where('status', '!=', 'revoked')
            ->where(function ($query) {
                $query->where('status', 'expired')
                    ->orWhereNotNull('expires_at');
            })
            ->with('server')
            ->get()
            ->filter(function ($cert) use ($days) {
                return $cert->needsRenewal($days);
            });

        if ($certificates->isEmpty()) {
            $this->info('No certificates need renewal at this time.');

            return Command::SUCCESS;
        }

        $this->info("Found {$certificates->count()} certificate(s) to renew.");

        $renewed = 0;
        $failed = 0;

        foreach ($certificates as $certificate) {
            $this->line("Renewing certificate for {$certificate->domain_name}...");

            try {
                $result = $sslService->renewCertificate($certificate);

                if ($result['success']) {
                    $this->info("✓ Successfully renewed certificate for {$certificate->domain_name}");
                    $renewed++;
                } else {
                    $this->error("✗ Failed to renew certificate for {$certificate->domain_name}");
                    $this->error("  Error: {$result['message']}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Exception while renewing {$certificate->domain_name}: {$e->getMessage()}");
                $failed++;

                Log::error('SSL renewal exception', [
                    'certificate_id' => $certificate->id,
                    'domain' => $certificate->domain_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Renewal complete:');
        $this->info("  Renewed: {$renewed}");
        if ($failed > 0) {
            $this->error("  Failed: {$failed}");
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
