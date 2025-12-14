<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Traits\CacheableStats;
use App\Models\Deployment;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Auth;
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
        $this->loadActiveDeployments();
        $this->loadDeploymentTimeline();
        $this->loadAlertData();
        $this->isLoading = false;
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
        $this->forgetCacheKeys([
            'dashboard_onboarding_status',
            'dashboard_alert_data',
        ]);

        $this->loadOnboardingStatus();
        $this->loadActiveDeployments();
        $this->loadDeploymentTimeline();
        $this->loadAlertData();
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        $this->forgetCacheKeys(['dashboard_onboarding_status', 'dashboard_alert_data']);
        $this->loadActiveDeployments();
        $this->loadOnboardingStatus();
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
