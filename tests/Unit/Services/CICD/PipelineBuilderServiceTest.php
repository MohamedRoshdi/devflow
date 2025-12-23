<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CICD;

use App\Models\Project;
use App\Services\CICD\PipelineBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    private PipelineBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PipelineBuilderService();
    }

    public function test_generate_pipeline_config_for_github(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generatePipelineConfig($project, [
            'provider' => 'github',
            'trigger_events' => ['push', 'pull_request'],
        ]);

        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('on', $config);
        $this->assertArrayHasKey('jobs', $config);
        $this->assertEquals('DevFlow Pro CI/CD', $config['name']);
    }

    public function test_generate_pipeline_config_for_gitlab(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generatePipelineConfig($project, [
            'provider' => 'gitlab',
        ]);

        $this->assertArrayHasKey('stages', $config);
        $this->assertContains('test', $config['stages']);
        $this->assertContains('build', $config['stages']);
        $this->assertContains('deploy', $config['stages']);
    }

    public function test_generate_pipeline_config_for_bitbucket(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generatePipelineConfig($project, [
            'provider' => 'bitbucket',
        ]);

        $this->assertArrayHasKey('pipelines', $config);
        $this->assertArrayHasKey('branches', $config['pipelines']);
    }

    public function test_generate_pipeline_config_for_jenkins(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generatePipelineConfig($project, [
            'provider' => 'jenkins',
        ]);

        $this->assertArrayHasKey('pipeline', $config);
        $this->assertArrayHasKey('jenkinsfile_content', $config);
        $this->assertArrayHasKey('stages', $config['pipeline']);
    }

    public function test_generate_github_actions_config_includes_test_job(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, []);

        $this->assertArrayHasKey('test', $config['jobs']);
        $this->assertEquals('ubuntu-latest', $config['jobs']['test']['runs-on']);
    }

    public function test_generate_github_actions_config_includes_build_job(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, []);

        $this->assertArrayHasKey('build', $config['jobs']);
        $this->assertEquals(['test'], $config['jobs']['build']['needs']);
    }

    public function test_generate_github_actions_config_includes_deploy_job(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, []);

        $this->assertArrayHasKey('deploy', $config['jobs']);
        $this->assertEquals(['test', 'build'], $config['jobs']['deploy']['needs']);
    }

    public function test_generate_github_actions_config_includes_security_job_when_enabled(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, [
            'enable_security_scan' => true,
        ]);

        $this->assertArrayHasKey('security', $config['jobs']);
    }

    public function test_generate_github_actions_config_excludes_security_job_when_disabled(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, [
            'enable_security_scan' => false,
        ]);

        $this->assertArrayNotHasKey('security', $config['jobs']);
    }

    public function test_generate_github_actions_config_includes_quality_job_when_enabled(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateGitHubActionsConfig($project, [
            'enable_quality_check' => true,
        ]);

        $this->assertArrayHasKey('quality', $config['jobs']);
    }

    public function test_generate_test_job_includes_mysql_service_when_database_used(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'uses_database' => true,
        ]);

        $job = $this->service->generateTestJob($project);

        $this->assertArrayHasKey('mysql', $job['services']);
        $this->assertEquals('mysql:8.0', $job['services']['mysql']['image']);
    }

    public function test_generate_test_job_includes_redis_service_when_redis_used(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'uses_redis' => true,
        ]);

        $job = $this->service->generateTestJob($project);

        $this->assertArrayHasKey('redis', $job['services']);
        $this->assertEquals('redis:alpine', $job['services']['redis']['image']);
    }

    public function test_generate_deploy_job_for_kubernetes_strategy(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $job = $this->service->generateDeployJob($project, [
            'deployment_strategy' => 'kubernetes',
        ]);

        // Check that kubernetes-specific steps are included
        $stepNames = array_column($job['steps'], 'name');
        $this->assertContains('Configure kubectl', $stepNames);
        $this->assertContains('Deploy to Kubernetes', $stepNames);
    }

    public function test_generate_deploy_job_for_docker_strategy(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $job = $this->service->generateDeployJob($project, [
            'deployment_strategy' => 'docker',
        ]);

        $stepNames = array_column($job['steps'], 'name');
        $this->assertContains('Deploy via SSH', $stepNames);
    }

    public function test_generate_deploy_job_for_ssh_strategy(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $job = $this->service->generateDeployJob($project, [
            'deployment_strategy' => 'ssh',
        ]);

        $stepNames = array_column($job['steps'], 'name');
        $this->assertContains('Deploy via SSH', $stepNames);
    }

    public function test_generate_gitlab_ci_config_structure(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generateGitLabCIConfig($project, []);

        $this->assertArrayHasKey('stages', $config);
        $this->assertArrayHasKey('variables', $config);
        $this->assertArrayHasKey('test', $config);
        $this->assertArrayHasKey('build', $config);
        $this->assertArrayHasKey('deploy', $config);
    }

    public function test_generate_bitbucket_pipelines_config_structure(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'php_version' => '8.3',
        ]);

        $config = $this->service->generateBitbucketPipelinesConfig($project, []);

        $this->assertArrayHasKey('image', $config);
        $this->assertArrayHasKey('definitions', $config);
        $this->assertArrayHasKey('pipelines', $config);
    }

    public function test_generate_custom_config_structure(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $config = $this->service->generateCustomConfig($project, [
            'name' => 'Custom Pipeline',
        ]);

        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('stages', $config);
        $this->assertArrayHasKey('notifications', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('retry', $config);
    }

    public function test_generate_security_job_includes_trivy_scanner(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $job = $this->service->generateSecurityJob($project);

        $stepNames = array_column($job['steps'], 'name');
        $this->assertContains('Run Trivy vulnerability scanner', $stepNames);
    }

    public function test_generate_quality_job_includes_phpstan(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
        ]);

        $job = $this->service->generateQualityJob($project);

        $stepNames = array_column($job['steps'], 'name');
        $this->assertContains('Run PHPStan', $stepNames);
    }
}
