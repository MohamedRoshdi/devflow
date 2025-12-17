<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Traits\CacheableStats;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\SSLCertificate;
use App\Models\UserSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Component
 *
 * Main dashboard orchestrator that coordinates child components and handles
 * onboarding, widget management, and user preferences.
 *
 * Child components:
 * - DashboardStats: Statistics cards (main + secondary)
 * - DashboardQuickActions: Quick action buttons
 * - DashboardRecentActivity: Activity feed
 * - DashboardServerHealth: Server health metrics
 *
 * @property bool $isNewUser Whether user is new to the system
 * @property bool $hasCompletedOnboarding Whether user completed onboarding
 * @property array<string, bool> $onboardingSteps Onboarding step completion status
 * @property int $activeDeployments Number of currently running deployments
 * @property array<int, array<string, mixed>> $deploymentTimeline 7-day deployment timeline
 * @property array<int, string> $collapsedSections List of collapsed widget sections
 * @property array<int, string> $widgetOrder Custom widget order
 * @property bool $editMode Dashboard customization mode
 * @property bool $isLoading Initial loading state
 */
class Dashboard extends Component
{
    use CacheableStats;

    // Onboarding state
    public bool $isNewUser = false;

    public bool $hasCompletedOnboarding = false;

    /** @var array<string, bool> */
    public array $onboardingSteps = [
        'add_server' => false,
        'create_project' => false,
        'first_deployment' => false,
        'setup_domain' => false,
    ];

    // Active deployments for header display
    public int $activeDeployments = 0;

    // Deployment timeline data
    /** @var array<int, array<string, mixed>> */
    public array $deploymentTimeline = [];

    // Widget management
    /** @var array<int, string> */
    public array $collapsedSections = [];

    /** @var array<int, string> */
    public array $widgetOrder = [];

    public bool $editMode = false;

    // Lazy loading state
    public bool $isLoading = true;

    // Alert data for system status banner
    public int $healthCheckDown = 0;

    public int $queueFailed = 0;

    // Stats data
    /** @var array<string, int> */
    public array $stats = [];

    // Projects collection
    /** @var Collection<int, Project>|null */
    public ?Collection $projects = null;

    // Recent deployments collection
    /** @var Collection<int, Deployment>|null */
    public ?Collection $recentDeployments = null;

    // SSL statistics
    /** @var array<string, mixed> */
    public array $sslStats = [];

    // Health check statistics
    /** @var array<string, mixed> */
    public array $healthCheckStats = [];

    // Deployments count for today
    public int $deploymentsToday = 0;

    // Recent activity feed
    /** @var array<int, array<string, mixed>> */
    public array $recentActivity = [];

    // Server health data
    /** @var array<int, array<string, mixed>> */
    public array $serverHealth = [];

    // Queue statistics
    /** @var array<string, int> */
    public array $queueStats = [];

    // Overall security score
    public int $overallSecurityScore = 85;

    // Widget visibility toggles
    public bool $showQuickActions = true;

    public bool $showActivityFeed = true;

    public bool $showServerHealth = true;

    // Pagination settings
    public int $activityPerPage = 5;

    // Default widget order
    /** @var array<int, string> */
    public const DEFAULT_WIDGET_ORDER = [
        'getting_started',
        'stats_cards',
        'quick_actions',
        'activity_server_grid',
        'deployment_timeline',
    ];

    public function mount(): void
    {
        $this->loadUserPreferences();
        $this->loadOnboardingStatus();
    }

    /**
     * Lazy load dashboard data - called via wire:init
     */
    public function loadDashboardData(): void
    {
        $this->loadStats();
        $this->loadProjects();
        $this->loadRecentDeployments();
        $this->loadActiveDeployments();
        $this->loadDeploymentTimeline();
        $this->loadAlertData();
        $this->loadRecentActivity();
        $this->loadServerHealth();
        $this->isLoading = false;
    }

    /**
     * Load dashboard statistics
     */
    public function loadStats(): void
    {
        $cachedStats = $this->cacheOrFallback('dashboard_stats', 60, function () {
            $onlineServers = Server::where('status', 'online')->count();
            $totalServers = Server::count();
            $runningProjects = Project::where('status', 'running')->count();
            $totalProjects = Project::count();
            $successfulDeployments = Deployment::where('status', 'success')->count();
            $failedDeployments = Deployment::where('status', 'failed')->count();
            $totalDeployments = Deployment::count();

            return [
                'total_servers' => $totalServers,
                'online_servers' => $onlineServers,
                'total_projects' => $totalProjects,
                'running_projects' => $runningProjects,
                'total_deployments' => $totalDeployments,
                'successful_deployments' => $successfulDeployments,
                'failed_deployments' => $failedDeployments,
            ];
        });

        // Handle corrupted or invalid cache data
        if (! is_array($cachedStats) || ! isset($cachedStats['total_servers'])) {
            Cache::forget('dashboard_stats');
            $this->stats = [
                'total_servers' => 0,
                'online_servers' => 0,
                'total_projects' => 0,
                'running_projects' => 0,
                'total_deployments' => 0,
                'successful_deployments' => 0,
                'failed_deployments' => 0,
            ];
        } else {
            $this->stats = $cachedStats;
        }
    }

