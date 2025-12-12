# DevFlow Pro - Multi-Project Deployment & Management System
## Complete Development Guide

### Project Overview

**Project Name:** DevFlow Pro - Multi-Project Deployment & Management System
**Primary Developer:** MBFouad (Senior PHP Developer at ACID21)
**Tech Stack:** Laravel 12, PHP 8.4, Livewire 3, MySQL 8.0+, Redis, Docker, Nginx
**Code Standards:** PHPStan Level 8 compliance required
**Development Approach:** Direct code in conversation, single definitive solutions preferred

### Core Purpose

DevFlow Pro is a comprehensive project management and deployment system designed specifically for managing multiple Laravel/PHP projects on VPS servers. It provides:

- **Automated Deployments:** Git-based deployments from main/production branches
- **Project Monitoring:** Real-time status monitoring, health checks, and performance metrics
- **Domain Management:** Automatic SSL, subdomain creation, and DNS configuration
- **Storage Management:** Multi-storage support, usage tracking, and cleanup tools
- **Cache Management:** Redis/file cache clearing across all projects
- **Log Management:** Centralized log viewing, rotation, and analysis
- **Docker Integration:** Full Docker Compose orchestration and management
- **Multi-Tenancy Support:** Specialized tools for multi-tenant project management

### System Architecture

#### Core Architecture Pattern
```
┌─────────────────────────────────────────────────────────────────┐
│                    DevFlow Pro (Master System)                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐   │
│  │   Project   │ │   Docker    │ │      Domain &           │   │
│  │  Manager    │ │ Orchestrator│ │   SSL Manager           │   │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
    ┌─────────────────────────┼─────────────────────────┐
    │                         │                         │
┌───▼────────┐    ┌───────────▼──────────┐    ┌────────▼────────┐
│ Project A  │    │     Project B        │    │   Project C     │
│ (ATS Pro)  │    │  (E-commerce)        │    │ (GPS Tracker)   │
│            │    │                      │    │                 │
│ - Laravel  │    │ - Shopware           │    │ - Laravel       │
│ - Multi-   │    │ - Multi-store        │    │ - Real-time     │
│   tenant   │    │ - Payment Gateway    │    │ - Analytics     │
│ - Docker   │    │ - Docker             │    │ - Docker        │
└────────────┘    └──────────────────────┘    └─────────────────┘
```

#### Technology Stack

**Backend Framework:**
- **Laravel 12** - Latest framework for robust web applications
- **PHP 8.4** - Latest stable with property hooks and enhanced performance
- **Livewire 3** - Real-time UI updates without JavaScript complexity
- **MySQL 8.0+** - Primary database for project metadata and configurations
- **Redis** - Caching and queue management

**Infrastructure Management:**
- **Docker & Docker Compose** - Container orchestration for all projects
- **Nginx Proxy Manager** - Reverse proxy and SSL automation
- **Portainer** - Docker container visual management
- **Supervisor** - Process monitoring and queue workers

**File & Storage Management:**
- **Multi-Storage Support** - Local, S3, Google Cloud, Dropbox, Azure
- **File Synchronization** - Cross-storage migration and backup
- **Storage Analytics** - Usage tracking and optimization recommendations

### Database Schema Design

#### Core Tables Structure
```php
// Primary entities for project management
Project (1:N) -> Deployments, Domains, StorageConfigs
User (N:M) -> Projects (via permissions)
Server (1:N) -> Projects, DockerContainers
Domain (N:1) -> Project (1:N) -> SSLCertificates
Deployment (N:1) -> Project (1:N) -> DeploymentLogs
```

