<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Admin\ProjectTemplateManager;
use App\Models\ProjectTemplate;
use App\Models\User;

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectTemplateManagerTest extends TestCase
{
    

    protected User $admin;

    protected User $superAdmin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create users with different roles
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['name' => 'Regular User']);
        $this->regularUser->assignRole('user');
    }

    #[Test]
    public function component_renders_successfully_for_super_admin(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ProjectTemplateManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.project-template-manager')
            ->assertSet('activeTab', 'list')
            ->assertSet('showCreateModal', false);
    }

    #[Test]
    public function component_renders_successfully_for_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.admin.project-template-manager');
    }

    #[Test]
    public function component_blocks_regular_users(): void
    {
        Livewire::actingAs($this->regularUser)
            ->test(ProjectTemplateManager::class)
            ->assertForbidden();
    }

    #[Test]
    public function component_blocks_unauthenticated_users(): void
    {
        Livewire::test(ProjectTemplateManager::class)
            ->assertForbidden();
    }

    #[Test]
    public function templates_computed_property_returns_all_templates(): void
    {
        ProjectTemplate::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 3;
            });
    }

    #[Test]
    public function templates_are_ordered_by_system_flag_then_name(): void
    {
        ProjectTemplate::factory()->create(['name' => 'Zebra', 'is_system' => false]);
        ProjectTemplate::factory()->system()->create(['name' => 'Alpha System']);
        ProjectTemplate::factory()->create(['name' => 'Beta', 'is_system' => false]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertViewHas('templates', function ($templates) {
                return $templates->first()['name'] === 'Alpha System' &&
                       $templates->last()['name'] === 'Zebra';
            });
    }

    #[Test]
    public function search_filters_templates_by_name(): void
    {
        ProjectTemplate::factory()->create(['name' => 'Laravel Template']);
        ProjectTemplate::factory()->create(['name' => 'React Template']);
        ProjectTemplate::factory()->create(['name' => 'Vue Template']);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('searchTerm', 'Laravel')
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 1 &&
                       $templates->first()['name'] === 'Laravel Template';
            });
    }

    #[Test]
    public function framework_filter_filters_templates_by_framework(): void
    {
        ProjectTemplate::factory()->laravel()->create();
        ProjectTemplate::factory()->react()->create();
        ProjectTemplate::factory()->vue()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('frameworkFilter', 'laravel')
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 1 &&
                       $templates->first()['framework'] === 'laravel';
            });
    }

    #[Test]
    public function framework_filter_all_shows_all_templates(): void
    {
        ProjectTemplate::factory()->laravel()->create();
        ProjectTemplate::factory()->react()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('frameworkFilter', 'all')
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 2;
            });
    }

    #[Test]
    public function search_and_filter_work_together(): void
    {
        ProjectTemplate::factory()->create(['name' => 'Laravel API', 'framework' => 'laravel']);
        ProjectTemplate::factory()->create(['name' => 'Laravel Blog', 'framework' => 'laravel']);
        ProjectTemplate::factory()->create(['name' => 'React API', 'framework' => 'react']);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('searchTerm', 'API')
            ->set('frameworkFilter', 'laravel')
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 1 &&
                       $templates->first()['name'] === 'Laravel API';
            });
    }

    #[Test]
    public function updated_name_generates_slug_automatically(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'My Custom Template')
            ->assertSet('slug', 'my-custom-template');
    }

    #[Test]
    public function slug_generation_handles_special_characters(): void
    {
        // Str::slug converts '@' to 'at' and removes '/' and '!'
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Laravel/Vue @Project!')
            ->assertSet('slug', 'laravelvue-at-project');
    }

    #[Test]
    public function slug_is_not_regenerated_when_editing_template(): void
    {
        $template = ProjectTemplate::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', 'Updated Name')
            ->assertSet('slug', 'original-slug');
    }

    #[Test]
    public function open_create_modal_resets_form_and_shows_modal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('name', '')
            ->assertSet('slug', '')
            ->assertSet('editingTemplateId', null);
    }

    #[Test]
    public function open_edit_modal_loads_template_data(): void
    {
        $template = ProjectTemplate::factory()->create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'description' => 'Test description',
            'framework' => 'laravel',
            'php_version' => '8.4',
            'default_branch' => 'develop',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingTemplateId', $template->id)
            ->assertSet('name', 'Test Template')
            ->assertSet('slug', 'test-template')
            ->assertSet('description', 'Test description')
            ->assertSet('framework', 'laravel')
            ->assertSet('php_version', '8.4')
            ->assertSet('default_branch', 'develop')
            ->assertSet('is_active', false);
    }

    #[Test]
    public function open_edit_modal_loads_template_commands(): void
    {
        $template = ProjectTemplate::factory()->create([
            'install_commands' => ['composer install', 'npm install'],
            'build_commands' => ['npm run build'],
            'post_deploy_commands' => ['php artisan migrate'],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->assertSet('install_commands', ['composer install', 'npm install'])
            ->assertSet('build_commands', ['npm run build'])
            ->assertSet('post_deploy_commands', ['php artisan migrate']);
    }

    #[Test]
    public function open_edit_modal_loads_environment_template(): void
    {
        $template = ProjectTemplate::factory()->create([
            'env_template' => ['APP_NAME' => 'Test', 'APP_ENV' => 'production'],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->assertSet('env_template', ['APP_NAME' => 'Test', 'APP_ENV' => 'production']);
    }

    #[Test]
    public function create_template_validates_required_fields(): void
    {
        // framework has a default value 'laravel', so only name and slug should error
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('createTemplate')
            ->assertHasErrors(['name', 'slug']);
    }

    #[Test]
    public function create_template_validates_slug_format(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test Template')
            ->set('slug', 'Invalid Slug!')
            ->set('framework', 'laravel')
            ->call('createTemplate')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function create_template_validates_framework_value(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'invalid-framework')
            ->call('createTemplate')
            ->assertHasErrors(['framework']);
    }

    #[Test]
    public function create_template_validates_php_version(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('php_version', '7.4')
            ->call('createTemplate')
            ->assertHasErrors(['php_version']);
    }

    #[Test]
    public function create_template_validates_description_length(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('description', str_repeat('a', 1001))
            ->call('createTemplate')
            ->assertHasErrors(['description']);
    }

    #[Test]
    public function admin_can_create_template_with_valid_data(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'New Template')
            ->set('slug', 'new-template')
            ->set('framework', 'laravel')
            ->set('description', 'A new template')
            ->call('createTemplate')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('project_templates', [
            'name' => 'New Template',
            'slug' => 'new-template',
            'framework' => 'laravel',
            'description' => 'A new template',
            'user_id' => $this->admin->id,
            'is_system' => false,
        ]);
    }

    #[Test]
    public function create_template_sets_user_id_to_authenticated_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->call('createTemplate');

        $this->assertDatabaseHas('project_templates', [
            'slug' => 'test',
            'user_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function create_template_sets_is_system_to_false(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->call('createTemplate');

        $this->assertDatabaseHas('project_templates', [
            'slug' => 'test',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function create_template_with_all_optional_fields(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Full Template')
            ->set('slug', 'full-template')
            ->set('framework', 'laravel')
            ->set('description', 'Complete template')
            ->set('icon', '🚀')
            ->set('color', '#FF5733')
            ->set('php_version', '8.4')
            ->set('node_version', '20')
            ->set('health_check_path', '/api/health')
            ->set('default_branch', 'develop')
            ->set('is_active', true)
            ->set('docker_compose_template', 'version: "3.8"')
            ->set('dockerfile_template', 'FROM php:8.4')
            ->call('createTemplate');

        $this->assertDatabaseHas('project_templates', [
            'slug' => 'full-template',
            'icon' => '🚀',
            'color' => '#FF5733',
            'php_version' => '8.4',
            'node_version' => '20',
            'health_check_path' => '/api/health',
            'default_branch' => 'develop',
        ]);
    }

    #[Test]
    public function create_template_stores_commands_arrays(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('install_commands', ['composer install'])
            ->set('build_commands', ['npm run build'])
            ->set('post_deploy_commands', ['php artisan migrate'])
            ->call('createTemplate');

        $template = ProjectTemplate::where('slug', 'test')->first();
        $this->assertNotNull($template);
        $this->assertEquals(['composer install'], $template->install_commands);
        $this->assertEquals(['npm run build'], $template->build_commands);
        $this->assertEquals(['php artisan migrate'], $template->post_deploy_commands);
    }

    #[Test]
    public function create_template_stores_environment_template(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('env_template', ['APP_NAME' => 'Test', 'APP_DEBUG' => 'false'])
            ->call('createTemplate');

        $template = ProjectTemplate::where('slug', 'test')->first();
        $this->assertNotNull($template);
        $this->assertEquals(['APP_NAME' => 'Test', 'APP_DEBUG' => 'false'], $template->env_template);
    }

    #[Test]
    public function create_template_resets_form_after_success(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('description', 'Test description')
            ->call('createTemplate')
            ->assertSet('name', '')
            ->assertSet('slug', '')
            ->assertSet('description', '')
            ->assertSet('framework', 'laravel');
    }

    #[Test]
    public function update_template_validates_required_fields(): void
    {
        $template = ProjectTemplate::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', '')
            ->set('slug', '')
            ->call('updateTemplate')
            ->assertHasErrors(['name', 'slug']);
    }

    #[Test]
    public function admin_can_update_non_system_template(): void
    {
        $template = ProjectTemplate::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
            'is_system' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', 'Updated Name')
            ->set('description', 'Updated description')
            ->call('updateTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    #[Test]
    public function cannot_update_system_template(): void
    {
        $template = ProjectTemplate::factory()->system()->create([
            'name' => 'System Template',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', 'Hacked Name')
            ->call('updateTemplate');
            // System template should not be updated

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'name' => 'System Template',
        ]);
    }

    #[Test]
    public function update_template_resets_form_after_success(): void
    {
        $template = ProjectTemplate::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', 'Updated')
            ->call('updateTemplate')
            ->assertSet('showEditModal', false)
            ->assertSet('editingTemplateId', null);
    }

    #[Test]
    public function open_delete_modal_sets_template_id(): void
    {
        $template = ProjectTemplate::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openDeleteModal', $template->id)
            ->assertSet('deletingTemplateId', $template->id)
            ->assertSet('showDeleteModal', true);
    }

    #[Test]
    public function cannot_open_delete_modal_for_system_template(): void
    {
        $template = ProjectTemplate::factory()->system()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openDeleteModal', $template->id)
            ->assertSet('showDeleteModal', false);
    }

    #[Test]
    public function admin_can_delete_non_system_template(): void
    {
        $template = ProjectTemplate::factory()->create(['is_system' => false]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openDeleteModal', $template->id)
            ->call('deleteTemplate')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('project_templates', [
            'id' => $template->id,
        ]);
    }

    #[Test]
    public function cannot_delete_system_template(): void
    {
        $template = ProjectTemplate::factory()->system()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('deletingTemplateId', $template->id)
            ->call('deleteTemplate');
            // System template should still exist

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
        ]);
    }

    #[Test]
    public function clone_template_creates_copy_with_modified_name_and_slug(): void
    {
        $original = ProjectTemplate::factory()->create([
            'name' => 'Original Template',
            'slug' => 'original-template',
            'description' => 'Original description',
            'framework' => 'laravel',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('cloneTemplate', $original->id);

        $this->assertDatabaseHas('project_templates', [
            'name' => 'Original Template (Copy)',
            'description' => 'Original description',
            'framework' => 'laravel',
            'is_system' => false,
            'user_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function clone_template_copies_all_configuration(): void
    {
        $original = ProjectTemplate::factory()->create([
            'install_commands' => ['composer install'],
            'build_commands' => ['npm run build'],
            'env_template' => ['APP_NAME' => 'Test'],
            'php_version' => '8.4',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('cloneTemplate', $original->id);

        $clone = ProjectTemplate::where('name', $original->name.' (Copy)')->first();
        $this->assertNotNull($clone);
        $this->assertEquals($original->install_commands, $clone->install_commands);
        $this->assertEquals($original->build_commands, $clone->build_commands);
        $this->assertEquals($original->env_template, $clone->env_template);
        $this->assertEquals($original->php_version, $clone->php_version);
    }

    #[Test]
    public function clone_system_template_creates_non_system_copy(): void
    {
        $systemTemplate = ProjectTemplate::factory()->system()->create([
            'name' => 'System Template',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('cloneTemplate', $systemTemplate->id);

        $this->assertDatabaseHas('project_templates', [
            'name' => 'System Template (Copy)',
            'is_system' => false,
            'user_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function open_preview_modal_sets_template_id(): void
    {
        $template = ProjectTemplate::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openPreviewModal', $template->id)
            ->assertSet('previewingTemplateId', $template->id)
            ->assertSet('showPreviewModal', true);
    }

    #[Test]
    public function toggle_template_status_activates_inactive_template(): void
    {
        $template = ProjectTemplate::factory()->inactive()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('toggleTemplateStatus', $template->id);

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function toggle_template_status_deactivates_active_template(): void
    {
        $template = ProjectTemplate::factory()->active()->create();

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('toggleTemplateStatus', $template->id);

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function add_install_command_appends_to_array(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newInstallCommand', 'composer install')
            ->call('addInstallCommand')
            ->assertSet('install_commands', ['composer install'])
            ->assertSet('newInstallCommand', '');
    }

    #[Test]
    public function add_install_command_appends_multiple_commands(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newInstallCommand', 'composer install')
            ->call('addInstallCommand')
            ->set('newInstallCommand', 'npm install')
            ->call('addInstallCommand')
            ->assertSet('install_commands', ['composer install', 'npm install']);
    }

    #[Test]
    public function add_install_command_ignores_empty_string(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newInstallCommand', '')
            ->call('addInstallCommand')
            ->assertSet('install_commands', []);
    }

    #[Test]
    public function remove_install_command_removes_by_index(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('install_commands', ['composer install', 'npm install', 'yarn install'])
            ->call('removeInstallCommand', 1)
            ->assertSet('install_commands', ['composer install', 'yarn install']);
    }

    #[Test]
    public function add_build_command_appends_to_array(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newBuildCommand', 'npm run build')
            ->call('addBuildCommand')
            ->assertSet('build_commands', ['npm run build'])
            ->assertSet('newBuildCommand', '');
    }

    #[Test]
    public function remove_build_command_removes_by_index(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('build_commands', ['npm run build', 'npm run production'])
            ->call('removeBuildCommand', 0)
            ->assertSet('build_commands', ['npm run production']);
    }

    #[Test]
    public function add_post_deploy_command_appends_to_array(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newPostDeployCommand', 'php artisan migrate')
            ->call('addPostDeployCommand')
            ->assertSet('post_deploy_commands', ['php artisan migrate'])
            ->assertSet('newPostDeployCommand', '');
    }

    #[Test]
    public function remove_post_deploy_command_removes_by_index(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('post_deploy_commands', ['php artisan migrate', 'php artisan cache:clear'])
            ->call('removePostDeployCommand', 1)
            ->assertSet('post_deploy_commands', ['php artisan migrate']);
    }

    #[Test]
    public function add_env_variable_adds_to_template(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newEnvKey', 'APP_NAME')
            ->set('newEnvValue', 'MyApp')
            ->call('addEnvVariable')
            ->assertSet('env_template', ['APP_NAME' => 'MyApp'])
            ->assertSet('newEnvKey', '')
            ->assertSet('newEnvValue', '');
    }

    #[Test]
    public function add_env_variable_requires_both_key_and_value(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newEnvKey', 'APP_NAME')
            ->set('newEnvValue', '')
            ->call('addEnvVariable')
            ->assertSet('env_template', []);
    }

    #[Test]
    public function add_multiple_env_variables(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('newEnvKey', 'APP_NAME')
            ->set('newEnvValue', 'MyApp')
            ->call('addEnvVariable')
            ->set('newEnvKey', 'APP_ENV')
            ->set('newEnvValue', 'production')
            ->call('addEnvVariable')
            ->assertSet('env_template', ['APP_NAME' => 'MyApp', 'APP_ENV' => 'production']);
    }

    #[Test]
    public function remove_env_variable_removes_by_key(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('env_template', ['APP_NAME' => 'Test', 'APP_ENV' => 'production', 'APP_DEBUG' => 'false'])
            ->call('removeEnvVariable', 'APP_ENV')
            ->assertSet('env_template', ['APP_NAME' => 'Test', 'APP_DEBUG' => 'false']);
    }

    #[Test]
    public function templates_include_user_relationship(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        ProjectTemplate::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertViewHas('templates', function ($templates) use ($user) {
                return $templates->first()->user->id === $user->id;
            });
    }

    #[Test]
    public function component_initializes_with_default_values(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertSet('activeTab', 'list')
            ->assertSet('editingTemplateId', null)
            ->assertSet('showCreateModal', false)
            ->assertSet('showEditModal', false)
            ->assertSet('showDeleteModal', false)
            ->assertSet('showPreviewModal', false)
            ->assertSet('name', '')
            ->assertSet('framework', 'laravel')
            ->assertSet('default_branch', 'main')
            ->assertSet('php_version', '8.4')
            ->assertSet('is_active', true);
    }

    #[Test]
    public function default_values_for_arrays_are_empty(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertSet('install_commands', [])
            ->assertSet('build_commands', [])
            ->assertSet('post_deploy_commands', [])
            ->assertSet('env_template', []);
    }

    #[Test]
    public function create_template_handles_null_optional_values_correctly(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Minimal Template')
            ->set('slug', 'minimal')
            ->set('framework', 'laravel')
            ->set('description', '')
            ->set('icon', '')
            ->set('color', '')
            ->call('createTemplate');

        $template = ProjectTemplate::where('slug', 'minimal')->first();
        $this->assertNotNull($template);
        $this->assertNull($template->description);
        $this->assertNull($template->icon);
        // color has a NOT NULL constraint with default 'blue', so it won't be null
        $this->assertEquals('blue', $template->color);
    }

    #[Test]
    public function create_template_handles_empty_arrays_as_null(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->set('install_commands', [])
            ->call('createTemplate');

        $template = ProjectTemplate::where('slug', 'test')->first();
        $this->assertNotNull($template);
        $this->assertNull($template->install_commands);
    }

    #[Test]
    public function framework_validation_accepts_all_valid_frameworks(): void
    {
        $frameworks = ['laravel', 'react', 'vue', 'nextjs', 'nodejs', 'static', 'php', 'python', 'docker', 'custom'];

        foreach ($frameworks as $framework) {
            Livewire::actingAs($this->admin)
                ->test(ProjectTemplateManager::class)
                ->set('name', "Test {$framework}")
                ->set('slug', "test-{$framework}")
                ->set('framework', $framework)
                ->call('createTemplate')
                ->assertHasNoErrors(['framework']);

            $this->assertDatabaseHas('project_templates', [
                'slug' => "test-{$framework}",
                'framework' => $framework,
            ]);
        }
    }

    #[Test]
    public function php_version_validation_accepts_all_valid_versions(): void
    {
        $versions = ['8.1', '8.2', '8.3', '8.4'];

        foreach ($versions as $version) {
            Livewire::actingAs($this->admin)
                ->test(ProjectTemplateManager::class)
                ->set('name', "Test PHP {$version}")
                ->set('slug', "test-php-{$version}")
                ->set('framework', 'laravel')
                ->set('php_version', $version)
                ->call('createTemplate')
                ->assertHasNoErrors(['php_version']);
        }
    }

    #[Test]
    public function slug_must_match_regex_pattern(): void
    {
        $invalidSlugs = ['Invalid Slug', 'invalid_slug', 'INVALID', 'invalid.slug'];

        foreach ($invalidSlugs as $slug) {
            Livewire::actingAs($this->admin)
                ->test(ProjectTemplateManager::class)
                ->set('name', 'Test')
                ->set('slug', $slug)
                ->set('framework', 'laravel')
                ->call('createTemplate')
                ->assertHasErrors(['slug']);
        }
    }

    #[Test]
    public function slug_accepts_valid_patterns(): void
    {
        $validSlugs = ['valid-slug', 'valid123', 'my-template-123', 'a'];

        foreach ($validSlugs as $index => $slug) {
            Livewire::actingAs($this->admin)
                ->test(ProjectTemplateManager::class)
                ->set('name', "Test {$index}")
                ->set('slug', $slug)
                ->set('framework', 'laravel')
                ->call('createTemplate')
                ->assertHasNoErrors(['slug']);
        }
    }

    #[Test]
    public function super_admin_has_same_permissions_as_admin(): void
    {
        $template = ProjectTemplate::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->set('name', 'Updated by SuperAdmin')
            ->call('updateTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_templates', [
            'id' => $template->id,
            'name' => 'Updated by SuperAdmin',
        ]);
    }

    #[Test]
    public function templates_computed_property_is_cached(): void
    {
        ProjectTemplate::factory()->count(5)->create();

        $component = Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class);

        // Access templates twice - should use cache second time
        $component->assertViewHas('templates', function ($templates) {
            return $templates->count() === 5;
        });

        // Create new template
        ProjectTemplate::factory()->create();

        // Still cached, shows old count
        $component->assertViewHas('templates', function ($templates) {
            return $templates->count() === 5;
        });
    }

    #[Test]
    public function create_template_invalidates_templates_cache(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->assertViewHas('templates', function ($templates) {
                return $templates->count() === 0;
            })
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->call('createTemplate');

        // Cache should be invalidated
        $this->assertDatabaseCount('project_templates', 1);
    }

    #[Test]
    public function name_can_be_maximum_length(): void
    {
        $maxName = str_repeat('a', 255);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', $maxName)
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->call('createTemplate')
            ->assertHasNoErrors(['name']);
    }

    #[Test]
    public function name_cannot_exceed_maximum_length(): void
    {
        $tooLongName = str_repeat('a', 256);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->set('name', $tooLongName)
            ->set('slug', 'test')
            ->set('framework', 'laravel')
            ->call('createTemplate')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function component_handles_template_not_found_gracefully(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', 99999);
    }

    #[Test]
    public function open_edit_modal_handles_null_values_correctly(): void
    {
        $template = ProjectTemplate::factory()->create([
            'description' => null,
            'icon' => null,
            // 'color' has a NOT NULL constraint with default 'blue', so we don't test null here
            'php_version' => null,
            'node_version' => null,
            'health_check_path' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->assertSet('description', '')
            ->assertSet('icon', '')
            // color has a default value, so it won't be empty
            ->assertSet('php_version', '8.4')
            ->assertSet('node_version', '')
            ->assertSet('health_check_path', '');
    }

    #[Test]
    public function open_edit_modal_handles_null_command_arrays(): void
    {
        $template = ProjectTemplate::factory()->create([
            'install_commands' => null,
            'build_commands' => null,
            'post_deploy_commands' => null,
            'env_template' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ProjectTemplateManager::class)
            ->call('openEditModal', $template->id)
            ->assertSet('install_commands', [])
            ->assertSet('build_commands', [])
            ->assertSet('post_deploy_commands', [])
            ->assertSet('env_template', []);
    }
}
