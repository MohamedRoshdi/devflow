<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Security\PredictiveSecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CaptureSecurityBaselineCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'security:capture-baseline
                            {--server-id= : Capture baseline for a specific server}
                            {--all : Capture baselines for all guardian-enabled servers}';

    /**
     * @var string
     */
    protected $description = 'Capture the current server state as a security baseline for comparison';

    public function handle(PredictiveSecurityService $predictiveService): int
    {
        $this->info('Capturing security baselines...');

        $query = Server::query()->where('status', '!=', 'offline');

        if ($serverId = $this->option('server-id')) {
            $query->where('id', $serverId);
        } elseif ($this->option('all')) {
            $query->where('guardian_enabled', true);
        } else {
            $this->error('Please specify --server-id=<id> or --all');

            return Command::FAILURE;
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->warn('No servers found.');

            return Command::SUCCESS;
        }

        $captured = 0;
        $errors = 0;

        foreach ($servers as $server) {
            $this->line("Capturing baseline: <info>{$server->name}</info>");

            try {
                $baseline = $predictiveService->captureBaseline($server);

                $this->line("  Services: ".count($baseline->running_services));
                $this->line("  Ports: ".count($baseline->listening_ports));
                $this->line("  Users: ".count($baseline->system_users));
                $this->line("  Crontabs: ".count($baseline->crontab_entries));
                $this->line("  Avg CPU: {$baseline->avg_cpu_usage}%");
                $this->line("  Avg Memory: {$baseline->avg_memory_usage}%");
                $this->line('  <fg=green>Baseline captured successfully</>');

                $captured++;
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('Baseline capture failed', ['server_id' => $server->id, 'error' => $e->getMessage()]);
                $errors++;
            }

            $this->newLine();
        }

        $this->info("Baselines captured: {$captured}, Errors: {$errors}");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
