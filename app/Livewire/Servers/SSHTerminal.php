<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Symfony\Component\Process\Process;

class SSHTerminal extends Component
{
    public Server $server;

    public string $command = '';

    /** @var array<int, array<string, mixed>> */
    public array $history = [];

    public int $historyIndex = -1;

    public bool $isExecuting = false;

    /** @var array<int, string> */
    protected $listeners = ['executeCommand'];

    public function mount(Server $server)
    {
        $this->server = $server;

        // Load command history from session
        $this->history = session('ssh_history_'.$server->id, []);
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
            if (! empty($output)) {
                $fullOutput .= $output;
            }
            if (! empty($error)) {
                $fullOutput .= "\n".$error;
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
            session(['ssh_history_'.$this->server->id => $this->history]);

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
                'output' => 'Error: '.$e->getMessage(),
                'exit_code' => 1,
                'success' => false,
                'timestamp' => now()->toDateTimeString(),
            ];

            array_unshift($this->history, $historyItem);
            session(['ssh_history_'.$this->server->id => $this->history]);

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
        session()->forget('ssh_history_'.$this->server->id);
    }

    public function rerunCommand(int $index): void
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
            '-p '.$this->server->port,
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
            $sshOptions[] = '-i '.$keyFile;
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
                'cat /etc/os-release' => 'OS details',
                'df -h' => 'Disk usage',
                'free -h' => 'Memory usage',
                'uptime' => 'System uptime',
                'whoami' => 'Current user',
                'id' => 'User ID and groups',
            ],
            'Explore System' => [
                'ls -la ~' => 'List home directory',
                'ls -la /' => 'List root directory',
                'pwd' => 'Current directory',
                'find /var -type d -maxdepth 2 2>/dev/null | head -30' => 'Explore /var directories',
                'which nginx apache2 docker php' => 'Find installed services',
                'sudo find /home -maxdepth 2 -type d 2>/dev/null' => 'Explore home directories',
            ],
            'Process & Services' => [
                'ps aux | head -20' => 'Running processes',
                'systemctl list-units --type=service --state=running | head -30' => 'Running services',
                'systemctl status docker' => 'Docker status',
                'sudo netstat -tulpn | grep LISTEN' => 'Listening ports',
                'sudo ss -tulpn | grep LISTEN' => 'Listening sockets (ss)',
            ],
            'Docker' => [
                'docker --version' => 'Docker version',
                'docker ps' => 'Running containers',
                'docker ps -a' => 'All containers',
                'docker images' => 'Docker images',
                'docker compose version' => 'Docker Compose version',
                'docker system df' => 'Docker disk usage',
            ],
            'Web Services' => [
                'systemctl status nginx 2>/dev/null || echo "Nginx not installed"' => 'Nginx status',
                'systemctl status apache2 2>/dev/null || echo "Apache not installed"' => 'Apache status',
                'ls -la /var/www 2>/dev/null || echo "Directory not found"' => 'Web directory',
                'sudo ls -la /etc/nginx 2>/dev/null || echo "Nginx config not found"' => 'Nginx config',
                'sudo ls -la /var/log 2>/dev/null' => 'Log directory',
            ],
            'Logs' => [
                'journalctl -n 50 --no-pager' => 'System journal (last 50)',
                'sudo tail -50 /var/log/syslog 2>/dev/null || sudo tail -50 /var/log/messages 2>/dev/null || echo "Log not accessible"' => 'System log',
                'sudo dmesg | tail -30' => 'Kernel messages',
                'sudo ls -lah /var/log | head -30' => 'Available log files',
                'sudo journalctl -u docker -n 30 --no-pager 2>/dev/null || echo "Docker logs not available"' => 'Docker service logs',
            ],
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.s-s-h-terminal', [
            'quickCommands' => $this->getQuickCommands(),
        ]);
    }
}
