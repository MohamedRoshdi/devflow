<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\DockerRegistry;
use App\Models\KubernetesCluster;
use App\Models\Project;

/**
 * Kubernetes Service Facade.
 *
 * This service acts as a facade that delegates to specialized services:
 * - KubernetesDeploymentService: Deployment operations
 * - KubernetesConfigService: Manifest and Helm chart generation
 * - KubernetesMonitorService: Monitoring and status operations
 * - KubernetesRegistryService: Docker registry management
 *
 * Maintains backward compatibility with existing code while providing
 * a cleaner separation of concerns internally.
 */
class KubernetesService
{
    public function __construct(
        protected readonly KubernetesDeploymentService $deploymentService,
        protected readonly KubernetesConfigService $configService,
        protected readonly KubernetesMonitorService $monitorService,
        protected readonly KubernetesRegistryService $registryService,
    ) {}

    // =========================================================================
    // Deployment Operations (delegated to KubernetesDeploymentService)
    // =========================================================================

    /**
     * Deploy project to Kubernetes cluster
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function deployToKubernetes(Project $project, array $options = []): array
    {
        return $this->deploymentService->deployToKubernetes($project, $options);
    }

    /**
     * Deploy using Helm chart
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function deployWithHelm(Project $project, array $options = []): array
    {
        return $this->deploymentService->deployWithHelm($project, $options);
    }

    /**
     * Apply Kubernetes manifests
     *
     * @param  array<string, array<string, mixed>>  $manifests
     * @return array<string, array<string, mixed>>
     */
    public function applyManifests(Project $project, array $manifests): array
    {
        return $this->deploymentService->applyManifests($project, $manifests);
    }

    /**
     * Setup kubectl context for the cluster
     */
    public function setupKubectlContext(KubernetesCluster $cluster): void
    {
        $this->deploymentService->setupKubectlContext($cluster);
    }

    /**
     * Wait for deployment rollout to complete
     *
     * @return array<string, mixed>
     */
    public function waitForRollout(Project $project): array
    {
        return $this->deploymentService->waitForRollout($project);
    }

    /**
     * Delete Kubernetes resources for a project
     *
     * @return array<string, mixed>
     */
    public function deleteResources(Project $project): array
    {
        return $this->deploymentService->deleteResources($project);
    }

    /**
     * Build Docker image for Kubernetes
     */
    public function buildDockerImage(Project $project): string
    {
        return $this->deploymentService->buildDockerImage($project);
    }

    /**
     * Run post-deployment tasks
     */
    public function runPostDeploymentTasks(Project $project): void
    {
        $this->deploymentService->runPostDeploymentTasks($project);
    }

    // =========================================================================
    // Configuration Operations (delegated to KubernetesConfigService)
    // =========================================================================

    /**
     * Generate Kubernetes manifests for the project
     *
     * @param  array<string, mixed>  $options
     * @return array<string, array<string, mixed>>
     */
    public function generateManifests(Project $project, array $options = []): array
    {
        return $this->configService->generateManifests($project, $options);
    }

    /**
     * Generate Deployment manifest
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateDeploymentManifest(Project $project, array $options = []): array
    {
        return $this->configService->generateDeploymentManifest($project, $options);
    }

    /**
     * Generate Ingress manifest
     *
     * @return array<string, mixed>
     */
    public function generateIngressManifest(Project $project): array
    {
        return $this->configService->generateIngressManifest($project);
    }

    /**
     * Generate HorizontalPodAutoscaler manifest
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateHPAManifest(Project $project, array $options = []): array
    {
        return $this->configService->generateHPAManifest($project, $options);
    }

    /**
     * Generate Helm values for the project
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateHelmValues(Project $project, array $options = []): array
    {
        return $this->configService->generateHelmValues($project, $options);
    }

    /**
     * Generate Helm chart for the project
     */
    public function generateHelmChart(Project $project): string
    {
        return $this->configService->generateHelmChart($project);
    }

    /**
     * Get project volumes configuration
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectVolumes(Project $project): array
    {
        return $this->configService->getProjectVolumes($project);
    }

    /**
     * Get project volume mounts configuration
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectVolumeMounts(Project $project): array
    {
        return $this->configService->getProjectVolumeMounts($project);
    }

    /**
     * Get Laravel-specific init containers
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLaravelInitContainers(Project $project): array
    {
        return $this->configService->getLaravelInitContainers($project);
    }

    /**
     * Get ConfigMap data for the project
     *
     * @return array<string, string>
     */
    public function getConfigMapData(Project $project): array
    {
        return $this->configService->getConfigMapData($project);
    }

    /**
     * Get secret data for the project
     *
     * @return array<string, string>
     */
    public function getSecretData(Project $project): array
    {
        return $this->configService->getSecretData($project);
    }

    // =========================================================================
    // Monitoring Operations (delegated to KubernetesMonitorService)
    // =========================================================================

