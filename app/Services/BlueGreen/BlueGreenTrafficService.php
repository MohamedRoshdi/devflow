<?php

declare(strict_types=1);

namespace App\Services\BlueGreen;

use App\Models\BlueGreenEnvironment;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BlueGreenTrafficService
{
    /**
     * Switch the upstream proxy to point to the target environment.
     */
    public function switchUpstream(Project $project, BlueGreenEnvironment $targetEnvironment): void
    {
        /** @var string $method */
        $method = config('devflow.blue_green.proxy_method', 'nginx_config');

        match ($method) {
            'nginx_config' => $this->switchNginxConfig($project, $targetEnvironment),
            'npm_api' => $this->switchViaNpmApi($project, $targetEnvironment),
            default => $this->switchNginxConfig($project, $targetEnvironment),
        };
    }

    /**
     * Switch traffic by updating Nginx configuration directly.
     */
    private function switchNginxConfig(Project $project, BlueGreenEnvironment $targetEnvironment): void
    {
        $server = $project->server;
        if ($server === null) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $port = $targetEnvironment->port;
        if ($port === null) {
            throw new \RuntimeException('Target environment does not have a port assigned');
        }

        $slug = $project->validated_slug;
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        // Generate nginx upstream config
        $upstreamConfig = "upstream {$slug}_backend {\n    server 127.0.0.1:{$port};\n}";

        // Write upstream config and reload nginx
        $configPath = "/etc/nginx/conf.d/{$slug}-upstream.conf";
        $escapedConfig = str_replace(['"', '$'], ['\"', '\\$'], $upstreamConfig);

        $commands = [
            "echo \"{$escapedConfig}\" > {$configPath}",
            'nginx -t',
            'systemctl reload nginx',
        ];

        $result = Process::timeout(30)->run(
            "{$sshPrefix} \"" . implode(' && ', $commands) . '"'
        );

        if (!$result->successful()) {
            Log::error('Nginx upstream switch failed', [
                'project_id' => $project->id,
                'target' => $targetEnvironment->environment,
                'error' => $result->errorOutput(),
            ]);
            throw new \RuntimeException('Failed to switch Nginx upstream: ' . $result->errorOutput());
        }

        Log::info('Nginx upstream switched', [
            'project_id' => $project->id,
            'environment' => $targetEnvironment->environment,
            'port' => $port,
        ]);
    }

    /**
     * Switch traffic via Nginx Proxy Manager API.
     */
    private function switchViaNpmApi(Project $project, BlueGreenEnvironment $targetEnvironment): void
    {
        // NPM API integration placeholder
        // In a real implementation, this would call the NPM API to update the proxy host
        Log::info('NPM API traffic switch requested', [
            'project_id' => $project->id,
            'target' => $targetEnvironment->environment,
            'port' => $targetEnvironment->port,
        ]);

        // For now, fall back to direct nginx config
        $this->switchNginxConfig($project, $targetEnvironment);
    }

    /**
     * Get the current upstream configuration for a project.
     *
     * @return array{environment: string|null, port: int|null}
     */
    public function getCurrentUpstream(Project $project): array
    {
        $active = BlueGreenEnvironment::where('project_id', $project->id)
            ->where('status', 'active')
            ->first();

        return [
            'environment' => $active?->environment,
            'port' => $active?->port,
        ];
    }
}
