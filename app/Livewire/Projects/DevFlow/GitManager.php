<?php

declare(strict_types=1);

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

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

    public function mount(): void
    {
        $this->loadGitInfo();
        $this->selectedBranch = $this->gitBranch;
    }

    public function loadGitInfo(): void
    {
        $projectPath = base_path();
        $this->isGitRepo = is_dir($projectPath . '/.git');

        if ($this->isGitRepo) {
            // Get current branch
            $result = Process::run("cd {$projectPath} && git branch --show-current");
            $this->gitBranch = trim($result->output()) ?: 'unknown';

            // Get last commit
            $result = Process::run("cd {$projectPath} && git log -1 --format='%h - %s (%cr)'");
            $this->gitLastCommit = trim($result->output()) ?: 'unknown';

            // Get remote URL
            $result = Process::run("cd {$projectPath} && git remote get-url origin 2>/dev/null");
            $this->gitRemoteUrl = trim($result->output()) ?: 'No remote configured';

            $this->selectedBranch = $this->gitBranch;
        }
    }

    // ===== GIT SETUP METHODS =====

    public function toggleGitSetup(): void
    {
        $this->showGitSetup = !$this->showGitSetup;
        $this->gitSetupOutput = '';
    }

    public function initializeGit(): void
    {
        $this->isSettingUpGit = true;
        $this->gitSetupOutput = '';

        try {
            $projectPath = base_path();

            // Validate URL - support both HTTPS and SSH formats
            if (empty($this->newRepoUrl)) {
                throw new \Exception('Please enter a repository URL');
            }

            // Check for valid git URL formats:
            // - HTTPS: https://github.com/user/repo.git
            // - SSH: git@github.com:user/repo.git
            $isHttpsUrl = filter_var($this->newRepoUrl, FILTER_VALIDATE_URL);
            $isSshUrl = preg_match('/^git@[\w.-]+:[\w.\/-]+\.git$/', $this->newRepoUrl);

            if (!$isHttpsUrl && !$isSshUrl) {
                throw new \Exception('Please enter a valid repository URL (HTTPS or SSH format)');
            }

            $this->gitSetupOutput .= "🔧 Initializing Git repository...\n";

            // Initialize git
            $result = Process::run("cd {$projectPath} && git init");
            $this->gitSetupOutput .= $result->output() . "\n";

            // Add safe directory
            $result = Process::run("git config --global --add safe.directory {$projectPath}");

            // Add remote origin
            $this->gitSetupOutput .= "📡 Adding remote origin: {$this->newRepoUrl}\n";
            $result = Process::run("cd {$projectPath} && git remote add origin {$this->newRepoUrl}");
            if (!$result->successful()) {
                // Remote might already exist, try to set URL instead
                Process::run("cd {$projectPath} && git remote set-url origin {$this->newRepoUrl}");
            }

            // Fetch from remote
            $this->gitSetupOutput .= "📥 Fetching from remote...\n";
            $result = Process::timeout(120)->run("cd {$projectPath} && git fetch origin");
            $this->gitSetupOutput .= $result->output() . "\n";

            // Set branch
            $this->gitSetupOutput .= "🌿 Setting up branch: {$this->newBranch}\n";
            Process::run("cd {$projectPath} && git checkout -b {$this->newBranch} 2>/dev/null || git checkout {$this->newBranch}");

            // Set upstream
            Process::run("cd {$projectPath} && git branch --set-upstream-to=origin/{$this->newBranch} {$this->newBranch}");

            $this->gitSetupOutput .= "\n✅ Git repository initialized successfully!\n";
            $this->gitSetupOutput .= "Repository: {$this->newRepoUrl}\n";
            $this->gitSetupOutput .= "Branch: {$this->newBranch}\n";

            // Reload git info
            $this->loadGitInfo();
            $this->showGitSetup = false;

            session()->flash('message', 'Git repository initialized successfully!');

            Log::info('DevFlow Git initialized', [
                'user_id' => auth()->id(),
                'repo_url' => $this->newRepoUrl,
                'branch' => $this->newBranch,
            ]);

        } catch (\Exception $e) {
            $this->gitSetupOutput .= "\n❌ Error: " . $e->getMessage() . "\n";
            session()->flash('error', 'Git initialization failed: ' . $e->getMessage());
        } finally {
            $this->isSettingUpGit = false;
        }
    }

    public function removeGit(): void
    {
        try {
            $projectPath = base_path();

            // Remove .git directory
            Process::run("cd {$projectPath} && rm -rf .git");

            $this->isGitRepo = false;
            $this->gitBranch = '';
            $this->gitLastCommit = '';
            $this->gitRemoteUrl = '';

            session()->flash('message', 'Git repository removed. You can now reinitialize with a different repository.');

            Log::info('DevFlow Git removed', ['user_id' => auth()->id()]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove Git: ' . $e->getMessage());
        }
    }

    // ===== GIT TAB METHODS =====

    public function loadGitTab(): void
    {
        if (!$this->isGitRepo) {
            return;
        }

        $this->gitLoading = true;

        try {
            $this->loadGitCommits();
            $this->loadGitStatusInfo();
            $this->loadBranches();
            $this->loadCurrentCommit();
        } catch (\Exception $e) {
            Log::error('Failed to load Git tab: ' . $e->getMessage());
        } finally {
            $this->gitLoading = false;
        }
    }

    private function loadGitCommits(): void
    {
        $projectPath = base_path();
        $skip = max(0, ($this->commitPage - 1) * $this->commitPerPage);

        // Configure safe directory first
        Process::run("git config --global --add safe.directory {$projectPath} 2>&1 || true");

        // Get total commit count
        $countResult = Process::timeout(15)->run("cd {$projectPath} && git rev-list --count HEAD 2>&1");
        $this->commitTotal = $countResult->successful() ? (int) trim($countResult->output()) : 0;

        // Get commit history
        $logCommand = "cd {$projectPath} && git log --pretty=format:'%H|%an|%ae|%at|%s' --skip={$skip} -n {$this->commitPerPage} 2>&1";
        $logResult = Process::timeout(20)->run($logCommand);

        $this->commits = [];
        if ($logResult->successful()) {
            $lines = explode("\n", trim($logResult->output()));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $parts = explode('|', $line, 5);
                if (count($parts) === 5) {
                    [$hash, $author, $email, $timestamp, $message] = $parts;

                    $this->commits[] = [
                        'hash' => $hash,
                        'short_hash' => substr($hash, 0, 7),
                        'author' => $author,
                        'email' => $email,
                        'timestamp' => (int) $timestamp,
                        'date' => date('Y-m-d H:i:s', (int) $timestamp),
                        'message' => $message,
                    ];
                }
            }
        }
    }

    private function loadGitStatusInfo(): void
    {
        $projectPath = base_path();

        // Get git status
        $statusResult = Process::run("cd {$projectPath} && git status --porcelain 2>&1");

        $this->gitStatus = [
            'clean' => $statusResult->successful() && empty(trim($statusResult->output())),
            'modified' => [],
            'staged' => [],
            'untracked' => [],
        ];

        if ($statusResult->successful() && !empty(trim($statusResult->output()))) {
            $lines = explode("\n", trim($statusResult->output()));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $status = substr($line, 0, 2);
                $file = trim(substr($line, 3));

                if ($status === '??') {
                    $this->gitStatus['untracked'][] = $file;
                } elseif (trim($status[0]) !== '') {
                    $this->gitStatus['staged'][] = $file;
                } elseif (trim($status[1]) !== '') {
                    $this->gitStatus['modified'][] = $file;
                }
            }
        }
    }

    private function loadBranches(): void
    {
        $projectPath = base_path();

        // Get all branches
        $branchResult = Process::run("cd {$projectPath} && git branch -a 2>&1");

        $this->branches = [];
        if ($branchResult->successful()) {
            $lines = explode("\n", trim($branchResult->output()));

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Remove leading * and spaces
                $branch = preg_replace('/^\*?\s+/', '', $line);
                if ($branch === null) continue;

                // Skip remote HEAD references
                if (str_contains($branch, 'remotes/origin/HEAD')) continue;

                // Clean up remote branch names
                $branch = str_replace('remotes/origin/', '', $branch);

                if (!in_array($branch, $this->branches)) {
                    $this->branches[] = $branch;
                }
            }
        }
    }

    private function loadCurrentCommit(): void
    {
        $projectPath = base_path();

        $result = Process::run("cd {$projectPath} && git log -1 --pretty=format:'%H|%an|%ae|%at|%s' 2>&1");

        if ($result->successful() && !empty(trim($result->output()))) {
            $parts = explode('|', trim($result->output()), 5);
            if (count($parts) === 5) {
                [$hash, $author, $email, $timestamp, $message] = $parts;

                $this->currentCommit = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'email' => $email,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', (int) $timestamp),
                    'message' => $message,
                ];
            }
        }
    }

    public function refreshGitTab(): void
    {
        $this->loadGitInfo();
        $this->loadGitTab();
        session()->flash('message', 'Git information refreshed successfully!');
    }

    public function pullLatestChanges(): void
    {
        if (!$this->isGitRepo) {
            session()->flash('error', 'Not a Git repository');
            return;
        }

        $this->pullingChanges = true;

        try {
            $projectPath = base_path();

            // Fetch and pull
            $fetchResult = Process::timeout(60)->run("cd {$projectPath} && git fetch origin {$this->gitBranch} 2>&1");

            if (!$fetchResult->successful()) {
                throw new \Exception('Failed to fetch: ' . $fetchResult->errorOutput());
            }

            $pullResult = Process::timeout(60)->run("cd {$projectPath} && git pull origin {$this->gitBranch} 2>&1");

            if (!$pullResult->successful()) {
                throw new \Exception('Failed to pull: ' . $pullResult->errorOutput());
            }

            $this->loadGitInfo();
            $this->loadGitTab();

            session()->flash('message', 'Successfully pulled latest changes from ' . $this->gitBranch);

            Log::info('DevFlow Git pull completed', [
                'user_id' => auth()->id(),
                'branch' => $this->gitBranch,
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to pull changes: ' . $e->getMessage());
        } finally {
            $this->pullingChanges = false;
        }
    }

    public function switchBranch(string $branch): void
    {
        if (!$this->isGitRepo || empty($branch)) {
            session()->flash('error', 'Invalid branch selection');
            return;
        }

        try {
            $projectPath = base_path();

            // Checkout branch
            $checkoutResult = Process::timeout(30)->run("cd {$projectPath} && git checkout {$branch} 2>&1");

            if (!$checkoutResult->successful()) {
                throw new \Exception('Failed to switch branch: ' . $checkoutResult->errorOutput());
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
            session()->flash('error', 'Failed to switch branch: ' . $e->getMessage());
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

    /**
     * @return int
     */
    public function getCommitPagesProperty(): int
    {
        return max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.git-manager');
    }
}
