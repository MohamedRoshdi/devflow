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

            $errorMessage = ! empty($error) ? $error : (! empty($output) ? $output : 'Unknown error - no output from installation script');

            Log::error('Docker installation failed', [
                'server_id' => $server->id,
                'exit_code' => $process->getExitCode(),
                'error' => $errorMessage,
                'output' => substr($output, 0, 500), // First 500 chars
            ]);

            return [
                'success' => false,
                'message' => 'Docker installation failed. '.(strlen($errorMessage) > 200 ? substr($errorMessage, 0, 200).'...' : $errorMessage),
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
                'message' => 'Installation failed: '.$e->getMessage(),
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
        // For root users, no sudo needed. For others, use sudo with cached credentials.
        $isRoot = strtolower($server->username) === 'root';

        if ($isRoot) {
            // Root user - no sudo needed
            return $this->getDockerInstallScriptContent('');
        } elseif ($server->ssh_password) {
            // Non-root with password - cache sudo credentials first, then use sudo normally
            $escapedPassword = str_replace("'", "'\\''", $server->ssh_password);

            // The script first caches sudo credentials, then runs all commands with regular sudo
            $sudoCache = "echo '{$escapedPassword}' | sudo -S -v 2>/dev/null";

            return <<<BASH
#!/bin/bash
set -e

echo "=== Starting Docker Installation ==="
echo "Username: {$server->username}"
echo "Caching sudo credentials..."

# Cache sudo credentials (password provided via stdin)
{$sudoCache}

# Keep sudo alive in background
while true; do sudo -n true; sleep 50; kill -0 "\$\$" 2>/dev/null || exit; done &
SUDO_KEEP_ALIVE_PID=\$!

# Trap to kill the background process on exit
trap "kill \$SUDO_KEEP_ALIVE_PID 2>/dev/null" EXIT

export DEBIAN_FRONTEND=noninteractive

{$this->getDockerInstallScriptContent('sudo ')}
BASH;
        } else {
            // Non-root without password - try passwordless sudo
            return <<<BASH
#!/bin/bash
set -e

echo "=== Starting Docker Installation ==="
echo "Username: {$server->username}"
echo "Using passwordless sudo..."

export DEBIAN_FRONTEND=noninteractive

{$this->getDockerInstallScriptContent('sudo ')}
BASH;
        }
    }

    /**
     * Get the actual Docker installation commands
     */
    protected function getDockerInstallScriptContent(string $sudo): string
    {
        return <<<BASH
# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=\$ID
    VERSION_CODENAME=\${VERSION_CODENAME:-}

    # Handle Debian testing/unstable (trixie, sid) - use bookworm as fallback
    if [ "\$OS" = "debian" ]; then
        case "\$VERSION_CODENAME" in
            trixie|sid|testing|unstable)
                echo "Detected Debian testing/unstable (\$VERSION_CODENAME), using bookworm repository..."
                VERSION_CODENAME="bookworm"
                ;;
            "")
                VERSION_CODENAME="bookworm"
                ;;
        esac
    fi

    # Fallback to lsb_release if VERSION_CODENAME is empty
    if [ -z "\$VERSION_CODENAME" ]; then
        VERSION_CODENAME=\$(lsb_release -cs 2>/dev/null || echo 'stable')
    fi

    echo "Detected OS: \$OS (\$VERSION_CODENAME)"
else
    echo "Cannot detect OS"
    exit 1
fi

# Update package index
echo ""
echo "Step 1/6: Updating package index..."
{$sudo}apt-get update -qq

# Install prerequisites
echo ""
echo "Step 2/6: Installing prerequisites..."
{$sudo}apt-get install -y -qq ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key and repository based on OS
echo ""
echo "Step 3/6: Adding Docker repository..."
{$sudo}install -m 0755 -d /etc/apt/keyrings

# Remove old GPG key if exists to avoid conflicts
{$sudo}rm -f /etc/apt/keyrings/docker.gpg 2>/dev/null || true

