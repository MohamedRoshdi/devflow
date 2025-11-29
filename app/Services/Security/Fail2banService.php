<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Server;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class Fail2banService
{
    public function getFail2banStatus(Server $server): array
    {
        try {
            $result = $this->executeCommand($server, 'sudo fail2ban-client status 2>&1');

            if (!$result['success'] && str_contains($result['output'] . $result['error'], 'command not found')) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'jails' => [],
                    'message' => 'Fail2ban is not installed',
                ];
            }

            if (str_contains($result['output'] . $result['error'], 'not running') ||
                str_contains($result['output'] . $result['error'], 'failed to access socket')) {
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

        if (!$status['enabled']) {
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
            $result = $this->executeCommand($server, "sudo fail2ban-client status {$jailName} 2>&1");

            if (!$result['success']) {
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

                if (!$jailStatus['success']) {
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
            if (!$status['enabled']) {
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
                'message' => 'Failed to unban IP: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to unban IP: ' . $e->getMessage(),
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
                'message' => 'Failed to ban IP: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to ban IP: ' . $e->getMessage(),
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
                'message' => 'Failed to start Fail2ban: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start Fail2ban: ' . $e->getMessage(),
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
                'message' => 'Failed to stop Fail2ban: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to stop Fail2ban: ' . $e->getMessage(),
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
                'message' => 'Failed to install Fail2ban: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to install Fail2ban: ' . $e->getMessage(),
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
                if (!empty($ipList)) {
                    $data['banned_ips'] = array_map('trim', preg_split('/\s+/', $ipList));
                }
            }
        }

        return $data;
    }

    protected function validateIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
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

        // Escape the remote command for bash - use single quotes and escape any existing single quotes
        $escapedCommand = "'" . str_replace("'", "'\\''", $remoteCommand) . "'";

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s bash -c %s 2>&1',
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
            'ssh %s %s@%s bash -c %s 2>&1',
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
}
