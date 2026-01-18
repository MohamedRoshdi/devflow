<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackupSchedule;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled database backups';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Starting scheduled backup process...');

        // Find all active schedules that are due
        $dueSchedules = BackupSchedule::active()
            ->due()
            ->get();

        if ($dueSchedules->isEmpty()) {
            $this->info('No backups are due at this time.');

            return self::SUCCESS;
        }

        $this->info("Found {$dueSchedules->count()} backup(s) to run.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($dueSchedules as $schedule) {
            try {
                $this->info("Running backup for: {$schedule->database_name} (ID: {$schedule->id})");

                // Create the backup
                $backup = $backupService->createBackup($schedule);

                // Update schedule next run time
                $schedule->updateNextRun();

                // Clean up old backups
                $cleaned = $backupService->cleanupOldBackups($schedule);

                $this->info("Backup completed successfully. Cleaned up {$cleaned} old backup(s).");
                $successCount++;

                Log::info('Scheduled backup completed', [
                    'schedule_id' => $schedule->id,
                    'backup_id' => $backup->id,
                    'database' => $schedule->database_name,
                    'cleaned_count' => $cleaned,
                ]);

            } catch (\Exception $e) {
                $this->error("Backup failed for: {$schedule->database_name}");
                $this->error("Error: {$e->getMessage()}");
                $failureCount++;

                Log::error('Scheduled backup failed', [
                    'schedule_id' => $schedule->id,
                    'database' => $schedule->database_name,
                    'error' => $e->getMessage(),
                ]);

                // Still update next run time even if backup failed
                $schedule->updateNextRun();
            }
        }

        $this->info("Backup process completed: {$successCount} successful, {$failureCount} failed.");

        return self::SUCCESS;
    }
}
