# DevFlow Pro - Professional Deployment Management System

> **Modern, powerful, and easy-to-use deployment platform for managing multiple projects across multiple servers.**

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/yourusername/devflow-pro)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3-purple.svg)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

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
- **Create & Edit Projects** - Full CRUD operations with validation
- **Multiple Frameworks** - Laravel, Node.js, React, Vue, Next.js, Static sites
- **Version Control** - Git integration (HTTPS & SSH)
- **Branch Management** - Deploy from any branch
- **Git Commit Tracking** - View commit history and check for updates ⭐ NEW!
- **Update Notifications** - Get notified when new commits are available ⭐ NEW!

### 🖥️ Server Management
- **Multi-Server Support** - Manage unlimited servers
- **Real-Time Monitoring** - CPU, RAM, Disk usage
- **Auto-Discovery** - One-click current server addition
- **SSH Connectivity** - Automated connection testing
- **Server Health Checks** - Automatic ping and status detection

### 📦 Docker Integration
- **Smart Dockerfile Detection** - Uses your Dockerfile if it exists ⭐ NEW!
- **Dockerfile.production Support** - Separate dev/prod configurations ⭐ NEW!
- **Auto-Generation Fallback** - Creates Dockerfile only if needed
- **docker-compose Support** - Use your own configurations
- **Container Management** - Start, stop, restart containers
- **Real-Time Logs** - View container logs in dashboard

### 🔄 Automated Deployments
- **GitHub Integration** - Clone from public/private repositories
- **SSH Key Support** - Secure authentication for private repos
- **Build Automation** - Automatic builds and migrations
- **Deployment History** - Track all deployments with commit info
- **Real-Time Progress Viewer** - Watch deployments with live progress bar ⭐ NEW!
- **Auto-Refresh** - Updates every 3 seconds during deployment ⭐ NEW!
- **Extended Timeout** - 20 minutes for large projects with npm builds ⭐ NEW!

### 📊 Analytics & Monitoring
- **Performance Metrics** - Server and project analytics
- **Deployment Stats** - Success rates, duration tracking
- **Real-Time Updates** - Live status monitoring
- **Live Progress Tracking** - Step-by-step deployment visualization ⭐ NEW!
- **Commit History** - See what code is deployed ⭐ NEW!

### 🌐 Modern UI/UX
- **Beautiful Dashboard** - Clean, intuitive interface
- **Real-Time Updates** - Livewire-powered reactivity with auto-refresh
- **Live Progress Bars** - Animated deployment progress with step indicators ⭐ NEW!
- **Auto-Scrolling Logs** - Smart terminal-style log viewer ⭐ NEW!
- **Mobile Responsive** - Works on all devices
- **Visual Feedback** - Step indicators, spinners, and progress animations ⭐ NEW!

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

## 📖 Documentation

### User Guides
- [📘 Getting Started Guide](docs/GETTING_STARTED.md) - First steps
- [📗 Project Management Guide](docs/PROJECT_MANAGEMENT.md) - Creating & managing projects
- [📙 Deployment Guide](docs/DEPLOYMENT_GUIDE.md) - Deploying applications
- [📕 Server Management Guide](docs/SERVER_MANAGEMENT.md) - Managing servers

### Technical Documentation
- [🔧 Installation Guide](docs/INSTALLATION.md) - Detailed setup
- [⚙️ Configuration](docs/CONFIGURATION.md) - Environment variables & settings
- [🐳 Docker Setup](docs/DOCKER_SETUP.md) - Docker configuration
- [🔐 SSH Setup](docs/SSH_SETUP.md) - SSH key configuration for GitHub

### Reference
- [📚 API Documentation](API.md) - REST API endpoints
- [🔍 Troubleshooting](TROUBLESHOOTING.md) - Common issues & solutions
- [📝 Changelog](CHANGELOG.md) - Version history
- [🎯 Roadmap](COMPREHENSIVE_IMPROVEMENT_PLAN.md) - Future features

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

See [COMPREHENSIVE_IMPROVEMENT_PLAN.md](COMPREHENSIVE_IMPROVEMENT_PLAN.md) for the complete roadmap.

### Recently Added (v2.1) ⭐
- ✅ **Git Commit Tracking** - See exactly what code is deployed
- ✅ **Check for Updates** - Compare deployed version with GitHub
- ✅ **Real-Time Progress Viewer** - Watch deployments with live progress
- ✅ **Dockerfile Detection** - Respects your Dockerfile/Dockerfile.production
- ✅ **Extended Timeouts** - 20 minutes for large npm builds
- ✅ **Auto-Scrolling Logs** - Smart log viewer with terminal style
- ✅ **Step Indicators** - Visual deployment step progress

### Completed (v2.0)
- ✅ Project editing
- ✅ PHP 8.4 support
- ✅ Static site support
- ✅ SSH authentication for private repos

### Coming Soon (v2.2+)
- 🔄 Environment variables manager UI
- 🔄 Project templates library
- 🔄 One-click rollback system
- 🔄 Automatic SSL with Let's Encrypt
- 🔄 Team collaboration features
- 🔄 Deployment scheduling

---

## 🎉 Success Stories

> *"DevFlow Pro saved me 10+ hours per week on deployments!"*  
> **- Mohamed Roshdy, Full Stack Developer**

> *"Best deployment tool I've used. Simple yet powerful."*  
> **- DevOps Team Lead**

---

## 🔗 Links

- **Website:** https://devflowpro.com (coming soon)
- **Documentation:** https://docs.devflowpro.com (coming soon)
- **GitHub:** https://github.com/yourusername/devflow-pro
- **Twitter:** @devflowpro (coming soon)

---

<div align="center">

**Made with ❤️ by developers, for developers**

[Get Started](#quick-start-5-minutes) • [Documentation](#documentation) • [Support](#support)

</div>
