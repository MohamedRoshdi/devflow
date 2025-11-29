# VPS Deployment Guide - Docker Projects on NileStack Server

> Complete guide for deploying Laravel/PHP projects with Docker on VPS, including Nginx reverse proxy configuration and common issue fixes.

## Table of Contents
1. [Server Architecture](#server-architecture)
2. [Nginx Reverse Proxy Configuration](#nginx-reverse-proxy-configuration)
3. [Environment Configuration](#environment-configuration)
4. [Storage Permissions](#storage-permissions)
5. [Deployment Checklist](#deployment-checklist)
6. [Common Issues & Solutions](#common-issues--solutions)
7. [Quick Fix Scripts](#quick-fix-scripts)

---

## Server Architecture

### Current Setup (NileStack Server: 31.220.90.121)

```
┌─────────────────────────────────────────────────────────────────┐
│                    Host Nginx (Port 80/443)                      │
│                    Reverse Proxy to Docker                       │
└─────────────────────────────────────────────────────────────────┘
                              │
    ┌─────────────────────────┼─────────────────────────┐
    │                         │                         │
    ▼                         ▼                         ▼
┌────────────┐    ┌──────────────────────┐    ┌────────────────┐
│ Portfolio  │    │    Workspace Pro     │    │    ATS Pro     │
│ Port: 8003 │    │     Port: 8002       │    │   Port: 8000   │
│            │    │                      │    │                │
│ - FrankenPHP│    │ - Nginx Container    │    │ - Nginx        │
│ - PostgreSQL│    │ - PHP-FPM Container  │    │ - PHP-FPM      │
│ - Redis     │    │ - MySQL Container    │    │ - MySQL        │
└────────────┘    └──────────────────────┘    └────────────────┘
```

### Port Assignments

| Project | Docker Port | Host Binding | Domain |
|---------|-------------|--------------|--------|
| Portfolio | 8000 | 8003 | nilestack.duckdns.org |
| Workspace Pro | 80 | 8002 | workspace.nilestack.duckdns.org |
| ATS Pro | 80 | 8000 | ats.nilestack.duckdns.org |
| DevFlow Pro | 80 | 8001 | admin.nilestack.duckdns.org |

---

## Nginx Reverse Proxy Configuration

### CRITICAL: Always Use Proxy Pass to Docker

**WRONG Configuration (causes permission errors):**
```nginx
# DON'T DO THIS - Uses host PHP-FPM which has different permissions
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

**CORRECT Configuration (proxy to Docker container):**
```nginx
server {
    listen 80;
    listen [::]:80;
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    ssl_certificate /etc/nginx/ssl/self-signed.crt;
    ssl_certificate_key /etc/nginx/ssl/self-signed.key;

    server_name your-subdomain.nilestack.duckdns.org;

    charset utf-8;

    location / {
        proxy_pass http://127.0.0.1:YOUR_DOCKER_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    access_log /var/log/nginx/your-project-access.log;
    error_log /var/log/nginx/your-project-error.log;
}
```

### Project-Specific Nginx Configs

**Portfolio (nilestack.duckdns.org):**
```bash
# /etc/nginx/sites-available/portfolio-main
proxy_pass http://127.0.0.1:8003;
```

**Workspace Pro (workspace.nilestack.duckdns.org):**
```bash
# /etc/nginx/sites-available/workspace-pro
proxy_pass http://127.0.0.1:8002;
```

**ATS Pro (ats.nilestack.duckdns.org):**
```bash
# /etc/nginx/sites-available/ats-pro
proxy_pass http://127.0.0.1:8000;
```

---

## Environment Configuration

### .env File Rules

#### 1. Values with Spaces MUST be Quoted
```bash
# WRONG - Will cause parse errors
APP_NAME=ATS Pro

# CORRECT
APP_NAME="ATS Pro"
```

#### 2. Database Host Must Match Docker Service Name
```bash
# WRONG - Won't work inside Docker container
DB_HOST=127.0.0.1
DB_HOST=localhost

# CORRECT - Use Docker service name from docker-compose.yml
DB_HOST=mysql      # For MySQL services
DB_HOST=postgres   # For PostgreSQL services
DB_HOST=db         # If service is named 'db'
```

#### 3. Redis Host Must Match Docker Service Name
```bash
# WRONG
REDIS_HOST=127.0.0.1
REDIS_HOST=localhost

# CORRECT
REDIS_HOST=redis
```

### Example .env for Docker

```bash
APP_NAME="Your Project Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-subdomain.nilestack.duckdns.org

# Database - Use Docker service name
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Redis - Use Docker service name
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## Storage Permissions

### The Permission Problem

When Docker containers run as a specific user (e.g., `www` with UID 1000), but host files are owned by a different user (e.g., `www-data` with UID 33), permission errors occur.

### Fix: Match Container User UID

```bash
# Check container user
docker exec container-name whoami
docker exec container-name id
# Example output: uid=1000(www) gid=1000(www)

# Fix permissions on host (use the UID from container)
chown -R 1000:1000 /var/www/project-name/storage /var/www/project-name/bootstrap/cache
chmod -R 775 /var/www/project-name/storage /var/www/project-name/bootstrap/cache
```

### Universal Fix Script

```bash
#!/bin/bash
# fix-permissions.sh - Run on host

PROJECT_PATH=$1
CONTAINER_UID=${2:-1000}

if [ -z "$PROJECT_PATH" ]; then
    echo "Usage: ./fix-permissions.sh /var/www/project-name [container_uid]"
    exit 1
fi

echo "Fixing permissions for $PROJECT_PATH with UID $CONTAINER_UID..."

# Change ownership to match container user
chown -R $CONTAINER_UID:$CONTAINER_UID $PROJECT_PATH/storage
chown -R $CONTAINER_UID:$CONTAINER_UID $PROJECT_PATH/bootstrap/cache

# Set directory permissions
find $PROJECT_PATH/storage -type d -exec chmod 775 {} \;
find $PROJECT_PATH/bootstrap/cache -type d -exec chmod 775 {} \;

# Set file permissions
find $PROJECT_PATH/storage -type f -exec chmod 664 {} \;
find $PROJECT_PATH/bootstrap/cache -type f -exec chmod 664 {} \;

echo "Done!"
```

---

## Deployment Checklist

### Before Deployment

- [ ] Verify Docker container ports in docker-compose.yml
- [ ] Ensure .env values with spaces are quoted
- [ ] Verify DB_HOST and REDIS_HOST use Docker service names
- [ ] Check storage directories exist

### During Deployment

```bash
# 1. Pull latest code
cd /var/www/project-name
git pull origin main

# 2. Start containers
docker compose up -d

# 3. Wait for containers to be healthy
docker compose ps

# 4. Run migrations
docker exec container-name php artisan migrate --force

# 5. Clear and rebuild caches
docker exec container-name php artisan config:clear
docker exec container-name php artisan cache:clear
docker exec container-name php artisan view:clear
docker exec container-name php artisan config:cache
docker exec container-name php artisan route:cache
docker exec container-name php artisan view:cache

# 6. Rebuild frontend assets (if needed)
docker exec container-name npm run build
```

### After Deployment

- [ ] Check Nginx config uses proxy_pass (not fastcgi_pass)
- [ ] Reload Nginx: `nginx -t && systemctl reload nginx`
- [ ] Fix storage permissions (use container UID)
- [ ] Test all routes in browser
- [ ] Clear browser cache if seeing old assets

---

## Common Issues & Solutions

### Issue 1: "file_put_contents(): Failed to open stream: Permission denied"

**Cause:** Container user can't write to storage directory.

**Solution:**
```bash
# Find container UID
docker exec container-name id

# Fix permissions from host
chown -R 1000:1000 /var/www/project-name/storage /var/www/project-name/bootstrap/cache
chmod -R 777 /var/www/project-name/storage /var/www/project-name/bootstrap/cache
```

### Issue 2: "SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed"

**Cause:** .env has `DB_HOST=127.0.0.1` instead of Docker service name.

**Solution:**
```bash
# Edit .env
sed -i 's/^DB_HOST=127.0.0.1$/DB_HOST=mysql/' /var/www/project-name/.env
sed -i 's/^DB_HOST=localhost$/DB_HOST=mysql/' /var/www/project-name/.env

# Clear config cache
docker exec container-name php artisan config:clear
docker exec container-name php artisan config:cache

# Restart container
docker compose restart app
```

### Issue 3: "Connection refused" for Redis

**Cause:** .env has `REDIS_HOST=127.0.0.1` instead of Docker service name.

**Solution:**
```bash
# Edit .env
sed -i 's/^REDIS_HOST=127.0.0.1$/REDIS_HOST=redis/' /var/www/project-name/.env
sed -i 's/^REDIS_HOST=localhost$/REDIS_HOST=redis/' /var/www/project-name/.env

# Clear and recache config
docker exec container-name php artisan config:clear
docker exec container-name php artisan config:cache

# Restart containers
docker compose restart
```

### Issue 4: "Failed to parse dotenv file. Encountered unexpected whitespace"

**Cause:** .env value contains spaces without quotes.

**Solution:**
```bash
# Find the problematic line
grep -n " " /var/www/project-name/.env | grep -v "^#"

# Fix by adding quotes
# Before: APP_NAME=My App Name
# After:  APP_NAME="My App Name"
sed -i 's/^APP_NAME=\(.*\)$/APP_NAME="\1"/' /var/www/project-name/.env
```

### Issue 5: Old CSS/JS Assets Loading

**Cause:** View cache contains old asset references.

**Solution:**
```bash
# Rebuild assets
docker exec container-name npm run build

# Clear all caches
docker exec container-name php artisan cache:clear
docker exec container-name php artisan config:clear
docker exec container-name php artisan view:clear
docker exec container-name php artisan config:cache
docker exec container-name php artisan view:cache

# Restart container
docker compose restart container-name
```

### Issue 6: HTTP 500 with No Error Details

**Cause:** Usually nginx using wrong PHP processor.

**Solution:**
```bash
# Check nginx config
cat /etc/nginx/sites-enabled/your-project

# If you see fastcgi_pass, change to proxy_pass
# Edit the config to use:
# proxy_pass http://127.0.0.1:YOUR_DOCKER_PORT;

# Test and reload nginx
nginx -t && systemctl reload nginx
```

---

## Quick Fix Scripts

### Master Fix Script

Save as `/root/scripts/fix-deployment.sh`:

```bash
#!/bin/bash
# fix-deployment.sh - Fixes common deployment issues

set -e

PROJECT=$1
DOCKER_PORT=$2
CONTAINER=$3

if [ -z "$PROJECT" ] || [ -z "$DOCKER_PORT" ] || [ -z "$CONTAINER" ]; then
    echo "Usage: ./fix-deployment.sh project-name docker-port container-name"
    echo "Example: ./fix-deployment.sh portfolio 8003 nilestack-portfolio"
    exit 1
fi

PROJECT_PATH="/var/www/$PROJECT"

echo "=== Fixing deployment for $PROJECT ==="

# 1. Fix .env hosts
echo "1. Fixing .env database/redis hosts..."
sed -i 's/^DB_HOST=127.0.0.1$/DB_HOST=mysql/' $PROJECT_PATH/.env 2>/dev/null || true
sed -i 's/^DB_HOST=localhost$/DB_HOST=mysql/' $PROJECT_PATH/.env 2>/dev/null || true
sed -i 's/^REDIS_HOST=127.0.0.1$/REDIS_HOST=redis/' $PROJECT_PATH/.env 2>/dev/null || true
sed -i 's/^REDIS_HOST=localhost$/REDIS_HOST=redis/' $PROJECT_PATH/.env 2>/dev/null || true

# 2. Fix permissions
echo "2. Fixing storage permissions..."
chown -R 1000:1000 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache 2>/dev/null || true
chmod -R 777 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache

# 3. Clear caches
echo "3. Clearing caches..."
docker exec $CONTAINER php artisan config:clear 2>/dev/null || true
docker exec $CONTAINER php artisan cache:clear 2>/dev/null || true
docker exec $CONTAINER php artisan view:clear 2>/dev/null || true
docker exec $CONTAINER php artisan config:cache 2>/dev/null || true
docker exec $CONTAINER php artisan view:cache 2>/dev/null || true

# 4. Verify nginx config
echo "4. Checking nginx config..."
NGINX_CONFIG="/etc/nginx/sites-available/$PROJECT"
if grep -q "fastcgi_pass" $NGINX_CONFIG 2>/dev/null; then
    echo "WARNING: Nginx is using fastcgi_pass. Should use proxy_pass to port $DOCKER_PORT"
    echo "Run: nano $NGINX_CONFIG"
fi

echo "=== Done! ==="
```

### Deploy All Projects Script

Save as `/root/scripts/deploy-all.sh`:

```bash
#!/bin/bash
# deploy-all.sh - Deploy and fix all projects

echo "=== Deploying All Projects ==="

# Portfolio
echo "--- Portfolio ---"
cd /var/www/portfolio
git pull origin main
docker compose down && docker compose up -d
sleep 10
docker exec nilestack-portfolio npm run build
docker exec nilestack-portfolio php artisan config:cache
docker exec nilestack-portfolio php artisan view:cache
chown -R 1000:1000 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Workspace Pro
echo "--- Workspace Pro ---"
cd /var/www/workspace-pro
git pull origin main
docker compose restart
sleep 5
docker exec workspace-pro-app php artisan config:cache
docker exec workspace-pro-app php artisan view:cache
chown -R 1000:1000 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# ATS Pro
echo "--- ATS Pro ---"
cd /var/www/ats-pro
git pull origin main
docker compose restart
sleep 5
docker exec ats-app php artisan config:cache
docker exec ats-app php artisan view:cache
chown -R 1000:1000 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Reload nginx
nginx -t && systemctl reload nginx

echo "=== All Projects Deployed ==="
```

---

## Summary: Key Rules

1. **Always use proxy_pass** in Nginx for Docker projects (never fastcgi_pass)
2. **Quote .env values** that contain spaces
3. **Use Docker service names** for DB_HOST and REDIS_HOST (not 127.0.0.1)
4. **Match storage permissions** to container user UID (usually 1000)
5. **Clear caches** after any .env or config change
6. **Restart containers** after major changes

---

## Quick Reference

```bash
# Fix all common issues for a project
/root/scripts/fix-deployment.sh project-name docker-port container-name

# Example for Portfolio
/root/scripts/fix-deployment.sh portfolio 8003 nilestack-portfolio

# Example for Workspace
/root/scripts/fix-deployment.sh workspace-pro 8002 workspace-pro-app

# Example for ATS
/root/scripts/fix-deployment.sh ats-pro 8000 ats-app
```

---

*Last Updated: November 29, 2025*
*Server: 31.220.90.121 (NileStack)*
