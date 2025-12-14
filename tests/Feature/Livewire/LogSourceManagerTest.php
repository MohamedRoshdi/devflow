<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Logs\LogSourceManager;
use App\Models\LogSource;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\LogAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class LogSourceManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create(['name' => 'Production Server']);
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->assertStatus(200);
    }

    public function test_component_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->assertSet('showAddModal', false)
            ->assertSet('editingSourceId', null)
            ->assertSet('name', '')
            ->assertSet('type', 'file')
            ->assertSet('path', '')
            ->assertSet('project_id', null)
            ->assertSet('selectedTemplate', '')
            ->assertSet('testResult', null);
    }

    // ==================== SOURCES DISPLAY TESTS ====================

    public function test_displays_sources_for_server(): void
    {
        LogSource::factory()->count(3)->create([
            'server_id' => $this->server->id,
        ]);

        $otherServer = Server::factory()->create();
        LogSource::factory()->count(2)->create([
            'server_id' => $otherServer->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $sources = $component->viewData('sources');
        $this->assertCount(3, $sources);
    }

    public function test_sources_are_ordered_by_name(): void
    {
        LogSource::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Zebra Logs',
        ]);
        LogSource::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Alpha Logs',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $sources = $component->viewData('sources');
        $this->assertEquals('Alpha Logs', $sources->first()->name);
    }

    public function test_sources_include_project_relationship(): void
    {
        $project = Project::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Main Project',
        ]);

        LogSource::factory()->create([
            'server_id' => $this->server->id,
            'project_id' => $project->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $sources = $component->viewData('sources');
        $this->assertTrue($sources->first()->relationLoaded('project'));
        $this->assertEquals('Main Project', $sources->first()->project->name);
    }

    // ==================== ADD MODAL TESTS ====================

    public function test_can_open_add_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('openAddModal')
            ->assertSet('showAddModal', true);
    }

    public function test_open_add_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test')
            ->set('type', 'docker')
            ->set('path', '/test')
            ->call('openAddModal')
            ->assertSet('name', '')
            ->assertSet('type', 'file')
            ->assertSet('path', '')
            ->assertSet('editingSourceId', null);
    }

    public function test_can_close_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('openAddModal')
            ->assertSet('showAddModal', true)
            ->call('closeModal')
            ->assertSet('showAddModal', false);
    }

    public function test_close_modal_resets_form(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test')
            ->set('type', 'docker')
            ->set('path', '/test')
            ->call('closeModal')
            ->assertSet('name', '')
            ->assertSet('type', 'file')
            ->assertSet('path', '');
    }

    // ==================== ADD SOURCE TESTS ====================

    public function test_can_add_source(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Laravel Logs')
            ->set('type', 'file')
            ->set('path', '/var/www/app/storage/logs/laravel.log')
            ->call('addSource')
            ->assertSet('showAddModal', false)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'added successfully');
            });

        $this->assertDatabaseHas('log_sources', [
            'server_id' => $this->server->id,
            'name' => 'Laravel Logs',
            'type' => 'file',
            'path' => '/var/www/app/storage/logs/laravel.log',
            'is_active' => true,
        ]);
    }

    public function test_can_add_source_with_project(): void
    {
        $project = Project::factory()->create(['server_id' => $this->server->id]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Project Logs')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->set('project_id', $project->id)
            ->call('addSource');

        $this->assertDatabaseHas('log_sources', [
            'server_id' => $this->server->id,
            'project_id' => $project->id,
            'name' => 'Project Logs',
        ]);
    }

    public function test_add_source_validates_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', '')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->call('addSource')
            ->assertHasErrors(['name']);
    }

    public function test_add_source_validates_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test')
            ->set('type', 'invalid_type')
            ->set('path', '/var/log/app.log')
            ->call('addSource')
            ->assertHasErrors(['type']);
    }

    public function test_add_source_validates_path(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test')
            ->set('type', 'file')
            ->set('path', '')
            ->call('addSource')
            ->assertHasErrors(['path']);
    }

    // ==================== EDIT SOURCE TESTS ====================

    public function test_can_edit_source(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Original Name',
            'type' => 'file',
            'path' => '/original/path',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('editSource', $source->id)
            ->assertSet('showAddModal', true)
            ->assertSet('editingSourceId', $source->id)
            ->assertSet('name', 'Original Name')
            ->assertSet('type', 'file')
            ->assertSet('path', '/original/path');
    }

    public function test_can_update_source(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'Original Name',
            'type' => 'file',
            'path' => '/original/path',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('editSource', $source->id)
            ->set('name', 'Updated Name')
            ->set('path', '/updated/path')
            ->call('updateSource')
            ->assertSet('showAddModal', false)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'updated successfully');
            });

        $this->assertDatabaseHas('log_sources', [
            'id' => $source->id,
            'name' => 'Updated Name',
            'path' => '/updated/path',
        ]);
    }

    public function test_update_source_validates(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('editSource', $source->id)
            ->set('name', '')
            ->call('updateSource')
            ->assertHasErrors(['name']);
    }

    // ==================== TOGGLE SOURCE TESTS ====================

    public function test_can_toggle_source_off(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('toggleSource', $source->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'disabled');
            });

        $this->assertDatabaseHas('log_sources', [
            'id' => $source->id,
            'is_active' => false,
        ]);
    }

    public function test_can_toggle_source_on(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('toggleSource', $source->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'enabled');
            });

        $this->assertDatabaseHas('log_sources', [
            'id' => $source->id,
            'is_active' => true,
        ]);
    }

    // ==================== REMOVE SOURCE TESTS ====================

    public function test_can_remove_source(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('removeSource', $source->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'removed successfully');
            });

        $this->assertDatabaseMissing('log_sources', [
            'id' => $source->id,
        ]);
    }

    // ==================== TEMPLATE TESTS ====================

    public function test_can_select_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('selectTemplate', 'laravel')
            ->assertSet('selectedTemplate', 'laravel')
            ->assertSet('name', 'Laravel Application Logs')
            ->assertSet('type', 'file')
            ->assertSet('path', '/var/www/*/storage/logs/laravel.log');
    }

    public function test_can_select_nginx_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('selectTemplate', 'nginx_access')
            ->assertSet('name', 'Nginx Access Logs')
            ->assertSet('type', 'file')
            ->assertSet('path', '/var/log/nginx/access.log');
    }

    public function test_can_select_docker_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('selectTemplate', 'docker')
            ->assertSet('name', 'Docker Container Logs')
            ->assertSet('type', 'docker')
            ->assertSet('path', 'container_name');
    }

    public function test_templates_property_returns_all_templates(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $templates = $component->viewData('templates');
        $this->assertArrayHasKey('laravel', $templates);
        $this->assertArrayHasKey('nginx_access', $templates);
        $this->assertArrayHasKey('nginx_error', $templates);
        $this->assertArrayHasKey('php_fpm', $templates);
        $this->assertArrayHasKey('mysql', $templates);
        $this->assertArrayHasKey('system', $templates);
        $this->assertArrayHasKey('docker', $templates);
    }

    // ==================== TEST SOURCE TESTS ====================

    public function test_test_source_success(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLogFile')
                ->once()
                ->andReturn('Log content here');
        });

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test Logs')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->call('testSource')
            ->assertSet('testResult', 'success')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'Connection successful');
            });
    }

    public function test_test_source_no_content(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLogFile')
                ->once()
                ->andReturn('');
        });

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test Logs')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->call('testSource')
            ->assertSet('testResult', 'error')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'No logs found');
            });
    }

    public function test_test_source_exception(): void
    {
        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchLogFile')
                ->once()
                ->andThrow(new \Exception('Connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', 'Test Logs')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->call('testSource')
            ->assertSet('testResult', 'error')
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'Test failed');
            });
    }

    public function test_test_source_validates(): void
    {
        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->set('name', '')
            ->set('type', 'file')
            ->set('path', '/var/log/app.log')
            ->call('testSource')
            ->assertHasErrors(['name']);
    }

    // ==================== SYNC SOURCE TESTS ====================

    public function test_can_sync_source(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
            'is_active' => true,
        ]);

        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncLogs')
                ->once()
                ->andReturn([
                    'total_entries' => 50,
                    'success' => 1,
                    'failed' => 0,
                ]);
        });

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('syncSource', $source->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], '50 log entries');
            });
    }

    public function test_sync_source_handles_exception(): void
    {
        $source = LogSource::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->mock(LogAggregationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncLogs')
                ->once()
                ->andThrow(new \Exception('Sync failed'));
        });

        Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server])
            ->call('syncSource', $source->id)
            ->assertDispatched('notification', function ($name, $data): bool {
                return $data['type'] === 'error' &&
                    str_contains($data['message'], 'Sync failed');
            });
    }

    // ==================== PROJECTS COMPUTED PROPERTY TESTS ====================

    public function test_projects_property_returns_server_projects(): void
    {
        Project::factory()->count(2)->create(['server_id' => $this->server->id]);

        $otherServer = Server::factory()->create();
        Project::factory()->count(3)->create(['server_id' => $otherServer->id]);

        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $projects = $component->viewData('projects');
        $this->assertCount(2, $projects);
    }

    public function test_projects_are_ordered_by_name(): void
    {
        Project::factory()->create(['server_id' => $this->server->id, 'name' => 'Zebra']);
        Project::factory()->create(['server_id' => $this->server->id, 'name' => 'Alpha']);

        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $projects = $component->viewData('projects');
        $this->assertEquals('Alpha', $projects->first()->name);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_no_sources(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $sources = $component->viewData('sources');
        $this->assertCount(0, $sources);
    }

    public function test_handles_no_projects(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(LogSourceManager::class, ['server' => $this->server]);

        $projects = $component->viewData('projects');
        $this->assertCount(0, $projects);
    }
}
