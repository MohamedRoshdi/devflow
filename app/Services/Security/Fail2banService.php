<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityEvent;
use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class Fail2banService
{
    public function getFail2banStatus(Server $server): array
    {
        try {
            // First check if fail2ban-client binary exists (does not require sudo)
            $whichResult = $this->executeCommand($server, 'command -v fail2ban-client 2>/dev/null || which fail2ban-client 2>/dev/null');
            $installed = $whichResult['success'] && ! empty($whichResult['output']);

            if (! $installed) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'jails' => [],
                    'message' => 'Fail2ban is not installed',
                ];
            }

            // Use sudo -n (non-interactive) to avoid hanging on password prompt
            $result = $this->executeCommand($server, 'sudo -n fail2ban-client status 2>/dev/null');

            // If sudo -n fails, fall back with password injection or systemctl check
            if (! $result['success'] || empty($result['output'])) {
                // Try systemctl to check running state without needing fail2ban-client sudo
                $systemctlResult = $this->executeCommand($server, 'systemctl is-active fail2ban 2>/dev/null');
                $isRunning = $systemctlResult['success'] || trim($systemctlResult['output']) === 'active';

                if (! $isRunning) {
                    return [
                        'installed' => true,
                        'enabled' => false,
                        'jails' => [],
                        'message' => 'Fail2ban is installed but not running',
                    ];
                }

                // Service is active — try with getSudoPrefix (handles password-based sudo)
                $sudoPrefix = $this->getSudoPrefix($server);
                $result = $this->executeCommand($server, "{$sudoPrefix}fail2ban-client status 2>/dev/null");
            }

            $combined = $result['output'].$result['error'];

            if (str_contains($combined, 'not running') || str_contains($combined, 'failed to access socket')) {
                return [
                    'installed' => true,
                    'enabled' => false,
                    'jails' => [],
                    'message' => 'Fail2ban is installed but not running',
                ];
            }

            $jails = $this->parseJailList($result['output']);

            return [
                'installed' => true,
                'enabled' => true,
                'jails' => $jails,
                'raw_output' => $result['output'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get Fail2ban status', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'installed' => false,
                'enabled' => false,
                'jails' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getJails(Server $server): array
    {
        $status = $this->getFail2banStatus($server);

        if (! $status['enabled']) {
            return $status;
        }

        $jailDetails = [];
        foreach ($status['jails'] as $jailName) {
            $jailDetails[$jailName] = $this->getJailStatus($server, $jailName);
        }

        return [
            'success' => true,
            'jails' => $jailDetails,
        ];
    }

    public function getJailStatus(Server $server, string $jailName): array
    {
        try {
            $jailName = escapeshellarg($jailName);
            $result = $this->executeCommand($server, "sudo -n fail2ban-client status {$jailName} 2>&1");

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?: $result['output'],
                ];
            }

            return [
                'success' => true,
                'data' => $this->parseJailStatus($result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getBannedIPs(Server $server, ?string $jailName = null): array
    {
        try {
            if ($jailName) {
                $jailStatus = $this->getJailStatus($server, $jailName);

                if (! $jailStatus['success']) {
                    return ['success' => false, 'banned_ips' => [], 'error' => $jailStatus['error']];
                }

                return [
                    'success' => true,
                    'banned_ips' => [
                        $jailName => $jailStatus['data']['banned_ips'] ?? [],
                    ],
                    'total_banned' => $jailStatus['data']['currently_banned'] ?? 0,
                ];
            }

            // Get banned IPs from all jails
            $status = $this->getFail2banStatus($server);
            if (! $status['enabled']) {
                return ['success' => false, 'banned_ips' => [], 'error' => 'Fail2ban is not running'];
            }

            $allBannedIPs = [];
            $totalBanned = 0;

            foreach ($status['jails'] as $jail) {
                $jailStatus = $this->getJailStatus($server, $jail);
                if ($jailStatus['success'] && isset($jailStatus['data']['banned_ips'])) {
                    $allBannedIPs[$jail] = $jailStatus['data']['banned_ips'];
                    $totalBanned += count($jailStatus['data']['banned_ips']);
                }
            }

            return [
                'success' => true,
                'banned_ips' => $allBannedIPs,
                'total_banned' => $totalBanned,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'banned_ips' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function unbanIP(Server $server, string $ip, string $jailName = 'sshd'): array
    {
        try {
            $this->validateIp($ip);

            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedIp = escapeshellarg($ip);
            $escapedJail = escapeshellarg($jailName);

            $command = "{$sudoPrefix}fail2ban-client set {$escapedJail} unbanip {$escapedIp}";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'unbanned')) {
                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_IP_UNBANNED,
                    "Unbanned IP {$ip} from jail {$jailName}",
                    $ip
                );

                return [
                    'success' => true,
                    'message' => "IP {$ip} has been unbanned from {$jailName}",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to unban IP: '.($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to unban IP: '.$e->getMessage(),
            ];
        }
    }

    public function banIP(Server $server, string $ip, string $jailName = 'sshd'): array
    {
        try {
            $this->validateIp($ip);

            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedIp = escapeshellarg($ip);
            $escapedJail = escapeshellarg($jailName);

            $command = "{$sudoPrefix}fail2ban-client set {$escapedJail} banip {$escapedIp}";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_IP_BANNED,
                    "Manually banned IP {$ip} in jail {$jailName}",
                    $ip
                );

                return [
                    'success' => true,
                    'message' => "IP {$ip} has been banned in {$jailName}",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to ban IP: '.($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to ban IP: '.$e->getMessage(),
            ];
        }
    }

    public function startFail2ban(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}systemctl start fail2ban";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                $server->update(['fail2ban_enabled' => true]);

                return [
                    'success' => true,
                    'message' => 'Fail2ban started successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to start Fail2ban: '.($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start Fail2ban: '.$e->getMessage(),
            ];
        }
    }

    public function stopFail2ban(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}systemctl stop fail2ban";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                $server->update(['fail2ban_enabled' => false]);

                return [
                    'success' => true,
                    'message' => 'Fail2ban stopped successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to stop Fail2ban: '.($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to stop Fail2ban: '.$e->getMessage(),
            ];
        }
    }

    public function installFail2ban(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}apt-get update && {$sudoPrefix}apt-get install -y fail2ban";
            $result = $this->executeCommand($server, $command, 120);

            if ($result['success']) {
                // Enable and start fail2ban
                $this->executeCommand($server, "{$sudoPrefix}systemctl enable fail2ban");
                $this->executeCommand($server, "{$sudoPrefix}systemctl start fail2ban");

                $server->update([
                    'fail2ban_installed' => true,
                    'fail2ban_enabled' => true,
                ]);

                return [
                    'success' => true,
                    'message' => 'Fail2ban installed and started successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to install Fail2ban: '.($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to install Fail2ban: '.$e->getMessage(),
            ];
        }
    }

    protected function parseJailList(string $output): array
    {
        $jails = [];

        if (preg_match('/Jail list:\s*(.+)/i', $output, $matches)) {
            $jailList = trim($matches[1]);
            $jails = array_map('trim', explode(',', $jailList));
            $jails = array_filter($jails);
        }

        return $jails;
    }

    protected function parseJailStatus(string $output): array
    {
        $data = [
            'currently_failed' => 0,
            'total_failed' => 0,
            'currently_banned' => 0,
            'total_banned' => 0,
            'banned_ips' => [],
        ];

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/Currently failed:\s*(\d+)/', $line, $matches)) {
                $data['currently_failed'] = (int) $matches[1];
            }
            if (preg_match('/Total failed:\s*(\d+)/', $line, $matches)) {
                $data['total_failed'] = (int) $matches[1];
            }
            if (preg_match('/Currently banned:\s*(\d+)/', $line, $matches)) {
                $data['currently_banned'] = (int) $matches[1];
            }
            if (preg_match('/Total banned:\s*(\d+)/', $line, $matches)) {
                $data['total_banned'] = (int) $matches[1];
            }
            if (preg_match('/Banned IP list:\s*(.*)/', $line, $matches)) {
                $ipList = trim($matches[1]);
                if (! empty($ipList)) {
                    $data['banned_ips'] = array_map('trim', preg_split('/\s+/', $ipList));
                }
            }
        }

        return $data;
    }

    protected function validateIp(string $ip): void
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }
    }

    protected function executeCommand(Server $server, string $command, int $timeout = 30): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address);

            if ($isLocalhost) {
                $result = Process::timeout($timeout)->run($command);

                return [
                    'success' => $result->successful(),
                    'output' => trim($result->output()),
                    'error' => $result->errorOutput(),
                ];
            }

            $sshCommand = $this->buildSSHCommand($server, $command);
            $result = Process::timeout($timeout)->run($sshCommand);

            return [
                'success' => $result->successful(),
                'output' => trim($result->output()),
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function isLocalhost(string $ip): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ip, $localIPs)) {
            return true;
        }

        $serverIP = gethostbyname(gethostname());

        return $ip === $serverIP;
    }

    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $port = $server->port ?? 22;

        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p '.$port,
        ];

        // Escape double quotes and backslashes for the remote command
        $escapedCommand = str_replace(['\\', '"', '$', '`'], ['\\\\', '\\"', '\\$', '\\`'], $remoteCommand);

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                $escapedCommand
            );
        }

        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "%s" 2>&1',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            $escapedCommand
        );
    }

    protected function getSudoPrefix(Server $server): string
    {
        $isRoot = strtolower($server->username) === 'root';

        if ($isRoot) {
            return '';
        }

        if ($server->ssh_password) {
            $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);

            return "echo '{$escapedPassword}' | sudo -S ";
        }

        return 'sudo ';
    }

    public function getWhitelistedIPs(Server $server, string $jailName = 'sshd'): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedJail = escapeshellarg($jailName);

            $command = "{$sudoPrefix}fail2ban-client get {$escapedJail} ignoreip";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                $output = trim($result['output']);

                // Parse the output - format is usually: "These IP addresses/networks are ignored:\n|- 127.0.0.1\n|- ::1"
                $ips = [];
                $lines = explode("\n", $output);

                foreach ($lines as $line) {
                    // Match patterns like "|- 127.0.0.1" or just IP addresses
                    if (preg_match('/(?:\|-\s*)?(\d+\.\d+\.\d+\.\d+(?:\/\d+)?|[0-9a-f:]+(?:\/\d+)?)/', $line, $matches)) {
                        $ips[] = $matches[1];
                    }
                }

                return [
                    'success' => true,
                    'whitelisted_ips' => array_values(array_unique($ips)),
                    'total' => count($ips),
                ];
            }

            return [
                'success' => false,
                'whitelisted_ips' => [],
                'error' => $result['error'] ?: $result['output'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'whitelisted_ips' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function addToWhitelist(Server $server, string $ip, string $jailName = 'sshd'): array
    {
        try {
            $this->validateIp($ip);

            // First, check if IP is already whitelisted
            $currentWhitelist = $this->getWhitelistedIPs($server, $jailName);
            if ($currentWhitelist['success'] && in_array($ip, $currentWhitelist['whitelisted_ips'])) {
                return [
                    'success' => false,
                    'message' => "IP {$ip} is already whitelisted",
                ];
            }

            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedIp = escapeshellarg($ip);
            $escapedJail = escapeshellarg($jailName);

            // Add IP to whitelist using fail2ban-client
            $command = "{$sudoPrefix}fail2ban-client set {$escapedJail} addignoreip {$escapedIp}";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'added')) {
                // Also unban the IP if it's currently banned
                $this->unbanIP($server, $ip, $jailName);

                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_IP_WHITELISTED,
                    "Added IP {$ip} to whitelist in jail {$jailName}",
                    $ip
                );

                return [
                    'success' => true,
                    'message' => "IP {$ip} has been added to whitelist and unbanned",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to add IP to whitelist: '.($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add IP to whitelist: '.$e->getMessage(),
            ];
        }
    }

    public function removeFromWhitelist(Server $server, string $ip, string $jailName = 'sshd'): array
    {
        try {
            $this->validateIp($ip);

            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedIp = escapeshellarg($ip);
            $escapedJail = escapeshellarg($jailName);

            $command = "{$sudoPrefix}fail2ban-client set {$escapedJail} delignoreip {$escapedIp}";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'removed') || str_contains($result['output'], 'deleted')) {
                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_IP_REMOVED_FROM_WHITELIST,
                    "Removed IP {$ip} from whitelist in jail {$jailName}",
                    $ip
                );

                return [
                    'success' => true,
                    'message' => "IP {$ip} has been removed from whitelist",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to remove IP from whitelist: '.($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove IP from whitelist: '.$e->getMessage(),
            ];
        }
    }

    public function unbanAllIPs(Server $server, string $jailName = 'sshd'): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $escapedJail = escapeshellarg($jailName);

            $command = "{$sudoPrefix}fail2ban-client unban --all";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_BULK_UNBAN,
                    'Unbanned all IPs from all jails'
                );

                return [
                    'success' => true,
                    'message' => 'All IPs have been unbanned',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to unban all IPs: '.($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to unban all IPs: '.$e->getMessage(),
            ];
        }
    }

    public function transferToWhitelist(Server $server, string $ip, string $jailName = 'sshd'): array
    {
        try {
            $this->validateIp($ip);

            // First add to whitelist
            $whitelistResult = $this->addToWhitelist($server, $ip, $jailName);

            if (! $whitelistResult['success']) {
                return $whitelistResult;
            }

            // Unban is already called in addToWhitelist, so we're done
            $this->logEvent(
                $server,
                SecurityEvent::TYPE_IP_TRANSFERRED,
                "Transferred IP {$ip} from banned list to whitelist in jail {$jailName}",
                $ip
            );

            return [
                'success' => true,
                'message' => "IP {$ip} has been transferred to whitelist and unbanned",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to transfer IP to whitelist: '.$e->getMessage(),
            ];
        }
    }

    protected function logEvent(Server $server, string $eventType, string $details, ?string $sourceIp = null): void
    {
        SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => $eventType,
            'details' => $details,
            'source_ip' => $sourceIp,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Get top attacking IPs from SSH auth logs
     *
     * @return array{success: bool, attackers: array<int, array{ip: string, attempts: int}>, total_attacks: int, error?: string}
     */
    public function getTopAttackingIPs(Server $server, int $limit = 20): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);

            // Parse auth.log for failed SSH attempts and count by IP
            $command = "{$sudoPrefix}grep -E 'Failed password|Invalid user' /var/log/auth.log 2>/dev/null | grep -oE '[0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+' | sort | uniq -c | sort -rn | head -{$limit}";
            $result = $this->executeCommand($server, $command);

            if (! $result['success'] && empty($result['output'])) {
                // Try alternate log location for RHEL/CentOS
                $command = "{$sudoPrefix}grep -E 'Failed password|Invalid user' /var/log/secure 2>/dev/null | grep -oE '[0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+' | sort | uniq -c | sort -rn | head -{$limit}";
                $result = $this->executeCommand($server, $command);
            }

            $attackers = [];
            $totalAttacks = 0;

            if (! empty($result['output'])) {
                $lines = array_filter(explode("\n", trim($result['output'])));
                foreach ($lines as $line) {
                    if (preg_match('/^\s*(\d+)\s+(\d+\.\d+\.\d+\.\d+)/', trim($line), $matches)) {
                        $attempts = (int) $matches[1];
                        $attackers[] = [
                            'ip' => $matches[2],
                            'attempts' => $attempts,
                        ];
                        $totalAttacks += $attempts;
                    }
                }
            }

            return [
                'success' => true,
                'attackers' => $attackers,
                'total_attacks' => $totalAttacks,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'attackers' => [],
                'total_attacks' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent failed SSH login attempts
     *
     * @return array{success: bool, attempts: array<int, array{timestamp: string, ip: string, user: string, type: string}>, error?: string}
     */
    public function getRecentFailedLogins(Server $server, int $limit = 50): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);

            $command = "{$sudoPrefix}grep -E 'Failed password|Invalid user' /var/log/auth.log 2>/dev/null | tail -{$limit}";
            $result = $this->executeCommand($server, $command);

            if (! $result['success'] && empty($result['output'])) {
                $command = "{$sudoPrefix}grep -E 'Failed password|Invalid user' /var/log/secure 2>/dev/null | tail -{$limit}";
                $result = $this->executeCommand($server, $command);
            }

            $attempts = [];

            if (! empty($result['output'])) {
                $lines = array_filter(explode("\n", trim($result['output'])));
                foreach ($lines as $line) {
                    $attempt = $this->parseAuthLogLine($line);
                    if ($attempt) {
                        $attempts[] = $attempt;
                    }
                }
            }

            // Reverse to show most recent first
            $attempts = array_reverse($attempts);

            return [
                'success' => true,
                'attempts' => $attempts,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'attempts' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get recent successful SSH logins
     *
     * @return array{success: bool, logins: array<int, array{timestamp: string, ip: string, user: string, method: string}>, error?: string}
     */
    public function getRecentSuccessfulLogins(Server $server, int $limit = 30): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);

            $command = "{$sudoPrefix}grep 'Accepted' /var/log/auth.log 2>/dev/null | tail -{$limit}";
            $result = $this->executeCommand($server, $command);

            if (! $result['success'] && empty($result['output'])) {
                $command = "{$sudoPrefix}grep 'Accepted' /var/log/secure 2>/dev/null | tail -{$limit}";
                $result = $this->executeCommand($server, $command);
            }

            $logins = [];

            if (! empty($result['output'])) {
                $lines = array_filter(explode("\n", trim($result['output'])));
                foreach ($lines as $line) {
                    $login = $this->parseSuccessfulLoginLine($line);
                    if ($login) {
                        $logins[] = $login;
                    }
                }
            }

            // Reverse to show most recent first
            $logins = array_reverse($logins);

            return [
                'success' => true,
                'logins' => $logins,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'logins' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk ban multiple IPs at once
     *
     * @param  array<int, string>  $ips
     * @return array{success: bool, banned: int, failed: int, message: string}
     */
    public function bulkBanIPs(Server $server, array $ips, string $jailName = 'sshd'): array
    {
        $banned = 0;
        $failed = 0;

        foreach ($ips as $ip) {
            try {
                $result = $this->banIP($server, $ip, $jailName);
                if ($result['success']) {
                    $banned++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $this->logEvent(
            $server,
            SecurityEvent::TYPE_BULK_BAN,
            "Bulk banned {$banned} IPs in jail {$jailName}"
        );

        return [
            'success' => $banned > 0,
            'banned' => $banned,
            'failed' => $failed,
            'message' => "Banned {$banned} IPs".($failed > 0 ? ", {$failed} failed" : ''),
        ];
    }

    /**
     * Parse an auth.log line for failed login attempts
     *
     * @return array{timestamp: string, ip: string, user: string, type: string}|null
     */
    protected function parseAuthLogLine(string $line): ?array
    {
        // Match timestamp at beginning (various formats)
        $timestamp = '';
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $line, $tsMatch)) {
            $timestamp = $tsMatch[1];
        } elseif (preg_match('/^([A-Z][a-z]{2}\s+\d+\s+\d{2}:\d{2}:\d{2})/', $line, $tsMatch)) {
            $timestamp = $tsMatch[1];
        }

        // Extract IP address
        $ip = '';
        if (preg_match('/from\s+(\d+\.\d+\.\d+\.\d+)/', $line, $ipMatch)) {
            $ip = $ipMatch[1];
        }

        // Extract username
        $user = 'unknown';
        $type = 'failed_password';

        if (preg_match('/Invalid user\s+(\S+)\s+from/', $line, $userMatch)) {
            $user = $userMatch[1];
            $type = 'invalid_user';
        } elseif (preg_match('/Failed password for invalid user\s+(\S+)\s+from/', $line, $userMatch)) {
            $user = $userMatch[1];
            $type = 'invalid_user';
        } elseif (preg_match('/Failed password for\s+(\S+)\s+from/', $line, $userMatch)) {
            $user = $userMatch[1];
            $type = 'failed_password';
        }

        if (empty($ip)) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user' => $user,
            'type' => $type,
        ];
    }

    /**
     * Parse successful login line from auth.log
     *
     * @return array{timestamp: string, ip: string, user: string, method: string}|null
     */
    protected function parseSuccessfulLoginLine(string $line): ?array
    {
        $timestamp = '';
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $line, $tsMatch)) {
            $timestamp = $tsMatch[1];
        } elseif (preg_match('/^([A-Z][a-z]{2}\s+\d+\s+\d{2}:\d{2}:\d{2})/', $line, $tsMatch)) {
            $timestamp = $tsMatch[1];
        }

        $ip = '';
        if (preg_match('/from\s+(\d+\.\d+\.\d+\.\d+)/', $line, $ipMatch)) {
            $ip = $ipMatch[1];
        }

        $user = 'unknown';
        if (preg_match('/Accepted\s+\S+\s+for\s+(\S+)\s+from/', $line, $userMatch)) {
            $user = $userMatch[1];
        }

        $method = 'password';
        if (str_contains($line, 'publickey')) {
            $method = 'publickey';
        }

        if (empty($ip)) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user' => $user,
            'method' => $method,
        ];
    }
}
