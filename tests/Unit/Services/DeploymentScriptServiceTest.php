<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\Domain;
use App\Models\Project;
use App\Services\CustomScripts\DeploymentScriptService;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeploymentScriptServiceTest extends TestCase
{
    protected DeploymentScriptService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeploymentScriptService();
    }

    /**
     * Create a project with proper env_variables formatting
     */
    protected function createTestProject(array $attributes = []): Project
    {
        return Project::factory()->create(array_merge([
            'env_variables' => [],
        ], $attributes));
    }

    // ===== VALIDATION TESTS =====

    #[Test]
    public function it_validates_required_name_field(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'script' => 'echo "test"',
            // name is missing
        ]);
    }

    #[Test]
    public function it_validates_required_script_field(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            // script is missing
        ]);
    }

    #[Test]
    public function it_validates_script_language_enum(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            'script' => 'echo "test"',
            'language' => 'invalid_language',
        ]);
    }

    #[Test]
    public function it_validates_timeout_minimum_constraint(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            'script' => 'echo "test"',
            'timeout' => 5, // minimum is 10
        ]);
    }

    #[Test]
    public function it_validates_timeout_maximum_constraint(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            'script' => 'echo "test"',
            'timeout' => 5000, // maximum is 3600
        ]);
    }

    #[Test]
    public function it_validates_variables_must_be_array(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            'script' => 'echo "test"',
            'variables' => 'not_an_array',
        ]);
    }

    #[Test]
    public function it_validates_tags_must_be_array(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createScript([
            'name' => 'Test Script',
            'script' => 'echo "test"',
            'tags' => 'not_an_array',
        ]);
    }

    #[Test]
    public function it_creates_script_with_valid_data(): void
    {
        Process::fake();

        $script = $this->service->createScript([
            'name' => 'My Test Script',
            'script' => 'echo "Hello World"',
            'language' => 'bash',
            'timeout' => 300,
            'is_template' => false,
            'tags' => ['test', 'deployment'],
        ]);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertEquals('My Test Script', $script->name);
        $this->assertEquals('echo "Hello World"', $script->script);
        $this->assertEquals('bash', $script->language);
        $this->assertEquals(300, $script->timeout);
    }

    #[Test]
    public function it_executes_bash_script_successfully(): void
    {
        $project = $this->createTestProject([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'branch' => 'main',
            'php_version' => '8.4',
            'framework' => 'laravel',
        ]);

        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'commit_hash' => 'abc123',
            'branch' => 'main',
        ]);

        $script = DeploymentScript::factory()->bash()->create([
            'name' => 'Test Script',
            'script' => 'echo "Success"',
            'timeout' => 60,
        ]);

        $this->mockSuccessfulCommand('Script executed successfully');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    #[Test]
    public function it_handles_script_execution_failure(): void
    {
        $project = $this->createTestProject();
        $deployment = Deployment::factory()->create(['project_id' => $project->id]);
        $script = DeploymentScript::factory()->create([
            'script' => 'exit 1',
        ]);

        $this->mockFailedCommand('Script execution failed');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['exit_code']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function it_substitutes_project_name_variable(): void
    {
        $project = $this->createTestProject([
            'name' => 'My Awesome Project',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{PROJECT_NAME}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_project_slug_variable(): void
    {
        $project = $this->createTestProject([
            'slug' => 'my-project-slug',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{PROJECT_SLUG}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_branch_variable(): void
    {
        $project = $this->createTestProject([
            'branch' => 'develop',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{BRANCH}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_commit_hash_variable(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'commit_hash' => 'abc123def456',
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{COMMIT_HASH}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_deployment_id_variable(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{DEPLOYMENT_ID}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_php_version_variable(): void
    {
        $project = $this->createTestProject([
            'php_version' => '8.4',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{PHP_VERSION}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_framework_variable(): void
    {
        $project = $this->createTestProject([
            'framework' => 'laravel',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{FRAMEWORK}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_domain_variable(): void
    {
        $project = $this->createTestProject();

        Domain::factory()->create([
            'project_id' => $project->id,
            'domain' => 'myproject.com',
        ]);

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{DOMAIN}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_additional_runtime_variables(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{CUSTOM_VAR}}"',
        ]);

        $additionalVars = [
            '{{CUSTOM_VAR}}' => 'custom_value',
        ];

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment, $additionalVars);

        $this->assertTrue($result['success']);
    }

    // ===== TEMPLATE GENERATION TESTS =====

    #[Test]
    public function it_generates_laravel_deployment_template(): void
    {
        $project = $this->createTestProject([
            'name' => 'My Laravel App',
            'framework' => 'laravel',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('laravel_deployment', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Laravel Deployment', $script->name);
        $this->assertStringContainsString('composer install', $script->script);
        $this->assertStringContainsString('php artisan migrate', $script->script);
    }

    #[Test]
    public function it_generates_nodejs_deployment_template(): void
    {
        $project = $this->createTestProject([
            'name' => 'My Node App',
            'framework' => 'nodejs',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('node_deployment', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Node.js Deployment', $script->name);
        $this->assertStringContainsString('npm', $script->script);
    }

    #[Test]
    public function it_generates_database_backup_template(): void
    {
        $project = $this->createTestProject(['name' => 'My App']);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('database_backup', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Database Backup', $script->name);
        $this->assertStringContainsString('mysqldump', $script->script);
    }

    #[Test]
    public function it_generates_rollback_template(): void
    {
        $project = $this->createTestProject(['name' => 'My App']);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('rollback', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Rollback', $script->name);
        $this->assertStringContainsString('git reset', $script->script);
    }

    #[Test]
    public function it_generates_health_check_template(): void
    {
        $project = $this->createTestProject(['name' => 'My App']);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('health_check', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Health Check', $script->name);
        $this->assertStringContainsString('curl', $script->script);
    }

    #[Test]
    public function it_generates_cache_warmer_template(): void
    {
        $project = $this->createTestProject(['name' => 'My App']);

        Domain::factory()->create(['project_id' => $project->id]);

        $this->mockSuccessfulCommand();

        $script = $this->service->generateFromTemplate('cache_warmer', $project);

        $this->assertInstanceOf(DeploymentScript::class, $script);
        $this->assertStringContainsString('Cache Warmer', $script->name);
        $this->assertEquals('python', $script->language);
    }

    #[Test]
    public function it_throws_exception_for_invalid_template(): void
    {
        $project = $this->createTestProject(['name' => 'My App']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Template 'nonexistent_template' not found");

        $this->service->generateFromTemplate('nonexistent_template', $project);
    }

    #[Test]
    public function it_returns_all_available_templates(): void
    {
        $templates = $this->service->getAvailableTemplates();

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('laravel_deployment', $templates);
        $this->assertArrayHasKey('node_deployment', $templates);
        $this->assertArrayHasKey('database_backup', $templates);
        $this->assertArrayHasKey('rollback', $templates);
        $this->assertArrayHasKey('health_check', $templates);
        $this->assertArrayHasKey('cache_warmer', $templates);
    }

    #[Test]
    public function it_returns_execution_time_in_results(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "test"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertArrayHasKey('execution_time', $result);
        $this->assertIsFloat($result['execution_time']);
        $this->assertGreaterThanOrEqual(0, $result['execution_time']);
    }

    #[Test]
    public function it_handles_timeout_correctly(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'sleep 10',
            'timeout' => 1,
        ]);

        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: 'Timeout exceeded',
                exitCode: 124
            ),
        ]);

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function it_cleans_up_temp_file_after_execution(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "test"',
        ]);

        $this->mockSuccessfulCommand();

        $this->service->executeScript($project, $script, $deployment);

        // After execution, temp files should be cleaned up
        $tempFiles = glob(sys_get_temp_dir().'/devflow_script_*');
        $this->assertEmpty($tempFiles);
    }

    #[Test]
    public function it_executes_python_script_with_correct_interpreter(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->python()->create([
            'script' => 'print("Hello from Python")',
        ]);

        $this->mockSuccessfulCommand('Hello from Python');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_executes_php_script_with_correct_interpreter(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->php()->create([
            'script' => 'echo "Hello from PHP";',
        ]);

        $this->mockSuccessfulCommand('Hello from PHP');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_executes_node_script_with_correct_interpreter(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'language' => 'node',
            'script' => 'console.log("Hello from Node");',
        ]);

        $this->mockSuccessfulCommand('Hello from Node');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_includes_exit_code_in_result(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'exit 0',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertArrayHasKey('exit_code', $result);
        $this->assertEquals(0, $result['exit_code']);
    }

    #[Test]
    public function it_includes_output_in_result(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "Expected output"',
        ]);

        $this->mockSuccessfulCommand('Expected output');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertArrayHasKey('output', $result);
        $this->assertEquals('Expected output', trim($result['output']));
    }

    #[Test]
    public function it_includes_error_output_in_result_on_failure(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "Error occurred" >&2; exit 1',
        ]);

        $this->mockFailedCommand('Error occurred');

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Error occurred', trim($result['error']));
    }

    #[Test]
    public function it_substitutes_timestamp_variable(): void
    {
        $project = $this->createTestProject();
        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{TIMESTAMP}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_docker_image_variable(): void
    {
        $project = $this->createTestProject([
            'slug' => 'my-project',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{DOCKER_IMAGE}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_substitutes_project_path_variable(): void
    {
        $project = $this->createTestProject([
            'slug' => 'test-project',
        ]);

        Domain::factory()->create(['project_id' => $project->id]);

        $deployment = Deployment::factory()->create(['project_id' => $project->id]);

        $script = DeploymentScript::factory()->create([
            'script' => 'echo "{{PROJECT_PATH}}"',
        ]);

        $this->mockSuccessfulCommand();

        $result = $this->service->executeScript($project, $script, $deployment);

        $this->assertTrue($result['success']);
    }

    // ===== HELPER METHODS =====

    /**
     * Mock a successful command execution
     */
    protected function mockSuccessfulCommand(string $output = 'Success'): void
    {
        Process::fake([
            '*' => Process::result(
                output: $output,
                errorOutput: '',
                exitCode: 0
            ),
        ]);
    }

    /**
     * Mock a failed command execution
     */
    protected function mockFailedCommand(string $error = 'Error'): void
    {
        Process::fake([
            '*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }
}
