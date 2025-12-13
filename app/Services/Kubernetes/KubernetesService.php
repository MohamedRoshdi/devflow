<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\DockerRegistry;
use App\Models\KubernetesCluster;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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

        if (! $cluster) {
            throw new \Exception('No Kubernetes cluster configured for this project');
        }

        // Set up kubectl context
        $this->setupKubectlContext($cluster);

        // Create or update docker registry secrets
        $this->createDockerRegistrySecrets($project);

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
            'data' => $project->env_variables ?? [],
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
                        'imagePullSecrets' => $this->getImagePullSecrets($project),
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

        if (! $result->successful()) {
            throw new \Exception('Failed to connect to Kubernetes cluster: '.$result->errorOutput());
        }
    }

    /**
     * Wait for deployment rollout to complete
     */
    protected function waitForRollout(Project $project): array
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
     * Get pod status for the project
     */
    public function getPodStatus(Project $project): array
    {
        try {
            $command = sprintf(
                '%s get pods -n %s -l app=%s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $result = Process::run($command);

            if (! $result->successful()) {
                Log::warning('KubernetesService: Failed to get pod status', [
                    'project_id' => $project->id,
                    'project_slug' => $project->slug,
                    'namespace' => $project->slug,
                    'error' => $result->errorOutput(),
                ]);

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
        } catch (\Exception $e) {
            Log::error('KubernetesService: Failed to get pod status', [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'namespace' => $project->slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get service endpoints
     */
    public function getServiceEndpoints(Project $project): array
    {
        try {
            $command = sprintf(
                '%s get service %s-service -n %s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $result = Process::run($command);

            if (! $result->successful()) {
                Log::warning('KubernetesService: Failed to get service endpoints', [
                    'project_id' => $project->id,
                    'project_slug' => $project->slug,
                    'namespace' => $project->slug,
                    'service_name' => $project->slug . '-service',
                    'error' => $result->errorOutput(),
                ]);

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
                '%s get ingress %s-ingress -n %s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $ingressResult = Process::run($ingressCommand);

            if ($ingressResult->successful()) {
                $ingress = json_decode($ingressResult->output(), true);

                foreach ($ingress['spec']['rules'] ?? [] as $rule) {
                    $endpoints[] = 'https://'.$rule['host'];
                }
            }

            return $endpoints;
        } catch (\Exception $e) {
            Log::error('KubernetesService: Failed to get service endpoints', [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'namespace' => $project->slug,
                'service_name' => $project->slug . '-service',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Scale deployment
     */
    public function scaleDeployment(Project $project, int $replicas): array
    {
        $command = sprintf(
            '%s scale deployment/%s-deployment --replicas=%d -n %s',
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
    public function executeInPod(Project $project, string $command, ?string $podName = null): array
    {
        if (! $podName) {
            // Get first running pod
            $pods = $this->getPodStatus($project);
            $runningPods = array_filter($pods, fn ($pod) => $pod['status'] === 'Running');

            if (empty($runningPods)) {
                throw new \Exception('No running pods found');
            }

            $podName = array_values($runningPods)[0]['name'];
        }

        $execCommand = sprintf(
            '%s exec -n %s %s -- %s',
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
            '%s logs deployment/%s-deployment -n %s --tail=%d',
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
            'env' => $project->env_variables ?? [],
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
        $now = new \DateTime;
        $interval = $created->diff($now);

        if ($interval->days > 0) {
            return $interval->days.'d';
        } elseif ($interval->h > 0) {
            return $interval->h.'h';
        } else {
            return $interval->i.'m';
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
            'docker build -t %s %s && docker push %s',
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

    /**
     * Create or update Docker registry secrets in Kubernetes
     *
     * @return array<int, array<string, mixed>>
     */
    public function createDockerRegistrySecrets(Project $project): array
    {
        $results = [];
        $registries = $project->dockerRegistries()->active()->get();

        foreach ($registries as $registry) {
            try {
                $result = $this->createRegistrySecret($project, $registry);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error('KubernetesService: Failed to create registry secret', [
                    'project_id' => $project->id,
                    'registry_id' => $registry->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'success' => false,
                    'registry_id' => $registry->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create a single Docker registry secret
     *
     * @return array<string, mixed>
     */
    protected function createRegistrySecret(Project $project, DockerRegistry $registry): array
    {
        if (! $registry->isConfigured()) {
            throw new \Exception("Registry {$registry->name} is not properly configured");
        }

        if (! $registry->validateCredentials()) {
            throw new \Exception("Registry {$registry->name} has invalid credentials");
        }

        $secretName = $registry->getSecretName();
        $namespace = $project->slug;

        // Build docker config JSON
        $dockerConfig = $this->buildDockerConfigJson($registry);

        // Delete existing secret if it exists
        Process::run(
            sprintf(
                '%s delete secret %s -n %s --ignore-not-found=true',
                $this->kubectlPath,
                $secretName,
                $namespace
            )
        );

        // Create new secret
        $command = sprintf(
            '%s create secret docker-registry %s -n %s --docker-server=%s --docker-username=%s --docker-password=%s',
            $this->kubectlPath,
            $secretName,
            $namespace,
            escapeshellarg($registry->registry_url),
            escapeshellarg($registry->username),
            escapeshellarg($registry->getDecryptedPassword() ?? '')
        );

        if ($registry->email) {
            $command .= sprintf(' --docker-email=%s', escapeshellarg($registry->email));
        }

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new \Exception("Failed to create registry secret: {$result->errorOutput()}");
        }

        return [
            'success' => true,
            'registry_id' => $registry->id,
            'secret_name' => $secretName,
            'output' => $result->output(),
        ];
    }

    /**
     * Build Docker config JSON for registry authentication
     *
     * @return array<string, mixed>
     */
    protected function buildDockerConfigJson(DockerRegistry $registry): array
    {
        $auth = base64_encode($registry->username.':'.$registry->getDecryptedPassword());

        return [
            'auths' => [
                $registry->registry_url => [
                    'username' => $registry->username,
                    'password' => $registry->getDecryptedPassword(),
                    'email' => $registry->email ?? '',
                    'auth' => $auth,
                ],
            ],
        ];
    }

    /**
     * Get image pull secrets for a project
     *
     * @return array<int, array<string, string>>
     */
    protected function getImagePullSecrets(Project $project): array
    {
        $secrets = [];

        // Load active registries for the project
        $registries = $project->dockerRegistries()->active()->get();

        foreach ($registries as $registry) {
            $secrets[] = [
                'name' => $registry->getSecretName(),
            ];
        }

        // If no registries configured, return empty array (public images only)
        return $secrets;
    }

    /**
     * Store Docker registry credentials for a project
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function storeDockerRegistryCredentials(Project $project, array $credentialsData): DockerRegistry
    {
        // Validate required fields
        $this->validateRegistryData($credentialsData);

        // Extract credentials based on registry type
        $credentials = $this->extractCredentials($credentialsData);

        // Create registry record
        $registry = $project->dockerRegistries()->create([
            'name' => $credentialsData['name'],
            'registry_type' => $credentialsData['registry_type'],
            'registry_url' => $credentialsData['registry_url'] ?? DockerRegistry::getDefaultUrl($credentialsData['registry_type']),
            'username' => $credentialsData['username'],
            'credentials' => $credentials,
            'email' => $credentialsData['email'] ?? null,
            'is_default' => $credentialsData['is_default'] ?? false,
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        return $registry;
    }

    /**
     * Retrieve Docker registry credentials for a project
     *
     * @return array<int, DockerRegistry>
     */
    public function getDockerRegistryCredentials(Project $project): array
    {
        return $project->dockerRegistries()->active()->get()->all();
    }

    /**
     * Get the default Docker registry for a project
     */
    public function getDefaultDockerRegistry(Project $project): ?DockerRegistry
    {
        return $project->defaultDockerRegistry;
    }

    /**
     * Update Docker registry credentials
     *
     * @param  array<string, mixed>  $credentialsData
     */
    public function updateDockerRegistryCredentials(DockerRegistry $registry, array $credentialsData): DockerRegistry
    {
        // Validate the data
        $this->validateRegistryData($credentialsData, false);

        // Extract credentials if password/token is being updated
        if (isset($credentialsData['password']) || isset($credentialsData['token']) || isset($credentialsData['credentials'])) {
            $credentials = $this->extractCredentials($credentialsData);
            $registry->credentials = $credentials;
        }

        // Update other fields
        $registry->fill([
            'name' => $credentialsData['name'] ?? $registry->name,
            'registry_url' => $credentialsData['registry_url'] ?? $registry->registry_url,
            'username' => $credentialsData['username'] ?? $registry->username,
            'email' => $credentialsData['email'] ?? $registry->email,
            'is_default' => $credentialsData['is_default'] ?? $registry->is_default,
        ]);

        $registry->save();

        return $registry;
    }

    /**
     * Delete Docker registry credentials
     */
    public function deleteDockerRegistryCredentials(DockerRegistry $registry): bool
    {
        try {
            // Delete the Kubernetes secret if it exists
            $project = $registry->project;
            $secretName = $registry->getSecretName();

            if ($project && $project->kubernetesCluster) {
                $this->setupKubectlContext($project->kubernetesCluster);

                Process::run(
                    sprintf(
                        '%s delete secret %s -n %s --ignore-not-found=true',
                        $this->kubectlPath,
                        $secretName,
                        $project->slug
                    )
                );
            }

            // Delete the database record
            return (bool) $registry->delete();
        } catch (\Exception $e) {
            Log::error('KubernetesService: Failed to delete registry credentials', [
                'registry_id' => $registry->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test Docker registry connection
     *
     * @return array<string, mixed>
     */
    public function testDockerRegistryConnection(DockerRegistry $registry): array
    {
        try {
            if (! $registry->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Registry is not properly configured',
                ];
            }

            if (! $registry->validateCredentials()) {
                return [
                    'success' => false,
                    'message' => 'Registry credentials are invalid',
                ];
            }

            // Attempt to login to the registry
            $password = $registry->getDecryptedPassword();
            $command = sprintf(
                'echo %s | docker login %s --username %s --password-stdin',
                escapeshellarg($password ?? ''),
                escapeshellarg($registry->registry_url),
                escapeshellarg($registry->username)
            );

            $result = Process::run($command);

            if ($result->successful()) {
                // Logout after successful test
                Process::run("docker logout {$registry->registry_url}");

                // Update last tested timestamp
                $registry->update([
                    'last_tested_at' => now(),
                    'status' => DockerRegistry::STATUS_ACTIVE,
                ]);

                return [
                    'success' => true,
                    'message' => 'Successfully connected to registry',
                ];
            }

            $registry->update(['status' => DockerRegistry::STATUS_FAILED]);

            return [
                'success' => false,
                'message' => 'Failed to connect to registry',
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            Log::error('KubernetesService: Failed to test registry connection', [
                'registry_id' => $registry->id,
                'error' => $e->getMessage(),
            ]);

            $registry->update(['status' => DockerRegistry::STATUS_FAILED]);

            return [
                'success' => false,
                'message' => 'Exception occurred during connection test',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate registry data
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateRegistryData(array $data, bool $isCreate = true): void
    {
        if ($isCreate) {
            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Registry name is required');
            }

            if (empty($data['registry_type'])) {
                throw new \InvalidArgumentException('Registry type is required');
            }

            if (empty($data['username'])) {
                throw new \InvalidArgumentException('Username is required');
            }
        }

        // Validate registry type
        if (isset($data['registry_type']) && ! in_array($data['registry_type'], array_keys(DockerRegistry::getRegistryTypes()))) {
            throw new \InvalidArgumentException('Invalid registry type');
        }

        // Validate registry URL format
        if (isset($data['registry_url']) && ! $this->isValidRegistryUrl($data['registry_url'])) {
            throw new \InvalidArgumentException('Invalid registry URL format');
        }
    }

    /**
     * Validate registry URL format
     */
    protected function isValidRegistryUrl(string $url): bool
    {
        // Allow domain names with or without protocol
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        // Also allow simple domain names without protocol
        $pattern = '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(:[0-9]{1,5})?$/i';

        return preg_match($pattern, $url) === 1;
    }

    /**
     * Extract credentials from data based on registry type
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractCredentials(array $data): array
    {
        $registryType = $data['registry_type'] ?? DockerRegistry::TYPE_DOCKER_HUB;

        return match ($registryType) {
            DockerRegistry::TYPE_DOCKER_HUB => [
                'password' => $data['password'] ?? '',
            ],
            DockerRegistry::TYPE_GITHUB => [
                'token' => $data['token'] ?? $data['password'] ?? '',
            ],
            DockerRegistry::TYPE_GITLAB => [
                'token' => $data['token'] ?? null,
                'password' => $data['password'] ?? null,
            ],
            DockerRegistry::TYPE_AWS_ECR => [
                'aws_access_key_id' => $data['aws_access_key_id'] ?? '',
                'aws_secret_access_key' => $data['aws_secret_access_key'] ?? '',
                'region' => $data['region'] ?? 'us-east-1',
            ],
            DockerRegistry::TYPE_GOOGLE_GCR => [
                'service_account_json' => $data['service_account_json'] ?? [],
            ],
            DockerRegistry::TYPE_AZURE_ACR => [
                'password' => $data['password'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'client_secret' => $data['client_secret'] ?? null,
            ],
            default => [
                'password' => $data['password'] ?? '',
            ],
        };
    }
}
