<?php

return [
    // Page titles
    'title' => 'Install Script Generator',
    'generate_script' => 'Generate Install Script',

    // Deployment modes
    'deployment_mode' => 'Deployment Mode',
    'development' => 'Development',
    'development_desc' => 'Local development with debug tools',
    'production' => 'Production',
    'production_desc' => 'Secure, optimized for live servers',

    // Production settings
    'production_settings' => 'Production Settings',
    'domain' => 'Domain',
    'email' => 'Email (for SSL)',
    'skip_ssl' => 'Skip SSL Setup',
    'skip_ssl_desc' => 'Use when behind a reverse proxy',

    // Database
    'database_config' => 'Database Configuration',

    // Security & Services
    'security_services' => 'Security & Services',
    'queue_workers' => 'Queue Workers',

    // Script info
    'estimated_time' => 'Estimated installation time',
    'minutes' => 'minutes',
    'lines' => 'lines',

    // Actions
    'generate' => 'Generate Script',
    'generating' => 'Generating...',
    'download' => 'Download Script',
    'copy' => 'Copy',
    'reconfigure' => 'Reconfigure',
    'close' => 'Close',
    'copied_to_clipboard' => 'Script copied to clipboard!',

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

    // Validation messages
    'domain_required' => 'Domain is required for production mode',
    'email_required' => 'Email is required for SSL certificates',
];
