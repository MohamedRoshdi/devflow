<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Enums\RegionStatus;
use App\Models\Region;
use App\Services\RegionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * RegionManager - Region CRUD and health overview Livewire component
 *
 * Provides region management interface with create/edit modals,
 * status filtering, and health score display for each region.
 *
 * @property Collection<int, Region> $regions
 */
class RegionManager extends Component
{
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?int $editingRegionId = null;

    // Form fields
    public string $name = '';

    public string $code = '';

    public string $continent = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $dns_zone = '';

    // Filter
    public string $statusFilter = 'all';

    /**
     * Get all regions with server counts, filtered by status.
     *
     * @return Collection<int, Region>
     */
    #[Computed]
    public function regions(): Collection
    {
        $query = Region::withCount('servers');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('continent')
            ->orderBy('name')
            ->get();
    }

    /**
     * Initialize the component.
     *
     * @return void
     */
    public function mount(): void
    {
        // Regions are computed on demand
    }

    /**
     * Create a new region.
     *
     * @return void
     */
    public function createRegion(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:regions,code',
            'continent' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'dns_zone' => 'nullable|string|max:255',
        ]);

        $regionService = app(RegionService::class);

        try {
            $regionService->createRegion([
                'name' => $this->name,
                'code' => $this->code,
                'continent' => $this->continent,
                'latitude' => $this->latitude !== '' ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== '' ? (float) $this->longitude : null,
                'dns_zone' => $this->dns_zone !== '' ? $this->dns_zone : null,
                'status' => RegionStatus::Active,
            ]);

            $this->resetForm();
            $this->showCreateModal = false;
            unset($this->regions);

            session()->flash('message', 'Region created successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create region: ' . $e->getMessage());
        }
    }

    /**
     * Open the edit modal for a specific region.
     *
     * @param int $regionId The region ID to edit
     * @return void
     */
    public function editRegion(int $regionId): void
    {
        $region = Region::find($regionId);

        if ($region === null) {
            return;
        }

        $this->editingRegionId = $region->id;
        $this->name = $region->name;
        $this->code = $region->code;
        $this->continent = $region->continent ?? '';
        $this->latitude = $region->latitude !== null ? (string) $region->latitude : '';
        $this->longitude = $region->longitude !== null ? (string) $region->longitude : '';
        $this->dns_zone = $region->dns_zone ?? '';
        $this->showEditModal = true;
    }

    /**
     * Update the currently editing region.
     *
     * @return void
     */
    public function updateRegion(): void
    {
        if ($this->editingRegionId === null) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:regions,code,' . $this->editingRegionId,
            'continent' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'dns_zone' => 'nullable|string|max:255',
        ]);

        $region = Region::find($this->editingRegionId);

        if ($region === null) {
            return;
        }

        $regionService = app(RegionService::class);

        try {
            $regionService->updateRegion($region, [
                'name' => $this->name,
                'code' => $this->code,
                'continent' => $this->continent,
                'latitude' => $this->latitude !== '' ? (float) $this->latitude : null,
                'longitude' => $this->longitude !== '' ? (float) $this->longitude : null,
                'dns_zone' => $this->dns_zone !== '' ? $this->dns_zone : null,
            ]);

            $this->resetForm();
            $this->showEditModal = false;
            $this->editingRegionId = null;
            unset($this->regions);

            session()->flash('message', 'Region updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update region: ' . $e->getMessage());
        }
    }

    /**
     * Delete a region by its ID.
     *
     * @param int $regionId The region ID to delete
     * @return void
     */
    public function deleteRegion(int $regionId): void
    {
        $region = Region::find($regionId);

        if ($region === null) {
            return;
        }

        $regionService = app(RegionService::class);

        try {
            $regionService->deleteRegion($region);
            unset($this->regions);
            session()->flash('message', 'Region deleted successfully.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Reset all form fields to their defaults.
     *
     * @return void
     */
    public function resetForm(): void
    {
        $this->name = '';
        $this->code = '';
        $this->continent = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->dns_zone = '';
    }

    /**
     * Get the health score for a given region.
     *
     * @param int $regionId The region ID
     * @return array{score: int, online_count: int, total_count: int, status: string}
     */
    public function getHealthScore(int $regionId): array
    {
        $region = Region::with('servers')->find($regionId);

        if ($region === null) {
            return ['score' => 0, 'online_count' => 0, 'total_count' => 0, 'status' => 'unknown'];
        }

        $regionService = app(RegionService::class);

        return $regionService->getRegionHealthScore($region);
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.servers.region-manager');
    }
}
