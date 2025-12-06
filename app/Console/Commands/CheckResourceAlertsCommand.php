<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\ResourceAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckResourceAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check
                            {--server-id= : Check alerts for a specific server ID}
                            {--force : Force check even if cooldown is active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check resource alerts for all servers and trigger notifications';

    /**
     * Execute the console command.
     */
    public function handle(ResourceAlertService $alertService): int
    {
        $this->info('Starting resource alert check...');

        // Get servers to check
        $query = Server::query()
            ->where('status', '!=', 'offline')
            ->whereHas('resourceAlerts', function ($query) {
                $query->where('is_active', true);
            });

        if ($serverId = $this->option('server-id')) {
            $query->where('id', $serverId);
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->warn('No servers with active alerts found.');

            return Command::SUCCESS;
        }

        $this->info("Checking alerts for {$servers->count()} server(s)...");

        $totalChecked = 0;
        $totalTriggered = 0;
        $totalResolved = 0;
        $errors = 0;

        foreach ($servers as $server) {
            $this->line("Checking server: {$server->name} ({$server->ip_address})");

            try {
                $result = $alertService->evaluateAlerts($server);

                if (isset($result['error'])) {
                    $this->error("  Error: {$result['error']}");
                    $errors++;

                    continue;
                }

                $checked = $result['checked'] ?? 0;
                $triggered = $result['triggered'] ?? 0;
                $resolved = $result['resolved'] ?? 0;

                $totalChecked += $checked;
                $totalTriggered += $triggered;
                $totalResolved += $resolved;

                $this->info("  Checked: {$checked} | Triggered: {$triggered} | Resolved: {$resolved}");

            } catch (\Exception $e) {
                $this->error("  Failed to check alerts: {$e->getMessage()}");
                Log::error('Alert check failed', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }

        $this->newLine();
        $this->info('Alert check completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Servers Checked', $servers->count()],
                ['Alerts Checked', $totalChecked],
                ['Alerts Triggered', $totalTriggered],
                ['Alerts Resolved', $totalResolved],
                ['Errors', $errors],
            ]
        );

        if ($errors > 0) {
            $this->warn("Completed with {$errors} error(s). Check logs for details.");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
