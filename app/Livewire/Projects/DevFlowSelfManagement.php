<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Services\GitService;

class DevFlowSelfManagement extends Component
{
    // Deployment state
    public bool $isDeploying = false;
    public string $deploymentOutput = '';
    public string $deploymentStatus = '';
    public array $deploymentSteps = [];
    public int $currentStep = 0;

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

    // Git Tab State
    public array $commits = [];
    public array $gitStatus = [];
    public ?array $currentCommit = null;
    public int $commitPage = 1;
    public int $commitPerPage = 15;
    public int $commitTotal = 0;
    public bool $gitLoading = false;
    public string $selectedBranch = '';
    public array $branches = [];
    public bool $pullingChanges = false;

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
        $this->selectedBranch = $this->gitBranch;
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
                'host' => config('reverb.servers.reverb.host', config('reverb.host', 'localhost')),
                'port' => config('reverb.servers.reverb.port', config('reverb.port', 8080)),
                'app_id' => config('reverb.apps.0.app_id', 'Not configured'),
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

    // ===== DEPLOYMENT METHODS =====

    public function redeploy(): void
    {
        $this->isDeploying = true;
        $this->deploymentOutput = "Starting deployment...\n";
        $this->deploymentStatus = 'running';
        $this->currentStep = -1;

        $this->deploymentSteps = [
            ['name' => 'Git Pull', 'status' => 'pending', 'output' => ''],
            ['name' => 'Composer Install', 'status' => 'pending', 'output' => ''],
            ['name' => 'NPM Install', 'status' => 'pending', 'output' => ''],
            ['name' => 'NPM Build', 'status' => 'pending', 'output' => ''],
            ['name' => 'Database Migrations', 'status' => 'pending', 'output' => ''],
            ['name' => 'Clear Caches', 'status' => 'pending', 'output' => ''],
            ['name' => 'Rebuild Caches', 'status' => 'pending', 'output' => ''],
            ['name' => 'Restart Queue', 'status' => 'pending', 'output' => ''],
            ['name' => 'Restart PHP-FPM', 'status' => 'pending', 'output' => ''],
        ];

        Cache::put('devflow_deployment_start', microtime(true), 600);
        $this->dispatch('deployment-started');
    }

    public function pollDeploymentStep(): void
    {
        if (!$this->isDeploying || $this->deploymentStatus !== 'running') {
            return;
        }

        $this->currentStep++;
        $projectPath = base_path();

        if ($this->currentStep >= count($this->deploymentSteps)) {
            $this->finishDeployment(true);
            return;
        }

        $this->deploymentSteps[$this->currentStep]['status'] = 'running';
        $stepName = $this->deploymentSteps[$this->currentStep]['name'];
        $totalSteps = count($this->deploymentSteps);
        $this->deploymentOutput .= "\n[" . ($this->currentStep + 1) . "/{$totalSteps}] {$stepName}...\n";

        try {
            $output = match($this->currentStep) {
                0 => $this->stepGitPull($projectPath),
                1 => $this->stepComposerInstall($projectPath),
                2 => $this->stepNpmInstall($projectPath),
                3 => $this->stepNpmBuild($projectPath),
                4 => $this->stepMigrations(),
                5 => $this->stepClearCaches(),
                6 => $this->stepRebuildCaches(),
                7 => $this->stepRestartQueue(),
                8 => $this->stepRestartPhpFpm(),
                default => "Unknown step",
            };

            $this->deploymentSteps[$this->currentStep]['status'] = 'success';
            $this->deploymentSteps[$this->currentStep]['output'] = $output;
            $this->deploymentOutput .= "  ✓ {$output}\n";

            if ($this->currentStep >= count($this->deploymentSteps) - 1) {
                $this->finishDeployment(true);
            }

        } catch (\Exception $e) {
            $this->deploymentSteps[$this->currentStep]['status'] = 'failed';
            $this->deploymentSteps[$this->currentStep]['output'] = $e->getMessage();
            $this->finishDeployment(false, $e->getMessage());
        }
    }

    private function stepGitPull(string $projectPath): string
    {
        if (!$this->isGitRepo) {
            return "Skipped - Not a Git repository";
        }

        $output = "$ chown -R www-data:www-data .git && chmod -R 775 .git\n";
        Process::timeout(30)->run("chown -R www-data:www-data {$projectPath}/.git && chmod -R 775 {$projectPath}/.git");

        $cmd = "git fetch origin {$this->gitBranch} && git reset --hard origin/{$this->gitBranch}";
        $output .= "$ {$cmd}\n";
        $result = Process::timeout(120)->run("cd {$projectPath} && {$cmd}");
        if (!$result->successful()) {
            throw new \Exception($result->errorOutput());
        }
        return $output . ($result->output() ?: "Successfully pulled");
    }

