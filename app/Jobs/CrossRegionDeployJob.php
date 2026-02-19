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
 * CrossRegionDeployJob - Async job that orchestrates deployment across regions
 *
 * Receives a RegionDeployment model and iterates through regions based on the
 * selected strategy (sequential or parallel).
 *
 * - Sequential: deploys to regions one-by-one in region_order, stops on failure
 * - Parallel: dispatches individual DeployToRegionJob for all regions simultaneously
 *
 * @package App\Jobs
 */
class CrossRegionDeployJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes

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
     * @param RegionDeployment $regionDeployment The cross-region deployment to process
     */
    public function __construct(
        private readonly RegionDeployment $regionDeployment
    ) {}

    /**
     * Execute the job.
     *
     * Routes to the appropriate strategy handler based on the deployment strategy.
     *
     * @param CrossRegionDeploymentService $service The deployment service
     * @return void
     */
    public function handle(CrossRegionDeploymentService $service): void
    {
        $this->regionDeployment->update(['status' => 'running']);

        try {
            $strategy = $this->regionDeployment->strategy ?? 'sequential';

            match ($strategy) {
                'parallel' => $this->handleParallelDeployment(),
                default => $this->handleSequentialDeployment($service),
            };
        } catch (\Exception $e) {
            $this->regionDeployment->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            Log::error('Cross-region deployment job failed', [
                'region_deployment_id' => $this->regionDeployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle sequential deployment strategy.
     *
     * Deploys to each region in order, stopping immediately on failure.
     *
     * @param CrossRegionDeploymentService $service The deployment service
     * @return void
     */
    private function handleSequentialDeployment(CrossRegionDeploymentService $service): void
    {
        $regionOrder = $this->regionDeployment->region_order ?? [];
        $allSuccess = true;

        foreach ($regionOrder as $regionId) {
            $region = Region::find($regionId);

            if ($region === null) {
                Log::warning('Region not found during sequential deployment', [
                    'region_id' => $regionId,
                    'region_deployment_id' => $this->regionDeployment->id,
                ]);
                continue;
            }

            $result = $service->deployToRegion($this->regionDeployment, $region);

            if ($result['status'] === 'failed') {
                $allSuccess = false;

                Log::warning('Sequential deployment stopped due to region failure', [
                    'region_deployment_id' => $this->regionDeployment->id,
                    'failed_region_id' => $region->id,
                    'failed_region_name' => $region->name,
                ]);

                break; // Stop on failure for sequential strategy
            }
        }

        $this->regionDeployment->update([
            'status' => $allSuccess ? 'success' : 'failed',
            'completed_at' => now(),
        ]);

        $deployment = $this->regionDeployment->deployment;

        if ($deployment !== null) {
            $deployment->update([
                'status' => $allSuccess ? 'success' : 'failed',
                'completed_at' => now(),
                'duration_seconds' => $deployment->started_at
                    ? (int) now()->diffInSeconds($deployment->started_at)
                    : 0,
            ]);
        }
    }

    /**
     * Handle parallel deployment strategy.
     *
     * Dispatches a separate DeployToRegionJob for each region in the order list.
     *
     * @return void
     */
    private function handleParallelDeployment(): void
    {
        $regionOrder = $this->regionDeployment->region_order ?? [];

        foreach ($regionOrder as $regionId) {
            $region = Region::find($regionId);

            if ($region === null) {
                continue;
            }

            DeployToRegionJob::dispatch($this->regionDeployment, $region);
        }

        Log::info('Parallel deployment jobs dispatched', [
            'region_deployment_id' => $this->regionDeployment->id,
            'region_count' => count($regionOrder),
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
        $this->regionDeployment->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);

        $deployment = $this->regionDeployment->deployment;

        if ($deployment !== null) {
            $deployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => $exception?->getMessage() ?? 'Cross-region deployment job failed unexpectedly',
            ]);
        }

        Log::error('Cross-region deployment job failed', [
            'region_deployment_id' => $this->regionDeployment->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
