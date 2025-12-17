<?php

declare(strict_types=1);

namespace App\Services\Docker\Concerns;

use App\Models\Server;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

/**
 * Trait for executing commands on remote servers via SSH.
 *
 * Provides methods for secure SSH command execution with:
 * - Automatic localhost detection
 * - SSH key file caching and cleanup
 * - Configurable timeouts
 * - Input support for stdin
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
trait ExecutesRemoteCommands
{
    /**
     * Cache of temporary SSH key files per server ID
     *
     * @var array<int, string>
     */
    protected array $sshKeyFiles = [];

    /**
     * Cleanup temporary SSH key files on destruction
     */
    public function __destruct()
    {
        foreach ($this->sshKeyFiles as $keyFile) {
            if (file_exists($keyFile)) {
                @unlink($keyFile);
            }
        }
    }

    /**
     * Check if server is localhost
     *
     * Note: Only checks for common localhost IPs. External IP lookup removed
     * for performance and testability reasons.
     */
    protected function isLocalhost(Server $server): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        return in_array($server->ip_address, $localIPs);
    }

    /**
     * Execute a remote command via SSH or locally depending on server configuration
     *
     * @param Server $server The server to execute the command on
     * @param string $command The command to execute
     * @param bool $throwOnError Whether to throw exception on command failure
     * @return ProcessResult The completed process with results
     * @throws \RuntimeException When command fails and $throwOnError is true
     */
    protected function executeRemoteCommand(Server $server, string $command, bool $throwOnError = true): ProcessResult
    {
        $finalCommand = $this->isLocalhost($server)
            ? $command
            : $this->buildSSHCommand($server, $command);

        $result = Process::run($finalCommand);

        if ($throwOnError && ! $result->successful()) {
            throw new \RuntimeException(
                sprintf(
                    'Remote command failed on server %s: %s. Error: %s',
                    $server->name,
                    $command,
                    $result->errorOutput() ?: $result->output()
                )
            );
        }

        return $result;
    }

    /**
     * Execute a remote command and return only the output string
     *
     * @param Server $server The server to execute the command on
     * @param string $command The command to execute
     * @param bool $throwOnError Whether to throw exception on command failure
     * @return string The command output
     * @throws \RuntimeException When command fails and $throwOnError is true
     */
    protected function getRemoteOutput(Server $server, string $command, bool $throwOnError = true): string
    {
        $result = $this->executeRemoteCommand($server, $command, $throwOnError);

        return $result->output();
    }

    /**
     * Execute a remote command with a custom timeout
     *
     * @param Server $server The server to execute the command on
     * @param string $command The command to execute
     * @param int $timeout Timeout in seconds
     * @param bool $throwOnError Whether to throw exception on command failure
     * @return ProcessResult The completed process with results
     * @throws \RuntimeException When command fails and $throwOnError is true
     */
    protected function executeRemoteCommandWithTimeout(Server $server, string $command, int $timeout, bool $throwOnError = true): ProcessResult
    {
        $finalCommand = $this->isLocalhost($server)
            ? $command
            : $this->buildSSHCommand($server, $command);

        $result = Process::timeout($timeout)->run($finalCommand);

        if ($throwOnError && ! $result->successful()) {
            throw new \RuntimeException(
                sprintf(
                    'Remote command failed on server %s: %s. Error: %s',
                    $server->name,
                    $command,
                    $result->errorOutput() ?: $result->output()
                )
            );
        }

        return $result;
    }

    /**
     * Execute a remote command with input (e.g., for password stdin)
     *
     * @param Server $server The server to execute the command on
     * @param string $command The command to execute
     * @param string $input Input to pass to the command via stdin
     * @param bool $throwOnError Whether to throw exception on command failure
     * @return ProcessResult The completed process with results
     * @throws \RuntimeException When command fails and $throwOnError is true
     */
    protected function executeRemoteCommandWithInput(Server $server, string $command, string $input, bool $throwOnError = true): ProcessResult
    {
        $finalCommand = $this->isLocalhost($server)
            ? $command
            : $this->buildSSHCommand($server, $command);

        $result = Process::input($input)->run($finalCommand);

        if ($throwOnError && ! $result->successful()) {
            throw new \RuntimeException(
                sprintf(
                    'Remote command failed on server %s: %s. Error: %s',
                    $server->name,
                    $command,
                    $result->errorOutput() ?: $result->output()
                )
            );
        }

        return $result;
    }

    /**
     * Build SSH command for remote execution
     *
     * Security: Temp files are cached per server and cleaned up in __destruct()
     * Additionally, a shutdown function provides cleanup on unexpected termination
     *
     * @param Server $server The server to connect to
     * @param string $remoteCommand The command to execute remotely
     * @return string The complete SSH command string
     * @throws \RuntimeException When SSH key file creation fails
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p '.$server->port,
        ];

        if ($server->ssh_key) {
            // Reuse cached temp file if available for this server
            if (! isset($this->sshKeyFiles[$server->id])) {
                // Create temporary SSH key file
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                if ($keyFile === false) {
                    throw new \RuntimeException('Failed to create temporary SSH key file');
                }

                // Security: Set restrictive permissions before writing sensitive data
                chmod($keyFile, 0600);

                // Write SSH key content
                file_put_contents($keyFile, $server->ssh_key);

                // Cache the key file path
                $this->sshKeyFiles[$server->id] = $keyFile;

                // Security: Register shutdown function as additional cleanup protection
                register_shutdown_function(function () use ($keyFile): void {
                    if (file_exists($keyFile)) {
                        @unlink($keyFile);
                    }
                });
            }

            $sshOptions[] = '-i '.$this->sshKeyFiles[$server->id];
        }

        return sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($remoteCommand)
        );
    }
}
