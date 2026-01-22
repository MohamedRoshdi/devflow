<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\ServerCommandHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use phpseclib3\Net\SSH2;

/**
 * Service for executing commands on servers with automatic local/SSH detection.
 *
 * When the target server is the current server (where DevFlow is running),
 * commands are executed locally without SSH. Otherwise, SSH is used.
 * All command executions are logged to the command history.
 */
class CommandExecutorService
{
    /**
     * Execute a command on the server
     *
     * @param array<string, mixed> $metadata Additional context to log
     */
    public function execute(
        Server $server,
        string $command,
        string $action,
        bool $useSudo = false,
        int $timeout = 60,
        array $metadata = []
    ): ServerCommandHistory {
        $executionType = $server->shouldExecuteLocally() ? 'local' : 'ssh';

        // Create history record
        $history = ServerCommandHistory::create([
            'server_id' => $server->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'command' => $this->sanitizeCommand($command),
            'execution_type' => $executionType,
            'status' => 'running',
            'started_at' => now(),
            'metadata' => $metadata,
        ]);

        $startTime = microtime(true);

        try {
            if ($executionType === 'local') {
                $result = $this->executeLocal($command, $useSudo, $timeout);
            } else {
                $result = $this->executeRemote($server, $command, $useSudo, $timeout);
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $history->update([
                'status' => $result['success'] ? 'success' : 'failed',
                'output' => $this->truncateOutput($result['output'] ?? ''),
                'error_output' => $this->truncateOutput($result['error'] ?? ''),
                'exit_code' => $result['exit_code'] ?? null,
                'duration_ms' => $durationMs,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('CommandExecutorService: Command execution failed', [
                'server_id' => $server->id,
                'action' => $action,
                'execution_type' => $executionType,
                'error' => $e->getMessage(),
            ]);

            $history->update([
                'status' => 'failed',
                'error_output' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'completed_at' => now(),
            ]);
        }

        return $history->fresh() ?? $history;
    }

    /**
     * Execute command locally
     *
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    protected function executeLocal(string $command, bool $useSudo = false, int $timeout = 60): array
    {
        if ($useSudo) {
            $command = "sudo {$command}";
        }

        $result = Process::timeout($timeout)->run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'exit_code' => $result->exitCode() ?? -1,
        ];
    }

    /**
     * Execute command remotely via SSH
     *
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    protected function executeRemote(Server $server, string $command, bool $useSudo = false, int $timeout = 60): array
    {
        // Check if password authentication should be used
        $usesPassword = $server->ssh_password !== null && strlen($server->ssh_password) > 0;

        if ($usesPassword) {
            return $this->executeWithPhpseclib($server, $command, $useSudo, $timeout);
        }

        return $this->executeWithSystemSsh($server, $command, $useSudo, $timeout);
    }

    /**
     * Execute command using phpseclib (for password authentication)
     *
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    protected function executeWithPhpseclib(Server $server, string $command, bool $useSudo, int $timeout): array
    {
        $ssh = new SSH2($server->ip_address, $server->port, $timeout);

        if (! $ssh->login($server->username, $server->ssh_password)) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'SSH authentication failed',
                'exit_code' => -1,
            ];
        }

        $isRoot = strtolower($server->username) === 'root';

        if ($useSudo && ! $isRoot) {
            $ssh->enablePTY();
            $ssh->exec("sudo -S {$command}");
            $ssh->write($server->ssh_password."\n");
            sleep(1);
            $output = $ssh->read();
        } else {
            $output = $ssh->exec($command);
        }

        $exitCode = $ssh->getExitStatus();

        return [
            'success' => $exitCode === 0 || $exitCode === false,
            'output' => $output,
            'error' => '',
            'exit_code' => is_int($exitCode) ? $exitCode : 0,
        ];
    }

    /**
     * Execute command using system SSH (for key-based authentication)
     *
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    protected function executeWithSystemSsh(Server $server, string $command, bool $useSudo, int $timeout): array
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-o BatchMode=yes',
            '-p '.$server->port,
        ];

        // Handle SSH key
        $keyFile = null;
        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.escapeshellarg($keyFile);
        }

        $isRoot = strtolower($server->username) === 'root';
        $finalCommand = ($useSudo && ! $isRoot) ? "sudo {$command}" : $command;

        $sshCommand = sprintf(
            'ssh %s %s@%s %s 2>&1',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($finalCommand)
        );

        $result = Process::timeout($timeout)->run($sshCommand);

        // Clean up temp key file
        if ($keyFile && file_exists($keyFile)) {
            unlink($keyFile);
        }

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'exit_code' => $result->exitCode() ?? -1,
        ];
    }

    /**
     * Sanitize command for logging (remove sensitive data)
     */
    protected function sanitizeCommand(string $command): string
    {
        // Remove passwords from command string
        $patterns = [
            '/(-p\s*)[\'"]?[^\s\'"]+[\'"]?/i' => '$1***',
            '/(password[=:]\s*)[\'"]?[^\s\'"]+[\'"]?/i' => '$1***',
            '/(MYSQL_PASSWORD[=]\s*)[\'"]?[^\s\'"]+[\'"]?/i' => '$1***',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $command) ?? $command;
    }

    /**
     * Truncate output to prevent huge database entries
     */
    protected function truncateOutput(string $output, int $maxLength = 65535): string
    {
        if (strlen($output) <= $maxLength) {
            return $output;
        }

        return substr($output, 0, $maxLength - 50).'... [truncated]';
    }

    /**
     * Get command history for a server
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ServerCommandHistory>
     */
    public function getHistory(Server $server, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $server->commandHistory()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent commands across all servers for current user
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ServerCommandHistory>
     */
    public function getRecentCommands(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return ServerCommandHistory::with(['server:id,name', 'user:id,name'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
