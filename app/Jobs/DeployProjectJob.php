<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Services\DockerService;
use App\Services\GitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Increased for Docker builds which can take 10-20 minutes with npm builds
     */
    public $timeout = 1200; // 20 minutes

    public function __construct(
        public Deployment $deployment
    ) {}

    public function handle(): void
    {
        $startTime = now();
        
        try {
            $this->deployment->update([
                'status' => 'running',
                'started_at' => $startTime,
            ]);

            $dockerService = app(DockerService::class);
            $project = $this->deployment->project;
            
            $logs = [];
            $projectPath = "/var/www/{$project->slug}";

            // Step 1: Setup Git repository
            $logs[] = "=== Setting Up Repository ===";
            $logs[] = "Repository: {$project->repository_url}";
            $logs[] = "Branch: {$project->branch}";
            $logs[] = "Path: {$projectPath}";

            // Build SSH command helper for running commands as root on the server
            $server = $project->server;
            $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

            // Check if repository already exists (via SSH to ensure we check server state)
            $checkResult = \Illuminate\Support\Facades\Process::run("{$sshPrefix} \"test -d {$projectPath}/.git && echo 'exists' || echo 'not_exists'\"");
            $repoExists = trim($checkResult->output()) === 'exists';

            if ($repoExists) {
                $logs[] = "Repository already exists, pulling latest changes...";

                // Run git operations via SSH as root (fixes permission issues)
                $gitCommand = "cd {$projectPath} && " .
                    "git config --global safe.directory '*' && " .
                    "chown -R root:root {$projectPath}/.git {$projectPath}/storage {$projectPath}/bootstrap 2>/dev/null || true && " .
                    "git fetch origin {$project->branch} && " .
                    "git reset --hard origin/{$project->branch} && " .
                    "chown -R 1000:1000 {$projectPath}/storage {$projectPath}/bootstrap/cache && " .
                    "chmod -R 775 {$projectPath}/storage {$projectPath}/bootstrap/cache";

                $pullResult = \Illuminate\Support\Facades\Process::timeout(120)->run("{$sshPrefix} \"{$gitCommand}\"");

                if (!$pullResult->successful()) {
                    throw new \Exception('Git pull failed: ' . $pullResult->errorOutput());
                }

                $logs[] = "✓ Repository updated successfully";
            } else {
                // Repository doesn't exist, clone it
                $logs[] = "Cloning repository...";

                // Run clone via SSH as root
                $cloneCommand = "git config --global safe.directory '*' && " .
                    "rm -rf {$projectPath} 2>/dev/null || true && " .
                    "git clone --branch {$project->branch} {$project->repository_url} {$projectPath} && " .
                    "chown -R 1000:1000 {$projectPath}/storage {$projectPath}/bootstrap/cache && " .
                    "chmod -R 775 {$projectPath}/storage {$projectPath}/bootstrap/cache";

                $cloneResult = \Illuminate\Support\Facades\Process::timeout(300)->run("{$sshPrefix} \"{$cloneCommand}\"");

                if (!$cloneResult->successful()) {
                    throw new \Exception('Git clone failed: ' . $cloneResult->errorOutput());
                }

                $logs[] = "✓ Repository cloned successfully";
            }
            $logs[] = "";

            // Get current commit information
            $logs[] = "=== Recording Commit Information ===";
            $gitService = app(GitService::class);
            $commitInfo = $gitService->getCurrentCommit($project);
            
            if ($commitInfo) {
                $logs[] = "Commit: {$commitInfo['short_hash']}";
                $logs[] = "Author: {$commitInfo['author']}";
                $logs[] = "Message: {$commitInfo['message']}";
                
                // Update project with commit info
                $project->update([
                    'current_commit_hash' => $commitInfo['hash'],
                    'current_commit_message' => $commitInfo['message'],
                    'last_commit_at' => now()->setTimestamp($commitInfo['timestamp']),
                ]);
                
                // Update deployment with commit info
                $this->deployment->update([
                    'commit_hash' => $commitInfo['hash'],
                    'commit_message' => $commitInfo['message'],
                ]);
                
                $logs[] = "✓ Commit information recorded";
            } else {
                $logs[] = "⚠ Could not retrieve commit information";
            }
            $logs[] = "";

            // Step 2: Build container
            $logs[] = "=== Building Docker Container ===";
            $logs[] = "Environment: " . ($project->environment ?? 'production');
            $logs[] = "This may take 10-20 minutes for large projects with npm builds...";
            $logs[] = "Please be patient!";
            $logs[] = "";
            
            // Save initial logs so user can see progress started
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);
            
            $buildResult = $dockerService->buildContainer($project);
            
            if (!$buildResult['success']) {
                throw new \Exception('Build failed: ' . ($buildResult['error'] ?? 'Unknown error'));
            }
            
            $logs[] = $buildResult['output'] ?? 'Build successful';
            $logs[] = "✓ Build successful";

            // Step 3: Stop old container if running
            $logs[] = "";
            $logs[] = "=== Stopping Old Container ===";
            $dockerService->stopContainer($project);
            $logs[] = "✓ Old container stopped (if any)";
            $logs[] = "";
            
            // Save logs before starting
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);

            // Step 4: Start new container
            $logs[] = "=== Starting Container ===";
            $logs[] = "Environment: " . ($project->environment ?? 'production');
            if ($project->env_variables && count((array)$project->env_variables) > 0) {
                $logs[] = "Custom Variables: " . count((array)$project->env_variables) . " variable(s)";
            }
            $logs[] = "Starting new container...";
            $startResult = $dockerService->startContainer($project);
            
            if (!$startResult['success']) {
                throw new \Exception('Start failed: ' . ($startResult['error'] ?? 'Unknown error'));
            }
            
            $logs[] = "Container started successfully";
            if (isset($startResult['message'])) {
                $logs[] = $startResult['message'];
            }
            $logs[] = "";

            // Step 5: Laravel Optimization (inside container)
            $logs[] = "=== Laravel Optimization ===";
            $logs[] = "Running Laravel optimization commands inside container...";

            // Determine the correct container name
            $usesCompose = $dockerService->usesDockerCompose($project);
            $containerName = $usesCompose
                ? $dockerService->getAppContainerName($project)
                : $project->slug;

            $logs[] = "Target container: {$containerName}";
            $logs[] = "";

            $optimizationCommands = [
                'composer install --optimize-autoloader --no-dev' => 'Installing/updating dependencies',
                'php artisan config:cache' => 'Caching configuration',
                'php artisan route:cache' => 'Caching routes',
                'php artisan view:cache' => 'Caching views',
                'php artisan event:cache' => 'Caching events',
                'php artisan migrate --force' => 'Running migrations',
                'php artisan storage:link' => 'Linking storage',
                'php artisan optimize' => 'Optimizing application',
            ];

            foreach ($optimizationCommands as $cmd => $description) {
                $logs[] = "→ {$description}...";
                $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'Command may have already run or not applicable'";
                $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                // Log output but don't fail deployment if optimization fails
                if ($result->successful() || str_contains($result->output(), 'already')) {
                    $logs[] = "  ✓ {$description} completed";
                } else {
                    $logs[] = "  ⚠ {$description} skipped or failed (not critical)";
                }
            }

            $logs[] = "✓ Laravel optimization completed";
            $logs[] = "";

            // Update logs with optimization progress
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);

            // Step 6: Fix Permissions & Clear Caches
            $logs[] = "=== Fixing Permissions & Clearing Caches ===";
            $logs[] = "Setting proper ownership and permissions...";

            try {
                // Fix permissions via SSH on the server
                $permissionCommand = "cd {$projectPath} && " .
                    "chown -R www-data:www-data storage bootstrap/cache && " .
                    "chmod -R 775 storage bootstrap/cache";

                $permResult = \Illuminate\Support\Facades\Process::timeout(60)->run("{$sshPrefix} \"{$permissionCommand}\"");

                if ($permResult->successful()) {
                    $logs[] = "  ✓ Permissions fixed (www-data:www-data, 775)";
                } else {
                    $logs[] = "  ⚠ Permission fix partially completed: " . $permResult->errorOutput();
                }

                // Clear Laravel caches inside container
                $logs[] = "Clearing Laravel caches...";
                $cacheCommands = [
                    'php artisan config:clear' => 'Configuration cache',
                    'php artisan cache:clear' => 'Application cache',
                    'php artisan view:clear' => 'View cache',
                    'php artisan route:clear' => 'Route cache',
                ];

                foreach ($cacheCommands as $cmd => $description) {
                    $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'skipped'";
                    $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                    if ($result->successful() && !str_contains($result->output(), 'skipped')) {
                        $logs[] = "  ✓ {$description} cleared";
                    } else {
                        $logs[] = "  ⚠ {$description} clear skipped";
                    }
                }

                $logs[] = "✓ Permissions and caches handled";

            } catch (\Exception $permError) {
                // Don't fail deployment if permission fix fails
                $logs[] = "  ⚠ Permission fix encountered an error (non-critical): " . $permError->getMessage();
                Log::warning('Permission fix failed but deployment continues', [
                    'deployment_id' => $this->deployment->id,
                    'error' => $permError->getMessage(),
                ]);
            }

            $logs[] = "";

            // Update logs with permission fix progress
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);

            // Update deployment and project
            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            $this->deployment->update([
                'status' => 'success',
                'completed_at' => $endTime,
                'duration_seconds' => $duration,
                'output_log' => implode("\n", $logs),
            ]);

            $project->update([
                'status' => 'running',
                'last_deployed_at' => now(),
            ]);

            Log::info('Deployment successful', [
                'deployment_id' => $this->deployment->id,
                'project_id' => $project->id,
            ]);

        } catch (\Exception $e) {
            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            $this->deployment->update([
                'status' => 'failed',
                'completed_at' => $endTime,
                'duration_seconds' => $duration,
                'error_log' => $e->getMessage(),
            ]);

            Log::error('Deployment failed', [
                'deployment_id' => $this->deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

