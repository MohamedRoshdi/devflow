<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Traits\CacheableStats;
use App\Models\Deployment;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Server;
use App\Models\SSLCertificate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Stats Component
 *
 * Displays main statistics cards and secondary stats for the dashboard.
 * Handles servers, projects, deployments, security score, SSL, health checks, and queue stats.
 *
 * @property array<string, int> $stats System-wide statistics
 * @property int $deploymentsToday Number of deployments created today
 * @property int $activeDeployments Number of currently running deployments
 * @property int $overallSecurityScore Calculated overall security score
 * @property array<string, mixed> $sslStats SSL certificate statistics
 * @property array<string, mixed> $healthCheckStats Health check statistics
 * @property array<string, int> $queueStats Queue statistics
 * @property bool $isLoading Initial loading state
 */
class DashboardStats extends Component
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

    public int $deploymentsToday = 0;

    public int $activeDeployments = 0;

    public int $overallSecurityScore = 0;

    /** @var array<string, mixed> */
    public array $sslStats = [];

    /** @var array<string, mixed> */
    public array $healthCheckStats = [];

    /** @var array<string, int> */
    public array $queueStats = [];

    public bool $isLoading = false;

    public bool $hasError = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->loadStats();
    }

    /**
     * Load all stats data - called via wire:init from parent
     */
    public function loadStats(): void
    {
        try {
            $this->hasError = false;
            $this->errorMessage = '';
            $this->loadMainStats();
            $this->loadDeploymentsToday();
            $this->loadActiveDeployments();
            $this->loadSecurityScore();
            $this->loadSSLStats();
            $this->loadHealthCheckStats();
            $this->loadQueueStats();
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load dashboard statistics. Please try again.';
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Retry loading stats after an error
     */
    public function retryLoad(): void
    {
        $this->isLoading = true;
        $this->loadStats();
    }

    #[On('refresh-stats')]
    public function loadMainStats(): void
    {
        $defaultStats = [
            'total_servers' => 0,
            'online_servers' => 0,
            'total_projects' => 0,
            'running_projects' => 0,
            'total_deployments' => 0,
            'successful_deployments' => 0,
            'failed_deployments' => 0,
        ];

        $cachedStats = $this->cacheOrFallback('dashboard_stats', 60, function () {
            // Optimized: Single query with subqueries instead of 7 separate queries
            $result = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM servers) as total_servers,
                    (SELECT COUNT(*) FROM servers WHERE status = 'online') as online_servers,
                    (SELECT COUNT(*) FROM projects) as total_projects,
                    (SELECT COUNT(*) FROM projects WHERE status = 'running') as running_projects,
                    (SELECT COUNT(*) FROM deployments) as total_deployments,
                    (SELECT COUNT(*) FROM deployments WHERE status = 'success') as successful_deployments,
                    (SELECT COUNT(*) FROM deployments WHERE status = 'failed') as failed_deployments
            ");

            if ($result === null) {
                return [];
            }

            return [
                'total_servers' => (int) $result->total_servers,
                'online_servers' => (int) $result->online_servers,
                'total_projects' => (int) $result->total_projects,
                'running_projects' => (int) $result->running_projects,
                'total_deployments' => (int) $result->total_deployments,
                'successful_deployments' => (int) $result->successful_deployments,
                'failed_deployments' => (int) $result->failed_deployments,
            ];
        });

        $validatedStats = is_array($cachedStats) ? array_map('intval', $cachedStats) : [];
        /** @var array<string, int> */
        $this->stats = array_merge($defaultStats, $validatedStats);
    }

    public function loadDeploymentsToday(): void
    {
        $today = now()->startOfDay();
        $this->deploymentsToday = Deployment::where('created_at', '>=', $today)->count();
    }

    public function loadActiveDeployments(): void
    {
        $this->activeDeployments = Deployment::whereIn('status', ['pending', 'running'])
            ->count();
    }

    public function loadSecurityScore(): void
    {
        $score = $this->cacheOrFallback('dashboard_security_score', 300, function (): int {
            $avgScore = Server::where('status', 'online')
                ->whereNotNull('security_score')
                ->avg('security_score');

            return $avgScore ? (int) round((float) $avgScore) : 85;
        });
        $this->overallSecurityScore = (int) $score;
    }

    public function loadSSLStats(): void
    {
        $stats = $this->cacheOrFallback('dashboard_ssl_stats', 300, function (): array {
            $now = now()->toDateTimeString();
            $expiringSoonDate = now()->addDays(7)->toDateTimeString();

            // Optimized: Single query with subqueries instead of 6 separate queries
            $result = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM ssl_certificates) as total_certificates,
                    (SELECT COUNT(*) FROM ssl_certificates WHERE status = 'issued' AND expires_at > ?) as active_certificates,
                    (SELECT COUNT(*) FROM ssl_certificates WHERE expires_at <= ? AND expires_at > ?) as expiring_soon,
                    (SELECT COUNT(*) FROM ssl_certificates WHERE expires_at <= ?) as expired,
                    (SELECT COUNT(*) FROM ssl_certificates WHERE status = 'pending') as pending,
                    (SELECT COUNT(*) FROM ssl_certificates WHERE status = 'failed') as failed
            ", [$now, $expiringSoonDate, $now, $now]);

            if ($result === null) {
                return [];
            }

            return [
                'total_certificates' => (int) $result->total_certificates,
                'active_certificates' => (int) $result->active_certificates,
                'expiring_soon' => (int) $result->expiring_soon,
                'expired' => (int) $result->expired,
                'pending' => (int) $result->pending,
                'failed' => (int) $result->failed,
            ];
        });
        $this->sslStats = is_array($stats) ? $stats : [];
    }

    public function loadHealthCheckStats(): void
    {
        $this->healthCheckStats = $this->cacheOrFallback('dashboard_health_stats', 120, function () {
            // Optimized: Single query with subqueries instead of 5 separate queries
            $result = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM health_checks) as total_checks,
                    (SELECT COUNT(*) FROM health_checks WHERE is_active = 1) as active_checks,
                    (SELECT COUNT(*) FROM health_checks WHERE status = 'healthy') as healthy,
                    (SELECT COUNT(*) FROM health_checks WHERE status = 'degraded') as degraded,
                    (SELECT COUNT(*) FROM health_checks WHERE status = 'down') as down
            ");

            if ($result === null) {
                return [];
            }

            return [
                'total_checks' => (int) $result->total_checks,
                'active_checks' => (int) $result->active_checks,
                'healthy' => (int) $result->healthy,
                'degraded' => (int) $result->degraded,
                'down' => (int) $result->down,
            ];
        });
    }

    public function loadQueueStats(): void
    {
        $this->queueStats = $this->cachedStats('dashboard_queue_stats', 30, function () {
            return [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        }, ['pending' => 0, 'failed' => 0]);
    }

    /**
     * Clear stats-related caches
     */
    public function clearStatsCache(): void
    {
        $this->forgetCacheKeys([
            'dashboard_stats',
            'dashboard_ssl_stats',
            'dashboard_health_stats',
            'dashboard_queue_stats',
            'dashboard_security_score',
        ]);
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        $this->forgetCacheKeys(['dashboard_stats', 'dashboard_health_stats']);
        $this->loadMainStats();
        $this->loadHealthCheckStats();
        $this->loadActiveDeployments();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.dashboard-stats');
    }
}
