<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\IteratesLargeDatasets;
use App\Models\Project;
use App\Models\Server;
use App\Services\Health\ProjectHealthScorer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\LazyCollection;

/**
 * Project Health Service
 *
 * Provides centralized health checking for projects and servers.
 * Monitors HTTP endpoints, SSL certificates, Docker containers, and disk usage.
 */
class ProjectHealthService
{
    use IteratesLargeDatasets;

    public function __construct(
        private readonly DockerService $dockerService,
        private readonly ProjectHealthScorer $healthScorer
    ) {}

    /**
     * Health check cache TTL in seconds (5 minutes)
     */
    private const HEALTH_CHECK_CACHE_TTL = 300;

    /**
     * Docker status cache TTL in seconds (2 minutes)
     */
    private const DOCKER_STATUS_CACHE_TTL = 120;

    /**
     * Check health of all projects (memory-efficient using cursor)
     *
     * @return Collection<int, array{project: Project, status: string, checks: array}>
     */
    public function checkAllProjects(): Collection
    {
        return $this->mapByCursor(
            Project::with(['server', 'domains', 'deployments' => function ($query) {
                $query->latest()->limit(1);
            }]),
            function (Project $project) {
                $cacheKey = "project_health_{$project->id}";

                return Cache::remember($cacheKey, self::HEALTH_CHECK_CACHE_TTL, function () use ($project) {
                    return $this->checkProject($project);
                });
            }
        );
    }

    /**
     * Stream health checks for all projects (lazy evaluation)
     *
     * Use this for processing very large numbers of projects without memory issues.
     *
     * @return LazyCollection<int, array>
     */
    public function streamHealthChecks(): LazyCollection
    {
        return $this->streamTransform(
            Project::with(['server', 'domains', 'deployments' => function ($query) {
                $query->latest()->limit(1);
            }]),
            function (Project $project) {
                $cacheKey = "project_health_{$project->id}";

                return Cache::remember($cacheKey, self::HEALTH_CHECK_CACHE_TTL, function () use ($project) {
                    return $this->checkProject($project);
                });
            }
        );
    }

