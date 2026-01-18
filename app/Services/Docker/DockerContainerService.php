<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Support\Facades\Log;

/**
 * Service for Docker container lifecycle management.
 *
 * Handles:
 * - Container building (standalone and docker-compose)
 * - Container start/stop operations
 * - Container status and statistics
 * - Container resource management
 * - Command execution in containers
 * - Container export/backup
 *
 * Uses Laravel's Process facade for better testability with Process::fake()
 */
class DockerContainerService
{
    use ExecutesRemoteCommands;

    public function __construct(
        private readonly DockerfileGenerator $dockerfileGenerator
    ) {}

    // ==========================================
    // BUILD OPERATIONS
    // ==========================================

    /**
     * Build Docker container for a project
     *
     * This method delegates to either docker-compose build or standalone
     * container build based on project configuration.
     *
     * @return array{success: bool, output?: string, error?: string, type?: string}
     */
    public function buildContainer(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            if ($this->detectComposeUsage($server, $project)) {
                return $this->buildDockerComposeContainer($server, $project);
            }

            return $this->buildStandaloneContainer($server, $project);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect if project uses docker-compose
     */
    protected function detectComposeUsage(Server $server, Project $project): bool
    {
        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
        $result = $this->executeRemoteCommand($server, $checkComposeCmd, false);

        return trim($result->output()) === 'compose';
    }

    /**
     * Build container using docker-compose
     *
     * @return array{success: bool, output?: string, error?: string, type?: string}
     */
    protected function buildDockerComposeContainer(Server $server, Project $project): array
    {
        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        $buildCommand = "cd {$projectPath} && docker compose build --no-cache --pull";

        $result = $this->executeRemoteCommandWithTimeout(
            $server,
            $buildCommand,
            (int) config('devflow.timeouts.docker_compose_build', 1200),
            false
        );

        if ($result->successful()) {
            return [
                'success' => true,
                'output' => $result->output(),
                'type' => 'docker-compose',
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorOutput() ?: $result->output(),
        ];
    }

    /**
     * Build standalone Docker container
     *
     * @return array{success: bool, output?: string, error?: string, type?: string}
     */
    protected function buildStandaloneContainer(Server $server, Project $project): array
    {
        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        // Check for Dockerfile
        $checkDockerfileCommand = "cd {$projectPath} && if [ -f Dockerfile ]; then echo 'Dockerfile'; elif [ -f Dockerfile.production ]; then echo 'Dockerfile.production'; else echo 'missing'; fi";
        $checkResult = $this->executeRemoteCommand($server, $checkDockerfileCommand, false);
        $dockerfileType = trim($checkResult->output());

        $buildCommand = $this->prepareBuildCommand($project, $projectPath, $slug, $dockerfileType);

        $result = $this->executeRemoteCommandWithTimeout(
            $server,
            $buildCommand,
            (int) config('devflow.timeouts.docker_build', 600),
            false
        );

        if ($result->successful()) {
            return [
                'success' => true,
                'output' => $result->output(),
                'type' => 'standalone',
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Prepare build command based on Dockerfile type
     */
    protected function prepareBuildCommand(Project $project, string $projectPath, string $slug, string $dockerfileType): string
    {
        if ($dockerfileType === 'Dockerfile') {
            return sprintf(
                'cd %s && docker build -t %s .',
                $projectPath,
                escapeshellarg($slug)
            );
        }

        if ($dockerfileType === 'Dockerfile.production') {
            return sprintf(
                'cd %s && docker build -f Dockerfile.production -t %s .',
                $projectPath,
                escapeshellarg($slug)
            );
        }

        // Generate Dockerfile if project doesn't have one
        $dockerfile = $this->dockerfileGenerator->generate($project);

        // Properly escape single quotes for shell single-quoted string
        $escapedDockerfile = str_replace("'", "'\\''", $dockerfile);

        return sprintf(
            "cd %s && echo '%s' > Dockerfile && docker build -t %s .",
            $projectPath,
            $escapedDockerfile,
            escapeshellarg($slug)
        );
    }

    // ==========================================
    // START OPERATIONS
    // ==========================================

    /**
     * Start container for a project
     *
     * @return array{success: bool, output?: string, message?: string, error?: string, container_id?: string, port?: int}
     */
    public function startContainer(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
            $checkResult = $this->executeRemoteCommand($server, $checkComposeCmd, false);
            $usesCompose = trim($checkResult->output()) === 'compose';

            if ($usesCompose) {
                return $this->startDockerComposeContainers($server, $project);
            }

            return $this->startStandaloneContainer($server, $project);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start containers using Docker Compose
     *
     * @return array{success: bool, output?: string, message?: string, error?: string}
     */
    protected function startDockerComposeContainers(Server $server, Project $project): array
    {
        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        $this->cleanupOrphanedContainers($server, $project);

        $startCommand = "cd {$projectPath} && docker compose up -d --remove-orphans";

        $result = $this->executeRemoteCommandWithTimeout(
            $server,
            $startCommand,
            (int) config('devflow.timeouts.docker_compose_start', 300),
            false
        );

        if ($result->successful()) {
            return [
                'success' => true,
                'output' => $result->output(),
                'message' => 'Docker Compose services started',
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorOutput() ?: $result->output(),
        ];
    }

    /**
     * Start a standalone Docker container
     *
     * @return array{success: bool, container_id?: string, port?: int, error?: string}
     */
    protected function startStandaloneContainer(Server $server, Project $project): array
    {
        $cleanupResult = $this->cleanupExistingContainer($project);
        if (! $cleanupResult['success']) {
            Log::warning(
                "Failed to cleanup existing container for {$project->slug}: ".
                ($cleanupResult['error'] ?? 'Unknown error')
            );
        }

        $port = $project->port ?? (8000 + $project->id);
        $containerPort = $this->detectContainerPort($project);
        $envVars = $this->buildEnvironmentVariables($project);

        $validatedSlug = $project->validated_slug;
        $escapedSlug = escapeshellarg($validatedSlug);
        $startCommand = sprintf(
            'docker run -d --name %s -p %d:%d%s %s',
            $escapedSlug,
            $port,
            $containerPort,
            $envVars,
            $escapedSlug
        );

        $result = $this->executeRemoteCommand($server, $startCommand, false);

        if ($result->successful()) {
            if (! $project->port) {
                $project->update(['port' => $port]);
            }

            return [
                'success' => true,
                'container_id' => trim($result->output()),
                'port' => $port,
            ];
        }

        return [
            'success' => false,
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Cleanup orphaned Docker Compose containers
     */
    protected function cleanupOrphanedContainers(Server $server, Project $project): void
    {
        $slug = $project->validated_slug;
        $projectPath = "/var/www/{$slug}";

        // Step 1: Standard docker compose cleanup
        $cleanupCommand = "cd {$projectPath} && docker compose down --remove-orphans 2>/dev/null; true";
        $this->executeRemoteCommandWithTimeout(
            $server,
            $cleanupCommand,
            (int) config('devflow.timeouts.docker_compose_cleanup', 180),
            false
        );

        // Step 2: Remove containers by name pattern
        $escapedSlugForGrep = preg_quote($slug, '/');
        $orphanCleanupCommand = "docker ps -a --format '{{.Names}}' | grep -E '^{$escapedSlugForGrep}[-_]' | xargs -r docker rm -f 2>/dev/null; true";
        $this->executeRemoteCommand($server, $orphanCleanupCommand, false);

        // Step 3: Remove containers with explicit container_name
        $parseContainerNamesCmd = "cd {$projectPath} && grep -E '^\\s+container_name:' docker-compose.yml 2>/dev/null | sed 's/.*container_name:\\s*[\"'\\'']\\?\\([^\"'\\''\n]*\\)[\"'\\'']\\?/\\1/' | xargs -r -I {} docker rm -f {} 2>/dev/null; true";
        $this->executeRemoteCommand($server, $parseContainerNamesCmd, false);
    }

    /**
     * Build environment variables string for docker run command
     */
    protected function buildEnvironmentVariables(Project $project): string
    {
        $envFlags = '';

        $appEnv = $project->environment ?? 'production';
        $envFlags .= " -e APP_ENV={$appEnv}";

        $appDebug = in_array($appEnv, ['local', 'development']) ? 'true' : 'false';
        $envFlags .= " -e APP_DEBUG={$appDebug}";

        if ($project->env_variables && is_array($project->env_variables)) {
            foreach ($project->env_variables as $key => $value) {
                // Use escapeshellarg for proper shell escaping of environment values
                $envFlags .= ' -e '.escapeshellarg("{$key}={$value}");
            }
        }

        return $envFlags;
    }

    /**
     * Cleanup existing container
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    protected function cleanupExistingContainer(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $cleanupCommand = sprintf(
                'docker stop %s 2>/dev/null || true && docker rm -f %s 2>/dev/null || true',
                $escapedSlug,
                $escapedSlug
            );

            $result = $this->executeRemoteCommand($server, $cleanupCommand, false);

            return [
                'success' => true,
                'output' => $result->output(),
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
        if (in_array($project->framework, ['Laravel', 'Symfony', 'CodeIgniter'])) {
            return 80; // nginx serving PHP-FPM
        }

        if (in_array($project->framework, ['Next.js', 'React', 'Vue', 'Nuxt.js', 'Node.js'])) {
            return 3000; // Node.js default
        }

        return 80;
    }

    // ==========================================
    // STOP OPERATIONS
    // ==========================================

    /**
     * Stop container for a project
     *
     * @return array{success: bool, output?: string, message?: string, error?: string}
     */
    public function stopContainer(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
            $checkResult = $this->executeRemoteCommand($server, $checkComposeCmd, false);
            $usesCompose = trim($checkResult->output()) === 'compose';

            if ($usesCompose) {
                $stopCommand = "cd {$projectPath} && docker compose down --remove-orphans";

                $result = $this->executeRemoteCommandWithTimeout(
                    $server,
                    $stopCommand,
                    (int) config('devflow.timeouts.docker_compose_cleanup', 180),
                    false
                );

                return [
                    'success' => true,
                    'output' => $result->output(),
                    'message' => 'Docker Compose services stopped and orphans removed',
                ];
            }

            // Standalone container mode
            $escapedSlug = escapeshellarg($slug);
            $stopAndRemoveCommand = sprintf(
                'docker stop %s 2>/dev/null || true && docker rm -f %s 2>/dev/null || true',
                $escapedSlug,
                $escapedSlug
            );

            $result = $this->executeRemoteCommand($server, $stopAndRemoveCommand, false);

            return [
                'success' => true,
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // STATUS AND STATS
    // ==========================================

    /**
     * Get container status for a project
     *
     * @return array{success: bool, container?: array<string, mixed>|null, exists?: bool, error?: string}
     */
    public function getContainerStatus(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $statusCommand = sprintf(
                "docker ps -a --filter name=%s --format '{{json .}}'",
                $escapedSlug
            );

            $result = $this->executeRemoteCommand($server, $statusCommand, false);

            if ($result->successful()) {
                $output = trim($result->output());
                if (! empty($output)) {
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

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get container statistics (CPU, Memory, Network, Disk I/O)
     *
     * @return array{success: bool, stats?: array<string, mixed>|null, error?: string}
     */
    public function getContainerStats(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $statsCommand = sprintf(
                "docker stats --no-stream --format '{{json .}}' %s",
                $escapedSlug
            );

            $result = $this->executeRemoteCommand($server, $statsCommand, false);

            if ($result->successful()) {
                $stats = json_decode($result->output(), true);

                return [
                    'success' => true,
                    'stats' => $stats,
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // RESOURCE MANAGEMENT
    // ==========================================

    /**
     * Get container resource limits
     *
     * @return array{success: bool, memory_limit?: int, cpu_shares?: int, cpu_quota?: int, error?: string}
     */
    public function getContainerResourceLimits(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $inspectCommand = sprintf(
                "docker inspect --format='{{json .HostConfig}}' %s",
                $escapedSlug
            );

            $result = $this->executeRemoteCommand($server, $inspectCommand, false);

            if ($result->successful()) {
                $config = json_decode($result->output(), true);

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
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function setContainerResourceLimits(Project $project, ?int $memoryMB = null, ?int $cpuShares = null): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $updateCommand = 'docker update';
            if ($memoryMB !== null) {
                $updateCommand .= " --memory={$memoryMB}m";
            }
            if ($cpuShares !== null) {
                $updateCommand .= " --cpu-shares={$cpuShares}";
            }
            $updateCommand .= ' '.$escapedSlug;

            $result = $this->executeRemoteCommand($server, $updateCommand, false);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // COMMAND EXECUTION
    // ==========================================

    /**
     * Execute command in container
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function execInContainer(Project $project, string $command, bool $interactive = false): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $escapedCommand = escapeshellarg($command);
            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $execCommand = sprintf(
                'docker exec %s %s sh -c %s',
                $interactive ? '-it' : '',
                $escapedSlug,
                $escapedCommand
            );

            $result = $this->executeRemoteCommandWithTimeout(
                $server,
                $execCommand,
                (int) config('devflow.timeouts.command_exec', 300),
                false
            );

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get list of processes in container
     *
     * @return array{success: bool, processes?: string, error?: string}
     */
    public function getContainerProcesses(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $escapedSlug = escapeshellarg($slug);

            $psCommand = "docker top {$escapedSlug}";

            $result = $this->executeRemoteCommand($server, $psCommand, false);

            return [
                'success' => $result->successful(),
                'processes' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================
    // BACKUP & EXPORT
    // ==========================================

    /**
     * Export container as image (backup)
     *
     * @return array{success: bool, backup_name?: string, image_id?: string, error?: string}
     */
    public function exportContainer(Project $project, ?string $backupName = null): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $backupName = $backupName ?? "{$slug}-backup-".date('Y-m-d-H-i-s');

            $escapedSlug = escapeshellarg($slug);
            $escapedBackupName = escapeshellarg($backupName);
            $commitCommand = "docker commit {$escapedSlug} {$escapedBackupName}";

            $result = $this->executeRemoteCommand($server, $commitCommand, false);

            if ($result->successful()) {
                return [
                    'success' => true,
                    'backup_name' => $backupName,
                    'image_id' => trim($result->output()),
                ];
            }

            return ['success' => false, 'error' => $result->errorOutput()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restart a container
     *
     * @return array{success: bool, output?: string, error?: string}
     */
    public function restartContainer(Project $project): array
    {
        try {
            $server = $project->server;
            if ($server === null) {
                return ['success' => false, 'error' => 'Project has no associated server'];
            }

            $slug = $project->validated_slug;
            $projectPath = "/var/www/{$slug}";

            $checkComposeCmd = "test -f {$projectPath}/docker-compose.yml && echo 'compose' || echo 'standalone'";
            $checkResult = $this->executeRemoteCommand($server, $checkComposeCmd, false);
            $usesCompose = trim($checkResult->output()) === 'compose';

            if ($usesCompose) {
                $restartCommand = "cd {$projectPath} && docker compose restart";
            } else {
                $escapedSlug = escapeshellarg($slug);
                $restartCommand = "docker restart {$escapedSlug}";
            }

            $result = $this->executeRemoteCommand($server, $restartCommand, false);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
