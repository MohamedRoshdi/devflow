<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Enums\RegionStatus;
use App\Models\Region;
use App\Models\RegionDeployment;
use App\Models\Server;
use App\Services\RegionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * RegionDashboard - Single region detail view Livewire component
 *
 * Displays detailed information about a region including its servers,
 * health score, recent deployments, and status management controls.
 *
 * @property Collection<int, Server> $servers
 * @property Collection<int, RegionDeployment> $recentDeployments
 * @property array{score: int, online_count: int, total_count: int, status: string} $healthScore
 */
class RegionDashboard extends Component
{
    public Region $region;

    /**
     * Get the servers in this region.
     *
     * @return Collection<int, Server>
     */
    #[Computed]
    public function servers(): Collection
    {
        return $this->region->servers()
            ->orderBy('status')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get recent cross-region deployments touching this region.
     *
     * @return Collection<int, RegionDeployment>
     */
    #[Computed]
    public function recentDeployments(): Collection
    {
        return RegionDeployment::whereJsonContains('region_order', $this->region->id)
            ->with(['project', 'initiator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get the aggregated health score for this region.
     *
     * @return array{score: int, online_count: int, total_count: int, status: string}
     */
    #[Computed]
    public function healthScore(): array
    {
        $regionService = app(RegionService::class);

        return $regionService->getRegionHealthScore($this->region);
    }

    /**
     * Initialize the component with a region.
     *
     * @param Region $region The region to display
     * @return void
     */
    public function mount(Region $region): void
    {
        $this->region = $region->load(['servers', 'dnsRoutingRules']);
    }

    /**
     * Change the region's status.
     *
     * @param string $status The new status value
     * @return void
     */
    public function changeRegionStatus(string $status): void
    {
        $regionStatus = RegionStatus::tryFrom($status);

        if ($regionStatus === null) {
            session()->flash('error', 'Invalid status provided.');

            return;
        }

        $regionService = app(RegionService::class);
        $regionService->updateRegion($this->region, ['status' => $regionStatus]);

        $this->region->refresh();
        unset($this->healthScore);
        unset($this->servers);

        session()->flash('message', "Region status changed to {$regionStatus->label()}.");
    }

    /**
     * Remove a server from this region.
     *
     * @param int $serverId The server ID to remove
     * @return void
     */
    public function removeServer(int $serverId): void
    {
        $server = Server::find($serverId);

        if ($server === null) {
            return;
        }

        $regionService = app(RegionService::class);
        $regionService->removeServerFromRegion($server);

        unset($this->servers);
        unset($this->healthScore);

        session()->flash('message', "Server '{$server->name}' removed from region.");
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.servers.region-dashboard');
    }
}
