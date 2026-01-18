<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\SystemLogService;
use Illuminate\Console\Command;

class SyncSystemLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system-logs:sync
                            {--server= : Specific server ID to sync logs from}
                            {--type= : Specific log type to collect (system, auth, docker, nginx, etc.)}
                            {--lines=100 : Number of log lines to collect}
                            {--clean-old : Clean old logs after syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync system logs from servers';

    /**
     * Execute the console command.
     */
    public function handle(SystemLogService $logService): int
    {
        $this->info('Starting system log synchronization...');

        $serverId = $this->option('server');
        $logType = $this->option('type');
        $lines = (int) $this->option('lines');

        // Get servers to process
        $servers = $serverId
            ? Server::where('id', $serverId)->get()
            : Server::where('status', 'online')->get();

        if ($servers->isEmpty()) {
            $this->error('No servers found to sync logs from');
            return self::FAILURE;
        }

        $this->info("Processing {$servers->count()} server(s)...");

        $totalCollected = 0;
        $totalStored = 0;

        foreach ($servers as $server) {
            $this->line("Processing server: {$server->name}");

            try {
                // Collect logs
                $logs = $logService->collectLogsFromServer($server, $lines, $logType);
                $collected = $logs->count();

                // Store logs
                $stored = $logService->storeLogs($logs);

                $totalCollected += $collected;
                $totalStored += $stored;

                $this->info("  ✓ Collected: {$collected} | Stored: {$stored}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                continue;
            }
        }

        // Clean old logs if requested
        if ($this->option('clean-old')) {
            $this->line('Cleaning old logs...');
            $deleted = $logService->cleanOldLogs(30);
            $this->info("  ✓ Deleted {$deleted} old log entries");
        }

        $this->newLine();
        $this->info("Synchronization completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Collected', number_format($totalCollected)],
                ['Total Stored', number_format($totalStored)],
            ]
        );

        return self::SUCCESS;
    }
}
