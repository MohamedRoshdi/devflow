<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Process;

class GitService
{
    /**
     * Get the latest commits from a project repository
     */
    public function getLatestCommits(Project $project, int $limit = 10): array
    {
        try {
            $projectPath = "/var/www/{$project->slug}";
            
            // Check if the repository is cloned
            if (!$this->isRepositoryCloned($projectPath)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet. Deploy the project first.',
                ];
            }

            // Configure safe directory to fix ownership issues
            $this->configureSafeDirectory($projectPath);

            // Fetch latest commits from remote
            $fetchCommand = "cd {$projectPath} && git fetch origin {$project->branch} 2>&1";
            $fetchResult = Process::run($fetchCommand);

            if (!$fetchResult->successful()) {
                // Try to fix ownership and retry
                $this->fixRepositoryOwnership($projectPath);
                $fetchResult = Process::run($fetchCommand);
                
                if (!$fetchResult->successful()) {
                    return [
                        'success' => false,
                        'error' => 'Failed to fetch from remote: ' . $fetchResult->errorOutput(),
                    ];
                }
            }

            // Get commit history
            $logCommand = "cd {$projectPath} && git log origin/{$project->branch} --pretty=format:'%H|%an|%ae|%at|%s' -n {$limit}";
            $logResult = Process::run($logCommand);

            if (!$logResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get commit history: ' . $logResult->errorOutput(),
                ];
            }

            $commits = [];
            $lines = explode("\n", trim($logResult->output()));
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                [$hash, $author, $email, $timestamp, $message] = explode('|', $line, 5);
                
                $commits[] = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'email' => $email,
                    'timestamp' => (int)$timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'message' => $message,
                ];
            }

            return [
                'success' => true,
                'commits' => $commits,
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
            $projectPath = "/var/www/{$project->slug}";
            
            if (!$this->isRepositoryCloned($projectPath)) {
                return null;
            }

            $command = "cd {$projectPath} && git log -1 --pretty=format:'%H|%an|%at|%s'";
            $result = Process::run($command);

            if (!$result->successful()) {
                return null;
            }

            [$hash, $author, $timestamp, $message] = explode('|', trim($result->output()), 4);

            return [
                'hash' => $hash,
                'short_hash' => substr($hash, 0, 7),
                'author' => $author,
                'timestamp' => (int)$timestamp,
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
            $projectPath = "/var/www/{$project->slug}";
            
            if (!$this->isRepositoryCloned($projectPath)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet. Deploy the project first.',
                ];
            }

            // Configure safe directory to fix ownership issues
            $this->configureSafeDirectory($projectPath);

            // Fetch latest from remote
            $fetchCommand = "cd {$projectPath} && git fetch origin {$project->branch} 2>&1";
            $fetchResult = Process::run($fetchCommand);

            if (!$fetchResult->successful()) {
                // Try to fix ownership and retry
                $this->fixRepositoryOwnership($projectPath);
                $fetchResult = Process::run($fetchCommand);
                
                if (!$fetchResult->successful()) {
                    return [
                        'success' => false,
                        'error' => 'Failed to fetch from remote: ' . $fetchResult->errorOutput(),
                    ];
                }
            }

            // Get current local commit
            $localCommand = "cd {$projectPath} && git rev-parse HEAD";
            $localResult = Process::run($localCommand);

            if (!$localResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get local commit: ' . $localResult->errorOutput(),
                ];
            }

            $localCommit = trim($localResult->output());

            // Get latest remote commit
            $remoteCommand = "cd {$projectPath} && git rev-parse origin/{$project->branch}";
            $remoteResult = Process::run($remoteCommand);

            if (!$remoteResult->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get remote commit: ' . $remoteResult->errorOutput(),
                ];
            }

            $remoteCommit = trim($remoteResult->output());

            // Count commits behind
            $behindCommand = "cd {$projectPath} && git rev-list --count HEAD..origin/{$project->branch}";
            $behindResult = Process::run($behindCommand);
            $commitsBehind = $behindResult->successful() ? (int)trim($behindResult->output()) : 0;

            $isUpToDate = $localCommit === $remoteCommit;

            return [
                'success' => true,
                'up_to_date' => $isUpToDate,
                'local_commit' => substr($localCommit, 0, 7),
                'remote_commit' => substr($remoteCommit, 0, 7),
                'commits_behind' => $commitsBehind,
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
        
        if (!$commitInfo) {
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
     * Check if repository is cloned
     */
    protected function isRepositoryCloned(string $path): bool
    {
        return file_exists($path . '/.git');
    }

    /**
     * Get commit difference between two commits
     */
    public function getCommitDiff(Project $project, string $fromCommit, string $toCommit): array
    {
        try {
            $projectPath = "/var/www/{$project->slug}";
            
            if (!$this->isRepositoryCloned($projectPath)) {
                return [
                    'success' => false,
                    'error' => 'Repository not cloned yet.',
                ];
            }

            $command = "cd {$projectPath} && git log {$fromCommit}..{$toCommit} --pretty=format:'%H|%an|%at|%s'";
            $result = Process::run($command);

            if (!$result->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get commit diff: ' . $result->errorOutput(),
                ];
            }

            $commits = [];
            $lines = explode("\n", trim($result->output()));
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                [$hash, $author, $timestamp, $message] = explode('|', $line, 4);
                
                $commits[] = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'timestamp' => (int)$timestamp,
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
     * Configure Git safe directory to fix ownership issues
     */
    protected function configureSafeDirectory(string $projectPath): void
    {
        try {
            // Add the project path as a safe directory
            $configCommand = "git config --global --add safe.directory {$projectPath} 2>&1 || true";
            Process::run($configCommand);
        } catch (\Exception $e) {
            // Log but don't fail - this is a best-effort fix
            \Log::debug("Failed to configure safe directory: " . $e->getMessage());
        }
    }

    /**
     * Fix repository ownership issues
     */
    protected function fixRepositoryOwnership(string $projectPath): void
    {
        try {
            // Set ownership to current user (www-data or whatever is running the web server)
            $user = posix_getpwuid(posix_geteuid())['name'] ?? 'www-data';
            $chownCommand = "sudo chown -R {$user}:{$user} {$projectPath} 2>&1 || true";
            Process::run($chownCommand);
            
            // Also configure safe directory again after ownership fix
            $this->configureSafeDirectory($projectPath);
        } catch (\Exception $e) {
            // Log but don't fail
            \Log::debug("Failed to fix repository ownership: " . $e->getMessage());
        }
    }
}

