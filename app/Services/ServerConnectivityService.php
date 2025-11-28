<?php

namespace App\Services;

use App\Models\Server;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class ServerConnectivityService
{
    /**
     * Test if server is reachable via SSH
     */
    public function testConnection(Server $server): array
    {
        try {
            // For localhost/same server, just check if it's the same IP
            if ($this->isLocalhost($server->ip_address)) {
                return [
                    'reachable' => true,
                    'message' => 'Localhost connection available',
                    'latency_ms' => 0,
                ];
            }

            // Test SSH connection with timeout
            $command = $this->buildSSHCommand($server, 'echo "CONNECTION_TEST"');
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(10);
            
            $startTime = microtime(true);
            $process->run();
            $latency = (microtime(true) - $startTime) * 1000;

            if ($process->isSuccessful() && str_contains($process->getOutput(), 'CONNECTION_TEST')) {
                return [
                    'reachable' => true,
                    'message' => 'SSH connection successful',
                    'latency_ms' => round($latency, 2),
                ];
            }

            return [
                'reachable' => false,
                'message' => 'SSH connection failed: ' . $process->getErrorOutput(),
                'error' => $process->getErrorOutput(),
            ];

        } catch (\Exception $e) {
            Log::error('Server connectivity test failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'reachable' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ping server and update status
     */
    public function pingAndUpdateStatus(Server $server): bool
    {
        $result = $this->testConnection($server);

        $status = $result['reachable'] ? 'online' : 'offline';
        
        $server->update([
            'status' => $status,
            'last_ping_at' => now(),
        ]);

        return $result['reachable'];
    }

    /**
     * Get server system information
     */
    public function getServerInfo(Server $server): array
    {
        try {
            if ($this->isLocalhost($server->ip_address)) {
                return $this->getLocalServerInfo();
            }

            $commands = [
                'os' => 'uname -s',
                'cpu_cores' => 'nproc',
                'memory_gb' => 'free -m | awk \'/^Mem:/{printf "%.1f", $2/1024}\'',
                'disk_gb' => 'df -BG / | tail -1 | awk \'{print $2}\' | sed \'s/G//\'',
            ];

            $info = [];
            foreach ($commands as $key => $cmd) {
                $command = $this->buildSSHCommand($server, $cmd, true);
                $process = Process::fromShellCommandline($command);
                $process->setTimeout(10);
                $process->run();

                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    // For numeric fields, extract only the number
                    if (in_array($key, ['cpu_cores', 'memory_gb', 'disk_gb'])) {
                        $output = $this->extractNumericValue($output);
                    }
                    $info[$key] = $output;
                }
            }

            return $info;

        } catch (\Exception $e) {
            Log::error('Failed to get server info', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract numeric value from output (handles SSH warnings mixed in output)
     */
    protected function extractNumericValue(string $output): float|int|null
    {
        // Split by lines and find the first line that is purely numeric
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (is_numeric($line)) {
                // Return float if it has decimals, int otherwise
                return str_contains($line, '.') ? (float) $line : (int) $line;
            }
        }

        // Try to extract any number (including decimals) from the output
        if (preg_match('/(\d+\.?\d*)/', $output, $matches)) {
            $value = $matches[1];
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return null;
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
     * Get local server information
     */
    protected function getLocalServerInfo(): array
    {
        try {
            return [
                'os' => PHP_OS,
                'cpu_cores' => $this->executeLocal('nproc'),
                'memory_gb' => $this->executeLocal('free -g | awk \'/^Mem:/{print $2}\''),
                'disk_gb' => $this->executeLocal('df -BG / | tail -1 | awk \'{print $2}\' | sed \'s/G//\''),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Execute command locally
     */
    protected function executeLocal(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->run();
        return trim($process->getOutput());
    }

    /**
     * Reboot the server
     */
    public function rebootServer(Server $server): array
    {
        try {
            $isRoot = strtolower($server->username) === 'root';

            // Build sudo prefix for non-root users
            if ($isRoot) {
                $sudoPrefix = '';
            } elseif ($server->ssh_password) {
                $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);
                $sudoPrefix = "echo '{$escapedPassword}' | sudo -S ";
            } else {
                $sudoPrefix = 'sudo ';
            }

            $command = $this->buildSSHCommand($server, "{$sudoPrefix}reboot");
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
            $process->run();

            // Reboot command usually closes connection, so we check if it was initiated
            // Update server status to reflect it's rebooting
            $server->update([
                'status' => 'maintenance',
                'last_ping_at' => now(),
            ]);

            Log::info('Server reboot initiated', ['server_id' => $server->id]);

            return [
                'success' => true,
                'message' => 'Server reboot initiated. It may take a few minutes to come back online.',
            ];

        } catch (\Exception $e) {
            Log::error('Server reboot failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reboot server: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Restart a specific service on the server
     */
    public function restartService(Server $server, string $service): array
    {
        try {
            $allowedServices = ['nginx', 'apache2', 'mysql', 'mariadb', 'redis', 'php-fpm', 'docker', 'supervisor'];

            // Also allow php8.x-fpm variants
            if (!in_array($service, $allowedServices) && !preg_match('/^php\d+\.\d+-fpm$/', $service)) {
                return [
                    'success' => false,
                    'message' => 'Service not allowed: ' . $service,
                ];
            }

            $isRoot = strtolower($server->username) === 'root';

            if ($isRoot) {
                $sudoPrefix = '';
            } elseif ($server->ssh_password) {
                $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);
                $sudoPrefix = "echo '{$escapedPassword}' | sudo -S ";
            } else {
                $sudoPrefix = 'sudo ';
            }

            $command = $this->buildSSHCommand($server, "{$sudoPrefix}systemctl restart {$service}");
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                Log::info('Service restarted', ['server_id' => $server->id, 'service' => $service]);
                return [
                    'success' => true,
                    'message' => "Service '{$service}' restarted successfully.",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to restart service: ' . $process->getErrorOutput(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to restart service: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get server uptime
     */
    public function getUptime(Server $server): array
    {
        try {
            $command = $this->buildSSHCommand($server, 'uptime -p', true);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'uptime' => trim($process->getOutput()),
                ];
            }

            return ['success' => false, 'uptime' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'uptime' => null];
        }
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(Server $server): array
    {
        try {
            $command = $this->buildSSHCommand($server, "df -h / | tail -1 | awk '{print $5}'", true);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'usage' => trim($process->getOutput()),
                ];
            }

            return ['success' => false, 'usage' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'usage' => null];
        }
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(Server $server): array
    {
        try {
            $command = $this->buildSSHCommand($server, "free | awk '/^Mem:/{printf \"%.1f\", \$3/\$2 * 100}'", true);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'usage' => trim($process->getOutput()) . '%',
                ];
            }

            return ['success' => false, 'usage' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'usage' => null];
        }
    }

    /**
     * Clear system cache (drop caches)
     */
    public function clearSystemCache(Server $server): array
    {
        try {
            $isRoot = strtolower($server->username) === 'root';

            if ($isRoot) {
                $sudoPrefix = '';
            } elseif ($server->ssh_password) {
                $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);
                $sudoPrefix = "echo '{$escapedPassword}' | sudo -S ";
            } else {
                $sudoPrefix = 'sudo ';
            }

            // Sync filesystem and drop caches
            $command = $this->buildSSHCommand($server, "{$sudoPrefix}sync && {$sudoPrefix}sh -c 'echo 3 > /proc/sys/vm/drop_caches'");
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'message' => 'System cache cleared successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to clear cache: ' . $process->getErrorOutput(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build SSH command
     *
     * @param Server $server
     * @param string $remoteCommand
     * @param bool $suppressWarnings - If true, redirects stderr to /dev/null
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand, bool $suppressWarnings = false): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        // Check if password authentication should be used
        if ($server->ssh_password) {
            // Use sshpass for password authentication
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand),
                $stderrRedirect
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
            'ssh %s %s@%s "%s" %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand),
            $stderrRedirect
        );
    }
}