#### Critical Database Tables
```sql
-- Projects management
CREATE TABLE projects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    repository_url TEXT NOT NULL,
    branch VARCHAR(100) DEFAULT 'main',
    framework ENUM('laravel', 'shopware', 'symfony', 'wordpress', 'custom') NOT NULL,
    php_version VARCHAR(10) DEFAULT '8.4',
    project_type ENUM('single_tenant', 'multi_tenant', 'saas', 'microservice') DEFAULT 'single_tenant',
    server_id BIGINT UNSIGNED NOT NULL,
    docker_compose_path VARCHAR(500) DEFAULT 'docker-compose.yml',
    deployment_script TEXT NULL,
    environment_variables JSON NULL,
    storage_driver ENUM('local', 's3', 'gcs', 'dropbox', 'azure') DEFAULT 'local',
    storage_config JSON NULL,
    status ENUM('active', 'inactive', 'maintenance', 'failed') DEFAULT 'active',
    last_deployment_at TIMESTAMP NULL,
    health_check_url VARCHAR(500) NULL,
    health_check_interval INT DEFAULT 300, -- seconds
    auto_deploy BOOLEAN DEFAULT FALSE,
    backup_enabled BOOLEAN DEFAULT TRUE,
    monitoring_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_project_server (server_id),
    INDEX idx_project_status (status),
    INDEX idx_project_type (project_type),
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);

-- Server infrastructure
CREATE TABLE servers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    hostname VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    ssh_user VARCHAR(100) DEFAULT 'root',
    ssh_port INT DEFAULT 22,
    ssh_key_path VARCHAR(500) NULL,
    docker_installed BOOLEAN DEFAULT FALSE,
    nginx_proxy_manager_installed BOOLEAN DEFAULT FALSE,
    portainer_installed BOOLEAN DEFAULT FALSE,
    operating_system VARCHAR(100) NULL,
    cpu_cores INT NULL,
    ram_gb INT NULL,
    storage_gb INT NULL,
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
    last_health_check TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_server_status (status),
    INDEX idx_server_ip (ip_address)
);

-- Domain management
CREATE TABLE domains (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    domain VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) NULL,
    full_domain VARCHAR(255) GENERATED ALWAYS AS (
        CASE 
            WHEN subdomain IS NULL THEN domain
            ELSE CONCAT(subdomain, '.', domain)
        END
    ) STORED,
    ssl_enabled BOOLEAN DEFAULT TRUE,
    ssl_auto_renew BOOLEAN DEFAULT TRUE,
    ssl_certificate_path VARCHAR(500) NULL,
    ssl_expires_at TIMESTAMP NULL,
    dns_provider VARCHAR(100) NULL,
    dns_config JSON NULL,
    redirect_to VARCHAR(255) NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'pending', 'failed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_full_domain (full_domain),
    INDEX idx_domain_project (project_id),
    INDEX idx_domain_status (status),
    INDEX idx_ssl_expiry (ssl_expires_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Deployment tracking
CREATE TABLE deployments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    commit_hash VARCHAR(40) NOT NULL,
    commit_message TEXT NULL,
    branch VARCHAR(100) NOT NULL,
    triggered_by ENUM('manual', 'webhook', 'scheduled', 'rollback') NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'running', 'success', 'failed', 'rolled_back') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_seconds INT NULL,
    output TEXT NULL,
    error_message TEXT NULL,
    rollback_deployment_id BIGINT UNSIGNED NULL,
    environment_snapshot JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_deployment_project (project_id),
    INDEX idx_deployment_status (status),
    INDEX idx_deployment_date (created_at),
    INDEX idx_commit_hash (commit_hash),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (rollback_deployment_id) REFERENCES deployments(id) ON DELETE SET NULL
);

-- Storage analytics
CREATE TABLE storage_usage (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    storage_driver VARCHAR(50) NOT NULL,
    total_files INT DEFAULT 0,
    total_size_bytes BIGINT DEFAULT 0,
    cache_size_bytes BIGINT DEFAULT 0,
    logs_size_bytes BIGINT DEFAULT 0,
    uploads_size_bytes BIGINT DEFAULT 0,
    backup_size_bytes BIGINT DEFAULT 0,
    last_cleanup_at TIMESTAMP NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usage_project_date (project_id, recorded_at),
    INDEX idx_usage_driver (storage_driver),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### Core Service Architecture

#### Project Management Service
```php
<?php

declare(strict_types=1);

