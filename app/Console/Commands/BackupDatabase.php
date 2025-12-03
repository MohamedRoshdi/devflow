<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\{Project, BackupSchedule};
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database {project? : Project slug or ID}
                            {--type=manual : Backup type (manual, scheduled, pre_deploy)}
                            {--database= : Specific database name to backup}';

    /**
     * The console command description.
     */
    protected $description = 'Create manual database backup for one or all projects';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $projectIdentifier = $this->argument('project');
        $type = $this->option('type');
        $databaseName = $this->option('database');

        if ($projectIdentifier) {
            return $this->backupSingleProject($projectIdentifier, $type, $databaseName, $backupService);
        }

        return $this->backupAllProjects($type, $backupService);
    }

    /**
     * Backup a single project
     */
    protected function backupSingleProject(
        string $identifier,
        string $type,
        ?string $databaseName,
        DatabaseBackupService $backupService
    ): int {
        // Find project by slug or ID
        $project = is_numeric($identifier)
            ? Project::find($identifier)
            : Project::where('slug', $identifier)->first();

        if (!$project) {
            $this->error("Project not found: {$identifier}");
            return self::FAILURE;
        }

        $this->info("Backing up project: {$project->name}");

        // Get all backup schedules for this project
        $query = BackupSchedule::where('project_id', $project->id);

        if ($databaseName) {
            $query->where('database_name', $databaseName);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->warn("No backup schedules configured for this project.");
            return self::SUCCESS;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($schedules as $schedule) {
            try {
                $this->info("  Creating backup for: {$schedule->database_name}");

                $backup = $backupService->createBackup($schedule, $type);

                $this->info("  Backup completed: {$backup->file_name} ({$backup->file_size_human})");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("  Backup failed: {$e->getMessage()}");
                $failureCount++;

                Log::error('Manual backup failed', [
                    'project_id' => $project->id,
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\nBackup process completed:");
        $this->info("  Success: {$successCount}");
        $this->info("  Failed: {$failureCount}");

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Backup all projects
     */
    protected function backupAllProjects(string $type, DatabaseBackupService $backupService): int
    {
        $this->info('Backing up all projects...');

        $projects = Project::whereHas('backupSchedules')->with('backupSchedules')->get();

        if ($projects->isEmpty()) {
            $this->warn('No projects with backup schedules found.');
            return self::SUCCESS;
        }

        $totalSuccess = 0;
        $totalFailure = 0;

        foreach ($projects as $project) {
            $this->info("\nProject: {$project->name}");

            foreach ($project->backupSchedules as $schedule) {
                try {
                    $this->info("  Backing up: {$schedule->database_name}");

                    $backup = $backupService->createBackup($schedule, $type);

                    $this->info("  Completed: {$backup->file_size_human}");
                    $totalSuccess++;

                } catch (\Exception $e) {
                    $this->error("  Failed: {$e->getMessage()}");
                    $totalFailure++;
                }
            }
        }

        $this->info("\n\nTotal backups:");
        $this->info("  Success: {$totalSuccess}");
        $this->info("  Failed: {$totalFailure}");

        return $totalFailure > 0 ? self::FAILURE : self::SUCCESS;
    }
}
