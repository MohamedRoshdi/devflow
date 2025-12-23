<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\DockerComposeService;
use App\Services\Docker\DockerContainerService;
use App\Services\Docker\DockerfileGenerator;
use App\Services\Docker\DockerImageService;
use App\Services\Docker\DockerLogService;
use App\Services\Docker\DockerNetworkService;
use App\Services\Docker\DockerRegistryService;
use App\Services\Docker\DockerSystemService;
use App\Services\Docker\DockerVolumeService;

/**
 * Docker service facade for managing Docker containers, images, volumes, and networks.
 *
 * This service acts as a facade, delegating all operations to specialized sub-services:
 * - DockerContainerService: Container lifecycle management
 * - DockerComposeService: Docker Compose operations
 * - DockerImageService: Image management
 * - DockerVolumeService: Volume operations
 * - DockerNetworkService: Network operations
 * - DockerRegistryService: Registry operations
 * - DockerSystemService: System-level operations
 * - DockerLogService: Log management
 * - DockerfileGenerator: Dockerfile generation
 *
 * All public methods maintain backward compatibility with existing consumers.
 */
class DockerService
{
    public function __construct(
        private readonly DockerContainerService $containerService,
        private readonly DockerComposeService $composeService,
        private readonly DockerImageService $imageService,
        private readonly DockerVolumeService $volumeService,
        private readonly DockerNetworkService $networkService,
        private readonly DockerRegistryService $registryService,
        private readonly DockerSystemService $systemService,
        private readonly DockerLogService $logService,
        private readonly DockerfileGenerator $dockerfileGenerator,
    ) {}

    // ==========================================
    // SYSTEM OPERATIONS
    // ==========================================

    /**
     * Check if Docker is installed on the server
     *
     * @return array{installed: bool, version?: string, error?: string}
     */
    public function checkDockerInstallation(Server $server): array
    {
        return $this->systemService->checkDockerInstallation($server);
    }

    /**
     * Install Docker on the server
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function installDocker(Server $server): array
    {
        return $this->systemService->installDocker($server);
    }

    /**
     * Get Docker system information
     *
     * @return array{success: bool, info?: array<string, mixed>, error?: string}
     */
    public function getSystemInfo(Server $server): array
    {
        return $this->systemService->getSystemInfo($server);
    }

    /**
     * Clean up Docker system (remove unused data)
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function systemPrune(Server $server, bool $volumes = false): array
    {
        return $this->systemService->systemPrune($server, $volumes);
    }

    /**
     * Get Docker disk usage
     *
     * @return array{success: bool, usage?: array<int, mixed>, error?: string}
     */
    public function getDiskUsage(Server $server): array
    {
        return $this->systemService->getDiskUsage($server);
    }

    // ==========================================
    // CONTAINER OPERATIONS
    // ==========================================

    /**
     * Build Docker container for a project
     *
     * @return array{success: bool, output?: string, error?: string, type?: string}
     */
    public function buildContainer(Project $project): array
    {
        return $this->containerService->buildContainer($project);
    }

    /**
     * Start container for a project
     *
     * @return array{success: bool, output?: string, message?: string, error?: string, container_id?: string, port?: int}
     */
    public function startContainer(Project $project): array
    {
        return $this->containerService->startContainer($project);
    }

    /**
     * Stop container for a project
     *
     * @return array{success: bool, output?: string, message?: string, error?: string}
     */
    public function stopContainer(Project $project): array
    {
        return $this->containerService->stopContainer($project);
    }

    /**
     * Get container status for a project
     *
     * @return array{success: bool, container?: array<string, mixed>|null, exists?: bool, error?: string}
     */
    public function getContainerStatus(Project $project): array
    {
        return $this->containerService->getContainerStatus($project);
    }

    /**
     * Get container statistics (CPU, Memory, Network, Disk I/O)
     *
     * @return array{success: bool, stats?: array<string, mixed>|null, error?: string}
     */
    public function getContainerStats(Project $project): array
    {
        return $this->containerService->getContainerStats($project);
    }

