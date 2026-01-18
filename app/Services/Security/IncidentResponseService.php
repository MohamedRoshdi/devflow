<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityIncident;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class IncidentResponseService
{
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
