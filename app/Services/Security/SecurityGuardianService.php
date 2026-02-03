<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Events\AutoRemediationCompleted;
use App\Models\RemediationLog;
use App\Models\SecurityBaseline;
use App\Models\SecurityEvent;
use App\Models\SecurityIncident;
use App\Models\SecurityPrediction;
use App\Models\SecurityScan;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

/**
 * Top-level security orchestrator that coordinates threat detection,
 * incident response, and predictive security analysis.
 *
 * Acts as the single entry point for comprehensive security operations
 * including full server scans, auto-remediation, and dashboard aggregation.
 */
class SecurityGuardianService
{
    public function __construct(
        private readonly ThreatDetectionService $threatDetection,
        private readonly IncidentResponseService $incidentResponse,
        private readonly PredictiveSecurityService $predictiveSecurity,
    ) {}

    /**
     * Run a full guardian scan on a server: threat detection, incident creation,
     * predictive analysis, and optional auto-remediation.
     *
     * @return array{
     *     threats: array<int, array<string, mixed>>,
     *     incidents: array<int, SecurityIncident>,
     *     predictions: array<int, array<string, mixed>>,
     *     remediation_results: array<int, array{success: bool, actions: array<int, array{action: string, success: bool, message: string}>}>,
     *     scan_time: float,
     * }
     */
    public function runFullScan(Server $server, bool $autoRemediate = false, ?int $userId = null): array
    {
        $startTime = microtime(true);

        Log::info('SecurityGuardian: Starting full scan', [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'auto_remediate' => $autoRemediate,
        ]);

        // Step 1: Run guardian-enhanced threat scan
        $scanResult = $this->threatDetection->scanServerGuardian($server);
        $threats = $scanResult['threats'];

        // Step 2: Create incidents from detected threats
        $incidents = [];
        if (! empty($threats)) {
            $incidents = $this->threatDetection->createIncidentsFromThreats($server, $threats, $userId);
        }

        // Step 3: Run predictive security analysis
        $predictions = $this->predictiveSecurity->analyzeServer($server);

        // Step 4: Auto-remediate if enabled and allowed
        $remediationResults = [];
        if ($autoRemediate && $server->auto_remediation_enabled) {
            $remediationResults = $this->runAutoRemediation($server, $incidents);
        }

        $scanTime = round(microtime(true) - $startTime, 2);

        // Log the guardian scan event
        SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => SecurityEvent::TYPE_GUARDIAN_SCAN,
            'details' => "Guardian scan completed: " . count($threats) . " threats, " . count($predictions) . " predictions",
            'metadata' => [
                'threats_count' => count($threats),
                'incidents_count' => count($incidents),
                'predictions_count' => count($predictions),
                'remediation_count' => count($remediationResults),
                'auto_remediate' => $autoRemediate,
                'scan_time' => $scanTime,
                'user_id' => $userId,
            ],
        ]);

        Log::info('SecurityGuardian: Full scan completed', [
            'server_id' => $server->id,
            'threats_found' => count($threats),
            'incidents_created' => count($incidents),
            'predictions_generated' => count($predictions),
            'remediations_executed' => count($remediationResults),
            'scan_time' => $scanTime,
        ]);

        return [
            'threats' => $threats,
            'incidents' => $incidents,
            'predictions' => $predictions,
            'remediation_results' => $remediationResults,
            'scan_time' => $scanTime,
        ];
    }

    /**
     * Run a full scan on all guardian-enabled servers.
     *
     * @return array<int, array{
     *     threats: array<int, array<string, mixed>>,
     *     incidents: array<int, SecurityIncident>,
     *     predictions: array<int, array<string, mixed>>,
     *     remediation_results: array<int, array{success: bool, actions: array<int, array{action: string, success: bool, message: string}>}>,
     *     scan_time: float,
     * }>
     */
    public function runScanAllServers(bool $autoRemediate = false): array
    {
        $servers = Server::where('guardian_enabled', true)->get();
        $results = [];

        foreach ($servers as $server) {
            try {
                $results[$server->id] = $this->runFullScan($server, $autoRemediate);
            } catch (\Throwable $e) {
                Log::error('SecurityGuardian: Scan failed for server', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'error' => $e->getMessage(),
                ]);

                $results[$server->id] = [
                    'threats' => [],
                    'incidents' => [],
                    'predictions' => [],
                    'remediation_results' => [],
                    'scan_time' => 0.0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get a traffic-light security overview for a single server.
     *
     * @return array{
     *     active_incidents: int,
     *     active_predictions: int,
     *     latest_scan: array<string, mixed>|null,
     *     latest_baseline: array<string, mixed>|null,
     *     hardening_level: string|null,
     *     overall_status: string,
     * }
     */
    public function getServerSecurityOverview(Server $server): array
    {
        $activeIncidents = SecurityIncident::where('server_id', $server->id)
            ->active()
            ->get();

        $activePredictions = SecurityPrediction::where('server_id', $server->id)
            ->active()
            ->get();

        $latestScan = SecurityScan::where('server_id', $server->id)
            ->where('scan_type', SecurityScan::SCAN_TYPE_GUARDIAN)
            ->latest()
            ->first();

        $latestBaseline = SecurityBaseline::where('server_id', $server->id)
            ->latest()
            ->first();

        // Determine overall status using traffic-light logic
        $overallStatus = $this->determineOverallStatus(
            $activeIncidents,
            $activePredictions,
            $latestScan,
            $latestBaseline,
        );

        return [
            'active_incidents' => $activeIncidents->count(),
            'active_predictions' => $activePredictions->count(),
            'latest_scan' => $latestScan !== null ? [
                'id' => $latestScan->id,
                'status' => $latestScan->status,
                'score' => $latestScan->score,
                'risk_level' => $latestScan->risk_level,
                'completed_at' => $latestScan->completed_at?->toIso8601String(),
            ] : null,
            'latest_baseline' => $latestBaseline !== null ? [
                'id' => $latestBaseline->id,
                'created_at' => $latestBaseline->created_at?->toIso8601String(),
                'total_processes' => $latestBaseline->total_processes,
                'avg_cpu_usage' => $latestBaseline->avg_cpu_usage,
                'avg_memory_usage' => $latestBaseline->avg_memory_usage,
            ] : null,
            'hardening_level' => $server->hardening_level,
            'overall_status' => $overallStatus,
        ];
    }

    /**
     * Aggregate dashboard statistics across all guardian-enabled servers.
     *
     * @return array{
     *     total_servers: int,
     *     servers_secure: int,
     *     servers_warning: int,
     *     servers_critical: int,
     *     total_active_incidents: int,
     *     total_active_predictions: int,
     *     recent_remediations: int,
     * }
     */
    public function getDashboardStats(): array
    {
        $servers = Server::where('guardian_enabled', true)->get();

        $serversSecure = 0;
        $serversWarning = 0;
        $serversCritical = 0;

        foreach ($servers as $server) {
            $overview = $this->getServerSecurityOverview($server);

            match ($overview['overall_status']) {
                'secure' => $serversSecure++,
                'warning' => $serversWarning++,
                'critical' => $serversCritical++,
                default => null, // 'unknown' - not counted in any bucket
            };
        }

        $totalActiveIncidents = SecurityIncident::active()
            ->whereHas('server', fn ($q) => $q->where('guardian_enabled', true))
            ->count();

        $totalActivePredictions = SecurityPrediction::active()
            ->whereHas('server', fn ($q) => $q->where('guardian_enabled', true))
            ->count();

        $recentRemediations = RemediationLog::where('created_at', '>=', now()->subDay())
            ->whereHas('server', fn ($q) => $q->where('guardian_enabled', true))
            ->count();

        return [
            'total_servers' => $servers->count(),
            'servers_secure' => $serversSecure,
            'servers_warning' => $serversWarning,
            'servers_critical' => $serversCritical,
            'total_active_incidents' => $totalActiveIncidents,
            'total_active_predictions' => $totalActivePredictions,
            'recent_remediations' => $recentRemediations,
        ];
    }

    /**
     * Execute auto-remediation on a collection of incidents.
     *
     * @param array<int, SecurityIncident> $incidents
     * @return array<int, array{success: bool, actions: array<int, array{action: string, success: bool, message: string}>}>
     */
    private function runAutoRemediation(Server $server, array $incidents): array
    {
        $remediationResults = [];

        foreach ($incidents as $incident) {
            try {
                $result = $this->incidentResponse->autoRemediate($incident);
                $remediationResults[] = $result;
            } catch (\Throwable $e) {
                Log::warning('SecurityGuardian: Auto-remediation failed for incident', [
                    'incident_id' => $incident->id,
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);

                $remediationResults[] = [
                    'success' => false,
                    'actions' => [[
                        'action' => 'auto_remediation_error',
                        'success' => false,
                        'message' => $e->getMessage(),
                    ]],
                ];
            }
        }

        // Dispatch event when remediation batch completes
        if (! empty($remediationResults)) {
            event(new AutoRemediationCompleted($server, $remediationResults));

            SecurityEvent::create([
                'server_id' => $server->id,
                'event_type' => SecurityEvent::TYPE_AUTO_REMEDIATION,
                'details' => "Auto-remediation completed for " . count($remediationResults) . " incidents",
                'metadata' => [
                    'incidents_count' => count($remediationResults),
                    'successful' => count(array_filter($remediationResults, fn (array $r): bool => $r['success'])),
                    'failed' => count(array_filter($remediationResults, fn (array $r): bool => ! $r['success'])),
                ],
            ]);
        }

        return $remediationResults;
    }

    /**
     * Determine the overall security status using traffic-light logic.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, SecurityIncident> $activeIncidents
     * @param \Illuminate\Database\Eloquent\Collection<int, SecurityPrediction> $activePredictions
     */
    private function determineOverallStatus(
        \Illuminate\Database\Eloquent\Collection $activeIncidents,
        \Illuminate\Database\Eloquent\Collection $activePredictions,
        ?SecurityScan $latestScan,
        ?SecurityBaseline $latestBaseline,
    ): string {
        // No scan or baseline data means we cannot determine status
        if ($latestScan === null && $latestBaseline === null) {
            return 'unknown';
        }

        // Any critical-severity active incident => critical
        $hasCriticalIncident = $activeIncidents->contains(
            fn (SecurityIncident $incident): bool => $incident->severity === SecurityIncident::SEVERITY_CRITICAL
        );

        if ($hasCriticalIncident) {
            return 'critical';
        }

        // Any high-severity active incident or high-severity active prediction => warning
        $hasHighIncident = $activeIncidents->contains(
            fn (SecurityIncident $incident): bool => $incident->severity === SecurityIncident::SEVERITY_HIGH
        );

        $hasHighPrediction = $activePredictions->contains(
            fn (SecurityPrediction $prediction): bool => $prediction->severity === SecurityIncident::SEVERITY_HIGH
                || $prediction->severity === SecurityIncident::SEVERITY_CRITICAL
        );

        if ($hasHighIncident || $hasHighPrediction) {
            return 'warning';
        }

        return 'secure';
    }
}
