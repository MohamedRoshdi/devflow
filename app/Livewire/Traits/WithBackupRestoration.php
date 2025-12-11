<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Backup Restoration Trait
 *
 * Provides backup restoration, deletion, verification, and download functionality.
 * Handles all backup file operations and integrity checks.
 */
trait WithBackupRestoration
{
    public bool $showDeleteModal = false;

    public bool $showRestoreModal = false;

    public bool $showVerifyModal = false;

    public ?int $backupIdToDelete = null;

    public ?int $backupIdToRestore = null;

    public ?int $backupIdToVerify = null;

    public bool $isVerifying = false;

    /**
     * Confirm backup deletion
     *
     * @param int $backupId
     * @return void
     */
    public function confirmDeleteBackup(int $backupId): void
    {
        $this->backupIdToDelete = $backupId;
        $this->showDeleteModal = true;
    }

    /**
     * Delete a backup
     *
     * @return void
     */
    public function deleteBackup(): void
    {
        if (! $this->backupIdToDelete) {
            return;
        }

        try {
            $backup = DatabaseBackup::with(['project'])->findOrFail($this->backupIdToDelete);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $backupService->deleteBackup($backup);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Backup deleted successfully!',
            ]);

            $this->showDeleteModal = false;
            $this->backupIdToDelete = null;
            unset($this->backups);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete backup: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm backup restoration
     *
     * @param int $backupId
     * @return void
     */
    public function confirmRestoreBackup(int $backupId): void
    {
        $this->backupIdToRestore = $backupId;
        $this->showRestoreModal = true;
    }

    /**
     * Restore a backup
     *
     * @return void
     */
    public function restoreBackup(): void
    {
        if (! $this->backupIdToRestore) {
            return;
        }

        try {
            $backup = DatabaseBackup::with(['project'])->findOrFail($this->backupIdToRestore);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $backupService->restoreBackup($backup);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Database restored successfully!',
            ]);

            $this->showRestoreModal = false;
            $this->backupIdToRestore = null;

        } catch (\Exception $e) {
            Log::error('Failed to restore backup', [
                'backup_id' => $this->backupIdToRestore,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to restore backup: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Confirm backup verification
     *
     * @param int $backupId
     * @return void
     */
    public function confirmVerifyBackup(int $backupId): void
    {
        $this->backupIdToVerify = $backupId;
        $this->showVerifyModal = true;
    }

    /**
     * Verify backup integrity
     *
     * @return void
     */
    public function verifyBackup(): void
    {
        if (! $this->backupIdToVerify) {
            return;
        }

        $this->isVerifying = true;

        try {
            $backup = DatabaseBackup::with(['project'])->findOrFail($this->backupIdToVerify);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $isValid = $backupService->verifyBackup($backup);

            if ($isValid) {
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'Backup verification passed! Checksum is valid.',
                ]);
            } else {
                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Backup verification failed! Checksum mismatch detected.',
                ]);
            }

            $this->showVerifyModal = false;
            $this->backupIdToVerify = null;
            unset($this->backups);

        } catch (\Exception $e) {
            Log::error('Failed to verify backup', [
                'backup_id' => $this->backupIdToVerify,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to verify backup: '.$e->getMessage(),
            ]);
        } finally {
            $this->isVerifying = false;
        }
    }

    /**
     * Download a backup file
     *
     * @param int $backupId
     * @return StreamedResponse
     */
    public function downloadBackup(int $backupId): StreamedResponse
    {
        try {
            $backup = DatabaseBackup::with(['project'])->findOrFail($backupId);

            if ($backup->project_id !== $this->project->id) {
                throw new \Exception('Unauthorized');
            }

            $backupService = app(DatabaseBackupService::class);
            $filePath = $backupService->downloadBackup($backup);

            return response()->streamDownload(function () use ($filePath) {
                echo file_get_contents($filePath);
            }, $backup->file_name, [
                'Content-Type' => 'application/gzip',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to download backup: '.$e->getMessage(),
            ]);

            return response()->streamDownload(function () {}, 'error.txt');
        }
    }
}
