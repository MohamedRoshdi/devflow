<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use App\Models\Deployment;
use App\Services\DockerService;
use App\Services\GitService;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class ProjectShow extends Component
{
    use WithPagination;

    public Project $project;
    public $showDeployModal = false;
    public $commits = [];
    public $updateStatus = null;
    public $checkingForUpdates = false;
    public $autoCheckEnabled = true;
    public int $commitPage = 1;
    public int $commitPerPage = 8;
    public int $commitTotal = 0;
    public int $deploymentsPerPage = 5;
    protected $paginationTheme = 'tailwind';

    public bool $gitLoaded = false;
    public bool $commitsLoading = false;
    public bool $commitsRequested = false;
    public bool $updateStatusLoaded = false;
    public bool $updateStatusRequested = false;
    public ?string $firstTab = null;
    public ?string $lastGitRefreshAt = null;

    // Cache for Git data
    private $cachedCommits = null;
    private $cachedUpdateStatus = null;
    private $cacheExpiry = null;

    public function mount(Project $project)
    {
        // Check if project belongs to current user
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }

        $this->project = $project;
        $this->firstTab = request()->query('tab', 'overview');

        // Initialize state
        $this->gitLoaded = false;
        $this->commitsLoading = false;
        $this->commitsRequested = false;
        $this->updateStatusLoaded = false;
        $this->updateStatusRequested = false;
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
        // If already loaded and cache is valid, use cached data
        if ($this->gitLoaded && $this->cacheExpiry && now()->lt($this->cacheExpiry)) {
            // Use cached data
            if ($this->cachedCommits !== null) {
                $this->commits = $this->cachedCommits;
                $this->commitTotal = count($this->commits);
            }
            if ($this->cachedUpdateStatus !== null) {
                $this->updateStatus = $this->cachedUpdateStatus;
            }
            return;
        }

        // Only load if not currently loading
        if ($this->commitsLoading) {
            return;
        }

        // Set loading state
        $this->commitsLoading = true;

        try {
            // Load commits
            $this->loadCommitsInternal();

            // Check for updates if needed
            if (!$this->updateStatusLoaded) {
                $this->checkForUpdatesInternal();
            }

            // Cache the data
            $this->cachedCommits = $this->commits;
            $this->cachedUpdateStatus = $this->updateStatus;
            $this->cacheExpiry = now()->addMinutes(5);

            $this->gitLoaded = true;
            $this->lastGitRefreshAt = now();

        } catch (\Exception $e) {
            \Log::error('prepareGitTab failed: ' . $e->getMessage());

            // Set empty data to prevent infinite loading
            $this->commits = [];
            $this->commitTotal = 0;
            $this->gitLoaded = true;

            // Display error message to user
            session()->flash('error', 'Failed to load Git data. Please try again.');
        } finally {
            $this->commitsLoading = false;
        }
    }

    private function loadCommitsInternal(): void
    {
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
            throw $e;
        }
    }

    private function checkForUpdatesInternal(): void
    {
        try {
            $gitService = app(GitService::class);
            $result = $gitService->checkForUpdates($this->project);

            if ($result['success']) {
                $this->updateStatus = $result;
                $this->updateStatusLoaded = true;
            }
        } catch (\Exception $e) {
            // Silently fail for update check
            $this->updateStatusLoaded = true;
        }
    }

    public function loadCommitsAction(): void
    {
        if ($this->commitsRequested && !$this->commitsLoading) {
            return;
        }

        $this->commitsRequested = true;
        $this->loadCommits();
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

                $totalPages = max(1, (int)ceil(max(0, $this->commitTotal) / $this->commitPerPage));

                if ($this->commitTotal === 0) {
                    $totalPages = 1;
                }

                if ($this->commitPage > $totalPages) {
                    $this->commitPage = $totalPages;
                    $this->loadCommits();
                    return;
                }

                // Mark commits as loaded
                $this->commitsRequested = true;
            } else {
                $this->commits = [];
                $this->commitTotal = 0;
                \Log::warning('Git commits load failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->commits = [];
            $this->commitTotal = 0;
            \Log::error('Exception loading commits: ' . $e->getMessage());
        } finally {
            $this->commitsLoading = false;
        }
    }

    public function getCommitRangeProperty(): array
    {
        if ($this->commitTotal === 0 || count($this->commits) === 0) {
            return ['start' => 0, 'end' => 0];
        }

        $start = (($this->commitPage - 1) * $this->commitPerPage) + 1;
        $end = $start + count($this->commits) - 1;

        return [
            'start' => $start,
            'end' => min($end, $this->commitTotal),
        ];
    }

    public function setCommitPerPage($perPage): void
    {
        $perPage = (int) $perPage;

        if ($perPage <= 0 || $perPage > 50) {
            return;
        }

        $this->commitPerPage = $perPage;
        $this->commitPage = 1;
        $this->commitsRequested = true;
        $this->loadCommits();
    }

    public function goToCommitPage(int $page): void
    {
        $totalPages = max(1, (int)ceil(max(0, $this->commitTotal) / $this->commitPerPage));
        $page = min(max(1, $page), $totalPages);

        if ($page !== $this->commitPage) {
            $this->commitPage = $page;
            $this->commitsRequested = true;
            $this->loadCommits();
        }
    }

    public function previousCommitPage(): void
    {
        $this->goToCommitPage($this->commitPage - 1);
    }

    public function nextCommitPage(): void
    {
        $this->goToCommitPage($this->commitPage + 1);
    }

    /**
     * Compatibility handler for nested components emitting switchTab.
     * The project view itself manages tabs via Alpine, so we simply ignore.
     */
    public function switchTab($tab): void
    {
        // Intentionally left blank - prevents Livewire MethodNotFound exceptions
        // when child components bubble switchTab events to the parent.
    }

    public function firstCommitPage(): void
    {
        $this->goToCommitPage(1);
    }

    public function lastCommitPage(): void
    {
        $totalPages = max(1, (int) ceil(max(0, $this->commitTotal) / $this->commitPerPage));
        $this->goToCommitPage($totalPages);
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
                
                if ($interactive) {
                    if ($result['up_to_date']) {
                        session()->flash('message', 'Project is up-to-date with the latest commit!');
                    } else {
                        session()->flash('message', "New updates available! {$result['commits_behind']} commit(s) behind.");
                    }
                }
            } else {
                if ($interactive) {
                    session()->flash('error', 'Failed to check for updates: ' . $result['error']);
                }
                $this->updateStatusLoaded = true;
            }
            
            $this->checkingForUpdates = false;
        } catch (\Exception $e) {
            $this->checkingForUpdates = false;
            $this->updateStatusLoaded = true;
            if ($interactive) {
                session()->flash('error', 'Failed to check for updates: ' . $e->getMessage());
            }
        }
    }

    public function prepareGitTabInteractive(): void
    {
        $this->prepareGitTab();
    }

    public function refreshGitData(): void
    {
        // Clear cache to force refresh
        $this->cachedCommits = null;
        $this->cachedUpdateStatus = null;
        $this->cacheExpiry = null;
        $this->gitLoaded = false;

        // Reload data
        $this->prepareGitTab();
    }

    public function deploy()
    {
        try {
            $deployment = Deployment::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'branch' => $this->project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            // Dispatch deployment job
            \App\Jobs\DeployProjectJob::dispatch($deployment);

            $this->showDeployModal = false;
            
            // Auto-redirect to deployment page to watch progress
            return redirect()->route('deployments.show', $deployment);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start deployment: ' . $e->getMessage());
        }
    }

    public function startProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->startContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'running']);
                session()->flash('message', 'Project started successfully');
            } else {
                session()->flash('error', 'Failed to start project: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start project: ' . $e->getMessage());
        }
    }

    public function stopProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->stopContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'stopped']);
                session()->flash('message', 'Project stopped successfully');
            } else {
                session()->flash('error', 'Failed to stop project: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop project: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $deployments = $this->project->deployments()
            ->latest()
            ->paginate($this->deploymentsPerPage, ['*'], 'deploymentsPage');
        $domains = $this->project->domains;

        return view('livewire.projects.project-show', [
            'deployments' => $deployments,
            'domains' => $domains,
            'commits' => $this->commits,
            'updateStatus' => $this->updateStatus,
            'firstTab' => $this->firstTab,
        ]);
    }
}

