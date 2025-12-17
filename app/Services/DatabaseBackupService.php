<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BackupSchedule;
use App\Models\DatabaseBackup;
use App\Models\Project;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * Create backup based on schedule or manual request
     */
    public function createBackup(BackupSchedule $schedule, string $type = 'scheduled'): DatabaseBackup
    {
        $backup = DatabaseBackup::create([
            'project_id' => $schedule->project_id,
            'server_id' => $schedule->server_id,
            'database_type' => $schedule->database_type,
            'database_name' => $schedule->database_name,
            'type' => $type,
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
            $metadata = match ($schedule->database_type) {
                'mysql' => $this->backupMySQL($schedule->server, $schedule->database_name, $localPath),
                'postgresql' => $this->backupPostgreSQL($schedule->server, $schedule->database_name, $localPath),
                'sqlite' => $this->backupSQLite($schedule->server, $schedule->database_name, $localPath),
            };

            // Get file size
            $fileSize = file_exists($localPath) ? filesize($localPath) : 0;

            // Calculate checksum
            $checksum = $this->calculateChecksum($localPath);

            // Encrypt if requested
            if ($schedule->encrypt ?? false) {
                $localPath = $this->encryptBackup($localPath);
                $backup->file_name .= '.enc';
            }

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
                'checksum' => $checksum,
                'metadata' => $metadata,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Database backup completed', [
                'backup_id' => $backup->id,
                'database' => $schedule->database_name,
                'size' => $fileSize,
                'checksum' => $checksum,
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

        $freshBackup = $backup->fresh();
        if ($freshBackup === null) {
            throw new \RuntimeException('Failed to refresh backup after creation');
        }

        return $freshBackup;
    }

    /**
     * Backup MySQL database via SSH
     *
     * @return array<string, mixed>
     */
    public function backupMySQL(Server $server, string $database, string $outputPath): array
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Get database metadata before backup
        $metadataCommand = sprintf(
            'mysql -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = %s; SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = %s;" %s',
            escapeshellarg($database),
            escapeshellarg($database),
            escapeshellarg($database)
        );

        $metadataOutput = $this->executeSSHCommandOutput($server, $metadataCommand);

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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('MySQL backup failed: '.$process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (! $this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }

        return [
            'database_type' => 'mysql',
            'database_name' => $database,
            'tables_count' => $this->parseTableCount($metadataOutput),
            'backup_method' => 'mysqldump',
            'compression' => 'gzip',
        ];
    }

    /**
     * Backup PostgreSQL database via SSH
     *
     * @return array<string, mixed>
     */
    public function backupPostgreSQL(Server $server, string $database, string $outputPath): array
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('PostgreSQL backup failed: '.$process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (! $this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }

        return [
            'database_type' => 'postgresql',
            'database_name' => $database,
            'backup_method' => 'pg_dump',
            'compression' => 'gzip',
        ];
    }

    /**
     * Backup SQLite database via SSH
     *
     * @return array<string, mixed>
     */
    public function backupSQLite(Server $server, string $database, string $outputPath): array
    {
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('SQLite backup failed: '.$process->getErrorOutput());
        }

        // Download the file from remote server if not localhost
        if (! $this->isLocalhost($server->ip_address)) {
            $this->downloadFile($server, $outputPath, $outputPath);
        }

        return [
            'database_type' => 'sqlite',
            'database_name' => $database,
            'backup_method' => 'file_copy',
            'compression' => 'gzip',
        ];
    }

    /**
     * Download backup file
     */
    public function downloadBackup(DatabaseBackup $backup): string
    {
        if ($backup->storage_disk === 's3') {
            // Download from S3
            $tempPath = storage_path('app/temp/'.$backup->file_name);
            $directory = dirname($tempPath);
            if (! is_dir($directory)) {
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
    public function restoreBackup(DatabaseBackup $backup, bool $verifyIntegrity = true): void
    {
        if ($backup->status !== 'completed') {
            throw new \RuntimeException('Cannot restore incomplete backup');
        }

        $server = $backup->server;

        try {
            Log::info('Starting database restore', [
                'backup_id' => $backup->id,
                'database' => $backup->database_name,
                'database_type' => $backup->database_type,
            ]);

            // Download backup file
            $localPath = $this->downloadBackup($backup);

            // Verify integrity before restore if requested
            if ($verifyIntegrity && $backup->checksum) {
                $currentChecksum = $this->calculateChecksum($localPath);

                if ($currentChecksum !== $backup->checksum) {
                    throw new \RuntimeException('Backup integrity check failed: checksum mismatch. The backup file may be corrupted.');
                }

                Log::info('Backup integrity verified', [
                    'backup_id' => $backup->id,
                    'checksum' => $currentChecksum,
                ]);
            }

            // Decrypt if encrypted
            if (str_ends_with($backup->file_name ?? '', '.enc')) {
                Log::info('Decrypting backup', ['backup_id' => $backup->id]);
                $localPath = $this->decryptBackup($localPath);
            }

            // Upload to server if remote
            $remotePath = '/tmp/restore_'.uniqid().'_'.($backup->file_name ?? 'backup.sql.gz');

            if (! $this->isLocalhost($server->ip_address ?? '127.0.0.1')) {
                Log::info('Uploading backup to remote server', [
                    'backup_id' => $backup->id,
                    'remote_path' => $remotePath,
                ]);
                $this->uploadFile($server, $localPath, $remotePath);
            } else {
                $remotePath = $localPath;
            }

            // Restore based on database type
            Log::info('Restoring database', [
                'backup_id' => $backup->id,
                'type' => $backup->database_type,
            ]);

            match ($backup->database_type) {
                'mysql' => $this->restoreMySQL($server, $backup->database_name ?? 'default', $remotePath),
                'postgresql' => $this->restorePostgreSQL($server, $backup->database_name ?? 'default', $remotePath),
                'sqlite' => $this->restoreSQLite($server, $backup->database_name ?? 'default', $remotePath),
            };

            // Cleanup remote temp file
            if (! $this->isLocalhost($server->ip_address ?? '127.0.0.1')) {
                $this->executeSSHCommand($server, "rm -f {$remotePath}");
            }

            // Cleanup local temp file if downloaded from S3
            if ($backup->storage_disk === 's3' && file_exists($localPath)) {
                @unlink($localPath);
            }

            Log::info('Database restored from backup successfully', [
                'backup_id' => $backup->id,
                'database' => $backup->database_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Database restore failed', [
                'backup_id' => $backup->id,
                'database' => $backup->database_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Verify backup integrity using checksum
     */
    public function verifyBackup(DatabaseBackup $backup): bool
    {
        try {
            $filePath = $this->downloadBackup($backup);

            // Calculate current checksum
            $currentChecksum = $this->calculateChecksum($filePath);

            // Compare with stored checksum
            $isValid = $currentChecksum === $backup->checksum;

            if ($isValid) {
                $backup->update(['verified_at' => now()]);

                Log::info('Backup verified successfully', [
                    'backup_id' => $backup->id,
                    'checksum' => $currentChecksum,
                ]);
            } else {
                Log::error('Backup verification failed - checksum mismatch', [
                    'backup_id' => $backup->id,
                    'stored_checksum' => $backup->checksum,
                    'calculated_checksum' => $currentChecksum,
                ]);
            }

            // Clean up temp file if S3
            if ($backup->storage_disk === 's3' && file_exists($filePath)) {
                @unlink($filePath);
            }

            return $isValid;

        } catch (\Exception $e) {
            Log::error('Backup verification error', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up old backups based on enhanced retention policy
     */
    public function applyRetentionPolicy(Project $project): int
    {
        $schedules = BackupSchedule::where('project_id', $project->id)
            ->where('is_active', true)
            ->get();

        $totalDeleted = 0;

        foreach ($schedules as $schedule) {
            $totalDeleted += $this->cleanupOldBackups($schedule);
        }

        return $totalDeleted;
    }

    /**
     * Clean up old backups based on retention policy
     */
    public function cleanupOldBackups(BackupSchedule $schedule): int
    {
        $backups = DatabaseBackup::where('project_id', $schedule->project_id)
            ->where('database_name', $schedule->database_name)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $toKeep = $this->selectBackupsToKeep(
            $backups,
            $schedule->retention_daily ?? 7,
            $schedule->retention_weekly ?? 4,
            $schedule->retention_monthly ?? 3
        );

        $count = 0;
        foreach ($backups as $backup) {
            if (! in_array($backup->id, $toKeep)) {
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
        }

        Log::info('Old backups cleaned up', [
            'schedule_id' => $schedule->id,
            'count' => $count,
            'kept' => count($toKeep),
        ]);

        return $count;
    }

    /**
     * Select backups to keep based on retention policy
     *
     * @param  Collection<int, DatabaseBackup>  $backups
     * @return array<int>
     */
    protected function selectBackupsToKeep(
        Collection $backups,
        int $dailyCount,
        int $weeklyCount,
        int $monthlyCount
    ): array {
        $keep = [];
        $now = Carbon::now();

        // Keep daily backups
        $dailyBackups = $backups->filter(function ($backup) use ($now) {
            return $backup->created_at->isAfter($now->copy()->subDays(30));
        })->sortByDesc('created_at')->take($dailyCount);

        foreach ($dailyBackups as $backup) {
            $keep[] = $backup->id;
        }

        // Keep weekly backups (one per week)
        $weeklyBackups = $backups->filter(function ($backup) use ($now) {
            return $backup->created_at->isAfter($now->copy()->subWeeks(12));
        })->groupBy(function ($backup) {
            return $backup->created_at->format('Y-W');
        })->map(function ($group) {
            return $group->sortByDesc('created_at')->first();
        })->sortByDesc('created_at')->take($weeklyCount);

        foreach ($weeklyBackups as $backup) {
            if ($backup !== null) {
                $keep[] = $backup->id;
            }
        }

        // Keep monthly backups (one per month)
        $monthlyBackups = $backups->groupBy(function ($backup) {
            return $backup->created_at->format('Y-m');
        })->map(function ($group) {
            return $group->sortByDesc('created_at')->first();
        })->sortByDesc('created_at')->take($monthlyCount);

        foreach ($monthlyBackups as $backup) {
            if ($backup !== null) {
                $keep[] = $backup->id;
            }
        }

        return array_unique($keep);
    }

    /**
     * Upload file to S3
     */
    public function uploadToS3(string $localPath, string $s3FileName): string
    {
        $s3Path = 'backups/'.date('Y/m/d').'/'.$s3FileName;

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
        $directory = storage_path('app/backups/'.date('Y/m/d'));
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory.'/'.$backup->file_name;
    }

    /**
     * Download file from remote server via SCP
     */
    protected function downloadFile(Server $server, string $remotePath, string $localPath): void
    {
        $scpOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-P '.$server->port,
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
                $sshOptions[] = '-i '.$keyFile;
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('File download failed: '.$process->getErrorOutput());
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
            '-P '.$server->port,
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
                $sshOptions[] = '-i '.$keyFile;
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('File upload failed: '.$process->getErrorOutput());
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('SSH command failed: '.$process->getErrorOutput());
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
                escapeshellarg($remoteCommand)
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
            escapeshellarg($remoteCommand)
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

    /**
     * Calculate SHA-256 checksum for file
     */
    protected function calculateChecksum(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $hash = hash_file('sha256', $filePath);
        if ($hash === false) {
            throw new \RuntimeException("Failed to calculate checksum for: {$filePath}");
        }

        return $hash;
    }

    /**
     * Encrypt backup file (optional feature)
     */
    protected function encryptBackup(string $filePath): string
    {
        $key = config('app.key');
        $encryptedPath = $filePath.'.enc';

        $inputFile = fopen($filePath, 'rb');
        $outputFile = fopen($encryptedPath, 'wb');

        $iv = random_bytes(16);
        fwrite($outputFile, $iv);

        while (! feof($inputFile)) {
            $chunk = fread($inputFile, 8192);
            $encrypted = openssl_encrypt($chunk, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            fwrite($outputFile, $encrypted);
        }

        fclose($inputFile);
        fclose($outputFile);

        // Remove original file
        @unlink($filePath);

        return $encryptedPath;
    }

    /**
     * Decrypt backup file
     */
    protected function decryptBackup(string $encryptedPath): string
    {
        if (! file_exists($encryptedPath)) {
            throw new \RuntimeException("Encrypted backup file not found: {$encryptedPath}");
        }

        $key = config('app.key');
        $decryptedPath = preg_replace('/\.enc$/', '', $encryptedPath);

        if ($decryptedPath === $encryptedPath) {
            $decryptedPath = $encryptedPath.'.decrypted';
        }

        $inputFile = fopen($encryptedPath, 'rb');
        if ($inputFile === false) {
            throw new \RuntimeException("Failed to open encrypted backup file: {$encryptedPath}");
        }

        $outputFile = fopen($decryptedPath, 'wb');
        if ($outputFile === false) {
            fclose($inputFile);
            throw new \RuntimeException("Failed to create decrypted backup file: {$decryptedPath}");
        }

        // Read IV from the beginning of the file
        $iv = fread($inputFile, 16);
        if (strlen($iv) !== 16) {
            fclose($inputFile);
            fclose($outputFile);
            throw new \RuntimeException('Invalid encrypted backup file: IV not found');
        }

        while (! feof($inputFile)) {
            $chunk = fread($inputFile, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $decrypted = openssl_decrypt($chunk, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($decrypted === false) {
                fclose($inputFile);
                fclose($outputFile);
                @unlink($decryptedPath);
                throw new \RuntimeException('Failed to decrypt backup file. The encryption key may be incorrect.');
            }

            fwrite($outputFile, $decrypted);
        }

        fclose($inputFile);
        fclose($outputFile);

        // Remove encrypted file
        @unlink($encryptedPath);

        return $decryptedPath;
    }

    /**
     * Parse table count from MySQL metadata output
     */
    protected function parseTableCount(string $output): int
    {
        preg_match('/table_count\s+(\d+)/i', $output, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * Execute SSH command and return output
     */
    protected function executeSSHCommandOutput(Server $server, string $remoteCommand): string
    {
        $command = $this->buildSSHCommand($server, $remoteCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return '';
    }
}
