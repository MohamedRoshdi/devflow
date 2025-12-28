<?php

return [
    // Page titles
    'title' => 'Install Script Generator',
    'generate_script' => 'Generate Install Script',
    'run_install_script' => 'Run Install Script',

    // Script detection
    'checking_script' => 'Checking for install.sh...',
    'script_found' => 'install.sh found in project',
    'no_script_found' => 'No install.sh found',
    'no_script_found_desc' => 'This project does not have an install.sh file in the repository. Add one to enable automated installation.',

    // Deployment modes
    'deployment_mode' => 'Deployment Mode',
    'development' => 'Development',
    'development_desc' => 'Local development with debug tools',
    'production' => 'Production',
    'production_desc' => 'Secure, optimized for live servers',
    'production_mode' => 'Production Mode',
    'production_mode_desc' => 'Enable security hardening, SSL, and optimizations',

    // Production settings
    'production_settings' => 'Production Settings',
    'domain' => 'Domain',
    'email' => 'Email (for SSL)',
    'skip_ssl' => 'Skip SSL Setup',
    'skip_ssl_desc' => 'Use when behind a reverse proxy',

    // Database
    'database_config' => 'Database Configuration',
    'database' => 'Database Driver',
    'db_password' => 'Database Password',
    'auto_generated' => 'Leave empty to auto-generate',
    'db_password_hint' => 'Leave empty to generate a secure random password',

    // Security & Services
    'security_services' => 'Security & Services',
    'queue_workers' => 'Queue Workers',

    // Script info
    'estimated_time' => 'Estimated installation time',
    'minutes' => 'minutes',
    'lines' => 'lines',
    'output' => 'Output',

    // Actions
    'generate' => 'Generate Script',
    'generating' => 'Generating...',
    'download' => 'Download Script',
    'copy' => 'Copy',
    'reconfigure' => 'Reconfigure',
    'close' => 'Close',
    'copied_to_clipboard' => 'Script copied to clipboard!',
    'run_script' => 'Run Script',
    'view_script' => 'View Script',
    'running' => 'Running...',
    'completed' => 'Completed',
    'failed' => 'Failed',

    // Run results
    'run_success' => 'Install script completed successfully!',
    'run_failed' => 'Install script execution failed',

    // Usage instructions
    'usage_instructions' => 'Usage Instructions',
    'step_1' => 'Download or copy the script to your server',
    'step_2' => 'Make it executable: chmod +x install.sh',
    'step_3' => 'Run the script: ./install.sh',

    // Security features
    'security_features' => 'Security Features',
    'ufw_firewall' => 'UFW Firewall',
    'fail2ban' => 'Fail2ban Protection',
    'ssl_certificate' => 'SSL Certificate',
    'php_optimization' => 'PHP Optimizations',
    'secure_permissions' => 'Secure Permissions',

    // Script options (for help display)
    'option_production' => 'Enable production mode with security hardening',
    'option_domain' => 'Domain for SSL certificate',
    'option_email' => 'Email for Let\'s Encrypt notifications',
    'option_db_driver' => 'Database: pgsql or mysql',
    'option_skip_ssl' => 'Skip SSL setup (use behind proxy)',

    // Validation messages
    'domain_required' => 'Domain is required for production mode',
    'email_required' => 'Email is required for SSL certificates',
];
