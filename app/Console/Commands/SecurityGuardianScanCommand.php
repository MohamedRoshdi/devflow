<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Security\SecurityGuardianService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SecurityGuardianScanCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'security:guardian-scan
                            {--server-id= : Scan a specific server by ID}
                            {--all : Scan all guardian-enabled servers}
                            {--auto-remediate : Automatically remediate detected threats}';

    /**
     * @var string
     */
    protected $description = 'Run Security Guardian comprehensive scan with threat detection and predictive analysis';

    public function handle(SecurityGuardianService $guardianService): int
    {
        $this->info('Starting Security Guardian scan...');

        if ($this->option('all')) {
            return $this->scanAllServers($guardianService);
        }

        $serverId = $this->option('server-id');
        if (! $serverId) {
            $this->error('Please specify --server-id=<id> or --all');

            return Command::FAILURE;
        }

        $server = Server::find($serverId);
        if (! $server) {
            $this->error("Server with ID {$serverId} not found.");

            return Command::FAILURE;
        }

        return $this->scanServer($guardianService, $server);
    }

    private function scanServer(SecurityGuardianService $guardianService, Server $server): int
    {
        $this->line("Scanning: <info>{$server->name}</info> ({$server->ip_address})");

        try {
            $autoRemediate = (bool) $this->option('auto-remediate');
            $results = $guardianService->runFullScan($server, $autoRemediate);

            $this->displayResults($server, $results);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error scanning server: {$e->getMessage()}");
            Log::error('Guardian scan failed', ['server_id' => $server->id, 'error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    private function scanAllServers(SecurityGuardianService $guardianService): int
    {
        $autoRemediate = (bool) $this->option('auto-remediate');
        $allResults = $guardianService->runScanAllServers($autoRemediate);

        if (empty($allResults)) {
            $this->warn('No guardian-enabled servers found.');

            return Command::SUCCESS;
        }

        $totalThreats = 0;
        $totalPredictions = 0;
        $totalIncidents = 0;

        foreach ($allResults as $serverId => $results) {
            $server = Server::find($serverId);
            if (! $server) {
                continue;
            }

            $this->displayResults($server, $results);

            $totalThreats += count($results['threats'] ?? []);
            $totalPredictions += count($results['predictions'] ?? []);
            $totalIncidents += count($results['incidents'] ?? []);
        }

        $this->newLine();
        $this->info('Overall Summary:');
        $this->table(['Metric', 'Value'], [
            ['Servers scanned', count($allResults)],
            ['Total threats', $totalThreats],
            ['Total incidents', $totalIncidents],
            ['Total predictions', $totalPredictions],
        ]);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function displayResults(Server $server, array $results): void
    {
        $threats = $results['threats'] ?? [];
        $predictions = $results['predictions'] ?? [];
        $incidents = $results['incidents'] ?? [];
        $scanTime = $results['scan_time'] ?? 0;

        $this->newLine();
        $this->line("<fg=cyan>Server:</> {$server->name} ({$server->ip_address})");
        $this->line("  Scan time: {$scanTime}s");

        if (empty($threats)) {
            $this->line('  <fg=green>No threats detected</>');
        } else {
            $this->line('  <fg=red>'.count($threats).' threat(s) found:</>');
            foreach ($threats as $threat) {
                $severityColor = match ($threat['severity'] ?? 'medium') {
                    'critical' => 'red',
                    'high' => 'yellow',
                    'medium' => 'blue',
                    default => 'white',
                };
                $this->line("    - <fg={$severityColor}>[{$threat['severity']}]</> {$threat['title']}");
            }
        }

        if (! empty($predictions)) {
            $this->line('  <fg=yellow>'.count($predictions).' prediction(s):</>');
            foreach ($predictions as $prediction) {
                $this->line("    - [{$prediction->severity}] {$prediction->title}");
            }
        }

        if (! empty($results['remediation_results'])) {
            $this->line('  <fg=magenta>Remediation results:</>');
            foreach ($results['remediation_results'] as $result) {
                $actions = $result['actions'] ?? [];
                foreach ($actions as $action) {
                    $status = ($action['success'] ?? false) ? '<fg=green>OK</>' : '<fg=red>FAIL</>';
                    $this->line("    {$status} {$action['action']}: {$action['message']}");
                }
            }
        }

        $this->line('  Incidents created: '.count($incidents));
    }
}
