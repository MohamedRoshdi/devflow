<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\CanaryRelease;
use App\Models\Project;
use App\Services\CanaryDeploymentService;
use App\Services\CanaryMetricsCollectorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class CanaryReleaseDashboard extends Component
{
    public Project $project;

    public string $statusMessage = '';

    public string $statusType = 'info';

    // Form fields for initiating a canary release
    public int $initialWeight = 10;

    public int $stepDuration = 5;

    public float $errorThreshold = 5.0;

    public int $responseTimeThreshold = 2000;

    public bool $autoPromote = true;

    public bool $autoRollback = true;

    public bool $showInitiateModal = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function promote(int $canaryReleaseId): void
    {
        try {
            $release = CanaryRelease::findOrFail($canaryReleaseId);
            $service = app(CanaryDeploymentService::class);
            $service->promote($release);
            $this->dispatch('notification', type: 'success', message: 'Canary release promoted to stable.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Promotion failed: ' . $e->getMessage());
        }
    }

    public function rollback(int $canaryReleaseId): void
    {
        try {
            $release = CanaryRelease::findOrFail($canaryReleaseId);
            $service = app(CanaryDeploymentService::class);
            $service->rollback($release, 'manual');
            $this->dispatch('notification', type: 'success', message: 'Canary release rolled back.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Rollback failed: ' . $e->getMessage());
        }
    }

    public function advanceWeight(int $canaryReleaseId): void
    {
        try {
            $release = CanaryRelease::findOrFail($canaryReleaseId);
            $service = app(CanaryDeploymentService::class);
            $service->advanceWeight($release);
            $this->dispatch('notification', type: 'success', message: 'Canary weight advanced.');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to advance: ' . $e->getMessage());
        }
    }

    /**
     * @return Collection<int, CanaryRelease>
     */
    public function getActiveReleasesProperty(): Collection
    {
        return CanaryRelease::where('project_id', $this->project->id)
            ->whereNotIn('status', ['completed', 'failed', 'rolled_back'])
            ->with(['deployment', 'metrics' => fn ($q) => $q->latest('recorded_at')->limit(20)])
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, CanaryRelease>
     */
    public function getRecentReleasesProperty(): Collection
    {
        return CanaryRelease::where('project_id', $this->project->id)
            ->with('deployment')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * @return array{stable: array{avg_error_rate: float, avg_response_time: int, total_requests: int, total_errors: int, p95_response_time: int, p99_response_time: int}, canary: array{avg_error_rate: float, avg_response_time: int, total_requests: int, total_errors: int, p95_response_time: int, p99_response_time: int}}|null
     */
    public function getMetricsComparisonProperty(): ?array
    {
        $activeRelease = CanaryRelease::where('project_id', $this->project->id)
            ->where('status', 'monitoring')
            ->first();

        if ($activeRelease === null) {
            return null;
        }

        /** @var CanaryMetricsCollectorService $collector */
        $collector = app(CanaryMetricsCollectorService::class);

        return $collector->getMetricsComparison($activeRelease);
    }

    public function render(): View
    {
        return view('livewire.deployments.canary-release-dashboard', [
            'activeReleases' => $this->getActiveReleasesProperty(),
            'recentReleases' => $this->getRecentReleasesProperty(),
            'metricsComparison' => $this->getMetricsComparisonProperty(),
        ]);
    }
}
