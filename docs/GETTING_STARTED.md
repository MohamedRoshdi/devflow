# DevFlow Pro - Getting Started Guide

> **Your Complete Guide to Understanding and Using DevFlow Pro**
>
> Written for developers, DevOps engineers, and system administrators who want to simplify their deployment workflow.

---

## What is DevFlow Pro?

**DevFlow Pro** is a deployment management platform that helps you deploy and manage multiple web applications across multiple servers from a single dashboard.

### In Simple Terms

Think of it like this:

| Without DevFlow Pro | With DevFlow Pro |
|---------------------|------------------|
| SSH into each server manually | Click "Deploy" from your browser |
| Run git pull, composer install, npm build... | Automated with one click |
| Manage Docker containers via terminal | Visual dashboard with buttons |
| Check server health manually | Real-time monitoring alerts |
| Configure nginx, SSL, firewall manually | Automated setup and management |

---

## Who Should Use DevFlow Pro?

### Developers

**Your Problem:** You have 5+ Laravel projects on different servers. Deploying updates means SSHing into each server and running the same commands.

**DevFlow Solution:** Add your projects once, then deploy updates with a single click. See deployment logs in real-time.

### DevOps Engineers

**Your Problem:** Managing Docker containers, configuring CI/CD pipelines, monitoring server health across multiple servers.

**DevFlow Solution:**
- Full Docker management (start/stop/restart containers)
- CI/CD pipeline builder for GitHub Actions, GitLab CI
- Real-time server metrics (CPU, RAM, Disk)
- Automated health checks with Slack/Discord alerts

### System Administrators

**Your Problem:** Server security, SSL certificates, firewall rules, user access management.

**DevFlow Solution:**
- UFW firewall management from dashboard
- Let's Encrypt SSL auto-renewal
- Fail2ban intrusion prevention
- SSH hardening with security score

### Agencies / Freelancers

**Your Problem:** Managing 20+ client websites on different servers. Each client has different requirements.

**DevFlow Solution:**
- Organize projects by teams
- Role-based access (Admin, Developer, Viewer)
- Multi-tenant support for SaaS applications
- Per-project environment variables

---

## Quick Start: Your First Deployment in 10 Minutes

### Step 1: Access DevFlow Pro

```
URL: https://admin.nilestack.duckdns.org (or your installation URL)
```

> **Note:** Registration is disabled by default. Ask your admin for credentials.

### Step 2: Add Your First Server

1. Click **Servers** in the navigation
2. Click **Add Current Server** (if DevFlow is on the same server)
   - OR click **Add Server** for a remote server
3. Fill in details:
   - **Name:** Production Server (any name you want)
   - **IP Address:** Your server's IP
   - **SSH User:** Usually `root`
   - **Auth Method:** Password or SSH Key
4. Click **Add Server**

DevFlow will test the connection and show server status (Online/Offline).

### Step 3: Create Your First Project

1. Click **Projects** in the navigation
2. Click **Create Project**
3. Fill in the form:

```
Project Name:     My Laravel App
Repository URL:   git@github.com:username/my-app.git
Branch:           main
Framework:        Laravel
PHP Version:      8.3
Server:           [Select your server]
```

4. Click **Create Project**

### Step 4: Deploy!

1. Go to your project page
2. Click the big blue **Deploy** button
3. Watch the real-time deployment:
   - Cloning repository
   - Installing dependencies (composer, npm)
   - Building assets
   - Running migrations
   - Clearing caches
   - Starting container

**Deployment complete!** Your app is now live.

---

## Understanding the Dashboard

When you log in, you'll see the main dashboard with these sections:

### Top Navigation

| Item | What It Does |
|------|--------------|
| **Dashboard** | Overview of all projects and servers |
| **Servers** | Manage your servers, view metrics |
| **Projects** | Manage your applications |
| **Deployments** | History of all deployments |
| **Analytics** | Deployment statistics and trends |
| **Health** | Health status of all projects |
| **More** | Advanced features (see below) |

### Dashboard Stats

- **Total Projects:** Number of projects you're managing
- **Active Deployments:** Currently running deployments
- **Server Health Score:** Average health across all servers
- **Recent Activity:** Latest deployments and actions

---

## Core Features Explained

### 1. Project Management

**What:** Manage all your web applications in one place.

**Key Features:**
- Create/Edit/Delete projects
- Supports Laravel, Node.js, React, Vue, Static sites
- Git integration (GitHub, GitLab, Bitbucket)
- Environment variables management

**When to Use:**
- Adding a new website to manage
- Changing deployment settings
- Updating environment variables

### 2. Deployment System

**What:** Automated deployment pipeline for your projects.

**Key Features:**
- One-click deployment
- Real-time deployment logs
- Automatic dependency installation
- Database migrations
- Cache clearing
- Instant rollback

