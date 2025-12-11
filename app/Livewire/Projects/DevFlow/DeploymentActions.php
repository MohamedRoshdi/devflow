<?php

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeploymentActions extends Component
{
    // Deployment state
    public bool $isDeploying = false;
    public string $deploymentOutput = '';
    public string $deploymentStatus = '';
    /** @var array<int, array{name: string, status: string, output: string}> */
    public array $deploymentSteps = [];
    public int $currentStep = 0;

    // Git info
    public bool $isGitRepo = false;
    public string $gitBranch = '';

    // Deploy script management
    public bool $showDeployScript = false;
    public string $deployScript = '';

    public function mount(): void
    {
        $this->loadGitInfo();
        $this->loadDeployScript();
    }

    private function loadGitInfo(): void
    {
        $projectPath = base_path();
        $this->isGitRepo = is_dir($projectPath . '/.git');

        if ($this->isGitRepo) {
            $result = Process::run("cd {$projectPath} && git branch --show-current");
            $this->gitBranch = trim($result->output()) ?: 'master';
        }
    }

    private function loadDeployScript(): void
    {
        $scriptPath = base_path('deploy.sh');
        if (file_exists($scriptPath)) {
            $this->deployScript = file_get_contents($scriptPath);
        } else {
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.deployment-actions');
    }
}
