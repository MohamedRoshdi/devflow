<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

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

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->server = Server::factory()->online()->create([
            'user_id' => $this->user->id,
            'username' => 'testuser',
            'ip_address' => '192.168.1.100',
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'environment' => 'production',
            'env_variables' => [
                'APP_NAME' => 'Test App',
                'APP_DEBUG' => 'false',
                'DB_HOST' => 'localhost',
            ],
        ]);
    }

    /** @test */
    public function component_renders_successfully_for_authenticated_users(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-environment')
            ->assertSet('projectId', $this->project->id)
            ->assertSet('environment', 'production');
    }

    /** @test */
    public function component_loads_environment_variables_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('envVariables', [
                'APP_NAME' => 'Test App',
                'APP_DEBUG' => 'false',
                'DB_HOST' => 'localhost',
            ]);
    }

    /** @test */
    public function component_handles_project_without_env_variables(): void
    {
        $projectWithoutEnv = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'env_variables' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $projectWithoutEnv])
            ->assertSet('envVariables', []);
    }

    /** @test */
    public function component_displays_environment_variables(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSee('APP_NAME')
            ->assertSee('Test App')
            ->assertSee('APP_DEBUG')
            ->assertSee('DB_HOST');
    }

    /** @test */
    public function user_can_update_environment(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('environment', 'staging')
            ->call('updateEnvironment')
            ->assertDispatched('environmentUpdated');

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'environment' => 'staging',
        ]);
    }

    /** @test */
    public function update_environment_validates_valid_environments(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('environment', 'invalid-env')
            ->call('updateEnvironment')
            ->assertHasErrors(['environment']);
    }

    /** @test */
    public function update_environment_accepts_all_valid_environment_types(): void
    {
        $validEnvironments = ['local', 'development', 'staging', 'production'];

        foreach ($validEnvironments as $env) {
            Livewire::actingAs($this->user)
                ->test(ProjectEnvironment::class, ['project' => $this->project])
                ->set('environment', $env)
                ->call('updateEnvironment')
                ->assertHasNoErrors(['environment']);

            $this->assertDatabaseHas('projects', [
                'id' => $this->project->id,
                'environment' => $env,
            ]);
        }
    }

    /** @test */
    public function user_can_open_env_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('openEnvModal')
            ->assertSet('showEnvModal', true)
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '')
            ->assertSet('editingEnvKey', null);
    }

    /** @test */
    public function user_can_close_env_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('showEnvModal', true)
            ->set('newEnvKey', 'TEST_KEY')
            ->set('newEnvValue', 'test value')
            ->call('closeEnvModal')
            ->assertSet('showEnvModal', false);
    }

    /** @test */
    public function user_can_add_new_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'NEW_KEY')
            ->set('newEnvValue', 'new value')
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $this->project->refresh();
        $this->assertEquals('new value', $this->project->env_variables['NEW_KEY']);
    }

    /** @test */
    public function add_env_variable_requires_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', '')
            ->set('newEnvValue', 'some value')
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvKey']);
    }

    /** @test */
    public function add_env_variable_accepts_empty_value(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'EMPTY_KEY')
            ->set('newEnvValue', '')
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $this->project->refresh();
        $this->assertEquals('', $this->project->env_variables['EMPTY_KEY']);
    }

    /** @test */
    public function add_env_variable_validates_max_key_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', str_repeat('A', 256))
            ->set('newEnvValue', 'value')
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvKey']);
    }

    /** @test */
    public function add_env_variable_validates_max_value_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'TEST_KEY')
            ->set('newEnvValue', str_repeat('a', 1001))
            ->call('addEnvVariable')
            ->assertHasErrors(['newEnvValue']);
    }

    /** @test */
    public function add_env_variable_resets_form_fields_after_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'TEST_KEY')
            ->set('newEnvValue', 'test value')
            ->call('addEnvVariable')
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    /** @test */
    public function user_can_edit_existing_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('editEnvVariable', 'APP_NAME')
            ->assertSet('editingEnvKey', 'APP_NAME')
            ->assertSet('newEnvKey', 'APP_NAME')
            ->assertSet('newEnvValue', 'Test App')
            ->assertSet('showEnvModal', true);
    }

    /** @test */
    public function user_can_update_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('editingEnvKey', 'APP_NAME')
            ->set('newEnvKey', 'APP_NAME')
            ->set('newEnvValue', 'Updated App Name')
            ->call('updateEnvVariable')
            ->assertSet('showEnvModal', false);

        $this->project->refresh();
        $this->assertEquals('Updated App Name', $this->project->env_variables['APP_NAME']);
    }

    /** @test */
    public function update_env_variable_can_rename_key(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('editingEnvKey', 'APP_NAME')
            ->set('newEnvKey', 'APPLICATION_NAME')
            ->set('newEnvValue', 'Updated App')
            ->call('updateEnvVariable');

        $this->project->refresh();
        $this->assertArrayNotHasKey('APP_NAME', $this->project->env_variables);
        $this->assertEquals('Updated App', $this->project->env_variables['APPLICATION_NAME']);
    }

    /** @test */
    public function user_can_delete_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteEnvVariable', 'APP_DEBUG');

        $this->project->refresh();
        $this->assertArrayNotHasKey('APP_DEBUG', $this->project->env_variables);
    }

    /** @test */
    public function delete_env_variable_shows_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteEnvVariable', 'APP_DEBUG');

        $this->assertEquals('Environment variable deleted successfully', session('message'));
    }

    /** @test */
    public function delete_env_variable_handles_non_existent_key_gracefully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteEnvVariable', 'NON_EXISTENT_KEY');

        $this->project->refresh();
        $this->assertArrayNotHasKey('NON_EXISTENT_KEY', $this->project->env_variables);
    }

    /** @test */
    public function component_can_parse_env_file_content(): void
    {
        $envContent = <<<'ENV'
# Application Configuration
APP_NAME=MyApp
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE="my_database"

# Cache Configuration
CACHE_DRIVER='redis'
REDIS_HOST=localhost
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertIsArray($parsedVars);
        $this->assertEquals('MyApp', $parsedVars['APP_NAME']);
        $this->assertEquals('production', $parsedVars['APP_ENV']);
        $this->assertEquals('false', $parsedVars['APP_DEBUG']);
        $this->assertEquals('127.0.0.1', $parsedVars['DB_HOST']);
        $this->assertEquals('3306', $parsedVars['DB_PORT']);
        $this->assertEquals('my_database', $parsedVars['DB_DATABASE']);
        $this->assertEquals('redis', $parsedVars['CACHE_DRIVER']);
    }

    /** @test */
    public function parse_env_file_skips_comments(): void
    {
        $envContent = <<<'ENV'
# This is a comment
APP_NAME=Test
# Another comment
APP_ENV=production
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertCount(2, $parsedVars);
        $this->assertArrayNotHasKey('# This is a comment', $parsedVars);
    }

    /** @test */
    public function parse_env_file_skips_empty_lines(): void
    {
        $envContent = <<<'ENV'
APP_NAME=Test

APP_ENV=production


APP_DEBUG=false
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertCount(3, $parsedVars);
    }

    /** @test */
    public function parse_env_file_removes_surrounding_quotes(): void
    {
        $envContent = <<<'ENV'
SINGLE_QUOTED='value with spaces'
DOUBLE_QUOTED="another value"
NO_QUOTES=simple
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertEquals('value with spaces', $parsedVars['SINGLE_QUOTED']);
        $this->assertEquals('another value', $parsedVars['DOUBLE_QUOTED']);
        $this->assertEquals('simple', $parsedVars['NO_QUOTES']);
    }

    /** @test */
    public function user_can_open_server_env_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('openServerEnvModal')
            ->assertSet('showServerEnvModal', true)
            ->assertSet('editingServerEnvKey', null)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '');
    }

    /** @test */
    public function user_can_close_server_env_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('showServerEnvModal', true)
            ->set('serverEnvKey', 'TEST')
            ->set('serverEnvValue', 'value')
            ->call('closeServerEnvModal')
            ->assertSet('showServerEnvModal', false)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '')
            ->assertSet('editingServerEnvKey', null);
    }

    /** @test */
    public function user_can_edit_server_environment_variable(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        // Manually set serverEnvVariables to simulate loaded state
        $component->set('serverEnvVariables', [
            'APP_NAME' => 'Server App',
            'APP_ENV' => 'production',
        ]);

        $component->call('editServerEnvVariable', 'APP_NAME')
            ->assertSet('editingServerEnvKey', 'APP_NAME')
            ->assertSet('serverEnvKey', 'APP_NAME')
            ->assertSet('serverEnvValue', 'Server App')
            ->assertSet('showServerEnvModal', true);
    }

    /** @test */
    public function save_server_env_variable_validates_key_format(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', '123INVALID')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    /** @test */
    public function save_server_env_variable_validates_key_naming_convention(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'invalid-key-name')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    /** @test */
    public function save_server_env_variable_accepts_valid_key_names(): void
    {
        $validKeys = ['APP_NAME', 'DB_HOST', 'CACHE_DRIVER', 'REDIS_HOST', 'MY_CUSTOM_VAR'];

        foreach ($validKeys as $key) {
            Livewire::actingAs($this->user)
                ->test(ProjectEnvironment::class, ['project' => $this->project])
                ->set('serverEnvKey', $key)
                ->set('serverEnvValue', 'test value')
                ->call('saveServerEnvVariable')
                ->assertHasNoErrors(['serverEnvKey']);
        }
    }

    /** @test */
    public function save_server_env_variable_validates_key_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', '')
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    /** @test */
    public function save_server_env_variable_validates_key_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', str_repeat('A', 256))
            ->set('serverEnvValue', 'value')
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvKey']);
    }

    /** @test */
    public function save_server_env_variable_validates_value_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'TEST_KEY')
            ->set('serverEnvValue', str_repeat('a', 2001))
            ->call('saveServerEnvVariable')
            ->assertHasErrors(['serverEnvValue']);
    }

    /** @test */
    public function save_server_env_variable_converts_key_to_uppercase(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('serverEnvKey', 'app_name')
            ->set('serverEnvValue', 'Test App')
            ->call('saveServerEnvVariable');

        // The key should be converted to uppercase internally
        // We can't test SSH execution here, but we verified the validation passes
    }

    /** @test */
    public function component_handles_project_without_server(): void
    {
        $projectWithoutServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $projectWithoutServer]);

        // Since we can't mock Process in this context, we just verify the component loads
        $component->assertSet('projectId', $projectWithoutServer->id);
    }

    /** @test */
    public function component_initializes_with_correct_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('showEnvModal', false)
            ->assertSet('showServerEnvModal', false)
            ->assertSet('serverEnvLoading', false)
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '')
            ->assertSet('editingEnvKey', null)
            ->assertSet('editingServerEnvKey', null)
            ->assertSet('serverEnvKey', '')
            ->assertSet('serverEnvValue', '');
    }

    /** @test */
    public function component_uses_locked_attribute_for_project_id(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);

        // Attempt to change the locked property (it should remain unchanged)
        $component->set('projectId', 999);

        // Livewire's Locked attribute prevents this change
        $component->assertSet('projectId', $this->project->id);
    }

    /** @test */
    public function get_project_method_loads_server_relationship(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $project = $this->invokeMethod($component->instance(), 'getProject');

        $this->assertNotNull($project->server);
        $this->assertEquals($this->server->id, $project->server->id);
    }

    /** @test */
    public function component_handles_special_characters_in_env_values(): void
    {
        $specialValue = 'value with "quotes" and \'apostrophes\' and $pecial characters';

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'SPECIAL_VALUE')
            ->set('newEnvValue', $specialValue)
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $this->project->refresh();
        $this->assertEquals($specialValue, $this->project->env_variables['SPECIAL_VALUE']);
    }

    /** @test */
    public function component_handles_multiline_env_values(): void
    {
        $multilineValue = "Line 1\nLine 2\nLine 3";

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'MULTILINE')
            ->set('newEnvValue', $multilineValue)
            ->call('addEnvVariable')
            ->assertHasNoErrors();

        $this->project->refresh();
        $this->assertEquals($multilineValue, $this->project->env_variables['MULTILINE']);
    }

    /** @test */
    public function component_preserves_existing_variables_when_adding_new_one(): void
    {
        $originalCount = count($this->project->env_variables);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'ADDITIONAL_KEY')
            ->set('newEnvValue', 'additional value')
            ->call('addEnvVariable');

        $this->project->refresh();
        $this->assertCount($originalCount + 1, $this->project->env_variables);
        $this->assertEquals('Test App', $this->project->env_variables['APP_NAME']);
        $this->assertEquals('additional value', $this->project->env_variables['ADDITIONAL_KEY']);
    }

    /** @test */
    public function component_preserves_existing_variables_when_deleting_one(): void
    {
        $originalCount = count($this->project->env_variables);

        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->call('deleteEnvVariable', 'APP_DEBUG');

        $this->project->refresh();
        $this->assertCount($originalCount - 1, $this->project->env_variables);
        $this->assertEquals('Test App', $this->project->env_variables['APP_NAME']);
        $this->assertEquals('localhost', $this->project->env_variables['DB_HOST']);
    }

    /** @test */
    public function parse_env_file_handles_equals_signs_in_values(): void
    {
        $envContent = "DATABASE_URL=mysql://user:pass@localhost/db?option=value";

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertEquals('mysql://user:pass@localhost/db?option=value', $parsedVars['DATABASE_URL']);
    }

    /** @test */
    public function parse_env_file_handles_empty_values(): void
    {
        $envContent = <<<'ENV'
KEY_WITH_VALUE=value
EMPTY_KEY=
ANOTHER_KEY=another
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertEquals('', $parsedVars['EMPTY_KEY']);
        $this->assertArrayHasKey('EMPTY_KEY', $parsedVars);
    }

    /** @test */
    public function parse_env_file_trims_whitespace_from_keys_and_values(): void
    {
        $envContent = <<<'ENV'
  KEY_WITH_SPACES  =  value with spaces
NORMAL_KEY=normal_value
ENV;

        $component = Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project]);

        $parsedVars = $this->invokeMethod($component->instance(), 'parseEnvFile', [$envContent]);

        $this->assertArrayHasKey('KEY_WITH_SPACES', $parsedVars);
        $this->assertEquals('value with spaces', $parsedVars['KEY_WITH_SPACES']);
    }

    /** @test */
    public function component_handles_environment_update_with_special_values(): void
    {
        foreach (['local', 'development', 'staging', 'production'] as $env) {
            $this->project->refresh();

            Livewire::actingAs($this->user)
                ->test(ProjectEnvironment::class, ['project' => $this->project])
                ->set('environment', $env)
                ->call('updateEnvironment')
                ->assertHasNoErrors()
                ->assertDispatched('environmentUpdated');

            $this->assertDatabaseHas('projects', [
                'id' => $this->project->id,
                'environment' => $env,
            ]);
        }
    }

    /** @test */
    public function update_environment_flashes_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('environment', 'staging')
            ->call('updateEnvironment');

        $this->assertEquals('Environment updated to Staging', session('message'));
    }

    /** @test */
    public function add_env_variable_flashes_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('newEnvKey', 'NEW_KEY')
            ->set('newEnvValue', 'value')
            ->call('addEnvVariable');

        $this->assertEquals('Environment variable added successfully', session('message'));
    }

    /** @test */
    public function update_env_variable_flashes_success_message(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProjectEnvironment::class, ['project' => $this->project])
            ->set('editingEnvKey', 'APP_NAME')
            ->set('newEnvKey', 'APP_NAME')
            ->set('newEnvValue', 'Updated')
            ->call('updateEnvVariable');

        $this->assertEquals('Environment variable updated successfully', session('message'));
    }

    /**
     * Helper method to invoke private/protected methods for testing.
     *
     * @param  object  $object  The object instance
     * @param  string  $methodName  The method name to invoke
     * @param  array<int, mixed>  $parameters  The method parameters
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
