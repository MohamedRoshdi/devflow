<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class GitHubRepoPicker extends Component
{
    #[Modelable]
    public string $repositoryUrl = '';

    #[Modelable]
    public string $branch = '';

    public string $search = '';

    public string $visibilityFilter = 'all';

    public int $selectedRepoId = 0;

    /** @var array<int, array{name: string, protected: bool}> */
    public array $branches = [];

    public bool $loadingBranches = false;

    public string $selectedBranch = '';

    public bool $isOpen = false;

    public string $step = 'select-repo'; // select-repo, select-branch

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
                ->orWhere('full_name', 'like', "%{$this->search}%")
            )
            )
            ->when($this->visibilityFilter !== 'all', fn ($q) => $q->where('private', $this->visibilityFilter === 'private')
            )
            ->orderBy('stars_count', 'desc')
            ->limit(50)
            ->get();
    }

    public function open(): void
    {
        if (! $this->connection) {
            $this->dispatch('notification', type: 'error', message: 'Please connect to GitHub first');

            return;
        }

        $this->isOpen = true;
        $this->step = 'select-repo';
        $this->reset(['selectedRepoId', 'selectedBranch', 'branches', 'search']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['selectedRepoId', 'selectedBranch', 'branches', 'search', 'step']);
    }

    public function selectRepository(int $repoId): void
    {
        try {
            $this->selectedRepoId = $repoId;
            $this->loadingBranches = true;

            $repository = GitHubRepository::findOrFail($repoId);

            // Load branches from GitHub
            $branches = $this->gitHubService->listBranches($this->connection, $repository->full_name);

            $this->branches = collect($branches)->map(fn ($branch) => [
                'name' => $branch['name'],
                'protected' => $branch['protected'] ?? false,
            ])->toArray();

            // Set default branch
            $this->selectedBranch = $repository->default_branch;

            $this->loadingBranches = false;
            $this->step = 'select-branch';

        } catch (\Exception $e) {
            $this->loadingBranches = false;
            $this->dispatch('notification', type: 'error', message: 'Failed to load branches: '.$e->getMessage());
        }
    }

    public function backToRepoSelection(): void
    {
        $this->step = 'select-repo';
        $this->reset(['selectedRepoId', 'selectedBranch', 'branches']);
    }

    public function confirmSelection(): void
    {
        if ($this->selectedRepoId === 0 || empty($this->selectedBranch)) {
            $this->dispatch('notification', type: 'error', message: 'Please select a repository and branch');

            return;
        }

        try {
            $repository = GitHubRepository::findOrFail($this->selectedRepoId);

            // Set the modelable properties
            $this->repositoryUrl = $repository->clone_url;
            $this->branch = $this->selectedBranch;

            $this->dispatch('repository-selected', [
                'repository' => $repository->toArray(),
                'branch' => $this->selectedBranch,
            ]);

            $this->dispatch('notification', type: 'success', message: 'Repository selected successfully');

            $this->close();

        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to select repository: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.projects.github-repo-picker');
    }
}
