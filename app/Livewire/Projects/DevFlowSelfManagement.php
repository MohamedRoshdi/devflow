<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

    // Deployment
    public bool $isDeploying = false;
    public string $deploymentOutput = '';
    public string $deploymentStatus = '';
    public array $deploymentSteps = [];
    public int $currentStep = 0;
    public bool $showDeployScript = false;
    public string $deployScript = '';

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

    // Cache
    public array $cacheStats = [];

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

    // Logs
    public string $recentLogs = '';
    public array $logFiles = [];
    public string $selectedLogFile = '';

    // Queue Status
    public array $queueStatus = [];

    // Domain Configuration
    public bool $showDomainEditor = false;
    public string $currentAppUrl = '';
    public string $currentAppDomain = '';
    public array $nginxSites = [];

    public function mount(): void
    {
        $this->loadSystemInfo();
        $this->loadGitInfo();
        $this->loadConfiguration();
        $this->loadDatabaseInfo();
        $this->loadEnvVariables();
        $this->loadQueueStatus();
        $this->loadRecentLogs();
        $this->loadDomainInfo();
        $this->loadDeployScript();
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

    private function loadRecentLogs(): void
    {
        try {
            $logsPath = storage_path('logs');

            // Get all log files
            $this->logFiles = [];
            $files = glob($logsPath . '/laravel*.log');

            if ($files) {
                // Sort by modification time (newest first)
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

                foreach ($files as $file) {
                    $filename = basename($file);
                    $this->logFiles[] = [
                        'name' => $filename,
                        'path' => $file,
                        'size' => $this->formatBytes(filesize($file)),
                        'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    ];
                }
            }

            // Select first log file by default
            if (empty($this->selectedLogFile) && !empty($this->logFiles)) {
                $this->selectedLogFile = $this->logFiles[0]['name'];
            }

            // Load selected log content
            $this->loadLogContent();

        } catch (\Exception $e) {
            $this->recentLogs = 'Unable to load logs: ' . $e->getMessage();
        }
    }

    private function loadLogContent(): void
    {
        if (empty($this->selectedLogFile)) {
            $this->recentLogs = 'No log file selected';
            return;
        }

        $logPath = storage_path('logs/' . $this->selectedLogFile);
        if (file_exists($logPath)) {
            $result = Process::run("tail -100 " . escapeshellarg($logPath));
            $this->recentLogs = $result->output();
        } else {
            $this->recentLogs = 'Log file not found';
        }
    }

    public function selectLogFile(string $filename): void
    {
        $this->selectedLogFile = $filename;
        $this->loadLogContent();
    }

    public function clearLogFile(string $filename): void
    {
        $logPath = storage_path('logs/' . basename($filename));

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            try {
                file_put_contents($logPath, '');
                session()->flash('message', "Log file {$filename} cleared successfully!");
                Log::info('DevFlow log file cleared', ['file' => $filename, 'user_id' => auth()->id()]);
                $this->loadRecentLogs();
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to clear log: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'Invalid log file');
        }
    }

    public function deleteLogFile(string $filename): void
    {
        $logPath = storage_path('logs/' . basename($filename));

        // Don't allow deleting the main laravel.log
        if ($filename === 'laravel.log') {
            session()->flash('error', 'Cannot delete the main log file. Use clear instead.');
            return;
        }

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            try {
                unlink($logPath);
                session()->flash('message', "Log file {$filename} deleted successfully!");
                Log::info('DevFlow log file deleted', ['file' => $filename, 'user_id' => auth()->id()]);

                // Reset selection if deleted file was selected
                if ($this->selectedLogFile === $filename) {
                    $this->selectedLogFile = '';
                }
                $this->loadRecentLogs();
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to delete log: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'Invalid log file');
        }
    }

    public function downloadLogFile(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $logPath = storage_path('logs/' . basename($filename));

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            return response()->streamDownload(function () use ($logPath) {
                echo file_get_contents($logPath);
            }, $filename, [
                'Content-Type' => 'text/plain',
            ]);
        }

        abort(404, 'Log file not found');
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

    public function refreshLogs(): void
    {
        $this->loadRecentLogs();
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

    public function clearCache(string $type = 'all'): void
    {
        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'cache':
                    Artisan::call('cache:clear');
                    break;
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    break;
            }
            session()->flash('message', ucfirst($type) . ' cache cleared successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function rebuildCache(): void
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            session()->flash('message', 'All caches rebuilt successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to rebuild cache: ' . $e->getMessage());
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

    private function loadDeployScript(): void
    {
        $scriptPath = base_path('deploy.sh');
        if (file_exists($scriptPath)) {
            $this->deployScript = file_get_contents($scriptPath);
        } else {
            // Default deployment script
            $this->deployScript = $this->getDefaultDeployScript();
        }
    }

    private function getDefaultDeployScript(): string
    {
        $branch = $this->gitBranch ?: 'master';
        return <<<BASH
#!/bin/bash
# DevFlow Pro Deployment Script
# Generated: $(date)

set -e  # Exit on error

# Configuration
PROJECT_PATH="$(pwd)"
BRANCH="{$branch}"

echo "=========================================="
echo "  DevFlow Pro Deployment"
echo "  Started: \$(date)"
echo "=========================================="

# Step 1: Enable Maintenance Mode
echo ""
echo "[1/9] Enabling maintenance mode..."
php artisan down --refresh=15 || true

# Step 2: Git Pull
echo ""
echo "[2/9] Pulling latest changes from \$BRANCH..."
git fetch origin \$BRANCH
git reset --hard origin/\$BRANCH

# Step 3: Composer Install
echo ""
echo "[3/9] Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Step 4: NPM Install & Build
echo ""
echo "[4/9] Installing Node dependencies..."
npm ci --prefer-offline

echo ""
echo "[5/9] Building frontend assets..."
npm run build

# Step 6: Database Migrations
echo ""
echo "[6/9] Running database migrations..."
php artisan migrate --force

# Step 7: Clear & Rebuild Caches
echo ""
echo "[7/9] Clearing old caches..."
php artisan optimize:clear

echo ""
echo "[8/9] Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 8: Restart Queue Workers
echo ""
echo "[9/9] Restarting queue workers..."
php artisan queue:restart

# Step 9: Disable Maintenance Mode
echo ""
echo "Disabling maintenance mode..."
php artisan up

echo ""
echo "=========================================="
echo "  Deployment Complete!"
echo "  Finished: \$(date)"
echo "=========================================="
BASH;
    }

    public function toggleDeployScript(): void
    {
        $this->showDeployScript = !$this->showDeployScript;
    }

    public function saveDeployScript(): void
    {
        try {
            $scriptPath = base_path('deploy.sh');
            file_put_contents($scriptPath, $this->deployScript);
            chmod($scriptPath, 0755);
            session()->flash('message', 'Deployment script saved successfully!');
            Log::info('DevFlow deployment script updated', ['user_id' => auth()->id()]);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save script: ' . $e->getMessage());
        }
    }

    public function resetDeployScript(): void
    {
        $this->deployScript = $this->getDefaultDeployScript();
        session()->flash('message', 'Deployment script reset to default');
    }

    public function redeploy(): void
    {
        $this->isDeploying = true;
        $this->deploymentOutput = '';
        $this->deploymentStatus = 'running';
        $this->currentStep = 0;

        // Initialize deployment steps
        $this->deploymentSteps = [
            ['name' => 'Maintenance Mode', 'status' => 'pending', 'output' => ''],
            ['name' => 'Git Pull', 'status' => 'pending', 'output' => ''],
            ['name' => 'Composer Install', 'status' => 'pending', 'output' => ''],
            ['name' => 'NPM Install', 'status' => 'pending', 'output' => ''],
            ['name' => 'NPM Build', 'status' => 'pending', 'output' => ''],
            ['name' => 'Database Migrations', 'status' => 'pending', 'output' => ''],
            ['name' => 'Clear Caches', 'status' => 'pending', 'output' => ''],
            ['name' => 'Rebuild Caches', 'status' => 'pending', 'output' => ''],
            ['name' => 'Restart Queue', 'status' => 'pending', 'output' => ''],
            ['name' => 'Go Live', 'status' => 'pending', 'output' => ''],
        ];

        $projectPath = base_path();
        $startTime = microtime(true);

        try {
            // Step 1: Maintenance Mode
            $this->runDeploymentStep(0, function () {
                Artisan::call('down', ['--refresh' => 15]);
                return "Maintenance mode enabled (auto-refresh: 15s)";
            });

            // Step 2: Git Pull
            $this->runDeploymentStep(1, function () use ($projectPath) {
                if (!$this->isGitRepo) {
                    return "Skipped - Not a Git repository";
                }
                $result = Process::timeout(120)->run("cd {$projectPath} && git fetch origin {$this->gitBranch} && git reset --hard origin/{$this->gitBranch}");
                if (!$result->successful()) {
                    throw new \Exception($result->errorOutput());
                }
                return $result->output() ?: "Successfully pulled from origin/{$this->gitBranch}";
            });

            // Step 3: Composer Install
            $this->runDeploymentStep(2, function () use ($projectPath) {
                $result = Process::timeout(300)->run("cd {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>&1");
                if (!$result->successful()) {
                    throw new \Exception($result->errorOutput());
                }
                return "Dependencies installed successfully";
            });

            // Step 4: NPM Install (with cleanup and proper permissions for production)
            $this->runDeploymentStep(3, function () use ($projectPath) {
                // Clean node_modules first to prevent corruption issues
                Process::timeout(60)->run("cd {$projectPath} && rm -rf node_modules package-lock.json 2>&1");

                // Fix ownership to www-data for production
                Process::timeout(30)->run("chown -R www-data:www-data {$projectPath} 2>&1");

                // Run npm as www-data user to avoid permission issues
                $result = Process::timeout(300)->run("cd {$projectPath} && sudo -u www-data npm install 2>&1");
                if (!$result->successful()) {
                    // Fallback to root if sudo fails (local dev)
                    $result = Process::timeout(300)->run("cd {$projectPath} && npm install 2>&1");
                    if (!$result->successful()) {
                        throw new \Exception($result->errorOutput() ?: $result->output());
                    }
                }
                return "Node dependencies installed (clean install)";
            });

            // Step 5: NPM Build
            $this->runDeploymentStep(4, function () use ($projectPath) {
                // Run npm build as www-data user
                $result = Process::timeout(300)->run("cd {$projectPath} && sudo -u www-data npm run build 2>&1");
                if (!$result->successful()) {
                    // Fallback to root if sudo fails (local dev)
                    $result = Process::timeout(300)->run("cd {$projectPath} && npm run build 2>&1");
                    if (!$result->successful()) {
                        throw new \Exception($result->errorOutput() ?: $result->output());
                    }
                }
                return "Frontend assets built successfully";
            });

            // Step 6: Database Migrations
            $this->runDeploymentStep(5, function () {
                Artisan::call('migrate', ['--force' => true]);
                $output = Artisan::output();
                return $output ?: "No pending migrations";
            });

            // Step 7: Clear Caches
            $this->runDeploymentStep(6, function () {
                Artisan::call('optimize:clear');
                return "All caches cleared";
            });

            // Step 8: Rebuild Caches
            $this->runDeploymentStep(7, function () {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
                Artisan::call('event:cache');
                return "Config, route, view, and event caches rebuilt";
            });

            // Step 9: Restart Queue
            $this->runDeploymentStep(8, function () {
                Artisan::call('queue:restart');
                return "Queue workers will restart on next job";
            });

            // Step 10: Go Live
            $this->runDeploymentStep(9, function () {
                Artisan::call('up');
                return "Application is now live!";
            });

            $duration = round(microtime(true) - $startTime, 2);
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

        } catch (\Exception $e) {
            // Mark current step as failed
            if (isset($this->deploymentSteps[$this->currentStep])) {
                $this->deploymentSteps[$this->currentStep]['status'] = 'failed';
                $this->deploymentSteps[$this->currentStep]['output'] = $e->getMessage();
            }

            $this->deploymentOutput .= "\n========================================\n";
            $this->deploymentOutput .= "❌ DEPLOYMENT FAILED\n";
            $this->deploymentOutput .= "Step: " . ($this->deploymentSteps[$this->currentStep]['name'] ?? 'Unknown') . "\n";
            $this->deploymentOutput .= "Error: " . $e->getMessage() . "\n";
            $this->deploymentOutput .= "========================================\n";
            $this->deploymentStatus = 'failed';

            // Try to bring the app back up
            try {
                Artisan::call('up');
                $this->deploymentOutput .= "\n⚠️ Maintenance mode disabled after failure\n";
            } catch (\Exception $upError) {
                $this->deploymentOutput .= "\n⚠️ Warning: Could not disable maintenance mode\n";
            }

            Log::error('DevFlow self-deployment failed', [
                'error' => $e->getMessage(),
                'step' => $this->currentStep,
            ]);
        } finally {
            $this->isDeploying = false;
            $this->loadSystemInfo();
            $this->loadGitInfo();
        }
    }

    private function runDeploymentStep(int $stepIndex, callable $callback): void
    {
        $this->currentStep = $stepIndex;
        $this->deploymentSteps[$stepIndex]['status'] = 'running';

        $stepName = $this->deploymentSteps[$stepIndex]['name'];
        $this->deploymentOutput .= "\n[" . ($stepIndex + 1) . "/10] {$stepName}...\n";

        try {
            $output = $callback();
            $this->deploymentSteps[$stepIndex]['status'] = 'success';
            $this->deploymentSteps[$stepIndex]['output'] = $output;
            $this->deploymentOutput .= "  ✓ {$output}\n";
        } catch (\Exception $e) {
            $this->deploymentSteps[$stepIndex]['status'] = 'failed';
            $this->deploymentSteps[$stepIndex]['output'] = $e->getMessage();
            throw $e;
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    public function render()
    {
        return view('livewire.projects.devflow-self-management');
    }
}
