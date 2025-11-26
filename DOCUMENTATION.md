# DevFlow Pro - Complete Documentation

**Version:** 2.5.3
**Last Updated:** November 26, 2025

This is the comprehensive documentation combining user guides, deployment instructions, features overview, and troubleshooting.

---

## 📚 Table of Contents

1. [Quick Start](#quick-start)
2. [Installation & Deployment](#installation--deployment)
3. [User Guide](#user-guide)
4. [Features Overview](#features-overview)
5. [Docker Management](#docker-management)
6. [Environment Management](#environment-management)
7. [Advanced Features](#advanced-features)
   - [Kubernetes Integration](#kubernetes-integration)
   - [CI/CD Pipelines](#cicd-pipelines)
   - [Custom Scripts](#custom-deployment-scripts)
   - [Notifications](#notification-system)
   - [Multi-Tenant](#multi-tenant-management)
8. [Troubleshooting](#troubleshooting)
9. [API Reference](#api-reference)
10. [Best Practices](#best-practices)

---

## 🚀 Quick Start

### For New Users

**First Time Setup (5 Minutes):**

1. **Access Your Installation:**
   ```
   Main Hub: http://nilestack.duckdns.org
   Admin Panel: http://admin.nilestack.duckdns.org
   Direct IP: http://31.220.90.121
   ```

2. **Create Admin User:**
   ```bash
   cd /var/www/devflow-pro
   php artisan tinker
   >>> \App\Models\User::create([
   ...     'name' => 'Admin',
   ...     'email' => 'admin@example.com',
   ...     'password' => bcrypt('your-secure-password'),
   ... ]);
   ```

3. **Login & Add Server:**
   - Click "Servers" → "Add Current Server" (automatic)
   - Or manually add remote servers

4. **Create Your First Project:**
   - Click "Projects" → "Create Project"
   - Fill in repository URL (SSH recommended for private repos)
   - Select framework and PHP/Node version
   - Click "Create"

5. **Deploy:**
   - Click "Deploy" button
   - Watch real-time progress
   - Your app is live!

---

## 📦 Installation & Deployment

### VPS Server Deployment

**Prerequisites:**
- Ubuntu 20.04+ / Debian 11+
- Root or sudo access
- Clean server (recommended)

**Method 1: Quick Deployment (Recommended)**

```bash
# On your local machine (where you have the code):
cd /path/to/DEVFLOW_PRO
./quick-deploy.sh
```

This single command will:
1. Upload and run server setup script
2. Install all dependencies (Nginx, PHP 8.2, MySQL, Redis, Node.js, Supervisor)
3. Deploy the application
4. Configure web server and services
5. Start queue workers

**Method 2: Manual Step-by-Step**

```bash
# Step 1: Setup Server (run on VPS)
ssh root@your-server-ip
bash <(curl -s https://your-repo/setup-server.sh)

# Step 2: Deploy Application (run on local machine)
cd /path/to/DEVFLOW_PRO
./deploy.sh
```

**Method 3: Manual Installation**

```bash
# 1. Clone repository on server
ssh root@your-server-ip
cd /var/www
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# 2. Install PHP dependencies
composer install --optimize-autoloader --no-dev

# 3. Install Node dependencies
npm install
npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate
nano .env  # Edit database credentials

# 5. Run migrations
php artisan migrate --force

# 6. Publish assets
php artisan livewire:publish --assets

# 7. Set permissions
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# 8. Configure Nginx (see CREDENTIALS.md for config)
nano /etc/nginx/sites-available/devflow-pro

# 9. Restart services
systemctl restart nginx php8.2-fpm
supervisorctl restart all
```

**Post-Installation:**

```bash
# Create first admin user
php artisan tinker
>>> \App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'admin@devflow.local',
...     'password' => bcrypt('ChangeThisPassword123!'),
... ]);

# Access application
Portfolio: http://nilestack.duckdns.org
Admin Panel: http://admin.nilestack.duckdns.org
Direct IP: http://31.220.90.121
```

---

## 👤 User Guide

### Managing Servers

**Adding Servers:**

**Method 1 - Auto-detect Current Server:**
1. Go to "Servers" page
2. Click "⚡ Add Current Server"
3. Server automatically detected with all specs

**Method 2 - Add Remote Server:**
1. Click "+ Add Server"
2. Fill in details:
   - Name, IP address, SSH port
   - Username (usually 'root')
   - Optional: SSH private key
3. Click "Add Server"
4. Connection tested automatically

**Server Actions:**
- **Ping** - Test connectivity and detect Docker
- **View** - See server details and metrics
- **Docker Management** - Access Docker dashboard (when Docker detected)

### Creating Projects

**Step-by-Step:**

1. **Navigate:** Projects → Create Project

2. **Basic Info:**
   - Name: Your project name
   - Slug: Auto-generated URL-friendly name

3. **Repository:**
   - URL: `git@github.com:user/repo.git` (SSH recommended)
   - Branch: `main` or your deployment branch

4. **Framework & Runtime:**
   - Select framework (Laravel, Static, React, Node.js, etc.)
   - Choose PHP version (8.4, 8.3, 8.2, etc.)
   - Choose Node version if needed

5. **Build Config:**
   - Root directory (usually `/`)
   - Build command (e.g., `npm run build`)
   - Start command (optional, Docker handles this)

6. **Click "Create Project"**

**SSH Setup for Private Repos:**

```bash
# Get server's SSH public key
ssh root@your-server
cat /root/.ssh/id_rsa.pub

# Add to GitHub:
# 1. Go to https://github.com/settings/keys
# 2. Click "New SSH key"
# 3. Paste the public key
# 4. Save

# Use SSH URL in project:
git@github.com:username/repo.git
```

### Deploying Applications

**First Deployment:**

1. Open project page
2. Click "🚀 Deploy" button
3. Confirm deployment
4. Watch real-time progress:
   - Cloning repository
   - Building Docker image
   - Running migrations (Laravel)
   - Starting container
   - Running optimizations
5. Deployment complete! ✅

**Deployment Process (Laravel):**

```bash
# Automatic steps for Laravel projects:
1. git pull/clone repository
2. composer install --optimize-autoloader --no-dev
3. npm install && npm run build
4. docker build -t project-slug:latest .
5. docker stop project-slug (if exists)
6. docker rm -f project-slug (if exists)
7. docker run -d --name project-slug \
   -e APP_ENV=production \
   -e APP_DEBUG=false \
   -e DB_HOST=172.17.0.1 \
   project-slug:latest
8. Inside container:
   - php artisan config:cache
   - php artisan route:cache
   - php artisan view:cache
   - php artisan event:cache
   - php artisan migrate --force
   - php artisan storage:link
   - php artisan optimize
```

**Redeployment:**

After pushing code changes:
1. Go to project page
2. Click "Check for Updates" (compares with GitHub)
3. If behind, click "Deploy Latest"
4. New deployment starts
5. Old container replaced with new one

---

## ✨ Features Overview

### Project Management
- Create, edit, delete projects
- Multi-framework support (Laravel, Node.js, React, Vue, Static, etc.)
- Git integration (HTTPS & SSH)
- Branch management
- Commit tracking and update notifications
- Environment management (Local/Dev/Staging/Prod)
- Custom environment variables with secure storage

### Server Management
- Multi-server support
- Real-time monitoring (CPU, RAM, Disk)
- Auto-discovery (one-click current server addition)
- SSH connectivity testing
- Docker detection and management
- Server health checks

### Docker Features
- **Smart Dockerfile Detection** - Uses your Dockerfile if exists
- **Project-Specific Docker Management** - Each project has isolated Docker control
- **Auto Conflict Resolution** - Handles container name conflicts automatically
- **Resource Monitoring** - Real-time CPU, Memory, Network, Disk I/O stats
- **Container Management** - Start, stop, restart, backup containers
- **Image Management** - Build, view, delete project-specific images
- **Logs Viewer** - Terminal-style log streaming (50-500 lines)
- **Volume & Network Management** - Full Docker orchestration

### Deployment System
- Real-time deployment progress (0-100%)
- Live log streaming with auto-scroll
- Extended timeout (20 minutes for large builds)
- Automatic Laravel optimization (8 commands)
- Smart Git operations (pull vs clone - 10-20x faster)
- Deployment history with commit tracking
- Rollback capability

### UI/UX Features
- Modern gradient design
- Tabbed project interface (Overview/Docker/Environment/Git/Deployments)
- Dark theme with toggle and persistence
- Real-time updates with Livewire
- Mobile responsive
- Clickable project cards and server rows
- Loading overlays with status indicators

### User Management
- User CRUD operations
- Role-based access (Admin, Manager, User)
- Secure authentication
- Invite-only registration (self-service disabled)

---

## 🐳 Docker Management

### Project-Specific Docker Control

**Location:** Project Page → Docker Management Section

**Three Tabs:**

**1. Overview Tab**
- Container status (Running/Stopped/Not Found)
- Real-time stats (CPU, Memory, Network I/O, Disk I/O)
- Container actions (Start/Stop/Restart/Backup)
- Quick project stats

**2. Images Tab**
- Lists Docker images related to this project only
- Shows: Repository, Tag, ID, Created, Size
- Actions: Build new image, Delete image
- Filtered by project slug for security

**3. Logs Tab**
- Real-time container logs (Docker output) or Laravel application logs
- Terminal-style display (green text on dark background)
- Adjustable line count (100-1000)
- One-click refresh
- **Clear Logs button** - Clears Laravel logs for fresh error capture (with confirmation dialog)

**Common Tasks:**

**Start Container:**
```
1. Go to project page
2. Docker Management → Overview tab
3. Click "▶️ Start Container"
4. Wait for success message
```

**View Logs:**
```
1. Docker Management → Logs tab
2. Select line count (100 recommended)
3. Click "🔄 Refresh Logs"
4. Scroll through to find errors
```

**Cleanup Old Images:**
```
1. Docker Management → Images tab
2. Identify old/unused images
3. Click "🗑️ Delete" on old image
4. Confirm deletion
```

**Backup Container:**
```
1. Overview tab → Running container
2. Click "💾 Backup Container"
3. Backup image created with timestamp
4. Check Images tab to verify
```

### Automatic Conflict Resolution

**Problem:** Container name already in use
**Solution:** Automatic cleanup! ⭐

The system now automatically:
1. Detects existing container with same name
2. Stops old container
3. Removes old container with force flag
4. Starts new container
5. No manual intervention needed!

---

## ⚙️ Environment Management

### Application Environment Selection

**4 Environment Types:**

**🏠 Local** (Development Machine)
- APP_ENV: local
- APP_DEBUG: true (detailed errors)
- Best for: Local development

**💻 Development** (Dev Server)
- APP_ENV: development  
- APP_DEBUG: true (stack traces)
- Best for: Team development, testing features

**🔧 Staging** (Pre-Production)
- APP_ENV: staging
- APP_DEBUG: false (errors logged only)
- Best for: QA testing, client previews

**🚀 Production** (Live Users)
- APP_ENV: production
- APP_DEBUG: false (secure, no error exposure)
- Best for: Production deployments

**How to Change:**
1. Project page → Environment tab
2. Click desired environment card
3. Confirm change
4. **Important:** Restart container to apply!

### Server .env File Management (NEW in v2.5.3)

**View and Edit Server .env Directly:**

The Environment tab now shows the actual `.env` file from your server, allowing you to:
- View all environment variables currently set on the server
- Edit existing variables directly
- Add new variables to the server .env file
- Delete variables from the server

**How to Use:**

1. Go to Project → Environment tab
2. The "Server .env File" section shows all variables from `/var/www/project-slug/.env`
3. Click **Edit** next to any variable to modify it
4. Click **Add Variable** to add new variables
5. Click **Delete** to remove variables
6. Click **Refresh** to reload the current server state

**Security Features:**
- 🔒 Sensitive values (PASSWORD, SECRET, KEY, TOKEN) are masked
- 🔒 Changes are made via secure SSH connection
- 🔒 Confirmation required before delete operations

**Note:** Changes take effect immediately on the server. You may need to clear config cache (`php artisan config:clear`) or restart the container for Laravel to pick up new values.

### Custom Environment Variables (Database)

These variables are stored in DevFlow Pro's database and injected during deployment.

**Pre-Configured Variables (Automatic):**
- APP_ENV, APP_DEBUG, APP_KEY
- DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- CACHE_STORE, SESSION_DRIVER, QUEUE_CONNECTION

**Adding Custom Variables:**

1. Environment tab → "Add Variable" button (in Environment Variables section)
2. Enter name and value:
   ```
   Variable Name: API_KEY
   Value: your-api-key-here
   ```
3. Click "Add Variable"
4. **Re-deploy to apply** (these are injected during deployment)

**Security Features:**
- 🔒 Values encrypted in database
- 🔒 Passwords automatically masked (••••••••)
- 🔒 Per-project isolation
- 🔒 Never committed to git

**Variable Injection Flow:**

```
Configure in DevFlow Pro
    ↓
Saves to database
    ↓
Deploy/Restart Container
    ↓
Variables injected via docker run -e
    ↓
Application accesses via env()
```

---

## 🚀 Advanced Features

DevFlow Pro v2.5 introduces enterprise-grade features for modern DevOps workflows.

### ☸️ Kubernetes Integration

**Overview:**
Deploy and manage applications on Kubernetes clusters directly from DevFlow Pro.

**Getting Started:**
1. Navigate to **Advanced → Kubernetes** in the navigation menu
2. Click "Add Cluster" to configure your K8s cluster:
   ```yaml
   Name: Production Cluster
   API Server URL: https://k8s.example.com:6443
   Namespace: default
   Kubeconfig: [Paste your kubeconfig content]
   ```
3. Test connection to verify setup
4. Select a project and click "Deploy to K8s"

**Features:**
- **Multi-Cluster Support** - Manage development, staging, and production clusters
- **Namespace Management** - Organize deployments by namespace
- **Pod Monitoring** - Real-time status, logs, and metrics
- **Scaling Controls** - Horizontal pod autoscaling
- **Rolling Updates** - Zero-downtime deployments
- **Helm Integration** - Deploy using Helm charts
- **Secret Management** - Encrypted kubeconfig storage

**Deployment Process:**
```
Select Project → Choose Cluster → Configure Resources → Deploy
    ↓
Generate K8s Manifests
    ↓
Apply to Cluster
    ↓
Monitor Pod Status
```

### 🔧 CI/CD Pipelines

**Overview:**
Visual pipeline builder supporting multiple CI/CD providers.

**Supported Providers:**
- GitHub Actions
- GitLab CI/CD
- Bitbucket Pipelines
- Jenkins

**Creating a Pipeline:**
1. Go to **Advanced → CI/CD Pipelines**
2. Click "Create Pipeline"
3. Select your provider
4. Configure stages:
   ```yaml
   Build → Test → Deploy
   ```
5. Add jobs to each stage
6. Configure triggers (push, PR, schedule)
7. Save and execute

**Pipeline Features:**
- **Visual Editor** - Drag-and-drop pipeline configuration
- **Template Library** - Pre-built pipelines for common scenarios
- **Parallel Execution** - Run multiple jobs concurrently
- **Artifact Storage** - Share files between jobs
- **Environment Variables** - Secure secret management
- **Webhook Integration** - Automatic triggers
- **YAML Export** - Version control your pipelines

**Example Pipeline:**
```yaml
stages:
  - build
  - test
  - deploy

build:
  script:
    - npm install
    - npm run build
  artifacts:
    paths:
      - dist/

test:
  script:
    - npm run test
    - npm run lint

deploy:
  script:
    - docker build -t app:latest .
    - docker push registry/app:latest
  only:
    - main
```

### 📜 Custom Deployment Scripts

**Overview:**
Create and manage custom deployment scripts in multiple languages.

**Supported Languages:**
- Bash/Shell
- Python
- PHP
- Node.js
- Ruby

**Creating Scripts:**
1. Navigate to **Advanced → Deployment Scripts**
2. Click "Create Script"
3. Configure:
   ```bash
   Name: Laravel Deployment
   Language: Bash
   Timeout: 300 seconds
   ```
4. Write your script with template variables:
   ```bash
   #!/bin/bash
   echo "Deploying @{{PROJECT_NAME}} from branch @{{BRANCH}}"
   cd @{{PROJECT_PATH}}
   git pull origin @{{BRANCH}}
   composer install --no-dev
   php artisan migrate --force
   php artisan config:cache
   ```
5. Save and test

**Available Variables:**
- `@{{PROJECT_NAME}}` - Project name
- `@{{PROJECT_SLUG}}` - Project slug
- `@{{BRANCH}}` - Current branch
- `@{{COMMIT_HASH}}` - Latest commit
- `@{{TIMESTAMP}}` - Current timestamp
- `@{{DOMAIN}}` - Primary domain
- `@{{PROJECT_PATH}}` - Project directory

**Script Features:**
- **Version Control** - Track changes with rollback
- **Execution History** - Detailed logs and output
- **Error Handling** - Automatic retry on failure
- **Scheduled Execution** - Cron-based scheduling
- **Template Library** - Reusable script templates
- **Secure Execution** - Sandboxed environment

### 🔔 Notification System

**Overview:**
Real-time notifications for deployment events and system alerts.

**Supported Channels:**
- Slack
- Discord
- Microsoft Teams
- Custom Webhooks

**Setting Up Notifications:**
1. Go to **Advanced → Notifications**
2. Click "Add Channel"
3. Configure channel:
   ```
   Name: Team Slack
   Provider: Slack
   Webhook URL: https://hooks.slack.com/services/XXX
   ```
4. Select events to monitor:
   - Deployment Started
   - Deployment Completed
   - Deployment Failed
   - Health Check Failed
   - SSL Expiring
5. Test notification
6. Enable channel

**Notification Features:**
- **Rich Formatting** - Markdown, embeds, attachments
- **Custom Templates** - Create reusable messages
- **Event Filtering** - Choose which events to receive
- **Delivery Tracking** - Monitor notification status
- **Silent Hours** - Configure quiet periods
- **Fallback Channels** - Secondary notification methods

**Example Slack Message:**
```json
{
  "text": "Deployment Status",
  "attachments": [{
    "color": "good",
    "title": "Deployment Successful",
    "fields": [
      {"title": "Project", "value": "My App"},
      {"title": "Environment", "value": "Production"},
      {"title": "Version", "value": "v2.5.0"},
      {"title": "Duration", "value": "2m 15s"}
    ]
  }]
}
```

### 🏢 Multi-Tenant Management

**Overview:**
Manage multi-tenant applications with isolated deployments.

**Key Concepts:**
- **Tenant** - Isolated instance with own database/storage
- **Master Project** - Base application code
- **Tenant Deployment** - Deploying updates to tenants

**Setting Up Multi-Tenancy:**
1. Mark project as multi-tenant:
   ```
   Project Settings → Type: Multi-Tenant
   ```
2. Navigate to **Advanced → Multi-Tenant**
3. Select your project
4. Create tenants:
   ```
   Name: Customer A
   Subdomain: customer-a
   Database: tenant_customer_a
   Plan: Enterprise
   ```
5. Deploy to tenants

**Tenant Features:**
- **Database Isolation** - Separate database per tenant
- **Storage Isolation** - Dedicated storage paths
- **Custom Configuration** - Per-tenant environment variables
- **Bulk Operations** - Deploy to multiple tenants
- **Resource Quotas** - Limit CPU/memory per tenant
- **Usage Analytics** - Track resource consumption
- **Backup/Restore** - Tenant-specific backups
- **Migration Tools** - Move tenants between servers

**Deployment Strategies:**
1. **Sequential** - Deploy one tenant at a time
2. **Parallel** - Deploy to multiple tenants simultaneously
3. **Canary** - Deploy to subset first
4. **Blue-Green** - Zero-downtime tenant updates

**Tenant Management Commands:**
```bash
# Create tenant
php artisan tenant:create customer-a

# Deploy to all tenants
php artisan tenant:deploy --all

# Backup specific tenant
php artisan tenant:backup customer-a

# Reset tenant data
php artisan tenant:reset customer-a
```

---

## 🐛 Troubleshooting

### Critical Issues & Quick Fixes

#### ❌ Livewire Actions Not Working (500 Errors)

**Symptoms:** Buttons don't work, 500 errors
**Cause:** Livewire assets not published
**Fix:**
```bash
cd /var/www/devflow-pro
php artisan livewire:publish --assets
systemctl restart php8.2-fpm
# Browser: Ctrl + Shift + R (hard refresh)
```

#### ❌ Docker Container Can't Connect to MySQL

**Symptoms:** `host.docker.internal` connection failed
**Cause:** On Linux, must use Docker bridge IP
**Fix:**
```bash
# Use 172.17.0.1 instead of host.docker.internal
# In project environment variables:
DB_HOST=172.17.0.1

# Grant MySQL access from Docker network:
mysql -e "GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'172.17.%';"
```

#### ❌ Changes Not Showing After Deployment

**Symptoms:** Old version still displays
**Cause:** Browser cache
**Fix:**
- Hard refresh: `Ctrl + Shift + R` (Windows/Linux)
- Or: `Cmd + Shift + R` (Mac)
- Or: Test in incognito window

### Common Issues

**"Unable to find image 'xxx:latest'"**
- Solution: Deploy first, then start
- Order: CREATE → DEPLOY → START

**"Permission denied" on deployment**
- Check: `/var/www/` permissions
- Fix: `chown -R www-data:www-data /var/www/devflow-pro`

**"Git clone failed: could not read Username"**
- Private repo without SSH key
- Fix: Add server's SSH public key to GitHub
- See: "SSH Setup for Private Repos" section

**"Port already in use"**
- Another container using the port
- System now auto-resolves conflicts
- Or: Use unique ports per project

**"Build failed"**
- Check deployment logs for errors
- Common: Missing dependencies, syntax errors
- Fix code issues and redeploy

**"Method not found on component"**
- Livewire cache stale
- Fix:
  ```bash
  composer dump-autoload --optimize
  php artisan optimize:clear
  systemctl restart php8.2-fpm
  ```

### Debug Commands

**Check Services:**
```bash
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status redis-server
systemctl status supervisor
```

**View Logs:**
```bash
# Application logs
tail -f /var/www/devflow-pro/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

**Docker Debugging:**
```bash
# List containers
docker ps -a

# View container logs
docker logs project-slug

# Inspect container
docker inspect project-slug

# Execute command in container
docker exec -it project-slug bash
```

---

## 🔌 API Reference

### Authentication

All API requests require authentication via Bearer token.

**Get Token:**
```bash
POST /api/login
{
  "email": "admin@example.com",
  "password": "your-password"
}
```

**Use Token:**
```bash
Authorization: Bearer your-token-here
```

### Endpoints

**Servers:**
```
GET    /api/servers           # List all servers
POST   /api/servers           # Create server
GET    /api/servers/{id}      # Get server details
PUT    /api/servers/{id}      # Update server
DELETE /api/servers/{id}      # Delete server
POST   /api/servers/{id}/ping # Test connection
```

**Projects:**
```
GET    /api/projects          # List all projects
POST   /api/projects          # Create project
GET    /api/projects/{id}     # Get project details
PUT    /api/projects/{id}     # Update project
DELETE /api/projects/{id}     # Delete project
POST   /api/projects/{id}/deploy    # Deploy project
POST   /api/projects/{id}/start     # Start container
POST   /api/projects/{id}/stop      # Stop container
```

**Deployments:**
```
GET    /api/deployments              # List deployments
GET    /api/deployments/{id}         # Get deployment details
GET    /api/projects/{id}/deployments # Project deployments
```

**Webhooks:**
```
POST   /api/webhooks/github/{token}  # GitHub webhook
POST   /api/webhooks/gitlab/{token}  # GitLab webhook
```

---

## ✅ Best Practices

### Development Workflow

**1. Project Setup:**
- ✅ Use SSH URLs for private repositories
- ✅ Add server's SSH key to GitHub first
- ✅ Choose appropriate environment (Dev/Staging/Prod)
- ✅ Configure environment variables before first deploy

**2. Deployment:**
- ✅ Test in staging before production
- ✅ Monitor deployment logs
- ✅ Verify application after deployment
- ✅ Check container logs for errors

**3. Docker Management:**
- ✅ Use project-specific Docker panel (not server-wide)
- ✅ Clean up old images weekly
- ✅ Backup containers before major updates
- ✅ Monitor resource usage (CPU/Memory)

**4. Environment Variables:**
- ✅ Use different secrets per environment
- ✅ Never commit .env files to git
- ✅ Rotate credentials regularly
- ✅ Mask sensitive values

**5. Security:**
- ✅ Change default passwords immediately
- ✅ Use strong passwords for database
- ✅ Keep SSH keys secure
- ✅ Regularly update system packages
- ✅ Monitor access logs
- ✅ Disable debug mode in production

### DO:
- ✅ Create projects in order: CREATE → CONFIGURE → DEPLOY → START
- ✅ Use unique ports for each project
- ✅ Enable auto-deploy for CI/CD workflows
- ✅ Regular server monitoring and maintenance
- ✅ Keep Docker images updated
- ✅ Backup important data regularly

### DON'T:
- ❌ Skip the deployment step (must deploy before start)
- ❌ Use same ports for multiple projects
- ❌ Delete projects with running containers (stop first)
- ❌ Forget to add SSH key for private repos
- ❌ Ignore failed deployment logs
- ❌ Run production with APP_DEBUG=true
- ❌ Share production credentials publicly

---

## 📞 Support & Resources

**Documentation:**
- README.md - Main documentation
- CREDENTIALS.md - Access information
- CHANGELOG.md - Version history
- TROUBLESHOOTING.md - Detailed troubleshooting

**Online Resources:**
- GitHub Repository: https://github.com/yourusername/devflow-pro
- GitHub Issues: Report bugs
- GitHub Discussions: Ask questions
- Email: support@devflowpro.com

**Quick Commands Reference:**

```bash
# Application
cd /var/www/devflow-pro
php artisan optimize:clear    # Clear all caches
php artisan optimize          # Optimize for production
php artisan migrate --force   # Run migrations

# Services
systemctl restart nginx php8.2-fpm supervisor

# Docker
docker ps -a                  # List all containers
docker logs project-slug      # View container logs
docker stats project-slug     # Monitor resources

# Deployment
./quick-deploy.sh            # Full deployment
./deploy.sh                  # Application only
```

---

## 🎯 Quick Reference

**Essential URLs:**
```
Dashboard:   /dashboard
Servers:     /servers
Projects:    /projects
Deployments: /deployments
```

**Project Lifecycle:**
```
CREATE → CONFIGURE → DEPLOY → START → RUNNING
```

**Common Ports:**
```
Nginx:      80 (HTTP), 443 (HTTPS)
MySQL:      3306
Redis:      6379
PHP-FPM:    Unix socket
Docker:     Bridge 172.17.0.1
```

---

## 🌐 Production Environment (November 25, 2025)

### Current Deployment Status

| Application | URL | Database | PHP | Status |
|------------|-----|----------|-----|--------|
| **Portfolio** | http://nilestack.duckdns.org | portfolio_db | 8.2 | ✅ Active |
| **DevFlow Pro** | http://admin.nilestack.duckdns.org | devflow_pro | 8.2 | ✅ Active |
| **ATS Pro** | http://ats.nilestack.duckdns.org | ats_pro | 8.3 | ✅ Configured |
| **Portainer** | https://nilestack.duckdns.org:9443 | - | - | ✅ Active |

### Infrastructure Details
- **Server:** 31.220.90.121 (Ubuntu 24.04.3 LTS)
- **Web Server:** Nginx 1.24.0
- **Database:** MySQL 8.0 (User: devflow_user)
- **Cache:** Redis 7.x
- **Queue:** Supervisor with Laravel workers

### Latest Updates (v2.5.1)
- ✅ Fixed project show page tabs - now using server-side Blade rendering
- ✅ Fixed Livewire component snapshot issues
- ✅ Fixed deploy button functionality
- ✅ Added @livewireStyles and @livewireScripts to layouts
- ✅ Advanced Features: Kubernetes, CI/CD, Scripts, Notifications, Multi-tenant
- ✅ Portfolio as main site on nilestack.duckdns.org
- ✅ DevFlow Pro on secure admin subdomain
- ✅ Unified MySQL user management
- ✅ PHP 8.3 support for ATS Pro
- ✅ All applications fully configured and operational

---

**Version:** 2.5.1 | **Last Updated:** November 26, 2025

Happy Deploying! 🚀
