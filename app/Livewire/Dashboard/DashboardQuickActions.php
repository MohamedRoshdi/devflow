<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Jobs\DeployProjectJob;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dashboard Quick Actions Component
 *
 * Provides quick action buttons for common dashboard operations:
 * - Add Server link
 * - Deploy All projects
 * - Clear All Caches
 * - View Logs link
 * - Health Checks link
 */
class DashboardQuickActions extends Component
{
    /**
     * Clear all application caches
     */
    public function clearAllCaches(): void
    {
        try {
            $cacheService = app(\App\Services\CacheManagementService::class);
            $result = $cacheService->clearAllCachesComplete();

            $clearedCount = count($result['cleared']);
            $failedCount = count($result['failed']);

            if ($failedCount === 0) {
                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => "All caches cleared successfully! ({$clearedCount} caches cleared)",
                ]);
            } else {
                $this->dispatch('notification', [
                    'type' => 'warning',
                    'message' => "Partially cleared: {$clearedCount} caches cleared, {$failedCount} failed",
                ]);
            }

            // Dispatch event to refresh other dashboard components
            $this->dispatch('refresh-dashboard');
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
                $deployment = Deployment::create([
                    'project_id' => $project->id,
                    'server_id' => $project->server_id,
                    'user_id' => Auth::id(),
                    'branch' => $project->branch ?? 'main',
                    'commit_hash' => 'pending',
                    'status' => 'pending',
                    'triggered_by' => 'manual',
                ]);

                DeployProjectJob::dispatch($deployment);
                $deploymentCount++;
            }

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Deploying {$deploymentCount} projects. Check deployments page for progress.",
            ]);

            // Dispatch event to refresh stats
            $this->dispatch('refresh-stats');
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to deploy projects: '.$e->getMessage(),
            ]);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard.dashboard-quick-actions');
    }
}
