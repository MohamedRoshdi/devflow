<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProvisioningLog;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ServerProvisioningService
{
    /**
     * Provision a server with selected packages
     */
    public function provisionServer(Server $server, array $options = []): void
    {
        $server->update([
            'provision_status' => 'provisioning',
        ]);

        $tasks = [
            'update_system' => $options['update_system'] ?? true,
            'install_nginx' => $options['install_nginx'] ?? true,
            'install_mysql' => $options['install_mysql'] ?? false,
            'install_postgresql' => $options['install_postgresql'] ?? false,
            'install_redis' => $options['install_redis'] ?? false,
            'install_php' => $options['install_php'] ?? true,
            'install_composer' => $options['install_composer'] ?? true,
            'install_nodejs' => $options['install_nodejs'] ?? true,
            'configure_firewall' => $options['configure_firewall'] ?? true,
            'setup_swap' => $options['setup_swap'] ?? true,
            'secure_ssh' => $options['secure_ssh'] ?? true,
        ];

        $installedPackages = $server->installed_packages ?? [];

        try {
            if ($tasks['update_system']) {
                $this->updateSystem($server);
            }

            if ($tasks['install_nginx']) {
                $this->installNginx($server);
                $installedPackages[] = 'nginx';
            }

            if ($tasks['install_mysql']) {
                $mysqlPassword = $options['mysql_root_password'] ?? bin2hex(random_bytes(16));
                $this->installMySQL($server, $mysqlPassword);
                $installedPackages[] = 'mysql';
            }

            if ($tasks['install_postgresql']) {
                $pgPassword = $options['postgresql_password'] ?? bin2hex(random_bytes(16));
                $pgDatabases = $options['postgresql_databases'] ?? [];
                $this->installPostgreSQL($server, $pgPassword, $pgDatabases);
                $installedPackages[] = 'postgresql-16';
            }

            if ($tasks['install_redis']) {
                $redisPassword = $options['redis_password'] ?? null;
                $redisMaxMemory = $options['redis_max_memory_mb'] ?? 512;
                $this->installRedis($server, $redisPassword, $redisMaxMemory);
                $installedPackages[] = 'redis';
            }

            if ($tasks['install_php']) {
                $phpVersion = $options['php_version'] ?? '8.4';
                $this->installPHP($server, $phpVersion);
                $installedPackages[] = 'php-'.$phpVersion;
            }

            if ($tasks['install_composer']) {
                $this->installComposer($server);
                $installedPackages[] = 'composer';
            }

            if ($tasks['install_nodejs']) {
                $nodeVersion = $options['node_version'] ?? '20';
                $this->installNodeJS($server, $nodeVersion);
                $installedPackages[] = 'nodejs-'.$nodeVersion;
            }

            if ($tasks['configure_firewall']) {
                $ports = $options['firewall_ports'] ?? [22, 80, 443];
                $this->configureFirewall($server, $ports);
                $installedPackages[] = 'ufw';
            }

            if ($tasks['setup_swap']) {
                $swapSize = $options['swap_size_gb'] ?? 2;
                $this->setupSwap($server, $swapSize);
            }

            if ($tasks['secure_ssh']) {
                $this->secureSSH($server);
            }

            $server->update([
                'provision_status' => 'completed',
                'provisioned_at' => now(),
                'installed_packages' => array_unique($installedPackages),
            ]);

            // Send success notification
            if ($server->user) {
                $server->user->notify(
                    new \App\Notifications\ServerProvisioningCompleted($server, true)
                );
            }

        } catch (\Exception $e) {
            Log::error('Server provisioning failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            $server->update([
                'provision_status' => 'failed',
            ]);

            // Send failure notification
            if ($server->user) {
                $server->user->notify(
                    new \App\Notifications\ServerProvisioningCompleted($server, false, $e->getMessage())
                );
            }

            throw $e;
        }
    }

    /**
     * Update system packages
     */
    public function updateSystem(Server $server): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'update_system',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get update',
                'DEBIAN_FRONTEND=noninteractive apt-get upgrade -y',
                'DEBIAN_FRONTEND=noninteractive apt-get autoremove -y',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install Nginx web server
     */
    public function installNginx(Server $server): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_nginx',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get install -y nginx',
                'systemctl enable nginx',
                'systemctl start nginx',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install MySQL database server
     */
    public function installMySQL(Server $server, string $rootPassword): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_mysql',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Properly escape the MySQL password for shell execution
            $escapedPassword = str_replace("'", "'\\''", $rootPassword);
            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server',
                sprintf("mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '%s';\"", $escapedPassword),
                'systemctl enable mysql',
                'systemctl start mysql',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install PostgreSQL 16 database server
     *
     * @param  array<int, string>  $databases
     */
    public function installPostgreSQL(Server $server, string $password, array $databases = []): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_postgresql',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $escapedPassword = str_replace("'", "'\\''", $password);
            $commands = [
                'curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql-keyring.gpg 2>/dev/null || true',
                'echo "deb [signed-by=/usr/share/keyrings/postgresql-keyring.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" | tee /etc/apt/sources.list.d/pgdg.list',
                'DEBIAN_FRONTEND=noninteractive apt-get update',
                'DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql-16 postgresql-contrib-16',
                'systemctl enable postgresql',
                'systemctl start postgresql',
                sprintf("sudo -u postgres psql -c \"CREATE USER app_user WITH PASSWORD '%s';\" 2>/dev/null || sudo -u postgres psql -c \"ALTER USER app_user WITH PASSWORD '%s';\"", $escapedPassword, $escapedPassword),
            ];

            foreach ($databases as $database) {
                $database = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
                $commands[] = sprintf('sudo -u postgres psql -c "CREATE DATABASE %s OWNER app_user;" 2>/dev/null || true', $database);
                $commands[] = sprintf('sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE %s TO app_user;"', $database);
            }

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install Redis server
     */
    public function installRedis(Server $server, ?string $password = null, int $maxMemoryMB = 512): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_redis',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server',
                'sed -i "s/^bind .*/bind 127.0.0.1 ::1/" /etc/redis/redis.conf',
                'sed -i "s/^supervised .*/supervised systemd/" /etc/redis/redis.conf',
                sprintf('sed -i "s/^# maxmemory <bytes>/maxmemory %dmb/" /etc/redis/redis.conf', $maxMemoryMB),
                'sed -i "s/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/" /etc/redis/redis.conf',
            ];

            if ($password !== null && $password !== '') {
                $escapedPassword = str_replace("'", "'\\''", $password);
                $commands[] = sprintf('sed -i "s/^# requirepass .*/requirepass %s/" /etc/redis/redis.conf', $escapedPassword);
            }

            $commands[] = 'systemctl enable redis-server';
            $commands[] = 'systemctl start redis-server';

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install PHP with common extensions
     */
    public function installPHP(Server $server, string $version = '8.4'): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_php_'.$version,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $extensions = [
                'cli', 'fpm', 'common', 'curl', 'zip', 'gd', 'mysql', 'pgsql', 'mbstring',
                'xml', 'redis', 'intl', 'bcmath', 'soap', 'imagick', 'opcache',
            ];

            $packages = array_map(fn ($ext) => "php{$version}-{$ext}", $extensions);

            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common',
                'DEBIAN_FRONTEND=noninteractive add-apt-repository -y ppa:ondrej/php',
                'DEBIAN_FRONTEND=noninteractive apt-get update',
                'DEBIAN_FRONTEND=noninteractive apt-get install -y '.implode(' ', $packages),
                "systemctl enable php{$version}-fpm",
                "systemctl start php{$version}-fpm",
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install Composer
     */
    public function installComposer(Server $server): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_composer',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'cd /tmp && curl -sS https://getcomposer.org/installer -o composer-setup.php',
                'cd /tmp && php composer-setup.php --install-dir=/usr/local/bin --filename=composer',
                'rm -f /tmp/composer-setup.php',
                'chmod +x /usr/local/bin/composer',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Install Node.js
     */
    public function installNodeJS(Server $server, string $version = '20'): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'install_nodejs_'.$version,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                "curl -fsSL https://deb.nodesource.com/setup_{$version}.x | bash -",
                'DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs',
                'npm install -g npm@latest',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Configure UFW firewall
     */
    public function configureFirewall(Server $server, array $ports = [80, 443, 22]): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'configure_firewall',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'DEBIAN_FRONTEND=noninteractive apt-get install -y ufw',
                'ufw --force disable',
            ];

            // Allow specified ports
            foreach ($ports as $port) {
                $commands[] = "ufw allow {$port}/tcp";
            }

            $commands[] = 'ufw --force enable';

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $server->update([
                'ufw_installed' => true,
                'ufw_enabled' => true,
            ]);

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Setup swap file
     */
    public function setupSwap(Server $server, int $sizeGB = 2): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'setup_swap',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                "fallocate -l {$sizeGB}G /swapfile",
                'chmod 600 /swapfile',
                'mkswap /swapfile',
                'swapon /swapfile',
                'echo "/swapfile none swap sw 0 0" >> /etc/fstab',
                'sysctl vm.swappiness=10',
                'echo "vm.swappiness=10" >> /etc/sysctl.conf',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Secure SSH configuration
     */
    public function secureSSH(Server $server): bool
    {
        $log = ProvisioningLog::create([
            'server_id' => $server->id,
            'task' => 'secure_ssh',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $commands = [
                'sed -i "s/#PermitRootLogin yes/PermitRootLogin prohibit-password/" /etc/ssh/sshd_config',
                'sed -i "s/#PasswordAuthentication yes/PasswordAuthentication no/" /etc/ssh/sshd_config',
                'sed -i "s/PasswordAuthentication yes/PasswordAuthentication no/" /etc/ssh/sshd_config',
                'systemctl reload sshd',
            ];

            $output = '';
            foreach ($commands as $command) {
                $output .= $this->executeSSHCommand($server, $command)."\n";
            }

            $log->markAsCompleted($output);

            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate complete provisioning script
     */
    public function getProvisioningScript(array $options): string
    {
        $script = "#!/bin/bash\n";
        $script .= "# DevFlow Pro - Server Provisioning Script\n";
        $script .= '# Generated at: '.now()->toDateTimeString()."\n\n";
        $script .= "set -e\n\n";
        $script .= "echo '🚀 Starting server provisioning...'\n\n";

        if ($options['update_system'] ?? true) {
            $script .= "# Update system\n";
            $script .= "echo '📦 Updating system packages...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get update\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get upgrade -y\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get autoremove -y\n\n";
        }

        if ($options['install_nginx'] ?? true) {
            $script .= "# Install Nginx\n";
            $script .= "echo '🌐 Installing Nginx...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y nginx\n";
            $script .= "systemctl enable nginx\n";
            $script .= "systemctl start nginx\n\n";
        }

        if ($options['install_mysql'] ?? false) {
            $script .= "# Install MySQL\n";
            $script .= "echo '🗄️ Installing MySQL...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server\n";
            $script .= "systemctl enable mysql\n";
            $script .= "systemctl start mysql\n\n";
        }

        if ($options['install_postgresql'] ?? false) {
            $script .= "# Install PostgreSQL 16\n";
            $script .= "echo '🐘 Installing PostgreSQL 16...'\n";
            $script .= "curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql-keyring.gpg 2>/dev/null || true\n";
            $script .= "echo \"deb [signed-by=/usr/share/keyrings/postgresql-keyring.gpg] http://apt.postgresql.org/pub/repos/apt \$(lsb_release -cs)-pgdg main\" | tee /etc/apt/sources.list.d/pgdg.list\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get update\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql-16 postgresql-contrib-16\n";
            $script .= "systemctl enable postgresql\n";
            $script .= "systemctl start postgresql\n";
            $pgPassword = $options['postgresql_password'] ?? 'CHANGE_ME';
            $script .= "sudo -u postgres psql -c \"CREATE USER app_user WITH PASSWORD '{$pgPassword}';\" 2>/dev/null || sudo -u postgres psql -c \"ALTER USER app_user WITH PASSWORD '{$pgPassword}';\"\n";
            $pgDatabases = $options['postgresql_databases'] ?? [];
            foreach ($pgDatabases as $database) {
                $database = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
                $script .= "sudo -u postgres psql -c \"CREATE DATABASE {$database} OWNER app_user;\" 2>/dev/null || true\n";
                $script .= "sudo -u postgres psql -c \"GRANT ALL PRIVILEGES ON DATABASE {$database} TO app_user;\"\n";
            }
            $script .= "\n";
        }

        if ($options['install_redis'] ?? false) {
            $script .= "# Install Redis\n";
            $script .= "echo '📦 Installing Redis...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server\n";
            $script .= "sed -i 's/^bind .*/bind 127.0.0.1 ::1/' /etc/redis/redis.conf\n";
            $script .= "sed -i 's/^supervised .*/supervised systemd/' /etc/redis/redis.conf\n";
            $redisMaxMemory = $options['redis_max_memory_mb'] ?? 512;
            $script .= "sed -i 's/^# maxmemory <bytes>/maxmemory {$redisMaxMemory}mb/' /etc/redis/redis.conf\n";
            $script .= "sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf\n";
            $redisPassword = $options['redis_password'] ?? null;
            if ($redisPassword !== null && $redisPassword !== '') {
                $script .= "sed -i 's/^# requirepass .*/requirepass {$redisPassword}/' /etc/redis/redis.conf\n";
            }
            $script .= "systemctl enable redis-server\n";
            $script .= "systemctl start redis-server\n\n";
        }

        if ($options['install_php'] ?? true) {
            $phpVersion = $options['php_version'] ?? '8.4';
            $script .= "# Install PHP {$phpVersion}\n";
            $script .= "echo '🐘 Installing PHP {$phpVersion}...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common\n";
            $script .= "DEBIAN_FRONTEND=noninteractive add-apt-repository -y ppa:ondrej/php\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get update\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y php{$phpVersion}-cli php{$phpVersion}-fpm php{$phpVersion}-common ";
            $script .= "php{$phpVersion}-curl php{$phpVersion}-zip php{$phpVersion}-gd php{$phpVersion}-mysql php{$phpVersion}-pgsql ";
            $script .= "php{$phpVersion}-mbstring php{$phpVersion}-xml php{$phpVersion}-redis php{$phpVersion}-intl ";
            $script .= "php{$phpVersion}-bcmath php{$phpVersion}-soap php{$phpVersion}-imagick php{$phpVersion}-opcache\n";
            $script .= "systemctl enable php{$phpVersion}-fpm\n";
            $script .= "systemctl start php{$phpVersion}-fpm\n\n";
        }

        if ($options['install_composer'] ?? true) {
            $script .= "# Install Composer\n";
            $script .= "echo '🎼 Installing Composer...'\n";
            $script .= "cd /tmp && curl -sS https://getcomposer.org/installer -o composer-setup.php\n";
            $script .= "php composer-setup.php --install-dir=/usr/local/bin --filename=composer\n";
            $script .= "rm -f /tmp/composer-setup.php\n";
            $script .= "chmod +x /usr/local/bin/composer\n\n";
        }

        if ($options['install_nodejs'] ?? true) {
            $nodeVersion = $options['node_version'] ?? '20';
            $script .= "# Install Node.js {$nodeVersion}\n";
            $script .= "echo '⬢ Installing Node.js {$nodeVersion}...'\n";
            $script .= "curl -fsSL https://deb.nodesource.com/setup_{$nodeVersion}.x | bash -\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs\n";
            $script .= "npm install -g npm@latest\n\n";
        }

        if ($options['configure_firewall'] ?? true) {
            $ports = $options['firewall_ports'] ?? [22, 80, 443];
            $script .= "# Configure Firewall\n";
            $script .= "echo '🔥 Configuring UFW firewall...'\n";
            $script .= "DEBIAN_FRONTEND=noninteractive apt-get install -y ufw\n";
            $script .= "ufw --force disable\n";
            foreach ($ports as $port) {
                $script .= "ufw allow {$port}/tcp\n";
            }
            $script .= "ufw --force enable\n\n";
        }

        if ($options['setup_swap'] ?? true) {
            $swapSize = $options['swap_size_gb'] ?? 2;
            $script .= "# Setup Swap\n";
            $script .= "echo '💾 Setting up {$swapSize}GB swap...'\n";
            $script .= "fallocate -l {$swapSize}G /swapfile\n";
            $script .= "chmod 600 /swapfile\n";
            $script .= "mkswap /swapfile\n";
            $script .= "swapon /swapfile\n";
            $script .= "echo \"/swapfile none swap sw 0 0\" >> /etc/fstab\n";
            $script .= "sysctl vm.swappiness=10\n";
            $script .= "echo \"vm.swappiness=10\" >> /etc/sysctl.conf\n\n";
        }

        $script .= "echo '✅ Server provisioning completed!'\n";

        return $script;
    }

    /**
     * Execute SSH command using ServerMetricsService pattern
     */
    protected function executeSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=30',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);
            $command = sprintf(
                'sshpass -p %s ssh %s %s@%s %s 2>&1',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remoteCommand)
            );
        } else {
            $sshOptions[] = '-o BatchMode=yes';

            if ($server->ssh_key) {
                $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
                file_put_contents($keyFile, $server->ssh_key);
                chmod($keyFile, 0600);
                $sshOptions[] = '-i '.$keyFile;
            }

            $command = sprintf(
                'ssh %s %s@%s %s 2>&1',
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($remoteCommand)
            );
        }

        $result = Process::timeout(600)->run($command); // 10 minutes for long-running installs

        if (! $result->successful()) {
            throw new \RuntimeException("SSH command failed: {$remoteCommand}\nError: {$result->errorOutput()}");
        }

        return trim($result->output());
    }
}
