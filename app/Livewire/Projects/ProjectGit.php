<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\GitService;
use Livewire\Component;
use Livewire\Attributes\On;

class ProjectGit extends Component
{
    public Project $project;
    /** @var array<int, array<string, mixed>> */
    public array $commits = [];
    /** @var array<int, string> */
    public array $branches = [];
    /** @var array<string, mixed> */
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

    public function deployProject(): void
    {
        try {
            // Check if there's already an active deployment
            $activeDeployment = $this->project->deployments()
                ->whereIn('status', ['pending', 'running'])
                ->first();

            if ($activeDeployment) {
                $this->dispatch('notification', type: 'error', message: 'A deployment is already in progress. Please wait for it to complete.');
                $this->redirect(route('deployments.show', $activeDeployment), navigate: true);
                return;
            }

            $deployment = \App\Models\Deployment::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'branch' => $this->project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            \App\Jobs\DeployProjectJob::dispatch($deployment);

            $this->dispatch('notification', type: 'success', message: 'Deployment started successfully!');

            // Redirect to deployment page
            $this->redirect(route('deployments.show', $deployment), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: 'Failed to start deployment: ' . $e->getMessage());
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
