<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker system-level operations.
 *
 * Handles:
 * - Docker installation checking and installation
 * - System information retrieval
 * - System cleanup and pruning
 * - Disk usage monitoring
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerSystemService
{
    use ExecutesRemoteCommands;

    /**
     * Check if Docker is installed on the server
     *
     * @return array{installed: bool, version?: string, error?: string}
     */
    public function checkDockerInstallation(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand($server, 'docker --version', false);

            if ($result->successful()) {
                $output = $result->output();
                preg_match('/Docker version (.+?),/', $output, $matches);

                return [
                    'installed' => true,
                    'version' => $matches[1] ?? 'unknown',
                ];
            }

            return [
                'installed' => false,
                'error' => $result->errorOutput() ?: 'Docker command failed',
            ];
        } catch (\Exception $e) {
            return ['installed' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Install Docker on the server
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function installDocker(Server $server): array
    {
        try {
            $script = <<<'BASH'
            curl -fsSL https://get.docker.com -o get-docker.sh && \
            sh get-docker.sh && \
            systemctl start docker && \
            systemctl enable docker && \
            usermod -aG docker $USER
            BASH;

            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                $script,
                (int) config('devflow.timeouts.docker_install', 300),
                false
            );

            if ($result->successful()) {
                return [
                    'success' => true,
                    'output' => $result->output(),
                ];
            }

            return [
                'success' => false,
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Docker system information
     *
     * @return array{success: bool, info?: array<string, mixed>, error?: string}
     */
    public function getSystemInfo(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker info --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $info = json_decode($result->output(), true);

                return [
                    'success' => true,
                    'info' => $info,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean up Docker system (remove unused data)
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function systemPrune(Server $server, bool $volumes = false): array
    {
        try {
            $command = 'docker system prune -f';
            if ($volumes) {
                $command .= ' --volumes';
            }

            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                $command,
                (int) config('devflow.timeouts.system_prune', 300),
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
     * Get Docker disk usage
     *
     * @return array{success: bool, usage?: array<int, mixed>, error?: string}
     */
    public function getDiskUsage(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker system df --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $lines = array_filter(explode("\n", $output));
                $usage = array_map(fn ($line) => json_decode($line, true), $lines);

                return [
                    'success' => true,
                    'usage' => $usage,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