namespace App\Services\ProjectManager;

use App\Models\Project;
use App\Models\Deployment;
use Illuminate\Support\Collection;

class ProjectManagerService
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly DeploymentService $deploymentService,
        private readonly DomainService $domainService,
        private readonly StorageService $storageService,
        private readonly LogManagerService $logManager
    ) {}

    public function createProject(array $projectData): Project
    {
        $project = Project::create($projectData);
        
        // Initialize project structure
        $this->dockerService->initializeContainers($project);
        $this->domainService->setupDomain($project);
        $this->storageService->initializeStorage($project);
        
        return $project;
    }

    public function deployProject(Project $project, bool $forceDeploy = false): Deployment
    {
        return $this->deploymentService->deploy($project, $forceDeploy);
    }

    public function getProjectHealth(Project $project): array
    {
        return [
            'containers' => $this->dockerService->getContainerStatus($project),
            'domains' => $this->domainService->checkDomainHealth($project),
            'storage' => $this->storageService->getUsageStats($project),
            'last_deployment' => $project->deployments()->latest()->first(),
            'error_logs' => $this->logManager->getRecentErrors($project),
        ];
    }

    public function cleanupProject(Project $project): array
    {
        return [
            'cache_cleared' => $this->storageService->clearCache($project),
            'logs_rotated' => $this->logManager->rotateLogs($project),
            'temp_files_removed' => $this->storageService->cleanupTempFiles($project),
            'containers_cleaned' => $this->dockerService->cleanupContainers($project),
        ];
    }
}
```

#### Docker Orchestration Service
```php
<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;
use Illuminate\Support\Facades\Process;

class DockerService
{
    public function deployProject(Project $project): array
    {
        $projectPath = $this->getProjectPath($project);
        
        // Git pull latest changes
        $this->executeCommand("cd {$projectPath} && git pull origin {$project->branch}");
        
        // Update environment variables
        $this->updateEnvironmentFile($project);
        
        // Docker compose operations
        $this->executeCommand("cd {$projectPath} && docker-compose down --remove-orphans");
        $this->executeCommand("cd {$projectPath} && docker-compose pull");
        $this->executeCommand("cd {$projectPath} && docker-compose up -d --build");
        
        // Laravel-specific commands for Laravel projects
        if ($project->framework === 'laravel') {
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app composer install --optimize-autoloader --no-dev");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan config:cache");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan route:cache");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan view:cache");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan migrate --force");
        }
        
        return $this->getContainerStatus($project);
    }

    public function getContainerStatus(Project $project): array
    {
        $projectPath = $this->getProjectPath($project);
        $output = $this->executeCommand("cd {$projectPath} && docker-compose ps --format json");
        
        return json_decode($output, true) ?? [];
    }

    public function clearProjectCache(Project $project): bool
    {
        $projectPath = $this->getProjectPath($project);
        
        if ($project->framework === 'laravel') {
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan cache:clear");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan config:clear");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan route:clear");
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan view:clear");
            
            // Redis cache clear if Redis is configured
            $this->executeCommand("cd {$projectPath} && docker-compose exec -T redis redis-cli FLUSHDB", false);
        }
        
        return true;
    }

    public function restartServices(Project $project, array $services = []): array
    {
        $projectPath = $this->getProjectPath($project);
        
        if (empty($services)) {
            $this->executeCommand("cd {$projectPath} && docker-compose restart");
        } else {
            $serviceList = implode(' ', $services);
            $this->executeCommand("cd {$projectPath} && docker-compose restart {$serviceList}");
        }
        
        return $this->getContainerStatus($project);
    }

    private function executeCommand(string $command, bool $throwOnError = true): string
    {
        $result = Process::run($command);
        
        if ($throwOnError && !$result->successful()) {
            throw new \RuntimeException("Command failed: {$command}. Error: {$result->errorOutput()}");
        }
        
        return $result->output();
    }

    private function getProjectPath(Project $project): string
    {
        return config('devflow.projects_path') . '/' . $project->slug;
    }

    private function updateEnvironmentFile(Project $project): void
    {
        $projectPath = $this->getProjectPath($project);
        $envPath = $projectPath . '/.env';
        
        if ($project->environment_variables && file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            foreach ($project->environment_variables as $key => $value) {
                $pattern = "/^{$key}=.*/m";
                $replacement = "{$key}={$value}";
                
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, $replacement, $envContent);
                } else {
                    $envContent .= "\n{$replacement}";
                }
            }
            
            file_put_contents($envPath, $envContent);
        }
    }
}
```

### Livewire Component Architecture

#### Project Dashboard Component
```php
<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Services\ProjectManager\ProjectManagerService;
use Livewire\Component;
use Livewire\Attributes\{Computed, On};

