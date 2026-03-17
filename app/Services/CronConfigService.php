<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

class CronConfigService
{
    use ExecutesRemoteCommands;

    /**
     * Generate cron config content for a project.
     *
     * Produces a valid /etc/cron.d/ file with the required user field.
     */
    public function generateConfig(Project $project): string
    {
        $slug = $project->slug;
        $deployPath = $project->deploy_path ?? "/var/www/{$slug}";

        return <<<CRON
        # DevFlow managed cron for {$slug}
        * * * * * www-data php {$deployPath}/artisan schedule:run >> /dev/null 2>&1

        CRON;
    }

    /**
     * Install the cron config on a server.
     *
     * Writes to /etc/cron.d/{slug}-scheduler with correct permissions (644).
     *
     *
     * @throws \RuntimeException
     */
    public function installConfig(Server $server, Project $project): bool
    {
        $slug = $project->slug;
        $filePath = "/etc/cron.d/{$slug}-scheduler";
        $config = $this->generateConfig($project);

        Log::info('Installing cron config', [
            'server' => $server->name,
            'project' => $slug,
            'path' => $filePath,
        ]);

        // Write cron file via tee
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$filePath} > /dev/null",
            $config
        );

        // /etc/cron.d/ files must be owned by root and mode 644
        $this->executeRemoteCommand($server, "chmod 644 {$filePath}");

        Log::info('Cron config installed successfully', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        return true;
    }

    /**
     * Remove the cron config from a server.
     */
    public function removeConfig(Server $server, Project $project): bool
    {
        $slug = $project->slug;
        $filePath = "/etc/cron.d/{$slug}-scheduler";

        Log::info('Removing cron config', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        $this->executeRemoteCommand($server, "rm -f {$filePath}", false);

        return true;
    }

    /**
     * Check if the cron config is installed on a server.
     */
    public function isInstalled(Server $server, Project $project): bool
    {
        $slug = $project->slug;
        $filePath = "/etc/cron.d/{$slug}-scheduler";

        $result = $this->executeRemoteCommand($server, "test -f {$filePath} && echo 'exists'", false);

        return str_contains($result->output(), 'exists');
    }
}