    /**
     * Get container resource limits
     *
     * @return array{success: bool, memory_limit?: int, cpu_shares?: int, cpu_quota?: int, error?: string}
     */
    public function getContainerResourceLimits(Project $project): array
    {
        return $this->containerService->getContainerResourceLimits($project);
    }

    /**
     * Set container resource limits
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function setContainerResourceLimits(Project $project, ?int $memoryMB = null, ?int $cpuShares = null): array
    {
        return $this->containerService->setContainerResourceLimits($project, $memoryMB, $cpuShares);
    }

    /**
     * Execute command in container
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function execInContainer(Project $project, string $command, bool $interactive = false): array
    {
        return $this->containerService->execInContainer($project, $command, $interactive);
    }

    /**
     * Get list of processes in container
     *
     * @return array{success: bool, processes?: string, error?: string}
     */
    public function getContainerProcesses(Project $project): array
    {
        return $this->containerService->getContainerProcesses($project);
    }

    /**
     * Export container as image (backup)
     *
     * @return array{success: bool, backup_name?: string, image_id?: string, error?: string}
     */
    public function exportContainer(Project $project, ?string $backupName = null): array
    {
        return $this->containerService->exportContainer($project, $backupName);
    }

    // ==========================================
    // LOG OPERATIONS
    // ==========================================

    /**
     * Get container logs
     *
     * @return array{success: bool, logs?: string, source?: string, error?: string}
     */
    public function getContainerLogs(Project $project, int $lines = 100): array
    {
        return $this->logService->getContainerLogs($project, $lines);
    }

    /**
     * Get Laravel-specific logs from container or host
     *
     * @return array{success: bool, logs?: string, source?: string, error?: string}
     */
    public function getLaravelLogs(Project $project, int $lines = 200): array
    {
        return $this->logService->getLaravelLogs($project, $lines);
    }

    /**
     * Clear Laravel logs
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function clearLaravelLogs(Project $project): array
    {
        return $this->logService->clearLaravelLogs($project);
    }

    /**
     * Download Laravel logs as a file
     *
     * @return array{success: bool, content?: string, filename?: string, error?: string}
     */
    public function downloadLaravelLogs(Project $project): array
    {
        return $this->logService->downloadLaravelLogs($project);
    }

    // ==========================================
    // DOCKER COMPOSE OPERATIONS
    // ==========================================

    /**
     * Check if a project uses docker-compose
     */
    public function usesDockerCompose(Project $project): bool
    {
        return $this->composeService->usesDockerCompose($project);
    }

    /**
     * Get the app container name for a docker-compose project
     */
    public function getAppContainerName(Project $project): string
    {
        return $this->composeService->getAppContainerName($project);
    }

    /**
     * Deploy with docker-compose
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deployWithCompose(Project $project): array
    {
        return $this->composeService->deployWithCompose($project);
    }

    /**
     * Stop docker-compose services
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function stopCompose(Project $project): array
    {
        return $this->composeService->stopCompose($project);
    }

    /**
     * Get docker-compose service status
     *
     * @return array{success: bool, services?: array<string, mixed>|null, error?: string}
     */
    public function getComposeStatus(Project $project): array
    {
        return $this->composeService->getComposeStatus($project);
    }

    // ==========================================
    // IMAGE OPERATIONS
    // ==========================================

    /**
     * List all Docker images on server
     *
     * @return array{success: bool, images?: array<int, mixed>, error?: string}
     */
    public function listImages(Server $server): array
    {
        return $this->imageService->listImages($server);
    }

    /**
     * List Docker images related to a specific project
     *
     * @return array{success: bool, images?: array<int, mixed>, error?: string}
     */
    public function listProjectImages(Project $project): array
    {
        return $this->imageService->listProjectImages($project);
    }

    /**
     * Delete a Docker image
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteImage(Server $server, string $imageId): array
    {
        return $this->imageService->deleteImage($server, $imageId);
    }

    /**
     * Prune unused Docker images
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pruneImages(Server $server, bool $all = false): array
    {
        return $this->imageService->pruneImages($server, $all);
    }

    /**
     * Pull Docker image from registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pullImage(Server $server, string $imageName): array
    {
        return $this->imageService->pullImage($server, $imageName);
    }

    /**
     * Tag image for registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function tagImage(Server $server, string $sourceImage, string $targetImage): array
    {
        return $this->imageService->tagImage($server, $sourceImage, $targetImage);
    }

    /**
     * Save image to tar file
     *
     * @return array{success: bool, file_path?: string, error?: string}
     */
    public function saveImageToFile(Server $server, string $imageName, string $filePath): array
    {
        return $this->imageService->saveImageToFile($server, $imageName, $filePath);
    }

