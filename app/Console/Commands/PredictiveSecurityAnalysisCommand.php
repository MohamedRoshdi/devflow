<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Security\PredictiveSecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PredictiveSecurityAnalysisCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'security:predict
                            {--server-id= : Analyze a specific server by ID}
                            {--all : Analyze all guardian-enabled servers}';

    /**
     * @var string
     */
    protected $description = 'Run predictive security analysis on server metrics and baselines';

    public function handle(PredictiveSecurityService $predictiveService): int
    {
        $this->info('Starting predictive security analysis...');

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
            $this->warn('No servers found for analysis.');

            return Command::SUCCESS;
        }

        $this->info("Analyzing {$servers->count()} server(s)...");
        $totalPredictions = 0;

        foreach ($servers as $server) {
            $this->line("Analyzing: <info>{$server->name}</info>");

            try {
                $predictions = $predictiveService->analyzeServer($server);

                if (empty($predictions)) {
                    $this->line('  <fg=green>No issues predicted</>');
                } else {
                    $this->line('  <fg=yellow>'.count($predictions).' prediction(s):</>');
                    foreach ($predictions as $prediction) {
                        $confidence = round($prediction->confidence_score * 100);
                        $this->line("    - [{$prediction->severity}] {$prediction->title} ({$confidence}% confidence)");
                    }
                    $totalPredictions += count($predictions);
                }

                // Show baseline drift
                $drifts = $predictiveService->detectBaselineDrift($server);
                if (! empty($drifts)) {
                    $this->line('  <fg=cyan>Baseline drifts detected:</>');
                    foreach ($drifts as $category => $drift) {
                        $added = $drift['added'] ?? [];
                        $removed = $drift['removed'] ?? [];
                        if (! empty($added)) {
                            $this->line("    + {$category}: ".implode(', ', array_slice($added, 0, 5)));
                        }
                        if (! empty($removed)) {
                            $this->line("    - {$category}: ".implode(', ', array_slice($removed, 0, 5)));
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('Predictive analysis failed', ['server_id' => $server->id, 'error' => $e->getMessage()]);
            }

            $this->newLine();
        }

        $this->info("Analysis complete. {$totalPredictions} prediction(s) created.");

        return Command::SUCCESS;
    }
}
