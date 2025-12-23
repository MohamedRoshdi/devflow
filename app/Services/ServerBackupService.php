<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\ServerBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class ServerBackupService
{
    public function __construct(
        private readonly ServerConnectivityService $connectivityService
    ) {}

    /**
     * Create a full backup of the server
     */
    public function createFullBackup(Server $server): ServerBackup
    {
        $backup = ServerBackup::create([
            'server_id' => $server->id,
            'type' => 'full',
            'status' => 'running',
            'storage_driver' => 'local',
            'started_at' => now(),
        ]);

        try {
            $backupDir = storage_path('backups/servers');
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFileName = "server_{$server->id}_full_{$timestamp}.tar.gz";
            $backupPath = "{$backupDir}/{$backupFileName}";

            // Directories to backup
            $dirsToBackup = [
                '/etc',
                '/var/www',
                '/opt',
                '/home',
            ];

            // Create temporary script for backup
            $backupScript = $this->generateBackupScript($dirsToBackup, $backupFileName);

            // Execute backup command via SSH
            $command = $this->buildSSHCommand($server, $backupScript);
            $result = Process::timeout(3600)->run($command); // 1 hour timeout

            if (! $result->successful()) {
                throw new \RuntimeException('Backup failed: '.$result->errorOutput());
            }

            // Download backup file from remote server
            $this->downloadBackupFile($server, $backupFileName, $backupPath);

            // Get file size
            $fileSize = file_exists($backupPath) ? filesize($backupPath) : 0;

            // Update backup record
            $backup->update([
                'status' => 'completed',
                'storage_path' => "backups/servers/{$backupFileName}",
                'size_bytes' => $fileSize,
                'completed_at' => now(),
                'metadata' => [
                    'directories' => $dirsToBackup,
                    'method' => 'tar',
                ],
            ]);

            Log::info('Server full backup completed', [
                'server_id' => $server->id,
                'backup_id' => $backup->id,
                'size' => $fileSize,
            ]);

            // Cleanup remote backup file
            $this->cleanupRemoteFile($server, "/tmp/{$backupFileName}");

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Server full backup failed', [
                'server_id' => $server->id,
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $freshBackup = $backup->fresh();
        if ($freshBackup === null) {
            throw new \RuntimeException('Failed to refresh backup');
        }

return $freshBackup;
    }

    /**
     * Create an incremental backup using rsync
     */
    public function createIncrementalBackup(Server $server): ServerBackup
    {
        $backup = ServerBackup::create([
            'server_id' => $server->id,
            'type' => 'incremental',
            'status' => 'running',
            'storage_driver' => 'local',
            'started_at' => now(),
        ]);

        try {
            $backupDir = storage_path("backups/servers/incremental/{$server->id}");
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = "{$backupDir}/{$timestamp}";

            // Directories to backup
            $dirsToBackup = '/var/www /etc /opt';

            // Build rsync command
            $rsyncCommand = $this->buildRsyncCommand($server, $dirsToBackup, $backupPath);

            $result = Process::timeout(3600)->run($rsyncCommand);

            if (! $result->successful()) {
                throw new \RuntimeException('Incremental backup failed: '.$result->errorOutput());
            }

            // Calculate backup size
            $size = $this->getDirectorySize($backupPath);

            $backup->update([
                'status' => 'completed',
                'storage_path' => "backups/servers/incremental/{$server->id}/{$timestamp}",
                'size_bytes' => $size,
                'completed_at' => now(),
                'metadata' => [
                    'method' => 'rsync',
                    'incremental' => true,
                ],
            ]);

            Log::info('Server incremental backup completed', [
                'server_id' => $server->id,
                'backup_id' => $backup->id,
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Server incremental backup failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $freshBackup = $backup->fresh();
        if ($freshBackup === null) {
            throw new \RuntimeException('Failed to refresh backup');
        }

return $freshBackup;
    }

    /**
     * Create a snapshot backup (for cloud providers or LVM)
     */
    public function createSnapshot(Server $server): ServerBackup
    {
        $backup = ServerBackup::create([
            'server_id' => $server->id,
            'type' => 'snapshot',
            'status' => 'running',
            'storage_driver' => 'local',
            'started_at' => now(),
        ]);

        try {
            // Check if LVM is available
            $lvmCheck = $this->executeRemoteCommand($server, 'which lvcreate');

            if (empty($lvmCheck)) {
                throw new \RuntimeException('LVM not available on this server. Snapshot backups require LVM or cloud provider support.');
            }

            // Create LVM snapshot
            $timestamp = now()->format('Y-m-d_H-i-s');
            $snapshotName = "backup_snapshot_{$timestamp}";

            // Find root volume
            $volumeInfo = $this->executeRemoteCommand($server, 'lvdisplay | grep "LV Path" | head -1');

            if (empty($volumeInfo)) {
                throw new \RuntimeException('Could not find LVM volume');
            }

            // Create snapshot (allocate 10% of volume size)
            $this->executeRemoteCommand($server, "sudo lvcreate -L10G -s -n {$snapshotName} /dev/mapper/root", true);

            $backup->update([
                'status' => 'completed',
                'storage_path' => "lvm://{$snapshotName}",
                'size_bytes' => 10 * 1024 * 1024 * 1024, // 10GB
                'completed_at' => now(),
                'metadata' => [
                    'method' => 'lvm',
                    'snapshot_name' => $snapshotName,
                ],
            ]);

            Log::info('Server snapshot created', [
                'server_id' => $server->id,
                'snapshot_name' => $snapshotName,
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Server snapshot failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $freshBackup = $backup->fresh();
        if ($freshBackup === null) {
            throw new \RuntimeException('Failed to refresh backup');
        }

return $freshBackup;
    }

    /**
     * Restore a backup
     */
    public function restoreBackup(ServerBackup $backup, bool $verifyIntegrity = true): bool
    {
        if (! $backup->isCompleted()) {
            throw new \RuntimeException('Cannot restore incomplete backup. Status: '.$backup->status);
        }

        try {
            $server = $backup->server;

            Log::info('Starting server backup restore', [
                'backup_id' => $backup->id,
                'backup_type' => $backup->type,
                'server_id' => $server->id,
                'server_name' => $server->name,
            ]);

            // Verify backup file exists before attempting restore
            if ($backup->type !== 'snapshot') {
                $backupPath = storage_path($backup->storage_path);

                if (! file_exists($backupPath)) {
                    throw new \RuntimeException("Backup file not found at: {$backupPath}");
                }

                Log::info('Backup file verified', [
                    'backup_id' => $backup->id,
                    'file_path' => $backupPath,
                    'file_size' => filesize($backupPath),
                ]);
            }

            // Restore based on backup type
            $result = match ($backup->type) {
                'full' => $this->restoreFullBackup($backup, $server, $verifyIntegrity),
                'incremental' => $this->restoreIncrementalBackup($backup, $server),
                'snapshot' => $this->restoreSnapshot($backup, $server),
                default => throw new \RuntimeException("Unknown backup type: {$backup->type}"),
            };

            Log::info('Server backup restore completed successfully', [
                'backup_id' => $backup->id,
                'backup_type' => $backup->type,
                'server_id' => $server->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Server backup restoration failed', [
                'backup_id' => $backup->id,
                'backup_type' => $backup->type,
                'server_id' => $backup->server_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(ServerBackup $backup): bool
    {
        try {
            if ($backup->storage_driver === 'local' && $backup->storage_path) {
                $fullPath = storage_path($backup->storage_path);

                if (is_file($fullPath)) {
                    unlink($fullPath);
                } elseif (is_dir($fullPath)) {
                    $this->deleteDirectory($fullPath);
                }
            }

            // Remove snapshot if it exists
            if ($backup->type === 'snapshot' && str_starts_with($backup->storage_path, 'lvm://')) {
                $snapshotName = str_replace('lvm://', '', $backup->storage_path);
                $this->executeRemoteCommand($backup->server, "sudo lvremove -f /dev/mapper/{$snapshotName}", true);
            }

            $backup->delete();

            Log::info('Backup deleted', ['backup_id' => $backup->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload backup to S3
     */
    public function uploadToS3(ServerBackup $backup): bool
    {
        if ($backup->storage_driver !== 'local') {
            throw new \RuntimeException('Backup is not stored locally');
        }

        try {
            $localPath = storage_path($backup->storage_path);

            if (! file_exists($localPath)) {
                throw new \RuntimeException('Backup file not found');
            }

            $s3Path = "server-backups/{$backup->server_id}/".basename($backup->storage_path);

            Storage::disk('s3')->put($s3Path, file_get_contents($localPath));

            $backup->update([
                'storage_driver' => 's3',
                'storage_path' => $s3Path,
            ]);

            // Delete local file
            unlink($localPath);

            Log::info('Backup uploaded to S3', ['backup_id' => $backup->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to upload backup to S3', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Estimate backup size
     */
    public function getBackupSize(Server $server): array
    {
        try {
            $sizes = [];

            // Get size of common directories
            $directories = [
                'etc' => '/etc',
                'var_www' => '/var/www',
                'opt' => '/opt',
                'home' => '/home',
            ];

            foreach ($directories as $key => $dir) {
                $output = $this->executeRemoteCommand($server, "sudo du -sb {$dir} 2>/dev/null | cut -f1", true);
                $sizes[$key] = $output ? (int) trim($output) : 0;
            }

            $sizes['total'] = array_sum($sizes);

            return $sizes;

        } catch (\Exception $e) {
            Log::error('Failed to estimate backup size', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return ['total' => 0];
        }
    }

    // Private helper methods

    private function generateBackupScript(array $dirs, string $fileName): string
    {
        $dirsList = implode(' ', $dirs);

        return <<<BASH
cd /tmp && \
sudo tar -czf {$fileName} {$dirsList} 2>/dev/null && \
echo "BACKUP_CREATED"
BASH;
    }

    private function buildSSHCommand(Server $server, string $command): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($command)
            );
        }

        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($command)
        );
    }

    private function buildRsyncCommand(Server $server, string $source, string $destination): string
    {
        $sshCommand = "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p {$server->port}";

        if ($server->ssh_password) {
            $sshCommand = 'sshpass -p '.escapeshellarg($server->ssh_password).' '.$sshCommand;
        } elseif ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshCommand .= " -i {$keyFile}";
        }

        return sprintf(
            'rsync -avz --delete -e "%s" %s@%s:%s %s',
            $sshCommand,
            $server->username,
            $server->ip_address,
            $source,
            $destination
        );
    }

    private function downloadBackupFile(Server $server, string $remoteFileName, string $localPath): void
    {
        $scpCommand = sprintf(
            'scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -P %d %s@%s:/tmp/%s %s',
            $server->port,
            $server->username,
            $server->ip_address,
            $remoteFileName,
            $localPath
        );

        if ($server->ssh_password) {
            $scpCommand = 'sshpass -p '.escapeshellarg($server->ssh_password).' '.$scpCommand;
        }

        $result = Process::timeout(3600)->run($scpCommand);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to download backup file: '.$result->errorOutput());
        }
    }

    private function executeRemoteCommand(Server $server, string $command, bool $suppressWarnings = false): string
    {
        $command = $this->buildSSHCommand($server, $command);

        if ($suppressWarnings) {
            $command .= ' 2>/dev/null';
        }

        $result = Process::timeout(60)->run($command);

        return trim($result->output());
    }

    private function cleanupRemoteFile(Server $server, string $filePath): void
    {
        try {
            $this->executeRemoteCommand($server, "sudo rm -f {$filePath}", true);
        } catch (\Exception $e) {
            Log::warning("Failed to cleanup remote file: {$filePath}");
        }
    }

    private function getDirectorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $result = Process::run('du -sb '.escapeshellarg($path).' | cut -f1');

        return (int) trim($result->output());
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $path.DIRECTORY_SEPARATOR.$file;

            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($path);
    }

    private function restoreFullBackup(ServerBackup $backup, Server $server, bool $verifyIntegrity = true): bool
    {
        $localPath = storage_path($backup->storage_path);

        if (! file_exists($localPath)) {
            throw new \RuntimeException("Backup file not found at: {$localPath}");
        }

        // Verify integrity if requested
        if ($verifyIntegrity && $backup->size_bytes) {
            $actualSize = filesize($localPath);
            if ($actualSize !== $backup->size_bytes) {
                throw new \RuntimeException("Backup file size mismatch. Expected: {$backup->size_bytes}, Got: {$actualSize}");
            }

            Log::info('Backup file size verified', [
                'backup_id' => $backup->id,
                'size' => $actualSize,
            ]);
        }

        // Upload backup to server
        $remoteBackupPath = '/tmp/restore_'.uniqid().'_'.basename($backup->storage_path);

        Log::info('Uploading backup to server', [
            'backup_id' => $backup->id,
            'remote_path' => $remoteBackupPath,
        ]);

        $this->uploadBackupFile($server, $localPath, $remoteBackupPath);

        // Verify upload
        $remoteFileCheck = $this->executeRemoteCommand($server, "test -f {$remoteBackupPath} && echo 'exists'", true);
        if (trim($remoteFileCheck) !== 'exists') {
            throw new \RuntimeException('Backup file upload verification failed');
        }

        Log::info('Backup uploaded successfully, starting extraction', [
            'backup_id' => $backup->id,
        ]);

        // Extract backup (extract to root with absolute paths preserved)
        try {
            $extractCommand = "sudo tar -xzf {$remoteBackupPath} -C / --overwrite 2>&1";
            $output = $this->executeRemoteCommand($server, $extractCommand, false);

            Log::info('Backup extraction completed', [
                'backup_id' => $backup->id,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Backup extraction failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to extract backup: '.$e->getMessage());
        }

        // Cleanup remote backup file
        $this->cleanupRemoteFile($server, $remoteBackupPath);

        // Restart services to apply restored configurations
        try {
            Log::info('Restarting services after restore', ['backup_id' => $backup->id]);

            // Restart common services that might be affected
            $this->executeRemoteCommand($server, 'sudo systemctl restart nginx', true);
            $this->executeRemoteCommand($server, 'sudo systemctl restart php*-fpm', true);

            Log::info('Services restarted successfully', ['backup_id' => $backup->id]);
        } catch (\Exception $e) {
            Log::warning('Failed to restart some services', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Full server backup restored successfully', [
            'backup_id' => $backup->id,
            'server_id' => $server->id,
        ]);

        return true;
    }

    private function restoreIncrementalBackup(ServerBackup $backup, Server $server): bool
    {
        $localPath = storage_path($backup->storage_path);

        if (! is_dir($localPath)) {
            throw new \RuntimeException("Backup directory not found at: {$localPath}");
        }

        Log::info('Starting incremental backup restore', [
            'backup_id' => $backup->id,
            'local_path' => $localPath,
        ]);

        // Build rsync command to push from local to remote server
        $sshCommand = "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p {$server->port}";

        if ($server->ssh_password) {
            $sshCommand = 'sshpass -p '.escapeshellarg($server->ssh_password).' '.$sshCommand;
        } elseif ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshCommand .= " -i {$keyFile}";
        }

        // Rsync from local to remote (push mode)
        $rsyncCommand = sprintf(
            'rsync -avz --delete -e "%s" %s/ %s@%s:/',
            $sshCommand,
            escapeshellarg(rtrim($localPath, '/')),
            $server->username,
            $server->ip_address
        );

        Log::info('Executing rsync restore', [
            'backup_id' => $backup->id,
            'command' => $rsyncCommand,
        ]);

        $result = Process::timeout(3600)->run($rsyncCommand);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to restore incremental backup: '.$result->errorOutput());
        }

        Log::info('Incremental backup restored successfully', [
            'backup_id' => $backup->id,
            'output' => $result->output(),
        ]);

        // Restart services
        try {
            $this->executeRemoteCommand($server, 'sudo systemctl restart nginx', true);
            $this->executeRemoteCommand($server, 'sudo systemctl restart php*-fpm', true);
        } catch (\Exception $e) {
            Log::warning('Failed to restart services after incremental restore', [
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    private function restoreSnapshot(ServerBackup $backup, Server $server): bool
    {
        // LVM snapshot restoration
        if (str_starts_with($backup->storage_path, 'lvm://')) {
            $snapshotName = str_replace('lvm://', '', $backup->storage_path);

            Log::info('Attempting LVM snapshot restoration', [
                'backup_id' => $backup->id,
                'snapshot_name' => $snapshotName,
            ]);

            // Check if snapshot exists
            $snapshotCheck = $this->executeRemoteCommand($server, "sudo lvdisplay /dev/mapper/{$snapshotName} 2>&1", true);

            if (strpos($snapshotCheck, 'not found') !== false) {
                throw new \RuntimeException("LVM snapshot '{$snapshotName}' not found on server");
            }

            // Provide instructions for manual restoration
            $instructions = <<<INSTRUCTIONS

LVM Snapshot Restoration Instructions:
======================================

The snapshot '{$snapshotName}' exists on the server but must be restored manually.

To restore this snapshot, SSH into the server and execute:

1. Stop all services:
   sudo systemctl stop nginx php*-fpm mysql

2. Merge the snapshot (this will restore the original volume):
   sudo lvconvert --merge /dev/mapper/{$snapshotName}

3. Reboot the server to complete the merge:
   sudo reboot

4. After reboot, verify services are running:
   sudo systemctl status nginx php*-fpm mysql

WARNING: This operation will replace all data on the root volume with the snapshot data.
Make sure you have a current backup before proceeding.

INSTRUCTIONS;

            Log::warning('LVM snapshot requires manual restoration', [
                'backup_id' => $backup->id,
                'snapshot_name' => $snapshotName,
                'instructions' => $instructions,
            ]);

            throw new \RuntimeException("LVM snapshot restoration must be performed manually:\n\n{$instructions}");
        }

        // Cloud provider snapshots
        throw new \RuntimeException(
            'Snapshot restoration for cloud providers must be performed through '.
            'your cloud provider\'s management console (AWS, DigitalOcean, Vultr, etc.). '.
            'This typically involves: 1) Creating a new server from the snapshot, '.
            '2) Testing the restored server, 3) Updating DNS to point to the new server.'
        );
    }

    private function uploadBackupFile(Server $server, string $localPath, string $remotePath): void
    {
        $scpCommand = sprintf(
            'scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -P %d %s %s@%s:%s',
            $server->port,
            $localPath,
            $server->username,
            $server->ip_address,
            $remotePath
        );

        if ($server->ssh_password) {
            $scpCommand = 'sshpass -p '.escapeshellarg($server->ssh_password).' '.$scpCommand;
        }

        $result = Process::timeout(3600)->run($scpCommand);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to upload backup file');
        }
    }
}
