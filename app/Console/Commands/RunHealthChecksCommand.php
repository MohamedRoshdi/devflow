<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HealthCheckService;
use Illuminate\Console\Command;

class RunHealthChecksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all due health checks and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(HealthCheckService $healthCheckService): int
    {
        $this->info('Starting health checks...');

        try {
            $runCount = $healthCheckService->runDueChecks();

            $this->info("Health checks completed. Ran {$runCount} check(s).");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Health checks failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
