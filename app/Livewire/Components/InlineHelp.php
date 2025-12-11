<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\HelpContentService;
use Livewire\Component;
use Livewire\Attributes\{On, Locked};

class InlineHelp extends Component
{
    #[Locked]
    public string $helpKey;

    public bool $collapsible = false;
    public bool $showDetails = false;
    public bool $isLoading = false;
    public ?string $locale = null;

    protected HelpContentService $helpService;

    public function boot(HelpContentService $helpService): void
    {
        $this->helpService = $helpService;
    }

    public function mount(string $helpKey, bool $collapsible = false): void
    {
        $this->helpKey = $helpKey;
        $this->collapsible = $collapsible;
        $this->locale = app()->getLocale();

        // Record view asynchronously
        $this->recordView();
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function recordView(): void
    {
        try {
            $this->helpService->recordView(
                $this->helpKey,
                auth()->id()
            );
        } catch (\Exception $e) {
            // Silently fail - don't disrupt user experience
            report($e);
        }
    }

    public function markHelpful(): void
    {
        $this->isLoading = true;

        try {
            $this->helpService->recordHelpful(
                $this->helpKey,
                auth()->id()
            );

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Thanks for your feedback!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to record feedback. Please try again.',
            ]);
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    public function markNotHelpful(): void
    {
        $this->isLoading = true;

        try {
            $this->helpService->recordNotHelpful(
                $this->helpKey,
                auth()->id()
            );

            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Thanks! We will improve this help content.',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to record feedback. Please try again.',
            ]);
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    #[On('help-content-updated')]
    public function refreshHelpContent(): void
    {
        $this->render();
    }

    public function render(): \Illuminate\View\View
    {
        // Check if user wants to see inline help (default: true)
        if (auth()->check() && !auth()->user()->show_inline_help) {
            return view('livewire.components.inline-help-hidden');
        }

        $helpContent = null;
        $relatedHelp = collect();

        try {
            $helpContent = $this->helpService->getByKey($this->helpKey);

            if ($helpContent && $this->showDetails) {
                $relatedHelp = $this->helpService->getRelatedHelp($this->helpKey);
            }
        } catch (\Exception $e) {
            // Log error but don't break the page
            report($e);
        }

        return view('livewire.components.inline-help', [
            'helpContent' => $helpContent,
            'relatedHelp' => $relatedHelp,
        ]);
    }
}
