<?php

declare(strict_types=1);

namespace App\Services\SSH;

use App\Models\Server;
use InvalidArgumentException;
use RuntimeException;

/**
 * Builder class for constructing secure SSH commands.
 *
 * This class centralizes SSH command building logic to ensure
 * consistent security practices and proper argument escaping.
 */
class SSHCommandBuilder
{
    private string $host;
    private string $username = 'root';
    private int $port = 22;
    private ?string $keyFile = null;
    private ?string $keyContent = null;
    private ?string $password = null;
    private int $timeout = 30;
    private bool $strictHostKeyChecking = false;
    private string $knownHostsFile = '/dev/null';
    private ?string $tempKeyFile = null;

    /**
     * @var array<string, string> Additional SSH options
     */
    private array $options = [];

    /**
     * Create builder from a Server model.
     */
    public static function forServer(Server $server): self
    {
        $builder = new self();
        $builder->host = $server->ip_address;
        $builder->username = $server->username ?? 'root';
        $builder->port = (int) ($server->port ?? 22);

        if ($server->ssh_key) {
            $builder->keyContent = $server->ssh_key;
        }

        if ($server->ssh_password) {
            $builder->password = $server->ssh_password;
        }

        return $builder;
    }

    /**
     * Create builder for a host.
     */
    public static function forHost(string $host): self
    {
        $builder = new self();
        $builder->host = $host;

        return $builder;
    }

    /**
     * Set the SSH username.
     */
    public function asUser(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the SSH port.
     */
    public function onPort(int $port): self
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Port must be between 1 and 65535');
        }
        $this->port = $port;

        return $this;
    }

    /**
     * Set the private key file path.
     */
    public function withKeyFile(string $keyFile): self
    {
        $this->keyFile = $keyFile;

        return $this;
    }

    /**
     * Set the private key content directly.
     */
    public function withKeyContent(string $keyContent): self
    {
        $this->keyContent = $keyContent;

        return $this;
    }

    /**
     * Set connection timeout.
     */
    public function withTimeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);

        return $this;
    }

    /**
     * Enable strict host key checking (recommended for production).
     */
    public function withStrictHostKeyChecking(bool $enabled = true): self
    {
        $this->strictHostKeyChecking = $enabled;

        return $this;
    }

    /**
     * Set known hosts file path.
     */
    public function withKnownHostsFile(string $path): self
    {
        $this->knownHostsFile = $path;

        return $this;
    }

    /**
     * Add a custom SSH option.
     */
    public function withOption(string $option, string $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * Build the SSH command for remote execution.
     *
     * @param string $remoteCommand The command to execute on the remote server
     * @return array{command: string, key_file: string|null} The SSH command and optional temp key file
     */
    public function buildCommand(string $remoteCommand): array
    {
        $this->validateConfiguration();
        $this->createTempKeyFileIfNeeded();

        $sshOptions = $this->buildOptions();
        $userHost = escapeshellarg($this->username) . '@' . escapeshellarg($this->host);

        $command = sprintf(
            'ssh %s %s %s',
            implode(' ', $sshOptions),
            $userHost,
            escapeshellarg($remoteCommand)
        );

        return [
            'command' => $command,
            'key_file' => $this->tempKeyFile,
        ];
    }

    /**
     * Build SCP command for file transfer.
     *
     * @param string $source Source path (can be local or remote)
     * @param string $destination Destination path (can be local or remote)
     * @param bool $recursive Whether to copy directories recursively
     * @return array{command: string, key_file: string|null}
     */
    public function buildScpCommand(string $source, string $destination, bool $recursive = false): array
    {
        $this->validateConfiguration();
        $this->createTempKeyFileIfNeeded();

        $scpOptions = $this->buildOptions('-P');

        if ($recursive) {
            array_unshift($scpOptions, '-r');
        }

        $command = sprintf(
            'scp %s %s %s',
            implode(' ', $scpOptions),
            escapeshellarg($source),
            escapeshellarg($destination)
        );

        return [
            'command' => $command,
            'key_file' => $this->tempKeyFile,
        ];
    }

    /**
     * Get the remote host string (user@host).
     */
    public function getRemoteHost(): string
    {
        return "{$this->username}@{$this->host}";
    }

    /**
     * Securely clean up the temporary key file.
     *
     * This should be called after the SSH command has been executed.
     */
    public function cleanup(): void
    {
        if ($this->tempKeyFile !== null && file_exists($this->tempKeyFile)) {
            // Overwrite with zeros before deletion for security
            $size = filesize($this->tempKeyFile);
            if ($size !== false && $size > 0) {
                file_put_contents($this->tempKeyFile, str_repeat("\0", $size));
            }
            @unlink($this->tempKeyFile);
            $this->tempKeyFile = null;
        }
    }

    /**
     * Destructor ensures cleanup of temp files.
     */
    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Validate the builder configuration.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->host)) {
            throw new InvalidArgumentException('SSH host is required');
        }

        if (empty($this->username)) {
            throw new InvalidArgumentException('SSH username is required');
        }
    }

    /**
     * Create a temporary key file if key content is provided.
     */
    private function createTempKeyFileIfNeeded(): void
    {
        if ($this->keyContent !== null && $this->tempKeyFile === null) {
            $tempFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            if ($tempFile === false) {
                throw new RuntimeException('Failed to create temporary SSH key file');
            }

            file_put_contents($tempFile, $this->keyContent);
            chmod($tempFile, 0600);
            $this->tempKeyFile = $tempFile;
        }
    }

    /**
     * Build SSH options array.
     *
     * @param string $portFlag Port flag (-p for ssh, -P for scp)
     * @return array<string>
     */
    private function buildOptions(string $portFlag = '-p'): array
    {
        $options = [];

        // Port
        $options[] = $portFlag . ' ' . (int) $this->port;

        // Connection timeout
        $options[] = '-o ConnectTimeout=' . (int) $this->timeout;

        // Host key checking
        if ($this->strictHostKeyChecking) {
            $options[] = '-o StrictHostKeyChecking=yes';
        } else {
            $options[] = '-o StrictHostKeyChecking=no';
            $options[] = '-o UserKnownHostsFile=' . escapeshellarg($this->knownHostsFile);
        }

        // Key file
        if ($this->tempKeyFile !== null) {
            $options[] = '-i ' . escapeshellarg($this->tempKeyFile);
        } elseif ($this->keyFile !== null) {
            $options[] = '-i ' . escapeshellarg($this->keyFile);
        }

        // Batch mode (non-interactive)
        $options[] = '-o BatchMode=yes';

        // Custom options
        foreach ($this->options as $option => $value) {
            $options[] = '-o ' . escapeshellarg($option) . '=' . escapeshellarg($value);
        }

        return $options;
    }
}