    /**
     * Load image from tar file
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function loadImageFromFile(Server $server, string $filePath): array
    {
        return $this->imageService->loadImageFromFile($server, $filePath);
    }

    // ==========================================
    // VOLUME OPERATIONS
    // ==========================================

    /**
     * List all Docker volumes on server
     *
     * @return array{success: bool, volumes?: array<int, mixed>, error?: string}
     */
    public function listVolumes(Server $server): array
    {
        return $this->volumeService->listVolumes($server);
    }

    /**
     * Create a Docker volume
     *
     * @param array{driver?: string, labels?: array<string, string>} $options
     * @return array{success: bool, volume_name?: string, error?: string}
     */
    public function createVolume(Server $server, string $name, array $options = []): array
    {
        return $this->volumeService->createVolume($server, $name, $options);
    }

    /**
     * Delete a Docker volume
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteVolume(Server $server, string $name): array
    {
        return $this->volumeService->deleteVolume($server, $name);
    }

    /**
     * Get volume details and usage
     *
     * @return array{success: bool, volume?: array<string, mixed>|null, error?: string}
     */
    public function getVolumeInfo(Server $server, string $name): array
    {
        return $this->volumeService->getVolumeInfo($server, $name);
    }

    // ==========================================
    // NETWORK OPERATIONS
    // ==========================================

    /**
     * List all Docker networks on server
     *
     * @return array{success: bool, networks?: array<int, mixed>, error?: string}
     */
    public function listNetworks(Server $server): array
    {
        return $this->networkService->listNetworks($server);
    }

    /**
     * Create a Docker network
     *
     * @return array{success: bool, network_id?: string, error?: string}
     */
    public function createNetwork(Server $server, string $name, string $driver = 'bridge'): array
    {
        return $this->networkService->createNetwork($server, $name, $driver);
    }

    /**
     * Delete a Docker network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function deleteNetwork(Server $server, string $name): array
    {
        return $this->networkService->deleteNetwork($server, $name);
    }

    /**
     * Connect container to network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function connectContainerToNetwork(Project $project, string $networkName): array
    {
        return $this->networkService->connectContainerToNetwork($project, $networkName);
    }

    /**
     * Disconnect container from network
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function disconnectContainerFromNetwork(Project $project, string $networkName): array
    {
        return $this->networkService->disconnectContainerFromNetwork($project, $networkName);
    }

    // ==========================================
    // REGISTRY OPERATIONS
    // ==========================================

    /**
     * Login to Docker registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function registryLogin(Server $server, string $registry, string $username, string $password): array
    {
        return $this->registryService->registryLogin($server, $registry, $username, $password);
    }

    /**
     * Push image to registry
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function pushImage(Server $server, string $imageName): array
    {
        return $this->registryService->pushImage($server, $imageName);
    }

    // ==========================================
    // DOCKERFILE GENERATION
    // ==========================================

    /**
     * Generate Dockerfile content based on project framework
     */
    public function generateDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->generate($project);
    }

    /**
     * Generate Laravel/PHP Dockerfile
     */
    public function getLaravelDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->getLaravelDockerfile($project);
    }

    /**
     * Generate Node.js Dockerfile
     */
    public function getNodeDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->getNodeDockerfile($project);
    }

    /**
     * Generate React Dockerfile
     */
    public function getReactDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->getReactDockerfile($project);
    }

    /**
     * Generate Vue Dockerfile
     */
    public function getVueDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->getVueDockerfile($project);
    }

    /**
     * Generate generic Dockerfile
     */
    public function getGenericDockerfile(Project $project): string
    {
        return $this->dockerfileGenerator->getGenericDockerfile($project);
    }
}
