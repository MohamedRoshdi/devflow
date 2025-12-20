<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\CacheMonitoringService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard Cache Stats Component
 *
 * Displays cache performance statistics including hit/miss rates,
 * top keys, and recommendations for cache optimization.
 */
class DashboardCacheStats extends Component
{
    /** @var array{hits: int, misses: int, hit_rate: float, total_requests: int, avg_latency_ms: float} */
    public array $stats = [
        'hits' => 0,
        'misses' => 0,
        'hit_rate' => 0.0,
        'total_requests' => 0,
        'avg_latency_ms' => 0.0,
    ];

    /** @var array<int, array{key: string, hits: int, misses: int, hit_rate: float}> */
    public array $topKeys = [];

    /** @var array<int, array{key: string, hits: int, misses: int, hit_rate: float}> */
    public array $lowPerformingKeys = [];

    /** @var array<int, string> */
    public array $recommendations = [];

    /** @var array<string, mixed>|null */
    public ?array $redisStats = null;

    public bool $isLoading = true;

    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $showDetails = false;

    public function mount(): void
    {
        $this->loadCacheStats();
    }

    public function loadCacheStats(): void
    {
        $this->isLoading = true;

        try {
            $service = app(CacheMonitoringService::class);
            $report = $service->getMonitoringReport();

            $this->stats = $report['summary'];
            $this->topKeys = array_slice($report['top_keys'], 0, 5);
            $this->lowPerformingKeys = array_slice($report['low_performing'], 0, 3);
            $this->recommendations = $report['recommendations'];
            $this->redisStats = $report['redis'];
            $this->hasError = false;
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load cache statistics.';
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function resetStats(): void
    {
        try {
            $service = app(CacheMonitoringService::class);
            $service->resetStatistics();
            $this->loadCacheStats();
            $this->dispatch('notify', type: 'success', message: 'Cache statistics have been reset.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to reset cache statistics.');
            report($e);
        }
    }

    #[On('refresh-cache-stats')]
    public function refreshStats(): void
    {
        $this->loadCacheStats();
    }

    /**
     * Get the hit rate status class based on percentage.
     */
    public function getHitRateStatus(): string
    {
        if ($this->stats['hit_rate'] >= 80) {
            return 'text-green-500';
        }

        if ($this->stats['hit_rate'] >= 50) {
            return 'text-yellow-500';
        }

        return 'text-red-500';
    }

    /**
     * Get the hit rate badge class.
     */
    public function getHitRateBadgeClass(): string
    {
        if ($this->stats['hit_rate'] >= 80) {
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        }

        if ($this->stats['hit_rate'] >= 50) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }

        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    }

    /**
     * Format bytes for display.
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return round($value, 2) . ' ' . $units[$index];
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-cache-stats');
    }
}
