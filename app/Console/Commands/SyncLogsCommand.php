<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\LogAggregationService;
use Illuminate\Console\Command;

class SyncLogsCommand extends Command
{
    protected $signature = 'logs:sync {--server= : Specific server ID to sync}';

    protected $description = 'Sync logs from all active log sources';

    public function __construct(
        private readonly LogAggregationService $logService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting log synchronization...');

        $servers = $this->option('server')
            ? Server::where('id', $this->option('server'))->get()
            : Server::where('status', 'online')->get();

        if ($servers->isEmpty()) {
            $this->error('No servers found for synchronization');

            return self::FAILURE;
        }

        $totalEntries = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($servers as $server) {
            $this->info("Syncing logs from: {$server->name}");

            try {
                $results = $this->logService->syncLogs($server);

                $totalEntries += $results['total_entries'];
                $totalSuccess += $results['success'];
                $totalFailed += $results['failed'];

                $this->line("  ✓ Synced {$results['total_entries']} entries from {$results['success']} sources");

                if ($results['failed'] > 0) {
                    $this->warn("  ⚠ {$results['failed']} sources failed:");
                    foreach ($results['errors'] as $error) {
                        $this->line("    - {$error['source']}: {$error['error']}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to sync {$server->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Synchronization completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Entries', number_format($totalEntries)],
                ['Successful Sources', $totalSuccess],
                ['Failed Sources', $totalFailed],
            ]
        );

        return self::SUCCESS;
    }
}
