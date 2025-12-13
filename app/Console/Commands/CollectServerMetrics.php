<?php

namespace App\Console\Commands;

use App\Events\ServerMetricsUpdated;
use App\Models\Server;
use App\Services\ServerMetricsService;
use Illuminate\Console\Command;

class CollectServerMetrics extends Command
{
    protected $signature = 'servers:collect-metrics {server_id?} {--broadcast : Broadcast metrics via WebSocket}';

    protected $description = 'Collect metrics from servers';

    public function handle(ServerMetricsService $metricsService): int
    {
        $serverId = $this->argument('server_id');
        $shouldBroadcast = $this->option('broadcast');

        if ($serverId) {
            $server = Server::find($serverId);

            if (! $server) {
                $this->error("Server with ID {$serverId} not found.");

                return self::FAILURE;
            }

            return $this->collectMetricsForServer($server, $metricsService, $shouldBroadcast);
        }

        // Collect metrics for all online servers
        $servers = Server::where('status', 'online')->get();

        if ($servers->isEmpty()) {
            $this->info('No online servers found.');

            return self::SUCCESS;
        }

        $this->info("Collecting metrics from {$servers->count()} servers...");

        $successCount = 0;
        $failCount = 0;

        foreach ($servers as $server) {
            $result = $this->collectMetricsForServer($server, $metricsService, $shouldBroadcast);

            if ($result === self::SUCCESS) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $this->info("Metrics collection completed. Success: {$successCount}, Failed: {$failCount}");

        return self::SUCCESS;
    }

    protected function collectMetricsForServer(Server $server, ServerMetricsService $metricsService, bool $broadcast = false): int
    {
        $this->info("Collecting metrics for: {$server->name} ({$server->ip_address})");

        try {
            $metric = $metricsService->collectMetrics($server);

            $this->line("  ✓ CPU: {$metric->cpu_usage}%");
            $this->line("  ✓ Memory: {$metric->memory_usage}%");
            $this->line("  ✓ Disk: {$metric->disk_usage}%");

            // Broadcast the metrics update via WebSocket
            if ($broadcast) {
                event(new ServerMetricsUpdated($server, $metric));
                $this->line('  ✓ Broadcast sent');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('  ✗ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
