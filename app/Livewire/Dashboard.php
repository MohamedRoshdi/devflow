<?php

namespace App\Livewire;

use App\Jobs\DeployProjectJob;
use App\Livewire\Traits\CacheableStats;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Component
 *
 * Main dashboard component that displays system overview, server health metrics,
 * deployment statistics, SSL certificate status, and recent activity feed.
 * Features lazy loading for improved performance and user customization options.
 *
 * @property array<string, int> $stats System-wide statistics (servers, projects, deployments)
 * @property \Illuminate\Database\Eloquent\Collection $recentDeployments Recent deployment records
 * @property array<int, mixed> $serverMetrics Server performance metrics
 * @property \Illuminate\Database\Eloquent\Collection $projects Recent projects
 * @property array<string, mixed> $sslStats SSL certificate statistics
 * @property array<string, mixed> $healthCheckStats Health check statistics
 * @property array<int, array<string, mixed>> $recentActivity Recent system activity feed
 * @property array<int, array<string, mixed>> $serverHealth Server health status and metrics
 * @property int $deploymentsToday Number of deployments created today
 * @property bool $showQuickActions Toggle for quick actions widget
 * @property bool $showActivityFeed Toggle for activity feed widget
 * @property bool $showServerHealth Toggle for server health widget
 * @property array<string, int> $queueStats Queue statistics (pending, failed jobs)
 * @property int $overallSecurityScore Calculated overall security score
 * @property array<int, string> $collapsedSections List of collapsed widget sections
 * @property int $activeDeployments Number of currently running deployments
 * @property array<int, array<string, mixed>> $deploymentTimeline 7-day deployment timeline
 * @property int $activityPerPage Number of activity items per page
 * @property bool $loadingMoreActivity Loading state for activity feed pagination
 * @property array<int, string> $widgetOrder Custom widget order for drag-and-drop
 * @property bool $editMode Dashboard customization mode
 * @property bool $isLoading Initial loading state
 * @property bool $isNewUser Whether user is new to the system
 * @property bool $hasCompletedOnboarding Whether user completed onboarding
 * @property array<string, bool> $onboardingSteps Onboarding step completion status
 */
class Dashboard extends Component
{
    use CacheableStats;

    /** @var array<string, int> */
    public array $stats = [
        'total_servers' => 0,
        'online_servers' => 0,
        'total_projects' => 0,
        'running_projects' => 0,
        'total_deployments' => 0,
        'successful_deployments' => 0,
        'failed_deployments' => 0,
    ];

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Deployment>|array<int, mixed> */
    public $recentDeployments = [];

    /** @var array<int, mixed> */
    public array $serverMetrics = [];

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project>|array<int, mixed> */
    public $projects = [];

    /** @var array<string, mixed> */
    public array $sslStats = [];

    /** @var array<string, mixed> */
    public array $healthCheckStats = [];

    /** @var array<int, array<string, mixed>> */
    public array $recentActivity = [];

    /** @var array<int, array<string, mixed>> */
    public array $serverHealth = [];

    public int $deploymentsToday = 0;

    // New properties for enhanced dashboard
    public bool $showQuickActions = true;

    public bool $showActivityFeed = true;

    public bool $showServerHealth = true;

    /** @var array<string, int> */
    public array $queueStats = [];

    public int $overallSecurityScore = 0;

    /** @var array<int, string> */
    public array $collapsedSections = [];

    public int $activeDeployments = 0;

    /** @var array<int, array<string, mixed>> */
    public array $deploymentTimeline = [];

    // Lazy loading properties for activity feed
    public int $activityPerPage = 5;

    public bool $loadingMoreActivity = false;

    // Widget order for drag-and-drop customization
    /** @var array<int, string> */
    public array $widgetOrder = [];

    public bool $editMode = false;

    // Lazy loading state
    public bool $isLoading = true;

    // Default widget order
    /** @var array<int, string> */
    public const DEFAULT_WIDGET_ORDER = [
        'getting_started',
        'stats_cards',
        'quick_actions',
        'activity_server_grid',
        'deployment_timeline',
    ];

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

