<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\Project;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GitHubSettings extends Component
{
    public string $search = '';

    public string $visibilityFilter = 'all';

    public string $languageFilter = 'all';

    public bool $syncing = false;

    public int $syncProgress = 0;

    public int $selectedRepoId = 0;

    public int $selectedProjectId = 0;

    public bool $showLinkModal = false;

    public function __construct(
        private readonly GitHubService $gitHubService
    ) {}

    #[Computed]
    public function connection(): ?GitHubConnection
    {
        return GitHubConnection::activeForUser(Auth::id());
    }

    #[Computed]
    public function repositories()
    {
        if (! $this->connection) {
            return collect();
        }

        return GitHubRepository::where('github_connection_id', $this->connection->id)
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            )
            )
            ->when($this->visibilityFilter !== 'all', fn ($q) => $q->where('private', $this->visibilityFilter === 'private')
            )
            ->when($this->languageFilter !== 'all', fn ($q) => $q->where('language', $this->languageFilter)
            )
            ->with('project')
            ->orderBy('stars_count', 'desc')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->connection) {
            return [
                'total' => 0,
                'public' => 0,
                'private' => 0,
                'linked' => 0,
            ];
        }

        $repos = GitHubRepository::where('github_connection_id', $this->connection->id);

        return [
            'total' => $repos->count(),
            'public' => (clone $repos)->where('private', false)->count(),
            'private' => (clone $repos)->where('private', true)->count(),
            'linked' => (clone $repos)->whereNotNull('project_id')->count(),
        ];
    }

    #[Computed]
    public function availableLanguages(): array
    {
        if (! $this->connection) {
            return [];
        }

        return GitHubRepository::where('github_connection_id', $this->connection->id)
            ->whereNotNull('language')
            ->distinct()
            ->pluck('language')
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('name')->get();
    }

    public function syncRepositories(): void
    {
        if (! $this->connection) {
            $this->dispatch('notification', type: 'error', message: 'No GitHub connection found');

            return;
        }

        $this->syncing = true;
        $this->syncProgress = 0;

        try {
            $count = $this->gitHubService->syncRepositories($this->connection);

            $this->syncing = false;
            $this->syncProgress = 100;

            // Clear computed properties cache
            unset($this->repositories);
            unset($this->stats);
            unset($this->availableLanguages);

            $this->dispatch('notification', type: 'success', message: "Successfully synced {$count} repositories");
        } catch (\Exception $e) {
            $this->syncing = false;
            $this->dispatch('notification', type: 'error', message: 'Failed to sync repositories: '.$e->getMessage());
        }
    }

    public function openLinkModal(int $repoId): void
    {
        $this->selectedRepoId = $repoId;
        $this->selectedProjectId = 0;
        $this->showLinkModal = true;
    }

    public function linkToProject(): void
    {
        if ($this->selectedRepoId === 0 || $this->selectedProjectId === 0) {
            $this->dispatch('notification', type: 'error', message: 'Please select a project');

            return;
        }

        try {
            $repository = GitHubRepository::findOrFail($this->selectedRepoId);

            // Check if this project is already linked to another repo
            $existingLink = GitHubRepository::where('project_id', $this->selectedProjectId)
                ->where('id', '!=', $this->selectedRepoId)
                ->first();

            if ($existingLink) {
                // Unlink the previous repo
                $existingLink->update(['project_id' => null]);
            }

            $repository->update(['project_id' => $this->selectedProjectId]);

            // Update project with GitHub repo URL
            $project = Project::find($this->selectedProjectId);
            if ($project && empty($project->repository_url)) {
                $project->update(['repository_url' => $repository->clone_url]);
            }

            $this->showLinkModal = false;
            unset($this->repositories);
            unset($this->stats);

            $this->dispatch('notification', type: 'success', message: 'Repository linked to project successfully');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to link repository: '.$e->getMessage());
        }
    }

    public function unlinkProject(int $repoId): void
    {
        try {
            $repository = GitHubRepository::findOrFail($repoId);
            $repository->update(['project_id' => null]);

            unset($this->repositories);
            unset($this->stats);

            $this->dispatch('notification', type: 'success', message: 'Repository unlinked from project');
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to unlink repository: '.$e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->dispatch('confirm-disconnect');
    }

    #[On('disconnect-confirmed')]
    public function confirmDisconnect()
    {
        return redirect()->route('github.disconnect');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.github-settings')
            ->layout('layouts.app');
    }
}
