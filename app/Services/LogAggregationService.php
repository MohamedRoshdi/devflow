<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogEntry;
use App\Models\LogSource;
use App\Models\Server;
use App\Services\LogParsers\LogParserFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for aggregating and managing logs from multiple sources.
 *
 * This service fetches logs from various sources (files, Docker containers,
 * systemd journals) and parses them using specialized parsers.
 */
class LogAggregationService
{
    private readonly LogParserFactory $parserFactory;

    public function __construct(
        private readonly ServerConnectivityService $connectivityService
    ) {
        $this->parserFactory = new LogParserFactory();
    }

    /**
     * Sync logs from all active sources for a server.
     *
     * @return array{success: int, failed: int, total_entries: int, errors: array<int, array{source: string, error: string}>}
     */
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

    /**
     * Sync a single log source.
     */
    private function syncLogSource(LogSource $source): int
    {
        $content = $this->fetchLogs($source->server, $source->type, $source->path, 1000);

        if ($content === '') {
            return 0;
        }

        $parserSource = $source->source ?? 'other';
        $parsedLogs = $this->parserFactory->parse($parserSource, $content);

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

    /**
     * Fetch logs from a server based on type.
     */
    private function fetchLogs(Server $server, string $type, string $path, int $lines = 100): string
    {
        $command = $this->buildFetchCommand($type, $path, $lines);

        try {
            $result = $this->connectivityService->executeCommand($server, $command);

            return $result['output'] ?? '';
        } catch (\Exception $e) {
            Log::warning("Failed to fetch {$type} logs from {$server->name}: {$path}", [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Build the shell command for fetching logs.
     */
    private function buildFetchCommand(string $type, string $path, int $lines): string
    {
        return match ($type) {
            'file' => "tail -n {$lines} {$path} 2>/dev/null || echo ''",
            'docker' => "docker logs --tail {$lines} {$path} 2>&1 || echo ''",
            'journald' => "journalctl -u {$path} -n {$lines} --no-pager 2>/dev/null || echo ''",
            default => throw new \InvalidArgumentException("Unsupported log type: {$type}"),
        };
    }

    /**
     * Fetch log file content (public API).
     */
    public function fetchLogFile(Server $server, string $path, int $lines = 100): string
    {
        return $this->fetchLogs($server, 'file', $path, $lines);
    }

    /**
     * Parse log content using the specified parser.
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, file_path?: string, line_number?: int, context?: array<string, mixed>}>
     */
    public function parseLogContent(string $source, string $content): array
    {
        return $this->parserFactory->parse($source, $content);
    }

    /**
     * Parse Nginx log content (backwards compatibility).
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon}>
     */
    public function parseNginxLog(string $content): array
    {
        return $this->parserFactory->parse('nginx', $content);
    }

    /**
     * Parse Laravel log content (backwards compatibility).
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, file_path?: string, line_number?: int}>
     */
    public function parseLaravelLog(string $content): array
    {
        return $this->parserFactory->parse('laravel', $content);
    }

    /**
     * Parse system log content (backwards compatibility).
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon, context?: array<string, mixed>}>
     */
    public function parseSystemLog(string $content): array
    {
        return $this->parserFactory->parse('system', $content);
    }

    /**
     * Parse Docker log content (backwards compatibility).
     *
     * @return array<int, array{source: string, level: string, message: string, logged_at: \Carbon\Carbon}>
     */
    public function parseDockerLog(string $container, string $content): array
    {
        return $this->parserFactory->parse('docker', $content);
    }

    /**
     * Search logs with filters.
     *
     * @param  array{server_id?: int, project_id?: int, source?: string, level?: string, search?: string, date_from?: string, date_to?: string}  $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, LogEntry>
     */
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

    /**
     * Clean up old log entries.
     */
    public function cleanOldLogs(int $days = 30): int
    {
        return LogEntry::where('logged_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get available log parser types.
     *
     * @return array<int, string>
     */
    public function getAvailableParserTypes(): array
    {
        return $this->parserFactory->getAvailableTypes();
    }
}
