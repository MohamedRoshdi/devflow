<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityEvent;
use App\Models\SecurityScan;
use App\Models\Server;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SecurityScoreService
{
    public function __construct(
        protected FirewallService $firewallService,
        protected Fail2banService $fail2banService,
        protected SSHSecurityService $sshSecurityService
    ) {}

    public function runSecurityScan(Server $server): SecurityScan
    {
        $scan = SecurityScan::create([
            'server_id' => $server->id,
            'status' => SecurityScan::STATUS_RUNNING,
            'started_at' => now(),
            'triggered_by' => Auth::id(),
        ]);

        try {
            $findings = $this->collectFindings($server);
            $score = $this->calculateScoreFromFindings($findings);
            $recommendations = $this->generateRecommendations($findings);

            $scan->update([
                'status' => SecurityScan::STATUS_COMPLETED,
                'score' => $score,
                'risk_level' => SecurityScan::getRiskLevelFromScore($score),
                'findings' => $findings,
                'recommendations' => $recommendations,
                'completed_at' => now(),
            ]);

            // Update server security score
            $server->update([
                'security_score' => $score,
                'last_security_scan_at' => now(),
            ]);

            // Log the scan event
            SecurityEvent::create([
                'server_id' => $server->id,
                'event_type' => SecurityEvent::TYPE_SECURITY_SCAN,
                'details' => "Security scan completed. Score: {$score}/100",
                'metadata' => ['score' => $score, 'risk_level' => $scan->risk_level],
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Security scan failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            $scan->update([
                'status' => SecurityScan::STATUS_FAILED,
                'completed_at' => now(),
                'findings' => ['error' => $e->getMessage()],
            ]);
        }

        $freshScan = $scan->fresh();
        if ($freshScan === null) {
            throw new \RuntimeException('Failed to refresh scan');
        }

return $freshScan;
    }

    public function calculateScore(Server $server): int
    {
        $findings = $this->collectFindings($server);

        return $this->calculateScoreFromFindings($findings);
    }

    public function getScoreBreakdown(Server $server): array
    {
        $findings = $this->collectFindings($server);

        return [
            'total_score' => $this->calculateScoreFromFindings($findings),
            'firewall' => [
                'score' => $findings['firewall']['score'] ?? 0,
                'max' => 20,
                'status' => $findings['firewall']['enabled'] ?? false,
            ],
            'fail2ban' => [
                'score' => $findings['fail2ban']['score'] ?? 0,
                'max' => 15,
                'status' => $findings['fail2ban']['enabled'] ?? false,
            ],
            'ssh_port' => [
                'score' => $findings['ssh']['port_score'] ?? 0,
                'max' => 10,
                'port' => $findings['ssh']['port'] ?? 22,
            ],
            'root_login' => [
                'score' => $findings['ssh']['root_login_score'] ?? 0,
                'max' => 15,
                'disabled' => ! ($findings['ssh']['root_login_enabled'] ?? true),
            ],
            'password_auth' => [
                'score' => $findings['ssh']['password_auth_score'] ?? 0,
                'max' => 15,
                'disabled' => ! ($findings['ssh']['password_auth_enabled'] ?? true),
            ],
            'open_ports' => [
                'score' => $findings['open_ports']['score'] ?? 0,
                'max' => 10,
                'count' => $findings['open_ports']['count'] ?? 0,
            ],
            'updates' => [
                'score' => $findings['updates']['score'] ?? 0,
                'max' => 15,
                'pending' => $findings['updates']['security_updates'] ?? 0,
            ],
        ];
    }

    protected function collectFindings(Server $server): array
    {
        $findings = [];

        // Check firewall
        $ufwStatus = $this->firewallService->getUfwStatus($server);
        $findings['firewall'] = [
            'installed' => $ufwStatus['installed'] ?? false,
            'enabled' => $ufwStatus['enabled'] ?? false,
            'rules_count' => count($ufwStatus['rules'] ?? []),
            'score' => ($ufwStatus['enabled'] ?? false) ? 20 : 0,
        ];

        // Check Fail2ban
        $fail2banStatus = $this->fail2banService->getFail2banStatus($server);
        $findings['fail2ban'] = [
            'installed' => $fail2banStatus['installed'] ?? false,
            'enabled' => $fail2banStatus['enabled'] ?? false,
            'jails_count' => count($fail2banStatus['jails'] ?? []),
            'score' => ($fail2banStatus['enabled'] ?? false) ? 15 : 0,
        ];

        // Check SSH configuration
        $sshConfig = $this->sshSecurityService->getCurrentConfig($server);
        if ($sshConfig['success']) {
            $config = $sshConfig['config'];
            $portScore = ($config['port'] !== 22) ? 10 : 0;
            $rootLoginScore = (! $config['root_login_enabled']) ? 15 : 0;
            $passwordAuthScore = (! $config['password_auth_enabled']) ? 15 : 0;

            $findings['ssh'] = [
                'port' => $config['port'],
                'root_login_enabled' => $config['root_login_enabled'],
                'password_auth_enabled' => $config['password_auth_enabled'],
                'pubkey_auth_enabled' => $config['pubkey_auth_enabled'],
                'max_auth_tries' => $config['max_auth_tries'],
                'port_score' => $portScore,
                'root_login_score' => $rootLoginScore,
                'password_auth_score' => $passwordAuthScore,
            ];
        } else {
            $findings['ssh'] = [
                'error' => 'Unable to retrieve SSH configuration',
                'port_score' => 0,
                'root_login_score' => 0,
                'password_auth_score' => 0,
            ];
        }

        // Check open ports
        $openPorts = $this->getOpenPorts($server);
        $commonPorts = $this->countCommonPorts($openPorts);
        $findings['open_ports'] = [
            'ports' => $openPorts,
            'count' => count($openPorts),
            'common_ports' => $commonPorts,
            'score' => $this->calculateOpenPortsScore($openPorts),
        ];

        // Check for security updates
        $updates = $this->checkSecurityUpdates($server);
        $findings['updates'] = [
            'security_updates' => $updates['security_count'] ?? 0,
            'total_updates' => $updates['total_count'] ?? 0,
            'score' => $this->calculateUpdatesScore($updates),
        ];

        return $findings;
    }

    protected function calculateScoreFromFindings(array $findings): int
    {
        $score = 0;

        // Firewall (20 points)
        $score += $findings['firewall']['score'] ?? 0;

        // Fail2ban (15 points)
        $score += $findings['fail2ban']['score'] ?? 0;

        // SSH Port (10 points)
        $score += $findings['ssh']['port_score'] ?? 0;

        // Root Login (15 points)
        $score += $findings['ssh']['root_login_score'] ?? 0;

        // Password Auth (15 points)
        $score += $findings['ssh']['password_auth_score'] ?? 0;

        // Open Ports (10 points)
        $score += $findings['open_ports']['score'] ?? 0;

        // Updates (15 points)
        $score += $findings['updates']['score'] ?? 0;

        return (int) min(100, max(0, $score));
    }

    protected function generateRecommendations(array $findings): array
    {
        $recommendations = [];

        // Firewall recommendations
        if (! ($findings['firewall']['installed'] ?? false)) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'firewall',
                'title' => 'Install UFW Firewall',
                'description' => 'UFW (Uncomplicated Firewall) is not installed. Install it to protect your server from unauthorized access.',
                'command' => 'sudo apt-get install -y ufw',
            ];
        } elseif (! ($findings['firewall']['enabled'] ?? false)) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'firewall',
                'title' => 'Enable UFW Firewall',
                'description' => 'UFW is installed but not enabled. Enable it to activate firewall protection.',
                'command' => 'sudo ufw enable',
            ];
        }

        // Fail2ban recommendations
        if (! ($findings['fail2ban']['installed'] ?? false)) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'fail2ban',
                'title' => 'Install Fail2ban',
                'description' => 'Fail2ban protects against brute-force attacks by banning IPs with too many failed login attempts.',
                'command' => 'sudo apt-get install -y fail2ban',
            ];
        } elseif (! ($findings['fail2ban']['enabled'] ?? false)) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'fail2ban',
                'title' => 'Enable Fail2ban',
                'description' => 'Fail2ban is installed but not running. Start it to protect against brute-force attacks.',
                'command' => 'sudo systemctl start fail2ban',
            ];
        }

        // SSH recommendations
        if (($findings['ssh']['port'] ?? 22) === 22) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'ssh',
                'title' => 'Change Default SSH Port',
                'description' => 'Using the default SSH port (22) makes your server an easy target for automated attacks. Consider changing to a non-standard port.',
            ];
        }

        if ($findings['ssh']['root_login_enabled'] ?? true) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'ssh',
                'title' => 'Disable Root Login',
                'description' => 'Root login via SSH is enabled. Disable it and use a regular user with sudo privileges.',
            ];
        }

        if ($findings['ssh']['password_auth_enabled'] ?? true) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'ssh',
                'title' => 'Disable Password Authentication',
                'description' => 'Password authentication is enabled. Use SSH keys only for more secure authentication.',
            ];
        }

        // Updates recommendations
        if (($findings['updates']['security_updates'] ?? 0) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'updates',
                'title' => 'Install Security Updates',
                'description' => "There are {$findings['updates']['security_updates']} security updates available. Install them to patch vulnerabilities.",
                'command' => 'sudo apt-get update && sudo apt-get upgrade -y',
            ];
        }

        return $recommendations;
    }

    protected function getOpenPorts(Server $server): array
    {
        try {
            $command = "sudo ss -tulpn 2>/dev/null | grep LISTEN | awk '{print \$5}' | sed 's/.*://' | sort -nu";
            $result = $this->executeCommand($server, $command);

            if (! $result['success']) {
                return [];
            }

            $ports = array_filter(
                array_map('trim', explode("\n", $result['output'])),
                fn ($port) => is_numeric($port)
            );

            return array_values(array_map('intval', $ports));
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function countCommonPorts(array $ports): int
    {
        $commonPorts = [22, 80, 443, 3306, 5432, 6379, 27017];

        return count(array_intersect($ports, $commonPorts));
    }

    protected function calculateOpenPortsScore(array $ports): int
    {
        $count = count($ports);

        // Fewer open ports is better
        if ($count <= 3) {
            return 10;
        } elseif ($count <= 5) {
            return 7;
        } elseif ($count <= 10) {
            return 4;
        }

        return 0;
    }

    protected function checkSecurityUpdates(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);

            // Check for updates
            $this->executeCommand($server, "{$sudoPrefix}apt-get update -qq");

            // Count security updates
            $result = $this->executeCommand($server, 'apt list --upgradable 2>/dev/null | grep -i security | wc -l');
            $securityCount = (int) trim($result['output']);

            // Count total updates
            $result = $this->executeCommand($server, 'apt list --upgradable 2>/dev/null | grep -v Listing | wc -l');
            $totalCount = (int) trim($result['output']);

            return [
                'security_count' => $securityCount,
                'total_count' => $totalCount,
            ];
        } catch (\Exception $e) {
            return [
                'security_count' => 0,
                'total_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function calculateUpdatesScore(array $updates): int
    {
        $securityUpdates = $updates['security_count'] ?? 0;

        if ($securityUpdates === 0) {
            return 15;
        } elseif ($securityUpdates <= 2) {
            return 10;
        } elseif ($securityUpdates <= 5) {
            return 5;
        }

        return 0;
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
}
