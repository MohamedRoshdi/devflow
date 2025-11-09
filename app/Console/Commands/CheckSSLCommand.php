<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain;
use App\Services\SSLService;

class CheckSSLCommand extends Command
{
    protected $signature = 'devflow:check-ssl';
    protected $description = 'Check SSL certificates and renew if needed';

    public function __construct(
        protected SSLService $sslService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Checking SSL certificates...');

        $result = $this->sslService->checkExpiringCertificates();

        if (count($result['renewed']) > 0) {
            $this->info('Renewed certificates for:');
            foreach ($result['renewed'] as $domain) {
                $this->info("  ✓ {$domain}");
            }
        }

        if (count($result['failed']) > 0) {
            $this->error('Failed to renew certificates for:');
            foreach ($result['failed'] as $failure) {
                $this->error("  ✗ {$failure['domain']}: {$failure['error']}");
            }
        }

        if (count($result['renewed']) === 0 && count($result['failed']) === 0) {
            $this->info('No certificates need renewal at this time.');
        }

        return 0;
    }
}

