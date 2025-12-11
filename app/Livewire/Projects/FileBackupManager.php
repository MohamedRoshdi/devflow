<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\FileBackup;
use App\Models\Project;
use App\Services\FileBackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileBackupManager extends Component
{
    public Project $project;

    public bool $showCreateModal = false;

    public bool $showRestoreModal = false;

    public bool $showManifestModal = false;

    public bool $showExcludePatternsModal = false;

    public string $backupType = 'full';

    public ?int $baseBackupId = null;

    public string $storageDisk = 'local';

    public ?int $selectedBackupId = null;

    public bool $overwriteOnRestore = false;

    /** @var array<string, mixed> */
    public array $manifest = [];

    /** @var array<int, string> */
    public array $excludePatterns = [];

    public string $newExcludePattern = '';

    public string $searchTerm = '';

    public string $filterType = 'all';

    public string $filterStatus = 'all';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->loadExcludePatterns();
    }

    #[Computed]
    public function backups()
    {
        return FileBackup::forProject($this->project->id)
            ->when($this->searchTerm, fn ($q) => $q->where('filename', 'like', "%{$this->searchTerm}%"))
            ->when($this->filterType !== 'all', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->with(['parentBackup', 'childBackups'])
            ->latest()
            ->get()
            ->map(function (FileBackup $backup) {
                return [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'type' => $backup->type,
                    'type_color' => $backup->type_color,
                    'status' => $backup->status,
                    'status_color' => $backup->status_color,
                    'size' => $backup->formatted_size,
                    'files_count' => number_format($backup->files_count),
                    'duration' => $backup->formatted_duration ?? '-',
                    'checksum' => $backup->checksum ? substr($backup->checksum, 0, 8).'...' : '-',
                    'created_at' => $backup->created_at?->format('Y-m-d H:i:s') ?? '-',
                    'created_at_human' => $backup->created_at?->diffForHumans() ?? '-',
                    'parent_backup_id' => $backup->parent_backup_id,
                    'has_children' => $backup->childBackups->isNotEmpty(),
                    'incremental_depth' => $backup->getIncrementalDepth(),
                    'storage_disk' => $backup->storage_disk,
                    'error_message' => $backup->error_message,
                ];
            });
    }

    #[Computed]
    public function fullBackups()
    {
        return FileBackup::forProject($this->project->id)
            ->full()
            ->completed()
            ->latest()
            ->get()
            ->map(fn ($backup) => [
                'id' => $backup->id,
                'label' => $backup->filename.' ('.($backup->created_at?->format('Y-m-d H:i') ?? 'Unknown').')',
            ]);
    }

    #[Computed]
    public function storageDisks()
    {
        return [
            ['value' => 'local', 'label' => 'Local Storage'],
            ['value' => 's3', 'label' => 'Amazon S3'],
            ['value' => 'gcs', 'label' => 'Google Cloud Storage'],
            ['value' => 'azure', 'label' => 'Azure Blob Storage'],
        ];
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->backupType = 'full';
        $this->baseBackupId = null;
        $this->storageDisk = 'local';
    }

    public function createBackup(FileBackupService $backupService): void
    {
        try {
            $this->validate([
                'backupType' => 'required|in:full,incremental',
                'storageDisk' => 'required|in:local,s3,gcs,azure',
                'baseBackupId' => 'required_if:backupType,incremental|nullable|exists:file_backups,id',
            ]);

            if ($this->backupType === 'full') {
                $backup = $backupService->createFullBackup($this->project, [
                    'storage_disk' => $this->storageDisk,
                    'exclude' => $this->excludePatterns,
                ]);
            } else {
                $baseBackup = FileBackup::findOrFail($this->baseBackupId);
                $backup = $backupService->createIncrementalBackup($this->project, $baseBackup, [
                    'storage_disk' => $this->storageDisk,
                    'exclude' => $this->excludePatterns,
                ]);
            }

            $this->showCreateModal = false;
            $this->dispatch('notification', type: 'success', message: 'File backup created successfully!');
            unset($this->backups);

        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to create backup: '.$e->getMessage());
            Log::error('File backup creation failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function openRestoreModal(int $backupId): void
    {
        $this->selectedBackupId = $backupId;
        $this->showRestoreModal = true;
        $this->overwriteOnRestore = false;
    }

    public function restoreBackup(FileBackupService $backupService): void
    {
        try {
            $backup = FileBackup::findOrFail($this->selectedBackupId);

            $backupService->restoreBackup($backup, $this->overwriteOnRestore);

            $this->showRestoreModal = false;
            $this->dispatch('notification', type: 'success', message: 'Files restored successfully!');

        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to restore backup: '.$e->getMessage());
            Log::error('File backup restore failed', [
                'backup_id' => $this->selectedBackupId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadBackup(int $backupId): StreamedResponse
    {
        try {
            $backup = FileBackup::findOrFail($backupId);

            if (! $backup->isCompleted()) {
                $this->dispatch('notification', type: 'error', message: 'Cannot download incomplete backup');

                return response()->streamDownload(function () {}, '');
            }

            return Storage::disk($backup->storage_disk)->download(
                $backup->storage_path,
                $backup->filename
            );

        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to download backup: '.$e->getMessage());

            return response()->streamDownload(function () {}, '');
        }
    }

    public function viewManifest(int $backupId): void
    {
        $backup = FileBackup::findOrFail($backupId);
        $this->manifest = $backup->manifest ?? [];
        $this->showManifestModal = true;
    }

    public function deleteBackup(int $backupId, FileBackupService $backupService): void
    {
        try {
            $backup = FileBackup::findOrFail($backupId);

            if ($backup->childBackups->isNotEmpty()) {
                $this->dispatch('notification',
                    type: 'warning',
                    message: 'This backup has '.$backup->childBackups->count().' incremental backup(s). They will also be deleted.'
                );
            }

            $backupService->deleteBackup($backup);

            $this->dispatch('notification', type: 'success', message: 'Backup deleted successfully!');
            unset($this->backups);

        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to delete backup: '.$e->getMessage());
        }
    }

    public function openExcludePatternsModal(): void
    {
        $this->loadExcludePatterns();
        $this->showExcludePatternsModal = true;
    }

    public function addExcludePattern(): void
    {
        if (empty($this->newExcludePattern)) {
            return;
        }

        if (! in_array($this->newExcludePattern, $this->excludePatterns)) {
            $this->excludePatterns[] = $this->newExcludePattern;
            $this->saveExcludePatterns();
        }

        $this->newExcludePattern = '';
    }

    public function removeExcludePattern(int $index): void
    {
        unset($this->excludePatterns[$index]);
        $this->excludePatterns = array_values($this->excludePatterns);
        $this->saveExcludePatterns();
    }

    public function resetExcludePatterns(FileBackupService $backupService): void
    {
        $this->excludePatterns = $backupService->getExcludePatterns($this->project, []);
        $this->saveExcludePatterns();
        $this->dispatch('notification', type: 'success', message: 'Exclude patterns reset to defaults');
    }

    private function loadExcludePatterns(): void
    {
        $metadata = $this->project->metadata ?? [];
        $this->excludePatterns = $metadata['backup_excludes'] ?? [];
    }

    private function saveExcludePatterns(): void
    {
        $metadata = $this->project->metadata ?? [];
        $metadata['backup_excludes'] = $this->excludePatterns;
        $this->project->update(['metadata' => $metadata]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.file-backup-manager');
    }
}
