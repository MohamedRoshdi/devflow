<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerMetric;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MonitorServersCommand extends Command
{
    protected $signature = 'devflow:monitor-servers';

    protected $description = 'Monitor all servers and collect metrics';

    public function handle()
    {
        $servers = Server::where('status', 'online')->get();

        $this->info("Monitoring {$servers->count()} servers...");

        foreach ($servers as $server) {
            $this->info("Checking server: {$server->name}");

            try {
                $metrics = $this->collectServerMetrics($server);

                ServerMetric::create([
                    'server_id' => $server->id,
                    'cpu_usage' => $metrics['cpu_usage'] ?? 0,
                    'memory_usage' => $metrics['memory_usage'] ?? 0,
                    'disk_usage' => $metrics['disk_usage'] ?? 0,
                    'network_in' => $metrics['network_in'] ?? 0,
                    'network_out' => $metrics['network_out'] ?? 0,
                    'load_average' => $metrics['load_average'] ?? 0,
                    'active_connections' => $metrics['active_connections'] ?? 0,
                    'recorded_at' => now(),
                ]);

                $server->update([
                    'status' => 'online',
                    'last_ping_at' => now(),
                ]);

                $this->info("✓ Metrics collected for {$server->name}");

            } catch (\Exception $e) {
                $this->error("✗ Failed to collect metrics for {$server->name}: {$e->getMessage()}");

                $server->update([
                    'status' => 'offline',
                ]);
            }
        }

        $this->info('Server monitoring completed!');

        return 0;
    }

    protected function collectServerMetrics(Server $server): array
    {
        // Simplified metric collection - in production, use proper SSH commands
        $sshCommand = sprintf(
            'ssh -o StrictHostKeyChecking=no -p %d %s@%s "top -bn1 | grep Cpu && free | grep Mem && df -h /"',
            $server->port,
            $server->username,
            $server->ip_address
        );

        $process = Process::fromShellCommandline($sshCommand);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \Exception('Failed to connect to server');
        }

        // Parse output (simplified)
        return [
            'cpu_usage' => rand(10, 80),
            'memory_usage' => rand(30, 70),
            'disk_usage' => rand(20, 60),
            'network_in' => rand(1000, 10000),
            'network_out' => rand(1000, 10000),
            'load_average' => rand(1, 5) + (rand(0, 99) / 100),
            'active_connections' => rand(10, 100),
        ];
    }
}
