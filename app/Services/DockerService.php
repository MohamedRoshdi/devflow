<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Project;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerService
{
    public function checkDockerInstallation(Server $server): array
    {
        try {
            $command = $this->isLocalhost($server) 
                ? 'docker --version'
                : $this->buildSSHCommand($server, 'docker --version');
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                preg_match('/Docker version (.+?),/', $output, $matches);
                
                return [
                    'installed' => true,
                    'version' => $matches[1] ?? 'unknown',
                ];
            }

            return ['installed' => false];
        } catch (\Exception $e) {
            return ['installed' => false, 'error' => $e->getMessage()];
        }
    }

    protected function isLocalhost(Server $server): bool
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

    public function installDocker(Server $server): array
    {
        try {
            $script = <<<'BASH'
            curl -fsSL https://get.docker.com -o get-docker.sh && \
            sh get-docker.sh && \
            systemctl start docker && \
            systemctl enable docker && \
            usermod -aG docker $USER
            BASH;

            $command = $this->buildSSHCommand($server, $script);
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 minutes
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'output' => $process->getOutput(),
                ];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function buildContainer(Project $project): array
    {
        try {
            $server = $project->server;
            
            $projectPath = "/var/www/{$project->slug}";
            
            // Check for Dockerfile or Dockerfile.production
            $checkDockerfileCommand = "cd {$projectPath} && if [ -f Dockerfile ]; then echo 'Dockerfile'; elif [ -f Dockerfile.production ]; then echo 'Dockerfile.production'; else echo 'missing'; fi";
            $checkCommand = $this->isLocalhost($server)
                ? $checkDockerfileCommand
                : $this->buildSSHCommand($server, $checkDockerfileCommand);
            
            $checkProcess = Process::fromShellCommandline($checkCommand);
            $checkProcess->run();
            $dockerfileType = trim($checkProcess->getOutput());
            
            // Build Docker image
            if ($dockerfileType === 'Dockerfile') {
                // Use project's Dockerfile
                $buildCommand = sprintf(
                    "cd %s && docker build -t %s .",
                    $projectPath,
                    $project->slug
                );
            } elseif ($dockerfileType === 'Dockerfile.production') {
                // Use project's Dockerfile.production
                $buildCommand = sprintf(
                    "cd %s && docker build -f Dockerfile.production -t %s .",
                    $projectPath,
                    $project->slug
                );
            } else {
                // Generate Dockerfile if project doesn't have one
                $dockerfile = $this->generateDockerfile($project);
                $buildCommand = sprintf(
                    "cd %s && echo '%s' > Dockerfile && docker build -t %s .",
                    $projectPath,
                    addslashes($dockerfile),
                    $project->slug
                );
            }

            $command = $this->isLocalhost($server)
                ? $buildCommand
                : $this->buildSSHCommand($server, $buildCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'output' => $process->getOutput(),
                ];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function startContainer(Project $project): array
    {
        try {
            $server = $project->server;
            
            // First, clean up any existing container with the same name
            $cleanupResult = $this->cleanupExistingContainer($project);
            if (!$cleanupResult['success']) {
                \Log::warning("Failed to cleanup existing container for {$project->slug}: " . ($cleanupResult['error'] ?? 'Unknown error'));
            }
            
            // Use project's assigned port, or default based on project ID
            $port = $project->port ?? (8000 + $project->id);
            
            // Determine the internal container port based on the Dockerfile
            // For PHP-FPM containers, use port 9000
            // For nginx containers, use port 80
            $containerPort = $this->detectContainerPort($project);
            
            // Build environment variables string
            $envVars = $this->buildEnvironmentVariables($project);
            
            $startCommand = sprintf(
                "docker run -d --name %s -p %d:%d%s %s",
                $project->slug,
                $port,
                $containerPort,
                $envVars,
                $project->slug
            );

            $command = $this->isLocalhost($server)
                ? $startCommand
                : $this->buildSSHCommand($server, $startCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                // Update project with the port if not set
                if (!$project->port) {
                    $project->update(['port' => $port]);
                }
                
                return [
                    'success' => true,
                    'container_id' => trim($process->getOutput()),
                    'port' => $port,
                ];
            }

            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Build environment variables string for docker run command
     */
    protected function buildEnvironmentVariables(Project $project): string
    {
        $envFlags = '';
        
        // Add APP_ENV from project environment
        $appEnv = $project->environment ?? 'production';
        $envFlags .= " -e APP_ENV={$appEnv}";
        
        // Add APP_DEBUG based on environment
        $appDebug = in_array($appEnv, ['local', 'development']) ? 'true' : 'false';
        $envFlags .= " -e APP_DEBUG={$appDebug}";
        
        // Add custom environment variables
        if ($project->env_variables && is_array($project->env_variables)) {
            foreach ($project->env_variables as $key => $value) {
                // Escape special characters in value
                $escapedValue = addslashes($value);
                $envFlags .= " -e {$key}=\"{$escapedValue}\"";
            }
        }
        
        return $envFlags;
    }

    protected function cleanupExistingContainer(Project $project): array
    {
        try {
            $server = $project->server;
            
            // Stop and remove any existing container with this name
            $cleanupCommand = sprintf(
                "docker stop %s 2>/dev/null || true && docker rm -f %s 2>/dev/null || true",
                $project->slug,
                $project->slug
            );
            
            $command = $this->isLocalhost($server)
                ? $cleanupCommand
                : $this->buildSSHCommand($server, $cleanupCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => true,
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Detect the internal port used by the container
     */
    protected function detectContainerPort(Project $project): int
    {
        // For Laravel/PHP projects with Dockerfile.production, use port 80 (nginx + PHP-FPM)
        // For Node.js projects, typically use port 3000
        // For static sites with nginx, use port 80
        
        if (in_array($project->framework, ['Laravel', 'Symfony', 'CodeIgniter'])) {
            return 80; // nginx serving PHP-FPM
        }
        
        if (in_array($project->framework, ['Next.js', 'React', 'Vue', 'Nuxt.js', 'Node.js'])) {
            return 3000; // Node.js default
        }
        
        // Default to 80 for nginx-based containers
        return 80;
    }

    public function stopContainer(Project $project): array
    {
        try {
            $server = $project->server;
            
            // Stop and force remove the container to avoid "name already in use" errors
            // Using -f flag to force removal even if container is running
            $stopAndRemoveCommand = sprintf(
                "docker stop %s 2>/dev/null || true && docker rm -f %s 2>/dev/null || true",
                $project->slug,
                $project->slug
            );
            
            $command = $this->isLocalhost($server)
                ? $stopAndRemoveCommand
                : $this->buildSSHCommand($server, $stopAndRemoveCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => true, // Always return success since we use || true
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getContainerLogs(Project $project, int $lines = 100): array
    {
        try {
            $server = $project->server;
            
            $logsCommand = "docker logs --tail {$lines} " . $project->slug;
            $command = $this->isLocalhost($server)
                ? $logsCommand
                : $this->buildSSHCommand($server, $logsCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'logs' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function buildSSHCommand(Server $server, string $remoteCommand): string
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

    protected function generateDockerfile(Project $project): string
    {
        // Generate appropriate Dockerfile based on framework
        return match($project->framework) {
            'Laravel', 'laravel' => $this->getLaravelDockerfile($project),
            'Node.js', 'nodejs' => $this->getNodeDockerfile($project),
            'React', 'react' => $this->getReactDockerfile($project),
            'Vue', 'vue' => $this->getVueDockerfile($project),
            default => $this->getGenericDockerfile($project),
        };
    }

    protected function getLaravelDockerfile(Project $project): string
    {
        $phpVersion = $project->php_version ?? '8.2';
        
        return <<<DOCKERFILE
FROM php:{$phpVersion}-fpm-alpine

WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apk add --no-cache nginx supervisor curl git unzip \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev \
    && apk add --no-cache --virtual .build-deps \
        \$PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j\$(nproc) pdo pdo_mysql pcntl gd zip \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel optimization
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
DOCKERFILE;
    }

    protected function getNodeDockerfile(Project $project): string
    {
        $nodeVersion = $project->node_version ?? '20';
        
        return <<<DOCKERFILE
FROM node:{$nodeVersion}-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
DOCKERFILE;
    }

    protected function getReactDockerfile(Project $project): string
    {
        return <<<DOCKERFILE
FROM node:20-alpine as build

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/build /usr/share/nginx/html
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
DOCKERFILE;
    }

    protected function getVueDockerfile(Project $project): string
    {
        return <<<DOCKERFILE
FROM node:20-alpine as build

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
DOCKERFILE;
    }

    protected function getGenericDockerfile(Project $project): string
    {
        return <<<DOCKERFILE
FROM alpine:latest

WORKDIR /app
COPY . .

EXPOSE 80

CMD ["sh", "-c", "while true; do sleep 3600; done"]
DOCKERFILE;
    }

    // ==========================================
    // ADVANCED DOCKER MANAGEMENT FEATURES
    // ==========================================

    /**
     * Get container statistics (CPU, Memory, Network, Disk I/O)
     */
    public function getContainerStats(Project $project): array
    {
        try {
            $server = $project->server;
            
            $statsCommand = sprintf(
                "docker stats --no-stream --format '{{json .}}' %s",
                $project->slug
            );
            
            $command = $this->isLocalhost($server)
                ? $statsCommand
                : $this->buildSSHCommand($server, $statsCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $stats = json_decode($process->getOutput(), true);
                return [
                    'success' => true,
                    'stats' => $stats,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get container resource limits
     */
    public function getContainerResourceLimits(Project $project): array
    {
        try {
            $server = $project->server;
            
            $inspectCommand = sprintf(
                "docker inspect --format='{{json .HostConfig}}' %s",
                $project->slug
            );
            
            $command = $this->isLocalhost($server)
                ? $inspectCommand
                : $this->buildSSHCommand($server, $inspectCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $config = json_decode($process->getOutput(), true);
                return [
                    'success' => true,
                    'memory_limit' => $config['Memory'] ?? 0,
                    'cpu_shares' => $config['CpuShares'] ?? 0,
                    'cpu_quota' => $config['CpuQuota'] ?? 0,
                ];
            }

            return ['success' => false];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Set container resource limits
     */
    public function setContainerResourceLimits(Project $project, ?int $memoryMB = null, ?int $cpuShares = null): array
    {
        try {
            $server = $project->server;
            
            $updateCommand = "docker update";
            if ($memoryMB !== null) {
                $updateCommand .= " --memory={$memoryMB}m";
            }
            if ($cpuShares !== null) {
                $updateCommand .= " --cpu-shares={$cpuShares}";
            }
            $updateCommand .= " " . $project->slug;
            
            $command = $this->isLocalhost($server)
                ? $updateCommand
                : $this->buildSSHCommand($server, $updateCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // VOLUME MANAGEMENT
    // ==========================================

    /**
     * List all Docker volumes on server
     */
    public function listVolumes(Server $server): array
    {
        try {
            $volumesCommand = "docker volume ls --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $volumesCommand
                : $this->buildSSHCommand($server, $volumesCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = array_filter(explode("\n", $output));
                $volumes = array_map(fn($line) => json_decode($line, true), $lines);
                
                return [
                    'success' => true,
                    'volumes' => $volumes,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a Docker volume
     */
    public function createVolume(Server $server, string $name, array $options = []): array
    {
        try {
            $createCommand = "docker volume create";
            
            if (isset($options['driver'])) {
                $createCommand .= " --driver={$options['driver']}";
            }
            
            foreach ($options['labels'] ?? [] as $key => $value) {
                $createCommand .= " --label={$key}={$value}";
            }
            
            $createCommand .= " {$name}";
            
            $command = $this->isLocalhost($server)
                ? $createCommand
                : $this->buildSSHCommand($server, $createCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'volume_name' => trim($process->getOutput()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker volume
     */
    public function deleteVolume(Server $server, string $name): array
    {
        try {
            $deleteCommand = "docker volume rm {$name}";
            
            $command = $this->isLocalhost($server)
                ? $deleteCommand
                : $this->buildSSHCommand($server, $deleteCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get volume details and usage
     */
    public function getVolumeInfo(Server $server, string $name): array
    {
        try {
            $inspectCommand = "docker volume inspect {$name}";
            
            $command = $this->isLocalhost($server)
                ? $inspectCommand
                : $this->buildSSHCommand($server, $inspectCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $info = json_decode($process->getOutput(), true);
                return [
                    'success' => true,
                    'volume' => $info[0] ?? null,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // NETWORK MANAGEMENT
    // ==========================================

    /**
     * List all Docker networks on server
     */
    public function listNetworks(Server $server): array
    {
        try {
            $networksCommand = "docker network ls --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $networksCommand
                : $this->buildSSHCommand($server, $networksCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = array_filter(explode("\n", $output));
                $networks = array_map(fn($line) => json_decode($line, true), $lines);
                
                return [
                    'success' => true,
                    'networks' => $networks,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a Docker network
     */
    public function createNetwork(Server $server, string $name, string $driver = 'bridge'): array
    {
        try {
            $createCommand = "docker network create --driver={$driver} {$name}";
            
            $command = $this->isLocalhost($server)
                ? $createCommand
                : $this->buildSSHCommand($server, $createCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'network_id' => trim($process->getOutput()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker network
     */
    public function deleteNetwork(Server $server, string $name): array
    {
        try {
            $deleteCommand = "docker network rm {$name}";
            
            $command = $this->isLocalhost($server)
                ? $deleteCommand
                : $this->buildSSHCommand($server, $deleteCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Connect container to network
     */
    public function connectContainerToNetwork(Project $project, string $networkName): array
    {
        try {
            $server = $project->server;
            $connectCommand = "docker network connect {$networkName} {$project->slug}";
            
            $command = $this->isLocalhost($server)
                ? $connectCommand
                : $this->buildSSHCommand($server, $connectCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Disconnect container from network
     */
    public function disconnectContainerFromNetwork(Project $project, string $networkName): array
    {
        try {
            $server = $project->server;
            $disconnectCommand = "docker network disconnect {$networkName} {$project->slug}";
            
            $command = $this->isLocalhost($server)
                ? $disconnectCommand
                : $this->buildSSHCommand($server, $disconnectCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // IMAGE MANAGEMENT
    // ==========================================

    /**
     * List all Docker images on server
     */
    public function listImages(Server $server): array
    {
        try {
            $imagesCommand = "docker images --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $imagesCommand
                : $this->buildSSHCommand($server, $imagesCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = array_filter(explode("\n", $output));
                $images = array_map(fn($line) => json_decode($line, true), $lines);
                
                return [
                    'success' => true,
                    'images' => $images,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List Docker images related to a specific project
     */
    public function listProjectImages(Project $project): array
    {
        try {
            $server = $project->server;
            $imagesCommand = "docker images --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $imagesCommand
                : $this->buildSSHCommand($server, $imagesCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = array_filter(explode("\n", $output));
                $allImages = array_map(fn($line) => json_decode($line, true), $lines);
                
                // Filter images related to this project (by slug or tag)
                $projectImages = array_filter($allImages, function($image) use ($project) {
                    $repository = $image['Repository'] ?? '';
                    $tag = $image['Tag'] ?? '';
                    
                    // Match images that contain the project slug in the repository name or tag
                    return stripos($repository, $project->slug) !== false || 
                           stripos($tag, $project->slug) !== false ||
                           $repository === $project->slug;
                });
                
                return [
                    'success' => true,
                    'images' => array_values($projectImages), // Re-index array
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get container status for a project
     */
    public function getContainerStatus(Project $project): array
    {
        try {
            $server = $project->server;
            
            $statusCommand = sprintf(
                "docker ps -a --filter name=%s --format '{{json .}}'",
                $project->slug
            );
            
            $command = $this->isLocalhost($server)
                ? $statusCommand
                : $this->buildSSHCommand($server, $statusCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (!empty($output)) {
                    $container = json_decode($output, true);
                    return [
                        'success' => true,
                        'container' => $container,
                        'exists' => true,
                    ];
                }
                
                return [
                    'success' => true,
                    'container' => null,
                    'exists' => false,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a Docker image
     */
    public function deleteImage(Server $server, string $imageId): array
    {
        try {
            $deleteCommand = "docker rmi {$imageId}";
            
            $command = $this->isLocalhost($server)
                ? $deleteCommand
                : $this->buildSSHCommand($server, $deleteCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Prune unused Docker images
     */
    public function pruneImages(Server $server, bool $all = false): array
    {
        try {
            $pruneCommand = "docker image prune -f";
            if ($all) {
                $pruneCommand .= " -a";
            }
            
            $command = $this->isLocalhost($server)
                ? $pruneCommand
                : $this->buildSSHCommand($server, $pruneCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pull Docker image from registry
     */
    public function pullImage(Server $server, string $imageName): array
    {
        try {
            $pullCommand = "docker pull {$imageName}";
            
            $command = $this->isLocalhost($server)
                ? $pullCommand
                : $this->buildSSHCommand($server, $pullCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes for large images
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // DOCKER COMPOSE
    // ==========================================

    /**
     * Deploy with docker-compose
     */
    public function deployWithCompose(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            
            $composeCommand = "cd {$projectPath} && docker-compose up -d --build";
            
            $command = $this->isLocalhost($server)
                ? $composeCommand
                : $this->buildSSHCommand($server, $composeCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(1200); // 20 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stop docker-compose services
     */
    public function stopCompose(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            
            $composeCommand = "cd {$projectPath} && docker-compose down";
            
            $command = $this->isLocalhost($server)
                ? $composeCommand
                : $this->buildSSHCommand($server, $composeCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get docker-compose service status
     */
    public function getComposeStatus(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            
            $composeCommand = "cd {$projectPath} && docker-compose ps --format json";
            
            $command = $this->isLocalhost($server)
                ? $composeCommand
                : $this->buildSSHCommand($server, $composeCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $services = json_decode($output, true);
                
                return [
                    'success' => true,
                    'services' => $services ?? [],
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // CONTAINER EXECUTION
    // ==========================================

    /**
     * Execute command in container
     */
    public function execInContainer(Project $project, string $command, bool $interactive = false): array
    {
        try {
            $server = $project->server;
            
            $execCommand = sprintf(
                "docker exec %s %s %s",
                $interactive ? '-it' : '',
                $project->slug,
                $command
            );
            
            $cmd = $this->isLocalhost($server)
                ? $execCommand
                : $this->buildSSHCommand($server, $execCommand);
            
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(300); // 5 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get list of processes in container
     */
    public function getContainerProcesses(Project $project): array
    {
        try {
            $server = $project->server;
            
            $psCommand = "docker top {$project->slug}";
            
            $command = $this->isLocalhost($server)
                ? $psCommand
                : $this->buildSSHCommand($server, $psCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'processes' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // BACKUP & RESTORE
    // ==========================================

    /**
     * Export container as image (backup)
     */
    public function exportContainer(Project $project, string $backupName = null): array
    {
        try {
            $server = $project->server;
            $backupName = $backupName ?? "{$project->slug}-backup-" . date('Y-m-d-H-i-s');
            
            $commitCommand = "docker commit {$project->slug} {$backupName}";
            
            $command = $this->isLocalhost($server)
                ? $commitCommand
                : $this->buildSSHCommand($server, $commitCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'backup_name' => $backupName,
                    'image_id' => trim($process->getOutput()),
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save image to tar file
     */
    public function saveImageToFile(Server $server, string $imageName, string $filePath): array
    {
        try {
            $saveCommand = "docker save -o {$filePath} {$imageName}";
            
            $command = $this->isLocalhost($server)
                ? $saveCommand
                : $this->buildSSHCommand($server, $saveCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load image from tar file
     */
    public function loadImageFromFile(Server $server, string $filePath): array
    {
        try {
            $loadCommand = "docker load -i {$filePath}";
            
            $command = $this->isLocalhost($server)
                ? $loadCommand
                : $this->buildSSHCommand($server, $loadCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // DOCKER REGISTRY
    // ==========================================

    /**
     * Login to Docker registry
     */
    public function registryLogin(Server $server, string $registry, string $username, string $password): array
    {
        try {
            $loginCommand = sprintf(
                "echo '%s' | docker login %s -u %s --password-stdin",
                $password,
                $registry,
                $username
            );
            
            $command = $this->isLocalhost($server)
                ? $loginCommand
                : $this->buildSSHCommand($server, $loginCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Push image to registry
     */
    public function pushImage(Server $server, string $imageName): array
    {
        try {
            $pushCommand = "docker push {$imageName}";
            
            $command = $this->isLocalhost($server)
                ? $pushCommand
                : $this->buildSSHCommand($server, $pushCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(600); // 10 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Tag image for registry
     */
    public function tagImage(Server $server, string $sourceImage, string $targetImage): array
    {
        try {
            $tagCommand = "docker tag {$sourceImage} {$targetImage}";
            
            $command = $this->isLocalhost($server)
                ? $tagCommand
                : $this->buildSSHCommand($server, $tagCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // SYSTEM MANAGEMENT
    // ==========================================

    /**
     * Get Docker system info
     */
    public function getSystemInfo(Server $server): array
    {
        try {
            $infoCommand = "docker info --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $infoCommand
                : $this->buildSSHCommand($server, $infoCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $info = json_decode($process->getOutput(), true);
                return [
                    'success' => true,
                    'info' => $info,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean up Docker system (remove unused data)
     */
    public function systemPrune(Server $server, bool $volumes = false): array
    {
        try {
            $pruneCommand = "docker system prune -f";
            if ($volumes) {
                $pruneCommand .= " --volumes";
            }
            
            $command = $this->isLocalhost($server)
                ? $pruneCommand
                : $this->buildSSHCommand($server, $pruneCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 minutes
            $process->run();

            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Docker disk usage
     */
    public function getDiskUsage(Server $server): array
    {
        try {
            $dfCommand = "docker system df --format '{{json .}}'";
            
            $command = $this->isLocalhost($server)
                ? $dfCommand
                : $this->buildSSHCommand($server, $dfCommand);
            
            $process = Process::fromShellCommandline($command);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = array_filter(explode("\n", $output));
                $usage = array_map(fn($line) => json_decode($line, true), $lines);
                
                return [
                    'success' => true,
                    'usage' => $usage,
                ];
            }

            return ['success' => false, 'error' => $process->getErrorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

