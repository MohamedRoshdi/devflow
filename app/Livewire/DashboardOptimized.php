<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Server;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\SSLCertificate;
use App\Models\HealthCheck;
use App\Models\ServerMetric;
use App\Models\UserSettings;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Optimized Dashboard Component with Cache Tags and Eager Loading
 *
 * Performance improvements:
 * - Extended cache times from 60s to 300s (5 minutes) for stats
 * - Added cache tags for efficient invalidation
 * - Optimized queries with proper indexes
 * - Reduced N+1 queries with eager loading
 */
class DashboardOptimized extends Component
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
    public array $deploymentTimeline = [];

    // Lazy loading properties for activity feed
    public int $activityPerPage = 5;
    public bool $loadingMoreActivity = false;

    // Widget order for drag-and-drop customization
    public array $widgetOrder = [];
    public bool $editMode = false;

    // Default widget order
    public const DEFAULT_WIDGET_ORDER = [
        'stats_cards',
        'quick_actions',
        'activity_server_grid',
        'deployment_timeline',
    ];

    public function mount()
    {
        $this->loadUserPreferences();
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
        $this->loadDeploymentTimeline();
    }

    #[On('refresh-dashboard')]
    public function loadStats()
    {
        // Cache for 5 minutes (300 seconds) with tags for efficient invalidation
        try {
            $this->stats = Cache::tags(['dashboard', 'stats'])->remember('dashboard_stats_v2', 300, function () {
                return [
                    'total_servers' => Server::count(),
                    'online_servers' => Server::where('status', 'online')->count(),
                    'total_projects' => Project::count(),
                    'running_projects' => Project::where('status', 'running')->count(),
                    'total_deployments' => Deployment::count(),
                    'successful_deployments' => Deployment::where('status', 'success')->count(),
                    'failed_deployments' => Deployment::where('status', 'failed')->count(),
                ];
            });
        } catch (\Exception $e) {
            // If Redis is not available, fetch directly without caching
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
    }

    public function loadRecentDeployments()
    {
        // Optimized with eager loading to prevent N+1 queries
        $this->recentDeployments = Deployment::with(['project:id,name,slug', 'server:id,name'])
            ->select(['id', 'project_id', 'server_id', 'status', 'branch', 'commit_message', 'created_at'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function loadProjects()
    {
        // Optimized with eager loading and select specific columns
        $this->projects = Project::with(['server:id,name', 'domains:id,project_id,domain,subdomain'])
            ->select(['id', 'name', 'slug', 'status', 'server_id', 'framework', 'created_at'])
            ->latest()
            ->take(6)
            ->get();
    }

    public function loadSSLStats()
    {
        // Cache for 5 minutes with tags
        try {
            $this->sslStats = Cache::tags(['dashboard', 'ssl'])->remember('dashboard_ssl_stats_v2', 300, function () {
                $now = now();
                $expiringSoonDate = $now->copy()->addDays(7);

                return [
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
                        ->with(['domain:id,domain,subdomain', 'server:id,name'])
                        ->select(['id', 'domain_id', 'server_id', 'expires_at', 'status'])
                        ->orderBy('expires_at', 'asc')
                        ->take(5)
                        ->get(),
                ];
            });
        } catch (\Exception $e) {
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
                    ->with(['domain:id,domain,subdomain', 'server:id,name'])
                    ->select(['id', 'domain_id', 'server_id', 'expires_at', 'status'])
                    ->orderBy('expires_at', 'asc')
                    ->take(5)
                    ->get(),
            ];
        }
    }

    public function loadHealthCheckStats()
    {
        // Cache for 2 minutes with tags
        try {
            $this->healthCheckStats = Cache::tags(['dashboard', 'health'])->remember('dashboard_health_stats_v2', 120, function () {
                return [
                    'total_checks' => HealthCheck::count(),
                    'active_checks' => HealthCheck::where('is_active', true)->count(),
                    'healthy' => HealthCheck::where('status', 'healthy')->count(),
                    'degraded' => HealthCheck::where('status', 'degraded')->count(),
                    'down' => HealthCheck::where('status', 'down')->count(),
                    'down_checks' => HealthCheck::where('status', 'down')
                        ->with(['project:id,name', 'server:id,name'])
                        ->select(['id', 'project_id', 'server_id', 'status', 'last_failure_at'])
                        ->orderBy('last_failure_at', 'desc')
                        ->take(5)
                        ->get(),
                ];
            });
        } catch (\Exception $e) {
            $this->healthCheckStats = [
                'total_checks' => HealthCheck::count(),
                'active_checks' => HealthCheck::where('is_active', true)->count(),
                'healthy' => HealthCheck::where('status', 'healthy')->count(),
                'degraded' => HealthCheck::where('status', 'degraded')->count(),
                'down' => HealthCheck::where('status', 'down')->count(),
                'down_checks' => HealthCheck::where('status', 'down')
                    ->with(['project:id,name', 'server:id,name'])
                    ->select(['id', 'project_id', 'server_id', 'status', 'last_failure_at'])
                    ->orderBy('last_failure_at', 'desc')
                    ->take(5)
                    ->get(),
            ];
        }
    }

    public function loadDeploymentsToday()
    {
        $today = now()->startOfDay();
        // Use index on created_at for faster query
        $this->deploymentsToday = Deployment::where('created_at', '>=', $today)->count();
    }

    public function loadRecentActivity()
    {
        $deploymentsLimit = 4;
        $projectsLimit = 1;

        // Optimized with eager loading
        $recentDeployments = Deployment::with(['project:id,name', 'user:id,name'])
            ->select(['id', 'project_id', 'user_id', 'branch', 'status', 'triggered_by', 'created_at'])
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

        $recentProjects = Project::with(['user:id,name', 'server:id,name'])
            ->select(['id', 'name', 'framework', 'server_id', 'user_id', 'status', 'created_at'])
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

        $this->recentActivity = collect()
            ->merge($recentDeployments)
            ->merge($recentProjects)
            ->sortByDesc('timestamp')
            ->take($this->activityPerPage)
            ->values()
            ->all();
    }

    public function loadServerHealth()
    {
        // Cache for 2 minutes with tags
        try {
            $this->serverHealth = Cache::tags(['dashboard', 'server_health'])->remember('dashboard_server_health_v2', 120, function () {
                // Optimized: Select only needed columns
                $servers = Server::where('status', 'online')
                    ->select(['id', 'name', 'status'])
                    ->get();

                return $servers->map(function ($server) {
                    // Use optimized index: server_id + recorded_at
                    $latestMetric = ServerMetric::where('server_id', $server->id)
                        ->select([
                            'cpu_usage', 'memory_usage', 'disk_usage',
                            'memory_used_mb', 'memory_total_mb',
                            'disk_used_gb', 'disk_total_gb',
                            'load_average_1', 'load_average_5', 'load_average_15',
                            'network_in_bytes', 'network_out_bytes', 'recorded_at'
                        ])
                        ->orderBy('recorded_at', 'desc')
                        ->first();

                    if (!$latestMetric) {
                        return [
                            'server_id' => $server->id,
                            'server_name' => $server->name,
                            'cpu_usage' => null,
                            'memory_usage' => null,
                            'disk_usage' => null,
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
            });
        } catch (\Exception $e) {
            // Fallback without caching
            $servers = Server::where('status', 'online')
                ->select(['id', 'name', 'status'])
                ->get();

            $this->serverHealth = $servers->map(function ($server) {
                $latestMetric = ServerMetric::where('server_id', $server->id)
                    ->orderBy('recorded_at', 'desc')
                    ->first();

                if (!$latestMetric) {
                    return [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'cpu_usage' => null,
                        'memory_usage' => null,
                        'disk_usage' => null,
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
    }

    private function getServerHealthStatus(float $cpu, float $memory, float $disk): string
    {
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
        // Cache for 1 minute with tags
        try {
            $this->queueStats = Cache::tags(['dashboard', 'queue'])->remember('dashboard_queue_stats_v2', 60, function () {
                try {
                    return [
                        'pending' => DB::table('jobs')->count(),
                        'failed' => DB::table('failed_jobs')->count(),
                    ];
                } catch (\Exception $e) {
                    return [
                        'pending' => 0,
                        'failed' => 0,
                    ];
                }
            });
        } catch (\Exception $e) {
            try {
                $this->queueStats = [
                    'pending' => DB::table('jobs')->count(),
                    'failed' => DB::table('failed_jobs')->count(),
                ];
            } catch (\Exception $e) {
                $this->queueStats = [
                    'pending' => 0,
                    'failed' => 0,
                ];
            }
        }
    }

    public function loadSecurityScore(): void
    {
        // Cache for 5 minutes with tags
        try {
            $this->overallSecurityScore = Cache::tags(['dashboard', 'security'])->remember('dashboard_security_score_v2', 300, function () {
                $avgScore = Server::where('status', 'online')
                    ->whereNotNull('security_score')
                    ->avg('security_score');

                return $avgScore ? (int) round($avgScore) : 85;
            });
        } catch (\Exception $e) {
            $avgScore = Server::where('status', 'online')
                ->whereNotNull('security_score')
                ->avg('security_score');

            $this->overallSecurityScore = $avgScore ? (int) round($avgScore) : 85;
        }
    }

    public function loadActiveDeployments(): void
    {
        // Use status index for fast query
        $this->activeDeployments = Deployment::whereIn('status', ['pending', 'running'])
            ->count();
    }

    public function loadDeploymentTimeline(): void
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        // Optimized with proper indexes on status and created_at
        $deployments = Deployment::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $this->deploymentTimeline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateFormatted = now()->subDays($i)->format('M d');

            $deployment = $deployments->get($date);

            if ($deployment) {
                $total = (int) $deployment->total;
                $successful = (int) $deployment->successful;
                $failed = (int) $deployment->failed;

                $successPercent = $total > 0 ? ($successful / $total) * 100 : 0;
                $failedPercent = $total > 0 ? ($failed / $total) * 100 : 0;
            } else {
                $total = 0;
                $successful = 0;
                $failed = 0;
                $successPercent = 0;
                $failedPercent = 0;
            }

            $this->deploymentTimeline[] = [
                'date' => $dateFormatted,
                'full_date' => $date,
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'success_percent' => round($successPercent, 1),
                'failed_percent' => round($failedPercent, 1),
            ];
        }
    }

    /**
     * Clear all dashboard-related caches using tags for efficient invalidation
     */
    public function clearDashboardCache(): void
    {
        try {
            // Use cache tags for efficient bulk invalidation
            Cache::tags(['dashboard'])->flush();
        } catch (\Exception $e) {
            // Silently fail if Redis is not available
        }
    }

    #[On('refresh-dashboard')]
    public function refreshDashboard(): void
    {
        $this->clearDashboardCache();

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

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        // Clear relevant caches using tags
        try {
            Cache::tags(['dashboard', 'stats'])->flush();
            Cache::tags(['dashboard', 'health'])->flush();
        } catch (\Exception $e) {
            // Silently fail if Redis is not available
        }

        $this->loadStats();
        $this->loadHealthCheckStats();
        $this->loadActiveDeployments();
    }

    private function loadUserPreferences(): void
    {
        if (!Auth::check()) {
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $this->collapsedSections = $userSettings->getAdditionalSetting('dashboard_collapsed_sections', []);
            $this->widgetOrder = $userSettings->getAdditionalSetting('dashboard_widget_order', self::DEFAULT_WIDGET_ORDER);

            foreach (self::DEFAULT_WIDGET_ORDER as $widget) {
                if (!in_array($widget, $this->widgetOrder)) {
                    $this->widgetOrder[] = $widget;
                }
            }
        } catch (\Exception $e) {
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
