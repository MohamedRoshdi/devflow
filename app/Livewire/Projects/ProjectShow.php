<?php

namespace App\Livewire\Projects;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\DockerService;
use App\Services\GitService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShow extends Component
{
    use WithPagination;

    public Project $project;

    public bool $showDeployModal = false;

    /** @var array<int, array<string, mixed>> */
    public array $commits = [];

    /** @var array<string, mixed>|null */
    public ?array $updateStatus = null;

    public bool $checkingForUpdates = false;

    public bool $autoCheckEnabled = true;

    public bool $autoRefreshEnabled = true;

    public int $autoRefreshInterval = 30; // seconds

    public int $commitPage = 1;

    public int $commitPerPage = 8;

    public int $commitTotal = 0;

    public int $deploymentsPerPage = 5;

    protected string $paginationTheme = 'tailwind';

    public bool $gitLoaded = false;

    public bool $commitsLoading = false;

    public bool $commitsRequested = false;

    public bool $updateStatusLoaded = false;

    public bool $updateStatusRequested = false;

    public ?string $firstTab = null;

    public ?string $lastGitRefreshAt = null;

    public string $activeTab = 'overview';

    public bool $showBranchSelector = false;

    public string $branchSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $availableBranches = [];

    public bool $branchesLoading = false;

    public bool $branchSwitching = false;

    public string $selectedBranch = '';

    public bool $showBranchConfirmModal = false;

    public function mount(Project $project)
    {
        // Use Policy for authorization
        $this->authorize('view', $project);

        // Eager load domains and check for active deployments
        $this->project = $project->load(['domains', 'activeDeployment']);
        $this->firstTab = request()->query('tab', 'overview');
        $this->activeTab = $this->firstTab;
        $this->selectedBranch = $project->branch;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'git' && ! $this->gitLoaded) {
            $this->prepareGitTab();
        }
    }

    public function preloadUpdateStatus(): void
    {
        if ($this->updateStatusLoaded || $this->updateStatusRequested) {
            return;
        }

        $this->updateStatusRequested = true;
        $this->checkForUpdates();
    }

    public function prepareGitTab(): void
    {
        if ($this->gitLoaded || $this->commitsLoading) {
            return;
        }

        $this->commitsLoading = true;

        try {
            $gitService = app(GitService::class);
            $result = $gitService->getLatestCommits($this->project, $this->commitPerPage, $this->commitPage);

            if ($result['success']) {
                $this->commits = $result['commits'];
                $this->commitTotal = $result['total'] ?? count($this->commits);
                $this->commitsRequested = true;
            } else {
                $this->commits = [];
                $this->commitTotal = 0;
            }

            if (! $this->updateStatusLoaded) {
                $updateResult = $gitService->checkForUpdates($this->project);
                if ($updateResult['success']) {
                    $this->updateStatus = $updateResult;
                    $this->updateStatusLoaded = true;
                }
            }

            $this->gitLoaded = true;
            $this->lastGitRefreshAt = now()->toISOString();

        } catch (\Exception $e) {
            \Log::error('prepareGitTab failed: '.$e->getMessage());
            $this->commits = [];
            $this->commitTotal = 0;
            $this->gitLoaded = true;
        } finally {
            $this->commitsLoading = false;
        }
    }

    public function loadCommits()
    {
        $this->commitsLoading = true;

        try {
            $gitService = app(GitService::class);
            $result = $gitService->getLatestCommits($this->project, $this->commitPerPage, $this->commitPage);

            if ($result['success']) {
                $this->commits = $result['commits'];
                $this->commitTotal = $result['total'] ?? count($this->commits);
                $this->commitsRequested = true;
            } else {
                $this->commits = [];
                $this->commitTotal = 0;
            }
        } catch (\Exception $e) {
            $this->commits = [];
            $this->commitTotal = 0;
        } finally {
            $this->commitsLoading = false;
        }
    }

    public function getCommitPagesProperty(): int
    {
        return max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
    }

    public function getCommitRangeProperty(): array
    {
        $start = ($this->commitPage - 1) * $this->commitPerPage + 1;
        $end = min($this->commitPage * $this->commitPerPage, $this->commitTotal);

        return [
            'start' => $this->commitTotal > 0 ? $start : 0,
            'end' => $end,
        ];
    }

    public function previousCommitPage(): void
    {
        if ($this->commitPage > 1) {
            $this->commitPage--;
            $this->loadCommits();
        }
    }

    public function nextCommitPage(): void
    {
        if ($this->commitPage < $this->commitPages) {
            $this->commitPage++;
            $this->loadCommits();
        }
    }

    public function firstCommitPage(): void
    {
        $this->commitPage = 1;
        $this->loadCommits();
    }

    public function lastCommitPage(): void
    {
        $this->commitPage = $this->commitPages;
        $this->loadCommits();
    }

    public function checkForUpdates(bool $interactive = false)
    {
        try {
            $this->checkingForUpdates = true;

            $gitService = app(GitService::class);
            $result = $gitService->checkForUpdates($this->project);

            if ($result['success']) {
                $this->updateStatus = $result;
                $this->updateStatusLoaded = true;
            }

            $this->checkingForUpdates = false;
        } catch (\Exception $e) {
            $this->checkingForUpdates = false;
            $this->updateStatusLoaded = true;
        }
    }

    public function refreshGitData(): void
    {
        $this->gitLoaded = false;
        $this->commitsLoading = false;
        $this->commits = [];
        $this->updateStatus = null;
        $this->updateStatusLoaded = false;
        $this->prepareGitTab();
    }

    public function autoRefreshGit(): void
    {
        // Only refresh if auto-refresh is enabled and we're on the git tab
        if (! $this->autoRefreshEnabled || $this->activeTab !== 'git') {
            return;
        }

        $this->refreshGitData();
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefreshEnabled = ! $this->autoRefreshEnabled;
    }

    public function setAutoRefreshInterval(int $seconds): void
    {
        $this->autoRefreshInterval = max(10, min(300, $seconds)); // Between 10s and 5min
    }

    public function toggleBranchSelector(): void
    {
        $this->showBranchSelector = ! $this->showBranchSelector;

        if ($this->showBranchSelector && empty($this->availableBranches)) {
            $this->loadBranches();
        }
    }

    public function loadBranches(): void
    {
        $this->branchesLoading = true;

        try {
            $gitService = app(GitService::class);
            $result = $gitService->getBranches($this->project);

            if ($result['success']) {
                $this->availableBranches = $result['branches'];
            } else {
                $this->availableBranches = [];
                session()->flash('error', 'Failed to load branches: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->availableBranches = [];
            session()->flash('error', 'Failed to load branches: '.$e->getMessage());
        } finally {
            $this->branchesLoading = false;
        }
    }

    public function getFilteredBranchesProperty(): array
    {
        if (empty($this->branchSearch)) {
            return $this->availableBranches;
        }

        return array_filter($this->availableBranches, function ($branch) {
            return str_contains(strtolower($branch['name']), strtolower($this->branchSearch));
        });
    }

    public function selectBranchForSwitch(string $branchName): void
    {
        $this->selectedBranch = $branchName;
        $this->showBranchConfirmModal = true;
    }

    public function cancelBranchSwitch(): void
    {
        $this->showBranchConfirmModal = false;
        $this->selectedBranch = $this->project->branch;
    }

    public function confirmBranchSwitch(): void
    {
        if ($this->selectedBranch === $this->project->branch) {
            $this->showBranchConfirmModal = false;

            return;
        }

        $this->branchSwitching = true;

        try {
            $gitService = app(GitService::class);
            $result = $gitService->switchBranch($this->project, $this->selectedBranch);

            if ($result['success']) {
                $this->project->refresh();
                session()->flash('message', $result['message']);

                // Refresh git data after branch switch
                $this->gitLoaded = false;
                $this->updateStatusLoaded = false;
                $this->showBranchConfirmModal = false;
                $this->showBranchSelector = false;

                // Check for updates on new branch
                $this->checkForUpdates();
                $this->prepareGitTab();
            } else {
                session()->flash('error', 'Failed to switch branch: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to switch branch: '.$e->getMessage());
        } finally {
            $this->branchSwitching = false;
        }
    }

    public function deploy()
    {
        try {
            // Check if there's already an active deployment
            $activeDeployment = $this->project->deployments()
                ->whereIn('status', ['pending', 'running'])
                ->first();

            if ($activeDeployment) {
                session()->flash('error', 'A deployment is already in progress. Please wait for it to complete or cancel it first.');
                return $this->redirect(route('deployments.show', $activeDeployment), navigate: true);
            }

            $deployment = Deployment::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'branch' => $this->project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            \App\Jobs\DeployProjectJob::dispatch($deployment);

            session()->flash('message', 'Deployment started successfully!');

            return $this->redirect(route('deployments.show', $deployment), navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start deployment: '.$e->getMessage());
        }
    }

    public function startProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->startContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'running']);
                $this->project->refresh();
                session()->flash('message', 'Project started successfully');
            } else {
                session()->flash('error', 'Failed to start project: '.$result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start project: '.$e->getMessage());
        }
    }

    public function stopProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->stopContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'stopped']);
                $this->project->refresh();
                session()->flash('message', 'Project stopped successfully');
            } else {
                session()->flash('error', 'Failed to stop project: '.$result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop project: '.$e->getMessage());
        }
    }

    public function render(): \Illuminate\View\View
    {
        $deployments = $this->project->deployments()
            ->with(['user', 'server'])
            ->latest()
            ->paginate($this->deploymentsPerPage, ['*'], 'deploymentsPage');

        return view('livewire.projects.project-show', [
            'deployments' => $deployments,
            'domains' => $this->project->domains,
            'commitPages' => $this->commitPages,
        ]);
    }
}
