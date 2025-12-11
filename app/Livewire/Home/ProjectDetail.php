<?php

namespace App\Livewire\Home;

use App\Models\Project;
use Livewire\Component;

class ProjectDetail extends Component
{
    public ?Project $project = null;

    public bool $notFound = false;

    public function mount(string $slug)
    {
        // Only show running projects with primary domains for security
        $this->project = Project::query()
            ->where('slug', $slug)
            ->where('status', 'running')
            ->whereHas('domains', function ($query) {
                $query->where('is_primary', true)
                    ->whereNotNull('domain')
                    ->where('domain', '!=', '');
            })
            ->with(['domains' => function ($query) {
                $query->where('is_primary', true);
            }])
            ->first();

        if (! $this->project) {
            $this->notFound = true;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.home.project-detail')
            ->layout('layouts.marketing');
    }
}