class ProjectDashboard extends Component
{
    public string $searchTerm = '';
    public string $statusFilter = 'all';
    public string $frameworkFilter = 'all';
    
    public function __construct(
        private readonly ProjectManagerService $projectManager
    ) {}

    #[Computed]
    public function projects()
    {
        return Project::query()
            ->when($this->searchTerm, fn($q) => $q->where('name', 'like', "%{$this->searchTerm}%"))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->frameworkFilter !== 'all', fn($q) => $q->where('framework', $this->frameworkFilter))
            ->with(['domains', 'latestDeployment', 'server'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function (Project $project) {
                return array_merge($project->toArray(), [
                    'health' => $this->projectManager->getProjectHealth($project)
                ]);
            });
    }

    public function deployProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        
        try {
            $this->projectManager->deployProject($project);
            $this->dispatch('notification', type: 'success', message: "Deployment started for {$project->name}");
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Deployment failed: {$e->getMessage()}");
        }
    }

    public function cleanupProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        
        try {
            $results = $this->projectManager->cleanupProject($project);
            $this->dispatch('notification', type: 'success', message: "Project cleanup completed");
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Cleanup failed: {$e->getMessage()}");
        }
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        unset($this->projects);
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.dashboard.project-dashboard');
    }
}
```

#### Project Management Component
```php
<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\{Project, Server};
use App\Services\ProjectManager\ProjectManagerService;
use Livewire\Component;
use Livewire\Attributes\{Validate, Computed};

class ProjectManager extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|string|max:255|regex:/^[a-z0-9-]+$/')]
    public string $slug = '';
    
    #[Validate('required|url')]
    public string $repository_url = '';
    
    #[Validate('required|string|max:100')]
    public string $branch = 'main';
    
    #[Validate('required|in:laravel,shopware,symfony,wordpress,custom')]
    public string $framework = 'laravel';
    
    #[Validate('required|in:8.1,8.2,8.3,8.4')]
    public string $php_version = '8.4';
    
    #[Validate('required|in:single_tenant,multi_tenant,saas,microservice')]
    public string $project_type = 'single_tenant';
    
    #[Validate('required|exists:servers,id')]
    public int $server_id;
    
    #[Validate('nullable|string|max:500')]
    public string $docker_compose_path = 'docker-compose.yml';
    
    public array $environment_variables = [];
    
    public string $newEnvKey = '';
    public string $newEnvValue = '';
    
    public bool $showCreateForm = false;
    public bool $showEnvModal = false;

    public function __construct(
        private readonly ProjectManagerService $projectManager
    ) {}

    #[Computed]
    public function servers()
    {
        return Server::where('status', 'online')->get();
    }

    public function updatedName(): void
    {
        $this->slug = str($this->name)->slug()->toString();
    }

    public function addEnvironmentVariable(): void
    {
        if ($this->newEnvKey && $this->newEnvValue) {
            $this->environment_variables[$this->newEnvKey] = $this->newEnvValue;
            $this->reset('newEnvKey', 'newEnvValue');
        }
    }

    public function removeEnvironmentVariable(string $key): void
    {
        unset($this->environment_variables[$key]);
    }

    public function createProject(): void
    {
        $this->validate();
        
        try {
            $project = $this->projectManager->createProject([
                'name' => $this->name,
                'slug' => $this->slug,
                'repository_url' => $this->repository_url,
                'branch' => $this->branch,
                'framework' => $this->framework,
                'php_version' => $this->php_version,
                'project_type' => $this->project_type,
                'server_id' => $this->server_id,
                'docker_compose_path' => $this->docker_compose_path,
                'environment_variables' => $this->environment_variables,
            ]);
            
            $this->dispatch('project-created', projectId: $project->id);
            $this->dispatch('notification', type: 'success', message: 'Project created successfully!');
            $this->reset();
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to create project: {$e->getMessage()}");
        }
    }

    public function render()
    {
        return view('livewire.projects.project-manager');
    }
}
```

### Installation & Setup Guide

#### Quick VPS Installation Script
```bash
#!/bin/bash
# DevFlow Pro - One-Click VPS Installation Script

