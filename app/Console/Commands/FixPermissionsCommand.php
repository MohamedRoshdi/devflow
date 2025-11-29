<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class FixPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-permissions
                            {--path= : Custom project path (defaults to base path)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Laravel storage and cache permissions';

    /**
     * Required directories for Laravel storage
     *
     * @var array<int, string>
     */
    private array $requiredDirectories = [
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/testing',
        'storage/app/public',
        'bootstrap/cache',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Starting Laravel permissions fix...');
        $this->newLine();

        // Get the project path
        $projectPath = $this->option('path') ?? base_path();

        if (!is_dir($projectPath)) {
            $this->error("❌ Project path does not exist: {$projectPath}");
            return self::FAILURE;
        }

        $this->info("📁 Project path: {$projectPath}");
        $this->newLine();

        // Step 1: Create missing directories
        if (!$this->createMissingDirectories($projectPath)) {
            return self::FAILURE;
        }

        // Step 2: Fix ownership
        if (!$this->fixOwnership($projectPath)) {
            return self::FAILURE;
        }

        // Step 3: Fix permissions
        if (!$this->fixPermissions($projectPath)) {
            return self::FAILURE;
        }

        // Step 4: Clear caches
        if (!$this->clearCaches($projectPath)) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Permissions fixed successfully!');

        return self::SUCCESS;
    }

    /**
     * Create missing directories
     */
    private function createMissingDirectories(string $projectPath): bool
    {
        $this->info('📂 Creating missing directories...');

        try {
            foreach ($this->requiredDirectories as $directory) {
                $fullPath = $projectPath . '/' . $directory;

                if (!is_dir($fullPath)) {
                    if (!File::makeDirectory($fullPath, 0775, true)) {
                        $this->error("   ❌ Failed to create: {$directory}");
                        return false;
                    }
                    $this->line("   ✓ Created: <fg=green>{$directory}</>");
                } else {
                    $this->line("   ✓ Exists: <fg=gray>{$directory}</>");
                }
            }

            $this->newLine();
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Error creating directories: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Fix ownership to www-data:www-data
     */
    private function fixOwnership(string $projectPath): bool
    {
        $this->info('👤 Fixing ownership to www-data:www-data...');

        $paths = [
            $projectPath . '/storage',
            $projectPath . '/bootstrap/cache',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                $this->warn("   ⚠ Path not found, skipping: {$path}");
                continue;
            }

            try {
                $relativePath = str_replace($projectPath . '/', '', $path);

                // Check if running as root or with sudo capabilities
                $currentUser = posix_getuid();

                if ($currentUser === 0) {
                    // Running as root, can change ownership directly
                    $process = new Process(['chown', '-R', 'www-data:www-data', $path]);
                } else {
                    // Try with sudo
                    $this->line("   ℹ Running with sudo (may require password)...");
                    $process = new Process(['sudo', 'chown', '-R', 'www-data:www-data', $path]);
                }

                $process->setTimeout(60);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->line("   ✓ Ownership fixed: <fg=green>{$relativePath}</>");
                } else {
                    $this->warn("   ⚠ Could not change ownership for: {$relativePath}");
                    $this->line("   ℹ Error: {$process->getErrorOutput()}");
                    $this->line("   ℹ Continuing with permission fixes...");
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠ Ownership fix failed for {$path}: {$e->getMessage()}");
                $this->line("   ℹ Continuing with permission fixes...");
            }
        }

        $this->newLine();
        return true;
    }

    /**
     * Fix permissions (775 for directories, 664 for files)
     */
    private function fixPermissions(string $projectPath): bool
    {
        $this->info('🔐 Fixing permissions (775 for directories, 664 for files)...');

        $paths = [
            $projectPath . '/storage',
            $projectPath . '/bootstrap/cache',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                $this->warn("   ⚠ Path not found, skipping: {$path}");
                continue;
            }

            try {
                $relativePath = str_replace($projectPath . '/', '', $path);

                // Set directory permissions
                $this->setPermissionsRecursively($path, 0775, 0664);

                $this->line("   ✓ Permissions fixed: <fg=green>{$relativePath}</>");
            } catch (\Exception $e) {
                $this->error("   ❌ Permission fix failed for {$path}: {$e->getMessage()}");
                return false;
            }
        }

        $this->newLine();
        return true;
    }

    /**
     * Recursively set permissions for directories and files
     */
    private function setPermissionsRecursively(string $path, int $dirMode, int $fileMode): void
    {
        if (is_dir($path)) {
            // Set directory permission
            chmod($path, $dirMode);

            // Process directory contents
            $items = scandir($path);
            if ($items === false) {
                return;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $item;

                if (is_dir($fullPath)) {
                    $this->setPermissionsRecursively($fullPath, $dirMode, $fileMode);
                } else {
                    chmod($fullPath, $fileMode);
                }
            }
        } elseif (is_file($path)) {
            chmod($path, $fileMode);
        }
    }

    /**
     * Clear all Laravel caches
     */
    private function clearCaches(string $projectPath): bool
    {
        $this->info('🧹 Clearing all caches...');

        $cacheCommands = [
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'cache:clear' => 'Application cache',
        ];

        foreach ($cacheCommands as $command => $description) {
            try {
                $this->call($command);
                $this->line("   ✓ Cleared: <fg=green>{$description}</>");
            } catch (\Exception $e) {
                $this->warn("   ⚠ Could not clear {$description}: {$e->getMessage()}");
            }
        }

        // Clear compiled files
        try {
            $compiledPath = $projectPath . '/bootstrap/cache/compiled.php';
            if (file_exists($compiledPath)) {
                unlink($compiledPath);
                $this->line("   ✓ Removed: <fg=green>Compiled classes</>");
            }

            $servicesPath = $projectPath . '/bootstrap/cache/services.php';
            if (file_exists($servicesPath)) {
                unlink($servicesPath);
                $this->line("   ✓ Removed: <fg=green>Services cache</>");
            }

            $packagesPath = $projectPath . '/bootstrap/cache/packages.php';
            if (file_exists($packagesPath)) {
                unlink($packagesPath);
                $this->line("   ✓ Removed: <fg=green>Packages cache</>");
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠ Could not remove compiled files: {$e->getMessage()}");
        }

        // Try to clear OPcache if available
        if (function_exists('opcache_reset')) {
            try {
                opcache_reset();
                $this->line("   ✓ Cleared: <fg=green>OPcache</>");
            } catch (\Exception $e) {
                $this->warn("   ⚠ Could not clear OPcache: {$e->getMessage()}");
            }
        }

        return true;
    }
}
