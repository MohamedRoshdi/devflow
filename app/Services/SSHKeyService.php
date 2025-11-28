<?php

namespace App\Services;

use App\Models\SSHKey;
use App\Models\Server;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;

class SSHKeyService
{
    /**
     * Generate a new SSH key pair
     *
     * @param string $type Key type (rsa, ed25519, ecdsa)
     * @param string $comment Optional comment for the key
     * @return array{success: bool, public_key?: string, private_key?: string, fingerprint?: string, error?: string}
     */
    public function generateKeyPair(string $type = 'ed25519', string $comment = ''): array
    {
        try {
            // Create temporary directory for key generation
            $tempDir = sys_get_temp_dir() . '/ssh_keys_' . uniqid();
            mkdir($tempDir, 0700, true);
            $keyPath = $tempDir . '/id_' . $type;

            // Prepare ssh-keygen command based on key type
            $command = match($type) {
                'ed25519' => [
                    'ssh-keygen',
                    '-t', 'ed25519',
                    '-f', $keyPath,
                    '-N', '', // No passphrase
                    '-C', $comment ?: 'devflow-' . date('Y-m-d-H-i-s'),
                ],
                'rsa' => [
                    'ssh-keygen',
                    '-t', 'rsa',
                    '-b', '4096',
                    '-f', $keyPath,
                    '-N', '',
                    '-C', $comment ?: 'devflow-' . date('Y-m-d-H-i-s'),
                ],
                'ecdsa' => [
                    'ssh-keygen',
                    '-t', 'ecdsa',
                    '-b', '521',
                    '-f', $keyPath,
                    '-N', '',
                    '-C', $comment ?: 'devflow-' . date('Y-m-d-H-i-s'),
                ],
                default => throw new \InvalidArgumentException("Unsupported key type: {$type}"),
            };

            // Generate key pair
            $process = new Process($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Failed to generate SSH key: ' . $process->getErrorOutput());
            }

            // Read generated keys
            $privateKey = file_get_contents($keyPath);
            $publicKey = file_get_contents($keyPath . '.pub');

            if (!$privateKey || !$publicKey) {
                throw new \RuntimeException('Failed to read generated SSH keys');
            }

            // Get fingerprint
            $fingerprint = $this->getFingerprint($publicKey);

            // Cleanup temp files
            @unlink($keyPath);
            @unlink($keyPath . '.pub');
            @rmdir($tempDir);

            return [
                'success' => true,
                'public_key' => trim($publicKey),
                'private_key' => $privateKey,
                'fingerprint' => $fingerprint,
            ];

        } catch (\Exception $e) {
            // Cleanup on error
            if (isset($tempDir) && is_dir($tempDir)) {
                @unlink($keyPath ?? '');
                @unlink(($keyPath ?? '') . '.pub');
                @rmdir($tempDir);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import an existing SSH key pair
     *
     * @param string $publicKey The public key content
     * @param string $privateKey The private key content
     * @return array{success: bool, fingerprint?: string, type?: string, error?: string}
     */
    public function importKey(string $publicKey, string $privateKey): array
    {
        try {
            // Validate public key format
            $publicKey = trim($publicKey);
            if (!preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp\d+)\s+/', $publicKey)) {
                throw new \InvalidArgumentException('Invalid public key format');
            }

            // Validate private key format
            $privateKey = trim($privateKey);
            if (!str_contains($privateKey, '-----BEGIN') || !str_contains($privateKey, 'PRIVATE KEY-----')) {
                throw new \InvalidArgumentException('Invalid private key format');
            }

            // Determine key type
            $type = 'rsa';
            if (str_starts_with($publicKey, 'ssh-ed25519')) {
                $type = 'ed25519';
            } elseif (str_starts_with($publicKey, 'ecdsa-sha2-nistp')) {
                $type = 'ecdsa';
            }

            // Get fingerprint
            $fingerprint = $this->getFingerprint($publicKey);

            return [
                'success' => true,
                'fingerprint' => $fingerprint,
                'type' => $type,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate SSH key fingerprint
     *
     * @param string $publicKey The public key content
     * @return string The fingerprint in MD5 format
     */
    public function getFingerprint(string $publicKey): string
    {
        try {
            // Create temporary file for public key
            $tempFile = tempnam(sys_get_temp_dir(), 'ssh_pub_');
            file_put_contents($tempFile, trim($publicKey));

            // Get fingerprint using ssh-keygen
            $process = new Process([
                'ssh-keygen',
                '-l',
                '-E', 'md5',
                '-f', $tempFile,
            ]);
            $process->run();

            @unlink($tempFile);

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Failed to calculate fingerprint: ' . $process->getErrorOutput());
            }

            $output = $process->getOutput();

            // Extract fingerprint from output (format: "2048 MD5:xx:xx:xx... comment (RSA)")
            if (preg_match('/MD5:([a-f0-9:]+)/i', $output, $matches)) {
                return $matches[1];
            }

            // Fallback: calculate SHA256 hash
            $parts = explode(' ', trim($publicKey), 3);
            if (count($parts) >= 2) {
                $keyData = base64_decode($parts[1]);
                return substr(hash('sha256', $keyData), 0, 32);
            }

            throw new \RuntimeException('Could not extract fingerprint from ssh-keygen output');

        } catch (\Exception $e) {
            // Fallback to simple hash
            return substr(hash('sha256', trim($publicKey)), 0, 32);
        }
    }

    /**
     * Deploy SSH key to a server's authorized_keys
     *
     * @param SSHKey $key The SSH key to deploy
     * @param Server $server The target server
     * @return array{success: bool, message?: string, error?: string}
     */
    public function deployKeyToServer(SSHKey $key, Server $server): array
    {
        try {
            $publicKey = trim($key->public_key);

            // Check if server is localhost
            if ($this->isLocalhost($server)) {
                return $this->deployToLocalhost($publicKey, $server);
            }

            // Deploy to remote server via SSH
            return $this->deployToRemoteServer($publicKey, $server);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove SSH key from server's authorized_keys
     *
     * @param SSHKey $key The SSH key to remove
     * @param Server $server The target server
     * @return array{success: bool, message?: string, error?: string}
     */
    public function removeKeyFromServer(SSHKey $key, Server $server): array
    {
        try {
            $fingerprint = $key->fingerprint;

            // Check if server is localhost
            if ($this->isLocalhost($server)) {
                return $this->removeFromLocalhost($key->public_key, $server);
            }

            // Remove from remote server via SSH
            return $this->removeFromRemoteServer($key->public_key, $server);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deploy key to localhost
     */
    private function deployToLocalhost(string $publicKey, Server $server): array
    {
        $username = $server->username;
        $homeDir = $username === 'root' ? '/root' : "/home/{$username}";
        $authorizedKeysPath = "{$homeDir}/.ssh/authorized_keys";
        $sshDir = "{$homeDir}/.ssh";

        // Create .ssh directory if it doesn't exist
        if (!is_dir($sshDir)) {
            mkdir($sshDir, 0700, true);
            if ($username !== 'root') {
                chown($sshDir, $username);
            }
        }

        // Check if key already exists
        if (file_exists($authorizedKeysPath)) {
            $existing = file_get_contents($authorizedKeysPath);
            if (str_contains($existing, $publicKey)) {
                return [
                    'success' => true,
                    'message' => 'SSH key already deployed to this server',
                ];
            }
        }

        // Append public key to authorized_keys
        $result = file_put_contents($authorizedKeysPath, "\n" . $publicKey . "\n", FILE_APPEND);

        if ($result === false) {
            throw new \RuntimeException('Failed to write to authorized_keys file');
        }

        // Set proper permissions
        chmod($authorizedKeysPath, 0600);
        if ($username !== 'root') {
            chown($authorizedKeysPath, $username);
        }

        return [
            'success' => true,
            'message' => 'SSH key deployed successfully to server',
        ];
    }

    /**
     * Deploy key to remote server
     */
    private function deployToRemoteServer(string $publicKey, Server $server): array
    {
        // Build SSH command to add key
        $escapedKey = addslashes($publicKey);
        $remoteCommand = "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '{$escapedKey}' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys";

        $sshCommand = $this->buildSSHCommand($server, $remoteCommand);

        $process = Process::fromShellCommandline($sshCommand);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to deploy key to server: ' . $process->getErrorOutput());
        }

        return [
            'success' => true,
            'message' => 'SSH key deployed successfully to remote server',
        ];
    }

    /**
     * Remove key from localhost
     */
    private function removeFromLocalhost(string $publicKey, Server $server): array
    {
        $username = $server->username;
        $homeDir = $username === 'root' ? '/root' : "/home/{$username}";
        $authorizedKeysPath = "{$homeDir}/.ssh/authorized_keys";

        if (!file_exists($authorizedKeysPath)) {
            return [
                'success' => true,
                'message' => 'Key not found in authorized_keys',
            ];
        }

        // Read current authorized_keys
        $lines = file($authorizedKeysPath, FILE_IGNORE_NEW_LINES);
        $publicKey = trim($publicKey);

        // Filter out the key to remove
        $filtered = array_filter($lines, function($line) use ($publicKey) {
            return trim($line) !== $publicKey;
        });

        // Write back filtered keys
        file_put_contents($authorizedKeysPath, implode("\n", $filtered) . "\n");

        return [
            'success' => true,
            'message' => 'SSH key removed from server',
        ];
    }

    /**
     * Remove key from remote server
     */
    private function removeFromRemoteServer(string $publicKey, Server $server): array
    {
        $escapedKey = addslashes($publicKey);
        $remoteCommand = "sed -i '\\|{$escapedKey}|d' ~/.ssh/authorized_keys";

        $sshCommand = $this->buildSSHCommand($server, $remoteCommand);

        $process = Process::fromShellCommandline($sshCommand);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to remove key from server: ' . $process->getErrorOutput());
        }

        return [
            'success' => true,
            'message' => 'SSH key removed from remote server',
        ];
    }

    /**
     * Check if server is localhost
     */
    private function isLocalhost(Server $server): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($server->ip_address, $localIPs)) {
            return true;
        }

        // Check if IP matches server's own IP
        try {
            $publicIP = trim(file_get_contents('http://api.ipify.org'));
            if ($server->ip_address === $publicIP) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return false;
    }

    /**
     * Build SSH command for remote execution
     */
    private function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p ' . $server->port,
        ];

        if ($server->ssh_key) {
            // Save SSH key to temp file
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "%s"',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            addslashes($remoteCommand)
        );
    }
}
