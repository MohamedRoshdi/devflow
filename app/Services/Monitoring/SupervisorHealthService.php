<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Checks supervisor process health on remote (or local) servers.
 *
 * Unhealthy statuses: FATAL, STOPPED, UNKNOWN, BACKOFF
 */
class SupervisorHealthService
{
    use ExecutesRemoteCommands;

    /** @var array<string> Statuses that are considered unhealthy */
    public const UNHEALTHY_STATUSES = ['FATAL', 'STOPPED', 'UNKNOWN', 'BACKOFF'];

    /**
     * Check supervisor process status on a server.
     * Returns all processes visible to `supervisorctl status`.
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null}>
     */
    public function checkProcesses(Server $server): array
    {
        try {
            $output = $this->getRemoteOutput(
                $server,
                'sudo supervisorctl status 2>/dev/null || true',
                false
            );

            return $this->parseStatusOutput($output);
        } catch (\Exception $e) {
            Log::warning('SupervisorHealthService: failed to check processes', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check whether any processes are in an unhealthy state.
     */
    public function hasUnhealthyProcesses(Server $server): bool
    {
        $processes = $this->checkProcesses($server);

        return collect($processes)->contains(
            fn (array $p): bool => in_array($p['status'], self::UNHEALTHY_STATUSES, true)
        );
    }

    /**
     * Return only the processes that are in an unhealthy state.
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null}>
     */
    public function getUnhealthyProcesses(Server $server): array
    {
        return array_values(array_filter(
            $this->checkProcesses($server),
            fn (array $p): bool => in_array($p['status'], self::UNHEALTHY_STATUSES, true)
        ));
    }

    /**
     * Restart a specific supervisor process or process group.
     *
     * Tries `{name}:*` first (group), then falls back to bare `{name}`.
     */
    public function restartProcess(Server $server, string $processName): bool
    {
        try {
            $safeProcess = escapeshellarg($processName);

            $this->executeRemoteCommand(
                $server,
                "sudo supervisorctl restart {$safeProcess}:* 2>/dev/null || sudo supervisorctl restart {$safeProcess} 2>/dev/null || true",
                false
            );

            Log::info('SupervisorHealthService: restarted process', [
                'server_id' => $server->id,
                'process' => $processName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SupervisorHealthService: failed to restart process', [
                'server_id' => $server->id,
                'process' => $processName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Restart all processes in a supervisor group (`{groupName}:*`).
     */
    public function restartGroup(Server $server, string $groupName): bool
    {
        try {
            $safeGroup = escapeshellarg($groupName);

            $this->executeRemoteCommand(
                $server,
                "sudo supervisorctl restart {$safeGroup}:*",
                false
            );

            Log::info('SupervisorHealthService: restarted group', [
                'server_id' => $server->id,
                'group' => $groupName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SupervisorHealthService: failed to restart group', [
                'server_id' => $server->id,
                'group' => $groupName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Parse `supervisorctl status` output into a structured array.
     *
     * Supervisor lines look like:
     *   e-store-octane     RUNNING   pid 1234, uptime 1:23:45
     *   e-store-queue:00   FATAL     Exited too quickly
     *   e-store-queue:01   STOPPED   Nov 12 10:15 AM
     *
     * @return array<int, array{name: string, status: string, pid: string|null, uptime: string|null}>
     */
    private function parseStatusOutput(string $output): array
    {
        $processes = [];

        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);

            if ($line === '' || str_contains($line, 'no such process') || str_starts_with($line, 'error')) {
                continue;
            }

            // Match: name  STATUS  rest-of-line
            if (! preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF|UNKNOWN)\s*(.*)$/i', $line, $m)) {
                continue;
            }

            $name = $m[1];
            $status = strtoupper($m[2]);
            $rest = trim($m[3]);

            // Extract PID from "pid 1234, uptime ..." format
            $pid = null;
            $uptime = null;
            if (preg_match('/pid\s+(\d+),\s+uptime\s+(\S+)/i', $rest, $pm)) {
                $pid = $pm[1];
                $uptime = $pm[2];
            }

            $processes[] = [
                'name' => $name,
                'status' => $status,
                'pid' => $pid,
                'uptime' => $uptime,
            ];
        }

        return $processes;
    }
}
