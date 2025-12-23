<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker volume management.
 *
 * Handles:
 * - Volume listing
 * - Volume creation and deletion
 * - Volume inspection
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerVolumeService
{
    use ExecutesRemoteCommands;

    /**
     * List all Docker volumes on server
     *
     * @return array{success: bool, volumes?: array<int, mixed>, error?: string}
     */
    public function listVolumes(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker volume ls --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $lines = array_filter(explode("\n", $output));
                $volumes = array_map(fn ($line) => json_decode($line, true), $lines);

                return [
                    'success' => true,
                    'volumes' => $volumes,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a Docker volume
     *
     * @param array{driver?: string, labels?: array<string, string>} $options
     * @return array{success: bool, volume_name?: string, error?: string}
     */
    public function createVolume(Server $server, string $name, array $options = []): array
    {
        try {
            $createCommand = 'docker volume create';

            if (isset($options['driver'])) {
                $createCommand .= " --driver={$options['driver']}";
            }

            foreach ($options['labels'] ?? [] as $key => $value) {
                $createCommand .= " --label={$key}={$value}";
            }

            $createCommand .= " {$name}";

            $result = $this->executeRemoteCommand($server, $createCommand, false);

            return [
                'success' => $result->successful(),
                'volume_name' => trim($result->output()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker volume
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteVolume(Server $server, string $name): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker volume rm {$name}",
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
     * Get volume details and usage
     *
     * @return array{success: bool, volume?: array<string, mixed>|null, error?: string}
     */
    public function getVolumeInfo(Server $server, string $name): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker volume inspect {$name}",
                false
            );

            if ($result->successful()) {
                $info = json_decode($result->output(), true);

                return [
                    'success' => true,
                    'volume' => $info[0] ?? null,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
