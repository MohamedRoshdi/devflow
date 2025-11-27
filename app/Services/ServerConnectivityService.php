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
                'memory_gb' => 'free -g | awk \'/^Mem:/{print $2}\'',
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
    protected function extractNumericValue(string $output): ?int
    {
        // Split by lines and find the first line that is purely numeric
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (is_numeric($line)) {
                return (int) $line;
            }
        }

        // Try to extract any number from the output
        if (preg_match('/(\d+)/', $output, $matches)) {
            return (int) $matches[1];
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