    /**
     * Check health of a single project
     *
     * @return array{id: int, name: string, slug: string, status: string, server_name: string, checks: array, health_score: int, issues: array<int, string>, last_checked: string}
     */
    public function checkProject(Project $project): array
    {
        $checks = [
            'http' => $this->checkHttpHealth($project),
            'ssl' => $this->checkSSLHealth($project),
            'docker' => $this->checkDockerHealth($project),
            'disk' => $this->checkDiskUsage($project),
            'deployment' => $this->checkDeploymentHealth($project),
        ];

        $issues = $this->collectIssues($checks, $project);
        $healthScore = $this->healthScorer->calculateOverallScore($checks, $project);
        $status = $this->healthScorer->determineOverallStatus($healthScore);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'status' => $status,
            'server_name' => $project->server->name ?? 'Unknown',
            'checks' => $checks,
            'health_score' => $healthScore,
            'issues' => $issues,
            'last_checked' => (string) now()->toISOString(),
        ];
    }

    /**
     * Check HTTP health of a project
     *
     * @return array{status: string, response_time: int|null, http_code: int|null, error: string|null}
     */
    private function checkHttpHealth(Project $project): array
    {
        $url = $this->getHealthCheckUrl($project);

        if (!$url) {
            return [
                'status' => 'unknown',
                'response_time' => null,
                'http_code' => null,
                'error' => 'No health check URL configured',
            ];
        }

        try {
            $startTime = microtime(true);
            $response = Http::timeout(config('devflow.timeouts.health_check', 10))->get($url);
            $responseTime = (int) round((microtime(true) - $startTime) * 1000); // ms

            $status = $response->successful() ? 'healthy' : 'unhealthy';

            return [
                'status' => $status,
                'response_time' => $responseTime,
                'http_code' => $response->status(),
                'error' => $response->successful() ? null : "HTTP {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'response_time' => null,
                'http_code' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get health check URL for a project
     */
    private function getHealthCheckUrl(Project $project): ?string
    {
        if ($project->health_check_url) {
            return $project->health_check_url;
        }

        // Try primary domain
        if ($project->domains->isNotEmpty()) {
            $domain = $project->domains->first();
            return "https://{$domain->full_domain}";
        }

        return null;
    }

    /**
     * Check SSL certificate health
     *
     * @return array{status: string, valid: bool, expires_at: string|null, days_remaining: int|null, error: string|null}
     */
    private function checkSSLHealth(Project $project): array
    {
        if ($project->domains->isEmpty()) {
            return [
                'status' => 'unknown',
                'valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'error' => 'No domains configured',
            ];
        }

        $domain = $project->domains->first();

        // Check cache first to avoid frequent SSL checks
        $cacheKey = "ssl_health_{$domain->id}";
        $cacheDuration = config('devflow.cache.ssl_certificate', 3600);

        return Cache::remember($cacheKey, $cacheDuration, function () use ($domain) {
            return $this->performSSLCheck($domain);
        });
    }

    /**
     * Perform actual SSL certificate check
     *
     * @param \App\Models\Domain $domain
     */
    private function performSSLCheck(\App\Models\Domain $domain): array
    {
        try {
            $streamContext = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $timeout = config('devflow.timeouts.ssl_check', 30);
            $client = @stream_socket_client(
                "ssl://{$domain->full_domain}:443",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $streamContext
            );

            if (!$client) {
                return [
                    'status' => 'error',
                    'valid' => false,
                    'expires_at' => null,
                    'days_remaining' => null,
                    'error' => $errstr ?? 'Cannot connect to SSL endpoint',
                ];
            }

            $params = stream_context_get_params($client);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            fclose($client);

            $expiresAt = $cert['validTo_time_t'] ?? null;
            $daysRemaining = $expiresAt ? floor(($expiresAt - time()) / 86400) : null;

            $expiryWarningDays = config('devflow.health_check.ssl_expiry_warning_days', 7);
            $status = 'valid';
            if ($daysRemaining !== null) {
                if ($daysRemaining <= 0) {
                    $status = 'expired';
                } elseif ($daysRemaining <= $expiryWarningDays) {
                    $status = 'expiring_soon';
                }
            }

            return [
                'status' => $status,
                'valid' => $daysRemaining > 0,
                'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : null,
                'days_remaining' => $daysRemaining,
                'error' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'valid' => false,
                'expires_at' => null,
                'days_remaining' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Docker container health (cached per project)
     *
     * @return array{status: string, running: bool, containers: array|null, error: string|null}
     */
    private function checkDockerHealth(Project $project): array
    {
        $cacheKey = "docker_health_{$project->id}";

        return Cache::remember($cacheKey, self::DOCKER_STATUS_CACHE_TTL, function () use ($project) {
            return $this->performDockerHealthCheck($project);
        });
    }

    /**
     * Perform actual Docker health check
     *
     * @return array{status: string, running: bool, containers: array|null, error: string|null}
     */
    private function performDockerHealthCheck(Project $project): array
    {
        try {
            $containerStatus = $this->dockerService->getContainerStatus($project);

            if (!$containerStatus['success']) {
                return [
                    'status' => 'error',
                    'running' => false,
                    'containers' => null,
                    'error' => $containerStatus['error'] ?? 'Failed to get container status',
                ];
            }

            $running = $containerStatus['exists'] ?? false;
            $container = $containerStatus['container'] ?? null;

            $status = 'stopped';
            if ($running && $container) {
                $containerState = $container['State'] ?? '';
                $status = str_contains(strtolower($containerState), 'running') ? 'running' : 'stopped';
            }

            return [
                'status' => $status,
                'running' => $status === 'running',
                'containers' => $container ? [$container] : [],
                'error' => null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'running' => false,
                'containers' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check disk usage for a project
     *
     * @return array{status: string, usage_percent: int|null, usage_bytes: int|null, error: string|null}
     */
    private function checkDiskUsage(Project $project): array
    {
        try {
            $server = $project->server;
            if (!$server) {
                return [
                    'status' => 'unknown',
                    'usage_percent' => null,
                    'usage_bytes' => null,
                    'error' => 'No server configured',
                ];
            }

            // Use the server's latest metric if available
            if ($server->latestMetric) {
                $diskUsage = (float) $server->latestMetric->disk_usage;
                $diskCritical = config('devflow.health_check.disk_critical_threshold', 90);
                $diskWarning = config('devflow.health_check.disk_warning_threshold', 75);
                $status = 'healthy';

                if ($diskUsage > $diskCritical) {
                    $status = 'critical';
                } elseif ($diskUsage > $diskWarning) {
                    $status = 'warning';
                }

                return [
                    'status' => $status,
                    'usage_percent' => (int) $diskUsage,
                    'usage_bytes' => $server->latestMetric->disk_used_gb * 1024 * 1024 * 1024,
                    'error' => null,
                ];
            }

            return [
                'status' => 'unknown',
                'usage_percent' => null,
                'usage_bytes' => null,
                'error' => 'No metrics available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'usage_percent' => null,
                'usage_bytes' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check deployment health
     *
     * @return array{status: string, last_deployment: string|null, last_status: string|null, error: string|null}
     */
    private function checkDeploymentHealth(Project $project): array
    {
        $lastDeployment = $project->deployments->first();

        if (!$lastDeployment) {
            return [
                'status' => 'none',
                'last_deployment' => null,
                'last_status' => null,
                'error' => 'No deployments yet',
            ];
        }

        $status = match ($lastDeployment->status) {
            'success' => 'healthy',
            'failed' => 'failed',
            'running', 'pending' => 'in_progress',
            default => 'unknown',
        };

        return [
            'status' => $status,
            'last_deployment' => $lastDeployment->created_at?->diffForHumans(),
            'last_status' => $lastDeployment->status,
            'error' => $lastDeployment->status === 'failed' ? 'Last deployment failed' : null,
        ];
    }

    /**
     * Collect all issues from health checks
     *
     * @return array<int, string>
     */
    private function collectIssues(array $checks, Project $project): array
    {
        $issues = [];

        // HTTP issues
        if ($checks['http']['status'] === 'unreachable') {
            $issues[] = 'Health check endpoint not responding';
        } elseif ($checks['http']['status'] === 'unhealthy') {
            $issues[] = 'HTTP health check failed';
        }

        // SSL issues
        if ($checks['ssl']['status'] === 'expired') {
            $issues[] = 'SSL certificate has expired';
        } elseif ($checks['ssl']['status'] === 'expiring_soon') {
            $issues[] = 'SSL certificate expiring soon';
        }

        // Docker issues
        if ($checks['docker']['status'] === 'stopped') {
            $issues[] = 'Docker container is stopped';
        } elseif ($checks['docker']['status'] === 'error') {
            $issues[] = 'Docker container error';
        }

        // Disk issues
        if ($checks['disk']['status'] === 'critical') {
            $issues[] = 'Critical disk usage';
        } elseif ($checks['disk']['status'] === 'warning') {
            $issues[] = 'High disk usage';
        }

        // Deployment issues
        if ($checks['deployment']['status'] === 'failed') {
            $issues[] = 'Last deployment failed';
        } elseif ($checks['deployment']['status'] === 'none') {
            $issues[] = 'No deployments yet';
        }

        // Project status issues
        if ($project->status === 'stopped') {
            $issues[] = 'Project is stopped';
        } elseif ($project->status === 'failed') {
            $issues[] = 'Project is in failed state';
        }

        return $issues;
    }


    /**
     * Check health of a server
     *
     * @return array{status: string, health_score: int, checks: array, issues: array}
     */
    public function checkServerHealth(Server $server): array
    {
        $checks = [
            'connectivity' => $this->checkServerConnectivity($server),
            'resources' => $this->checkServerResources($server),
            'docker' => $this->checkServerDockerStatus($server),
        ];

        $issues = $this->collectServerIssues($checks, $server);
        $healthScore = $this->healthScorer->calculateServerHealthScore($checks, $server);
        $status = $this->healthScorer->determineOverallStatus($healthScore);

        return [
            'id' => $server->id,
            'name' => $server->name,
            'ip_address' => $server->ip_address,
            'status' => $status,
            'health_score' => $healthScore,
            'checks' => $checks,
            'issues' => $issues,
            'last_checked' => (string) now()->toISOString(),
        ];
    }

    /**
     * Check server connectivity
     */
    private function checkServerConnectivity(Server $server): array
    {
        $status = $server->status === 'online' ? 'online' : 'offline';

        return [
            'status' => $status,
            'online' => $status === 'online',
            'error' => $status === 'offline' ? 'Server is offline' : null,
        ];
    }

    /**
     * Check server resource usage
     */
    private function checkServerResources(Server $server): array
    {
        if (!$server->latestMetric) {
            return [
                'status' => 'unknown',
                'cpu_usage' => null,
                'memory_usage' => null,
                'disk_usage' => null,
                'error' => 'No metrics available',
            ];
        }

        $cpuUsage = (float) $server->latestMetric->cpu_usage;
        $memoryUsage = (float) $server->latestMetric->memory_usage;
        $diskUsage = (float) $server->latestMetric->disk_usage;

        $cpuCritical = config('devflow.health_check.cpu_critical_threshold', 90);
        $cpuWarning = config('devflow.health_check.cpu_warning_threshold', 75);
        $memoryCritical = config('devflow.health_check.memory_critical_threshold', 90);
        $memoryWarning = config('devflow.health_check.memory_warning_threshold', 75);
        $diskCritical = config('devflow.health_check.disk_critical_threshold', 90);
        $diskWarning = config('devflow.health_check.disk_warning_threshold', 75);

        $status = 'healthy';
        if ($cpuUsage > $cpuCritical || $memoryUsage > $memoryCritical || $diskUsage > $diskCritical) {
            $status = 'critical';
        } elseif ($cpuUsage > $cpuWarning || $memoryUsage > $memoryWarning || $diskUsage > $diskWarning) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'cpu_usage' => $cpuUsage,
            'memory_usage' => $memoryUsage,
            'disk_usage' => $diskUsage,
            'error' => null,
        ];
    }

    /**
     * Check server Docker status
     */
    private function checkServerDockerStatus(Server $server): array
    {
        $dockerCheck = $this->dockerService->checkDockerInstallation($server);

        return [
            'status' => $dockerCheck['installed'] ? 'installed' : 'not_installed',
            'installed' => $dockerCheck['installed'],
            'version' => $dockerCheck['version'] ?? null,
            'error' => $dockerCheck['error'] ?? null,
        ];
    }

    /**
     * Collect server issues
     */
    private function collectServerIssues(array $checks, Server $server): array
    {
        $issues = [];

        if ($checks['connectivity']['status'] === 'offline') {
            $issues[] = 'Server is offline';
        }

        if ($checks['resources']['status'] === 'critical') {
            if (($checks['resources']['cpu_usage'] ?? 0) > 90) {
                $issues[] = 'Critical CPU usage';
            }
            if (($checks['resources']['memory_usage'] ?? 0) > 90) {
                $issues[] = 'Critical memory usage';
            }
            if (($checks['resources']['disk_usage'] ?? 0) > 90) {
                $issues[] = 'Critical disk usage';
            }
        } elseif ($checks['resources']['status'] === 'warning') {
            $issues[] = 'High resource usage';
        }

        if ($checks['docker']['status'] === 'not_installed') {
            $issues[] = 'Docker is not installed';
        }

        return $issues;
    }


    /**
     * Invalidate health check cache for a project
     */
    public function invalidateProjectCache(Project $project): void
    {
        Cache::forget("project_health_{$project->id}");
        Cache::forget("docker_health_{$project->id}");
    }

    /**
     * Invalidate health check cache for all projects
     */
    public function invalidateAllProjectCaches(): void
    {
        Project::chunk(100, function ($projects) {
            foreach ($projects as $project) {
                $this->invalidateProjectCache($project);
            }
        });
    }

    /**
     * Get cached health data or return stale data with async refresh
     *
     * Returns cached data immediately if available, and dispatches
     * an async job to refresh if cache is stale.
     *
     * @return array|null
     */
    public function getCachedHealthOrRefreshAsync(Project $project): ?array
    {
        $cacheKey = "project_health_{$project->id}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData === null) {
            // No cached data, dispatch async job and return null
            \App\Jobs\CheckProjectHealthJob::dispatchForProject($project);

            return null;
        }

        return $cachedData;
    }

    /**
     * Check if health data exists in cache
     */
    public function hasHealthCache(Project $project): bool
    {
        return Cache::has("project_health_{$project->id}");
    }

    /**
     * Dispatch async health check for a project
     */
    public function dispatchAsyncHealthCheck(Project $project): void
    {
        \App\Jobs\CheckProjectHealthJob::dispatchForProject($project);
    }

    /**
     * Dispatch async health checks for all running projects
     */
    public function dispatchAllAsyncHealthChecks(): void
    {
        \App\Jobs\CheckProjectHealthJob::dispatchForAllProjects();
    }
}
