<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

class SupervisorConfigService
{
    use ExecutesRemoteCommands;

    /**
     * Generate supervisor worker config content.
     *
     * @param Project $project
     * @param array{queue_names?: string, num_workers?: int, max_tries?: int, max_time?: int, memory_limit?: int} $options
     * @return string
     */
    public function generateConfig(Project $project, array $options = []): string
    {
        $slug = $project->validated_slug;
        $queueNames = $options['queue_names'] ?? 'default';
        $numWorkers = $options['num_workers'] ?? 2;
        $maxTries = $options['max_tries'] ?? 3;
        $maxTime = $options['max_time'] ?? 3600;
        $memoryLimit = $options['memory_limit'] ?? 128;

        return <<<EOF
[program:{$slug}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/{$slug}/artisan queue:work --queue={$queueNames} --sleep=3 --tries={$maxTries} --max-time={$maxTime} --memory={$memoryLimit}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs={$numWorkers}
redirect_stderr=true
stdout_logfile=/var/www/{$slug}/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stopwaitsecs=3600
EOF;
    }

    /**
     * Install supervisor config on remote server.
     *
     * @param Server $server
     * @param Project $project
     * @param array{queue_names?: string, num_workers?: int, max_tries?: int, max_time?: int, memory_limit?: int} $options
     * @return bool
     */
    public function installConfig(Server $server, Project $project, array $options = []): bool
    {
        $slug = $project->validated_slug;
        $configPath = "/etc/supervisor/conf.d/{$slug}-worker.conf";
        $content = $this->generateConfig($project, $options);

        Log::info('Installing supervisor config', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        // Write config via tee to avoid escaping issues
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$configPath} > /dev/null",
            $content
        );

        // Reload supervisor
        $this->executeRemoteCommand($server, 'supervisorctl reread && supervisorctl update');

        Log::info('Supervisor config installed', ['project' => $slug]);

        return true;
    }

    /**
     * Remove supervisor config from remote server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function removeConfig(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $configPath = "/etc/supervisor/conf.d/{$slug}-worker.conf";

        Log::info('Removing supervisor config', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        // Stop workers first (non-throwing — may already be stopped)
        $this->executeRemoteCommand(
            $server,
            "supervisorctl stop {$slug}-worker:* 2>/dev/null || true",
            false
        );

        // Remove config file
        $this->executeRemoteCommand($server, "rm -f {$configPath}");

        // Update supervisor
        $this->executeRemoteCommand($server, 'supervisorctl reread && supervisorctl update');

        Log::info('Supervisor config removed', ['project' => $slug]);

        return true;
    }

    /**
     * Restart queue workers on remote server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function restartWorkers(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;

        Log::info('Restarting workers', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        // Signal Laravel queue workers to restart after current job
        $this->executeRemoteCommand(
            $server,
            "cd /var/www/{$slug} && php artisan queue:restart",
            false
        );

        // Restart supervisor group
        $this->executeRemoteCommand(
            $server,
            "supervisorctl restart {$slug}-worker:*",
            false
        );

        return true;
    }

    /**
     * Get worker status from remote server.
     *
     * @param Server $server
     * @param Project $project
     * @return array<int, array{name: string, status: string, pid: string, uptime: string}>
     */
    public function getWorkerStatus(Server $server, Project $project): array
    {
        $slug = $project->validated_slug;

        $output = $this->getRemoteOutput(
            $server,
            "supervisorctl status {$slug}-worker:* 2>/dev/null || true",
            false
        );

        $workers = [];
        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, 'no such process') || str_contains($line, 'ERROR')) {
                continue;
            }

            // Parse supervisor status output: "name  STATUS  pid PID, uptime HH:MM:SS"
            if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED)\s+(.*)$/', $line, $matches)) {
                $workers[] = [
                    'name' => $matches[1],
                    'status' => $matches[2],
                    'pid' => $matches[3],
                    'uptime' => $matches[3],
                ];
            }
        }

        return $workers;
    }
}
