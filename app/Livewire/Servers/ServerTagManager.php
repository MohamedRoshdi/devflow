<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\ServerTag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ServerTagManager extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tags = [];

    public string $newTagName = '';

    public string $newTagColor = '#6366f1';

    public ?int $editingTag = null;

    public string $editTagName = '';

    public string $editTagColor = '';

    public bool $showEditModal = false;

    public function mount(): void
    {
        $this->loadTags();
    }

    public function loadTags(): void
    {
        // All tags are shared across all users
        $this->tags = ServerTag::withCount('servers')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function createTag(): void
    {
        $this->validate([
            'newTagName' => 'required|string|max:50|unique:server_tags,name',
            'newTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ], [
            'newTagName.required' => 'Tag name is required',
            'newTagName.unique' => 'A tag with this name already exists',
            'newTagColor.regex' => 'Color must be a valid hex color code',
        ]);

        ServerTag::create([
            'user_id' => auth()->id(),
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
        ]);

        $this->reset('newTagName', 'newTagColor');
        $this->newTagColor = '#6366f1';
        $this->loadTags();
        $this->dispatch('tag-updated');
        session()->flash('message', 'Tag created successfully');
    }

    public function editTag(int $tagId): void
    {
        $tag = ServerTag::find($tagId);

        if (! $tag) {
            return;
        }

        $this->editingTag = $tag->id;
        $this->editTagName = $tag->name;
        $this->editTagColor = $tag->color;
        $this->showEditModal = true;
    }

    public function updateTag(): void
    {
        if (! $this->editingTag) {
            return;
        }

        $this->validate([
            'editTagName' => 'required|string|max:50|unique:server_tags,name,'.$this->editingTag,
            'editTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ], [
            'editTagName.required' => 'Tag name is required',
            'editTagName.unique' => 'A tag with this name already exists',
            'editTagColor.regex' => 'Color must be a valid hex color code',
        ]);

        $tag = ServerTag::find($this->editingTag);

        if ($tag) {
            $tag->update([
                'name' => $this->editTagName,
                'color' => $this->editTagColor,
            ]);

            $this->closeEditModal();
            $this->loadTags();
            $this->dispatch('tag-updated');
            session()->flash('message', 'Tag updated successfully');
        }
    }

    public function deleteTag(int $tagId): void
    {
        $tag = ServerTag::find($tagId);

        if ($tag) {
            $tag->delete();
            $this->loadTags();
            $this->dispatch('tag-updated');
            session()->flash('message', 'Tag deleted successfully');
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset('editingTag', 'editTagName', 'editTagColor');
    }

    #[On('tag-updated')]
    public function refreshTags(): void
    {
        $this->loadTags();
    }

    public function render(): View
    {
        return view('livewire.servers.server-tag-manager');
    }
}
