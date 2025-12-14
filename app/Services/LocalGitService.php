<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Service for local git operations.
 *
 * Handles git commands on the local filesystem, useful for
 * self-managed deployments like DevFlow Pro's own installation.
 */
class LocalGitService
{
    protected string $projectPath;

    public function __construct(?string $projectPath = null)
    {
        $this->projectPath = $projectPath ?? base_path();
    }

    /**
     * Set the project path for git operations
     */
    public function setProjectPath(string $path): self
    {
        $this->projectPath = $path;

        return $this;
    }

    /**
     * Check if the project path is a git repository
     */
    public function isGitRepository(): bool
    {
        return is_dir($this->projectPath.'/.git');
    }

    /**
     * Get basic git information
     *
     * @return array{branch: string, last_commit: string, remote_url: string}
     */
    public function getGitInfo(): array
    {
        if (! $this->isGitRepository()) {
            return [
                'branch' => '',
                'last_commit' => '',
                'remote_url' => '',
            ];
        }

        return [
            'branch' => $this->getCurrentBranch(),
            'last_commit' => $this->getLastCommitInfo(),
            'remote_url' => $this->getRemoteUrl(),
        ];
    }

    /**
     * Get current branch name
     */
    public function getCurrentBranch(): string
    {
        $result = $this->runGitCommand('branch --show-current');

        return $result['success'] ? trim($result['output']) : 'unknown';
    }

    /**
     * Get last commit info formatted
     */
    public function getLastCommitInfo(): string
    {
        $result = $this->runGitCommand("log -1 --format='%h - %s (%cr)'");

        return $result['success'] ? trim($result['output']) : 'unknown';
    }

    /**
     * Get remote origin URL
     */
    public function getRemoteUrl(): string
    {
        $result = $this->runGitCommand('remote get-url origin 2>/dev/null');

        return $result['success'] ? trim($result['output']) : 'No remote configured';
    }

