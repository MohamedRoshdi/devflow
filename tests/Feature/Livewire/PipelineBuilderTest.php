<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\CICD\PipelineBuilder;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PipelineBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'create-pipelines']);
        Permission::create(['name' => 'edit-pipelines']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('create-pipelines');

        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
    }

    // =========================================================================
    // Authorization Tests
    // =========================================================================

    public function test_guest_cannot_access_pipeline_builder(): void
    {
        Livewire::test(PipelineBuilder::class, ['project' => $this->project])
            ->assertForbidden();
    }

    public function test_user_without_pipeline_permission_cannot_access(): void
    {
        $userWithoutPermission = User::factory()->create();

        Livewire::actingAs($userWithoutPermission)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertForbidden();
    }

    public function test_user_with_create_pipelines_permission_can_access(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertOk()
            ->assertSet('project.id', $this->project->id);
    }

    public function test_user_with_edit_pipelines_permission_can_access(): void
    {
        $editUser = User::factory()->create();
        $editUser->givePermissionTo('edit-pipelines');

        Livewire::actingAs($editUser)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertOk();
    }

    // =========================================================================
    // Component Loading Tests
    // =========================================================================

    public function test_component_mounts_with_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id)
            ->assertSet('showStageModal', false)
            ->assertSet('showTemplateModal', false);
    }

    public function test_component_mounts_without_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class)
            ->assertSet('project', null)
            ->assertSet('stages', [
                'pre_deploy' => [],
                'deploy' => [],
                'post_deploy' => [],
            ]);
    }

    public function test_component_loads_existing_stages(): void
    {
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Pre-deploy Stage',
            'order' => 0,
        ]);

        PipelineStage::factory()->deploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Deploy Stage',
            'order' => 0,
        ]);

        PipelineStage::factory()->postDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Post-deploy Stage',
            'order' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->assertCount('stages.pre_deploy', 1)
            ->assertCount('stages.deploy', 1)
            ->assertCount('stages.post_deploy', 1);
    }

    public function test_stages_are_ordered_correctly(): void
    {
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 2',
            'order' => 1,
        ]);

        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 1',
            'order' => 0,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project]);

        $stages = $component->get('stages.pre_deploy');
        $this->assertEquals('Stage 1', $stages[0]['name']);
        $this->assertEquals('Stage 2', $stages[1]['name']);
    }

    // =========================================================================
    // Stage Creation Tests
    // =========================================================================

    public function test_add_stage_opens_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->assertSet('showStageModal', true)
            ->assertSet('stageType', 'pre_deploy')
            ->assertSet('editingStageId', null);
    }

    public function test_can_create_pre_deploy_stage(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Install Dependencies')
            ->set('commands', "composer install\nnpm install")
            ->set('timeoutSeconds', 300)
            ->set('continueOnFailure', false)
            ->call('saveStage')
            ->assertSet('showStageModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
            'timeout_seconds' => 300,
            'continue_on_failure' => false,
        ]);
    }

    public function test_can_create_deploy_stage(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'deploy')
            ->set('stageName', 'Run Migrations')
            ->set('commands', 'php artisan migrate --force')
            ->set('timeoutSeconds', 120)
            ->call('saveStage');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Run Migrations',
            'type' => 'deploy',
        ]);
    }

    public function test_can_create_post_deploy_stage(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'post_deploy')
            ->set('stageName', 'Clear Cache')
            ->set('commands', 'php artisan cache:clear')
            ->set('timeoutSeconds', 60)
            ->call('saveStage');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Clear Cache',
            'type' => 'post_deploy',
        ]);
    }

    public function test_cannot_save_stage_without_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class)
            ->set('stageName', 'Test Stage')
            ->set('commands', 'echo test')
            ->call('saveStage')
            ->assertDispatched('notification', function ($name, $params) {
                return $params['type'] === 'error';
            });
    }

    public function test_stage_order_is_incremented_correctly(): void
    {
        PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'order' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Second Stage')
            ->set('commands', 'echo test')
            ->call('saveStage');

        $newStage = PipelineStage::where('name', 'Second Stage')->first();
        $this->assertEquals(1, $newStage->order);
    }

    // =========================================================================
    // Stage Validation Tests
    // =========================================================================

    public function test_stage_name_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', '')
            ->set('commands', 'echo test')
            ->call('saveStage')
            ->assertHasErrors(['stageName' => 'required']);
    }

    public function test_commands_are_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Test Stage')
            ->set('commands', '')
            ->call('saveStage')
            ->assertHasErrors(['commands' => 'required']);
    }

    public function test_stage_type_must_be_valid(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Test Stage')
            ->set('stageType', 'invalid_type')
            ->set('commands', 'echo test')
            ->call('saveStage')
            ->assertHasErrors(['stageType' => 'in']);
    }

    public function test_timeout_must_be_at_least_10_seconds(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Test Stage')
            ->set('commands', 'echo test')
            ->set('timeoutSeconds', 5)
            ->call('saveStage')
            ->assertHasErrors(['timeoutSeconds' => 'min']);
    }

    public function test_timeout_cannot_exceed_3600_seconds(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Test Stage')
            ->set('commands', 'echo test')
            ->set('timeoutSeconds', 4000)
            ->call('saveStage')
            ->assertHasErrors(['timeoutSeconds' => 'max']);
    }

    public function test_stage_name_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', str_repeat('a', 256))
            ->set('commands', 'echo test')
            ->call('saveStage')
            ->assertHasErrors(['stageName' => 'max']);
    }

    // =========================================================================
    // Stage Editing Tests
    // =========================================================================

    public function test_edit_stage_opens_modal_with_data(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Existing Stage',
            'type' => 'deploy',
            'commands' => ['echo "hello"', 'echo "world"'],
            'timeout_seconds' => 120,
            'continue_on_failure' => true,
            'environment_variables' => ['KEY' => 'value'],
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('editStage', $stage->id)
            ->assertSet('showStageModal', true)
            ->assertSet('editingStageId', $stage->id)
            ->assertSet('stageName', 'Existing Stage')
            ->assertSet('stageType', 'deploy')
            ->assertSet('commands', "echo \"hello\"\necho \"world\"")
            ->assertSet('timeoutSeconds', 120)
            ->assertSet('continueOnFailure', true)
            ->assertSet('envVariables', ['KEY' => 'value']);
    }

    public function test_can_update_existing_stage(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
            'type' => 'pre_deploy',
            'commands' => ['echo original'],
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('editStage', $stage->id)
            ->set('stageName', 'Updated Name')
            ->set('commands', 'echo updated')
            ->call('saveStage')
            ->assertSet('showStageModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'name' => 'Updated Name',
        ]);
    }

    // =========================================================================
    // Stage Deletion Tests
    // =========================================================================

    public function test_can_delete_stage(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage to Delete',
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage->id)
            ->assertDispatched('notification');

        $this->assertDatabaseMissing('pipeline_stages', [
            'id' => $stage->id,
        ]);
    }

    public function test_deleting_stage_reorders_remaining_stages(): void
    {
        $stage1 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 1',
            'order' => 0,
        ]);

        $stage2 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 2',
            'order' => 1,
        ]);

        $stage3 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 3',
            'order' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('deleteStage', $stage2->id);

        $stage3->refresh();
        $this->assertEquals(1, $stage3->order);
    }

    // =========================================================================
    // Toggle Stage Tests
    // =========================================================================

    public function test_can_enable_stage(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification');

        $stage->refresh();
        $this->assertTrue($stage->enabled);
    }

    public function test_can_disable_stage(): void
    {
        $stage = PipelineStage::factory()->create([
            'project_id' => $this->project->id,
            'enabled' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('toggleStage', $stage->id)
            ->assertDispatched('notification');

        $stage->refresh();
        $this->assertFalse($stage->enabled);
    }

    // =========================================================================
    // Environment Variables Tests
    // =========================================================================

    public function test_can_add_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('newEnvKey', 'DATABASE_URL')
            ->set('newEnvValue', 'mysql://localhost')
            ->call('addEnvVariable')
            ->assertSet('envVariables', ['DATABASE_URL' => 'mysql://localhost'])
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    public function test_can_remove_environment_variable(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('envVariables', ['KEY1' => 'value1', 'KEY2' => 'value2'])
            ->call('removeEnvVariable', 'KEY1')
            ->assertSet('envVariables', ['KEY2' => 'value2']);
    }

    public function test_empty_env_key_is_not_added(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('newEnvKey', '')
            ->set('newEnvValue', 'some_value')
            ->call('addEnvVariable')
            ->assertSet('envVariables', []);
    }

    public function test_stage_saves_with_environment_variables(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Stage with Env')
            ->set('commands', 'echo $TEST_VAR')
            ->set('envVariables', ['TEST_VAR' => 'hello'])
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Stage with Env')->first();
        $this->assertEquals(['TEST_VAR' => 'hello'], $stage->environment_variables);
    }

    // =========================================================================
    // Template Application Tests
    // =========================================================================

    public function test_cannot_apply_template_without_project(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class)
            ->call('applyTemplate', 'laravel')
            ->assertDispatched('notification', function ($name, $params) {
                return $params['type'] === 'error';
            });
    }

    public function test_can_apply_laravel_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'laravel')
            ->assertSet('showTemplateModal', false)
            ->assertDispatched('notification');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Composer Dependencies',
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

        // Laravel template creates 5 stages
        $this->assertEquals(5, PipelineStage::where('project_id', $this->project->id)->count());
    }

    public function test_can_apply_nodejs_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'nodejs')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Install Dependencies',
            'type' => 'pre_deploy',
        ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Build Application',
            'type' => 'deploy',
        ]);

        // Node.js template creates 3 stages
        $this->assertEquals(3, PipelineStage::where('project_id', $this->project->id)->count());
    }

    public function test_can_apply_static_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'static')
            ->assertDispatched('notification');

        $this->assertDatabaseHas('pipeline_stages', [
            'project_id' => $this->project->id,
            'name' => 'Copy Files',
            'type' => 'deploy',
        ]);

        // Static template creates 1 stage
        $this->assertEquals(1, PipelineStage::where('project_id', $this->project->id)->count());
    }

    public function test_unknown_template_creates_no_stages(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'unknown_template');

        $this->assertEquals(0, PipelineStage::where('project_id', $this->project->id)->count());
    }

    public function test_template_stages_have_correct_order(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('applyTemplate', 'laravel');

        $preDeployStages = PipelineStage::where('project_id', $this->project->id)
            ->where('type', 'pre_deploy')
            ->orderBy('order')
            ->get();

        $this->assertEquals(0, $preDeployStages[0]->order);
        $this->assertEquals(1, $preDeployStages[1]->order);
        $this->assertEquals(2, $preDeployStages[2]->order);
    }

    // =========================================================================
    // Stage Reordering Tests
    // =========================================================================

    public function test_can_reorder_stages_via_event(): void
    {
        $stage1 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 1',
            'order' => 0,
        ]);

        $stage2 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 2',
            'order' => 1,
        ]);

        $stage3 = PipelineStage::factory()->preDeploy()->create([
            'project_id' => $this->project->id,
            'name' => 'Stage 3',
            'order' => 2,
        ]);

        // Reorder: Stage 3, Stage 1, Stage 2
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->dispatch('stages-reordered', stageIds: [$stage3->id, $stage1->id, $stage2->id], type: 'pre_deploy');

        $stage1->refresh();
        $stage2->refresh();
        $stage3->refresh();

        $this->assertEquals(1, $stage1->order);
        $this->assertEquals(2, $stage2->order);
        $this->assertEquals(0, $stage3->order);
    }

    // =========================================================================
    // Form Reset Tests
    // =========================================================================

    public function test_form_is_reset_after_saving_stage(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Test Stage')
            ->set('commands', 'echo test')
            ->set('timeoutSeconds', 120)
            ->set('continueOnFailure', true)
            ->set('envVariables', ['KEY' => 'value'])
            ->call('saveStage')
            ->assertSet('stageName', '')
            ->assertSet('commands', '')
            ->assertSet('timeoutSeconds', 300)
            ->assertSet('continueOnFailure', false)
            ->assertSet('envVariables', [])
            ->assertSet('editingStageId', null);
    }

    // =========================================================================
    // Commands Parsing Tests
    // =========================================================================

    public function test_multiline_commands_are_parsed_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Multi Command Stage')
            ->set('commands', "composer install\n\nnpm install\n\nnpm run build")
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Multi Command Stage')->first();
        $this->assertEquals([
            'composer install',
            'npm install',
            'npm run build',
        ], $stage->commands);
    }

    public function test_empty_lines_are_filtered_from_commands(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Filtered Commands')
            ->set('commands', "  \necho test\n   \n\necho done\n  ")
            ->call('saveStage');

        $stage = PipelineStage::where('name', 'Filtered Commands')->first();
        $this->assertEquals(['echo test', 'echo done'], $stage->commands);
    }

    // =========================================================================
    // Continue On Failure Tests
    // =========================================================================

    public function test_can_create_stage_with_continue_on_failure(): void
    {
        Livewire::actingAs($this->user)
            ->test(PipelineBuilder::class, ['project' => $this->project])
            ->call('addStage', 'pre_deploy')
            ->set('stageName', 'Non-Critical Stage')
            ->set('commands', 'echo test')
            ->set('continueOnFailure', true)
            ->call('saveStage');

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Non-Critical Stage',
            'continue_on_failure' => true,
        ]);
    }
}