**When to Use:**
- Pushing code updates to production
- Rolling back a broken deployment
- Deploying to staging first

**Example Workflow:**
```
1. Push code to GitHub
2. Open DevFlow Pro
3. Click "Check for Updates" (sees new commits)
4. Click "Deploy"
5. Watch real-time progress
6. Done in ~2-5 minutes
```

### 3. Docker Management

**What:** Visual interface for Docker containers.

**Key Features:**
- View running containers
- Start/Stop/Restart containers
- View container logs
- Build Docker images
- Resource monitoring (CPU, RAM)

**When to Use:**
- Container not responding? Restart it
- Need to see error logs? View container logs
- Running out of memory? Check resource usage

**Where:** Project Page > Docker Tab

### 4. Server Management

**What:** Manage your infrastructure.

**Key Features:**
- Multi-server support
- Real-time metrics (CPU, RAM, Disk)
- SSH terminal in browser
- Docker installation
- Service restart (nginx, mysql, php-fpm)

**When to Use:**
- Adding a new server
- Monitoring server health
- Running quick commands
- Installing Docker

**Where:** Servers > [Your Server]

### 5. Environment Variables

**What:** Manage application configuration securely.

**Key Features:**
- Per-environment settings (Local, Dev, Staging, Production)
- Secure encrypted storage
- Auto-injection during deployment
- Direct server .env editing

**When to Use:**
- Setting up database credentials
- Adding API keys
- Changing APP_DEBUG for debugging

**Where:** Project Page > Environment Tab

---

## Advanced Features Guide

### Kubernetes Integration

**What For:** Deploying applications to Kubernetes clusters.

**When to Use:**
- You have a Kubernetes cluster (EKS, GKE, self-hosted)
- Need auto-scaling for high traffic
- Want zero-downtime deployments

**Best For:**
- Large applications needing high availability
- Microservices architecture
- Applications with variable traffic

**How to Start:**
1. Go to **More > Kubernetes**
2. Click **Add Cluster**
3. Paste your kubeconfig
4. Deploy projects to K8s

### CI/CD Pipelines

**What For:** Automated testing and deployment workflows.

**When to Use:**
- Want automatic deployments on git push
- Need to run tests before deployment
- Multiple stages (test > staging > production)

**Best For:**
- Teams with code review processes
- Projects requiring automated testing
- Complex deployment workflows

**How to Start:**
1. Go to **More > CI/CD Pipelines**
2. Select your CI provider (GitHub Actions, GitLab CI)
3. Configure stages (Build, Test, Deploy)
4. Export YAML to your repository

### Multi-Tenant Management

**What For:** Managing SaaS applications with multiple clients.

**When to Use:**
- Building a SaaS product
- Each client has their own database
- Need to deploy updates to all tenants

**Best For:**
- SaaS applications
- White-label products
- Multi-database architectures

**How to Start:**
1. Mark project as "Multi-Tenant" in settings
2. Go to **More > Multi-Tenant**
3. Add tenants with their databases
4. Deploy to all or selected tenants

### Notification Channels

**What For:** Getting alerts about deployments and issues.

**When to Use:**
- Want deployment notifications in Slack
- Need alerts when servers go down
- Want SSL expiry warnings

**Best For:**
- Teams using Slack/Discord
- Monitoring production environments
- Tracking deployment history

**Supported Channels:**
- Slack
- Discord
- Microsoft Teams
- Custom Webhooks
- Email

**How to Start:**
1. Go to **More > Notifications**
2. Add your webhook URL
3. Select events to receive
4. Test the notification

### Server Security Management

**What For:** Hardening your servers against attacks.

**When to Use:**
- Setting up a new server
- Regular security audits
- After a security incident

**Features:**
- **Firewall (UFW):** Allow/block ports
- **Fail2ban:** Block brute force attacks
- **SSH Hardening:** Disable root login, change port
- **Security Score:** 0-100 rating

**How to Start:**
1. Go to **Servers > [Server] > Security**
2. View your security score
3. Enable firewall
4. Configure SSH hardening
5. Run security scan

### Database Backups

**What For:** Protecting your data.

**When to Use:**
- Production databases
- Before risky deployments
- Disaster recovery planning

**Features:**
- Scheduled backups (daily, weekly)
- Multiple storage options (S3, local)
- One-click restore
- Retention policies

**How to Start:**
1. Go to **Project > Backups**
2. Configure backup schedule
3. Set retention period
4. Enable auto-cleanup

### API Access

**What For:** Integrating DevFlow with other tools.

**When to Use:**
- Building custom dashboards
- Automating deployments from scripts
- CI/CD integration

**How to Start:**
1. Go to **Settings > API Tokens**
2. Create new token
3. Use in API calls

