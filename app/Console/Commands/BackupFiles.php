<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FileBackup;
use App\Models\Project;
use App\Services\FileBackupService;
use Illuminate\Console\Command;

class BackupFiles extends Command
{
    protected $signature = 'backup:files
                            {project? : The project slug to backup}
                            {--type=full : Backup type (full or incremental)}
                            {--base-backup= : Base backup ID for incremental backups}
                            {--storage=local : Storage disk (local, s3, gcs, azure)}
                            {--all : Backup all projects}';

    protected $description = 'Create file backups for projects';

    public function __construct(private readonly FileBackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type');
        $storageDisk = $this->option('storage') ?? 'local';
        $backupAll = $this->option('all');

        if (! in_array($type, ['full', 'incremental'])) {
            $this->error('Invalid backup type. Use "full" or "incremental".');

            return self::FAILURE;
        }

        // Get projects to backup
        if ($backupAll) {
            $projects = Project::whereHas('server')->get();
            $this->info('Backing up all '.$projects->count().' projects...');
        } else {
            $projectSlug = $this->argument('project');

            if (! $projectSlug) {
                $this->error('Please provide a project slug or use --all flag.');

                return self::FAILURE;
            }

            $project = Project::where('slug', $projectSlug)->first();

            if (! $project) {
                $this->error("Project '{$projectSlug}' not found.");

                return self::FAILURE;
            }

            $projects = collect([$project]);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($projects as $project) {
            try {
                $this->info("Starting {$type} backup for project: {$project->name}");

                if ($type === 'full') {
                    $backup = $this->createFullBackup($project, $storageDisk);
                } else {
                    $backup = $this->createIncrementalBackup($project, $storageDisk);
                }

                if ($backup->isCompleted()) {
                    $this->info('Backup completed successfully!');
                    $this->table(
                        ['Property', 'Value'],
                        [
                            ['Backup ID', $backup->id],
                            ['Type', $backup->type],
                            ['Size', $backup->formatted_size],
                            ['Files', number_format($backup->files_count)],
                            ['Duration', $backup->formatted_duration],
                            ['Storage', $backup->storage_disk],
                            ['Checksum', substr($backup->checksum, 0, 16).'...'],
                        ]
                    );
                    $successCount++;
                } else {
                    $this->error('Backup failed: '.($backup->error_message ?? 'Unknown error'));
                    $failedCount++;
                }

            } catch (\Exception $e) {
                $this->error("Failed to backup project {$project->name}: ".$e->getMessage());
                $failedCount++;
            }

            if ($projects->count() > 1) {
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info('Backup Summary:');
        $this->info("  Successful: {$successCount}");
        if ($failedCount > 0) {
            $this->error("  Failed: {$failedCount}");
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function createFullBackup(Project $project, string $storageDisk): FileBackup
    {
        $bar = $this->output->createProgressBar(4);
        $bar->setFormat('  [%bar%] %message%');

        $bar->setMessage('Creating backup record...');
        $bar->start();

        $backup = $this->backupService->createFullBackup($project, [
            'storage_disk' => $storageDisk,
        ]);

        $bar->advance();
        $bar->setMessage('Backup completed!');
        $bar->finish();
        $this->newLine();

        return $backup;
    }

    private function createIncrementalBackup(Project $project, string $storageDisk): FileBackup
    {
        // Find the latest full backup
        $baseBackupId = $this->option('base-backup');

        if ($baseBackupId) {
            $baseBackup = FileBackup::find($baseBackupId);

            if (! $baseBackup) {
                throw new \RuntimeException("Base backup with ID {$baseBackupId} not found.");
            }

            if ($baseBackup->project_id !== $project->id) {
                throw new \RuntimeException('Base backup does not belong to this project.');
            }
        } else {
            // Find latest full backup for this project
            $baseBackup = FileBackup::forProject($project->id)
                ->full()
                ->completed()
                ->latest()
                ->first();

            if (! $baseBackup) {
                throw new \RuntimeException('No completed full backup found. Please create a full backup first.');
            }

            $createdAt = $baseBackup->created_at?->diffForHumans() ?? 'unknown time';
            $this->info("Using base backup: {$baseBackup->id} (created {$createdAt})");
        }

        $bar = $this->output->createProgressBar(4);
        $bar->setFormat('  [%bar%] %message%');

        $bar->setMessage('Creating incremental backup...');
        $bar->start();

        $backup = $this->backupService->createIncrementalBackup($project, $baseBackup, [
            'storage_disk' => $storageDisk,
        ]);

        $bar->advance();
        $bar->setMessage('Incremental backup completed!');
        $bar->finish();
        $this->newLine();

        return $backup;
    }
}
