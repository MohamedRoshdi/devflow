<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Monitoring\RedisHealthService;
use Illuminate\Console\Command;

class CheckRedisHealthCommand extends Command
{
    protected $signature = 'monitoring:check-redis-health
                            {--server= : Only check a specific server ID}';

    protected $description = 'Check Redis connectivity and health on all online servers';

    public function handle(RedisHealthService $service): int
    {
        $serverId = $this->option('server');

        $query = Server::where('status', 'online');

        if ($serverId !== null) {
            $query->where('id', $serverId);
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->info('No online servers found.');

            return self::SUCCESS;
        }

        $this->info("Checking Redis health on {$servers->count()} server(s)...");
        $this->newLine();

        $failCount = 0;

        foreach ($servers as $server) {
            $healthy = $this->checkServer($service, $server);

            if (! $healthy) {
                $failCount++;
            }
        }

        if ($failCount > 0) {
            $this->newLine();
            $this->error("{$failCount} server(s) reported Redis issues.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function checkServer(RedisHealthService $service, Server $server): bool
    {
        $this->line("<fg=cyan>Server: {$server->name} ({$server->ip_address})</>");

        $summary = $service->getHealthSummary($server);

        $statusColor = match ($summary['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            default => 'red',
        };

        $icon = $summary['reachable'] ? ($summary['status'] === 'healthy' ? '✓' : '⚠') : '✗';
        $this->line("  <fg={$statusColor}>{$icon}</> Redis: {$summary['status']}");

        if (! $summary['reachable']) {
            foreach ($summary['issues'] as $issue) {
                $this->line("    <fg=red>- {$issue}</>");
            }
            $this->newLine();

            return false;
        }

        $memory = $summary['memory'];
        $this->line("  Memory: {$memory['used_memory_human']} used / {$memory['maxmemory_human']} max (fragmentation: {$memory['mem_fragmentation_ratio']})");

        $clients = $summary['clients'];
        $this->line("  Clients: {$clients['connected_clients']} connected, {$clients['blocked_clients']} blocked");

        $serverInfo = $summary['server_info'];
        if ($serverInfo['redis_version'] !== 'N/A') {
            $this->line("  Version: {$serverInfo['redis_version']}, uptime: {$serverInfo['uptime_in_days']}d");
        }

        foreach ($summary['issues'] as $issue) {
            $this->line("  <fg=yellow>⚠ {$issue}</>");
        }

        $this->newLine();

        return $summary['status'] !== 'critical';
    }
}
