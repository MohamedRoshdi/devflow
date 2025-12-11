<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Process;

class GitService
{
    /**
     * Cache of temporary SSH key files per server ID
     * @var array<int, string>
     */
    protected array $sshKeyFiles = [];

    /**
     * Cleanup temporary SSH key files on destruction
     */
    public function __destruct()
    {
        foreach ($this->sshKeyFiles as $keyFile) {
            if (file_exists($keyFile)) {
                @unlink($keyFile);
            }
        }
    }

    /**
     * Check if server is localhost
     * Note: Even for localhost, we use SSH to run commands as root for git operations
     * since the web server runs as www-data which doesn't have SSH keys for GitHub
     */
    protected function isLocalhost(Server $server): bool
    {
        // Always return false to use SSH - this ensures git commands run as root
        // which has the SSH keys configured for GitHub access
        return false;
    }

    /**
     * Build SSH command for remote execution
     *
     * Security: Temp files are cached per server and cleaned up in __destruct()
     * Additionally, a shutdown function provides cleanup on unexpected termination
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p '.$server->port,
        ];

        if ($server->ssh_key) {
            // Reuse cached temp file if available for this server
            if (!isset($this->sshKeyFiles[$server->id])) {
                // Create temporary SSH key file
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                if ($keyFile === false) {
                    throw new \RuntimeException('Failed to create temporary SSH key file');
                }

                // Security: Set restrictive permissions before writing sensitive data
                chmod($keyFile, 0600);

                // Write SSH key content
                file_put_contents($keyFile, $server->ssh_key);

                // Cache the key file path
                $this->sshKeyFiles[$server->id] = $keyFile;

                // Security: Register shutdown function as additional cleanup protection
                // This ensures cleanup even if the process is terminated unexpectedly
                register_shutdown_function(function () use ($keyFile) {
                    if (file_exists($keyFile)) {
                        @unlink($keyFile);
                    }
                });
            }

            $sshOptions[] = '-i '.$this->sshKeyFiles[$server->id];
        }

        // Escape the command for safe SSH execution
        // Use single quotes and escape any single quotes in the command
        $escapedCommand = str_replace("'", "'\\''", $remoteCommand);

        return sprintf(
            "ssh %s %s@%s '%s'",
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            $escapedCommand
        );
    }

    /**
     * Get the latest commits from a project repository
     */
    public function getLatestCommits(Project $project, int $perPage = 10, int $page = 1): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            $skip = max(0, ($page - 1) * $perPage);

            // Check if repository exists
            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return [
                    'success' => true,
                    'commits' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }

            // Configure safe directory first
            $safeConfigCommand = "git config --global --add safe.directory {$projectPath} 2>&1 || true";
            $command = $this->isLocalhost($server)
                ? $safeConfigCommand
                : $this->buildSSHCommand($server, $safeConfigCommand);
            Process::run($command);

            // Fetch latest commits from remote with timeout
            $fetchCommand = "cd {$projectPath} && timeout 30 git fetch origin {$project->branch} 2>&1 || echo 'fetch-failed'";
            $command = $this->isLocalhost($server)
                ? $fetchCommand
                : $this->buildSSHCommand($server, $fetchCommand);

            $fetchResult = Process::timeout(35)->run($command);

            // Check if fetch failed
            if (str_contains($fetchResult->output(), 'fetch-failed') || ! $fetchResult->successful()) {
                \Log::warning("Git fetch failed for {$project->slug}: ".$fetchResult->errorOutput());
            }

            // Determine total commits for pagination (falls back to local HEAD if remote not available)
            $countCommand = "cd {$projectPath} && timeout 10 git rev-list --count origin/{$project->branch} 2>&1";
            $command = $this->isLocalhost($server)
                ? $countCommand
                : $this->buildSSHCommand($server, $countCommand);
            $countResult = Process::timeout(15)->run($command);
            if (! $countResult->successful()) {
                $countCommand = "cd {$projectPath} && timeout 10 git rev-list --count HEAD 2>&1";
                $command = $this->isLocalhost($server)
                    ? $countCommand
                    : $this->buildSSHCommand($server, $countCommand);
                $countResult = Process::timeout(15)->run($command);
            }
            $totalCommits = $countResult->successful() ? (int) trim($countResult->output()) : 0;

