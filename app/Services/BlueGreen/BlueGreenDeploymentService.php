<?php

declare(strict_types=1);

namespace App\Services\BlueGreen;

use App\Models\BlueGreenEnvironment;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlueGreenDeploymentService
{
    public function __construct(
        private readonly BlueGreenEnvironmentService $environmentService,
        private readonly BlueGreenHealthCheckService $healthCheckService,
        private readonly BlueGreenTrafficService $trafficService
    ) {}

    /**
     * Initialize blue-green environments for a project.
     * Creates both blue and green Docker Compose stacks.
     */
    public function initialize(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            // Create blue environment
            BlueGreenEnvironment::updateOrCreate(
                ['project_id' => $project->id, 'environment' => 'blue'],
                [
                    'status' => 'inactive',
                    'port' => $this->environmentService->assignPort($project, 'blue'),
                    'health_status' => 'unknown',
                ]
            );

            // Create green environment
            BlueGreenEnvironment::updateOrCreate(
                ['project_id' => $project->id, 'environment' => 'green'],
                [
                    'status' => 'inactive',
                    'port' => $this->environmentService->assignPort($project, 'green'),
                    'health_status' => 'unknown',
                ]
            );

            // Set project deployment strategy
            $project->update([
                'deployment_strategy' => 'blue_green',
                'active_environment' => 'blue',
            ]);

            Log::info('Blue-green environments initialized', ['project_id' => $project->id]);
        });
    }

    /**
     * Deploy to the standby (inactive) environment.
     */
    public function deploy(Project $project, Deployment $deployment): BlueGreenEnvironment
    {
        $activeEnv = $project->active_environment ?? 'blue';
        $targetEnv = $activeEnv === 'blue' ? 'green' : 'blue';

        /** @var BlueGreenEnvironment $environment */
        $environment = BlueGreenEnvironment::where('project_id', $project->id)
            ->where('environment', $targetEnv)
            ->firstOrFail();

        try {
            $environment->update(['status' => 'deploying']);

            $deployment->update([
                'deployment_strategy' => 'blue_green',
                'target_environment' => $targetEnv,
            ]);

            // Build and start the standby environment
            $this->environmentService->buildEnvironment($project, $environment);

            // Update commit hash
            $environment->update([
                'status' => 'inactive',
                'commit_hash' => $deployment->commit_hash,
            ]);

            Log::info('Blue-green deployment completed to standby', [
                'project_id' => $project->id,
                'environment' => $targetEnv,
            ]);

            return $environment;
        } catch (\Exception $e) {
            $environment->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Switch traffic from active to standby environment.
     */
    public function switchTraffic(Project $project): BlueGreenEnvironment
    {
        $activeEnv = $project->active_environment ?? 'blue';
        $targetEnv = $activeEnv === 'blue' ? 'green' : 'blue';

        /** @var BlueGreenEnvironment $standby */
        $standby = BlueGreenEnvironment::where('project_id', $project->id)
            ->where('environment', $targetEnv)
            ->firstOrFail();

        /** @var BlueGreenEnvironment $current */
        $current = BlueGreenEnvironment::where('project_id', $project->id)
            ->where('environment', $activeEnv)
            ->firstOrFail();

        // Verify standby is healthy before switching
        $healthResult = $this->healthCheckService->checkHealth($project, $standby);
        if (!$healthResult['healthy']) {
            throw new \RuntimeException(
                "Cannot switch traffic: standby environment '{$targetEnv}' is not healthy. " .
                ($healthResult['message'] ?? 'Health check failed.')
            );
        }

        // Switch traffic via nginx/proxy
        $this->trafficService->switchUpstream($project, $standby);

        // Update statuses
        $standby->update(['status' => 'active', 'health_status' => 'healthy']);
        $current->update(['status' => 'inactive']);
        $project->update(['active_environment' => $targetEnv]);

        Log::info('Blue-green traffic switched', [
            'project_id' => $project->id,
            'from' => $activeEnv,
            'to' => $targetEnv,
        ]);

        return $standby;
    }

    /**
     * Rollback by switching traffic back to the previous environment.
     */
    public function rollback(Project $project): BlueGreenEnvironment
    {
        // Rollback is just a traffic switch back
        return $this->switchTraffic($project);
    }

    /**
     * Get the status of both environments.
     *
     * @return array{blue: BlueGreenEnvironment|null, green: BlueGreenEnvironment|null, active: string|null}
     */
    public function getStatus(Project $project): array
    {
        $environments = BlueGreenEnvironment::where('project_id', $project->id)->get();

        return [
            'blue' => $environments->firstWhere('environment', 'blue'),
            'green' => $environments->firstWhere('environment', 'green'),
            'active' => $project->active_environment,
        ];
    }

    /**
     * Disable blue-green deployment for a project.
     */
    public function disable(Project $project): void
    {
        BlueGreenEnvironment::where('project_id', $project->id)->delete();
        $project->update([
            'deployment_strategy' => 'standard',
            'active_environment' => null,
            'blue_green_config' => null,
        ]);

        Log::info('Blue-green deployment disabled', ['project_id' => $project->id]);
    }
}
