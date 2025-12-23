<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use App\Models\Server;
use App\Models\ServerTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

/**
 * Server Filtering and Sorting Trait
 *
 * Provides reusable filtering, sorting, and query building logic for server lists.
 * Includes support for search, status filtering, tag filtering, and caching.
 *
 * @property string $search Search term for filtering servers
 * @property string $statusFilter Filter servers by status
 * @property array<int> $tagFilter Filter servers by tag IDs
 */
trait WithServerFiltering
{
    public string $search = '';

    public string $statusFilter = '';

    /** @var array<int> */
    public array $tagFilter = [];

    /**
     * Clear cached queries when search/filter changes
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'tagFilter'])) {
            unset($this->serversQuery);
            $this->resetPage();
        }
    }

    /**
     * Get all servers with eager loading
     * Cached using #[Computed] attribute to prevent multiple queries
     *
     * @return Collection<int, Server>
     */
    #[Computed]
    public function accessibleServers(): Collection
    {
        return Server::with(['tags:id,name,color', 'projects:id,name,server_id', 'user:id,name'])
            ->select([
                'id', 'name', 'hostname', 'ip_address', 'port', 'status',
                'user_id', 'docker_installed', 'last_ping_at', 'created_at', 'updated_at',
                'cpu_cores', 'memory_gb', 'disk_gb', 'location_name', 'os',
            ])
            ->get();
    }

    /**
     * Get the base servers query with filters applied
     * Cached using #[Computed] attribute to prevent multiple queries
     *
     * @return \Illuminate\Database\Eloquent\Builder<Server>
     */
    #[Computed]
    public function serversQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Server::query()
            ->with([
                'tags:id,name,color',
                'user:id,name',
            ])
            ->select([
                'id', 'name', 'hostname', 'ip_address', 'port', 'status',
                'user_id', 'docker_installed', 'last_ping_at', 'created_at', 'updated_at',
                'cpu_cores', 'memory_gb', 'disk_gb', 'location_name', 'os',
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('hostname', 'like', '%'.$this->search.'%')
                        ->orWhere('ip_address', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when(! empty($this->tagFilter), function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('server_tags.id', $this->tagFilter);
                });
            })
            ->latest();
    }

    /**
     * Get all tags with caching
     * Cached using #[Computed] attribute to prevent multiple queries
     *
     * @return Collection<int, ServerTag>
     */
    #[Computed]
    public function allTags(): Collection
    {
        return Cache::remember('server_tags_list', 600, function () {
            return ServerTag::withCount('servers')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Toggle tag filter
     *
     * Adds or removes a tag from the active filter list.
     *
     * @param int $tagId The ID of the tag to toggle
     * @return void
     */
    public function toggleTagFilter(int $tagId): void
    {
        if (in_array($tagId, $this->tagFilter)) {
            $this->tagFilter = array_diff($this->tagFilter, [$tagId]);
        } else {
            $this->tagFilter[] = $tagId;
        }
        unset($this->serversQuery);
        $this->resetPage();
    }
}
