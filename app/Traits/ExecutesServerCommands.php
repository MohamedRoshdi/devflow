<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Server;
use Illuminate\Support\Facades\Process;

trait ExecutesServerCommands
{
    /**
     * Execute a command on the server (local or remote)
     *
     * @return array{success: bool, output: string, error: string}
     */
    protected function executeCommand(Server $server, string $command, int $timeout = 30): array
    {
        try {
            $isLocalhost = $this->isLocalhost($server->ip_address) || $server->is_current_server;

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
            if ($keyFile !== false) {
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i '.$keyFile;
            }
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
