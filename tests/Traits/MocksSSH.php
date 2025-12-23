<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Process;

trait MocksSSH
{
    /**
     * Mock all external process commands to prevent timeouts.
     * Call this in setUp() for tests that may trigger SSH/external commands.
     */
    protected function mockAllProcesses(): void
    {
        Process::fake([
            // SSH and SCP commands
            '*ssh*' => Process::result(output: 'SSH command executed'),
            '*scp*' => Process::result(output: 'File transferred'),

            // Docker commands
            '*docker*' => Process::result(output: '{"Status": "running"}'),
            '*docker-compose*' => Process::result(output: 'Services started'),

            // Git commands
            '*git*' => Process::result(output: 'abc123 Commit message'),

            // Database backup commands
            '*mysqldump*' => Process::result(output: 'Backup created'),
            '*pg_dump*' => Process::result(output: 'Backup created'),
            '*mysql*' => Process::result(output: 'OK'),

            // System commands
            '*cat*' => Process::result(output: 'file content'),
            '*grep*' => Process::result(output: 'match'),
            '*tail*' => Process::result(output: 'log content'),
            '*timeout*' => Process::result(output: 'command output'),

            // Catch-all for any other commands
            '*' => Process::result(output: 'Command executed'),
        ]);
    }

    /**
     * Mock SSH command execution with successful response.
     */
    protected function mockSshSuccess(string $output = 'Success'): void
    {
        Process::fake([
            '*ssh*' => Process::result(
                output: $output,
                errorOutput: ''
            ),
            '*scp*' => Process::result(
                output: 'File transferred successfully',
                errorOutput: ''
            ),
            '*' => Process::result(output: 'Command executed'),
        ]);
    }

    /**
     * Mock SSH command execution with failure.
     */
    protected function mockSshFailure(string $error = 'Connection failed'): void
    {
        Process::fake([
            '*ssh*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 255
            ),
            '*scp*' => Process::result(
                output: '',
                errorOutput: $error,
                exitCode: 1
            ),
        ]);
    }

    /**
     * Mock server metrics collection.
     */
    protected function mockServerMetrics(array $metrics = []): void
    {
        $defaultMetrics = [
            'cpu' => '25.5',
            'memory' => '45.0 2048 4096',
            'disk' => '60.0 120 200',
            'load' => '1.5 1.2 1.0',
            'network_in' => '1024000',
            'network_out' => '2048000',
        ];

        $metrics = array_merge($defaultMetrics, $metrics);

        Process::fake([
            '*top -bn1*' => Process::result(output: $metrics['cpu']),
            '*free -m*' => Process::result(output: $metrics['memory']),
            '*df -BG*' => Process::result(output: $metrics['disk']),
            '*/proc/loadavg*' => Process::result(output: $metrics['load']),
            '*rx_bytes*' => Process::result(output: $metrics['network_in']),
            '*tx_bytes*' => Process::result(output: $metrics['network_out']),
        ]);
    }

    /**
     * Mock database backup commands.
     */
    protected function mockDatabaseBackup(bool $success = true): void
    {
        if ($success) {
            Process::fake([
                '*mysqldump*' => Process::result(output: 'Backup created'),
                '*pg_dump*' => Process::result(output: 'Backup created'),
                '*mysql -e*' => Process::result(output: 'table_count 10'),
            ]);
        } else {
            Process::fake([
                '*mysqldump*' => Process::result(
                    output: '',
                    errorOutput: 'Backup failed',
                    exitCode: 1
                ),
                '*pg_dump*' => Process::result(
                    output: '',
                    errorOutput: 'Backup failed',
                    exitCode: 1
                ),
            ]);
        }
    }

    /**
     * Mock Docker commands.
     */
    protected function mockDockerCommands(bool $success = true): void
    {
        if ($success) {
            Process::fake([
                '*docker*' => Process::result(output: 'Container started'),
                '*docker-compose*' => Process::result(output: 'Services started'),
            ]);
        } else {
            Process::fake([
                '*docker*' => Process::result(
                    output: '',
                    errorOutput: 'Docker command failed',
                    exitCode: 1
                ),
            ]);
        }
    }

    /**
     * Mock Git commands.
     */
    protected function mockGitCommands(bool $success = true, string $commitHash = 'abc123'): void
    {
        if ($success) {
            Process::fake([
                '*git pull*' => Process::result(output: 'Already up to date.'),
                '*git fetch*' => Process::result(output: 'Fetching origin'),
                '*git reset*' => Process::result(output: 'HEAD is now at '.$commitHash),
                '*git rev-parse*' => Process::result(output: $commitHash),
                '*git log*' => Process::result(output: $commitHash.' Test commit message'),
            ]);
        } else {
            Process::fake([
                '*git*' => Process::result(
                    output: '',
                    errorOutput: 'Git command failed',
                    exitCode: 128
                ),
            ]);
        }
    }
}
