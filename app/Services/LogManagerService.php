<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class LogManagerService
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly LogAggregationService $logAggregationService
    ) {}

    /**
     * Get recent errors from project logs
     *
     * @param Project $project
     * @param int $limit Maximum number of error entries to return
     * @return Collection<int, array{source: string, level: string, message: string, logged_at: Carbon, file_path?: string, line_number?: int}>
     */
    public function getRecentErrors(Project $project, int $limit = 50): Collection
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Determine the log path based on framework
            $logPath = match ($project->framework) {
                'Laravel', 'laravel' => "{$projectPath}/storage/logs/laravel.log",
                'Symfony', 'symfony' => "{$projectPath}/var/log/prod.log",
                default => "{$projectPath}/logs/error.log",
            };

            // Fetch log content (last 500 lines to ensure we get enough errors)
            $logContent = $this->logAggregationService->fetchLogFile(
                $server,
                $logPath,
                500
            );

            if (empty($logContent)) {
                return collect();
            }

            // Parse logs based on framework
            $parsedLogs = match ($project->framework) {
                'Laravel', 'laravel' => $this->logAggregationService->parseLaravelLog($logContent),
                'Nginx', 'nginx' => $this->logAggregationService->parseNginxLog($logContent),
                default => $this->parseGenericErrorLog($logContent),
            };

            // Filter for errors and critical issues only
            return collect($parsedLogs)
                ->filter(fn (array $log) => in_array(
                    strtolower($log['level'] ?? 'info'),
                    ['error', 'critical', 'alert', 'emergency']
                ))
                ->sortByDesc('logged_at')
                ->take($limit)
                ->values();
        } catch (\Exception $e) {
            Log::error('Failed to get recent errors for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Rotate logs for a project
     *
     * @param Project $project
     * @return array{rotated: int, archived: string|null, error?: string}
     */
    public function rotateLogs(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";
            $timestamp = now()->format('Y-m-d_His');

            // Determine log paths based on framework
            $logPaths = match ($project->framework) {
                'Laravel', 'laravel' => [
                    "{$projectPath}/storage/logs/laravel.log",
                ],
                'Symfony', 'symfony' => [
                    "{$projectPath}/var/log/prod.log",
                    "{$projectPath}/var/log/dev.log",
                ],
                default => [
                    "{$projectPath}/logs/error.log",
                    "{$projectPath}/logs/access.log",
                ],
            };

            $rotatedCount = 0;
            $archivePath = null;

            foreach ($logPaths as $logPath) {
                // Create archive name
                $archiveName = basename($logPath) . ".{$timestamp}";
                $archiveDir = dirname($logPath) . '/archive';

                // Use sudo for non-root users
                $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

                // Rotate: copy current log to archive, then truncate
                $rotateCommand = <<<BASH
                if [ -f {$logPath} ] && [ -s {$logPath} ]; then
                    {$sudo}mkdir -p {$archiveDir} &&
                    {$sudo}cp {$logPath} {$archiveDir}/{$archiveName} &&
                    {$sudo}gzip -f {$archiveDir}/{$archiveName} &&
                    {$sudo}truncate -s 0 {$logPath} &&
                    echo "rotated"
                else
                    echo "skip"
                fi
                BASH;

                $result = $this->executeServerCommand($server, $rotateCommand);
                $output = trim($result['output'] ?? '');

                if ($output === 'rotated') {
                    $rotatedCount++;
                    $archivePath = "{$archiveDir}/{$archiveName}.gz";
                }
            }

            // Clean up old archives (keep last 30 days)
            $this->cleanupOldArchives($project, 30);

            return [
                'rotated' => $rotatedCount,
                'archived' => $archivePath,
                'timestamp' => $timestamp,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to rotate logs for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'rotated' => 0,
                'archived' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get log file statistics
     *
     * @param Project $project
     * @return array{total_size_bytes: int, total_files: int, files: array<int, array{path: string, size_bytes: int, modified_at: string}>, error_count_by_level: array<string, int>}
     */
    public function getLogStats(Project $project): array
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Determine log directory based on framework
            $logDir = match ($project->framework) {
                'Laravel', 'laravel' => "{$projectPath}/storage/logs",
                'Symfony', 'symfony' => "{$projectPath}/var/log",
                default => "{$projectPath}/logs",
            };

            $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

            // Get log file statistics
            $statsCommand = <<<BASH
            if [ -d {$logDir} ]; then
                {$sudo}find {$logDir} -type f \( -name "*.log" -o -name "*.log.gz" \) -exec stat -c '%n|%s|%Y' {} \; 2>/dev/null || echo ""
            else
                echo ""
            fi
            BASH;

            $result = $this->executeServerCommand($server, $statsCommand);
            $output = trim($result['output'] ?? '');

            $files = [];
            $totalSize = 0;

            if (! empty($output)) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (empty($line)) {
                        continue;
                    }

                    [$path, $size, $mtime] = explode('|', $line);
                    $sizeInt = (int) $size;
                    $totalSize += $sizeInt;

                    $files[] = [
                        'path' => $path,
                        'size_bytes' => $sizeInt,
                        'modified_at' => Carbon::createFromTimestamp((int) $mtime)->toDateTimeString(),
                    ];
                }
            }

            // Get error counts by level (from current log file)
            $errorCounts = $this->getErrorCountsByLevel($project);

            return [
                'total_size_bytes' => $totalSize,
                'total_files' => count($files),
                'files' => $files,
                'error_count_by_level' => $errorCounts,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get log stats for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_size_bytes' => 0,
                'total_files' => 0,
                'files' => [],
                'error_count_by_level' => [],
            ];
        }
    }

    /**
     * Search logs for a specific pattern
     *
     * @param Project $project
     * @param string $pattern Search pattern (supports regex)
     * @param string|null $level Filter by log level (error, warning, info, etc.)
     * @return Collection<int, array{source: string, level: string, message: string, logged_at: Carbon, file_path?: string, line_number?: int}>
     */
    public function searchLogs(Project $project, string $pattern, ?string $level = null): Collection
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            // Determine log path based on framework
            $logPath = match ($project->framework) {
                'Laravel', 'laravel' => "{$projectPath}/storage/logs/laravel.log",
                'Symfony', 'symfony' => "{$projectPath}/var/log/prod.log",
                default => "{$projectPath}/logs/error.log",
            };

            $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

            // Search using grep
            $escapedPattern = escapeshellarg($pattern);
            $searchCommand = "{$sudo}grep -i {$escapedPattern} {$logPath} 2>/dev/null | tail -n 100 || echo ''";

            $result = $this->executeServerCommand($server, $searchCommand);
            $logContent = trim($result['output'] ?? '');

            if (empty($logContent)) {
                return collect();
            }

            // Parse logs
            $parsedLogs = match ($project->framework) {
                'Laravel', 'laravel' => $this->logAggregationService->parseLaravelLog($logContent),
                'Nginx', 'nginx' => $this->logAggregationService->parseNginxLog($logContent),
                default => $this->parseGenericErrorLog($logContent),
            };

            $collection = collect($parsedLogs);

            // Filter by level if specified
            if ($level !== null) {
                $collection = $collection->filter(
                    fn (array $log) => strtolower($log['level'] ?? 'info') === strtolower($level)
                );
            }

            return $collection->sortByDesc('logged_at')->values();
        } catch (\Exception $e) {
            Log::error('Failed to search logs for project', [
                'project_id' => $project->id,
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Download log files as archive
     *
     * @param Project $project
     * @param \DateTimeInterface|null $from Start date for filtering
     * @param \DateTimeInterface|null $to End date for filtering
     * @return string Path to the created archive file
     * @throws \RuntimeException If archive creation fails
     */
    public function exportLogs(Project $project, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): string
    {
        $server = $project->server;
        $projectPath = "/var/www/{$project->slug}";
        $timestamp = now()->format('Y-m-d_His');

        // Determine log directory based on framework
        $logDir = match ($project->framework) {
            'Laravel', 'laravel' => "{$projectPath}/storage/logs",
            'Symfony', 'symfony' => "{$projectPath}/var/log",
            default => "{$projectPath}/logs",
        };

        $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

        // Create temporary archive on server
        $tempArchive = "/tmp/{$project->slug}_logs_{$timestamp}.tar.gz";

        // Build find command with date filters if provided
        $findConditions = '';
        if ($from !== null) {
            $newerDate = $from->format('Y-m-d');
            $findConditions .= " -newermt '{$newerDate}'";
        }
        if ($to !== null) {
            $olderDate = $to->format('Y-m-d');
            $findConditions .= " ! -newermt '{$olderDate}'";
        }

        $archiveCommand = <<<BASH
        if [ -d {$logDir} ]; then
            {$sudo}find {$logDir} -type f \( -name "*.log" -o -name "*.log.gz" \){$findConditions} -print0 2>/dev/null | {$sudo}tar -czf {$tempArchive} --null -T - 2>/dev/null && echo "{$tempArchive}" || echo "error"
        else
            echo "error"
        fi
        BASH;

        $result = $this->executeServerCommand($server, $archiveCommand);
        $output = trim($result['output'] ?? '');

        if ($output === 'error' || empty($output)) {
            throw new \RuntimeException('Failed to create log archive on server');
        }

        // For localhost, return the path directly
        if ($this->isLocalhost($server)) {
            return $tempArchive;
        }

        // For remote servers, we would need to download the file
        // This is a placeholder - actual implementation would use SCP/SFTP
        Log::warning('Remote log export not fully implemented', [
            'project_id' => $project->id,
            'archive_path' => $tempArchive,
        ]);

        return $tempArchive;
    }

    /**
     * Clear all logs for a project (archives them first)
     *
     * @param Project $project
     * @return bool Success status
     */
    public function clearLogs(Project $project): bool
    {
        try {
            // First, rotate/archive current logs
            $rotateResult = $this->rotateLogs($project);

            if (isset($rotateResult['error'])) {
                Log::warning('Log rotation failed during clearLogs', [
                    'project_id' => $project->id,
                    'error' => $rotateResult['error'],
                ]);
                return false;
            }

            // For Laravel projects, use the DockerService method
            if (in_array($project->framework, ['Laravel', 'laravel'])) {
                $result = $this->dockerService->clearLaravelLogs($project);
                return $result['success'] ?? false;
            }

            // For other frameworks, manually clear logs
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            $logDir = match ($project->framework) {
                'Symfony', 'symfony' => "{$projectPath}/var/log",
                default => "{$projectPath}/logs",
            };

            $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

            // Truncate all .log files
            $clearCommand = "{$sudo}find {$logDir} -type f -name '*.log' -exec truncate -s 0 {} \; 2>/dev/null && echo 'success' || echo 'error'";

            $result = $this->executeServerCommand($server, $clearCommand);
            $output = trim($result['output'] ?? '');

            return $output === 'success';
        } catch (\Exception $e) {
            Log::error('Failed to clear logs for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get real-time log stream (last N lines for Livewire)
     *
     * @param Project $project
     * @param int $lines Number of lines to retrieve
     * @return array<string, mixed>
     */
    public function tailLogs(Project $project, int $lines = 100): array
    {
        try {
            // For Laravel projects, use DockerService
            if (in_array($project->framework, ['Laravel', 'laravel'])) {
                return $this->dockerService->getLaravelLogs($project, $lines);
            }

            // For other frameworks, fetch directly
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            $logPath = match ($project->framework) {
                'Symfony', 'symfony' => "{$projectPath}/var/log/prod.log",
                default => "{$projectPath}/logs/error.log",
            };

            $logContent = $this->logAggregationService->fetchLogFile($server, $logPath, $lines);

            if (empty($logContent)) {
                return [
                    'success' => true,
                    'logs' => 'No logs available',
                ];
            }

            return [
                'success' => true,
                'logs' => $logContent,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'logs' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse a Laravel log file
     *
     * @param string $path Path to the log file
     * @return Collection<int, array{source: string, level: string, message: string, logged_at: Carbon, file_path?: string, line_number?: int}>
     * @phpstan-ignore method.unused (Reserved for future direct file parsing)
     */
    private function parseLogFile(string $path): Collection
    {
        if (! file_exists($path)) {
            return collect();
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return collect();
        }

        // Use LogAggregationService to parse
        $parsedLogs = $this->logAggregationService->parseLaravelLog($content);

        return collect($parsedLogs);
    }

    /**
     * Parse generic error log format
     *
     * @param string $content Log file content
     * @return array<int, array{source: string, level: string, message: string, logged_at: Carbon}>
     */
    private function parseGenericErrorLog(string $content): array
    {
        $logs = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Try to parse timestamp from beginning of line
            if (preg_match('/^\[(.*?)\]\s*(.*)$/', $line, $matches)) {
                try {
                    $timestamp = Carbon::parse($matches[1]);
                    $message = $matches[2];
                } catch (\Exception $e) {
                    $timestamp = now();
                    $message = $line;
                }
            } else {
                $timestamp = now();
                $message = $line;
            }

            // Detect level from message content
            $level = 'info';
            if (stripos($message, 'error') !== false) {
                $level = 'error';
            } elseif (stripos($message, 'warning') !== false || stripos($message, 'warn') !== false) {
                $level = 'warning';
            } elseif (stripos($message, 'critical') !== false) {
                $level = 'critical';
            }

            $logs[] = [
                'source' => 'generic',
                'level' => $level,
                'message' => trim($message),
                'logged_at' => $timestamp,
            ];
        }

        return $logs;
    }

    /**
     * Get error counts by level for a project
     *
     * @param Project $project
     * @return array<string, int>
     */
    private function getErrorCountsByLevel(Project $project): array
    {
        try {
            $errors = $this->getRecentErrors($project, 1000);

            $counts = [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
            ];

            foreach ($errors as $error) {
                $level = strtolower($error['level'] ?? 'error');
                if (isset($counts[$level])) {
                    $counts[$level]++;
                }
            }

            return $counts;
        } catch (\Exception $e) {
            return [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
            ];
        }
    }

    /**
     * Clean up old archived logs
     *
     * @param Project $project
     * @param int $retentionDays Number of days to keep archives
     * @return int Number of archives deleted
     */
    private function cleanupOldArchives(Project $project, int $retentionDays = 30): int
    {
        try {
            $server = $project->server;
            $projectPath = "/var/www/{$project->slug}";

            $archiveDir = match ($project->framework) {
                'Laravel', 'laravel' => "{$projectPath}/storage/logs/archive",
                'Symfony', 'symfony' => "{$projectPath}/var/log/archive",
                default => "{$projectPath}/logs/archive",
            };

            $sudo = strtolower($server->username ?? 'root') === 'root' ? '' : 'sudo ';

            // Delete files older than retention period
            $cleanupCommand = <<<BASH
            if [ -d {$archiveDir} ]; then
                {$sudo}find {$archiveDir} -type f -mtime +{$retentionDays} -delete 2>/dev/null && {$sudo}find {$archiveDir} -type f -mtime +{$retentionDays} | wc -l || echo "0"
            else
                echo "0"
            fi
            BASH;

            $result = $this->executeServerCommand($server, $cleanupCommand);
            $output = trim($result['output'] ?? '0');

            return (int) $output;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old archives', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Execute a command on the server
     *
     * @param \App\Models\Server $server
     * @param string $command
     * @return array{success: bool, output: string, error?: string}
     */
    private function executeServerCommand($server, string $command): array
    {
        try {
            if ($this->isLocalhost($server)) {
                $result = Process::timeout(300)->run($command); // 5 minutes

                return [
                    'success' => $result->successful(),
                    'output' => $result->output(),
                    'error' => $result->errorOutput(),
                ];
            }

            // For remote servers, use SSH
            $sshCommand = $this->buildSSHCommand($server, $command);
            $result = Process::timeout(300)->run($sshCommand);

            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if server is localhost
     *
     * @param \App\Models\Server $server
     * @return bool
     */
    private function isLocalhost($server): bool
    {
        $localIPs = ['127.0.0.1', '::1', 'localhost'];
        return in_array($server->ip_address, $localIPs, true);
    }

    /**
     * Build SSH command for remote execution
     *
     * @param \App\Models\Server $server
     * @param string $remoteCommand
     * @return string
     */
    private function buildSSHCommand($server, string $remoteCommand): string
    {
        $port = $server->port ?? 22;
        $username = $server->username ?? 'root';

        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p ' . $port,
        ];

        if ($server->ssh_key) {
            // Create temporary SSH key file
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            if ($keyFile !== false) {
                chmod($keyFile, 0600);
                file_put_contents($keyFile, $server->ssh_key);
                $sshOptions[] = '-i ' . $keyFile;

                // Register cleanup
                register_shutdown_function(static function () use ($keyFile): void {
                    if (file_exists($keyFile)) {
                        @unlink($keyFile);
                    }
                });
            }
        }

        return sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            $username,
            $server->ip_address,
            escapeshellarg($remoteCommand)
        );
    }
}