    private function stepComposerInstall(string $projectPath): string
    {
        $cmd = "composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev";
        $output = "$ {$cmd}\n";
        $result = Process::timeout(300)->run("cd {$projectPath} && {$cmd} 2>&1");
        if (!$result->successful()) {
            throw new \Exception($result->errorOutput());
        }
        return $output . "Dependencies installed successfully";
    }

    private function stepNpmInstall(string $projectPath): string
    {
        $output = "$ rm -rf node_modules package-lock.json\n";
        Process::timeout(60)->run("cd {$projectPath} && rm -rf node_modules package-lock.json 2>&1");

        $cmd = "npm install";
        $output .= "$ {$cmd}\n";
        $result = Process::timeout(300)->run("cd {$projectPath} && {$cmd} 2>&1");
        if (!$result->successful()) {
            throw new \Exception($result->errorOutput() ?: $result->output());
        }
        return $output . "Node dependencies installed";
    }

    private function stepNpmBuild(string $projectPath): string
    {
        $nodePath = "{$projectPath}/node_modules/.bin";
        $cmd = "npm run build";
        $output = "$ PATH=\"node_modules/.bin:\$PATH\" {$cmd}\n";
        $result = Process::timeout(300)->run("cd {$projectPath} && PATH=\"{$nodePath}:\$PATH\" {$cmd} 2>&1");
        if (!$result->successful()) {
            $output .= "$ node ./node_modules/.bin/vite build\n";
            $result = Process::timeout(300)->run("cd {$projectPath} && /usr/bin/node ./node_modules/.bin/vite build 2>&1");
            if (!$result->successful()) {
                throw new \Exception($result->errorOutput() ?: $result->output());
            }
        }
        return $output . "Frontend assets built successfully";
    }

    private function stepMigrations(): string
    {
        $output = "$ php artisan migrate --force\n";
        Artisan::call('migrate', ['--force' => true]);
        return $output . (Artisan::output() ?: "No pending migrations");
    }

    private function stepClearCaches(): string
    {
        $projectPath = base_path();
        $output = "$ rm -rf bootstrap/cache/*.php\n";
        Process::timeout(30)->run("rm -rf {$projectPath}/bootstrap/cache/*.php");

        $output .= "$ composer dump-autoload -o\n";
        Process::timeout(60)->run("cd {$projectPath} && composer dump-autoload -o 2>&1");

        $output .= "$ php artisan optimize:clear\n";
        Artisan::call('optimize:clear');

        $output .= "$ php artisan package:discover\n";
        Artisan::call('package:discover');

        return $output . "All caches cleared, packages re-discovered";
    }

    private function stepRebuildCaches(): string
    {
        $output = "$ php artisan config:cache\n";
        Artisan::call('config:cache');
        $output .= "$ php artisan route:cache\n";
        Artisan::call('route:cache');
        $output .= "$ php artisan view:cache\n";
        Artisan::call('view:cache');
        $output .= "$ php artisan event:cache\n";
        Artisan::call('event:cache');
        return $output . "Caches rebuilt successfully";
    }

    private function stepRestartQueue(): string
    {
        $output = "$ php artisan queue:restart\n";
        Artisan::call('queue:restart');
        return $output . "Queue workers will restart on next job";
    }

    private function stepRestartPhpFpm(): string
    {
        $projectPath = base_path();

        $output = "$ chown -R www-data:www-data storage bootstrap/cache public/build\n";
        Process::timeout(60)->run("chown -R www-data:www-data {$projectPath}/storage {$projectPath}/bootstrap/cache {$projectPath}/public/build 2>&1 || true");

        $output .= "$ systemctl restart php8.2-fpm\n";
        Process::timeout(30)->run("systemctl restart php8.2-fpm 2>&1 || service php8.2-fpm restart 2>&1 || true");
        return $output . "PHP-FPM restarted - OPcache cleared, permissions fixed";
    }

