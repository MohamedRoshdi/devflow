<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\Process\Process;

class WebTerminal extends Component
{
    public Server $server;

    public bool $isConnected = false;

    public string $currentDirectory = '~';

    /** @var array<int, array<string, mixed>> */
    public array $commandHistory = [];

    public int $historyPointer = -1;

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->commandHistory = session('web_terminal_history_' . $server->id, []);
    }

    #[On('terminal-command')]
    public function executeCommand(string $command): void
    {
        if (empty(trim($command))) {
            $this->dispatch('terminal-output', output: '');

            return;
        }

        $commandToExecute = trim($command);

        // Handle special local commands
        if ($commandToExecute === 'clear') {
            $this->dispatch('terminal-clear');

            return;
        }

        if ($commandToExecute === 'history') {
            $historyOutput = $this->formatHistory();
            $this->dispatch('terminal-output', output: $historyOutput);

            return;
        }

        // Add to history
        $this->addToHistory($commandToExecute);

        try {
            // Build and execute SSH command
            $sshCommand = $this->buildSSHCommand($commandToExecute);

            $process = Process::fromShellCommandline($sshCommand);
            $process->setTimeout(300);
            $process->run();

            $output = $process->getOutput();
            $error = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Combine output
            $fullOutput = '';
            if (! empty($output)) {
                $fullOutput .= $output;
            }
            if (! empty($error)) {
                if (! empty($fullOutput)) {
                    $fullOutput .= "\n";
                }
                $fullOutput .= $error;
            }

            // Handle directory change tracking
            if (str_starts_with($commandToExecute, 'cd ')) {
                $this->updateCurrentDirectory($commandToExecute);
            }

            // Dispatch output to terminal
            $this->dispatch('terminal-output', output: $fullOutput, exitCode: $exitCode);

            // Log execution
            Log::info('WebTerminal command executed', [
                'server_id' => $this->server->id,
                'command' => $commandToExecute,
                'exit_code' => $exitCode,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('terminal-output', output: "\033[31mError: " . $e->getMessage() . "\033[0m", exitCode: 1);

            Log::error('WebTerminal command failed', [
                'server_id' => $this->server->id,
                'command' => $commandToExecute,
                'error' => $e->getMessage(),
            ]);
        }
    }

    #[On('terminal-connect')]
    public function connect(): void
    {
        try {
            // Test connection
            $testCommand = $this->buildSSHCommand('echo "connected" && pwd');
            $process = Process::fromShellCommandline($testCommand);
            $process->setTimeout(30);
            $process->run();

            if ($process->isSuccessful()) {
                $this->isConnected = true;
                $output = trim($process->getOutput());
                $lines = explode("\n", $output);
                if (count($lines) > 1) {
                    $this->currentDirectory = trim($lines[count($lines) - 1]);
                }

                $welcomeMessage = $this->getWelcomeMessage();
                $this->dispatch('terminal-connected', message: $welcomeMessage);
            } else {
                $this->dispatch('terminal-error', message: 'Connection failed: ' . $process->getErrorOutput());
            }
        } catch (\Exception $e) {
            $this->dispatch('terminal-error', message: 'Connection error: ' . $e->getMessage());
        }
    }

    #[On('terminal-disconnect')]
    public function disconnect(): void
    {
        $this->isConnected = false;
        $this->dispatch('terminal-disconnected');
    }

    #[On('get-history-up')]
    public function getHistoryUp(): void
    {
        if ($this->historyPointer < count($this->commandHistory) - 1) {
            $this->historyPointer++;
            $command = $this->commandHistory[$this->historyPointer] ?? '';
            $this->dispatch('set-command', command: $command);
        }
    }

    #[On('get-history-down')]
    public function getHistoryDown(): void
    {
        if ($this->historyPointer > 0) {
            $this->historyPointer--;
            $command = $this->commandHistory[$this->historyPointer] ?? '';
            $this->dispatch('set-command', command: $command);
        } elseif ($this->historyPointer === 0) {
            $this->historyPointer = -1;
            $this->dispatch('set-command', command: '');
        }
    }

    public function clearHistory(): void
    {
        $this->commandHistory = [];
        $this->historyPointer = -1;
        session()->forget('web_terminal_history_' . $this->server->id);
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

        // Add directory context if not home
        if ($this->currentDirectory !== '~' && ! str_starts_with($remoteCommand, 'cd ')) {
            $remoteCommand = "cd {$this->currentDirectory} && {$remoteCommand}";
        }

        if ($this->server->ssh_password) {
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

        $sshOptions[] = '-o BatchMode=yes';

        if ($this->server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            if ($keyFile !== false) {
                file_put_contents($keyFile, $this->server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i ' . $keyFile;
            }
        }

        return sprintf(
            'ssh %s %s@%s %s 2>&1',
            implode(' ', $sshOptions),
            $this->server->username,
            $this->server->ip_address,
            escapeshellarg($remoteCommand)
        );
    }

    protected function addToHistory(string $command): void
    {
        // Don't add duplicates consecutively
        if (! empty($this->commandHistory) && $this->commandHistory[0] === $command) {
            return;
        }

        array_unshift($this->commandHistory, $command);
        $this->commandHistory = array_slice($this->commandHistory, 0, 100);
        $this->historyPointer = -1;

        session(['web_terminal_history_' . $this->server->id => $this->commandHistory]);
    }

    protected function formatHistory(): string
    {
        $output = '';
        foreach (array_reverse($this->commandHistory) as $index => $cmd) {
            $output .= sprintf("  %d  %s\n", $index + 1, $cmd);
        }

        return $output ?: 'No commands in history.';
    }

    protected function updateCurrentDirectory(string $cdCommand): void
    {
        // Parse the cd command to track directory
        $parts = explode(' ', $cdCommand, 2);
        if (isset($parts[1])) {
            $newDir = trim($parts[1]);
            if ($newDir === '~' || $newDir === '') {
                $this->currentDirectory = '~';
            } elseif (str_starts_with($newDir, '/')) {
                $this->currentDirectory = $newDir;
            } elseif ($newDir === '..') {
                $this->currentDirectory = dirname($this->currentDirectory);
            } else {
                $this->currentDirectory = rtrim($this->currentDirectory, '/') . '/' . $newDir;
            }
        }
    }

    protected function getWelcomeMessage(): string
    {
        $serverInfo = "{$this->server->username}@{$this->server->ip_address}";

        return <<<WELCOME
\033[32m╔══════════════════════════════════════════════════════════════╗
║                    DevFlow Pro Terminal                       ║
╚══════════════════════════════════════════════════════════════╝\033[0m

\033[36mConnected to:\033[0m {$serverInfo}
\033[36mServer:\033[0m {$this->server->name}
\033[36mPort:\033[0m {$this->server->port}

\033[33mType 'help' for available commands.\033[0m
\033[33mUse ↑/↓ arrows to navigate command history.\033[0m

WELCOME;
    }

    public function getPrompt(): string
    {
        $user = $this->server->username;
        $host = $this->server->name;
        $dir = $this->currentDirectory;

        return "\033[32m{$user}@{$host}\033[0m:\033[34m{$dir}\033[0m$ ";
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.servers.web-terminal');
    }
}
