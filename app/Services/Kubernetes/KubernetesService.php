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
        $isLaravel = $project->framework === 'laravel';

        // Extract hosts and TLS config from domains
        $hosts = [];
        $tlsHosts = [];
        foreach ($project->domains ?? [] as $domain) {
            $hosts[] = [
                'host' => $domain->full_domain ?? $domain,
                'paths' => [
                    [
                        'path' => '/',
                        'pathType' => 'Prefix',
                    ],
                ],
            ];
            $tlsHosts[] = $domain->full_domain ?? $domain;
        }

        return [
            'nameOverride' => '',
            'fullnameOverride' => '',

            'replicaCount' => $options['replicas'] ?? 3,

            'image' => [
                'registry' => config('kubernetes.docker_registry', ''),
                'repository' => $project->slug,
                'tag' => $project->current_version ?? 'latest',
                'pullPolicy' => 'Always',
            ],

            'imagePullSecrets' => $this->getImagePullSecrets($project),

            'serviceAccount' => [
                'create' => true,
                'annotations' => [],
                'name' => '',
            ],

            'podAnnotations' => [
                'prometheus.io/scrape' => 'true',
                'prometheus.io/port' => (string) ($project->container_port ?? 8000),
                'prometheus.io/path' => '/metrics',
            ],

            'podSecurityContext' => [
                'fsGroup' => 1000,
                'runAsUser' => 1000,
                'runAsNonRoot' => true,
            ],

            'securityContext' => [
                'allowPrivilegeEscalation' => false,
                'capabilities' => [
                    'drop' => ['ALL'],
                ],
                'readOnlyRootFilesystem' => false,
            ],

            'service' => [
                'type' => $options['service_type'] ?? 'ClusterIP',
                'port' => 80,
                'targetPort' => $project->container_port ?? 8000,
                'annotations' => [],
            ],

            'ingress' => [
                'enabled' => ! empty($hosts),
                'className' => 'nginx',
                'annotations' => [
                    'cert-manager.io/cluster-issuer' => 'letsencrypt-prod',
                    'nginx.ingress.kubernetes.io/ssl-redirect' => 'true',
                    'nginx.ingress.kubernetes.io/force-ssl-redirect' => 'true',
                ],
                'hosts' => $hosts,
                'tls' => empty($tlsHosts) ? [] : [
                    [
                        'secretName' => "{$project->slug}-tls",
                        'hosts' => $tlsHosts,
                    ],
                ],
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

            'livenessProbe' => [
                'enabled' => true,
                'path' => $isLaravel ? '/health' : '/',
                'initialDelaySeconds' => 30,
                'periodSeconds' => 10,
                'timeoutSeconds' => 5,
                'failureThreshold' => 3,
            ],

            'readinessProbe' => [
                'enabled' => true,
                'path' => $isLaravel ? '/health' : '/',
                'initialDelaySeconds' => 10,
                'periodSeconds' => 5,
                'timeoutSeconds' => 3,
                'failureThreshold' => 3,
            ],

            'autoscaling' => [
                'enabled' => $options['enable_autoscaling'] ?? false,
                'minReplicas' => $options['min_replicas'] ?? 2,
                'maxReplicas' => $options['max_replicas'] ?? 10,
                'targetCPUUtilizationPercentage' => $options['target_cpu_utilization'] ?? 70,
                'targetMemoryUtilizationPercentage' => $options['target_memory_utilization'] ?? 80,
                'behavior' => [
                    'scaleDown' => [
                        'stabilizationWindowSeconds' => 300,
                        'policies' => [
                            [
                                'type' => 'Percent',
                                'value' => 50,
                                'periodSeconds' => 60,
                            ],
                        ],
                    ],
                    'scaleUp' => [
                        'stabilizationWindowSeconds' => 60,
                        'policies' => [
                            [
                                'type' => 'Percent',
                                'value' => 100,
                                'periodSeconds' => 60,
                            ],
                        ],
                    ],
                ],
            ],

            'podDisruptionBudget' => [
                'enabled' => $options['enable_pdb'] ?? true,
                'minAvailable' => $options['pdb_min_available'] ?? 1,
            ],

            'nodeSelector' => [],

            'tolerations' => [],

            'affinity' => [
                'podAntiAffinity' => [
                    'preferredDuringSchedulingIgnoredDuringExecution' => [
                        [
                            'weight' => 100,
                            'podAffinityTerm' => [
                                'labelSelector' => [
                                    'matchExpressions' => [
                                        [
                                            'key' => 'app.kubernetes.io/name',
                                            'operator' => 'In',
                                            'values' => [$project->slug],
                                        ],
                                    ],
                                ],
                                'topologyKey' => 'kubernetes.io/hostname',
                            ],
                        ],
                    ],
                ],
            ],

            'volumes' => $this->getProjectVolumes($project),

            'volumeMounts' => $this->getProjectVolumeMounts($project),

            'initContainers' => $isLaravel ? $this->getLaravelInitContainers($project) : [],

            'config' => $this->getConfigMapData($project),

            'secrets' => $this->getSecretData($project),

            'env' => $project->env_variables ?? [],

            'rbac' => [
                'create' => $isLaravel,
            ],
        ];
    }


    /**
     * Get project volumes configuration
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProjectVolumes(Project $project): array
    {
        $volumes = [];

        if ($project->framework === 'laravel') {
            // Storage volume for Laravel applications
            $volumes[] = [
                'name' => 'storage',
                'persistentVolumeClaim' => [
                    'claimName' => "{$project->slug}-storage",
                ],
            ];

            // Cache volume
            $volumes[] = [
                'name' => 'cache',
                'emptyDir' => [
                    'medium' => 'Memory',
                    'sizeLimit' => '128Mi',
                ],
            ];
        }

        return $volumes;
    }

    /**
     * Get project volume mounts configuration
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProjectVolumeMounts(Project $project): array
    {
        $mounts = [];

        if ($project->framework === 'laravel') {
            $mounts[] = [
                'name' => 'storage',
                'mountPath' => '/var/www/html/storage/app',
                'subPath' => 'app',
            ];

            $mounts[] = [
                'name' => 'storage',
                'mountPath' => '/var/www/html/storage/logs',
                'subPath' => 'logs',
            ];

            $mounts[] = [
                'name' => 'cache',
                'mountPath' => '/var/www/html/storage/framework/cache',
            ];
        }

        return $mounts;
    }

    /**
     * Get Laravel-specific init containers
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getLaravelInitContainers(Project $project): array
    {
        return [
            [
                'name' => 'storage-permissions',
                'image' => 'busybox:latest',
                'command' => ['sh', '-c'],
                'args' => [
                    'chown -R 1000:1000 /var/www/html/storage && chmod -R 775 /var/www/html/storage',
                ],
                'volumeMounts' => $this->getProjectVolumeMounts($project),
            ],
        ];
    }

    /**
     * Get ConfigMap data for the project
     *
     * @return array<string, string>
     */
    protected function getConfigMapData(Project $project): array
    {
        $config = [
            'APP_NAME' => $project->name,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'LOG_CHANNEL' => 'stderr',
            'LOG_LEVEL' => 'info',
        ];

        // Add framework-specific config
        if ($project->framework === 'laravel') {
            $config = array_merge($config, [
                'CACHE_DRIVER' => 'redis',
                'QUEUE_CONNECTION' => 'redis',
                'SESSION_DRIVER' => 'redis',
                'SESSION_LIFETIME' => '120',
                'BROADCAST_DRIVER' => 'log',
            ]);
        }

        // Merge with project-specific non-sensitive env variables
        if (! empty($project->env_variables) && is_array($project->env_variables)) {
            foreach ($project->env_variables as $key => $value) {
                // Skip sensitive variables (they go in secrets)
                if (! $this->isSensitiveVariable((string) $key)) {
                    $config[$key] = (string) $value;
                }
            }
        }

        return $config;
    }

    /**
     * Check if a variable name is sensitive
     */
    protected function isSensitiveVariable(string $key): bool
    {
        $sensitivePatterns = [
            'PASSWORD',
            'SECRET',
            'KEY',
            'TOKEN',
            'CREDENTIAL',
            'API_',
            'AWS_',
            'GOOGLE_',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains(strtoupper($key), $pattern)) {
                return true;
            }
        }

        return false;
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
        if (! is_dir("{$chartPath}/templates")) {
            mkdir("{$chartPath}/templates", 0755, true);
        }

        // Generate Chart.yaml
        $this->generateChartYaml($project, $chartPath);

        // Generate values.yaml
        $this->generateValuesYaml($project, $chartPath);

        // Generate Helm templates
        $this->generateHelmTemplates($project, $chartPath);

        return $chartPath;
    }

    /**
     * Generate Chart.yaml file
     */
    protected function generateChartYaml(Project $project, string $chartPath): void
    {
        $chartContent = yaml_emit([
            'apiVersion' => 'v2',
            'name' => $project->slug,
            'description' => "Helm chart for {$project->name}",
            'type' => 'application',
            'version' => '1.0.0',
            'appVersion' => $project->current_version ?? '1.0.0',
            'keywords' => [
                $project->framework ?? 'php',
                'devflow-pro',
                'kubernetes',
            ],
            'maintainers' => [
                [
                    'name' => 'DevFlow Pro',
                    'email' => 'admin@devflow.pro',
                ],
            ],
        ]);

        file_put_contents("{$chartPath}/Chart.yaml", $chartContent);
    }

    /**
     * Generate values.yaml file
     */
    protected function generateValuesYaml(Project $project, string $chartPath): void
    {
        $valuesContent = yaml_emit($this->generateHelmValues($project));
        file_put_contents("{$chartPath}/values.yaml", $valuesContent);
    }

    /**
     * Generate all Helm template files
     */
    protected function generateHelmTemplates(Project $project, string $chartPath): void
    {
        $templatesPath = "{$chartPath}/templates";

        // Generate _helpers.tpl
        $this->generateHelpersTemplate($project, $templatesPath);

        // Generate deployment.yaml
        $this->generateDeploymentTemplate($project, $templatesPath);

        // Generate service.yaml
        $this->generateServiceTemplate($project, $templatesPath);

        // Generate ingress.yaml
        $this->generateIngressTemplate($project, $templatesPath);

        // Generate configmap.yaml
        $this->generateConfigMapTemplate($project, $templatesPath);

        // Generate secret.yaml
        $this->generateSecretTemplate($project, $templatesPath);

        // Generate hpa.yaml (Horizontal Pod Autoscaler)
        $this->generateHpaTemplate($project, $templatesPath);

        // Generate pdb.yaml (Pod Disruption Budget)
        $this->generatePdbTemplate($project, $templatesPath);

        // Generate serviceaccount.yaml
        $this->generateServiceAccountTemplate($project, $templatesPath);

        // Generate NOTES.txt
        $this->generateNotesTemplate($project, $templatesPath);

        // Generate RBAC templates if Laravel with queue workers
        if ($project->framework === 'laravel') {
            $this->generateRbacTemplates($project, $templatesPath);
        }
    }

    /**
     * Generate _helpers.tpl template
     */
    protected function generateHelpersTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
{{/*
Expand the name of the chart.
*/}}
{{- define "chart.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "chart.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "chart.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "chart.labels" -}}
helm.sh/chart: {{ include "chart.chart" . }}
{{ include "chart.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "chart.selectorLabels" -}}
app.kubernetes.io/name: {{ include "chart.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "chart.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "chart.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
Return the proper image name
*/}}
{{- define "chart.image" -}}
{{- $registry := .Values.image.registry -}}
{{- $repository := .Values.image.repository -}}
{{- $tag := .Values.image.tag | default .Chart.AppVersion -}}
{{- if $registry }}
{{- printf "%s/%s:%s" $registry $repository $tag }}
{{- else }}
{{- printf "%s:%s" $repository $tag }}
{{- end }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/_helpers.tpl", $content);
    }

    /**
     * Generate deployment.yaml template
     */
    protected function generateDeploymentTemplate(Project $project, string $templatesPath): void
    {
        $isLaravel = $project->framework === 'laravel';

        $content = <<<'TEMPLATE'
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
spec:
  {{- if not .Values.autoscaling.enabled }}
  replicas: {{ .Values.replicaCount }}
  {{- end }}
  selector:
    matchLabels:
      {{- include "chart.selectorLabels" . | nindent 6 }}
  template:
    metadata:
      annotations:
        checksum/config: {{ include (print $.Template.BasePath "/configmap.yaml") . | sha256sum }}
        checksum/secret: {{ include (print $.Template.BasePath "/secret.yaml") . | sha256sum }}
      {{- with .Values.podAnnotations }}
        {{- toYaml . | nindent 8 }}
      {{- end }}
      labels:
        {{- include "chart.selectorLabels" . | nindent 8 }}
    spec:
      {{- with .Values.imagePullSecrets }}
      imagePullSecrets:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      serviceAccountName: {{ include "chart.serviceAccountName" . }}
      securityContext:
        {{- toYaml .Values.podSecurityContext | nindent 8 }}
      {{- if .Values.initContainers }}
      initContainers:
        {{- toYaml .Values.initContainers | nindent 8 }}
      {{- end }}
      containers:
        - name: {{ .Chart.Name }}
          securityContext:
            {{- toYaml .Values.securityContext | nindent 12 }}
          image: {{ include "chart.image" . }}
          imagePullPolicy: {{ .Values.image.pullPolicy }}
          ports:
            - name: http
              containerPort: {{ .Values.service.targetPort }}
              protocol: TCP
          {{- if .Values.livenessProbe.enabled }}
          livenessProbe:
            httpGet:
              path: {{ .Values.livenessProbe.path }}
              port: http
            initialDelaySeconds: {{ .Values.livenessProbe.initialDelaySeconds }}
            periodSeconds: {{ .Values.livenessProbe.periodSeconds }}
            timeoutSeconds: {{ .Values.livenessProbe.timeoutSeconds }}
            failureThreshold: {{ .Values.livenessProbe.failureThreshold }}
          {{- end }}
          {{- if .Values.readinessProbe.enabled }}
          readinessProbe:
            httpGet:
              path: {{ .Values.readinessProbe.path }}
              port: http
            initialDelaySeconds: {{ .Values.readinessProbe.initialDelaySeconds }}
            periodSeconds: {{ .Values.readinessProbe.periodSeconds }}
            timeoutSeconds: {{ .Values.readinessProbe.timeoutSeconds }}
            failureThreshold: {{ .Values.readinessProbe.failureThreshold }}
          {{- end }}
          resources:
            {{- toYaml .Values.resources | nindent 12 }}
          envFrom:
            - configMapRef:
                name: {{ include "chart.fullname" . }}-config
            - secretRef:
                name: {{ include "chart.fullname" . }}-secret
          {{- if .Values.env }}
          env:
            {{- range $key, $value := .Values.env }}
            - name: {{ $key }}
              value: {{ $value | quote }}
            {{- end }}
          {{- end }}
          {{- if .Values.volumeMounts }}
          volumeMounts:
            {{- toYaml .Values.volumeMounts | nindent 12 }}
          {{- end }}
      {{- if .Values.volumes }}
      volumes:
        {{- toYaml .Values.volumes | nindent 8 }}
      {{- end }}
      {{- with .Values.nodeSelector }}
      nodeSelector:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      {{- with .Values.affinity }}
      affinity:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      {{- with .Values.tolerations }}
      tolerations:
        {{- toYaml . | nindent 8 }}
      {{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/deployment.yaml", $content);
    }

    /**
     * Generate service.yaml template
     */
    protected function generateServiceTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
apiVersion: v1
kind: Service
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
  {{- with .Values.service.annotations }}
  annotations:
    {{- toYaml . | nindent 4 }}
  {{- end }}
spec:
  type: {{ .Values.service.type }}
  {{- if and (eq .Values.service.type "LoadBalancer") .Values.service.loadBalancerIP }}
  loadBalancerIP: {{ .Values.service.loadBalancerIP }}
  {{- end }}
  ports:
    - port: {{ .Values.service.port }}
      targetPort: http
      protocol: TCP
      name: http
      {{- if and (eq .Values.service.type "NodePort") .Values.service.nodePort }}
      nodePort: {{ .Values.service.nodePort }}
      {{- end }}
  selector:
    {{- include "chart.selectorLabels" . | nindent 4 }}
TEMPLATE;

        file_put_contents("{$templatesPath}/service.yaml", $content);
    }

    /**
     * Generate ingress.yaml template
     */
    protected function generateIngressTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
{{- if .Values.ingress.enabled -}}
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
  {{- with .Values.ingress.annotations }}
  annotations:
    {{- toYaml . | nindent 4 }}
  {{- end }}
spec:
  {{- if .Values.ingress.className }}
  ingressClassName: {{ .Values.ingress.className }}
  {{- end }}
  {{- if .Values.ingress.tls }}
  tls:
    {{- range .Values.ingress.tls }}
    - hosts:
        {{- range .hosts }}
        - {{ . | quote }}
        {{- end }}
      secretName: {{ .secretName }}
    {{- end }}
  {{- end }}
  rules:
    {{- range .Values.ingress.hosts }}
    - host: {{ .host | quote }}
      http:
        paths:
          {{- range .paths }}
          - path: {{ .path }}
            pathType: {{ .pathType }}
            backend:
              service:
                name: {{ include "chart.fullname" $ }}
                port:
                  number: {{ $.Values.service.port }}
          {{- end }}
    {{- end }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/ingress.yaml", $content);
    }

    /**
     * Generate configmap.yaml template
     */
    protected function generateConfigMapTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
apiVersion: v1
kind: ConfigMap
metadata:
  name: {{ include "chart.fullname" . }}-config
  labels:
    {{- include "chart.labels" . | nindent 4 }}
data:
  {{- range $key, $value := .Values.config }}
  {{ $key }}: {{ $value | quote }}
  {{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/configmap.yaml", $content);
    }

    /**
     * Generate secret.yaml template
     */
    protected function generateSecretTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
apiVersion: v1
kind: Secret
metadata:
  name: {{ include "chart.fullname" . }}-secret
  labels:
    {{- include "chart.labels" . | nindent 4 }}
type: Opaque
stringData:
  {{- range $key, $value := .Values.secrets }}
  {{ $key }}: {{ $value | quote }}
  {{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/secret.yaml", $content);
    }

    /**
     * Generate hpa.yaml (Horizontal Pod Autoscaler) template
     */
    protected function generateHpaTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
{{- if .Values.autoscaling.enabled }}
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: {{ include "chart.fullname" . }}
  minReplicas: {{ .Values.autoscaling.minReplicas }}
  maxReplicas: {{ .Values.autoscaling.maxReplicas }}
  metrics:
    {{- if .Values.autoscaling.targetCPUUtilizationPercentage }}
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: {{ .Values.autoscaling.targetCPUUtilizationPercentage }}
    {{- end }}
    {{- if .Values.autoscaling.targetMemoryUtilizationPercentage }}
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: {{ .Values.autoscaling.targetMemoryUtilizationPercentage }}
    {{- end }}
  {{- if .Values.autoscaling.behavior }}
  behavior:
    {{- toYaml .Values.autoscaling.behavior | nindent 4 }}
  {{- end }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/hpa.yaml", $content);
    }

    /**
     * Generate pdb.yaml (Pod Disruption Budget) template
     */
    protected function generatePdbTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
{{- if .Values.podDisruptionBudget.enabled }}
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
spec:
  {{- if .Values.podDisruptionBudget.minAvailable }}
  minAvailable: {{ .Values.podDisruptionBudget.minAvailable }}
  {{- end }}
  {{- if .Values.podDisruptionBudget.maxUnavailable }}
  maxUnavailable: {{ .Values.podDisruptionBudget.maxUnavailable }}
  {{- end }}
  selector:
    matchLabels:
      {{- include "chart.selectorLabels" . | nindent 6 }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/pdb.yaml", $content);
    }

    /**
     * Generate serviceaccount.yaml template
     */
    protected function generateServiceAccountTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
{{- if .Values.serviceAccount.create -}}
apiVersion: v1
kind: ServiceAccount
metadata:
  name: {{ include "chart.serviceAccountName" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
  {{- with .Values.serviceAccount.annotations }}
  annotations:
    {{- toYaml . | nindent 4 }}
  {{- end }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/serviceaccount.yaml", $content);
    }

    /**
     * Generate NOTES.txt template
     */
    protected function generateNotesTemplate(Project $project, string $templatesPath): void
    {
        $content = <<<'TEMPLATE'
1. Get the application URL by running these commands:
{{- if .Values.ingress.enabled }}
{{- range $host := .Values.ingress.hosts }}
  {{- range .paths }}
  http{{ if $.Values.ingress.tls }}s{{ end }}://{{ $host.host }}{{ .path }}
  {{- end }}
{{- end }}
{{- else if contains "NodePort" .Values.service.type }}
  export NODE_PORT=$(kubectl get --namespace {{ .Release.Namespace }} -o jsonpath="{.spec.ports[0].nodePort}" services {{ include "chart.fullname" . }})
  export NODE_IP=$(kubectl get nodes --namespace {{ .Release.Namespace }} -o jsonpath="{.items[0].status.addresses[0].address}")
  echo http://$NODE_IP:$NODE_PORT
{{- else if contains "LoadBalancer" .Values.service.type }}
     NOTE: It may take a few minutes for the LoadBalancer IP to be available.
           You can watch the status of by running 'kubectl get --namespace {{ .Release.Namespace }} svc -w {{ include "chart.fullname" . }}'
  export SERVICE_IP=$(kubectl get svc --namespace {{ .Release.Namespace }} {{ include "chart.fullname" . }} --template "{{"{{ range (index .status.loadBalancer.ingress 0) }}{{.}}{{ end }}"}}")
  echo http://$SERVICE_IP:{{ .Values.service.port }}
{{- else if contains "ClusterIP" .Values.service.type }}
  export POD_NAME=$(kubectl get pods --namespace {{ .Release.Namespace }} -l "app.kubernetes.io/name={{ include "chart.name" . }},app.kubernetes.io/instance={{ .Release.Name }}" -o jsonpath="{.items[0].metadata.name}")
  export CONTAINER_PORT=$(kubectl get pod --namespace {{ .Release.Namespace }} $POD_NAME -o jsonpath="{.spec.containers[0].ports[0].containerPort}")
  echo "Visit http://127.0.0.1:8080 to use your application"
  kubectl --namespace {{ .Release.Namespace }} port-forward $POD_NAME 8080:$CONTAINER_PORT
{{- end }}

2. Check pod status:
  kubectl get pods --namespace {{ .Release.Namespace }} -l "app.kubernetes.io/name={{ include "chart.name" . }},app.kubernetes.io/instance={{ .Release.Name }}"

3. View logs:
  kubectl logs --namespace {{ .Release.Namespace }} -l "app.kubernetes.io/name={{ include "chart.name" . }},app.kubernetes.io/instance={{ .Release.Name }}" --tail=100 -f

4. Execute commands in the pod:
  kubectl exec --namespace {{ .Release.Namespace }} -it $(kubectl get pods --namespace {{ .Release.Namespace }} -l "app.kubernetes.io/name={{ include "chart.name" . }},app.kubernetes.io/instance={{ .Release.Name }}" -o jsonpath="{.items[0].metadata.name}") -- /bin/sh
TEMPLATE;

        file_put_contents("{$templatesPath}/NOTES.txt", $content);
    }

    /**
     * Generate RBAC templates for Laravel applications
     */
    protected function generateRbacTemplates(Project $project, string $templatesPath): void
    {
        // Role template
        $roleContent = <<<'TEMPLATE'
{{- if .Values.rbac.create -}}
apiVersion: rbac.authorization.k8s.io/v1
kind: Role
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
rules:
  - apiGroups: [""]
    resources: ["pods", "pods/log"]
    verbs: ["get", "list", "watch"]
  - apiGroups: [""]
    resources: ["configmaps", "secrets"]
    verbs: ["get", "list"]
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/role.yaml", $roleContent);

        // RoleBinding template
        $roleBindingContent = <<<'TEMPLATE'
{{- if .Values.rbac.create -}}
apiVersion: rbac.authorization.k8s.io/v1
kind: RoleBinding
metadata:
  name: {{ include "chart.fullname" . }}
  labels:
    {{- include "chart.labels" . | nindent 4 }}
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: Role
  name: {{ include "chart.fullname" . }}
subjects:
  - kind: ServiceAccount
    name: {{ include "chart.serviceAccountName" . }}
    namespace: {{ .Release.Namespace }}
{{- end }}
TEMPLATE;

        file_put_contents("{$templatesPath}/rolebinding.yaml", $roleBindingContent);
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
