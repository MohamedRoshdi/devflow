<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Region;
use App\Models\RegionDeployment;
use App\Services\CrossRegionDeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DeployToRegionJob - Deploys to a single region as part of a parallel cross-region deployment
 *
 * This job is dispatched by CrossRegionDeployJob when using the parallel strategy.
 * Each instance handles deployment to one specific region.
 *
 * @package App\Jobs
 */
class DeployToRegionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900; // 15 minutes per region

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @param RegionDeployment $regionDeployment The parent cross-region deployment
     * @param Region $region The region to deploy to
     */
    public function __construct(
        private readonly RegionDeployment $regionDeployment,
        private readonly Region $region
    ) {}

    /**
     * Execute the job.
     *
     * Deploys to the specified region and checks if all parallel deployments have completed.
     *
     * @param CrossRegionDeploymentService $service The deployment service
     * @return void
     */
    public function handle(CrossRegionDeploymentService $service): void
    {
        $service->deployToRegion($this->regionDeployment, $this->region);

        // Check if all regions have completed (for parallel strategy)
        $this->checkOverallCompletion();
    }

    /**
     * Check if all regions in the parallel deployment have completed.
     *
     * If all regions are done, updates the parent RegionDeployment status.
     *
     * @return void
     */
    private function checkOverallCompletion(): void
    {
        $this->regionDeployment->refresh();
        $statuses = $this->regionDeployment->region_statuses ?? [];

        $allComplete = true;
        $anyFailed = false;

        foreach ($statuses as $regionStatus) {
            $status = is_array($regionStatus) ? ($regionStatus['status'] ?? 'pending') : 'pending';

            if (in_array($status, ['pending', 'running'], true)) {
                $allComplete = false;
            }

            if ($status === 'failed') {
                $anyFailed = true;
            }
        }

        if ($allComplete) {
            $finalStatus = $anyFailed ? 'failed' : 'success';

            $this->regionDeployment->update([
                'status' => $finalStatus,
                'completed_at' => now(),
            ]);

            $deployment = $this->regionDeployment->deployment;

            if ($deployment !== null) {
                $deployment->update([
                    'status' => $finalStatus,
                    'completed_at' => now(),
                    'duration_seconds' => $deployment->started_at
                        ? (int) now()->diffInSeconds($deployment->started_at)
                        : 0,
                ]);
            }

            Log::info('Parallel cross-region deployment completed', [
                'region_deployment_id' => $this->regionDeployment->id,
                'final_status' => $finalStatus,
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable|null $exception The exception that caused the failure
     * @return void
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('DeployToRegionJob failed', [
            'region_deployment_id' => $this->regionDeployment->id,
            'region_id' => $this->region->id,
            'region_name' => $this->region->name,
            'error' => $exception?->getMessage(),
        ]);

        // Attempt to check overall completion even on failure
        $this->checkOverallCompletion();
    }
}
