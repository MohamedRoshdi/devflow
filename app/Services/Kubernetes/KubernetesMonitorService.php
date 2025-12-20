<?php

declare(strict_types=1);

namespace App\Services\Kubernetes;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Handles Kubernetes monitoring and status operations.
 *
 * Responsible for retrieving pod status, service endpoints,
 * deployment logs, scaling, and executing commands in pods.
 */
class KubernetesMonitorService
{
    protected string $kubectlPath = '/usr/local/bin/kubectl';

    /**
     * Get pod status for the project
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPodStatus(Project $project): array
    {
        try {
            $command = sprintf(
                '%s get pods -n %s -l app=%s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $result = Process::run($command);

            if (! $result->successful()) {
                Log::warning('KubernetesMonitorService: Failed to get pod status', [
                    'project_id' => $project->id,
                    'project_slug' => $project->slug,
                    'namespace' => $project->slug,
                    'error' => $result->errorOutput(),
                ]);

                return [];
            }

            $pods = json_decode($result->output(), true);

            return array_map(function ($pod) {
                return [
                    'name' => $pod['metadata']['name'],
                    'status' => $pod['status']['phase'],
                    'ready' => $this->isPodReady($pod),
                    'restarts' => array_sum(array_column($pod['status']['containerStatuses'] ?? [], 'restartCount')),
                    'age' => $this->calculateAge($pod['metadata']['creationTimestamp']),
                    'node' => $pod['spec']['nodeName'] ?? 'unknown',
                ];
            }, $pods['items'] ?? []);
        } catch (\Exception $e) {
            Log::error('KubernetesMonitorService: Failed to get pod status', [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'namespace' => $project->slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get service endpoints
     *
     * @return array<int, string>
     */
    public function getServiceEndpoints(Project $project): array
    {
        try {
            $command = sprintf(
                '%s get service %s-service -n %s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $result = Process::run($command);

            if (! $result->successful()) {
                Log::warning('KubernetesMonitorService: Failed to get service endpoints', [
                    'project_id' => $project->id,
                    'project_slug' => $project->slug,
                    'namespace' => $project->slug,
                    'service_name' => $project->slug.'-service',
                    'error' => $result->errorOutput(),
                ]);

                return [];
            }

            $service = json_decode($result->output(), true);

            $endpoints = [];

            // Get external endpoints
            if (isset($service['status']['loadBalancer']['ingress'])) {
                foreach ($service['status']['loadBalancer']['ingress'] as $ingress) {
                    $endpoints[] = $ingress['ip'] ?? $ingress['hostname'];
                }
            }

            // Get ingress endpoints
            $ingressCommand = sprintf(
                '%s get ingress %s-ingress -n %s -o json',
                $this->kubectlPath,
                $project->slug,
                $project->slug
            );

            $ingressResult = Process::run($ingressCommand);

            if ($ingressResult->successful()) {
                $ingress = json_decode($ingressResult->output(), true);

                foreach ($ingress['spec']['rules'] ?? [] as $rule) {
                    $endpoints[] = 'https://'.$rule['host'];
                }
            }

            return $endpoints;
        } catch (\Exception $e) {
            Log::error('KubernetesMonitorService: Failed to get service endpoints', [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'namespace' => $project->slug,
                'service_name' => $project->slug.'-service',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Scale deployment
     *
     * @return array<string, mixed>
     */
    public function scaleDeployment(Project $project, int $replicas): array
    {
        $command = sprintf(
            '%s scale deployment/%s-deployment --replicas=%d -n %s',
            $this->kubectlPath,
            $project->slug,
            $replicas,
            $project->slug
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'new_replicas' => $replicas,
        ];
    }

    /**
     * Execute command in pod
     *
     * @return array<string, mixed>
     */
    public function executeInPod(Project $project, string $command, ?string $podName = null): array
    {
        if (! $podName) {
            // Get first running pod
            $pods = $this->getPodStatus($project);
            $runningPods = array_filter($pods, fn ($pod) => $pod['status'] === 'Running');

            if (empty($runningPods)) {
                throw new \Exception('No running pods found');
            }

            $podName = array_values($runningPods)[0]['name'];
        }

        $execCommand = sprintf(
            '%s exec -n %s %s -- %s',
            $this->kubectlPath,
            $project->slug,
            $podName,
            $command
        );

        $result = Process::run($execCommand);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'pod' => $podName,
        ];
    }

    /**
     * Get deployment logs
     *
     * @return array<string, mixed>
     */
    public function getDeploymentLogs(Project $project, int $lines = 100): array
    {
        $command = sprintf(
            '%s logs deployment/%s-deployment -n %s --tail=%d',
            $this->kubectlPath,
            $project->slug,
            $project->slug,
            $lines
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'logs' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Get pod logs
     *
     * @return array<string, mixed>
     */
    public function getPodLogs(Project $project, string $podName, int $lines = 100): array
    {
        $command = sprintf(
            '%s logs %s -n %s --tail=%d',
            $this->kubectlPath,
            $podName,
            $project->slug,
            $lines
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'logs' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }

    /**
     * Get deployment status
     *
     * @return array<string, mixed>
     */
    public function getDeploymentStatus(Project $project): array
    {
        $command = sprintf(
            '%s get deployment %s-deployment -n %s -o json',
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            return [
                'success' => false,
                'error' => $result->errorOutput(),
            ];
        }

        $deployment = json_decode($result->output(), true);

        return [
            'success' => true,
            'name' => $deployment['metadata']['name'] ?? '',
            'replicas' => $deployment['status']['replicas'] ?? 0,
            'ready_replicas' => $deployment['status']['readyReplicas'] ?? 0,
            'available_replicas' => $deployment['status']['availableReplicas'] ?? 0,
            'updated_replicas' => $deployment['status']['updatedReplicas'] ?? 0,
            'conditions' => $deployment['status']['conditions'] ?? [],
        ];
    }

    /**
     * Get resource usage for pods
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResourceUsage(Project $project): array
    {
        $command = sprintf(
            '%s top pods -n %s -l app=%s --no-headers',
            $this->kubectlPath,
            $project->slug,
            $project->slug
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            return [];
        }

        $usage = [];
        $lines = explode("\n", trim($result->output()));

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 3) {
                $usage[] = [
                    'pod' => $parts[0],
                    'cpu' => $parts[1],
                    'memory' => $parts[2],
                ];
            }
        }

        return $usage;
    }

    /**
     * Get events for the project namespace
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(Project $project, int $limit = 50): array
    {
        $command = sprintf(
            '%s get events -n %s --sort-by=.lastTimestamp -o json',
            $this->kubectlPath,
            $project->slug
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            return [];
        }

        $events = json_decode($result->output(), true);

        return array_slice(array_map(function ($event) {
            return [
                'type' => $event['type'] ?? 'Unknown',
                'reason' => $event['reason'] ?? '',
                'message' => $event['message'] ?? '',
                'count' => $event['count'] ?? 1,
                'first_timestamp' => $event['firstTimestamp'] ?? null,
                'last_timestamp' => $event['lastTimestamp'] ?? null,
                'source' => $event['source']['component'] ?? 'unknown',
            ];
        }, $events['items'] ?? []), 0, $limit);
    }

    /**
     * Check if pod is ready
     *
     * @param  array<string, mixed>  $pod
     */
    protected function isPodReady(array $pod): bool
    {
        foreach ($pod['status']['conditions'] ?? [] as $condition) {
            if ($condition['type'] === 'Ready' && $condition['status'] === 'True') {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate age from timestamp
     */
    protected function calculateAge(string $timestamp): string
    {
        $created = new \DateTime($timestamp);
        $now = new \DateTime;
        $interval = $created->diff($now);

        if ($interval->days > 0) {
            return $interval->days.'d';
        } elseif ($interval->h > 0) {
            return $interval->h.'h';
        } else {
            return $interval->i.'m';
        }
    }

    /**
     * Get kubectl path
     */
    public function getKubectlPath(): string
    {
        return $this->kubectlPath;
    }
}
