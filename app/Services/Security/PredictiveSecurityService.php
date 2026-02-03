<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Events\SecurityPredictionCreated;
use App\Models\SecurityBaseline;
use App\Models\SecurityIncident;
use App\Models\SecurityPrediction;
use App\Models\Server;
use App\Traits\ExecutesServerCommands;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PredictiveSecurityService
{
    use ExecutesServerCommands;

    /**
     * CPU anomaly threshold multiplier against baseline average.
     */
    private const CPU_ANOMALY_MULTIPLIER = 2.0;

    /**
     * Duration in minutes for sustained CPU anomaly detection.
     */
    private const CPU_ANOMALY_SUSTAINED_MINUTES = 15;

    /**
     * Memory exhaustion threshold percentage.
     */
    private const MEMORY_EXHAUSTION_THRESHOLD = 95.0;

    /**
     * Hours of memory data to use for trend projection.
     */
    private const MEMORY_TREND_HOURS = 2;

    /**
     * Hours ahead to project memory exhaustion.
     */
    private const MEMORY_PROJECTION_HOURS = 2;

    /**
     * Orchestrate all predictive security checks for a server.
     *
     * Runs CPU anomaly detection, memory exhaustion prediction, brute force
     * escalation analysis, and baseline drift detection. Returns an array of
     * all generated predictions.
     *
     * @param Server $server The server to analyze
     * @return array<int, array<string, mixed>> Array of prediction data arrays
     */
    public function analyzeServer(Server $server): array
    {
        $predictions = [];

        Log::info('Starting predictive security analysis', [
            'server_id' => $server->id,
            'server_name' => $server->name,
        ]);

        $cpuPrediction = $this->predictCpuAnomaly($server);
        if ($cpuPrediction !== null) {
            $predictions[] = $cpuPrediction;
        }

        $memoryPrediction = $this->predictMemoryExhaustion($server);
        if ($memoryPrediction !== null) {
            $predictions[] = $memoryPrediction;
        }

        $bruteForcePrediction = $this->predictBruteForceEscalation($server);
        if ($bruteForcePrediction !== null) {
            $predictions[] = $bruteForcePrediction;
        }

        $driftResults = $this->detectBaselineDrift($server);
        foreach ($driftResults as $drift) {
            $predictions[] = $drift;
        }

        Log::info('Predictive security analysis completed', [
            'server_id' => $server->id,
            'predictions_generated' => count($predictions),
        ]);

        return $predictions;
    }

    /**
     * Predict CPU anomaly indicating possible crypto miner activity.
     *
     * Examines the last 15 minutes of ServerMetric records. If the average CPU
     * usage during that window exceeds the baseline average multiplied by the
     * anomaly threshold (2.0x), a prediction is created with high confidence.
     *
     * @param Server $server The server to check
     * @return array<string, mixed>|null Prediction data or null if no anomaly detected
     */
    public function predictCpuAnomaly(Server $server): ?array
    {
        $baseline = $server->latestBaseline;
        if ($baseline === null) {
            return null;
        }

        $since = Carbon::now()->subMinutes(self::CPU_ANOMALY_SUSTAINED_MINUTES);

        /** @var Collection<int, \App\Models\ServerMetric> $recentMetrics */
        $recentMetrics = $server->metrics()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($recentMetrics->isEmpty()) {
            return null;
        }

        $avgCpu = (float) $recentMetrics->avg('cpu_usage');
        $baselineAvgCpu = $baseline->avg_cpu_usage;
        $threshold = $baselineAvgCpu * self::CPU_ANOMALY_MULTIPLIER;

        // Require baseline to be meaningful (above 1% to avoid false positives on idle servers)
        if ($baselineAvgCpu < 1.0) {
            $threshold = 80.0;
        }

        if ($avgCpu <= $threshold) {
            return null;
        }

        // Check all readings are above threshold (sustained anomaly)
        $allAboveThreshold = $recentMetrics->every(
            fn ($metric): bool => (float) $metric->cpu_usage > $baselineAvgCpu * 1.5
        );

        if (! $allAboveThreshold) {
            return null;
        }

        $confidence = min(0.95, 0.6 + (($avgCpu - $threshold) / 100.0) * 0.35);

        $prediction = $this->createPrediction(
            server: $server,
            type: SecurityPrediction::TYPE_CPU_ANOMALY,
            severity: SecurityIncident::SEVERITY_HIGH,
            title: 'Sustained CPU anomaly detected - possible crypto miner',
            description: sprintf(
                'Average CPU usage over the last %d minutes is %.1f%%, which is %.1fx the baseline average of %.1f%%. '
                .'This sustained elevated usage pattern is consistent with crypto mining activity.',
                self::CPU_ANOMALY_SUSTAINED_MINUTES,
                $avgCpu,
                $baselineAvgCpu > 0 ? round($avgCpu / $baselineAvgCpu, 1) : 0,
                $baselineAvgCpu
            ),
            evidence: [
                'avg_cpu_last_15min' => round($avgCpu, 2),
                'baseline_avg_cpu' => round($baselineAvgCpu, 2),
                'threshold' => round($threshold, 2),
                'sample_count' => $recentMetrics->count(),
                'peak_cpu' => round((float) $recentMetrics->max('cpu_usage'), 2),
                'min_cpu' => round((float) $recentMetrics->min('cpu_usage'), 2),
            ],
            confidence: $confidence,
        );

        return $prediction->toArray();
    }

    /**
     * Predict memory exhaustion using linear regression on recent metrics.
     *
     * Collects the last 2 hours of memory usage data, performs a simple linear
     * regression, and projects the trend forward. If projected usage exceeds 95%
     * within 2 hours, a critical prediction is generated.
     *
     * @param Server $server The server to check
     * @return array<string, mixed>|null Prediction data or null if no exhaustion predicted
     */
    public function predictMemoryExhaustion(Server $server): ?array
    {
        $since = Carbon::now()->subHours(self::MEMORY_TREND_HOURS);

        /** @var Collection<int, \App\Models\ServerMetric> $metrics */
        $metrics = $server->metrics()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at', 'asc')
            ->get();

        // Need at least 5 data points for meaningful regression
        if ($metrics->count() < 5) {
            return null;
        }

        // Perform linear regression: y = mx + b where y is memory_usage, x is time offset in seconds
        $firstRecordedAt = $metrics->first()?->recorded_at;
        if ($firstRecordedAt === null) {
            return null;
        }

        $n = $metrics->count();
        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($metrics as $metric) {
            $x = (float) $firstRecordedAt->diffInSeconds($metric->recorded_at);
            $y = (float) $metric->memory_usage;

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if (abs($denominator) < 0.0001) {
            return null;
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Only predict if memory is trending upward
        if ($slope <= 0.0) {
            return null;
        }

        // Project forward: how many seconds until memory hits threshold?
        $currentMemory = (float) ($metrics->last()?->memory_usage ?? 0);
        $currentTimeOffset = (float) $firstRecordedAt->diffInSeconds(Carbon::now());

        $projectedAtTwoHours = $intercept + ($slope * ($currentTimeOffset + (self::MEMORY_PROJECTION_HOURS * 3600)));

        if ($projectedAtTwoHours < self::MEMORY_EXHAUSTION_THRESHOLD) {
            return null;
        }

        // Calculate when threshold will be hit
        $secondsUntilThreshold = (self::MEMORY_EXHAUSTION_THRESHOLD - $intercept - ($slope * $currentTimeOffset)) / $slope;
        if ($secondsUntilThreshold < 0) {
            $secondsUntilThreshold = 0.0;
        }

        $predictedImpactAt = Carbon::now()->addSeconds((int) $secondsUntilThreshold);

        // Higher confidence when closer to threshold and trend is steeper
        $confidence = min(0.95, 0.5 + ($currentMemory / 200.0) + ($slope * 100.0));
        $confidence = max(0.4, $confidence);

        $prediction = $this->createPrediction(
            server: $server,
            type: SecurityPrediction::TYPE_MEMORY_EXHAUSTION,
            severity: SecurityIncident::SEVERITY_CRITICAL,
            title: 'Memory exhaustion predicted within ' . $this->formatTimeUntil($secondsUntilThreshold),
            description: sprintf(
                'Linear regression on %d data points over the last %d hours shows memory usage trending upward at '
                .'%.4f%%/second (%.2f%%/hour). Current usage is %.1f%%. Projected to reach %.1f%% in approximately %s.',
                $n,
                self::MEMORY_TREND_HOURS,
                $slope,
                $slope * 3600,
                $currentMemory,
                min($projectedAtTwoHours, 100.0),
                $this->formatTimeUntil($secondsUntilThreshold)
            ),
            evidence: [
                'current_memory_usage' => round($currentMemory, 2),
                'slope_per_second' => round($slope, 6),
                'slope_per_hour' => round($slope * 3600, 2),
                'projected_2h_usage' => round(min($projectedAtTwoHours, 100.0), 2),
                'data_points' => $n,
                'seconds_until_threshold' => round($secondsUntilThreshold, 0),
                'threshold' => self::MEMORY_EXHAUSTION_THRESHOLD,
            ],
            confidence: round($confidence, 2),
            predictedImpactAt: $predictedImpactAt,
        );

        return $prediction->toArray();
    }

    /**
     * Predict brute force attack escalation based on fail2ban ban rate.
     *
     * Parses fail2ban status for the sshd jail to determine the current number
     * of banned IPs. If the ban count is increasing (checked against recent
     * prediction evidence), an escalation prediction is created.
     *
     * @param Server $server The server to check
     * @return array<string, mixed>|null Prediction data or null if no escalation detected
     */
    public function predictBruteForceEscalation(Server $server): ?array
    {
        if (! $server->fail2ban_installed || ! $server->fail2ban_enabled) {
            return null;
        }

        $result = $this->executeCommand($server, 'fail2ban-client status sshd', 15);

        if (! $result['success'] || empty($result['output'])) {
            return null;
        }

        $output = $result['output'];

        // Parse currently banned count
        $currentBanned = 0;
        if (preg_match('/Currently banned:\s*(\d+)/', $output, $matches)) {
            $currentBanned = (int) $matches[1];
        }

        // Parse total banned count
        $totalBanned = 0;
        if (preg_match('/Total banned:\s*(\d+)/', $output, $matches)) {
            $totalBanned = (int) $matches[1];
        }

        // Parse currently failed count
        $currentFailed = 0;
        if (preg_match('/Currently failed:\s*(\d+)/', $output, $matches)) {
            $currentFailed = (int) $matches[1];
        }

        // Parse banned IP list
        $bannedIps = '';
        if (preg_match('/Banned IP list:\s*(.+)/s', $output, $matches)) {
            $bannedIps = trim($matches[1]);
        }

        // Check if ban rate is increasing by comparing with last prediction
        $lastPrediction = SecurityPrediction::query()
            ->where('server_id', $server->id)
            ->where('prediction_type', SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->orderBy('created_at', 'desc')
            ->first();

        $previousBanned = 0;
        if ($lastPrediction !== null && is_array($lastPrediction->evidence)) {
            $previousBanned = (int) ($lastPrediction->evidence['total_banned'] ?? 0);
        }

        $banRateIncreasing = $totalBanned > $previousBanned;

        // Generate prediction if there are active bans and rate is increasing,
        // or if current banned count is high
        if ($currentBanned < 3 && ! $banRateIncreasing) {
            return null;
        }

        // Only predict escalation if there's meaningful activity
        if ($currentBanned < 1 && $currentFailed < 5) {
            return null;
        }

        $severity = SecurityIncident::SEVERITY_MEDIUM;
        $confidence = 0.5;

        if ($currentBanned >= 10 && $banRateIncreasing) {
            $severity = SecurityIncident::SEVERITY_CRITICAL;
            $confidence = 0.9;
        } elseif ($currentBanned >= 5 || ($banRateIncreasing && $totalBanned > $previousBanned + 3)) {
            $severity = SecurityIncident::SEVERITY_HIGH;
            $confidence = 0.75;
        }

        $prediction = $this->createPrediction(
            server: $server,
            type: SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION,
            severity: $severity,
            title: sprintf('Brute force escalation - %d IPs currently banned', $currentBanned),
            description: sprintf(
                'Fail2ban sshd jail shows %d currently banned IPs (total: %d) with %d current failures. '
                .'%sBan rate is %s over the last hour. This suggests an active and potentially coordinated brute force campaign.',
                $currentBanned,
                $totalBanned,
                $currentFailed,
                $banRateIncreasing ? sprintf('Ban count increased from %d to %d. ', $previousBanned, $totalBanned) : '',
                $banRateIncreasing ? 'increasing' : 'stable'
            ),
            evidence: [
                'currently_banned' => $currentBanned,
                'total_banned' => $totalBanned,
                'currently_failed' => $currentFailed,
                'banned_ips' => $bannedIps,
                'previous_total_banned' => $previousBanned,
                'ban_rate_increasing' => $banRateIncreasing,
            ],
            confidence: $confidence,
        );

        return $prediction->toArray();
    }

    /**
     * Detect drift from the established security baseline.
     *
     * Captures the current server state and compares it against the latest
     * SecurityBaseline. Returns predictions for any new services, users,
     * crontab entries, or listening ports that were not present at baseline time.
     *
     * @param Server $server The server to check
     * @return array<int, array<string, mixed>> Array of prediction data for each drift detected
     */
    public function detectBaselineDrift(Server $server): array
    {
        $predictions = [];
        $baseline = $server->latestBaseline;

        if ($baseline === null) {
            return $predictions;
        }

        // Get current state
        $currentServices = $this->getCurrentRunningServices($server);
        $currentUsers = $this->getCurrentSystemUsers($server);
        $currentCrontabs = $this->getCurrentCrontabEntries($server);
        $currentPorts = $this->getCurrentListeningPorts($server);

        // Detect new services
        $newServices = array_diff($currentServices, $baseline->running_services);
        if (! empty($newServices)) {
            $prediction = $this->createPrediction(
                server: $server,
                type: SecurityPrediction::TYPE_NEW_SERVICE_DETECTED,
                severity: SecurityIncident::SEVERITY_MEDIUM,
                title: sprintf('%d new service(s) detected since baseline', count($newServices)),
                description: sprintf(
                    'The following services were not present in the baseline captured on %s: %s. '
                    .'New services should be reviewed to ensure they are authorized.',
                    $baseline->created_at?->format('Y-m-d H:i:s') ?? 'unknown',
                    implode(', ', array_values($newServices))
                ),
                evidence: [
                    'new_services' => array_values($newServices),
                    'baseline_services_count' => count($baseline->running_services),
                    'current_services_count' => count($currentServices),
                    'baseline_captured_at' => $baseline->created_at?->toIso8601String(),
                ],
                confidence: 0.85,
            );
            $predictions[] = $prediction->toArray();
        }

        // Detect new users
        $newUsers = array_diff($currentUsers, $baseline->system_users);
        if (! empty($newUsers)) {
            $prediction = $this->createPrediction(
                server: $server,
                type: SecurityPrediction::TYPE_NEW_USER_DETECTED,
                severity: SecurityIncident::SEVERITY_HIGH,
                title: sprintf('%d new system user(s) detected since baseline', count($newUsers)),
                description: sprintf(
                    'The following user accounts were not present in the baseline: %s. '
                    .'Unauthorized user accounts may indicate a security breach.',
                    implode(', ', array_values($newUsers))
                ),
                evidence: [
                    'new_users' => array_values($newUsers),
                    'baseline_users_count' => count($baseline->system_users),
                    'current_users_count' => count($currentUsers),
                    'baseline_captured_at' => $baseline->created_at?->toIso8601String(),
                ],
                confidence: 0.9,
            );
            $predictions[] = $prediction->toArray();
        }

        // Detect new crontab entries
        $newCrontabs = array_diff($currentCrontabs, $baseline->crontab_entries);
        if (! empty($newCrontabs)) {
            $prediction = $this->createPrediction(
                server: $server,
                type: SecurityPrediction::TYPE_NEW_CRONTAB_DETECTED,
                severity: SecurityIncident::SEVERITY_MEDIUM,
                title: sprintf('%d new crontab entry(ies) detected since baseline', count($newCrontabs)),
                description: sprintf(
                    'New crontab entries detected that were not in the baseline. '
                    .'Malicious crontab entries are a common persistence mechanism. Entries: %s',
                    implode(' | ', array_values($newCrontabs))
                ),
                evidence: [
                    'new_crontabs' => array_values($newCrontabs),
                    'baseline_crontabs_count' => count($baseline->crontab_entries),
                    'current_crontabs_count' => count($currentCrontabs),
                    'baseline_captured_at' => $baseline->created_at?->toIso8601String(),
                ],
                confidence: 0.8,
            );
            $predictions[] = $prediction->toArray();
        }

        // Detect new listening ports
        $newPorts = array_diff($currentPorts, $baseline->listening_ports);
        if (! empty($newPorts)) {
            $prediction = $this->createPrediction(
                server: $server,
                type: SecurityPrediction::TYPE_PORT_ANOMALY,
                severity: SecurityIncident::SEVERITY_HIGH,
                title: sprintf('%d new listening port(s) detected since baseline', count($newPorts)),
                description: sprintf(
                    'New listening ports detected: %s. '
                    .'Unexpected open ports may indicate backdoors, reverse shells, or unauthorized services.',
                    implode(', ', array_values($newPorts))
                ),
                evidence: [
                    'new_ports' => array_values($newPorts),
                    'baseline_ports_count' => count($baseline->listening_ports),
                    'current_ports_count' => count($currentPorts),
                    'baseline_captured_at' => $baseline->created_at?->toIso8601String(),
                ],
                confidence: 0.85,
            );
            $predictions[] = $prediction->toArray();
        }

        return $predictions;
    }

    /**
     * Capture the current server state as a security baseline snapshot.
     *
     * Runs commands on the server to collect running services, listening ports,
     * system users, crontab entries, systemd services, and average CPU/memory
     * usage from recent metrics.
     *
     * @param Server $server The server to capture a baseline for
     * @return SecurityBaseline The newly created baseline record
     */
    public function captureBaseline(Server $server): SecurityBaseline
    {
        Log::info('Capturing security baseline', ['server_id' => $server->id]);

        $runningServices = $this->getCurrentRunningServices($server);
        $listeningPorts = $this->getCurrentListeningPorts($server);
        $systemUsers = $this->getCurrentSystemUsers($server);
        $crontabEntries = $this->getCurrentCrontabEntries($server);
        $systemdServices = $this->getCurrentSystemdServices($server);

        // Get average CPU and memory from last hour of metrics
        $oneHourAgo = Carbon::now()->subHour();

        /** @var Collection<int, \App\Models\ServerMetric> $recentMetrics */
        $recentMetrics = $server->metrics()
            ->where('recorded_at', '>=', $oneHourAgo)
            ->get();

        $avgCpu = $recentMetrics->isNotEmpty() ? round((float) $recentMetrics->avg('cpu_usage'), 2) : 0.0;
        $avgMemory = $recentMetrics->isNotEmpty() ? round((float) $recentMetrics->avg('memory_usage'), 2) : 0.0;

        // Get total process count
        $processResult = $this->executeCommand($server, 'ps aux --no-headers | wc -l');
        $totalProcesses = $processResult['success'] ? (int) trim($processResult['output']) : 0;

        // Get network connections summary
        $netResult = $this->executeCommand($server, "ss -tunp 2>/dev/null | awk 'NR>1{print \$1}' | sort | uniq -c | sort -rn");
        $networkSummary = [];
        if ($netResult['success'] && ! empty($netResult['output'])) {
            $lines = array_filter(explode("\n", $netResult['output']));
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line), 2);
                if (count($parts) === 2) {
                    $networkSummary[$parts[1]] = (int) $parts[0];
                }
            }
        }

        $baseline = SecurityBaseline::create([
            'server_id' => $server->id,
            'running_services' => $runningServices,
            'listening_ports' => $listeningPorts,
            'system_users' => $systemUsers,
            'crontab_entries' => $crontabEntries,
            'systemd_services' => $systemdServices,
            'avg_cpu_usage' => $avgCpu,
            'avg_memory_usage' => $avgMemory,
            'total_processes' => $totalProcesses,
            'network_connections_summary' => $networkSummary,
        ]);

        $server->update(['last_baseline_at' => Carbon::now()]);

        Log::info('Security baseline captured', [
            'server_id' => $server->id,
            'baseline_id' => $baseline->id,
            'services_count' => count($runningServices),
            'ports_count' => count($listeningPorts),
            'users_count' => count($systemUsers),
        ]);

        return $baseline;
    }

    /**
     * Create a security prediction record and dispatch the associated event.
     *
     * Checks for a duplicate active prediction of the same type before creating.
     * If one already exists within the last hour, no new prediction is created and
     * the existing one is returned instead.
     *
     * @param Server $server The server the prediction is for
     * @param string $type The prediction type constant from SecurityPrediction
     * @param string $severity The severity level constant from SecurityIncident
     * @param string $title Short descriptive title for the prediction
     * @param string $description Detailed description of the predicted issue
     * @param array<string, mixed> $evidence Supporting data for the prediction
     * @param float $confidence Confidence score between 0.0 and 1.0
     * @param Carbon|null $predictedImpactAt Optional estimated time of impact
     * @return SecurityPrediction The created or existing prediction record
     */
    public function createPrediction(
        Server $server,
        string $type,
        string $severity,
        string $title,
        string $description,
        array $evidence,
        float $confidence,
        ?Carbon $predictedImpactAt = null,
    ): SecurityPrediction {
        // Check for existing active prediction of same type within last hour
        $existing = SecurityPrediction::query()
            ->where('server_id', $server->id)
            ->where('prediction_type', $type)
            ->where('status', SecurityPrediction::STATUS_ACTIVE)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->first();

        if ($existing instanceof SecurityPrediction) {
            // Update evidence on existing prediction instead of creating duplicate
            $existing->update([
                'evidence' => $evidence,
                'confidence_score' => $confidence,
            ]);

            return $existing;
        }

        $prediction = SecurityPrediction::create([
            'server_id' => $server->id,
            'prediction_type' => $type,
            'severity' => $severity,
            'status' => SecurityPrediction::STATUS_ACTIVE,
            'title' => $title,
            'description' => $description,
            'evidence' => $evidence,
            'confidence_score' => $confidence,
            'predicted_impact_at' => $predictedImpactAt,
            'recommended_actions' => $this->getRecommendedActions($type),
        ]);

        event(new SecurityPredictionCreated($prediction));

        Log::info('Security prediction created', [
            'prediction_id' => $prediction->id,
            'server_id' => $server->id,
            'type' => $type,
            'severity' => $severity,
            'confidence' => $confidence,
        ]);

        return $prediction;
    }

    /**
     * Get the list of currently running services on the server.
     *
     * @param Server $server The server to query
     * @return array<int, string> List of running service names
     */
    private function getCurrentRunningServices(Server $server): array
    {
        $result = $this->executeCommand(
            $server,
            "systemctl list-units --type=service --state=running --no-pager --plain 2>/dev/null | awk '{print \$1}' | grep '.service' | sed 's/.service$//'",
        );

        if (! $result['success'] || empty($result['output'])) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $result['output']))
        ));
    }

    /**
     * Get the list of currently listening ports on the server.
     *
     * @param Server $server The server to query
     * @return array<int, string> List of listening port entries (format: "proto:port")
     */
    private function getCurrentListeningPorts(Server $server): array
    {
        $result = $this->executeCommand(
            $server,
            "ss -tlnp 2>/dev/null | awk 'NR>1{print \$1\":\"\$4}' | sed 's/.*://' | sort -un",
        );

        if (! $result['success'] || empty($result['output'])) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $result['output']))
        ));
    }

    /**
     * Get the list of current system users on the server.
     *
     * Filters to users with login shells (not nologin or false).
     *
     * @param Server $server The server to query
     * @return array<int, string> List of system usernames
     */
    private function getCurrentSystemUsers(Server $server): array
    {
        $result = $this->executeCommand(
            $server,
            "cat /etc/passwd | grep -v 'nologin$' | grep -v 'false$' | cut -d: -f1 | sort",
        );

        if (! $result['success'] || empty($result['output'])) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $result['output']))
        ));
    }

    /**
     * Get the current crontab entries from the server.
     *
     * Collects entries from all user crontabs and system cron directories.
     *
     * @param Server $server The server to query
     * @return array<int, string> List of crontab entry lines
     */
    private function getCurrentCrontabEntries(Server $server): array
    {
        $entries = [];

        // Root crontab
        $rootResult = $this->executeCommand($server, "crontab -l 2>/dev/null | grep -v '^#' | grep -v '^$'");
        if ($rootResult['success'] && ! empty($rootResult['output'])) {
            $entries = array_merge($entries, array_filter(explode("\n", $rootResult['output'])));
        }

        // System crontabs
        $sysResult = $this->executeCommand($server, "cat /etc/crontab /etc/cron.d/* 2>/dev/null | grep -v '^#' | grep -v '^$'");
        if ($sysResult['success'] && ! empty($sysResult['output'])) {
            $entries = array_merge($entries, array_filter(explode("\n", $sysResult['output'])));
        }

        return array_values(array_unique(array_map('trim', $entries)));
    }

    /**
     * Get the list of systemd services and their states on the server.
     *
     * @param Server $server The server to query
     * @return array<int, string> List of systemd service unit names
     */
    private function getCurrentSystemdServices(Server $server): array
    {
        $result = $this->executeCommand(
            $server,
            "systemctl list-unit-files --type=service --no-pager --plain 2>/dev/null | awk '{print \$1}' | grep '.service'",
        );

        if (! $result['success'] || empty($result['output'])) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $result['output']))
        ));
    }

    /**
     * Get recommended actions for a given prediction type.
     *
     * @param string $type The prediction type constant
     * @return array<int, string> List of recommended action descriptions
     */
    private function getRecommendedActions(string $type): array
    {
        return match ($type) {
            SecurityPrediction::TYPE_CPU_ANOMALY => [
                'Run "top" or "htop" to identify the high-CPU process',
                'Check for crypto miner processes (xmrig, minerd, kdevtmpfsi)',
                'Review recently created files in /tmp and /var/tmp',
                'Check for unauthorized crontab entries',
                'Consider killing suspicious processes and investigating entry point',
            ],
            SecurityPrediction::TYPE_MEMORY_EXHAUSTION => [
                'Identify the memory-consuming processes with "ps aux --sort=-rss | head"',
                'Check for memory leaks in application services',
                'Review OOM killer logs: "dmesg | grep -i oom"',
                'Consider restarting heavy services to reclaim memory',
                'Evaluate if server needs a memory upgrade',
            ],
            SecurityPrediction::TYPE_BRUTE_FORCE_ESCALATION => [
                'Review fail2ban banned IPs: "fail2ban-client status sshd"',
                'Check authentication logs: "tail -100 /var/log/auth.log"',
                'Consider tightening fail2ban rules (lower maxretry, longer bantime)',
                'Verify SSH is configured with key-only authentication',
                'Consider geographic IP blocking if attacks originate from specific regions',
            ],
            SecurityPrediction::TYPE_NEW_SERVICE_DETECTED => [
                'Verify the new service is authorized and expected',
                'Check service configuration files for suspicious content',
                'Review the binary path with "systemctl show <service> --property=ExecStart"',
                'Disable unauthorized services: "systemctl disable --now <service>"',
            ],
            SecurityPrediction::TYPE_NEW_USER_DETECTED => [
                'Verify the new user account is authorized',
                'Check user UID/GID: "id <username>"',
                'Review user home directory for suspicious files',
                'Check if user has SSH keys: "cat /home/<user>/.ssh/authorized_keys"',
                'Remove unauthorized users: "userdel -r <username>"',
            ],
            SecurityPrediction::TYPE_NEW_CRONTAB_DETECTED => [
                'Review the new crontab entries for suspicious commands',
                'Check for download-and-execute patterns (wget|curl piped to sh/bash)',
                'Verify the scheduled task serves a legitimate purpose',
                'Remove unauthorized entries: "crontab -e"',
            ],
            SecurityPrediction::TYPE_PORT_ANOMALY => [
                'Identify the process listening on the new port: "ss -tlnp | grep <port>"',
                'Verify the service is authorized and expected',
                'Check if the port is exposed to the internet or only locally',
                'Consider closing the port with firewall rules if unauthorized',
                'Investigate the binary behind the listening socket',
            ],
            SecurityPrediction::TYPE_DISK_EXHAUSTION => [
                'Identify large files: "du -sh /* | sort -rh | head"',
                'Check log file sizes: "du -sh /var/log/*"',
                'Clean package cache: "apt-get clean" or "yum clean all"',
                'Review and rotate old log files',
                'Check for large temp files in /tmp and /var/tmp',
            ],
            default => [
                'Investigate the alert and verify the finding',
                'Review recent changes to the server',
                'Check system logs for related events',
            ],
        };
    }

    /**
     * Format a duration in seconds to a human-readable string.
     *
     * @param float $seconds Duration in seconds
     * @return string Formatted time string (e.g. "2h 15m", "45m", "30s")
     */
    private function formatTimeUntil(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%ds', (int) $seconds);
        }

        if ($seconds < 3600) {
            return sprintf('%dm', (int) ($seconds / 60));
        }

        $hours = (int) ($seconds / 3600);
        $minutes = (int) (($seconds % 3600) / 60);

        if ($minutes === 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%dh %dm', $hours, $minutes);
    }
}
