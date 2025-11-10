# DevFlow Pro - Complete User Guide

**Version 2.0** | Last Updated: November 9, 2025

---

## 📚 Table of Contents

1. [Getting Started](#getting-started)
2. [Managing Servers](#managing-servers)
3. [Creating Projects](#creating-projects)
4. [Editing Projects](#editing-projects)
5. [Deploying Applications](#deploying-applications)
6. [Managing Containers](#managing-containers)
7. [Viewing Logs](#viewing-logs)
8. [Troubleshooting](#troubleshooting)

---

## 🚀 Getting Started

### First Login

1. **Visit your DevFlow Pro installation**
   ```
   http://your-server-ip/
   ```

2. **Register an account**
   - Click "Register here"
   - Fill in your details
   - Submit

3. **Login**
   - Use your credentials
   - You'll see the dashboard

### Dashboard Overview

**What you'll see:**
- **Servers** - Total servers and their status
- **Projects** - Active projects count
- **Deployments** - Recent deployment activity
- **Quick Actions** - Add server, create project

**Navigation:**
- **Dashboard** - Overview
- **Servers** - Manage servers
- **Projects** - Manage projects
- **Deployments** - View deployment history
- **Analytics** - Performance metrics

---

## 🖥️ Managing Servers

### Adding Your First Server

#### Method 1: Add Current Server (Quickest!)

1. Go to **Servers** page
2. Click **"⚡ Add Current Server"**
3. Done! Server automatically detected

**What it does:**
- Detects server IP
- Gets hostname
- Checks Docker installation
- Tests connectivity
- Retrieves system specs (CPU, RAM, Disk)

#### Method 2: Add Remote Server

1. Go to **Servers** page
2. Click **"+ Add Server"**
3. Fill in details:
   - **Name:** My Production Server
   - **Hostname:** server.example.com
   - **IP Address:** 192.168.1.100
   - **Port:** 22
   - **Username:** root (or your SSH user)
   - **SSH Key:** (paste private key if needed)
4. Click **"Add Server"**

**Automatic checks:**
- SSH connectivity test
- Docker installation detection
- System information gathering
- Status update (online/offline)

### Server Status Indicators

| Status | Color | Meaning |
|--------|-------|---------|
| **Online** | 🟢 Green | Server responsive, ready |
| **Offline** | 🔴 Red | Can't reach server |
| **Maintenance** | 🟡 Yellow | Manual maintenance mode |
| **Error** | 🔴 Red | Connection error |

### Server Actions

**From Server List:**
- **👁️ View** - See server details
- **🔄 Refresh** - Update status

**From Server Details:**
- **📊 View Metrics** - CPU, RAM, Disk usage
- **🐳 Docker Info** - Container status
- **🔍 Detect Docker** - Check for Docker installation
- **🔄 Ping Server** - Test connectivity (also checks Docker)
- **🐳 Docker Management** - Access Docker dashboard (when Docker detected)

---

## 📦 Creating Projects

### Step-by-Step Project Creation

**1. Navigate to Projects**
- Click **"Projects"** in top navigation
- Click **"+ Create Project"**

**2. Fill in Basic Information**

**Project Name** (Required)
- Example: `ATS Pro`, `My Website`, `API Server`
- Auto-generates slug

**Slug** (Auto-filled)
- URL-friendly identifier
- Example: `ats-pro`, `my-website`
- Can be customized

**3. Select Server**

Choose from your servers:
- Shows status (online/offline)
- Displays specs (CPU, RAM)
- Shows Docker availability
- **Refresh** button to update status

**Tip:** Only online servers with Docker recommended!

**4. Repository Configuration**

**Repository URL** (Required)
- **HTTPS:** `https://github.com/user/repo.git`
- **SSH:** `git@github.com:user/repo.git` ✅ Recommended for private repos!

**Branch** (Required, Default: main)
- `main`, `master`, `develop`, etc.
- Deploy from any branch

**5. Framework & Runtime**

**Framework** (Dropdown)
- **Static Site** - HTML/CSS/JS (no build)
- **Laravel** - PHP framework
- **Node.js** - Express, APIs
- **React** - Frontend framework
- **Vue.js** - Frontend framework
- **Next.js** - React framework
- **Nuxt.js** - Vue framework

**PHP Version** (For PHP projects)
- **8.4** - Latest
- **8.3** - Recommended (default)
- **8.2, 8.1, 8.0, 7.4** - Older versions

**Node Version** (For JS projects)
- **20 (LTS)** - Recommended
- **18, 16** - Older versions

**6. Build Configuration**

**Root Directory**
- Where your code lives
- Default: `/`
- Example: `/public` for static sites

**Build Command** (Optional)
- Runs during deployment
- Examples:
  - Laravel: `composer install && npm run build`
  - React: `npm run build`
  - Static: (leave empty)

**Start Command** (Optional)
- How to start your app
- Examples:
  - Node.js: `npm start`
  - PM2: `pm2 start app.js`
  - Docker handles this automatically

**7. Options**

**Auto-deploy on git push**
- ☑️ Check to enable
- Requires webhook setup
- Deploys automatically on push to branch

**8. GPS Location** (Optional)
- Latitude/Longitude
- For location-based features
- Auto-detection available

**9. Click "Create Project"**

✅ Project created!
- Redirects to project page
- Ready to deploy!

---

## ✏️ Editing Projects

### How to Edit

**1. Go to Project Page**
- From projects list, click project name
- Or visit `/projects/{id}`

**2. Click "✏️ Edit" Button**
- Located in top right
- Opens edit form

**3. Update Settings**
- Change any settings
- Update repository URL
- Switch servers
- Modify framework/versions

**4. Click "Update Project"**
- Saves changes
- Returns to project page

### What You Can Edit

✅ **Project name & slug**
✅ **Repository URL** - Switch repos or change to SSH
✅ **Branch** - Change deployment branch
✅ **Server** - Move to different server
✅ **Framework & versions** - Update PHP/Node
✅ **Build commands** - Modify build process
✅ **Auto-deploy** - Enable/disable webhooks

⚠️ **Note:** Changing server or repository requires redeployment!

---

## 🚀 Deploying Applications

### First Deployment

**Prerequisites:**
- ✅ Project created
- ✅ Server online
- ✅ Repository accessible (SSH key added for private repos)

**Steps:**

**1. Open Project Page**
- Navigate to your project
- Click project name from list

**2. Click "🚀 Deploy"**
- Blue deploy button in top right
- Confirmation modal appears

**3. Confirm Deployment**
- Click "Deploy" in modal
- Deployment starts immediately

**4. Watch Progress**
- Real-time logs appear
- Status updates live
- See each step:
  - Cloning repository
  - Building Docker image
  - Running migrations
  - Starting container

**5. Deployment Complete!**
- Status: Success (green) or Failed (red)
- Duration shown
- Full logs available

### Deployment Process (Behind the Scenes)

```
1. 📥 Clone Repository
   git clone --branch main https://github.com/user/repo

2. 🐳 Build Docker Image
   docker build -t project-slug:latest .
   
3. 🔄 Stop Old Container
   docker stop project-slug (if exists)
   
4. ▶️ Start New Container
   docker run -d --name project-slug project-slug:latest
   
5. ✅ Update Status
   Project status → running
```

### Redeployment (Updates)

**When to redeploy:**
- After pushing code changes
- When updating dependencies
- To apply configuration changes

**How to redeploy:**
1. Push changes to GitHub
2. Go to project page
3. Click "🚀 Deploy"
4. New deployment starts
5. Old container replaced

**Auto-deploy:**
- Enable "auto-deploy" in project settings
- Setup GitHub webhook
- Pushes trigger automatic deployments!

---

## 🎮 Managing Containers

### Start Project

**When:** After successful deployment

**How:**
1. Go to project page
2. Click **"▶️ Start Project"** button
3. Container starts
4. Status changes to "running"

**What it does:**
```bash
docker run -d --name project-slug -p 8000:80 project-slug:latest
```

### Stop Project

**When:** Want to stop the application

**How:**
1. Go to project page
2. Click **"⏹️ Stop Project"** button
3. Confirm if prompted
4. Container stops
5. Status changes to "stopped"

**What it does:**
```bash
docker stop project-slug
```

### Container Lifecycle

```
Created → Deployed → Started → Running
   ↓         ↓          ↓          ↓
Database   Docker    Container   Accessible
  Entry     Image      Running       App
  
Running → Stopped → Redeployed → Running
   ↓          ↓           ↓           ↓
  Stop     Container   New Build    New
 Button     Stopped      Image    Container
```

---

## 📋 Viewing Logs

### Deployment Logs

**Access:**
1. Go to **Deployments** page
2. Click on a deployment
3. Scroll to "Deployment Logs"

**What you'll see:**
```
=== Cloning Repository ===
Repository: git@github.com:user/repo.git
Branch: main
Cloning repository...
✓ Repository cloned successfully

=== Building Docker Container ===
Building Docker image...
✓ Build successful

=== Starting Container ===
Container started with ID: abc123def456
```

### Container Logs

**Access:**
1. Go to project page
2. Click **"📋 View Logs"** button
3. Real-time logs appear

**What you'll see:**
- Application output
- Error messages
- Debug information
- Server responses

---

## 🐳 Accessing Docker Management

### How to Access Docker Dashboard

**Step 1: Detect Docker**
1. Go to **Servers** page
2. Click on a server
3. If you see "Docker: ✗ Not Installed", click **"🔍 Detect Docker"**
4. Or click **"Ping Server"** which also checks for Docker

**Step 2: Access Dashboard**
1. After Docker is detected, you'll see **"🐳 Docker Management"** button
2. Click it to access the Docker dashboard

**What You'll See:**
- **📊 Overview** - Docker version, containers, images, disk usage
- **🖼️ Images** - List, delete, prune Docker images
- **💾 Volumes** - Manage persistent storage
- **🌐 Networks** - Network configuration
- **🧹 Cleanup** - Free up disk space

### Docker Dashboard Features

**Overview Tab:**
- Docker system information
- Container counts (running/stopped)
- Image statistics
- Disk usage breakdown with reclaimable space

**Images Tab:**
- View all Docker images
- See image sizes
- Delete individual images
- Prune unused images to free space

**Volumes Tab:**
- List all volumes
- View mountpoints
- Delete volumes (with warning)

**Networks Tab:**
- List Docker networks
- View network details
- Delete custom networks

**Cleanup Tab:**
- Prune dangling images
- System-wide cleanup
- See disk space savings

**Direct URL:**
```
http://your-domain.com/servers/{server-id}/docker
```

---

## 🐛 Troubleshooting

### Common Issues

#### "Unable to find image 'xxx:latest'"
**Problem:** Tried to start before deploying  
**Solution:** Click "🚀 Deploy" first, then "▶️ Start"

#### "Permission denied" on deployment
**Problem:** Server permissions not set  
**Solution:** Contact system admin to fix `/var/www/` permissions

#### "Git clone failed: could not read Username"
**Problem:** Private repository, no authentication  
**Solution:**
1. Generate SSH key on server
2. Add to GitHub (https://github.com/settings/keys)
3. Use SSH URL: `git@github.com:user/repo.git`

#### "Port already in use"
**Problem:** Another container using the port  
**Solution:**
- Use unique ports in docker-compose.yml
- Or stop the other container

#### "Build failed"
**Problem:** Code issues, missing dependencies  
**Solution:**
- Check deployment logs
- Fix code issues
- Redeploy

### Getting Help

**Check:**
1. [Troubleshooting Guide](TROUBLESHOOTING.md) - Common solutions
2. [GitHub Issues](https://github.com/yourusername/devflow-pro/issues) - Report bugs
3. [Comprehensive Plan](COMPREHENSIVE_IMPROVEMENT_PLAN.md) - Upcoming fixes

---

## 💡 Best Practices

### ✅ DO:
- Use SSH URLs for private repositories
- Test deployments in staging first
- Monitor deployment logs
- Keep Docker images updated
- Use unique ports for each project
- Enable auto-deploy for CI/CD
- Regular server monitoring

### ❌ DON'T:
- Don't skip deployment step (CREATE → DEPLOY → START)
- Don't use same ports for multiple projects
- Don't delete projects with running containers
- Don't forget to add SSH key for private repos
- Don't ignore failed deployment logs

---

## 🎯 Quick Reference

### Project Lifecycle
```
CREATE → DEPLOY → START → RUNNING
```

### Essential URLs
```
Dashboard:   /dashboard
Servers:     /servers
Projects:    /projects
Deployments: /deployments
Analytics:   /analytics
```

### Keyboard Shortcuts (Coming Soon)
```
Ctrl + K     - Quick search
Ctrl + D     - Deploy current project
Ctrl + S     - View servers
Ctrl + P     - View projects
```

---

## 📞 Support

**Need help?**
- 📖 Read this guide thoroughly
- 🔍 Check [Troubleshooting](TROUBLESHOOTING.md)
- 💬 Ask on GitHub Discussions
- 🐛 Report bugs on GitHub Issues

---

**Happy Deploying! 🚀**

