<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\CanaryReleaseStatusUpdated;
use App\Models\CanaryRelease;
use App\Services\CanaryDeploymentService;
use App\Services\CanaryMetricsCollectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorCanaryReleaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public CanaryRelease $canaryRelease
    ) {}

    public function handle(
        CanaryDeploymentService $deploymentService,
        CanaryMetricsCollectorService $metricsCollector
    ): void {
        // Only monitor active canary releases
        if (! $this->canaryRelease->isMonitoring()) {
            return;
        }

        try {
            // Collect metrics
            $metricsCollector->collectMetrics($this->canaryRelease);

            // Evaluate health and decide action
            $evaluation = $deploymentService->evaluateHealth($this->canaryRelease);

            Log::info('Canary release monitored', [
                'canary_release_id' => $this->canaryRelease->id,
                'action' => $evaluation['action'],
                'reason' => $evaluation['reason'],
            ]);

            // Execute action
            match ($evaluation['action']) {
                'advance' => $deploymentService->advanceWeight($this->canaryRelease),
                'rollback' => null, // Already handled in evaluateHealth
                default => null,
            };

            // Broadcast status update
            $this->canaryRelease->refresh();
            broadcast(new CanaryReleaseStatusUpdated($this->canaryRelease));
        } catch (\Exception $e) {
            Log::error('Canary monitoring failed', [
                'canary_release_id' => $this->canaryRelease->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
