<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker registry operations.
 *
 * Handles:
 * - Registry authentication
 * - Image push operations
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerRegistryService
{
    use ExecutesRemoteCommands;

    /**
     * Login to Docker registry
     *
     * Uses --password-stdin for secure password handling to avoid exposing
     * credentials in process lists or shell history.
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function registryLogin(Server $server, string $registry, string $username, string $password): array
    {
        try {
            // Use --password-stdin without echoing password in command line
            $loginCommand = sprintf(
                'docker login %s -u %s --password-stdin',
                escapeshellarg($registry),
                escapeshellarg($username)
            );

            // Pass password via stdin input instead of echoing it in the command
            $result = $this->executeRemoteCommandWithInput(
                $server,
                $loginCommand,
                $password,
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
     * Push image to registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pushImage(Server $server, string $imageName): array
    {
        try {
            $escapedImage = escapeshellarg($imageName);
            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "docker push {$escapedImage}",
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
     * Logout from Docker registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function registryLogout(Server $server, string $registry): array
    {
        try {
            $escapedRegistry = escapeshellarg($registry);
            $result = $this->executeRemoteCommand(
                $server,
                "docker logout {$escapedRegistry}",
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
}
