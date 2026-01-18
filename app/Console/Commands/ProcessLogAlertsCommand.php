<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LogAlertService;
use Illuminate\Console\Command;

class ProcessLogAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:process-alerts
                            {--server= : Process alerts for specific server ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process log alerts and send notifications when patterns match';

    /**
     * Execute the console command.
     */
    public function handle(LogAlertService $alertService): int
    {
        $this->info('🚨 Processing log alerts...');

        $serverId = $this->option('server');
        if ($serverId) {
            $this->line("Filtering for server ID: {$serverId}");
        }

        try {
            $results = $alertService->processAlerts($serverId ? (int) $serverId : null);

            $triggered = collect($results)->where('status', 'triggered')->count();
            $errors = collect($results)->where('status', 'error')->count();
            $ok = collect($results)->where('status', 'ok')->count();

            // Display results table
            $this->table(
                ['Alert Name', 'Status', 'Matches/Info'],
                collect($results)->map(fn($r) => [
                    $r['alert_name'],
                    $this->colorizeStatus($r['status']),
                    $r['match_count'] ?? $r['error'] ?? '-'
                ])
            );

            $this->newLine();
            $this->info("✅ Processed " . count($results) . " alert(s)");
            $this->line("  └─ Triggered: <fg=red>{$triggered}</> | OK: <fg=green>{$ok}</> | Errors: <fg=yellow>{$errors}</>");

            if ($triggered > 0) {
                $this->warn("⚠️  {$triggered} alert(s) were triggered. Notifications sent.");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process alerts: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Colorize status for terminal output.
     */
    protected function colorizeStatus(string $status): string
    {
        return match ($status) {
            'triggered' => "<fg=red>TRIGGERED</>",
            'ok' => "<fg=green>OK</>",
            'error' => "<fg=yellow>ERROR</>",
            default => $status,
        };
    }
}