    public function mount(): void
    {
        // Only load lightweight preferences on mount
        // Heavy data loads via wire:init for better UX
        $this->loadUserPreferences();
        $this->loadOnboardingStatus();
    }

    /**
     * Lazy load all dashboard data - called via wire:init
     */
    public function loadDashboardData(): void
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
        $this->loadDeploymentTimeline();
        $this->isLoading = false;
    }

    /**
     * Load onboarding status to determine if user needs guidance
     */
    public function loadOnboardingStatus(): void
    {
        $serverCount = Server::count();
        $projectCount = Project::count();
        $deploymentCount = Deployment::count();
        $domainCount = \App\Models\Domain::count();

        // Check individual steps
        $this->onboardingSteps = [
            'add_server' => $serverCount > 0,
            'create_project' => $projectCount > 0,
            'first_deployment' => $deploymentCount > 0,
            'setup_domain' => $domainCount > 0,
        ];

        // User is new if they have no servers and no projects
        $this->isNewUser = $serverCount === 0 && $projectCount === 0;

        // Onboarding is complete if all steps are done
        $this->hasCompletedOnboarding = ! in_array(false, $this->onboardingSteps, true);
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

    #[On('refresh-dashboard')]
    public function loadStats(): void
    {
        // Default stats structure to ensure all keys are always present
        $defaultStats = [
            'total_servers' => 0,
            'online_servers' => 0,
            'total_projects' => 0,
            'running_projects' => 0,
            'total_deployments' => 0,
            'successful_deployments' => 0,
            'failed_deployments' => 0,
        ];

        // All resources are shared across all users
        // Cache for 60 seconds to improve performance
        $cachedStats = $this->cacheOrFallback('dashboard_stats', 60, function () {
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

        // Merge with defaults to ensure all keys exist (handles corrupted cache data)
        $this->stats = array_merge($defaultStats, is_array($cachedStats) ? $cachedStats : []);
    }

    public function loadRecentDeployments(): void
    {
        // All deployments are shared
        $this->recentDeployments = Deployment::with(['project', 'server'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function loadProjects(): void
    {
        // All projects are shared
        $this->projects = Project::with(['server', 'domains'])
            ->latest()
            ->take(6)
            ->get();
    }

    public function loadSSLStats(): void
    {
        // Cache for 5 minutes (300 seconds) - SSL data doesn't change frequently
        $this->sslStats = $this->cacheOrFallback('dashboard_ssl_stats', 300, function () {
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
                    ->with(['domain', 'server'])
                    ->orderBy('expires_at', 'asc')
                    ->take(5)
                    ->get(),
            ];
        });
    }

    public function loadHealthCheckStats(): void
    {
        // Cache for 2 minutes (120 seconds) - health checks update frequently
        $this->healthCheckStats = $this->cacheOrFallback('dashboard_health_stats', 120, function () {
            return [
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
        });
    }

    public function loadDeploymentsToday(): void
    {
        $today = now()->startOfDay();
        $this->deploymentsToday = Deployment::where('created_at', '>=', $today)->count();
    }

    public function loadRecentActivity(): void
    {
        $deploymentsLimit = 4;
        $projectsLimit = 1;

        // Get recent deployments
        $recentDeployments = Deployment::with(['project', 'user'])
            ->latest()
            ->take($deploymentsLimit)
            ->get()
            ->map(function ($deployment) {
                return [
                    'type' => 'deployment',
                    'id' => $deployment->id,
                    'title' => 'Deployment: '.($deployment->project?->name ?? 'Unknown'),
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
            ->take($this->activityPerPage)
            ->values()
            ->all();
    }

    public function loadMoreActivity(): void
    {
        $this->loadingMoreActivity = true;

        // Calculate current count and check max limit
        $currentCount = count($this->recentActivity);
        $maxItems = 20;

        if ($currentCount >= $maxItems) {
            $this->loadingMoreActivity = false;

            return;
        }

        // Calculate how many more items to load
        $itemsToLoad = min($this->activityPerPage, $maxItems - $currentCount);

        // Get additional deployments
        $deploymentsToLoad = (int) ceil($itemsToLoad * 0.8); // 80% deployments
        $projectsToLoad = (int) ceil($itemsToLoad * 0.2); // 20% projects

        // Get more recent deployments (skip already loaded)
        $recentDeployments = Deployment::with(['project', 'user'])
            ->latest()
            ->skip($currentCount)
            ->take($deploymentsToLoad)
            ->get()
            ->map(function ($deployment) {
                return [
                    'type' => 'deployment',
                    'id' => $deployment->id,
                    'title' => 'Deployment: '.($deployment->project?->name ?? 'Unknown'),
                    'description' => "Deployment on branch {$deployment->branch} - {$deployment->status}",
                    'status' => $deployment->status,
                    'user' => $deployment->user?->name ?? 'System',
                    'timestamp' => $deployment->created_at,
                    'triggered_by' => $deployment->triggered_by,
                ];
            });

        // Get more recent project creations (skip already loaded)
        $currentProjectsCount = collect($this->recentActivity)
            ->where('type', 'project_created')
            ->count();

        $recentProjects = Project::with(['user', 'server'])
            ->latest()
            ->skip($currentProjectsCount)
            ->take($projectsToLoad)
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

        // Merge new items with existing activity
        $this->recentActivity = collect($this->recentActivity)
            ->merge($recentDeployments)
            ->merge($recentProjects)
            ->sortByDesc('timestamp')
            ->take($maxItems)
            ->values()
            ->all();

        $this->loadingMoreActivity = false;
    }

    public function loadServerHealth(): void
    {
        // Default server entry structure to ensure all keys are present
        $defaultServerEntry = [
            'server_id' => 0,
            'server_name' => 'Unknown',
            'cpu_usage' => null,
            'memory_usage' => null,
            'disk_usage' => null,
            'load_average' => null,
            'status' => 'unknown',
            'recorded_at' => null,
            'health_status' => 'unknown',
        ];

        // Cache for 1 minute (60 seconds) - server metrics change frequently
        $cachedHealth = $this->cacheOrFallback('dashboard_server_health', 60, function () {
            $servers = Server::with(['latestMetric'])
                ->where('status', 'online')
                ->get();

            return $servers->map(function ($server) {
                $latestMetric = $server->latestMetric;

                if (! $latestMetric) {
                    return [
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                        'cpu_usage' => null,
                        'memory_usage' => null,
                        'disk_usage' => null,
                        'load_average' => null,
                        'status' => $server->status,
                        'recorded_at' => null,
                        'health_status' => 'unknown',
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

        // Sanitize cached data to ensure all server entries have required keys
        $this->serverHealth = is_array($cachedHealth) ? array_map(
            fn ($server) => is_array($server) ? array_merge($defaultServerEntry, $server) : $defaultServerEntry,
            $cachedHealth
        ) : [];
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
        // Cache for 30 seconds - queue stats change frequently
        $this->queueStats = $this->cachedStats('dashboard_queue_stats', 30, function () {
            return [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        }, ['pending' => 0, 'failed' => 0]);
    }

    public function loadSecurityScore(): void
    {
        // Cache for 5 minutes (300 seconds) - security scores don't change frequently
        $this->overallSecurityScore = $this->cacheOrFallback('dashboard_security_score', 300, function () {
            $avgScore = Server::where('status', 'online')
                ->whereNotNull('security_score')
                ->avg('security_score');

            return $avgScore ? (int) round($avgScore) : 85;
        });
    }

    public function loadActiveDeployments(): void
    {
        $this->activeDeployments = Deployment::whereIn('status', ['pending', 'running'])
            ->count();
    }

    public function loadDeploymentTimeline(): void
    {
        // Get deployments from last 7 days grouped by date
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        // Get all deployments in the last 7 days
        // Use single quotes for string literals for SQLite compatibility
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

        // Build timeline for all 7 days (including days with no deployments)
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

    public function toggleSection(string $section): void
    {
        if (in_array($section, $this->collapsedSections)) {
            $this->collapsedSections = array_values(array_diff($this->collapsedSections, [$section]));
        } else {
            $this->collapsedSections[] = $section;
        }

        // Save the collapsed sections preference to database
        $this->saveCollapsedSections();
    }

    /**
     * Clear all dashboard-related caches
     */
    public function clearDashboardCache(): void
    {
        $this->forgetCacheKeys([
            'dashboard_stats',
            'dashboard_ssl_stats',
            'dashboard_health_stats',
            'dashboard_server_health',
            'dashboard_queue_stats',
            'dashboard_security_score',
        ]);
    }

    #[On('refresh-dashboard')]
    public function refreshDashboard(): void
    {
        // Clear all dashboard caches to force refresh
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
        // Clear relevant caches when deployment completes
        $this->forgetCacheKeys(['dashboard_stats', 'dashboard_health_stats']);

        // Reload the affected data
        $this->loadStats();
        $this->loadHealthCheckStats();
        $this->loadActiveDeployments();
    }

    public function clearAllCaches(): void
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            // Also clear dashboard-specific caches
            $this->clearDashboardCache();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'All caches cleared successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to clear caches: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Deploy all active projects
     */
    public function deployAll(): void
    {
        try {
            $projects = Project::whereIn('status', ['active', 'running'])
                ->whereNotNull('server_id')
                ->get();

            if ($projects->isEmpty()) {
                $this->dispatch('notification', [
                    'type' => 'warning',
                    'message' => 'No active projects found to deploy.',
                ]);

                return;
            }

            $deploymentCount = 0;
            foreach ($projects as $project) {
                // Create a new deployment record
                $deployment = Deployment::create([
                    'project_id' => $project->id,
                    'server_id' => $project->server_id,
                    'user_id' => Auth::id(),
                    'branch' => $project->branch ?? 'main',
                    'commit_hash' => 'pending',
                    'status' => 'pending',
                    'triggered_by' => 'manual',
                ]);

                // Dispatch the deployment job
                DeployProjectJob::dispatch($deployment);
                $deploymentCount++;
            }

            $this->loadActiveDeployments();

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Deploying {$deploymentCount} projects. Check deployments page for progress.",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to deploy projects: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Load user preferences from database
     */
    private function loadUserPreferences(): void
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;

            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $this->collapsedSections = $userSettings->getAdditionalSetting('dashboard_collapsed_sections', []);
            $this->widgetOrder = $userSettings->getAdditionalSetting('dashboard_widget_order', self::DEFAULT_WIDGET_ORDER);

            // Check if getting started was dismissed
            $gettingStartedDismissed = $userSettings->getAdditionalSetting('dashboard_getting_started_dismissed', false);
            if ($gettingStartedDismissed) {
                $this->hasCompletedOnboarding = true;
            }

            // Ensure all default widgets are present (in case new widgets are added)
            foreach (self::DEFAULT_WIDGET_ORDER as $widget) {
                if (! in_array($widget, $this->widgetOrder)) {
                    $this->widgetOrder[] = $widget;
                }
            }
        } catch (\Exception $e) {
            // If there's any error loading preferences, default to empty array
            $this->collapsedSections = [];
            $this->widgetOrder = self::DEFAULT_WIDGET_ORDER;
        }
    }

    /**
     * Save collapsed sections preference to database
     */
    private function saveCollapsedSections(): void
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $userSettings->updateSetting('dashboard_collapsed_sections', $this->collapsedSections);
        } catch (\Exception $e) {
            // Silently fail - user preferences are not critical
            // You could log this error if needed
        }
    }

    /**
     * Handle widget order update from JavaScript SortableJS
     */
    #[On('widget-order-updated')]
    public function updateWidgetOrder(array $order): void
    {
        // Validate that all widgets are present
        $validWidgets = array_intersect($order, self::DEFAULT_WIDGET_ORDER);
        if (count($validWidgets) !== count(self::DEFAULT_WIDGET_ORDER)) {
            return; // Invalid order, ignore
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
        // Check if user is authenticated
        if (! Auth::check()) {
            return;
        }

        try {
            $userSettings = UserSettings::getForUser(Auth::user());
            $userSettings->updateSetting('dashboard_widget_order', $this->widgetOrder);
        } catch (\Exception $e) {
            // Silently fail - user preferences are not critical
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
