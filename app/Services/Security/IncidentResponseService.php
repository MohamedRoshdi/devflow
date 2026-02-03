<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\RemediationLog;
use App\Models\SecurityIncident;
use App\Models\Server;
use App\Traits\ExecutesServerCommands;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class IncidentResponseService
{
    use ExecutesServerCommands;

    /**
     * Protected services that must never be disabled
     *
     * @var array<int, string>
     */
    protected array $protectedServices = [
        'ssh', 'sshd', 'ssh.socket', 'docker', 'containerd', 'nginx', 'apache2',
        'mysql', 'mariadb', 'postgresql', 'redis-server', 'fail2ban', 'ufw',
        'supervisor', 'cron', 'rsyslog', 'systemd-journald', 'systemd-logind',
        'systemd-networkd', 'systemd-resolved', 'systemd-timesyncd', 'dbus',
        'networking', 'NetworkManager',
    ];

    /**
     * Protected binary paths that must never be removed
     *
     * @var array<int, string>
     */
    protected array $protectedPaths = [
        '/usr/bin/bash', '/usr/bin/sh', '/usr/sbin/sshd', '/usr/bin/ssh',
        '/usr/bin/sudo', '/usr/sbin/nginx', '/usr/bin/docker',
        '/usr/bin/python3', '/usr/bin/perl', '/usr/bin/php',
        '/bin/bash', '/bin/sh', '/sbin/init',
    ];
    /**
     * Kill a suspicious process
     *
     * @return array{success: bool, message: string}
     */
    public function killProcess(Server $server, int $pid): array
    {
        $result = $this->executeCommand($server, "kill -9 {$pid} 2>&1");

        $success = $result['success'] || str_contains($result['error'], 'No such process');

        Log::info('Incident response: Kill process', [
            'server_id' => $server->id,
            'pid' => $pid,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? "Process {$pid} terminated" : "Failed to kill process: {$result['error']}",
        ];
    }

    /**
     * Remove a malware directory
     *
     * @return array{success: bool, message: string}
     */
    public function removeDirectory(Server $server, string $path): array
    {
        // Safety check - never remove system directories
        $protectedPaths = ['/', '/home', '/etc', '/var', '/usr', '/bin', '/sbin', '/root', '/boot'];

        if (in_array($path, $protectedPaths, true)) {
            return [
                'success' => false,
                'message' => 'Cannot remove protected system directory',
            ];
        }

        // Verify directory exists and is not a symlink to protected location
        $checkResult = $this->executeCommand($server, "readlink -f '{$path}' 2>/dev/null");
        $realPath = trim($checkResult['output'] ?? '');

        if (in_array($realPath, $protectedPaths, true)) {
            return [
                'success' => false,
                'message' => 'Directory symlinks to protected location',
            ];
        }

        $result = $this->executeCommand($server, "rm -rf '{$path}' 2>&1");

        Log::info('Incident response: Remove directory', [
            'server_id' => $server->id,
            'path' => $path,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Directory {$path} removed" : "Failed to remove: {$result['error']}",
        ];
    }

    /**
     * Remove a backdoor user account
     *
     * @return array{success: bool, message: string}
     */
    public function removeUser(Server $server, string $username): array
    {
        // Safety check - never remove essential users
        $protectedUsers = ['root', 'www-data', 'nginx', 'mysql', 'postgres', 'redis', 'nobody'];

        if (in_array($username, $protectedUsers, true)) {
            return [
                'success' => false,
                'message' => 'Cannot remove protected system user',
            ];
        }

        // First, try userdel
        $result = $this->executeCommand($server, "userdel -r {$username} 2>&1");

        if (! $result['success']) {
            // Fallback: manually remove from passwd/shadow
            $this->executeCommand($server, "sed -i '/^{$username}:/d' /etc/passwd");
            $this->executeCommand($server, "sed -i '/^{$username}:/d' /etc/shadow");
        }

        // Verify removal
        $verifyResult = $this->executeCommand($server, "grep '^{$username}:' /etc/passwd");
        $success = empty($verifyResult['output']);

        Log::info('Incident response: Remove user', [
            'server_id' => $server->id,
            'username' => $username,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? "User {$username} removed" : "Failed to remove user",
        ];
    }

    /**
     * Block outbound SSH connections (port 22)
     *
     * @return array{success: bool, message: string}
     */
    public function blockOutboundSSH(Server $server): array
    {
        $commands = [
            'iptables -A OUTPUT -p tcp --dport 22 -m owner --uid-owner root -j ACCEPT',
            'iptables -A OUTPUT -p tcp --dport 22 -j DROP',
        ];

        $success = true;

        foreach ($commands as $cmd) {
            $result = $this->executeCommand($server, $cmd);
            if (! $result['success']) {
                $success = false;
            }
        }

        // Save iptables rules
        $this->executeCommand($server, 'netfilter-persistent save 2>/dev/null || iptables-save > /etc/iptables.rules');

        Log::info('Incident response: Block outbound SSH', [
            'server_id' => $server->id,
            'success' => $success,
        ]);

        return [
            'success' => $success,
            'message' => $success ? 'Outbound SSH blocked (except for root)' : 'Failed to block outbound SSH',
        ];
    }

    /**
     * Harden SSH configuration
     *
     * @return array{success: bool, message: string}
     */
    public function hardenSSH(Server $server): array
    {
        $configContent = <<<'EOF'
# SSH Hardening - Applied by DevFlow Pro Incident Response
PasswordAuthentication no
PermitEmptyPasswords no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
UsePAM yes
MaxAuthTries 3
LoginGraceTime 20
PermitRootLogin prohibit-password
EOF;

        // Create hardening config
        $result = $this->executeCommand(
            $server,
            "echo '{$configContent}' > /etc/ssh/sshd_config.d/devflow-hardening.conf"
        );

        if (! $result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to create SSH hardening config',
            ];
        }

        // Restart SSH service
        $restartResult = $this->executeCommand($server, 'systemctl restart ssh 2>/dev/null || systemctl restart sshd');

        Log::info('Incident response: Harden SSH', [
            'server_id' => $server->id,
            'success' => $restartResult['success'],
        ]);

        return [
            'success' => $restartResult['success'],
            'message' => $restartResult['success'] ? 'SSH hardened and restarted' : 'SSH hardening applied but restart may have failed',
        ];
    }

    /**
     * Install and configure fail2ban
     *
     * @return array{success: bool, message: string}
     */
    public function installFail2ban(Server $server): array
    {
        // Install fail2ban
        $installResult = $this->executeCommand(
            $server,
            'apt-get update -qq && apt-get install -y -qq fail2ban'
        );

        if (! $installResult['success']) {
            return [
                'success' => false,
                'message' => 'Failed to install fail2ban',
            ];
        }

        // Configure jail
        $jailConfig = <<<'EOF'
[DEFAULT]
bantime = 24h
findtime = 10m
maxretry = 3
ignoreip = 127.0.0.1/8 ::1

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400
EOF;

        $this->executeCommand($server, "echo '{$jailConfig}' > /etc/fail2ban/jail.local");

        // Start fail2ban
        $this->executeCommand($server, 'systemctl enable fail2ban && systemctl restart fail2ban');

        // Update server record
        $server->update([
            'fail2ban_installed' => true,
            'fail2ban_enabled' => true,
        ]);

        Log::info('Incident response: Install fail2ban', [
            'server_id' => $server->id,
            'success' => true,
        ]);

        return [
            'success' => true,
            'message' => 'Fail2ban installed and configured',
        ];
    }

    /**
     * Enable lockdown mode (block all non-essential traffic)
     *
     * @return array{success: bool, message: string}
     */
    public function enableLockdownMode(Server $server): array
    {
        $commands = [
            // Allow established connections
            'iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',
            'iptables -A OUTPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',

            // Allow loopback
            'iptables -A INPUT -i lo -j ACCEPT',
            'iptables -A OUTPUT -o lo -j ACCEPT',

            // Allow SSH in (on configured port)
            "iptables -A INPUT -p tcp --dport {$server->port} -j ACCEPT",

            // Allow HTTP/HTTPS
            'iptables -A INPUT -p tcp --dport 80 -j ACCEPT',
            'iptables -A INPUT -p tcp --dport 443 -j ACCEPT',

            // Block all outbound except DNS and HTTPS (for package updates)
            'iptables -A OUTPUT -p udp --dport 53 -j ACCEPT',
            'iptables -A OUTPUT -p tcp --dport 443 -j ACCEPT',
            'iptables -A OUTPUT -p tcp --dport 80 -j ACCEPT',

            // Drop everything else
            'iptables -A INPUT -j DROP',
            'iptables -A OUTPUT -j DROP',
        ];

        $success = true;

        foreach ($commands as $cmd) {
            $result = $this->executeCommand($server, $cmd);
            if (! $result['success']) {
                Log::warning('Lockdown command failed', ['command' => $cmd, 'error' => $result['error']]);
            }
        }

        // Save rules
        $this->executeCommand($server, 'netfilter-persistent save 2>/dev/null || iptables-save > /etc/iptables.rules');

        // Update server record
        $server->update(['lockdown_mode' => true]);

        Log::info('Incident response: Enable lockdown mode', [
            'server_id' => $server->id,
        ]);

        return [
            'success' => true,
            'message' => 'Lockdown mode enabled - only SSH, HTTP, HTTPS, and DNS allowed',
        ];
    }

    /**
     * Disable lockdown mode
     *
     * @return array{success: bool, message: string}
     */
    public function disableLockdownMode(Server $server): array
    {
        // Flush all rules
        $this->executeCommand($server, 'iptables -F');
        $this->executeCommand($server, 'iptables -X');

        // Reset to default ACCEPT policy
        $this->executeCommand($server, 'iptables -P INPUT ACCEPT');
        $this->executeCommand($server, 'iptables -P FORWARD ACCEPT');
        $this->executeCommand($server, 'iptables -P OUTPUT ACCEPT');

        // Save rules
        $this->executeCommand($server, 'netfilter-persistent save 2>/dev/null || iptables-save > /etc/iptables.rules');

        // Update server record
        $server->update(['lockdown_mode' => false]);

        Log::info('Incident response: Disable lockdown mode', [
            'server_id' => $server->id,
        ]);

        return [
            'success' => true,
            'message' => 'Lockdown mode disabled - all traffic allowed',
        ];
    }

    /**
     * Apply auto-remediation for an incident
     *
     * @return array{success: bool, actions: array<int, array{action: string, success: bool, message: string}>}
     */
    public function autoRemediate(SecurityIncident $incident): array
    {
        $server = $incident->server;
        $actions = [];

        // Check if auto-remediation is enabled for this server
        if (! $server->auto_remediation_enabled) {
            return [
                'success' => false,
                'actions' => [['action' => 'auto_remediation', 'success' => false, 'message' => 'Auto-remediation not enabled for this server']],
            ];
        }

        switch ($incident->incident_type) {
            case SecurityIncident::TYPE_SUSPICIOUS_PROCESS:
                // Kill processes from deleted executables
                if (isset($incident->affected_items['pid'])) {
                    $result = $this->killProcess($server, (int) $incident->affected_items['pid']);
                    $actions[] = ['action' => 'kill_process', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

            case SecurityIncident::TYPE_MALWARE:
                // Remove malware directories
                if (isset($incident->affected_items['path'])) {
                    $result = $this->removeDirectory($server, $incident->affected_items['path']);
                    $actions[] = ['action' => 'remove_directory', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

            case SecurityIncident::TYPE_OUTBOUND_ATTACK:
                // Block outbound SSH
                $result = $this->blockOutboundSSH($server);
                $actions[] = ['action' => 'block_outbound_ssh', 'success' => $result['success'], 'message' => $result['message']];

                break;

            case SecurityIncident::TYPE_BRUTE_FORCE:
                // Install fail2ban if not already installed
                if (! $server->fail2ban_installed) {
                    $result = $this->installFail2ban($server);
                    $actions[] = ['action' => 'install_fail2ban', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

                // Note: Backdoor user removal requires manual confirmation
            case SecurityIncident::TYPE_BACKDOOR_USER:
                $actions[] = [
                    'action' => 'backdoor_user_detected',
                    'success' => false,
                    'message' => 'Backdoor user detected - manual removal required for safety',
                ];

                break;

            case SecurityIncident::TYPE_CRYPTO_MINER:
                // Kill miner processes
                if (isset($incident->affected_items['pid'])) {
                    $result = $this->killProcess($server, (int) $incident->affected_items['pid']);
                    $actions[] = ['action' => 'kill_miner_process', 'success' => $result['success'], 'message' => $result['message']];
                }
                // Remove miner binary if found
                if (isset($incident->affected_items['exe_path'])) {
                    $result = $this->removeMaliciousBinary($server, $incident->affected_items['exe_path']);
                    $actions[] = ['action' => 'remove_miner_binary', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

            case SecurityIncident::TYPE_IRC_BOTNET:
                // Block IRC port
                $result = $this->blockOutboundPort($server, 6667);
                $actions[] = ['action' => 'block_irc_port', 'success' => $result['success'], 'message' => $result['message']];

                break;

            case SecurityIncident::TYPE_MALICIOUS_SERVICE:
                // Disable the malicious service
                if (isset($incident->affected_items['service_name'])) {
                    $result = $this->disableSystemdService($server, $incident->affected_items['service_name']);
                    $actions[] = ['action' => 'disable_malicious_service', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

            case SecurityIncident::TYPE_MINING_POOL_CONNECTION:
                // Block the mining pool port
                if (isset($incident->affected_items['port'])) {
                    $result = $this->blockOutboundPort($server, (int) $incident->affected_items['port']);
                    $actions[] = ['action' => 'block_mining_port', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;

            case SecurityIncident::TYPE_PROXY_TUNNEL:
                // Disable the proxy service
                if (isset($incident->affected_items['service_name'])) {
                    $result = $this->disableSystemdService($server, $incident->affected_items['service_name']);
                    $actions[] = ['action' => 'disable_proxy_service', 'success' => $result['success'], 'message' => $result['message']];
                }

                break;
        }

        // Log remediation actions on the incident
        foreach ($actions as $action) {
            $incident->addRemediationAction($action['action'], $action['success'], $action['message']);
        }

        // If all actions succeeded, mark as mitigating
        $allSuccess = count(array_filter($actions, fn ($a) => $a['success'])) === count($actions);

        if ($allSuccess && ! empty($actions)) {
            $incident->update(['status' => SecurityIncident::STATUS_MITIGATING]);
        }

        return [
            'success' => $allSuccess,
            'actions' => $actions,
        ];
    }

    /**
     * Disable a systemd service (stop + disable)
     *
     * @return array{success: bool, message: string}
     */
    public function disableSystemdService(Server $server, string $serviceName): array
    {
        // Safety check - never disable protected services
        foreach ($this->protectedServices as $protected) {
            if (str_contains($serviceName, $protected)) {
                return [
                    'success' => false,
                    'message' => "Cannot disable protected service: {$serviceName}",
                ];
            }
        }

        $this->executeCommand($server, "systemctl stop {$serviceName} 2>&1");
        $result = $this->executeCommand($server, "systemctl disable {$serviceName} 2>&1");

        $this->logRemediation($server, 'disable_service', $serviceName,
            "systemctl stop {$serviceName} && systemctl disable {$serviceName}",
            "systemctl enable {$serviceName} && systemctl start {$serviceName}",
            $result['success']
        );

        Log::info('Incident response: Disable systemd service', [
            'server_id' => $server->id,
            'service' => $serviceName,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Service {$serviceName} stopped and disabled" : "Failed to disable service: {$result['error']}",
        ];
    }

    /**
     * Remove a systemd service completely (stop + disable + remove file + daemon-reload)
     *
     * @return array{success: bool, message: string}
     */
    public function removeSystemdService(Server $server, string $serviceName): array
    {
        // Safety check
        foreach ($this->protectedServices as $protected) {
            if (str_contains($serviceName, $protected)) {
                return [
                    'success' => false,
                    'message' => "Cannot remove protected service: {$serviceName}",
                ];
            }
        }

        // Find the service file path
        $pathResult = $this->executeCommand($server, "systemctl show {$serviceName} --property=FragmentPath --no-pager 2>/dev/null");
        $servicePath = str_replace('FragmentPath=', '', trim($pathResult['output'] ?? ''));

        // Stop and disable
        $this->executeCommand($server, "systemctl stop {$serviceName} 2>&1");
        $this->executeCommand($server, "systemctl disable {$serviceName} 2>&1");

        // Remove service file
        if (! empty($servicePath) && file_exists($servicePath)) {
            $this->executeCommand($server, "rm -f '{$servicePath}' 2>&1");
        }

        // Reload daemon
        $result = $this->executeCommand($server, 'systemctl daemon-reload 2>&1');

        $this->logRemediation($server, 'remove_service', $serviceName,
            "systemctl stop {$serviceName} && systemctl disable {$serviceName} && rm -f '{$servicePath}' && systemctl daemon-reload",
            null,
            $result['success']
        );

        Log::info('Incident response: Remove systemd service', [
            'server_id' => $server->id,
            'service' => $serviceName,
            'service_path' => $servicePath,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Service {$serviceName} completely removed" : "Failed to remove service: {$result['error']}",
        ];
    }

    /**
     * Remove a malicious crontab entry for a specific user
     *
     * @return array{success: bool, message: string}
     */
    public function removeMaliciousCrontabEntry(Server $server, string $user, string $pattern): array
    {
        // Get current crontab
        $currentResult = $this->executeCommand($server, "crontab -u {$user} -l 2>/dev/null");
        if (! $currentResult['success'] || empty($currentResult['output'])) {
            return ['success' => true, 'message' => 'No crontab to clean'];
        }

        // Filter out lines matching the malicious pattern
        $lines = explode("\n", $currentResult['output']);
        $cleanLines = array_filter($lines, function (string $line) use ($pattern): bool {
            return ! preg_match($pattern, $line);
        });

        $cleanContent = implode("\n", $cleanLines);

        // Write the cleaned crontab
        $escapedContent = str_replace(["'", '\\'], ["'\\''", '\\\\'], $cleanContent);
        $result = $this->executeCommand($server, "echo '{$escapedContent}' | crontab -u {$user} - 2>&1");

        $this->logRemediation($server, 'remove_crontab', "user:{$user} pattern:{$pattern}",
            "Filtered crontab for user {$user}",
            "Restore original crontab for {$user}",
            $result['success']
        );

        Log::info('Incident response: Remove malicious crontab entry', [
            'server_id' => $server->id,
            'user' => $user,
            'pattern' => $pattern,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Cleaned crontab for user {$user}" : "Failed to clean crontab: {$result['error']}",
        ];
    }

    /**
     * Remove a malicious binary with safety checks
     *
     * @return array{success: bool, message: string}
     */
    public function removeMaliciousBinary(Server $server, string $binaryPath): array
    {
        // Safety check - never remove protected binaries
        $realPath = $binaryPath;
        $realResult = $this->executeCommand($server, "readlink -f '{$binaryPath}' 2>/dev/null");
        if ($realResult['success'] && ! empty($realResult['output'])) {
            $realPath = trim($realResult['output']);
        }

        if (in_array($realPath, $this->protectedPaths, true)) {
            return [
                'success' => false,
                'message' => "Cannot remove protected binary: {$binaryPath}",
            ];
        }

        // Don't remove binaries in standard system directories
        $systemDirs = ['/usr/bin/', '/usr/sbin/', '/bin/', '/sbin/', '/usr/lib/'];
        foreach ($systemDirs as $dir) {
            if (str_starts_with($realPath, $dir)) {
                return [
                    'success' => false,
                    'message' => "Cannot remove binary in system directory: {$realPath}",
                ];
            }
        }

        // Get file info before removal
        $fileInfo = $this->executeCommand($server, "ls -la '{$binaryPath}' 2>/dev/null");

        $result = $this->executeCommand($server, "rm -f '{$binaryPath}' 2>&1");

        $this->logRemediation($server, 'remove_binary', $binaryPath,
            "rm -f '{$binaryPath}'",
            null,
            $result['success'],
            $fileInfo['output'] ?? null
        );

        Log::info('Incident response: Remove malicious binary', [
            'server_id' => $server->id,
            'binary_path' => $binaryPath,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Binary {$binaryPath} removed" : "Failed to remove binary: {$result['error']}",
        ];
    }

    /**
     * Block mining pool IPs via UFW
     *
     * @param array<int, string> $ips
     * @return array{success: bool, message: string}
     */
    public function blockMiningPool(Server $server, array $ips): array
    {
        $blocked = 0;

        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (empty($ip) || ! filter_var($ip, FILTER_VALIDATE_IP)) {
                continue;
            }

            $result = $this->executeCommand($server, "ufw deny out to {$ip} 2>&1");
            if ($result['success']) {
                $blocked++;
            }
        }

        $this->logRemediation($server, 'block_ip', 'mining_pool_ips:'.implode(',', $ips),
            'ufw deny out to [IPs]',
            'ufw delete deny out to [IPs]',
            $blocked > 0
        );

        Log::info('Incident response: Block mining pool IPs', [
            'server_id' => $server->id,
            'ips_blocked' => $blocked,
            'total_ips' => count($ips),
        ]);

        return [
            'success' => $blocked > 0,
            'message' => "Blocked {$blocked} of ".count($ips).' mining pool IPs',
        ];
    }

    /**
     * Block outbound port (e.g., IRC 6667, stratum ports)
     *
     * @return array{success: bool, message: string}
     */
    public function blockOutboundPort(Server $server, int $port): array
    {
        // Safety check - never block essential ports
        $essentialPorts = [22, 80, 443, 53, 25, 587, 993, 995];
        if (in_array($port, $essentialPorts, true)) {
            return [
                'success' => false,
                'message' => "Cannot block essential port: {$port}",
            ];
        }

        $result = $this->executeCommand($server, "ufw deny out {$port} 2>&1");

        $this->logRemediation($server, 'block_port', "port:{$port}",
            "ufw deny out {$port}",
            "ufw delete deny out {$port}",
            $result['success']
        );

        Log::info('Incident response: Block outbound port', [
            'server_id' => $server->id,
            'port' => $port,
            'success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? "Outbound port {$port} blocked" : "Failed to block port: {$result['error']}",
        ];
    }

    /**
     * Log a remediation action for audit trail
     */
    protected function logRemediation(
        Server $server,
        string $action,
        string $target,
        ?string $command = null,
        ?string $rollbackCommand = null,
        bool $success = false,
        ?string $output = null,
        ?int $incidentId = null,
        bool $autoTriggered = false,
    ): RemediationLog {
        return RemediationLog::create([
            'server_id' => $server->id,
            'security_incident_id' => $incidentId,
            'action' => $action,
            'target' => $target,
            'command_executed' => $command,
            'rollback_command' => $rollbackCommand,
            'success' => $success,
            'output' => $output,
            'auto_triggered' => $autoTriggered,
        ]);
    }

    /**
     * Generate an incident report for the hosting provider
     */
    public function generateIncidentReport(SecurityIncident $incident): string
    {
        $server = $incident->server;

        $report = "# Security Incident Report\n\n";
        $report .= "**Server:** {$server->name} ({$server->ip_address})\n";
        $report .= "**Incident Type:** ".SecurityIncident::getIncidentTypes()[$incident->incident_type]."\n";
        $report .= "**Severity:** {$incident->severity}\n";
        $report .= "**Status:** {$incident->status}\n";
        $report .= "**Detected At:** {$incident->detected_at->format('Y-m-d H:i:s')}\n";

        if ($incident->resolved_at) {
            $report .= "**Resolved At:** {$incident->resolved_at->format('Y-m-d H:i:s')}\n";
        }

        $report .= "\n## Description\n\n{$incident->description}\n";

        $report .= "\n## Findings\n\n";

        foreach ($incident->findings ?? [] as $finding) {
            $report .= "- **{$finding['title']}**\n";
            $report .= "  {$finding['description']}\n\n";
        }

        $report .= "\n## Affected Items\n\n```json\n".json_encode($incident->affected_items, JSON_PRETTY_PRINT)."\n```\n";

        if (! empty($incident->remediation_actions)) {
            $report .= "\n## Remediation Actions Taken\n\n";

            foreach ($incident->remediation_actions as $action) {
                $status = $action['success'] ? '✅' : '❌';
                $report .= "- {$status} **{$action['action']}**: {$action['message']}\n";
                $report .= "  Performed at: {$action['performed_at']}\n\n";
            }
        }

        return $report;
    }

    /**
     * Execute a command on the server
     *
     * @return array{success: bool, output: string, error: string}
     */
    protected function executeCommand(Server $server, string $command): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address) || $server->is_current_server;

            if ($isLocalhost) {
                $result = Process::timeout(60)->run($command);

                return [
                    'success' => $result->successful(),
                    'output' => trim($result->output()),
                    'error' => $result->errorOutput(),
                ];
            }

            $sshCommand = $this->buildSSHCommand($server, $command);
            $result = Process::timeout(60)->run($sshCommand);

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

    /**
     * Build SSH command for remote execution
     */
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

    /**
     * Check if the IP address is localhost
     */
    protected function isLocalhost(string $ipAddress): bool
    {
        $localhostAddresses = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($ipAddress, $localhostAddresses, true)) {
            return true;
        }

        $serverIps = gethostbynamel(gethostname()) ?: [];

        return in_array($ipAddress, $serverIps, true);
    }
}
