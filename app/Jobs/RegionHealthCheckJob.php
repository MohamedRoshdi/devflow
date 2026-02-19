<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\RegionStatus;
use App\Models\Region;
use App\Services\RegionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RegionHealthCheckJob - Periodic job that checks health of all active regions
 *
 * Iterates through all non-offline regions, aggregates server statuses,
 * and updates region status if all servers are offline (marks region as degraded).
 *
 * @package App\Jobs
 */
class RegionHealthCheckJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Execute the job.
     *
     * Checks health of all non-offline regions and updates their status
     * based on the health of their servers.
     *
     * @param RegionService $regionService The region service
     * @return void
     */
    public function handle(RegionService $regionService): void
    {
        $regions = Region::where('status', '!=', RegionStatus::Offline)->get();

        Log::info('Region health check started', [
            'regions_to_check' => $regions->count(),
        ]);

        $results = [];

        foreach ($regions as $region) {
            try {
                $health = $regionService->getRegionHealthScore($region);

                $results[$region->code] = $health;

                // If all servers are offline and the region is active, mark as degraded
                if ($health['total_count'] > 0 && $health['online_count'] === 0 && $region->status === RegionStatus::Active) {
                    $region->update(['status' => RegionStatus::Degraded]);

                    Log::warning('Region marked as degraded - all servers offline', [
                        'region_id' => $region->id,
                        'region_name' => $region->name,
                        'total_servers' => $health['total_count'],
                    ]);
                }

                // If a degraded region has recovered (all servers online), mark as active
                if ($health['score'] === 100 && $region->status === RegionStatus::Degraded) {
                    $region->update(['status' => RegionStatus::Active]);

                    Log::info('Region recovered - all servers online', [
                        'region_id' => $region->id,
                        'region_name' => $region->name,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to check region health', [
                    'region_id' => $region->id,
                    'region_name' => $region->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Region health check completed', [
            'regions_checked' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable|null $exception The exception that caused the failure
     * @return void
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Region health check job failed', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
