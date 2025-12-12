<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Livewire\Traits\WithBackupCreation;
use App\Livewire\Traits\WithBackupRestoration;
use App\Livewire\Traits\WithBackupScheduleManagement;
use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Database Backup Manager Component
 *
 * Manages database backups and backup schedules for projects.
 * Provides functionality for creating, restoring, verifying, and downloading backups,
 * as well as managing automated backup schedules.
 *
 * This component has been refactored to use composition via traits:
 * - WithBackupCreation: Handles one-time backup creation
 * - WithBackupRestoration: Handles backup restore, delete, verify, and download
 * - WithBackupScheduleManagement: Handles backup schedule CRUD operations
 */
class DatabaseBackupManager extends Component
{
    use WithPagination;
    use WithBackupCreation;
    use WithBackupRestoration;
    use WithBackupScheduleManagement;

    public Project $project;

    /**
     * Validation rules for backup and schedule forms
     *
     * @return array<string, string>
     */
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

    /**
     * Mount component with project
     *
     * @param Project $project
     * @return void
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Get paginated backups for current project
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, DatabaseBackup>
     */
    #[Computed]
    public function backups()
    {
        return DatabaseBackup::where('project_id', $this->project->id)
            ->with(['project'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Get all backup schedules for current project
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BackupSchedule>
     */
    #[Computed]
    public function schedules()
    {
        return BackupSchedule::where('project_id', $this->project->id)
            ->with(['server', 'project'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get backup statistics for current project
     *
     * @return array<string, mixed>
     */
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

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
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

    /**
     * Render the component view
     *
     * @return \Illuminate\View\View
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.database-backup-manager', [
            'backups' => $this->backups,
            'schedules' => $this->schedules,
            'stats' => $this->stats,
        ]);
    }
}
