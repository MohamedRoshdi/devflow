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

            // Step 1: Clone repository from GitHub
            $logs[] = "=== Cloning Repository ===";
            $logs[] = "Repository: {$project->repository_url}";
            $logs[] = "Branch: {$project->branch}";
            $logs[] = "Path: {$projectPath}";
            
            // Remove old directory if exists
            if (file_exists($projectPath)) {
                $logs[] = "Removing old project directory...";
                \Illuminate\Support\Facades\Process::run("rm -rf {$projectPath}");
            }
            
            // Clone repository
            $logs[] = "Cloning repository...";
            $cloneResult = \Illuminate\Support\Facades\Process::run(
                "git clone --branch {$project->branch} {$project->repository_url} {$projectPath}"
            );
            
            if (!$cloneResult->successful()) {
                throw new \Exception('Git clone failed: ' . $cloneResult->errorOutput());
            }
            
            $logs[] = "✓ Repository cloned successfully";
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

            // Step 2: Stop old container if running
            $logs[] = "\nStopping old container...";
            $dockerService->stopContainer($project);

            // Step 3: Start new container
            $logs[] = "\nStarting new container...";
            $startResult = $dockerService->startContainer($project);
            
            if (!$startResult['success']) {
                throw new \Exception('Start failed: ' . ($startResult['error'] ?? 'Unknown error'));
            }
            
            $logs[] = "Container started successfully with ID: " . ($startResult['container_id'] ?? 'unknown');

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

