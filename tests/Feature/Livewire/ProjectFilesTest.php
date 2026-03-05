<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\ProjectFiles;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFilesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->server = Server::factory()->create([
            'user_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
            'slug' => 'test-project',
        ]);

        $this->actingAs($this->user);
    }

    public function test_component_renders(): void
    {
        Process::fake([
            '*' => Process::result("drwxr-xr-x  2 user group 4096 2024-01-15 10:30 app\n-rw-r--r--  1 user group 1234 2024-01-15 10:30 composer.json\n", '', 0),
        ]);

        Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee(__('project_files.title'));
    }

    public function test_files_are_loaded_on_mount(): void
    {
        Process::fake([
            '*' => Process::result("drwxr-xr-x  2 user group 4096 2024-01-15 10:30 app\n-rw-r--r--  1 user group 1234 2024-01-15 10:30 composer.json\n", '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project]);

        $files = $component->get('files');
        $this->assertIsArray($files);
    }

    public function test_navigate_to_directory(): void
    {
        Process::fake([
            '*' => Process::result("drwxr-xr-x  2 user group 4096 2024-01-15 10:30 app\n-rw-r--r--  1 user group 1234 2024-01-15 10:30 composer.json\n", '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->call('navigateTo', 'app');

        $currentPath = $component->get('currentPath');
        $this->assertStringEndsWith('/app', $currentPath);
    }

    public function test_navigate_up(): void
    {
        Process::fake([
            '*' => Process::result("drwxr-xr-x  2 user group 4096 2024-01-15 10:30 app\n-rw-r--r--  1 user group 1234 2024-01-15 10:30 composer.json\n", '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->call('navigateTo', 'app')
            ->call('navigateUp');

        $basePath = config('devflow.projects_path', '/var/www').'/'.$this->project->slug;
        $currentPath = $component->get('currentPath');
        $this->assertEquals($basePath, $currentPath);
    }

    public function test_view_file(): void
    {
        Process::fake([
            '*ls*' => Process::result("-rw-r--r--  1 user group 100 2024-01-15 10:30 test.txt\n", '', 0),
            '*stat*' => Process::result('100', '', 0),
            '*file*' => Process::result('text/plain', '', 0),
            '*cat*' => Process::result('File content here', '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->call('viewFile', 'test.txt');

        $this->assertTrue($component->get('showFileModal'));
        $this->assertEquals('test.txt', $component->get('selectedFile'));
    }

    public function test_close_file_modal(): void
    {
        Process::fake([
            '*' => Process::result("-rw-r--r--  1 user group 100 2024-01-15 10:30 test.txt\n", '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->set('showFileModal', true)
            ->set('selectedFile', 'test.txt')
            ->call('closeFileModal');

        $this->assertFalse($component->get('showFileModal'));
        $this->assertNull($component->get('selectedFile'));
    }

    public function test_breadcrumbs_are_computed(): void
    {
        Process::fake([
            '*' => Process::result("drwxr-xr-x  2 user group 4096 2024-01-15 10:30 app\n", '', 0),
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $this->project])
            ->call('navigateTo', 'app');

        $currentPath = $component->get('currentPath');
        $this->assertStringEndsWith('/app', $currentPath);
        // Breadcrumbs are a computed property; verify path navigated correctly
        $component->assertSee('app');
    }

    public function test_error_displayed_when_no_server(): void
    {
        $projectNoServer = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => null,
            'slug' => 'no-server-project',
        ]);

        $component = Livewire::test(ProjectFiles::class, ['project' => $projectNoServer]);

        $error = $component->get('error');
        $this->assertNotEmpty($error);
    }
}
