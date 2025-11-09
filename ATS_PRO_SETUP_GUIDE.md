# ATS Pro Project Setup Guide

**Project:** ATS Pro  
**Repository:** https://github.com/MohamedRoshdi/ats-pro  
**Status:** Ready to deploy with DevFlow Pro  

---

## ✅ Issue Fixed!

**Problem:** Getting 500 error when creating project  
**Cause:** ProjectShow component using authorize() policy  
**Fix:** Changed to direct user_id check  
**Status:** ✅ Fixed and deployed!  

**You can now create projects without 500 errors!**

---

## 🚀 How to Add Your ATS Pro Project

### Step 1: Make Sure Your Repository is Ready

Your ATS Pro is already on GitHub:
```
https://github.com/MohamedRoshdi/ats-pro
```

**Check if it has Docker:**
- Does your repo have `docker-compose.yml`?
- If YES → DevFlow Pro will use Docker
- If NO → DevFlow Pro will create Docker container for you

---

### Step 2: Create Project in DevFlow Pro

**Visit:** http://31.220.90.121/projects/create

**Fill in the form:**

```
Project Name: ATS Pro
Slug: ats-pro (will auto-generate)

Server Configuration:
└─ Select: "Current VPS Server" (the one with green online badge)

Repository:
├─ Repository URL: https://github.com/MohamedRoshdi/ats-pro
└─ Branch: main (or your default branch name)

Framework & Runtime:
├─ Framework: Laravel
├─ PHP Version: 8.2 (or match your project)
└─ Node Version: 20

Build Configuration:
├─ Root Directory: /
├─ Build Command: npm run build (if you need to compile assets)
└─ Start Command: (leave empty for Docker/Laravel)

Options:
└─ Auto-deploy: ☐ (uncheck for now, enable later)
```

**Click:** "Create Project"

---

### Step 3: What Happens After Creation

**If Project Created Successfully:**
- ✅ Redirects to project details page
- ✅ Shows project information
- ✅ "Deploy" button available
- ✅ Project status: "stopped"

**If Still Getting 500:**
- The fix was just deployed
- Try refreshing the page (Ctrl+F5)
- Or try creating again

---

### Step 4: Deploy Your ATS Pro

**On Project Details Page:**

1. **Click:** "🚀 Deploy" button
2. **Confirm:** Deployment modal
3. **Watch:** Real-time deployment logs

**What DevFlow Pro Will Do:**

**If you have docker-compose.yml:**
```bash
1. Clone repository to /var/www/ats-pro
2. Pull latest from main branch
3. Run: docker-compose down
4. Run: docker-compose up -d --build
5. Check container status
6. Update project status
```

**If you DON'T have docker-compose.yml:**
```bash
1. Clone repository
2. Create Dockerfile for Laravel
3. Build Docker image
4. Start container
5. Run Laravel commands:
   - composer install
   - php artisan migrate
   - php artisan config:cache
```

---

## 🐳 If Your ATS Pro Uses Docker

### What You Need in Your Repository

**Required:**
```
ats-pro/
├── docker-compose.yml     ← Main Docker config
├── Dockerfile            ← If referenced in docker-compose
├── .env.example          ← Environment template
├── (your Laravel files)
```

### Example docker-compose.yml for Laravel Multi-Tenant:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ats-pro-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=ats_pro
      - REDIS_HOST=redis
    networks:
      - ats-network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: ats-pro-nginx
    restart: unless-stopped
    ports:
      - "8080:80"  # Use unique port
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - ats-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: ats-pro-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ats_pro
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_PASSWORD: secret
      MYSQL_USER: ats_pro
    volumes:
      - mysql-data:/var/lib/mysql
    ports:
      - "3307:3306"  # Use unique port
    networks:
      - ats-network

  redis:
    image: redis:alpine
    container_name: ats-pro-redis
    restart: unless-stopped
    ports:
      - "6380:6379"  # Use unique port
    networks:
      - ats-network

networks:
  ats-network:
    driver: bridge

volumes:
  mysql-data:
```

**Important Notes:**
- Use **unique ports** (8080, 3307, 6380) to avoid conflicts
- Use **unique container names** (ats-pro-app, ats-pro-mysql, etc.)
- If you have multiple projects, each needs different ports

---

## 📝 Environment Variables

### In DevFlow Pro Project Settings

You can add environment variables in the project creation form:

**Common Variables for ATS Pro:**
```
APP_NAME=ATS Pro
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=http://31.220.90.121:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ats_pro
DB_USERNAME=ats_pro
DB_PASSWORD=secret

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

