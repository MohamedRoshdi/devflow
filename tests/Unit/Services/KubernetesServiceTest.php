<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Domain;
use App\Models\KubernetesCluster;
use App\Models\Project;
use App\Services\Kubernetes\KubernetesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Process;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * KubernetesService Test Suite
 *
 * Comprehensive unit tests for the KubernetesService covering:
 * - Cluster connection and authentication
 * - Namespace management
 * - Pod operations (list, create, status)
 * - Deployment operations and rollouts
 * - Service operations and endpoints
 * - ConfigMap and Secret management
 * - Resource scaling
 * - Health checks and pod readiness
 * - HPA (Horizontal Pod Autoscaler) configuration
 * - Ingress manifest generation
 * - Helm chart deployment
 * - Docker image building
 * - Error handling and failure scenarios
 * - Command execution in pods
 * - Deployment logs retrieval
 * - Resource deletion
 *
 * Total Tests: 38
 *
 * Note: Some tests require the PHP yaml extension (php-yaml).
 * To install: sudo apt-get install php8.4-yaml
 * Or use: pecl install yaml
 */
class KubernetesServiceTest extends TestCase
{
    protected KubernetesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KubernetesService();

        // Mock config values
        config(['kubernetes.docker_registry' => 'registry.example.com']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockProject(array $attributes = []): Project|Mockery\MockInterface
    {
        $project = Mockery::mock(Project::class)->makePartial();

        $defaults = [
            'id' => 1,
            'slug' => 'test-project',
            'name' => 'Test Project',
            'branch' => 'main',
            'framework' => 'laravel',
            'container_port' => 8000,
            'current_version' => 'v1.0.0',
            'latest_commit_hash' => 'abcd1234567890',
            'env_variables' => ['APP_ENV' => 'production'],
            'db_password' => 'secret123',
            'app_key' => 'base64:key123',
            'api_secret' => 'api-secret-key',
            'post_deployment_script' => null,
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $project->$key = $value;
            $project->shouldReceive('getAttribute')->with($key)->andReturn($value)->byDefault();
        }

        $project->shouldReceive('getProjectPath')->andReturn('/var/www/test-project')->byDefault();

        // Mock domains relationship by default
        $project->shouldReceive('domains')->andReturn(Mockery::mock([
            'exists' => false,
            'toArray' => [],
        ]))->byDefault();

        return $project;
    }

    protected function mockKubernetesCluster(array $attributes = []): KubernetesCluster|Mockery\MockInterface
    {
        $cluster = Mockery::mock(KubernetesCluster::class)->makePartial();

        $defaults = [
            'id' => 1,
            'name' => 'Test Cluster',
            'kubeconfig' => 'test-kubeconfig-content',
            'is_active' => true,
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $cluster->$key = $value;
            $cluster->shouldReceive('getAttribute')->with($key)->andReturn($value)->byDefault();
        }

        return $cluster;
    }

    #[Test]
    public function it_can_deploy_project_to_kubernetes(): void
    {
        $cluster = $this->mockKubernetesCluster();
        $project = $this->mockProject();
        $project->kubernetesCluster = $cluster;

        Process::fake([
            '*cluster-info*' => Process::result(output: 'Cluster is running'),
            '*apply*' => Process::result(output: 'resource created'),
            '*rollout status*' => Process::result(output: 'successfully rolled out'),
            '*get pods*' => Process::result(output: json_encode([
                'items' => [[
                    'metadata' => ['name' => 'test-pod-1', 'creationTimestamp' => '2025-12-08T00:00:00Z'],
                    'status' => ['phase' => 'Running', 'conditions' => [['type' => 'Ready', 'status' => 'True']], 'containerStatuses' => [['restartCount' => 0]]],
                    'spec' => ['nodeName' => 'node-1'],
                ]],
            ])),
            '*get service*' => Process::result(output: json_encode([
                'metadata' => ['name' => 'test-service'],
                'status' => ['loadBalancer' => ['ingress' => [['ip' => '1.2.3.4']]]],
            ])),
            '*get ingress*' => Process::result(output: json_encode(['spec' => ['rules' => [['host' => 'test.example.com']]]])),
            '*docker build*' => Process::result(output: 'Successfully built'),
            '*php artisan*' => Process::result(output: 'Migration completed'),
        ]);

        $result = $this->service->deployToKubernetes($project);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('deployment', $result);
        $this->assertArrayHasKey('rollout_status', $result);
        $this->assertArrayHasKey('endpoints', $result);
        $this->assertArrayHasKey('pods', $result);
    }

    #[Test]
    public function it_throws_exception_when_no_cluster_configured(): void
    {
        $project = $this->mockProject();
        $project->kubernetesCluster = null;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No Kubernetes cluster configured for this project');

        $this->service->deployToKubernetes($project);
    }

    #[Test]
    public function it_can_setup_kubectl_context(): void
    {
        $cluster = $this->mockKubernetesCluster();

        Process::fake([
            '*cluster-info*' => Process::result(output: 'Kubernetes control plane is running'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setupKubectlContext');
        $method->setAccessible(true);

        $method->invoke($this->service, $cluster);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_cluster_connection_fails(): void
    {
        $cluster = $this->mockKubernetesCluster();

        Process::fake([
            '*cluster-info*' => Process::result(errorOutput: 'Connection refused', exitCode: 1),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setupKubectlContext');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to connect to Kubernetes cluster');

        $method->invoke($this->service, $cluster);
    }

    #[Test]
    public function it_can_generate_namespace_manifest(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, []);

        $this->assertArrayHasKey('namespace', $manifests);
        $this->assertEquals('v1', $manifests['namespace']['apiVersion']);
        $this->assertEquals('Namespace', $manifests['namespace']['kind']);
        $this->assertEquals('test-app', $manifests['namespace']['metadata']['name']);
        $this->assertEquals('devflow-pro', $manifests['namespace']['metadata']['labels']['managed-by']);
    }

    #[Test]
    public function it_can_generate_configmap_manifest(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'env_variables' => [
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ],
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, []);

        $this->assertArrayHasKey('configmap', $manifests);
        $this->assertEquals('ConfigMap', $manifests['configmap']['kind']);
        $this->assertEquals('test-app-config', $manifests['configmap']['metadata']['name']);
        $this->assertEquals('production', $manifests['configmap']['data']['APP_ENV']);
        $this->assertEquals('false', $manifests['configmap']['data']['APP_DEBUG']);
    }

    #[Test]
    public function it_can_generate_secret_manifest(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, []);

        $this->assertArrayHasKey('secret', $manifests);
        $this->assertEquals('Secret', $manifests['secret']['kind']);
        $this->assertEquals('test-app-secret', $manifests['secret']['metadata']['name']);
        $this->assertEquals('Opaque', $manifests['secret']['type']);
        $this->assertArrayHasKey('stringData', $manifests['secret']);
    }

    #[Test]
    public function it_can_generate_deployment_manifest(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'container_port' => 8080,
            'current_version' => 'v1.0.0',
        ]);

        Process::fake([
            '*docker build*' => Process::result(output: 'Successfully built'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateDeploymentManifest');
        $method->setAccessible(true);

        $manifest = $method->invoke($this->service, $project, ['replicas' => 5]);

        $this->assertEquals('apps/v1', $manifest['apiVersion']);
        $this->assertEquals('Deployment', $manifest['kind']);
        $this->assertEquals('test-app-deployment', $manifest['metadata']['name']);
        $this->assertEquals(5, $manifest['spec']['replicas']);
        $this->assertEquals('RollingUpdate', $manifest['spec']['strategy']['type']);
        $this->assertEquals(8080, $manifest['spec']['template']['spec']['containers'][0]['ports'][0]['containerPort']);
    }

    #[Test]
    public function it_can_generate_deployment_manifest_with_custom_resources(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*docker build*' => Process::result(output: 'Successfully built'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateDeploymentManifest');
        $method->setAccessible(true);

        $manifest = $method->invoke($this->service, $project, [
            'memory_request' => '512Mi',
            'cpu_request' => '250m',
            'memory_limit' => '1Gi',
            'cpu_limit' => '1000m',
        ]);

        $resources = $manifest['spec']['template']['spec']['containers'][0]['resources'];
        $this->assertEquals('512Mi', $resources['requests']['memory']);
        $this->assertEquals('250m', $resources['requests']['cpu']);
        $this->assertEquals('1Gi', $resources['limits']['memory']);
        $this->assertEquals('1000m', $resources['limits']['cpu']);
    }

    #[Test]
    public function it_can_generate_service_manifest(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'container_port' => 9000,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, ['service_type' => 'LoadBalancer']);

        $this->assertArrayHasKey('service', $manifests);
        $this->assertEquals('Service', $manifests['service']['kind']);
        $this->assertEquals('test-app-service', $manifests['service']['metadata']['name']);
        $this->assertEquals('LoadBalancer', $manifests['service']['spec']['type']);
        $this->assertEquals(80, $manifests['service']['spec']['ports'][0]['port']);
        $this->assertEquals(9000, $manifests['service']['spec']['ports'][0]['targetPort']);
    }

    #[Test]
    public function it_can_generate_ingress_manifest_when_domains_exist(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        $domain = Mockery::mock(Domain::class)->makePartial();
        $domain->id = 1;
        $domain->full_domain = 'app.example.com';

        $domainsRelation = Mockery::mock();
        $domainsRelation->shouldReceive('exists')->andReturn(true);
        $domainsRelation->shouldReceive('toArray')->andReturn([$domain]);
        $domainsRelation->domains = new Collection([$domain]);
        $project->shouldReceive('domains')->andReturn($domainsRelation);
        $project->domains = new Collection([$domain]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, []);

        $this->assertArrayHasKey('ingress', $manifests);
        $this->assertEquals('Ingress', $manifests['ingress']['kind']);
        $this->assertEquals('test-app-ingress', $manifests['ingress']['metadata']['name']);
    }

    #[Test]
    public function it_can_generate_hpa_manifest_when_autoscaling_enabled(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateManifests');
        $method->setAccessible(true);

        $manifests = $method->invoke($this->service, $project, [
            'enable_autoscaling' => true,
            'min_replicas' => 3,
            'max_replicas' => 20,
            'target_cpu_utilization' => 75,
            'target_memory_utilization' => 85,
        ]);

        $this->assertArrayHasKey('hpa', $manifests);
        $this->assertEquals('HorizontalPodAutoscaler', $manifests['hpa']['kind']);
        $this->assertEquals('autoscaling/v2', $manifests['hpa']['apiVersion']);
        $this->assertEquals(3, $manifests['hpa']['spec']['minReplicas']);
        $this->assertEquals(20, $manifests['hpa']['spec']['maxReplicas']);
        $this->assertEquals(75, $manifests['hpa']['spec']['metrics'][0]['resource']['target']['averageUtilization']);
        $this->assertEquals(85, $manifests['hpa']['spec']['metrics'][1]['resource']['target']['averageUtilization']);
    }

    #[Test]
    public function it_can_apply_manifests_to_cluster(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*kubectl apply*' => Process::result(output: 'namespace/test-app created'),
        ]);

        $manifests = [
            'namespace' => [
                'apiVersion' => 'v1',
                'kind' => 'Namespace',
                'metadata' => ['name' => 'test-app'],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('applyManifests');
        $method->setAccessible(true);

        $results = $method->invoke($this->service, $project, $manifests);

        $this->assertArrayHasKey('namespace', $results);
        $this->assertTrue($results['namespace']['success']);
        $this->assertStringContainsString('created', $results['namespace']['output']);
    }

    #[Test]
    public function it_can_handle_manifest_application_failure(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*kubectl apply*' => Process::result(errorOutput: 'Error: namespace already exists', exitCode: 1),
        ]);

        $manifests = [
            'namespace' => [
                'apiVersion' => 'v1',
                'kind' => 'Namespace',
                'metadata' => ['name' => 'test-app'],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('applyManifests');
        $method->setAccessible(true);

        $results = $method->invoke($this->service, $project, $manifests);

        $this->assertFalse($results['namespace']['success']);
        $this->assertStringContainsString('already exists', $results['namespace']['error']);
    }

    #[Test]
    public function it_can_wait_for_deployment_rollout(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*rollout status*' => Process::result(output: 'deployment "test-app-deployment" successfully rolled out'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('waitForRollout');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $project);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully rolled out', $result['output']);
    }

    #[Test]
    public function it_can_get_pod_status(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*get pods*' => Process::result(output: json_encode([
                'items' => [
                    [
                        'metadata' => ['name' => 'test-app-pod-1', 'creationTimestamp' => '2025-12-08T10:00:00Z'],
                        'status' => [
                            'phase' => 'Running',
                            'conditions' => [['type' => 'Ready', 'status' => 'True']],
                            'containerStatuses' => [['restartCount' => 2]],
                        ],
                        'spec' => ['nodeName' => 'worker-node-1'],
                    ],
                    [
                        'metadata' => ['name' => 'test-app-pod-2', 'creationTimestamp' => '2025-12-08T11:30:00Z'],
                        'status' => [
                            'phase' => 'Pending',
                            'conditions' => [['type' => 'Ready', 'status' => 'False']],
                            'containerStatuses' => [['restartCount' => 0]],
                        ],
                        'spec' => ['nodeName' => 'worker-node-2'],
                    ],
                ],
            ])),
        ]);

        $pods = $this->service->getPodStatus($project);

        $this->assertCount(2, $pods);
        $this->assertEquals('test-app-pod-1', $pods[0]['name']);
        $this->assertEquals('Running', $pods[0]['status']);
        $this->assertTrue($pods[0]['ready']);
        $this->assertEquals(2, $pods[0]['restarts']);
        $this->assertEquals('worker-node-1', $pods[0]['node']);
        $this->assertEquals('Pending', $pods[1]['status']);
        $this->assertFalse($pods[1]['ready']);
    }

    #[Test]
    public function it_returns_empty_array_when_get_pod_status_fails(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*get pods*' => Process::result(errorOutput: 'namespace not found', exitCode: 1),
        ]);

        $pods = $this->service->getPodStatus($project);

        $this->assertEmpty($pods);
    }

    #[Test]
    public function it_can_get_service_endpoints(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*get service*' => Process::result(output: json_encode([
                'metadata' => ['name' => 'test-app-service'],
                'status' => [
                    'loadBalancer' => [
                        'ingress' => [
                            ['ip' => '203.0.113.10'],
                            ['hostname' => 'lb.example.com'],
                        ],
                    ],
                ],
            ])),
            '*get ingress*' => Process::result(output: json_encode([
                'spec' => [
                    'rules' => [
                        ['host' => 'app.example.com'],
                        ['host' => 'api.example.com'],
                    ],
                ],
            ])),
        ]);

        $endpoints = $this->service->getServiceEndpoints($project);

        $this->assertContains('203.0.113.10', $endpoints);
        $this->assertContains('lb.example.com', $endpoints);
        $this->assertContains('https://app.example.com', $endpoints);
        $this->assertContains('https://api.example.com', $endpoints);
    }

    #[Test]
    public function it_can_scale_deployment(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*scale deployment*' => Process::result(output: 'deployment.apps/test-app-deployment scaled'),
        ]);

        $result = $this->service->scaleDeployment($project, 10);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['new_replicas']);
        $this->assertStringContainsString('scaled', $result['output']);
    }

    #[Test]
    public function it_can_handle_scale_deployment_failure(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*scale deployment*' => Process::result(errorOutput: 'deployment not found', exitCode: 1),
        ]);

        $result = $this->service->scaleDeployment($project, 5);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    #[Test]
    public function it_can_execute_command_in_pod(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*get pods*' => Process::result(output: json_encode([
                'items' => [[
                    'metadata' => ['name' => 'test-app-pod-abc123', 'creationTimestamp' => '2025-12-08T00:00:00Z'],
                    'status' => ['phase' => 'Running', 'conditions' => [['type' => 'Ready', 'status' => 'True']], 'containerStatuses' => [['restartCount' => 0]]],
                    'spec' => ['nodeName' => 'node-1'],
                ]],
            ])),
            '*exec*' => Process::result(output: 'Command executed successfully'),
        ]);

        $result = $this->service->executeInPod($project, 'ls -la');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('executed successfully', $result['output']);
        $this->assertEquals('test-app-pod-abc123', $result['pod']);
    }

    #[Test]
    public function it_can_execute_command_in_specific_pod(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*exec*' => Process::result(output: 'Migration completed'),
        ]);

        $result = $this->service->executeInPod($project, 'php artisan migrate', 'specific-pod-name');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Migration completed', $result['output']);
        $this->assertEquals('specific-pod-name', $result['pod']);
    }

    #[Test]
    public function it_throws_exception_when_no_running_pods_found(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*get pods*' => Process::result(output: json_encode([
                'items' => [[
                    'metadata' => ['name' => 'test-app-pod-abc123', 'creationTimestamp' => '2025-12-08T00:00:00Z'],
                    'status' => ['phase' => 'Pending', 'conditions' => [['type' => 'Ready', 'status' => 'False']]],
                    'spec' => ['nodeName' => 'node-1'],
                ]],
            ])),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No running pods found');

        $this->service->executeInPod($project, 'ls -la');
    }

    #[Test]
    public function it_can_get_deployment_logs(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*logs deployment*' => Process::result(output: "2025-12-08 10:00:00 [INFO] Application started\n2025-12-08 10:00:01 [INFO] Server listening on port 8000"),
        ]);

        $result = $this->service->getDeploymentLogs($project, 50);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Application started', $result['logs']);
        $this->assertStringContainsString('Server listening', $result['logs']);
    }

    #[Test]
    public function it_can_handle_get_deployment_logs_failure(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*logs deployment*' => Process::result(errorOutput: 'deployment not found', exitCode: 1),
        ]);

        $result = $this->service->getDeploymentLogs($project);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    #[Test]
    public function it_can_delete_kubernetes_resources(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*delete namespace*' => Process::result(output: 'namespace "test-app" deleted'),
        ]);

        $result = $this->service->deleteResources($project);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('deleted', $result['output']);
    }

    #[Test]
    public function it_can_handle_delete_resources_failure(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*delete namespace*' => Process::result(errorOutput: 'namespace not found', exitCode: 1),
        ]);

        $result = $this->service->deleteResources($project);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    #[Test]
    public function it_can_deploy_with_helm(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'current_version' => 'v1.2.3',
            'container_port' => 8080,
            'env_variables' => ['APP_ENV' => 'production'],
        ]);

        Process::fake([
            '*helm upgrade*' => Process::result(output: 'Release "test-app" has been upgraded.'),
        ]);

        $result = $this->service->deployWithHelm($project, ['replicas' => 5, 'enable_autoscaling' => true]);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-app', $result['release']);
        $this->assertStringContainsString('upgraded', $result['output']);
    }

    #[Test]
    public function it_can_generate_helm_values(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'current_version' => 'v2.0.0',
            'container_port' => 9000,
            'env_variables' => ['APP_ENV' => 'staging', 'LOG_LEVEL' => 'debug'],
        ]);

        $domain = Mockery::mock(Domain::class)->makePartial();
        $domain->full_domain = 'staging.example.com';
        $domainsCollection = new Collection([$domain]);
        $domainsRelation = Mockery::mock();
        $domainsRelation->shouldReceive('toArray')->andReturn([$domain]);
        $project->shouldReceive('domains')->andReturn($domainsRelation);
        $project->domains = $domainsCollection;

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateHelmValues');
        $method->setAccessible(true);

        $values = $method->invoke($this->service, $project, [
            'replicas' => 7,
            'service_type' => 'NodePort',
            'memory_limit' => '2Gi',
            'cpu_limit' => '2000m',
            'enable_autoscaling' => true,
            'min_replicas' => 5,
            'max_replicas' => 15,
        ]);

        $this->assertEquals(7, $values['replicaCount']);
        $this->assertEquals('v2.0.0', $values['image']['tag']);
        $this->assertEquals('NodePort', $values['service']['type']);
        $this->assertEquals(9000, $values['service']['targetPort']);
        $this->assertEquals('2Gi', $values['resources']['limits']['memory']);
        $this->assertTrue($values['autoscaling']['enabled']);
        $this->assertEquals(5, $values['autoscaling']['minReplicas']);
        $this->assertEquals(15, $values['autoscaling']['maxReplicas']);
        $this->assertEquals('staging', $values['env']['APP_ENV']);
    }

    #[Test]
    public function it_calculates_pod_age_correctly(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateAge');
        $method->setAccessible(true);

        $twoDaysAgo = (new \DateTime())->modify('-2 days')->format('Y-m-d\TH:i:s\Z');
        $this->assertEquals('2d', $method->invoke($this->service, $twoDaysAgo));

        $threeHoursAgo = (new \DateTime())->modify('-3 hours')->format('Y-m-d\TH:i:s\Z');
        $this->assertEquals('3h', $method->invoke($this->service, $threeHoursAgo));

        $tenMinutesAgo = (new \DateTime())->modify('-10 minutes')->format('Y-m-d\TH:i:s\Z');
        $this->assertEquals('10m', $method->invoke($this->service, $tenMinutesAgo));
    }

    #[Test]
    public function it_can_check_if_pod_is_ready(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isPodReady');
        $method->setAccessible(true);

        $readyPod = [
            'status' => [
                'conditions' => [
                    ['type' => 'Initialized', 'status' => 'True'],
                    ['type' => 'Ready', 'status' => 'True'],
                    ['type' => 'ContainersReady', 'status' => 'True'],
                ],
            ],
        ];

        $notReadyPod = [
            'status' => [
                'conditions' => [
                    ['type' => 'Initialized', 'status' => 'True'],
                    ['type' => 'Ready', 'status' => 'False'],
                ],
            ],
        ];

        $this->assertTrue($method->invoke($this->service, $readyPod));
        $this->assertFalse($method->invoke($this->service, $notReadyPod));
    }

    #[Test]
    public function it_can_build_docker_image(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'current_version' => 'v1.5.0',
            'latest_commit_hash' => 'abcd1234567890',
        ]);

        Process::fake([
            '*docker build*' => Process::result(output: 'Successfully built image'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildDockerImage');
        $method->setAccessible(true);

        $image = $method->invoke($this->service, $project);

        $this->assertStringContainsString('registry.example.com', $image);
        $this->assertStringContainsString('test-app', $image);
        $this->assertStringContainsString('v1.5.0', $image);
    }

    #[Test]
    public function it_uses_commit_hash_when_no_version_set(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'current_version' => null,
            'latest_commit_hash' => 'abcdef123456789',
        ]);

        Process::fake([
            '*docker build*' => Process::result(output: 'Successfully built'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildDockerImage');
        $method->setAccessible(true);

        $image = $method->invoke($this->service, $project);

        $this->assertStringContainsString('abcdef1', $image);
    }

    #[Test]
    public function it_can_get_secret_data(): void
    {
        $project = $this->mockProject([
            'db_password' => 'secret123',
            'app_key' => 'base64:key123',
            'api_secret' => 'api-secret-key',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getSecretData');
        $method->setAccessible(true);

        $secrets = $method->invoke($this->service, $project);

        $this->assertArrayHasKey('DB_PASSWORD', $secrets);
        $this->assertArrayHasKey('APP_KEY', $secrets);
        $this->assertArrayHasKey('API_SECRET', $secrets);
    }

    #[Test]
    public function it_runs_post_deployment_tasks_for_laravel_projects(): void
    {
        $project = $this->mockProject([
            'slug' => 'laravel-app',
            'framework' => 'laravel',
            'post_deployment_script' => 'php artisan optimize',
        ]);

        Process::fake([
            '*get pods*' => Process::result(output: json_encode([
                'items' => [[
                    'metadata' => ['name' => 'laravel-app-pod', 'creationTimestamp' => '2025-12-08T00:00:00Z'],
                    'status' => ['phase' => 'Running', 'conditions' => [['type' => 'Ready', 'status' => 'True']], 'containerStatuses' => [['restartCount' => 0]]],
                    'spec' => ['nodeName' => 'node-1'],
                ]],
            ])),
            '*exec*php artisan migrate*' => Process::result(output: 'Migration completed'),
            '*exec*php artisan cache:clear*' => Process::result(output: 'Cache cleared'),
            '*exec*php artisan optimize*' => Process::result(output: 'Optimization completed'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('runPostDeploymentTasks');
        $method->setAccessible(true);

        $method->invoke($this->service, $project);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_generates_ingress_manifest_with_multiple_domains(): void
    {
        $project = $this->mockProject(['slug' => 'multi-domain-app']);

        $domain1 = Mockery::mock(Domain::class)->makePartial();
        $domain1->id = 1;
        $domain1->full_domain = 'www.example.com';

        $domain2 = Mockery::mock(Domain::class)->makePartial();
        $domain2->id = 2;
        $domain2->full_domain = 'api.example.org';

        $domain3 = Mockery::mock(Domain::class)->makePartial();
        $domain3->id = 3;
        $domain3->full_domain = 'example.net';

        $domainsCollection = new Collection([$domain1, $domain2, $domain3]);
        $project->domains = $domainsCollection;

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateIngressManifest');
        $method->setAccessible(true);

        $manifest = $method->invoke($this->service, $project);

        $this->assertEquals('Ingress', $manifest['kind']);
        $this->assertCount(3, $manifest['spec']['rules']);
        $this->assertCount(3, $manifest['spec']['tls']);

        $hosts = array_column($manifest['spec']['rules'], 'host');
        $this->assertContains('www.example.com', $hosts);
        $this->assertContains('api.example.org', $hosts);
        $this->assertContains('example.net', $hosts);
    }

    #[Test]
    public function it_generates_correct_probe_configuration(): void
    {
        $project = $this->mockProject([
            'slug' => 'test-app',
            'container_port' => 3000,
        ]);

        Process::fake([
            '*docker build*' => Process::result(output: 'Built successfully'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateDeploymentManifest');
        $method->setAccessible(true);

        $manifest = $method->invoke($this->service, $project, []);

        $container = $manifest['spec']['template']['spec']['containers'][0];

        $this->assertEquals('/health', $container['livenessProbe']['httpGet']['path']);
        $this->assertEquals(3000, $container['livenessProbe']['httpGet']['port']);
        $this->assertEquals(30, $container['livenessProbe']['initialDelaySeconds']);
        $this->assertEquals(10, $container['livenessProbe']['periodSeconds']);

        $this->assertEquals('/ready', $container['readinessProbe']['httpGet']['path']);
        $this->assertEquals(3000, $container['readinessProbe']['httpGet']['port']);
        $this->assertEquals(5, $container['readinessProbe']['initialDelaySeconds']);
        $this->assertEquals(5, $container['readinessProbe']['periodSeconds']);
    }

    #[Test]
    public function it_generates_deployment_with_environment_references(): void
    {
        $project = $this->mockProject(['slug' => 'test-app']);

        Process::fake([
            '*docker build*' => Process::result(output: 'Built'),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateDeploymentManifest');
        $method->setAccessible(true);

        $manifest = $method->invoke($this->service, $project, []);

        $container = $manifest['spec']['template']['spec']['containers'][0];
        $envFrom = $container['envFrom'];

        $this->assertCount(2, $envFrom);
        $this->assertEquals('test-app-config', $envFrom[0]['configMapRef']['name']);
        $this->assertEquals('test-app-secret', $envFrom[1]['secretRef']['name']);
    }
}