    /**
     * Load recent projects
     */
    public function loadProjects(): void
    {
        $this->projects = Project::with(['server', 'domains'])
            ->latest()
            ->limit(6)
            ->get();
    }

    /**
     * Load recent deployments
     */
    public function loadRecentDeployments(): void
    {
        $this->recentDeployments = Deployment::with(['project', 'server'])
            ->select(['id', 'project_id', 'server_id', 'status', 'commit_hash', 'branch', 'created_at', 'completed_at'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Load SSL certificate statistics
     */
    public function loadSSLStats(): void
    {
        $cached = $this->cacheOrFallback('dashboard_ssl_stats', 300, function () {
            $total = SSLCertificate::count();
            $active = SSLCertificate::whereIn('status', ['issued', 'active'])->count();
            $expiringSoon = SSLCertificate::where('expires_at', '<=', now()->addDays(30))
                ->where('expires_at', '>', now())
                ->count();
            $expired = SSLCertificate::where('expires_at', '<', now())->count();
            $pending = SSLCertificate::where('status', 'pending')->count();
            $failed = SSLCertificate::where('status', 'failed')->count();

            return [
                'total_certificates' => $total,
                'active_certificates' => $active,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
                'pending' => $pending,
                'failed' => $failed,
            ];
        });

        // Always fetch fresh expiring certificates to avoid serialization issues
        $expiringCertificates = SSLCertificate::where('expires_at', '<=', now()->addDays(30))
            ->where('expires_at', '>', now())
            ->with('domain')
            ->get();

        $this->sslStats = array_merge(is_array($cached) ? $cached : [], [
            'expiring_certificates' => $expiringCertificates,
        ]);
    }

    /**
     * Load health check statistics
     */
    public function loadHealthCheckStats(): void
    {
        $cached = $this->cacheOrFallback('dashboard_health_stats', 120, function () {
            $total = HealthCheck::count();
            $active = HealthCheck::where('is_active', true)->count();
            $healthy = HealthCheck::where('status', 'healthy')->count();
            $degraded = HealthCheck::where('status', 'degraded')->count();
            $down = HealthCheck::where('status', 'down')->count();

            return [
                'total_checks' => $total,
                'active_checks' => $active,
                'healthy' => $healthy,
                'degraded' => $degraded,
                'down' => $down,
            ];
        });

        // Always fetch fresh down checks to avoid serialization issues
        $downChecks = HealthCheck::where('status', 'down')
            ->with('project')
            ->get();

        $this->healthCheckStats = array_merge(is_array($cached) ? $cached : [], [
            'down_checks' => $downChecks,
        ]);
    }

    /**
     * Load today's deployment count
     */
    public function loadDeploymentsToday(): void
    {
        $this->deploymentsToday = Deployment::whereDate('created_at', today())->count();
    }

    /**
     * Load recent activity feed
     */
    public function loadRecentActivity(): void
    {
        $activities = [];

        // Get recent deployments
        $deployments = Deployment::with('project')
            ->latest()
            ->limit($this->activityPerPage)
            ->get();

        foreach ($deployments as $deployment) {
            $activities[] = [
                'type' => 'deployment',
                'title' => 'Deployment ' . ucfirst($deployment->status),
                'description' => $deployment->project?->name ?? 'Unknown project',
                'status' => $deployment->status,
                'timestamp' => $deployment->created_at,
                'id' => $deployment->id,
            ];
        }

        // Get recent projects
        $projects = Project::latest()
            ->limit($this->activityPerPage)
            ->get();

        foreach ($projects as $project) {
            $activities[] = [
                'type' => 'project',
                'title' => 'Project Created',
                'description' => $project->name,
                'status' => $project->status,
                'timestamp' => $project->created_at,
                'id' => $project->id,
            ];
        }

        // Sort by timestamp descending and limit
        usort($activities, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $this->recentActivity = array_slice($activities, 0, $this->activityPerPage);
    }

    /**
     * Load more activity items
     */
    public function loadMoreActivity(): void
    {
        if ($this->activityPerPage < 20) {
            $this->activityPerPage += 5;
            $this->loadRecentActivity();
        }
    }

    /**
     * Load server health metrics
     */
    public function loadServerHealth(): void
    {
        $this->serverHealth = $this->cacheOrFallback('dashboard_server_health', 60, function () {
            $servers = Server::where('status', 'online')->get();
            $healthData = [];

            foreach ($servers as $server) {
                $metric = ServerMetric::where('server_id', $server->id)
                    ->latest('recorded_at')
                    ->first();

                $cpuUsage = $metric?->cpu_usage;
                $memoryUsage = $metric?->memory_usage;
                $diskUsage = $metric?->disk_usage;

                $healthStatus = 'unknown';
                if ($cpuUsage !== null && $memoryUsage !== null && $diskUsage !== null) {
                    $maxUsage = max($cpuUsage, $memoryUsage, $diskUsage);
                    if ($maxUsage >= 90) {
                        $healthStatus = 'critical';
                    } elseif ($maxUsage >= 75) {
                        $healthStatus = 'warning';
                    } else {
                        $healthStatus = 'healthy';
                    }
                }

                $healthData[] = [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'cpu_usage' => $cpuUsage,
                    'memory_usage' => $memoryUsage,
                    'disk_usage' => $diskUsage,
                    'status' => $server->status,
                    'health_status' => $healthStatus,
                ];
            }

            return $healthData;
        });
    }

    /**
     * Load queue statistics
     */
    public function loadQueueStats(): void
    {
        $this->queueStats = $this->cacheOrFallback('dashboard_queue_stats', 30, function () {
            return [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        });
    }

    /**
     * Load security score
     */
    public function loadSecurityScore(): void
    {
        $this->overallSecurityScore = (int) $this->cacheOrFallback('dashboard_security_score', 300, function () {
            $serversWithScores = Server::whereNotNull('security_score')
                ->where('status', 'online')
                ->avg('security_score');

            return $serversWithScores !== null ? (int) round($serversWithScores) : 85;
        });
    }

    /**
     * Clear all dashboard caches
     */
    public function clearDashboardCache(): void
    {
        $cacheKeys = [
            'dashboard_stats',
            'dashboard_ssl_stats',
            'dashboard_health_stats',
            'dashboard_server_health',
            'dashboard_queue_stats',
            'dashboard_security_score',
            'dashboard_onboarding_status',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear all caches and dispatch notification
     */
    public function clearAllCaches(): void
    {
        $this->clearDashboardCache();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'All dashboard caches cleared successfully!',
        ]);
    }

    /**
     * Deploy all running projects
     */
    public function deployAll(): void
    {
        $runningProjects = Project::where('status', 'running')->get();

        if ($runningProjects->isEmpty()) {
            $this->dispatch('notification', [
                'type' => 'warning',
                'message' => 'No running projects to deploy.',
            ]);

            return;
        }

        foreach ($runningProjects as $project) {
            // Create a deployment record for the project
            $deployment = Deployment::create([
                'project_id' => $project->id,
                'server_id' => $project->server_id,
                'user_id' => Auth::id(),
                'branch' => $project->branch ?? 'main',
                'commit_hash' => $project->current_commit_hash ?? 'HEAD',
                'status' => 'pending',
                'triggered_by' => 'bulk_deploy',
            ]);

            DeployProjectJob::dispatch($deployment);
        }

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "Deployment queued for {$runningProjects->count()} projects.",
        ]);
    }

    /**
     * Refresh onboarding status (clears cache and reloads)
     */
    public function refreshOnboardingStatus(): void
    {
        Cache::forget('dashboard_onboarding_status');
        $this->loadOnboardingStatus();
    }

    /**
     * Load onboarding status to determine if user needs guidance
     */
    public function loadOnboardingStatus(): void
    {
        $onboardingData = $this->cacheOrFallback('dashboard_onboarding_status', 300, function () {
            $counts = DB::select("
                SELECT
                    (SELECT COUNT(*) FROM servers) as server_count,
                    (SELECT COUNT(*) FROM projects) as project_count,
                    (SELECT COUNT(*) FROM deployments) as deployment_count,
                    (SELECT COUNT(*) FROM domains) as domain_count
            ");

            $result = $counts[0];

            return [
                'server_count' => (int) $result->server_count,
                'project_count' => (int) $result->project_count,
                'deployment_count' => (int) $result->deployment_count,
                'domain_count' => (int) $result->domain_count,
            ];
        });

        $serverCount = (int) ($onboardingData['server_count'] ?? 0);
        $projectCount = (int) ($onboardingData['project_count'] ?? 0);
        $deploymentCount = (int) ($onboardingData['deployment_count'] ?? 0);
        $domainCount = (int) ($onboardingData['domain_count'] ?? 0);

        $this->onboardingSteps = [
            'add_server' => $serverCount > 0,
            'create_project' => $projectCount > 0,
            'first_deployment' => $deploymentCount > 0,
            'setup_domain' => $domainCount > 0,
        ];

        $this->isNewUser = $serverCount === 0 && $projectCount === 0;
        $this->hasCompletedOnboarding = ! in_array(false, $this->onboardingSteps, true);
    }

    /**
     * Load alert data for system status banner
     */
    public function loadAlertData(): void
    {
        $alertData = $this->cacheOrFallback('dashboard_alert_data', 120, function () {
            return [
                'health_check_down' => DB::table('health_checks')->where('status', 'down')->count(),
                'queue_failed' => DB::table('failed_jobs')->count(),
            ];
        });

        $this->healthCheckDown = (int) ($alertData['health_check_down'] ?? 0);
        $this->queueFailed = (int) ($alertData['queue_failed'] ?? 0);
    }

    public function loadActiveDeployments(): void
    {
        $this->activeDeployments = Deployment::whereIn('status', ['pending', 'running'])
            ->count();
    }

    public function loadDeploymentTimeline(): void
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $deployments = Deployment::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
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
     * Dismiss the getting started section
     */
    public function dismissGettingStarted(): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $userSettings->updateSetting('dashboard_getting_started_dismissed', true);
            $this->hasCompletedOnboarding = true;

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Getting started section hidden. You can always access features from the sidebar.',
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    public function toggleSection(string $section): void
    {
        if (in_array($section, $this->collapsedSections)) {
            $this->collapsedSections = array_values(array_diff($this->collapsedSections, [$section]));
        } else {
            $this->collapsedSections[] = $section;
        }

        $this->saveCollapsedSections();
    }

    #[On('refresh-dashboard')]
    public function refreshDashboard(): void
    {
        $this->clearDashboardCache();
        $this->forgetCacheKeys([
            'dashboard_onboarding_status',
            'dashboard_alert_data',
        ]);

        $this->loadStats();
        $this->loadProjects();
        $this->loadRecentDeployments();
        $this->loadOnboardingStatus();
        $this->loadActiveDeployments();
        $this->loadDeploymentTimeline();
        $this->loadAlertData();
        $this->loadRecentActivity();
        $this->loadServerHealth();
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        $this->forgetCacheKeys(['dashboard_onboarding_status', 'dashboard_alert_data', 'dashboard_stats']);
        $this->loadStats();
        $this->loadActiveDeployments();
        $this->loadOnboardingStatus();
        $this->loadRecentDeployments();
    }

    /**
     * Load user preferences from database
     */
    private function loadUserPreferences(): void
    {
        if (! Auth::check()) {
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;

            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $collapsedSections = $userSettings->getAdditionalSetting('dashboard_collapsed_sections', []);
            $this->collapsedSections = is_array($collapsedSections) ? array_values(array_filter($collapsedSections, 'is_string')) : [];
            $widgetOrder = $userSettings->getAdditionalSetting('dashboard_widget_order', self::DEFAULT_WIDGET_ORDER);
            $this->widgetOrder = is_array($widgetOrder) ? array_values(array_filter($widgetOrder, 'is_string')) : self::DEFAULT_WIDGET_ORDER;

            $gettingStartedDismissed = $userSettings->getAdditionalSetting('dashboard_getting_started_dismissed', false);
            if ($gettingStartedDismissed) {
                $this->hasCompletedOnboarding = true;
            }

            foreach (self::DEFAULT_WIDGET_ORDER as $widget) {
                if (! in_array($widget, $this->widgetOrder)) {
                    $this->widgetOrder[] = $widget;
                }
            }
        } catch (\Exception $e) {
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;
        }
    }

    /**
     * Save collapsed sections preference to database
     */
    private function saveCollapsedSections(): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $userSettings->updateSetting('dashboard_collapsed_sections', $this->collapsedSections);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Handle widget order update from JavaScript SortableJS
     */
    #[On('widget-order-updated')]
    public function updateWidgetOrder(array $order): void
    {
        $validWidgets = array_intersect($order, self::DEFAULT_WIDGET_ORDER);
        if (count($validWidgets) !== count(self::DEFAULT_WIDGET_ORDER)) {
            return;
        }

        $this->widgetOrder = $order;
        $this->saveWidgetOrder();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Dashboard layout saved!',
        ]);
    }

    /**
     * Save widget order preference to database
     */
    private function saveWidgetOrder(): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $userSettings->updateSetting('dashboard_widget_order', $this->widgetOrder);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Toggle edit mode for dashboard customization
     */
    public function toggleEditMode(): void
    {
        $this->editMode = ! $this->editMode;
    }

    /**
     * Reset widget order to default
     */
    public function resetWidgetOrder(): void
    {
        $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;
        $this->saveWidgetOrder();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Dashboard layout reset to default!',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard');
    }
}
