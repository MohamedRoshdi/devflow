<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Server;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Auth;

class ServerSecurityService
{
    public function __construct(
        protected FirewallService $firewallService,
        protected Fail2banService $fail2banService,
        protected SSHSecurityService $sshSecurityService,
        protected SecurityScoreService $securityScoreService
    ) {}

    public function getSecurityOverview(Server $server): array
    {
        $ufwStatus = $this->firewallService->getUfwStatus($server);
        $fail2banStatus = $this->fail2banService->getFail2banStatus($server);
        $sshConfig = $this->sshSecurityService->getCurrentConfig($server);
        $openPorts = $this->getOpenPorts($server);

        return [
            'ufw' => $ufwStatus,
            'fail2ban' => $fail2banStatus,
            'ssh' => $sshConfig,
            'open_ports' => $openPorts,
            'security_score' => $server->security_score,
            'risk_level' => $server->security_risk_level,
            'last_scan_at' => $server->last_security_scan_at,
        ];
    }

    public function checkSecurityToolsStatus(Server $server): array
    {
        $ufwStatus = $this->firewallService->getUfwStatus($server);
        $fail2banStatus = $this->fail2banService->getFail2banStatus($server);

        $server->update([
            'ufw_installed' => $ufwStatus['installed'] ?? false,
            'ufw_enabled' => $ufwStatus['enabled'] ?? false,
            'fail2ban_installed' => $fail2banStatus['installed'] ?? false,
            'fail2ban_enabled' => $fail2banStatus['enabled'] ?? false,
        ]);

        return [
            'ufw' => $ufwStatus,
            'fail2ban' => $fail2banStatus,
        ];
    }

    public function getOpenPorts(Server $server): array
    {
        try {
            $command = "sudo ss -tulpn 2>/dev/null | grep LISTEN | awk '{print \$5}' | sed 's/.*://' | sort -u";
            $result = $this->executeCommand($server, $command);

            if (!$result['success']) {
                return ['success' => false, 'ports' => []];
            }

            $ports = array_filter(
                array_map('trim', explode("\n", $result['output'])),
                fn($port) => is_numeric($port)
            );

            return [
                'success' => true,
                'ports' => array_values($ports),
                'count' => count($ports),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'ports' => [], 'error' => $e->getMessage()];
        }
    }

    public function logSecurityEvent(
        Server $server,
        string $eventType,
        ?string $details = null,
        ?string $sourceIp = null,
        ?array $metadata = null
    ): SecurityEvent {
        return SecurityEvent::create([
            'server_id' => $server->id,
            'event_type' => $eventType,
            'details' => $details,
            'source_ip' => $sourceIp,
            'metadata' => $metadata,
            'user_id' => Auth::id(),
        ]);
    }

    public function getRecentEvents(Server $server, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $server->securityEvents()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function executeCommand(Server $server, string $command): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address);

            if ($isLocalhost) {
                $process = \Symfony\Component\Process\Process::fromShellCommandline($command);
                $process->setTimeout(30);
                $process->run();

                return [
                    'success' => $process->isSuccessful(),
                    'output' => trim($process->getOutput()),
                    'error' => $process->getErrorOutput(),
                ];
            }

            $sshCommand = $this->buildSSHCommand($server, $command);
            $process = \Symfony\Component\Process\Process::fromShellCommandline($sshCommand);
            $process->setTimeout(30);
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
        if ($ip === $serverIP) {
            return true;
        }

        return false;
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
}
