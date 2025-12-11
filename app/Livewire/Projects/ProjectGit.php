<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\GitService;
use Livewire\Component;
use Livewire\Attributes\On;

class ProjectGit extends Component
{
    public Project $project;
    public array $commits = [];
    public array $branches = [];
    public array $updateStatus = [];
    public bool $loading = true;
    public ?string $error = null;
    public int $currentPage = 1;
    public int $perPage = 10;
    public int $totalCommits = 0;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->loadGitData();
    }

    public function loadGitData(): void
    {
        $this->loading = true;
        $this->error = null;

        try {
            $gitService = app(GitService::class);

            // Get commits
            $commitsResult = $gitService->getLatestCommits($this->project, $this->perPage, $this->currentPage);

            if ($commitsResult['success']) {
                $this->commits = $commitsResult['commits'] ?? [];
                $this->totalCommits = $commitsResult['total'] ?? 0;
            } else {
                $this->error = $commitsResult['error'] ?? 'Failed to load commits';
            }

            // Get branches
            $branchesResult = $gitService->getBranches($this->project);

            if ($branchesResult['success']) {
                $this->branches = $branchesResult['branches'] ?? [];
            }

            // Check for updates
            $updateResult = $gitService->checkForUpdates($this->project);

            if ($updateResult['success']) {
                $this->updateStatus = $updateResult;
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function switchBranch(string $branchName): void
    {
        try {
            $gitService = app(GitService::class);
            $result = $gitService->switchBranch($this->project, $branchName);

            if ($result['success']) {
                $this->dispatch('notification', type: 'success', message: "Successfully switched to branch: {$branchName}");
                $this->loadGitData();
            } else {
                $this->dispatch('notification', type: 'error', message: $result['error'] ?? 'Failed to switch branch');
            }
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: $e->getMessage());
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadGitData();
        }
    }

    public function nextPage(): void
    {
        $maxPage = ceil($this->totalCommits / $this->perPage);
        if ($this->currentPage < $maxPage) {
            $this->currentPage++;
            $this->loadGitData();
        }
    }

    #[On('refresh-git')]
    public function refresh(): void
    {
        $this->loadGitData();
    }

    public function render()
    {
        return view('livewire.projects.project-git');
    }
}
