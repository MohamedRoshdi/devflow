<?php

namespace App\Livewire\Servers;

use Livewire\Component;
use App\Models\Server;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class SSHTerminal extends Component
{
    public Server $server;
    public string $command = '';
    public array $history = [];
    public int $historyIndex = -1;
    public bool $isExecuting = false;

    protected $listeners = ['executeCommand'];

    public function mount(Server $server)
    {
        $this->server = $server;

        // Load command history from session
        $this->history = session('ssh_history_' . $server->id, []);
    }

    public function executeCommand()
    {
        if (empty(trim($this->command))) {
            return;
        }

        $this->isExecuting = true;

        try {
            $commandToExecute = trim($this->command);

            // Build SSH command
            $sshCommand = $this->buildSSHCommand($commandToExecute);

            // Execute command
            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Combine output and error
            $fullOutput = '';
            if (!empty($output)) {
                $fullOutput .= $output;
            }
            if (!empty($error)) {
                $fullOutput .= "\n" . $error;
            }

            // Add to history
            $historyItem = [
                'command' => $commandToExecute,
                'output' => $fullOutput ?: '(no output)',
                'exit_code' => $exitCode,
                'success' => $exitCode === 0,
                'timestamp' => now()->toDateTimeString(),
            ];

            array_unshift($this->history, $historyItem);

            // Keep only last 50 commands
            $this->history = array_slice($this->history, 0, 50);

            // Save to session
            session(['ssh_history_' . $this->server->id => $this->history]);

            // Log command execution
            Log::info('SSH command executed', [
                'server_id' => $this->server->id,
                'command' => $commandToExecute,
                'exit_code' => $exitCode,
            ]);

            // Clear command input
            $this->command = '';
            $this->historyIndex = -1;

        } catch (\Exception $e) {
            // Add error to history
            $historyItem = [
                'command' => $this->command,
                'output' => 'Error: ' . $e->getMessage(),
                'exit_code' => 1,
                'success' => false,
                'timestamp' => now()->toDateTimeString(),
            ];

            array_unshift($this->history, $historyItem);
            session(['ssh_history_' . $this->server->id => $this->history]);

            Log::error('SSH command failed', [
                'server_id' => $this->server->id,
                'command' => $this->command,
                'error' => $e->getMessage(),
            ]);
        }

        $this->isExecuting = false;
    }

    public function clearHistory()
    {
        $this->history = [];
        session()->forget('ssh_history_' . $this->server->id);
    }

    public function rerunCommand($index)
    {
        if (isset($this->history[$index])) {
            $this->command = $this->history[$index]['command'];
        }
    }

    protected function buildSSHCommand(string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-p ' . $this->server->port,
        ];

        // Check if password authentication should be used
        if ($this->server->ssh_password) {
            // Use sshpass for password authentication
            $escapedPassword = escapeshellarg($this->server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $this->server->username,
                $this->server->ip_address,
                escapeshellarg($remoteCommand)
            );
        }

        // Use SSH key authentication
        $sshOptions[] = '-o BatchMode=yes';

        if ($this->server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $this->server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i ' . $keyFile;
        }

        return sprintf(
            'ssh %s %s@%s %s 2>&1',
            implode(' ', $sshOptions),
            $this->server->username,
            $this->server->ip_address,
            escapeshellarg($remoteCommand)
        );
    }

    public function getQuickCommands(): array
    {
        return [
            'System Info' => [
                'uname -a' => 'System information',
                'df -h' => 'Disk usage',
                'free -h' => 'Memory usage',
                'uptime' => 'System uptime',
                'whoami' => 'Current user',
            ],
            'Process Management' => [
                'ps aux | head -20' => 'Running processes',
                'top -bn1 | head -20' => 'Top processes',
                'systemctl status docker' => 'Docker service status',
                'systemctl status nginx' => 'Nginx service status',
            ],
            'Docker' => [
                'docker ps' => 'Running containers',
                'docker ps -a' => 'All containers',
                'docker images' => 'Docker images',
                'docker version' => 'Docker version',
            ],
            'Files & Directories' => [
                'ls -la /var/www' => 'List web directory',
                'pwd' => 'Current directory',
                'ls -lah' => 'List files (detailed)',
            ],
            'Logs' => [
                'tail -50 /var/log/nginx/error.log' => 'Nginx error log',
                'journalctl -n 50' => 'System journal',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.servers.ssh-terminal', [
            'quickCommands' => $this->getQuickCommands(),
        ]);
    }
}
