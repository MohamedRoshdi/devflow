<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Livewire\Component;

class HealthDashboard extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $projectsHealth = [];

    /** @var array<int, array<string, mixed>> */
    public array $serversHealth = [];

    public bool $isLoading = true;

    public string $filterStatus = 'all';

    public ?string $lastCheckedAt = null;

    public function mount(): void
    {
        // Check if user has permission to view health checks
        $user = auth()->user();
        abort_unless(
            $user && $user->can('view-health-checks'),
            403,
            'You do not have permission to view health dashboard.'
        );

        // Don't load data on mount - use wire:init for lazy loading
        // This allows the page to render immediately with a loading state
    }

    public function loadHealthData()
    {
        $this->isLoading = true;

        $this->loadProjectsHealth();
        $this->loadServersHealth();

        $this->lastCheckedAt = now()->toISOString();
        $this->isLoading = false;
    }

    protected function loadProjectsHealth()
    {
        // All projects are shared across all users
        // Eager load only the latest deployment to avoid N+1 queries
        $projects = Project::with([
            'server',
            'domains',
            'deployments' => function ($query) {
                $query->latest()->limit(1);
            },
        ])->get();

        $this->projectsHealth = $projects->map(function ($project) {
            $cacheKey = "project_health_{$project->id}";

            return Cache::remember($cacheKey, 60, function () use ($project) {
                return $this->checkProjectHealth($project);
            });
        })->toArray();
    }

    protected function loadServersHealth()
    {
        // All servers are shared across all users
        // Eager load projects count to avoid N+1 queries
        $servers = Server::withCount('projects')->get();

        $this->serversHealth = $servers->map(function ($server) {
            $cacheKey = "server_health_{$server->id}";

            return Cache::remember($cacheKey, 60, function () use ($server) {
                return $this->checkServerHealth($server);
            });
        })->toArray();
    }

    protected function checkProjectHealth(Project $project): array
    {
        $health = [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'status' => $project->status,
            'server_name' => $project->server->name ?? 'Unknown',
            'last_deployment' => null,
            'last_deployment_status' => null,
            'uptime_status' => 'unknown',
            'response_time' => null,
            'health_score' => 0,
            'issues' => [],
        ];

        // Check last deployment (already eager loaded)
        $lastDeployment = $project->deployments->first();
        if ($lastDeployment) {
            $health['last_deployment'] = $lastDeployment->created_at->diffForHumans();
            $health['last_deployment_status'] = $lastDeployment->status;

            if ($lastDeployment->status === 'failed') {
                $health['issues'][] = 'Last deployment failed';
            }
        } else {
            $health['issues'][] = 'No deployments yet';
        }

        // Check HTTP health if URL available
        if ($project->health_check_url) {
            $httpHealth = $this->checkHttpHealth($project->health_check_url);
            $health['uptime_status'] = $httpHealth['status'];
            $health['response_time'] = $httpHealth['response_time'];

            if ($httpHealth['status'] !== 'healthy') {
                $health['issues'][] = 'Health check endpoint not responding';
            }
        } elseif ($project->domains->isNotEmpty()) {
            // Try the primary domain
            $domain = $project->domains->first();
            $url = "https://{$domain->full_domain}";
            $httpHealth = $this->checkHttpHealth($url);
            $health['uptime_status'] = $httpHealth['status'];
            $health['response_time'] = $httpHealth['response_time'];

            if ($httpHealth['status'] !== 'healthy') {
                $health['issues'][] = 'Domain not responding';
            }
        }

        // Check project status
        if ($project->status === 'stopped') {
            $health['issues'][] = 'Project is stopped';
        } elseif ($project->status === 'failed') {
            $health['issues'][] = 'Project is in failed state';
        }

        // Calculate health score (0-100)
        $health['health_score'] = $this->calculateHealthScore($health);

        return $health;
    }

    protected function checkServerHealth(Server $server): array
    {
        $health = [
            'id' => $server->id,
            'name' => $server->name,
            'ip_address' => $server->ip_address,
            'status' => $server->status,
            'projects_count' => $server->projects_count ?? 0,
            'cpu_usage' => null,
            'ram_usage' => null,
            'disk_usage' => null,
            'uptime' => null,
            'health_score' => 0,
            'issues' => [],
        ];

        // Try to get server metrics via SSH
        if ($server->status === 'online') {
            try {
                $metrics = $this->getServerMetrics($server);
                $health = array_merge($health, $metrics);

                // Check for high resource usage
                if ($metrics['cpu_usage'] && $metrics['cpu_usage'] > 90) {
                    $health['issues'][] = 'High CPU usage';
                }
                if ($metrics['ram_usage'] && $metrics['ram_usage'] > 90) {
                    $health['issues'][] = 'High RAM usage';
                }
                if ($metrics['disk_usage'] && $metrics['disk_usage'] > 90) {
                    $health['issues'][] = 'Low disk space';
                }
            } catch (\Exception $e) {
                $health['issues'][] = 'Failed to fetch metrics';
            }
        } else {
            $health['issues'][] = 'Server is offline';
        }

        // Calculate health score
        $health['health_score'] = $this->calculateServerHealthScore($health);

        return $health;
    }

    protected function checkHttpHealth(string $url): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)->get($url);
            $responseTime = round((microtime(true) - $startTime) * 1000); // ms

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $responseTime,
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'response_time' => null,
                'http_code' => null,
            ];
        }
    }

    protected function getServerMetrics(Server $server): array
    {
        $sshOptions = "-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=5 -p {$server->port}";
        $sshCommand = "ssh {$sshOptions} {$server->username}@{$server->ip_address}";

        // Get CPU usage
        $cpuResult = Process::timeout(10)->run("{$sshCommand} 'top -bn1 | grep \"Cpu(s)\" | awk \"{print \\$2}\" | cut -d\".\" -f1'");
        $cpuUsage = $cpuResult->successful() ? (int) trim($cpuResult->output()) : null;

        // Get RAM usage
        $ramResult = Process::timeout(10)->run("{$sshCommand} 'free | grep Mem | awk \"{print \\$3/\\$2 * 100.0}\"'");
        $ramUsage = $ramResult->successful() ? round((float) trim($ramResult->output())) : null;

        // Get disk usage
        $diskResult = Process::timeout(10)->run("{$sshCommand} 'df -h / | tail -1 | awk \"{print \\$5}\" | tr -d \"%\"'");
        $diskUsage = $diskResult->successful() ? (int) trim($diskResult->output()) : null;

        // Get uptime
        $uptimeResult = Process::timeout(10)->run("{$sshCommand} 'uptime -p'");
        $uptime = $uptimeResult->successful() ? trim($uptimeResult->output()) : null;

        return [
            'cpu_usage' => $cpuUsage,
            'ram_usage' => $ramUsage,
            'disk_usage' => $diskUsage,
            'uptime' => $uptime,
        ];
    }

    protected function calculateHealthScore(array $health): int
    {
        $score = 100;

        // Deduct for issues
        $score -= count($health['issues']) * 15;

        // Deduct for status
        if ($health['status'] !== 'running') {
            $score -= 30;
        }

        // Deduct for uptime status
        if ($health['uptime_status'] === 'unreachable') {
            $score -= 40;
        } elseif ($health['uptime_status'] === 'unhealthy') {
            $score -= 20;
        }

        // Deduct for slow response time
        if ($health['response_time'] && $health['response_time'] > 2000) {
            $score -= 10;
        }

        // Deduct for failed last deployment
        if ($health['last_deployment_status'] === 'failed') {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }

    protected function calculateServerHealthScore(array $health): int
    {
        $score = 100;

        // Deduct for issues
        $score -= count($health['issues']) * 15;

        // Deduct for status
        if ($health['status'] !== 'online') {
            $score -= 50;
        }

        // Deduct for high resource usage
        if ($health['cpu_usage'] && $health['cpu_usage'] > 80) {
            $score -= ($health['cpu_usage'] - 80) / 2;
        }
        if ($health['ram_usage'] && $health['ram_usage'] > 80) {
            $score -= ($health['ram_usage'] - 80) / 2;
        }
        if ($health['disk_usage'] && $health['disk_usage'] > 80) {
            $score -= ($health['disk_usage'] - 80) / 2;
        }

        return max(0, min(100, (int) $score));
    }

    public function refreshHealth()
    {
        // Clear cache for all projects and servers
        foreach ($this->projectsHealth as $project) {
            Cache::forget("project_health_{$project['id']}");
        }
        foreach ($this->serversHealth as $server) {
            Cache::forget("server_health_{$server['id']}");
        }

        $this->loadHealthData();
    }

    public function getFilteredProjects(): array
    {
        if ($this->filterStatus === 'all') {
            return $this->projectsHealth;
        }

        return array_filter($this->projectsHealth, function ($project) {
            if ($this->filterStatus === 'healthy') {
                return $project['health_score'] >= 80;
            } elseif ($this->filterStatus === 'warning') {
                return $project['health_score'] >= 50 && $project['health_score'] < 80;
            } elseif ($this->filterStatus === 'critical') {
                return $project['health_score'] < 50;
            }

            return true;
        });
    }

    public function getOverallStats(): array
    {
        $total = count($this->projectsHealth);
        $healthy = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] >= 80));
        $warning = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] >= 50 && $p['health_score'] < 80));
        $critical = count(array_filter($this->projectsHealth, fn ($p) => $p['health_score'] < 50));

        $avgScore = $total > 0
            ? round(array_sum(array_column($this->projectsHealth, 'health_score')) / $total)
            : 0;

        return [
            'total' => $total,
            'healthy' => $healthy,
            'warning' => $warning,
            'critical' => $critical,
            'avg_score' => $avgScore,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.health-dashboard', [
            'filteredProjects' => $this->getFilteredProjects(),
            'stats' => $this->getOverallStats(),
        ]);
    }
}
