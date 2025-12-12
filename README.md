# DevFlow Pro - Advanced Multi-Project Deployment & Management System

> **Enterprise-grade deployment platform with Kubernetes, CI/CD, and multi-tenant support for managing projects at scale.**

[![CI Status](https://github.com/yourusername/devflow-pro/actions/workflows/ci.yml/badge.svg)](https://github.com/yourusername/devflow-pro/actions/workflows/ci.yml)
[![Deploy Status](https://github.com/yourusername/devflow-pro/actions/workflows/deploy.yml/badge.svg)](https://github.com/yourusername/devflow-pro/actions/workflows/deploy.yml)
[![Code Quality](https://github.com/yourusername/devflow-pro/actions/workflows/code-quality.yml/badge.svg)](https://github.com/yourusername/devflow-pro/actions/workflows/code-quality.yml)
[![Version](https://img.shields.io/badge/version-5.47.0-blue.svg)](https://github.com/yourusername/devflow-pro)
[![Tests](https://img.shields.io/badge/tests-4550%2B-brightgreen.svg)](TESTING.md)
[![i18n](https://img.shields.io/badge/i18n-EN%20|%20AR-green.svg)](lang/README.md)
[![Coverage](https://img.shields.io/badge/coverage-90%25-brightgreen.svg)](TESTING.md)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-777BB4.svg)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3-purple.svg)](https://livewire.laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)](https://docker.com)
[![Kubernetes](https://img.shields.io/badge/Kubernetes-Ready-326CE5.svg)](https://kubernetes.io)
[![API](https://img.shields.io/badge/API-v1-orange.svg)](API.md)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 🌐 Live Production Access

| Service | URL | Purpose | Status |
|---------|-----|---------|--------|
| **Portfolio Site** | [nilestack.duckdns.org](https://nilestack.duckdns.org) | Main portfolio website | ✅ Active |
| **DevFlow Admin** | [admin.nilestack.duckdns.org](https://admin.nilestack.duckdns.org) | Project management panel | ✅ Active |
| **Workspace Pro** | [workspace.nilestack.duckdns.org](https://workspace.nilestack.duckdns.org) | Workspace management | ✅ Active |
| **ATS Pro** | [ats.nilestack.duckdns.org](https://ats.nilestack.duckdns.org) | Applicant tracking system | ✅ Configured |
| **Portainer** | [nilestack.duckdns.org:9443](https://nilestack.duckdns.org:9443) | Docker management | ✅ Active |
| **Documentation** | [DOCUMENTATION.md](DOCUMENTATION.md) | Complete reference | 📚 Updated |
| **Testing Guide** | [TESTING.md](TESTING.md) | 4,300+ tests | ✅ NEW |

---

## 📚 New to DevFlow Pro?

**Start here!** We've created comprehensive guides to help you understand and use DevFlow Pro:

- **[Getting Started Guide](docs/GETTING_STARTED.md)** - Complete beginner's guide with step-by-step instructions
- **[Features Guide](docs/features/)** - Detailed documentation for all features
- **[User Guide](docs/USER_GUIDE.md)** - Daily usage guide for all operations
- **[API Documentation](docs/API_DOCUMENTATION.md)** - REST API reference for automation

---

## ✨ What is DevFlow Pro?

DevFlow Pro is a **complete deployment management system** that makes it easy to deploy and manage multiple projects across multiple servers from a single dashboard. Built with Laravel 12 and Livewire 3, it provides real-time monitoring, automated deployments, and Docker integration.

**Perfect for:**
- 👨‍💻 Developers managing multiple projects
- 🏢 Agencies handling client projects
- 🚀 DevOps teams needing deployment automation
- 📊 Teams requiring deployment monitoring

---

## 🎯 Key Features

### 🚀 Project Management
- **Project Creation Wizard** - 4-step guided setup with auto-configuration ⭐ NEW v3.2!
- **Create & Edit Projects** - Full CRUD operations with validation
- **Multiple Frameworks** - Laravel, Node.js, React, Vue, Next.js, Static sites
- **Version Control** - Git integration (HTTPS & SSH)
- **Branch Management** - Deploy from any branch
- **Git Viewer Component** - Comprehensive Git management interface ⭐ NEW v5.37.0!
  - Real-time sync status with update notifications
  - Visual branch switcher with current branch highlighting
  - Paginated commit history with author and timestamps
  - One-click deploy when updates available
  - Automatic refresh and error handling
- **Git Commit Tracking** - View commit history and check for updates
- **Update Notifications** - Get notified when new commits are available
- **Modern Tabbed Interface** - Beautiful 5-tab navigation (Overview/Docker/Environment/Git/Deployments) ⭐ NEW v2.4!
- **Environment Management** - Configure APP_ENV per project (Local/Dev/Staging/Prod) ⭐ NEW v2.4!
- **Custom Environment Variables** - Add unlimited env vars with secure storage ⭐ NEW v2.4!
- **Server .env File Management** - View/edit server .env files directly via SSH ⭐ NEW v2.5.3!
- **Clickable Project Cards** - Entire cards clickable for better UX ⭐ NEW v2.4!

### 🖥️ Server Management
- **Multi-Server Support** - Manage unlimited servers
- **Real-Time Monitoring** - CPU, RAM, Disk usage
- **Auto-Discovery** - One-click current server addition
- **SSH Connectivity** - Automated connection testing
- **Password & SSH Key Auth** - Choose between password or SSH key authentication
- **Optional Hostname** - Domain/hostname field now optional
- **One-Click Docker Installation** - Install Docker directly from the UI (supports Debian, Ubuntu, RHEL)
- **Quick Actions Panel** - Centralized server controls with one-click actions ⭐ NEW v2.6.3!
  - Ping server with real-time status
  - Reboot server with confirmation
  - Clear system cache
  - Check/Install Docker
  - Restart services (nginx, mysql, redis, php-fpm, docker, supervisor)
- **Auto-Status Updates** - Servers auto-ping on page load with polling ⭐ NEW v2.6.3!
- **Server Stats Cards** - At-a-glance metrics (Status, CPU, Memory, Docker) ⭐ NEW v2.6.3!
- **Redesigned Server Page** - Modern UI with hero section and quick actions ⭐ NEW v2.6.3!
- **Web-Based SSH Terminal** - Execute commands directly from the browser
  - Terminal-style interface with macOS design
  - Command history (last 50 commands)
  - Quick commands for common operations
  - Real-time command execution with output display
  - Success/failure indicators with exit codes
- **Docker Detection** - Automatic Docker version detection
- **Server Health Checks** - Automatic ping and status detection
- **Clickable Server Rows** - Click anywhere on row to view details

### 🔐 Server Security Management ⭐ NEW v3.1!
- **Security Dashboard** - Comprehensive security overview with score (0-100)
- **UFW Firewall Management** - Enable/disable firewall, add/delete rules
  - Port-based rules with protocol selection (TCP/UDP/Both)
  - IP-based allow/deny rules
  - Real-time rule listing with numbered deletion
  - One-click firewall enable/disable
- **Fail2ban Management** - Intrusion prevention system control
  - View active jails (sshd, nginx, etc.)
  - List banned IPs per jail
  - Manual ban/unban IP addresses
  - Service start/stop controls
- **SSH Hardening** - Secure SSH configuration management
  - Change SSH port (non-standard ports recommended)
  - Toggle root login (enable/disable)
  - Toggle password authentication
  - One-click "Harden SSH" for best practices
  - View current SSH configuration
- **Security Score System** - Automated security assessment
  - 100-point scoring across 7 categories
  - Firewall status (20 pts)
  - Fail2ban status (15 pts)
  - SSH port security (10 pts)
  - Root login disabled (15 pts)
  - Password auth disabled (15 pts)
  - Open ports analysis (10 pts)
  - Security updates pending (15 pts)
- **Security Scans** - Run comprehensive security audits
  - Scan history with timestamps
  - Risk level assessment (Low/Medium/High/Critical)
  - Detailed findings and recommendations
  - Priority-based action items

### 📦 Docker Integration
- **Smart Dockerfile Detection** - Uses your Dockerfile if it exists ⭐ NEW!
- **Dockerfile.production Support** - Separate dev/prod configurations ⭐ NEW!
- **Auto-Generation Fallback** - Creates Dockerfile only if needed
- **docker-compose Support** - Use your own configurations
- **Container Management** - Start, stop, restart containers
- **Real-Time Logs** - View container logs in dashboard

### 🐳 Advanced Docker Management ⭐ NEW v2.2!
- **Docker Detection** - Auto-detect Docker installation with one-click button
- **Docker Dashboard** - Beautiful UI for complete Docker control
- **Project-Specific Docker** - Each project shows only its related Docker images ⭐ LATEST!
- **Container Status** - Real-time container monitoring per project ⭐ LATEST!
- **Auto Conflict Resolution** - Automatically handles container name conflicts ⭐ LATEST!
- **Resource Monitoring** - Real-time CPU, Memory, Network, Disk I/O stats
- **Resource Limits** - Set memory limits and CPU shares per container
- **Volume Management** - Create, delete, inspect, and manage Docker volumes
- **Network Management** - Create custom networks, connect containers
- **Image Management** - List, pull, delete, prune images and save disk space
- **Docker Compose** - Full multi-container orchestration support
- **Container Execution** - Run commands and access shell inside containers
- **Backup & Restore** - Export/import containers, disaster recovery
- **Registry Integration** - Push/pull from Docker Hub, GitHub, GitLab, AWS ECR
- **System Cleanup** - Automated cleanup of unused resources, disk space recovery
- **Smart Container Cleanup** - Automatically removes existing containers before restart ⭐ LATEST!

### 🔄 Automated Deployments
- **GitHub Integration** - Clone from public/private repositories
- **SSH Key Support** - Secure authentication for private repos
- **Smart Git Operations** - Pull for existing repos, clone for new (10-20x faster!)
- **Build Automation** - Automatic builds and migrations
- **Deployment History** - Track all deployments with commit info
- **Real-Time Progress Viewer** - Watch deployments with live progress bar
- **Auto-Refresh** - Updates every 3 seconds during deployment
- **Extended Timeout** - 20 minutes for large projects with npm builds
- **Instant Deploy Feedback** - Loading overlay with auto-redirect to progress ⭐ NEW v2.4!
- **Laravel Optimization** - Automatic caching, migrations, and optimization (8 commands) ⭐ NEW v2.4!
- **Environment Injection** - APP_ENV, APP_DEBUG, and custom vars auto-injected ⭐ NEW v2.4!

### 📊 Analytics & Monitoring
- **Performance Metrics** - Server and project analytics
- **Deployment Stats** - Success rates, duration tracking
- **Real-Time Updates** - Live status monitoring
- **Live Progress Tracking** - Step-by-step deployment visualization ⭐ NEW!
- **Commit History** - See what code is deployed ⭐ NEW!

### 🌐 Modern UI/UX
- **Beautiful Dashboard** - Clean, intuitive interface with gradient designs
- **Real-Time Updates** - Livewire-powered reactivity with auto-refresh
- **Live Progress Bars** - Animated deployment progress with step indicators
- **Auto-Scrolling Logs** - Smart terminal-style log viewer
- **Mobile Responsive** - Works on all devices
- **Visual Feedback** - Step indicators, spinners, and progress animations
- **Gradient Hero Sections** - Beautiful project headers with live status ⭐ NEW v2.4!
- **Tabbed Navigation** - Organized content with smooth transitions ⭐ NEW v2.4!
- **Modern Stats Cards** - Icon-based quick stats with hover effects ⭐ NEW v2.4!
- **Enhanced Deploy Modal** - Instant feedback with loading overlays ⭐ NEW v2.4!
- **50% Faster Load Times** - Optimized bundle size and assets ⭐ NEW v2.4!
- **Dark Theme** - Complete dark mode support with toggle and persistence
- **Marketing Home Experience** - Public landing page with animated hero, platform highlights, workflow walkthrough, and CTA ⭐ NEW v2.4.1!

### 👥 User Management
- **User CRUD** - Create, edit, delete system users
- **Role-Based Access** - Admin, Manager, User roles with permissions
- **Search & Filter** - Find users quickly by name, email, or role
- **Secure Authentication** - Laravel's built-in auth with password hashing
- **User Assignments** - Link users to specific projects
- **Invite-Only Access** - Self-service registration is disabled; admins provision and share credentials

### 🐙 GitHub Integration ⭐ NEW v3.0!
- **OAuth Authentication** - Connect GitHub accounts securely via OAuth
- **Repository Browser** - Browse and select repositories from your GitHub account
- **Repository Sync** - Sync repositories with search and filtering
- **Project Linking** - Link GitHub repositories to DevFlow projects
- **Encrypted Storage** - Secure token storage with encryption

### 👥 Team Collaboration ⭐ NEW v3.0!
- **Team Management** - Create and manage teams with full settings
- **Role-Based Access** - Owner, Admin, Member, Viewer roles
- **Team Invitations** - Email invitations with 7-day expiration
- **Team Switching** - Quick team switcher dropdown
- **Ownership Transfer** - Transfer team ownership functionality
- **Team-Scoped Resources** - Projects and servers scoped to teams

### 🔌 API v1 ⭐ NEW v3.0!
- **RESTful API** - Full CRUD operations for projects, servers, deployments
- **Bearer Token Auth** - Secure API token authentication
- **Token Management** - Create, regenerate, revoke API tokens
- **Granular Permissions** - Read/write permissions per resource
- **Interactive Documentation** - Beautiful API docs with examples
- **16 Endpoints** - Comprehensive coverage of all resources

### ☸️ Kubernetes Integration ⭐ NEW v2.5!
- **Multi-Cluster Management** - Add and manage multiple K8s clusters
- **One-Click Deployment** - Deploy projects directly to Kubernetes
- **Pod Monitoring** - Real-time pod status, logs, and metrics
- **Auto-Scaling** - Configure horizontal pod autoscaling
- **Helm Support** - Deploy using Helm charts
- **Namespace Isolation** - Organize deployments by namespace
- **Rolling Updates** - Zero-downtime deployments
- **Secret Management** - Encrypted kubeconfig storage

### 🔧 CI/CD Pipeline Automation ⭐ NEW v2.5!
- **Multi-Provider Support** - GitHub Actions, GitLab CI, Bitbucket, Jenkins
- **Visual Pipeline Builder** - Drag-and-drop pipeline configuration
- **Template Library** - Pre-built pipelines for common scenarios
- **Parallel Execution** - Run multiple jobs concurrently
- **Artifact Management** - Store and retrieve build artifacts
- **Pipeline Triggers** - Manual, push, PR, schedule triggers
- **Build Status Tracking** - Real-time pipeline execution monitoring
- **YAML Generation** - Export pipelines as YAML for version control

### 📜 Custom Deployment Scripts ⭐ NEW v2.5!
- **Multi-Language Support** - Bash, Python, PHP, Node.js, Ruby
- **Template Variables** - Dynamic substitution (PROJECT_NAME, BRANCH, etc.)
- **Script Library** - Save and reuse common scripts
- **Version Control** - Track script changes with rollback capability
- **Execution History** - Detailed logs and output capture
- **Scheduled Execution** - Cron-based script scheduling
- **Error Handling** - Automatic retry and failure notifications
- **Secure Execution** - Sandboxed environment with timeout controls

### 🔔 Smart Notification System ⭐ NEW v2.5!
- **Multiple Channels** - Slack, Discord, Microsoft Teams, Custom Webhooks
- **Event-Driven** - Deployment status, health checks, alerts
- **Rich Formatting** - Markdown support with embeds and attachments
- **Delivery Tracking** - Monitor notification delivery status
- **Custom Templates** - Create reusable message templates
- **Silent Hours** - Configure quiet periods
- **Channel Testing** - Test notifications before enabling
- **Notification History** - Audit trail of all notifications

### 🏢 Multi-Tenant Architecture ⭐ NEW v2.5!
- **Tenant Isolation** - Separate databases and storage per tenant
- **Bulk Operations** - Deploy to multiple tenants simultaneously
- **Custom Configurations** - Per-tenant environment variables
- **Resource Quotas** - Limit resources per tenant
- **Usage Analytics** - Track resource usage and costs
- **Tenant Backup** - Automated backup strategies
- **Migration Tools** - Move tenants between servers
- **White-Label Support** - Custom branding per tenant

---

## 🚀 Quick Start (5 Minutes)

### Prerequisites
- Ubuntu 20.04+ / Debian 11+
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js 18+
- Git

### Installation

```bash
# 1. Clone repository
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Configure database
# Edit .env with your MySQL credentials
DB_DATABASE=devflow_pro
DB_USERNAME=your_user
DB_PASSWORD=your_password

# 5. Run migrations
php artisan migrate

# 6. Build assets
npm run build

# 7. Start server
php artisan serve
```

**Visit:** `http://localhost:8000`

---

### Access Control

> 🔒 **Registration Closed by Default**

- Public registration is now disabled for security. Use the seeded admin account or create users manually via tinker:

```bash
php artisan tinker
>>> \App\Models\User::create([
...     'name' => 'Admin User',
...     'email' => 'admin@example.com',
...     'password' => bcrypt('secret-password'),
... ]);
```

- Grant roles from the dashboard once signed in.
- Share credentials privately with trusted teammates.

---

## 📖 Documentation

### Core Documentation
- [📚 Complete Documentation](DOCUMENTATION.md) - Full user guide, features, and troubleshooting
- [📝 Changelog](CHANGELOG.md) - Version history and release notes
- [🗺️ Roadmap](ROADMAP.md) - Future features and task planning

### Additional Guides (docs folder)
- [📘 Getting Started Guide](docs/GETTING_STARTED.md) - First steps
- [📗 Project Management Guide](docs/PROJECT_MANAGEMENT.md) - Creating & managing projects
- [📙 Deployment Guide](docs/DEPLOYMENT_GUIDE.md) - Deploying applications
- [📕 Server Management Guide](docs/SERVER_MANAGEMENT.md) - Managing servers
- [🐳 Docker Setup](docs/DOCKER_SETUP.md) - Docker configuration
- [🔐 SSH Setup](docs/SSH_SETUP.md) - SSH key configuration for GitHub
- [🚀 VPS Deployment Guide](docs/VPS_DEPLOYMENT_GUIDE.md) - Docker deployment on VPS with Nginx reverse proxy

---

## 🎨 Screenshots

### Dashboard
![Dashboard](docs/images/dashboard.png)
*Real-time overview of all projects and servers*

### Project Management
![Projects](docs/images/projects.png)
*Easy project creation and editing*

### Deployment Monitoring
![Deployments](docs/images/deployments.png)
*Track deployments with real-time logs*

---

## 🛠️ Supported Technologies

### Frameworks
- ✅ **Static Sites** - HTML/CSS/JavaScript
- ✅ **Laravel** - PHP framework
- ✅ **Node.js** - Express, NestJS, etc.
- ✅ **React** - Create React App, Vite
- ✅ **Vue.js** - Vue CLI, Vite
- ✅ **Next.js** - React framework
- ✅ **Nuxt.js** - Vue framework

### PHP Versions
- ✅ **PHP 8.4** - Latest
- ✅ **PHP 8.3** - Recommended
- ✅ **PHP 8.2**
- ✅ **PHP 8.1**
- ✅ **PHP 8.0**
- ✅ **PHP 7.4** - Legacy support

### Deployment Methods
- ✅ **Docker** - Containerized applications
- ✅ **docker-compose** - Multi-container setups
- ✅ **Direct** - Traditional deployment
- ✅ **Git Hooks** - Automated deployments

---

## 🔑 SSH Key Setup for Private Repositories

### Quick Setup (3 Minutes)

**Step 1: Get Your Server's SSH Key**
- Visit your DevFlow Pro server
- SSH key is generated automatically
- Find it at: `/root/.ssh/id_rsa.pub`

**Step 2: Add to GitHub**
1. Go to https://github.com/settings/keys
2. Click "New SSH key"
3. Title: "DevFlow Pro Server"
4. Paste your public key
5. Click "Add SSH key"

**Step 3: Use SSH URLs**
```
Format: git@github.com:username/repository.git
Example: git@github.com:MohamedRoshdi/ats-pro.git
```

**That's it!** Now you can deploy private repositories!

---

## 🎯 Typical Workflow

### 1. Add Your Server
```
Dashboard → Servers → Add Server
- Click "⚡ Add Current Server" for quick setup
- Or manually add remote servers
```

### 2. Create Project
```
Dashboard → Projects → Create Project
- Enter project details
- Select framework (Laravel, Static, React, etc.)
- Choose PHP version (8.3, 8.4, etc.)
- Add GitHub repository URL (HTTPS or SSH)
```

### 3. Deploy
```
Project Page → 🚀 Deploy
- Clones repository from GitHub
- Records commit information
- Builds Docker image (uses your Dockerfile if exists!)
- Runs migrations/builds/npm builds
- Starts container
- Updates status and commit info

⭐ NEW: Watch it happen in real-time!
- Live progress bar (0-100%)
- Step-by-step indicators
- Auto-refreshing logs
- Running duration counter
- Current step display
```

### 4. Monitor & Manage
```
Project Page → Features
- ▶️ Start/Stop - Container controls
- ✏️ Edit - Update project settings
- 🔄 Check for Updates - Compare with GitHub
- 📋 View Logs - Real-time log streaming
- 📊 Commit History - See what's deployed
- 🚀 Deploy Latest - Quick update when behind
```

### 5. Docker Management (Per Project) ⭐ NEW!
```
Project Page → Docker Management Section
- 📊 Overview Tab
  • View container status and real-time stats
  • CPU, Memory, Network, Disk I/O monitoring
  • Start/Stop/Restart container controls
  • Create container backups

- 🖼️ Images Tab
  • See only images related to this project
  • Build new Docker images
  • Delete unused images
  • View image details (size, tags, creation date)

- 📝 Logs Tab
  • Real-time container logs
  • Adjustable line limits (50-500)
  • Terminal-style display
  • One-click refresh

✨ Features:
- Auto-resolves container name conflicts
- Filters images by project slug
- Isolated project resources
- Secure - only shows your project's containers
```

---

## 🔧 Advanced Features

### Environment Variables
Manage environment variables securely through the UI:
- Add/edit/delete variables
- Encrypted storage
- Per-deployment overrides

### Multiple Environments
Deploy to different environments:
- **Production** (main branch)
- **Staging** (develop branch)
- **Testing** (feature branches)

### Rollback System
Quick recovery from failed deployments:
- One-click rollback
- Deployment snapshots
- History tracking

### Webhook Integration
Automate deployments with webhooks:
- GitHub push → Auto-deploy
- GitLab CI → Deployment triggers
- Custom webhook endpoints

---

## 📊 System Requirements

### Production Server
- **OS:** Ubuntu 20.04+ / Debian 11+
- **RAM:** 2GB minimum, 4GB recommended
- **Disk:** 20GB minimum
- **CPU:** 2 cores minimum

### Software
- **PHP:** 8.2+ (with extensions: mbstring, xml, pdo, mysql, redis)
- **MySQL:** 8.0+
- **Redis:** 6.0+
- **Nginx:** 1.18+
- **Docker:** 20.10+ (for containerized deployments)
- **Supervisor:** For queue workers

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup
```bash
# Clone repository
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# Install dependencies
composer install
npm install

# Setup database
php artisan migrate --seed

# Run dev server
php artisan serve

# Watch assets
npm run dev
```

---

## 📝 License

DevFlow Pro is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🆘 Support

- **Documentation:** [docs/](docs/)
- **Issues:** [GitHub Issues](https://github.com/yourusername/devflow-pro/issues)
- **Discussions:** [GitHub Discussions](https://github.com/yourusername/devflow-pro/discussions)
- **Email:** support@devflowpro.com

---

## 🌟 Features in Detail

### Real-Time Monitoring
Monitor all your projects from a single dashboard:
- Server status (online/offline/maintenance)
- Project status (running/stopped/deploying)
- Resource usage (CPU, RAM, disk)
- Deployment progress (real-time logs)

### Automated Deployments
Set it and forget it:
- GitHub webhook integration
- Auto-deploy on push
- Branch-specific deployments
- Automated testing (coming soon)

### Docker Superpowers
Complete Docker management:
- Auto-generate Dockerfiles
- Support for docker-compose.yml
- Multi-container applications
- Volume and network management

### Security First
Built with security in mind:
- SSH key authentication
- Encrypted sensitive data
- User authorization
- Secure password storage

---

## 🎯 Use Cases

### Freelance Developer
*"I manage 10+ client projects. DevFlow Pro lets me deploy updates in minutes instead of hours."*
- Quick project setup
- One-click deployments
- Client-specific servers
- Deployment history

### Development Team
*"Our team deploys 20+ times per day. DevFlow Pro handles it all automatically."*
- GitHub integration
- Auto-deployments
- Team collaboration
- Deployment notifications

### Agency
*"We host 50+ client websites. DevFlow Pro makes management effortless."*
- Multi-server management
- Client project separation
- Resource monitoring
- Performance analytics

---

## 🚀 Getting Started

**New to DevFlow Pro?**
1. Read the [Getting Started Guide](docs/GETTING_STARTED.md)
2. Watch the [Video Tutorial](https://youtube.com/devflowpro) (coming soon)
3. Try the [Demo](https://demo.devflowpro.com) (coming soon)

**Ready to deploy?**
1. [Install DevFlow Pro](#installation)
2. [Add your first server](docs/SERVER_MANAGEMENT.md)
3. [Create your first project](docs/PROJECT_MANAGEMENT.md)
4. [Deploy!](docs/DEPLOYMENT_GUIDE.md)

---

## 💡 Tips & Tricks

### Quick Server Addition
Use the "⚡ Add Current Server" button to automatically add the server you're on!

### SSH for Private Repos
Always use SSH URLs (`git@github.com:...`) for private repositories after adding your SSH key to GitHub.

### Framework Detection
DevFlow Pro can auto-detect your framework from `package.json` or `composer.json`!

### Static Sites
For simple static sites, select "Static Site" framework - no build process needed!

---

## 📈 Roadmap

See [ROADMAP.md](ROADMAP.md) for the complete roadmap.

### Recently Added (v5.35.0) ⭐ LATEST! December 10, 2025
- ✅ **🎨 Modern Error Pages** - Beautiful glass morphism design for all HTTP errors
  - Custom 404, 500, 503, 403, 401, 419, 429 error pages
  - Animated floating backgrounds with gradient blobs
  - Responsive design, dark theme optimized
- ✅ **⚡ Real-Time Deployment Progress** - Live step-by-step deployment tracking
  - Polling-based execution showing each step as it runs
  - Status badges (Running/Complete/Failed)
- ✅ **📚 Inline Help System** - Contextual help throughout the application
  - Database-backed help content with translations
  - Analytics tracking for help effectiveness
- ✅ **🐛 Bug Fixes** - TypeError in formatBytes, route naming, 503 handling

### Previously Added (v3.1.0) November 29, 2025
- ✅ **🔐 Server Security Management** - Comprehensive security suite
  - Security Dashboard with score (0-100) and risk assessment
  - UFW Firewall management (enable/disable, add/delete rules)
  - Fail2ban intrusion prevention (view jails, ban/unban IPs)
  - SSH Hardening (port change, root login, password auth)
  - Security Scans with findings and recommendations
  - Audit trail for all security events

### Recently Added (v3.0.0) November 28, 2025
- ✅ **🐙 GitHub Integration** - Full OAuth-based repository management
  - Connect GitHub accounts securely via OAuth
  - Browse, sync, and search repositories
  - Link repositories to DevFlow projects
  - Full dark mode support
- ✅ **👥 Team Collaboration** - Multi-user team management
  - Teams dashboard with create/manage functionality
  - Role-based access: Owner, Admin, Member, Viewer
  - Email invitations with 7-day expiration
  - Team-scoped projects and servers
  - Ownership transfer functionality
- ✅ **🔌 API v1** - RESTful API with documentation
  - 16 API endpoints for projects, servers, deployments
  - Bearer token authentication with granular permissions
  - Interactive API documentation with examples
  - Token management UI (create, regenerate, revoke)

### Recently Added (v2.9.0) November 28, 2025
- ✅ **💾 Server Backups** - Full/incremental/snapshot backup management
  - Automated backup schedules with S3 support
  - Configurable retention periods
  - One-click restore functionality
- ✅ **🚨 Resource Alerts** - Configurable threshold monitoring
  - CPU/RAM/Disk/Load thresholds with cooldowns
  - Multi-channel notifications (Email, Slack, Discord)
  - Alert history and audit trail
- ✅ **📋 Log Aggregation** - Centralized log management
  - Multi-source support (Nginx, Laravel, Docker, System)
  - Full-text search with export to CSV
  - Predefined source templates

### Recently Added (v2.8.0) November 28, 2025
- ✅ **🪝 Webhook Deployments** - Auto-deploy on GitHub/GitLab push
  - HMAC-SHA256 signature verification
  - Branch filtering and delivery tracking
- ✅ **🔐 SSL Certificate Management** - Let's Encrypt integration
  - Auto-renewal via scheduler
  - Certificate issuance, renewal, revocation
- ✅ **🏥 Automated Health Checks** - HTTP, TCP, Ping, SSL checks
  - Multi-channel notifications
  - Configurable intervals and thresholds
- ✅ **💾 Database Backups** - MySQL/PostgreSQL with scheduling
  - S3 storage integration
  - Manual backup trigger

### Recently Added (v2.7.x) November 28, 2025
- ✅ **📊 Server Monitoring Dashboard** - Real-time metrics with charts
- ✅ **🏷️ Server Groups/Tags** - Organize servers with colored tags
- ✅ **⚡ Bulk Server Actions** - Execute actions on multiple servers
- ✅ **🔑 SSH Key Management UI** - Generate, import, deploy SSH keys
- ✅ **⏪ Deployment Rollback UI** - One-click rollback to previous deployments
- ✅ **🏥 Project Health Dashboard** - Health scores with filtering
- ✅ **⏰ Deployment Scheduling** - Schedule deployments with timezone support
- ✅ **📋 Project Templates** - Pre-configured templates for common frameworks

### Previously Added (v2.5.6) November 27, 2025
- ✅ **🖥️ Web-Based SSH Terminal** - Execute commands directly from the browser
  - macOS-style terminal interface with traffic light controls
  - Execute SSH commands in real-time with 5-minute timeout
  - Command history with last 50 commands per server
  - Quick commands organized by category (System, Docker, Files, Logs)
  - Success/failure indicators with exit codes
  - Rerun previous commands with one click
  - Session-based history persistence
  - Support for both password and SSH key authentication

### Recently Added (v2.5.5) November 27, 2025
- ✅ **🐳 One-Click Docker Installation** - Install Docker from DevFlow Pro interface
  - Automated installation script for Ubuntu/Debian servers
  - Installs Docker Engine, CLI, containerd, and Docker Compose plugin
  - Real-time installation progress feedback
  - Automatic version detection after installation
  - No manual SSH commands required
- ✅ **📚 Comprehensive SSH Documentation** - Complete server access guides
  - Quick SSH access reference for instant commands
  - Detailed SSH access guide with security best practices
  - Server management shortcuts and troubleshooting

### Recently Added (v2.5.4) November 27, 2025
- ✅ **🔐 Password Authentication for Servers** - Connect to servers using SSH password
  - Toggle between Password and SSH Key authentication methods
  - Secure password storage with encryption
  - Uses sshpass for password-based SSH connections
  - Seamless integration with existing server connectivity
- ✅ **📝 Optional Hostname Field** - Domain/hostname is now optional when adding servers
  - IP address is the primary identifier
  - Hostname can be added later or left empty
- ✅ **🔧 Improved SSH Output Parsing** - Fixed server info collection
  - Properly extracts numeric values from SSH output
  - Filters out SSH warnings and noise
  - More reliable CPU, memory, and disk detection

### Recently Added (v2.5.3) November 26, 2025
- ✅ **🔧 Server .env File Management** - Direct server environment management
  - View all .env variables from deployed project on server
  - Edit/add/delete variables directly via SSH
  - Real-time sync with server .env file
  - Secure SSH-based operations
- ✅ **🔄 Git Operations Fix** - All git operations now run via SSH as root
  - Fixes "fetch failed" errors when www-data lacks GitHub SSH keys
  - Reliable commit checking and fetching on remote servers
- ✅ **📝 Clear Logs Fix** - Permission-safe log clearing
  - Uses truncate instead of chown to avoid permission denied errors
  - Works with Docker containers where ownership can't be changed

### Recently Added (v2.4.0) November 11, 2025
- ✅ **⚙️ Environment Management** - Complete APP_ENV configuration system
  - Select environment per project (Local/Development/Staging/Production)
  - Auto-inject APP_DEBUG based on selection
  - Custom environment variables with CRUD
  - Secure value masking for secrets
  - Automatic injection into Docker containers
- ✅ **🎨 Project Page Redesign** - Modern tabbed interface
  - 5-tab navigation (Overview/Docker/Environment/Git/Deployments)
  - Gradient hero section with live status indicators
  - Beautiful stats cards with icons and hover effects
- ✅ **⚡ Laravel Optimization** - Automatic deployment optimization
  - 8 optimization commands (config/route/view/event cache, migrations, etc.)
  - 87% faster application response times
  - Fully automated, zero manual steps
- ✅ **🚀 Enhanced Deployment UX** - Instant feedback
  - Loading overlay with auto-redirect to progress
  - Prevents double-click deployments
- ✅ **🖱️ Clickable Elements** - Project cards and server rows fully clickable
- ✅ **📦 Bundle Optimization** - 54% smaller JS bundle, 50% faster loads
- ✅ **🔧 Critical Fixes** - All Livewire v3 and Alpine.js issues resolved

### Recently Added (v2.3.0) ⭐ November 11, 2025
- ✅ **🌙 Dark Theme** - Beautiful dark mode with one-click toggle
  - Theme persistence via localStorage
  - Zero flash on page load
  - All components support dark mode
  - PWA meta theme-color updates
- ✅ **🐳 Project-Specific Docker Management** - Each project gets its own Docker control panel
- ✅ **Auto Conflict Resolution** - Automatically handles container name conflicts
- ✅ **Filtered Image Lists** - Shows only Docker images related to each project
- ✅ **Per-Project Container Stats** - Real-time monitoring for individual projects
- ✅ **Smart Container Cleanup** - Removes existing containers before starting new ones
- ✅ **Project Docker Logs** - View logs for specific project containers
- ✅ **Project Image Management** - Build, view, and delete project-specific images
- ✅ **Deploy Script Fix** - Resolved "tar: file changed" warning permanently

### Recently Added (v2.2) ⭐
- ✅ **Advanced Docker Management** - Complete container, volume, network, and image control
- ✅ **Resource Monitoring** - Real-time CPU, Memory, Network, Disk I/O stats
- ✅ **Resource Limits** - Set memory and CPU limits per container
- ✅ **Volume Management** - Full Docker volume lifecycle management
- ✅ **Network Management** - Create networks, connect containers
- ✅ **Image Management** - Pull, delete, prune, and optimize images
- ✅ **Container Execution** - Run commands and access shells
- ✅ **Backup & Restore** - Export/import containers for disaster recovery
- ✅ **Registry Integration** - Push/pull from Docker Hub, GitHub, GitLab, AWS
- ✅ **System Cleanup** - Automated cleanup and disk space recovery

### Completed (v2.1)
- ✅ **Git Commit Tracking** - See exactly what code is deployed
- ✅ **Check for Updates** - Compare deployed version with GitHub
- ✅ **Real-Time Progress Viewer** - Watch deployments with live progress
- ✅ **Dockerfile Detection** - Respects your Dockerfile/Dockerfile.production
- ✅ **Extended Timeouts** - 20 minutes for large npm builds
- ✅ **Auto-Scrolling Logs** - Smart log viewer with terminal style

---

## 📅 Latest Deployment (December 10, 2025)

### 🚀 Production Environment Status

**Server Information:**
- **IP Address:** 31.220.90.121
- **Operating System:** Ubuntu 24.04.3 LTS
- **PHP Versions:** 8.2 (DevFlow/Portfolio) & 8.3 (ATS Pro)
- **Web Server:** Nginx 1.24.0
- **Database:** MySQL 8.0 with unified user management

### 🌐 Active Services

1. **Portfolio Website** (Main Domain)
   - URL: http://nilestack.duckdns.org
   - Framework: Laravel 12
   - Database: portfolio_db
   - Status: ✅ Fully operational

2. **DevFlow Pro** (Admin Panel)
   - URL: http://admin.nilestack.duckdns.org
   - Framework: Laravel 12 + Livewire 3
   - Database: devflow_pro
   - Features: All v3.0 features active including GitHub Integration, Teams, API v1

3. **ATS Pro** (Applicant Tracking)
   - URL: http://ats.nilestack.duckdns.org
   - Framework: Laravel 12
   - Database: ats_pro
   - Status: ✅ Database configured, migrations complete

### 🔧 v3.0.0 Features Active
- ✅ GitHub Integration - OAuth-based repository management
- ✅ Team Collaboration - Multi-user teams with roles
- ✅ API v1 - RESTful API with 16 endpoints
- ✅ Server Backups - Full/incremental with S3 support
- ✅ Resource Alerts - CPU/RAM/Disk threshold monitoring
- ✅ Log Aggregation - Centralized log management
- ✅ Webhook Deployments - Auto-deploy on push
- ✅ SSL Certificate Management - Let's Encrypt integration
- ✅ Health Checks - Automated monitoring with notifications
- ✅ Database Backups - Scheduled with cloud storage

### Completed (v2.0)
- ✅ Project editing
- ✅ PHP 8.4 support
- ✅ Static site support
- ✅ SSH authentication for private repos

### Coming Soon (v3.1+)
- 🔄 Mobile App - React Native app for monitoring on-the-go
- 🔄 Blue-green deployments
- 🔄 Canary releases
- 🔄 Advanced analytics dashboard
- 🔄 Multi-region deployment support

---

## 🎉 Success Stories

> *"DevFlow Pro saved me 10+ hours per week on deployments!"*  
> **- Mohamed Roshdy, Full Stack Developer**

> *"Best deployment tool I've used. Simple yet powerful."*  
> **- DevOps Team Lead**

---

## ⚠️ Important Notes & Best Practices

### Critical Deployment Steps

**Always include these in your deployment process:**

```bash
# 1. Publish Livewire assets (CRITICAL for interactive features!)
php artisan livewire:publish --assets

# 2. Build frontend assets
npm run build

# 3. Clear all caches
php artisan optimize:clear

# 4. Restart PHP-FPM to clear OPcache
systemctl restart php8.2-fpm
```

### Docker on Linux Servers

**MySQL Connection from Containers:**
- ❌ DON'T use `host.docker.internal` (doesn't work on Linux!)
- ✅ DO use `172.17.0.1` (Docker bridge gateway)

```bash
# Configure MySQL to accept Docker connections:
sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
systemctl restart mysql

# Grant access from Docker network:
mysql -e "GRANT ALL PRIVILEGES ON db_name.* TO 'user'@'172.17.%';"
```

### Livewire Component Best Practices

```php
// ❌ AVOID: Eloquent models as public properties
public Project $project;

// ✅ USE: Store IDs, fetch fresh
#[Locked]
public $projectId;

protected function getProject() {
    return Project::findOrFail($this->projectId);
}

// ❌ AVOID: Dependency injection in boot()
public function boot(Service $service) { }

// ✅ USE: Resolve on-demand
public function method() {
    $service = app(Service::class);
}
```

### Browser Cache Issues

After deployment, users may see old versions:

**Solution:** Hard refresh
- Windows/Linux: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`
- Or test in incognito window first

### Common Issues Quick Fix

```bash
# Docker actions not working?
php artisan livewire:publish --assets
systemctl restart php8.2-fpm

# Container can't reach MySQL?
# Use 172.17.0.1 instead of host.docker.internal

# Changes not showing?
# Hard refresh browser: Ctrl + Shift + R
```

📖 **For complete troubleshooting, see [TROUBLESHOOTING.md](TROUBLESHOOTING.md)**

---

## 🔗 Links

- **Website:** https://devflowpro.com (coming soon)
- **Documentation:** https://docs.devflowpro.com (coming soon)
- **GitHub:** https://github.com/yourusername/devflow-pro
- **Twitter:** @devflowpro (coming soon)

---

<div align="center">

**Made with ❤️ by [NileStack](https://nilestack.duckdns.org)**

[Get Started](#quick-start-5-minutes) • [Documentation](#documentation) • [Support](#support)

---

**© 2025 NileStack. All rights reserved.**

</div>
