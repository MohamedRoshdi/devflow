<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CanaryNginxService
{
    /**
     * Configure weighted routing between stable and canary upstream.
     *
     * @param  int  $canaryWeight  Percentage of traffic to send to canary (0-100)
     */
    public function configureWeightedRouting(Project $project, int $canaryWeight): void
    {
        $server = $project->server;
        if ($server === null) {
            throw new \RuntimeException('Project does not have an associated server');
        }

        $slug = $project->validated_slug;
        $stableWeight = 100 - $canaryWeight;
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        // Generate nginx upstream config with weights
        $stablePort = $project->port ?? 8080;
        $canaryPort = $stablePort + 1; // Canary runs on next port

        $config = "upstream {$slug}_backend {\n";
        if ($stableWeight > 0) {
            $config .= "    server 127.0.0.1:{$stablePort} weight={$stableWeight};\n";
        }
        if ($canaryWeight > 0) {
            $config .= "    server 127.0.0.1:{$canaryPort} weight={$canaryWeight};\n";
        }
        $config .= '}';

        $configPath = "/etc/nginx/conf.d/{$slug}-upstream.conf";
        $escapedConfig = str_replace(['"', '$'], ['\"', '\\$'], $config);

        $commands = [
            "echo \"{$escapedConfig}\" > {$configPath}",
            'nginx -t',
            'systemctl reload nginx',
        ];

        $result = Process::timeout(30)->run("{$sshPrefix} \"" . implode(' && ', $commands) . '"');

        if (! $result->successful()) {
            Log::error('Canary nginx config update failed', [
                'project_id' => $project->id,
                'canary_weight' => $canaryWeight,
                'error' => $result->errorOutput(),
            ]);
            throw new \RuntimeException('Failed to update Nginx canary config: ' . $result->errorOutput());
        }

        Log::info('Canary nginx routing configured', [
            'project_id' => $project->id,
            'canary_weight' => $canaryWeight,
            'stable_weight' => $stableWeight,
        ]);
    }

    /**
     * Remove canary routing config (revert to standard).
     */
    public function removeCanaryRouting(Project $project): void
    {
        $this->configureWeightedRouting($project, 0);
    }

    /**
     * Generate the nginx config content for inspection.
     */
    public function generateConfig(Project $project, int $canaryWeight): string
    {
        $slug = $project->validated_slug;
        $stableWeight = 100 - $canaryWeight;
        $stablePort = $project->port ?? 8080;
        $canaryPort = $stablePort + 1;

        $config = "upstream {$slug}_backend {\n";
        if ($stableWeight > 0) {
            $config .= "    server 127.0.0.1:{$stablePort} weight={$stableWeight};\n";
        }
        if ($canaryWeight > 0) {
            $config .= "    server 127.0.0.1:{$canaryPort} weight={$canaryWeight};\n";
        }
        $config .= "}\n";

        return $config;
    }
}
