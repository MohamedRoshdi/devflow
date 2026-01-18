<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker Compose operations.
 *
 * Handles:
 * - Docker Compose detection
 * - Compose deployment and stop
 * - Service status and container name resolution
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerComposeService
{
    use ExecutesRemoteCommands;

    /**
     * Check if a project uses docker-compose
     */
    public function usesDockerCompose(Project $project): bool
    {
        $server = $project->server;
        if ($server === null) {
            return false;
        }

        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
        $result = $this->executeRemoteCommand($server, $checkComposeCmd, false);

        return trim($result->output()) === 'compose';
    }

    /**
     * Get the app container name for a docker-compose project
     * Tries common naming patterns: {slug}-app, {slug}_app_1, app
     */
    public function getAppContainerName(Project $project): string
    {
        $server = $project->server;
        if ($server === null) {
            return $project->validated_slug.'-app';
        }

        $slug = $project->validated_slug;

        // Try common naming patterns
        $patterns = [
            "{$slug}-app",    // docker-compose v2 naming
            "{$slug}_app_1",  // docker-compose v1 naming
            'app',            // generic app service
        ];

        foreach ($patterns as $pattern) {
            $escapedPattern = escapeshellarg($pattern);
            $checkCmd = "docker ps --filter 'name={$escapedPattern}' --format '{{.Names}}' | head -1";
            $result = $this->executeRemoteCommand($server, $checkCmd, false);
            $containerName = trim($result->output());

            if (! empty($containerName)) {
                return $containerName;
            }
        }

        // Fallback to slug-app pattern
        return "{$slug}-app";
    }

    /**
     * Deploy with docker-compose
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deployWithCompose(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "cd {$projectPath} && docker compose up -d --build",
                (int) config('devflow.timeouts.docker_compose_build', 1200),
                false
            );

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop docker-compose services
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function stopCompose(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $result = $this->executeRemoteCommand(
                $server,
                "cd {$projectPath} && docker compose down",
                false
            );

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get docker-compose service status
     *
     * @return array{success: bool, services?: array<string, mixed>|null, error?: string}
     */
    public function getComposeStatus(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $result = $this->executeRemoteCommand(
                $server,
                "cd {$projectPath} && docker compose ps --format json",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $services = json_decode($output, true);

                return [
                    'success' => true,
                    'services' => $services ?? [],
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restart docker-compose services
     *
     * @param array<int, string> $services Optional list of services to restart
     * @return array{success: bool, output?: string, error?: string}
     */
    public function restartCompose(Project $project, array $services = []): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $serviceList = empty($services) ? '' : ' '.implode(' ', array_map('escapeshellarg', $services));
            $result = $this->executeRemoteCommand(
                $server,
                "cd {$projectPath} && docker compose restart{$serviceList}",
                false
            );

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pull latest images for docker-compose services
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pullCompose(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "cd {$projectPath} && docker compose pull",
                (int) config('devflow.timeouts.docker_pull', 600),
                false
            );

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * View docker-compose logs
     *
     * @return array{success: bool, logs?: string, error?: string}
     */
    public function getComposeLogs(Project $project, int $lines = 100, ?string $service = null): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $serviceArg = $service !== null ? ' '.escapeshellarg($service) : '';
            $result = $this->executeRemoteCommand(
                $server,
                "cd {$projectPath} && docker compose logs --tail {$lines}{$serviceArg}",
                false
            );

            return [
                'success' => $result->successful(),
                'logs' => $result->output() ?: $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