    /**
     * Get commit history with pagination
     *
     * @return array{commits: array<int, array<string, mixed>>, total: int}
     */
    public function getCommits(int $page = 1, int $perPage = 15): array
    {
        $this->ensureSafeDirectory();

        $skip = max(0, ($page - 1) * $perPage);

        // Get total count
        $countResult = $this->runGitCommand('rev-list --count HEAD', 15);
        $total = $countResult['success'] ? (int) trim($countResult['output']) : 0;

        // Get commits
        $logResult = $this->runGitCommand(
            "log --pretty=format:'%H|%an|%ae|%at|%s' --skip={$skip} -n {$perPage}",
            20
        );

        $commits = [];
        if ($logResult['success'] && ! empty(trim($logResult['output']))) {
            $lines = explode("\n", trim($logResult['output']));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                $parts = explode('|', $line, 5);
                if (count($parts) === 5) {
                    [$hash, $author, $email, $timestamp, $message] = $parts;

                    $commits[] = [
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

        return ['commits' => $commits, 'total' => $total];
    }

    /**
     * Get current HEAD commit details
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentCommit(): ?array
    {
        $result = $this->runGitCommand("log -1 --pretty=format:'%H|%an|%ae|%at|%s'");

        if (! $result['success'] || empty(trim($result['output']))) {
            return null;
        }

        $parts = explode('|', trim($result['output']), 5);
        if (count($parts) !== 5) {
            return null;
        }

        [$hash, $author, $email, $timestamp, $message] = $parts;

        return [
            'hash' => $hash,
            'short_hash' => substr($hash, 0, 7),
            'author' => $author,
            'email' => $email,
            'timestamp' => (int) $timestamp,
            'date' => date('Y-m-d H:i:s', (int) $timestamp),
            'message' => $message,
        ];
    }

    /**
     * Get git status (modified, staged, untracked files)
     *
     * @return array{clean: bool, modified: array<int, string>, staged: array<int, string>, untracked: array<int, string>}
     */
    public function getStatus(): array
    {
        $result = $this->runGitCommand('status --porcelain');

        $status = [
            'clean' => true,
            'modified' => [],
            'staged' => [],
            'untracked' => [],
        ];

        if (! $result['success'] || empty(trim($result['output']))) {
            return $status;
        }

        $status['clean'] = false;
        $lines = explode("\n", trim($result['output']));

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $statusCode = substr($line, 0, 2);
            $file = trim(substr($line, 3));

            if ($statusCode === '??') {
                $status['untracked'][] = $file;
            } elseif (trim($statusCode[0]) !== '') {
                $status['staged'][] = $file;
            } elseif (trim($statusCode[1]) !== '') {
                $status['modified'][] = $file;
            }
        }

        return $status;
    }

    /**
     * Get all branches (local and remote)
     *
     * @return array<int, string>
     */
    public function getBranches(): array
    {
        $result = $this->runGitCommand('branch -a');

        $branches = [];
        if (! $result['success']) {
            return $branches;
        }

        $lines = explode("\n", trim($result['output']));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Remove leading * and spaces
            $branch = preg_replace('/^\*?\s+/', '', $line);
            if ($branch === null) {
                continue;
            }

            // Skip remote HEAD references
            if (str_contains($branch, 'remotes/origin/HEAD')) {
                continue;
            }

            // Clean up remote branch names
            $branch = str_replace('remotes/origin/', '', $branch);

            if (! in_array($branch, $branches)) {
                $branches[] = $branch;
            }
        }

        return $branches;
    }

    /**
     * Initialize a new git repository
     *
     * @return array{success: bool, output: string, error: string}
     */
    public function initialize(string $remoteUrl, string $branch = 'master'): array
    {
        $output = '';

        try {
            // Validate URL
            if (! $this->isValidGitUrl($remoteUrl)) {
                return [
                    'success' => false,
                    'output' => '',
                    'error' => 'Invalid repository URL (must be HTTPS or SSH format)',
                ];
            }

            // Initialize git
            $output .= "Initializing Git repository...\n";
            $initResult = $this->runGitCommand('init');
            $output .= $initResult['output']."\n";

            // Add safe directory
            $this->ensureSafeDirectory();

            // Add/set remote origin
            $output .= "Adding remote origin: {$remoteUrl}\n";
            $remoteResult = $this->runGitCommand("remote add origin {$remoteUrl}");
            if (! $remoteResult['success']) {
                $this->runGitCommand("remote set-url origin {$remoteUrl}");
            }

            // Fetch from remote
            $output .= "Fetching from remote...\n";
            $fetchResult = $this->runGitCommand('fetch origin', 120);
            $output .= $fetchResult['output']."\n";

            // Checkout branch
            $output .= "Setting up branch: {$branch}\n";
            $this->runGitCommand("checkout -b {$branch} 2>/dev/null || git checkout {$branch}");
            $this->runGitCommand("branch --set-upstream-to=origin/{$branch} {$branch}");

            $output .= "\nGit repository initialized successfully!\n";

            Log::info('LocalGitService: Repository initialized', [
                'path' => $this->projectPath,
                'remote' => $remoteUrl,
                'branch' => $branch,
            ]);

            return [
                'success' => true,
                'output' => $output,
                'error' => '',
            ];
        } catch (\Exception $e) {
            Log::error('LocalGitService: Failed to initialize repository', [
                'path' => $this->projectPath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'output' => $output,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove git repository
     *
     * @return array{success: bool, error: string}
     */
    public function remove(): array
    {
        try {
            $result = Process::run("rm -rf {$this->projectPath}/.git");

            if (! $result->successful()) {
                throw new \Exception($result->errorOutput());
            }

            Log::info('LocalGitService: Repository removed', [
                'path' => $this->projectPath,
            ]);

            return ['success' => true, 'error' => ''];
        } catch (\Exception $e) {
            Log::error('LocalGitService: Failed to remove repository', [
                'path' => $this->projectPath,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pull latest changes from remote
     *
     * @return array{success: bool, output: string, error: string}
     */
    public function pull(string $branch): array
    {
        try {
            // Fetch first
            $fetchResult = $this->runGitCommand("fetch origin {$branch}", 60);
            if (! $fetchResult['success']) {
                throw new \Exception('Failed to fetch: '.$fetchResult['error']);
            }

            // Pull
            $pullResult = $this->runGitCommand("pull origin {$branch}", 60);
            if (! $pullResult['success']) {
                throw new \Exception('Failed to pull: '.$pullResult['error']);
            }

            Log::info('LocalGitService: Pull completed', [
                'path' => $this->projectPath,
                'branch' => $branch,
            ]);

            return [
                'success' => true,
                'output' => $pullResult['output'],
                'error' => '',
            ];
        } catch (\Exception $e) {
            Log::error('LocalGitService: Pull failed', [
                'path' => $this->projectPath,
                'branch' => $branch,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Switch to a different branch
     *
     * @return array{success: bool, error: string}
     */
    public function switchBranch(string $branch): array
    {
        try {
            $result = $this->runGitCommand("checkout {$branch}", 30);

            if (! $result['success']) {
                throw new \Exception($result['error']);
            }

            Log::info('LocalGitService: Branch switched', [
                'path' => $this->projectPath,
                'branch' => $branch,
            ]);

            return ['success' => true, 'error' => ''];
        } catch (\Exception $e) {
            Log::error('LocalGitService: Failed to switch branch', [
                'path' => $this->projectPath,
                'branch' => $branch,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ensure the project path is marked as a safe directory
     */
    protected function ensureSafeDirectory(): void
    {
        Process::run("git config --global --add safe.directory {$this->projectPath} 2>&1 || true");
    }

    /**
     * Validate git URL format (HTTPS or SSH)
     */
    protected function isValidGitUrl(string $url): bool
    {
        // HTTPS format
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        // SSH format: git@github.com:user/repo.git
        if (preg_match('/^git@[\w.-]+:[\w.\/-]+\.git$/', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Run a git command in the project path
     *
     * @return array{success: bool, output: string, error: string}
     */
    protected function runGitCommand(string $command, int $timeout = 30): array
    {
        $fullCommand = "cd {$this->projectPath} && git {$command} 2>&1";

        $result = Process::timeout($timeout)->run($fullCommand);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
        ];
    }
}
