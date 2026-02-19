<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

class PhpFpmPoolService
{
    use ExecutesRemoteCommands;

    /**
     * Generate PHP-FPM pool configuration for a project.
     *
     * @param Project $project
     * @return string
     */
    public function generatePoolConfig(Project $project): string
    {
        $slug = $project->validated_slug;

        return <<<INI
        [{$slug}]
        user = www-data
        group = www-data
        listen = /run/php/{$slug}.sock
        listen.owner = www-data
        listen.group = www-data
        pm = dynamic
        pm.max_children = 10
        pm.start_servers = 2
        pm.min_spare_servers = 1
        pm.max_spare_servers = 3
        pm.max_requests = 500
        php_admin_value[error_log] = /var/log/php/{$slug}-error.log
        INI;
    }

    /**
     * Install the PHP-FPM pool config on a remote server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function installPool(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $phpVersion = $project->php_version ?? '8.4';
        $filePath = "/etc/php/{$phpVersion}/fpm/pool.d/{$slug}.conf";
        $config = $this->generatePoolConfig($project);

        Log::info('Installing PHP-FPM pool', [
            'server' => $server->name,
            'project' => $slug,
            'path' => $filePath,
        ]);

        // Ensure log directory exists
        $this->executeRemoteCommand($server, 'mkdir -p /var/log/php', false);

        // Write pool config via tee
        $this->executeRemoteCommandWithInput(
            $server,
            "tee {$filePath} > /dev/null",
            $config
        );

        // Restart PHP-FPM to pick up the new pool
        $this->reloadFpm($server, $project);

        Log::info('PHP-FPM pool installed', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        return true;
    }

    /**
     * Remove the PHP-FPM pool config from a remote server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function removePool(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $phpVersion = $project->php_version ?? '8.4';
        $filePath = "/etc/php/{$phpVersion}/fpm/pool.d/{$slug}.conf";

        Log::info('Removing PHP-FPM pool', [
            'server' => $server->name,
            'project' => $slug,
        ]);

        $this->executeRemoteCommand($server, "rm -f {$filePath}", false);

        // Reload FPM after removal
        $this->reloadFpm($server, $project);

        return true;
    }

    /**
     * Check if a PHP-FPM pool config is installed on the server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function isInstalled(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $phpVersion = $project->php_version ?? '8.4';
        $filePath = "/etc/php/{$phpVersion}/fpm/pool.d/{$slug}.conf";

        $result = $this->executeRemoteCommand($server, "test -f {$filePath} && echo 'exists'", false);

        return str_contains($result->output(), 'exists');
    }

    /**
     * Reload PHP-FPM service on the server.
     *
     * @param Server $server
     * @param Project $project
     * @return bool
     */
    public function reloadFpm(Server $server, Project $project): bool
    {
        $phpVersion = $project->php_version ?? '8.4';
        $service = 'php' . str_replace('.', '', $phpVersion) . '-fpm';

        // Try versioned service name first (e.g. php84-fpm), then dotted (php8.4-fpm)
        $result = $this->executeRemoteCommand(
            $server,
            "systemctl reload {$service} 2>/dev/null || systemctl reload php{$phpVersion}-fpm",
            false
        );

        return $result->successful();
    }
}