These will be automatically injected into your .env file during deployment.

---

## 🔄 Deployment Process for ATS Pro

### First Deployment

1. **Create Project** (form above)
2. **Click Deploy**
3. **Wait** (~2-5 minutes for first deploy)
4. **Check Logs** (real-time in dashboard)
5. **Verify** (visit http://31.220.90.121:8080)

### Subsequent Deployments

**Manual:**
- Open project
- Click "Deploy"
- Done!

**Automatic (Webhook):**
- Enable "auto-deploy" in project settings
- Configure GitHub webhook
- Push to GitHub → Auto-deploys!

---

## 🎯 Troubleshooting ATS Pro Deployment

### If Deployment Fails

**Check 1: Repository Access**
```bash
# Make sure repo is public or SSH key is configured
# DevFlow Pro needs to clone your repository
```

**Check 2: Docker Compose File**
```bash
# Ensure docker-compose.yml is valid
# Test locally first: docker-compose up
```

**Check 3: Port Conflicts**
```bash
# Make sure ports are not already in use
# Use unique ports: 8080, 3307, 6380, etc.
```

**Check 4: Deployment Logs**
- View real-time logs in DevFlow Pro
- Shows any errors during deployment
- Docker build output visible

---

## 📊 Expected Result

### After Successful Deployment

**On Dashboard:**
- ✅ ATS Pro project listed
- ✅ Status: "running" (green)
- ✅ Server: Current VPS Server
- ✅ Last deployed: timestamp

**Access Your Application:**
```
http://31.220.90.121:8080
```
(Port depends on your docker-compose.yml configuration)

**Docker Containers Running:**
```bash
ssh root@31.220.90.121
docker ps | grep ats-pro

# Should show:
# ats-pro-app
# ats-pro-nginx  
# ats-pro-mysql
# ats-pro-redis
```

---

## 💡 Recommendations for ATS Pro

### Multi-Tenant Configuration

Since ATS Pro is likely multi-tenant, ensure:

1. **Tenant Databases:**
   - Central database for system
   - Separate databases per tenant
   - Configure in .env

2. **Tenant Domains:**
   - Use DevFlow Pro domain management
   - Add tenant subdomains
   - Configure SSL per domain

3. **Storage:**
   - Configure tenant-specific storage
   - Use separate folders per tenant
   - Track storage usage

### Performance Optimization

For multi-tenant applications:
- Enable Redis caching
- Use queue workers
- Configure Horizon (if using)
- Set up proper indexes

---

## 🔗 Quick Reference

**Your ATS Pro Details:**
```
Repository: https://github.com/MohamedRoshdi/ats-pro
Framework: Laravel (likely multi-tenant)
Recommended Port: 8080 (nginx)
Database Port: 3307 (to avoid conflict)
Redis Port: 6380
```

**DevFlow Pro URLs:**
```
Create Project: http://31.220.90.121/projects/create
View Projects: http://31.220.90.121/projects
Dashboard: http://31.220.90.121/dashboard
```

---

## ✅ Checklist for Adding ATS Pro

- [x] GitHub repository ready: https://github.com/MohamedRoshdi/ats-pro
- [x] Server added in DevFlow Pro
- [x] 500 error fixed (authorization)
- [ ] Add docker-compose.yml to repo (if using Docker)
- [ ] Create project in DevFlow Pro
- [ ] Configure environment variables
- [ ] Deploy project
- [ ] Test application
- [ ] Configure domains (if needed)
- [ ] Setup SSL (if needed)
- [ ] Enable webhooks (optional)

---

## 🎯 Next Steps

**RIGHT NOW:**

1. **Visit:** http://31.220.90.121/projects/create
2. **Fill in:**
   - Name: ATS Pro
   - Repository: https://github.com/MohamedRoshdi/ats-pro
   - Server: Select your server
   - Framework: Laravel
3. **Click:** "Create Project"
4. **Deploy:** Click the deploy button
5. **Monitor:** Watch deployment logs
6. **Access:** Your ATS Pro application!

---

**Questions about Docker configuration for ATS Pro? I can help you create a proper docker-compose.yml file!**

**Ready to deploy your ATS Pro?** Visit: http://31.220.90.121/projects/create

