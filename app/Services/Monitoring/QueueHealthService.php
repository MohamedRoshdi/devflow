<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Checks queue worker health on remote (or local) servers.
 *
 * Uses supervisor to list worker processes and redis-cli / artisan to
 * gather queue depth metrics. All commands run over the same SSH path
 * as every other service that uses ExecutesRemoteCommands.
 */
class QueueHealthService
{
    use ExecutesRemoteCommands;

    /** @var array<string> Supervisor statuses that mean a worker is not processing jobs */
    public const UNHEALTHY_STATUSES = ['FATAL', 'STOPPED', 'EXITED', 'BACKOFF', 'UNKNOWN'];

    /**
     * Check queue worker health via supervisorctl on a remote server.
     *
     * Runs `supervisorctl status | grep -i queue` and parses the output
     * into a structured array. Each entry describes one worker process.
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null, healthy: bool}>
     */
    public function checkWorkers(Server $server): array
    {
        try {
            $output = $this->getRemoteOutput(
                $server,
                'sudo supervisorctl status 2>/dev/null | grep -i queue || true',
                false
            );

            return $this->parseWorkerOutput($output);
        } catch (\Exception $e) {
            Log::warning('QueueHealthService: failed to check workers', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Return true if every detected queue worker is in a healthy (RUNNING) state.
     *
     * Returns false when no workers are found — an empty supervisor config is
     * itself an unhealthy signal.
     */
    public function allWorkersHealthy(Server $server): bool
    {
        $workers = $this->checkWorkers($server);

        if ($workers === []) {
            return false;
        }

        return collect($workers)->every(fn (array $w): bool => $w['healthy']);
    }

    /**
     * Return only the workers that are in an unhealthy state.
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null, healthy: bool}>
     */
    public function getUnhealthyWorkers(Server $server): array
    {
        return array_values(array_filter(
            $this->checkWorkers($server),
            fn (array $w): bool => ! $w['healthy']
        ));
    }

    /**
     * Get queue depth statistics from the remote server.
     *
     * Checks the Redis list length for the given queue names directly via
     * redis-cli, avoiding the need to run `php artisan queue:monitor` (which
     * requires a full Laravel bootstrap on the remote host).
     *
     * @param  array<int, string>  $queues  Queue names to check (defaults to ['default'])
     * @return array<string, int> Map of queue name => pending job count
     */
    public function getQueueDepths(Server $server, array $queues = ['default']): array
    {
        $depths = [];

        foreach ($queues as $queue) {
            $safeQueue = escapeshellarg("queues:{$queue}");

            try {
                $output = $this->getRemoteOutput(
                    $server,
                    "redis-cli LLEN {$safeQueue} 2>/dev/null || echo 0",
                    false
                );

                $depths[$queue] = max(0, (int) trim($output));
            } catch (\Exception $e) {
                Log::warning('QueueHealthService: failed to get queue depth', [
                    'server_id' => $server->id,
                    'queue' => $queue,
                    'error' => $e->getMessage(),
                ]);

                $depths[$queue] = -1; // -1 signals "unable to retrieve"
            }
        }

        return $depths;
    }

    /**
     * Count failed jobs in the remote application's failed_jobs table.
     *
     * Runs a one-liner artisan command inside the project path so no
     * assumptions about the DB schema need to be made locally.
     *
     * @param  string  $projectPath  Absolute path to the Laravel project on the remote server
     */
    public function getFailedJobCount(Server $server, string $projectPath): int
    {
        try {
            $safePath = escapeshellarg($projectPath);

            $output = $this->getRemoteOutput(
                $server,
                "cd {$safePath} && php artisan tinker --execute=\"echo \\DB::table('failed_jobs')->count();\" 2>/dev/null || echo 0",
                false
            );

            // Tinker may prefix the number with other output; grab the last integer on the last line.
            $lines = array_filter(array_map('trim', explode("\n", $output)));
            $last = end($lines);

            return max(0, (int) preg_replace('/\D/', '', (string) $last));
        } catch (\Exception $e) {
            Log::warning('QueueHealthService: failed to count failed jobs', [
                'server_id' => $server->id,
                'project_path' => $projectPath,
                'error' => $e->getMessage(),
            ]);

            return -1;
        }
    }

    /**
     * Detect jobs that have been reserved but not completed within the threshold.
     *
     * A job is considered stuck when its `reserved_at` timestamp is older than
     * $thresholdMinutes ago. This indicates a crashed worker or infinite loop.
     *
     * @param  string  $projectPath  Absolute path to the Laravel project on the remote server
     * @return int Number of stuck jobs (-1 when the query could not be executed)
     */
    public function getStuckJobCount(Server $server, string $projectPath, int $thresholdMinutes = 30): int
    {
        try {
            $safePath = escapeshellarg($projectPath);
            $safeThreshold = (int) $thresholdMinutes;

            $output = $this->getRemoteOutput(
                $server,
                "cd {$safePath} && php artisan tinker --execute=\"echo \\DB::table('jobs')->whereNotNull('reserved_at')->where('reserved_at', '<', now()->subMinutes({$safeThreshold})->timestamp)->count();\" 2>/dev/null || echo 0",
                false
            );

            $lines = array_filter(array_map('trim', explode("\n", $output)));
            $last = end($lines);

            return max(0, (int) preg_replace('/\D/', '', (string) $last));
        } catch (\Exception $e) {
            Log::warning('QueueHealthService: failed to count stuck jobs', [
                'server_id' => $server->id,
                'project_path' => $projectPath,
                'threshold_minutes' => $thresholdMinutes,
                'error' => $e->getMessage(),
            ]);

            return -1;
        }
    }

    /**
     * Aggregate all queue health signals into a single status summary.
     *
     * @param  array<int, string>  $queues
     * @return array{status: string, issues: array<int, string>, workers: array<int, array<string, mixed>>, depths: array<string, int>, failed_jobs: int, stuck_jobs: int}
     */
    public function getHealthSummary(Server $server, string $projectPath, array $queues = ['default']): array
    {
        $workers = $this->checkWorkers($server);
        $depths = $this->getQueueDepths($server, $queues);
        $failedJobs = $this->getFailedJobCount($server, $projectPath);
        $stuckJobs = $this->getStuckJobCount($server, $projectPath);

        $issues = [];
        $status = 'healthy';

        // Worker health
        $unhealthyWorkers = array_filter($workers, fn (array $w): bool => ! $w['healthy']);
        if ($workers === []) {
            $status = 'critical';
            $issues[] = 'No queue workers found in supervisorctl — workers may not be configured';
        } elseif ($unhealthyWorkers !== []) {
            $status = 'critical';
            foreach ($unhealthyWorkers as $w) {
                $issues[] = "Worker {$w['name']} is {$w['status']}";
            }
        }

        // Queue backlog
        foreach ($depths as $queue => $depth) {
            if ($depth < 0) {
                if ($status !== 'critical') {
                    $status = 'warning';
                }
                $issues[] = "Could not check depth of queue '{$queue}'";
            } elseif ($depth > 500) {
                if ($status !== 'critical') {
                    $status = 'warning';
                }
                $issues[] = "Queue '{$queue}' has {$depth} pending jobs (high backlog)";
            }
        }

        // Failed jobs
        if ($failedJobs > 50) {
            if ($status !== 'critical') {
                $status = 'warning';
            }
            $issues[] = "{$failedJobs} failed jobs in failed_jobs table";
        }

        // Stuck jobs
        if ($stuckJobs > 0) {
            if ($status !== 'critical') {
                $status = 'warning';
            }
            $issues[] = "{$stuckJobs} job(s) appear stuck (reserved >30 min without completion)";
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'workers' => $workers,
            'depths' => $depths,
            'failed_jobs' => $failedJobs,
            'stuck_jobs' => $stuckJobs,
        ];
    }

    /**
     * Parse `supervisorctl status | grep queue` output.
     *
     * Expected line format (same as SupervisorHealthService):
     *   e-store-queue:00   RUNNING   pid 1234, uptime 0:12:34
     *   e-store-queue:01   FATAL     Exited too quickly
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null, healthy: bool}>
     */
    private function parseWorkerOutput(string $output): array
    {
        $workers = [];

        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);

            if ($line === '' || str_contains($line, 'no such process') || str_starts_with($line, 'error')) {
                continue;
            }

            if (! preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF|UNKNOWN)\s*(.*)$/i', $line, $m)) {
                continue;
            }

            $name = $m[1];
            $status = strtoupper($m[2]);
            $rest = trim($m[3]);

            $pid = null;
            $uptime = null;
            if (preg_match('/pid\s+(\d+),\s+uptime\s+(\S+)/i', $rest, $pm)) {
                $pid = $pm[1];
                $uptime = $pm[2];
            }

            $workers[] = [
                'name' => $name,
                'status' => $status,
                'pid' => $pid,
                'uptime' => $uptime,
                'healthy' => ! in_array($status, self::UNHEALTHY_STATUSES, true),
            ];
        }

        return $workers;
    }
}
