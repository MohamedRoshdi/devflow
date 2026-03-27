<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Checks Redis health on remote (or local) servers via redis-cli.
 *
 * All commands run over the same SSH path as every other service
 * that uses the ExecutesRemoteCommands trait — no additional
 * dependencies are required.
 */
class RedisHealthService
{
    use ExecutesRemoteCommands;

    /**
     * Check whether Redis is reachable and responding on the server.
     *
     * Runs `redis-cli ping` and verifies the response is "PONG".
     */
    public function isHealthy(Server $server): bool
    {
        try {
            $output = $this->getRemoteOutput(
                $server,
                'redis-cli ping 2>/dev/null || echo "FAIL"',
                false
            );

            return trim($output) === 'PONG';
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: ping failed', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get Redis memory usage statistics.
     *
     * Parses `redis-cli info memory` and returns human-readable values for
     * used_memory, peak used memory, and the configured maxmemory limit.
     *
     * @return array{used_memory_human: string, used_memory_peak_human: string, maxmemory_human: string, maxmemory_policy: string, mem_fragmentation_ratio: float}
     */
    public function getMemoryStats(Server $server): array
    {
        $defaults = [
            'used_memory_human' => 'N/A',
            'used_memory_peak_human' => 'N/A',
            'maxmemory_human' => 'N/A',
            'maxmemory_policy' => 'N/A',
            'mem_fragmentation_ratio' => 0.0,
        ];

        try {
            $output = $this->getRemoteOutput(
                $server,
                'redis-cli info memory 2>/dev/null || true',
                false
            );

            $parsed = $this->parseInfoSection($output);

            return [
                'used_memory_human' => $parsed['used_memory_human'] ?? 'N/A',
                'used_memory_peak_human' => $parsed['used_memory_peak_human'] ?? 'N/A',
                'maxmemory_human' => $parsed['maxmemory_human'] ?? '0B',
                'maxmemory_policy' => $parsed['maxmemory_policy'] ?? 'N/A',
                'mem_fragmentation_ratio' => (float) ($parsed['mem_fragmentation_ratio'] ?? 0.0),
            ];
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: failed to get memory stats', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    /**
     * Get Redis keyspace statistics.
     *
     * Parses `redis-cli info keyspace` and returns per-database key counts
     * and expiry information.
     *
     * @return array<string, array{keys: int, expires: int, avg_ttl: int}>
     */
    public function getKeyStats(Server $server): array
    {
        try {
            $output = $this->getRemoteOutput(
                $server,
                'redis-cli info keyspace 2>/dev/null || true',
                false
            );

            $stats = [];

            foreach (explode("\n", $output) as $line) {
                $line = trim($line);

                // Lines look like: db0:keys=123,expires=45,avg_ttl=60000
                if (! preg_match('/^(db\d+):keys=(\d+),expires=(\d+),avg_ttl=(\d+)/', $line, $m)) {
                    continue;
                }

                $stats[$m[1]] = [
                    'keys' => (int) $m[2],
                    'expires' => (int) $m[3],
                    'avg_ttl' => (int) $m[4],
                ];
            }

            return $stats;
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: failed to get key stats', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get Redis client connection statistics.
     *
     * Parses `redis-cli info clients` and returns the number of connected
     * and blocked clients.
     *
     * @return array{connected_clients: int, blocked_clients: int, tracking_clients: int}
     */
    public function getClientStats(Server $server): array
    {
        $defaults = [
            'connected_clients' => 0,
            'blocked_clients' => 0,
            'tracking_clients' => 0,
        ];

        try {
            $output = $this->getRemoteOutput(
                $server,
                'redis-cli info clients 2>/dev/null || true',
                false
            );

            $parsed = $this->parseInfoSection($output);

            return [
                'connected_clients' => (int) ($parsed['connected_clients'] ?? 0),
                'blocked_clients' => (int) ($parsed['blocked_clients'] ?? 0),
                'tracking_clients' => (int) ($parsed['tracking_clients'] ?? 0),
            ];
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: failed to get client stats', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    /**
     * Get Redis server uptime and version information.
     *
     * @return array{redis_version: string, uptime_in_seconds: int, uptime_in_days: int, tcp_port: int}
     */
    public function getServerInfo(Server $server): array
    {
        $defaults = [
            'redis_version' => 'N/A',
            'uptime_in_seconds' => 0,
            'uptime_in_days' => 0,
            'tcp_port' => 6379,
        ];

        try {
            $output = $this->getRemoteOutput(
                $server,
                'redis-cli info server 2>/dev/null || true',
                false
            );

            $parsed = $this->parseInfoSection($output);

            return [
                'redis_version' => $parsed['redis_version'] ?? 'N/A',
                'uptime_in_seconds' => (int) ($parsed['uptime_in_seconds'] ?? 0),
                'uptime_in_days' => (int) ($parsed['uptime_in_days'] ?? 0),
                'tcp_port' => (int) ($parsed['tcp_port'] ?? 6379),
            ];
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: failed to get server info', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    /**
     * Get the length of a specific Redis list (queue depth).
     *
     * Used as a lightweight alternative to parsing artisan queue:monitor output.
     */
    public function getListLength(Server $server, string $key): int
    {
        try {
            $safeKey = escapeshellarg($key);

            $output = $this->getRemoteOutput(
                $server,
                "redis-cli LLEN {$safeKey} 2>/dev/null || echo 0",
                false
            );

            return max(0, (int) trim($output));
        } catch (\Exception $e) {
            Log::warning('RedisHealthService: failed to get list length', [
                'server_id' => $server->id,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return -1;
        }
    }

    /**
     * Aggregate all Redis health signals into a single status summary.
     *
     * @return array{status: string, issues: array<int, string>, reachable: bool, memory: array<string, mixed>, clients: array<string, mixed>, server_info: array<string, mixed>}
     */
    public function getHealthSummary(Server $server): array
    {
        $reachable = $this->isHealthy($server);

        if (! $reachable) {
            return [
                'status' => 'critical',
                'issues' => ['Redis is not responding to PING — service may be down'],
                'reachable' => false,
                'memory' => [],
                'clients' => [],
                'server_info' => [],
            ];
        }

        $memory = $this->getMemoryStats($server);
        $clients = $this->getClientStats($server);
        $serverInfo = $this->getServerInfo($server);

        $issues = [];
        $status = 'healthy';

        // High memory fragmentation is a warning signal
        if ($memory['mem_fragmentation_ratio'] > 1.5) {
            $status = 'warning';
            $issues[] = "High memory fragmentation ratio ({$memory['mem_fragmentation_ratio']}) — consider running MEMORY PURGE or restarting Redis";
        }

        // Many blocked clients can indicate deadlocks or slow consumers
        if ($clients['blocked_clients'] > 10) {
            $status = 'warning';
            $issues[] = "{$clients['blocked_clients']} clients are currently blocked";
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'reachable' => true,
            'memory' => $memory,
            'clients' => $clients,
            'server_info' => $serverInfo,
        ];
    }

    /**
     * Parse a `redis-cli info` section output into a key => value map.
     *
     * Each line in a redis-cli info section looks like:
     *   used_memory_human:1.23M
     *   connected_clients:5
     *
     * Lines starting with `#` are section headers and are skipped.
     *
     * @return array<string, string>
     */
    private function parseInfoSection(string $output): array
    {
        $parsed = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $parsed[trim($key)] = trim($value);
        }

        return $parsed;
    }
}
