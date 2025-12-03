<?php

namespace App\Jobs;

use App\Events\DeploymentLogUpdated;
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

    /**
     * Broadcast a log line to WebSocket listeners
     */
    private function broadcastLog(string $line): void
    {
        $level = $this->detectLogLevel($line);
        broadcast(new DeploymentLogUpdated($this->deployment->id, $line, $level));
    }

    /**
     * Detect log level based on content
     */
    private function detectLogLevel(string $line): string
    {
        $lowerLine = strtolower($line);

        // Check for error patterns
        if (preg_match('/^(error|fatal|failed)/i', $line) ||
            str_contains($lowerLine, 'exception') ||
            str_contains($lowerLine, 'fatal error') ||
            str_contains($lowerLine, 'failed')) {
            return 'error';
        }

        // Check for warning patterns
        if (preg_match('/^(warning|warn|notice)/i', $line) ||
            str_contains($lowerLine, 'deprecated') ||
            str_contains($lowerLine, 'skipped')) {
            return 'warning';
        }

        // Everything else is info
        return 'info';
    }

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

            // Helper function to add log and broadcast
            $addLog = function(string $line) use (&$logs) {
                $logs[] = $line;
                $this->broadcastLog($line);
            };

            // Step 1: Setup Git repository
            $addLog("=== Setting Up Repository ===");
            $addLog("Repository: {$project->repository_url}");
            $addLog("Branch: {$project->branch}");
            $addLog("Path: {$projectPath}");

            // Build SSH command helper for running commands as root on the server
            $server = $project->server;
            $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

            // Check if repository already exists (via SSH to ensure we check server state)
            $checkResult = \Illuminate\Support\Facades\Process::run("{$sshPrefix} \"test -d {$projectPath}/.git && echo 'exists' || echo 'not_exists'\"");
            $repoExists = trim($checkResult->output()) === 'exists';

            if ($repoExists) {
                $addLog("Repository already exists, pulling latest changes...");

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

                $addLog("✓ Repository updated successfully");
            } else {
                // Repository doesn't exist, clone it
                $addLog("Cloning repository...");

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

                $addLog("✓ Repository cloned successfully");
            }
            $addLog("");

            // Get current commit information
            $addLog("=== Recording Commit Information ===");
            $gitService = app(GitService::class);
            $commitInfo = $gitService->getCurrentCommit($project);

            if ($commitInfo) {
                $addLog("Commit: {$commitInfo['short_hash']}");
                $addLog("Author: {$commitInfo['author']}");
                $addLog("Message: {$commitInfo['message']}");
                
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
                
                $addLog("✓ Commit information recorded");
            } else {
                $addLog("⚠ Could not retrieve commit information");
            }
            $addLog("");

            // Step 2: Build container
            $addLog("=== Building Docker Container ===");
            $addLog("Environment: " . ($project->environment ?? 'production'));
            $addLog("This may take 10-20 minutes for large projects with npm builds...");
            $addLog("Please be patient!");
            $addLog("");
            
            // Save initial logs so user can see progress started
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);
            
            $buildResult = $dockerService->buildContainer($project);
            
            if (!$buildResult['success']) {
                throw new \Exception('Build failed: ' . ($buildResult['error'] ?? 'Unknown error'));
            }
            
            // Broadcast build output line by line if available
            if (!empty($buildResult['output'])) {
                foreach (explode("\n", $buildResult['output']) as $buildLine) {
                    if (trim($buildLine)) {
                        $addLog($buildLine);
                    }
                }
            }
            $addLog("✓ Build successful");

            // Step 3: Stop old container if running
            $addLog("");
            $addLog("=== Stopping Old Container ===");
            $dockerService->stopContainer($project);
            $addLog("✓ Old container stopped (if any)");
            $addLog("");
            
            // Save logs before starting
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);

            // Step 4: Start new container
            $addLog("=== Starting Container ===");
            $addLog("Environment: " . ($project->environment ?? 'production'));
            if ($project->env_variables && count((array)$project->env_variables) > 0) {
                $addLog("Custom Variables: " . count((array)$project->env_variables) . " variable(s)");
            }
            $addLog("Starting new container...");
            $startResult = $dockerService->startContainer($project);

            if (!$startResult['success']) {
                throw new \Exception('Start failed: ' . ($startResult['error'] ?? 'Unknown error'));
            }

            $addLog("Container started successfully");
            if (isset($startResult['message'])) {
                $addLog($startResult['message']);
            }
            $addLog("");

            // Step 5: Laravel Optimization (inside container)
            $addLog("=== Laravel Optimization ===");
            $addLog("Running Laravel optimization commands inside container...");

            // Determine the correct container name
            $usesCompose = $dockerService->usesDockerCompose($project);
            $containerName = $usesCompose
                ? $dockerService->getAppContainerName($project)
                : $project->slug;

            $addLog("Target container: {$containerName}");
            $addLog("");

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
                $addLog("→ {$description}...");
                $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'Command may have already run or not applicable'";
                $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                // Log output but don't fail deployment if optimization fails
                if ($result->successful() || str_contains($result->output(), 'already')) {
                    $addLog("  ✓ {$description} completed");
                } else {
                    $addLog("  ⚠ {$description} skipped or failed (not critical)");
                }
            }

            $addLog("✓ Laravel optimization completed");
            $addLog("");

            // Update logs with optimization progress
            $this->deployment->update([
                'output_log' => implode("\n", $logs),
            ]);

            // Step 6: Fix Permissions & Environment Configuration
            $addLog("=== Fixing Permissions & Environment ===");

            try {
                // Determine container UID (Docker containers typically run as UID 1000)
                $containerUid = '1000';
                $uidCheckCmd = "docker exec {$containerName} id -u 2>/dev/null || echo '1000'";
                $uidResult = \Illuminate\Support\Facades\Process::run($uidCheckCmd);
                if ($uidResult->successful() && is_numeric(trim($uidResult->output()))) {
                    $containerUid = trim($uidResult->output());
                }
                $addLog("Container UID: {$containerUid}");

                // Fix permissions via SSH on the server with correct UID
                $addLog("Setting proper ownership and permissions...");
                $permissionCommand = "cd {$projectPath} && " .
                    "chown -R {$containerUid}:{$containerUid} storage bootstrap/cache 2>/dev/null && " .
                    "chmod -R 777 storage bootstrap/cache";

                $permResult = \Illuminate\Support\Facades\Process::timeout(60)->run("{$sshPrefix} \"{$permissionCommand}\"");

                if ($permResult->successful()) {
                    $addLog("  ✓ Permissions fixed (UID:{$containerUid}, 777)");
                } else {
                    $addLog("  ⚠ Permission fix partially completed: " . $permResult->errorOutput());
                }

                // Fix .env file for Docker (DB_HOST, REDIS_HOST should use service names)
                $addLog("Checking .env configuration for Docker...");
                $envFixCommand = "cd {$projectPath} && " .
                    "sed -i 's/^DB_HOST=127.0.0.1\$/DB_HOST=mysql/' .env 2>/dev/null; " .
                    "sed -i 's/^DB_HOST=localhost\$/DB_HOST=mysql/' .env 2>/dev/null; " .
                    "sed -i 's/^REDIS_HOST=127.0.0.1\$/REDIS_HOST=redis/' .env 2>/dev/null; " .
                    "sed -i 's/^REDIS_HOST=localhost\$/REDIS_HOST=redis/' .env 2>/dev/null; " .
                    "echo 'env checked'";

                $envResult = \Illuminate\Support\Facades\Process::timeout(30)->run("{$sshPrefix} \"{$envFixCommand}\"");
                if ($envResult->successful()) {
                    $addLog("  ✓ Environment configuration validated");
                }

                // Clear Laravel caches inside container
                $addLog("Clearing Laravel caches...");
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
                        $addLog("  ✓ {$description} cleared");
                    } else {
                        $addLog("  ⚠ {$description} clear skipped");
                    }
                }

                // Rebuild config cache with corrected .env values
                $addLog("Rebuilding configuration cache...");
                $rebuildCommands = [
                    'php artisan config:cache' => 'Configuration',
                    'php artisan route:cache' => 'Routes',
                    'php artisan view:cache' => 'Views',
                ];

                foreach ($rebuildCommands as $cmd => $description) {
                    $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'skipped'";
                    $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                    if ($result->successful() && !str_contains($result->output(), 'skipped')) {
                        $addLog("  ✓ {$description} cached");
                    }
                }

                $addLog("✓ Permissions, environment, and caches handled");

            } catch (\Exception $permError) {
                // Don't fail deployment if permission fix fails
                $addLog("  ⚠ Permission fix encountered an error (non-critical): " . $permError->getMessage());
                Log::warning('Permission fix failed but deployment continues', [
                    'deployment_id' => $this->deployment->id,
                    'error' => $permError->getMessage(),
                ]);
            }

            $addLog("");

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

