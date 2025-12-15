<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\CICD\PipelineBuilder;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\RefreshDatabaseTestCase;

class PipelineBuilderTest extends RefreshDatabaseTestCase
{

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
     * Give the user permissions using Laravel's Gate facade
     *
     * @param  User  $user
     * @param  array<string>  $permissions
     */
    protected function giveUserPermissions(User $user, array $permissions): void
    {
        // Define gates for all possible permissions used in this test
        $allPermissions = ['create-pipelines', 'edit-pipelines'];

        foreach ($allPermissions as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, function ($authUser) use ($user, $permissions, $permission) {
                // Grant permission only if user has it and is the authenticated user
                return $authUser->id === $user->id && in_array($permission, $permissions, true);
            });
        }
    }

    #[Test]
    public function component_renders_successfully_for_users_with_create_permission(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.cicd.pipeline-builder')
            ->assertSet('project', fn ($project) => $project->id === $this->project->id);
    }

    #[Test]
    public function component_renders_successfully_for_users_with_edit_permission(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.cicd.pipeline-builder');
    }

    #[Test]
    public function component_blocks_users_without_permissions(): void
    {
        $this->giveUserPermissions($this->userWithoutPermissions, []);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You do not have permission to manage pipelines.');

        Livewire::actingAs($this->userWithoutPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project]);
    }

    #[Test]
    public function component_blocks_unauthenticated_users(): void
    {
        $this->expectException(\TypeError::class);

        Livewire::test(PipelineBuilder::class, ['project' => $this->project]);
    }

    #[Test]
    public function component_initializes_with_empty_stages_when_no_project(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->assertSet('project', null)
            ->assertSet('stages', [
                'pre_deploy' => [],
                'deploy' => [],
                'post_deploy' => [],
            ]);
    }

    #[Test]
    public function component_loads_existing_stages_for_project(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function add_stage_opens_modal_with_correct_type(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->assertSet('showStageModal', true)
            ->assertSet('stageType', 'pre_deploy')
            ->assertSet('editingStageId', null);
    }

    #[Test]
    public function add_stage_resets_form_fields(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function edit_stage_loads_stage_data_into_form(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function save_stage_creates_new_stage_successfully(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Install Composer')
            ->set('stageType', 'pre_deploy')
            ->set('commands', "composer install\ncomposer dump-autoload")
            ->set('timeoutSeconds', 300)
            ->set('continueOnFailure', false)
            ->call('saveStage')
            ->assertDispatched('notification', function ($name, array $data) {
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

    #[Test]
    public function save_stage_updates_existing_stage_successfully(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage updated successfully!';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'name' => 'Updated Name',
            'type' => 'deploy',
            'timeout_seconds' => 500,
        ]);
    }

    #[Test]
    public function save_stage_requires_project(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->set('stageName', 'Test Stage')
            ->set('commands', 'test command')
            ->call('saveStage')
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'error' && $data['message'] === 'Please select a project first';
            });

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    #[Test]
    public function save_stage_validates_required_fields(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', '')
            ->set('commands', '')
            ->call('saveStage')
            ->assertHasErrors(['stageName', 'commands']);
    }

    #[Test]
    public function save_stage_validates_stage_type(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('stageType', 'invalid_type')
            ->set('commands', 'test command')
            ->call('saveStage')
            ->assertHasErrors(['stageType']);
    }

    #[Test]
    public function save_stage_validates_timeout_range(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function save_stage_filters_empty_commands(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Test Stage')
            ->set('commands', "command1\n\n\ncommand2\n  \ncommand3")
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Test Stage')->first();
        $this->assertEquals(['command1', 'command2', 'command3'], $stage->commands);
    }

    #[Test]
    public function save_stage_assigns_correct_order_for_new_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function delete_stage_removes_stage_successfully(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage to Delete',
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage->id)
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage deleted successfully';
            });

        $this->assertDatabaseMissing('pipeline_stages', [
            'id' => $stage->id,
        ]);
    }

    #[Test]
    public function delete_stage_reorders_remaining_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function toggle_stage_enables_disabled_stage(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => false,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage enabled';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'enabled' => true,
        ]);
    }

    #[Test]
    public function toggle_stage_disables_enabled_stage(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success' && $data['message'] === 'Stage disabled';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'enabled' => false,
        ]);
    }

    #[Test]
    public function update_stage_order_reorders_stages_correctly(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function add_env_variable_adds_variable_to_array(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('newEnvKey', 'NODE_ENV')
            ->set('newEnvValue', 'production')
            ->call('addEnvVariable')
            ->assertSet('envVariables', ['NODE_ENV' => 'production'])
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    #[Test]
    public function add_env_variable_requires_both_key_and_value(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function remove_env_variable_removes_variable_from_array(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('envVariables', ['KEY1' => 'value1', 'KEY2' => 'value2'])
            ->call('removeEnvVariable', 'KEY1')
            ->assertSet('envVariables', ['KEY2' => 'value2']);
    }

    #[Test]
    public function apply_template_requires_project(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class)
            ->call('applyTemplate', 'laravel')
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'error' && $data['message'] === 'Please select a project first';
            });

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    #[Test]
    public function apply_laravel_template_creates_correct_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'laravel')
            ->assertDispatched('notification', function ($name, array $data) {
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

    #[Test]
    public function apply_nodejs_template_creates_correct_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'nodejs')
            ->assertDispatched('notification', function ($name, array $data) {
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

    #[Test]
    public function apply_static_template_creates_correct_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'static')
            ->assertDispatched('notification', function ($name, array $data) {
                return $data['type'] === 'success';
            });

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Copy Files',
            'type' => 'deploy',
        ]);
    }

    #[Test]
    public function apply_template_preserves_order_of_existing_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function apply_invalid_template_creates_no_stages(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'invalid_template');

        $this->assertDatabaseCount('pipeline_stages', 0);
    }

    #[Test]
    public function stage_form_modal_state_management(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function template_modal_state_management(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('showTemplateModal', false)
            ->set('showTemplateModal', true)
            ->assertSet('showTemplateModal', true)
            ->call('applyTemplate', 'laravel')
            ->assertSet('showTemplateModal', false);
    }

    #[Test]
    public function component_initializes_with_default_values(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function save_stage_persists_environment_variables(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', 'Deploy Stage')
            ->set('commands', 'deploy command')
            ->set('envVariables', ['API_KEY' => 'secret', 'DEBUG' => 'false'])
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Deploy Stage')->first();
        $this->assertEquals(['API_KEY' => 'secret', 'DEBUG' => 'false'], $stage->environment_variables);
    }

    #[Test]
    public function stages_are_grouped_by_type_correctly(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function load_stages_is_called_after_save(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function load_stages_is_called_after_delete(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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

    #[Test]
    public function load_stages_is_called_after_toggle(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertSet('stages.pre_deploy.0.enabled', false);
    }

    #[Test]
    public function multiple_environment_variables_can_be_added(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function continue_on_failure_flag_persists(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function stage_name_max_length_validation(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

        Livewire::actingAs($this->userWithPermissions)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->set('stageName', str_repeat('a', 256))
            ->set('commands', 'test')
            ->call('saveStage')
            ->assertHasErrors(['stageName']);
    }

    #[Test]
    public function commands_string_is_properly_converted_to_array(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['create-pipelines']);

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

    #[Test]
    public function editing_stage_preserves_unmodified_fields(): void
    {
        $this->giveUserPermissions($this->userWithPermissions, ['edit-pipelines']);

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
