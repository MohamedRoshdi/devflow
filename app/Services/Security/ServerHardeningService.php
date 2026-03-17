<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\RemediationLog;
use App\Models\SecurityEvent;
use App\Models\Server;
use App\Traits\ExecutesServerCommands;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Provides one-click server hardening with comprehensive security configuration.
 *
 * Orchestrates SSH hardening, firewall setup, fail2ban configuration,
 * kernel sysctl tuning, and disabling unnecessary services.
 */
class ServerHardeningService
{
    use ExecutesServerCommands;

    /**
     * Services that are safe to disable on a typical production server.
     *
     * @var array<int, string>
     */
    private const UNNECESSARY_SERVICES = [
        'avahi-daemon',
        'cups',
        'cups-browsed',
        'bluetooth',
        'ModemManager',
        'whoopsie',
        'apport',
        'speech-dispatcher',
        'colord',
        'packagekit',
    ];

    /**
     * Sysctl hardening parameters for kernel security.
     *
     * @var array<string, string>
     */
    private const SYSCTL_HARDENING_PARAMS = [
        'net.ipv4.ip_forward' => '0',
        'net.ipv4.tcp_syncookies' => '1',
        'net.ipv4.conf.all.accept_redirects' => '0',
        'net.ipv4.conf.default.accept_redirects' => '0',
        'net.ipv6.conf.all.accept_redirects' => '0',
        'net.ipv6.conf.default.accept_redirects' => '0',
        'net.ipv4.conf.all.send_redirects' => '0',
        'net.ipv4.conf.default.send_redirects' => '0',
        'net.ipv4.conf.all.accept_source_route' => '0',
        'net.ipv4.conf.default.accept_source_route' => '0',
        'net.ipv4.conf.all.log_martians' => '1',
        'net.ipv4.conf.default.log_martians' => '1',
        'net.ipv4.icmp_echo_ignore_broadcasts' => '1',
        'net.ipv4.icmp_ignore_bogus_error_responses' => '1',
        'net.ipv4.conf.all.rp_filter' => '1',
        'net.ipv4.conf.default.rp_filter' => '1',
        'net.ipv4.tcp_max_syn_backlog' => '2048',
        'net.ipv4.tcp_synack_retries' => '2',
        'net.ipv4.tcp_syn_retries' => '5',
        'kernel.randomize_va_space' => '2',
    ];

    /**
     * Orchestrate all hardening steps based on the provided options.
     *
     * Available options:
     *   - ssh (bool): Harden SSH configuration (default: true)
     *   - ssh_port (int|null): Change SSH port if specified
     *   - firewall (bool): Setup UFW firewall (default: true)
     *   - fail2ban (bool): Configure fail2ban (default: true)
     *   - fail2ban_maxretry (int): Fail2ban max retry (default: 3)
     *   - fail2ban_bantime (int): Fail2ban ban duration in seconds (default: 86400)
     *   - fail2ban_findtime (int): Fail2ban find time window in seconds (default: 600)
     *   - sysctl (bool): Apply kernel hardening (default: true)
     *   - disable_services (bool): Disable unnecessary services (default: true)
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function hardenServer(Server $server, array $options = []): array
    {
        $results = [];
        $overallSuccess = true;

        $defaults = [
            'ssh' => true,
            'ssh_port' => null,
            'firewall' => true,
            'fail2ban' => true,
            'fail2ban_maxretry' => 3,
            'fail2ban_bantime' => 86400,
            'fail2ban_findtime' => 600,
            'sysctl' => true,
            'disable_services' => true,
        ];

        /** @var array<string, mixed> $config */
        $config = array_merge($defaults, $options);

