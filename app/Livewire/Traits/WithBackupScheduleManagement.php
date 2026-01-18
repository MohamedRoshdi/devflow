<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\BackupSchedule;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Backup Schedule Management Trait
 *
 * Provides backup schedule creation, modification, and deletion functionality.
 * Handles schedule form management and validation.
 */
trait WithBackupScheduleManagement
{
    public bool $showScheduleModal = false;

    public string $scheduleDatabase = '';

    public string $scheduleDatabaseType = 'mysql';

    public string $frequency = 'daily';

    public string $time = '02:00';

    public int $dayOfWeek = 0;

    public int $dayOfMonth = 1;

    public int $retentionDays = 30;

    public string $storageDisk = 'local';

    public bool $isCreatingSchedule = false;

    /**
     * Open schedule modal
     *
     * @return void
     */
    public function openScheduleModal(): void
    {
        $this->showScheduleModal = true;
        $this->resetScheduleForm();
    }

    /**
     * Create a backup schedule
     *
     * @return void
     */
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

    /**
     * Toggle schedule active status
     *
     * @param int $scheduleId
     * @return void
     */
    public function toggleSchedule(int $scheduleId): void
    {
        try {
            $schedule = BackupSchedule::with(['project', 'server'])->findOrFail($scheduleId);

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

    /**
     * Delete a backup schedule
     *
     * @param int $scheduleId
     * @return void
     */
    public function deleteSchedule(int $scheduleId): void
    {
        try {
            $schedule = BackupSchedule::with(['project', 'server'])->findOrFail($scheduleId);

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

    /**
     * Reset schedule form
     *
     * @return void
     */
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
}
