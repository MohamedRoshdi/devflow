<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerBackupSchedule;
use App\Services\ServerBackupService;
use Illuminate\Support\Facades\Log;

class RunServerBackupsCommand extends Command
{
    protected $signature = 'server:backups';
    protected $description = 'Process all active server backup schedules';

    public function handle(ServerBackupService $backupService): int
    {
        $this->info('Processing server backup schedules...');

        $schedules = ServerBackupSchedule::where('is_active', true)->get();

        if ($schedules->isEmpty()) {
            $this->info('No active backup schedules found.');
            return self::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->isDue()) {
                continue;
            }

            $this->info("Running {$schedule->type} backup for server #{$schedule->server_id}...");

            try {
                $backup = match($schedule->type) {
                    'full' => $backupService->createFullBackup($schedule->server),
                    'incremental' => $backupService->createIncrementalBackup($schedule->server),
                    'snapshot' => $backupService->createSnapshot($schedule->server),
                };

                // Update last run time
                $schedule->update(['last_run_at' => now()]);

                // Upload to S3 if configured
                if ($schedule->storage_driver === 's3' && $backup->storage_driver === 'local') {
                    $this->info("Uploading backup to S3...");
                    $backupService->uploadToS3($backup);
                }

                // Cleanup old backups based on retention policy
                $this->cleanupOldBackups($schedule, $backupService);

                $this->info("✓ Backup completed for server #{$schedule->server_id}");
                $processedCount++;

            } catch (\Exception $e) {
                $this->error("✗ Backup failed for server #{$schedule->server_id}: {$e->getMessage()}");

                Log::error('Scheduled backup failed', [
                    'schedule_id' => $schedule->id,
                    'server_id' => $schedule->server_id,
                    'error' => $e->getMessage(),
                ]);

                $failedCount++;
            }
        }

        $this->info("\nBackup Summary:");
        $this->info("- Processed: {$processedCount}");
        $this->info("- Failed: {$failedCount}");

        return self::SUCCESS;
    }

    private function cleanupOldBackups(ServerBackupSchedule $schedule, ServerBackupService $backupService): void
    {
        $cutoffDate = now()->subDays($schedule->retention_days);

        $oldBackups = $schedule->server->backups()
            ->where('type', $schedule->type)
            ->where('status', 'completed')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        foreach ($oldBackups as $backup) {
            try {
                $backupService->deleteBackup($backup);
                $this->info("  Cleaned up old backup from {$backup->created_at->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->warn("  Failed to cleanup backup #{$backup->id}: {$e->getMessage()}");
            }
        }
    }
}
