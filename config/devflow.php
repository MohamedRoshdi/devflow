<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | DevFlow Pro Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the DevFlow Pro deployment
    | and project management system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Projects Path
    |--------------------------------------------------------------------------
    |
    | The base directory where all project files are stored on the server.
    |
    */

    'projects_path' => env('DEVFLOW_PROJECTS_PATH', '/var/www'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts (in seconds)
    |--------------------------------------------------------------------------
    |
    | Configure timeout values for various long-running operations.
    |
    */

    'timeouts' => [
        // Docker installation timeout (5 minutes)
        'docker_install' => (int) env('DEVFLOW_TIMEOUT_DOCKER_INSTALL', 300),

        // Docker compose build timeout (20 minutes)
        'docker_compose_build' => (int) env('DEVFLOW_TIMEOUT_DOCKER_COMPOSE_BUILD', 1200),

        // Docker standalone build timeout (10 minutes)
        'docker_build' => (int) env('DEVFLOW_TIMEOUT_DOCKER_BUILD', 600),

        // Docker compose start/stop timeout (5 minutes)
        'docker_compose_start' => (int) env('DEVFLOW_TIMEOUT_DOCKER_COMPOSE_START', 300),

        // Docker compose cleanup timeout for complex projects (3 minutes)
        'docker_compose_cleanup' => (int) env('DEVFLOW_TIMEOUT_DOCKER_COMPOSE_CLEANUP', 180),

        // Backup operation timeout (30 minutes)
        'backup' => (int) env('DEVFLOW_TIMEOUT_BACKUP', 1800),

        // Health check HTTP request timeout (10 seconds)
        'health_check' => (int) env('DEVFLOW_TIMEOUT_HEALTH_CHECK', 10),

        // SSL certificate check timeout (30 seconds)
        'ssl_check' => (int) env('DEVFLOW_TIMEOUT_SSL_CHECK', 30),

        // Log download timeout (2 minutes)
        'log_download' => (int) env('DEVFLOW_TIMEOUT_LOG_DOWNLOAD', 120),

        // Docker pull timeout for large images (10 minutes)
        'docker_pull' => (int) env('DEVFLOW_TIMEOUT_DOCKER_PULL', 600),

        // System prune timeout (5 minutes)
        'system_prune' => (int) env('DEVFLOW_TIMEOUT_SYSTEM_PRUNE', 300),

        // Command execution timeout (5 minutes)
        'command_exec' => (int) env('DEVFLOW_TIMEOUT_COMMAND_EXEC', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache duration for various data types (in seconds).
    |
    */

    'cache' => [
        // Project health check cache duration (1 minute)
        'project_health' => (int) env('DEVFLOW_CACHE_PROJECT_HEALTH', 60),

        // SSL certificate check cache duration (1 hour)
        'ssl_certificate' => (int) env('DEVFLOW_CACHE_SSL_CERTIFICATE', 3600),

        // Server metrics cache duration (5 minutes)
        'server_metrics' => (int) env('DEVFLOW_CACHE_SERVER_METRICS', 300),

        // Docker container status cache duration (30 seconds)
        'container_status' => (int) env('DEVFLOW_CACHE_CONTAINER_STATUS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for project health monitoring.
    |
    */

    'health_check' => [
        // Default interval between health checks (in seconds) - 5 minutes
        'interval' => (int) env('DEVFLOW_HEALTH_CHECK_INTERVAL', 300),

        // SSL certificate expiry warning threshold (in days)
        'ssl_expiry_warning_days' => (int) env('DEVFLOW_SSL_EXPIRY_WARNING_DAYS', 7),

        // Disk usage warning threshold (percentage)
        'disk_warning_threshold' => (int) env('DEVFLOW_DISK_WARNING_THRESHOLD', 75),

        // Disk usage critical threshold (percentage)
        'disk_critical_threshold' => (int) env('DEVFLOW_DISK_CRITICAL_THRESHOLD', 90),

        // CPU usage warning threshold (percentage)
        'cpu_warning_threshold' => (int) env('DEVFLOW_CPU_WARNING_THRESHOLD', 75),

        // CPU usage critical threshold (percentage)
        'cpu_critical_threshold' => (int) env('DEVFLOW_CPU_CRITICAL_THRESHOLD', 90),

        // Memory usage warning threshold (percentage)
        'memory_warning_threshold' => (int) env('DEVFLOW_MEMORY_WARNING_THRESHOLD', 75),

        // Memory usage critical threshold (percentage)
        'memory_critical_threshold' => (int) env('DEVFLOW_MEMORY_CRITICAL_THRESHOLD', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automated deployments.
    |
    */

    'deployment' => [
        // Maximum number of concurrent deployments
        'max_concurrent' => (int) env('DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS', 3),

        // Deployment retention period (in days)
        'retention_days' => (int) env('DEVFLOW_DEPLOYMENT_RETENTION_DAYS', 30),

        // Enable deployment approval workflow
        'require_approval' => (bool) env('DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for backup operations.
    |
    */

    'backup' => [
        // Backup storage path
        'path' => env('DEVFLOW_BACKUP_PATH', storage_path('backups')),

        // Backup retention period (in days)
        'retention_days' => (int) env('DEVFLOW_BACKUP_RETENTION_DAYS', 7),

        // Enable automatic backups before deployment
        'auto_backup_before_deploy' => (bool) env('DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Docker Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Docker operations.
    |
    */

    'docker' => [
        // Default PHP version for Laravel projects
        'default_php_version' => env('DEVFLOW_DEFAULT_PHP_VERSION', '8.4'),

        // Default Node.js version for JavaScript projects
        'default_node_version' => env('DEVFLOW_DEFAULT_NODE_VERSION', '20'),

        // Enable Docker BuildKit
        'buildkit_enabled' => (bool) env('DEVFLOW_DOCKER_BUILDKIT', true),

        // Default container restart policy
        'restart_policy' => env('DEVFLOW_DOCKER_RESTART_POLICY', 'unless-stopped'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for notifications and alerts.
    |
    */

    'notifications' => [
        // Enable email notifications
        'email_enabled' => (bool) env('DEVFLOW_NOTIFICATIONS_EMAIL', true),

        // Enable Slack notifications
        'slack_enabled' => (bool) env('DEVFLOW_NOTIFICATIONS_SLACK', false),

        // Enable Discord notifications
        'discord_enabled' => (bool) env('DEVFLOW_NOTIFICATIONS_DISCORD', false),

        // Notification channels for deployment failures
        'deployment_failure_channels' => explode(',', env('DEVFLOW_DEPLOYMENT_FAILURE_CHANNELS', 'email')),

        // Notification channels for health check failures
        'health_check_failure_channels' => explode(',', env('DEVFLOW_HEALTH_CHECK_FAILURE_CHANNELS', 'email')),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Administration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for system administration features like backups, monitoring,
    | and database optimization. Configure via environment variables.
    |
    */

    'system_admin' => [
        // Primary server for system administration (use Server model instead when possible)
        'primary_server' => [
            'ip_address' => env('DEVFLOW_PRIMARY_SERVER_IP'),
            'username' => env('DEVFLOW_PRIMARY_SERVER_USER', 'root'),
            'port' => (int) env('DEVFLOW_PRIMARY_SERVER_PORT', 22),
        ],

        // Monitoring and backup paths
        'paths' => [
            'backup_log' => env('DEVFLOW_BACKUP_LOG_PATH', '/opt/backups/databases/backup.log'),
            'backup_dir' => env('DEVFLOW_BACKUP_DIR', '/opt/backups/databases'),
            'monitor_log' => env('DEVFLOW_MONITOR_LOG_PATH', '/var/log/devflow-monitor.log'),
            'optimization_log' => env('DEVFLOW_OPTIMIZATION_LOG_PATH', '/var/log/devflow-db-optimization.log'),
        ],

        // Scripts paths
        'scripts' => [
            'backup' => env('DEVFLOW_BACKUP_SCRIPT', '/opt/scripts/backup-databases.sh'),
            'optimize' => env('DEVFLOW_OPTIMIZE_SCRIPT', '/opt/scripts/optimize-databases.sh'),
        ],
    ],

];
