<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerMetric;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServerMetricsService
{
    /**
     * Collect metrics from a server via SSH
     */
    public function collectMetrics(Server $server): ?ServerMetric
    {
        try {
            if ($this->isLocalhost($server->ip_address)) {
                return $this->collectLocalMetrics($server);
            }

            return $this->collectRemoteMetrics($server);
        } catch (\Exception $e) {
            Log::error('Failed to collect server metrics', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get metrics history for a server
     */
    public function getMetricsHistory(Server $server, string $period = '24h'): \Illuminate\Database\Eloquent\Collection
    {
        $since = $this->getPeriodStartDate($period);

        return ServerMetric::where('server_id', $server->id)
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at', 'desc')
            ->get();
    }

    /**
     * Get latest metrics for a server
     */
    public function getLatestMetrics(Server $server): ?ServerMetric
    {
        return ServerMetric::where('server_id', $server->id)
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Collect metrics from localhost
     */
    protected function collectLocalMetrics(Server $server): ServerMetric
    {
        // CPU Usage
        $cpuUsage = $this->executeLocal("top -bn1 | grep 'Cpu(s)' | awk '{print 100 - \$8}'");

        // Memory Usage
        $memoryInfo = $this->executeLocal("free -m | awk '/^Mem:/{printf \"%.2f %.0f %.0f\", (\$3/\$2)*100, \$3, \$2}'");
        $memoryParts = explode(' ', trim($memoryInfo));

        // Disk Usage
        $diskInfo = $this->executeLocal("df -BG / | tail -1 | awk '{gsub(/G/,\"\"); printf \"%.2f %.0f %.0f\", (\$3/\$2)*100, \$3, \$2}'");
        $diskParts = explode(' ', trim($diskInfo));

        // Load Average
        $loadAvg = $this->executeLocal("cat /proc/loadavg | awk '{print \$1\" \"\$2\" \"\$3}'");
        $loadParts = explode(' ', trim($loadAvg));

        // Network Usage
        $networkIn = $this->executeLocal("cat /sys/class/net/eth0/statistics/rx_bytes 2>/dev/null || echo 0");
        $networkOut = $this->executeLocal("cat /sys/class/net/eth0/statistics/tx_bytes 2>/dev/null || echo 0");

        return ServerMetric::create([
            'server_id' => $server->id,
            'cpu_usage' => $this->sanitizeDecimal($cpuUsage),
            'memory_usage' => $this->sanitizeDecimal($memoryParts[0] ?? 0),
            'memory_used_mb' => (int)($memoryParts[1] ?? 0),
            'memory_total_mb' => (int)($memoryParts[2] ?? 0),
            'disk_usage' => $this->sanitizeDecimal($diskParts[0] ?? 0),
            'disk_used_gb' => (int)($diskParts[1] ?? 0),
            'disk_total_gb' => (int)($diskParts[2] ?? 0),
            'load_average_1' => $this->sanitizeDecimal($loadParts[0] ?? 0),
            'load_average_5' => $this->sanitizeDecimal($loadParts[1] ?? 0),
            'load_average_15' => $this->sanitizeDecimal($loadParts[2] ?? 0),
            'network_in_bytes' => (int)trim($networkIn),
            'network_out_bytes' => (int)trim($networkOut),
            'recorded_at' => now(),
        ]);
    }

    /**
     * Collect metrics from remote server via SSH
     */
    protected function collectRemoteMetrics(Server $server): ServerMetric
    {
        // CPU Usage
        $cpuCmd = "top -bn1 | grep 'Cpu(s)' | awk '{print 100 - \$8}'";
        $cpuUsage = $this->executeSSHCommand($server, $cpuCmd);

        // Memory Usage
        $memoryCmd = "free -m | awk '/^Mem:/{printf \"%.2f %.0f %.0f\", (\$3/\$2)*100, \$3, \$2}'";
        $memoryInfo = $this->executeSSHCommand($server, $memoryCmd);
        $memoryParts = explode(' ', trim($memoryInfo));

        // Disk Usage
        $diskCmd = "df -BG / | tail -1 | awk '{gsub(/G/,\"\"); printf \"%.2f %.0f %.0f\", (\$3/\$2)*100, \$3, \$2}'";
        $diskInfo = $this->executeSSHCommand($server, $diskCmd);
        $diskParts = explode(' ', trim($diskInfo));

        // Load Average
        $loadCmd = "cat /proc/loadavg | awk '{print \$1\" \"\$2\" \"\$3}'";
        $loadAvg = $this->executeSSHCommand($server, $loadCmd);
        $loadParts = explode(' ', trim($loadAvg));

        // Network Usage - try multiple interface names
        $networkInCmd = "cat /sys/class/net/eth0/statistics/rx_bytes 2>/dev/null || cat /sys/class/net/ens3/statistics/rx_bytes 2>/dev/null || cat /sys/class/net/enp0s3/statistics/rx_bytes 2>/dev/null || echo 0";
        $networkOutCmd = "cat /sys/class/net/eth0/statistics/tx_bytes 2>/dev/null || cat /sys/class/net/ens3/statistics/tx_bytes 2>/dev/null || cat /sys/class/net/enp0s3/statistics/tx_bytes 2>/dev/null || echo 0";

        $networkIn = $this->executeSSHCommand($server, $networkInCmd);
        $networkOut = $this->executeSSHCommand($server, $networkOutCmd);

        return ServerMetric::create([
            'server_id' => $server->id,
            'cpu_usage' => $this->sanitizeDecimal($cpuUsage),
            'memory_usage' => $this->sanitizeDecimal($memoryParts[0] ?? 0),
            'memory_used_mb' => (int)($memoryParts[1] ?? 0),
            'memory_total_mb' => (int)($memoryParts[2] ?? 0),
            'disk_usage' => $this->sanitizeDecimal($diskParts[0] ?? 0),
            'disk_used_gb' => (int)($diskParts[1] ?? 0),
            'disk_total_gb' => (int)($diskParts[2] ?? 0),
            'load_average_1' => $this->sanitizeDecimal($loadParts[0] ?? 0),
            'load_average_5' => $this->sanitizeDecimal($loadParts[1] ?? 0),
            'load_average_15' => $this->sanitizeDecimal($loadParts[2] ?? 0),
            'network_in_bytes' => (int)trim($networkIn),
            'network_out_bytes' => (int)trim($networkOut),
            'recorded_at' => now(),
        ]);
    }

    /**
     * Execute SSH command and return output
     */
    protected function executeSSHCommand(Server $server, string $remoteCommand): string
    {
        $command = $this->buildSSHCommand($server, $remoteCommand);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(10);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return '0';
    }

    /**
     * Execute command locally
     */
    protected function executeLocal(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(5);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : '0';
    }

    /**
     * Build SSH command
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $server->port,
        ];

        // Check if password authentication should be used
        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" 2>/dev/null',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand)
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "%s" 2>/dev/null',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand)
        );
    }

    /**
     * Check if IP is localhost
     */
    protected function isLocalhost(string $ip): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ip, $localIPs)) {
            return true;
        }

        // Check if IP matches server's own IP
        $serverIP = gethostbyname(gethostname());
        if ($ip === $serverIP) {
            return true;
        }

        // Try to get server's public IP
        try {
            $publicIP = trim(file_get_contents('http://api.ipify.org'));
            if ($ip === $publicIP) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return false;
    }

    /**
     * Get period start date based on period string
     */
    protected function getPeriodStartDate(string $period): Carbon
    {
        return match($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    /**
     * Sanitize decimal values to ensure they're within valid range
     */
    protected function sanitizeDecimal(mixed $value): float
    {
        $float = (float)$value;

        // Clamp between 0 and 100 for percentages
        if ($float < 0) {
            return 0.0;
        }

        if ($float > 100) {
            return 100.0;
        }

        return round($float, 2);
    }

    /**
     * Get top processes by CPU usage
     */
    public function getTopProcessesByCPU(Server $server, int $limit = 10): array
    {
        try {
            $command = "ps aux --sort=-%cpu | head -" . ($limit + 1);

            if ($this->isLocalhost($server->ip_address)) {
                $output = $this->executeLocal($command);
            } else {
                $output = $this->executeSSHCommand($server, $command);
            }

            return $this->parseProcessOutput($output);
        } catch (\Exception $e) {
            Log::error('Failed to get top processes by CPU', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get top processes by Memory usage
     */
    public function getTopProcessesByMemory(Server $server, int $limit = 10): array
    {
        try {
            $command = "ps aux --sort=-%mem | head -" . ($limit + 1);

            if ($this->isLocalhost($server->ip_address)) {
                $output = $this->executeLocal($command);
            } else {
                $output = $this->executeSSHCommand($server, $command);
            }

            return $this->parseProcessOutput($output);
        } catch (\Exception $e) {
            Log::error('Failed to get top processes by Memory', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Parse ps aux output into structured array
     */
    protected function parseProcessOutput(string $output): array
    {
        $lines = explode("\n", trim($output));
        $processes = [];

        // Skip header line
        array_shift($lines);

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse ps aux format: USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND
            preg_match('/^(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(.+)$/', $line, $matches);

            if (count($matches) === 6) {
                $processes[] = [
                    'user' => $matches[1],
                    'pid' => (int)$matches[2],
                    'cpu' => (float)$matches[3],
                    'mem' => (float)$matches[4],
                    'command' => $this->truncateCommand($matches[5], 80),
                    'full_command' => $matches[5],
                ];
            }
        }

        return $processes;
    }

    /**
     * Truncate command string to specified length
     */
    protected function truncateCommand(string $command, int $length = 80): string
    {
        if (strlen($command) <= $length) {
            return $command;
        }

        return substr($command, 0, $length - 3) . '...';
    }
}
