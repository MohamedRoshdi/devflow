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
            ];
        });
        $this->sslStats = is_array($stats) ? $stats : [];
    }

    public function loadHealthCheckStats(): void
    {
        $this->healthCheckStats = $this->cacheOrFallback('dashboard_health_stats', 120, function () {
            return [
                'total_checks' => HealthCheck::count(),
                'active_checks' => HealthCheck::where('is_active', true)->count(),
                'healthy' => HealthCheck::where('status', 'healthy')->count(),
                'degraded' => HealthCheck::where('status', 'degraded')->count(),
                'down' => HealthCheck::where('status', 'down')->count(),
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
