<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{Server, DatabaseBackup, BackupSchedule};
use Illuminate\Support\Facades\{Storage, Log};
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class DatabaseBackupService
{
    /**
     * Create backup based on schedule
     */
    public function createBackup(BackupSchedule $schedule): DatabaseBackup
    {
        $backup = DatabaseBackup::create([
            'project_id' => $schedule->project_id,
            'server_id' => $schedule->server_id,
            'database_type' => $schedule->database_type,
            'database_name' => $schedule->database_name,
            'file_name' => $this->generateFileName($schedule->database_name),
            'file_path' => '',
            'storage_disk' => $schedule->storage_disk,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        try {
            $backup->update(['status' => 'running']);

            $localPath = $this->getLocalBackupPath($backup);

            // Create backup based on database type
            match($schedule->database_type) {
                'mysql' => $this->backupMySQL($schedule->server, $schedule->database_name, $localPath),
                'postgresql' => $this->backupPostgreSQL($schedule->server, $schedule->database_name, $localPath),
                'sqlite' => $this->backupSQLite($schedule->server, $schedule->database_name, $localPath),
            };

            // Get file size
            $fileSize = file_exists($localPath) ? filesize($localPath) : 0;

            // Upload to S3 if configured
            if ($schedule->storage_disk === 's3') {
                $s3Path = $this->uploadToS3($localPath, $backup->file_name);
                $backup->file_path = $s3Path;

                // Remove local file after successful upload
                @unlink($localPath);
            } else {
                $backup->file_path = $localPath;
            }

            $backup->update([
                'file_size' => $fileSize,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Database backup completed', [
                'backup_id' => $backup->id,
                'database' => $schedule->database_name,
                'size' => $fileSize,
            ]);

        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Database backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    /**
     * Backup MySQL database via SSH
     */
    public function backupMySQL(Server $server, string $database, string $outputPath): void
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Build mysqldump command
        $dumpCommand = sprintf(
            'mysqldump --single-transaction --quick --lock-tables=false %s | gzip > %s',
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Execute via SSH
        $command = $this->buildSSHCommand($server, $dumpCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour timeout for large databases
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("MySQL backup failed: " . $process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (!$this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }
    }

    /**
     * Backup PostgreSQL database via SSH
     */
    public function backupPostgreSQL(Server $server, string $database, string $outputPath): void
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Build pg_dump command
        $dumpCommand = sprintf(
            'pg_dump %s | gzip > %s',
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Execute via SSH
        $command = $this->buildSSHCommand($server, $dumpCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("PostgreSQL backup failed: " . $process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (!$this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }
    }

    /**
     * Backup SQLite database via SSH
     */
    public function backupSQLite(Server $server, string $database, string $outputPath): void
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Build copy command with gzip
        $copyCommand = sprintf(
            'cat %s | gzip > %s',
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Execute via SSH
        $command = $this->buildSSHCommand($server, $copyCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("SQLite backup failed: " . $process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (!$this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup(DatabaseBackup $backup): string
    {
        if ($backup->storage_disk === 's3') {
            // Download from S3
            $tempPath = storage_path('app/temp/' . $backup->file_name);
            $directory = dirname($tempPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            Storage::disk('s3')->download($backup->file_path, $tempPath);
            return $tempPath;
        }

        // Return local file path
        return $backup->file_path;
    }

    /**
     * Delete backup file and record
     */
    public function deleteBackup(DatabaseBackup $backup): void
    {
        try {
            if ($backup->storage_disk === 's3') {
                Storage::disk('s3')->delete($backup->file_path);
            } else {
                @unlink($backup->file_path);
            }

            $backup->delete();

            Log::info('Backup deleted', ['backup_id' => $backup->id]);
        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(DatabaseBackup $backup): void
    {
        if ($backup->status !== 'completed') {
            throw new \RuntimeException('Cannot restore incomplete backup');
        }

        $server = $backup->server;
        $localPath = $this->downloadBackup($backup);

        try {
            // Upload to server if remote
            $remotePath = '/tmp/' . $backup->file_name;

            if (!$this->isLocalhost($server->ip_address)) {
                $this->uploadFile($server, $localPath, $remotePath);
            } else {
                $remotePath = $localPath;
            }

            // Restore based on database type
            match($backup->database_type) {
                'mysql' => $this->restoreMySQL($server, $backup->database_name, $remotePath),
                'postgresql' => $this->restorePostgreSQL($server, $backup->database_name, $remotePath),
                'sqlite' => $this->restoreSQLite($server, $backup->database_name, $remotePath),
            };

            // Cleanup remote temp file
            if (!$this->isLocalhost($server->ip_address)) {
                $this->executeSSHCommand($server, "rm -f {$remotePath}");
            }

            Log::info('Database restored from backup', [
                'backup_id' => $backup->id,
                'database' => $backup->database_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Database restore failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMySQL(Server $server, string $database, string $backupPath): void
    {
        $restoreCommand = sprintf(
            'gunzip < %s | mysql %s',
            escapeshellarg($backupPath),
            escapeshellarg($database)
        );

        $this->executeSSHCommand($server, $restoreCommand, 3600);
    }

    /**
     * Restore PostgreSQL database
     */
    protected function restorePostgreSQL(Server $server, string $database, string $backupPath): void
    {
        // Drop and recreate database
        $dropCommand = sprintf('dropdb --if-exists %s && createdb %s', escapeshellarg($database), escapeshellarg($database));
        $this->executeSSHCommand($server, $dropCommand);

        // Restore
        $restoreCommand = sprintf(
            'gunzip < %s | psql %s',
            escapeshellarg($backupPath),
            escapeshellarg($database)
        );

        $this->executeSSHCommand($server, $restoreCommand, 3600);
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSQLite(Server $server, string $database, string $backupPath): void
    {
        $restoreCommand = sprintf(
            'gunzip < %s > %s',
            escapeshellarg($backupPath),
            escapeshellarg($database)
        );

        $this->executeSSHCommand($server, $restoreCommand, 600);
    }

    /**
     * Clean up old backups based on retention policy
     */
    public function cleanupOldBackups(BackupSchedule $schedule): int
    {
        $cutoffDate = Carbon::now()->subDays($schedule->retention_days);

        $oldBackups = DatabaseBackup::where('project_id', $schedule->project_id)
            ->where('database_name', $schedule->database_name)
            ->where('status', 'completed')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($oldBackups as $backup) {
            try {
                $this->deleteBackup($backup);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup old backup', [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Old backups cleaned up', [
            'schedule_id' => $schedule->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Upload file to S3
     */
    public function uploadToS3(string $localPath, string $s3FileName): string
    {
        $s3Path = 'backups/' . date('Y/m/d') . '/' . $s3FileName;

        Storage::disk('s3')->put($s3Path, file_get_contents($localPath));

        return $s3Path;
    }

    /**
     * Generate backup filename
     */
    protected function generateFileName(string $database): string
    {
        return sprintf(
            '%s_%s.sql.gz',
            $database,
            now()->format('Y-m-d_His')
        );
    }

    /**
     * Get local backup path
     */
    protected function getLocalBackupPath(DatabaseBackup $backup): string
    {
        $directory = storage_path('app/backups/' . date('Y/m/d'));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory . '/' . $backup->file_name;
    }

    /**
     * Download file from remote server via SCP
     */
    protected function downloadFile(Server $server, string $remotePath, string $localPath): void
    {
        $scpOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-P ' . $server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);
            $command = sprintf(
                'sshpass -p %s scp %s %s@%s:%s %s',
                $escapedPassword,
                implode(' ', $scpOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath),
                escapeshellarg($localPath)
            );
        } else {
            $sshOptions = $scpOptions;
            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i ' . $keyFile;
            }

            $command = sprintf(
                'scp %s %s@%s:%s %s',
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath),
                escapeshellarg($localPath)
            );
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("File download failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Upload file to remote server via SCP
     */
    protected function uploadFile(Server $server, string $localPath, string $remotePath): void
    {
        $scpOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-P ' . $server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);
            $command = sprintf(
                'sshpass -p %s scp %s %s %s@%s:%s',
                $escapedPassword,
                implode(' ', $scpOptions),
                escapeshellarg($localPath),
                $server->username,
                $server->ip_address,
                escapeshellarg($remotePath)
            );
        } else {
            $sshOptions = $scpOptions;
            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i ' . $keyFile;
            }

            $command = sprintf(
                'scp %s %s %s@%s:%s',
                implode(' ', $sshOptions),
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
    protected function executeSSHCommand(Server $server, string $remoteCommand, int $timeout = 60): void
    {
        $command = $this->buildSSHCommand($server, $remoteCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("SSH command failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Build SSH command (reusing pattern from ServerConnectivityService)
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s"',
                $escapedPassword,
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
     * Check if IP is localhost
     */
    protected function isLocalhost(string $ip): bool
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
