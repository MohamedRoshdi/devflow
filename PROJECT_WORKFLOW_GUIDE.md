# DevFlow Pro - Project Workflow Guide

**Quick Reference for Project Management**

---

## 🎯 The 3-Step Project Lifecycle

```
┌─────────────┐      ┌─────────────┐      ┌─────────────┐
│   CREATE    │  →   │   DEPLOY    │  →   │    START    │
│   PROJECT   │      │   PROJECT   │      │   PROJECT   │
└─────────────┘      └─────────────┘      └─────────────┘
     │                     │                     │
     │                     │                     │
  Database           Build Docker           Run Container
   Record             Image                 from Image
```

---

## Step 1: CREATE Project

**What It Does:**
- Creates database record
- Stores configuration (repo URL, branch, server, etc.)
- Sets initial status to "stopped"

**What It Does NOT Do:**
- ❌ Clone code
- ❌ Build Docker image
- ❌ Start any containers

**Where:** http://31.220.90.121/projects/create

**Result:**
- Project exists in database
- Status: `stopped`
- Ready for deployment

---

## Step 2: DEPLOY Project ⚠️ REQUIRED BEFORE STARTING!

**What It Does:**
1. **Clone Repository**
   ```bash
   git clone <your-repo-url> /var/www/<project-slug>/
   ```

2. **Build Docker Image**
   - If `docker-compose.yml` exists:
     ```bash
     docker-compose build
     ```
   - If `Dockerfile` exists:
     ```bash
     docker build -t <project-slug>:latest .
     ```
   - If neither exists:
     - DevFlow Pro creates a Dockerfile for you
     - Builds Laravel/Node.js container

3. **Run Setup Commands**
   - `composer install` (if Laravel/PHP)
   - `npm install` (if Node.js)
   - `php artisan migrate` (if Laravel)
   - `php artisan config:cache`

4. **Create Docker Image**
   - Result: `<project-slug>:latest` image exists
   - Stored locally on server
   - Ready to run

**Where:** Project detail page → "🚀 Deploy" button

**Duration:** 2-5 minutes (first deployment)

**Result:**
- Code cloned to `/var/www/<project-slug>/`
- Docker image `<project-slug>:latest` created
- Project ready to start

---

## Step 3: START Project

**What It Does:**
- Runs the Docker container from built image
- Makes application accessible on configured port

**Command Behind the Scenes:**
```bash
docker run -d \
  --name <project-slug> \
  -p <port>:80 \
  <project-slug>:latest
```

**Requirements:**
- ⚠️ Project must be DEPLOYED first
- ⚠️ Docker image must exist

**Where:** Project detail page → "▶️ Start" button

**Result:**
- Container running
- Status: `running`
- Application accessible at `http://server-ip:port`

---

## Common Workflows

### First Time Setup (New Project)

```
1. CREATE project
   ↓
2. DEPLOY project (wait for completion)
   ↓
3. START project
   ↓
4. ACCESS application
```

### Update Code (After Pushing to GitHub)

```
1. Push changes to GitHub
   ↓
2. STOP project (if running)
   ↓
3. DEPLOY project again (pulls latest code)
   ↓
4. START project
   ↓
5. ACCESS updated application
```

### Just Restart Application

```
1. STOP project
   ↓
2. START project
```

---

## Error: "Unable to find image 'xxx:latest' locally"

**Cause:** You tried to START before DEPLOYING

**Solution:**
1. Go to project page
2. Click "🚀 Deploy" button
3. Wait for deployment to complete
4. Then click "▶️ Start" button

---

## Docker Concepts

### Image vs Container

**Docker Image:**
- Like a "template" or "blueprint"
- Built from your code
- Stored on disk
- Can't run by itself
- Example: `ats-pro:latest`

**Docker Container:**
- Like a "running instance"
- Created from an image
- Actually runs your application
- Can be started/stopped
- Example: Container named `ats-pro`

**Analogy:**
- **Image** = Recipe for a cake
- **Container** = The actual baked cake

You need the recipe (image) before you can bake the cake (container)!

### Build vs Run

**Build (during DEPLOY):**
```bash
docker build -t ats-pro:latest .
```
- Creates the image
- One-time process (or when code changes)
- Takes 2-5 minutes

**Run (during START):**
```bash
docker run -d --name ats-pro ats-pro:latest
```
- Starts a container from image
- Can do this multiple times
- Takes seconds

---

## Project States

### stopped
- Project created but not deployed yet
- OR deployed but container not running
- **Can Do:** Deploy, Start (if deployed)

### deploying
- Deployment in progress
- Cloning code, building image
- **Can Do:** Wait, view logs

### running
- Container is active
- Application is accessible
- **Can Do:** Stop, view logs, redeploy

### failed
- Deployment or start failed
- Check logs for errors
- **Can Do:** Fix issues, deploy again

---

## Button Guide

