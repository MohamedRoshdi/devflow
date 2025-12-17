<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use Symfony\Component\Process\Process;

class StorageService
{
    public function calculateProjectStorage(Project $project): int
    {
        if (! $project->server) {
            return 0;
        }

        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            $command = $this->buildSSHCommand(
                $server,
                "du -sm {$projectPath} | cut -f1"
            );

            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $sizeMB = (int) trim($process->getOutput());

                $project->update(['storage_used_mb' => $sizeMB]);

                return $sizeMB;
            }

            return 0;
        } catch (\Exception $e) {
            \Log::error('Failed to calculate project storage', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function getTotalStorageUsed(): array
    {
        $totalMB = Project::sum('storage_used_mb');
        $maxGB = (int) config('app.max_storage_gb', 100);
        $maxMB = $maxGB * 1024;

        return [
            'used_mb' => $totalMB,
            'used_gb' => round($totalMB / 1024, 2),
            'max_gb' => $maxGB,
            'percentage' => $maxMB > 0 ? round(($totalMB / $maxMB) * 100, 2) : 0,
        ];
    }

    public function cleanupProjectStorage(Project $project): bool
    {
        if (! $project->server) {
            return false;
        }

        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Clean logs, cache, temp files
            $cleanupCommands = [
                "rm -rf {$projectPath}/storage/logs/*",
                "rm -rf {$projectPath}/storage/framework/cache/*",
                "rm -rf {$projectPath}/storage/framework/sessions/*",
                "rm -rf {$projectPath}/storage/framework/views/*",
            ];

            foreach ($cleanupCommands as $cmd) {
                $command = $this->buildSSHCommand($server, $cmd);
                $process = Process::fromShellCommandline($command);
                $process->run();
            }

            // Recalculate storage
            $this->calculateProjectStorage($project);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to cleanup project storage', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

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

        return sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($remoteCommand)
        );
    }
}
