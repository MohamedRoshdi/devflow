<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\ServerProvisioningService;
use Illuminate\Console\Command;

class ProvisionServer extends Command
{
    protected $signature = 'server:provision
                            {server : The server ID or hostname}
                            {--packages=* : Packages to install (nginx,php,mysql,composer,nodejs)}
                            {--php-version=8.4 : PHP version to install}
                            {--node-version=20 : Node.js version to install}
                            {--mysql-password= : MySQL root password}
                            {--swap-size=2 : Swap file size in GB}
                            {--no-firewall : Skip firewall configuration}
                            {--no-swap : Skip swap configuration}
                            {--no-ssh-security : Skip SSH security hardening}';

    protected $description = 'Provision a server with required packages and configurations';

    public function __construct(
        protected ServerProvisioningService $provisioningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $serverIdentifier = $this->argument('server');

        // Find server by ID or hostname
        $server = is_numeric($serverIdentifier)
            ? Server::find($serverIdentifier)
            : Server::where('hostname', $serverIdentifier)->orWhere('name', $serverIdentifier)->first();

        if (! $server) {
            $this->error("Server not found: {$serverIdentifier}");

            return self::FAILURE;
        }

        $this->info("Provisioning server: {$server->name} ({$server->ip_address})");
        $this->newLine();

        // Parse packages
        $requestedPackages = $this->option('packages');
        $allPackages = empty($requestedPackages) || in_array('all', $requestedPackages);

        $options = [
            'update_system' => true,
            'install_nginx' => $allPackages || in_array('nginx', $requestedPackages),
            'install_mysql' => $allPackages || in_array('mysql', $requestedPackages),
            'install_php' => $allPackages || in_array('php', $requestedPackages),
            'install_composer' => $allPackages || in_array('composer', $requestedPackages),
            'install_nodejs' => $allPackages || in_array('nodejs', $requestedPackages),
            'configure_firewall' => ! $this->option('no-firewall'),
            'setup_swap' => ! $this->option('no-swap'),
            'secure_ssh' => ! $this->option('no-ssh-security'),
            'php_version' => $this->option('php-version'),
            'node_version' => $this->option('node-version'),
            'swap_size_gb' => (int) $this->option('swap-size'),
        ];

        // Get MySQL password if installing MySQL
        if ($options['install_mysql']) {
            $mysqlPassword = $this->option('mysql-password');
            if (! $mysqlPassword) {
                $mysqlPassword = $this->secret('Enter MySQL root password (leave empty for auto-generated)');
                if (! $mysqlPassword) {
                    $mysqlPassword = bin2hex(random_bytes(16));
                    $this->warn("Auto-generated MySQL root password: {$mysqlPassword}");
                    $this->warn('Please save this password securely!');
                }
            }
            $options['mysql_root_password'] = $mysqlPassword;
        }

        // Display provisioning plan
        $this->info('Provisioning plan:');
        $this->table(
            ['Task', 'Status'],
            [
                ['Update System', $options['update_system'] ? '✓' : '✗'],
                ['Install Nginx', $options['install_nginx'] ? '✓' : '✗'],
                ['Install MySQL', $options['install_mysql'] ? '✓' : '✗'],
                ['Install PHP '.$options['php_version'], $options['install_php'] ? '✓' : '✗'],
                ['Install Composer', $options['install_composer'] ? '✓' : '✗'],
                ['Install Node.js '.$options['node_version'], $options['install_nodejs'] ? '✓' : '✗'],
                ['Configure Firewall', $options['configure_firewall'] ? '✓' : '✗'],
                ['Setup Swap ('.$options['swap_size_gb'].'GB)', $options['setup_swap'] ? '✓' : '✗'],
                ['Secure SSH', $options['secure_ssh'] ? '✓' : '✗'],
            ]
        );

        $this->newLine();

        if (! $this->confirm('Do you want to proceed with provisioning?', true)) {
            $this->warn('Provisioning cancelled.');

            return self::SUCCESS;
        }

        try {
            $this->info('Starting provisioning...');
            $this->newLine();

            $progressBar = $this->output->createProgressBar();
            $progressBar->start();

            $this->provisioningService->provisionServer($server, $options);

            $progressBar->finish();
            $this->newLine(2);

            $this->info('✓ Server provisioned successfully!');
            $freshServer = $server->fresh();
            $packages = $freshServer?->installed_packages ?? [];
            $this->info('Provisioned packages: '.implode(', ', $packages));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Provisioning failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
