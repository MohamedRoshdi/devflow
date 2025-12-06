<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupManager extends Component
{
    use WithPagination;

    public Project $project;

    // Modal states
    public bool $showCreateBackupModal = false;

    public bool $showScheduleModal = false;

    public bool $showDeleteModal = false;

    public bool $showRestoreModal = false;

    public bool $showVerifyModal = false;

    // Form properties for creating backup
    public string $databaseType = 'mysql';

    public string $databaseName = '';

    // Form properties for creating schedule
    public string $scheduleDatabase = '';

    public string $scheduleDatabaseType = 'mysql';

    public string $frequency = 'daily';

    public string $time = '02:00';

    public int $dayOfWeek = 0;

    public int $dayOfMonth = 1;

    public int $retentionDays = 30;

    public string $storageDisk = 'local';

    // Action tracking
    public ?int $backupIdToDelete = null;

    public ?int $backupIdToRestore = null;

    public ?int $backupIdToVerify = null;

    public bool $isCreatingBackup = false;

    public bool $isCreatingSchedule = false;

    public bool $isVerifying = false;

    protected function rules(): array
    {
        return [
            'databaseName' => 'required|string|max:255',
            'databaseType' => 'required|in:mysql,postgresql,sqlite',
            'scheduleDatabase' => 'required|string|max:255',
            'scheduleDatabaseType' => 'required|in:mysql,postgresql,sqlite',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'dayOfWeek' => 'nullable|integer|min:0|max:6',
            'dayOfMonth' => 'nullable|integer|min:1|max:31',
            'retentionDays' => 'required|integer|min:1|max:365',
            'storageDisk' => 'required|in:local,s3',
        ];
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    #[Computed]
    public function backups()
    {
        return DatabaseBackup::where('project_id', $this->project->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    #[Computed]
    public function schedules()
    {
        return BackupSchedule::where('project_id', $this->project->id)
            ->with('server')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $totalBackups = DatabaseBackup::where('project_id', $this->project->id)->count();
        $scheduledBackups = BackupSchedule::where('project_id', $this->project->id)
            ->where('is_active', true)
            ->count();

        $totalSize = DatabaseBackup::where('project_id', $this->project->id)
            ->where('status', 'completed')
            ->sum('file_size');

        $lastBackup = DatabaseBackup::where('project_id', $this->project->id)
            ->where('status', 'completed')
            ->latest('created_at')
            ->first();

        return [
            'total_backups' => $totalBackups,
            'scheduled_backups' => $scheduledBackups,
            'total_size' => $this->formatBytes($totalSize),
            'last_backup' => $lastBackup?->created_at?->diffForHumans() ?? 'Never',
        ];
    }

    public function openCreateBackupModal(): void
    {
        $this->showCreateBackupModal = true;
        $this->databaseName = '';
        $this->databaseType = 'mysql';
    }

    public function openScheduleModal(): void
    {
        $this->showScheduleModal = true;
        $this->resetScheduleForm();
    }

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

    public function createSchedule(): void
    {
        $rules = [
            'scheduleDatabase' => 'required|string|max:255',
            'scheduleDatabaseType' => 'required|in:mysql,postgresql,sqlite',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'retentionDays' => 'required|integer|min:1|max:365',
            'storageDisk' => 'required|in:local,s3',
        ];

        if ($this->frequency === 'weekly') {
            $rules['dayOfWeek'] = 'required|integer|min:0|max:6';
        }

        if ($this->frequency === 'monthly') {
            $rules['dayOfMonth'] = 'required|integer|min:1|max:31';
        }

        $this->validate($rules);

        $this->isCreatingSchedule = true;

        try {
            BackupSchedule::create([
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'database_type' => $this->scheduleDatabaseType,
                'database_name' => $this->scheduleDatabase,
                'frequency' => $this->frequency,
                'time' => $this->time.':00',
                'day_of_week' => $this->frequency === 'weekly' ? $this->dayOfWeek : null,
                'day_of_month' => $this->frequency === 'monthly' ? $this->dayOfMonth : null,
                'retention_days' => $this->retentionDays,
                'storage_disk' => $this->storageDisk,
                'is_active' => true,
            ]);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Backup schedule created successfully!',
            ]);

            $this->showScheduleModal = false;
            $this->resetScheduleForm();
            unset($this->schedules);

        } catch (\Exception $e) {
            Log::error('Failed to create schedule', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to create schedule: '.$e->getMessage(),
            ]);
        } finally {
            $this->isCreatingSchedule = false;
        }
    }

    public function toggleSchedule(int $scheduleId): void
    {
        try {
            $schedule = BackupSchedule::findOrFail($scheduleId);

            if ($schedule->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $schedule->update(['is_active' => ! $schedule->is_active]);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Schedule '.($schedule->is_active ? 'activated' : 'deactivated').' successfully!',
            ]);

            unset($this->schedules);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to toggle schedule: '.$e->getMessage(),
            ]);
        }
    }

    public function confirmDeleteBackup(int $backupId): void
    {
        $this->backupIdToDelete = $backupId;
        $this->showDeleteModal = true;
    }

    public function deleteBackup(): void
    {
        if (! $this->backupIdToDelete) {
            return;
        }

        try {
            $backup = DatabaseBackup::findOrFail($this->backupIdToDelete);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $backupService->deleteBackup($backup);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Backup deleted successfully!',
            ]);

            $this->showDeleteModal = false;
            $this->backupIdToDelete = null;
            unset($this->backups);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete backup: '.$e->getMessage(),
            ]);
        }
    }

    public function confirmRestoreBackup(int $backupId): void
    {
        $this->backupIdToRestore = $backupId;
        $this->showRestoreModal = true;
    }

    public function restoreBackup(): void
    {
        if (! $this->backupIdToRestore) {
            return;
        }

        try {
            $backup = DatabaseBackup::findOrFail($this->backupIdToRestore);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $backupService->restoreBackup($backup);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Database restored successfully!',
            ]);

            $this->showRestoreModal = false;
            $this->backupIdToRestore = null;

        } catch (\Exception $e) {
            Log::error('Failed to restore backup', [
                'backup_id' => $this->backupIdToRestore,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to restore backup: '.$e->getMessage(),
            ]);
        }
    }

    public function confirmVerifyBackup(int $backupId): void
    {
        $this->backupIdToVerify = $backupId;
        $this->showVerifyModal = true;
    }

    public function verifyBackup(): void
    {
        if (! $this->backupIdToVerify) {
            return;
        }

        $this->isVerifying = true;

        try {
            $backup = DatabaseBackup::findOrFail($this->backupIdToVerify);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $isValid = $backupService->verifyBackup($backup);

            if ($isValid) {
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'Backup verification passed! Checksum is valid.',
                ]);
            } else {
                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Backup verification failed! Checksum mismatch detected.',
                ]);
            }

            $this->showVerifyModal = false;
            $this->backupIdToVerify = null;
            unset($this->backups);

        } catch (\Exception $e) {
            Log::error('Failed to verify backup', [
                'backup_id' => $this->backupIdToVerify,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to verify backup: '.$e->getMessage(),
            ]);
        } finally {
            $this->isVerifying = false;
        }
    }

    public function downloadBackup(int $backupId): StreamedResponse
    {
        try {
            $backup = DatabaseBackup::findOrFail($backupId);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $filePath = $backupService->downloadBackup($backup);

            return response()->streamDownload(function () use ($filePath) {
                echo file_get_contents($filePath);
            }, $backup->file_name, [
                'Content-Type' => 'application/gzip',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to download backup: '.$e->getMessage(),
            ]);

            return response()->streamDownload(function () {}, 'error.txt');
        }
    }

    public function deleteSchedule(int $scheduleId): void
    {
        try {
            $schedule = BackupSchedule::findOrFail($scheduleId);

            if ($schedule->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $schedule->delete();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Schedule deleted successfully!',
            ]);

            unset($this->schedules);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete schedule: '.$e->getMessage(),
            ]);
        }
    }

    protected function resetBackupForm(): void
    {
        $this->databaseName = '';
        $this->databaseType = 'mysql';
    }

    protected function resetScheduleForm(): void
    {
        $this->scheduleDatabase = '';
        $this->scheduleDatabaseType = 'mysql';
        $this->frequency = 'daily';
        $this->time = '02:00';
        $this->dayOfWeek = 0;
        $this->dayOfMonth = 1;
        $this->retentionDays = 30;
        $this->storageDisk = 'local';
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function render()
    {
        return view('livewire.projects.database-backup-manager', [
            'backups' => $this->backups,
            'schedules' => $this->schedules,
            'stats' => $this->stats,
        ]);
    }
}
