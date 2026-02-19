<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Jobs\CrossRegionDeployJob;
use App\Models\Project;
use App\Models\Region;
use App\Models\RegionDeployment;
use App\Services\CrossRegionDeploymentService;
use App\Services\RegionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * CrossRegionDeployment - Cross-region deployment management Livewire component
 *
 * Provides the UI for initiating deployments across multiple regions,
 * monitoring deployment progress, and managing rollbacks.
 *
 * @property Collection<int, Region> $regions
 * @property Collection<int, RegionDeployment> $deploymentHistory
 */
class CrossRegionDeployment extends Component
{
    public Project $project;

    /** @var array<int, int> */
    public array $selectedRegions = [];

    public string $strategy = 'sequential';

    public ?int $activeDeploymentId = null;

    public bool $showDeployModal = false;

    /**
     * Get all available regions.
     *
     * @return Collection<int, Region>
     */
    #[Computed]
    public function regions(): Collection
    {
        $regionService = app(RegionService::class);

        return $regionService->getAvailableRegions();
    }

    /**
     * Get deployment history for this project.
     *
     * @return Collection<int, RegionDeployment>
     */
    #[Computed]
    public function deploymentHistory(): Collection
    {
        $deploymentService = app(CrossRegionDeploymentService::class);

        return $deploymentService->getDeploymentHistory($this->project);
    }

    /**
     * Initialize the component with a project.
     *
     * @param Project $project The project for cross-region deployments
     * @return void
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Toggle a region's selection for deployment.
     *
     * @param int $regionId The region ID to toggle
     * @return void
     */
    public function toggleRegion(int $regionId): void
    {
        $key = array_search($regionId, $this->selectedRegions, true);

        if ($key !== false) {
            unset($this->selectedRegions[$key]);
            $this->selectedRegions = array_values($this->selectedRegions);
        } else {
            $this->selectedRegions[] = $regionId;
        }
    }

    /**
     * Select all available regions.
     *
     * @return void
     */
    public function selectAllRegions(): void
    {
        $this->selectedRegions = $this->regions->pluck('id')->toArray();
    }

    /**
     * Deselect all regions.
     *
     * @return void
     */
    public function deselectAllRegions(): void
    {
        $this->selectedRegions = [];
    }

    /**
     * Initiate a cross-region deployment.
     *
     * Validates selection, creates the deployment, and dispatches the job.
     *
     * @return void
     */
    public function initiateDeploy(): void
    {
        if (empty($this->selectedRegions)) {
            session()->flash('error', 'Please select at least one region.');

            return;
        }

        $user = auth()->user();

        if ($user === null) {
            session()->flash('error', 'You must be logged in to initiate a deployment.');

            return;
        }

        $deploymentService = app(CrossRegionDeploymentService::class);

        try {
            $regionDeployment = $deploymentService->initiateCrossRegionDeployment(
                $this->project,
                $this->selectedRegions,
                $this->strategy,
                $user
            );

            CrossRegionDeployJob::dispatch($regionDeployment);

            $this->activeDeploymentId = $regionDeployment->id;
            $this->showDeployModal = false;
            $this->selectedRegions = [];
            unset($this->deploymentHistory);

            session()->flash('message', 'Cross-region deployment initiated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to initiate deployment: ' . $e->getMessage());
        }
    }

    /**
     * Rollback a cross-region deployment.
     *
     * @param int $regionDeploymentId The region deployment ID to rollback
     * @return void
     */
    public function rollbackDeployment(int $regionDeploymentId): void
    {
        $regionDeployment = RegionDeployment::find($regionDeploymentId);

        if ($regionDeployment === null) {
            session()->flash('error', 'Deployment not found.');

            return;
        }

        $deploymentService = app(CrossRegionDeploymentService::class);

        try {
            $deploymentService->rollbackAll($regionDeployment);
            unset($this->deploymentHistory);

            session()->flash('message', 'Rollback initiated for all regions.');
        } catch (\Exception $e) {
            session()->flash('error', 'Rollback failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the current progress for a deployment.
     *
     * @param int $regionDeploymentId The region deployment ID
     * @return array{
     *     status: string,
     *     strategy: string,
     *     total_regions: int,
     *     completed: int,
     *     failed: int,
     *     pending: int,
     *     running: int,
     *     regions: array<int|string, mixed>
     * }
     */
    public function getDeploymentProgress(int $regionDeploymentId): array
    {
        $regionDeployment = RegionDeployment::find($regionDeploymentId);

        if ($regionDeployment === null) {
            return [
                'status' => 'unknown',
                'strategy' => '',
                'total_regions' => 0,
                'completed' => 0,
                'failed' => 0,
                'pending' => 0,
                'running' => 0,
                'regions' => [],
            ];
        }

        $deploymentService = app(CrossRegionDeploymentService::class);

        return $deploymentService->getDeploymentProgress($regionDeployment);
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.deployments.cross-region-deployment');
    }
}
