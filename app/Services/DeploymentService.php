<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DeploymentService - Centralized deployment orchestration
 *
 * This service manages all deployment operations including:
 * - Creating and executing deployments
 * - Checking for active deployments
 * - Managing deployment logs
 * - Rollback functionality
 * - Scheduled deployments
 *
 * @package App\Services
 */
class DeploymentService
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly GitService $gitService
    ) {}

    /**
     * Create and execute a new deployment
     *
     * @param Project $project The project to deploy
     * @param User $user The user initiating the deployment
     * @param string $triggeredBy How the deployment was triggered (manual, webhook, scheduled, rollback)
     * @param string|null $commitHash Specific commit to deploy (null for latest)
     * @return Deployment The created deployment record
     * @throws \RuntimeException If a deployment is already active
     * @throws \Exception If deployment creation fails
     */
    public function deploy(
        Project $project,
        User $user,
        string $triggeredBy = 'manual',
        ?string $commitHash = null
    ): Deployment {
        return DB::transaction(function () use ($project, $user, $triggeredBy, $commitHash) {
            // Check for concurrent deployments
            if ($this->hasActiveDeployment($project)) {
                throw new \RuntimeException(
                    "A deployment is already in progress for project '{$project->name}'. " .
                    "Please wait for it to complete or cancel it first."
                );
            }

            // Get current commit information if not provided
            if ($commitHash === null) {
                $currentCommit = $this->gitService->getCurrentCommit($project);
                $commitHash = $currentCommit['hash'] ?? 'pending';
                $commitMessage = $currentCommit['message'] ?? null;
            } else {
                $commitMessage = null;
            }

            // Capture current environment snapshot for rollback capability
            $environmentSnapshot = $this->captureEnvironmentSnapshot($project);

            // Create deployment record
            $deployment = Deployment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'branch' => $project->branch ?? 'main',
                'commit_hash' => $commitHash,
                'commit_message' => $commitMessage,
                'status' => 'pending',
                'triggered_by' => $triggeredBy,
                'started_at' => now(),
                'environment_snapshot' => $environmentSnapshot,
            ]);

            // Dispatch the deployment job to queue
            DeployProjectJob::dispatch($deployment);

            Log::info('Deployment created and queued', [
                'deployment_id' => $deployment->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
                'triggered_by' => $triggeredBy,
                'commit_hash' => $commitHash,
            ]);

            return $deployment;
        });
    }

    /**
     * Check if project has an active deployment
     *
     * Active deployments are those with status 'pending' or 'running'
     *
     * @param Project $project The project to check
     * @return bool True if there is an active deployment
     */
    public function hasActiveDeployment(Project $project): bool
    {
        return $project->deployments()
            ->whereIn('status', ['pending', 'running'])
            ->exists();
    }

    /**
     * Get the currently active deployment for a project
     *
     * @param Project $project The project to check
     * @return Deployment|null The active deployment or null if none exists
     */
    public function getActiveDeployment(Project $project): ?Deployment
    {
        return $project->deployments()
            ->whereIn('status', ['pending', 'running'])
            ->with(['user', 'server'])
            ->first();
    }

    /**
     * Get deployment logs with real-time updates
     *
     * @param Deployment $deployment The deployment to get logs for
     * @return array{success: bool, logs: string, status: string, error?: string}
     */
    public function getDeploymentLogs(Deployment $deployment): array
    {
        try {
            $logs = $deployment->output_log ?? '';

            // If deployment failed, append error logs
            if ($deployment->isFailed() && $deployment->error_log) {
                $logs .= "\n\n=== ERRORS ===\n" . $deployment->error_log;
            }

            return [
                'success' => true,
                'logs' => $logs,
                'status' => $deployment->status,
                'started_at' => $deployment->started_at?->toIso8601String(),
                'completed_at' => $deployment->completed_at?->toIso8601String(),
                'duration_seconds' => $deployment->duration_seconds,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'logs' => '',
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a running deployment
     *
     * Note: This marks the deployment as cancelled but cannot stop the actual
     * job if it's already executing. Use with caution.
     *
     * @param Deployment $deployment The deployment to cancel
     * @return bool True if cancellation was successful
     */
    public function cancelDeployment(Deployment $deployment): bool
    {
        try {
            // Can only cancel pending or running deployments
            if (!in_array($deployment->status, ['pending', 'running'])) {
                Log::warning('Attempted to cancel non-active deployment', [
                    'deployment_id' => $deployment->id,
                    'status' => $deployment->status,
                ]);
                return false;
            }

            $deployment->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($deployment->started_at),
                'error_log' => 'Deployment cancelled by user',
            ]);

            Log::info('Deployment cancelled', [
                'deployment_id' => $deployment->id,
                'project_id' => $deployment->project_id,
                'cancelled_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel deployment', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Rollback to a previous deployment
     *
     * Creates a new deployment that reverts to the commit from a previous
     * successful deployment
     *
     * @param Project $project The project to rollback
     * @param Deployment $targetDeployment The deployment to rollback to
     * @param User $user The user initiating the rollback
     * @return Deployment The new rollback deployment
     * @throws \RuntimeException If rollback cannot be performed
     */
    public function rollback(
        Project $project,
        Deployment $targetDeployment,
        User $user
    ): Deployment {
        // Validate that target deployment is from the same project
        if ($targetDeployment->project_id !== $project->id) {
            throw new \RuntimeException('Target deployment does not belong to this project');
        }

        // Validate that target deployment was successful
        if (!$targetDeployment->isSuccess()) {
            throw new \RuntimeException('Can only rollback to successful deployments');
        }

        // Check if target deployment has a commit hash
        if (!$targetDeployment->commit_hash) {
            throw new \RuntimeException('Target deployment does not have a commit hash');
        }

        Log::info('Initiating rollback', [
            'project_id' => $project->id,
            'target_deployment_id' => $targetDeployment->id,
            'target_commit' => $targetDeployment->commit_hash,
            'user_id' => $user->id,
        ]);

        // Create rollback deployment
        $deployment = DB::transaction(function () use ($project, $targetDeployment, $user) {
            // Check for active deployments
            if ($this->hasActiveDeployment($project)) {
                throw new \RuntimeException(
                    'Cannot rollback while another deployment is in progress'
                );
            }

            return Deployment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'branch' => $targetDeployment->branch,
                'commit_hash' => $targetDeployment->commit_hash,
                'commit_message' => 'Rollback to: ' . ($targetDeployment->commit_message ?? $targetDeployment->commit_hash),
                'status' => 'pending',
                'triggered_by' => 'rollback',
                'rollback_deployment_id' => $targetDeployment->id,
                'started_at' => now(),
                'environment_snapshot' => $targetDeployment->environment_snapshot,
            ]);
        });

        // Dispatch the rollback deployment
        DeployProjectJob::dispatch($deployment);

        return $deployment;
    }

    /**
     * Queue deployment for later execution
     *
     * @param Project $project The project to deploy
     * @param User $user The user scheduling the deployment
     * @param \DateTimeInterface|null $scheduledAt When to execute (null for immediate)
     * @return Deployment The scheduled deployment
     * @throws \Exception If scheduling fails
     */
    public function queueDeployment(
        Project $project,
        User $user,
        ?\DateTimeInterface $scheduledAt = null
    ): Deployment {
        return DB::transaction(function () use ($project, $user, $scheduledAt) {
            // Get current commit information
            $currentCommit = $this->gitService->getCurrentCommit($project);
            $commitHash = $currentCommit['hash'] ?? 'pending';
            $commitMessage = $currentCommit['message'] ?? null;

            // Create deployment record with scheduled status
            $deployment = Deployment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'branch' => $project->branch ?? 'main',
                'commit_hash' => $commitHash,
                'commit_message' => $commitMessage,
                'status' => 'scheduled',
                'triggered_by' => 'scheduled',
                'metadata' => [
                    'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s'),
                ],
            ]);

            // Dispatch the deployment job with delay if scheduled time provided
            if ($scheduledAt) {
                $delay = now()->diffInSeconds($scheduledAt);
                DeployProjectJob::dispatch($deployment)->delay($delay);
            } else {
                DeployProjectJob::dispatch($deployment);
            }

            Log::info('Deployment scheduled', [
                'deployment_id' => $deployment->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
                'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s'),
            ]);

            return $deployment;
        });
    }

    /**
     * Deploy multiple projects in batch
     *
     * @param array<Project> $projects Projects to deploy
     * @param User $user The user initiating deployments
     * @return array{successful: int, failed: int, deployments: array<Deployment>}
     */
    public function batchDeploy(array $projects, User $user): array
    {
        $successful = 0;
        $failed = 0;
        $deployments = [];

        // Batch query: Get all project IDs with active deployments in single query
        $projectIds = array_map(fn ($p) => $p->id, $projects);
        $projectsWithActiveDeployments = Deployment::whereIn('project_id', $projectIds)
            ->whereIn('status', ['pending', 'running'])
            ->distinct()
            ->pluck('project_id')
            ->flip()
            ->toArray();

        foreach ($projects as $project) {
            try {
                // Skip projects without servers
                if (!$project->server_id) {
                    Log::warning('Skipping project without server', [
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                    ]);
                    $failed++;
                    continue;
                }

                // Skip if already deploying (uses pre-fetched batch data)
                if (isset($projectsWithActiveDeployments[$project->id])) {
                    Log::warning('Skipping project with active deployment', [
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                    ]);
                    $failed++;
                    continue;
                }

                $deployment = $this->deploy($project, $user, 'manual');
                $deployments[] = $deployment;
                $successful++;
            } catch (\Exception $e) {
                Log::error('Batch deployment failed for project', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'deployments' => $deployments,
        ];
    }

    /**
     * Get deployment statistics for a project
     *
     * @param Project $project The project to analyze
     * @param int $days Number of days to look back (default: 30)
     * @return array{total: int, successful: int, failed: int, success_rate: float, avg_duration: float}
     */
    public function getDeploymentStats(Project $project, int $days = 30): array
    {
        $deployments = $project->deployments()
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $total = $deployments->count();
        $successful = $deployments->where('status', 'success')->count();
        $failed = $deployments->where('status', 'failed')->count();
        $successRate = $total > 0 ? ($successful / $total) * 100 : 0;

        $completedDeployments = $deployments->whereNotNull('duration_seconds');
        $avgDuration = $completedDeployments->isNotEmpty()
            ? $completedDeployments->avg('duration_seconds')
            : 0;

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => round($successRate, 2),
            'avg_duration' => round($avgDuration, 2),
        ];
    }

    /**
     * Check for available updates for a project
     *
     * @param Project $project The project to check
     * @return array{has_updates: bool, commits_behind: int, local_commit: string|null, remote_commit: string|null}
     */
    public function checkForUpdates(Project $project): array
    {
        $updateStatus = $this->gitService->checkForUpdates($project);

        if (!$updateStatus['success']) {
            return [
                'has_updates' => false,
                'commits_behind' => 0,
                'local_commit' => null,
                'remote_commit' => null,
                'error' => $updateStatus['error'] ?? 'Unknown error',
            ];
        }

        return [
            'has_updates' => !$updateStatus['up_to_date'],
            'commits_behind' => $updateStatus['commits_behind'] ?? 0,
            'local_commit' => $updateStatus['local_commit'] ?? null,
            'remote_commit' => $updateStatus['remote_commit'] ?? null,
            'local_meta' => $updateStatus['local_meta'] ?? null,
            'remote_meta' => $updateStatus['remote_meta'] ?? null,
        ];
    }

    /**
     * Capture current environment snapshot for rollback purposes
     *
     * @param Project $project The project to capture
     * @return array<string, mixed> Environment snapshot data
     */
    private function captureEnvironmentSnapshot(Project $project): array
    {
        return [
            'branch' => $project->branch,
            'environment' => $project->environment,
            'php_version' => $project->php_version,
            'framework' => $project->framework,
            'env_variables' => $project->env_variables,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate deployment prerequisites
     *
     * @param Project $project The project to validate
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateDeploymentPrerequisites(Project $project): array
    {
        $errors = [];

        // Check if project has a server assigned
        if (!$project->server_id) {
            $errors[] = 'Project does not have a server assigned';
        }

        // Check if repository URL is set
        if (!$project->repository_url) {
            $errors[] = 'Project does not have a repository URL configured';
        }

        // Check if branch is set
        if (!$project->branch) {
            $errors[] = 'Project does not have a branch configured';
        }

        // Check if server is online
        if ($project->server && $project->server->status !== 'online') {
            $errors[] = 'Server is not online';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get recent deployments for a project
     *
     * @param Project $project The project to get deployments for
     * @param int $limit Maximum number of deployments to return
     * @return \Illuminate\Database\Eloquent\Collection<int, Deployment>
     */
    public function getRecentDeployments(Project $project, int $limit = 10)
    {
        return $project->deployments()
            ->with(['user', 'server'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark deployment as success (for manual intervention)
     *
     * @param Deployment $deployment The deployment to mark as successful
     * @return bool True if successful
     */
    public function markAsSuccess(Deployment $deployment): bool
    {
        try {
            $deployment->update([
                'status' => 'success',
                'completed_at' => $deployment->completed_at ?? now(),
                'duration_seconds' => $deployment->duration_seconds ?? now()->diffInSeconds($deployment->started_at),
            ]);

            Log::info('Deployment manually marked as success', [
                'deployment_id' => $deployment->id,
                'marked_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark deployment as success', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark deployment as failed (for manual intervention)
     *
     * @param Deployment $deployment The deployment to mark as failed
     * @param string|null $errorMessage Optional error message
     * @return bool True if successful
     */
    public function markAsFailed(Deployment $deployment, ?string $errorMessage = null): bool
    {
        try {
            $deployment->update([
                'status' => 'failed',
                'completed_at' => $deployment->completed_at ?? now(),
                'duration_seconds' => $deployment->duration_seconds ?? now()->diffInSeconds($deployment->started_at),
                'error_log' => $errorMessage ?? $deployment->error_log ?? 'Manually marked as failed',
            ]);

            Log::info('Deployment manually marked as failed', [
                'deployment_id' => $deployment->id,
                'marked_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark deployment as failed', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
