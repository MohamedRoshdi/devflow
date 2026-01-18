<?php

declare(strict_types=1);

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class SystemInfo extends Component
{
    // System Info
    /** @var array<string, string> */
    public array $systemInfo = [];

    // Configuration
    public bool $maintenanceMode = false;
    public bool $debugMode = false;
    public string $appEnv = '';
    public string $cacheDriver = '';
    public string $queueDriver = '';
    public string $sessionDriver = '';

    // Database
    /** @var array<string, mixed> */
    public array $databaseInfo = [];
    /** @var array<int, string> */
    public array $pendingMigrations = [];

    // Environment Editor
    public bool $showEnvEditor = false;
    /** @var array<string, string> */
    public array $envVariables = [];
    public string $newEnvKey = '';
    public string $newEnvValue = '';
    /** @var array<int, string> */
    public array $editableEnvKeys = [
        'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
        'DB_HOST', 'DB_PORT', 'DB_DATABASE',
        'CACHE_DRIVER', 'QUEUE_CONNECTION', 'SESSION_DRIVER',
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_FROM_ADDRESS',
        'BROADCAST_DRIVER', 'FILESYSTEM_DISK',
    ];

    // Domain Configuration
    public bool $showDomainEditor = false;
    public string $currentAppUrl = '';
    public string $currentAppDomain = '';
    /** @var array<int, string> */
    public array $nginxSites = [];

    public function mount(): void
    {
        $this->loadSystemInfo();
        $this->loadConfiguration();
        $this->loadDatabaseInfo();
        $this->loadEnvVariables();
        $this->loadDomainInfo();
    }

    private function loadSystemInfo(): void
    {
        $diskFree = disk_free_space(base_path());
        $diskTotal = disk_total_space(base_path());

        $this->systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'document_root' => base_path(),
            'storage_path' => storage_path(),
            'disk_free' => $this->formatBytes($diskFree !== false ? $diskFree : 0),
            'disk_total' => $this->formatBytes($diskTotal !== false ? $diskTotal : 0),
            'memory_limit' => (string) ini_get('memory_limit'),
            'max_execution_time' => (string) ini_get('max_execution_time') . 's',
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
        ];
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
            $defaultConnection = config('database.default');
            $this->databaseInfo = [
                'connection' => $defaultConnection,
                'database' => config("database.connections.{$defaultConnection}.database"),
                'host' => config("database.connections.{$defaultConnection}.host"),
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
            if ($envContent === false) {
                return;
            }

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

    private function loadDomainInfo(): void
    {
        $this->currentAppUrl = config('app.url', 'Not set');
        $parsedHost = parse_url($this->currentAppUrl, PHP_URL_HOST);
        $this->currentAppDomain = ($parsedHost !== false && $parsedHost !== null) ? $parsedHost : 'localhost';

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

    // ===== DOMAIN METHODS =====

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
            $parsedHost = parse_url($url, PHP_URL_HOST);
            $this->currentAppDomain = ($parsedHost !== false && $parsedHost !== null) ? $parsedHost : 'localhost';
            session()->flash('message', 'APP_URL updated successfully! You may need to update your Nginx configuration.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update APP_URL: ' . $e->getMessage());
        }
    }

    // ===== ENVIRONMENT EDITOR METHODS =====

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
            if ($envContent === false) {
                throw new \Exception('Failed to read .env file');
            }

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

    // ===== MAINTENANCE & CONFIGURATION METHODS =====

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

    public function refreshSystemInfo(): void
    {
        $this->loadSystemInfo();
        $this->loadConfiguration();
        $this->loadDatabaseInfo();
        $this->loadDomainInfo();
        session()->flash('message', 'System information refreshed successfully!');
    }

    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) max((float) $bytes, 0);
        if ($bytes == 0) {
            return '0 B';
        }
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $powInt = (int) $pow;
        return round($bytes / (1024 ** $powInt), $precision) . ' ' . $units[$powInt];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.system-info');
    }
}