**Example:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  https://devflow.example.com/api/v1/projects/my-app/deploy
```

---

## Project Types and When to Use Each Feature

### Simple Static Website

**Setup:**
- Framework: Static
- No Docker needed
- Deploy via rsync

**Features Needed:**
- Basic project management
- SSL certificates
- Simple deployments

### Laravel Application

**Setup:**
- Framework: Laravel
- PHP Version: 8.3+
- Docker: Yes

**Features Needed:**
- Environment variables (DB credentials)
- Deployment with migrations
- Queue workers
- Cache management

### React/Vue SPA

**Setup:**
- Framework: React or Vue
- Build command: npm run build
- Docker: Optional

**Features Needed:**
- Asset building
- Environment variables
- CDN deployment (optional)

### Node.js Application

**Setup:**
- Framework: Node.js
- Build command: npm run build
- Start command: npm start

**Features Needed:**
- Process management (PM2)
- Environment variables
- Health checks

### Microservices Architecture

**Setup:**
- Multiple small services
- Docker Compose
- Kubernetes recommended

**Features Needed:**
- Kubernetes integration
- CI/CD pipelines
- Service discovery
- Centralized logging

### Multi-Tenant SaaS

**Setup:**
- Laravel with tenant package
- Separate databases per tenant
- Shared codebase

**Features Needed:**
- Multi-tenant management
- Bulk deployments
- Per-tenant configurations
- Tenant backup/restore

---

## Common Workflows

### Daily Development

```
1. Write code locally
2. Push to GitHub
3. Open DevFlow
4. Click "Check for Updates"
5. Deploy to staging
6. Test
7. Deploy to production
```

### New Project Setup

```
1. Add server (if new)
2. Create project with repo URL
3. Configure environment variables
4. First deployment
5. Set up domain/SSL
6. Configure backups
7. Set up notifications
```

### Emergency Rollback

```
1. Go to Deployments tab
2. Find last working deployment
3. Click "Rollback"
4. Confirm
5. Done in seconds
```

### Server Maintenance

```
1. Go to Servers
2. Check metrics
3. Run security scan
4. Update packages (via SSH terminal)
5. Restart services if needed
```

---

## Troubleshooting Quick Guide

### Deployment Failed

1. Check deployment logs for errors
2. Common issues:
   - Missing SSH key for private repo
   - Database credentials wrong
   - Disk space full
   - Memory limit reached

### Container Not Starting

1. Go to Docker tab
2. View container logs
3. Look for errors
4. Fix configuration and redeploy

### Can't Connect to Server

1. Check server status (Servers page)
2. Verify IP address is correct
3. Check SSH credentials
4. Ensure port 22 is open

### SSL Certificate Issues

1. Check domain DNS points to server
2. Go to Servers > SSL
3. Issue new certificate
4. Wait for propagation

---

## Best Practices

### For Beginners

1. **Start Simple:** One server, one project
2. **Test First:** Deploy to staging before production
3. **Use Environment Variables:** Never hardcode secrets
4. **Enable Notifications:** Know when deployments fail
5. **Regular Backups:** Enable database backups early

### For Teams

1. **Use Teams:** Organize by department or client
2. **Role-Based Access:** Viewers for stakeholders, Members for developers
3. **Standardize:** Use project templates
4. **Document:** Keep environment variables documented
5. **Review:** Use deployment approvals for production

### For Production

1. **Security First:** Run security scans regularly
2. **Monitor:** Set up resource alerts
3. **Backup:** Daily database backups
4. **SSL:** Auto-renew enabled
5. **Rollback Plan:** Know how to rollback quickly

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + K` | Quick search |
| `Ctrl/Cmd + D` | Deploy current project |
| `Ctrl/Cmd + L` | View logs |
| `G then P` | Go to projects |
| `G then S` | Go to servers |
| `?` | Show all shortcuts |

---

## Getting Help

### Documentation

- **Full Documentation:** [DOCUMENTATION.md](../DOCUMENTATION.md)
- **API Reference:** [docs/API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Troubleshooting:** [docs/TROUBLESHOOTING_GUIDE.md](TROUBLESHOOTING_GUIDE.md)

### Support

- **GitHub Issues:** Report bugs or request features
- **Email:** support@devflowpro.com

---

## Next Steps

1. **Add Your First Server** - [Server Management Guide](#4-server-management)
2. **Create Your First Project** - [Project Management Guide](#1-project-management)
3. **Deploy Your First App** - [Deployment Guide](#2-deployment-system)
4. **Set Up Notifications** - [Notification Setup](#notification-channels)
5. **Secure Your Server** - [Security Guide](#server-security-management)

---

**Happy Deploying!**

*DevFlow Pro - Deploy Production Apps in Minutes, Not Days.*
