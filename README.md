<div align="center">

# DevFlow Pro

**Advanced Multi-Project Deployment & Management System**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9.svg)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)](https://docker.com)
[![Kubernetes](https://img.shields.io/badge/Kubernetes-Ready-326CE5.svg)](https://kubernetes.io)

[Features](#features) • [Installation](#installation) • [Documentation](#documentation) • [Contributing](#contributing) • [Support](#support)

</div>

---

## What is DevFlow Pro?

DevFlow Pro is an enterprise-grade deployment management platform that makes it easy to deploy, monitor, and manage multiple projects across multiple servers from a single dashboard. Built with Laravel 12 and Livewire 3, it provides real-time monitoring, automated deployments, Docker orchestration, and Kubernetes integration.

**Perfect for:**
- Developers managing multiple projects
- Agencies handling client projects
- DevOps teams needing deployment automation
- Teams requiring comprehensive monitoring and analytics

---

## Features

### Project Management
- **Multi-Framework Support** - Laravel, Node.js, React, Vue, Next.js, Nuxt.js, Static sites
- **Project Creation Wizard** - Guided 4-step setup with auto-configuration
- **Git Integration** - HTTPS and SSH support for public and private repositories
- **Branch Management** - Deploy from any branch with visual branch switcher
- **Environment Management** - Configure APP_ENV (Local/Dev/Staging/Production) per project
- **Custom Environment Variables** - Add unlimited env vars with secure storage
- **Modern Tabbed Interface** - Beautiful 5-tab navigation (Overview/Docker/Environment/Git/Deployments)

### Server Management
- **Multi-Server Support** - Manage unlimited servers from one dashboard
- **Real-Time Monitoring** - CPU, RAM, Disk usage with live updates
- **Auto-Discovery** - One-click current server addition
- **Multiple Authentication Methods** - Password and SSH key authentication
- **One-Click Docker Installation** - Install Docker directly from the UI
- **Web-Based SSH Terminal** - Execute commands directly from the browser
- **Server Health Checks** - Automatic ping and status detection
- **Quick Actions Panel** - Centralized server controls (reboot, clear cache, restart services)

### Security Management
- **Security Dashboard** - Comprehensive security overview with 0-100 scoring
- **UFW Firewall Management** - Enable/disable firewall, manage rules
- **Fail2ban Integration** - Intrusion prevention with ban/unban controls
- **SSH Hardening** - Change port, disable root login, configure authentication
- **Security Scans** - Automated audits with findings and recommendations
- **Security Score System** - 100-point scoring across 7 categories

### Docker Integration
- **Smart Dockerfile Detection** - Uses existing Dockerfiles or auto-generates
- **docker-compose Support** - Multi-container orchestration
- **Container Management** - Start, stop, restart, view logs
- **Resource Monitoring** - Real-time CPU, Memory, Network, Disk I/O stats
- **Resource Limits** - Set memory limits and CPU shares per container
- **Volume & Network Management** - Complete Docker lifecycle management
- **Image Management** - Pull, delete, prune images and optimize storage
- **Registry Integration** - Push/pull from Docker Hub, GitHub, GitLab, AWS ECR
- **Backup & Restore** - Export/import containers for disaster recovery
- **Project-Specific Views** - Each project shows only its related containers and images

### Kubernetes Integration
- **Multi-Cluster Management** - Manage multiple K8s clusters
- **One-Click Deployment** - Deploy projects directly to Kubernetes
- **Pod Monitoring** - Real-time pod status, logs, and metrics
- **Auto-Scaling** - Configure horizontal pod autoscaling
- **Helm Support** - Deploy using Helm charts
- **Namespace Isolation** - Organize deployments by namespace
- **Rolling Updates** - Zero-downtime deployments

### CI/CD Pipeline Automation
- **Multi-Provider Support** - GitHub Actions, GitLab CI, Bitbucket, Jenkins
- **Visual Pipeline Builder** - Drag-and-drop configuration
- **Template Library** - Pre-built pipelines for common scenarios
- **Parallel Execution** - Run multiple jobs concurrently
- **Artifact Management** - Store and retrieve build artifacts
- **Pipeline Triggers** - Manual, push, PR, schedule triggers
- **YAML Generation** - Export pipelines for version control

### Automated Deployments
- **Git-Based Deployments** - Clone or pull from repositories
- **Smart Git Operations** - 10-20x faster with pull for existing repos
- **Build Automation** - Automatic builds and migrations
- **Deployment History** - Track all deployments with commit info
- **Real-Time Progress Viewer** - Watch deployments with live progress bar
- **Laravel Optimization** - Automatic caching, migrations (8 optimization commands)
- **Extended Timeout** - 20 minutes for large builds
- **Webhook Integration** - Auto-deploy on GitHub/GitLab push with HMAC verification

### Monitoring & Analytics
- **Performance Metrics** - Server and project analytics
- **Deployment Stats** - Success rates, duration tracking
- **Real-Time Updates** - Live status monitoring with auto-refresh
- **Resource Alerts** - CPU/RAM/Disk threshold monitoring with notifications
- **Log Aggregation** - Centralized log management with full-text search
- **Health Checks** - HTTP, TCP, Ping, SSL checks with configurable intervals

### Backup & Recovery
- **Server Backups** - Full/incremental/snapshot backup strategies
- **Database Backups** - MySQL/PostgreSQL with scheduling
- **S3 Integration** - Cloud storage support
- **Automated Schedules** - Configurable retention periods
- **One-Click Restore** - Quick disaster recovery

### SSL & Domain Management
- **Let's Encrypt Integration** - Automatic SSL certificate issuance
- **Auto-Renewal** - Scheduled certificate renewal
- **Multi-Domain Support** - Manage unlimited domains per project
- **Certificate Tracking** - Expiration monitoring and alerts

### Team Collaboration
- **Team Management** - Create and manage teams with full settings
- **Role-Based Access** - Owner, Admin, Member, Viewer roles
- **Team Invitations** - Email invitations with 7-day expiration
- **Team Switching** - Quick team switcher dropdown
- **Ownership Transfer** - Transfer team ownership
- **Team-Scoped Resources** - Projects and servers scoped to teams

### GitHub Integration
- **OAuth Authentication** - Secure GitHub account connection
- **Repository Browser** - Browse and select repositories
- **Repository Sync** - Search and filter repositories
- **Project Linking** - Link GitHub repos to DevFlow projects
- **Encrypted Storage** - Secure token storage

### API v1
- **RESTful API** - Full CRUD operations for projects, servers, deployments
- **Bearer Token Auth** - Secure API token authentication
- **Token Management** - Create, regenerate, revoke tokens
- **Granular Permissions** - Read/write permissions per resource
- **Interactive Documentation** - API docs with examples
- **16 Endpoints** - Comprehensive resource coverage

### Modern UI/UX
- **Beautiful Dashboard** - Clean, intuitive interface with gradients
- **Real-Time Updates** - Livewire-powered reactivity
- **Live Progress Bars** - Animated deployment progress
- **Dark Theme** - Complete dark mode with toggle and persistence
- **Mobile Responsive** - Works on all devices
- **50% Faster Load Times** - Optimized bundle size and assets

---

## Installation

### Prerequisites

- **Operating System:** Ubuntu 20.04+ / Debian 11+ / RHEL 8+
- **PHP:** 8.2, 8.3, or 8.4
- **Database:** PostgreSQL 14+ (recommended) or MySQL 8.0+
- **Redis:** 6.0+ (for caching and queues)
- **Node.js:** 18.x or 20.x
- **Composer:** Latest stable version
- **Git:** Latest stable version
- **Docker:** 20.10+ (optional, for containerized deployments)

### Quick Installation (Traditional)

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Create environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Configure database in .env
# For PostgreSQL (recommended):
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=your_username
DB_PASSWORD=your_password

# 7. Run database migrations
php artisan migrate

# 8. Seed initial data (optional)
php artisan db:seed

# 9. Build frontend assets
npm run build

# 10. Start the application
php artisan serve
```

Visit `http://localhost:8000` to access DevFlow Pro.

### Docker Installation

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# 2. Copy environment file
cp .env.example .env

# 3. Build and start containers
docker-compose up -d

# 4. Generate application key
docker-compose exec app php artisan key:generate

# 5. Run migrations
docker-compose exec app php artisan migrate

# 6. Build frontend assets
docker-compose exec app npm install
docker-compose exec app npm run build
```

Visit `http://localhost` to access DevFlow Pro.

### Production Deployment

For production deployment with Nginx, SSL, and advanced configuration, see [VPS Deployment Guide](docs/VPS_DEPLOYMENT_GUIDE.md).

---

## Configuration

### Environment Variables

DevFlow Pro requires several environment variables for proper operation. Here are the essential ones:

#### Application Settings
```env
APP_NAME="DevFlow Pro"
APP_ENV=production
APP_KEY=                    # Generated by php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com
```

#### Database Configuration
```env
# PostgreSQL (Recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password
```

#### Cache & Queue Configuration
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

#### DevFlow Specific Paths
```env
PROJECTS_PATH=/opt/devflow/projects
BACKUPS_PATH=/opt/devflow/backups
LOGS_PATH=/opt/devflow/logs
SSL_PATH=/opt/devflow/ssl
```

#### Docker Configuration
```env
DOCKER_SOCKET=/var/run/docker.sock
DOCKER_NETWORK=devflow_network
```

#### GitHub Integration (Optional)
```env
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_WEBHOOK_SECRET=your_webhook_secret
```

#### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@devflow.pro
```

For a complete list of configuration options, see `.env.example`.

---

## Usage

### Quick Start Workflow

#### 1. Add Your Server
```
Dashboard → Servers → Add Server
- Click "Add Current Server" for quick setup
- Or manually add remote servers with SSH credentials
```

#### 2. Create a Project
```
Dashboard → Projects → Create Project
- Enter project name and repository URL
- Select framework (Laravel, Node.js, React, etc.)
- Choose PHP version (8.2, 8.3, 8.4)
- Configure environment variables
```

#### 3. Deploy Your Project
```
Project Page → Deploy Button
- Clones/pulls from Git repository
- Builds Docker image (uses your Dockerfile if exists)
- Runs migrations and optimizations
- Starts container
- Watch in real-time with progress bar
```

#### 4. Monitor & Manage
```
Project Page → Tabs
- Overview: Status, stats, quick actions
- Docker: Container management, logs, images
- Environment: Manage env variables
- Git: Branch switching, commit history, updates
- Deployments: View deployment history and logs
```

For detailed guides, see the [Documentation](#documentation) section.

---

## Documentation

### Core Documentation
- [Complete Documentation](DOCUMENTATION.md) - Full user guide and reference
- [Changelog](CHANGELOG.md) - Version history and release notes
- [Roadmap](ROADMAP.md) - Future features and planning

### Guides
- [Getting Started Guide](docs/GETTING_STARTED.md) - First steps with DevFlow Pro
- [Project Management Guide](docs/PROJECT_MANAGEMENT.md) - Creating and managing projects
- [Deployment Guide](docs/DEPLOYMENT_GUIDE.md) - Deploying applications
- [Server Management Guide](docs/SERVER_MANAGEMENT.md) - Managing servers
- [Docker Setup Guide](docs/DOCKER_SETUP.md) - Docker configuration
- [SSH Setup Guide](docs/SSH_SETUP.md) - SSH key configuration
- [VPS Deployment Guide](docs/VPS_DEPLOYMENT_GUIDE.md) - Production deployment
- [API Documentation](docs/API_DOCUMENTATION.md) - REST API reference
- [Testing Guide](TESTING.md) - Running tests

---

## Supported Technologies

### Frameworks
- Laravel (PHP)
- Node.js (Express, NestJS, etc.)
- React (CRA, Vite)
- Vue.js (Vue CLI, Vite)
- Next.js
- Nuxt.js
- Static Sites (HTML/CSS/JS)
- Custom frameworks

### PHP Versions
- PHP 8.4 (Latest)
- PHP 8.3 (Recommended)
- PHP 8.2
- PHP 8.1 (Limited support)

### Deployment Methods
- Docker containers
- docker-compose multi-container setups
- Kubernetes clusters
- Traditional server deployments

---

## System Requirements

### Minimum Requirements
- **CPU:** 2 cores
- **RAM:** 2GB
- **Disk:** 20GB
- **OS:** Ubuntu 20.04+, Debian 11+, RHEL 8+

### Recommended for Production
- **CPU:** 4+ cores
- **RAM:** 4GB+
- **Disk:** 50GB+ SSD
- **OS:** Ubuntu 22.04 LTS

### Required Software
- PHP 8.2+ with extensions: mbstring, xml, pdo, pgsql/mysql, redis, curl, zip
- PostgreSQL 14+ or MySQL 8.0+
- Redis 6.0+
- Nginx 1.18+ or Apache 2.4+
- Docker 20.10+ (for containerized deployments)
- Supervisor (for queue workers)

---

## Contributing

We welcome contributions from the community! Whether it's bug reports, feature requests, documentation improvements, or code contributions, your help is appreciated.

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines on:
- Code of Conduct
- How to report bugs
- How to suggest features
- Development setup
- Pull request process
- Coding standards

### Quick Development Setup

```bash
# Clone repository
git clone https://github.com/yourusername/devflow-pro.git
cd devflow-pro

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Run development server
php artisan serve

# Watch assets (in another terminal)
npm run dev
```

---

## Testing

DevFlow Pro has comprehensive test coverage with 4,500+ tests.

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run PHPStan (static analysis)
composer analyse

# Run Laravel Pint (code style)
composer lint:fix
```

For more details, see [TESTING.md](TESTING.md).

---

## Security

Security is a top priority for DevFlow Pro. If you discover a security vulnerability, please email security@devflow.pro instead of using the issue tracker.

### Security Features
- SSH key authentication
- Encrypted sensitive data
- Role-based access control
- API token authentication
- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting

---

## License

DevFlow Pro is open-source software licensed under the [MIT License](LICENSE).

---

## Support

- **Documentation:** [docs/](docs/)
- **Issues:** [GitHub Issues](https://github.com/yourusername/devflow-pro/issues)
- **Discussions:** [GitHub Discussions](https://github.com/yourusername/devflow-pro/discussions)
- **Email:** support@devflow.pro

---

## Acknowledgments

### Technologies
- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [Livewire](https://livewire.laravel.com) - A full-stack framework for Laravel
- [Alpine.js](https://alpinejs.dev) - A rugged, minimal framework for composing JavaScript behavior
- [Tailwind CSS](https://tailwindcss.com) - A utility-first CSS framework
- [Docker](https://docker.com) - Container platform
- [Kubernetes](https://kubernetes.io) - Container orchestration

### Contributors
Thank you to all contributors who have helped make DevFlow Pro better!

---

<div align="center">

**Developed by [NileStack](https://github.com/nilestack)**

[Get Started](#installation) • [Documentation](#documentation) • [Support](#support)

---

© 2025 NileStack. All rights reserved.

</div>
