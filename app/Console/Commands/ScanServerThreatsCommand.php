<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Security\IncidentResponseService;
use App\Services\Security\ThreatDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanServerThreatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:scan-threats
                            {--server-id= : Scan a specific server by ID}
                            {--all : Scan all online servers}
                            {--auto-remediate : Automatically remediate critical/high threats}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan servers for security threats and create incidents';

    /**
     * Execute the console command.
     */
    public function handle(
        ThreatDetectionService $threatService,
        IncidentResponseService $responseService
    ): int {
        $this->info('Starting security threat scan...');

        // Determine which servers to scan
        $query = Server::query()->where('status', '!=', 'offline');

        if ($serverId = $this->option('server-id')) {
            $query->where('id', $serverId);
        } elseif (! $this->option('all')) {
            $this->error('Please specify --server-id=<id> or --all to scan all servers');

            return Command::FAILURE;
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->warn('No servers found to scan.');

            return Command::SUCCESS;
        }

        $this->info("Scanning {$servers->count()} server(s) for threats...");
        $this->newLine();

        $totalThreats = 0;
        $totalIncidents = 0;
        $errors = 0;

        foreach ($servers as $server) {
            $this->line("Scanning: <info>{$server->name}</info> ({$server->ip_address})");

            try {
                // Run threat scan
                $scanResult = $threatService->scanServer($server);
                $threats = $scanResult['threats'];
                $scanTime = $scanResult['scan_time'];

                $this->line("  Scan completed in {$scanTime}s");

                if (empty($threats)) {
                    $this->line('  <fg=green>No threats detected</>');
                    $this->newLine();

                    continue;
                }

                $threatCount = count($threats);
                $totalThreats += $threatCount;

                $this->line("  <fg=red>Found {$threatCount} threat(s)!</>");

                // Display threats
                foreach ($threats as $threat) {
                    $severityColor = match ($threat['severity']) {
                        'critical' => 'red',
                        'high' => 'yellow',
                        'medium' => 'blue',
                        default => 'white',
                    };
                    $this->line("    - <fg={$severityColor}>[{$threat['severity']}]</> {$threat['title']}");
                }

                // Create incidents
                $incidents = $threatService->createIncidentsFromThreats($server, $threats);
                $totalIncidents += count($incidents);

                $this->line("  <fg=cyan>Created ".count($incidents).' incident(s)</>');

                // Auto-remediate if enabled
                if ($this->option('auto-remediate')) {
                    foreach ($incidents as $incident) {
                        if (in_array($incident->severity, ['critical', 'high'], true)) {
                            $this->line("  Auto-remediating: {$incident->title}");
                            $result = $responseService->autoRemediate($incident);

                            foreach ($result['actions'] as $action) {
                                $status = $action['success'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
                                $this->line("    {$status} {$action['action']}: {$action['message']}");
                            }
                        }
                    }
                }

                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  Error scanning server: {$e->getMessage()}");
                Log::error('Threat scan failed', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
                $this->newLine();
            }
        }

        // Summary
        $this->newLine();
        $this->info('Scan Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Servers scanned', $servers->count()],
                ['Total threats found', $totalThreats],
                ['Incidents created', $totalIncidents],
                ['Errors', $errors],
            ]
        );

        if ($totalThreats > 0) {
            $this->newLine();
            $this->warn('⚠️  Security threats were detected! Review incidents in the dashboard.');
        }

        return Command::SUCCESS;
    }
}
