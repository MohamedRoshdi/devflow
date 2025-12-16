<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;


use PHPUnit\Framework\Attributes\Test;
use App\Livewire\Projects\ProjectConfiguration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectConfigurationTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Project',
            'slug' => 'original-project',
            'repository_url' => 'https://github.com/user/original-repo.git',
            'branch' => 'main',
            'framework' => 'laravel',
            'php_version' => '8.3',
            'node_version' => '20',
            'root_directory' => '/',
            'health_check_url' => 'https://example.com/health',
            'auto_deploy' => false,
        ]);
        $this->actingAs($this->user);
    }

    #[Test]
    public function component_renders_successfully_for_authenticated_users(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertViewIs('livewire.projects.project-configuration');
    }

    #[Test]
    public function component_loads_project_data_on_mount(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id)
            ->assertSet('name', 'Original Project')
            ->assertSet('slug', 'original-project')
            ->assertSet('repository_url', 'https://github.com/user/original-repo.git')
            ->assertSet('branch', 'main')
            ->assertSet('framework', 'laravel')
            ->assertSet('php_version', '8.3')
            ->assertSet('node_version', '20')
            ->assertSet('root_directory', '/')
            ->assertSet('health_check_url', 'https://example.com/health')
            ->assertSet('auto_deploy', false);
    }

    #[Test]
    public function component_loads_project_with_null_values(): void
    {
        $projectWithNulls = Project::factory()->create([
            'user_id' => $this->user->id,
            'repository_url' => null,
            'framework' => null,
            'health_check_url' => null,
        ]);

        Livewire::test(ProjectConfiguration::class, ['project' => $projectWithNulls])
            ->assertSet('repository_url', '')
            ->assertSet('framework', '')
            ->assertSet('health_check_url', '');
    }

    #[Test]
    public function updated_name_automatically_generates_slug(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'New Amazing Project')
            ->assertSet('slug', 'new-amazing-project');
    }

    #[Test]
    public function updated_name_handles_special_characters_in_slug(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Project @#$ With Special! Chars')
            ->assertSet('slug', 'project-with-special-chars');
    }

    #[Test]
    public function updated_name_handles_unicode_characters_in_slug(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Проект Тест مشروع')
            ->assertSet('slug', 'proiekt-tiest-mshrw');
    }

    #[Test]
    public function save_configuration_validates_required_name(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', '')
            ->call('saveConfiguration')
            ->assertHasErrors(['name' => 'required']);
    }

    #[Test]
    public function save_configuration_validates_name_max_length(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', str_repeat('a', 256))
            ->call('saveConfiguration')
            ->assertHasErrors(['name' => 'max']);
    }

    #[Test]
    public function save_configuration_validates_required_slug(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('slug', '')
            ->call('saveConfiguration')
            ->assertHasErrors(['slug' => 'required']);
    }

    #[Test]
    public function save_configuration_validates_slug_format(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('slug', 'Invalid Slug With Spaces')
            ->call('saveConfiguration')
            ->assertHasErrors(['slug' => 'regex']);
    }

    #[Test]
    public function save_configuration_validates_slug_with_uppercase(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('slug', 'InvalidSlugWithUppercase')
            ->call('saveConfiguration')
            ->assertHasErrors(['slug' => 'regex']);
    }

    #[Test]
    public function save_configuration_validates_slug_with_special_characters(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('slug', 'invalid_slug@special')
            ->call('saveConfiguration')
            ->assertHasErrors(['slug' => 'regex']);
    }

    #[Test]
    public function save_configuration_accepts_valid_slug_format(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Project')
            ->set('slug', 'valid-slug-123')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['slug']);

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'slug' => 'valid-slug-123',
        ]);
    }

    #[Test]
    public function save_configuration_validates_slug_uniqueness(): void
    {
        Project::factory()->create([
            'slug' => 'existing-project',
            'user_id' => $this->user->id,
        ]);

        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('slug', 'existing-project')
            ->call('saveConfiguration')
            ->assertHasErrors(['slug' => 'unique']);
    }

    #[Test]
    public function save_configuration_allows_keeping_same_slug(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Name')
            ->set('slug', 'original-project')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['slug']);

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'slug' => 'original-project',
        ]);
    }

    #[Test]
    public function save_configuration_ignores_soft_deleted_slug(): void
    {
        $deletedProject = Project::factory()->create([
            'slug' => 'deleted-project',
            'user_id' => $this->user->id,
        ]);
        $deletedProject->delete();

        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Test Project')
            ->set('slug', 'deleted-project')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['slug']);
    }

    #[Test]
    public function save_configuration_validates_repository_url_format_https(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', 'invalid-url')
            ->call('saveConfiguration')
            ->assertHasErrors(['repository_url' => 'regex']);
    }

    #[Test]
    public function save_configuration_accepts_https_repository_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', 'https://github.com/user/repo.git')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['repository_url']);
    }

    #[Test]
    public function save_configuration_accepts_ssh_repository_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', 'git@github.com:user/repo.git')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['repository_url']);
    }

    #[Test]
    public function save_configuration_accepts_gitlab_repository_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', 'https://gitlab.com/user/repo.git')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['repository_url']);
    }

    #[Test]
    public function save_configuration_accepts_bitbucket_repository_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', 'https://bitbucket.org/user/repo.git')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['repository_url']);
    }

    #[Test]
    public function save_configuration_accepts_empty_repository_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('repository_url', '')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['repository_url']);
    }

    #[Test]
    public function save_configuration_validates_required_branch(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('branch', '')
            ->call('saveConfiguration')
            ->assertHasErrors(['branch' => 'required']);
    }

    #[Test]
    public function save_configuration_validates_branch_max_length(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('branch', str_repeat('a', 256))
            ->call('saveConfiguration')
            ->assertHasErrors(['branch' => 'max']);
    }

    #[Test]
    public function save_configuration_validates_required_root_directory(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('root_directory', '')
            ->call('saveConfiguration')
            ->assertHasErrors(['root_directory' => 'required']);
    }

    #[Test]
    public function save_configuration_validates_health_check_url_format(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('health_check_url', 'invalid-url')
            ->call('saveConfiguration')
            ->assertHasErrors(['health_check_url' => 'url']);
    }

    #[Test]
    public function save_configuration_accepts_valid_health_check_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('health_check_url', 'https://example.com/api/health')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['health_check_url']);
    }

    #[Test]
    public function save_configuration_validates_health_check_url_max_length(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('health_check_url', 'https://example.com/' . str_repeat('a', 500))
            ->call('saveConfiguration')
            ->assertHasErrors(['health_check_url' => 'max']);
    }

    #[Test]
    public function save_configuration_accepts_empty_health_check_url(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('health_check_url', '')
            ->call('saveConfiguration')
            ->assertHasNoErrors(['health_check_url']);
    }

    #[Test]
    public function save_configuration_validates_auto_deploy_is_boolean(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('auto_deploy', 'not-a-boolean')
            ->call('saveConfiguration')
            ->assertHasErrors(['auto_deploy' => 'boolean']);
    }

    #[Test]
    public function save_configuration_accepts_true_for_auto_deploy(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('auto_deploy', true)
            ->call('saveConfiguration')
            ->assertHasNoErrors(['auto_deploy']);

        $this->assertTrue($this->project->fresh()->auto_deploy);
    }

    #[Test]
    public function save_configuration_accepts_false_for_auto_deploy(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('auto_deploy', false)
            ->call('saveConfiguration')
            ->assertHasNoErrors(['auto_deploy']);

        $this->assertFalse($this->project->fresh()->auto_deploy);
    }

    #[Test]
    public function save_configuration_updates_project_successfully(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Project Name')
            ->set('slug', 'updated-project-slug')
            ->set('repository_url', 'https://github.com/newuser/newrepo.git')
            ->set('branch', 'develop')
            ->set('framework', 'vue')
            ->set('php_version', '8.4')
            ->set('node_version', '22')
            ->set('root_directory', '/var/www')
            ->set('health_check_url', 'https://newdomain.com/health')
            ->set('auto_deploy', true)
            ->call('saveConfiguration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Updated Project Name',
            'slug' => 'updated-project-slug',
            'repository_url' => 'https://github.com/newuser/newrepo.git',
            'branch' => 'develop',
            'framework' => 'vue',
            'php_version' => '8.4',
            'node_version' => '22',
            'root_directory' => '/var/www',
            'health_check_url' => 'https://newdomain.com/health',
            'auto_deploy' => true,
        ]);
    }

    #[Test]
    public function save_configuration_stores_null_for_empty_optional_fields(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('branch', 'main')
            ->set('root_directory', '/')
            ->set('repository_url', '')
            ->set('framework', '')
            ->set('health_check_url', '')
            ->call('saveConfiguration')
            ->assertHasNoErrors();

        $freshProject = $this->project->fresh();
        $this->assertNull($freshProject->repository_url);
        $this->assertNull($freshProject->framework);
        $this->assertNull($freshProject->health_check_url);
    }

    #[Test]
    public function save_configuration_sets_success_flash_message(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Project')
            ->set('slug', 'updated-project')
            ->call('saveConfiguration')
            ->assertSessionHas('message', 'Project configuration updated successfully!');
    }

    #[Test]
    public function save_configuration_redirects_to_project_show(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Project')
            ->set('slug', 'updated-project')
            ->call('saveConfiguration')
            ->assertRedirect(route('projects.show', $this->project->fresh()));
    }

    #[Test]
    public function save_configuration_handles_exception_gracefully(): void
    {
        $this->project->delete();

        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Project')
            ->set('slug', 'updated-project')
            ->call('saveConfiguration')
            ->assertSessionHas('error');
    }

    #[Test]
    public function save_configuration_validates_all_fields_together(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', '')
            ->set('slug', 'INVALID SLUG')
            ->set('branch', '')
            ->set('repository_url', 'not-a-url')
            ->set('root_directory', '')
            ->set('health_check_url', 'not-a-url')
            ->set('auto_deploy', 'not-boolean')
            ->call('saveConfiguration')
            ->assertHasErrors([
                'name',
                'slug',
                'branch',
                'repository_url',
                'root_directory',
                'health_check_url',
                'auto_deploy',
            ]);
    }

    #[Test]
    public function component_displays_available_frameworks(): void
    {
        $component = Livewire::test(ProjectConfiguration::class, ['project' => $this->project]);

        $frameworks = $component->get('frameworks');

        $this->assertIsArray($frameworks);
        $this->assertArrayHasKey('laravel', $frameworks);
        $this->assertArrayHasKey('nodejs', $frameworks);
        $this->assertArrayHasKey('react', $frameworks);
        $this->assertArrayHasKey('vue', $frameworks);
        $this->assertArrayHasKey('nextjs', $frameworks);
        $this->assertArrayHasKey('nuxt', $frameworks);
        $this->assertArrayHasKey('static', $frameworks);
        $this->assertEquals('Laravel', $frameworks['laravel']);
    }

    #[Test]
    public function component_displays_available_php_versions(): void
    {
        $component = Livewire::test(ProjectConfiguration::class, ['project' => $this->project]);

        $phpVersions = $component->get('phpVersions');

        $this->assertIsArray($phpVersions);
        $this->assertArrayHasKey('8.4', $phpVersions);
        $this->assertArrayHasKey('8.3', $phpVersions);
        $this->assertArrayHasKey('8.2', $phpVersions);
        $this->assertArrayHasKey('8.1', $phpVersions);
        $this->assertArrayHasKey('8.0', $phpVersions);
        $this->assertArrayHasKey('7.4', $phpVersions);
    }

    #[Test]
    public function component_displays_available_node_versions(): void
    {
        $component = Livewire::test(ProjectConfiguration::class, ['project' => $this->project]);

        $nodeVersions = $component->get('nodeVersions');

        $this->assertIsArray($nodeVersions);
        $this->assertArrayHasKey('22', $nodeVersions);
        $this->assertArrayHasKey('20', $nodeVersions);
        $this->assertArrayHasKey('18', $nodeVersions);
        $this->assertArrayHasKey('16', $nodeVersions);
    }

    #[Test]
    public function save_configuration_allows_selecting_different_frameworks(): void
    {
        $frameworks = ['laravel', 'nodejs', 'react', 'vue', 'nextjs', 'nuxt', 'static'];

        foreach ($frameworks as $framework) {
            $project = Project::factory()->create(['user_id' => $this->user->id]);

            Livewire::test(ProjectConfiguration::class, ['project' => $project])
                ->set('framework', $framework)
                ->call('saveConfiguration')
                ->assertHasNoErrors(['framework']);

            $this->assertEquals($framework, $project->fresh()->framework);
        }
    }

    #[Test]
    public function save_configuration_allows_selecting_different_php_versions(): void
    {
        $phpVersions = ['8.4', '8.3', '8.2', '8.1', '8.0', '7.4'];

        foreach ($phpVersions as $version) {
            $project = Project::factory()->create(['user_id' => $this->user->id]);

            Livewire::test(ProjectConfiguration::class, ['project' => $project])
                ->set('php_version', $version)
                ->call('saveConfiguration')
                ->assertHasNoErrors(['php_version']);

            $this->assertEquals($version, $project->fresh()->php_version);
        }
    }

    #[Test]
    public function save_configuration_allows_selecting_different_node_versions(): void
    {
        $nodeVersions = ['22', '20', '18', '16'];

        foreach ($nodeVersions as $version) {
            $project = Project::factory()->create(['user_id' => $this->user->id]);

            Livewire::test(ProjectConfiguration::class, ['project' => $project])
                ->set('node_version', $version)
                ->call('saveConfiguration')
                ->assertHasNoErrors(['node_version']);

            $this->assertEquals($version, $project->fresh()->node_version);
        }
    }

    #[Test]
    public function save_configuration_allows_various_root_directory_formats(): void
    {
        $directories = ['/', '/var/www', '/app', './public', 'dist'];

        foreach ($directories as $directory) {
            $project = Project::factory()->create(['user_id' => $this->user->id]);

            Livewire::test(ProjectConfiguration::class, ['project' => $project])
                ->set('root_directory', $directory)
                ->call('saveConfiguration')
                ->assertHasNoErrors(['root_directory']);

            $this->assertEquals($directory, $project->fresh()->root_directory);
        }
    }

    #[Test]
    public function save_configuration_allows_various_branch_names(): void
    {
        $branches = ['main', 'master', 'develop', 'feature/new-feature', 'release-1.0', 'hotfix_bug'];

        foreach ($branches as $branch) {
            $project = Project::factory()->create(['user_id' => $this->user->id]);

            Livewire::test(ProjectConfiguration::class, ['project' => $project])
                ->set('branch', $branch)
                ->call('saveConfiguration')
                ->assertHasNoErrors(['branch']);

            $this->assertEquals($branch, $project->fresh()->branch);
        }
    }

    #[Test]
    public function project_id_is_locked_and_cannot_be_modified(): void
    {
        $component = Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->assertSet('projectId', $this->project->id);

        $originalProjectId = $component->get('projectId');

        $component->set('projectId', 999);

        $this->assertEquals($originalProjectId, $component->get('projectId'));
    }

    #[Test]
    public function component_loads_project_relationship(): void
    {
        $component = Livewire::test(ProjectConfiguration::class, ['project' => $this->project]);

        $loadedProject = $component->get('project');

        $this->assertInstanceOf(Project::class, $loadedProject);
        $this->assertEquals($this->project->id, $loadedProject->id);
    }

    #[Test]
    public function save_configuration_preserves_fields_not_in_form(): void
    {
        $this->project->update([
            'status' => 'running',
            'deployment_method' => 'docker',
            'user_id' => $this->user->id,
            'server_id' => 1,
        ]);

        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Updated Name')
            ->call('saveConfiguration');

        $freshProject = $this->project->fresh();
        $this->assertEquals('running', $freshProject->status);
        $this->assertEquals('docker', $freshProject->deployment_method);
        $this->assertEquals($this->user->id, $freshProject->user_id);
    }

    #[Test]
    public function save_configuration_with_minimum_required_fields(): void
    {
        Livewire::test(ProjectConfiguration::class, ['project' => $this->project])
            ->set('name', 'Minimal Project')
            ->set('slug', 'minimal-project')
            ->set('branch', 'main')
            ->set('root_directory', '/')
            ->set('repository_url', '')
            ->set('framework', '')
            ->set('health_check_url', '')
            ->call('saveConfiguration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Minimal Project',
            'slug' => 'minimal-project',
            'branch' => 'main',
            'root_directory' => '/',
        ]);
    }

    #[Test]
    public function component_default_values_match_model_defaults(): void
    {
        $newProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'php_version' => null,
            'node_version' => null,
        ]);

        $component = Livewire::test(ProjectConfiguration::class, ['project' => $newProject]);

        $this->assertEquals('8.3', $component->get('php_version'));
        $this->assertEquals('20', $component->get('node_version'));
    }
}
