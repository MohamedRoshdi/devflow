<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogEntry;
use App\Models\LogSource;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LogAggregationService
{
    public function __construct(
        private readonly ServerConnectivityService $connectivityService
    ) {}

    public function syncLogs(Server $server): array
    {
        $sources = LogSource::active()
            ->forServer($server->id)
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'total_entries' => 0,
            'errors' => [],
        ];

        foreach ($sources as $source) {
            try {
                $entries = $this->syncLogSource($source);
                $results['success']++;
                $results['total_entries'] += $entries;

                $source->update(['last_synced_at' => now()]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'source' => $source->name,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to sync log source: {$source->name}", [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    private function syncLogSource(LogSource $source): int
    {
        $content = match ($source->type) {
            'file' => $this->fetchLogFile($source->server, $source->path, 1000),
            'docker' => $this->fetchDockerLogs($source->server, $source->path, 1000),
            'journald' => $this->fetchJournaldLogs($source->server, $source->path, 1000),
            default => throw new \InvalidArgumentException("Unsupported log type: {$source->type}"),
        };

        if (empty($content)) {
            return 0;
        }

        $parsedLogs = $this->parseLogContent($content, $source);

        $count = 0;
        foreach ($parsedLogs as $logData) {
            LogEntry::create(array_merge($logData, [
                'server_id' => $source->server_id,
                'project_id' => $source->project_id,
            ]));
            $count++;
        }

        return $count;
    }

    public function fetchLogFile(Server $server, string $path, int $lines = 100): string
    {
        $command = "tail -n {$lines} {$path} 2>/dev/null || echo ''";

        try {
            $result = $this->connectivityService->executeCommand($server, $command);
            return $result['output'] ?? '';
        } catch (\Exception $e) {
            Log::warning("Failed to fetch log file from {$server->name}: {$path}", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function fetchDockerLogs(Server $server, string $containerName, int $lines = 100): string
    {
        $command = "docker logs --tail {$lines} {$containerName} 2>&1 || echo ''";

        try {
            $result = $this->connectivityService->executeCommand($server, $command);
            return $result['output'] ?? '';
        } catch (\Exception $e) {
            Log::warning("Failed to fetch Docker logs from {$server->name}: {$containerName}", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function fetchJournaldLogs(Server $server, string $unit, int $lines = 100): string
    {
        $command = "journalctl -u {$unit} -n {$lines} --no-pager 2>/dev/null || echo ''";

        try {
            $result = $this->connectivityService->executeCommand($server, $command);
            return $result['output'] ?? '';
        } catch (\Exception $e) {
            Log::warning("Failed to fetch journald logs from {$server->name}: {$unit}", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function parseLogContent(string $content, LogSource $source): array
    {
        $lines = array_filter(explode("\n", $content));

        return match ($source->source ?? 'other') {
            'nginx' => $this->parseNginxLogs($lines),
            'laravel' => $this->parseLaravelLogs($lines),
            'php' => $this->parsePhpLogs($lines),
            'mysql' => $this->parseMysqlLogs($lines),
            'system' => $this->parseSystemLogs($lines),
            'docker' => $this->parseDockerLogs($lines),
            default => $this->parseGenericLogs($lines, $source->source ?? 'other'),
        };
    }

    public function parseNginxLog(string $content): array
    {
        return $this->parseNginxLogs(array_filter(explode("\n", $content)));
    }

    private function parseNginxLogs(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            // Nginx error log format: 2025/11/28 10:30:45 [error] 123#123: *456 message
            if (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[(.*?)\].*?: (.*)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'nginx',
                    'level' => $this->normalizeLevel($matches[2]),
                    'message' => trim($matches[3]),
                    'logged_at' => Carbon::createFromFormat('Y/m/d H:i:s', $matches[1]),
                ];
            }
            // Nginx access log - treat as info
            elseif (preg_match('/^\S+ - - \[(.*?)\]/', $line, $matches)) {
                $logs[] = [
                    'source' => 'nginx',
                    'level' => 'info',
                    'message' => trim($line),
                    'logged_at' => Carbon::createFromFormat('d/M/Y:H:i:s O', $matches[1]),
                ];
            }
        }

        return $logs;
    }

    public function parseLaravelLog(string $content): array
    {
        return $this->parseLaravelLogs(array_filter(explode("\n", $content)));
    }

    private function parseLaravelLogs(array $lines): array
    {
        $logs = [];
        $currentLog = null;

        foreach ($lines as $line) {
            // Laravel log format: [2025-11-28 10:30:45] local.ERROR: Message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)$/', $line, $matches)) {
                if ($currentLog) {
                    $logs[] = $currentLog;
                }

                $currentLog = [
                    'source' => 'laravel',
                    'level' => strtolower($matches[2]),
                    'message' => trim($matches[3]),
                    'logged_at' => Carbon::parse($matches[1]),
                ];
            }
            // Stack trace or continuation
            elseif ($currentLog && (str_starts_with($line, '#') || str_starts_with($line, ' '))) {
                $currentLog['message'] .= "\n" . trim($line);

                // Extract file path and line number from stack trace
                if (preg_match('/^#\d+ (.+?)\((\d+)\)/', $line, $matches)) {
                    $currentLog['file_path'] = $matches[1];
                    $currentLog['line_number'] = (int)$matches[2];
                }
            }
        }

        if ($currentLog) {
            $logs[] = $currentLog;
        }

        return $logs;
    }

    private function parsePhpLogs(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            // PHP error log format: [28-Nov-2025 10:30:45 UTC] PHP Warning: message in /path/file.php on line 123
            if (preg_match('/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}.*?)\] PHP (.*?): (.*?) in (.*?) on line (\d+)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'php',
                    'level' => $this->normalizeLevel($matches[2]),
                    'message' => trim($matches[3]),
                    'file_path' => $matches[4],
                    'line_number' => (int)$matches[5],
                    'logged_at' => Carbon::parse($matches[1]),
                ];
            }
            elseif (preg_match('/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}.*?)\] (.*)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'php',
                    'level' => 'error',
                    'message' => trim($matches[2]),
                    'logged_at' => Carbon::parse($matches[1]),
                ];
            }
        }

        return $logs;
    }

    private function parseMysqlLogs(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            // MySQL error log format: 2025-11-28T10:30:45.123456Z 123 [ERROR] message
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z) \d+ \[(.*?)\] (.*)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'mysql',
                    'level' => $this->normalizeLevel($matches[2]),
                    'message' => trim($matches[3]),
                    'logged_at' => Carbon::parse($matches[1]),
                ];
            }
        }

        return $logs;
    }

    public function parseSystemLog(string $content): array
    {
        return $this->parseSystemLogs(array_filter(explode("\n", $content)));
    }

    private function parseSystemLogs(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            // Syslog format: Nov 28 10:30:45 hostname service[pid]: message
            if (preg_match('/^(\w{3} \d{2} \d{2}:\d{2}:\d{2}) (\S+) (.*?): (.*)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'system',
                    'level' => 'info',
                    'message' => trim($matches[4]),
                    'logged_at' => Carbon::parse($matches[1] . ' ' . now()->year),
                    'context' => [
                        'hostname' => $matches[2],
                        'service' => $matches[3],
                    ],
                ];
            }
        }

        return $logs;
    }

    public function parseDockerLog(string $container, string $content): array
    {
        return $this->parseDockerLogs(array_filter(explode("\n", $content)));
    }

    private function parseDockerLogs(array $lines): array
    {
        $logs = [];

        foreach ($lines as $line) {
            // Docker logs can be in various formats, try to parse timestamp if present
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z) (.*)$/', $line, $matches)) {
                $logs[] = [
                    'source' => 'docker',
                    'level' => 'info',
                    'message' => trim($matches[2]),
                    'logged_at' => Carbon::parse($matches[1]),
                ];
            } else {
                $logs[] = [
                    'source' => 'docker',
                    'level' => 'info',
                    'message' => trim($line),
                    'logged_at' => now(),
                ];
            }
        }

        return $logs;
    }

    private function parseGenericLogs(array $lines, string $source): array
    {
        $logs = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $logs[] = [
                'source' => $source,
                'level' => 'info',
                'message' => trim($line),
                'logged_at' => now(),
            ];
        }

        return $logs;
    }

    public function searchLogs(array $filters): Collection
    {
        $query = LogEntry::query()->with(['server', 'project']);

        if (!empty($filters['server_id'])) {
            $query->byServer($filters['server_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->byProject($filters['project_id']);
        }

        if (!empty($filters['source'])) {
            $query->bySource($filters['source']);
        }

        if (!empty($filters['level'])) {
            $query->byLevel($filters['level']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }

        return $query->recent()->get();
    }

    public function cleanOldLogs(int $days = 30): int
    {
        return LogEntry::where('logged_at', '<', now()->subDays($days))->delete();
    }

    private function normalizeLevel(string $level): string
    {
        $level = strtolower($level);

        return match ($level) {
            'warn', 'warning' => 'warning',
            'err', 'error' => 'error',
            'crit', 'critical' => 'critical',
            'emerg', 'emergency' => 'emergency',
            'notice' => 'notice',
            'debug' => 'debug',
            'alert' => 'alert',
            default => 'info',
        };
    }
}
