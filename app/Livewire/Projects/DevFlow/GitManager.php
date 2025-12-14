<?php

declare(strict_types=1);

namespace App\Livewire\Projects\DevFlow;

use App\Services\LocalGitService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class GitManager extends Component
{
    // Git Info
    public bool $isGitRepo = false;
    public string $gitBranch = '';
    public string $gitLastCommit = '';
    public string $gitRemoteUrl = '';

    // Git Setup Form
    public bool $showGitSetup = false;
    public string $newRepoUrl = 'https://github.com/your-username/devflow-pro.git';
    public string $newBranch = 'master';
    public string $gitSetupOutput = '';
    public bool $isSettingUpGit = false;

    // Git Tab State
    /** @var array<int, array<string, mixed>> */
    public array $commits = [];
    /** @var array<string, mixed> */
    public array $gitStatus = [];
    /** @var array<string, mixed>|null */
    public ?array $currentCommit = null;
    public int $commitPage = 1;
    public int $commitPerPage = 15;
    public int $commitTotal = 0;
    public bool $gitLoading = false;
    public string $selectedBranch = '';
    /** @var array<int, string> */
    public array $branches = [];
    public bool $pullingChanges = false;

    protected LocalGitService $gitService;

    public function boot(): void
    {
        $this->gitService = new LocalGitService();
    }

    public function mount(): void
    {
        $this->loadGitInfo();
        $this->selectedBranch = $this->gitBranch;

        if ($this->isGitRepo) {
            $this->loadGitTab();
        }
    }

    public function loadGitInfo(): void
    {
        $this->isGitRepo = $this->gitService->isGitRepository();

        if ($this->isGitRepo) {
            $gitInfo = $this->gitService->getGitInfo();
            $this->gitBranch = $gitInfo['branch'];
            $this->gitLastCommit = $gitInfo['last_commit'];
            $this->gitRemoteUrl = $gitInfo['remote_url'];
            $this->selectedBranch = $this->gitBranch;
        }
    }

    // ===== GIT SETUP METHODS =====

    public function toggleGitSetup(): void
    {
        $this->showGitSetup = ! $this->showGitSetup;
        $this->gitSetupOutput = '';
    }

    public function initializeGit(): void
    {
        $this->isSettingUpGit = true;
        $this->gitSetupOutput = '';

        try {
            if (empty($this->newRepoUrl)) {
                throw new \Exception('Please enter a repository URL');
            }

            $result = $this->gitService->initialize($this->newRepoUrl, $this->newBranch);

            if (! $result['success']) {
                throw new \Exception($result['error']);
            }

            $this->gitSetupOutput = $result['output'];
            $this->loadGitInfo();
            $this->showGitSetup = false;

            session()->flash('message', 'Git repository initialized successfully!');

            Log::info('DevFlow Git initialized', [
                'user_id' => auth()->id(),
                'repo_url' => $this->newRepoUrl,
                'branch' => $this->newBranch,
            ]);
        } catch (\Exception $e) {
            $this->gitSetupOutput .= "\n❌ Error: ".$e->getMessage()."\n";
            session()->flash('error', 'Git initialization failed: '.$e->getMessage());
        } finally {
            $this->isSettingUpGit = false;
        }
    }

    public function removeGit(): void
    {
        try {
            $result = $this->gitService->remove();

            if (! $result['success']) {
                throw new \Exception($result['error']);
            }

            $this->isGitRepo = false;
            $this->gitBranch = '';
            $this->gitLastCommit = '';
            $this->gitRemoteUrl = '';

            session()->flash('message', 'Git repository removed. You can now reinitialize with a different repository.');

            Log::info('DevFlow Git removed', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove Git: '.$e->getMessage());
        }
    }

    // ===== GIT TAB METHODS =====

    public function loadGitTab(): void
    {
        if (! $this->isGitRepo) {
            return;
        }

        $this->gitLoading = true;

        try {
            $this->loadGitCommits();
            $this->loadGitStatusInfo();
            $this->loadBranches();
            $this->loadCurrentCommit();
        } catch (\Exception $e) {
            Log::error('Failed to load Git tab: '.$e->getMessage());
        } finally {
            $this->gitLoading = false;
        }
    }

    private function loadGitCommits(): void
    {
        $result = $this->gitService->getCommits($this->commitPage, $this->commitPerPage);
        $this->commits = $result['commits'];
        $this->commitTotal = $result['total'];
    }

    private function loadGitStatusInfo(): void
    {
        $this->gitStatus = $this->gitService->getStatus();
    }

    private function loadBranches(): void
    {
        $this->branches = $this->gitService->getBranches();
    }

    private function loadCurrentCommit(): void
    {
        $this->currentCommit = $this->gitService->getCurrentCommit();
    }

    public function refreshGitTab(): void
    {
        $this->loadGitInfo();
        $this->loadGitTab();
        session()->flash('message', 'Git information refreshed successfully!');
    }

    public function pullLatestChanges(): void
    {
        if (! $this->isGitRepo) {
            session()->flash('error', 'Not a Git repository');

            return;
        }

        $this->pullingChanges = true;

        try {
            $result = $this->gitService->pull($this->gitBranch);

            if (! $result['success']) {
                throw new \Exception($result['error']);
            }

            $this->loadGitInfo();
            $this->loadGitTab();

            session()->flash('message', 'Successfully pulled latest changes from '.$this->gitBranch);

            Log::info('DevFlow Git pull completed', [
                'user_id' => auth()->id(),
                'branch' => $this->gitBranch,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to pull changes: '.$e->getMessage());
        } finally {
            $this->pullingChanges = false;
        }
    }

    public function switchBranch(string $branch): void
    {
        if (! $this->isGitRepo || empty($branch)) {
            session()->flash('error', 'Invalid branch selection');

            return;
        }

        try {
            $result = $this->gitService->switchBranch($branch);

            if (! $result['success']) {
                throw new \Exception($result['error']);
            }

            $this->selectedBranch = $branch;
            $this->gitBranch = $branch;
            $this->loadGitInfo();
            $this->loadGitTab();

            session()->flash('message', "Successfully switched to branch: {$branch}");

            Log::info('DevFlow Git branch switched', [
                'user_id' => auth()->id(),
                'branch' => $branch,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to switch branch: '.$e->getMessage());
        }
    }

    public function previousCommitPage(): void
    {
        if ($this->commitPage > 1) {
            $this->commitPage--;
            $this->loadGitCommits();
        }
    }

    public function nextCommitPage(): void
    {
        $maxPages = max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
        if ($this->commitPage < $maxPages) {
            $this->commitPage++;
            $this->loadGitCommits();
        }
    }

    public function getCommitPagesProperty(): int
    {
        return max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
    }

    public function render(): View
    {
        return view('livewire.projects.devflow.git-manager');
    }
}
