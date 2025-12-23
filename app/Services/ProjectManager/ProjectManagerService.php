<?php

declare(strict_types=1);

namespace App\Services\ProjectManager;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\LogAggregationService;
use App\Services\RollbackService;
use App\Services\SSLService;
use App\Services\StorageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Core orchestration service for project management operations.
 *
 * This service coordinates all project-related operations including:
 * - Project creation and initialization
 * - Deployment orchestration
 * - Health monitoring
 * - Cleanup operations
 * - Rollback management
 */
class ProjectManagerService
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly GitService $gitService,
        private readonly SSLService $sslService,
        private readonly LogAggregationService $logManager,
        private readonly StorageService $storageService,
        private readonly RollbackService $rollbackService
    ) {}

    /**
     * Create a new project with full initialization.
     *
     * Creates the project record and initializes:
     * - Docker containers (if server is connected)
     * - Domain configuration
     * - Storage setup
     *
     * @param array<string, mixed> $projectData Project configuration data
     * @return Project Created project instance
     * @throws \Exception If project creation fails
     */
    public function createProject(array $projectData): Project
    {
        try {
            DB::beginTransaction();

            // Create project record
            $project = Project::create($projectData);

            Log::info("Project created: {$project->slug}", [
                'project_id' => $project->id,
                'server_id' => $project->server_id,
            ]);

            // Initialize project components
            if ($project->server) {
                $this->initializeProjectComponents($project);
            }

            DB::commit();

            $refreshedProject = $project->fresh();
            if (!$refreshedProject) {
                throw new \Exception('Failed to refresh project after creation');
            }

            return $refreshedProject;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create project', [
                'error' => $e->getMessage(),
                'data' => $projectData,
            ]);

            throw new \Exception("Failed to create project: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Initialize project components (containers, storage, etc.)
     *
     * @param Project $project
     * @return void
     */
    private function initializeProjectComponents(Project $project): void
    {
        try {
            // Update project status to initializing
            $project->update(['setup_status' => 'in_progress']);

            Log::info("Initializing project components for: {$project->slug}");

            // Docker initialization is handled during first deployment
            // Storage initialization
            if ($project->storage_used_mb === null) {
                $project->update(['storage_used_mb' => 0]);
            }

            // Mark setup as completed
            $project->update([
                'setup_status' => 'completed',
                'setup_completed_at' => now(),
            ]);

            Log::info("Project components initialized for: {$project->slug}");

        } catch (\Exception $e) {
            $project->update(['setup_status' => 'failed']);

            Log::error("Failed to initialize project components: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Deploy a project to its server.
     *
     * Orchestrates the full deployment process:
     * - Checks for existing deployments
     * - Creates deployment record
     * - Pulls latest code via Git
     * - Builds and starts Docker containers
     * - Updates deployment status
     *
     * @param Project $project Project to deploy
     * @param bool $forceDeploy Force deployment even if one is in progress
     * @return Deployment Created deployment instance
     * @throws \Exception If deployment fails
     */
    public function deployProject(Project $project, bool $forceDeploy = false): Deployment
    {
        try {
            // Check for active deployments
            if (!$forceDeploy) {
                $activeDeployment = Deployment::where('project_id', $project->id)
                    ->whereIn('status', ['pending', 'running'])
                    ->first();

                if ($activeDeployment) {
                    throw new \Exception('Deployment already in progress');
                }
            }

            // Create deployment record
            $deployment = Deployment::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'server_id' => $project->server_id,
                'branch' => $project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            Log::info("Deployment started for project: {$project->slug}", [
                'deployment_id' => $deployment->id,
            ]);

            // Update deployment status to running
            $deployment->update(['status' => 'running']);

            // Get latest commit information from Git
            $currentCommit = $this->gitService->getCurrentCommit($project);
            if ($currentCommit) {
                $deployment->update([
                    'commit_hash' => $currentCommit['hash'],
                    'commit_message' => $currentCommit['message'],
                ]);
            }

            // Perform Docker deployment
            $dockerResult = $this->performDockerDeployment($project);

            if (!$dockerResult['success']) {
                throw new \Exception($dockerResult['error'] ?? 'Docker deployment failed');
            }

            // Update deployment as successful
            $deployment->update([
                'status' => 'success',
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($deployment->started_at),
                'output_log' => $dockerResult['output'] ?? null,
            ]);

            // Update project metadata
            $project->update([
                'last_deployed_at' => now(),
                'status' => 'running',
            ]);

            // Update Git commit info
            $this->gitService->updateProjectCommitInfo($project);

            Log::info("Deployment completed successfully: {$project->slug}", [
                'deployment_id' => $deployment->id,
                'duration' => $deployment->duration_seconds,
            ]);

            $refreshedDeployment = $deployment->fresh();
            if (!$refreshedDeployment) {
                throw new \Exception('Failed to refresh deployment after completion');
            }

            return $refreshedDeployment;

        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($deployment)) {
                $deployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                    'duration_seconds' => now()->diffInSeconds($deployment->started_at),
                ]);
            }

            throw new \Exception("Deployment failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Perform Docker deployment operations
     *
     * @param Project $project
     * @return array{success: bool, output?: string, error?: string}
     */
    private function performDockerDeployment(Project $project): array
    {
        try {
            // Check if project uses Docker Compose
            $usesCompose = $this->dockerService->usesDockerCompose($project);

            /** @var array{success: bool, output?: string, error?: string} $result */
            $result = [];

            if ($usesCompose) {
                // Deploy with Docker Compose
                $composeResult = $this->dockerService->deployWithCompose($project);
                $result = [
                    'success' => $composeResult['success'] ?? false,
                    'output' => $composeResult['output'] ?? '',
                    'error' => $composeResult['error'] ?? '',
                ];
            } else {
                // Build and start standalone container
                $buildResult = $this->dockerService->buildContainer($project);
                if (!$buildResult['success']) {
                    return [
                        'success' => false,
                        'output' => $buildResult['output'] ?? '',
                        'error' => $buildResult['error'] ?? '',
                    ];
                }

                $startResult = $this->dockerService->startContainer($project);
                $result = [
                    'success' => $startResult['success'] ?? false,
                    'output' => $startResult['output'] ?? '',
                    'error' => $startResult['error'] ?? '',
                ];
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get comprehensive health status for a project.
     *
     * Returns detailed health information including:
     * - Docker container status
     * - Domain/SSL health
     * - Storage usage statistics
     * - Latest deployment info
     * - Recent error logs
     *
     * @param Project $project Project to check
     * @return array<string, mixed> Comprehensive health data
     */
    public function getProjectHealth(Project $project): array
    {
        try {
            $health = [
                'overall_status' => 'healthy',
                'checks' => [],
            ];

            // 1. Container health
            $containerStatus = $this->getContainerHealth($project);
            $health['checks']['containers'] = $containerStatus;
            if (!$containerStatus['healthy']) {
                $health['overall_status'] = 'unhealthy';
            }

            // 2. Domain/SSL health
            $domainHealth = $this->getDomainHealth($project);
            $health['checks']['domains'] = $domainHealth;
            if (!$domainHealth['healthy']) {
                $health['overall_status'] = 'warning';
            }

            // 3. Storage usage
            $storageStats = $this->getStorageHealth($project);
            $health['checks']['storage'] = $storageStats;
            if ($storageStats['warning']) {
                $health['overall_status'] = 'warning';
            }

            // 4. Latest deployment
            $health['latest_deployment'] = $project->deployments()
                ->latest()
                ->first();

            // 5. Recent errors
            $health['recent_errors'] = $this->getRecentErrors($project);

            return $health;

        } catch (\Exception $e) {
            Log::error("Failed to get project health: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'overall_status' => 'error',
                'error' => $e->getMessage(),
                'checks' => [],
            ];
        }
    }

    /**
     * Get container health status
     *
     * @param Project $project
     * @return array{healthy: bool, status?: array<string, mixed>, error?: string}
     */
    private function getContainerHealth(Project $project): array
    {
        try {
            $status = $this->dockerService->getContainerStatus($project);

            if (!$status['success']) {
                return [
                    'healthy' => false,
                    'error' => $status['error'] ?? 'Failed to get container status',
                ];
            }

            $isHealthy = isset($status['exists']) && $status['exists'] &&
                         isset($status['container']['State']) &&
                         str_contains(strtolower($status['container']['State']), 'up');

            return [
                'healthy' => $isHealthy,
                'status' => $status['container'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get domain and SSL health
     *
     * @param Project $project
     * @return array{healthy: bool, domains: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Domain>, ssl_issues: array<int, string>}
     */
    private function getDomainHealth(Project $project): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Domain> $domains */
        $domains = $project->domains;

        /** @var array<int, string> $sslIssues */
        $sslIssues = [];

        foreach ($domains as $domain) {
            if ($domain->ssl_enabled && $domain->ssl_expires_at) {
                $daysUntilExpiry = now()->diffInDays($domain->ssl_expires_at, false);
                if ($daysUntilExpiry < 30) {
                    $sslIssues[] = "Domain {$domain->domain} SSL expires in {$daysUntilExpiry} days";
                }
            }
        }

        return [
            'healthy' => empty($sslIssues),
            'domains' => $domains,
            'ssl_issues' => $sslIssues,
        ];
    }

    /**
     * Get storage health status
     *
     * @param Project $project
     * @return array{usage_mb: int, warning: bool}
     */
    private function getStorageHealth(Project $project): array
    {
        $usageMB = $project->storage_used_mb ?? 0;
        $warningThreshold = 5000; // 5GB warning threshold

        return [
            'usage_mb' => $usageMB,
            'usage_gb' => round($usageMB / 1024, 2),
            'warning' => $usageMB > $warningThreshold,
        ];
    }

    /**
     * Get recent error logs
     *
     * @param Project $project
     * @return array<int, array<string, mixed>>
     */
    private function getRecentErrors(Project $project): array
    {
        try {
            // Get Docker container logs (last 50 lines)
            $logsResult = $this->dockerService->getContainerLogs($project, 50);

            if (!$logsResult['success']) {
                return [];
            }

            $logs = $logsResult['logs'] ?? '';
            $lines = explode("\n", $logs);

            // Filter error lines
            $errors = [];
            foreach ($lines as $line) {
                if (preg_match('/(error|exception|fatal|critical)/i', $line)) {
                    $errors[] = [
                        'message' => trim($line),
                        'timestamp' => now(),
                    ];
                }
            }

            return array_slice($errors, 0, 10); // Return last 10 errors

        } catch (\Exception $e) {
            Log::warning("Failed to get recent errors for project: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Cleanup project resources.
     *
     * Performs comprehensive cleanup:
     * - Clears application caches
     * - Rotates log files
     * - Removes temporary files
     * - Cleans unused Docker resources
     *
     * @param Project $project Project to cleanup
     * @return array<string, mixed> Cleanup results
     */
    public function cleanupProject(Project $project): array
    {
        try {
            $results = [
                'success' => true,
                'operations' => [],
            ];

            Log::info("Starting cleanup for project: {$project->slug}");

            // 1. Clear application caches (Laravel-specific)
            if (in_array($project->framework, ['Laravel', 'laravel'])) {
                $cacheResult = $this->clearApplicationCache($project);
                $results['operations']['cache_cleared'] = $cacheResult;
            }

            // 2. Clear Docker logs
            $logsResult = $this->dockerService->clearLaravelLogs($project);
            $results['operations']['logs_cleared'] = $logsResult;

            // 3. Clean storage
            $storageResult = $this->storageService->cleanupProjectStorage($project);
            $results['operations']['storage_cleaned'] = $storageResult;

            // 4. Recalculate storage usage
            $this->storageService->calculateProjectStorage($project);

            Log::info("Cleanup completed for project: {$project->slug}", [
                'results' => $results,
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error("Cleanup failed for project: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'operations' => [],
            ];
        }
    }

    /**
     * Clear application cache for Laravel projects
     *
     * @param Project $project
     * @return array{success: bool, output?: string, error?: string}
     */
    private function clearApplicationCache(Project $project): array
    {
        try {
            $commands = [
                'php artisan cache:clear',
                'php artisan config:clear',
                'php artisan route:clear',
                'php artisan view:clear',
            ];

            $outputs = [];
            foreach ($commands as $cmd) {
                $result = $this->dockerService->execInContainer($project, $cmd);
                if ($result['success'] && isset($result['output'])) {
                    $outputs[] = $result['output'];
                }
            }

            return [
                'success' => true,
                'output' => implode("\n", $outputs),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Rollback project to a previous deployment.
     *
     * Delegates to RollbackService for the actual rollback operation.
     *
     * @param Project $project Project to rollback
     * @param int $deploymentId Target deployment ID
     * @return Deployment New rollback deployment
     * @throws \Exception If rollback fails
     */
    public function rollbackProject(Project $project, int $deploymentId): Deployment
    {
        try {
            $targetDeployment = Deployment::findOrFail($deploymentId);

            if ($targetDeployment->project_id !== $project->id) {
                throw new \Exception('Deployment does not belong to this project');
            }

            Log::info("Starting rollback for project: {$project->slug}", [
                'target_deployment_id' => $deploymentId,
            ]);

            $result = $this->rollbackService->rollbackToDeployment($targetDeployment);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Rollback failed');
            }

            Log::info("Rollback completed successfully: {$project->slug}");

            return $result['deployment'];

        } catch (\Exception $e) {
            Log::error("Rollback failed for project: {$project->slug}", [
                'deployment_id' => $deploymentId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Rollback failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get available rollback points for a project
     *
     * @param Project $project
     * @param int $limit Maximum number of rollback points
     * @return array<int, array<string, mixed>>
     */
    public function getRollbackPoints(Project $project, int $limit = 10): array
    {
        return $this->rollbackService->getRollbackPoints($project, $limit);
    }

    /**
     * Stop a project's containers
     *
     * @param Project $project
     * @return array{success: bool, message?: string, error?: string}
     */
    public function stopProject(Project $project): array
    {
        try {
            Log::info("Stopping project: {$project->slug}");

            $result = $this->dockerService->stopContainer($project);

            if ($result['success']) {
                $project->update(['status' => 'stopped']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Failed to stop project: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Start a project's containers
     *
     * @param Project $project
     * @return array{success: bool, message?: string, error?: string}
     */
    public function startProject(Project $project): array
    {
        try {
            Log::info("Starting project: {$project->slug}");

            $result = $this->dockerService->startContainer($project);

            if ($result['success']) {
                $project->update(['status' => 'running']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Failed to start project: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restart a project's containers
     *
     * @param Project $project
     * @return array{success: bool, message?: string, error?: string}
     */
    public function restartProject(Project $project): array
    {
        try {
            Log::info("Restarting project: {$project->slug}");

            $stopResult = $this->stopProject($project);
            if (!$stopResult['success']) {
                return $stopResult;
            }

            sleep(2); // Brief pause between stop and start

            return $this->startProject($project);

        } catch (\Exception $e) {
            Log::error("Failed to restart project: {$project->slug}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