    private function finishDeployment(bool $success, string $errorMessage = ''): void
    {
        $startTime = Cache::get('devflow_deployment_start', microtime(true));
        $duration = round(microtime(true) - $startTime, 2);

        if ($success) {
            $this->deploymentOutput .= "\n========================================\n";
            $this->deploymentOutput .= "✅ DEPLOYMENT SUCCESSFUL\n";
            $this->deploymentOutput .= "Duration: {$duration} seconds\n";
            $this->deploymentOutput .= "Completed: " . now()->format('Y-m-d H:i:s') . "\n";
            $this->deploymentOutput .= "========================================\n";
            $this->deploymentStatus = 'success';

            Log::info('DevFlow self-deployment completed', [
                'user_id' => auth()->id(),
                'duration' => $duration,
            ]);
        } else {
            $this->deploymentOutput .= "\n========================================\n";
            $this->deploymentOutput .= "❌ DEPLOYMENT FAILED\n";
            $this->deploymentOutput .= "Step: " . ($this->deploymentSteps[$this->currentStep]['name'] ?? 'Unknown') . "\n";
            $this->deploymentOutput .= "Error: " . $errorMessage . "\n";
            $this->deploymentOutput .= "========================================\n";
            $this->deploymentStatus = 'failed';

            Log::error('DevFlow self-deployment failed', [
                'error' => $errorMessage,
                'step' => $this->currentStep,
            ]);
        }

        $this->isDeploying = false;
        $this->loadGitInfo();
        Cache::forget('devflow_deployment_start');
        $this->dispatch('deployment-completed');
    }

    public function closeDeployment(): void
    {
        $this->isDeploying = false;
        $this->deploymentStatus = '';
        $this->deploymentOutput = '';
        $this->currentStep = -1;
        $this->deploymentSteps = array_map(function ($step) {
            $step['status'] = 'pending';
            return $step;
        }, $this->deploymentSteps);
    }

    // ===== CACHE MANAGEMENT METHODS =====

    public function clearCache(string $type = 'all'): void
    {
        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    session()->flash('message', 'Configuration cache cleared!');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    session()->flash('message', 'Route cache cleared!');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    session()->flash('message', 'View cache cleared!');
                    break;
                case 'event':
                    Artisan::call('event:clear');
                    session()->flash('message', 'Event cache cleared!');
                    break;
                case 'app':
                    Artisan::call('cache:clear');
                    session()->flash('message', 'Application cache cleared!');
                    break;
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    session()->flash('message', 'All caches cleared successfully!');
                    break;
            }

            Log::info('DevFlow cache cleared', [
                'type' => $type,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    // ===== GIT TAB METHODS =====

    public function loadGitTab(): void
    {
        if (!$this->isGitRepo) {
            return;
        }

        $this->gitLoading = true;

        try {
            $this->loadGitCommits();
            $this->loadGitStatusInfo();
            $this->loadBranches();
            $this->loadCurrentCommit();
        } catch (\Exception $e) {
            Log::error('Failed to load Git tab: ' . $e->getMessage());
        } finally {
            $this->gitLoading = false;
        }
    }

    private function loadGitCommits(): void
    {
        $projectPath = base_path();
        $skip = max(0, ($this->commitPage - 1) * $this->commitPerPage);

        // Configure safe directory first
        Process::run("git config --global --add safe.directory {$projectPath} 2>&1 || true");

        // Get total commit count
        $countResult = Process::timeout(15)->run("cd {$projectPath} && git rev-list --count HEAD 2>&1");
        $this->commitTotal = $countResult->successful() ? (int) trim($countResult->output()) : 0;

        // Get commit history
        $logCommand = "cd {$projectPath} && git log --pretty=format:'%H|%an|%ae|%at|%s' --skip={$skip} -n {$this->commitPerPage} 2>&1";
        $logResult = Process::timeout(20)->run($logCommand);

        $this->commits = [];
        if ($logResult->successful()) {
            $lines = explode("\n", trim($logResult->output()));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $parts = explode('|', $line, 5);
                if (count($parts) === 5) {
                    [$hash, $author, $email, $timestamp, $message] = $parts;

                    $this->commits[] = [
                        'hash' => $hash,
                        'short_hash' => substr($hash, 0, 7),
                        'author' => $author,
                        'email' => $email,
                        'timestamp' => (int) $timestamp,
                        'date' => date('Y-m-d H:i:s', $timestamp),
                        'message' => $message,
                    ];
                }
            }
        }
    }

    private function loadGitStatusInfo(): void
    {
        $projectPath = base_path();

        // Get git status
        $statusResult = Process::run("cd {$projectPath} && git status --porcelain 2>&1");

        $this->gitStatus = [
            'clean' => $statusResult->successful() && empty(trim($statusResult->output())),
            'modified' => [],
            'staged' => [],
            'untracked' => [],
        ];

        if ($statusResult->successful() && !empty(trim($statusResult->output()))) {
            $lines = explode("\n", trim($statusResult->output()));

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $status = substr($line, 0, 2);
                $file = trim(substr($line, 3));

                if ($status === '??') {
                    $this->gitStatus['untracked'][] = $file;
                } elseif (trim($status[0]) !== '') {
                    $this->gitStatus['staged'][] = $file;
                } elseif (trim($status[1]) !== '') {
                    $this->gitStatus['modified'][] = $file;
                }
            }
        }
    }

