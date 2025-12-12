<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Models\ServerTag;
use Livewire\Attributes\On;
use Livewire\Component;

class ServerTagAssignment extends Component
{
    public Server $server;

    /** @var array<int, array<string, mixed>> */
    public array $availableTags = [];

    /** @var array<int, int> */
    public array $selectedTags = [];

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->loadTags();
    }

    public function loadTags(): void
    {
        // Get all user's tags
        $this->availableTags = ServerTag::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->toArray();

        // Get currently selected tags for this server
        $this->selectedTags = $this->server->tags()->pluck('server_tags.id')->toArray();
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTags)) {
            // Remove tag
            $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
        } else {
            // Add tag
            $this->selectedTags[] = $tagId;
        }
    }

    public function saveTags(): void
    {
        // Sync tags with server
        $this->server->tags()->sync($this->selectedTags);

        $this->dispatch('tags-assigned');
        $this->dispatch('tag-updated');
        session()->flash('message', 'Tags updated successfully');
    }

    #[On('tag-updated')]
    public function refreshTags(): void
    {
        $this->loadTags();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.server-tag-assignment');
    }
}
