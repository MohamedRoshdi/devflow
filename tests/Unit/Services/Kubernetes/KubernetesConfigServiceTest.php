<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Kubernetes;

use App\Models\Project;
use App\Services\Kubernetes\KubernetesConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KubernetesConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    private KubernetesConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KubernetesConfigService();
    }

    public function test_generate_manifests_returns_all_required_resources(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
            'framework' => 'laravel',
        ]);

        $manifests = $this->service->generateManifests($project);

        $this->assertArrayHasKey('namespace', $manifests);
        $this->assertArrayHasKey('deployment', $manifests);
        $this->assertArrayHasKey('service', $manifests);
        $this->assertArrayHasKey('configmap', $manifests);
    }

    public function test_generate_namespace_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateNamespaceManifest($project);

        $this->assertEquals('v1', $manifest['apiVersion']);
        $this->assertEquals('Namespace', $manifest['kind']);
        $this->assertEquals('test-project', $manifest['metadata']['name']);
    }

    public function test_generate_deployment_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateDeploymentManifest($project);

        $this->assertEquals('apps/v1', $manifest['apiVersion']);
        $this->assertEquals('Deployment', $manifest['kind']);
        $this->assertEquals('test-project-deployment', $manifest['metadata']['name']);
        $this->assertEquals('test-project', $manifest['metadata']['namespace']);
    }

    public function test_generate_deployment_manifest_with_custom_replicas(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateDeploymentManifest($project, [
            'replicas' => 5,
        ]);

        $this->assertEquals(5, $manifest['spec']['replicas']);
    }

    public function test_generate_deployment_manifest_with_resources(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateDeploymentManifest($project, [
            'resources' => [
                'limits' => [
                    'cpu' => '2',
                    'memory' => '2Gi',
                ],
                'requests' => [
                    'cpu' => '500m',
                    'memory' => '512Mi',
                ],
            ],
        ]);

        $container = $manifest['spec']['template']['spec']['containers'][0];
        $this->assertEquals('2', $container['resources']['limits']['cpu']);
        $this->assertEquals('2Gi', $container['resources']['limits']['memory']);
    }

    public function test_generate_service_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateServiceManifest($project);

        $this->assertEquals('v1', $manifest['apiVersion']);
        $this->assertEquals('Service', $manifest['kind']);
        $this->assertEquals('test-project-service', $manifest['metadata']['name']);
        $this->assertArrayHasKey('ports', $manifest['spec']);
    }

    public function test_generate_ingress_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateIngressManifest($project, [
            'host' => 'test.example.com',
        ]);

        $this->assertEquals('networking.k8s.io/v1', $manifest['apiVersion']);
        $this->assertEquals('Ingress', $manifest['kind']);
        $this->assertEquals('test-project-ingress', $manifest['metadata']['name']);
        $this->assertEquals('test.example.com', $manifest['spec']['rules'][0]['host']);
    }

    public function test_generate_ingress_manifest_with_tls(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateIngressManifest($project, [
            'host' => 'test.example.com',
            'enable_tls' => true,
        ]);

        $this->assertArrayHasKey('tls', $manifest['spec']);
        $this->assertEquals('test.example.com', $manifest['spec']['tls'][0]['hosts'][0]);
    }

    public function test_generate_configmap_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateConfigMapManifest($project, [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);

        $this->assertEquals('v1', $manifest['apiVersion']);
        $this->assertEquals('ConfigMap', $manifest['kind']);
        $this->assertEquals('test-project-config', $manifest['metadata']['name']);
        $this->assertEquals('production', $manifest['data']['APP_ENV']);
    }

    public function test_generate_secret_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateSecretManifest($project, [
            'DB_PASSWORD' => 'secret123',
            'APP_KEY' => 'base64:abc123',
        ]);

        $this->assertEquals('v1', $manifest['apiVersion']);
        $this->assertEquals('Secret', $manifest['kind']);
        $this->assertEquals('Opaque', $manifest['type']);
        // Secrets should be base64 encoded
        $this->assertEquals(base64_encode('secret123'), $manifest['data']['DB_PASSWORD']);
    }

    public function test_generate_hpa_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateHPAManifest($project, [
            'min_replicas' => 2,
            'max_replicas' => 10,
            'target_cpu' => 80,
        ]);

        $this->assertEquals('autoscaling/v2', $manifest['apiVersion']);
        $this->assertEquals('HorizontalPodAutoscaler', $manifest['kind']);
        $this->assertEquals(2, $manifest['spec']['minReplicas']);
        $this->assertEquals(10, $manifest['spec']['maxReplicas']);
    }

    public function test_generate_pdb_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generatePDBManifest($project, [
            'min_available' => 1,
        ]);

        $this->assertEquals('policy/v1', $manifest['apiVersion']);
        $this->assertEquals('PodDisruptionBudget', $manifest['kind']);
        $this->assertEquals(1, $manifest['spec']['minAvailable']);
    }

    public function test_generate_helm_values(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
            'php_version' => '8.3',
        ]);

        $values = $this->service->generateHelmValues($project);

        $this->assertArrayHasKey('replicaCount', $values);
        $this->assertArrayHasKey('image', $values);
        $this->assertArrayHasKey('service', $values);
        $this->assertArrayHasKey('ingress', $values);
        $this->assertArrayHasKey('resources', $values);
    }

    public function test_generate_helm_values_with_options(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $values = $this->service->generateHelmValues($project, [
            'replicas' => 5,
            'enable_ingress' => true,
            'host' => 'test.example.com',
        ]);

        $this->assertEquals(5, $values['replicaCount']);
        $this->assertTrue($values['ingress']['enabled']);
        $this->assertEquals('test.example.com', $values['ingress']['hosts'][0]['host']);
    }

    public function test_generate_helm_chart_creates_directory_structure(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $chartPath = $this->service->generateHelmChart($project);

        $this->assertDirectoryExists($chartPath);
        $this->assertFileExists($chartPath . '/Chart.yaml');
        $this->assertFileExists($chartPath . '/values.yaml');
        $this->assertDirectoryExists($chartPath . '/templates');
    }

    public function test_generate_network_policy_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateNetworkPolicyManifest($project);

        $this->assertEquals('networking.k8s.io/v1', $manifest['apiVersion']);
        $this->assertEquals('NetworkPolicy', $manifest['kind']);
        $this->assertEquals('test-project-network-policy', $manifest['metadata']['name']);
    }

    public function test_generate_service_account_manifest(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifest = $this->service->generateServiceAccountManifest($project);

        $this->assertEquals('v1', $manifest['apiVersion']);
        $this->assertEquals('ServiceAccount', $manifest['kind']);
        $this->assertEquals('test-project-sa', $manifest['metadata']['name']);
    }

    public function test_generate_deployment_includes_health_checks(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
            'health_check_url' => '/health',
        ]);

        $manifest = $this->service->generateDeploymentManifest($project);

        $container = $manifest['spec']['template']['spec']['containers'][0];
        $this->assertArrayHasKey('livenessProbe', $container);
        $this->assertArrayHasKey('readinessProbe', $container);
    }

    public function test_generate_manifests_includes_ingress_when_domain_provided(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifests = $this->service->generateManifests($project, [
            'enable_ingress' => true,
            'host' => 'test.example.com',
        ]);

        $this->assertArrayHasKey('ingress', $manifests);
    }

    public function test_generate_manifests_includes_hpa_when_autoscaling_enabled(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $manifests = $this->service->generateManifests($project, [
            'enable_autoscaling' => true,
            'min_replicas' => 2,
            'max_replicas' => 10,
        ]);

        $this->assertArrayHasKey('hpa', $manifests);
    }

    public function test_get_kubectl_path(): void
    {
        $path = $this->service->getKubectlPath();

        $this->assertEquals('/usr/local/bin/kubectl', $path);
    }
}