    private function loadBranches(): void
    {
        $projectPath = base_path();

        // Get all branches
        $branchResult = Process::run("cd {$projectPath} && git branch -a 2>&1");

        $this->branches = [];
        if ($branchResult->successful()) {
            $lines = explode("\n", trim($branchResult->output()));

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Remove leading * and spaces
                $branch = preg_replace('/^\*?\s+/', '', $line);

                // Skip remote HEAD references
                if (str_contains($branch, 'remotes/origin/HEAD')) continue;

                // Clean up remote branch names
                $branch = str_replace('remotes/origin/', '', $branch);

                if (!in_array($branch, $this->branches)) {
                    $this->branches[] = $branch;
                }
            }
        }
    }

    private function loadCurrentCommit(): void
    {
        $projectPath = base_path();

        $result = Process::run("cd {$projectPath} && git log -1 --pretty=format:'%H|%an|%ae|%at|%s' 2>&1");

        if ($result->successful() && !empty(trim($result->output()))) {
            $parts = explode('|', trim($result->output()), 5);
            if (count($parts) === 5) {
                [$hash, $author, $email, $timestamp, $message] = $parts;

                $this->currentCommit = [
                    'hash' => $hash,
                    'short_hash' => substr($hash, 0, 7),
                    'author' => $author,
                    'email' => $email,
                    'timestamp' => (int) $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'message' => $message,
                ];
            }
        }
    }

    public function refreshGitTab(): void
    {
        $this->loadGitInfo();
        $this->loadGitTab();
        session()->flash('message', 'Git information refreshed successfully!');
    }

    public function pullLatestChanges(): void
    {
        if (!$this->isGitRepo) {
            session()->flash('error', 'Not a Git repository');
            return;
        }

        $this->pullingChanges = true;

        try {
            $projectPath = base_path();

            // Fetch and pull
            $fetchResult = Process::timeout(60)->run("cd {$projectPath} && git fetch origin {$this->gitBranch} 2>&1");

            if (!$fetchResult->successful()) {
                throw new \Exception('Failed to fetch: ' . $fetchResult->errorOutput());
            }

            $pullResult = Process::timeout(60)->run("cd {$projectPath} && git pull origin {$this->gitBranch} 2>&1");

            if (!$pullResult->successful()) {
                throw new \Exception('Failed to pull: ' . $pullResult->errorOutput());
            }

            $this->loadGitInfo();
            $this->loadGitTab();

            session()->flash('message', 'Successfully pulled latest changes from ' . $this->gitBranch);

            Log::info('DevFlow Git pull completed', [
                'user_id' => auth()->id(),
                'branch' => $this->gitBranch,
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to pull changes: ' . $e->getMessage());
        } finally {
            $this->pullingChanges = false;
        }
    }

    public function switchBranch(string $branch): void
    {
        if (!$this->isGitRepo || empty($branch)) {
            session()->flash('error', 'Invalid branch selection');
            return;
        }

        try {
            $projectPath = base_path();

            // Checkout branch
            $checkoutResult = Process::timeout(30)->run("cd {$projectPath} && git checkout {$branch} 2>&1");

            if (!$checkoutResult->successful()) {
                throw new \Exception('Failed to switch branch: ' . $checkoutResult->errorOutput());
            }

            $this->selectedBranch = $branch;
            $this->gitBranch = $branch;
            $this->loadGitInfo();
            $this->loadGitTab();

            session()->flash('message', "Successfully switched to branch: {$branch}");

            Log::info('DevFlow Git branch switched', [
                'user_id' => auth()->id(),
                'branch' => $branch,
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to switch branch: ' . $e->getMessage());
        }
    }

    public function previousCommitPage(): void
    {
        if ($this->commitPage > 1) {
            $this->commitPage--;
            $this->loadGitCommits();
        }
    }

    public function nextCommitPage(): void
    {
        $maxPages = max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
        if ($this->commitPage < $maxPages) {
            $this->commitPage++;
            $this->loadGitCommits();
        }
    }

    public function getCommitPagesProperty(): int
    {
        return max(1, (int) ceil($this->commitTotal / $this->commitPerPage));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow-self-management');
    }
}
