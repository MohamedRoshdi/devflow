<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Livewire\Traits\WithBulkServerActions;
use App\Livewire\Traits\WithServerActions;
use App\Livewire\Traits\WithServerFiltering;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Server List Component
 *
 * Manages server listing with advanced features including search, filtering,
 * bulk operations, connectivity testing, and real-time status monitoring.
 * Supports server tagging, health monitoring, and Docker installation.
 *
 * This component has been refactored to use composition via traits:
 * - WithServerFiltering: Handles search, status, and tag filtering
 * - WithServerActions: Handles individual server actions (ping, reboot, delete)
 * - WithBulkServerActions: Handles bulk operations on multiple servers
 *
 * @property string $search Search term for filtering servers by name, hostname, or IP
 * @property string $statusFilter Filter servers by status (online, offline, maintenance)
 * @property array<int> $tagFilter Filter servers by tag IDs
 * @property bool $isPingingAll Loading state for bulk ping operation
 * @property array<int> $selectedServers Array of selected server IDs for bulk actions
 * @property bool $selectAll Whether all servers on current page are selected
 * @property bool $bulkActionInProgress Loading state for bulk operations
 * @property array<int, mixed> $bulkActionResults Results from bulk operations
 * @property bool $showResultsModal Toggle for bulk action results modal
 * @property bool $isLoading Initial loading state
 */
class ServerList extends Component
{
    use WithPagination;
    use WithServerFiltering;
    use WithServerActions;
    use WithBulkServerActions;

    public bool $isLoading = true;

    /**
     * Initialize component on mount
     *
     * @return void
     */
    public function mount(): void
    {
        // Don't ping on mount - use wire:init for lazy loading
    }

    /**
     * Lazy load server data - called via wire:init
     *
     * Auto-pings all servers after initial page render for status updates.
     *
     * @return void
     */
    public function loadServerData(): void
    {
        // Auto-ping all servers after initial page render
        $this->pingAllServersInBackground();
        $this->isLoading = false;
    }

    /**
     * Refresh server list when a new server is created
     *
     * @return void
     */
    #[On('server-created')]
    public function refreshServers(): void
    {
        unset($this->accessibleServers);
        $this->resetPage();
    }

    /**
     * Render the server list view with pagination
     *
     * Applies filters and returns paginated server results with tags.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        // Use cached serversQuery with pagination
        $servers = $this->serversQuery->paginate(10);

        return view('livewire.servers.server-list', [
            'servers' => $servers,
            'allTags' => $this->allTags,
        ]);
    }
}
