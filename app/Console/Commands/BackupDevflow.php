<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDevflow extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'devflow:backup-self';

    /**
     * The console command description.
     */
    protected $description = "Backup DevFlow's own database (SQLite or PostgreSQL)";

    public function handle(): int
    {
        $backupDir = storage_path('backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $connection = config('database.default');

        try {
            if ($connection === 'sqlite') {
                return $this->backupSqlite($backupDir, $timestamp);
            }

            if ($connection === 'pgsql') {
                return $this->backupPgsql($backupDir, $timestamp);
            }

            $this->error("Unsupported DB_CONNECTION: {$connection}");

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Backup failed: '.$e->getMessage());
            Log::error('DevFlow self-backup failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    private function backupSqlite(string $backupDir, string $timestamp): int
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! $dbPath || ! file_exists($dbPath)) {
            $this->error("SQLite database file not found: {$dbPath}");

            return self::FAILURE;
        }

        $destination = "{$backupDir}/devflow_{$timestamp}.sqlite";
        copy($dbPath, $destination);

        $this->info("Backup saved: {$destination}");
        $this->pruneOldBackups($backupDir, 30);

        return self::SUCCESS;
    }

    private function backupPgsql(string $backupDir, string $timestamp): int
    {
        $dbName = config('database.connections.pgsql.database');
        $destination = "{$backupDir}/devflow_{$timestamp}.sql";

        $exitCode = 0;
        system("pg_dump {$dbName} > {$destination}", $exitCode);

        if ($exitCode !== 0) {
            $this->error('pg_dump failed with exit code: '.$exitCode);

            return self::FAILURE;
        }

        $this->info("Backup saved: {$destination}");
        $this->pruneOldBackups($backupDir, 30);

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $backupDir, int $keep): void
    {
        $files = glob("{$backupDir}/devflow_*");

        if ($files === false || count($files) <= $keep) {
            return;
        }

        // Sort oldest first
        usort($files, fn (string $a, string $b): int => filemtime($a) <=> filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $keep);
        foreach ($toDelete as $file) {
            unlink($file);
        }
    }
}
