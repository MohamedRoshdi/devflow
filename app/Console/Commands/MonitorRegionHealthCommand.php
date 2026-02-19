<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RegionHealthCheckJob;
use Illuminate\Console\Command;

/**
 * MonitorRegionHealthCommand - Dispatches the region health check job
 *
 * Designed to be scheduled every 5 minutes to monitor the health
 * of all active regions by aggregating their server statuses.
 *
 * @package App\Console\Commands
 */
class MonitorRegionHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regions:monitor-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor health of all regions by checking server statuses';

    /**
     * Execute the console command.
     *
     * Dispatches the RegionHealthCheckJob to the queue.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        RegionHealthCheckJob::dispatch();

        $this->info('Region health check job dispatched.');

        return self::SUCCESS;
    }
}
