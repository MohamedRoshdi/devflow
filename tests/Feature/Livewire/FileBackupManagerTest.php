<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Projects\FileBackupManager;
use App\Models\FileBackup;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\FileBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FileBackupManagerTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

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
        ]);
    }

    private function mockBackupService(): void
    {
        $this->mock(FileBackupService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createFullBackup')->andReturn(
                FileBackup::factory()->create(['project_id' => $this->project->id])
            );
            $mock->shouldReceive('createIncrementalBackup')->andReturn(
                FileBackup::factory()->create(['project_id' => $this->project->id, 'type' => 'incremental'])
            );
            $mock->shouldReceive('restoreBackup')->andReturn(true);
            $mock->shouldReceive('deleteBackup')->andReturn(true);
            $mock->shouldReceive('getExcludePatterns')->andReturn(['vendor', 'node_modules', '.git']);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertStatus(200);
    }

    public function test_component_loads_project_on_mount(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('project.id', $this->project->id);
    }

    public function test_shows_existing_backups(): void
    {
        FileBackup::factory()->count(3)->create(['project_id' => $this->project->id]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertCount(3, $backups);
    }

    public function test_displays_empty_state_without_backups(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertCount(0, $backups);
    }

    // ==================== CREATE BACKUP TESTS ====================

    public function test_can_open_create_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('showCreateModal', false)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSet('backupType', 'full')
            ->assertSet('baseBackupId', null)
            ->assertSet('storageDisk', 'local');
    }

    public function test_can_create_full_backup(): void
    {
        $this->mockBackupService();

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('openCreateModal')
            ->set('backupType', 'full')
            ->set('storageDisk', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertDispatched('notification');
    }

    public function test_can_create_incremental_backup(): void
    {
        $this->mockBackupService();
        $baseBackup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'full',
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('openCreateModal')
            ->set('backupType', 'incremental')
            ->set('baseBackupId', $baseBackup->id)
            ->set('storageDisk', 'local')
            ->call('createBackup')
            ->assertSet('showCreateModal', false)
            ->assertDispatched('notification');
    }

    public function test_create_backup_validates_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('backupType', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['backupType']);
    }

    public function test_create_backup_validates_storage_disk(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('storageDisk', 'invalid')
            ->call('createBackup')
            ->assertHasErrors(['storageDisk']);
    }

    public function test_incremental_requires_base_backup(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('backupType', 'incremental')
            ->set('baseBackupId', null)
            ->call('createBackup')
            ->assertHasErrors(['baseBackupId']);
    }

    // ==================== RESTORE BACKUP TESTS ====================

    public function test_can_open_restore_modal(): void
    {
        $backup = FileBackup::factory()->create(['project_id' => $this->project->id]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('showRestoreModal', false)
            ->call('openRestoreModal', $backup->id)
            ->assertSet('showRestoreModal', true)
            ->assertSet('selectedBackupId', $backup->id)
            ->assertSet('overwriteOnRestore', false);
    }

    public function test_can_restore_backup(): void
    {
        $this->mockBackupService();
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('openRestoreModal', $backup->id)
            ->call('restoreBackup')
            ->assertSet('showRestoreModal', false)
            ->assertDispatched('notification');
    }

    public function test_can_restore_with_overwrite(): void
    {
        $this->mockBackupService();
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('openRestoreModal', $backup->id)
            ->set('overwriteOnRestore', true)
            ->call('restoreBackup')
            ->assertDispatched('notification');
    }

    // ==================== DELETE BACKUP TESTS ====================

    public function test_can_delete_backup(): void
    {
        $this->mockBackupService();
        $backup = FileBackup::factory()->create(['project_id' => $this->project->id]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('deleteBackup', $backup->id)
            ->assertDispatched('notification');
    }

    public function test_delete_warns_about_child_backups(): void
    {
        $this->mockBackupService();
        $parentBackup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'full',
        ]);
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'incremental',
            'parent_backup_id' => $parentBackup->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('deleteBackup', $parentBackup->id)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'warning';
            });
    }

    // ==================== VIEW MANIFEST TESTS ====================

    public function test_can_view_manifest(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'manifest' => ['files' => ['file1.txt', 'file2.txt']],
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('viewManifest', $backup->id)
            ->assertSet('showManifestModal', true)
            ->assertSet('manifest', ['files' => ['file1.txt', 'file2.txt']]);
    }

    public function test_manifest_is_empty_array_when_null(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'manifest' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('viewManifest', $backup->id)
            ->assertSet('manifest', []);
    }

    // ==================== EXCLUDE PATTERNS TESTS ====================

    public function test_can_open_exclude_patterns_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('showExcludePatternsModal', false)
            ->call('openExcludePatternsModal')
            ->assertSet('showExcludePatternsModal', true);
    }

    public function test_can_add_exclude_pattern(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('newExcludePattern', '*.log')
            ->call('addExcludePattern')
            ->assertSet('newExcludePattern', '')
            ->assertSet('excludePatterns', function ($patterns) {
                return in_array('*.log', $patterns);
            });
    }

    public function test_empty_pattern_is_not_added(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('newExcludePattern', '')
            ->call('addExcludePattern')
            ->assertSet('excludePatterns', []);
    }

    public function test_duplicate_pattern_is_not_added(): void
    {
        $this->project->update(['metadata' => ['backup_excludes' => ['vendor']]]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('newExcludePattern', 'vendor')
            ->call('addExcludePattern')
            ->assertSet('excludePatterns', function ($patterns) {
                return count(array_filter($patterns, fn ($p) => $p === 'vendor')) === 1;
            });
    }

    public function test_can_remove_exclude_pattern(): void
    {
        $this->project->update(['metadata' => ['backup_excludes' => ['vendor', 'node_modules']]]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('excludePatterns', ['vendor', 'node_modules'])
            ->call('removeExcludePattern', 0)
            ->assertSet('excludePatterns', ['node_modules']);
    }

    public function test_can_reset_exclude_patterns(): void
    {
        $this->mockBackupService();
        $this->project->update(['metadata' => ['backup_excludes' => ['custom']]]);

        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->call('resetExcludePatterns')
            ->assertDispatched('notification');
    }

    // ==================== FILTER TESTS ====================

    public function test_can_filter_by_search_term(): void
    {
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'filename' => 'backup-2024-01-01.zip',
        ]);
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'filename' => 'other-file.zip',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('searchTerm', 'backup-2024');

        $backups = $component->viewData('backups');
        $this->assertCount(1, $backups);
    }

    public function test_can_filter_by_type(): void
    {
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'full',
        ]);
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'incremental',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('filterType', 'full');

        $backups = $component->viewData('backups');
        $this->assertCount(1, $backups);
    }

    public function test_can_filter_by_status(): void
    {
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'completed',
        ]);
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'failed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('filterStatus', 'completed');

        $backups = $component->viewData('backups');
        $this->assertCount(1, $backups);
    }

    public function test_filter_all_shows_all_backups(): void
    {
        FileBackup::factory()->count(5)->create(['project_id' => $this->project->id]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->set('filterType', 'all')
            ->set('filterStatus', 'all');

        $backups = $component->viewData('backups');
        $this->assertCount(5, $backups);
    }

    // ==================== FULL BACKUPS LIST TESTS ====================

    public function test_full_backups_computed_property(): void
    {
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'full',
            'status' => 'completed',
        ]);
        FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'incremental',
            'status' => 'completed',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $fullBackups = $component->viewData('fullBackups');
        $this->assertCount(1, $fullBackups);
    }

    // ==================== STORAGE DISKS TESTS ====================

    public function test_storage_disks_computed_property(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $disks = $component->viewData('storageDisks');
        $this->assertCount(4, $disks);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project])
            ->assertSet('showCreateModal', false)
            ->assertSet('showRestoreModal', false)
            ->assertSet('showManifestModal', false)
            ->assertSet('showExcludePatternsModal', false)
            ->assertSet('backupType', 'full')
            ->assertSet('storageDisk', 'local')
            ->assertSet('filterType', 'all')
            ->assertSet('filterStatus', 'all')
            ->assertSet('searchTerm', '');
    }

    // ==================== PROJECT ISOLATION TESTS ====================

    public function test_backups_are_project_specific(): void
    {
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
        FileBackup::factory()->count(3)->create(['project_id' => $this->project->id]);
        FileBackup::factory()->count(5)->create(['project_id' => $otherProject->id]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $this->assertCount(3, $backups);
    }

    // ==================== BACKUP DATA TESTS ====================

    public function test_backup_data_includes_all_fields(): void
    {
        $backup = FileBackup::factory()->create([
            'project_id' => $this->project->id,
            'filename' => 'test-backup.zip',
            'type' => 'full',
            'status' => 'completed',
            'checksum' => 'abc123def456',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(FileBackupManager::class, ['project' => $this->project]);

        $backups = $component->viewData('backups');
        $firstBackup = $backups->first();

        $this->assertEquals($backup->id, $firstBackup['id']);
        $this->assertEquals('test-backup.zip', $firstBackup['filename']);
        $this->assertEquals('full', $firstBackup['type']);
        $this->assertEquals('completed', $firstBackup['status']);
    }
}
