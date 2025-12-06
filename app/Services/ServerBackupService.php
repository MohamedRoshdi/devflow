<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

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
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(3600); // 1 hour timeout
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Backup failed: '.$process->getErrorOutput());
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

            $process = Process::fromShellCommandline($rsyncCommand);
            $process->setTimeout(3600);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Incremental backup failed: '.$process->getErrorOutput());
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
    public function restoreBackup(ServerBackup $backup): bool
    {
        if (! $backup->isCompleted()) {
            throw new \RuntimeException('Cannot restore incomplete backup');
        }

        try {
            $server = $backup->server;

            if ($backup->type === 'full') {
                return $this->restoreFullBackup($backup, $server);
            } elseif ($backup->type === 'incremental') {
                return $this->restoreIncrementalBackup($backup, $server);
            } elseif ($backup->type === 'snapshot') {
                return $this->restoreSnapshot($backup, $server);
            }

            throw new \RuntimeException('Unknown backup type');
        } catch (\Exception $e) {
            Log::error('Backup restoration failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
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
                'sshpass -p %s ssh %s %s@%s "%s"',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($command)
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
            'ssh %s %s@%s "%s"',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($command)
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

        $process = Process::fromShellCommandline($scpCommand);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to download backup file: '.$process->getErrorOutput());
        }
    }

    private function executeRemoteCommand(Server $server, string $command, bool $suppressWarnings = false): string
    {
        $command = $this->buildSSHCommand($server, $command);

        if ($suppressWarnings) {
            $command .= ' 2>/dev/null';
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        return trim($process->getOutput());
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

        $process = Process::fromShellCommandline('du -sb '.escapeshellarg($path).' | cut -f1');
        $process->run();

        return (int) trim($process->getOutput());
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

    private function restoreFullBackup(ServerBackup $backup, Server $server): bool
    {
        $localPath = storage_path($backup->storage_path);

        if (! file_exists($localPath)) {
            throw new \RuntimeException('Backup file not found');
        }

        // Upload backup to server
        $remoteBackupPath = '/tmp/restore_'.basename($backup->storage_path);
        $this->uploadBackupFile($server, $localPath, $remoteBackupPath);

        // Extract backup
        $this->executeRemoteCommand($server, "sudo tar -xzf {$remoteBackupPath} -C / --overwrite", true);

        // Cleanup
        $this->cleanupRemoteFile($server, $remoteBackupPath);

        Log::info('Full backup restored', ['backup_id' => $backup->id]);

        return true;
    }

    private function restoreIncrementalBackup(ServerBackup $backup, Server $server): bool
    {
        $localPath = storage_path($backup->storage_path);

        if (! is_dir($localPath)) {
            throw new \RuntimeException('Backup directory not found');
        }

        // Use rsync to restore
        $rsyncCommand = $this->buildRsyncCommand($server, $localPath.'/', $server->username.'@'.$server->ip_address.':/');

        $process = Process::fromShellCommandline($rsyncCommand);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to restore incremental backup');
        }

        Log::info('Incremental backup restored', ['backup_id' => $backup->id]);

        return true;
    }

    private function restoreSnapshot(ServerBackup $backup, Server $server): bool
    {
        // This is a placeholder - snapshot restoration depends on the infrastructure
        throw new \RuntimeException('Snapshot restoration must be performed manually through your cloud provider or LVM tools');
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

        $process = Process::fromShellCommandline($scpCommand);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to upload backup file');
        }
    }
}
