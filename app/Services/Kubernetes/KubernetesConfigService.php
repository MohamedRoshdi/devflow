<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\Project;

/**
 * Handles Kubernetes configuration and manifest generation.
 *
 * Responsible for generating Kubernetes manifests, Helm charts,
 * values files, and all template configurations.
 */
class KubernetesConfigService
{
    /**
     * Generate Kubernetes manifests for the project
     *
     * @param  array<string, mixed>  $options
     * @return array<string, array<string, mixed>>
     */
    public function generateManifests(Project $project, array $options = []): array
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
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateDeploymentManifest(Project $project, array $options = []): array
    {
        $replicas = $options['replicas'] ?? 3;
        $image = $this->getImageName($project);

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
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateIngressManifest(Project $project, array $options = []): array
    {
        $rules = [];

        // Use host from options or from project domains
        $host = $options['host'] ?? null;

        if ($host) {
            // Single host from options
            $rules[] = [
                'host' => $host,
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
        } else {
            // Multiple hosts from project domains
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
        }

        $spec = [
            'rules' => $rules,
        ];

        // Add TLS configuration if enabled
        $enableTls = $options['enable_tls'] ?? false;
        if ($enableTls && $host) {
            $spec['tls'] = [
                [
                    'hosts' => [$host],
                    'secretName' => "{$project->slug}-tls",
                ],
            ];
        } elseif ($project->domains->isNotEmpty()) {
            $spec['tls'] = $project->domains->map(function ($domain) use ($project) {
                return [
                    'hosts' => [$domain->full_domain],
                    'secretName' => "{$project->slug}-tls-{$domain->id}",
                ];
            })->toArray();
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
            'spec' => $spec,
        ];
    }

    /**
     * Generate HorizontalPodAutoscaler manifest
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateHPAManifest(Project $project, array $options = []): array
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
     * Generate Helm values for the project
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateHelmValues(Project $project, array $options = []): array
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
     * Generate Helm chart for the project
     */
    public function generateHelmChart(Project $project): string
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

        $this->generateHelpersTemplate($project, $templatesPath);
        $this->generateDeploymentTemplate($project, $templatesPath);
        $this->generateServiceTemplate($project, $templatesPath);
        $this->generateIngressTemplate($project, $templatesPath);
        $this->generateConfigMapTemplate($project, $templatesPath);
        $this->generateSecretTemplate($project, $templatesPath);
        $this->generateHpaTemplate($project, $templatesPath);
        $this->generatePdbTemplate($project, $templatesPath);
        $this->generateServiceAccountTemplate($project, $templatesPath);
        $this->generateNotesTemplate($project, $templatesPath);

        if ($project->framework === 'laravel') {
            $this->generateRbacTemplates($project, $templatesPath);
        }
    }

    /**
     * Get project volumes configuration
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectVolumes(Project $project): array
    {
        $volumes = [];

        if ($project->framework === 'laravel') {
            $volumes[] = [
                'name' => 'storage',
                'persistentVolumeClaim' => [
                    'claimName' => "{$project->slug}-storage",
                ],
            ];

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
    public function getProjectVolumeMounts(Project $project): array
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
    public function getLaravelInitContainers(Project $project): array
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
    public function getConfigMapData(Project $project): array
    {
        $config = [
            'APP_NAME' => $project->name,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'LOG_CHANNEL' => 'stderr',
            'LOG_LEVEL' => 'info',
        ];

        if ($project->framework === 'laravel') {
            $config = array_merge($config, [
                'CACHE_DRIVER' => 'redis',
                'QUEUE_CONNECTION' => 'redis',
                'SESSION_DRIVER' => 'redis',
                'SESSION_LIFETIME' => '120',
                'BROADCAST_DRIVER' => 'log',
            ]);
        }

        if (! empty($project->env_variables) && is_array($project->env_variables)) {
            foreach ($project->env_variables as $key => $value) {
                if (! $this->isSensitiveVariable((string) $key)) {
                    $config[$key] = (string) $value;
                }
            }
        }

        return $config;
    }

    /**
     * Get secret data for the project
     *
     * @return array<string, string>
     */
    public function getSecretData(Project $project): array
    {
        return [
            'DB_PASSWORD' => encrypt($project->db_password ?? ''),
            'APP_KEY' => encrypt($project->app_key ?? ''),
            'API_SECRET' => encrypt($project->api_secret ?? ''),
        ];
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
     * Get image name for the project
     */
    protected function getImageName(Project $project): string
    {
        $registry = config('kubernetes.docker_registry');
        $tag = $project->current_version ?? 'latest';

        return "{$registry}/{$project->slug}:{$tag}";
    }

    /**
     * Get image pull secrets for a project
     *
     * @return array<int, array<string, string>>
     */
    protected function getImagePullSecrets(Project $project): array
    {
        $secrets = [];

        $registries = $project->dockerRegistries()->active()->get();

        foreach ($registries as $registry) {
            $secrets[] = [
                'name' => $registry->getSecretName(),
            ];
        }

        return $secrets;
    }

    // Template generation methods follow...

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

    protected function generateDeploymentTemplate(Project $project, string $templatesPath): void
    {
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
TEMPLATE;

        file_put_contents("{$templatesPath}/NOTES.txt", $content);
    }

    protected function generateRbacTemplates(Project $project, string $templatesPath): void
    {
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
}
