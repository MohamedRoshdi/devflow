<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\BackupSchedule;
use App\Models\Project;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Log;

/**
 * Backup Creation Trait
 *
 * Provides backup creation functionality for database backup management.
 * Handles one-time backup creation and form management.
 */
trait WithBackupCreation
{
    public bool $showCreateBackupModal = false;

    public string $databaseType = 'mysql';

    public string $databaseName = '';

    public bool $isCreatingBackup = false;

    /**
     * Open create backup modal
     *
     * @return void
     */
    public function openCreateBackupModal(): void
    {
        $this->showCreateBackupModal = true;
        $this->databaseName = '';
        $this->databaseType = 'mysql';
    }

    /**
     * Create a one-time database backup
     *
     * @return void
     */
    public function createBackup(): void
    {
        $this->validate([
            'databaseName' => 'required|string|max:255',
            'databaseType' => 'required|in:mysql,postgresql,sqlite',
        ]);

        $this->isCreatingBackup = true;

        try {
            // Create a temporary schedule for one-time backup
            $schedule = new BackupSchedule([
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'database_type' => $this->databaseType,
                'database_name' => $this->databaseName,
                'frequency' => 'daily',
                'storage_disk' => 'local',
                'retention_days' => 30,
            ]);

            $backupService = app(DatabaseBackupService::class);
            $backupService->createBackup($schedule);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Database backup created successfully!',
            ]);

            $this->showCreateBackupModal = false;
            $this->resetBackupForm();
            unset($this->backups);

        } catch (\Exception $e) {
            Log::error('Failed to create backup', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to create backup: '.$e->getMessage(),
            ]);
        } finally {
            $this->isCreatingBackup = false;
        }
    }

    /**
     * Reset backup form
     *
     * @return void
     */
    protected function resetBackupForm(): void
    {
        $this->databaseName = '';
        $this->databaseType = 'mysql';
    }
}
