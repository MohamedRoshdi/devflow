<?php

declare(strict_types=1);

namespace App\Services\BlueGreen;

use App\Models\BlueGreenEnvironment;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BlueGreenEnvironmentService
{
    /**
     * Assign a port for the environment based on project ID and offset.
     */
    public function assignPort(Project $project, string $environment): int
    {
        /** @var int $portRange */
        $portRange = config('devflow.blue_green.port_range_start', 10000);

        return $environment === 'blue'
            ? $portRange + ((int) $project->id * 2)
            : $portRange + ((int) $project->id * 2) + 1;
    }

    /**
     * Build and start the Docker environment for blue or green.
     */
    public function buildEnvironment(Project $project, BlueGreenEnvironment $environment): void
    {
        $server = $project->server;
        if ($server === null) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $projectPath = config('devflow.projects_path', '/var/www') . '/' . $project->validated_slug;
        $envSuffix = $environment->environment;
        $stackName = "{$project->validated_slug}-{$envSuffix}";

        $sshPrefix = $this->buildSshPrefix($server);

        $port = $environment->port ?? $this->assignPort($project, $envSuffix);

        // Build and start containers with environment-specific compose
        $commands = [
            "cd {$projectPath}",
            "docker compose -p {$stackName} -f docker-compose.yml up -d --build",
        ];

        $command = implode(' && ', $commands);
        $timeout = (int) config('devflow.timeouts.docker_compose_build', 1200);

        $result = Process::timeout($timeout)->run("{$sshPrefix} \"{$command}\"");

        if (!$result->successful()) {
            Log::error('Blue-green environment build failed', [
                'project_id' => $project->id,
                'environment' => $envSuffix,
                'error' => $result->errorOutput(),
            ]);
            throw new \RuntimeException("Failed to build {$envSuffix} environment: " . $result->errorOutput());
        }

        // Get container IDs
        $containerResult = Process::run("{$sshPrefix} \"cd {$projectPath} && docker compose -p {$stackName} ps -q\"");

        /** @var array<int, string> $containerIds */
        $containerIds = array_values(array_filter(explode("\n", trim($containerResult->output()))));

        $environment->update([
            'container_ids' => $containerIds,
            'port' => $port,
        ]);

        Log::info('Blue-green environment built', [
            'project_id' => $project->id,
            'environment' => $envSuffix,
            'containers' => count($containerIds),
        ]);
    }

    /**
     * Stop and remove containers for an environment.
     */
    public function destroyEnvironment(Project $project, BlueGreenEnvironment $environment): void
    {
        $server = $project->server;
        if ($server === null) {
            return;
        }

        $projectPath = config('devflow.projects_path', '/var/www') . '/' . $project->validated_slug;
        $stackName = "{$project->validated_slug}-{$environment->environment}";
        $sshPrefix = $this->buildSshPrefix($server);

        Process::timeout(180)->run(
            "{$sshPrefix} \"cd {$projectPath} && docker compose -p {$stackName} down --remove-orphans\""
        );

        $environment->update(['container_ids' => null, 'status' => 'inactive']);
    }

    private function buildSshPrefix(Server $server): string
    {
        return "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";
    }
}
