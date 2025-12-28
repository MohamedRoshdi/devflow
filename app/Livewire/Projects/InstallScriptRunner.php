<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use phpseclib3\Net\SSH2;

class InstallScriptRunner extends Component
{
    public Project $project;

    public bool $showModal = false;

    public bool $hasInstallScript = false;

    public bool $isChecking = true;

    public bool $isRunning = false;

    public string $runOutput = '';

    public string $runStatus = ''; // 'success', 'error', 'running'

    // Script options
    public bool $productionMode = false;

    public string $domain = '';

    public string $email = '';

    public string $dbDriver = 'pgsql';

    public string $dbPassword = '';

    public bool $skipSsl = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->domain = $project->domains()->where('is_primary', true)->first()?->domain ?? '';
        $this->email = config('mail.from.address', 'admin@example.com');
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->reset(['runOutput', 'runStatus', 'isRunning']);
        $this->checkInstallScript();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['runOutput', 'runStatus', 'isRunning']);
    }

    /**
     * Check if install.sh exists in the project directory on the server
     */
    public function checkInstallScript(): void
    {
        $this->isChecking = true;
        $this->hasInstallScript = false;

        try {
            $server = $this->project->server;
            if (! $server) {
                $this->isChecking = false;

                return;
            }

            $projectPath = $this->getProjectPath();
            $checkCommand = "test -f {$projectPath}/install.sh && echo 'EXISTS' || echo 'NOT_FOUND'";

            $output = $this->executeCommand($server, $checkCommand);

            $this->hasInstallScript = str_contains(trim($output ?? ''), 'EXISTS');
        } catch (\Exception $e) {
            Log::error('InstallScriptRunner: Failed to check install.sh', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->isChecking = false;
    }

    /**
     * Run the install.sh script on the server
     */
    public function runInstallScript(): void
    {
        $this->validate([
            'domain' => $this->productionMode ? 'required|string' : 'nullable|string',
            'email' => $this->productionMode ? 'required|email' : 'nullable|email',
            'dbDriver' => 'required|in:pgsql,mysql',
        ]);

        $this->isRunning = true;
        $this->runStatus = 'running';
        $this->runOutput = "Starting install script...\n";

        try {
            $server = $this->project->server;
            if (! $server) {
                throw new \RuntimeException('Server not found');
            }

            $projectPath = $this->getProjectPath();

            // Build the command with options
            $scriptCommand = "cd {$projectPath} && chmod +x install.sh && ./install.sh";

            if ($this->productionMode) {
                $scriptCommand .= ' --production';
                $scriptCommand .= ' --domain '.escapeshellarg($this->domain);
                $scriptCommand .= ' --email '.escapeshellarg($this->email);
            }

            $scriptCommand .= ' --db-driver '.escapeshellarg($this->dbDriver);

            if ($this->dbPassword) {
                $scriptCommand .= ' --db-password '.escapeshellarg($this->dbPassword);
            }

            if ($this->skipSsl) {
                $scriptCommand .= ' --skip-ssl';
            }

            $this->runOutput .= "Command: {$scriptCommand}\n\n";

            // Execute the script (this may take a while)
            $output = $this->executeCommand($server, $scriptCommand, 600); // 10 minute timeout

            $this->runOutput .= $output ?? 'No output received';
            $this->runStatus = 'success';

            $this->dispatch('notify', message: __('install_script.run_success'), type: 'success');

        } catch (\Exception $e) {
            $this->runOutput .= "\n\nError: ".$e->getMessage();
            $this->runStatus = 'error';

            Log::error('InstallScriptRunner: Script execution failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', message: __('install_script.run_failed'), type: 'error');
        }

        $this->isRunning = false;
    }

    /**
     * View the install.sh content
     */
    public function viewScript(): void
    {
        try {
            $server = $this->project->server;
            if (! $server) {
                return;
            }

            $projectPath = $this->getProjectPath();
            $output = $this->executeCommand($server, "cat {$projectPath}/install.sh");

            $this->runOutput = $output ?? 'Could not read script content';
            $this->runStatus = '';
        } catch (\Exception $e) {
            $this->runOutput = 'Error reading script: '.$e->getMessage();
            $this->runStatus = 'error';
        }
    }

    /**
     * Get the project path on the server
     */
    protected function getProjectPath(): string
    {
        $basePath = config('devflow.projects_path', '/var/www');

        return rtrim($basePath, '/').'/'.$this->project->slug;
    }

    /**
     * Execute a command on the server
     */
    protected function executeCommand($server, string $command, int $timeout = 30): ?string
    {
        // Check if localhost - execute locally
        if ($this->isLocalhost($server->ip_address)) {
            return $this->executeLocally($command, $timeout);
        }

        // Check if using password authentication
        if ($server->ssh_password !== null && strlen($server->ssh_password) > 0) {
            return $this->executeWithPhpseclib($server, $command, $timeout);
        }

        // Use system SSH
        return $this->executeWithSystemSsh($server, $command, $timeout);
    }

    /**
     * Check if IP is localhost
     */
    protected function isLocalhost(string $ip): bool
    {
        $localIPs = ['127.0.0.1', '127.0.1.1', '::1', 'localhost'];

        return in_array($ip, $localIPs);
    }

    /**
     * Execute command locally
     */
    protected function executeLocally(string $command, int $timeout): ?string
    {
        $result = \Illuminate\Support\Facades\Process::timeout($timeout)->run($command);

        return $result->output().$result->errorOutput();
    }

    /**
     * Execute command using phpseclib (password auth)
     */
    protected function executeWithPhpseclib($server, string $command, int $timeout): ?string
    {
        $ssh = new SSH2($server->ip_address, $server->port, $timeout);

        if (! $ssh->login($server->username, $server->ssh_password)) {
            throw new \RuntimeException('SSH authentication failed');
        }

        return $ssh->exec($command);
    }

    /**
     * Execute command using system SSH (key auth)
     */
    protected function executeWithSystemSsh($server, string $command, int $timeout): ?string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=10',
            '-o LogLevel=ERROR',
            '-o BatchMode=yes',
            '-p '.$server->port,
        ];

        $sshCommand = sprintf(
            'ssh %s %s@%s %s 2>&1',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($command)
        );

        $result = \Illuminate\Support\Facades\Process::timeout($timeout)->run($sshCommand);

        return $result->output();
    }

    #[Computed]
    public function scriptOptions(): array
    {
        return [
            ['flag' => '--production', 'description' => __('install_script.option_production')],
            ['flag' => '--domain', 'description' => __('install_script.option_domain')],
            ['flag' => '--email', 'description' => __('install_script.option_email')],
            ['flag' => '--db-driver', 'description' => __('install_script.option_db_driver')],
            ['flag' => '--skip-ssl', 'description' => __('install_script.option_skip_ssl')],
        ];
    }

    #[On('refresh-install-script')]
    public function refreshStatus(): void
    {
        $this->checkInstallScript();
    }

    public function render(): View
    {
        return view('livewire.projects.install-script-runner');
    }
}
