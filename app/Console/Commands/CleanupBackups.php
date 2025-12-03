<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupBackups extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:cleanup {project? : Project slug or ID}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Apply retention policy and cleanup old database backups';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $projectIdentifier = $this->argument('project');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no backups will be deleted');
        }

        if ($projectIdentifier) {
            return $this->cleanupSingleProject($projectIdentifier, $backupService, $dryRun);
        }

        return $this->cleanupAllProjects($backupService, $dryRun);
    }

    /**
     * Cleanup backups for a single project
     */
    protected function cleanupSingleProject(
        string $identifier,
        DatabaseBackupService $backupService,
        bool $dryRun
    ): int {
        // Find project by slug or ID
        $project = is_numeric($identifier)
            ? Project::find($identifier)
            : Project::where('slug', $identifier)->first();

        if (!$project) {
            $this->error("Project not found: {$identifier}");
            return self::FAILURE;
        }

        $this->info("Cleaning up backups for: {$project->name}");

        try {
            if ($dryRun) {
                $this->simulateCleanup($project, $backupService);
            } else {
                $deletedCount = $backupService->applyRetentionPolicy($project);
                $this->info("Deleted {$deletedCount} old backup(s)");

                Log::info('Backup cleanup completed', [
                    'project_id' => $project->id,
                    'deleted_count' => $deletedCount,
                ]);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");

            Log::error('Backup cleanup failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Cleanup backups for all projects
     */
    protected function cleanupAllProjects(DatabaseBackupService $backupService, bool $dryRun): int
    {
        $this->info('Cleaning up backups for all projects...');

        $projects = Project::whereHas('backupSchedules')->get();

        if ($projects->isEmpty()) {
            $this->warn('No projects with backup schedules found.');
            return self::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($projects as $project) {
            $this->info("\nProject: {$project->name}");

            try {
                if ($dryRun) {
                    $this->simulateCleanup($project, $backupService);
                } else {
                    $deletedCount = $backupService->applyRetentionPolicy($project);
                    $this->info("  Deleted: {$deletedCount} backup(s)");
                    $totalDeleted += $deletedCount;
                }

            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
            }
        }

        if (!$dryRun) {
            $this->info("\nTotal backups deleted: {$totalDeleted}");

            Log::info('Global backup cleanup completed', [
                'total_deleted' => $totalDeleted,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Simulate cleanup without deleting
     */
    protected function simulateCleanup(Project $project, DatabaseBackupService $backupService): void
    {
        foreach ($project->backupSchedules as $schedule) {
            $backups = $project->databaseBackups()
                ->where('database_name', $schedule->database_name)
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->get();

            $this->info("  Database: {$schedule->database_name}");
            $this->info("  Total backups: {$backups->count()}");
            $this->info("  Retention: Daily={$schedule->retention_daily}, Weekly={$schedule->retention_weekly}, Monthly={$schedule->retention_monthly}");

            // Calculate which backups would be kept/deleted
            $toKeep = $this->calculateKeepCount($backups, $schedule->retention_daily, $schedule->retention_weekly, $schedule->retention_monthly);

            $wouldDelete = $backups->count() - $toKeep;
            $this->info("  Would keep: {$toKeep} backup(s)");
            $this->info("  Would delete: {$wouldDelete} backup(s)");
        }
    }

    /**
     * Calculate how many backups would be kept
     */
    protected function calculateKeepCount($backups, int $daily, int $weekly, int $monthly): int
    {
        // Simplified calculation - actual logic is in the service
        return min($backups->count(), max($daily, $weekly, $monthly));
    }
}
