<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Region;
use App\Models\RegionDeployment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * CrossRegionDeploymentService - Cross-region deployment orchestration
 *
 * This service manages deployments across multiple geographic regions,
 * supporting both sequential and parallel deployment strategies.
 *
 * @package App\Services
 */
class CrossRegionDeploymentService
{
    public function __construct(
        private readonly DeploymentService $deploymentService,
        private readonly RegionService $regionService
    ) {}

    /**
     * Initiate a cross-region deployment.
     *
     * Creates a RegionDeployment record and sets up region statuses for tracking.
     *
     * @param Project $project The project to deploy
     * @param array<int, int> $regionIds The region IDs to deploy to
     * @param string $strategy The deployment strategy ('sequential' or 'parallel')
     * @param User $user The user initiating the deployment
     * @return RegionDeployment The created cross-region deployment
     *
     * @throws \InvalidArgumentException If no valid regions are provided
     */
    public function initiateCrossRegionDeployment(
        Project $project,
        array $regionIds,
        string $strategy,
        User $user
    ): RegionDeployment {
        $regions = Region::whereIn('id', $regionIds)->get();

        if ($regions->isEmpty()) {
            throw new \InvalidArgumentException('No valid regions provided for deployment.');
        }

        return DB::transaction(function () use ($project, $regions, $strategy, $user): RegionDeployment {
            // Create the base deployment record
            $deployment = Deployment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'branch' => $project->branch ?? 'main',
                'commit_hash' => 'pending',
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
                'metadata' => [
                    'type' => 'cross_region',
                    'strategy' => $strategy,
                    'region_count' => $regions->count(),
                ],
            ]);

            // Build region order and initial statuses
            $regionOrder = $regions->pluck('id')->toArray();
            $regionStatuses = [];
            foreach ($regions as $region) {
                $regionStatuses[$region->id] = [
                    'status' => 'pending',
                    'region_name' => $region->name,
                    'region_code' => $region->code,
                    'started_at' => null,
                    'completed_at' => null,
                    'error' => null,
                ];
            }

            $regionDeployment = RegionDeployment::create([
                'project_id' => $project->id,
                'deployment_id' => $deployment->id,
                'initiated_by' => $user->id,
                'strategy' => $strategy,
                'region_order' => $regionOrder,
                'region_statuses' => $regionStatuses,
                'status' => 'pending',
                'started_at' => now(),
            ]);

            Log::info('Cross-region deployment initiated', [
                'region_deployment_id' => $regionDeployment->id,
                'project_id' => $project->id,
                'strategy' => $strategy,
                'regions' => $regionOrder,
                'user_id' => $user->id,
            ]);

            return $regionDeployment;
        });
    }

    /**
     * Deploy to a specific region within a cross-region deployment.
     *
     * Executes deployment commands on all servers in the specified region.
     *
     * @param RegionDeployment $regionDeployment The parent cross-region deployment
     * @param Region $region The target region to deploy to
     * @return array{status: string, region_id: int, region_name: string, error: string|null}
     */
    public function deployToRegion(RegionDeployment $regionDeployment, Region $region): array
    {
        $this->updateRegionStatus($regionDeployment, $region->id, 'running');

        try {
            $servers = $region->servers()->where('status', 'online')->get();

            if ($servers->isEmpty()) {
                throw new \RuntimeException("No online servers found in region '{$region->name}'.");
            }

            $project = $regionDeployment->project;

            if ($project === null) {
                throw new \RuntimeException('Project not found for region deployment.');
            }

            foreach ($servers as $server) {
                $this->deployToServer($project, $server);
            }

            $this->updateRegionStatus($regionDeployment, $region->id, 'success');

            Log::info('Region deployment successful', [
                'region_deployment_id' => $regionDeployment->id,
                'region_id' => $region->id,
                'region_name' => $region->name,
                'servers_deployed' => $servers->count(),
            ]);

            return [
                'status' => 'success',
                'region_id' => $region->id,
                'region_name' => $region->name,
                'error' => null,
            ];
        } catch (\Exception $e) {
            $this->updateRegionStatus($regionDeployment, $region->id, 'failed', $e->getMessage());

            Log::error('Region deployment failed', [
                'region_deployment_id' => $regionDeployment->id,
                'region_id' => $region->id,
                'region_name' => $region->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'region_id' => $region->id,
                'region_name' => $region->name,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current deployment progress per region.
     *
     * @param RegionDeployment $regionDeployment The cross-region deployment to check
     * @return array{
     *     status: string,
     *     strategy: string,
     *     total_regions: int,
     *     completed: int,
     *     failed: int,
     *     pending: int,
     *     running: int,
     *     regions: array<int|string, mixed>
     * }
     */
    public function getDeploymentProgress(RegionDeployment $regionDeployment): array
    {
        $regionDeployment->refresh();
        $statuses = $regionDeployment->region_statuses ?? [];

        $completed = 0;
        $failed = 0;
        $pending = 0;
        $running = 0;

        foreach ($statuses as $regionStatus) {
            $status = is_array($regionStatus) ? ($regionStatus['status'] ?? 'pending') : 'pending';
            match ($status) {
                'success' => $completed++,
                'failed' => $failed++,
                'running' => $running++,
                default => $pending++,
            };
        }

        return [
            'status' => $regionDeployment->status,
            'strategy' => $regionDeployment->strategy,
            'total_regions' => count($statuses),
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'running' => $running,
            'regions' => $statuses,
        ];
    }

    /**
     * Rollback a specific region within a cross-region deployment.
     *
     * @param RegionDeployment $regionDeployment The parent deployment
     * @param Region $region The region to rollback
     * @return bool True if rollback succeeded
     */
    public function rollbackRegion(RegionDeployment $regionDeployment, Region $region): bool
    {
        try {
            $this->updateRegionStatus($regionDeployment, $region->id, 'rolling_back');

            $project = $regionDeployment->project;

            if ($project === null) {
                throw new \RuntimeException('Project not found for rollback.');
            }

            $servers = $region->servers()->where('status', 'online')->get();

            foreach ($servers as $server) {
                $this->rollbackServer($project, $server);
            }

            $this->updateRegionStatus($regionDeployment, $region->id, 'rolled_back');

            Log::info('Region rollback successful', [
                'region_deployment_id' => $regionDeployment->id,
                'region_id' => $region->id,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->updateRegionStatus($regionDeployment, $region->id, 'rollback_failed', $e->getMessage());

            Log::error('Region rollback failed', [
                'region_deployment_id' => $regionDeployment->id,
                'region_id' => $region->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Rollback all regions in a cross-region deployment.
     *
     * @param RegionDeployment $regionDeployment The deployment to rollback
     * @return bool True if all rollbacks succeeded
     */
    public function rollbackAll(RegionDeployment $regionDeployment): bool
    {
        $regionOrder = $regionDeployment->region_order ?? [];
        $allSuccess = true;

        foreach ($regionOrder as $regionId) {
            $region = Region::find($regionId);

            if ($region === null) {
                continue;
            }

            $statuses = $regionDeployment->region_statuses ?? [];
            $regionEntry = $statuses[$regionId] ?? [];
            $regionStatus = is_array($regionEntry) ? ($regionEntry['status'] ?? 'pending') : 'pending';

            // Only rollback regions that were successfully deployed or are running
            if ($regionStatus === 'success' || $regionStatus === 'running') {
                $result = $this->rollbackRegion($regionDeployment, $region);

                if (!$result) {
                    $allSuccess = false;
                }
            }
        }

        $regionDeployment->update([
            'status' => $allSuccess ? 'rolled_back' : 'rollback_partial',
            'completed_at' => now(),
        ]);

        return $allSuccess;
    }

    /**
     * Get cross-region deployment history for a project.
     *
     * @param Project $project The project to get history for
     * @return Collection<int, RegionDeployment>
     */
    public function getDeploymentHistory(Project $project): Collection
    {
        return RegionDeployment::where('project_id', $project->id)
            ->with(['initiator', 'deployment'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Deploy to a specific server via SSH.
     *
     * @param Project $project The project to deploy
     * @param \App\Models\Server $server The target server
     * @return void
     *
     * @throws \RuntimeException If deployment commands fail
     */
    private function deployToServer(Project $project, \App\Models\Server $server): void
    {
        $projectPath = "/var/www/{$project->slug}";
        $branch = escapeshellarg($project->branch ?? 'main');
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        $commands = [
            "cd {$projectPath} && git fetch origin {$branch} && git reset --hard origin/{$branch}",
        ];

        foreach ($commands as $command) {
            $result = Process::timeout(120)->run("{$sshPrefix} \"{$command}\"");

            if (!$result->successful()) {
                throw new \RuntimeException(
                    "Deployment command failed on server '{$server->name}': {$result->errorOutput()}"
                );
            }
        }
    }

    /**
     * Rollback a specific server by resetting to the previous commit.
     *
     * @param Project $project The project to rollback
     * @param \App\Models\Server $server The target server
     * @return void
     *
     * @throws \RuntimeException If rollback commands fail
     */
    private function rollbackServer(Project $project, \App\Models\Server $server): void
    {
        $projectPath = "/var/www/{$project->slug}";
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        $command = "cd {$projectPath} && git reset --hard HEAD~1";

        $result = Process::timeout(120)->run("{$sshPrefix} \"{$command}\"");

        if (!$result->successful()) {
            throw new \RuntimeException(
                "Rollback command failed on server '{$server->name}': {$result->errorOutput()}"
            );
        }
    }

    /**
     * Update the status of a specific region within a cross-region deployment.
     *
     * @param RegionDeployment $regionDeployment The parent deployment
     * @param int $regionId The region ID to update
     * @param string $status The new status
     * @param string|null $error Optional error message
     * @return void
     */
    private function updateRegionStatus(
        RegionDeployment $regionDeployment,
        int $regionId,
        string $status,
        ?string $error = null
    ): void {
        $statuses = $regionDeployment->region_statuses ?? [];

        if (!isset($statuses[$regionId]) || !is_array($statuses[$regionId])) {
            $statuses[$regionId] = [];
        }

        $statuses[$regionId]['status'] = $status;

        if ($status === 'running') {
            $statuses[$regionId]['started_at'] = now()->toIso8601String();
        }

        if (in_array($status, ['success', 'failed', 'rolled_back', 'rollback_failed'], true)) {
            $statuses[$regionId]['completed_at'] = now()->toIso8601String();
        }

        if ($error !== null) {
            $statuses[$regionId]['error'] = $error;
        }

        $regionDeployment->update(['region_statuses' => $statuses]);
    }
}
