<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;

use App\Livewire\CICD\PipelineBuilder;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PipelineBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected User $userWithPermissions;

    protected User $userWithoutPermissions;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with and without permissions
        $this->userWithPermissions = User::factory()->create(['name' => 'Pipeline Manager']);
        $this->userWithoutPermissions = User::factory()->create(['name' => 'Regular User']);

        // Create a test project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'slug' => 'test-project',
        ]);
    }

    /**
     * Mock the user's can() method to simulate permissions
     *
     * @param  User  $user
     * @param  array<string>  $permissions
     */
    protected function mockUserPermissions(User $user, array $permissions): void
    {
        $user->shouldReceive('can')
            ->andReturnUsing(function (string $ability) use ($permissions) {
                return in_array($ability, $permissions, true);
            });
    }

    /** @test */
    public function component_renders_successfully_for_users_with_create_permission(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.cicd.pipeline-builder')
            ->assertSet('project', fn ($project) => $project->id === $this->project->id);
    }

    /** @test */
    public function component_renders_successfully_for_users_with_edit_permission(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.cicd.pipeline-builder');
    }

    /** @test */
    public function component_blocks_users_without_permissions(): void
    {
        $this->mockUserPermissions($this->userWithoutPermissions, []);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You do not have permission to manage pipelines.');

        Livewire::actingAs($this->userWithoutPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project]);
    }

    /** @test */
    public function component_blocks_unauthenticated_users(): void
    {
        $this->expectException(\TypeError::class);

        Livewire::test(PipelineBuilder::class, ['project' => $this->project]);
    }

    /** @test */
    public function component_initializes_with_empty_stages_when_no_project(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->assertSet('project', null)
            ->assertSet('stages', [
                'pre_deploy' => [],
                'deploy' => [],
                'post_deploy' => [],
            ]);
    }

    /** @test */
    public function component_loads_existing_stages_for_project(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        // Create pipeline stages for the project
        $preDeployStage = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'order' => 0,
        ]);

        $deployStage = PipelineStage::factory()->deploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Run Migrations',
            'order' => 0,
        ]);

        $postDeployStage = PipelineStage::factory()->postDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Clear Cache',
            'order' => 0,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('stages.pre_deploy', function ($stages) use ($preDeployStage) {
                return count($stages) === 1 && $stages[0]['id'] === $preDeployStage->id;
            })
            ->assertSet('stages.deploy', function ($stages) use ($deployStage) {
                return count($stages) === 1 && $stages[0]['id'] === $deployStage->id;
            })
            ->assertSet('stages.post_deploy', function ($stages) use ($postDeployStage) {
                return count($stages) === 1 && $stages[0]['id'] === $postDeployStage->id;
            });
    }

    /** @test */
    public function add_stage_opens_modal_with_correct_type(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->assertSet('showStageModal', true)
            ->assertSet('stageType', 'pre_deploy')
            ->assertSet('editingStageId', null);
    }

    /** @test */
    public function add_stage_resets_form_fields(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Old Name')
            ->set('commands', 'old command')
            ->call('addStage', 'deploy')
            ->assertSet('stageName', '')
            ->assertSet('commands', '')
            ->assertSet('stageType', 'deploy')
            ->assertSet('timeoutSeconds', 300)
            ->assertSet('continueOnFailure', false)
            ->assertSet('envVariables', []);
    }

    /** @test */
    public function edit_stage_loads_stage_data_into_form(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Build Assets',
            'type' => 'pre_deploy',
            'commands' => ['npm install', 'npm run build'],
            'timeout_seconds' => 600,
            'continue_on_failure' => true,
            'environment_variables' => ['NODE_ENV' => 'production'],
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('editStage', $stage->id)
            ->assertSet('showStageModal', true)
            ->assertSet('editingStageId', $stage->id)
            ->assertSet('stageName', 'Build Assets')
            ->assertSet('stageType', 'pre_deploy')
            ->assertSet('commands', "npm install\nnpm run build")
            ->assertSet('timeoutSeconds', 600)
            ->assertSet('continueOnFailure', true)
            ->assertSet('envVariables', ['NODE_ENV' => 'production']);
    }

    /** @test */
    public function save_stage_creates_new_stage_successfully(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Install Composer')
            ->set('stageType', 'pre_deploy')
            ->set('commands', "composer install\ncomposer dump-autoload")
            ->set('timeoutSeconds', 300)
            ->set('continueOnFailure', false)
            ->call('saveStage')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage created successfully!';
            })
            ->assertSet('showStageModal', false);

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Composer',
            'type' => 'pre_deploy',
            'timeout_seconds' => 300,
            'continue_on_failure' => false,
            'order' => 0,
        ]);

        $stage = PipelineStage::where('name', 'Install Composer')->first();
        $this->assertNotNull($stage);
        $this->assertEquals(['composer install', 'composer dump-autoload'], $stage->commands);
    }

    /** @test */
    public function save_stage_updates_existing_stage_successfully(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Old Name',
            'commands' => ['old command'],
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('editingStageId', $stage->id)
            ->set('stageName', 'Updated Name')
            ->set('stageType', 'deploy')
            ->set('commands', 'updated command')
            ->set('timeoutSeconds', 500)
            ->call('saveStage')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage updated successfully!';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'name' => 'Updated Name',
            'type' => 'deploy',
            'timeout_seconds' => 500,
        ]);
    }

    /** @test */
    public function save_stage_requires_project(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->set('stageName', 'Test Stage')
            ->set('commands', 'test command')
            ->call('saveStage')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && $data['message'] === 'Please select a project first';
            });

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    /** @test */
    public function save_stage_validates_required_fields(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', '')
            ->set('commands', '')
            ->call('saveStage')
            ->assertHasErrors(['stageName', 'commands']);
    }

    /** @test */
    public function save_stage_validates_stage_type(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('stageType', 'invalid_type')
            ->set('commands', 'test command')
            ->call('saveStage')
            ->assertHasErrors(['stageType']);
    }

    /** @test */
    public function save_stage_validates_timeout_range(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('commands', 'test command')
            ->set('timeoutSeconds', 5)
            ->call('saveStage')
            ->assertHasErrors(['timeoutSeconds']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('commands', 'test command')
            ->set('timeoutSeconds', 5000)
            ->call('saveStage')
            ->assertHasErrors(['timeoutSeconds']);
    }

    /** @test */
    public function save_stage_filters_empty_commands(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('commands', "command1\n\n\ncommand2\n  \ncommand3")
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Test Stage')->first();
        $this->assertEquals(['command1', 'command2', 'command3'], $stage->commands);
    }

    /** @test */
    public function save_stage_assigns_correct_order_for_new_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        // Create existing stages
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 0,
        ]);
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 1,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'New Stage')
            ->set('stageType', 'pre_deploy')
            ->set('commands', 'test')
            ->call('saveStage');

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'New Stage',
            'order' => 2,
        ]);
    }

    /** @test */
    public function delete_stage_removes_stage_successfully(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage to Delete',
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage deleted successfully';
            });

        $this->assertDatabaseMissing('pipeline_stages', [
            'id' => $stage->id,
        ]);
    }

    /** @test */
    public function delete_stage_reorders_remaining_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage1 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 0,
        ]);
        $stage2 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 1,
        ]);
        $stage3 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 2,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage2->id);

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage1->id,
            'order' => 0,
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage3->id,
            'order' => 1,
        ]);
    }

    /** @test */
    public function toggle_stage_enables_disabled_stage(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => false,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage enabled';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'enabled' => true,
        ]);
    }

    /** @test */
    public function toggle_stage_disables_enabled_stage(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage disabled';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'enabled' => false,
        ]);
    }

    /** @test */
    public function update_stage_order_reorders_stages_correctly(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage1 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 0,
        ]);
        $stage2 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 1,
        ]);
        $stage3 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 2,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->dispatch('stages-reordered', stageIds: [$stage3->id, $stage1->id, $stage2->id], type: 'pre_deploy');

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage3->id,
            'order' => 0,
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage1->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage2->id,
            'order' => 2,
        ]);
    }

    /** @test */
    public function add_env_variable_adds_variable_to_array(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('newEnvKey', 'NODE_ENV')
            ->set('newEnvValue', 'production')
            ->call('addEnvVariable')
            ->assertSet('envVariables', ['NODE_ENV' => 'production'])
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    /** @test */
    public function add_env_variable_requires_both_key_and_value(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('newEnvKey', 'KEY')
            ->set('newEnvValue', '')
            ->call('addEnvVariable')
            ->assertSet('envVariables', []);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('newEnvKey', '')
            ->set('newEnvValue', 'value')
            ->call('addEnvVariable')
            ->assertSet('envVariables', []);
    }

    /** @test */
    public function remove_env_variable_removes_variable_from_array(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('envVariables', ['KEY1' => 'value1', 'KEY2' => 'value2'])
            ->call('removeEnvVariable', 'KEY1')
            ->assertSet('envVariables', ['KEY2' => 'value2']);
    }

    /** @test */
    public function apply_template_requires_project(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->call('applyTemplate', 'laravel')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'error' && $data['message'] === 'Please select a project first';
            });

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    /** @test */
    public function apply_laravel_template_creates_correct_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'laravel')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Template applied successfully!';
            })
            ->assertSet('showTemplateModal', false);

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Composer Dependencies',
            'type' => 'pre_deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install NPM Dependencies',
            'type' => 'pre_deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Build Frontend Assets',
            'type' => 'pre_deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Run Database Migrations',
            'type' => 'deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Clear & Cache Config',
            'type' => 'post_deploy',
        ]);
    }

    /** @test */
    public function apply_nodejs_template_creates_correct_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'nodejs')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Run Tests',
            'type' => 'pre_deploy',
        ]);
        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Build Application',
            'type' => 'deploy',
        ]);
    }

    /** @test */
    public function apply_static_template_creates_correct_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'static')
            ->assertDispatched('notification', function (array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Copy Files',
            'type' => 'deploy',
        ]);
    }

    /** @test */
    public function apply_template_preserves_order_of_existing_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 0,
        ]);
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 1,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'static');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Copy Files',
            'order' => 0,
        ]);
    }

    /** @test */
    public function apply_invalid_template_creates_no_stages(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'invalid_template');

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    /** @test */
    public function stage_form_modal_state_management(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('showStageModal', false)
            ->call('addStage', 'pre_deploy')
            ->assertSet('showStageModal', true)
            ->set('stageName', 'Test Stage')
            ->set('commands', 'test')
            ->call('saveStage')
            ->assertSet('showStageModal', false);
    }

    /** @test */
    public function template_modal_state_management(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('showTemplateModal', false)
            ->set('showTemplateModal', true)
            ->assertSet('showTemplateModal', true)
            ->call('applyTemplate', 'laravel')
            ->assertSet('showTemplateModal', false);
    }

    /** @test */
    public function component_initializes_with_default_values(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('showStageModal', false)
            ->assertSet('showTemplateModal', false)
            ->assertSet('editingStageId', null)
            ->assertSet('stageName', '')
            ->assertSet('stageType', 'pre_deploy')
            ->assertSet('commands', '')
            ->assertSet('timeoutSeconds', 300)
            ->assertSet('continueOnFailure', false)
            ->assertSet('envVariables', [])
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    /** @test */
    public function save_stage_persists_environment_variables(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Deploy Stage')
            ->set('commands', 'deploy command')
            ->set('envVariables', ['API_KEY' => 'secret', 'DEBUG' => 'false'])
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Deploy Stage')->first();
        $this->assertEquals(['API_KEY' => 'secret', 'DEBUG' => 'false'], $stage->environment_variables);
    }

    /** @test */
    public function stages_are_grouped_by_type_correctly(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Pre-Deploy 1',
        ]);
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Pre-Deploy 2',
        ]);
        PipelineStage::factory()->deploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Deploy 1',
        ]);
        PipelineStage::factory()->postDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Post-Deploy 1',
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('stages.pre_deploy', function ($stages) {
                return count($stages) === 2;
            })
            ->assertSet('stages.deploy', function ($stages) {
                return count($stages) === 1;
            })
            ->assertSet('stages.post_deploy', function ($stages) {
                return count($stages) === 1;
            });
    }

    /** @test */
    public function load_stages_is_called_after_save(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        $component = Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'New Stage')
            ->set('commands', 'test')
            ->call('saveStage');

        // Verify stages are reloaded
        $component->assertSet('stages.pre_deploy', function ($stages) {
            return count($stages) === 1;
        });
    }

    /** @test */
    public function load_stages_is_called_after_delete(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage->id);

        // Verify stages are reloaded
        $component->assertSet('stages.pre_deploy', function ($stages) {
            return count($stages) === 0;
        });
    }

    /** @test */
    public function load_stages_is_called_after_toggle(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertSet('stages.pre_deploy.0.enabled', false);
    }

    /** @test */
    public function multiple_environment_variables_can_be_added(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('newEnvKey', 'VAR1')
            ->set('newEnvValue', 'value1')
            ->call('addEnvVariable')
            ->set('newEnvKey', 'VAR2')
            ->set('newEnvValue', 'value2')
            ->call('addEnvVariable')
            ->set('newEnvKey', 'VAR3')
            ->set('newEnvValue', 'value3')
            ->call('addEnvVariable')
            ->assertSet('envVariables', [
                'VAR1' => 'value1',
                'VAR2' => 'value2',
                'VAR3' => 'value3',
            ]);
    }

    /** @test */
    public function continue_on_failure_flag_persists(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Optional Stage')
            ->set('commands', 'optional command')
            ->set('continueOnFailure', true)
            ->call('saveStage');

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Optional Stage',
            'continue_on_failure' => true,
        ]);
    }

    /** @test */
    public function stage_name_max_length_validation(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', str_repeat('a', 256))
            ->set('commands', 'test')
            ->call('saveStage')
            ->assertHasErrors(['stageName']);
    }

    /** @test */
    public function commands_string_is_properly_converted_to_array(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Multi-Command Stage')
            ->set('commands', "composer install\nnpm install\nnpm run build\nphp artisan migrate")
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Multi-Command Stage')->first();
        $this->assertIsArray($stage->commands);
        $this->assertCount(4, $stage->commands);
        $this->assertEquals('composer install', $stage->commands[0]);
        $this->assertEquals('php artisan migrate', $stage->commands[3]);
    }

    /** @test */
    public function editing_stage_preserves_unmodified_fields(): void
    {
        $this->mockUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
            'commands' => ['original command'],
            'timeout_seconds' => 600,
            'environment_variables' => ['KEY' => 'value'],
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('editStage', $stage->id)
            ->set('stageName', 'Updated Name')
            ->call('saveStage');

        $stage->refresh();
        $this->assertEquals('Updated Name', $stage->name);
        $this->assertEquals(600, $stage->timeout_seconds);
        $this->assertEquals(['KEY' => 'value'], $stage->environment_variables);
    }
}
