<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Kubernetes;

use App\Models\DockerRegistry;
use App\Models\Project;
use App\Services\Kubernetes\KubernetesRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class KubernetesRegistryServiceTest extends TestCase
{
    use RefreshDatabase;

    private KubernetesRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KubernetesRegistryService();
    }

    public function test_create_docker_registry_secrets_for_all_registries(): void
    {
        Process::fake([
            '*delete secret*' => Process::result(output: 'deleted'),
            '*create secret*' => Process::result(output: 'created'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $registry1 = DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $registry2 = DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $results = $this->service->createDockerRegistrySecrets($project);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
    }

    public function test_create_registry_secret_deletes_existing_and_creates_new(): void
    {
        Process::fake([
            '*delete secret*' => Process::result(output: 'secret deleted'),
            '*create secret*' => Process::result(output: 'secret/test-registry-secret created'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $registry = DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'name' => 'test-registry',
            'registry_url' => 'docker.io',
            'username' => 'testuser',
            'credentials' => ['password' => 'testpass'],
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $result = $this->service->createRegistrySecret($project, $registry);

        $this->assertTrue($result['success']);
        $this->assertEquals($registry->id, $result['registry_id']);

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'delete secret');
        });

        Process::assertRan(function ($process) {
            return str_contains($process->command, 'create secret docker-registry');
        });
    }

    public function test_create_registry_secret_throws_when_not_configured(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $registry = DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'registry_url' => null,
            'username' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not properly configured');

        $this->service->createRegistrySecret($project, $registry);
    }

    public function test_build_docker_config_json(): void
    {
        $registry = DockerRegistry::factory()->create([
            'registry_url' => 'ghcr.io',
            'username' => 'testuser',
            'credentials' => ['password' => 'testpass'],
            'email' => 'test@example.com',
        ]);

        $config = $this->service->buildDockerConfigJson($registry);

        $this->assertArrayHasKey('auths', $config);
        $this->assertArrayHasKey('ghcr.io', $config['auths']);
        $this->assertEquals('testuser', $config['auths']['ghcr.io']['username']);
        $this->assertEquals('test@example.com', $config['auths']['ghcr.io']['email']);
        $this->assertArrayHasKey('auth', $config['auths']['ghcr.io']);
    }

    public function test_get_image_pull_secrets(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'name' => 'docker-hub',
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'name' => 'github-packages',
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $secrets = $this->service->getImagePullSecrets($project);

        $this->assertCount(2, $secrets);
        $this->assertArrayHasKey('name', $secrets[0]);
        $this->assertArrayHasKey('name', $secrets[1]);
    }

    public function test_store_docker_registry_credentials(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $registry = $this->service->storeDockerRegistryCredentials($project, [
            'name' => 'My Docker Hub',
            'registry_type' => DockerRegistry::TYPE_DOCKER_HUB,
            'registry_url' => 'docker.io',
            'username' => 'myuser',
            'password' => 'mypassword',
            'email' => 'me@example.com',
            'is_default' => true,
        ]);

        $this->assertInstanceOf(DockerRegistry::class, $registry);
        $this->assertEquals('My Docker Hub', $registry->name);
        $this->assertEquals('docker.io', $registry->registry_url);
        $this->assertEquals('myuser', $registry->username);
        $this->assertTrue($registry->is_default);
    }

    public function test_store_docker_registry_credentials_validates_required_fields(): void
    {
        $project = Project::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Registry name is required');

        $this->service->storeDockerRegistryCredentials($project, [
            'registry_type' => DockerRegistry::TYPE_DOCKER_HUB,
        ]);
    }

    public function test_store_docker_registry_credentials_validates_registry_type(): void
    {
        $project = Project::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid registry type');

        $this->service->storeDockerRegistryCredentials($project, [
            'name' => 'Test',
            'registry_type' => 'invalid-type',
            'username' => 'user',
        ]);
    }

    public function test_get_docker_registry_credentials(): void
    {
        $project = Project::factory()->create();

        DockerRegistry::factory()->count(3)->create([
            'project_id' => $project->id,
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'status' => DockerRegistry::STATUS_FAILED,
        ]);

        $registries = $this->service->getDockerRegistryCredentials($project);

        $this->assertCount(3, $registries);
    }

    public function test_get_default_docker_registry(): void
    {
        $project = Project::factory()->create();

        DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'is_default' => false,
        ]);

        $defaultRegistry = DockerRegistry::factory()->create([
            'project_id' => $project->id,
            'is_default' => true,
        ]);

        $result = $this->service->getDefaultDockerRegistry($project);

        $this->assertEquals($defaultRegistry->id, $result->id);
    }

    public function test_update_docker_registry_credentials(): void
    {
        $registry = DockerRegistry::factory()->create([
            'name' => 'Old Name',
            'registry_url' => 'docker.io',
            'username' => 'olduser',
        ]);

        $updated = $this->service->updateDockerRegistryCredentials($registry, [
            'name' => 'New Name',
            'username' => 'newuser',
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('newuser', $updated->username);
    }

    public function test_delete_docker_registry_credentials(): void
    {
        Process::fake([
            '*delete secret*' => Process::result(output: 'deleted'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $registry = DockerRegistry::factory()->create([
            'project_id' => $project->id,
        ]);

        $result = $this->service->deleteDockerRegistryCredentials($registry);

        $this->assertTrue($result);
        $this->assertNull(DockerRegistry::find($registry->id));
    }

    public function test_test_docker_registry_connection_success(): void
    {
        Process::fake([
            '*docker login*' => Process::result(output: 'Login Succeeded'),
            '*docker logout*' => Process::result(output: 'Logout succeeded'),
        ]);

        $registry = DockerRegistry::factory()->create([
            'registry_url' => 'docker.io',
            'username' => 'testuser',
            'credentials' => ['password' => 'testpass'],
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $result = $this->service->testDockerRegistryConnection($registry);

        $this->assertTrue($result['success']);
        $this->assertEquals('Successfully connected to registry', $result['message']);
    }

    public function test_test_docker_registry_connection_failure(): void
    {
        Process::fake([
            '*docker login*' => Process::result(exitCode: 1, errorOutput: 'unauthorized'),
        ]);

        $registry = DockerRegistry::factory()->create([
            'registry_url' => 'docker.io',
            'username' => 'testuser',
            'credentials' => ['password' => 'wrongpass'],
            'status' => DockerRegistry::STATUS_ACTIVE,
        ]);

        $result = $this->service->testDockerRegistryConnection($registry);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to connect', $result['message']);

        $registry->refresh();
        $this->assertEquals(DockerRegistry::STATUS_FAILED, $registry->status);
    }

    public function test_extract_credentials_for_docker_hub(): void
    {
        $project = Project::factory()->create();

        $registry = $this->service->storeDockerRegistryCredentials($project, [
            'name' => 'Docker Hub',
            'registry_type' => DockerRegistry::TYPE_DOCKER_HUB,
            'username' => 'user',
            'password' => 'pass123',
        ]);

        $this->assertArrayHasKey('password', $registry->credentials);
        $this->assertEquals('pass123', $registry->credentials['password']);
    }

    public function test_extract_credentials_for_github(): void
    {
        $project = Project::factory()->create();

        $registry = $this->service->storeDockerRegistryCredentials($project, [
            'name' => 'GitHub Packages',
            'registry_type' => DockerRegistry::TYPE_GITHUB,
            'username' => 'user',
            'token' => 'ghp_token123',
        ]);

        $this->assertArrayHasKey('token', $registry->credentials);
        $this->assertEquals('ghp_token123', $registry->credentials['token']);
    }

    public function test_extract_credentials_for_aws_ecr(): void
    {
        $project = Project::factory()->create();

        $registry = $this->service->storeDockerRegistryCredentials($project, [
            'name' => 'AWS ECR',
            'registry_type' => DockerRegistry::TYPE_AWS_ECR,
            'username' => 'AWS',
            'aws_access_key_id' => 'AKIA...',
            'aws_secret_access_key' => 'secret...',
            'region' => 'us-west-2',
        ]);

        $this->assertArrayHasKey('aws_access_key_id', $registry->credentials);
        $this->assertArrayHasKey('aws_secret_access_key', $registry->credentials);
        $this->assertEquals('us-west-2', $registry->credentials['region']);
    }

    public function test_get_kubectl_path(): void
    {
        $path = $this->service->getKubectlPath();

        $this->assertEquals('/usr/local/bin/kubectl', $path);
    }
}
