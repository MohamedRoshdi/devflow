<?php

declare(strict_types=1);

/**
 * Optimized Test Bootstrap
 *
 * Pre-warms database connections and caches to improve test performance
 */

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

// Set memory limit for tests
ini_set('memory_limit', '512M');

// Optimize garbage collection for tests
gc_enable();
gc_collect_cycles();