if [ "\$OS" = "debian" ]; then
    echo "Configuring Docker repository for Debian..."
    curl -fsSL https://download.docker.com/linux/debian/gpg | {$sudo}gpg --batch --yes --dearmor -o /etc/apt/keyrings/docker.gpg
    {$sudo}chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \$VERSION_CODENAME stable" | {$sudo}tee /etc/apt/sources.list.d/docker.list > /dev/null
elif [ "\$OS" = "ubuntu" ]; then
    echo "Configuring Docker repository for Ubuntu..."
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | {$sudo}gpg --batch --yes --dearmor -o /etc/apt/keyrings/docker.gpg
    {$sudo}chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=\$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \$VERSION_CODENAME stable" | {$sudo}tee /etc/apt/sources.list.d/docker.list > /dev/null
elif [ "\$OS" = "centos" ] || [ "\$OS" = "rhel" ] || [ "\$OS" = "rocky" ] || [ "\$OS" = "almalinux" ]; then
    echo "Configuring Docker repository for RHEL-based OS..."
    {$sudo}dnf -y install dnf-plugins-core 2>/dev/null || {$sudo}yum -y install yum-utils
    {$sudo}dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo 2>/dev/null || {$sudo}yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    echo ""
    echo "Step 4/6: Installing Docker packages..."
    {$sudo}dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin 2>/dev/null || {$sudo}yum -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    echo ""
    echo "Step 5/6: Starting Docker service..."
    {$sudo}systemctl start docker
    {$sudo}systemctl enable docker
    echo ""
    echo "Step 6/6: Configuring user permissions..."
    {$sudo}usermod -aG docker \$USER 2>/dev/null || true
    echo ""
    echo "=== Verifying Installation ==="
    docker --version
    docker compose version
    echo ""
    echo "=== Docker Installation Completed Successfully ==="
    exit 0
elif [ "\$OS" = "fedora" ]; then
    echo "Configuring Docker repository for Fedora..."
    {$sudo}dnf -y install dnf-plugins-core
    {$sudo}dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo
    echo ""
    echo "Step 4/6: Installing Docker packages..."
    {$sudo}dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    echo ""
    echo "Step 5/6: Starting Docker service..."
    {$sudo}systemctl start docker
    {$sudo}systemctl enable docker
    echo ""
    echo "Step 6/6: Configuring user permissions..."
    {$sudo}usermod -aG docker \$USER 2>/dev/null || true
    echo ""
    echo "=== Verifying Installation ==="
    docker --version
    docker compose version
    echo ""
    echo "=== Docker Installation Completed Successfully ==="
    exit 0
else
    echo "Unsupported OS: \$OS"
    echo "Supported: debian, ubuntu, centos, rhel, rocky, almalinux, fedora"
    exit 1
fi

# Update package index with Docker repository (Debian/Ubuntu)
echo ""
echo "Step 4/6: Updating package index with Docker repository..."
{$sudo}apt-get update -qq

# Install Docker Engine
echo ""
echo "Step 5/6: Installing Docker packages (this may take a few minutes)..."
{$sudo}apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start and enable Docker service
echo ""
echo "Step 6/6: Starting Docker service..."
{$sudo}systemctl start docker
{$sudo}systemctl enable docker

# Add current user to docker group
{$sudo}usermod -aG docker \$USER 2>/dev/null || true

# Verify installation
echo ""
echo "=== Verifying Installation ==="
docker --version
docker compose version

echo ""
echo "=== Docker Installation Completed Successfully ==="
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
            '-p '.$server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        // For long/complex scripts, use base64 encoding to avoid escaping issues
        $isLongScript = strlen($remoteCommand) > 500 || str_contains($remoteCommand, '$(');

        if ($isLongScript) {
            // Base64 encode the script to avoid shell escaping issues
            $encodedScript = base64_encode($remoteCommand);
            $executeCommand = "echo {$encodedScript} | base64 -d | /bin/bash";
        } else {
            $executeCommand = '/bin/bash -c '.escapeshellarg($remoteCommand);
        }

        // Check if password authentication should be used
        if ($server->ssh_password) {
            // Use sshpass for password authentication
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($executeCommand),
                $stderrRedirect
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s %s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($executeCommand),
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
                'message' => 'Failed to check Docker Compose: '.$e->getMessage(),
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
