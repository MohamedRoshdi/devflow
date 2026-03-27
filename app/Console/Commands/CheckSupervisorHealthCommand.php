<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Monitoring\SupervisorHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Checks supervisor process health on all online servers and alerts on FATAL/STOPPED processes.
 *
 * Scheduled every 2 minutes via routes/console.php.
 */
class CheckSupervisorHealthCommand extends Command
{
    protected $signature = 'supervisor:check-health
                            {--server= : Check a specific server by ID}
                            {--alert : Send alert notifications (default: only log)}';

    protected $description = 'Check supervisor process health on all online servers and alert on unhealthy processes';

    public function __construct(private readonly SupervisorHealthService $supervisorHealth)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $servers = $this->resolveServers();

        if ($servers->isEmpty()) {
            $this->info('No online servers to check.');

            return self::SUCCESS;
        }

        $this->info("Checking supervisor health on {$servers->count()} server(s)...");

        $totalUnhealthy = 0;

        foreach ($servers as $server) {
            $totalUnhealthy += $this->checkServer($server);
        }

        if ($totalUnhealthy > 0) {
            $this->warn("Found {$totalUnhealthy} unhealthy process(es) across all servers.");

            return self::FAILURE;
        }

        $this->info('All supervisor processes are healthy.');

        return self::SUCCESS;
    }

    /**
     * Check a single server and return the count of unhealthy processes.
     */
    private function checkServer(Server $server): int
    {
        $this->line("  Checking {$server->name} ({$server->ip_address})...");

        try {
            $unhealthy = $this->supervisorHealth->getUnhealthyProcesses($server);

            if (empty($unhealthy)) {
                $this->line('    <info>✓ All processes healthy</info>');

                return 0;
            }

            foreach ($unhealthy as $process) {
                $this->warn("    ✗ {$process['name']} → {$process['status']}");

                Log::warning('Supervisor process unhealthy', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'process' => $process['name'],
                    'status' => $process['status'],
                ]);
            }

            if ($this->option('alert')) {
                $this->sendAlert($server, $unhealthy);
            }

            return count($unhealthy);

        } catch (\Exception $e) {
            $this->error("    Failed to check {$server->name}: {$e->getMessage()}");

            Log::error('CheckSupervisorHealthCommand: error checking server', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Server>
     */
    private function resolveServers(): \Illuminate\Database\Eloquent\Collection
    {
        $serverId = $this->option('server');

        if ($serverId !== null) {
            $server = Server::where('status', 'online')->find((int) $serverId);

            return $server !== null
                ? collect([$server])
                : collect();
        }

        return Server::where('status', 'online')->get();
    }

    /**
     * Send an alert notification for unhealthy processes.
     *
     * @param  array<int, array{name: string, status: string, pid: string|null, uptime: string|null}>  $unhealthyProcesses
     */
    private function sendAlert(Server $server, array $unhealthyProcesses): void
    {
        $processLines = array_map(
            fn (array $p): string => "  - {$p['name']}: {$p['status']}",
            $unhealthyProcesses
        );

        $body = implode("\n", [
            "Supervisor Health Alert — {$server->name} ({$server->ip_address})",
            '',
            'The following processes are in an unhealthy state:',
            implode("\n", $processLines),
            '',
            'Please review the supervisor manager in DevFlow Pro to restart or investigate.',
        ]);

        $adminEmail = config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        try {
            Mail::raw($body, function ($message) use ($adminEmail, $server): void {
                $message->to($adminEmail)
                    ->subject("[DevFlow] Supervisor Alert — {$server->name}: unhealthy processes detected");
            });
        } catch (\Exception $e) {
            Log::error('CheckSupervisorHealthCommand: failed to send alert email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
