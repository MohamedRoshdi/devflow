<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Project, FileBackup, Server};
use Illuminate\Support\Facades\{Storage, Log};
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class FileBackupService
{
    private array $defaultExcludes = [
        'storage/logs/*',
        'storage/framework/cache/*',
        'storage/framework/sessions/*',
        'storage/framework/views/*',
        'node_modules/*',
        'vendor/*',
        '.git/*',
        '*.log',
        '.env',
        '.env.*',
    ];

    /**
     * Create a full backup of project files
     */
    public function createFullBackup(Project $project, array $options = []): FileBackup
    {
        $sourcePath = $options['source_path'] ?? "/var/www/{$project->slug}";
        $storageDisk = $options['storage_disk'] ?? 'local';
        $excludePatterns = $this->getExcludePatterns($project, $options['exclude'] ?? []);

        $backup = FileBackup::create([
            'project_id' => $project->id,
            'filename' => $this->generateFilename($project, 'full'),
            'type' => 'full',
            'source_path' => $sourcePath,
            'storage_disk' => $storageDisk,
            'storage_path' => '',
            'status' => 'pending',
            'started_at' => now(),
            'exclude_patterns' => $excludePatterns,
        ]);

        try {
            $backup->update(['status' => 'running']);

            $server = $project->server;
            $tempPath = $this->getTempPath($backup);

            // Create tar.gz with exclude patterns
            $this->createTarArchive($server, $sourcePath, $tempPath, $excludePatterns);

            // Download file if remote server
            $localPath = $this->downloadBackupFile($server, $tempPath, $backup);

            // Calculate checksum
            $checksum = hash_file('sha256', $localPath);

            // Generate manifest (list of files)
            $manifest = $this->generateManifest($localPath);

            // Upload to storage if not local
            if ($storageDisk !== 'local') {
                $storagePath = $this->uploadToStorage($localPath, $backup->filename, $storageDisk);
                @unlink($localPath); // Remove local temp file
            } else {
                $storagePath = $this->moveToLocalStorage($localPath, $backup->filename);
            }

            // Get file size
            $fileSize = Storage::disk($storageDisk)->size($storagePath);

            $backup->update([
                'storage_path' => $storagePath,
                'size_bytes' => $fileSize,
                'files_count' => count($manifest),
                'checksum' => $checksum,
                'manifest' => $manifest,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Cleanup remote temp file
            $this->cleanupRemoteTempFile($server, $tempPath);

            Log::info('File backup completed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'size' => $fileSize,
                'files_count' => count($manifest),
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('File backup failed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    /**
     * Create an incremental backup based on a full backup
     */
    public function createIncrementalBackup(Project $project, FileBackup $baseBackup, array $options = []): FileBackup
    {
        if ($baseBackup->type !== 'full') {
            throw new \InvalidArgumentException('Base backup must be a full backup');
        }

        if (!$baseBackup->isCompleted()) {
            throw new \InvalidArgumentException('Base backup must be completed');
        }

        $sourcePath = $options['source_path'] ?? $baseBackup->source_path;
        $storageDisk = $options['storage_disk'] ?? $baseBackup->storage_disk;
        $excludePatterns = $this->getExcludePatterns($project, $options['exclude'] ?? []);

        $backup = FileBackup::create([
            'project_id' => $project->id,
            'filename' => $this->generateFilename($project, 'incremental'),
            'type' => 'incremental',
            'source_path' => $sourcePath,
            'storage_disk' => $storageDisk,
            'storage_path' => '',
            'status' => 'pending',
            'started_at' => now(),
            'exclude_patterns' => $excludePatterns,
            'parent_backup_id' => $baseBackup->id,
        ]);

        try {
            $backup->update(['status' => 'running']);

            $server = $project->server;
            $tempPath = $this->getTempPath($backup);

            // Get files modified since base backup
            $baseBackupTime = $baseBackup->created_at->timestamp;

            // Create tar.gz with only files newer than base backup
            $this->createIncrementalTarArchive($server, $sourcePath, $tempPath, $excludePatterns, $baseBackupTime);

            // Download file if remote server
            $localPath = $this->downloadBackupFile($server, $tempPath, $backup);

            // Calculate checksum
            $checksum = hash_file('sha256', $localPath);

            // Generate manifest (list of changed files)
            $manifest = $this->generateManifest($localPath);

            // Upload to storage if not local
            if ($storageDisk !== 'local') {
                $storagePath = $this->uploadToStorage($localPath, $backup->filename, $storageDisk);
                @unlink($localPath);
            } else {
                $storagePath = $this->moveToLocalStorage($localPath, $backup->filename);
            }

            // Get file size
            $fileSize = Storage::disk($storageDisk)->size($storagePath);

            $backup->update([
                'storage_path' => $storagePath,
                'size_bytes' => $fileSize,
                'files_count' => count($manifest),
                'checksum' => $checksum,
                'manifest' => $manifest,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Cleanup remote temp file
            $this->cleanupRemoteTempFile($server, $tempPath);

            Log::info('Incremental backup completed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'base_backup_id' => $baseBackup->id,
                'size' => $fileSize,
                'files_count' => count($manifest),
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Incremental backup failed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    /**
     * Restore files from backup
     */
    public function restoreBackup(FileBackup $backup, bool $overwrite = false, ?string $targetPath = null): bool
    {
        if (!$backup->isCompleted()) {
            throw new \InvalidArgumentException('Cannot restore incomplete backup');
        }

        $project = $backup->project;
        $server = $project->server;
        $targetPath = $targetPath ?? $backup->source_path;

        try {
            Log::info('Starting backup restore', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'target_path' => $targetPath,
                'overwrite' => $overwrite,
            ]);

            // Get the backup chain (for incremental backups)
            $backupChain = $backup->getBackupChain();

            // Download and extract each backup in order
            foreach ($backupChain as $chainBackup) {
                $localPath = $this->downloadBackupToLocal($chainBackup);

                // Upload to server
                $remoteTempPath = "/tmp/restore_{$chainBackup->id}.tar.gz";
                $this->uploadFileToServer($server, $localPath, $remoteTempPath);

                // Extract on server
                $this->extractTarArchive($server, $remoteTempPath, $targetPath, $overwrite);

                // Cleanup
                @unlink($localPath);
                $this->cleanupRemoteTempFile($server, $remoteTempPath);
            }

            Log::info('Backup restore completed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'backups_restored' => count($backupChain),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Backup restore failed', [
                'backup_id' => $backup->id,
                'project' => $project->slug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete backup file and record
     */
    public function deleteBackup(FileBackup $backup): void
    {
        try {
            // Delete file from storage
            if (Storage::disk($backup->storage_disk)->exists($backup->storage_path)) {
                Storage::disk($backup->storage_disk)->delete($backup->storage_path);
            }

            // Delete child backups first (incremental backups)
            foreach ($backup->childBackups as $childBackup) {
                $this->deleteBackup($childBackup);
            }

            $backup->delete();

            Log::info('Backup deleted', [
                'backup_id' => $backup->id,
                'project' => $backup->project->slug,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get exclude patterns for a project
     */
    public function getExcludePatterns(Project $project, array $additionalExcludes = []): array
    {
        $excludes = $this->defaultExcludes;

        // Add project-specific excludes from metadata if exists
        if (isset($project->metadata['backup_excludes']) && is_array($project->metadata['backup_excludes'])) {
            $excludes = array_merge($excludes, $project->metadata['backup_excludes']);
        }

        // Add additional excludes
        if (!empty($additionalExcludes)) {
            $excludes = array_merge($excludes, $additionalExcludes);
        }

        return array_unique($excludes);
    }

    /**
     * Create tar archive on server with exclude patterns
     */
    private function createTarArchive(Server $server, string $sourcePath, string $targetPath, array $excludePatterns): void
    {
        // Build exclude flags
        $excludeFlags = '';
        foreach ($excludePatterns as $pattern) {
            $excludeFlags .= " --exclude='{$pattern}'";
        }

        $tarCommand = sprintf(
            "cd %s && tar -czf %s%s .",
            escapeshellarg($sourcePath),
            escapeshellarg($targetPath),
            $excludeFlags
        );

        $this->executeSSHCommand($server, $tarCommand, 3600); // 1 hour timeout for large backups
    }

    /**
     * Create incremental tar archive (only files modified after timestamp)
     */
    private function createIncrementalTarArchive(Server $server, string $sourcePath, string $targetPath, array $excludePatterns, int $sinceTimestamp): void
    {
        // Build exclude flags
        $excludeFlags = '';
        foreach ($excludePatterns as $pattern) {
            $excludeFlags .= " --exclude='{$pattern}'";
        }

        // Use find to get files modified since timestamp, then tar them
        $findCommand = sprintf(
            "cd %s && find . -type f -newermt '@%d' ! -path './.git/*'%s -print0 | tar -czf %s --null -T -",
            escapeshellarg($sourcePath),
            $sinceTimestamp,
            $this->buildFindExcludes($excludePatterns),
            escapeshellarg($targetPath)
        );

        $this->executeSSHCommand($server, $findCommand, 3600);
    }

    /**
     * Build find exclude patterns
     */
    private function buildFindExcludes(array $patterns): string
    {
        $excludes = '';
        foreach ($patterns as $pattern) {
            // Convert glob patterns to find patterns
            $pattern = str_replace('*', '', $pattern);
            $excludes .= " ! -path '*{$pattern}*'";
        }
        return $excludes;
    }

    /**
     * Extract tar archive on server
     */
    private function extractTarArchive(Server $server, string $archivePath, string $targetPath, bool $overwrite): void
    {
        // Create target directory if it doesn't exist
        $mkdirCommand = "mkdir -p " . escapeshellarg($targetPath);
        $this->executeSSHCommand($server, $mkdirCommand);

        // Extract command
        $extractFlags = $overwrite ? '--overwrite' : '--skip-old-files';
        $tarCommand = sprintf(
            "tar -xzf %s -C %s %s",
            escapeshellarg($archivePath),
            escapeshellarg($targetPath),
            $extractFlags
        );

        $this->executeSSHCommand($server, $tarCommand, 1800); // 30 minutes timeout
    }

    /**
     * Generate manifest (list of files in archive)
     */
    private function generateManifest(string $archivePath): array
    {
        try {
            $process = Process::fromShellCommandline("tar -tzf " . escapeshellarg($archivePath) . " | head -1000");
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                $files = array_filter(explode("\n", $process->getOutput()));
                return array_values($files);
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('Failed to generate manifest', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Download backup file from server to local temp
     */
    private function downloadBackupFile(Server $server, string $remotePath, FileBackup $backup): string
    {
        if ($this->isLocalhost($server->ip_address)) {
            return $remotePath;
        }

        $localPath = storage_path('app/temp/' . $backup->filename);
        $directory = dirname($localPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->downloadFile($server, $remotePath, $localPath);

        return $localPath;
    }

    /**
     * Download backup to local storage for restore
     */
    private function downloadBackupToLocal(FileBackup $backup): string
    {
        $localPath = storage_path('app/temp/' . $backup->filename);
        $directory = dirname($localPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($backup->storage_disk === 'local') {
            $storagePath = storage_path('app/' . $backup->storage_path);
            copy($storagePath, $localPath);
        } else {
            Storage::disk($backup->storage_disk)->download($backup->storage_path, $localPath);
        }

        return $localPath;
    }

    /**
     * Upload file to storage
     */
    private function uploadToStorage(string $localPath, string $filename, string $disk): string
    {
        $storagePath = 'file-backups/' . date('Y/m/d') . '/' . $filename;

        Storage::disk($disk)->put($storagePath, file_get_contents($localPath));

        return $storagePath;
    }

    /**
     * Move file to local storage
     */
    private function moveToLocalStorage(string $localPath, string $filename): string
    {
        $storagePath = 'file-backups/' . date('Y/m/d') . '/' . $filename;
        $targetPath = storage_path('app/' . $storagePath);
        $directory = dirname($targetPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        rename($localPath, $targetPath);

        return $storagePath;
    }

    /**
     * Download file from server via SCP
     */
    private function downloadFile(Server $server, string $remotePath, string $localPath): void
    {
        $scpOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-P ' . $server->port,
        ];

        if ($server->ssh_password) {
            $command = sprintf(
                'sshpass -p %s scp %s %s@%s:%s %s',
                escapeshellarg($server->ssh_password),
                implode(' ', $scpOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath),
                escapeshellarg($localPath)
            );
        } else {
            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $scpOptions[] = '-i ' . $keyFile;
            }

            $command = sprintf(
                'scp %s %s@%s:%s %s',
                implode(' ', $scpOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath),
                escapeshellarg($localPath)
            );
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour for large files
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("File download failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Upload file to server via SCP
     */
    private function uploadFileToServer(Server $server, string $localPath, string $remotePath): void
    {
        $scpOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-P ' . $server->port,
        ];

        if ($server->ssh_password) {
            $command = sprintf(
                'sshpass -p %s scp %s %s %s@%s:%s',
                escapeshellarg($server->ssh_password),
                implode(' ', $scpOptions),
                escapeshellarg($localPath),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath)
            );
        } else {
            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $scpOptions[] = '-i ' . $keyFile;
            }

            $command = sprintf(
                'scp %s %s %s@%s:%s',
                implode(' ', $scpOptions),
                escapeshellarg($localPath),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath)
            );
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("File upload failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Execute SSH command
     */
    private function executeSSHCommand(Server $server, string $remoteCommand, int $timeout = 60): string
    {
        $command = $this->buildSSHCommand($server, $remoteCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("SSH command failed: " . $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Build SSH command
     */
    private function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $server->port,
        ];

        if ($server->ssh_password) {
            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s"',
                escapeshellarg($server->ssh_password),
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand)
            );
        }

        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "%s"',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand)
        );
    }

    /**
     * Cleanup remote temp file
     */
    private function cleanupRemoteTempFile(Server $server, string $remotePath): void
    {
        try {
            $this->executeSSHCommand($server, "rm -f " . escapeshellarg($remotePath));
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup remote temp file', [
                'path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate backup filename
     */
    private function generateFilename(Project $project, string $type): string
    {
        return sprintf(
            '%s_%s_%s.tar.gz',
            $project->slug,
            $type,
            now()->format('Y-m-d_His')
        );
    }

    /**
     * Get temporary path for backup on server
     */
    private function getTempPath(FileBackup $backup): string
    {
        return "/tmp/backup_{$backup->id}_{$backup->filename}";
    }

    /**
     * Check if IP is localhost
     */
    private function isLocalhost(string $ip): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ip, $localIPs)) {
            return true;
        }

        $serverIP = gethostbyname(gethostname());
        if ($ip === $serverIP) {
            return true;
        }

        try {
            $publicIP = trim(file_get_contents('http://api.ipify.org'));
            if ($ip === $publicIP) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return false;
    }
}