set -e

echo "🚀 DevFlow Pro - VPS Installation Starting..."

# Update system
apt update && apt upgrade -y

# Install required packages
apt install -y curl wget git nginx certbot python3-certbot-nginx \
               software-properties-common apt-transport-https ca-certificates \
               gnupg lsb-release supervisor redis-server

# Install Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
apt update && apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Install Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Install PHP 8.4
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.4-fpm php8.4-cli php8.4-common php8.4-curl php8.4-zip \
               php8.4-gd php8.4-mysql php8.4-mbstring php8.4-xml php8.4-redis \
               php8.4-intl php8.4-bcmath php8.4-soap php8.4-imagick

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt install -y nodejs

# Install MySQL
apt install -y mysql-server
mysql_secure_installation

# Create DevFlow directory structure
mkdir -p /opt/devflow/{projects,backups,logs,ssl}
chown -R www-data:www-data /opt/devflow
chmod -R 755 /opt/devflow

# Clone DevFlow Pro
cd /opt/devflow
git clone https://github.com/your-repo/devflow-pro.git manager
cd manager

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Set permissions
chown -R www-data:www-data /opt/devflow/manager
chmod -R 755 /opt/devflow/manager
chmod -R 777 /opt/devflow/manager/storage
chmod -R 777 /opt/devflow/manager/bootstrap/cache

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database (interactive)
echo "📝 Configuring database..."
read -p "Enter MySQL root password: " mysql_password
mysql -u root -p$mysql_password -e "CREATE DATABASE devflow_pro;"
mysql -u root -p$mysql_password -e "CREATE USER 'devflow'@'localhost' IDENTIFIED BY 'secure_password_here';"
mysql -u root -p$mysql_password -e "GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'localhost';"
mysql -u root -p$mysql_password -e "FLUSH PRIVILEGES;"

# Run migrations and seeders
php artisan migrate
php artisan db:seed

