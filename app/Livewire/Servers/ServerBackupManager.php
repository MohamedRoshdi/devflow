<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Models\ServerBackup;
use App\Models\ServerBackupSchedule;
use App\Services\ServerBackupService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ServerBackupManager extends Component
{
    use WithPagination;

    public Server $server;

    public bool $showCreateModal = false;

    public bool $showScheduleModal = false;

    // Create Backup Form
    public string $backupType = 'full';

    public string $storageDriver = 'local';

    // Schedule Form
    public string $scheduleType = 'full';

    public string $scheduleFrequency = 'daily';

    public string $scheduleTime = '02:00';

    public ?int $scheduleDayOfWeek = null;

    public ?int $scheduleDayOfMonth = null;

    public int $retentionDays = 30;

    public string $scheduleStorageDriver = 'local';

    /** @var array<string, string> */
    protected $listeners = ['backupCreated' => '$refresh'];

    public function mount(Server $server)
    {
        $this->server = $server;
    }

    public function createBackup()
    {
        $this->validate([
            'backupType' => 'required|in:full,incremental,snapshot',
            'storageDriver' => 'required|in:local,s3',
        ]);

        try {
            $backupService = app(ServerBackupService::class);

            // Run backup in background
            dispatch(function () use ($backupService) {
                try {
                    match ($this->backupType) {
                        'full' => $backupService->createFullBackup($this->server),
                        'incremental' => $backupService->createIncrementalBackup($this->server),
                        'snapshot' => $backupService->createSnapshot($this->server),
                    };
                } catch (\Exception $e) {
                    Log::error('Background backup failed', [
                        'server_id' => $this->server->id,
                        'type' => $this->backupType,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            session()->flash('message', 'Backup started successfully. This may take several minutes.');
            $this->showCreateModal = false;
            $this->reset(['backupType', 'storageDriver']);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start backup: '.$e->getMessage());
            Log::error('Failed to create backup', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteBackup(int $backupId)
    {
        try {
            $backup = ServerBackup::findOrFail($backupId);

            if ($backup->server_id !== $this->server->id) {
                throw new \Exception('Backup does not belong to this server');
            }

            $backupService = app(ServerBackupService::class);
            $backupService->deleteBackup($backup);

            session()->flash('message', 'Backup deleted successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete backup: '.$e->getMessage());
            Log::error('Failed to delete backup', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function restoreBackup(int $backupId)
    {
        try {
            $backup = ServerBackup::findOrFail($backupId);

            if ($backup->server_id !== $this->server->id) {
                throw new \Exception('Backup does not belong to this server');
            }

            if (! $backup->isCompleted()) {
                throw new \Exception('Cannot restore incomplete backup');
            }

            $backupService = app(ServerBackupService::class);

            // Run restoration in background
            dispatch(function () use ($backupService, $backup) {
                try {
                    $backupService->restoreBackup($backup);
                } catch (\Exception $e) {
                    Log::error('Background restore failed', [
                        'backup_id' => $backup->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            session()->flash('info', 'Backup restoration started. This may take several minutes and will require a server reboot.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restore backup: '.$e->getMessage());
            Log::error('Failed to restore backup', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function createSchedule()
    {
        $rules = [
            'scheduleType' => 'required|in:full,incremental,snapshot',
            'scheduleFrequency' => 'required|in:daily,weekly,monthly',
            'scheduleTime' => 'required|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'retentionDays' => 'required|integer|min:1|max:365',
            'scheduleStorageDriver' => 'required|in:local,s3',
        ];

        if ($this->scheduleFrequency === 'weekly') {
            $rules['scheduleDayOfWeek'] = 'required|integer|min:0|max:6';
        }

        if ($this->scheduleFrequency === 'monthly') {
            $rules['scheduleDayOfMonth'] = 'required|integer|min:1|max:31';
        }

        $this->validate($rules);

        try {
            ServerBackupSchedule::create([
                'server_id' => $this->server->id,
                'type' => $this->scheduleType,
                'frequency' => $this->scheduleFrequency,
                'time' => $this->scheduleTime,
                'day_of_week' => $this->scheduleDayOfWeek,
                'day_of_month' => $this->scheduleDayOfMonth,
                'retention_days' => $this->retentionDays,
                'storage_driver' => $this->scheduleStorageDriver,
                'is_active' => true,
            ]);

            session()->flash('message', 'Backup schedule created successfully.');
            $this->showScheduleModal = false;
            $this->resetScheduleForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create schedule: '.$e->getMessage());
            Log::error('Failed to create backup schedule', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function toggleSchedule(int $scheduleId)
    {
        try {
            $schedule = ServerBackupSchedule::findOrFail($scheduleId);

            if ($schedule->server_id !== $this->server->id) {
                throw new \Exception('Schedule does not belong to this server');
            }

            $schedule->update(['is_active' => ! $schedule->is_active]);

            session()->flash('message', 'Schedule '.($schedule->is_active ? 'activated' : 'deactivated').' successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to toggle schedule: '.$e->getMessage());
        }
    }

    public function deleteSchedule(int $scheduleId)
    {
        try {
            $schedule = ServerBackupSchedule::findOrFail($scheduleId);

            if ($schedule->server_id !== $this->server->id) {
                throw new \Exception('Schedule does not belong to this server');
            }

            $schedule->delete();

            session()->flash('message', 'Schedule deleted successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete schedule: '.$e->getMessage());
        }
    }

    public function uploadToS3(int $backupId)
    {
        try {
            $backup = ServerBackup::findOrFail($backupId);

            if ($backup->server_id !== $this->server->id) {
                throw new \Exception('Backup does not belong to this server');
            }

            $backupService = app(ServerBackupService::class);
            $backupService->uploadToS3($backup);

            session()->flash('message', 'Backup uploaded to S3 successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload backup: '.$e->getMessage());
        }
    }

    private function resetScheduleForm()
    {
        $this->reset([
            'scheduleType',
            'scheduleFrequency',
            'scheduleTime',
            'scheduleDayOfWeek',
            'scheduleDayOfMonth',
            'retentionDays',
            'scheduleStorageDriver',
        ]);

        $this->scheduleType = 'full';
        $this->scheduleFrequency = 'daily';
        $this->scheduleTime = '02:00';
        $this->retentionDays = 30;
        $this->scheduleStorageDriver = 'local';
    }

    public function render(): \Illuminate\View\View
    {
        $backups = ServerBackup::where('server_id', $this->server->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $schedules = ServerBackupSchedule::where('server_id', $this->server->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.servers.server-backup-manager', [
            'backups' => $backups,
            'schedules' => $schedules,
        ]);
    }
}
