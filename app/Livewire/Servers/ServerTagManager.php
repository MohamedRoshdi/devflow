<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\ServerTag;
use Livewire\Attributes\On;

class ServerTagManager extends Component
{
    public $tags = [];
    public $newTagName = '';
    public $newTagColor = '#6366f1';
    public $editingTag = null;
    public $editTagName = '';
    public $editTagColor = '';
    public $showEditModal = false;

    public function mount(): void
    {
        $this->loadTags();
    }

    public function loadTags(): void
    {
        $this->tags = ServerTag::where('user_id', auth()->id())
            ->withCount('servers')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function createTag(): void
    {
        $this->validate([
            'newTagName' => 'required|string|max:50|unique:server_tags,name,NULL,id,user_id,' . auth()->id(),
            'newTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ], [
            'newTagName.required' => 'Tag name is required',
            'newTagName.unique' => 'You already have a tag with this name',
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
        $tag = ServerTag::where('id', $tagId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$tag) {
            return;
        }

        $this->editingTag = $tag->id;
        $this->editTagName = $tag->name;
        $this->editTagColor = $tag->color;
        $this->showEditModal = true;
    }

    public function updateTag(): void
    {
        if (!$this->editingTag) {
            return;
        }

        $this->validate([
            'editTagName' => 'required|string|max:50|unique:server_tags,name,' . $this->editingTag . ',id,user_id,' . auth()->id(),
            'editTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ], [
            'editTagName.required' => 'Tag name is required',
            'editTagName.unique' => 'You already have a tag with this name',
            'editTagColor.regex' => 'Color must be a valid hex color code',
        ]);

        $tag = ServerTag::where('id', $this->editingTag)
            ->where('user_id', auth()->id())
            ->first();

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
        $tag = ServerTag::where('id', $tagId)
            ->where('user_id', auth()->id())
            ->first();

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

    public function render()
    {
        return view('livewire.servers.server-tag-manager');
    }
}
