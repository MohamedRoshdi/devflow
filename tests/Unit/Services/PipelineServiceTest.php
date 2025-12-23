<?php

namespace Tests\Unit\Services;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use App\Services\CICD\PipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesProjects;

class PipelineServiceTest extends TestCase
{
    use CreatesProjects, RefreshDatabase;

    protected object $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock service that skips file operations
        $this->service = $this->getMockBuilder(PipelineService::class)
            ->onlyMethods(['createPipelineFile', 'setupWebhook'])
            ->getMock();

        // Setup default expectations for file operations (these methods return void)
        $this->service->method('createPipelineFile');
        $this->service->method('setupWebhook');
    }

    // ==========================================
    // PIPELINE CREATION TESTS
    // ==========================================

    #[Test]
    public function it_creates_pipeline_with_basic_configuration(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'name' => 'Test Pipeline',
            'provider' => 'github',
            'trigger_events' => ['push', 'pull_request'],
            'branch_filters' => ['main', 'develop'],
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertInstanceOf(Pipeline::class, $pipeline);
        $this->assertEquals('Test Pipeline', $pipeline->name);
        $this->assertEquals('github', $pipeline->provider);
        $this->assertEquals($project->id, $pipeline->project_id);
        $this->assertNotNull($pipeline->id);
        $this->assertIsArray($pipeline->configuration);
    }

    #[Test]
    public function it_creates_pipeline_with_default_values(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, []);

        // Assert
        $this->assertStringContainsString('Pipeline', $pipeline->name);
        $this->assertEquals('github', $pipeline->provider);
        $this->assertNotNull($pipeline->id);
    }

    #[Test]
    public function it_creates_pipeline_for_github_provider(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'name' => 'GitHub Pipeline',
            'provider' => 'github',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertEquals('github', $pipeline->provider);
        $this->assertIsArray($pipeline->configuration);
        $this->assertArrayHasKey('name', $pipeline->configuration);
        $this->assertArrayHasKey('on', $pipeline->configuration);
        $this->assertArrayHasKey('jobs', $pipeline->configuration);
    }

    #[Test]
    public function it_creates_pipeline_for_gitlab_provider(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'name' => 'GitLab Pipeline',
            'provider' => 'gitlab',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertEquals('gitlab', $pipeline->provider);
        $this->assertIsArray($pipeline->configuration);
        $this->assertArrayHasKey('stages', $pipeline->configuration);
    }

    #[Test]
    public function it_creates_pipeline_with_security_scan_enabled(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'enable_security_scan' => true,
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertArrayHasKey('security', $pipeline->configuration['jobs']);
        $securityJob = $pipeline->configuration['jobs']['security'];
        $this->assertIsArray($securityJob);
        $this->assertArrayHasKey('steps', $securityJob);
    }

    #[Test]
    public function it_creates_pipeline_with_quality_check_enabled(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'enable_quality_check' => true,
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertArrayHasKey('quality', $pipeline->configuration['jobs']);
        $qualityJob = $pipeline->configuration['jobs']['quality'];
        $this->assertIsArray($qualityJob);
        $this->assertArrayHasKey('steps', $qualityJob);
    }

    #[Test]
    public function it_creates_pipeline_with_custom_branch_filters(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'trigger_events' => ['push'],
            'branch_filters' => ['staging', 'production'],
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $triggers = $pipeline->configuration['on'];
        $this->assertArrayHasKey('push', $triggers);
        $this->assertEquals(['staging', 'production'], $triggers['push']['branches']);
    }

    #[Test]
    public function it_creates_pipeline_with_scheduled_triggers(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'trigger_events' => ['schedule'],
            'schedule' => '0 3 * * *',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $triggers = $pipeline->configuration['on'];
        $this->assertArrayHasKey('schedule', $triggers);
        $this->assertEquals('0 3 * * *', $triggers['schedule'][0]['cron']);
    }

    // ==========================================
    // PIPELINE EXECUTION TESTS
    // ==========================================

    #[Test]
    public function it_executes_pipeline_and_creates_pipeline_run(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
            '*' => Process::result(output: 'Success'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'manual');

        // Assert
        $this->assertInstanceOf(PipelineRun::class, $run);
        $this->assertEquals($pipeline->id, $run->pipeline_id);
        $this->assertEquals('manual', $run->trigger);
        $this->assertNotNull($run->started_at);
    }

    #[Test]
    public function it_executes_pipeline_with_github_provider(): void
    {
        // Arrange
        $project = $this->createLaravelProject([
            'repository_url' => 'https://github.com/owner/repo.git',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
        ]);

        Http::fake([
            'api.github.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'webhook');

        // Assert
        $this->assertEquals('webhook', $run->trigger);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.github.com');
        });
    }

    #[Test]
    public function it_executes_custom_pipeline_successfully(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'build',
                        'steps' => [
                            ['name' => 'Install dependencies', 'run' => 'composer install'],
                        ],
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
            '*composer install*' => Process::result(output: 'Dependencies installed'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'manual');

        // Assert
        $this->assertEquals('success', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->completed_at);
    }

    #[Test]
    public function it_marks_pipeline_run_as_failed_on_error(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'test',
                        'steps' => [
                            ['name' => 'Run tests', 'run' => 'vendor/bin/phpunit'],
                        ],
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
            '*phpunit*' => Process::result(
                output: '',
                errorOutput: 'Tests failed',
                exitCode: 1
            ),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'manual');

        // Assert
        $this->assertEquals('failed', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->error);
        $this->assertNotNull($run->fresh()->completed_at);
    }

    #[Test]
    public function it_executes_multiple_pipeline_stages_sequentially(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'build',
                        'steps' => [
                            ['name' => 'Build', 'run' => 'npm run build'],
                        ],
                    ],
                    [
                        'name' => 'test',
                        'steps' => [
                            ['name' => 'Test', 'run' => 'npm test'],
                        ],
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
            '*npm run build*' => Process::result(output: 'Build complete'),
            '*npm test*' => Process::result(output: 'Tests passed'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'manual');

        // Assert
        $this->assertEquals('success', $run->fresh()->status);
    }

    #[Test]
    public function it_stops_pipeline_execution_on_first_failure(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'stage1',
                        'steps' => [
                            ['name' => 'Step 1', 'run' => 'command1'],
                        ],
                    ],
                    [
                        'name' => 'stage2',
                        'steps' => [
                            ['name' => 'Step 2', 'run' => 'command2'],
                        ],
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456'),
            '*command1*' => Process::result(
                output: '',
                errorOutput: 'Command failed',
                exitCode: 1
            ),
            '*command2*' => Process::result(output: 'Should not run'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline, 'manual');

        // Assert
        $this->assertEquals('failed', $run->fresh()->status);
    }

    // ==========================================
    // PIPELINE CONFIGURATION TESTS
    // ==========================================

    #[Test]
    public function it_generates_github_actions_config_for_laravel_project(): void
    {
        // Arrange
        $project = $this->createLaravelProject([
            'framework' => 'laravel',
            'php_version' => '8.4',
        ]);
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $config = $pipeline->configuration;
        $this->assertArrayHasKey('jobs', $config);
        $this->assertArrayHasKey('test', $config['jobs']);
        $this->assertArrayHasKey('build', $config['jobs']);
        $this->assertArrayHasKey('deploy', $config['jobs']);
    }

    #[Test]
    public function it_generates_test_job_with_services(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $testJob = $pipeline->configuration['jobs']['test'];
        $this->assertArrayHasKey('steps', $testJob);
        $this->assertIsArray($testJob['steps']);
        $this->assertNotEmpty($testJob['steps']);
    }

    #[Test]
    public function it_generates_build_job_configuration(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $buildJob = $pipeline->configuration['jobs']['build'];
        $this->assertIsArray($buildJob);
        $this->assertArrayHasKey('steps', $buildJob);
        $this->assertArrayHasKey('needs', $buildJob);
        $this->assertEquals('test', $buildJob['needs']);
    }

    #[Test]
    public function it_generates_deploy_job_configuration(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $this->assertIsArray($deployJob);
        $this->assertArrayHasKey('steps', $deployJob);
        $this->assertArrayHasKey('needs', $deployJob);
        $this->assertEquals(['test', 'build'], $deployJob['needs']);
    }

    #[Test]
    public function it_generates_deployment_steps_for_kubernetes_strategy(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'deployment_strategy' => 'kubernetes',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $steps = $deployJob['steps'];
        $kubernetesStep = collect($steps)->first(function ($step) {
            return isset($step['name']) && str_contains($step['name'], 'Kubernetes');
        });
        $this->assertNotNull($kubernetesStep);
    }

    #[Test]
    public function it_generates_deployment_steps_for_docker_strategy(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'deployment_strategy' => 'docker',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $steps = $deployJob['steps'];
        $dockerStep = collect($steps)->first(function ($step) {
            return isset($step['name']) && str_contains($step['name'], 'SSH');
        });
        $this->assertNotNull($dockerStep);
    }

    #[Test]
    public function it_generates_deployment_steps_for_ssh_strategy(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'deployment_strategy' => 'ssh',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $steps = $deployJob['steps'];
        $sshStep = collect($steps)->first(function ($step) {
            return isset($step['uses']) && str_contains($step['uses'], 'ssh-action');
        });
        $this->assertNotNull($sshStep);
    }

    // ==========================================
    // GITLAB CI CONFIGURATION TESTS
    // ==========================================

    #[Test]
    public function it_generates_gitlab_ci_configuration(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = ['provider' => 'gitlab'];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $config = $pipeline->configuration;
        $this->assertArrayHasKey('stages', $config);
        $this->assertArrayHasKey('test', $config);
        $this->assertArrayHasKey('build', $config);
        $this->assertArrayHasKey('deploy', $config);
    }

    #[Test]
    public function it_generates_gitlab_test_stage(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'gitlab']);

        // Assert
        $testStage = $pipeline->configuration['test'];
        $this->assertIsArray($testStage);
        $this->assertArrayHasKey('stage', $testStage);
        $this->assertEquals('test', $testStage['stage']);
    }

    // ==========================================
    // TRIGGER CONFIGURATION TESTS
    // ==========================================

    #[Test]
    public function it_configures_push_trigger(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'trigger_events' => ['push'],
            'branch_filters' => ['main'],
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $triggers = $pipeline->configuration['on'];
        $this->assertArrayHasKey('push', $triggers);
        $this->assertEquals(['main'], $triggers['push']['branches']);
    }

    #[Test]
    public function it_configures_pull_request_trigger(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'trigger_events' => ['pull_request'],
            'branch_filters' => ['main', 'develop'],
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $triggers = $pipeline->configuration['on'];
        $this->assertArrayHasKey('pull_request', $triggers);
        $this->assertEquals(['main', 'develop'], $triggers['pull_request']['branches']);
    }

    #[Test]
    public function it_includes_workflow_dispatch_for_manual_triggering(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $triggers = $pipeline->configuration['on'];
        $this->assertArrayHasKey('workflow_dispatch', $triggers);
    }

    #[Test]
    public function it_ignores_documentation_files_in_push_trigger(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'provider' => 'github',
            'trigger_events' => ['push'],
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $pushTrigger = $pipeline->configuration['on']['push'];
        $this->assertArrayHasKey('paths-ignore', $pushTrigger);
        $this->assertContains('**.md', $pushTrigger['paths-ignore']);
    }

    // ==========================================
    // UTILITY METHOD TESTS
    // ==========================================

    #[Test]
    public function it_extracts_github_owner_from_https_url(): void
    {
        // Arrange
        $project = $this->createLaravelProject([
            'repository_url' => 'https://github.com/testowner/testrepo.git',
        ]);
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123'),
        ]);

        Http::fake([
            'api.github.com/repos/testowner/*' => Http::response(['status' => 'success'], 200),
        ]);

        // Act
        $this->service->executePipeline($pipeline);

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'testowner');
        });
    }

    #[Test]
    public function it_extracts_github_repo_from_https_url(): void
    {
        // Arrange
        $project = $this->createLaravelProject([
            'repository_url' => 'https://github.com/testowner/testrepo.git',
        ]);
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123'),
        ]);

        Http::fake([
            'api.github.com/repos/*/testrepo/*' => Http::response(['status' => 'success'], 200),
        ]);

        // Act
        $this->service->executePipeline($pipeline);

        // Assert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'testrepo');
        });
    }

    #[Test]
    public function it_gets_current_commit_hash(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
        ]);

        Process::fake([
            '*git rev-parse HEAD*' => Process::result(output: 'abc123def456789'),
        ]);

        // Act
        $run = $this->service->executePipeline($pipeline);

        // Assert
        $this->assertNotNull($run->commit_hash);
        $this->assertEquals('abc123def456789', trim($run->commit_hash));
    }

    // ==========================================
    // ARTIFACT HANDLING TESTS
    // ==========================================

    #[Test]
    public function it_creates_deployment_artifact_in_build_job(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $buildJob = $pipeline->configuration['jobs']['build'];
        $artifactStep = collect($buildJob['steps'])->first(function ($step) {
            return isset($step['name']) && str_contains($step['name'], 'artifact');
        });
        $this->assertNotNull($artifactStep);
    }

    #[Test]
    public function it_downloads_artifact_in_deploy_job(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $downloadStep = collect($deployJob['steps'])->first(function ($step) {
            return isset($step['name']) && str_contains($step['name'], 'Download');
        });
        $this->assertNotNull($downloadStep);
    }

    // ==========================================
    // PROVIDER SUPPORT TESTS
    // ==========================================

    #[Test]
    public function it_supports_github_provider(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $this->assertEquals('github', $pipeline->provider);
    }

    #[Test]
    public function it_supports_gitlab_provider(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'gitlab']);

        // Assert
        $this->assertEquals('gitlab', $pipeline->provider);
    }

    #[Test]
    public function it_verifies_supported_providers_list(): void
    {
        // Arrange & Act
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('supportedProviders');
        $property->setAccessible(true);
        $supportedProviders = $property->getValue($this->service);

        // Assert
        $this->assertIsArray($supportedProviders);
        $this->assertContains('github', $supportedProviders);
        $this->assertContains('gitlab', $supportedProviders);
        $this->assertContains('bitbucket', $supportedProviders);
        $this->assertContains('jenkins', $supportedProviders);
        $this->assertContains('custom', $supportedProviders);
    }

    #[Test]
    public function it_creates_pipeline_configuration_array(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $this->assertIsArray($pipeline->configuration);
        $this->assertNotEmpty($pipeline->configuration);
    }

    #[Test]
    public function it_stores_pipeline_database_record(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        $config = [
            'name' => 'Test Pipeline',
            'provider' => 'github',
        ];

        // Act
        $pipeline = $this->service->createPipeline($project, $config);

        // Assert
        $this->assertDatabaseHas('pipelines', [
            'id' => $pipeline->id,
            'project_id' => $project->id,
            'name' => 'Test Pipeline',
            'provider' => 'github',
        ]);
    }

    // ==========================================
    // NOTIFICATION TESTS
    // ==========================================

    #[Test]
    public function it_includes_notification_step_in_deploy_job(): void
    {
        // Arrange
        $project = $this->createLaravelProject();
        Process::fake(['*' => Process::result(output: 'Success')]);

        // Act
        $pipeline = $this->service->createPipeline($project, ['provider' => 'github']);

        // Assert
        $deployJob = $pipeline->configuration['jobs']['deploy'];
        $notificationStep = collect($deployJob['steps'])->last();
        $this->assertStringContainsString('Notify', $notificationStep['name']);
        $this->assertEquals('always()', $notificationStep['if']);
    }
}
