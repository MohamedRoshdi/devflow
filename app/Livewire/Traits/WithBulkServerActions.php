<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\Server;
use App\Services\BulkServerActionService;

/**
 * Bulk Server Actions Trait
 *
 * Provides bulk operation functionality for server management including
 * selection management, bulk ping, bulk reboot, and bulk Docker installation.
 */
trait WithBulkServerActions
{
    /** @var array<int> */
    public array $selectedServers = [];

    public bool $selectAll = false;

    public bool $bulkActionInProgress = false;

    /** @var array<int, mixed> */
    public array $bulkActionResults = [];

    public bool $showResultsModal = false;

    /**
     * Toggle server selection for bulk actions
     *
     * @param int $serverId The ID of the server to toggle
     * @return void
     */
    public function toggleServerSelection(int $serverId): void
    {
        if (in_array($serverId, $this->selectedServers)) {
            $this->selectedServers = array_values(array_diff($this->selectedServers, [$serverId]));
        } else {
            $this->selectedServers[] = $serverId;
        }

        // Update selectAll state based on current selection
        $totalServersOnPage = $this->serversQuery->pluck('id')->toArray();
        $this->selectAll = count($this->selectedServers) > 0 &&
                          count(array_intersect($totalServersOnPage, $this->selectedServers)) === count($totalServersOnPage);
    }

    /**
     * Toggle select all servers on current page
     *
     * Selects or deselects all visible servers for bulk operations.
     *
     * @return void
     */
    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        if ($this->selectAll) {
            // Select all servers on current page
            $serverIds = $this->serversQuery->pluck('id')->toArray();
            $this->selectedServers = array_unique(array_merge($this->selectedServers, $serverIds));
        } else {
            // Deselect all servers
            $this->selectedServers = [];
        }
    }

    /**
     * Clear all selections
     *
     * Resets selection state and closes results modal.
     *
     * @return void
     */
    public function clearSelection(): void
    {
        $this->selectedServers = [];
        $this->selectAll = false;
        $this->bulkActionResults = [];
        $this->showResultsModal = false;
    }

    /**
     * Bulk ping selected servers
     *
     * Tests connectivity to all selected servers and displays results.
     *
     * @return void
     */
    public function bulkPing(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');

            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->pingServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        // Clear caches after bulk ping
        unset($this->accessibleServers, $this->serversQuery);

        session()->flash('message', "Bulk ping completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk reboot selected servers
     *
     * Initiates reboot on all selected servers simultaneously.
     *
     * @return void
     */
    public function bulkReboot(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');

            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->rebootServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        // Clear caches after bulk reboot
        unset($this->accessibleServers, $this->serversQuery);

        session()->flash('message', "Bulk reboot initiated: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk install Docker on selected servers
     *
     * Installs Docker and Docker Compose on all selected servers.
     *
     * @return void
     */
    public function bulkInstallDocker(): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');

            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->installDockerOnServers($servers);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        // Clear caches after bulk Docker installation
        unset($this->accessibleServers, $this->serversQuery);

        session()->flash('message', "Bulk Docker installation completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Bulk restart service on selected servers
     *
     * Restarts a specific system service on all selected servers.
     *
     * @param string $service The service name to restart (e.g., 'nginx', 'mysql')
     * @return void
     */
    public function bulkRestartService(string $service): void
    {
        if (empty($this->selectedServers)) {
            session()->flash('error', 'No servers selected');

            return;
        }

        $this->bulkActionInProgress = true;
        $this->bulkActionResults = [];

        $servers = Server::whereIn('id', $this->selectedServers)->get();

        $bulkService = app(BulkServerActionService::class);
        $results = $bulkService->restartServiceOnServers($servers, $service);
        $stats = $bulkService->getSummaryStats($results);

        $this->bulkActionResults = $results;
        $this->bulkActionInProgress = false;
        $this->showResultsModal = true;

        // Clear caches after bulk service restart
        unset($this->accessibleServers, $this->serversQuery);

        session()->flash('message', "Bulk {$service} restart completed: {$stats['successful']} successful, {$stats['failed']} failed");
    }

    /**
     * Close results modal
     *
     * Hides the bulk action results modal.
     *
     * @return void
     */
    public function closeResultsModal(): void
    {
        $this->showResultsModal = false;
    }
}
