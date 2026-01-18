<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker network management.
 *
 * Handles:
 * - Network listing
 * - Network creation and deletion
 * - Container network connections
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerNetworkService
{
    use ExecutesRemoteCommands;

    /**
     * List all Docker networks on server
     *
     * @return array{success: bool, networks?: array<int, mixed>, error?: string}
     */
    public function listNetworks(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker network ls --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $lines = array_filter(explode("\n", $output));
                $networks = array_map(fn ($line) => json_decode($line, true), $lines);

                return [
                    'success' => true,
                    'networks' => $networks,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a Docker network
     *
     * @return array{success: bool, network_id?: string, error?: string}
     */
    public function createNetwork(Server $server, string $name, string $driver = 'bridge'): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker network create --driver={$driver} {$name}",
                false
            );

            return [
                'success' => $result->successful(),
                'network_id' => trim($result->output()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteNetwork(Server $server, string $name): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker network rm {$name}",
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
     * Connect container to network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function connectContainerToNetwork(Project $project, string $networkName): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);
            $escapedNetwork = escapeshellarg($networkName);
            $result = $this->executeRemoteCommand(
                $server,
                "docker network connect {$escapedNetwork} {$escapedSlug}",
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
     * Disconnect container from network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function disconnectContainerFromNetwork(Project $project, string $networkName): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);
            $escapedNetwork = escapeshellarg($networkName);
            $result = $this->executeRemoteCommand(
                $server,
                "docker network disconnect {$escapedNetwork} {$escapedSlug}",
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
