<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\HelpContent;
use App\Models\HelpContentTranslation;
use App\Services\HelpContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class HelpContentManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = 'all';
    public string $statusFilter = 'all';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Form fields
    public ?int $editingId = null;
    public string $key = '';
    public string $category = '';
    public string $ui_element_type = 'tooltip';
    public string $icon = 'info-circle';
    public string $title = '';
    public string $brief = '';
    /** @var array<string, string> */
    public array $details = [];
    public string $docs_url = '';
    public string $video_url = '';
    public bool $is_active = true;

    // Translation fields
    public string $ar_brief = '';
    /** @var array<string, string> */
    public array $ar_details = [];

    // Modal states
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // New detail fields
    public string $newDetailKey = '';
    public string $newDetailValue = '';
    public string $newDetailKeyAr = '';
    public string $newDetailValueAr = '';

    /** @var array<string, string|array<string>> */
    protected array $rules = [
        'key' => 'required|string|max:255',
        'category' => 'required|string|max:100',
        'ui_element_type' => 'required|in:tooltip,popover,modal,sidebar,inline',
        'icon' => 'required|string|max:100',
        'title' => 'required|string|max:255',
        'brief' => 'required|string|max:500',
        'details' => 'nullable|array',
        'docs_url' => 'nullable|url|max:500',
        'video_url' => 'nullable|url|max:500',
        'is_active' => 'boolean',
        'ar_brief' => 'nullable|string|max:500',
        'ar_details' => 'nullable|array',
    ];

    public function mount(): void
    {
        // Only super-admin and admin users can manage help content
        $user = auth()->user();
        abort_unless(
            $user && $user->hasRole(['super-admin', 'admin']),
            403,
            'You do not have permission to manage help content.'
        );
    }

    protected function getHelpContentService(): HelpContentService
    {
        return app(HelpContentService::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function helpContents()
    {
        return HelpContent::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->categoryFilter !== 'all', fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->withCount('interactions')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => HelpContent::count(),
            'active' => HelpContent::where('is_active', true)->count(),
            'most_viewed' => HelpContent::orderBy('view_count', 'desc')->first(),
            'most_helpful' => HelpContent::where('helpful_count', '>', 0)
                ->orderByRaw('(helpful_count / (helpful_count + not_helpful_count + 1)) DESC')
                ->first(),
        ];
    }

    #[Computed]
    public function categories()
    {
        return HelpContent::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $helpContent = HelpContent::with('translations')->findOrFail($id);

        $this->editingId = $helpContent->id;
        $this->key = $helpContent->key;
        $this->category = $helpContent->category;
        $this->ui_element_type = $helpContent->ui_element_type;
        $this->icon = $helpContent->icon ?? 'info-circle';
        $this->title = $helpContent->title;
        $this->brief = $helpContent->brief;
        $this->details = $helpContent->details ?? [];
        $this->docs_url = $helpContent->docs_url ?? '';
        $this->video_url = $helpContent->video_url ?? '';
        $this->is_active = $helpContent->is_active;

        // Load Arabic translation if exists
        $arTranslation = $helpContent->translations()->where('locale', 'ar')->first();
        if ($arTranslation) {
            $this->ar_brief = $arTranslation->brief ?? '';
            $this->ar_details = $arTranslation->details ?? [];
        } else {
            $this->ar_brief = '';
            $this->ar_details = [];
        }

        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            if ($this->editingId) {
                $helpContent = HelpContent::findOrFail($this->editingId);
                $helpContent->update([
                    'key' => $this->key,
                    'category' => $this->category,
                    'ui_element_type' => $this->ui_element_type,
                    'icon' => $this->icon,
                    'title' => $this->title,
                    'brief' => $this->brief,
                    'details' => $this->details,
                    'docs_url' => $this->docs_url ?: null,
                    'video_url' => $this->video_url ?: null,
                    'is_active' => $this->is_active,
                ]);

                $message = 'Help content updated successfully!';
            } else {
                $helpContent = HelpContent::create([
                    'key' => $this->key,
                    'category' => $this->category,
                    'ui_element_type' => $this->ui_element_type,
                    'icon' => $this->icon,
                    'title' => $this->title,
                    'brief' => $this->brief,
                    'details' => $this->details,
                    'docs_url' => $this->docs_url ?: null,
                    'video_url' => $this->video_url ?: null,
                    'is_active' => $this->is_active,
                ]);

                $message = 'Help content created successfully!';
            }

            // Save or update Arabic translation
            if ($this->ar_brief) {
                HelpContentTranslation::updateOrCreate(
                    [
                        'help_content_id' => $helpContent->id,
                        'locale' => 'ar',
                    ],
                    [
                        'brief' => $this->ar_brief,
                        'details' => $this->ar_details,
                    ]
                );
            }

            // Clear cache
            $this->getHelpContentService()->clearCache();

            session()->flash('message', $message);
            $this->showCreateModal = false;
            $this->resetForm();
            $this->dispatch('help-content-saved');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save help content: ' . $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        try {
            $helpContent = HelpContent::findOrFail($this->deletingId);
            $helpContent->delete();

            // Clear cache
            $this->getHelpContentService()->clearCache();

            session()->flash('message', 'Help content deleted successfully!');
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->dispatch('help-content-deleted');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete help content: ' . $e->getMessage());
        }
    }

    public function toggleActive(int $id): void
    {
        try {
            $helpContent = HelpContent::findOrFail($id);
            $helpContent->update(['is_active' => !$helpContent->is_active]);

            // Clear cache
            $this->getHelpContentService()->clearCache();

            session()->flash('message', 'Help content status updated!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function addDetail(): void
    {
        if ($this->newDetailKey && $this->newDetailValue) {
            $this->details[$this->newDetailKey] = $this->newDetailValue;
            $this->newDetailKey = '';
            $this->newDetailValue = '';
        }
    }

    public function removeDetail(string $key): void
    {
        unset($this->details[$key]);
    }

    public function addDetailAr(): void
    {
        if ($this->newDetailKeyAr && $this->newDetailValueAr) {
            $this->ar_details[$this->newDetailKeyAr] = $this->newDetailValueAr;
            $this->newDetailKeyAr = '';
            $this->newDetailValueAr = '';
        }
    }

    public function removeDetailAr(string $key): void
    {
        unset($this->ar_details[$key]);
    }

    public function clearCache(): void
    {
        try {
            Cache::flush();
            session()->flash('message', 'Cache cleared successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->key = '';
        $this->category = '';
        $this->ui_element_type = 'tooltip';
        $this->icon = 'info-circle';
        $this->title = '';
        $this->brief = '';
        $this->details = [];
        $this->docs_url = '';
        $this->video_url = '';
        $this->is_active = true;
        $this->ar_brief = '';
        $this->ar_details = [];
        $this->newDetailKey = '';
        $this->newDetailValue = '';
        $this->newDetailKeyAr = '';
        $this->newDetailValueAr = '';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.help-content-manager');
    }
}
