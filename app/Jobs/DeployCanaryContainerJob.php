<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CanaryRelease;
use App\Services\CanaryDeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployCanaryContainerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1200;

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

    public function handle(CanaryDeploymentService $deploymentService): void
    {
        try {
            $deploymentService->start($this->canaryRelease);

            Log::info('Canary container deployed and monitoring started', [
                'canary_release_id' => $this->canaryRelease->id,
            ]);
        } catch (\Exception $e) {
            /** @var array<string, mixed> $existingMetadata */
            $existingMetadata = $this->canaryRelease->metadata ?? [];

            $this->canaryRelease->update([
                'status' => 'failed',
                'completed_at' => now(),
                'metadata' => array_merge($existingMetadata, [
                    'deploy_error' => $e->getMessage(),
                ]),
            ]);

            Log::error('Canary container deployment failed', [
                'canary_release_id' => $this->canaryRelease->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->canaryRelease->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
