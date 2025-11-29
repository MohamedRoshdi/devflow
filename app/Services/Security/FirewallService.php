<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Server;
use App\Models\FirewallRule;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FirewallService
{
    public function getUfwStatus(Server $server): array
    {
        try {
            $result = $this->executeCommand($server, 'sudo ufw status verbose 2>&1');

            $combinedOutput = $result['output'] . ' ' . $result['error'];

            // Check if UFW command not found
            if (str_contains(strtolower($combinedOutput), 'command not found') ||
                str_contains(strtolower($combinedOutput), 'ufw: not found')) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'rules' => [],
                    'message' => 'UFW is not installed',
                    'raw_output' => $combinedOutput,
                ];
            }

            // Check for permission denied (still means UFW is installed)
            if (str_contains(strtolower($combinedOutput), 'permission denied')) {
                return [
                    'installed' => true,
                    'enabled' => false,
                    'rules' => [],
                    'message' => 'Permission denied - check sudo access',
                    'raw_output' => $combinedOutput,
                ];
            }

            $output = $result['output'];

            // UFW is installed if we get any status response
            $isInstalled = str_contains($output, 'Status:') ||
                           str_contains($output, 'inactive') ||
                           str_contains($output, 'active');

            // If the command ran but didn't return expected output, try which ufw
            if (!$isInstalled && $result['success']) {
                $whichResult = $this->executeCommand($server, 'which ufw 2>&1');
                $isInstalled = !empty(trim($whichResult['output'])) && str_contains($whichResult['output'], '/ufw');
            }

            // Also check if we got inactive status
            if (!$isInstalled && (str_contains($combinedOutput, 'inactive') || str_contains($combinedOutput, 'Status'))) {
                $isInstalled = true;
            }

            $enabled = str_contains($output, 'Status: active');
            $rules = $this->parseUfwStatus($output);

            // Update server record with detected status
            if ($isInstalled) {
                $server->update([
                    'ufw_installed' => true,
                    'ufw_enabled' => $enabled,
                ]);
            }

            return [
                'installed' => $isInstalled,
                'enabled' => $enabled,
                'rules' => $rules,
                'raw_output' => $combinedOutput,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get UFW status', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'installed' => false,
                'enabled' => false,
                'rules' => [],
                'error' => $e->getMessage(),
                'raw_output' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    public function enableUfw(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}ufw --force enable";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'active')) {
                $server->update(['ufw_enabled' => true]);

                $this->logEvent($server, SecurityEvent::TYPE_FIREWALL_ENABLED, 'UFW firewall enabled');

                return [
                    'success' => true,
                    'message' => 'Firewall enabled successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to enable firewall: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to enable firewall: ' . $e->getMessage(),
            ];
        }
    }

    public function disableUfw(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}ufw disable";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'disabled')) {
                $server->update(['ufw_enabled' => false]);

                $this->logEvent($server, SecurityEvent::TYPE_FIREWALL_DISABLED, 'UFW firewall disabled');

                return [
                    'success' => true,
                    'message' => 'Firewall disabled successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to disable firewall: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to disable firewall: ' . $e->getMessage(),
            ];
        }
    }

    public function getRulesNumbered(Server $server): array
    {
        try {
            $result = $this->executeCommand($server, 'sudo ufw status numbered 2>&1');

            if (!$result['success']) {
                return ['success' => false, 'rules' => [], 'error' => $result['error']];
            }

            $rules = $this->parseNumberedRules($result['output']);

            return [
                'success' => true,
                'rules' => $rules,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'rules' => [], 'error' => $e->getMessage()];
        }
    }

    public function addRule(
        Server $server,
        string $port,
        string $protocol = 'tcp',
        string $action = 'allow',
        ?string $fromIp = null,
        ?string $description = null
    ): array {
        try {
            $this->validatePort($port);
            $this->validateProtocol($protocol);
            $this->validateAction($action);

            if ($fromIp) {
                $this->validateIp($fromIp);
            }

            $sudoPrefix = $this->getSudoPrefix($server);

            if ($fromIp) {
                $command = "{$sudoPrefix}ufw {$action} from " . escapeshellarg($fromIp) . " to any port " . escapeshellarg($port);
            } else {
                $command = "{$sudoPrefix}ufw {$action} " . escapeshellarg($port) . "/" . escapeshellarg($protocol);
            }

            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'added') || str_contains($result['output'], 'updated')) {
                // Store rule in database for audit
                FirewallRule::create([
                    'server_id' => $server->id,
                    'action' => $action,
                    'direction' => 'in',
                    'protocol' => $protocol,
                    'port' => $port,
                    'from_ip' => $fromIp,
                    'description' => $description,
                    'is_active' => true,
                ]);

                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_RULE_ADDED,
                    "Added firewall rule: {$action} {$port}/{$protocol}" . ($fromIp ? " from {$fromIp}" : ''),
                    $fromIp
                );

                return [
                    'success' => true,
                    'message' => 'Rule added successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to add rule: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add rule: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteRule(Server $server, int $ruleNumber): array
    {
        try {
            if ($ruleNumber < 1) {
                return ['success' => false, 'message' => 'Invalid rule number'];
            }

            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "echo 'y' | {$sudoPrefix}ufw delete {$ruleNumber}";
            $result = $this->executeCommand($server, $command);

            if ($result['success'] || str_contains($result['output'], 'deleted')) {
                $this->logEvent(
                    $server,
                    SecurityEvent::TYPE_RULE_DELETED,
                    "Deleted firewall rule number: {$ruleNumber}"
                );

                return [
                    'success' => true,
                    'message' => 'Rule deleted successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete rule: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete rule: ' . $e->getMessage(),
            ];
        }
    }

    public function installUfw(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "{$sudoPrefix}apt-get update && {$sudoPrefix}apt-get install -y ufw";
            $result = $this->executeCommand($server, $command, 120);

            if ($result['success']) {
                $server->update(['ufw_installed' => true]);

                return [
                    'success' => true,
                    'message' => 'UFW installed successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to install UFW: ' . ($result['error'] ?: $result['output']),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to install UFW: ' . $e->getMessage(),
            ];
        }
    }

    public function resetToDefaults(Server $server): array
    {
        try {
            $sudoPrefix = $this->getSudoPrefix($server);
            $command = "echo 'y' | {$sudoPrefix}ufw reset";
            $result = $this->executeCommand($server, $command);

            if ($result['success']) {
                // Set default policies
                $this->executeCommand($server, "{$sudoPrefix}ufw default deny incoming");
                $this->executeCommand($server, "{$sudoPrefix}ufw default allow outgoing");

                // Allow SSH
                $this->executeCommand($server, "{$sudoPrefix}ufw allow ssh");

                $this->logEvent($server, SecurityEvent::TYPE_FIREWALL_DISABLED, 'UFW reset to defaults');

                return [
                    'success' => true,
                    'message' => 'Firewall reset to defaults. SSH allowed.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to reset firewall',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reset firewall: ' . $e->getMessage(),
            ];
        }
    }

    protected function parseUfwStatus(string $output): array
    {
        $rules = [];
        $lines = explode("\n", $output);
        $inRulesSection = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_contains($line, '---')) {
                $inRulesSection = true;
                continue;
            }

            if ($inRulesSection && !empty($line)) {
                $rules[] = $this->parseRuleLine($line);
            }
        }

        return $rules;
    }

    protected function parseNumberedRules(string $output): array
    {
        $rules = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^\[\s*(\d+)\]\s+(.+)$/', $line, $matches)) {
                $rules[] = [
                    'number' => (int) $matches[1],
                    'rule' => trim($matches[2]),
                    'parsed' => $this->parseRuleLine($matches[2]),
                ];
            }
        }

        return $rules;
    }

    protected function parseRuleLine(string $line): array
    {
        $parts = preg_split('/\s+/', $line);
        $rule = [
            'to' => '',
            'action' => '',
            'from' => '',
            'raw' => $line,
        ];

        // Simple parsing - UFW output can vary
        foreach ($parts as $i => $part) {
            if (in_array(strtoupper($part), ['ALLOW', 'DENY', 'REJECT', 'LIMIT'])) {
                $rule['action'] = strtolower($part);
                $rule['to'] = implode(' ', array_slice($parts, 0, $i));
            }
        }

        return $rule;
    }

    protected function validatePort(string $port): void
    {
        // Allow port ranges like "80:443" and service names
        if (preg_match('/^\d+$/', $port)) {
            $portNum = (int) $port;
            if ($portNum < 1 || $portNum > 65535) {
                throw new \InvalidArgumentException('Port must be between 1 and 65535');
            }
        } elseif (preg_match('/^\d+:\d+$/', $port)) {
            // Port range
            [$start, $end] = explode(':', $port);
            if ((int)$start < 1 || (int)$end > 65535 || (int)$start >= (int)$end) {
                throw new \InvalidArgumentException('Invalid port range');
            }
        } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/', $port)) {
            throw new \InvalidArgumentException('Invalid port or service name');
        }
    }

    protected function validateProtocol(string $protocol): void
    {
        if (!in_array($protocol, ['tcp', 'udp', 'any'])) {
            throw new \InvalidArgumentException('Protocol must be tcp, udp, or any');
        }
    }

    protected function validateAction(string $action): void
    {
        if (!in_array($action, ['allow', 'deny', 'reject', 'limit'])) {
            throw new \InvalidArgumentException('Action must be allow, deny, reject, or limit');
        }
    }

    protected function validateIp(string $ip): void
    {
        // Support CIDR notation
        $ipPart = explode('/', $ip)[0];
        if (!filter_var($ipPart, FILTER_VALIDATE_IP)) {
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

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "%s" 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                addslashes($remoteCommand)
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
            addslashes($remoteCommand)
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