### 🚀 Deploy Button
- **When:** After creating project, or when code changes
- **What:** Clones code + builds Docker image
- **Required:** Before first start
- **Frequency:** Every time you update code

### ▶️ Start Button
- **When:** After deployment completes
- **What:** Runs container from built image
- **Required:** Project must be deployed first
- **Frequency:** When you want to run the app

### ⏹️ Stop Button
- **When:** Application is running
- **What:** Stops the container
- **Result:** App no longer accessible

### 📋 Logs Button
- **When:** Anytime
- **What:** Shows container logs
- **Use:** Debugging, monitoring

---

## Quick Troubleshooting

### Error: "Unable to find image"
**Problem:** Tried to start without deploying  
**Fix:** Deploy first, then start

### Error: "Port already in use"
**Problem:** Another container using same port  
**Fix:** Change port in project settings, or stop other container

### Error: "Permission denied"
**Problem:** Docker permissions not set  
**Fix:** Already fixed (www-data in docker group)

### Deployment Fails
**Problem:** Code issues, missing dependencies  
**Fix:** Check deployment logs, fix code, deploy again

### Container Stops Immediately
**Problem:** Application crash, configuration error  
**Fix:** Check container logs for errors

---

## Best Practices

### ✅ DO:
- Deploy after every code change
- Check deployment logs
- Test locally with Docker before deploying
- Use docker-compose.yml for complex setups
- Stop containers when not in use

### ❌ DON'T:
- Try to start without deploying
- Delete project while container is running
- Change ports while container is running
- Skip testing deployment logs

---

## Complete Example: Deploying ATS Pro

### Step-by-Step

**1. Create Project** (Already Done)
```
Visit: http://31.220.90.121/projects/create
Fill in:
  - Name: ATS Pro
  - Repository: https://github.com/MohamedRoshdi/ats-pro
  - Branch: main
  - Server: Current VPS Server
Click: Create Project
Result: Project #3 created
```

**2. Deploy Project** (DO THIS NOW!)
```
Visit: http://31.220.90.121/projects/3
Click: "🚀 Deploy" button
Watch: Real-time deployment logs
  ⏳ Cloning repository...
  ⏳ Installing dependencies...
  ⏳ Building Docker image...
  ✅ Deployment complete!
Duration: 2-5 minutes
Result: Docker image "ats-pro:latest" created
```

**3. Start Project** (After Deploy)
```
On same page: http://31.220.90.121/projects/3
Click: "▶️ Start" button
Watch: Container starting...
  ✅ Container started!
Duration: A few seconds
Result: Status changes to "running"
```

**4. Access Application**
```
URL: http://31.220.90.121:8000 (or your configured port)
Result: Your ATS Pro application is live!
```

---

## Status Indicators

| Status | Color | Meaning | Next Action |
|--------|-------|---------|-------------|
| stopped | Gray | Not running | Deploy or Start |
| deploying | Blue | Building image | Wait |
| running | Green | Active | Access app |
| failed | Red | Error occurred | Check logs |

---

## File Locations

After deployment, your project files are at:
```
/var/www/<project-slug>/
```

For ATS Pro:
```
/var/www/ats-pro/
├── app/
├── config/
├── database/
├── docker-compose.yml (if exists)
├── Dockerfile (if exists)
└── ... (your project files)
```

---

## Deployment Behind the Scenes

What DevFlow Pro does when you click "Deploy":

```bash
# 1. Create project directory
mkdir -p /var/www/ats-pro
cd /var/www/ats-pro

# 2. Clone repository
git clone https://github.com/MohamedRoshdi/ats-pro .
git checkout main

# 3. Check for docker-compose.yml
if [ -f docker-compose.yml ]; then
    # Use your Docker configuration
    docker-compose build
else
    # Create Dockerfile for Laravel
    # Build image
    docker build -t ats-pro:latest .
fi

# 4. Run setup commands
composer install
npm install
php artisan migrate
php artisan config:cache

# 5. Mark deployment complete
# Image "ats-pro:latest" now exists
```

---

## Remember

**The Golden Rule:**
```
CREATE → DEPLOY → START
```

**Never skip DEPLOY!**
- Creating a project just stores info in database
- Deploying builds the Docker image
- Starting runs the container

**Think of it like:**
- CREATE = Write down a recipe
- DEPLOY = Prepare ingredients and cook
- START = Serve the meal

You can't serve (START) without cooking (DEPLOY) first!

---

## Your Current Status

**Project:** ATS Pro (ID: 3)  
**Status:** stopped  
**Deployed:** ❌ NO (0 deployments)  
**Can Start:** ❌ NO (must deploy first)  

**Action Required:**
1. Visit: http://31.220.90.121/projects/3
2. Click: "🚀 Deploy"
3. Wait for deployment
4. Then click: "▶️ Start"

---

**Need Help?** Check the deployment logs if anything fails!