        // Step 1: SSH hardening
        if ($config['ssh'] === true) {
            $results['ssh'] = $this->hardenSSH($server);
            if (! ($results['ssh']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Step 2: Change SSH port if specified
        if ($config['ssh_port'] !== null) {
            $results['ssh_port'] = $this->changeSSHPort($server, (int) $config['ssh_port']);
            if (! ($results['ssh_port']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Step 3: Firewall setup
        if ($config['firewall'] === true) {
            $results['firewall'] = $this->setupFirewall($server);
            if (! ($results['firewall']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Step 4: Fail2ban configuration
        if ($config['fail2ban'] === true) {
            $results['fail2ban'] = $this->configureFail2ban($server, [
                'maxretry' => (int) $config['fail2ban_maxretry'],
                'bantime' => (int) $config['fail2ban_bantime'],
                'findtime' => (int) $config['fail2ban_findtime'],
            ]);
            if (! ($results['fail2ban']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Step 5: Kernel hardening
        if ($config['sysctl'] === true) {
            $results['sysctl'] = $this->hardenSysctl($server);
            if (! ($results['sysctl']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Step 6: Disable unnecessary services
        if ($config['disable_services'] === true) {
            $results['disabled_services'] = $this->disableUnusedServices($server);
            if (! ($results['disabled_services']['success'] ?? false)) {
                $overallSuccess = false;
            }
        }

        // Determine hardening level
        $hardeningLevel = $this->calculateHardeningLevel($results);

        $server->update([
            'last_hardening_at' => now(),
            'hardening_level' => $hardeningLevel,
        ]);

        $this->logSecurityEvent(
            $server,
            SecurityEvent::TYPE_SERVER_HARDENED,
            "Server hardening completed (level: {$hardeningLevel}). Steps: ".implode(', ', array_keys($results)),
            [
                'hardening_level' => $hardeningLevel,
                'steps_executed' => array_keys($results),
                'overall_success' => $overallSuccess,
            ]
        );

        return [
            'success' => $overallSuccess,
            'hardening_level' => $hardeningLevel,
            'results' => $results,
            'completed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Change the SSH port on the server.
     *
     * Updates both /etc/ssh/sshd_config and the systemd ssh.socket override,
     * adds the new port to UFW before restarting SSH to avoid lockout.
     *
     * @return array{success: bool, message: string}
     */
    public function changeSSHPort(Server $server, int $newPort): array
    {
        try {
            $this->validatePort($newPort);

            $sudoPrefix = $this->getSudoPrefix($server);
            $currentPort = $server->port ?? 22;

            // Step 1: Update /etc/ssh/sshd_config Port directive
            $checkCommand = "grep -E '^#?Port' /etc/ssh/sshd_config";
            $checkResult = $this->executeCommand($server, $checkCommand);

            if ($checkResult['success'] && ! empty(trim($checkResult['output']))) {
                $sedCommand = "{$sudoPrefix}sed -i 's/^#*Port.*/Port {$newPort}/' /etc/ssh/sshd_config";
            } else {
                $sedCommand = "echo 'Port {$newPort}' | {$sudoPrefix}tee -a /etc/ssh/sshd_config > /dev/null";
            }

            $configResult = $this->executeCommand($server, $sedCommand);
            if (! $configResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to update sshd_config: '.($configResult['error'] ?: $configResult['output']),
                ];
            }

            // Step 2: Create systemd socket override for ssh.socket
            $overrideDir = '/etc/systemd/system/ssh.socket.d';
            $this->executeCommand($server, "{$sudoPrefix}mkdir -p {$overrideDir}");

            $overrideContent = "[Socket]\\nListenStream=\\nListenStream={$newPort}";
            $overrideCommand = "{$sudoPrefix}bash -c 'printf \"{$overrideContent}\\n\" > {$overrideDir}/override.conf'";
            $overrideResult = $this->executeCommand($server, $overrideCommand);

            if (! $overrideResult['success']) {
                Log::warning('Failed to create ssh.socket override, continuing with sshd_config change', [
                    'server_id' => $server->id,
                    'error' => $overrideResult['error'],
                ]);
            }

            // Step 3: Add the new port to UFW BEFORE restarting SSH (critical to avoid lockout)
            $ufwResult = $this->executeCommand($server, "{$sudoPrefix}ufw allow {$newPort}/tcp", 15);
            if (! $ufwResult['success']) {
                Log::warning('Failed to add new SSH port to UFW, UFW may not be installed', [
                    'server_id' => $server->id,
                    'port' => $newPort,
                    'error' => $ufwResult['error'],
                ]);
            }

            // Step 4: Validate SSH config before restarting
            $validateResult = $this->executeCommand($server, "{$sudoPrefix}sshd -t");
            if (! $validateResult['success'] && ! empty(trim($validateResult['error']))) {
                // Rollback the sshd_config change
                $this->executeCommand($server, "{$sudoPrefix}sed -i 's/^Port.*/Port {$currentPort}/' /etc/ssh/sshd_config");

                return [
                    'success' => false,
                    'message' => 'SSH config validation failed, rolled back: '.$validateResult['error'],
                ];
            }

            // Step 5: Reload systemd and restart SSH
            $restartCommand = "{$sudoPrefix}systemctl daemon-reload && {$sudoPrefix}systemctl restart ssh.socket ssh.service";
            $restartResult = $this->executeCommand($server, $restartCommand, 30);

            // Fallback: try restarting sshd if ssh.socket/ssh.service names differ
            if (! $restartResult['success']) {
                $fallbackCommand = "{$sudoPrefix}systemctl daemon-reload && {$sudoPrefix}systemctl restart sshd";
                $restartResult = $this->executeCommand($server, $fallbackCommand, 30);
            }

            // Step 6: Update the server model port
            $server->update(['port' => $newPort]);

            $this->logRemediation($server, 'harden_ssh', 'ssh_port_change', [
                'command' => "Port changed from {$currentPort} to {$newPort}",
                'rollback' => "sed -i 's/^Port.*/Port {$currentPort}/' /etc/ssh/sshd_config && systemctl restart ssh.socket ssh.service",
                'success' => true,
            ]);

            return [
                'success' => true,
                'message' => "SSH port changed from {$currentPort} to {$newPort}. Server model updated.",
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to change SSH port', [
                'server_id' => $server->id,
                'new_port' => $newPort,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to change SSH port: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Enable UFW with sensible defaults.
     *
     * Sets default deny incoming, allow outgoing, and opens SSH, HTTP, HTTPS.
     *
     * @return array<string, mixed>
     */
    public function setupFirewall(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $sshPort = $server->port ?? 22;
            $steps = [];

            // Install UFW if not present
            $checkResult = $this->executeCommand($server, 'which ufw 2>/dev/null');
            if (! $checkResult['success'] || empty(trim($checkResult['output']))) {
                $installResult = $this->executeCommand(
                    $server,
                    "{$sudoPrefix}apt-get update -qq && {$sudoPrefix}apt-get install -y -qq ufw",
                    120
                );
                $steps['install'] = $installResult['success'] ? 'installed' : 'failed: '.$installResult['error'];

                if (! $installResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to install UFW: '.$installResult['error'],
                        'steps' => $steps,
                    ];
                }
            } else {
                $steps['install'] = 'already_installed';
            }

            // Set default policies
            $denyResult = $this->executeCommand($server, "{$sudoPrefix}ufw default deny incoming");
            $steps['default_deny_incoming'] = $denyResult['success'];

            $allowOutResult = $this->executeCommand($server, "{$sudoPrefix}ufw default allow outgoing");
            $steps['default_allow_outgoing'] = $allowOutResult['success'];

            // Allow SSH port (critical - must be before enabling)
            $sshResult = $this->executeCommand($server, "{$sudoPrefix}ufw allow {$sshPort}/tcp");
            $steps['allow_ssh'] = $sshResult['success'];

            // Allow HTTP
            $httpResult = $this->executeCommand($server, "{$sudoPrefix}ufw allow 80/tcp");
            $steps['allow_http'] = $httpResult['success'];

            // Allow HTTPS
            $httpsResult = $this->executeCommand($server, "{$sudoPrefix}ufw allow 443/tcp");
            $steps['allow_https'] = $httpsResult['success'];

            // Enable UFW
            $enableResult = $this->executeCommand($server, "{$sudoPrefix}ufw --force enable");
            $steps['enable'] = $enableResult['success'] || str_contains($enableResult['output'], 'active');

            $server->update([
                'ufw_installed' => true,
                'ufw_enabled' => true,
            ]);

            $this->logRemediation($server, 'harden_firewall', 'ufw_setup', [
                'command' => "UFW enabled with defaults: deny incoming, allow outgoing, allow SSH({$sshPort}), HTTP(80), HTTPS(443)",
                'rollback' => 'ufw disable',
                'success' => true,
            ]);

            $this->logSecurityEvent(
                $server,
                SecurityEvent::TYPE_FIREWALL_ENABLED,
                "Firewall configured via hardening: SSH({$sshPort}), HTTP(80), HTTPS(443)"
            );

            return [
                'success' => true,
                'message' => 'Firewall configured and enabled successfully',
                'steps' => $steps,
                'allowed_ports' => [$sshPort, 80, 443],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to setup firewall', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to setup firewall: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Install and configure fail2ban with a jail for sshd.
     *
     * @param  array<string, mixed>  $options  Keys: maxretry, bantime, findtime
     * @return array<string, mixed>
     */
    public function configureFail2ban(Server $server, array $options = []): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $maxretry = (int) ($options['maxretry'] ?? 3);
            $bantime = (int) ($options['bantime'] ?? 86400);
            $findtime = (int) ($options['findtime'] ?? 600);
            $sshPort = $server->port ?? 22;
            $steps = [];

            // Install fail2ban if not present
            $checkResult = $this->executeCommand($server, 'which fail2ban-server 2>/dev/null');
            if (! $checkResult['success'] || empty(trim($checkResult['output']))) {
                $installResult = $this->executeCommand(
                    $server,
                    "{$sudoPrefix}apt-get update -qq && {$sudoPrefix}apt-get install -y -qq fail2ban",
                    120
                );
                $steps['install'] = $installResult['success'] ? 'installed' : 'failed: '.$installResult['error'];

                if (! $installResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to install fail2ban: '.$installResult['error'],
                        'steps' => $steps,
                    ];
                }
            } else {
                $steps['install'] = 'already_installed';
            }

            // Create jail.local configuration
            $jailConfig = implode('\n', [
                '[DEFAULT]',
                "bantime = {$bantime}",
                "findtime = {$findtime}",
                "maxretry = {$maxretry}",
                'backend = systemd',
                '',
                '[sshd]',
                'enabled = true',
                "port = {$sshPort}",
                'filter = sshd',
                'logpath = /var/log/auth.log',
                "maxretry = {$maxretry}",
                "bantime = {$bantime}",
                "findtime = {$findtime}",
            ]);

            $writeCommand = "{$sudoPrefix}bash -c 'printf \"{$jailConfig}\\n\" > /etc/fail2ban/jail.local'";
            $writeResult = $this->executeCommand($server, $writeCommand);
            $steps['jail_config'] = $writeResult['success'] ? 'created' : 'failed: '.$writeResult['error'];

            if (! $writeResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to create jail.local: '.$writeResult['error'],
                    'steps' => $steps,
                ];
            }

            // Enable and restart fail2ban
            $this->executeCommand($server, "{$sudoPrefix}systemctl enable fail2ban");
            $restartResult = $this->executeCommand($server, "{$sudoPrefix}systemctl restart fail2ban");
            $steps['service_restart'] = $restartResult['success'];

            $server->update([
                'fail2ban_installed' => true,
                'fail2ban_enabled' => true,
            ]);

            $this->logRemediation($server, 'harden_fail2ban', 'fail2ban_configuration', [
                'command' => "fail2ban configured: maxretry={$maxretry}, bantime={$bantime}s, findtime={$findtime}s, port={$sshPort}",
                'rollback' => 'systemctl stop fail2ban && rm /etc/fail2ban/jail.local',
                'success' => true,
            ]);

            return [
                'success' => true,
                'message' => 'Fail2ban configured and started successfully',
                'steps' => $steps,
                'configuration' => [
                    'maxretry' => $maxretry,
                    'bantime' => $bantime,
                    'findtime' => $findtime,
                    'ssh_port' => $sshPort,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to configure fail2ban', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to configure fail2ban: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Apply kernel hardening parameters via sysctl.
     *
     * Disables IP forwarding, enables SYN cookies, ignores ICMP redirects,
     * and enables TCP SYN flood protection among other settings.
     *
     * @return array<string, mixed>
     */
    public function hardenSysctl(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $applied = [];
            $failed = [];

            // Build the sysctl configuration content
            $lines = ['# DevFlow Pro - Server Hardening Sysctl Configuration'];
            $lines[] = '# Applied: '.now()->toIso8601String();
            $lines[] = '';

            foreach (self::SYSCTL_HARDENING_PARAMS as $key => $value) {
                $lines[] = "{$key} = {$value}";
            }

            $content = implode('\n', $lines);
            $writeCommand = "{$sudoPrefix}bash -c 'printf \"{$content}\\n\" > /etc/sysctl.d/99-devflow-hardening.conf'";
            $writeResult = $this->executeCommand($server, $writeCommand);

            if (! $writeResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to write sysctl configuration: '.$writeResult['error'],
                ];
            }

            // Apply each parameter individually to capture per-parameter status
            foreach (self::SYSCTL_HARDENING_PARAMS as $key => $value) {
                $result = $this->executeCommand($server, "{$sudoPrefix}sysctl -w {$key}={$value} 2>&1");
                if ($result['success']) {
                    $applied[] = "{$key}={$value}";
                } else {
                    $failed[] = "{$key}: ".($result['error'] ?: $result['output']);
                }
            }

            // Reload all sysctl settings to ensure persistence
            $this->executeCommand($server, "{$sudoPrefix}sysctl --system 2>/dev/null");

            $success = count($failed) === 0;

            $this->logRemediation($server, 'harden_sysctl', 'kernel_parameters', [
                'command' => 'Applied '.count($applied).' sysctl parameters via /etc/sysctl.d/99-devflow-hardening.conf',
                'rollback' => 'rm /etc/sysctl.d/99-devflow-hardening.conf && sysctl --system',
                'success' => $success,
            ]);

            return [
                'success' => $success,
                'message' => $success
                    ? 'All sysctl hardening parameters applied successfully'
                    : 'Some sysctl parameters failed to apply',
                'applied' => $applied,
                'failed' => $failed,
                'total_params' => count(self::SYSCTL_HARDENING_PARAMS),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to apply sysctl hardening', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to apply sysctl hardening: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Disable known-unnecessary services if they are currently running.
     *
     * Uses a safe whitelist approach: only disables services from the predefined list.
     *
     * @return array<string, mixed>
     */
    public function disableUnusedServices(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $disabled = [];
            $alreadyStopped = [];
            $notInstalled = [];
            $failed = [];

            foreach (self::UNNECESSARY_SERVICES as $service) {
                // Check if service unit exists
                $existsResult = $this->executeCommand(
                    $server,
                    "{$sudoPrefix}systemctl list-unit-files {$service}.service 2>/dev/null | grep -c {$service}"
                );

                $exists = $existsResult['success'] && (int) trim($existsResult['output']) > 0;

                if (! $exists) {
                    $notInstalled[] = $service;

                    continue;
                }

                // Check if service is currently active
                $activeResult = $this->executeCommand(
                    $server,
                    "{$sudoPrefix}systemctl is-active {$service} 2>/dev/null"
                );

                $isActive = $activeResult['success'] && trim($activeResult['output']) === 'active';

                if (! $isActive) {
                    // Still disable to prevent future startup
                    $this->executeCommand($server, "{$sudoPrefix}systemctl disable {$service} 2>/dev/null");
                    $alreadyStopped[] = $service;

                    continue;
                }

                // Stop and disable the service
                $stopResult = $this->executeCommand(
                    $server,
                    "{$sudoPrefix}systemctl stop {$service} && {$sudoPrefix}systemctl disable {$service}"
                );

                if ($stopResult['success']) {
                    $disabled[] = $service;
                } else {
                    $failed[] = "{$service}: ".($stopResult['error'] ?: $stopResult['output']);
                }
            }

            $this->logRemediation($server, 'harden_services', 'disable_unused_services', [
                'command' => 'Disabled services: '.implode(', ', $disabled),
                'rollback' => 'systemctl enable --now '.implode(' ', $disabled),
                'success' => count($failed) === 0,
            ]);

            return [
                'success' => count($failed) === 0,
                'message' => 'Service cleanup completed',
                'disabled' => $disabled,
                'already_stopped' => $alreadyStopped,
                'not_installed' => $notInstalled,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to disable unused services', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to disable unused services: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check the current hardening state of the server.
     *
     * Returns detailed status for SSH config, firewall, fail2ban, and sysctl.
     *
     * @return array<string, mixed>
     */
    public function getHardeningStatus(Server $server): array
    {
        try {
            $sshStatus = $this->getSSHHardeningStatus($server);
            $firewallStatus = $this->getFirewallStatus($server);
            $fail2banStatus = $this->getFail2banHardeningStatus($server);
            $sysctlStatus = $this->getSysctlStatus($server);
            $servicesStatus = $this->getDisabledServicesStatus($server);

            $hardeningScore = $this->calculateStatusScore(
                $sshStatus,
                $firewallStatus,
                $fail2banStatus,
                $sysctlStatus
            );

            return [
                'success' => true,
                'score' => $hardeningScore,
                'level' => $this->scoreToLevel($hardeningScore),
                'ssh' => $sshStatus,
                'firewall' => $firewallStatus,
                'fail2ban' => $fail2banStatus,
                'sysctl' => $sysctlStatus,
                'services' => $servicesStatus,
                'last_hardening_at' => $server->last_hardening_at?->toIso8601String(),
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get hardening status', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get hardening status: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Harden SSH configuration with security best practices.
     *
     * @return array<string, mixed>
     */
    private function hardenSSH(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $changes = [];

            $hardeningParams = [
                'PermitRootLogin' => 'no',
                'PasswordAuthentication' => 'no',
                'PubkeyAuthentication' => 'yes',
                'MaxAuthTries' => '3',
                'X11Forwarding' => 'no',
                'PermitEmptyPasswords' => 'no',
                'LoginGraceTime' => '60',
                'ClientAliveInterval' => '300',
                'ClientAliveCountMax' => '2',
                'AllowAgentForwarding' => 'no',
                'AllowTcpForwarding' => 'no',
            ];

            foreach ($hardeningParams as $key => $value) {
                $result = $this->updateSshdConfigValue($server, $key, $value);
                if ($result !== null) {
                    $changes[] = $result;
                }
            }

            // Validate the configuration
            $validateResult = $this->executeCommand($server, "{$sudoPrefix}sshd -t");
            if (! $validateResult['success'] && ! empty(trim($validateResult['error']))) {
                return [
                    'success' => false,
                    'message' => 'SSH config validation failed after hardening: '.$validateResult['error'],
                    'changes' => $changes,
                ];
            }

            // Restart SSH to apply
            $restartCommand = "{$sudoPrefix}systemctl restart ssh.service || {$sudoPrefix}systemctl restart sshd";
            $this->executeCommand($server, $restartCommand, 15);

            $this->logRemediation($server, 'harden_ssh', 'ssh_configuration', [
                'command' => 'SSH hardened: '.implode(', ', array_filter($changes)),
                'rollback' => 'Restore /etc/ssh/sshd_config from backup',
                'success' => true,
            ]);

            return [
                'success' => true,
                'message' => 'SSH hardened successfully',
                'changes' => array_filter($changes),
                'warning' => 'Ensure SSH key access is configured before disconnecting.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to harden SSH: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get SSH-specific hardening status.
     *
     * @return array<string, mixed>
     */
    private function getSSHHardeningStatus(Server $server): array
    {
        $result = $this->executeCommand($server, 'sudo cat /etc/ssh/sshd_config 2>/dev/null');

        if (! $result['success']) {
            return ['readable' => false, 'error' => 'Cannot read sshd_config'];
        }

        $config = $this->parseSshdConfig($result['output']);

        return [
            'readable' => true,
            'port' => $config['port'],
            'root_login_disabled' => ! $config['root_login_enabled'],
            'password_auth_disabled' => ! $config['password_auth_enabled'],
            'pubkey_auth_enabled' => $config['pubkey_auth_enabled'],
            'max_auth_tries' => $config['max_auth_tries'],
            'x11_forwarding_disabled' => ! $config['x11_forwarding'],
            'is_hardened' => ! $config['root_login_enabled']
                && ! $config['password_auth_enabled']
                && $config['pubkey_auth_enabled']
                && $config['max_auth_tries'] <= 3,
        ];
    }

    /**
     * Get firewall status for hardening check.
     *
     * @return array<string, mixed>
     */
    private function getFirewallStatus(Server $server): array
    {
        $sudoPrefix = $this->getSudoPrefix($server);
        $result = $this->executeCommand($server, "{$sudoPrefix}ufw status verbose 2>&1");

        $combinedOutput = $result['output'].' '.$result['error'];

        $installed = ! str_contains(strtolower($combinedOutput), 'command not found')
            && ! str_contains(strtolower($combinedOutput), 'ufw: not found');
        $enabled = str_contains($result['output'], 'Status: active');

        // Check default policies
        $defaultDenyIncoming = str_contains($result['output'], 'Default: deny (incoming)');
        $defaultAllowOutgoing = str_contains($result['output'], 'allow (outgoing)');

        return [
            'installed' => $installed,
            'enabled' => $enabled,
            'default_deny_incoming' => $defaultDenyIncoming,
            'default_allow_outgoing' => $defaultAllowOutgoing,
            'is_hardened' => $installed && $enabled && $defaultDenyIncoming,
            'raw_status' => $result['output'],
        ];
    }

    /**
     * Get fail2ban hardening status.
     *
     * @return array<string, mixed>
     */
    private function getFail2banHardeningStatus(Server $server): array
    {
        $result = $this->executeCommand($server, 'sudo -n fail2ban-client status 2>&1');

        $combinedOutput = $result['output'].$result['error'];
        $installed = ! str_contains(strtolower($combinedOutput), 'command not found');
        $running = $installed
            && ! str_contains(strtolower($combinedOutput), 'not running')
            && ! str_contains(strtolower($combinedOutput), 'failed to access socket');

        $hasSSHJail = str_contains($result['output'], 'sshd');

        // Read jail.local for configuration details
        $configDetails = [];
        if ($running) {
            $jailResult = $this->executeCommand($server, 'sudo cat /etc/fail2ban/jail.local 2>/dev/null');
            if ($jailResult['success'] && ! empty($jailResult['output'])) {
                if (preg_match('/maxretry\s*=\s*(\d+)/', $jailResult['output'], $matches)) {
                    $configDetails['maxretry'] = (int) $matches[1];
                }
                if (preg_match('/bantime\s*=\s*(\d+)/', $jailResult['output'], $matches)) {
                    $configDetails['bantime'] = (int) $matches[1];
                }
                if (preg_match('/findtime\s*=\s*(\d+)/', $jailResult['output'], $matches)) {
                    $configDetails['findtime'] = (int) $matches[1];
                }
            }
        }

        return [
            'installed' => $installed,
            'running' => $running,
            'ssh_jail_active' => $hasSSHJail,
            'configuration' => $configDetails,
            'is_hardened' => $installed && $running && $hasSSHJail,
        ];
    }

    /**
     * Get sysctl hardening status.
     *
     * @return array<string, mixed>
     */
    private function getSysctlStatus(Server $server): array
    {
        $compliant = [];
        $nonCompliant = [];

        foreach (self::SYSCTL_HARDENING_PARAMS as $key => $expectedValue) {
            $result = $this->executeCommand($server, "sysctl -n {$key} 2>/dev/null");

            if ($result['success']) {
                $currentValue = trim($result['output']);
                if ($currentValue === $expectedValue) {
                    $compliant[] = $key;
                } else {
                    $nonCompliant[] = [
                        'param' => $key,
                        'expected' => $expectedValue,
                        'current' => $currentValue,
                    ];
                }
            } else {
                $nonCompliant[] = [
                    'param' => $key,
                    'expected' => $expectedValue,
                    'current' => 'unreadable',
                ];
            }
        }

        $totalParams = count(self::SYSCTL_HARDENING_PARAMS);

        return [
            'compliant_count' => count($compliant),
            'non_compliant_count' => count($nonCompliant),
            'total_params' => $totalParams,
            'compliant' => $compliant,
            'non_compliant' => $nonCompliant,
            'is_hardened' => count($nonCompliant) === 0,
            'config_file_exists' => $this->executeCommand(
                $server,
                'test -f /etc/sysctl.d/99-devflow-hardening.conf && echo yes || echo no'
            )['output'] === 'yes',
        ];
    }

    /**
     * Get status of unnecessary services.
     *
     * @return array<string, mixed>
     */
    private function getDisabledServicesStatus(Server $server): array
    {
        $sudoPrefix = $this->getSudoPrefix($server);
        $running = [];
        $stopped = [];
        $notInstalled = [];

        foreach (self::UNNECESSARY_SERVICES as $service) {
            $result = $this->executeCommand(
                $server,
                "{$sudoPrefix}systemctl is-active {$service} 2>/dev/null"
            );

            $status = trim($result['output']);

            if ($status === 'active') {
                $running[] = $service;
            } elseif (str_contains($status, 'could not be found') || $status === '') {
                $notInstalled[] = $service;
            } else {
                $stopped[] = $service;
            }
        }

        return [
            'running_unnecessary' => $running,
            'stopped' => $stopped,
            'not_installed' => $notInstalled,
            'is_clean' => count($running) === 0,
        ];
    }

    /**
     * Update a single sshd_config directive.
     */
    private function updateSshdConfigValue(Server $server, string $key, string $value): ?string
    {
        $sudoPrefix = $this->getSudoPrefix($server);

        $checkCommand = "grep -E '^#?{$key}' /etc/ssh/sshd_config";
        $checkResult = $this->executeCommand($server, $checkCommand);

        if ($checkResult['success'] && ! empty(trim($checkResult['output']))) {
            $command = "{$sudoPrefix}sed -i 's/^#*{$key}.*/{$key} {$value}/' /etc/ssh/sshd_config";
        } else {
            $command = "echo '{$key} {$value}' | {$sudoPrefix}tee -a /etc/ssh/sshd_config > /dev/null";
        }

        $result = $this->executeCommand($server, $command);

        if ($result['success']) {
            return "{$key}={$value}";
        }

        return null;
    }

    /**
     * Parse sshd_config content into a structured array.
     *
     * @return array<string, mixed>
     */
    private function parseSshdConfig(string $content): array
    {
        $config = [
            'port' => 22,
            'root_login_enabled' => true,
            'password_auth_enabled' => true,
            'pubkey_auth_enabled' => true,
            'max_auth_tries' => 6,
            'x11_forwarding' => false,
        ];

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^(\w+)\s+(.+)$/', $line, $matches)) {
                $key = strtolower($matches[1]);
                $val = trim($matches[2]);

                match ($key) {
                    'port' => $config['port'] = (int) $val,
                    'permitrootlogin' => $config['root_login_enabled'] = in_array(
                        strtolower($val),
                        ['yes', 'prohibit-password', 'without-password'],
                        true
                    ),
                    'passwordauthentication' => $config['password_auth_enabled'] = strtolower($val) === 'yes',
                    'pubkeyauthentication' => $config['pubkey_auth_enabled'] = strtolower($val) === 'yes',
                    'maxauthtries' => $config['max_auth_tries'] = (int) $val,
                    'x11forwarding' => $config['x11_forwarding'] = strtolower($val) === 'yes',
                    default => null,
                };
            }
        }

        return $config;
    }

    /**
     * Calculate a hardening level string based on completed steps.
     *
     * @param  array<string, mixed>  $results
     */
    private function calculateHardeningLevel(array $results): string
    {
        $successCount = 0;
        $totalCount = count($results);

        foreach ($results as $result) {
            if (is_array($result) && ($result['success'] ?? false)) {
                $successCount++;
            }
        }

        if ($totalCount === 0) {
            return 'none';
        }

        $ratio = $successCount / $totalCount;

        return match (true) {
            $ratio >= 1.0 => 'full',
            $ratio >= 0.75 => 'high',
            $ratio >= 0.5 => 'medium',
            $ratio > 0.0 => 'low',
            default => 'none',
        };
    }

    /**
     * Calculate a numeric score (0-100) from hardening status components.
     *
     * @param  array<string, mixed>  $ssh
     * @param  array<string, mixed>  $firewall
     * @param  array<string, mixed>  $fail2ban
     * @param  array<string, mixed>  $sysctl
     */
    private function calculateStatusScore(
        array $ssh,
        array $firewall,
        array $fail2ban,
        array $sysctl
    ): int {
        $score = 0;

        // SSH: up to 30 points
        if ($ssh['is_hardened'] ?? false) {
            $score += 30;
        } else {
            if ($ssh['root_login_disabled'] ?? false) {
                $score += 10;
            }
            if ($ssh['password_auth_disabled'] ?? false) {
                $score += 10;
            }
            if ($ssh['pubkey_auth_enabled'] ?? false) {
                $score += 5;
            }
            if (($ssh['max_auth_tries'] ?? 6) <= 3) {
                $score += 5;
            }
        }

        // Firewall: up to 25 points
        if ($firewall['is_hardened'] ?? false) {
            $score += 25;
        } elseif ($firewall['enabled'] ?? false) {
            $score += 15;
        } elseif ($firewall['installed'] ?? false) {
            $score += 5;
        }

        // Fail2ban: up to 25 points
        if ($fail2ban['is_hardened'] ?? false) {
            $score += 25;
        } elseif ($fail2ban['running'] ?? false) {
            $score += 15;
        } elseif ($fail2ban['installed'] ?? false) {
            $score += 5;
        }

        // Sysctl: up to 20 points
        if ($sysctl['is_hardened'] ?? false) {
            $score += 20;
        } else {
            $total = $sysctl['total_params'] ?? 1;
            $compliant = $sysctl['compliant_count'] ?? 0;
            $score += (int) round(20 * ($compliant / max($total, 1)));
        }

        return min($score, 100);
    }

    /**
     * Convert a numeric hardening score to a human-readable level.
     */
    private function scoreToLevel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'full',
            $score >= 70 => 'high',
            $score >= 40 => 'medium',
            $score > 0 => 'low',
            default => 'none',
        };
    }

    /**
     * Validate that a port number is within acceptable range for SSH.
     */
    private function validatePort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535');
        }

        if ($port < 1024 && $port !== 22) {
            throw new \InvalidArgumentException('SSH port must be 22 or above 1024 to avoid conflicts with well-known services');
        }
    }

    /**
     * Get the sudo prefix based on the server's username.
     */
    private function getSudoPrefix(Server $server): string
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

    /**
     * Log a security event for audit trail.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    private function logSecurityEvent(
        Server $server,
        string $eventType,
        string $details,
        ?array $metadata = null
    ): void {
        SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => $eventType,
            'details' => $details,
            'metadata' => $metadata,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Log a remediation action with rollback information.
     *
     * @param  array{command: string, rollback: string, success: bool}  $data
     */
    private function logRemediation(Server $server, string $action, string $target, array $data): void
    {
        RemediationLog::create([
            'server_id' => $server->id,
            'action' => $action,
            'target' => $target,
            'command_executed' => $data['command'],
            'rollback_command' => $data['rollback'],
            'success' => $data['success'],
            'auto_triggered' => false,
        ]);
    }
}
