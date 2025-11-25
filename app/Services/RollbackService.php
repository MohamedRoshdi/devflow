<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Project;
use App\Events\DeploymentStatusUpdated;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class RollbackService
{
    public function __construct(
        private DockerService $dockerService,
        private GitService $gitService
    ) {}

    /**
     * Rollback to a previous deployment
     */
    public function rollbackToDeployment(Deployment $targetDeployment): array
    {
        try {
            $project = $targetDeployment->project;

            // Create new deployment record for rollback
            $rollbackDeployment = Deployment::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'server_id' => $project->server_id,
                'branch' => $targetDeployment->branch,
                'commit_hash' => $targetDeployment->commit_hash,
                'commit_message' => "Rollback to: " . $targetDeployment->commit_message,
                'status' => 'running',
                'triggered_by' => 'rollback',
                'rollback_deployment_id' => $targetDeployment->id,
                'started_at' => now(),
            ]);

            // Broadcast start event
            broadcast(new DeploymentStatusUpdated(
                $rollbackDeployment,
                "Starting rollback to deployment #{$targetDeployment->id}",
                'info'
            ))->toOthers();

            // Step 1: Backup current state
            $this->backupCurrentState($project);

            // Step 2: Checkout the target commit
            $checkoutResult = $this->checkoutCommit($project, $targetDeployment->commit_hash);
            if (!$checkoutResult['success']) {
                throw new \Exception("Failed to checkout commit: " . $checkoutResult['error']);
            }

            // Step 3: Restore environment if available
            if ($targetDeployment->environment_snapshot) {
                $this->restoreEnvironment($project, $targetDeployment->environment_snapshot);
            }

            // Step 4: Rebuild and restart containers
            $dockerResult = $this->dockerService->deployProject($project);
            if (!$dockerResult) {
                throw new \Exception("Failed to rebuild Docker containers");
            }

            // Step 5: Run post-rollback checks
            $healthCheck = $this->performHealthCheck($project);
            if (!$healthCheck['healthy']) {
                throw new \Exception("Health check failed after rollback");
            }

            // Update deployment status
            $rollbackDeployment->update([
                'status' => 'success',
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($rollbackDeployment->started_at),
            ]);

            // Broadcast success
            broadcast(new DeploymentStatusUpdated(
                $rollbackDeployment,
                "Successfully rolled back to deployment #{$targetDeployment->id}",
                'success'
            ))->toOthers();

            return [
                'success' => true,
                'deployment' => $rollbackDeployment,
                'message' => 'Rollback completed successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Rollback failed: " . $e->getMessage());

            if (isset($rollbackDeployment)) {
                $rollbackDeployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

                broadcast(new DeploymentStatusUpdated(
                    $rollbackDeployment,
                    "Rollback failed: " . $e->getMessage(),
                    'error'
                ))->toOthers();
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a backup of the current state before rollback
     */
    private function backupCurrentState(Project $project): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;
        $backupPath = config('devflow.backups_path') . '/' . $project->slug;
        $timestamp = now()->format('Y-m-d_H-i-s');

        // Create backup directory
        Process::run("mkdir -p {$backupPath}");

        // Backup database if Laravel project
        if ($project->framework === 'laravel') {
            Process::run("cd {$projectPath} && docker-compose exec -T app php artisan backup:run --only-db --filename=rollback_{$timestamp}.sql");
        }

        // Backup environment file
        Process::run("cp {$projectPath}/.env {$backupPath}/.env.{$timestamp}");

        // Create git stash for uncommitted changes
        Process::run("cd {$projectPath} && git stash save 'Backup before rollback at {$timestamp}'");
    }

    /**
     * Checkout a specific commit
     */
    private function checkoutCommit(Project $project, string $commitHash): array
    {
        try {
            $projectPath = config('devflow.projects_path') . '/' . $project->slug;

            // Ensure we're on the correct branch
            $result = Process::run("cd {$projectPath} && git checkout {$project->branch}");
            if (!$result->successful()) {
                throw new \Exception("Failed to checkout branch: " . $result->errorOutput());
            }

            // Reset to the target commit
            $result = Process::run("cd {$projectPath} && git reset --hard {$commitHash}");
            if (!$result->successful()) {
                throw new \Exception("Failed to reset to commit: " . $result->errorOutput());
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restore environment variables from snapshot
     */
    private function restoreEnvironment(Project $project, array $environmentSnapshot): void
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;
        $envPath = "{$projectPath}/.env";

        $envContent = "";
        foreach ($environmentSnapshot as $key => $value) {
            $envContent .= "{$key}={$value}\n";
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Perform health checks after rollback
     */
    private function performHealthCheck(Project $project): array
    {
        try {
            // Check if containers are running
            $containers = $this->dockerService->getContainerStatus($project);
            if (empty($containers)) {
                return ['healthy' => false, 'error' => 'No containers running'];
            }

            // Check application health endpoint if available
            if ($project->health_check_url) {
                $ch = curl_init($project->health_check_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    return ['healthy' => false, 'error' => "Health check returned HTTP {$httpCode}"];
                }
            }

            return ['healthy' => true];
        } catch (\Exception $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get available rollback points for a project
     */
    public function getRollbackPoints(Project $project, int $limit = 10): array
    {
        return Deployment::where('project_id', $project->id)
            ->where('status', 'success')
            ->whereNull('rollback_deployment_id') // Exclude rollback deployments
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($deployment) {
                return [
                    'id' => $deployment->id,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => $deployment->commit_message,
                    'deployed_at' => $deployment->created_at,
                    'deployed_by' => $deployment->user->name ?? 'System',
                    'can_rollback' => $this->canRollback($deployment),
                ];
            })
            ->toArray();
    }

    /**
     * Check if a deployment can be rolled back to
     */
    private function canRollback(Deployment $deployment): bool
    {
        // Can't rollback to the current deployment
        $latestDeployment = $deployment->project->deployments()
            ->where('status', 'success')
            ->latest()
            ->first();

        if ($latestDeployment && $latestDeployment->id === $deployment->id) {
            return false;
        }

        // Must have a commit hash
        if (empty($deployment->commit_hash)) {
            return false;
        }

        return true;
    }
}