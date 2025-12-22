<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use phpseclib3\Net\SSH2;

class ServerConnectivityService
{
    /**
     * Check if server uses password authentication
     * Note: Using strlen() because PHP treats "0" as falsy
     */
    protected function usesPasswordAuth(Server $server): bool
    {
        return $server->ssh_password !== null && strlen($server->ssh_password) > 0;
    }

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

            // Use phpseclib for password authentication (more reliable)
            // Note: Using strlen() because PHP treats "0" as falsy
            if ($this->usesPasswordAuth($server)) {
                return $this->testConnectionWithPhpseclib($server);
            }

            // Use system SSH for key-based authentication
            $command = $this->buildSSHCommand($server, 'echo "CONNECTION_TEST"');

            $startTime = microtime(true);
            $result = Process::timeout(10)->run($command);
            $latency = (microtime(true) - $startTime) * 1000;

            if ($result->successful() && str_contains($result->output(), 'CONNECTION_TEST')) {
                return [
                    'reachable' => true,
                    'message' => 'SSH connection successful',
                    'latency_ms' => round($latency, 2),
                ];
            }

            return [
                'reachable' => false,
                'message' => 'SSH connection failed: '.$result->errorOutput(),
                'error' => $result->errorOutput(),
            ];

        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Connection test failed', [
                'server_id' => $server->id ?? null,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return [
                'reachable' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test SSH connection using phpseclib (for password authentication)
     */
    protected function testConnectionWithPhpseclib(Server $server): array
    {
        $startTime = microtime(true);

        try {
            $ssh = new SSH2($server->ip_address, $server->port, 10);

            if (! $ssh->login($server->username, $server->ssh_password)) {
                return [
                    'reachable' => false,
                    'message' => 'SSH authentication failed: Invalid username or password',
                    'error' => 'Authentication failed',
                ];
            }

            // Test command execution
            $output = $ssh->exec('echo "CONNECTION_TEST"');
            $latency = (microtime(true) - $startTime) * 1000;

            if (str_contains($output, 'CONNECTION_TEST')) {
                return [
                    'reachable' => true,
                    'message' => 'SSH connection successful',
                    'latency_ms' => round($latency, 2),
                ];
            }

            return [
                'reachable' => false,
                'message' => 'SSH connection established but command execution failed',
                'error' => 'Command execution failed',
            ];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Provide more user-friendly error messages
            if (str_contains($errorMessage, 'Connection refused')) {
                $errorMessage = 'Connection refused - SSH server may not be running on port '.$server->port;
            } elseif (str_contains($errorMessage, 'Connection timed out') || str_contains($errorMessage, 'timed out')) {
                $errorMessage = 'Connection timed out - Server may be unreachable or firewall blocking port '.$server->port;
            } elseif (str_contains($errorMessage, 'No route to host')) {
                $errorMessage = 'No route to host - Server IP may be incorrect or network issue';
            }

            return [
                'reachable' => false,
                'message' => 'SSH connection failed: '.$errorMessage,
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

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return [];
                }

                foreach ($commands as $key => $cmd) {
                    $output = trim($ssh->exec($cmd));
                    if (in_array($key, ['cpu_cores', 'memory_gb', 'disk_gb'])) {
                        $output = $this->extractNumericValue($output);
                    }
                    $info[$key] = $output;
                }

                return $info;
            }

            // Use system SSH for key-based authentication
            foreach ($commands as $key => $cmd) {
                $command = $this->buildSSHCommand($server, $cmd, true);
                $result = Process::timeout(10)->run($command);

                if ($result->successful()) {
                    $output = trim($result->output());
                    // For numeric fields, extract only the number
                    if (in_array($key, ['cpu_cores', 'memory_gb', 'disk_gb'])) {
                        $output = $this->extractNumericValue($output);
                    }
                    $info[$key] = $output;
                }
            }

            return $info;

        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Failed to get server info', [
                'server_id' => $server->id ?? null,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get a phpseclib SSH connection for password authentication
     */
    protected function getPhpseclibConnection(Server $server): ?SSH2
    {
        try {
            $ssh = new SSH2($server->ip_address, $server->port, 10);

            if (! $ssh->login($server->username, $server->ssh_password)) {
                Log::warning('ServerConnectivityService: phpseclib login failed', [
                    'ip' => $server->ip_address,
                    'username' => $server->username,
                ]);

                return null;
            }

            return $ssh;
        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: phpseclib connection failed', [
                'ip' => $server->ip_address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Execute a command on server using phpseclib or system SSH
     */
    protected function executeRemoteCommand(Server $server, string $command, bool $useSudo = false): ?string
    {
        try {
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return null;
                }

                if ($useSudo && strtolower($server->username) !== 'root') {
                    // For sudo with password using phpseclib
                    $ssh->enablePTY();
                    $ssh->exec("sudo -S {$command}");
                    $ssh->write($server->ssh_password."\n");
                    sleep(1); // Wait for command to execute
                    $output = $ssh->read();

                    return $output;
                }

                return $ssh->exec($command);
            }

            // Use system SSH for key-based authentication
            $sshCommand = $this->buildSSHCommand($server, $command);
            $result = Process::timeout(60)->run($sshCommand);

            return $result->successful() ? $result->output() : null;
        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Remote command execution failed', [
                'server_id' => $server->id ?? null,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            return null;
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
            // Ignore - external API call failure is non-critical for localhost detection
            Log::debug('ServerConnectivityService: Could not fetch public IP for localhost check', [
                'error' => $e->getMessage(),
            ]);
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
            Log::error('ServerConnectivityService: Failed to get local server info', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Execute command locally
     */
    protected function executeLocal(string $command): string
    {
        $result = Process::run($command);

        return trim($result->output());
    }

    /**
     * Reboot the server
     */
    public function rebootServer(Server $server): array
    {
        try {
            $isRoot = strtolower($server->username) === 'root';

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect to server',
                    ];
                }

                if ($isRoot) {
                    $ssh->exec('reboot');
                } else {
                    $ssh->enablePTY();
                    $ssh->exec('sudo -S reboot');
                    $ssh->write($server->ssh_password."\n");
                }
            } else {
                // Use system SSH for key-based authentication
                $sudoPrefix = $isRoot ? '' : 'sudo ';
                $command = $this->buildSSHCommand($server, "{$sudoPrefix}reboot");
                Process::timeout(30)->run($command);
            }

            // Reboot command usually closes connection, so we check if it was initiated
            // Update server status to reflect it's rebooting
            $server->update([
                'status' => 'maintenance',
                'last_ping_at' => now(),
            ]);

            Log::info('ServerConnectivityService: Server reboot initiated', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
            ]);

            return [
                'success' => true,
                'message' => 'Server reboot initiated. It may take a few minutes to come back online.',
            ];

        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Server reboot failed', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reboot server: '.$e->getMessage(),
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
            if (! in_array($service, $allowedServices) && ! preg_match('/^php\d+\.\d+-fpm$/', $service)) {
                return [
                    'success' => false,
                    'message' => 'Service not allowed: '.$service,
                ];
            }

            $isRoot = strtolower($server->username) === 'root';
            $restartCommand = "systemctl restart {$service}";

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect to server',
                    ];
                }

                if ($isRoot) {
                    $output = $ssh->exec($restartCommand);
                } else {
                    $ssh->enablePTY();
                    $ssh->exec("sudo -S {$restartCommand}");
                    $ssh->write($server->ssh_password."\n");
                    sleep(2); // Wait for service restart
                    $output = $ssh->read();
                }
            } else {
                // Use system SSH for key-based authentication
                $sudoPrefix = $isRoot ? '' : 'sudo ';
                $command = $this->buildSSHCommand($server, "{$sudoPrefix}{$restartCommand}");
                $result = Process::timeout(60)->run($command);

                if (! $result->successful()) {
                    return [
                        'success' => false,
                        'message' => 'Failed to restart service: '.$result->errorOutput(),
                    ];
                }
            }

