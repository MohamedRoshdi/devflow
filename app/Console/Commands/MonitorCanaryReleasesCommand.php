<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MonitorCanaryReleaseJob;
use App\Models\CanaryRelease;
use Illuminate\Console\Command;

class MonitorCanaryReleasesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'canary:monitor';

    /**
     * @var string
     */
    protected $description = 'Monitor active canary releases and dispatch monitoring jobs';

    public function handle(): int
    {
        $activeReleases = CanaryRelease::where('status', 'monitoring')->get();

        if ($activeReleases->isEmpty()) {
            $this->info('No active canary releases to monitor.');

            return self::SUCCESS;
        }

        foreach ($activeReleases as $release) {
            MonitorCanaryReleaseJob::dispatch($release);
            $this->info("Dispatched monitor job for canary release #{$release->id}");
        }

        $this->info("Dispatched {$activeReleases->count()} monitoring job(s).");

        return self::SUCCESS;
    }
}
