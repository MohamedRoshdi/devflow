<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;

/**
 * Service for Docker image management.
 *
 * Handles:
 * - Image listing and filtering
 * - Image deletion and pruning
 * - Image pull, save, load, and tag operations
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerImageService
{
    use ExecutesRemoteCommands;

    /**
     * List all Docker images on server
     *
     * @return array{success: bool, images?: array<int, mixed>, error?: string}
     */
    public function listImages(Server $server): array
    {
        try {
            $result = $this->executeRemoteCommand(
                $server,
                "docker images --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $lines = array_filter(explode("\n", $output));
                $images = array_map(fn ($line) => json_decode($line, true), $lines);

                return [
                    'success' => true,
                    'images' => $images,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List Docker images related to a specific project
     *
     * @return array{success: bool, images?: array<int, mixed>, error?: string}
     */
    public function listProjectImages(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $result = $this->executeRemoteCommand(
                $server,
                "docker images --format '{{json .}}'",
                false
            );

            if ($result->successful()) {
                $output = $result->output();
                $lines = array_filter(explode("\n", $output));
                $allImages = array_map(fn ($line) => json_decode($line, true), $lines);

                // Filter images related to this project (by slug or tag)
                $slug = $project->validated_slug;
                $projectImages = array_filter($allImages, function ($image) use ($slug) {
                    $repository = $image['Repository'] ?? '';
                    $tag = $image['Tag'] ?? '';

                    // Match images that contain the project slug in the repository name or tag
                    return stripos($repository, $slug) !== false ||
                           stripos($tag, $slug) !== false ||
                           $repository === $slug;
                });

                return [
                    'success' => true,
                    'images' => array_values($projectImages), // Re-index array
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker image
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteImage(Server $server, string $imageId): array
    {
        try {
            $escapedImageId = escapeshellarg($imageId);
            $result = $this->executeRemoteCommand(
                $server,
                "docker rmi {$escapedImageId}",
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
     * Prune unused Docker images
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pruneImages(Server $server, bool $all = false): array
    {
        try {
            $command = 'docker image prune -f';
            if ($all) {
                $command .= ' -a';
            }

            $result = $this->executeRemoteCommand($server, $command, false);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pull Docker image from registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pullImage(Server $server, string $imageName): array
    {
        try {
            $escapedImage = escapeshellarg($imageName);
            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "docker pull {$escapedImage}",
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
     * Tag image for registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function tagImage(Server $server, string $sourceImage, string $targetImage): array
    {
        try {
            $escapedSource = escapeshellarg($sourceImage);
            $escapedTarget = escapeshellarg($targetImage);
            $result = $this->executeRemoteCommand(
                $server,
                "docker tag {$escapedSource} {$escapedTarget}",
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
     * Save image to tar file
     *
     * @return array{success: bool, file_path?: string, error?: string}
     */
    public function saveImageToFile(Server $server, string $imageName, string $filePath): array
    {
        try {
            $escapedImage = escapeshellarg($imageName);
            $escapedPath = escapeshellarg($filePath);
            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "docker save -o {$escapedPath} {$escapedImage}",
                (int) config('devflow.timeouts.backup', 1800),
                false
            );

            return [
                'success' => $result->successful(),
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load image from tar file
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function loadImageFromFile(Server $server, string $filePath): array
    {
        try {
            $escapedPath = escapeshellarg($filePath);
            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                "docker load -i {$escapedPath}",
                (int) config('devflow.timeouts.backup', 1800),
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
