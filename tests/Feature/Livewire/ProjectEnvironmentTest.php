<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\ProjectEnvironment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProjectEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Server $server;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['status' => 'online']);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'environment' => 'production',
            'env_variables' => ['APP_NAME' => 'TestApp', 'DB_HOST' => 'localhost'],
        ]);
    }

    private function mockProcessSuccess(string $output = 'SUCCESS'): void
    {
        $this->mock(Process::class, function ($mock) use ($output): void {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturn(0);
            $mock->shouldReceive('isSuccessful')->andReturn(true);
            $mock->shouldReceive('getOutput')->andReturn($output);
            $mock->shouldReceive('getErrorOutput')->andReturn('');
        });
    }

    private function mockProcessFailure(string $error = 'Connection failed'): void
    {
        $this->mock(Process::class, function ($mock) use ($error): void {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturn(1);
            $mock->shouldReceive('isSuccessful')->andReturn(false);
            $mock->shouldReceive('getOutput')->andReturn('');
            $mock->shouldReceive('getErrorOutput')->andReturn($error);
        });
    }

    private function mockEnvFileContent(): void
    {
        $envContent = "APP_NAME=TestApp\nAPP_ENV=production\nAPP_DEBUG=false\nDB_HOST=localhost\nDB_DATABASE=test_db";
        $this->mockProcessSuccess($envContent);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_id_on_mount(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);
    }

    public function test_component_loads_environment_on_mount(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('environment', 'production');
    }

    public function test_component_loads_env_variables_on_mount(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('envVariables', ['APP_NAME' => 'TestApp', 'DB_HOST' => 'localhost']);
    }

    public function test_component_loads_server_env_on_mount(): void
    {
        $this->mockEnvFileContent();

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $serverEnv = $component->get('serverEnvVariables');
        $this->assertIsArray($serverEnv);
    }

    public function test_loads_default_environment_when_null(): void
    {
        $this->project->update(['environment' => null]);
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('environment', 'production');
    }

    public function test_loads_empty_env_variables_when_null(): void
    {
        $this->project->update(['env_variables' => null]);
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('envVariables', []);
    }

    // ==================== ENVIRONMENT UPDATE TESTS ====================

    public function test_can_update_environment_to_production(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('updateEnvironment', 'production')
            ->assertSet('environment', 'production')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('production', $freshProject->environment);
    }

    public function test_can_update_environment_to_staging(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('updateEnvironment', 'staging')
            ->assertSet('environment', 'staging')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('staging', $freshProject->environment);
    }

    public function test_can_update_environment_to_development(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('updateEnvironment', 'development')
            ->assertSet('environment', 'development')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('development', $freshProject->environment);
    }

    public function test_can_update_environment_to_local(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('updateEnvironment', 'local')
            ->assertSet('environment', 'local')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $this->assertEquals('local', $freshProject->environment);
    }

    public function test_validates_invalid_environment(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('environment', 'invalid')
            ->call('updateEnvironment')
            ->assertHasErrors(['environment']);
    }

    public function test_dispatches_environment_updated_event(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('updateEnvironment', 'staging')
            ->assertDispatched('environmentUpdated');
    }

    public function test_update_uses_provided_environment(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('environment', 'local')
            ->call('updateEnvironment', 'staging')
            ->assertSet('environment', 'staging');
    }

    // ==================== LOCAL ENV VARIABLE MODAL TESTS ====================

    public function test_can_open_env_modal(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('showEnvModal', false)
            ->call('openEnvModal')
            ->assertSet('showEnvModal', true)
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '')
            ->assertSet('editingEnvKey', null);
    }

    public function test_can_close_env_modal(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('openEnvModal')
            ->assertSet('showEnvModal', true)
            ->call('closeEnvModal')
            ->assertSet('showEnvModal', false);
    }

    // ==================== LOCAL ENV VARIABLE CRUD TESTS ====================

    public function test_can_add_env_variable(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'NEW_VAR')
            ->set('newEnvValue', 'new_value')
            ->call('addEnvVariable')
            ->assertSessionHas('message', 'Environment variable added successfully');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertArrayHasKey('NEW_VAR', $envVars);
        $this->assertEquals('new_value', $envVars['NEW_VAR']);
    }

    public function test_add_env_variable_requires_key(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', '')
            ->set('newEnvValue', 'value')
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvKey']);
    }

    public function test_add_env_variable_clears_form(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'NEW_VAR')
            ->set('newEnvValue', 'new_value')
            ->call('addEnvVariable')
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    public function test_can_edit_env_variable(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('editEnvVariable', 'APP_NAME')
            ->assertSet('editingEnvKey', 'APP_NAME')
            ->assertSet('newEnvKey', 'APP_NAME')
            ->assertSet('newEnvValue', 'TestApp')
            ->assertSet('showEnvModal', true);
    }

    public function test_can_update_env_variable(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('editEnvVariable', 'APP_NAME')
            ->set('newEnvValue', 'UpdatedApp')
            ->call('updateEnvVariable')
            ->assertSet('showEnvModal', false)
            ->assertSessionHas('message', 'Environment variable updated successfully');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertEquals('UpdatedApp', $envVars['APP_NAME']);
    }

    public function test_can_rename_env_variable_key(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('editEnvVariable', 'APP_NAME')
            ->set('newEnvKey', 'APP_TITLE')
            ->set('newEnvValue', 'TestApp')
            ->call('updateEnvVariable');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertArrayNotHasKey('APP_NAME', $envVars);
        $this->assertArrayHasKey('APP_TITLE', $envVars);
    }

    public function test_can_delete_env_variable(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteEnvVariable', 'DB_HOST')
            ->assertSessionHas('message', 'Environment variable deleted successfully');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertArrayNotHasKey('DB_HOST', $envVars);
    }

    // ==================== SERVER ENV LOAD TESTS ====================

    public function test_server_env_loading_state(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('serverEnvLoading', false);
    }

    public function test_server_env_handles_no_server(): void
    {
        $this->project->update(['server_id' => null]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('serverEnvError', 'No server configured for this project');
    }

    public function test_server_env_handles_env_not_found(): void
    {
        $this->mockProcessSuccess('__ENV_NOT_FOUND__');

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('serverEnvError', fn ($error) => str_contains($error ?? '', '.env file'));
    }

    public function test_server_env_handles_connection_error(): void
    {
        $this->mockProcessFailure('Connection refused');

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('serverEnvError', fn ($error) => str_contains($error ?? '', 'Failed to connect'));
    }

    public function test_can_refresh_server_env(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('loadServerEnv')
            ->assertSet('serverEnvLoading', false);
    }

    // ==================== SERVER ENV MODAL TESTS ====================

    public function test_can_open_server_env_modal(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('showServerEnvModal', false)
            ->call('openServerEnvModal')
            ->assertSet('showServerEnvModal', true)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '')
            ->assertSet('editingServerEnvKey', null);
    }

    public function test_can_close_server_env_modal(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('openServerEnvModal')
            ->assertSet('showServerEnvModal', true)
            ->call('closeServerEnvModal')
            ->assertSet('showServerEnvModal', false)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '')
            ->assertSet('editingServerEnvKey', null);
    }

    public function test_can_edit_server_env_variable(): void
    {
        $envContent = "APP_NAME=TestApp\nAPP_ENV=production";
        $this->mockProcessSuccess($envContent);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        // Set server env variables manually for test
        $component->set('serverEnvVariables', ['APP_NAME' => 'TestApp', 'APP_ENV' => 'production']);

        $component->call('editServerEnvVariable', 'APP_NAME')
            ->assertSet('editingServerEnvKey', 'APP_NAME')
            ->assertSet('serverEnvKey', 'APP_NAME')
            ->assertSet('serverEnvValue', 'TestApp')
            ->assertSet('showServerEnvModal', true);
    }

    // ==================== SERVER ENV VARIABLE SAVE TESTS ====================

    public function test_server_env_validates_key_format(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', '123INVALID')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    public function test_server_env_requires_key(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', '')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    public function test_server_env_allows_empty_value(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'EMPTY_VAR')
            ->set('serverEnvValue', '')
            ->call('saveServerEnvVariable')
            ->assertHasNoErrors(['serverEnvValue']);
    }

    public function test_server_env_key_converted_to_uppercase(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'lowercase_key')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertSessionHas('message', fn ($message) => str_contains($message, 'LOWERCASE_KEY'));
    }

    public function test_server_env_save_closes_modal(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('openServerEnvModal')
            ->set('serverEnvKey', 'NEW_VAR')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertSet('showServerEnvModal', false);
    }

    public function test_server_env_save_shows_error_on_failure(): void
    {
        $envContent = "APP_NAME=TestApp";
        $this->mock(Process::class, function ($mock) use ($envContent): void {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturn(0);
            $mock->shouldReceive('isSuccessful')->andReturn(true, true, false);
            $mock->shouldReceive('getOutput')->andReturn($envContent, $envContent, '');
            $mock->shouldReceive('getErrorOutput')->andReturn('', '', 'Permission denied');
        });

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'NEW_VAR')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertSessionHas('error');
    }

    // ==================== SERVER ENV VARIABLE DELETE TESTS ====================

    public function test_can_delete_server_env_variable(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteServerEnvVariable', 'OLD_VAR')
            ->assertSessionHas('message', fn ($message) => str_contains($message, 'deleted'));
    }

    public function test_delete_server_env_shows_error_on_failure(): void
    {
        $envContent = "APP_NAME=TestApp";
        $this->mock(Process::class, function ($mock) use ($envContent): void {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturn(0);
            $mock->shouldReceive('isSuccessful')->andReturn(true, false);
            $mock->shouldReceive('getOutput')->andReturn($envContent, '');
            $mock->shouldReceive('getErrorOutput')->andReturn('', 'Permission denied');
        });

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteServerEnvVariable', 'APP_NAME')
            ->assertSessionHas('error');
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_handles_empty_env_variables(): void
    {
        $this->project->update(['env_variables' => []]);
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('envVariables', []);
    }

    public function test_handles_special_characters_in_value(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'SPECIAL_VAR')
            ->set('newEnvValue', 'value with "quotes" and \'apostrophes\'')
            ->call('addEnvVariable')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertArrayHasKey('SPECIAL_VAR', $envVars);
    }

    public function test_handles_equals_sign_in_value(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'URL_VAR')
            ->set('newEnvValue', 'https://example.com?foo=bar&baz=qux')
            ->call('addEnvVariable')
            ->assertSessionHas('message');

        $freshProject = $this->project->fresh();
        $this->assertNotNull($freshProject);
        $envVars = (array) $freshProject->env_variables;
        $this->assertEquals('https://example.com?foo=bar&baz=qux', $envVars['URL_VAR']);
    }

    public function test_handles_multiline_value(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'MULTILINE_VAR')
            ->set('newEnvValue', "line1\nline2\nline3")
            ->call('addEnvVariable')
            ->assertSessionHas('message');
    }

    // ==================== VIEW DATA TESTS ====================

    public function test_renders_with_project_data(): void
    {
        $this->mockEnvFileContent();

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $project = $component->viewData('project');
        $this->assertEquals($this->project->id, $project->id);
    }

    public function test_project_includes_server_relation(): void
    {
        $this->mockEnvFileContent();

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $project = $component->viewData('project');
        $this->assertNotNull($project->server);
        $this->assertEquals($this->server->id, $project->server->id);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values_on_mount(): void
    {
        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('showEnvModal', false)
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '')
            ->assertSet('editingEnvKey', null)
            ->assertSet('showServerEnvModal', false)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '')
            ->assertSet('editingServerEnvKey', null);
    }

    // ==================== VALIDATION TESTS ====================

    public function test_env_key_max_length(): void
    {
        $this->mockEnvFileContent();

        $longKey = str_repeat('A', 256);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', $longKey)
            ->set('newEnvValue', 'value')
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvKey']);
    }

    public function test_env_value_max_length(): void
    {
        $this->mockEnvFileContent();

        $longValue = str_repeat('A', 1001);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'KEY')
            ->set('newEnvValue', $longValue)
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvValue']);
    }

    public function test_server_env_key_regex_validation(): void
    {
        $this->mockEnvFileContent();

        // Key cannot start with number
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', '1INVALID')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    public function test_server_env_key_allows_underscores(): void
    {
        $this->mockProcessSuccess();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'MY_VAR_NAME')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasNoErrors(['serverEnvKey']);
    }

    public function test_server_env_value_max_length(): void
    {
        $this->mockEnvFileContent();

        $longValue = str_repeat('A', 2001);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'KEY')
            ->set('serverEnvValue', $longValue)
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvValue']);
    }

    // ==================== PROJECT ISOLATION TESTS ====================

    public function test_env_variables_are_project_specific(): void
    {
        $this->mockEnvFileContent();

        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'env_variables' => ['OTHER_VAR' => 'other_value'],
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('envVariables', ['APP_NAME' => 'TestApp', 'DB_HOST' => 'localhost']);

        $this->mockEnvFileContent();

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $otherProject])
            ->assertSet('envVariables', ['OTHER_VAR' => 'other_value']);
    }
}
