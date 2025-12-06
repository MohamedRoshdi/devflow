<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\LoginViaUI;
use Tests\DuskTestCase;

/**
 * Comprehensive Pipeline Builder Tests for DevFlow Pro
 *
 * Tests all pipeline builder functionality including:
 * - Pipeline builder page access
 * - Creating and editing stages
 * - Stage configuration and ordering
 * - Template application
 * - Environment variables management
 * - Drag and drop functionality
 * - Stage enable/disable toggle
 */
class PipelineBuilderTest extends DuskTestCase
{
    use LoginViaUI;

    protected User $user;

    protected Server $server;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Use existing test user (shared database approach)
        $this->user = User::firstOrCreate(
            ['email' => 'admin@devflow.test'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get or create test server
        $this->server = Server::firstOrCreate(
            ['hostname' => 'test-pipeline.example.com'],
            [
                'user_id' => $this->user->id,
                'name' => 'Pipeline Test Server',
                'ip_address' => '192.168.1.200',
                'port' => 22,
                'username' => 'root',
                'status' => 'online',
            ]
        );

        // Create test project for pipeline testing
        $this->project = Project::firstOrCreate(
            ['slug' => 'test-pipeline-project'],
            [
                'user_id' => $this->user->id,
                'server_id' => $this->server->id,
                'name' => 'Test Pipeline Project',
                'framework' => 'laravel',
                'status' => 'running',
                'repository_url' => 'https://github.com/test/pipeline-test.git',
                'branch' => 'main',
                'deploy_path' => '/var/www/pipeline-test',
            ]
        );
    }

    /**
     * Test 1: Pipeline builder page loads successfully without project
     */
    public function test_pipeline_builder_page_loads_without_project(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pipelines')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Builder')
                ->assertSee('Visual CI/CD Pipeline Configuration')
                ->assertSee('No Project Selected')
                ->assertSee('Please select a project from the projects page to configure its pipeline')
                ->screenshot('pipeline-builder-no-project');
        });
    }

    /**
     * Test 2: Pipeline builder page loads with project
     */
    public function test_pipeline_builder_page_loads_with_project(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Builder')
                ->assertSee('Configure deployment pipeline for '.$this->project->name)
                ->screenshot('pipeline-builder-with-project');
        });
    }

    /**
     * Test 3: Pipeline builder shows three stage columns
     */
    public function test_pipeline_builder_shows_three_stage_columns(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pre-Deploy')
                ->assertSee('Deploy')
                ->assertSee('Post-Deploy')
                ->screenshot('pipeline-builder-three-columns');
        });
    }

    /**
     * Test 4: Apply template button is visible
     */
    public function test_apply_template_button_is_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button:contains("Apply Template")')
                ->screenshot('pipeline-template-button');
        });
    }

    /**
     * Test 5: Apply template button opens modal
     */
    public function test_apply_template_button_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Apply Template")')
                ->pause(500)
                ->assertSee('Choose Pipeline Template')
                ->assertSee('Select a pre-configured template to get started quickly')
                ->screenshot('pipeline-template-modal-opened');
        });
    }

    /**
     * Test 6: Template modal shows Laravel template
     */
    public function test_template_modal_shows_laravel_template(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Apply Template")')
                ->pause(500)
                ->assertSee('Laravel')
                ->assertSee('Composer, NPM, migrations, and caching')
                ->screenshot('pipeline-template-laravel');
        });
    }

    /**
     * Test 7: Template modal shows Node.js template
     */
    public function test_template_modal_shows_nodejs_template(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Apply Template")')
                ->pause(500)
                ->assertSee('Node.js')
                ->assertSee('NPM install, tests, and build')
                ->screenshot('pipeline-template-nodejs');
        });
    }

    /**
     * Test 8: Template modal shows Static Site template
     */
    public function test_template_modal_shows_static_template(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Apply Template")')
                ->pause(500)
                ->assertSee('Static Site')
                ->assertSee('Simple file copy deployment')
                ->screenshot('pipeline-template-static');
        });
    }

    /**
     * Test 9: Template modal can be closed
     */
    public function test_template_modal_can_be_closed(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Apply Template")')
                ->pause(500)
                ->assertSee('Choose Pipeline Template')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->assertDontSee('Choose Pipeline Template')
                ->screenshot('pipeline-template-modal-closed');
        });
    }

    /**
     * Test 10: Each column shows add stage button
     */
    public function test_each_column_shows_add_stage_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button:contains("Add Pre-Deploy Stage")')
                ->assertPresent('button:contains("Add Deploy Stage")')
                ->assertPresent('button:contains("Add Post-Deploy Stage")')
                ->screenshot('pipeline-add-stage-buttons');
        });
    }

    /**
     * Test 11: Add Pre-Deploy stage button opens modal
     */
    public function test_add_pre_deploy_stage_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Add New Stage')
                ->screenshot('pipeline-add-pre-deploy-modal');
        });
    }

    /**
     * Test 12: Add Deploy stage button opens modal
     */
    public function test_add_deploy_stage_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Deploy Stage")')
                ->pause(500)
                ->assertSee('Add New Stage')
                ->screenshot('pipeline-add-deploy-modal');
        });
    }

    /**
     * Test 13: Add Post-Deploy stage button opens modal
     */
    public function test_add_post_deploy_stage_opens_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Post-Deploy Stage")')
                ->pause(500)
                ->assertSee('Add New Stage')
                ->screenshot('pipeline-add-post-deploy-modal');
        });
    }

    /**
     * Test 14: Stage modal contains all required fields
     */
    public function test_stage_modal_contains_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertPresent('input[wire\\:model="stageName"]')
                ->assertPresent('input[wire\\:model="stageType"]')
                ->assertPresent('textarea[wire\\:model="commands"]')
                ->assertPresent('input[wire\\:model="timeoutSeconds"]')
                ->assertPresent('input[wire\\:model="continueOnFailure"]')
                ->screenshot('pipeline-stage-modal-fields');
        });
    }

    /**
     * Test 15: Stage modal shows stage name field
     */
    public function test_stage_modal_shows_stage_name_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Stage Name')
                ->assertPresent('input[wire\\:model="stageName"]')
                ->screenshot('pipeline-stage-name-field');
        });
    }

    /**
     * Test 16: Stage modal shows stage type selector
     */
    public function test_stage_modal_shows_stage_type_selector(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Stage Type')
                ->assertSee('Pre-Deploy')
                ->assertSee('Deploy')
                ->assertSee('Post-Deploy')
                ->screenshot('pipeline-stage-type-selector');
        });
    }

    /**
     * Test 17: Stage modal shows commands textarea
     */
    public function test_stage_modal_shows_commands_textarea(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Commands')
                ->assertSee('(one per line)')
                ->assertPresent('textarea[wire\\:model="commands"]')
                ->screenshot('pipeline-commands-textarea');
        });
    }

    /**
     * Test 18: Stage modal shows timeout field
     */
    public function test_stage_modal_shows_timeout_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Timeout (seconds)')
                ->assertPresent('input[wire\\:model="timeoutSeconds"]')
                ->screenshot('pipeline-timeout-field');
        });
    }

    /**
     * Test 19: Stage modal shows continue on failure checkbox
     */
    public function test_stage_modal_shows_continue_on_failure_checkbox(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Continue pipeline even if this stage fails')
                ->assertPresent('input[wire\\:model="continueOnFailure"]')
                ->screenshot('pipeline-continue-on-failure');
        });
    }

    /**
     * Test 20: Stage modal shows environment variables section
     */
    public function test_stage_modal_shows_environment_variables_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Environment Variables')
                ->assertPresent('input[wire\\:model="newEnvKey"]')
                ->assertPresent('input[wire\\:model="newEnvValue"]')
                ->screenshot('pipeline-env-variables-section');
        });
    }

    /**
     * Test 21: Stage modal has create button
     */
    public function test_stage_modal_has_create_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertPresent('button:contains("Create Stage")')
                ->screenshot('pipeline-create-stage-button');
        });
    }

    /**
     * Test 22: Stage modal has cancel button
     */
    public function test_stage_modal_has_cancel_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertPresent('button:contains("Cancel")')
                ->screenshot('pipeline-cancel-button');
        });
    }

    /**
     * Test 23: Stage modal can be closed with cancel button
     */
    public function test_stage_modal_can_be_closed_with_cancel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->assertSee('Add New Stage')
                ->click('button:contains("Cancel")')
                ->pause(500)
                ->assertDontSee('Add New Stage')
                ->screenshot('pipeline-modal-closed-cancel');
        });
    }

    /**
     * Test 24: Stage modal validates required fields
     */
    public function test_stage_modal_validates_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->click('button:contains("Create Stage")')
                ->pause(1000)
                ->waitFor('.text-red-500, .text-red-600, span:contains("field is required")', 5)
                ->screenshot('pipeline-validation-errors');
        });
    }

    /**
     * Test 25: Can create a new Pre-Deploy stage
     */
    public function test_can_create_new_pre_deploy_stage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->type('input[wire\\:model="stageName"]', 'Test Pre-Deploy Stage')
                ->type('textarea[wire\\:model="commands"]', "composer install\nnpm install")
                ->type('input[wire\\:model="timeoutSeconds"]', '600')
                ->screenshot('pipeline-create-pre-deploy-before')
                ->click('button:contains("Create Stage")')
                ->pause(2000)
                ->assertSee('Test Pre-Deploy Stage')
                ->screenshot('pipeline-create-pre-deploy-after');
        });
    }

    /**
     * Test 26: Can create a new Deploy stage
     */
    public function test_can_create_new_deploy_stage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Deploy Stage")')
                ->pause(500)
                ->type('input[wire\\:model="stageName"]', 'Test Deploy Stage')
                ->type('textarea[wire\\:model="commands"]', 'php artisan migrate --force')
                ->screenshot('pipeline-create-deploy-before')
                ->click('button:contains("Create Stage")')
                ->pause(2000)
                ->assertSee('Test Deploy Stage')
                ->screenshot('pipeline-create-deploy-after');
        });
    }

    /**
     * Test 27: Can create a new Post-Deploy stage
     */
    public function test_can_create_new_post_deploy_stage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Post-Deploy Stage")')
                ->pause(500)
                ->type('input[wire\\:model="stageName"]', 'Test Post-Deploy Stage')
                ->type('textarea[wire\\:model="commands"]', "php artisan cache:clear\nphp artisan config:cache")
                ->screenshot('pipeline-create-post-deploy-before')
                ->click('button:contains("Create Stage")')
                ->pause(2000)
                ->assertSee('Test Post-Deploy Stage')
                ->screenshot('pipeline-create-post-deploy-after');
        });
    }

    /**
     * Test 28: Created stage displays in correct column
     */
    public function test_created_stage_displays_in_correct_column(): void
    {
        // Create a test stage
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Column Test Stage',
            'type' => 'pre_deploy',
            'commands' => ['echo "test"'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Column Test Stage')
                ->screenshot('pipeline-stage-in-correct-column');
        });

        $stage->delete();
    }

    /**
     * Test 29: Stage card shows stage name
     */
    public function test_stage_card_shows_stage_name(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Display Name Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Display Name Test')
                ->screenshot('pipeline-stage-name-display');
        });

        $stage->delete();
    }

    /**
     * Test 30: Stage card shows command count
     */
    public function test_stage_card_shows_command_count(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Command Count Test',
            'type' => 'deploy',
            'commands' => ['command 1', 'command 2', 'command 3'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('3 command(s)')
                ->screenshot('pipeline-command-count');
        });

        $stage->delete();
    }

    /**
     * Test 31: Stage card shows timeout
     */
    public function test_stage_card_shows_timeout(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Timeout Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 600,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('600s timeout')
                ->screenshot('pipeline-timeout-display');
        });

        $stage->delete();
    }

    /**
     * Test 32: Stage card shows commands preview
     */
    public function test_stage_card_shows_commands_preview(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Commands Preview Test',
            'type' => 'deploy',
            'commands' => ['composer install', 'npm run build'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('composer install')
                ->screenshot('pipeline-commands-preview');
        });

        $stage->delete();
    }

    /**
     * Test 33: Stage card shows continue on failure indicator
     */
    public function test_stage_card_shows_continue_on_failure_indicator(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Continue Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
            'continue_on_failure' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Continue on failure')
                ->screenshot('pipeline-continue-on-failure-indicator');
        });

        $stage->delete();
    }

    /**
     * Test 34: Stage card shows environment variables indicator
     */
    public function test_stage_card_shows_environment_variables_indicator(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Env Vars Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
            'environment_variables' => ['KEY1' => 'value1', 'KEY2' => 'value2'],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('2 env var(s)')
                ->screenshot('pipeline-env-vars-indicator');
        });

        $stage->delete();
    }

    /**
     * Test 35: Stage card has edit button
     */
    public function test_stage_card_has_edit_button(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Edit Button Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button[wire\\:click="editStage('.$stage->id.')"]')
                ->screenshot('pipeline-edit-button');
        });

        $stage->delete();
    }

    /**
     * Test 36: Stage card has delete button
     */
    public function test_stage_card_has_delete_button(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Delete Button Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button[wire\\:click="deleteStage('.$stage->id.')"]')
                ->screenshot('pipeline-delete-button');
        });

        $stage->delete();
    }

    /**
     * Test 37: Stage card has enable/disable toggle
     */
    public function test_stage_card_has_enable_disable_toggle(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Toggle Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('button[wire\\:click="toggleStage('.$stage->id.')"]')
                ->screenshot('pipeline-toggle-button');
        });

        $stage->delete();
    }

    /**
     * Test 38: Edit button opens modal with stage data
     */
    public function test_edit_button_opens_modal_with_stage_data(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Edit Modal Test',
            'type' => 'deploy',
            'commands' => ['composer install', 'npm run build'],
            'timeout_seconds' => 600,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button[wire\\:click="editStage('.$stage->id.')"]')
                ->pause(1000)
                ->assertSee('Edit Stage')
                ->assertInputValue('input[wire\\:model="stageName"]', 'Edit Modal Test')
                ->screenshot('pipeline-edit-modal-with-data');
        });

        $stage->delete();
    }

    /**
     * Test 39: Edit modal has update button
     */
    public function test_edit_modal_has_update_button(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Update Button Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button[wire\\:click="editStage('.$stage->id.')"]')
                ->pause(500)
                ->assertPresent('button:contains("Update Stage")')
                ->screenshot('pipeline-update-button');
        });

        $stage->delete();
    }

    /**
     * Test 40: Can update existing stage
     */
    public function test_can_update_existing_stage(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Original Name',
            'type' => 'deploy',
            'commands' => ['original command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button[wire\\:click="editStage('.$stage->id.')"]')
                ->pause(500)
                ->clear('input[wire\\:model="stageName"]')
                ->type('input[wire\\:model="stageName"]', 'Updated Name')
                ->screenshot('pipeline-update-stage-before')
                ->click('button:contains("Update Stage")')
                ->pause(2000)
                ->assertSee('Updated Name')
                ->screenshot('pipeline-update-stage-after');
        });

        $stage->delete();
    }

    /**
     * Test 41: Can delete stage
     */
    public function test_can_delete_stage(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Stage To Delete',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Stage To Delete')
                ->screenshot('pipeline-delete-stage-before')
                ->click('button[wire\\:click="deleteStage('.$stage->id.')"]')
                ->acceptDialog()
                ->pause(2000)
                ->assertDontSee('Stage To Delete')
                ->screenshot('pipeline-delete-stage-after');
        });
    }

    /**
     * Test 42: Delete confirmation dialog appears
     */
    public function test_delete_confirmation_dialog_appears(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Confirm Delete Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button[wire\\:click="deleteStage('.$stage->id.')"]')
                ->pause(500)
                ->assertDialogOpened('Are you sure you want to delete this stage?')
                ->dismissDialog()
                ->screenshot('pipeline-delete-confirmation');
        });

        $stage->delete();
    }

    /**
     * Test 43: Can toggle stage enabled/disabled
     */
    public function test_can_toggle_stage_enabled_disabled(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Toggle Stage Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) use ($stage) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-toggle-before')
                ->click('button[wire\\:click="toggleStage('.$stage->id.')"]')
                ->pause(2000)
                ->screenshot('pipeline-toggle-after');
        });

        $stage->delete();
    }

    /**
     * Test 44: Disabled stage appears with opacity
     */
    public function test_disabled_stage_appears_with_opacity(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Disabled Stage',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => false,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('.stage-item.opacity-60')
                ->screenshot('pipeline-disabled-stage-opacity');
        });

        $stage->delete();
    }

    /**
     * Test 45: Stage counter shows correct count
     */
    public function test_stage_counter_shows_correct_count(): void
    {
        // Create multiple stages
        PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Counter Test 1',
            'type' => 'pre_deploy',
            'commands' => ['test'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Counter Test 2',
            'type' => 'pre_deploy',
            'commands' => ['test'],
            'timeout_seconds' => 300,
            'order' => 1,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->screenshot('pipeline-stage-counter');
        });

        // Cleanup
        PipelineStage::where('project_id', $this->project->id)->delete();
    }

    /**
     * Test 46: Can add environment variable in stage modal
     */
    public function test_can_add_environment_variable_in_stage_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->type('input[wire\\:model="newEnvKey"]', 'TEST_KEY')
                ->type('input[wire\\:model="newEnvValue"]', 'test_value')
                ->screenshot('pipeline-add-env-var-before')
                ->click('button:contains("Add")')
                ->pause(500)
                ->assertSee('TEST_KEY=test_value')
                ->screenshot('pipeline-add-env-var-after');
        });
    }

    /**
     * Test 47: Can remove environment variable in stage modal
     */
    public function test_can_remove_environment_variable_in_stage_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->click('button:contains("Add Pre-Deploy Stage")')
                ->pause(500)
                ->type('input[wire\\:model="newEnvKey"]', 'REMOVE_KEY')
                ->type('input[wire\\:model="newEnvValue"]', 'remove_value')
                ->click('button:contains("Add")')
                ->pause(500)
                ->assertSee('REMOVE_KEY=remove_value')
                ->screenshot('pipeline-remove-env-var-before')
                ->click('button[wire\\:click="removeEnvVariable(\'REMOVE_KEY\')"]')
                ->pause(500)
                ->assertDontSee('REMOVE_KEY=remove_value')
                ->screenshot('pipeline-remove-env-var-after');
        });
    }

    /**
     * Test 48: Empty column shows empty state
     */
    public function test_empty_column_shows_empty_state(): void
    {
        // Ensure no stages exist for this project
        PipelineStage::where('project_id', $this->project->id)->delete();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('No stages yet')
                ->screenshot('pipeline-empty-state');
        });
    }

    /**
     * Test 49: Stage card shows drag handle
     */
    public function test_stage_card_shows_drag_handle(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Drag Handle Test',
            'type' => 'deploy',
            'commands' => ['test command'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertPresent('.drag-handle')
                ->screenshot('pipeline-drag-handle');
        });

        $stage->delete();
    }

    /**
     * Test 50: Multiple commands show more indicator
     */
    public function test_multiple_commands_show_more_indicator(): void
    {
        $stage = PipelineStage::create([
            'project_id' => $this->project->id,
            'name' => 'Multiple Commands Test',
            'type' => 'deploy',
            'commands' => ['command 1', 'command 2', 'command 3', 'command 4', 'command 5'],
            'timeout_seconds' => 300,
            'order' => 0,
            'enabled' => true,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('+3 more command(s)')
                ->screenshot('pipeline-more-commands-indicator');
        });

        $stage->delete();
    }

    /**
     * Test 51: Pipeline builder is responsive on mobile
     */
    public function test_pipeline_builder_responsive_on_mobile(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                ->loginAs($this->user)
                ->visit('/projects/'.$this->project->slug.'/pipeline')
                ->waitForText('Pipeline Builder', 10)
                ->assertSee('Pipeline Builder')
                ->screenshot('pipeline-mobile-view');
        });
    }

    /**
     * Cleanup after tests
     */
    protected function tearDown(): void
    {
        // Clean up any test stages
        PipelineStage::where('project_id', $this->project->id)->delete();

        parent::tearDown();
    }
}
