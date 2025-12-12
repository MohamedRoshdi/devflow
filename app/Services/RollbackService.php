<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DeploymentStatusUpdated;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RollbackService
{
    public function __construct(
        private DockerService $dockerService,
        private GitService $gitService
    ) {}

    /**
     * Build SSH command for remote execution
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p '.$server->port,
        ];

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        $escapedCommand = str_replace("'", "'\\''", $remoteCommand);

        return sprintf(
            "ssh %s %s@%s '%s'",
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            $escapedCommand
        );
    }

    /**
     * Execute command on server (SSH or local)
     */
    protected function executeOnServer(Project $project, string $command, int $timeout = 120): array
    {
        $server = $project->server;
        $sshCommand = $this->buildSSHCommand($server, $command);

        $result = Process::timeout($timeout)->run($sshCommand);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Rollback to a previous deployment
     */
    public function rollbackToDeployment(Deployment $targetDeployment): array
    {
        try {
            $project = $targetDeployment->project;

            if ($project === null) {
                throw new \RuntimeException('Project not found for deployment');
            }

            // Create new deployment record for rollback
            $rollbackDeployment = Deployment::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'server_id' => $project->server_id,
                'branch' => $targetDeployment->branch,
                'commit_hash' => $targetDeployment->commit_hash,
                'commit_message' => 'Rollback to: '.$targetDeployment->commit_message,
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
            if (! $checkoutResult['success']) {
                throw new \Exception('Failed to checkout commit: '.$checkoutResult['error']);
            }

            // Step 3: Restore environment if available
            if ($targetDeployment->environment_snapshot) {
                $this->restoreEnvironment($project, $targetDeployment->environment_snapshot);
            }

            // Step 4: Rebuild and restart containers
            $dockerResult = $this->dockerService->deployWithCompose($project);
            if (! $dockerResult['success']) {
                throw new \Exception('Failed to rebuild Docker containers: '.($dockerResult['error'] ?? 'Unknown error'));
            }

            // Step 5: Run post-rollback checks
            $healthCheck = $this->performHealthCheck($project);
            if (! $healthCheck['healthy']) {
                throw new \Exception('Health check failed after rollback');
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
                'message' => 'Rollback completed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Rollback failed: '.$e->getMessage());

            if (isset($rollbackDeployment)) {
                $rollbackDeployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

                broadcast(new DeploymentStatusUpdated(
                    $rollbackDeployment,
                    'Rollback failed: '.$e->getMessage(),
                    'error'
                ))->toOthers();
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a backup of the current state before rollback
     */
    private function backupCurrentState(Project $project): void
    {
        $projectPath = "/var/www/{$project->slug}";
        $backupPath = "/var/www/backups/{$project->slug}";
        $timestamp = now()->format('Y-m-d_H-i-s');

        // Create backup directory
        $this->executeOnServer($project, "mkdir -p {$backupPath}");

        // Backup environment file
        $this->executeOnServer($project, "cp {$projectPath}/.env {$backupPath}/.env.{$timestamp} 2>/dev/null || true");

        // Create git stash for uncommitted changes
        $this->executeOnServer($project, "cd {$projectPath} && git stash save 'Backup before rollback at {$timestamp}' 2>/dev/null || true");

        Log::info("Backup created for {$project->slug} at {$timestamp}");
    }

    /**
     * Checkout a specific commit
     */
    private function checkoutCommit(Project $project, string $commitHash): array
    {
        try {
            $projectPath = "/var/www/{$project->slug}";

            // Configure safe directory
            $this->executeOnServer($project, "git config --global --add safe.directory {$projectPath} 2>&1 || true");

            // Fetch latest to ensure we have the commit
            $fetchResult = $this->executeOnServer($project, "cd {$projectPath} && git fetch origin {$project->branch} 2>&1");
            if (! $fetchResult['success']) {
                Log::warning('Git fetch warning: '.$fetchResult['error']);
            }

            // Ensure we're on the correct branch
            $result = $this->executeOnServer($project, "cd {$projectPath} && git checkout {$project->branch} 2>&1");
            if (! $result['success']) {
                throw new \Exception('Failed to checkout branch: '.$result['error']);
            }

            // Reset to the target commit
            $result = $this->executeOnServer($project, "cd {$projectPath} && git reset --hard {$commitHash} 2>&1");
            if (! $result['success']) {
                throw new \Exception('Failed to reset to commit: '.$result['error']);
            }

            Log::info("Checked out commit {$commitHash} for {$project->slug}");

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
        $projectPath = "/var/www/{$project->slug}";

        $envContent = '';
        foreach ($environmentSnapshot as $key => $value) {
            // Escape special characters for shell
            $escapedValue = str_replace(["'", '"', '$', '`', '\\'], ["'\\''", '\\"', '\\$', '\\`', '\\\\'], $value);
            $envContent .= "{$key}={$escapedValue}\n";
        }

        // Write env file via SSH
        $this->executeOnServer($project, "cat > {$projectPath}/.env << 'ENVEOF'\n{$envContent}ENVEOF");

        Log::info("Environment restored for {$project->slug}");
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
        $project = $deployment->project;
        if ($project === null) {
            return false;
        }

        $latestDeployment = $project->deployments()
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
