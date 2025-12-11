<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DevFlowSelfManagement extends Component
{
    // System Info
    public array $systemInfo = [];

    // Git Info
    public bool $isGitRepo = false;
    public string $gitBranch = '';
    public string $gitLastCommit = '';
    public string $gitRemoteUrl = '';

    // Git Setup Form
    public bool $showGitSetup = false;
    public string $newRepoUrl = 'https://github.com/your-username/devflow-pro.git';
    public string $newBranch = 'master';
    public string $gitSetupOutput = '';
    public bool $isSettingUpGit = false;

    // Configuration
    public bool $maintenanceMode = false;
    public bool $debugMode = false;
    public string $appEnv = '';
    public string $cacheDriver = '';
    public string $queueDriver = '';
    public string $sessionDriver = '';

    // Database
    public array $databaseInfo = [];
    public array $pendingMigrations = [];

    // Environment Editor
    public bool $showEnvEditor = false;
    public array $envVariables = [];
    public string $newEnvKey = '';
    public string $newEnvValue = '';
    public array $editableEnvKeys = [
        'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
        'DB_HOST', 'DB_PORT', 'DB_DATABASE',
        'CACHE_DRIVER', 'QUEUE_CONNECTION', 'SESSION_DRIVER',
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_FROM_ADDRESS',
        'BROADCAST_DRIVER', 'FILESYSTEM_DISK',
    ];

    // Queue Status
    public array $queueStatus = [];

    // Domain Configuration
    public bool $showDomainEditor = false;
    public string $currentAppUrl = '';
    public string $currentAppDomain = '';
    public array $nginxSites = [];

    // Reverb WebSocket
    public array $reverbStatus = [];
    public bool $reverbRunning = false;
    public string $reverbOutput = '';

    // Supervisor Processes
    public array $supervisorProcesses = [];

    // Scheduler
    public array $schedulerStatus = [];
    public string $lastSchedulerRun = '';

    public function mount(): void
    {
        $this->loadSystemInfo();
        $this->loadGitInfo();
        $this->loadConfiguration();
        $this->loadDatabaseInfo();
        $this->loadEnvVariables();
        $this->loadQueueStatus();
        $this->loadDomainInfo();
        $this->loadReverbStatus();
        $this->loadSupervisorProcesses();
        $this->loadSchedulerStatus();
    }

    private function loadSystemInfo(): void
    {
        $this->systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'document_root' => base_path(),
            'storage_path' => storage_path(),
            'disk_free' => $this->formatBytes(disk_free_space(base_path())),
            'disk_total' => $this->formatBytes(disk_total_space(base_path())),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];
    }

    private function loadGitInfo(): void
    {
        $projectPath = base_path();
        $this->isGitRepo = is_dir($projectPath . '/.git');

        if ($this->isGitRepo) {
            // Get current branch
            $result = Process::run("cd {$projectPath} && git branch --show-current");
            $this->gitBranch = trim($result->output()) ?: 'unknown';

            // Get last commit
            $result = Process::run("cd {$projectPath} && git log -1 --format='%h - %s (%cr)'");
            $this->gitLastCommit = trim($result->output()) ?: 'unknown';

            // Get remote URL
            $result = Process::run("cd {$projectPath} && git remote get-url origin 2>/dev/null");
            $this->gitRemoteUrl = trim($result->output()) ?: 'No remote configured';
        }
    }

    private function loadConfiguration(): void
    {
        $this->maintenanceMode = app()->isDownForMaintenance();
        $this->debugMode = config('app.debug');
        $this->appEnv = config('app.env');
        $this->cacheDriver = config('cache.default');
        $this->queueDriver = config('queue.default');
        $this->sessionDriver = config('session.driver');
    }

    private function loadDatabaseInfo(): void
    {
        try {
            $this->databaseInfo = [
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'tables_count' => count(DB::select('SHOW TABLES')),
            ];

            // Get pending migrations
            $result = Process::run("cd " . base_path() . " && php artisan migrate:status --pending 2>/dev/null");
            $output = trim($result->output());
            if (str_contains($output, 'Pending')) {
                preg_match_all('/\d+_\d+_\d+_\d+_\w+/', $output, $matches);
                $this->pendingMigrations = $matches[0] ?? [];
            }
        } catch (\Exception $e) {
            $this->databaseInfo = ['error' => $e->getMessage()];
        }
    }

    private function loadEnvVariables(): void
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $lines = explode("\n", $envContent);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;

                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    // Only load editable keys (non-sensitive)
                    if (in_array($key, $this->editableEnvKeys)) {
                        $this->envVariables[$key] = trim($value, '"\'');
                    }
                }
            }
        }
    }

    private function loadQueueStatus(): void
    {
        try {
            $result = Process::run("supervisorctl status devflow-worker:* 2>/dev/null");
            $output = trim($result->output());

            if (!empty($output)) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (preg_match('/(\S+)\s+(RUNNING|STOPPED|FATAL)\s+(.*)/', $line, $matches)) {
                        $this->queueStatus[] = [
                            'name' => $matches[1],
                            'status' => $matches[2],
                            'info' => $matches[3] ?? '',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Supervisor might not be available
        }
    }

    private function loadDomainInfo(): void
    {
        $this->currentAppUrl = config('app.url', 'Not set');
        $this->currentAppDomain = parse_url($this->currentAppUrl, PHP_URL_HOST) ?? 'localhost';

        // Try to list nginx sites
        try {
            $result = Process::run("ls -la /etc/nginx/sites-enabled/ 2>/dev/null | grep -v '^d' | awk '{print $NF}'");
            $output = trim($result->output());
            if (!empty($output)) {
                $this->nginxSites = array_filter(explode("\n", $output));
            }
        } catch (\Exception $e) {
            // Nginx might not be available
        }
    }

    public function toggleDomainEditor(): void
    {
        $this->showDomainEditor = !$this->showDomainEditor;
    }

    public function updateAppUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            session()->flash('error', 'Please enter a valid URL');
            return;
        }

        try {
            $this->updateEnvVariable('APP_URL', $url);
            $this->currentAppUrl = $url;
            $this->currentAppDomain = parse_url($url, PHP_URL_HOST) ?? 'localhost';
            session()->flash('message', 'APP_URL updated successfully! You may need to update your Nginx configuration.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update APP_URL: ' . $e->getMessage());
        }
    }

    public function toggleEnvEditor(): void
    {
        $this->showEnvEditor = !$this->showEnvEditor;
    }

    public function updateEnvVariable(string $key, string $value): void
    {
        if (!in_array($key, $this->editableEnvKeys)) {
            session()->flash('error', 'This environment variable cannot be edited.');
            return;
        }

        try {
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // Escape special characters in value
            $escapedValue = str_contains($value, ' ') ? "\"{$value}\"" : $value;

            // Replace the value
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$escapedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escapedValue}";
            }

            file_put_contents($envPath, $envContent);
            $this->envVariables[$key] = $value;

            // Clear config cache
            Artisan::call('config:clear');

            session()->flash('message', "Environment variable {$key} updated successfully!");
            Log::info('DevFlow env variable updated', ['key' => $key, 'user_id' => auth()->id()]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function toggleMaintenanceMode(): void
    {
        if ($this->maintenanceMode) {
            Artisan::call('up');
            $this->maintenanceMode = false;
            session()->flash('message', 'Maintenance mode disabled. Site is now live.');
        } else {
            Artisan::call('down', ['--refresh' => 15]);
            $this->maintenanceMode = true;
            session()->flash('message', 'Maintenance mode enabled. Site will show maintenance page.');
        }
    }

    public function runMigrations(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            $this->loadDatabaseInfo();
            session()->flash('message', 'Migrations completed: ' . $output);
        } catch (\Exception $e) {
            session()->flash('error', 'Migration failed: ' . $e->getMessage());
        }
    }

    public function restartQueue(): void
    {
        try {
            Artisan::call('queue:restart');
            session()->flash('message', 'Queue workers will restart on their next cycle.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restart queue: ' . $e->getMessage());
        }
    }

    public function toggleGitSetup(): void
    {
        $this->showGitSetup = !$this->showGitSetup;
        $this->gitSetupOutput = '';
    }

    public function initializeGit(): void
    {
        $this->isSettingUpGit = true;
        $this->gitSetupOutput = '';

        try {
            $projectPath = base_path();

            // Validate URL - support both HTTPS and SSH formats
            if (empty($this->newRepoUrl)) {
                throw new \Exception('Please enter a repository URL');
            }

            // Check for valid git URL formats:
            // - HTTPS: https://github.com/user/repo.git
            // - SSH: git@github.com:user/repo.git
            $isHttpsUrl = filter_var($this->newRepoUrl, FILTER_VALIDATE_URL);
            $isSshUrl = preg_match('/^git@[\w.-]+:[\w.\/-]+\.git$/', $this->newRepoUrl);

            if (!$isHttpsUrl && !$isSshUrl) {
                throw new \Exception('Please enter a valid repository URL (HTTPS or SSH format)');
            }

            $this->gitSetupOutput .= "🔧 Initializing Git repository...\n";

            // Initialize git
            $result = Process::run("cd {$projectPath} && git init");
            $this->gitSetupOutput .= $result->output() . "\n";

            // Add safe directory
            $result = Process::run("git config --global --add safe.directory {$projectPath}");

            // Add remote origin
            $this->gitSetupOutput .= "📡 Adding remote origin: {$this->newRepoUrl}\n";
            $result = Process::run("cd {$projectPath} && git remote add origin {$this->newRepoUrl}");
            if (!$result->successful()) {
                // Remote might already exist, try to set URL instead
                Process::run("cd {$projectPath} && git remote set-url origin {$this->newRepoUrl}");
            }

            // Fetch from remote
            $this->gitSetupOutput .= "📥 Fetching from remote...\n";
            $result = Process::timeout(120)->run("cd {$projectPath} && git fetch origin");
            $this->gitSetupOutput .= $result->output() . "\n";

            // Set branch
            $this->gitSetupOutput .= "🌿 Setting up branch: {$this->newBranch}\n";
            Process::run("cd {$projectPath} && git checkout -b {$this->newBranch} 2>/dev/null || git checkout {$this->newBranch}");

            // Set upstream
            Process::run("cd {$projectPath} && git branch --set-upstream-to=origin/{$this->newBranch} {$this->newBranch}");

            $this->gitSetupOutput .= "\n✅ Git repository initialized successfully!\n";
            $this->gitSetupOutput .= "Repository: {$this->newRepoUrl}\n";
            $this->gitSetupOutput .= "Branch: {$this->newBranch}\n";

            // Reload git info
            $this->loadGitInfo();
            $this->showGitSetup = false;

            session()->flash('message', 'Git repository initialized successfully!');

            Log::info('DevFlow Git initialized', [
                'user_id' => auth()->id(),
                'repo_url' => $this->newRepoUrl,
                'branch' => $this->newBranch,
            ]);

        } catch (\Exception $e) {
            $this->gitSetupOutput .= "\n❌ Error: " . $e->getMessage() . "\n";
            session()->flash('error', 'Git initialization failed: ' . $e->getMessage());
        } finally {
            $this->isSettingUpGit = false;
        }
    }

    public function removeGit(): void
    {
        try {
            $projectPath = base_path();

            // Remove .git directory
            Process::run("cd {$projectPath} && rm -rf .git");

            $this->isGitRepo = false;
            $this->gitBranch = '';
            $this->gitLastCommit = '';
            $this->gitRemoteUrl = '';

            session()->flash('message', 'Git repository removed. You can now reinitialize with a different repository.');

            Log::info('DevFlow Git removed', ['user_id' => auth()->id()]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove Git: ' . $e->getMessage());
        }
    }

    // ===== REVERB WEBSOCKET METHODS =====

    private function loadReverbStatus(): void
    {
        try {
            // Check if reverb process is running
            $result = Process::run("pgrep -f 'reverb:start' 2>/dev/null");
            $this->reverbRunning = !empty(trim($result->output()));

            // Get reverb config
            $this->reverbStatus = [
                'enabled' => config('broadcasting.default') === 'reverb',
                'host' => config('reverb.servers.reverb.host', env('REVERB_HOST', 'localhost')),
                'port' => config('reverb.servers.reverb.port', env('REVERB_PORT', 8080)),
                'app_id' => env('REVERB_APP_ID', 'Not configured'),
                'running' => $this->reverbRunning,
            ];
        } catch (\Exception $e) {
            $this->reverbStatus = ['error' => $e->getMessage()];
        }
    }

    public function startReverb(): void
    {
        try {
            // Start reverb in background
            Process::timeout(5)->run("cd " . base_path() . " && nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &");
            sleep(2);
            $this->loadReverbStatus();
            $this->reverbOutput = 'Reverb WebSocket server started successfully';
            session()->flash('message', 'Reverb WebSocket server started');
        } catch (\Exception $e) {
            $this->reverbOutput = 'Error: ' . $e->getMessage();
            session()->flash('error', 'Failed to start Reverb: ' . $e->getMessage());
        }
    }

    public function stopReverb(): void
    {
        try {
            Process::run("pkill -f 'reverb:start' 2>/dev/null");
            sleep(1);
            $this->loadReverbStatus();
            $this->reverbOutput = 'Reverb WebSocket server stopped';
            session()->flash('message', 'Reverb WebSocket server stopped');
        } catch (\Exception $e) {
            $this->reverbOutput = 'Error: ' . $e->getMessage();
            session()->flash('error', 'Failed to stop Reverb: ' . $e->getMessage());
        }
    }

    public function restartReverb(): void
    {
        $this->stopReverb();
        sleep(1);
        $this->startReverb();
    }

    // ===== SUPERVISOR METHODS =====

    private function loadSupervisorProcesses(): void
    {
        try {
            $result = Process::run("supervisorctl status 2>/dev/null");
            $output = trim($result->output());

            $this->supervisorProcesses = [];
            if (!empty($output) && !str_contains($output, 'error') && !str_contains($output, 'refused')) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|FATAL|STARTING|BACKOFF|STOPPING|EXITED|UNKNOWN)\s*(.*)$/', trim($line), $matches)) {
                        $this->supervisorProcesses[] = [
                            'name' => $matches[1],
                            'status' => $matches[2],
                            'info' => trim($matches[3] ?? ''),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->supervisorProcesses = [];
        }
    }

    public function supervisorAction(string $action, string $process = 'all'): void
    {
        try {
            $validActions = ['start', 'stop', 'restart', 'reread', 'update'];
            if (!in_array($action, $validActions)) {
                throw new \Exception('Invalid action');
            }

            $result = Process::run("supervisorctl {$action} {$process} 2>&1");
            $this->loadSupervisorProcesses();

            session()->flash('message', "Supervisor: {$action} {$process} - " . trim($result->output()));
        } catch (\Exception $e) {
            session()->flash('error', 'Supervisor error: ' . $e->getMessage());
        }
    }

    // ===== SCHEDULER METHODS =====

    private function loadSchedulerStatus(): void
    {
        try {
            // Check crontab for Laravel scheduler
            $result = Process::run("crontab -l 2>/dev/null | grep -c 'schedule:run'");
            $cronConfigured = (int) trim($result->output()) > 0;

            // Get scheduled tasks
            $result = Process::run("cd " . base_path() . " && php artisan schedule:list 2>/dev/null");
            $scheduleList = trim($result->output());

            // Check last run
            $cacheFile = storage_path('framework/schedule-*');
            $lastRun = 'Unknown';
            $files = glob($cacheFile);
            if (!empty($files)) {
                $lastRun = date('Y-m-d H:i:s', filemtime(end($files)));
            }

            $this->schedulerStatus = [
                'cron_configured' => $cronConfigured,
                'tasks' => $scheduleList ? explode("\n", $scheduleList) : [],
            ];
            $this->lastSchedulerRun = $lastRun;
        } catch (\Exception $e) {
            $this->schedulerStatus = ['error' => $e->getMessage()];
        }
    }

    public function runScheduler(): void
    {
        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();
            $this->loadSchedulerStatus();
            session()->flash('message', 'Scheduler executed: ' . ($output ?: 'No tasks due'));
        } catch (\Exception $e) {
            session()->flash('error', 'Scheduler error: ' . $e->getMessage());
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) max((float) $bytes, 0);
        if ($bytes == 0) {
            return '0 B';
        }
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow-self-management');
    }
}
