<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class SystemRedeploy extends Component
{
    public bool $isDeploying = false;
    public string $deploymentOutput = '';
    public string $deploymentStatus = '';

    public function redeploy(): void
    {
        $this->isDeploying = true;
        $this->deploymentOutput = '';
        $this->deploymentStatus = 'running';

        try {
            $projectPath = base_path();
            $output = [];

            // Step 1: Git pull (skip if not a git repository)
            if (is_dir($projectPath . '/.git')) {
                $this->deploymentOutput .= "📥 Pulling latest changes from repository...\n";
                $result = Process::run("cd {$projectPath} && git pull origin master");
                $output[] = $result->output();
                $this->deploymentOutput .= $result->output() . "\n";

                if (!$result->successful()) {
                    $this->deploymentOutput .= "⚠️  Git pull failed, continuing with deployment...\n";
                }
            } else {
                $this->deploymentOutput .= "ℹ️  Not a Git repository, skipping Git pull...\n";
            }

            // Step 2: Install composer dependencies
            $this->deploymentOutput .= "\n📦 Installing composer dependencies...\n";
            $result = Process::timeout(300)->run("cd {$projectPath} && composer install --optimize-autoloader --no-dev --no-interaction");
            $this->deploymentOutput .= "Composer completed\n";

            // Step 3: Install npm dependencies
            $this->deploymentOutput .= "\n📦 Installing npm dependencies...\n";
            $result = Process::timeout(300)->run("cd {$projectPath} && npm install");
            $this->deploymentOutput .= "NPM install completed\n";

            // Step 4: Build assets
            $this->deploymentOutput .= "\n🏗️  Building frontend assets...\n";
            $result = Process::timeout(300)->run("cd {$projectPath} && npm run build");
            $this->deploymentOutput .= "Assets built successfully\n";

            // Step 5: Run migrations
            $this->deploymentOutput .= "\n🗄️  Running database migrations...\n";
            $result = Process::run("cd {$projectPath} && php artisan migrate --force");
            $output[] = $result->output();
            $this->deploymentOutput .= $result->output() . "\n";

            // Step 6: Clear and rebuild caches
            $this->deploymentOutput .= "\n🧹 Clearing and rebuilding caches...\n";
            Process::run("cd {$projectPath} && php artisan optimize:clear");
            Process::run("cd {$projectPath} && php artisan config:cache");
            Process::run("cd {$projectPath} && php artisan route:cache");
            Process::run("cd {$projectPath} && php artisan view:cache");
            $this->deploymentOutput .= "Caches rebuilt\n";

            // Step 7: Restart queue workers (if any)
            $this->deploymentOutput .= "\n🔄 Restarting queue workers...\n";
            Process::run("cd {$projectPath} && php artisan queue:restart");
            $this->deploymentOutput .= "Queue workers restarted\n";

            $this->deploymentOutput .= "\n✅ Deployment completed successfully!\n";
            $this->deploymentStatus = 'success';

            // Log successful deployment
            Log::info('System redeployed successfully', [
                'user_id' => auth()->id(),
                'output' => implode("\n", $output)
            ]);

            session()->flash('message', 'System redeployed successfully!');

        } catch (\Exception $e) {
            $this->deploymentOutput .= "\n❌ Deployment failed: " . $e->getMessage() . "\n";
            $this->deploymentStatus = 'failed';

            Log::error('System redeploy failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'output' => $this->deploymentOutput
            ]);

            session()->flash('error', 'Deployment failed: ' . $e->getMessage());
        } finally {
            $this->isDeploying = false;
        }
    }

    public function render()
    {
        return view('livewire.settings.system-redeploy');
    }
}
