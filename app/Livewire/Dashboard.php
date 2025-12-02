<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\SSLCertificate;
use App\Models\HealthCheck;
use App\Models\ServerMetric;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $stats = [];
    public $recentDeployments = [];
    public $serverMetrics = [];
    public $projects = [];
    public $sslStats = [];
    public $healthCheckStats = [];
    public $recentActivity = [];
    public $serverHealth = [];
    public $deploymentsToday = 0;

    // New properties for enhanced dashboard
    public bool $showQuickActions = true;
    public bool $showActivityFeed = true;
    public bool $showServerHealth = true;
    public array $queueStats = [];
    public int $overallSecurityScore = 0;
    public array $collapsedSections = [];
    public int $activeDeployments = 0;

    public function mount()
    {
        $this->loadStats();
        $this->loadRecentDeployments();
        $this->loadProjects();
        $this->loadSSLStats();
        $this->loadHealthCheckStats();
        $this->loadDeploymentsToday();
        $this->loadRecentActivity();
        $this->loadServerHealth();
        $this->loadQueueStats();
        $this->loadSecurityScore();
        $this->loadActiveDeployments();
    }

    #[On('refresh-dashboard')]
    public function loadStats()
    {
        // All resources are shared across all users
        $this->stats = [
            'total_servers' => Server::count(),
            'online_servers' => Server::where('status', 'online')->count(),
            'total_projects' => Project::count(),
            'running_projects' => Project::where('status', 'running')->count(),
            'total_deployments' => Deployment::count(),
            'successful_deployments' => Deployment::where('status', 'success')->count(),
            'failed_deployments' => Deployment::where('status', 'failed')->count(),
        ];
    }

    public function loadRecentDeployments()
    {
        // All deployments are shared
        $this->recentDeployments = Deployment::with(['project', 'server'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function loadProjects()
    {
        // All projects are shared
        $this->projects = Project::with(['server', 'domains'])
            ->latest()
            ->take(6)
            ->get();
    }

    public function loadSSLStats()
    {
        $now = now();
        $expiringSoonDate = $now->copy()->addDays(7);

        $this->sslStats = [
            'total_certificates' => SSLCertificate::count(),
            'active_certificates' => SSLCertificate::where('status', 'issued')
                ->where('expires_at', '>', $now)
                ->count(),
            'expiring_soon' => SSLCertificate::where('expires_at', '<=', $expiringSoonDate)
                ->where('expires_at', '>', $now)
                ->count(),
            'expired' => SSLCertificate::where('expires_at', '<=', $now)->count(),
            'pending' => SSLCertificate::where('status', 'pending')->count(),
            'failed' => SSLCertificate::where('status', 'failed')->count(),
            'expiring_certificates' => SSLCertificate::where('expires_at', '<=', $expiringSoonDate)
                ->where('expires_at', '>', $now)
                ->with(['domain', 'server'])
                ->orderBy('expires_at', 'asc')
                ->take(5)
                ->get(),
        ];
    }

    public function loadHealthCheckStats()
    {
        $this->healthCheckStats = [
            'total_checks' => HealthCheck::count(),
            'active_checks' => HealthCheck::where('is_active', true)->count(),
            'healthy' => HealthCheck::where('status', 'healthy')->count(),
            'degraded' => HealthCheck::where('status', 'degraded')->count(),
            'down' => HealthCheck::where('status', 'down')->count(),
            'down_checks' => HealthCheck::where('status', 'down')
                ->with(['project', 'server'])
                ->orderBy('last_failure_at', 'desc')
                ->take(5)
                ->get(),
        ];
    }

    public function loadDeploymentsToday()
    {
        $today = now()->startOfDay();
        $this->deploymentsToday = Deployment::where('created_at', '>=', $today)->count();
    }

    public function loadRecentActivity()
    {
        $deploymentsLimit = 8;
        $projectsLimit = 2;

        // Get recent deployments
        $recentDeployments = Deployment::with(['project', 'user'])
            ->latest()
            ->take($deploymentsLimit)
            ->get()
            ->map(function ($deployment) {
                return [
                    'type' => 'deployment',
                    'id' => $deployment->id,
                    'title' => "Deployment: {$deployment->project->name}",
                    'description' => "Deployment on branch {$deployment->branch} - {$deployment->status}",
                    'status' => $deployment->status,
                    'user' => $deployment->user?->name ?? 'System',
                    'timestamp' => $deployment->created_at,
                    'triggered_by' => $deployment->triggered_by,
                ];
            });

        // Get recent project creations
        $recentProjects = Project::with(['user', 'server'])
            ->latest()
            ->take($projectsLimit)
            ->get()
            ->map(function ($project) {
                return [
                    'type' => 'project_created',
                    'id' => $project->id,
                    'title' => "Project Created: {$project->name}",
                    'description' => "New {$project->framework} project on {$project->server->name}",
                    'status' => $project->status,
                    'user' => $project->user?->name ?? 'System',
                    'timestamp' => $project->created_at,
                    'framework' => $project->framework,
                ];
            });

        // Merge and sort by timestamp
        $this->recentActivity = collect()
            ->merge($recentDeployments)
            ->merge($recentProjects)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    public function loadServerHealth()
    {
        $servers = Server::with('metrics')
            ->where('status', 'online')
            ->get();

        $this->serverHealth = $servers->map(function ($server) {
            // Get latest metric for each server
            $latestMetric = ServerMetric::where('server_id', $server->id)
                ->latest('recorded_at')
                ->first();

            if (!$latestMetric) {
                return [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'cpu_usage' => null,
                    'memory_usage' => null,
                    'disk_usage' => null,
                    'load_average' => null,
                    'status' => $server->status,
                    'recorded_at' => null,
                ];
            }

            return [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'cpu_usage' => (float) $latestMetric->cpu_usage,
                'memory_usage' => (float) $latestMetric->memory_usage,
                'memory_used_mb' => $latestMetric->memory_used_mb,
                'memory_total_mb' => $latestMetric->memory_total_mb,
                'disk_usage' => (float) $latestMetric->disk_usage,
                'disk_used_gb' => $latestMetric->disk_used_gb,
                'disk_total_gb' => $latestMetric->disk_total_gb,
                'load_average_1' => (float) $latestMetric->load_average_1,
                'load_average_5' => (float) $latestMetric->load_average_5,
                'load_average_15' => (float) $latestMetric->load_average_15,
                'network_in_bytes' => $latestMetric->network_in_bytes,
                'network_out_bytes' => $latestMetric->network_out_bytes,
                'status' => $server->status,
                'recorded_at' => $latestMetric->recorded_at,
                'health_status' => $this->getServerHealthStatus(
                    $latestMetric->cpu_usage,
                    $latestMetric->memory_usage,
                    $latestMetric->disk_usage
                ),
            ];
        })->all();
    }

    private function getServerHealthStatus(float $cpu, float $memory, float $disk): string
    {
        // Determine overall health based on resource usage thresholds
        if ($cpu > 90 || $memory > 90 || $disk > 90) {
            return 'critical';
        } elseif ($cpu > 75 || $memory > 75 || $disk > 75) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }

    public function loadQueueStats(): void
    {
        // Check if jobs table exists
        try {
            $this->queueStats = [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            // If jobs table doesn't exist, set default values
            $this->queueStats = [
                'pending' => 0,
                'failed' => 0,
            ];
        }
    }

    public function loadSecurityScore(): void
    {
        // Calculate average security score from servers
        $avgScore = Server::where('status', 'online')
            ->whereNotNull('security_score')
            ->avg('security_score');

        $this->overallSecurityScore = $avgScore ? (int) round($avgScore) : 85;
    }

    public function loadActiveDeployments(): void
    {
        $this->activeDeployments = Deployment::whereIn('status', ['pending', 'running'])
            ->count();
    }

    public function toggleSection(string $section): void
    {
        if (in_array($section, $this->collapsedSections)) {
            $this->collapsedSections = array_values(array_diff($this->collapsedSections, [$section]));
        } else {
            $this->collapsedSections[] = $section;
        }
    }

    #[On('refresh-dashboard')]
    public function refreshDashboard(): void
    {
        $this->loadStats();
        $this->loadRecentDeployments();
        $this->loadProjects();
        $this->loadSSLStats();
        $this->loadHealthCheckStats();
        $this->loadDeploymentsToday();
        $this->loadRecentActivity();
        $this->loadServerHealth();
        $this->loadQueueStats();
        $this->loadSecurityScore();
        $this->loadActiveDeployments();
    }

    public function clearAllCaches(): void
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'All caches cleared successfully!'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to clear caches: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

