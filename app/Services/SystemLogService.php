<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\SystemLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SystemLogService
{
    /**
     * Collect system logs from a server.
     */
    public function collectLogsFromServer(Server $server, int $lines = 100, ?string $logType = null): Collection
    {
        $logs = collect();

        if ($logType) {
            $logs = $this->collectSpecificLogType($server, $logType, $lines);
        } else {
            // Collect all log types
            $logs = $logs->merge($this->collectSyslog($server, $lines));
            $logs = $logs->merge($this->collectAuthLog($server, $lines));
            $logs = $logs->merge($this->collectDockerLogs($server, $lines));
        }

        return $logs;
    }

    /**
     * Collect specific log type from server.
     */
    public function collectSpecificLogType(Server $server, string $logType, int $lines = 100): Collection
    {
        return match ($logType) {
            SystemLog::TYPE_SYSTEM => $this->collectSyslog($server, $lines),
            SystemLog::TYPE_AUTH => $this->collectAuthLog($server, $lines),
            SystemLog::TYPE_DOCKER => $this->collectDockerLogs($server, $lines),
            SystemLog::TYPE_NGINX => $this->collectNginxLogs($server, $lines),
            SystemLog::TYPE_KERNEL => $this->collectKernelLog($server, $lines),
            default => collect(),
        };
    }

    /**
     * Collect syslog entries.
     */
    public function collectSyslog(Server $server, int $lines = 100): Collection
    {
        $command = "tail -n {$lines} /var/log/syslog 2>/dev/null || journalctl -n {$lines} --no-pager";
        $output = $this->executeRemoteCommand($server, $command);

        return $this->parseSyslog($output, $server);
    }

    /**
     * Collect auth log entries.
     */
    public function collectAuthLog(Server $server, int $lines = 100): Collection
    {
        $command = "tail -n {$lines} /var/log/auth.log 2>/dev/null || journalctl -u ssh -n {$lines} --no-pager";
        $output = $this->executeRemoteCommand($server, $command);

        return $this->parseAuthLog($output, $server);
    }

    /**
     * Collect Docker logs.
     */
    public function collectDockerLogs(Server $server, int $lines = 100): Collection
    {
        $command = "docker ps --format '{{.Names}}' 2>/dev/null || echo ''";
        $containers = $this->executeRemoteCommand($server, $command);

        $logs = collect();
        $containerNames = array_filter(explode("\n", $containers));

        foreach ($containerNames as $container) {
            $container = trim($container);
            if (empty($container)) {
                continue;
            }

            $logCommand = "docker logs --tail {$lines} {$container} 2>&1";
            $output = $this->executeRemoteCommand($server, $logCommand);

            $containerLogs = $this->parseDockerLogs($output, $server, $container);
            $logs = $logs->merge($containerLogs);
        }

        return $logs;
    }

    /**
     * Collect Nginx logs.
     */
    public function collectNginxLogs(Server $server, int $lines = 100): Collection
    {
        $command = "tail -n {$lines} /var/log/nginx/error.log 2>/dev/null || echo ''";
        $output = $this->executeRemoteCommand($server, $command);

        return $this->parseNginxLogs($output, $server);
    }

    /**
     * Collect kernel logs.
     */
    public function collectKernelLog(Server $server, int $lines = 100): Collection
    {
        $command = "dmesg --level=emerg,alert,crit,err,warn --time-format=iso | tail -n {$lines}";
        $output = $this->executeRemoteCommand($server, $command);

        return $this->parseKernelLog($output, $server);
    }

    /**
     * Parse syslog format.
     */
    protected function parseSyslog(string $output, Server $server): Collection
    {
        $logs = collect();
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse syslog format: Jan 18 13:07:18 hostname service[pid]: message
            if (preg_match('/^(\w{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})\s+(\S+)\s+(.+?)(?:\[(\d+)\])?\s*:\s*(.+)$/i', $line, $matches)) {
                $logs->push([
                    'server_id' => $server->id,
                    'log_type' => SystemLog::TYPE_SYSTEM,
                    'level' => $this->detectLogLevel($matches[5] ?? $line),
                    'source' => $matches[3] ?? 'syslog',
                    'message' => $matches[5] ?? $line,
                    'metadata' => [
                        'hostname' => $matches[2] ?? null,
                        'pid' => $matches[4] ?? null,
                        'raw_line' => $line,
                    ],
                    'logged_at' => $this->parseSyslogDate($matches[1] ?? null),
                ]);
            } else {
                // Fallback for unparseable lines
                $logs->push([
                    'server_id' => $server->id,
                    'log_type' => SystemLog::TYPE_SYSTEM,
                    'level' => $this->detectLogLevel($line),
                    'source' => 'syslog',
                    'message' => $line,
                    'metadata' => ['raw_line' => $line],
                    'logged_at' => now(),
                ]);
            }
        }

        return $logs;
    }

    /**
     * Parse auth log format.
     */
    protected function parseAuthLog(string $output, Server $server): Collection
    {
        $logs = collect();
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $logs->push([
                'server_id' => $server->id,
                'log_type' => SystemLog::TYPE_AUTH,
                'level' => $this->detectAuthLogLevel($line),
                'source' => 'auth',
                'message' => $line,
                'metadata' => [
                    'ip_address' => $this->extractIpAddress($line),
                    'raw_line' => $line,
                ],
                'ip_address' => $this->extractIpAddress($line),
                'logged_at' => $this->parseSyslogDate($this->extractDateFromLine($line)) ?? now(),
            ]);
        }

        return $logs;
    }

    /**
     * Parse Docker logs.
     */
    protected function parseDockerLogs(string $output, Server $server, string $containerName): Collection
    {
        $logs = collect();
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $logs->push([
                'server_id' => $server->id,
                'log_type' => SystemLog::TYPE_DOCKER,
                'level' => $this->detectLogLevel($line),
                'source' => "docker:{$containerName}",
                'message' => $line,
                'metadata' => [
                    'container' => $containerName,
                    'raw_line' => $line,
                ],
                'logged_at' => now(),
            ]);
        }

        return $logs;
    }

    /**
     * Parse Nginx logs.
     */
    protected function parseNginxLogs(string $output, Server $server): Collection
    {
        $logs = collect();
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $logs->push([
                'server_id' => $server->id,
                'log_type' => SystemLog::TYPE_NGINX,
                'level' => $this->detectNginxLogLevel($line),
                'source' => 'nginx',
                'message' => $line,
                'metadata' => ['raw_line' => $line],
                'logged_at' => now(),
            ]);
        }

        return $logs;
    }

    /**
     * Parse kernel logs.
     */
    protected function parseKernelLog(string $output, Server $server): Collection
    {
        $logs = collect();
        $lines = explode("\n", trim($output));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $logs->push([
                'server_id' => $server->id,
                'log_type' => SystemLog::TYPE_KERNEL,
                'level' => $this->detectKernelLogLevel($line),
                'source' => 'kernel',
                'message' => $line,
                'metadata' => ['raw_line' => $line],
                'logged_at' => now(),
            ]);
        }

        return $logs;
    }

    /**
     * Store collected logs in database.
     */
    public function storeLogs(Collection $logs): int
    {
        $stored = 0;

        foreach ($logs as $logData) {
            try {
                SystemLog::create($logData);
                $stored++;
            } catch (\Exception $e) {
                // Log the error but continue processing
                logger()->error('Failed to store system log', [
                    'error' => $e->getMessage(),
                    'log_data' => $logData,
                ]);
            }
        }

        return $stored;
    }

    /**
     * Detect log level from message content.
     */
    protected function detectLogLevel(string $message): string
    {
        $message = strtolower($message);

        if (Str::contains($message, ['emergency', 'panic'])) {
            return SystemLog::LEVEL_EMERGENCY;
        }

        if (Str::contains($message, ['alert'])) {
            return SystemLog::LEVEL_ALERT;
        }

        if (Str::contains($message, ['critical', 'crit', 'fatal'])) {
            return SystemLog::LEVEL_CRITICAL;
        }

        if (Str::contains($message, ['error', 'err', 'failed', 'failure'])) {
            return SystemLog::LEVEL_ERROR;
        }

        if (Str::contains($message, ['warning', 'warn'])) {
            return SystemLog::LEVEL_WARNING;
        }

        if (Str::contains($message, ['notice'])) {
            return SystemLog::LEVEL_NOTICE;
        }

        if (Str::contains($message, ['debug'])) {
            return SystemLog::LEVEL_DEBUG;
        }

        return SystemLog::LEVEL_INFO;
    }

    /**
     * Detect auth log level.
     */
    protected function detectAuthLogLevel(string $message): string
    {
        $message = strtolower($message);

        if (Str::contains($message, ['failed', 'failure', 'invalid', 'illegal', 'authentication failure'])) {
            return SystemLog::LEVEL_WARNING;
        }

        if (Str::contains($message, ['accepted', 'session opened', 'session closed'])) {
            return SystemLog::LEVEL_INFO;
        }

        return SystemLog::LEVEL_NOTICE;
    }

    /**
     * Detect Nginx log level.
     */
    protected function detectNginxLogLevel(string $message): string
    {
        if (preg_match('/\[emerg\]/i', $message)) {
            return SystemLog::LEVEL_EMERGENCY;
        }

        if (preg_match('/\[alert\]/i', $message)) {
            return SystemLog::LEVEL_ALERT;
        }

        if (preg_match('/\[crit\]/i', $message)) {
            return SystemLog::LEVEL_CRITICAL;
        }

        if (preg_match('/\[error\]/i', $message)) {
            return SystemLog::LEVEL_ERROR;
        }

        if (preg_match('/\[warn\]/i', $message)) {
            return SystemLog::LEVEL_WARNING;
        }

        return SystemLog::LEVEL_INFO;
    }

    /**
     * Detect kernel log level.
     */
    protected function detectKernelLogLevel(string $message): string
    {
        if (Str::contains($message, ['emerg'])) {
            return SystemLog::LEVEL_EMERGENCY;
        }

        if (Str::contains($message, ['alert'])) {
            return SystemLog::LEVEL_ALERT;
        }

        if (Str::contains($message, ['crit'])) {
            return SystemLog::LEVEL_CRITICAL;
        }

        if (Str::contains($message, ['err'])) {
            return SystemLog::LEVEL_ERROR;
        }

        if (Str::contains($message, ['warn'])) {
            return SystemLog::LEVEL_WARNING;
        }

        return SystemLog::LEVEL_INFO;
    }

    /**
     * Extract IP address from log line.
     */
    protected function extractIpAddress(string $line): ?string
    {
        // IPv4 pattern
        if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $line, $matches)) {
            return $matches[0];
        }

        // IPv6 pattern (simplified)
        if (preg_match('/\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/', $line, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract date from log line.
     */
    protected function extractDateFromLine(string $line): ?string
    {
        // Syslog format: Jan 18 13:07:18
        if (preg_match('/^(\w{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse syslog date format.
     */
    protected function parseSyslogDate(?string $dateString): ?\Carbon\Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            // Add current year since syslog doesn't include it
            $year = now()->year;
            return \Carbon\Carbon::parse("{$dateString} {$year}");
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Execute remote command on server.
     */
    protected function executeRemoteCommand(Server $server, string $command): string
    {
        $sshCommand = sprintf(
            'ssh -o StrictHostKeyChecking=no -p %d %s@%s "%s"',
            $server->ssh_port,
            $server->ssh_user,
            $server->ip_address,
            str_replace('"', '\\"', $command)
        );

        $result = Process::run($sshCommand);

        if (!$result->successful()) {
            logger()->error('Failed to execute remote command', [
                'server' => $server->name,
                'command' => $command,
                'error' => $result->errorOutput(),
            ]);

            return '';
        }

        return $result->output();
    }

    /**
     * Clean old logs.
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        return SystemLog::where('logged_at', '<', now()->subDays($daysToKeep))->delete();
    }

    /**
     * Get log statistics.
     */
    public function getLogStatistics(?Server $server = null): array
    {
        $query = SystemLog::query();

        if ($server) {
            $query->where('server_id', $server->id);
        }

        return [
            'total' => $query->count(),
            'by_level' => $query->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'by_type' => $query->selectRaw('log_type, COUNT(*) as count')
                ->groupBy('log_type')
                ->pluck('count', 'log_type')
                ->toArray(),
            'recent_critical' => $query->critical()->recent(24)->count(),
            'recent_errors' => $query->errors()->recent(24)->count(),
        ];
    }
}
