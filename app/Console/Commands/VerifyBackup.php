<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyBackup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:verify {backup? : Backup ID to verify}
                            {--all : Verify all unverified backups}
                            {--project= : Verify backups for specific project}';

    /**
     * The console command description.
     */
    protected $description = 'Verify database backup integrity using checksums';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $backupId = $this->argument('backup');
        $verifyAll = $this->option('all');
        $projectSlug = $this->option('project');

        if ($backupId) {
            return $this->verifySingleBackup((int)$backupId, $backupService);
        }

        if ($verifyAll || $projectSlug) {
            return $this->verifyMultipleBackups($projectSlug, $backupService);
        }

        $this->error('Please specify a backup ID, --all flag, or --project option');
        return self::FAILURE;
    }

    /**
     * Verify a single backup
     */
    protected function verifySingleBackup(int $backupId, DatabaseBackupService $backupService): int
    {
        $backup = DatabaseBackup::find($backupId);

        if (!$backup) {
            $this->error("Backup not found: {$backupId}");
            return self::FAILURE;
        }

        if ($backup->status !== 'completed') {
            $this->error("Backup is not completed (status: {$backup->status})");
            return self::FAILURE;
        }

        $this->info("Verifying backup: {$backup->file_name}");
        $this->info("Database: {$backup->database_name}");
        $this->info("Created: {$backup->created_at->format('Y-m-d H:i:s')}");

        try {
            $isValid = $backupService->verifyBackup($backup);

            if ($isValid) {
                $this->info("\n✓ Backup verification PASSED");
                $this->info("Checksum: {$backup->checksum}");
                $this->info("Verified at: {$backup->verified_at->format('Y-m-d H:i:s')}");
                return self::SUCCESS;
            } else {
                $this->error("\n✗ Backup verification FAILED");
                $this->error("Checksum mismatch detected!");
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("\n✗ Verification error: {$e->getMessage()}");

            Log::error('Backup verification failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Verify multiple backups
     */
    protected function verifyMultipleBackups(?string $projectSlug, DatabaseBackupService $backupService): int
    {
        $query = DatabaseBackup::where('status', 'completed');

        if ($projectSlug) {
            $query->whereHas('project', function ($q) use ($projectSlug) {
                $q->where('slug', $projectSlug);
            });
            $this->info("Verifying backups for project: {$projectSlug}");
        } else {
            $this->info('Verifying all unverified backups...');
            $query->whereNull('verified_at');
        }

        $backups = $query->orderBy('created_at', 'desc')->get();

        if ($backups->isEmpty()) {
            $this->warn('No backups found to verify.');
            return self::SUCCESS;
        }

        $this->info("Found {$backups->count()} backup(s) to verify\n");

        $successCount = 0;
        $failureCount = 0;

        $progressBar = $this->output->createProgressBar($backups->count());
        $progressBar->start();

        foreach ($backups as $backup) {
            try {
                $isValid = $backupService->verifyBackup($backup);

                if ($isValid) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $this->newLine();
                    $this->error("Failed: {$backup->file_name} - Checksum mismatch");
                }

            } catch (\Exception $e) {
                $failureCount++;
                $this->newLine();
                $this->error("Error: {$backup->file_name} - {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Verification completed:');
        $this->info("  Passed: {$successCount}");
        $this->info("  Failed: {$failureCount}");

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
