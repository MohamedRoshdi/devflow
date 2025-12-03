<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Project;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\PipelineStageRun;
use App\Models\Deployment;
use App\Events\PipelineStageUpdated;
use App\Events\DeploymentLogUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class PipelineExecutionService
{
    /**
     * Execute complete pipeline for a project
     */
    public function executePipeline(Project $project, array $triggerData): PipelineRun
    {
        // Create pipeline run record
        $pipelineRun = PipelineRun::create([
            'project_id' => $project->id,
            'deployment_id' => $triggerData['deployment_id'] ?? null,
            'status' => 'pending',
            'triggered_by' => $triggerData['triggered_by'] ?? 'manual',
            'trigger_data' => $triggerData,
            'branch' => $project->branch,
            'commit_sha' => $triggerData['commit_sha'] ?? null,
        ]);

        // Mark as running
        $pipelineRun->markRunning();

        try {
            // Get all enabled stages ordered by type and order
            $stages = PipelineStage::where('project_id', $project->id)
                ->enabled()
                ->ordered()
                ->get()
                ->groupBy('type');

            // Execute stages in sequence: pre_deploy -> deploy -> post_deploy
            $stageTypes = ['pre_deploy', 'deploy', 'post_deploy'];

            foreach ($stageTypes as $type) {
                if (!isset($stages[$type])) {
                    continue;
                }

                foreach ($stages[$type] as $stage) {
                    $success = $this->executeStage($pipelineRun, $stage);

                    // If stage failed and continue_on_failure is false, stop pipeline
                    if (!$success && !$stage->continue_on_failure) {
                        $pipelineRun->markFailed();
                        $this->logToPipeline($pipelineRun, "Pipeline stopped due to stage failure: {$stage->name}");
                        return $pipelineRun;
                    }
                }
            }

            // All stages completed successfully
            $pipelineRun->markSuccess();
            $this->logToPipeline($pipelineRun, "Pipeline completed successfully");

        } catch (\Exception $e) {
            $pipelineRun->markFailed();
            $this->logToPipeline($pipelineRun, "Pipeline failed with exception: {$e->getMessage()}");

            Log::error('Pipeline execution failed', [
                'pipeline_run_id' => $pipelineRun->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $pipelineRun;
    }

    /**
     * Execute a single pipeline stage
     */
    public function executeStage(PipelineRun $pipelineRun, PipelineStage $stage): bool
    {
        // Create stage run record
        $stageRun = PipelineStageRun::create([
            'pipeline_run_id' => $pipelineRun->id,
            'pipeline_stage_id' => $stage->id,
            'status' => 'pending',
        ]);

        try {
            // Mark stage as running
            $stageRun->markRunning();
            $this->broadcastStageUpdate($pipelineRun, $stageRun, "Starting stage: {$stage->name}");

            $project = $pipelineRun->project;
            $server = $project->server;
            $commands = $stage->commands ?? [];

            if (empty($commands)) {
                $stageRun->markSkipped();
                $this->broadcastStageUpdate($pipelineRun, $stageRun, "Stage skipped: No commands defined");
                return true;
            }

            // Execute each command
            foreach ($commands as $index => $command) {
                if (empty(trim($command))) {
                    continue;
                }

                $this->broadcastStageUpdate($pipelineRun, $stageRun, "Executing command " . ($index + 1) . "/" . count($commands));
                $this->broadcastStageUpdate($pipelineRun, $stageRun, "$ {$command}");

                $success = $this->executeCommand($server, $project, $command, $stageRun, $stage->timeout_seconds);

                if (!$success) {
                    $errorMsg = "Command failed: {$command}";
                    $stageRun->markFailed($errorMsg);
                    $this->broadcastStageUpdate($pipelineRun, $stageRun, $errorMsg);
                    return false;
                }
            }

            // Stage completed successfully
            $stageRun->markSuccess();
            $this->broadcastStageUpdate($pipelineRun, $stageRun, "Stage completed: {$stage->name}");
            return true;

        } catch (\Exception $e) {
            $errorMsg = "Stage failed with exception: {$e->getMessage()}";
            $stageRun->markFailed($errorMsg);
            $this->broadcastStageUpdate($pipelineRun, $stageRun, $errorMsg);

            Log::error('Pipeline stage execution failed', [
                'stage_run_id' => $stageRun->id,
                'stage_id' => $stage->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute a command via SSH
     */
    private function executeCommand(
        $server,
        Project $project,
        string $command,
        PipelineStageRun $stageRun,
        int $timeout = 600
    ): bool {
        $projectPath = "/var/www/{$project->slug}";

        // Build SSH command
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        // Wrap command to execute in project directory
        $wrappedCommand = "cd {$projectPath} && {$command}";
        $fullCommand = "{$sshPrefix} \"{$wrappedCommand}\"";

        try {
            // Execute command with real-time output
            $process = SymfonyProcess::fromShellCommandline($fullCommand);
            $process->setTimeout($timeout);

            $process->run(function ($type, $buffer) use ($stageRun) {
                // Append output to stage run and broadcast
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $stageRun->appendOutput($line);
                        $this->broadcastLogLine($stageRun, $line);
                    }
                }
            });

            return $process->isSuccessful();

        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $errorMsg = "Command timed out after {$timeout} seconds";
            $stageRun->appendOutput($errorMsg);
            $this->broadcastLogLine($stageRun, $errorMsg);
            return false;

        } catch (\Exception $e) {
            $errorMsg = "Command execution failed: {$e->getMessage()}";
            $stageRun->appendOutput($errorMsg);
            $this->broadcastLogLine($stageRun, $errorMsg);
            return false;
        }
    }

    /**
     * Broadcast stage update via WebSocket
     */
    private function broadcastStageUpdate(PipelineRun $pipelineRun, PipelineStageRun $stageRun, string $message): void
    {
        broadcast(new PipelineStageUpdated(
            $pipelineRun->id,
            $stageRun->id,
            $stageRun->pipelineStage->name,
            $stageRun->status,
            $message,
            $this->calculateProgress($pipelineRun)
        ));

        // Also broadcast to deployment log if there's a deployment
        if ($pipelineRun->deployment_id) {
            broadcast(new DeploymentLogUpdated(
                $pipelineRun->deployment_id,
                "[{$stageRun->pipelineStage->name}] {$message}",
                'info'
            ));
        }
    }

    /**
     * Broadcast individual log line
     */
    private function broadcastLogLine(PipelineStageRun $stageRun, string $line): void
    {
        $pipelineRun = $stageRun->pipelineRun;

        broadcast(new PipelineStageUpdated(
            $pipelineRun->id,
            $stageRun->id,
            $stageRun->pipelineStage->name,
            $stageRun->status,
            $line,
            $this->calculateProgress($pipelineRun)
        ));

        // Also broadcast to deployment log if there's a deployment
        if ($pipelineRun->deployment_id) {
            $level = $this->detectLogLevel($line);
            broadcast(new DeploymentLogUpdated(
                $pipelineRun->deployment_id,
                $line,
                $level
            ));
        }
    }

    /**
     * Calculate overall pipeline progress
     */
    private function calculateProgress(PipelineRun $pipelineRun): int
    {
        $totalStages = $pipelineRun->stageRuns()->count();

        if ($totalStages === 0) {
            return 0;
        }

        $completedStages = $pipelineRun->stageRuns()
            ->whereIn('status', ['success', 'failed', 'skipped'])
            ->count();

        return (int) (($completedStages / $totalStages) * 100);
    }

    /**
     * Detect log level based on content
     */
    private function detectLogLevel(string $line): string
    {
        $lowerLine = strtolower($line);

        if (preg_match('/^(error|fatal|failed)/i', $line) ||
            str_contains($lowerLine, 'exception') ||
            str_contains($lowerLine, 'fatal error')) {
            return 'error';
        }

        if (preg_match('/^(warning|warn|notice)/i', $line) ||
            str_contains($lowerLine, 'deprecated')) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Log message to pipeline run
     */
    private function logToPipeline(PipelineRun $pipelineRun, string $message): void
    {
        $logs = $pipelineRun->logs ?? [];
        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
        ];

        $pipelineRun->update(['logs' => $logs]);
    }

    /**
     * Rollback to previous successful deployment
     */
    public function rollback(PipelineRun $failedRun): ?PipelineRun
    {
        $project = $failedRun->project;

        // Find last successful deployment
        $lastSuccessfulDeployment = Deployment::where('project_id', $project->id)
            ->where('status', 'success')
            ->where('id', '<', $failedRun->deployment_id ?? 0)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastSuccessfulDeployment) {
            Log::warning('No previous successful deployment found for rollback', [
                'project_id' => $project->id,
                'failed_run_id' => $failedRun->id,
            ]);
            return null;
        }

        // Create new deployment for rollback
        $rollbackDeployment = Deployment::create([
            'project_id' => $project->id,
            'server_id' => $project->server_id,
            'user_id' => $failedRun->deployment->user_id ?? null,
            'status' => 'pending',
            'triggered_by' => 'rollback',
            'commit_hash' => $lastSuccessfulDeployment->commit_hash,
            'commit_message' => "Rollback to: {$lastSuccessfulDeployment->commit_message}",
            'branch' => $project->branch,
            'rollback_deployment_id' => $lastSuccessfulDeployment->id,
        ]);

        // Execute pipeline for rollback
        return $this->executePipeline($project, [
            'triggered_by' => 'rollback',
            'deployment_id' => $rollbackDeployment->id,
            'commit_sha' => $lastSuccessfulDeployment->commit_hash,
            'rollback_from' => $failedRun->id,
        ]);
    }

    /**
     * Cancel a running pipeline
     */
    public function cancelPipeline(PipelineRun $pipelineRun): void
    {
        if (!$pipelineRun->isRunning()) {
            return;
        }

        // Mark all running stage runs as cancelled
        $pipelineRun->stageRuns()
            ->where('status', 'running')
            ->each(function (PipelineStageRun $stageRun) {
                $stageRun->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                    'error_message' => 'Pipeline cancelled by user',
                ]);
            });

        // Mark pending stages as skipped
        $pipelineRun->stageRuns()
            ->where('status', 'pending')
            ->each(function (PipelineStageRun $stageRun) {
                $stageRun->markSkipped();
            });

        $pipelineRun->markCancelled();
        $this->logToPipeline($pipelineRun, 'Pipeline cancelled by user');
    }
}
