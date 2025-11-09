<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Services\DockerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

            // Step 1: Build container
            $logs[] = "Building Docker container...";
            $buildResult = $dockerService->buildContainer($project);
            
            if (!$buildResult['success']) {
                throw new \Exception('Build failed: ' . ($buildResult['error'] ?? 'Unknown error'));
            }
            
            $logs[] = $buildResult['output'] ?? 'Build successful';

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

