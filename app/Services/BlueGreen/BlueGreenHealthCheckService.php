<?php

declare(strict_types=1);

namespace App\Services\BlueGreen;

use App\Models\BlueGreenEnvironment;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BlueGreenHealthCheckService
{
    /**
     * Check the health of a blue-green environment.
     *
     * @return array{healthy: bool, message: string, checks: array<string, bool>}
     */
    public function checkHealth(Project $project, BlueGreenEnvironment $environment): array
    {
        /** @var array<string, bool> $checks */
        $checks = [];

        /** @var array<int, string> $messages */
        $messages = [];

        // Check 1: Containers are running
        $containersRunning = $this->checkContainersRunning($project, $environment);
        $checks['containers'] = $containersRunning;
        if (!$containersRunning) {
            $messages[] = 'Containers are not running';
        }

        // Check 2: HTTP health check (if health_check_url is configured)
        if ($project->health_check_url !== null && $project->health_check_url !== '' && $environment->port !== null) {
            $httpHealthy = $this->checkHttpHealth($project, $environment);
            $checks['http'] = $httpHealthy;
            if (!$httpHealthy) {
                $messages[] = 'HTTP health check failed';
            }
        } else {
            $checks['http'] = true; // Skip if not configured
        }

        $healthy = !in_array(false, $checks, true);

        $environment->update([
            'health_status' => $healthy ? 'healthy' : 'unhealthy',
            'last_health_check_at' => now(),
        ]);

        return [
            'healthy' => $healthy,
            'message' => $healthy ? 'All checks passed' : implode('; ', $messages),
            'checks' => $checks,
        ];
    }

    /**
     * Check if Docker containers are running for the environment.
     */
    private function checkContainersRunning(Project $project, BlueGreenEnvironment $environment): bool
    {
        $server = $project->server;
        if ($server === null) {
            return false;
        }

        $projectPath = config('devflow.projects_path', '/var/www') . '/' . $project->validated_slug;
        $stackName = "{$project->validated_slug}-{$environment->environment}";
        $sshPrefix = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 {$server->username}@{$server->ip_address}";

        $result = Process::timeout(30)->run(
            "{$sshPrefix} \"cd {$projectPath} && docker compose -p {$stackName} ps --status running -q | wc -l\""
        );

        if (!$result->successful()) {
            return false;
        }

        return (int) trim($result->output()) > 0;
    }

    /**
     * Perform HTTP health check against the environment's port.
     */
    private function checkHttpHealth(Project $project, BlueGreenEnvironment $environment): bool
    {
        $server = $project->server;
        if ($server === null || $environment->port === null) {
            return false;
        }

        /** @var int $maxRetries */
        $maxRetries = (int) config('devflow.blue_green.health_check_retries', 3);

        /** @var int $timeout */
        $timeout = (int) config('devflow.timeouts.health_check', 10);

        $url = "http://{$server->ip_address}:{$environment->port}";
        if ($project->health_check_url !== null && $project->health_check_url !== '') {
            $path = ltrim($project->health_check_url, '/');
            $url .= "/{$path}";
        }

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $response = Http::timeout($timeout)->get($url);
                if ($response->successful()) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::debug('Blue-green health check attempt failed', [
                    'attempt' => $i + 1,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($i < $maxRetries - 1) {
                sleep(5);
            }
        }

        return false;
    }
}
