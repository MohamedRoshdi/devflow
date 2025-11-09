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
            
            // Use project's assigned port, or default based on project ID
            $port = $project->port ?? (8000 + $project->id);
            
            // Determine the internal container port based on the Dockerfile
            // For PHP-FPM containers, use port 9000
            // For nginx containers, use port 80
            $containerPort = $this->detectContainerPort($project);
            
            $startCommand = sprintf(
                "docker run -d --name %s -p %d:%d %s",
                $project->slug,
                $port,
                $containerPort,
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
            
            // Stop and remove the container to avoid "name already in use" errors
            $stopAndRemoveCommand = sprintf(
                "docker stop %s 2>/dev/null || true && docker rm %s 2>/dev/null || true",
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
}

