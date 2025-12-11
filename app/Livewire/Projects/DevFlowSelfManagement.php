<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use Livewire\Component;

/**
 * DevFlow Self Management - Main Coordinator Component
 *
 * This component serves as the main container and coordinator for DevFlow's
 * self-management functionality. The actual functionality is distributed across
 * specialized sub-components for better maintainability and organization.
 *
 * Sub-components:
 * - GitManager: Git operations, commits, branches, sync
 * - SystemInfo: System information, configuration, environment
 * - ServiceManager: Supervisor, Queue, Reverb, Scheduler management
 * - CacheManager: Cache operations and storage management
 * - LogViewer: Log viewing and management
 * - DeploymentActions: Deployment operations and scripts
 */
class DevFlowSelfManagement extends Component
{
    /**
     * Active tab selection
     * Valid values: overview, git, system, services, cache, logs, deployment
     */
    public string $activeTab = 'overview';

    /**
     * Quick stats for overview dashboard
     * @var array<string, mixed>
     */
    public array $quickStats = [];

    public function mount(): void
    {
        $this->loadQuickStats();
    }

    /**
     * Load quick statistics for the overview dashboard
     */
    private function loadQuickStats(): void
    {
        $this->quickStats = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'is_git_repo' => is_dir(base_path() . '/.git'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    /**
     * Switch to a different tab
     */
    public function switchTab(string $tab): void
    {
        $validTabs = ['overview', 'git', 'system', 'services', 'cache', 'logs', 'deployment'];

        if (in_array($tab, $validTabs)) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Refresh quick stats
     */
    public function refreshStats(): void
    {
        $this->loadQuickStats();
        session()->flash('message', 'Quick stats refreshed!');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow-self-management');
    }
}
