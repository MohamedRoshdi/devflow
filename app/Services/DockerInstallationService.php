<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DockerInstallationService
{
    /**
     * Install Docker on a server
     */
    public function installDocker(Server $server): array
    {
        try {
            Log::info('Starting Docker installation', ['server_id' => $server->id]);

            // Build installation script
            $installScript = $this->getDockerInstallScript($server);

            // Execute installation via SSH
            $command = $this->buildSSHCommand($server, $installScript);

            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes timeout
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();

            if ($process->isSuccessful()) {
                // Verify installation
                $verifyResult = $this->verifyDockerInstallation($server);

                if ($verifyResult['installed']) {
                    // Update server record
                    $server->update([
                        'docker_installed' => true,
                        'docker_version' => $verifyResult['version'],
                    ]);

                    Log::info('Docker installed successfully', [
                        'server_id' => $server->id,
                        'version' => $verifyResult['version'],
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Docker installed successfully!',
                        'version' => $verifyResult['version'],
                        'output' => $output,
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Docker installation completed but verification failed',
                        'output' => $output,
                        'error' => $error,
                    ];
                }
            }

            $errorMessage = !empty($error) ? $error : (!empty($output) ? $output : 'Unknown error - no output from installation script');

            Log::error('Docker installation failed', [
                'server_id' => $server->id,
                'exit_code' => $process->getExitCode(),
                'error' => $errorMessage,
                'output' => substr($output, 0, 500), // First 500 chars
            ]);

            return [
                'success' => false,
                'message' => 'Docker installation failed. ' . (strlen($errorMessage) > 200 ? substr($errorMessage, 0, 200) . '...' : $errorMessage),
                'output' => $output,
                'error' => $error,
            ];

        } catch (\Exception $e) {
            Log::error('Docker installation exception', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Docker installation
     */
    public function verifyDockerInstallation(Server $server): array
    {
        try {
            // Check Docker version
            $versionCommand = $this->buildSSHCommand($server, 'docker --version', true);
            $process = Process::fromShellCommandline($versionCommand);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());

                // Extract version number (e.g., "Docker version 24.0.7, build...")
                preg_match('/Docker version ([0-9.]+)/', $output, $matches);
                $version = $matches[1] ?? null;

                return [
                    'installed' => true,
                    'version' => $version,
                    'output' => $output,
                ];
            }

            return [
                'installed' => false,
                'version' => null,
            ];

        } catch (\Exception $e) {
            return [
                'installed' => false,
                'version' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Docker installation script
     */
    protected function getDockerInstallScript(Server $server): string
    {
        // If user has SSH password, we'll pass it to sudo commands
        $sudoPrefix = '';
        if ($server->ssh_password) {
            $sudoPrefix = "echo " . escapeshellarg($server->ssh_password) . " | sudo -S";
        } else {
            // Assume passwordless sudo or root user
            $sudoPrefix = "sudo";
        }

        return <<<BASH
#!/bin/bash
set -e

echo "=== Starting Docker Installation ==="

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=\$ID
else
    echo "Cannot detect OS"
    exit 1
fi

echo "Detected OS: \$OS"

# Update package index
{$sudoPrefix} apt-get update

# Install prerequisites
{$sudoPrefix} apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Add Docker's official GPG key and repository based on OS
{$sudoPrefix} mkdir -p /etc/apt/keyrings

if [ "\$OS" = "debian" ]; then
    echo "Installing Docker for Debian..."
    curl -fsSL https://download.docker.com/linux/debian/gpg | {$sudoPrefix} gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    {$sudoPrefix} chmod a+r /etc/apt/keyrings/docker.gpg
    echo \
      "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
      \$(lsb_release -cs) stable" | {$sudoPrefix} tee /etc/apt/sources.list.d/docker.list > /dev/null
elif [ "\$OS" = "ubuntu" ]; then
    echo "Installing Docker for Ubuntu..."
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | {$sudoPrefix} gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    {$sudoPrefix} chmod a+r /etc/apt/keyrings/docker.gpg
    echo \
      "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      \$(lsb_release -cs) stable" | {$sudoPrefix} tee /etc/apt/sources.list.d/docker.list > /dev/null
else
    echo "Unsupported OS: \$OS"
    exit 1
fi

# Update package index again
{$sudoPrefix} apt-get update

# Install Docker Engine, CLI, containerd, and plugins
{$sudoPrefix} apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start and enable Docker service
{$sudoPrefix} systemctl start docker
{$sudoPrefix} systemctl enable docker

# Add current user to docker group (optional, for non-root usage)
{$sudoPrefix} usermod -aG docker \$USER || true

# Verify installation
docker --version
docker compose version

echo "=== Docker Installation Completed ==="
BASH;
    }

    /**
     * Build SSH command for remote execution
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand, bool $suppressWarnings = false): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        // Check if password authentication should be used
        if ($server->ssh_password) {
            // Use sshpass for password authentication
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s "bash -c %s" %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remoteCommand),
                $stderrRedirect
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s "bash -c %s" %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($remoteCommand),
            $stderrRedirect
        );
    }

    /**
     * Check if Docker Compose is installed
     */
    public function checkDockerCompose(Server $server): array
    {
        try {
            $command = $this->buildSSHCommand($server, 'docker compose version', true);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());

                // Extract version
                preg_match('/v?([0-9.]+)/', $output, $matches);
                $version = $matches[1] ?? null;

                return [
                    'installed' => true,
                    'version' => $version,
                ];
            }

            return ['installed' => false];

        } catch (\Exception $e) {
            return [
                'installed' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Install Docker Compose (if not already installed with Docker)
     */
    public function installDockerCompose(Server $server): array
    {
        try {
            // Docker Compose is now included as a plugin with Docker installation
            // Just verify it's available
            $result = $this->checkDockerCompose($server);

            if ($result['installed']) {
                return [
                    'success' => true,
                    'message' => 'Docker Compose is already installed',
                    'version' => $result['version'],
                ];
            }

            // If not available, it should have been installed with Docker
            // This is a fallback message
            return [
                'success' => false,
                'message' => 'Docker Compose was not installed with Docker. Please reinstall Docker.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to check Docker Compose: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Docker system information
     */
    public function getDockerInfo(Server $server): array
    {
        try {
            $command = $this->buildSSHCommand($server, 'docker info --format "{{json .}}"', true);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $info = json_decode($output, true);

                return [
                    'success' => true,
                    'info' => $info,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get Docker info',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
