<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanaryRelease;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CanaryDeploymentService
{
    public function __construct(
        private readonly CanaryNginxService $nginxService,
        private readonly CanaryMetricsCollectorService $metricsCollector
    ) {}

    /**
     * Initiate a canary release for a project.
     */
    public function initiate(Project $project, Deployment $deployment, ?array $weightSchedule = null): CanaryRelease
    {
        return DB::transaction(function () use ($project, $deployment, $weightSchedule): CanaryRelease {
            /** @var array<int, array{weight: int, duration_minutes: int}> $defaultSchedule */
            $defaultSchedule = $weightSchedule ?? config('devflow.canary.default_weight_schedule', [
                ['weight' => 10, 'duration_minutes' => 5],
                ['weight' => 25, 'duration_minutes' => 10],
                ['weight' => 50, 'duration_minutes' => 10],
                ['weight' => 100, 'duration_minutes' => 0],
            ]);

            // Get current stable version
            $latestSuccess = $project->deployments()
                ->where('status', 'success')
                ->where('is_canary', false)
                ->latest()
                ->first();

            $canaryRelease = CanaryRelease::create([
                'project_id' => $project->id,
                'deployment_id' => $deployment->id,
                'stable_version' => $latestSuccess?->commit_hash,
                'canary_version' => $deployment->commit_hash,
                'status' => 'pending',
                'current_weight' => 0,
                'weight_schedule' => $defaultSchedule,
                'current_step' => 0,
                'error_rate_threshold' => (float) config('devflow.canary.error_rate_threshold', 5.00),
                'response_time_threshold' => (int) config('devflow.canary.response_time_threshold', 2000),
                'auto_promote' => true,
                'auto_rollback' => true,
            ]);

            $deployment->update([
                'is_canary' => true,
                'canary_release_id' => $canaryRelease->id,
            ]);

            Log::info('Canary release initiated', [
                'canary_release_id' => $canaryRelease->id,
                'project_id' => $project->id,
            ]);

            return $canaryRelease;
        });
    }

    /**
     * Start the canary release - deploy canary container and set initial weight.
     */
    public function start(CanaryRelease $canaryRelease): void
    {
        $canaryRelease->update([
            'status' => 'deploying',
            'started_at' => now(),
        ]);

        try {
            $project = $canaryRelease->project;
            if ($project === null) {
                throw new \RuntimeException('Project not found for canary release');
            }

            // Set initial weight from schedule
            /** @var array<int, array{weight: int, duration_minutes: int}>|null $schedule */
            $schedule = $canaryRelease->weight_schedule;
            $initialWeight = $schedule[0]['weight'] ?? 10;

            // Configure nginx for traffic splitting
            $this->nginxService->configureWeightedRouting(
                $project,
                $initialWeight
            );

            $canaryRelease->update([
                'status' => 'monitoring',
                'current_weight' => $initialWeight,
                'current_step' => 0,
            ]);

            Log::info('Canary release started', [
                'canary_release_id' => $canaryRelease->id,
                'initial_weight' => $initialWeight,
            ]);
        } catch (\Exception $e) {
            $canaryRelease->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Advance the canary weight to the next step.
     */
    public function advanceWeight(CanaryRelease $canaryRelease): bool
    {
        $project = $canaryRelease->project;
        if ($project === null) {
            throw new \RuntimeException('Project not found for canary release');
        }

        /** @var array<int, array{weight: int, duration_minutes: int}>|null $schedule */
        $schedule = $canaryRelease->weight_schedule;

        if ($schedule === null || $schedule === []) {
            return false;
        }

        $nextStep = $canaryRelease->current_step + 1;

        if ($nextStep >= count($schedule)) {
            // All steps completed, promote
            $this->promote($canaryRelease);

            return true;
        }

        $nextWeight = $schedule[$nextStep]['weight'];

        $this->nginxService->configureWeightedRouting(
            $project,
            $nextWeight
        );

        $canaryRelease->update([
            'current_weight' => $nextWeight,
            'current_step' => $nextStep,
        ]);

        Log::info('Canary weight advanced', [
            'canary_release_id' => $canaryRelease->id,
            'new_weight' => $nextWeight,
            'step' => $nextStep,
        ]);

        // If weight is 100%, promote
        if ($nextWeight >= 100) {
            $this->promote($canaryRelease);

            return true;
        }

        return false;
    }

    /**
     * Promote the canary to stable (100% traffic, remove old stable).
     */
    public function promote(CanaryRelease $canaryRelease): void
    {
        $canaryRelease->update(['status' => 'promoting']);

        $project = $canaryRelease->project;
        if ($project === null) {
            $canaryRelease->update(['status' => 'failed']);
            throw new \RuntimeException('Project not found for canary release');
        }

        try {
            // Route 100% traffic to canary
            $this->nginxService->configureWeightedRouting($project, 100);

            $canaryRelease->update([
                'status' => 'completed',
                'current_weight' => 100,
                'promoted_at' => now(),
                'completed_at' => now(),
            ]);

            Log::info('Canary release promoted', [
                'canary_release_id' => $canaryRelease->id,
                'project_id' => $canaryRelease->project_id,
            ]);
        } catch (\Exception $e) {
            $canaryRelease->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Rollback the canary - route 100% to stable, remove canary.
     */
    public function rollback(CanaryRelease $canaryRelease, string $reason = 'manual'): void
    {
        $canaryRelease->update(['status' => 'rolling_back']);

        $project = $canaryRelease->project;
        if ($project === null) {
            $canaryRelease->update(['status' => 'failed']);
            throw new \RuntimeException('Project not found for canary release');
        }

        try {
            // Route 100% to stable (weight = 0 for canary)
            $this->nginxService->configureWeightedRouting($project, 0);

            /** @var array<string, mixed> $existingMetadata */
            $existingMetadata = $canaryRelease->metadata ?? [];

            $canaryRelease->update([
                'status' => 'rolled_back',
                'current_weight' => 0,
                'rolled_back_at' => now(),
                'completed_at' => now(),
                'metadata' => array_merge($existingMetadata, [
                    'rollback_reason' => $reason,
                ]),
            ]);

            Log::info('Canary release rolled back', [
                'canary_release_id' => $canaryRelease->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            $canaryRelease->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Evaluate canary health and decide whether to advance, hold, or rollback.
     *
     * @return array{action: string, reason: string}
     */
    public function evaluateHealth(CanaryRelease $canaryRelease): array
    {
        $metrics = $this->metricsCollector->getLatestMetrics($canaryRelease);

        if ($metrics === null) {
            return ['action' => 'hold', 'reason' => 'No metrics available yet'];
        }

        $canaryMetrics = $metrics['canary'];

        // Check error rate threshold
        if ($canaryMetrics !== null && ((float) $canaryMetrics->error_rate) > ((float) $canaryRelease->error_rate_threshold)) {
            if ($canaryRelease->auto_rollback) {
                $this->rollback(
                    $canaryRelease,
                    "Error rate {$canaryMetrics->error_rate}% exceeded threshold {$canaryRelease->error_rate_threshold}%"
                );

                return [
                    'action' => 'rollback',
                    'reason' => "Error rate exceeded threshold: {$canaryMetrics->error_rate}%",
                ];
            }

            return ['action' => 'hold', 'reason' => "Error rate high: {$canaryMetrics->error_rate}%"];
        }

        // Check response time threshold
        if ($canaryMetrics !== null && $canaryMetrics->avg_response_time_ms > $canaryRelease->response_time_threshold) {
            if ($canaryRelease->auto_rollback) {
                $this->rollback(
                    $canaryRelease,
                    "Response time {$canaryMetrics->avg_response_time_ms}ms exceeded threshold {$canaryRelease->response_time_threshold}ms"
                );

                return [
                    'action' => 'rollback',
                    'reason' => "Response time exceeded threshold: {$canaryMetrics->avg_response_time_ms}ms",
                ];
            }

            return ['action' => 'hold', 'reason' => "Response time high: {$canaryMetrics->avg_response_time_ms}ms"];
        }

        // Check if enough time has passed for current step
        /** @var array<int, array{weight: int, duration_minutes: int}>|null $schedule */
        $schedule = $canaryRelease->weight_schedule;
        $currentStep = $canaryRelease->current_step;

        if ($schedule !== null && isset($schedule[$currentStep])) {
            $durationMinutes = $schedule[$currentStep]['duration_minutes'] ?? 0;
            if ($durationMinutes > 0) {
                $stepStartedAt = $canaryRelease->updated_at;
                if ($stepStartedAt !== null && $stepStartedAt->diffInMinutes(now()) < $durationMinutes) {
                    return ['action' => 'hold', 'reason' => 'Waiting for step duration to complete'];
                }
            }
        }

        // Canary is healthy, advance to next step
        if ($canaryRelease->auto_promote) {
            return ['action' => 'advance', 'reason' => 'Canary is healthy, advancing weight'];
        }

        return ['action' => 'hold', 'reason' => 'Canary is healthy, waiting for manual promotion'];
    }
}
