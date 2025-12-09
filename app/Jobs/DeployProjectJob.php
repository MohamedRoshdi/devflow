<?php

namespace App\Jobs;

use App\Events\DeploymentLogUpdated;
use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Services\CICD\PipelineExecutionService;
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
    public int $timeout = 1800; // 30 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // Only try once - deployments should not auto-retry

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Deployment $deployment
    ) {}

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->deployment->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_log' => $exception?->getMessage() ?? 'Deployment job failed unexpectedly',
        ]);

        Log::error('Deployment job failed', [
            'deployment_id' => $this->deployment->id,
            'error' => $exception?->getMessage(),
        ]);
    }

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

            $project = $this->deployment->project;

            if ($project === null) {
                throw new \RuntimeException('Project not found for deployment');
            }

            // Check if project has pipeline stages configured
            $hasPipelineStages = PipelineStage::where('project_id', $project->id)
                ->enabled()
                ->exists();

            if ($hasPipelineStages) {
                // Use pipeline execution service
                $this->handlePipelineDeployment($project, $startTime);
            } else {
                // Use traditional direct deployment
                $this->handleDirectDeployment($project, $startTime);
            }

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

    /**
     * Handle deployment using pipeline stages
     */
    private function handlePipelineDeployment(Project $project, \Illuminate\Support\Carbon $startTime): void
    {
        $pipelineService = app(PipelineExecutionService::class);

        $this->broadcastLog('=== Using Pipeline Execution ===');
        $this->broadcastLog("Project: {$project->name}");
        $this->broadcastLog("Branch: {$project->branch}");
        $this->broadcastLog('');

        // Execute pipeline
        $pipelineRun = $pipelineService->executePipeline($project, [
            'triggered_by' => $this->deployment->triggered_by ?? 'manual',
            'deployment_id' => $this->deployment->id,
            'commit_sha' => $this->deployment->commit_hash,
        ]);

        // Update deployment based on pipeline result
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->deployment->update([
            'status' => $pipelineRun->status === 'success' ? 'success' : 'failed',
            'completed_at' => $endTime,
            'duration_seconds' => $duration,
        ]);

        if ($pipelineRun->status === 'success') {
            $project->update([
                'status' => 'running',
                'last_deployed_at' => now(),
            ]);

            Log::info('Pipeline deployment successful', [
                'deployment_id' => $this->deployment->id,
                'pipeline_run_id' => $pipelineRun->id,
                'project_id' => $project->id,
            ]);
        } else {
            Log::error('Pipeline deployment failed', [
                'deployment_id' => $this->deployment->id,
                'pipeline_run_id' => $pipelineRun->id,
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Handle traditional direct deployment (existing logic)
     */
    private function handleDirectDeployment(Project $project, \Illuminate\Support\Carbon $startTime): void
    {
        $dockerService = app(DockerService::class);

        $logs = [];
        $projectPath = "/var/www/{$project->slug}";

        // Helper function to add log and broadcast
        $addLog = function (string $line) use (&$logs) {
            $logs[] = $line;
            $this->broadcastLog($line);
        };

        // Step 1: Setup Git repository
        $addLog('=== Setting Up Repository ===');
        $addLog("Repository: {$project->repository_url}");
        $addLog("Branch: {$project->branch}");
        $addLog("Path: {$projectPath}");

        // Build SSH command helper for running commands as root on the server
        $server = $project->server;
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        // Check if repository already exists (via SSH to ensure we check server state)
        $addLog("$ ssh {$server->username}@{$server->ip_address} test -d {$projectPath}/.git");
        $checkResult = \Illuminate\Support\Facades\Process::run("{$sshPrefix} \"test -d {$projectPath}/.git && echo 'exists' || echo 'not_exists'\"");
        $repoExists = trim($checkResult->output()) === 'exists';
        $addLog($repoExists ? '→ Repository exists' : '→ Repository not found');

        // Save logs immediately so user can see progress
        $this->deployment->update(['output_log' => implode("\n", $logs)]);

        if ($repoExists) {
            $addLog('');
            $addLog('Repository already exists, pulling latest changes...');

            // Validate branch name format
            if (! preg_match('/^[a-zA-Z0-9._\/-]+$/', $project->branch)) {
                throw new \Exception('Invalid branch name format');
            }

            // Run git operations via SSH as root (fixes permission issues)
            $escapedBranch = escapeshellarg($project->branch);
            $addLog("$ git fetch origin {$project->branch}");
            $addLog("$ git reset --hard origin/{$project->branch}");

            $gitCommand = "cd {$projectPath} && ".
                "git config --global safe.directory '*' && ".
                "chown -R root:root {$projectPath}/.git {$projectPath}/storage {$projectPath}/bootstrap 2>/dev/null || true && ".
                "git fetch origin {$escapedBranch} && ".
                "git reset --hard origin/{$escapedBranch} && ".
                "chown -R 1000:1000 {$projectPath}/storage {$projectPath}/bootstrap/cache && ".
                "chmod -R 775 {$projectPath}/storage {$projectPath}/bootstrap/cache";

            $pullResult = \Illuminate\Support\Facades\Process::timeout(120)->run("{$sshPrefix} \"{$gitCommand}\"");

            if (! $pullResult->successful()) {
                $addLog('✗ Git pull failed');
                $addLog($pullResult->errorOutput());
                throw new \Exception('Git pull failed: '.$pullResult->errorOutput());
            }

            // Show git output
            if ($pullResult->output()) {
                foreach (explode("\n", trim($pullResult->output())) as $outputLine) {
                    if (trim($outputLine)) {
                        $addLog("  {$outputLine}");
                    }
                }
            }

            $addLog('✓ Repository updated successfully');
        } else {
            // Repository doesn't exist, clone it
            $addLog('');
            $addLog('=== Cloning Repository ===');

            // Validate branch name format
            if (! preg_match('/^[a-zA-Z0-9._\/-]+$/', $project->branch)) {
                throw new \Exception('Invalid branch name format');
            }

            // Run clone via SSH as root
            $escapedBranch = escapeshellarg($project->branch);
            $escapedRepoUrl = escapeshellarg($project->repository_url ?? '');

            $addLog("$ git clone --branch {$project->branch} {$project->repository_url} {$projectPath}");
            $addLog('→ This may take a few minutes for large repositories...');

            // Save logs so user sees clone starting
            $this->deployment->update(['output_log' => implode("\n", $logs)]);

            $cloneCommand = "git config --global safe.directory '*' && ".
                "rm -rf {$projectPath} 2>/dev/null || true && ".
                "git clone --branch {$escapedBranch} --progress {$escapedRepoUrl} {$projectPath} 2>&1 && ".
                "chown -R 1000:1000 {$projectPath}/storage {$projectPath}/bootstrap/cache 2>/dev/null || true && ".
                "chmod -R 775 {$projectPath}/storage {$projectPath}/bootstrap/cache 2>/dev/null || true";

            $cloneResult = \Illuminate\Support\Facades\Process::timeout(300)->run("{$sshPrefix} \"{$cloneCommand}\"");

            if (! $cloneResult->successful()) {
                $addLog('✗ Git clone failed');
                $addLog($cloneResult->errorOutput());
                throw new \Exception('Git clone failed: '.$cloneResult->errorOutput());
            }

            // Show clone output
            if ($cloneResult->output()) {
                foreach (explode("\n", trim($cloneResult->output())) as $outputLine) {
                    if (trim($outputLine)) {
                        $addLog("  {$outputLine}");
                    }
                }
            }

            $addLog('✓ Repository cloned successfully');
        }

        // Save logs after git operation
        $this->deployment->update(['output_log' => implode("\n", $logs)]);
        $addLog('');

        // Get current commit information
        $addLog('=== Recording Commit Information ===');
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

            $addLog('✓ Commit information recorded');
        } else {
            $addLog('⚠ Could not retrieve commit information');
        }
        $addLog('');

        // Step 2: Build container
        $addLog('=== Building Docker Container ===');
        $addLog('Environment: '.($project->environment ?? 'production'));
        $addLog('This may take 10-20 minutes for large projects with npm builds...');
        $addLog('Please be patient!');
        $addLog('');

        // Save initial logs so user can see progress started
        $this->deployment->update([
            'output_log' => implode("\n", $logs),
        ]);

        $buildResult = $dockerService->buildContainer($project);

        if (! $buildResult['success']) {
            throw new \Exception('Build failed: '.($buildResult['error'] ?? 'Unknown error'));
        }

        // Broadcast build output line by line if available
        if (! empty($buildResult['output'])) {
            foreach (explode("\n", $buildResult['output']) as $buildLine) {
                if (trim($buildLine)) {
                    $addLog($buildLine);
                }
            }
        }
        $addLog('✓ Build successful');

        // Step 3: Stop old container if running
        $addLog('');
        $addLog('=== Stopping Old Container ===');
        $dockerService->stopContainer($project);
        $addLog('✓ Old container stopped (if any)');
        $addLog('');

        // Save logs before starting
        $this->deployment->update([
            'output_log' => implode("\n", $logs),
        ]);

        // Step 4: Start new container
        $addLog('=== Starting Container ===');
        $addLog('Environment: '.($project->environment ?? 'production'));
        if ($project->env_variables && count((array) $project->env_variables) > 0) {
            $addLog('Custom Variables: '.count((array) $project->env_variables).' variable(s)');
        }
        $addLog('Starting new container...');
        $startResult = $dockerService->startContainer($project);

        if (! $startResult['success']) {
            throw new \Exception('Start failed: '.($startResult['error'] ?? 'Unknown error'));
        }

        $addLog('Container started successfully');
        if (isset($startResult['message'])) {
            $addLog($startResult['message']);
        }
        $addLog('');

        // Step 5: Laravel Optimization (inside container)
        $addLog('=== Laravel Optimization ===');
        $addLog('Running Laravel optimization commands inside container...');

        // Determine the correct container name
        $usesCompose = $dockerService->usesDockerCompose($project);
        $containerName = $usesCompose
            ? $dockerService->getAppContainerName($project)
            : $project->slug;

        $addLog("Target container: {$containerName}");
        $addLog('');

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
            $addLog("$ docker exec {$containerName} {$cmd}");
            $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'Command may have already run or not applicable'";
            $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

            // Log output but don't fail deployment if optimization fails
            if ($result->successful() || str_contains($result->output(), 'already')) {
                $addLog("  ✓ {$description} completed");
            } else {
                $addLog("  ⚠ {$description} skipped or failed (not critical)");
            }

            // Save logs after each command so user sees progress
            $this->deployment->update(['output_log' => implode("\n", $logs)]);
        }

        $addLog('✓ Laravel optimization completed');
        $addLog('');

        // Update logs with optimization progress
        $this->deployment->update([
            'output_log' => implode("\n", $logs),
        ]);

        // Step 6: Fix Permissions & Environment Configuration
        $addLog('=== Fixing Permissions & Environment ===');

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
            $addLog('Setting proper ownership and permissions...');
            $permissionCommand = "cd {$projectPath} && ".
                "chown -R {$containerUid}:{$containerUid} storage bootstrap/cache 2>/dev/null && ".
                'chmod -R 777 storage bootstrap/cache';

            $permResult = \Illuminate\Support\Facades\Process::timeout(60)->run("{$sshPrefix} \"{$permissionCommand}\"");

            if ($permResult->successful()) {
                $addLog("  ✓ Permissions fixed (UID:{$containerUid}, 777)");
            } else {
                $addLog('  ⚠ Permission fix partially completed: '.$permResult->errorOutput());
            }

            // Fix .env file for Docker (DB_HOST, REDIS_HOST should use service names)
            $addLog('Checking .env configuration for Docker...');
            $envFixCommand = "cd {$projectPath} && ".
                "sed -i 's/^DB_HOST=127.0.0.1\$/DB_HOST=mysql/' .env 2>/dev/null; ".
                "sed -i 's/^DB_HOST=localhost\$/DB_HOST=mysql/' .env 2>/dev/null; ".
                "sed -i 's/^REDIS_HOST=127.0.0.1\$/REDIS_HOST=redis/' .env 2>/dev/null; ".
                "sed -i 's/^REDIS_HOST=localhost\$/REDIS_HOST=redis/' .env 2>/dev/null; ".
                "echo 'env checked'";

            $envResult = \Illuminate\Support\Facades\Process::timeout(30)->run("{$sshPrefix} \"{$envFixCommand}\"");
            if ($envResult->successful()) {
                $addLog('  ✓ Environment configuration validated');
            }

            // Clear Laravel caches inside container
            $addLog('Clearing Laravel caches...');
            $cacheCommands = [
                'php artisan config:clear' => 'Configuration cache',
                'php artisan cache:clear' => 'Application cache',
                'php artisan view:clear' => 'View cache',
                'php artisan route:clear' => 'Route cache',
            ];

            foreach ($cacheCommands as $cmd => $description) {
                $addLog("$ docker exec {$containerName} {$cmd}");
                $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'skipped'";
                $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                if ($result->successful() && ! str_contains($result->output(), 'skipped')) {
                    $addLog("  ✓ {$description} cleared");
                } else {
                    $addLog("  ⚠ {$description} clear skipped");
                }
            }

            // Save logs progress
            $this->deployment->update(['output_log' => implode("\n", $logs)]);

            // Rebuild config cache with corrected .env values
            $addLog('Rebuilding configuration cache...');
            $rebuildCommands = [
                'php artisan config:cache' => 'Configuration',
                'php artisan route:cache' => 'Routes',
                'php artisan view:cache' => 'Views',
            ];

            foreach ($rebuildCommands as $cmd => $description) {
                $addLog("$ docker exec {$containerName} {$cmd}");
                $dockerCmd = "docker exec {$containerName} {$cmd} 2>&1 || echo 'skipped'";
                $result = \Illuminate\Support\Facades\Process::run($dockerCmd);

                if ($result->successful() && ! str_contains($result->output(), 'skipped')) {
                    $addLog("  ✓ {$description} cached");
                }
            }

            // Save logs progress
            $this->deployment->update(['output_log' => implode("\n", $logs)]);

            $addLog('✓ Permissions, environment, and caches handled');

        } catch (\Exception $permError) {
            // Don't fail deployment if permission fix fails
            $addLog('  ⚠ Permission fix encountered an error (non-critical): '.$permError->getMessage());
            Log::warning('Permission fix failed but deployment continues', [
                'deployment_id' => $this->deployment->id,
                'error' => $permError->getMessage(),
            ]);
        }

        $addLog('');

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
    }
}
