<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

class BareMetalProcessService
{
    use ExecutesRemoteCommands;

    /**
     * Start a Bare Metal project by ensuring PHP-FPM and Nginx are serving it.
     *
     * @return array{success: bool, error?: string}
     */
    public function startProject(Project $project): array
    {
        $server = $project->server;
        if ($server === null) {
            return ['success' => false, 'error' => 'No server assigned to project'];
        }

        $slug = $project->slug;
        $phpVersion = $project->php_version ?? '8.4';

        try {
            // Check the symlink exists (project was deployed at least once)
            $checkResult = $this->executeRemoteCommand(
                $server,
                "test -L /var/www/{$slug} && echo 'exists' || echo 'missing'",
                false
            );

            if (str_contains($checkResult->output(), 'missing')) {
                return ['success' => false, 'error' => 'Project has not been deployed yet. Run a deployment first.'];
            }

            // Reload PHP-FPM to ensure the pool is active
            $this->executeRemoteCommand(
                $server,
                "systemctl reload php{$phpVersion}-fpm 2>/dev/null || systemctl restart php{$phpVersion}-fpm",
                false
            );

            // Reload Nginx to pick up vhost config
            $this->executeRemoteCommand($server, 'systemctl reload nginx', false);

            // Restart supervisor workers if configured
            $this->executeRemoteCommand(
                $server,
                "supervisorctl start {$slug}-worker:* 2>/dev/null || true",
                false
            );

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop a Bare Metal project by disabling its PHP-FPM pool and supervisor workers.
     *
     * @return array{success: bool, error?: string}
     */
    public function stopProject(Project $project): array
    {
        $server = $project->server;
        if ($server === null) {
            return ['success' => false, 'error' => 'No server assigned to project'];
        }

        $slug = $project->slug;

        try {
            // Stop supervisor workers
            $this->executeRemoteCommand(
                $server,
                "supervisorctl stop {$slug}-worker:* 2>/dev/null || true",
                false
            );

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
