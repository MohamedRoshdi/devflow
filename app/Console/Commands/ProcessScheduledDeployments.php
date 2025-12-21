<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\ScheduledDeployment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledDeployments extends Command
{
    protected $signature = 'deployments:process-scheduled';

    protected $description = 'Process scheduled deployments that are due';

    public function handle(): int
    {
        $dueDeployments = ScheduledDeployment::due()
            ->with('project', 'user')
            ->get();

        if ($dueDeployments->isEmpty()) {
            $this->info('No scheduled deployments due.');

            return Command::SUCCESS;
        }

        $this->info("Processing {$dueDeployments->count()} scheduled deployment(s)...");

        foreach ($dueDeployments as $scheduled) {
            $this->processScheduledDeployment($scheduled);
        }

        return Command::SUCCESS;
    }

    protected function processScheduledDeployment(ScheduledDeployment $scheduled): void
    {
        $this->info("Processing deployment for project: {$scheduled->project->name}");

        try {
            // Mark as running
            $scheduled->update(['status' => 'running']);

            // Create the deployment record
            $deployment = Deployment::create([
                'user_id' => $scheduled->user_id,
                'project_id' => $scheduled->project_id,
                'server_id' => $scheduled->project->server_id,
                'branch' => $scheduled->branch,
                'status' => 'pending',
                'triggered_by' => 'scheduled',
                'started_at' => now(),
            ]);

            // Dispatch the deployment job
            DeployProjectJob::dispatch($deployment);

            // Update scheduled deployment with result
            $scheduled->update([
                'status' => 'completed',
                'deployment_id' => $deployment->id,
                'executed_at' => now(),
            ]);

            $this->info("Deployment started successfully for {$scheduled->project->name}");
            Log::info('Scheduled deployment executed', [
                'scheduled_deployment_id' => $scheduled->id,
                'deployment_id' => $deployment->id,
                'project' => $scheduled->project->name,
            ]);

        } catch (\Exception $e) {
            $scheduled->update([
                'status' => 'failed',
                'executed_at' => now(),
            ]);

            $this->error("Failed to process deployment for {$scheduled->project->name}: {$e->getMessage()}");
            Log::error('Scheduled deployment failed', [
                'scheduled_deployment_id' => $scheduled->id,
                'project' => $scheduled->project->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
