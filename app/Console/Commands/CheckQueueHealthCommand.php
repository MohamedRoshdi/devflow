<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Server;
use App\Services\Monitoring\QueueHealthService;
use Illuminate\Console\Command;

class CheckQueueHealthCommand extends Command
{
    protected $signature = 'monitoring:check-queue-health
                            {--server= : Only check a specific server ID}
                            {--project= : Only check a specific project ID}
                            {--threshold=30 : Minutes before a reserved job is considered stuck}';

    protected $description = 'Check queue worker health and job backlog on all online servers';

    public function handle(QueueHealthService $service): int
    {
        $serverId = $this->option('server');
        $projectId = $this->option('project');
        $threshold = (int) $this->option('threshold');

        if ($projectId !== null) {
            $project = Project::with('server')->find($projectId);

            if ($project === null) {
                $this->error("Project #{$projectId} not found.");

                return self::FAILURE;
            }

            if ($project->server === null) {
                $this->error("Project #{$projectId} has no server assigned.");

                return self::FAILURE;
            }

            $this->checkProjectHealth($service, $project->server, $project, $threshold);

            return self::SUCCESS;
        }

        $query = Server::where('status', 'online');

        if ($serverId !== null) {
            $query->where('id', $serverId);
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->info('No online servers found.');

            return self::SUCCESS;
        }

        $this->info("Checking queue health on {$servers->count()} server(s)...");
        $this->newLine();

        foreach ($servers as $server) {
            $this->checkServerHealth($service, $server, $threshold);
        }

        return self::SUCCESS;
    }

    private function checkServerHealth(QueueHealthService $service, Server $server, int $threshold): void
    {
        $this->line("<fg=cyan>Server: {$server->name} ({$server->ip_address})</>");

        $workers = $service->checkWorkers($server);

        if ($workers === []) {
            $this->warn('  No queue workers found in supervisorctl output.');
        } else {
            foreach ($workers as $worker) {
                $icon = $worker['healthy'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $uptime = $worker['uptime'] ? " — uptime {$worker['uptime']}" : '';
                $this->line("  {$icon} {$worker['name']}: {$worker['status']}{$uptime}");
            }
        }

        // Check project-level metrics for each project on this server
        $projects = $server->projects()->get();

        foreach ($projects as $project) {
            $deployPath = $project->deploy_path ?? ("/var/www/{$project->slug}");
            $this->checkProjectHealth($service, $server, $project, $threshold);
        }

        $this->newLine();
    }

    private function checkProjectHealth(QueueHealthService $service, Server $server, Project $project, int $threshold): void
    {
        $deployPath = $project->deploy_path ?? ("/var/www/{$project->slug}");

        $failedJobs = $service->getFailedJobCount($server, $deployPath);
        $stuckJobs = $service->getStuckJobCount($server, $deployPath, $threshold);

        if ($failedJobs < 0) {
            $this->warn("  [{$project->name}] Could not query failed_jobs table.");
        } else {
            $icon = $failedJobs > 50 ? '<fg=yellow>⚠</>' : '<fg=green>✓</>';
            $this->line("  {$icon} [{$project->name}] Failed jobs: {$failedJobs}");
        }

        if ($stuckJobs < 0) {
            $this->warn("  [{$project->name}] Could not check for stuck jobs.");
        } elseif ($stuckJobs > 0) {
            $this->line("  <fg=yellow>⚠</> [{$project->name}] Stuck jobs (>{$threshold}min): {$stuckJobs}");
        } else {
            $this->line("  <fg=green>✓</> [{$project->name}] No stuck jobs.");
        }
    }
}