            Log::info('ServerConnectivityService: Service restarted', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'service' => $service,
            ]);

            return [
                'success' => true,
                'message' => "Service '{$service}' restarted successfully.",
            ];

        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Failed to restart service', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to restart service: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get server uptime
     */
    public function getUptime(Server $server): array
    {
        try {
            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return ['success' => false, 'uptime' => null];
                }
                $output = trim($ssh->exec('uptime -p'));

                return [
                    'success' => true,
                    'uptime' => $output,
                ];
            }

            // Use system SSH for key-based authentication
            $command = $this->buildSSHCommand($server, 'uptime -p', true);
            $result = Process::timeout(10)->run($command);

            if ($result->successful()) {
                return [
                    'success' => true,
                    'uptime' => trim($result->output()),
                ];
            }

            return ['success' => false, 'uptime' => null];

        } catch (\Exception $e) {
            Log::warning('ServerConnectivityService: Failed to get uptime', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'uptime' => null];
        }
    }

    /**
     * Get disk usage
     */
    public function getDiskUsage(Server $server): array
    {
        try {
            $diskCommand = "df -h / | tail -1 | awk '{print \$5}'";

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return ['success' => false, 'usage' => null];
                }
                $output = trim($ssh->exec($diskCommand));

                return [
                    'success' => true,
                    'usage' => $output,
                ];
            }

            // Use system SSH for key-based authentication
            $command = $this->buildSSHCommand($server, $diskCommand, true);
            $result = Process::timeout(10)->run($command);

            if ($result->successful()) {
                return [
                    'success' => true,
                    'usage' => trim($result->output()),
                ];
            }

            return ['success' => false, 'usage' => null];

        } catch (\Exception $e) {
            Log::warning('ServerConnectivityService: Failed to get disk usage', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'usage' => null];
        }
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(Server $server): array
    {
        try {
            $memCommand = "free | awk '/^Mem:/{printf \"%.1f\", \$3/\$2 * 100}'";

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return ['success' => false, 'usage' => null];
                }
                $output = trim($ssh->exec($memCommand));

                return [
                    'success' => true,
                    'usage' => $output.'%',
                ];
            }

            // Use system SSH for key-based authentication
            $command = $this->buildSSHCommand($server, $memCommand, true);
            $result = Process::timeout(10)->run($command);

            if ($result->successful()) {
                return [
                    'success' => true,
                    'usage' => trim($result->output()).'%',
                ];
            }

            return ['success' => false, 'usage' => null];

        } catch (\Exception $e) {
            Log::warning('ServerConnectivityService: Failed to get memory usage', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

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
            $cacheCommand = "sync && sh -c 'echo 3 > /proc/sys/vm/drop_caches'";

            // Use phpseclib for password authentication
            if ($this->usesPasswordAuth($server)) {
                $ssh = $this->getPhpseclibConnection($server);
                if (! $ssh) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect to server',
                    ];
                }

                if ($isRoot) {
                    $ssh->exec($cacheCommand);
                } else {
                    $ssh->enablePTY();
                    $ssh->exec("sudo -S {$cacheCommand}");
                    $ssh->write($server->ssh_password."\n");
                    sleep(1);
                }

                return [
                    'success' => true,
                    'message' => 'System cache cleared successfully.',
                ];
            }

            // Use system SSH for key-based authentication
            $sudoPrefix = $isRoot ? '' : 'sudo ';
            $command = $this->buildSSHCommand($server, "{$sudoPrefix}{$cacheCommand}");
            $result = Process::timeout(30)->run($command);

            if ($result->successful()) {
                return [
                    'success' => true,
                    'message' => 'System cache cleared successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to clear cache: '.$result->errorOutput(),
            ];

        } catch (\Exception $e) {
            Log::error('ServerConnectivityService: Failed to clear system cache', [
                'server_id' => $server->id,
                'ip' => $server->ip_address,
                'port' => $server->port,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to clear cache: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build SSH command
     *
     * @param  bool  $suppressWarnings  - If true, redirects stderr to /dev/null
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand, bool $suppressWarnings = false): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        // Check if password authentication should be used
        if ($this->usesPasswordAuth($server)) {
            // Use sshpass for password authentication
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remoteCommand),
                $stderrRedirect
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            // Use provided key content
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        } else {
            // Try to use mounted host SSH keys (default locations)
            // Keys are mounted from host - copy to temp with correct permissions
            $possibleKeys = [
                '/tmp/host_ssh_key',  // Pre-copied key with correct permissions
                '/home/www-data/.ssh/id_rsa',
                '/home/www-data/.ssh/id_ed25519',
                '/root/.ssh/id_rsa',
            ];

            foreach ($possibleKeys as $keyPath) {
                if (file_exists($keyPath)) {
                    // Copy key to temp file with correct permissions (600)
                    $keyContent = @file_get_contents($keyPath);
                    if ($keyContent) {
                        $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                        file_put_contents($keyFile, $keyContent);
                        chmod($keyFile, 0600);
                        $sshOptions[] = '-i '.escapeshellarg($keyFile);
                        break;
                    }
                }
            }
        }

        return sprintf(
            'ssh %s %s@%s %s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($remoteCommand),
            $stderrRedirect
        );
    }
}
