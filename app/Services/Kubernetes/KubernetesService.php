<?php

namespace App\Services\Kubernetes;

use App\Models\Project;
use App\Models\KubernetesCluster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class KubernetesService
{
    protected string $kubectlPath = '/usr/local/bin/kubectl';
    protected string $helmPath = '/usr/local/bin/helm';

    /**
     * Deploy project to Kubernetes cluster
     */
    public function deployToKubernetes(Project $project, array $options = []): array
    {
        $cluster = $project->kubernetesCluster;

        if (!$cluster) {
            throw new \Exception('No Kubernetes cluster configured for this project');
        }

        // Set up kubectl context
        $this->setupKubectlContext($cluster);

        // Generate Kubernetes manifests
        $manifests = $this->generateManifests($project, $options);

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
            'endpoints' => $this->getServiceEndpoints($project),
            'pods' => $this->getPodStatus($project),
        ];
    }

    /**
     * Generate Kubernetes manifests for the project
     */
    protected function generateManifests(Project $project, array $options = []): array
    {
        $manifests = [];

        // Namespace
        $manifests['namespace'] = [
            'apiVersion' => 'v1',
            'kind' => 'Namespace',
            'metadata' => [
                'name' => $project->slug,
                'labels' => [
                    'app' => $project->slug,
                    'managed-by' => 'devflow-pro',
                ],
            ],
        ];

        // ConfigMap for environment variables
        $manifests['configmap'] = [
            'apiVersion' => 'v1',
            'kind' => 'ConfigMap',
            'metadata' => [
                'name' => "{$project->slug}-config",
                'namespace' => $project->slug,
            ],
            'data' => $project->environment_variables ?? [],
        ];

        // Secret for sensitive data
        $manifests['secret'] = [
            'apiVersion' => 'v1',
            'kind' => 'Secret',
            'metadata' => [
                'name' => "{$project->slug}-secret",
                'namespace' => $project->slug,
            ],
            'type' => 'Opaque',
            'stringData' => $this->getSecretData($project),
        ];

        // Deployment
        $manifests['deployment'] = $this->generateDeploymentManifest($project, $options);

        // Service
        $manifests['service'] = [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => "{$project->slug}-service",
                'namespace' => $project->slug,
            ],
            'spec' => [
                'selector' => [
                    'app' => $project->slug,
                ],
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'port' => 80,
                        'targetPort' => $project->container_port ?? 8000,
                    ],
                ],
                'type' => $options['service_type'] ?? 'ClusterIP',
            ],
        ];

        // Ingress (if domains configured)
        if ($project->domains()->exists()) {
            $manifests['ingress'] = $this->generateIngressManifest($project);
        }

        // HorizontalPodAutoscaler
        if ($options['enable_autoscaling'] ?? false) {
            $manifests['hpa'] = $this->generateHPAManifest($project, $options);
        }

        return $manifests;
    }

    /**
     * Generate Deployment manifest
     */
    protected function generateDeploymentManifest(Project $project, array $options = []): array
    {
        $replicas = $options['replicas'] ?? 3;
        $image = $this->buildDockerImage($project);

        return [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',
            'metadata' => [
                'name' => "{$project->slug}-deployment",
                'namespace' => $project->slug,
                'labels' => [
                    'app' => $project->slug,
                    'version' => $project->current_version ?? 'latest',
                ],
            ],
            'spec' => [
                'replicas' => $replicas,
                'strategy' => [
                    'type' => 'RollingUpdate',
                    'rollingUpdate' => [
                        'maxSurge' => 1,
                        'maxUnavailable' => 0,
                    ],
                ],
                'selector' => [
                    'matchLabels' => [
                        'app' => $project->slug,
                    ],
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => $project->slug,
                            'version' => $project->current_version ?? 'latest',
                        ],
                    ],
                    'spec' => [
                        'containers' => [
                            [
                                'name' => 'app',
                                'image' => $image,
                                'imagePullPolicy' => 'Always',
                                'ports' => [
                                    [
                                        'containerPort' => $project->container_port ?? 8000,
                                    ],
                                ],
                                'envFrom' => [
                                    [
                                        'configMapRef' => [
                                            'name' => "{$project->slug}-config",
                                        ],
                                    ],
                                    [
                                        'secretRef' => [
                                            'name' => "{$project->slug}-secret",
                                        ],
                                    ],
                                ],
                                'resources' => [
                                    'requests' => [
                                        'memory' => $options['memory_request'] ?? '256Mi',
                                        'cpu' => $options['cpu_request'] ?? '100m',
                                    ],
                                    'limits' => [
                                        'memory' => $options['memory_limit'] ?? '512Mi',
                                        'cpu' => $options['cpu_limit'] ?? '500m',
                                    ],
                                ],
                                'livenessProbe' => [
                                    'httpGet' => [
                                        'path' => '/health',
                                        'port' => $project->container_port ?? 8000,
                                    ],
                                    'initialDelaySeconds' => 30,
                                    'periodSeconds' => 10,
                                ],
                                'readinessProbe' => [
                                    'httpGet' => [
                                        'path' => '/ready',
                                        'port' => $project->container_port ?? 8000,
                                    ],
                                    'initialDelaySeconds' => 5,
                                    'periodSeconds' => 5,
                                ],
                            ],
                        ],
                        'imagePullSecrets' => [
                            [
                                'name' => 'docker-registry-secret',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate Ingress manifest
     */
    protected function generateIngressManifest(Project $project): array
    {
        $rules = [];

        foreach ($project->domains as $domain) {
            $rules[] = [
                'host' => $domain->full_domain,
                'http' => [
                    'paths' => [
                        [
                            'path' => '/',
                            'pathType' => 'Prefix',
                            'backend' => [
                                'service' => [
                                    'name' => "{$project->slug}-service",
                                    'port' => [
                                        'number' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            'apiVersion' => 'networking.k8s.io/v1',
            'kind' => 'Ingress',
            'metadata' => [
                'name' => "{$project->slug}-ingress",
                'namespace' => $project->slug,
                'annotations' => [
                    'kubernetes.io/ingress.class' => 'nginx',
                    'cert-manager.io/cluster-issuer' => 'letsencrypt-prod',
                    'nginx.ingress.kubernetes.io/ssl-redirect' => 'true',
                ],
            ],
            'spec' => [
                'tls' => array_map(function ($domain) use ($project) {
                    return [
                        'hosts' => [$domain->full_domain],
                        'secretName' => "{$project->slug}-tls-{$domain->id}",
                    ];
                }, $project->domains->toArray()),
                'rules' => $rules,
            ],
        ];
    }

    /**
     * Generate HorizontalPodAutoscaler manifest
     */
    protected function generateHPAManifest(Project $project, array $options = []): array
    {
        return [
            'apiVersion' => 'autoscaling/v2',
            'kind' => 'HorizontalPodAutoscaler',
            'metadata' => [
                'name' => "{$project->slug}-hpa",
                'namespace' => $project->slug,
            ],
            'spec' => [
                'scaleTargetRef' => [
                    'apiVersion' => 'apps/v1',
                    'kind' => 'Deployment',
                    'name' => "{$project->slug}-deployment",
                ],
                'minReplicas' => $options['min_replicas'] ?? 2,
                'maxReplicas' => $options['max_replicas'] ?? 10,
                'metrics' => [
                    [
                        'type' => 'Resource',
                        'resource' => [
                            'name' => 'cpu',
                            'target' => [
                                'type' => 'Utilization',
                                'averageUtilization' => $options['target_cpu_utilization'] ?? 70,
                            ],
                        ],
                    ],
                    [
                        'type' => 'Resource',
                        'resource' => [
                            'name' => 'memory',
                            'target' => [
                                'type' => 'Utilization',
                                'averageUtilization' => $options['target_memory_utilization'] ?? 80,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Apply Kubernetes manifests
     */
    protected function applyManifests(Project $project, array $manifests): array
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
    protected function setupKubectlContext(KubernetesCluster $cluster): void
    {
        // Write kubeconfig
        $kubeconfigPath = "/tmp/kubeconfig-{$cluster->id}";
        file_put_contents($kubeconfigPath, $cluster->kubeconfig);

        // Set KUBECONFIG environment variable
        putenv("KUBECONFIG={$kubeconfigPath}");

        // Test connection
        $result = Process::run("{$this->kubectlPath} cluster-info");

        if (!$result->successful()) {
            throw new \Exception("Failed to connect to Kubernetes cluster: " . $result->errorOutput());
        }
    }

    /**
     * Wait for deployment rollout to complete
     */
    protected function waitForRollout(Project $project): array
    {
        $command = sprintf(
            "%s rollout status deployment/%s-deployment -n %s --timeout=300s",
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
     * Get pod status for the project
     */
    public function getPodStatus(Project $project): array
    {
        $command = sprintf(
            "%s get pods -n %s -l app=%s -o json",
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $result = Process::run($command);

        if (!$result->successful()) {
            return [];
        }

        $pods = json_decode($result->output(), true);

        return array_map(function ($pod) {
            return [
                'name' => $pod['metadata']['name'],
                'status' => $pod['status']['phase'],
                'ready' => $this->isPodReady($pod),
                'restarts' => array_sum(array_column($pod['status']['containerStatuses'] ?? [], 'restartCount')),
                'age' => $this->calculateAge($pod['metadata']['creationTimestamp']),
                'node' => $pod['spec']['nodeName'] ?? 'unknown',
            ];
        }, $pods['items'] ?? []);
    }

    /**
     * Get service endpoints
     */
    public function getServiceEndpoints(Project $project): array
    {
        $command = sprintf(
            "%s get service %s-service -n %s -o json",
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $result = Process::run($command);

        if (!$result->successful()) {
            return [];
        }

        $service = json_decode($result->output(), true);

        $endpoints = [];

        // Get external endpoints
        if (isset($service['status']['loadBalancer']['ingress'])) {
            foreach ($service['status']['loadBalancer']['ingress'] as $ingress) {
                $endpoints[] = $ingress['ip'] ?? $ingress['hostname'];
            }
        }

        // Get ingress endpoints
        $ingressCommand = sprintf(
            "%s get ingress %s-ingress -n %s -o json",
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $ingressResult = Process::run($ingressCommand);

        if ($ingressResult->successful()) {
            $ingress = json_decode($ingressResult->output(), true);

            foreach ($ingress['spec']['rules'] ?? [] as $rule) {
                $endpoints[] = 'https://' . $rule['host'];
            }
        }

        return $endpoints;
    }

    /**
     * Scale deployment
     */
    public function scaleDeployment(Project $project, int $replicas): array
    {
        $command = sprintf(
            "%s scale deployment/%s-deployment --replicas=%d -n %s",
            $this->kubectlPath,
            $project->slug,
            $replicas,
            $project->slug
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'new_replicas' => $replicas,
        ];
    }

    /**
     * Execute command in pod
     */
    public function executeInPod(Project $project, string $command, string $podName = null): array
    {
        if (!$podName) {
            // Get first running pod
            $pods = $this->getPodStatus($project);
            $runningPods = array_filter($pods, fn($pod) => $pod['status'] === 'Running');

            if (empty($runningPods)) {
                throw new \Exception('No running pods found');
            }

            $podName = array_values($runningPods)[0]['name'];
        }

        $execCommand = sprintf(
            "%s exec -n %s %s -- %s",
            $this->kubectlPath,
            $project->slug,
            $podName,
            $command
        );

        $result = Process::run($execCommand);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'pod' => $podName,
        ];
    }

    /**
     * Get deployment logs
     */
    public function getDeploymentLogs(Project $project, int $lines = 100): array
    {
        $command = sprintf(
            "%s logs deployment/%s-deployment -n %s --tail=%d",
            $this->kubectlPath,
            $project->slug,
            $project->slug,
            $lines
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'logs' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Delete Kubernetes resources for a project
     */
    public function deleteResources(Project $project): array
    {
        $command = sprintf(
            "%s delete namespace %s --timeout=60s",
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
     * Deploy using Helm chart
     */
    public function deployWithHelm(Project $project, array $options = []): array
    {
        $chartPath = $options['chart_path'] ?? $this->generateHelmChart($project);
        $releaseName = $project->slug;
        $namespace = $project->slug;

        // Create values file
        $valuesFile = "/tmp/{$project->slug}-values.yaml";
        file_put_contents($valuesFile, yaml_emit($this->generateHelmValues($project, $options)));

        // Install or upgrade helm release
        $command = sprintf(
            "%s upgrade --install %s %s -n %s --create-namespace -f %s --wait --timeout 10m",
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
     * Generate Helm values for the project
     */
    protected function generateHelmValues(Project $project, array $options = []): array
    {
        return [
            'replicaCount' => $options['replicas'] ?? 3,
            'image' => [
                'repository' => $this->getImageRepository($project),
                'tag' => $project->current_version ?? 'latest',
                'pullPolicy' => 'Always',
            ],
            'service' => [
                'type' => $options['service_type'] ?? 'ClusterIP',
                'port' => 80,
                'targetPort' => $project->container_port ?? 8000,
            ],
            'ingress' => [
                'enabled' => true,
                'className' => 'nginx',
                'annotations' => [
                    'cert-manager.io/cluster-issuer' => 'letsencrypt-prod',
                ],
                'hosts' => array_map(function ($domain) {
                    return [
                        'host' => $domain->full_domain,
                        'paths' => [
                            [
                                'path' => '/',
                                'pathType' => 'Prefix',
                            ],
                        ],
                    ];
                }, $project->domains->toArray()),
            ],
            'resources' => [
                'limits' => [
                    'cpu' => $options['cpu_limit'] ?? '500m',
                    'memory' => $options['memory_limit'] ?? '512Mi',
                ],
                'requests' => [
                    'cpu' => $options['cpu_request'] ?? '100m',
                    'memory' => $options['memory_request'] ?? '256Mi',
                ],
            ],
            'autoscaling' => [
                'enabled' => $options['enable_autoscaling'] ?? false,
                'minReplicas' => $options['min_replicas'] ?? 2,
                'maxReplicas' => $options['max_replicas'] ?? 10,
                'targetCPUUtilizationPercentage' => $options['target_cpu_utilization'] ?? 70,
            ],
            'env' => $project->environment_variables ?? [],
            'secrets' => $this->getSecretData($project),
        ];
    }

    /**
     * Check if pod is ready
     */
    protected function isPodReady(array $pod): bool
    {
        foreach ($pod['status']['conditions'] ?? [] as $condition) {
            if ($condition['type'] === 'Ready' && $condition['status'] === 'True') {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate age from timestamp
     */
    protected function calculateAge(string $timestamp): string
    {
        $created = new \DateTime($timestamp);
        $now = new \DateTime();
        $interval = $created->diff($now);

        if ($interval->days > 0) {
            return $interval->days . 'd';
        } elseif ($interval->h > 0) {
            return $interval->h . 'h';
        } else {
            return $interval->i . 'm';
        }
    }

    /**
     * Build Docker image for Kubernetes
     */
    protected function buildDockerImage(Project $project): string
    {
        $registry = config('kubernetes.docker_registry');
        $tag = $project->current_version ?? substr($project->latest_commit_hash, 0, 7);

        $imageName = "{$registry}/{$project->slug}:{$tag}";

        // Build and push image
        $buildCommand = sprintf(
            "docker build -t %s %s && docker push %s",
            $imageName,
            $project->getProjectPath(),
            $imageName
        );

        Process::run($buildCommand);

        return $imageName;
    }

    /**
     * Get image repository for the project
     */
    protected function getImageRepository(Project $project): string
    {
        $registry = config('kubernetes.docker_registry');
        return "{$registry}/{$project->slug}";
    }

    /**
     * Get secret data for the project
     */
    protected function getSecretData(Project $project): array
    {
        return [
            'DB_PASSWORD' => encrypt($project->db_password ?? ''),
            'APP_KEY' => encrypt($project->app_key ?? ''),
            'API_SECRET' => encrypt($project->api_secret ?? ''),
        ];
    }

    /**
     * Run post-deployment tasks
     */
    protected function runPostDeploymentTasks(Project $project): void
    {
        // Run migrations
        if ($project->framework === 'laravel') {
            $this->executeInPod($project, 'php artisan migrate --force');
        }

        // Clear caches
        $this->executeInPod($project, 'php artisan cache:clear');

        // Run custom post-deployment script if defined
        if ($project->post_deployment_script) {
            $this->executeInPod($project, $project->post_deployment_script);
        }
    }

    /**
     * Generate Helm chart for the project
     */
    protected function generateHelmChart(Project $project): string
    {
        $chartPath = "/tmp/helm-{$project->slug}";

        // Create chart structure
        mkdir("{$chartPath}/templates", 0755, true);

        // Chart.yaml
        file_put_contents("{$chartPath}/Chart.yaml", yaml_emit([
            'apiVersion' => 'v2',
            'name' => $project->slug,
            'description' => "Helm chart for {$project->name}",
            'type' => 'application',
            'version' => '1.0.0',
            'appVersion' => $project->current_version ?? '1.0.0',
        ]));

        // values.yaml (default values)
        file_put_contents("{$chartPath}/values.yaml", yaml_emit($this->generateHelmValues($project)));

        // Template files (would be more complex in production)
        // This is simplified for demonstration

        return $chartPath;
    }
}