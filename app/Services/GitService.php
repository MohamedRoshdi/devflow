<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Process;

class GitService
{
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
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p '.$server->port,
        ];

        if ($server->ssh_key) {
            // Save SSH key to temp file
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
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

            return str_contains($result->output(), 'exists');
        }

        // Local check
        return is_dir("{$projectPath}/.git");
    }
}
