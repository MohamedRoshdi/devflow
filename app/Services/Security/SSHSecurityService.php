<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Server;
use App\Models\SshConfiguration;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SSHSecurityService
{
    public function getCurrentConfig(Server $server): array
    {
        try {
            $command = "sudo cat /etc/ssh/sshd_config 2>/dev/null";
            $result = $this->executeCommand($server, $command);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => 'Unable to read SSH configuration',
                ];
            }

            $config = $this->parseSshdConfig($result['output']);

            // Update or create local SSH configuration record
            SshConfiguration::updateOrCreate(
                ['server_id' => $server->id],
                [
                    'port' => $config['port'],
                    'root_login_enabled' => $config['root_login_enabled'],
                    'password_auth_enabled' => $config['password_auth_enabled'],
                    'pubkey_auth_enabled' => $config['pubkey_auth_enabled'],
                    'max_auth_tries' => $config['max_auth_tries'],
                    'last_synced_at' => now(),
                ]
            );

            return [
                'success' => true,
                'config' => $config,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get SSH config', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateConfig(Server $server, array $config): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $changes = [];

            if (isset($config['port'])) {
                $this->validatePort($config['port']);
                $changes[] = $this->updateConfigValue($server, 'Port', (string) $config['port']);
            }

            if (isset($config['root_login_enabled'])) {
                $value = $config['root_login_enabled'] ? 'yes' : 'no';
                $changes[] = $this->updateConfigValue($server, 'PermitRootLogin', $value);
            }

            if (isset($config['password_auth_enabled'])) {
                $value = $config['password_auth_enabled'] ? 'yes' : 'no';
                $changes[] = $this->updateConfigValue($server, 'PasswordAuthentication', $value);
            }

            if (isset($config['pubkey_auth_enabled'])) {
                $value = $config['pubkey_auth_enabled'] ? 'yes' : 'no';
                $changes[] = $this->updateConfigValue($server, 'PubkeyAuthentication', $value);
            }

            if (isset($config['max_auth_tries'])) {
                $this->validateMaxAuthTries($config['max_auth_tries']);
                $changes[] = $this->updateConfigValue($server, 'MaxAuthTries', (string) $config['max_auth_tries']);
            }

            // Validate configuration
            $validateResult = $this->executeCommand($server, "{$sudoPrefix}sshd -t");
            if (!$validateResult['success'] && !empty(trim($validateResult['error']))) {
                return [
                    'success' => false,
                    'message' => 'SSH configuration validation failed: ' . $validateResult['error'],
                ];
            }

            // Log the changes
            $this->logEvent(
                $server,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                'SSH configuration updated: ' . implode(', ', array_filter($changes))
            );

            return [
                'success' => true,
                'message' => 'SSH configuration updated. Restart SSH service to apply changes.',
                'changes' => $changes,
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update SSH configuration: ' . $e->getMessage(),
            ];
        }
    }

    public function changePort(Server $server, int $newPort): array
    {
        try {
            $this->validatePort($newPort);

            $result = $this->updateConfigValue($server, 'Port', (string) $newPort);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Failed to update SSH port',
                ];
            }

            $this->logEvent(
                $server,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                "SSH port changed to {$newPort}"
            );

            return [
                'success' => true,
                'message' => "SSH port changed to {$newPort}. Remember to update firewall rules and restart SSH.",
                'new_port' => $newPort,
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to change SSH port: ' . $e->getMessage(),
            ];
        }
    }

    public function toggleRootLogin(Server $server, bool $enable): array
    {
        try {
            $value = $enable ? 'yes' : 'no';
            $result = $this->updateConfigValue($server, 'PermitRootLogin', $value);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Failed to update root login setting',
                ];
            }

            $this->logEvent(
                $server,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                'Root login ' . ($enable ? 'enabled' : 'disabled')
            );

            return [
                'success' => true,
                'message' => 'Root login ' . ($enable ? 'enabled' : 'disabled') . '. Restart SSH to apply.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to toggle root login: ' . $e->getMessage(),
            ];
        }
    }

    public function togglePasswordAuth(Server $server, bool $enable): array
    {
        try {
            $value = $enable ? 'yes' : 'no';
            $result = $this->updateConfigValue($server, 'PasswordAuthentication', $value);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Failed to update password authentication setting',
                ];
            }

            $this->logEvent(
                $server,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                'Password authentication ' . ($enable ? 'enabled' : 'disabled')
            );

            return [
                'success' => true,
                'message' => 'Password authentication ' . ($enable ? 'enabled' : 'disabled') . '. Restart SSH to apply.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to toggle password auth: ' . $e->getMessage(),
            ];
        }
    }

    public function hardenSSH(Server $server): array
    {
        try {
            $changes = [];

            // Disable root login
            $changes[] = $this->updateConfigValue($server, 'PermitRootLogin', 'no');

            // Disable password authentication
            $changes[] = $this->updateConfigValue($server, 'PasswordAuthentication', 'no');

            // Enable public key authentication
            $changes[] = $this->updateConfigValue($server, 'PubkeyAuthentication', 'yes');

            // Set MaxAuthTries to 3
            $changes[] = $this->updateConfigValue($server, 'MaxAuthTries', '3');

            // Disable X11 forwarding
            $changes[] = $this->updateConfigValue($server, 'X11Forwarding', 'no');

            // Disable empty passwords
            $changes[] = $this->updateConfigValue($server, 'PermitEmptyPasswords', 'no');

            // Set login grace time
            $changes[] = $this->updateConfigValue($server, 'LoginGraceTime', '60');

            $this->logEvent(
                $server,
                SecurityEvent::TYPE_SSH_CONFIG_CHANGED,
                'SSH hardening applied: disabled root login, password auth, set MaxAuthTries to 3'
            );

            return [
                'success' => true,
                'message' => 'SSH hardening applied. Restart SSH service to apply changes.',
                'changes' => array_filter($changes),
                'warning' => 'Make sure you have SSH key access before restarting SSH!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to harden SSH: ' . $e->getMessage(),
            ];
        }
    }

    public function restartSSHService(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);

            // First validate the configuration
            $validateResult = $this->executeCommand($server, "{$sudoPrefix}sshd -t");
            if (!$validateResult['success'] && !empty(trim($validateResult['error']))) {
                return [
                    'success' => false,
                    'message' => 'SSH configuration is invalid: ' . $validateResult['error'],
                ];
            }

            // Restart the service
            $command = "{$sudoPrefix}systemctl restart sshd || {$sudoPrefix}systemctl restart ssh";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'SSH service restarted successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to restart SSH: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to restart SSH: ' . $e->getMessage(),
            ];
        }
    }

    public function validateConfig(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $result = $this->executeCommand($server, "{$sudoPrefix}sshd -t");

            if ($result['success'] || empty(trim($result['error']))) {
                return [
                    'success' => true,
                    'valid' => true,
                    'message' => 'SSH configuration is valid',
                ];
            }

            return [
                'success' => true,
                'valid' => false,
                'message' => 'SSH configuration has errors: ' . $result['error'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'valid' => false,
                'message' => 'Failed to validate SSH config: ' . $e->getMessage(),
            ];
        }
    }

    protected function parseSshdConfig(string $content): array
    {
        $config = [
            'port' => 22,
            'root_login_enabled' => true,
            'password_auth_enabled' => true,
            'pubkey_auth_enabled' => true,
            'max_auth_tries' => 6,
            'x11_forwarding' => false,
            'login_grace_time' => 120,
        ];

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key-value pairs
            if (preg_match('/^(\w+)\s+(.+)$/', $line, $matches)) {
                $key = strtolower($matches[1]);
                $value = trim($matches[2]);

                switch ($key) {
                    case 'port':
                        $config['port'] = (int) $value;
                        break;
                    case 'permitrootlogin':
                        $config['root_login_enabled'] = in_array(strtolower($value), ['yes', 'prohibit-password', 'without-password']);
                        break;
                    case 'passwordauthentication':
                        $config['password_auth_enabled'] = strtolower($value) === 'yes';
                        break;
                    case 'pubkeyauthentication':
                        $config['pubkey_auth_enabled'] = strtolower($value) === 'yes';
                        break;
                    case 'maxauthtries':
                        $config['max_auth_tries'] = (int) $value;
                        break;
                    case 'x11forwarding':
                        $config['x11_forwarding'] = strtolower($value) === 'yes';
                        break;
                    case 'logingracetime':
                        $config['login_grace_time'] = (int) $value;
                        break;
                }
            }
        }

        return $config;
    }

    protected function updateConfigValue(Server $server, string $key, string $value): ?string
    {
        $sudoPrefix = $this->getSudoPrefix($server);
        $escapedKey = escapeshellarg($key);
        $escapedValue = escapeshellarg($value);

        // First, check if the key exists (commented or not)
        $checkCommand = "grep -E '^#?{$key}' /etc/ssh/sshd_config";
        $checkResult = $this->executeCommand($server, $checkCommand);

        if ($checkResult['success'] && !empty(trim($checkResult['output']))) {
            // Key exists, update it
            $command = "{$sudoPrefix}sed -i 's/^#*{$key}.*/{$key} {$value}/' /etc/ssh/sshd_config";
        } else {
            // Key doesn't exist, append it
            $command = "echo '{$key} {$value}' | {$sudoPrefix}tee -a /etc/ssh/sshd_config > /dev/null";
        }

        $result = $this->executeCommand($server, $command);

        if ($result['success']) {
            return "{$key}={$value}";
        }

        return null;
    }

    protected function validatePort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535');
        }

        // Warn about well-known ports
        if ($port < 1024 && $port !== 22) {
            throw new \InvalidArgumentException('Port must be 22 or above 1024 for SSH');
        }
    }

    protected function validateMaxAuthTries(int $tries): void
    {
        if ($tries < 1 || $tries > 10) {
            throw new \InvalidArgumentException('MaxAuthTries must be between 1 and 10');
        }
    }

    protected function executeCommand(Server $server, string $command, int $timeout = 30): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address);

            if ($isLocalhost) {
                $process = Process::fromShellCommandline($command);
                $process->setTimeout($timeout);
                $process->run();

                return [
                    'success' => $process->isSuccessful(),
                    'output' => trim($process->getOutput()),
                    'error' => $process->getErrorOutput(),
                ];
            }

            $sshCommand = $this->buildSSHCommand($server, $command);
            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout($timeout);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => trim($process->getOutput()),
                'error' => $process->getErrorOutput(),
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
            '-p ' . $port,
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
            $sshOptions[] = '-i ' . $keyFile;
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

    protected function logEvent(Server $server, string $eventType, string $details): void
    {
        SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => $eventType,
            'details' => $details,
            'user_id' => Auth::id(),
        ]);
    }
}
