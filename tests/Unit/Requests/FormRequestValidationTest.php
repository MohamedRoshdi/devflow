<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\StoreServerRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Requests\Api\UpdateServerRequest;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;


class FormRequestValidationTest extends TestCase
{
    

    protected function validate(array $data, string $requestClass, ?int $routeId = null): \Illuminate\Validation\Validator
    {
        $request = new $requestClass();

        // Mock route parameter for Update requests
        if ($routeId !== null) {
            if (str_contains($requestClass, 'UpdateProject')) {
                $project = new \stdClass();
                $project->id = $routeId;
                $request->setRouteResolver(function () use ($project) {
                    $route = \Mockery::mock(\Illuminate\Routing\Route::class);
                    $route->shouldReceive('parameter')
                        ->with('project', \Mockery::any())
                        ->andReturn($project);
                    return $route;
                });
            } elseif (str_contains($requestClass, 'UpdateServer')) {
                $server = new \stdClass();
                $server->id = $routeId;
                $request->setRouteResolver(function () use ($server) {
                    $route = \Mockery::mock(\Illuminate\Routing\Route::class);
                    $route->shouldReceive('parameter')
                        ->with('server', \Mockery::any())
                        ->andReturn($server);
                    return $route;
                });
            }
        }

        return Validator::make($data, $request->rules());
    }

    // ================================
    // StoreProjectRequest Tests
    // ================================

