# DevFlow Pro - Complete User Guide

**Version 2.2.1** | Last Updated: November 11, 2025

---

## 📚 Table of Contents

1. [Getting Started](#getting-started)
2. [Managing Servers](#managing-servers)
3. [Creating Projects](#creating-projects)
4. [Editing Projects](#editing-projects)
5. [Deploying Applications](#deploying-applications)
6. [Managing Containers](#managing-containers)
7. [Project Docker Management](#project-docker-management--new-v221) ⭐ NEW!
8. [Viewing Logs](#viewing-logs)
9. [Troubleshooting](#troubleshooting)

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

## 🐳 Project Docker Management ⭐ NEW! v2.2.1

### Overview

Each project now has its own dedicated Docker management panel showing **only** the Docker resources related to that specific project. No more confusion with other projects' containers and images!

### Accessing Docker Management

**Location:**
1. Navigate to your project page
2. Scroll down past the project stats
3. Find the **"🐳 Docker Management"** section
4. Three tabs available: **Overview**, **Images**, **Logs**

### 📊 Overview Tab

**What you see:**
- **Container Status Card**
  - Container name
  - Current state (Running/Stopped/Not Found)
  - Exposed ports
  - Image being used
  
- **Real-Time Stats** (when running)
  - 🖥️ CPU Usage percentage
  - 💾 Memory Usage percentage
  - 🌐 Network I/O (bytes in/out)
  - 💿 Disk I/O (read/write)

**Container Actions:**

**If Container is Running:**
- **⏹️ Stop Container** - Stops and removes container
- **🔄 Restart Container** - Restarts the container
- **💾 Backup Container** - Creates timestamped backup image

**If Container is Stopped/Not Found:**
- **▶️ Start Container** - Starts the container
- **🔨 Build Image & Start** - Builds from code then starts

**Quick Stats Cards:**
- Docker Images count (for this project)
- Project Status indicator
- Server information

### 🖼️ Images Tab

**What you see:**
- Table of all Docker images related to this project
- Only shows images matching your project slug
- Filtered from all server images

**Image Information:**
- **Repository** - Image name
- **Tag** - Version tag (latest, v1.0, etc.)
- **Image ID** - First 12 characters
- **Created** - How long ago (e.g., "2 days ago")
- **Size** - Disk space used

**Actions:**
- **🔨 Build New Image** - Creates fresh image from code
- **🗑️ Delete** - Remove specific image (with confirmation)

**Use Cases:**
- **Build New Image:** After code changes
- **Delete Old Images:** Clean up unused versions to save disk space
- **Check Image Size:** Monitor disk usage

### 📝 Logs Tab

**What you see:**
- Real-time container logs in terminal style
- Green text on dark background (classic terminal look)
- Automatically scrollable

**Log Controls:**
- **Select Line Count:** Dropdown to choose 50, 100, 200, or 500 lines
- **🔄 Refresh Logs:** Update to latest logs
- Logs auto-scroll to show recent entries

**When to use:**
- **Debugging:** Check application errors
- **Monitoring:** Watch application behavior
- **Troubleshooting:** Identify issues in real-time

**Example Log View:**
```
[2025-11-11 13:45:23] Starting application...
[2025-11-11 13:45:24] Database connected successfully
[2025-11-11 13:45:25] Server listening on port 3000
[2025-11-11 13:45:30] GET /api/users - 200 OK
```

### 🔧 Common Tasks

#### Task 1: Start Your Project Container

**Steps:**
1. Go to your project page
2. Find Docker Management section
3. Click **Overview** tab
4. If no container exists, click **"▶️ Start Container"**
5. Wait for confirmation message
6. Check container status shows "Running"

**Troubleshooting:**
- If you see "name already in use" error → Don't worry! The system automatically handles this now
- System will stop and remove old container automatically
- Try clicking Start again

#### Task 2: View Container Logs for Debugging

**Steps:**
1. Open Docker Management section
2. Click **Logs** tab
3. Select number of lines (start with 100)
4. Click **"🔄 Refresh Logs"** for latest
5. Scroll through logs to find errors
6. Increase line count if needed

**Tips:**
- Look for red text or "ERROR" keywords
- Check timestamps to find recent issues
- Use browser's Ctrl+F to search logs

#### Task 3: Clean Up Old Docker Images

**Steps:**
1. Open Docker Management section
2. Click **Images** tab
3. Identify old/unused images
4. Click **"🗑️ Delete"** on old image
5. Confirm deletion
6. Check size freed up

**When to do this:**
- After multiple deployments
- Running low on disk space
- Having images from old versions

#### Task 4: Backup Your Container

**Steps:**
1. Make sure container is running
2. Go to **Overview** tab
3. Click **"💾 Backup Container"**
4. Wait for confirmation
5. New backup image created
6. Check **Images** tab for backup

**Backup Name Format:**
```
project-slug-backup-2025-11-11-13-45-30
```

### 🛡️ Security Features

**Access Control:**
- ✅ Only project owners can access Docker controls
- ✅ User authentication required
- ✅ Server ownership validated
- ✅ All operations logged

**Isolation:**
- ✅ Each project sees only its own images
- ✅ Containers filtered by project slug
- ✅ No cross-project interference
- ✅ Secure command execution

### 🚨 Conflict Resolution (Automatic)

**Problem:** Container name already in use
**Old Behavior:** Error message, manual cleanup needed
**New Behavior:** ⭐ Automatic resolution!

**How it works:**
1. You click "Start Container"
2. System checks for existing container with same name
3. Automatically stops old container
4. Automatically removes old container with force flag
5. Starts new container
6. Success!

**Technical Details:**
```bash
# What happens behind the scenes:
docker stop project-slug 2>/dev/null || true
docker rm -f project-slug 2>/dev/null || true
docker run -d --name project-slug -p 8001:80 project-slug:latest
```

### 💡 Pro Tips

**Tip 1: Monitor Resource Usage**
- Keep Overview tab open during high traffic
- Watch CPU/Memory stats
- Restart if resources maxed out

**Tip 2: Regular Image Cleanup**
- Delete old images weekly
- Keep only last 2-3 versions
- Saves significant disk space

**Tip 3: Check Logs After Deployment**
- Always check logs after deploying
- Verify application started correctly
- Catch errors early

**Tip 4: Use Backup Before Major Updates**
- Backup container before big changes
- Easy rollback if something breaks
- Backup includes all container data

### 🔍 Troubleshooting Docker Issues

**Issue: Container won't start**
**Solution:**
1. Check Images tab - is image present?
2. If no image, click "Build Image"
3. Check server has enough resources
4. View logs for error messages

**Issue: Can't see logs**
**Solution:**
1. Ensure container is running
2. Click "Refresh Logs"
3. Try lower line count (50)
4. Check server connectivity

**Issue: Image build fails**
**Solution:**
1. Check repository is accessible
2. Verify Dockerfile exists or can be generated
3. Check server has Docker installed
4. Review deployment logs

**Issue: Stats not showing**
**Solution:**
1. Container must be running
2. Refresh the page
3. Check server Docker version (needs 20.10+)
4. Verify user permissions

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

### Critical Issues (MUST READ!)

#### ❌ Livewire Actions Not Working (500 Errors)

**Symptoms:** Clicking buttons causes 500 errors, Docker actions dead

**Root Cause:** Livewire JavaScript assets not published

**Quick Fix:**
```bash
cd /var/www/devflow-pro
php artisan livewire:publish --assets
systemctl restart php8.2-fpm
```

**Browser:** Hard refresh (`Ctrl + Shift + R`)

#### ❌ Docker Container Can't Connect to MySQL

**Symptoms:** `host.docker.internal failed: Name does not resolve`

**Root Cause:** On Linux, use `172.17.0.1` not `host.docker.internal`

**Quick Fix:**
```bash
# Update container:
docker exec container-name sh -c 'sed -i "s/DB_HOST=.*/DB_HOST=172.17.0.1/" .env'
docker exec container-name php artisan config:clear

# Grant MySQL access:
mysql -e "GRANT ALL PRIVILEGES ON db.* TO 'user'@'172.17.%';"
```

#### ❌ Changes Not Showing After Deployment

**Symptoms:** Deployed but old version still showing

**Root Cause:** Browser cache

**Quick Fix:**
- Hard refresh: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
- Or test in incognito window

---

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
- System auto-resolves conflicts now!

#### "Build failed"
**Problem:** Code issues, missing dependencies  
**Solution:**
- Check deployment logs
- Fix code issues
- Redeploy

#### "Method not found on component"
**Problem:** Livewire cache stale

**Solution:**
```bash
composer dump-autoload --optimize
php artisan optimize:clear
systemctl restart php8.2-fpm
```

### Getting Help

**Check:**
1. 📖 [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Comprehensive troubleshooting guide
2. 🔍 [ATS_PRO_FINAL_FIX.md](ATS_PRO_FINAL_FIX.md) - Docker MySQL connection guide
3. 🐛 [GitHub Issues](https://github.com/yourusername/devflow-pro/issues) - Report bugs
4. 📋 Application logs: `/var/www/devflow-pro/storage/logs/laravel.log`

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

