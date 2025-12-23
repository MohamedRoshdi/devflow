<?php

declare(strict_types=1);

/**
 * Optimized Test Bootstrap
 *
 * Pre-warms database connections and caches to improve test performance
 */

// Reset opcache to ensure tests use fresh code (important when opcache.validate_timestamps=0)
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Create a temporary writable storage path for testing
$tempStoragePath = sys_get_temp_dir() . '/devflow-tests-storage';
if (!is_dir($tempStoragePath)) {
    mkdir($tempStoragePath, 0777, true);
}
if (!is_dir($tempStoragePath . '/framework/cache')) {
    mkdir($tempStoragePath . '/framework/cache', 0777, true);
}
if (!is_dir($tempStoragePath . '/framework/views')) {
    mkdir($tempStoragePath . '/framework/views', 0777, true);
}
if (!is_dir($tempStoragePath . '/framework/sessions')) {
    mkdir($tempStoragePath . '/framework/sessions', 0777, true);
}
if (!is_dir($tempStoragePath . '/logs')) {
    mkdir($tempStoragePath . '/logs', 0777, true);
}

// Set environment variable before Laravel boots
putenv('LARAVEL_STORAGE_PATH=' . $tempStoragePath);
$_ENV['LARAVEL_STORAGE_PATH'] = $tempStoragePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $tempStoragePath;

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Vendor autoload file not found. Run: composer install');
}

require_once $autoloadPath;

// Pre-warm opcache if available
if (function_exists('opcache_compile_file')) {
    $files = [
        __DIR__ . '/../app/Models/User.php',
        __DIR__ . '/../app/Models/Project.php',
        __DIR__ . '/../app/Models/Server.php',
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            @opcache_compile_file($file);
        }
    }
}

// Set memory limit for tests (increased for large test suite)
ini_set('memory_limit', '8G');

// Optimize garbage collection for tests
gc_enable();
gc_collect_cycles();