    public function test_store_project_requires_name(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_project_name_must_be_string(): void
    {
        $validator = $this->validate(['name' => 123], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_project_name_max_length_255(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_project_name_accepts_valid_string(): void
    {
        $validator = $this->validate([
            'name' => 'Valid Project Name',
            'slug' => 'valid-project-slug',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('name'));
    }

    public function test_store_project_requires_slug(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    public function test_store_project_slug_must_match_regex_pattern(): void
    {
        $validator = $this->validate([
            'slug' => 'Invalid_Slug',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    public function test_store_project_slug_accepts_valid_format(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'valid-slug-123',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('slug'));
    }

    public function test_store_project_slug_max_length_255(): void
    {
        $validator = $this->validate([
            'slug' => str_repeat('a', 256),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    public function test_store_project_requires_repository_url(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('repository_url', $validator->errors()->toArray());
    }

    public function test_store_project_repository_url_must_be_valid_url(): void
    {
        $validator = $this->validate([
            'repository_url' => 'not-a-valid-url',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('repository_url', $validator->errors()->toArray());
    }

    public function test_store_project_repository_url_accepts_valid_url(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('repository_url'));
    }

    public function test_store_project_repository_url_max_length_500(): void
    {
        $validator = $this->validate([
            'repository_url' => 'https://github.com/' . str_repeat('a', 500),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('repository_url', $validator->errors()->toArray());
    }

    public function test_store_project_requires_branch(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('branch', $validator->errors()->toArray());
    }

    public function test_store_project_branch_max_length_100(): void
    {
        $validator = $this->validate([
            'branch' => str_repeat('a', 101),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('branch', $validator->errors()->toArray());
    }

    public function test_store_project_requires_framework(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('framework', $validator->errors()->toArray());
    }

    public function test_store_project_framework_must_be_in_allowed_values(): void
    {
        $validator = $this->validate([
            'framework' => 'invalid-framework',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('framework', $validator->errors()->toArray());
    }

    public function test_store_project_framework_accepts_laravel(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('framework'));
    }

    public function test_store_project_framework_accepts_all_valid_values(): void
    {
        $validFrameworks = ['laravel', 'shopware', 'symfony', 'wordpress', 'nextjs', 'vue', 'react', 'custom'];

        foreach ($validFrameworks as $framework) {
            $validator = $this->validate([
                'name' => 'Project',
                'slug' => 'project',
                'repository_url' => 'https://github.com/user/repo',
                'branch' => 'main',
                'framework' => $framework,
                'project_type' => 'single_tenant',
                'server_id' => 1,
            ], StoreProjectRequest::class);
            $this->assertFalse($validator->errors()->has('framework'), "Framework {$framework} should be valid");
        }
    }

    public function test_store_project_requires_project_type(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_type', $validator->errors()->toArray());
    }

    public function test_store_project_type_must_be_in_allowed_values(): void
    {
        $validator = $this->validate([
            'project_type' => 'invalid-type',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_type', $validator->errors()->toArray());
    }

    public function test_store_project_type_accepts_all_valid_values(): void
    {
        $validTypes = ['single_tenant', 'multi_tenant', 'saas', 'microservice'];

        foreach ($validTypes as $type) {
            $validator = $this->validate([
                'name' => 'Project',
                'slug' => 'project',
                'repository_url' => 'https://github.com/user/repo',
                'branch' => 'main',
                'framework' => 'laravel',
                'project_type' => $type,
                'server_id' => 1,
            ], StoreProjectRequest::class);
            $this->assertFalse($validator->errors()->has('project_type'), "Project type {$type} should be valid");
        }
    }

    public function test_store_project_environment_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('environment'));
    }

    public function test_store_project_environment_accepts_valid_values(): void
    {
        $validEnvironments = ['production', 'staging', 'development'];

        foreach ($validEnvironments as $env) {
            $validator = $this->validate([
                'name' => 'Project',
                'slug' => 'project',
                'repository_url' => 'https://github.com/user/repo',
                'branch' => 'main',
                'framework' => 'laravel',
                'project_type' => 'single_tenant',
                'environment' => $env,
                'server_id' => 1,
            ], StoreProjectRequest::class);
            $this->assertFalse($validator->errors()->has('environment'), "Environment {$env} should be valid");
        }
    }

    public function test_store_project_environment_rejects_invalid_value(): void
    {
        $validator = $this->validate([
            'environment' => 'invalid-env',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('environment', $validator->errors()->toArray());
    }

    public function test_store_project_php_version_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('php_version'));
    }

    public function test_store_project_php_version_max_length_10(): void
    {
        $validator = $this->validate([
            'php_version' => str_repeat('8', 11),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('php_version', $validator->errors()->toArray());
    }

    public function test_store_project_node_version_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('node_version'));
    }

    public function test_store_project_node_version_max_length_10(): void
    {
        $validator = $this->validate([
            'node_version' => str_repeat('1', 11),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('node_version', $validator->errors()->toArray());
    }

    public function test_store_project_port_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_store_project_port_must_be_integer(): void
    {
        $validator = $this->validate([
            'port' => 'not-a-number',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_project_port_min_value_1(): void
    {
        $validator = $this->validate([
            'port' => 0,
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_project_port_max_value_65535(): void
    {
        $validator = $this->validate([
            'port' => 65536,
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_project_port_accepts_valid_value(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'port' => 8080,
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_store_project_root_directory_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('root_directory'));
    }

    public function test_store_project_root_directory_max_length_500(): void
    {
        $validator = $this->validate([
            'root_directory' => '/' . str_repeat('a', 500),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('root_directory', $validator->errors()->toArray());
    }

    public function test_store_project_build_command_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('build_command'));
    }

    public function test_store_project_build_command_max_length_1000(): void
    {
        $validator = $this->validate([
            'build_command' => str_repeat('a', 1001),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('build_command', $validator->errors()->toArray());
    }

    public function test_store_project_start_command_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('start_command'));
    }

    public function test_store_project_start_command_max_length_1000(): void
    {
        $validator = $this->validate([
            'start_command' => str_repeat('a', 1001),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_command', $validator->errors()->toArray());
    }

    public function test_store_project_install_commands_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('install_commands'));
    }

    public function test_store_project_install_commands_accepts_array_of_strings(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'install_commands' => ['npm install', 'composer install'],
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('install_commands'));
    }

    public function test_store_project_install_commands_item_max_length_1000(): void
    {
        $validator = $this->validate([
            'install_commands' => [str_repeat('a', 1001)],
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('install_commands.0', $validator->errors()->toArray());
    }

    public function test_store_project_build_commands_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('build_commands'));
    }

    public function test_store_project_build_commands_item_max_length_1000(): void
    {
        $validator = $this->validate([
            'build_commands' => [str_repeat('a', 1001)],
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('build_commands.0', $validator->errors()->toArray());
    }

    public function test_store_project_post_deploy_commands_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('post_deploy_commands'));
    }

    public function test_store_project_post_deploy_commands_item_max_length_1000(): void
    {
        $validator = $this->validate([
            'post_deploy_commands' => [str_repeat('a', 1001)],
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('post_deploy_commands.0', $validator->errors()->toArray());
    }

    public function test_store_project_env_variables_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('env_variables'));
    }

    public function test_store_project_env_variables_accepts_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'env_variables' => ['APP_ENV' => 'production', 'APP_DEBUG' => 'false'],
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('env_variables'));
    }

    public function test_store_project_env_variables_item_max_length_5000(): void
    {
        $validator = $this->validate([
            'env_variables' => ['KEY' => str_repeat('a', 5001)],
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('env_variables.KEY', $validator->errors()->toArray());
    }

    public function test_store_project_health_check_url_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('health_check_url'));
    }

    public function test_store_project_health_check_url_must_be_valid_url(): void
    {
        $validator = $this->validate([
            'health_check_url' => 'not-a-url',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('health_check_url', $validator->errors()->toArray());
    }

    public function test_store_project_health_check_url_max_length_500(): void
    {
        $validator = $this->validate([
            'health_check_url' => 'https://example.com/' . str_repeat('a', 500),
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('health_check_url', $validator->errors()->toArray());
    }

    public function test_store_project_auto_deploy_is_optional_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('auto_deploy'));
    }

    public function test_store_project_auto_deploy_accepts_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'auto_deploy' => true,
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('auto_deploy'));
    }

    public function test_store_project_webhook_enabled_is_optional_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('webhook_enabled'));
    }

    public function test_store_project_webhook_enabled_accepts_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'webhook_enabled' => false,
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('webhook_enabled'));
    }

    public function test_store_project_requires_server_id(): void
    {
        $validator = $this->validate([], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('server_id', $validator->errors()->toArray());
    }

    public function test_store_project_server_id_must_be_integer(): void
    {
        $validator = $this->validate([
            'server_id' => 'not-a-number',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('server_id', $validator->errors()->toArray());
    }

    public function test_store_project_template_id_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('template_id'));
    }

    public function test_store_project_template_id_must_be_integer(): void
    {
        $validator = $this->validate([
            'template_id' => 'not-a-number',
        ], StoreProjectRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('template_id', $validator->errors()->toArray());
    }

    public function test_store_project_metadata_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_store_project_metadata_accepts_array(): void
    {
        $validator = $this->validate([
            'name' => 'Project',
            'slug' => 'project',
            'repository_url' => 'https://github.com/user/repo',
            'branch' => 'main',
            'framework' => 'laravel',
            'project_type' => 'single_tenant',
            'metadata' => ['key' => 'value'],
            'server_id' => 1,
        ], StoreProjectRequest::class);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_store_project_request_authorize_returns_true(): void
    {
        $request = new StoreProjectRequest();
        $this->assertTrue($request->authorize());
    }

    // ================================
    // StoreServerRequest Tests
    // ================================

    public function test_store_server_requires_name(): void
    {
        $validator = $this->validate([], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_server_name_must_be_string(): void
    {
        $validator = $this->validate(['name' => 123], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_server_name_max_length_255(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_store_server_requires_hostname(): void
    {
        $validator = $this->validate([], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hostname', $validator->errors()->toArray());
    }

    public function test_store_server_hostname_max_length_255(): void
    {
        $validator = $this->validate([
            'hostname' => str_repeat('a', 256),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hostname', $validator->errors()->toArray());
    }

    public function test_store_server_requires_ip_address(): void
    {
        $validator = $this->validate([], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ip_address', $validator->errors()->toArray());
    }

    public function test_store_server_ip_address_must_be_valid_ip(): void
    {
        $validator = $this->validate([
            'ip_address' => 'not-an-ip',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ip_address', $validator->errors()->toArray());
    }

    public function test_store_server_ip_address_accepts_ipv4(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('ip_address'));
    }

    public function test_store_server_ip_address_accepts_ipv6(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('ip_address'));
    }

    public function test_store_server_port_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_store_server_port_must_be_integer(): void
    {
        $validator = $this->validate([
            'port' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_server_port_min_value_1(): void
    {
        $validator = $this->validate([
            'port' => 0,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_server_port_max_value_65535(): void
    {
        $validator = $this->validate([
            'port' => 65536,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_store_server_port_accepts_valid_value(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'port' => 22,
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_store_server_requires_username(): void
    {
        $validator = $this->validate([], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('username', $validator->errors()->toArray());
    }

    public function test_store_server_username_max_length_100(): void
    {
        $validator = $this->validate([
            'username' => str_repeat('a', 101),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('username', $validator->errors()->toArray());
    }

    public function test_store_server_ssh_key_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('ssh_key'));
    }

    public function test_store_server_ssh_key_accepts_string(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
            'ssh_key' => 'ssh-rsa AAAAB3...',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('ssh_key'));
    }

    public function test_store_server_ssh_password_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('ssh_password'));
    }

    public function test_store_server_ssh_password_max_length_500(): void
    {
        $validator = $this->validate([
            'ssh_password' => str_repeat('a', 501),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ssh_password', $validator->errors()->toArray());
    }

    public function test_store_server_os_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('os'));
    }

    public function test_store_server_os_max_length_100(): void
    {
        $validator = $this->validate([
            'os' => str_repeat('a', 101),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('os', $validator->errors()->toArray());
    }

    public function test_store_server_cpu_cores_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('cpu_cores'));
    }

    public function test_store_server_cpu_cores_must_be_integer(): void
    {
        $validator = $this->validate([
            'cpu_cores' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpu_cores', $validator->errors()->toArray());
    }

    public function test_store_server_cpu_cores_min_value_1(): void
    {
        $validator = $this->validate([
            'cpu_cores' => 0,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpu_cores', $validator->errors()->toArray());
    }

    public function test_store_server_memory_gb_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('memory_gb'));
    }

    public function test_store_server_memory_gb_must_be_integer(): void
    {
        $validator = $this->validate([
            'memory_gb' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('memory_gb', $validator->errors()->toArray());
    }

    public function test_store_server_memory_gb_min_value_1(): void
    {
        $validator = $this->validate([
            'memory_gb' => 0,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('memory_gb', $validator->errors()->toArray());
    }

    public function test_store_server_disk_gb_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('disk_gb'));
    }

    public function test_store_server_disk_gb_must_be_integer(): void
    {
        $validator = $this->validate([
            'disk_gb' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('disk_gb', $validator->errors()->toArray());
    }

    public function test_store_server_disk_gb_min_value_1(): void
    {
        $validator = $this->validate([
            'disk_gb' => 0,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('disk_gb', $validator->errors()->toArray());
    }

    public function test_store_server_docker_installed_is_optional_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('docker_installed'));
    }

    public function test_store_server_docker_installed_accepts_boolean(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
            'docker_installed' => true,
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('docker_installed'));
    }

    public function test_store_server_docker_version_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('docker_version'));
    }

    public function test_store_server_docker_version_max_length_50(): void
    {
        $validator = $this->validate([
            'docker_version' => str_repeat('a', 51),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('docker_version', $validator->errors()->toArray());
    }

    public function test_store_server_location_name_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('location_name'));
    }

    public function test_store_server_location_name_max_length_255(): void
    {
        $validator = $this->validate([
            'location_name' => str_repeat('a', 256),
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('location_name', $validator->errors()->toArray());
    }

    public function test_store_server_latitude_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('latitude'));
    }

    public function test_store_server_latitude_must_be_numeric(): void
    {
        $validator = $this->validate([
            'latitude' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());
    }

    public function test_store_server_latitude_between_negative_90_and_90(): void
    {
        $validator = $this->validate([
            'latitude' => 91,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());

        $validator = $this->validate([
            'latitude' => -91,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());
    }

    public function test_store_server_latitude_accepts_valid_value(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
            'latitude' => 40.7128,
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('latitude'));
    }

    public function test_store_server_longitude_is_optional(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('longitude'));
    }

    public function test_store_server_longitude_must_be_numeric(): void
    {
        $validator = $this->validate([
            'longitude' => 'not-a-number',
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitude', $validator->errors()->toArray());
    }

    public function test_store_server_longitude_between_negative_180_and_180(): void
    {
        $validator = $this->validate([
            'longitude' => 181,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitude', $validator->errors()->toArray());

        $validator = $this->validate([
            'longitude' => -181,
        ], StoreServerRequest::class);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitude', $validator->errors()->toArray());
    }

    public function test_store_server_longitude_accepts_valid_value(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
            'longitude' => -74.0060,
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('longitude'));
    }

    public function test_store_server_metadata_is_optional_array(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_store_server_metadata_accepts_array(): void
    {
        $validator = $this->validate([
            'name' => 'Server',
            'hostname' => 'server.example.com',
            'ip_address' => '192.168.1.1',
            'username' => 'root',
            'metadata' => ['key' => 'value'],
        ], StoreServerRequest::class);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_store_server_request_authorize_returns_true(): void
    {
        $request = new StoreServerRequest();
        $this->assertTrue($request->authorize());
    }

    // ================================
    // UpdateProjectRequest Tests
    // ================================

    public function test_update_project_name_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('name'));
    }

    public function test_update_project_name_must_be_string(): void
    {
        $validator = $this->validate(['name' => 123], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_update_project_name_max_length_255(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_update_project_slug_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('slug'));
    }

    public function test_update_project_slug_must_match_regex_pattern(): void
    {
        $validator = $this->validate([
            'slug' => 'Invalid_Slug',
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    public function test_update_project_repository_url_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('repository_url'));
    }

    public function test_update_project_repository_url_must_be_valid_url(): void
    {
        $validator = $this->validate([
            'repository_url' => 'not-a-valid-url',
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('repository_url', $validator->errors()->toArray());
    }

    public function test_update_project_branch_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('branch'));
    }

    public function test_update_project_branch_max_length_100(): void
    {
        $validator = $this->validate([
            'branch' => str_repeat('a', 101),
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('branch', $validator->errors()->toArray());
    }

    public function test_update_project_framework_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('framework'));
    }

    public function test_update_project_framework_must_be_in_allowed_values(): void
    {
        $validator = $this->validate([
            'framework' => 'invalid-framework',
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('framework', $validator->errors()->toArray());
    }

    public function test_update_project_framework_accepts_all_valid_values(): void
    {
        $validFrameworks = ['laravel', 'shopware', 'symfony', 'wordpress', 'nextjs', 'vue', 'react', 'custom'];

        foreach ($validFrameworks as $framework) {
            $validator = $this->validate([
                'framework' => $framework,
            ], UpdateProjectRequest::class, 1);
            $this->assertFalse($validator->errors()->has('framework'), "Framework {$framework} should be valid");
        }
    }

    public function test_update_project_project_type_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('project_type'));
    }

    public function test_update_project_type_accepts_all_valid_values(): void
    {
        $validTypes = ['single_tenant', 'multi_tenant', 'saas', 'microservice'];

        foreach ($validTypes as $type) {
            $validator = $this->validate([
                'project_type' => $type,
            ], UpdateProjectRequest::class, 1);
            $this->assertFalse($validator->errors()->has('project_type'), "Project type {$type} should be valid");
        }
    }

    public function test_update_project_environment_accepts_valid_values(): void
    {
        $validEnvironments = ['production', 'staging', 'development'];

        foreach ($validEnvironments as $env) {
            $validator = $this->validate([
                'environment' => $env,
            ], UpdateProjectRequest::class, 1);
            $this->assertFalse($validator->errors()->has('environment'), "Environment {$env} should be valid");
        }
    }

    public function test_update_project_status_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('status'));
    }

    public function test_update_project_status_accepts_all_valid_values(): void
    {
        $validStatuses = ['running', 'stopped', 'building', 'error'];

        foreach ($validStatuses as $status) {
            $validator = $this->validate([
                'status' => $status,
            ], UpdateProjectRequest::class, 1);
            $this->assertFalse($validator->errors()->has('status'), "Status {$status} should be valid");
        }
    }

    public function test_update_project_status_rejects_invalid_value(): void
    {
        $validator = $this->validate([
            'status' => 'invalid-status',
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_update_project_port_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_update_project_port_min_value_1(): void
    {
        $validator = $this->validate([
            'port' => 0,
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_update_project_port_max_value_65535(): void
    {
        $validator = $this->validate([
            'port' => 65536,
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_update_project_auto_deploy_accepts_boolean(): void
    {
        $validator = $this->validate([
            'auto_deploy' => false,
        ], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('auto_deploy'));
    }

    public function test_update_project_webhook_enabled_accepts_boolean(): void
    {
        $validator = $this->validate([
            'webhook_enabled' => true,
        ], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('webhook_enabled'));
    }

    public function test_update_project_server_id_is_optional(): void
    {
        $validator = $this->validate([], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('server_id'));
    }

    public function test_update_project_server_id_must_be_integer(): void
    {
        $validator = $this->validate([
            'server_id' => 'not-a-number',
        ], UpdateProjectRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('server_id', $validator->errors()->toArray());
    }

    public function test_update_project_metadata_accepts_array(): void
    {
        $validator = $this->validate([
            'metadata' => ['key' => 'value'],
        ], UpdateProjectRequest::class, 1);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_update_project_request_authorize_returns_true(): void
    {
        $request = new UpdateProjectRequest();
        $this->assertTrue($request->authorize());
    }

    // ================================
    // UpdateServerRequest Tests
    // ================================

    public function test_update_server_name_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('name'));
    }

    public function test_update_server_name_must_be_string(): void
    {
        $validator = $this->validate(['name' => 123], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_update_server_name_max_length_255(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 256),
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_update_server_hostname_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('hostname'));
    }

    public function test_update_server_hostname_max_length_255(): void
    {
        $validator = $this->validate([
            'hostname' => str_repeat('a', 256),
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('hostname', $validator->errors()->toArray());
    }

    public function test_update_server_ip_address_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('ip_address'));
    }

    public function test_update_server_ip_address_must_be_valid_ip(): void
    {
        $validator = $this->validate([
            'ip_address' => 'not-an-ip',
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ip_address', $validator->errors()->toArray());
    }

    public function test_update_server_ip_address_accepts_ipv4(): void
    {
        $validator = $this->validate([
            'ip_address' => '192.168.1.100',
        ], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('ip_address'));
    }

    public function test_update_server_port_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('port'));
    }

    public function test_update_server_port_min_value_1(): void
    {
        $validator = $this->validate([
            'port' => 0,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_update_server_port_max_value_65535(): void
    {
        $validator = $this->validate([
            'port' => 65536,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('port', $validator->errors()->toArray());
    }

    public function test_update_server_username_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('username'));
    }

    public function test_update_server_username_max_length_100(): void
    {
        $validator = $this->validate([
            'username' => str_repeat('a', 101),
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('username', $validator->errors()->toArray());
    }

    public function test_update_server_status_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('status'));
    }

    public function test_update_server_status_accepts_all_valid_values(): void
    {
        $validStatuses = ['online', 'offline', 'maintenance'];

        foreach ($validStatuses as $status) {
            $validator = $this->validate([
                'status' => $status,
            ], UpdateServerRequest::class, 1);
            $this->assertFalse($validator->errors()->has('status'), "Status {$status} should be valid");
        }
    }

    public function test_update_server_status_rejects_invalid_value(): void
    {
        $validator = $this->validate([
            'status' => 'invalid-status',
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_update_server_os_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('os'));
    }

    public function test_update_server_os_max_length_100(): void
    {
        $validator = $this->validate([
            'os' => str_repeat('a', 101),
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('os', $validator->errors()->toArray());
    }

    public function test_update_server_cpu_cores_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('cpu_cores'));
    }

    public function test_update_server_cpu_cores_must_be_integer(): void
    {
        $validator = $this->validate([
            'cpu_cores' => 'not-a-number',
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpu_cores', $validator->errors()->toArray());
    }

    public function test_update_server_cpu_cores_min_value_1(): void
    {
        $validator = $this->validate([
            'cpu_cores' => 0,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cpu_cores', $validator->errors()->toArray());
    }

    public function test_update_server_memory_gb_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('memory_gb'));
    }

    public function test_update_server_memory_gb_min_value_1(): void
    {
        $validator = $this->validate([
            'memory_gb' => 0,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('memory_gb', $validator->errors()->toArray());
    }

    public function test_update_server_disk_gb_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('disk_gb'));
    }

    public function test_update_server_disk_gb_min_value_1(): void
    {
        $validator = $this->validate([
            'disk_gb' => 0,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('disk_gb', $validator->errors()->toArray());
    }

    public function test_update_server_docker_installed_accepts_boolean(): void
    {
        $validator = $this->validate([
            'docker_installed' => true,
        ], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('docker_installed'));
    }

    public function test_update_server_docker_version_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('docker_version'));
    }

    public function test_update_server_docker_version_max_length_50(): void
    {
        $validator = $this->validate([
            'docker_version' => str_repeat('a', 51),
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('docker_version', $validator->errors()->toArray());
    }

    public function test_update_server_latitude_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('latitude'));
    }

    public function test_update_server_latitude_between_negative_90_and_90(): void
    {
        $validator = $this->validate([
            'latitude' => 91,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->toArray());
    }

    public function test_update_server_longitude_is_optional(): void
    {
        $validator = $this->validate([], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('longitude'));
    }

    public function test_update_server_longitude_between_negative_180_and_180(): void
    {
        $validator = $this->validate([
            'longitude' => 181,
        ], UpdateServerRequest::class, 1);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitude', $validator->errors()->toArray());
    }

    public function test_update_server_metadata_accepts_array(): void
    {
        $validator = $this->validate([
            'metadata' => ['key' => 'value'],
        ], UpdateServerRequest::class, 1);
        $this->assertFalse($validator->errors()->has('metadata'));
    }

    public function test_update_server_request_authorize_returns_true(): void
    {
        $request = new UpdateServerRequest();
        $this->assertTrue($request->authorize());
    }
}
