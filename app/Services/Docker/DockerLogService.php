<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker container log management.
 *
 * Handles:
 * - Container log retrieval
 * - Laravel-specific log operations
 * - Log clearing and downloading
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerLogService
{
    use ExecutesRemoteCommands;

    /**
     * Get container logs
     *
     * @return array{success: bool, logs?: string, source?: string, error?: string}
     */
    public function getContainerLogs(Project $project, int $lines = 100): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            // Check if project uses docker-compose
            $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
            $checkResult = $this->executeRemoteCommand($server, $checkComposeCmd, false);
            $usesCompose = trim($checkResult->output()) === 'compose';

            if ($usesCompose) {
                $logsCommand = "cd {$projectPath} && docker compose logs --tail {$lines} app 2>/dev/null || docker compose logs --tail {$lines} 2>/dev/null | tail -n {$lines}";
                $result = $this->executeRemoteCommand($server, $logsCommand, false);

                return [
                    'success' => true,
                    'logs' => $result->output() ?: $result->errorOutput(),
                    'source' => 'docker-compose',
                ];
            }

            // Standalone container mode
            $escapedSlug = escapeshellarg($slug);
            $logsCommand = "docker logs --tail {$lines} ".$escapedSlug;
            $result = $this->executeRemoteCommand($server, $logsCommand, false);

            return [
                'success' => $result->successful(),
                'logs' => $result->output(),
                'source' => 'container',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Laravel-specific logs from container or host
     *
     * @return array{success: bool, logs?: string, source?: string, error?: string}
     */
    public function getLaravelLogs(Project $project, int $lines = 200): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $containerPath = '/var/www/storage/logs/laravel.log';
            $hostPath = "/var/www/{$slug}/storage/logs/laravel.log";

            $escapedSlug = escapeshellarg($slug);
            $dockerCommand = "docker exec {$escapedSlug} sh -c 'if [ -f {$containerPath} ]; then tail -n {$lines} {$containerPath}; else echo \"Laravel log not found inside container\"; fi'";
            $result = $this->executeRemoteCommand($server, $dockerCommand, false);

            if ($result->successful() && trim($result->output()) !== 'Laravel log not found inside container') {
                return [
                    'success' => true,
                    'logs' => $result->output(),
                    'source' => 'container',
                ];
            }

            // Fall back to host filesystem
            $hostCommand = "if [ -f {$hostPath} ]; then tail -n {$lines} {$hostPath}; else echo 'Laravel log not found on host'; fi";
            $hostResult = $this->executeRemoteCommand($server, $hostCommand, false);

            return [
                'success' => $hostResult->successful(),
                'logs' => $hostResult->output(),
                'source' => 'host',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear Laravel logs
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function clearLaravelLogs(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                throw new \RuntimeException('Project has no server assigned');
            }

            $slug = $project->validated_slug;
            $hostPath = "/var/www/{$slug}/storage/logs/laravel.log";

            // Use sudo for non-root users to handle permission issues
            $sudo = strtolower((string) $server->username) === 'root' ? '' : 'sudo ';

            $hostCommand = "if [ -f {$hostPath} ]; then {$sudo}truncate -s 0 {$hostPath} && echo 'cleared'; elif [ -d /var/www/{$slug}/storage/logs ]; then {$sudo}touch {$hostPath} && {$sudo}chmod 666 {$hostPath} && echo 'created'; else echo 'not_found'; fi";
            $result = $this->executeRemoteCommand($server, $hostCommand, false);

            $output = trim($result->output());

            if ($result->successful() && ($output === 'cleared' || $output === 'created')) {
                return [
                    'success' => true,
                    'message' => 'Laravel logs cleared successfully',
                ];
            }

            if ($output === 'not_found') {
                return [
                    'success' => false,
                    'error' => "Log directory not found at /var/www/{$slug}/storage/logs",
                ];
            }

            return [
                'success' => false,
                'error' => 'Could not clear logs: '.($result->errorOutput() ?: $result->output() ?: 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download Laravel logs as a file
     *
     * @return array{success: bool, content?: string, filename?: string, error?: string}
     */
    public function downloadLaravelLogs(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                throw new \RuntimeException('Project has no server assigned');
            }

            $slug = $project->validated_slug;
            $hostPath = "/var/www/{$slug}/storage/logs/laravel.log";

            // Use sudo for non-root users
            $sudo = strtolower((string) $server->username) === 'root' ? '' : 'sudo ';

            $hostCommand = "if [ -f {$hostPath} ]; then {$sudo}cat {$hostPath}; else echo '__LOG_NOT_FOUND__'; fi";
            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                $hostCommand,
                (int) config('devflow.timeouts.log_download', 120),
                false
            );

            $output = $result->output();

            if (trim($output) === '__LOG_NOT_FOUND__') {
                return [
                    'success' => false,
                    'error' => 'Log file not found',
                ];
            }

            if ($result->successful()) {
                return [
                    'success' => true,
                    'content' => $output,
                    'filename' => $slug.'-laravel-'.now()->format('Y-m-d-His').'.log',
                ];
            }

            return [
                'success' => false,
                'error' => 'Could not download logs: '.($result->errorOutput() ?: 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