# Setup Nginx configuration
cat > /etc/nginx/sites-available/devflow-pro << 'EOF'
server {
    listen 80;
    listen 443 ssl http2;
    server_name devflow.your-domain.com;
    root /opt/devflow/manager/public;
    index index.php;

    # SSL Configuration (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/devflow.your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/devflow.your-domain.com/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
ln -s /etc/nginx/sites-available/devflow-pro /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# Setup Supervisor for queue workers
cat > /etc/supervisor/conf.d/devflow-worker.conf << 'EOF'
[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/devflow/manager/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/devflow/logs/worker.log
stdout_logfile_maxbytes=100MB
stdout_logfile_backups=5
EOF

supervisorctl reread && supervisorctl update && supervisorctl start devflow-worker:*

# Setup crontab for scheduled tasks
(crontab -l 2>/dev/null; echo "* * * * * cd /opt/devflow/manager && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Install Portainer for Docker management
docker volume create portainer_data
docker run -d -p 9443:9443 --name portainer --restart=always \
    -v /var/run/docker.sock:/var/run/docker.sock \
    -v portainer_data:/data \
    portainer/portainer-ce:latest

echo "✅ DevFlow Pro installation completed!"
echo "📊 Access your management panel at: https://devflow.your-domain.com"
echo "🐳 Access Portainer at: https://your-domain.com:9443"
echo "📝 Please update your domain configuration and SSL certificates"
echo "🔐 Default admin credentials - Email: admin@devflow.com, Password: DevFlow@2025"
```

### Key Features Implementation

#### 1. Automated Git Deployment
```php
<?php

namespace App\Services\Deployment;

class GitDeploymentService
{
    public function deployFromGit(Project $project, string $commitHash = null): Deployment
    {
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'commit_hash' => $commitHash ?? $this->getLatestCommitHash($project),
            'branch' => $project->branch,
            'triggered_by' => 'manual',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $projectPath = config('devflow.projects_path') . '/' . $project->slug;
            
            // Create project directory if it doesn't exist
            if (!is_dir($projectPath)) {
                $this->cloneRepository($project, $projectPath);
            }
            
            // Pull latest changes
            $this->executeGitCommand($projectPath, "git fetch origin {$project->branch}");
            $this->executeGitCommand($projectPath, "git reset --hard origin/{$project->branch}");
            
            // Run deployment scripts
            $this->runDeploymentScript($project, $projectPath);
            
            $deployment->update([
                'status' => 'success',
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($deployment->started_at),
            ]);
            
        } catch (\Exception $e) {
            $deployment->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            
            throw $e;
        }
        
        return $deployment;
    }

    private function runDeploymentScript(Project $project, string $projectPath): void
    {
        // Framework-specific deployment
        match($project->framework) {
            'laravel' => $this->deployLaravel($project, $projectPath),
            'shopware' => $this->deployShopware($project, $projectPath),
            'symfony' => $this->deploySymfony($project, $projectPath),
            default => $this->deployGeneric($project, $projectPath),
        };
    }

    private function deployLaravel(Project $project, string $projectPath): void
    {
        // Stop existing containers
        $this->executeCommand("cd {$projectPath} && docker-compose down");
        
        // Update environment
        $this->updateEnvironmentFile($project, $projectPath);
        
        // Build and start containers
        $this->executeCommand("cd {$projectPath} && docker-compose up -d --build");
        
        // Laravel specific commands
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app composer install --optimize-autoloader --no-dev");
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan config:cache");
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan route:cache");
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan view:cache");
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan migrate --force");
        
        // Clear caches
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan cache:clear");
        
        // Restart queue workers
        $this->executeCommand("cd {$projectPath} && docker-compose restart queue horizon", false);
    }
}
```

#### 2. Multi-Tenant Project Support
```php
<?php

namespace App\Services\MultiTenant;

class MultiTenantManager
{
    public function deployTenantUpdate(Project $project, array $tenantIds = []): array
    {
        $results = [];
        
        if ($project->project_type !== 'multi_tenant') {
            throw new \InvalidArgumentException('Project is not multi-tenant');
        }
        
        // Get all tenants or specific ones
        $tenants = empty($tenantIds) ? $this->getAllTenants($project) : $tenantIds;
        
        foreach ($tenants as $tenantId) {
            try {
                $results[$tenantId] = $this->deployToTenant($project, $tenantId);
            } catch (\Exception $e) {
                $results[$tenantId] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    private function deployToTenant(Project $project, string $tenantId): array
    {
        $projectPath = config('devflow.projects_path') . '/' . $project->slug;
        
        // Run tenant-specific migrations
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan migrate --force --database=tenant_{$tenantId}");
        
        // Clear tenant-specific caches
        $this->executeCommand("cd {$projectPath} && docker-compose exec -T app php artisan cache:clear --tenant={$tenantId}");
        
        // Restart tenant-specific services if needed
        $this->executeCommand("cd {$projectPath} && docker-compose restart tenant-{$tenantId}", false);
        
        return ['status' => 'success', 'timestamp' => now()];
    }
}
```

This comprehensive DevFlow Pro system provides complete project management capabilities with Docker integration, automated deployments, domain management, and specialized support for multi-tenant applications like your ATS Pro system.
- to memorize