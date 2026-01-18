<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\KubernetesCluster;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Handles Kubernetes deployment operations.
 *
 * Responsible for deploying projects to Kubernetes clusters,
 * managing rollouts, and executing post-deployment tasks.
 */
class KubernetesDeploymentService
{
    protected string $kubectlPath = '/usr/local/bin/kubectl';

    protected string $helmPath = '/usr/local/bin/helm';

    public function __construct(
        protected readonly KubernetesConfigService $configService,
        protected readonly KubernetesRegistryService $registryService,
        protected readonly KubernetesMonitorService $monitorService,
    ) {}

    /**
     * Deploy project to Kubernetes cluster
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function deployToKubernetes(Project $project, array $options = []): array
    {
        $cluster = $project->kubernetesCluster;

        if (! $cluster) {
            throw new \Exception('No Kubernetes cluster configured for this project');
        }

        // Set up kubectl context
        $this->setupKubectlContext($cluster);

        // Create or update docker registry secrets
        $this->registryService->createDockerRegistrySecrets($project);

        // Generate Kubernetes manifests
        $manifests = $this->configService->generateManifests($project, $options);

        // Apply manifests
        $deploymentResult = $this->applyManifests($project, $manifests);

        // Wait for rollout
        $rolloutStatus = $this->waitForRollout($project);

        // Run post-deployment tasks
        $this->runPostDeploymentTasks($project);

        return [
            'success' => true,
            'deployment' => $deploymentResult,
            'rollout_status' => $rolloutStatus,
            'endpoints' => $this->monitorService->getServiceEndpoints($project),
            'pods' => $this->monitorService->getPodStatus($project),
        ];
    }

    /**
     * Deploy using Helm chart
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function deployWithHelm(Project $project, array $options = []): array
    {
        $chartPath = $options['chart_path'] ?? $this->configService->generateHelmChart($project);
        $releaseName = $project->slug;
        $namespace = $project->slug;

        // Create values file
        $valuesFile = "/tmp/{$project->slug}-values.yaml";
        file_put_contents($valuesFile, yaml_emit($this->configService->generateHelmValues($project, $options)));

        // Install or upgrade helm release
        $command = sprintf(
            '%s upgrade --install %s %s -n %s --create-namespace -f %s --wait --timeout 10m',
            $this->helmPath,
            $releaseName,
            $chartPath,
            $namespace,
            $valuesFile
        );

        $result = Process::run($command);

        unlink($valuesFile);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'release' => $releaseName,
        ];
    }

    /**
     * Apply Kubernetes manifests
     *
     * @param  array<string, array<string, mixed>>  $manifests
     * @return array<string, array<string, mixed>>
     */
    public function applyManifests(Project $project, array $manifests): array
    {
        $results = [];

        foreach ($manifests as $type => $manifest) {
            $yamlContent = yaml_emit($manifest);
            $tempFile = "/tmp/{$project->slug}-{$type}.yaml";

            file_put_contents($tempFile, $yamlContent);

            $result = Process::run("{$this->kubectlPath} apply -f {$tempFile}");

            $results[$type] = [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ];

            unlink($tempFile);
        }

        return $results;
    }

    /**
     * Setup kubectl context for the cluster
     */
    public function setupKubectlContext(KubernetesCluster $cluster): void
    {
        // Write kubeconfig
        $kubeconfigPath = "/tmp/kubeconfig-{$cluster->id}";
        file_put_contents($kubeconfigPath, $cluster->kubeconfig);

        // Set KUBECONFIG environment variable
        putenv("KUBECONFIG={$kubeconfigPath}");

        // Test connection
        $result = Process::run("{$this->kubectlPath} cluster-info");

        if (! $result->successful()) {
            throw new \Exception('Failed to connect to Kubernetes cluster: '.$result->errorOutput());
        }
    }

    /**
     * Wait for deployment rollout to complete
     *
     * @return array<string, mixed>
     */
    public function waitForRollout(Project $project): array
    {
        $command = sprintf(
            '%s rollout status deployment/%s-deployment -n %s --timeout=300s',
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Delete Kubernetes resources for a project
     *
     * @return array<string, mixed>
     */
    public function deleteResources(Project $project): array
    {
        $command = sprintf(
            '%s delete namespace %s --timeout=60s',
            $this->kubectlPath,
            $project->slug
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Build Docker image for Kubernetes
     */
    public function buildDockerImage(Project $project): string
    {
        $registry = config('kubernetes.docker_registry');
        $tag = $project->current_version ?? substr($project->latest_commit_hash, 0, 7);

        $imageName = "{$registry}/{$project->slug}:{$tag}";

        // Build and push image
        $buildCommand = sprintf(
            'docker build -t %s %s && docker push %s',
            $imageName,
            $project->getProjectPath(),
            $imageName
        );

        Process::run($buildCommand);

        return $imageName;
    }

    /**
     * Run post-deployment tasks
     */
    public function runPostDeploymentTasks(Project $project): void
    {
        // Run migrations
        if ($project->framework === 'laravel') {
            $this->monitorService->executeInPod($project, 'php artisan migrate --force');
        }

        // Clear caches
        $this->monitorService->executeInPod($project, 'php artisan cache:clear');

        // Run custom post-deployment script if defined
        if ($project->post_deployment_script) {
            $this->monitorService->executeInPod($project, $project->post_deployment_script);
        }
    }

    /**
     * Get kubectl path
     */
    public function getKubectlPath(): string
    {
        return $this->kubectlPath;
    }
}
