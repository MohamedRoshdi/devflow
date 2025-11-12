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
    public bool $updateStatusLoaded = false;
    public bool $updateStatusRequested = false;

    public function mount(Project $project)
    {
        // Check if project belongs to current user
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }
        
        $this->project = $project;
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
        if ($this->gitLoaded) {
            return;
        }

        $this->commitsLoading = true;
        $this->loadCommits();
        $this->commitsLoading = false;

        if (!$this->updateStatusLoaded) {
            $this->checkForUpdates();
        }

        $this->gitLoaded = true;
    }

    public function loadCommits()
    {
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
            } else {
                $this->commits = [];
                $this->commitTotal = 0;
            }
        } catch (\Exception $e) {
            $this->commits = [];
            $this->commitTotal = 0;
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
        $this->loadCommits();
    }

    public function goToCommitPage(int $page): void
    {
        $totalPages = max(1, (int)ceil(max(0, $this->commitTotal) / $this->commitPerPage));
        $page = min(max(1, $page), $totalPages);

        if ($page !== $this->commitPage) {
            $this->commitPage = $page;
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
        ]);
    }
}