            // Get commit history (even if fetch failed, show what we have)
            $logCommand = "cd {$projectPath} && timeout 15 git log origin/{$project->branch} --pretty=format:'%H|%an|%ae|%at|%s' --skip={$skip} -n {$perPage} 2>&1 || timeout 15 git log HEAD --pretty=format:'%H|%an|%ae|%at|%s' --skip={$skip} -n {$perPage}";
            $command = $this->isLocalhost($server)
                ? $logCommand
                : $this->buildSSHCommand($server, $logCommand);

            $logResult = Process::timeout(20)->run($command);

            if (! $logResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get commit history: '.$logResult->errorOutput(),
                ];
            }

            $commits = [];
            $lines = explode("\n", trim($logResult->output()));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                [$hash, $author, $email, $timestamp, $message] = explode('|', $line, 5);

                $commits[] = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'email' => $email,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'message' => $message,
                ];
            }

            return [
                'success' => true,
                'commits' => $commits,
                'total' => $totalCommits,
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current commit hash of the deployed project
     */
    public function getCurrentCommit(Project $project): ?array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return null;
            }

            $gitCommand = "cd {$projectPath} && git log -1 --pretty=format:'%H|%an|%at|%s'";
            $command = $this->isLocalhost($server)
                ? $gitCommand
                : $this->buildSSHCommand($server, $gitCommand);

            $result = Process::timeout(15)->run($command);

            if (! $result->successful()) {
                return null;
            }

            [$hash, $author, $timestamp, $message] = explode('|', trim($result->output()), 4);

            return [
                'hash' => $hash,
                'short_hash' => substr($hash, 0, 7),
                'author' => $author,
                'timestamp' => (int) $timestamp,
                'date' => date('Y-m-d H:i:s', $timestamp),
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if project is up-to-date with remote
     */
    public function checkForUpdates(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Check if repository exists
            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return [
                    'success' => true,
                    'up_to_date' => true, // Assume up to date if not deployed (nothing to update)
                    'local_commit' => null,
                    'remote_commit' => null,
                    'commits_behind' => 0,
                    'local_meta' => null,
                    'remote_meta' => null,
                ];
            }

            // Configure safe directory first
            $safeConfigCommand = "git config --global --add safe.directory {$projectPath} 2>&1 || true";
            $command = $this->isLocalhost($server)
                ? $safeConfigCommand
                : $this->buildSSHCommand($server, $safeConfigCommand);
            Process::run($command);

            // Fetch latest from remote with timeout
            $fetchCommand = "cd {$projectPath} && timeout 30 git fetch origin {$project->branch} 2>&1 || echo 'fetch-failed'";
            $command = $this->isLocalhost($server)
                ? $fetchCommand
                : $this->buildSSHCommand($server, $fetchCommand);

            $fetchResult = Process::timeout(35)->run($command);

            // Log if fetch failed but continue to show current status
            if (str_contains($fetchResult->output(), 'fetch-failed') || ! $fetchResult->successful()) {
                \Log::warning("Git fetch failed for {$project->slug}: ".$fetchResult->errorOutput());
            }

            // Get current local commit
            $localCommand = "cd {$projectPath} && timeout 10 git rev-parse HEAD 2>&1";
            $command = $this->isLocalhost($server)
                ? $localCommand
                : $this->buildSSHCommand($server, $localCommand);

            $localResult = Process::timeout(15)->run($command);

            if (! $localResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get local commit: '.$localResult->errorOutput(),
                ];
            }

            $localCommit = trim($localResult->output());

            // Get latest remote commit
            $remoteGitCommand = "cd {$projectPath} && timeout 10 git rev-parse origin/{$project->branch} 2>&1";
            $command = $this->isLocalhost($server)
                ? $remoteGitCommand
                : $this->buildSSHCommand($server, $remoteGitCommand);

            $remoteResult = Process::timeout(15)->run($command);

            if (! $remoteResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get remote commit: '.$remoteResult->errorOutput(),
                ];
            }

            $remoteCommit = trim($remoteResult->output());

            // Count commits behind
            $behindGitCommand = "cd {$projectPath} && timeout 10 git rev-list --count HEAD..origin/{$project->branch} 2>&1";
            $command = $this->isLocalhost($server)
                ? $behindGitCommand
                : $this->buildSSHCommand($server, $behindGitCommand);

            $behindResult = Process::timeout(15)->run($command);
            $commitsBehind = $behindResult->successful() ? (int) trim($behindResult->output()) : 0;

            // Gather additional metadata for richer UI
            $localMetaCommand = "cd {$projectPath} && timeout 10 git show -s --format='%H|%an|%at|%s' {$localCommit}";
            $command = $this->isLocalhost($server)
                ? $localMetaCommand
                : $this->buildSSHCommand($server, $localMetaCommand);
            $localMetaResult = Process::timeout(15)->run($command);

            $remoteMetaCommand = "cd {$projectPath} && timeout 10 git show -s --format='%H|%an|%at|%s' {$remoteCommit}";
            $command = $this->isLocalhost($server)
                ? $remoteMetaCommand
                : $this->buildSSHCommand($server, $remoteMetaCommand);
            $remoteMetaResult = Process::timeout(15)->run($command);

            $localMeta = null;
            if ($localMetaResult->successful()) {
                [$hash, $author, $timestamp, $message] = explode('|', trim($localMetaResult->output()), 4);
                $localMeta = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', (int) $timestamp),
                    'message' => $message,
                ];
            }

            $remoteMeta = null;
            if ($remoteMetaResult->successful()) {
                [$hash, $author, $timestamp, $message] = explode('|', trim($remoteMetaResult->output()), 4);
                $remoteMeta = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', (int) $timestamp),
                    'message' => $message,
                ];
            }

            $isUpToDate = $localCommit === $remoteCommit;

            return [
                'success' => true,
                'up_to_date' => $isUpToDate,
                'local_commit' => substr($localCommit, 0, 7),
                'remote_commit' => substr($remoteCommit, 0, 7),
                'commits_behind' => $commitsBehind,
                'local_meta' => $localMeta,
                'remote_meta' => $remoteMeta,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update project's current commit information
     */
    public function updateProjectCommitInfo(Project $project): bool
    {
        $commitInfo = $this->getCurrentCommit($project);

        if (! $commitInfo) {
            return false;
        }

        $project->update([
            'current_commit_hash' => $commitInfo['hash'],
            'current_commit_message' => $commitInfo['message'],
            'last_commit_at' => now()->setTimestamp($commitInfo['timestamp']),
        ]);

        return true;
    }

    /**
     * Get commit difference between two commits
     */
    public function getCommitDiff(Project $project, string $fromCommit, string $toCommit): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet.',
                ];
            }

            $gitCommand = "cd {$projectPath} && git log {$fromCommit}..{$toCommit} --pretty=format:'%H|%an|%at|%s'";
            $command = $this->isLocalhost($server)
                ? $gitCommand
                : $this->buildSSHCommand($server, $gitCommand);

            $result = Process::timeout(15)->run($command);

            if (! $result->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get commit diff: '.$result->errorOutput(),
                ];
            }

            $commits = [];
            $lines = explode("\n", trim($result->output()));

            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }

                [$hash, $author, $timestamp, $message] = explode('|', $line, 4);

                $commits[] = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'message' => $message,
                ];
            }

            return [
                'success' => true,
                'commits' => $commits,
                'count' => count($commits),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all available branches from a local repository (for DevFlow self-management)
     */
    public function getLocalBranches(string $projectPath = null): array
    {
        try {
            $projectPath = $projectPath ?? base_path();

            // Check if repository exists
            if (! is_dir("{$projectPath}/.git")) {
                return [
                    'success' => false,
                    'error' => 'Not a Git repository.',
                ];
            }

            // Fetch all branches from remote
            $fetchResult = Process::timeout(30)->run("cd {$projectPath} && git fetch --all --prune 2>&1");

            // Get all remote branches
            $result = Process::timeout(15)->run("cd {$projectPath} && git branch -r --format='%(refname:short)|%(committerdate:relative)|%(committername)' 2>&1");

            if (! $result->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch branches: '.$result->errorOutput(),
                ];
            }

            // Get current branch
            $currentBranchResult = Process::timeout(10)->run("cd {$projectPath} && git branch --show-current 2>&1");
            $currentBranch = $currentBranchResult->successful() ? trim($currentBranchResult->output()) : 'main';

            $branches = [];
            $lines = array_filter(explode("\n", trim($result->output())));

            foreach ($lines as $line) {
                if (str_contains($line, 'HEAD ->')) {
                    continue;
                }

                $parts = explode('|', $line, 3);
                $branchName = str_replace('origin/', '', trim($parts[0] ?? ''));

                if (empty($branchName)) {
                    continue;
                }

                $branches[] = [
                    'name' => $branchName,
                    'full_name' => trim($parts[0] ?? ''),
                    'last_commit_date' => trim($parts[1] ?? 'unknown'),
                    'last_committer' => trim($parts[2] ?? 'unknown'),
                    'is_current' => $branchName === $currentBranch,
                    'is_main' => in_array($branchName, ['main', 'master', 'production']),
                ];
            }

            // Sort branches: current first, then main branches, then alphabetically
            usort($branches, function ($a, $b) {
                if ($a['is_current']) {
                    return -1;
                }
                if ($b['is_current']) {
                    return 1;
                }
                if ($a['is_main'] && ! $b['is_main']) {
                    return -1;
                }
                if ($b['is_main'] && ! $a['is_main']) {
                    return 1;
                }

                return strcmp($a['name'], $b['name']);
            });

            return [
                'success' => true,
                'branches' => $branches,
                'current_branch' => $currentBranch,
                'total' => count($branches),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Switch to a different branch in local repository (for DevFlow self-management)
     */
    public function switchLocalBranch(string $branchName, string $projectPath = null): array
    {
        try {
            $projectPath = $projectPath ?? base_path();

            // Check if repository exists
            if (! is_dir("{$projectPath}/.git")) {
                return [
                    'success' => false,
                    'error' => 'Not a Git repository.',
                ];
            }

            // Fetch the branch
            $fetchResult = Process::timeout(30)->run("cd {$projectPath} && git fetch origin {$branchName} 2>&1");

            if (! $fetchResult->successful()) {
                \Log::warning("Git fetch failed for branch {$branchName}: ".$fetchResult->errorOutput());
            }

            // Check if there are local changes
            $statusResult = Process::timeout(10)->run("cd {$projectPath} && git status --porcelain 2>&1");
            $hasChanges = ! empty(trim($statusResult->output()));

            if ($hasChanges) {
                // Stash local changes
                Process::timeout(15)->run("cd {$projectPath} && git stash save 'DevFlow auto-stash before branch switch' 2>&1");
            }

            // Switch to the branch (checkout and reset to remote)
            $switchResult = Process::timeout(30)->run("cd {$projectPath} && git checkout {$branchName} 2>&1 && git reset --hard origin/{$branchName} 2>&1");

            if (! $switchResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to switch branch: '.$switchResult->errorOutput(),
                ];
            }

            return [
                'success' => true,
                'message' => "Successfully switched to branch: {$branchName}",
                'branch' => $branchName,
                'had_local_changes' => $hasChanges,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all available branches from the repository
     */
    public function getBranches(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Check if repository exists
            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet.',
                ];
            }

            // Configure safe directory first
            $safeConfigCommand = "git config --global --add safe.directory {$projectPath} 2>&1 || true";
            $command = $this->isLocalhost($server)
                ? $safeConfigCommand
                : $this->buildSSHCommand($server, $safeConfigCommand);
            Process::run($command);

            // Fetch all branches from remote
            $fetchCommand = "cd {$projectPath} && timeout 30 git fetch --all --prune 2>&1";
            $command = $this->isLocalhost($server)
                ? $fetchCommand
                : $this->buildSSHCommand($server, $fetchCommand);
            Process::timeout(35)->run($command);

            // Get all remote branches
            $branchesCommand = "cd {$projectPath} && timeout 10 git branch -r --format='%(refname:short)|%(committerdate:relative)|%(committername)' 2>&1";
            $command = $this->isLocalhost($server)
                ? $branchesCommand
                : $this->buildSSHCommand($server, $branchesCommand);

            $result = Process::timeout(15)->run($command);

            if (! $result->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch branches: '.$result->errorOutput(),
                ];
            }

            // Get current branch
            $currentBranchCommand = "cd {$projectPath} && git branch --show-current 2>&1";
            $command = $this->isLocalhost($server)
                ? $currentBranchCommand
                : $this->buildSSHCommand($server, $currentBranchCommand);
            $currentBranchResult = Process::timeout(10)->run($command);
            $currentBranch = $currentBranchResult->successful() ? trim($currentBranchResult->output()) : $project->branch;

            $branches = [];
            $lines = array_filter(explode("\n", trim($result->output())));

            foreach ($lines as $line) {
                if (str_contains($line, 'HEAD ->')) {
                    continue;
                }

                $parts = explode('|', $line, 3);
                $branchName = str_replace('origin/', '', trim($parts[0] ?? ''));

                if (empty($branchName)) {
                    continue;
                }

                $branches[] = [
                    'name' => $branchName,
                    'full_name' => trim($parts[0] ?? ''),
                    'last_commit_date' => trim($parts[1] ?? 'unknown'),
                    'last_committer' => trim($parts[2] ?? 'unknown'),
                    'is_current' => $branchName === $currentBranch,
                    'is_main' => in_array($branchName, ['main', 'master', 'production']),
                ];
            }

            // Sort branches: current first, then main branches, then alphabetically
            usort($branches, function ($a, $b) {
                if ($a['is_current']) {
                    return -1;
                }
                if ($b['is_current']) {
                    return 1;
                }
                if ($a['is_main'] && ! $b['is_main']) {
                    return -1;
                }
                if ($b['is_main'] && ! $a['is_main']) {
                    return 1;
                }

                return strcmp($a['name'], $b['name']);
            });

            return [
                'success' => true,
                'branches' => $branches,
                'current_branch' => $currentBranch,
                'total' => count($branches),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Switch to a different branch
     */
    public function switchBranch(Project $project, string $branchName): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Check if repository exists
            if (! $this->isRepositoryCloned($projectPath, $server)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet.',
                ];
            }

            // Configure safe directory first
            $safeConfigCommand = "git config --global --add safe.directory {$projectPath} 2>&1 || true";
            $command = $this->isLocalhost($server)
                ? $safeConfigCommand
                : $this->buildSSHCommand($server, $safeConfigCommand);
            Process::run($command);

            // Fetch the branch
            $fetchCommand = "cd {$projectPath} && timeout 30 git fetch origin {$branchName} 2>&1";
            $command = $this->isLocalhost($server)
                ? $fetchCommand
                : $this->buildSSHCommand($server, $fetchCommand);

            $fetchResult = Process::timeout(35)->run($command);

            if (! $fetchResult->successful()) {
                \Log::warning("Git fetch failed for branch {$branchName}: ".$fetchResult->errorOutput());
            }

            // Check if there are local changes
            $statusCommand = "cd {$projectPath} && git status --porcelain 2>&1";
            $command = $this->isLocalhost($server)
                ? $statusCommand
                : $this->buildSSHCommand($server, $statusCommand);
            $statusResult = Process::timeout(10)->run($command);
            $hasChanges = ! empty(trim($statusResult->output()));

            if ($hasChanges) {
                // Stash local changes
                $stashCommand = "cd {$projectPath} && git stash save 'DevFlow auto-stash before branch switch' 2>&1";
                $command = $this->isLocalhost($server)
                    ? $stashCommand
                    : $this->buildSSHCommand($server, $stashCommand);
                Process::timeout(15)->run($command);
            }

            // Switch to the branch (checkout and reset to remote)
            $switchCommand = "cd {$projectPath} && git checkout {$branchName} 2>&1 && git reset --hard origin/{$branchName} 2>&1";
            $command = $this->isLocalhost($server)
                ? $switchCommand
                : $this->buildSSHCommand($server, $switchCommand);

            $switchResult = Process::timeout(30)->run($command);

            if (! $switchResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to switch branch: '.$switchResult->errorOutput(),
                ];
            }

            // Update project branch in database
            $project->update(['branch' => $branchName]);

            // Get the new commit info
            $commitInfo = $this->getCurrentCommit($project);

            return [
                'success' => true,
                'message' => "Successfully switched to branch: {$branchName}",
                'branch' => $branchName,
                'commit_info' => $commitInfo,
                'had_local_changes' => $hasChanges,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if repository is cloned at given path
     */
    protected function isRepositoryCloned(string $projectPath, ?Server $server = null): bool
    {
        if (! $server) {
            // Fallback to local check
            return is_dir("{$projectPath}/.git");
        }

        // Check via SSH if remote
        if (! $this->isLocalhost($server)) {
            $checkCommand = "test -d {$projectPath}/.git && echo 'exists' || echo 'not-exists'";
            $command = $this->buildSSHCommand($server, $checkCommand);
            $result = Process::run($command);

            return trim($result->output()) === 'exists';
        }

        // Local check
        return is_dir("{$projectPath}/.git");
    }
}