    /**
     * Get pod status for the project
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPodStatus(Project $project): array
    {
        return $this->monitorService->getPodStatus($project);
    }

    /**
     * Get service endpoints
     *
     * @return array<int, string>
     */
    public function getServiceEndpoints(Project $project): array
    {
        return $this->monitorService->getServiceEndpoints($project);
    }

    /**
     * Scale deployment
     *
     * @return array<string, mixed>
     */
    public function scaleDeployment(Project $project, int $replicas): array
    {
        return $this->monitorService->scaleDeployment($project, $replicas);
    }

    /**
     * Execute command in pod
     *
     * @return array<string, mixed>
     */
    public function executeInPod(Project $project, string $command, ?string $podName = null): array
    {
        return $this->monitorService->executeInPod($project, $command, $podName);
    }

    /**
     * Get deployment logs
     *
     * @return array<string, mixed>
     */
    public function getDeploymentLogs(Project $project, int $lines = 100): array
    {
        return $this->monitorService->getDeploymentLogs($project, $lines);
    }

    /**
     * Get pod logs
     *
     * @return array<string, mixed>
     */
    public function getPodLogs(Project $project, string $podName, int $lines = 100): array
    {
        return $this->monitorService->getPodLogs($project, $podName, $lines);
    }

    /**
     * Get deployment status
     *
     * @return array<string, mixed>
     */
    public function getDeploymentStatus(Project $project): array
    {
        return $this->monitorService->getDeploymentStatus($project);
    }

    /**
     * Get resource usage for pods
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResourceUsage(Project $project): array
    {
        return $this->monitorService->getResourceUsage($project);
    }

    /**
     * Get events for the project namespace
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(Project $project, int $limit = 50): array
    {
        return $this->monitorService->getEvents($project, $limit);
    }

    // =========================================================================
    // Registry Operations (delegated to KubernetesRegistryService)
    // =========================================================================

    /**
     * Create or update Docker registry secrets in Kubernetes
     *
     * @return array<int, array<string, mixed>>
     */
    public function createDockerRegistrySecrets(Project $project): array
    {
        return $this->registryService->createDockerRegistrySecrets($project);
    }

    /**
     * Create a single Docker registry secret
     *
     * @return array<string, mixed>
     */
    public function createRegistrySecret(Project $project, DockerRegistry $registry): array
    {
        return $this->registryService->createRegistrySecret($project, $registry);
    }

    /**
     * Build Docker config JSON for registry authentication
     *
     * @return array<string, mixed>
     */
    public function buildDockerConfigJson(DockerRegistry $registry): array
    {
        return $this->registryService->buildDockerConfigJson($registry);
    }

    /**
     * Get image pull secrets for a project
     *
     * @return array<int, array<string, string>>
     */
    public function getImagePullSecrets(Project $project): array
    {
        return $this->registryService->getImagePullSecrets($project);
    }

    /**
     * Store Docker registry credentials for a project
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function storeDockerRegistryCredentials(Project $project, array $credentialsData): DockerRegistry
    {
        return $this->registryService->storeDockerRegistryCredentials($project, $credentialsData);
    }

    /**
     * Retrieve Docker registry credentials for a project
     *
     * @return array<int, DockerRegistry>
     */
    public function getDockerRegistryCredentials(Project $project): array
    {
        return $this->registryService->getDockerRegistryCredentials($project);
    }

    /**
     * Get the default Docker registry for a project
     */
    public function getDefaultDockerRegistry(Project $project): ?DockerRegistry
    {
        return $this->registryService->getDefaultDockerRegistry($project);
    }

    /**
     * Update Docker registry credentials
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function updateDockerRegistryCredentials(DockerRegistry $registry, array $credentialsData): DockerRegistry
    {
        return $this->registryService->updateDockerRegistryCredentials($registry, $credentialsData);
    }

    /**
     * Delete Docker registry credentials
     */
    public function deleteDockerRegistryCredentials(DockerRegistry $registry): bool
    {
        return $this->registryService->deleteDockerRegistryCredentials($registry);
    }

    /**
     * Test Docker registry connection
     *
     * @return array<string, mixed>
     */
    public function testDockerRegistryConnection(DockerRegistry $registry): array
    {
        return $this->registryService->testDockerRegistryConnection($registry);
    }

    // =========================================================================
    // Direct Service Access (for advanced use cases)
    // =========================================================================

    /**
     * Get the deployment service instance
     */
    public function deployment(): KubernetesDeploymentService
    {
        return $this->deploymentService;
    }

    /**
     * Get the config service instance
     */
    public function config(): KubernetesConfigService
    {
        return $this->configService;
    }

    /**
     * Get the monitor service instance
     */
    public function monitor(): KubernetesMonitorService
    {
        return $this->monitorService;
    }

    /**
     * Get the registry service instance
     */
    public function registry(): KubernetesRegistryService
    {
        return $this->registryService;
    }
}
