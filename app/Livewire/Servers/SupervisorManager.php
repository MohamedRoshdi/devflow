<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Livewire\Concerns\WithPasswordConfirmation;
use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SupervisorManager extends Component
{
    use AuthorizesRequests;
    use ExecutesRemoteCommands;
    use WithPasswordConfirmation;

    #[Locked]
    public Server $server;

    /** @var array<int, array{name: string, command: string, numprocs: int, user: string, autostart: string, autorestart: string, logfile: string}> */
    public array $workers = [];

    /** @var array<string, array{name: string, status: string, pid: string, uptime: string}> */
    public array $workerStatuses = [];

    public bool $showCreateModal = false;

    public string $newWorkerName = '';

    public string $newWorkerCommand = '';

    public int $newWorkerNumProcs = 2;

    public string $newWorkerUser = 'deploy';

    /** @var array<int, string> */
    public array $logs = [];

    public string $logsWorkerName = '';

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadWorkerStatus();
        $this->loadWorkers();
    }

    /**
     * Load all supervisor .conf files from /etc/supervisor/conf.d/
     */
    public function loadWorkers(): void
    {
        try {
            $output = $this->getRemoteOutput(
                $this->server,
                "ls /etc/supervisor/conf.d/*.conf 2>/dev/null || echo ''",
                false
            );

            $this->workers = [];

            foreach (array_filter(explode("\n", trim($output))) as $confFile) {
                $confFile = trim($confFile);
                if ($confFile === '') {
                    continue;
                }

                $content = $this->getRemoteOutput(
                    $this->server,
                    "cat {$confFile} 2>/dev/null || echo ''",
                    false
                );

                $worker = $this->parseWorkerConfig($confFile, $content);
                if ($worker !== null) {
                    $this->workers[] = $worker;
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load workers: '.$e->getMessage());
        }
    }

    /**
     * Parse a supervisor .conf file content into a worker array.
     *
     * @return array{name: string, command: string, numprocs: int, user: string, autostart: string, autorestart: string, logfile: string}|null
     */
    private function parseWorkerConfig(string $confFile, string $content): ?array
    {
        if (trim($content) === '') {
            return null;
        }

        $name = '';
        if (preg_match('/^\[program:(.+)\]/m', $content, $m)) {
            $name = trim($m[1]);
        }

        if ($name === '') {
            return null;
        }

        $command = '';
        if (preg_match('/^command\s*=\s*(.+)$/m', $content, $m)) {
            $command = trim($m[1]);
        }

        $numprocs = 1;
        if (preg_match('/^numprocs\s*=\s*(\d+)/m', $content, $m)) {
            $numprocs = (int) $m[1];
        }

        $user = 'www-data';
        if (preg_match('/^user\s*=\s*(.+)$/m', $content, $m)) {
            $user = trim($m[1]);
        }

        $autostart = 'true';
        if (preg_match('/^autostart\s*=\s*(.+)$/m', $content, $m)) {
            $autostart = trim($m[1]);
        }

        $autorestart = 'true';
        if (preg_match('/^autorestart\s*=\s*(.+)$/m', $content, $m)) {
            $autorestart = trim($m[1]);
        }

        $logfile = '';
        if (preg_match('/^stdout_logfile\s*=\s*(.+)$/m', $content, $m)) {
            $logfile = trim($m[1]);
        }

        return [
            'name' => $name,
            'command' => $command,
            'numprocs' => $numprocs,
            'user' => $user,
            'autostart' => $autostart,
            'autorestart' => $autorestart,
            'logfile' => $logfile,
        ];
    }

    /**
     * SSH into the server, run `supervisorctl status`, and parse the output.
     */
    public function loadWorkerStatus(): void
    {
        try {
            $output = $this->getRemoteOutput(
                $this->server,
                'sudo supervisorctl status 2>/dev/null || true',
                false
            );

            $this->workerStatuses = [];

            foreach (explode("\n", trim($output)) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, 'error') || str_contains($line, 'no such process')) {
                    continue;
                }

                // "name  STATUS  pid PID, uptime HH:MM:SS"
                if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF|UNKNOWN)\s*(.*)$/i', $line, $m)) {
                    $workerName = $m[1];
                    // Strip process number suffix for grouping: "name_00" -> "name"
                    $groupName = preg_replace('/_\d+$/', '', $workerName) ?? $workerName;

                    $this->workerStatuses[$groupName] = [
                        'name' => $groupName,
                        'status' => strtoupper($m[2]),
                        'pid' => $m[3],
                        'uptime' => $m[3],
                    ];
                }
            }
        } catch (\Exception $e) {
            // Non-fatal: status display is best-effort
        }
    }

    /**
     * Create a new supervisor worker config and install it on the server.
     */
    public function createWorker(): void
    {
        $this->authorize('update', $this->server);

        $this->validate([
            'newWorkerName' => ['required', 'string', 'regex:/^[a-z0-9\-_]+$/i', 'max:64'],
            'newWorkerCommand' => ['required', 'string', 'max:512'],
            'newWorkerNumProcs' => ['required', 'integer', 'min:1', 'max:10'],
            'newWorkerUser' => ['required', 'string', 'max:64'],
        ]);

        $slug = strtolower(preg_replace('/[^a-z0-9\-_]/i', '-', trim($this->newWorkerName)) ?? $this->newWorkerName);
        $configPath = "/etc/supervisor/conf.d/{$slug}-worker.conf";
        $logFile = "/var/log/supervisor/{$slug}-worker.log";

        $config = <<<EOT
[program:{$slug}-worker]
process_name=%(program_name)s_%(process_num)02d
command={$this->newWorkerCommand}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user={$this->newWorkerUser}
numprocs={$this->newWorkerNumProcs}
redirect_stderr=true
stdout_logfile={$logFile}
stdout_logfile_maxbytes=10MB
stopwaitsecs=3600
EOT;

        try {
            $this->executeRemoteCommandWithInput(
                $this->server,
                "sudo tee {$configPath} > /dev/null",
                $config
            );

            $this->executeRemoteCommand(
                $this->server,
                'sudo supervisorctl reread && sudo supervisorctl update',
                false
            );

            $this->showCreateModal = false;
            $this->newWorkerName = '';
            $this->newWorkerCommand = '';
            $this->newWorkerNumProcs = 2;
            $this->newWorkerUser = 'deploy';

            $this->loadWorkers();
            $this->loadWorkerStatus();

            session()->flash('message', "Worker '{$slug}-worker' created successfully.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create worker: '.$e->getMessage());
        }
    }

    /**
     * Start a supervisor worker group.
     */
    public function startWorker(string $name): void
    {
        $this->authorize('update', $this->server);

        try {
            $this->executeRemoteCommand(
                $this->server,
                "sudo supervisorctl start {$name}:* 2>/dev/null || sudo supervisorctl start {$name} 2>/dev/null || true",
                false
            );
            $this->loadWorkerStatus();
            session()->flash('message', "Worker '{$name}' started.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start worker: '.$e->getMessage());
        }
    }

    /**
     * Stop a supervisor worker group.
     */
    public function stopWorker(string $name): void
    {
        $this->authorize('update', $this->server);

        try {
            $this->executeRemoteCommand(
                $this->server,
                "sudo supervisorctl stop {$name}:* 2>/dev/null || sudo supervisorctl stop {$name} 2>/dev/null || true",
                false
            );
            $this->loadWorkerStatus();
            session()->flash('message', "Worker '{$name}' stopped.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop worker: '.$e->getMessage());
        }
    }

    /**
     * Restart a supervisor worker group.
     */
    public function restartWorker(string $name): void
    {
        $this->authorize('update', $this->server);

        try {
            $this->executeRemoteCommand(
                $this->server,
                "sudo supervisorctl restart {$name}:* 2>/dev/null || sudo supervisorctl restart {$name} 2>/dev/null || true",
                false
            );
            $this->loadWorkerStatus();
            session()->flash('message', "Worker '{$name}' restarted.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restart worker: '.$e->getMessage());
        }
    }

    /**
     * Delete a supervisor worker config and stop its processes.
     */
    public function deleteWorker(string $name): void
    {
        $this->authorize('update', $this->server);

        $configPath = "/etc/supervisor/conf.d/{$name}.conf";

        try {
            // Stop processes first (non-fatal)
            $this->executeRemoteCommand(
                $this->server,
                "sudo supervisorctl stop {$name}:* 2>/dev/null || sudo supervisorctl stop {$name} 2>/dev/null || true",
                false
            );

            // Remove config file
            $this->executeRemoteCommand(
                $this->server,
                "sudo rm -f {$configPath}",
                false
            );

            // Reload supervisor
            $this->executeRemoteCommand(
                $this->server,
                'sudo supervisorctl reread && sudo supervisorctl update',
                false
            );

            $this->loadWorkers();
            $this->loadWorkerStatus();

            session()->flash('message', "Worker '{$name}' deleted.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete worker: '.$e->getMessage());
        }
    }

    /**
     * Fetch the last 50 lines of a worker's log file via SSH.
     */
    public function viewLogs(string $name): void
    {
        $this->authorize('view', $this->server);

        // Find logfile path from workers list
        $logFile = null;
        foreach ($this->workers as $worker) {
            if ($worker['name'] === $name && $worker['logfile'] !== '') {
                $logFile = $worker['logfile'];
                break;
            }
        }

        // Fallback to common paths
        if ($logFile === null) {
            $logFile = "/var/log/supervisor/{$name}.log";
        }

        try {
            $output = $this->getRemoteOutput(
                $this->server,
                "sudo tail -n 50 {$logFile} 2>/dev/null || echo '[Log file not found or empty]'",
                false
            );

            $this->logs = array_values(array_filter(
                explode("\n", $output),
                fn (string $line): bool => $line !== ''
            ));
            $this->logsWorkerName = $name;
        } catch (\Exception $e) {
            $this->logs = ["Error reading logs: {$e->getMessage()}"];
            $this->logsWorkerName = $name;
        }
    }

    /**
     * Reread and update supervisor configuration on the server.
     */
    public function rereadConfig(): void
    {
        $this->authorize('update', $this->server);

        try {
            $output = $this->getRemoteOutput(
                $this->server,
                'sudo supervisorctl reread && sudo supervisorctl update',
                false
            );

            $this->loadWorkers();
            $this->loadWorkerStatus();

            session()->flash('message', 'Supervisor config reloaded. '.(trim($output) ?: 'No changes.'));
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reread config: '.$e->getMessage());
        }
    }

    /**
     * Refresh status without reloading the full page.
     */
    public function refreshStatus(): void
    {
        $this->loadWorkerStatus();
    }

    public function render(): View
    {
        return view('livewire.servers.supervisor-manager');
    }
}
