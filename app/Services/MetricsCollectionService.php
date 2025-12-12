<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Collection;

/**
 * Metrics Collection Service
 *
 * Collects system metrics from servers via SSH.
 * Provides CPU, memory, disk, network, and load average statistics.
 */
class MetricsCollectionService
{
    /**
     * Default SSH timeout in seconds
     */
    private const SSH_TIMEOUT = 10;

    /**
     * Collect metrics from a server
     *
     * @return array{cpu: float|null, memory: array, disk: array, network: array, load: array, uptime: string|null}
     */
    public function collectServerMetrics(Server $server): array
    {
        return [
            'cpu' => $this->getCpuUsage($server),
            'memory' => $this->getMemoryUsage($server),
            'disk' => $this->getDiskUsage($server),
            'network' => $this->getNetworkStats($server),
            'load' => $this->getLoadAverage($server),
            'uptime' => $this->getUptime($server),
        ];
    }

    /**
     * Collect metrics from all online servers
     *
     * @return array<int, array>
     */
    public function collectAllServerMetrics(): array
    {
        return Server::where('status', 'online')
            ->get()
            ->mapWithKeys(fn(Server $server) => [
                $server->id => $this->collectServerMetrics($server)
            ])
            ->toArray();
    }

    /**
     * Get CPU usage percentage
     *
     * Uses top command to get current CPU usage.
     * Falls back to mpstat if available.
     */
    private function getCpuUsage(Server $server): ?float
    {
        try {
            // Try using top first (most compatible)
            $command = "top -bn1 | grep \"Cpu(s)\" | awk '{print \$2}' | cut -d'%' -f1";
            $result = $this->executeSSHCommand($server, $command);

            if ($result['success']) {
                $cpuUsage = (float) trim($result['output']);
                return $cpuUsage > 0 ? $cpuUsage : null;
            }

            // Fallback to mpstat if available
            $command = "mpstat 1 1 | awk '/Average/ {print 100 - \$NF}'";
            $result = $this->executeSSHCommand($server, $command);

            if ($result['success']) {
                $cpuUsage = (float) trim($result['output']);
                return $cpuUsage > 0 ? $cpuUsage : null;
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning("Failed to get CPU usage for server {$server->name}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get memory usage statistics
     *
     * @return array{usage_percent: float|null, used_mb: int|null, total_mb: int|null, free_mb: int|null, available_mb: int|null}
     */
    private function getMemoryUsage(Server $server): array
    {
        try {
            // Get detailed memory stats using free command
            $command = "free -m | grep Mem";
            $result = $this->executeSSHCommand($server, $command);

            if (!$result['success']) {
                return [
                    'usage_percent' => null,
                    'used_mb' => null,
                    'total_mb' => null,
                    'free_mb' => null,
                    'available_mb' => null,
                ];
            }

            // Parse free output: Mem: total used free shared buff/cache available
            $parts = preg_split('/\s+/', trim($result['output']));

            if (count($parts) < 7) {
                return [
                    'usage_percent' => null,
                    'used_mb' => null,
                    'total_mb' => null,
                    'free_mb' => null,
                    'available_mb' => null,
                ];
            }

            $total = (int) $parts[1];
            $used = (int) $parts[2];
            $free = (int) $parts[3];
            $available = (int) ($parts[6] ?? $free);

            $usagePercent = $total > 0 ? round(($used / $total) * 100, 2) : null;

            return [
                'usage_percent' => $usagePercent,
                'used_mb' => $used,
                'total_mb' => $total,
                'free_mb' => $free,
                'available_mb' => $available,
            ];
        } catch (\Exception $e) {
            \Log::warning("Failed to get memory usage for server {$server->name}: {$e->getMessage()}");
            return [
                'usage_percent' => null,
                'used_mb' => null,
                'total_mb' => null,
                'free_mb' => null,
                'available_mb' => null,
            ];
        }
    }

    /**
     * Get disk usage statistics
     *
     * @return array{usage_percent: float|null, used_gb: float|null, total_gb: float|null, free_gb: float|null, mount_point: string}
     */
    private function getDiskUsage(Server $server): array
    {
        try {
            // Get disk usage for root partition
            $command = "df -h / | tail -1";
            $result = $this->executeSSHCommand($server, $command);

            if (!$result['success']) {
                return [
                    'usage_percent' => null,
                    'used_gb' => null,
                    'total_gb' => null,
                    'free_gb' => null,
                    'mount_point' => '/',
                ];
            }

            // Parse df output: Filesystem Size Used Avail Use% Mounted on
            $parts = preg_split('/\s+/', trim($result['output']));

            if (count($parts) < 6) {
                return [
                    'usage_percent' => null,
                    'used_gb' => null,
                    'total_gb' => null,
                    'free_gb' => null,
                    'mount_point' => '/',
                ];
            }

            $totalStr = $parts[1];
            $usedStr = $parts[2];
            $availStr = $parts[3];
            $usagePercentStr = rtrim($parts[4], '%');
            $mountPoint = $parts[5];

            return [
                'usage_percent' => is_numeric($usagePercentStr) ? (float) $usagePercentStr : null,
                'used_gb' => $this->convertToGB($usedStr),
                'total_gb' => $this->convertToGB($totalStr),
                'free_gb' => $this->convertToGB($availStr),
                'mount_point' => $mountPoint,
            ];
        } catch (\Exception $e) {
            \Log::warning("Failed to get disk usage for server {$server->name}: {$e->getMessage()}");
            return [
                'usage_percent' => null,
                'used_gb' => null,
                'total_gb' => null,
                'free_gb' => null,
                'mount_point' => '/',
            ];
        }
    }

    /**
     * Convert human-readable size to GB
     */
    private function convertToGB(string $size): ?float
    {
        $size = strtoupper(trim($size));
        $value = (float) $size;

        if (str_contains($size, 'K')) {
            return round($value / 1024 / 1024, 2);
        } elseif (str_contains($size, 'M')) {
            return round($value / 1024, 2);
        } elseif (str_contains($size, 'G')) {
            return round($value, 2);
        } elseif (str_contains($size, 'T')) {
            return round($value * 1024, 2);
        }

        return null;
    }

    /**
     * Get network statistics
     *
     * @return array{in_bytes: int|null, out_bytes: int|null, in_packets: int|null, out_packets: int|null}
     */
    private function getNetworkStats(Server $server): array
    {
        try {
            // Get network stats from /proc/net/dev
            $command = "cat /proc/net/dev | grep -E 'eth0|ens|enp' | head -1 | awk '{print \$2,\$10,\$3,\$11}'";
            $result = $this->executeSSHCommand($server, $command);

            if (!$result['success']) {
                return [
                    'in_bytes' => null,
                    'out_bytes' => null,
                    'in_packets' => null,
                    'out_packets' => null,
                ];
            }

            $parts = preg_split('/\s+/', trim($result['output']));

            if (count($parts) < 4) {
                return [
                    'in_bytes' => null,
                    'out_bytes' => null,
                    'in_packets' => null,
                    'out_packets' => null,
                ];
            }

            return [
                'in_bytes' => (int) $parts[0],
                'out_bytes' => (int) $parts[1],
                'in_packets' => (int) $parts[2],
                'out_packets' => (int) $parts[3],
            ];
        } catch (\Exception $e) {
            \Log::warning("Failed to get network stats for server {$server->name}: {$e->getMessage()}");
            return [
                'in_bytes' => null,
                'out_bytes' => null,
                'in_packets' => null,
                'out_packets' => null,
            ];
        }
    }

    /**
     * Get load average (1, 5, 15 minutes)
     *
     * @return array{load_1: float|null, load_5: float|null, load_15: float|null}
     */
    private function getLoadAverage(Server $server): array
    {
        try {
            $command = "uptime | awk -F'load average:' '{print \$2}' | awk '{print \$1,\$2,\$3}' | tr -d ','";
            $result = $this->executeSSHCommand($server, $command);

            if (!$result['success']) {
                return [
                    'load_1' => null,
                    'load_5' => null,
                    'load_15' => null,
                ];
            }

            $parts = preg_split('/\s+/', trim($result['output']));

            if (count($parts) < 3) {
                return [
                    'load_1' => null,
                    'load_5' => null,
                    'load_15' => null,
                ];
            }

            return [
                'load_1' => (float) $parts[0],
                'load_5' => (float) $parts[1],
                'load_15' => (float) $parts[2],
            ];
        } catch (\Exception $e) {
            \Log::warning("Failed to get load average for server {$server->name}: {$e->getMessage()}");
            return [
                'load_1' => null,
                'load_5' => null,
                'load_15' => null,
            ];
        }
    }

    /**
     * Get server uptime
     */
    private function getUptime(Server $server): ?string
    {
        try {
            $command = "uptime -p";
            $result = $this->executeSSHCommand($server, $command);

            if ($result['success']) {
                return trim($result['output']);
            }

            // Fallback to basic uptime
            $command = "uptime | awk '{print \$3,\$4}' | tr -d ','";
            $result = $this->executeSSHCommand($server, $command);

            return $result['success'] ? trim($result['output']) : null;
        } catch (\Exception $e) {
            \Log::warning("Failed to get uptime for server {$server->name}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Execute SSH command on server
     *
     * @return array{success: bool, output: string, error: string|null}
     */
    private function executeSSHCommand(Server $server, string $command): array
    {
        try {
            $sshCommand = $this->buildSSHCommand($server, $command);
            $process = Process::timeout(self::SSH_TIMEOUT)->run($sshCommand);

            return [
                'success' => $process->successful(),
                'output' => $process->output(),
                'error' => $process->failed() ? $process->errorOutput() : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build SSH command for remote execution
     */
    private function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=5',
            "-p {$server->port}",
        ];

        // Add SSH key if available
        if ($server->ssh_key) {
            $keyFile = $this->createTempSSHKeyFile($server);
            if ($keyFile) {
                $sshOptions[] = "-i {$keyFile}";
            }
        }

        return sprintf(
            'ssh %s %s@%s "%s"',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand)
        );
    }

    /**
     * Create temporary SSH key file
     *
     * Note: This is a simplified version. In production, consider using
     * a proper key management service or caching mechanism.
     */
    private function createTempSSHKeyFile(Server $server): ?string
    {
        try {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            if ($keyFile === false) {
                return null;
            }

            chmod($keyFile, 0600);
            file_put_contents($keyFile, $server->ssh_key);

            // Register shutdown function to clean up
            register_shutdown_function(function () use ($keyFile) {
                if (file_exists($keyFile)) {
                    @unlink($keyFile);
                }
            });

            return $keyFile;
        } catch (\Exception $e) {
            \Log::warning("Failed to create SSH key file: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get comprehensive server health metrics
     *
     * @return array{cpu: float|null, memory: float|null, disk: float|null, load: float|null, status: string}
     */
    public function getServerHealthMetrics(Server $server): array
    {
        $metrics = $this->collectServerMetrics($server);

        $cpuUsage = $metrics['cpu'];
        $memoryUsage = $metrics['memory']['usage_percent'];
        $diskUsage = $metrics['disk']['usage_percent'];
        $load1 = $metrics['load']['load_1'];

        // Determine health status
        $status = 'healthy';
        if ($cpuUsage > 90 || $memoryUsage > 90 || $diskUsage > 90) {
            $status = 'critical';
        } elseif ($cpuUsage > 75 || $memoryUsage > 75 || $diskUsage > 75) {
            $status = 'warning';
        }

        return [
            'cpu' => $cpuUsage,
            'memory' => $memoryUsage,
            'disk' => $diskUsage,
            'load' => $load1,
            'status' => $status,
        ];
    }

    /**
     * Collect metrics and return formatted for dashboard
     *
     * @return array<int, array>
     */
    public function getFormattedMetricsForDashboard(): array
    {
        return Server::where('status', 'online')
            ->get()
            ->map(function (Server $server) {
                $metrics = $this->collectServerMetrics($server);

                return [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'cpu_usage' => $metrics['cpu'],
                    'memory_usage' => $metrics['memory']['usage_percent'],
                    'memory_used_mb' => $metrics['memory']['used_mb'],
                    'memory_total_mb' => $metrics['memory']['total_mb'],
                    'disk_usage' => $metrics['disk']['usage_percent'],
                    'disk_used_gb' => $metrics['disk']['used_gb'],
                    'disk_total_gb' => $metrics['disk']['total_gb'],
                    'load_average_1' => $metrics['load']['load_1'],
                    'load_average_5' => $metrics['load']['load_5'],
                    'load_average_15' => $metrics['load']['load_15'],
                    'network_in_bytes' => $metrics['network']['in_bytes'],
                    'network_out_bytes' => $metrics['network']['out_bytes'],
                    'uptime' => $metrics['uptime'],
                    'status' => $server->status,
                    'recorded_at' => now(),
                ];
            })
            ->toArray();
    }
}
