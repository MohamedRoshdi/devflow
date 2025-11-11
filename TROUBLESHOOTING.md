# DevFlow Pro - Troubleshooting Guide

## Common Issues and Solutions

---

## ⭐ NEW Issues & Solutions (v2.4.0)

### Environment Management Issues

#### 1. Environment Selection Not Persisting

**Symptoms:** Select environment (e.g., Development), refresh page, back to Production

**Cause:** `environment` field was missing from Project model's `$fillable` array

**Solution:**
```php
// Fixed in v2.4.0
// app/Models/Project.php now includes:
protected $fillable = [
    // ...
    'environment',  // ✅ Added
    // ...
];
```

**Verification:**
1. Select any environment
2. Refresh page
3. Should stay selected ✓

---

#### 2. Environment Not Affecting Deployed App

**Symptoms:** Set to Development, but app still shows generic 500 errors

**Cause:** APP_DEBUG wasn't being injected (only APP_ENV was)

**Solution:**
```
Fixed in v2.4.0 - Now auto-injects:
- APP_ENV (from your selection)
- APP_DEBUG (true for local/dev, false for staging/prod)
```

**Important:** Must restart container after changing environment!
```
Docker Tab → Restart Container
```

---

#### 3. Missing APP_KEY Error

**Symptoms:** "No application encryption key has been specified"

**Solution:**
Add APP_KEY to environment variables:
1. Go to Environment tab
2. Click "Add Variable"
3. Name: `APP_KEY`
4. Value: Generate via `php artisan key:generate --show`
5. Or use: `base64:K05BLhVEm2Qtu5SPGrH6BZIvOMJYlSVwyBlwS6gOjuk=`
6. Restart container

**Auto-Fix:** Newer deployments include APP_KEY automatically

---

### Deployment UX Issues

#### 1. Deploy Button - No Feedback

**Symptoms:** Click Deploy, nothing seems to happen, click multiple times

**Solution (Fixed in v2.4.0):**
- Deploy button now shows instant spinner
- Full-screen loading overlay appears
- Auto-redirects to deployment page
- Button disabled (prevents double-click)

**If Still No Feedback:**
- Hard refresh browser: `Ctrl + Shift + R`
- Clear browser cache
- Check browser console for errors

---

#### 2. Multiple Deployments Started

**Symptoms:** Deployment logs show multiple builds for same project

**Cause:** Double-clicking deploy button (old version)

**Prevention (Fixed in v2.4.0):**
- Button disables immediately on click
- Loading overlay prevents additional clicks
- Auto-redirect after deployment starts

**If It Happens:**
- Let both deployments complete
- They won't conflict (auto cleanup)
- Check Deployments tab for all instances

---

### Alpine.js / JavaScript Errors

#### 1. "Detected Multiple Instances of Alpine Running"

**Symptoms:** Browser console warning about multiple Alpine instances

**Cause:** Alpine.js imported manually + bundled with Livewire v3

**Solution (Fixed in v2.4.0):**
```javascript
// resources/js/app.js
// OLD (wrong):
import Alpine from 'alpinejs';  // ❌ Remove
Alpine.start();

// NEW (correct):
// Livewire v3 includes Alpine - don't import!
```

**Result:** 54% smaller bundle, no warnings

---

#### 2. "Alpine Expression Error: Unexpected token '}'"

**Symptoms:** Alpine errors in console, features not working

**Common Causes & Solutions:**

**Cause A: Chained $set() calls**
```blade
<!-- Wrong: -->
wire:click="$set('a', ''); $set('b', '')"  ❌

<!-- Fix: Create method -->
wire:click="clearFilters"  ✅
```

**Cause B: wire:click.stop without expression**
```blade
<!-- Wrong: -->
<div wire:click.stop>  ❌

<!-- Fix: Use Alpine -->
<div @click.stop>  ✅
```

**Solution:** Fixed in all modals in v2.4.0

---

#### 3. "The deferred DOM Node could not be resolved"

**Symptoms:** Errors when switching tabs or using nested Livewire components

**Cause:** Livewire trying to update hidden Alpine tab content

**Solution (Fixed in v2.4.0):**
```blade
<div x-show="activeTab === 'docker'" wire:ignore.self>
    @livewire('child-component')
</div>
```

**Explanation:** `wire:ignore.self` tells Livewire to skip morphing the container but allow child components to update

---

### Performance Issues

#### 1. Slow Page Loads

**Symptoms:** Pages take 2-3 seconds to load

**Solutions:**

**Check 1: Browser Cache**
```
Hard refresh: Ctrl + Shift + R
Clear cache: F12 → Application → Clear Storage
```

**Check 2: Bundle Size**
```
v2.4.0: 37.75 kB (optimized)
v2.3.0: 82.32 kB (had duplicate Alpine)

If > 80 kB: Update to v2.4.0
```

**Check 3: Server Resources**
```
Check server CPU/RAM usage
Restart PHP-FPM if needed:
systemctl restart php8.2-fpm
```

---

#### 2. Deployed App Slow Response

**Symptoms:** App takes seconds to respond, slow database queries

**Solutions:**

**Check 1: Laravel Optimization**
```
Verify caches exist in container:
docker exec project-name ls -la bootstrap/cache/

Should see:
- config.php (config cache)
- routes-v7.php (route cache)
- events.php (event cache)

If missing: Re-deploy (v2.4.0+ creates automatically)
```

**Check 2: Database Connection**
```
Inside container:
docker exec project-name php artisan tinker
>>> DB::connection()->getPdo();

Should connect without errors
```

**Check 3: Environment**
```
Production apps should have:
- APP_ENV=production
- Caching enabled
- Debug disabled

Check via:
docker inspect project-name --format='{{range .Config.Env}}{{println .}}{{end}}'
```

---

### Server Management Issues

#### 1. Server Shows as "Offline" After Adding

**Symptoms:** Server added but status shows "offline", cannot create projects

**Causes:**
- SSH connectivity not tested automatically
- Incorrect SSH credentials
- Server firewall blocking SSH
- Wrong port number

**Solutions:**
```bash
# Option 1: Use the Ping/Refresh button
# Go to Servers → Select your server → Click "Ping Server"
# This will test connectivity and update status

# Option 2: Manual SSH test from command line
ssh -p 22 root@31.220.90.121 "echo 'test'"

# Option 3: For localhost/same VPS
# The system should auto-detect this, but you can manually update:
# Go to server details → Click "Ping Server"

# Option 4: Check if SSH service is running
systemctl status ssh

# Option 5: Verify firewall allows SSH
ufw status
ufw allow 22/tcp
```

**Note:** After the latest update, servers are automatically tested when created. Use the refresh button in project creation if status needs updating.

#### 2. Cannot Add Project to Server

**Symptoms:** Server not appearing in project creation dropdown

**Solutions:**
- The latest update shows ALL servers (not just online ones)
- Look for your server in the list with status badge
- Click the "🔄 Refresh" button next to the server
- This will test connectivity and update status
- If server is localhost (same VPS), it will be detected automatically

### Application Issues

#### 1. "File not found" or 403 Forbidden Error

**Symptoms:** Browser shows "File not found" or 403 error

**Causes:**
- Missing public/index.php file
- Incorrect permissions
- Wrong Nginx configuration

**Solutions:**
```bash
# Fix permissions
cd /var/www/devflow-pro
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# Verify index.php exists
ls -la public/index.php

# Restart Nginx
systemctl restart nginx
```

#### 2. Database Connection Failed

**Symptoms:** Error message about database connection

**Solutions:**
```bash
# Check MySQL is running
systemctl status mysql

# Test database connection
mysql -u devflow -pdevflow_secure_password_123 devflow_pro

# Verify .env credentials
cd /var/www/devflow-pro
grep DB_ .env

# Run migrations if needed
php artisan migrate --force
```

#### 3. Assets Not Loading (CSS/JS)

**Symptoms:** Page loads but no styling or broken layout

**Solutions:**
```bash
# Rebuild assets
cd /var/www/devflow-pro
npm install
npm run build

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check build directory
ls -la public/build/
```

#### 4. Queue Workers Not Running

**Symptoms:** Deployments stuck in "pending" status

**Solutions:**
```bash
# Check worker status
supervisorctl status

# View worker logs
tail -f /var/www/devflow-pro/storage/logs/worker.log

# Restart workers
supervisorctl restart all

# If workers fail to start, check configuration
cat /etc/supervisor/conf.d/devflow-pro.conf
```

#### 5. Session/Cache Issues

**Symptoms:** Can't login, session expired immediately

**Solutions:**
```bash
# Check Redis is running
systemctl status redis-server

# Clear all caches
cd /var/www/devflow-pro
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Fix storage permissions
chmod -R 775 storage
```

---

### Server Connection Issues

#### 1. Can't Connect to Server via SSH

**Symptoms:** SSH connection fails when adding server

**Solutions:**
```bash
# Test SSH manually
ssh -p 22 username@server-ip

# Check SSH key
cat ~/.ssh/id_rsa.pub

# Verify server allows SSH connections
# On target server:
systemctl status ssh

# Check firewall
ufw status
```

#### 2. Docker Not Found on Server

**Symptoms:** "Docker not installed" error

**Solutions:**
```bash
# Install Docker on target server
ssh root@server-ip
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
systemctl start docker
systemctl enable docker
```

#### 3. Server Metrics Not Updating

**Symptoms:** Server stats showing 0 or not updating

**Solutions:**
```bash
# Check cron is running scheduled tasks
crontab -l

# Manually run monitor command
php artisan devflow:monitor-servers

# Check server can be reached
ping server-ip
```

---

### Deployment Issues

#### 1. Deployment Fails Immediately

**Symptoms:** Deployment status changes to "failed" right away

**Solutions:**
```bash
# Check deployment logs
cd /var/www/devflow-pro
php artisan tinker
>>> $deployment = App\Models\Deployment::latest()->first();
>>> echo $deployment->error_log;

# Check queue worker logs
tail -f storage/logs/worker.log

# Verify server Docker installation
ssh server "docker --version"
```

#### 2. Build Process Fails

**Symptoms:** Deployment fails during build step

**Solutions:**
```bash
# Check Docker is installed
ssh server "docker info"

# Verify repository access
ssh server "git ls-remote repository-url"

# Check available disk space
ssh server "df -h"
```

#### 3. Container Won't Start

**Symptoms:** Container builds but fails to start

**Solutions:**
```bash
# Check container logs
ssh server "docker logs project-slug"

# Check port availability
ssh server "netstat -tlnp | grep :80"

# Verify environment variables
# Check project .env configuration
```

---

### SSL Certificate Issues

#### 1. SSL Certificate Fails to Install

**Symptoms:** Domain shows "SSL failed" status

**Solutions:**
```bash
# Check certbot is installed
ssh server "certbot --version"

# Verify DNS is pointing to server
nslookup your-domain.com

# Check port 80 is open
ssh server "ufw status"

# Try manual certificate
ssh server "certbot certonly --nginx -d your-domain.com"
```

#### 2. Certificate Renewal Fails

**Symptoms:** Certificate expired or renewal errors

**Solutions:**
```bash
# Test renewal
ssh server "certbot renew --dry-run"

# Check cron job exists
ssh server "crontab -l | grep certbot"

# Manual renewal
ssh server "certbot renew --force-renewal"
```

---

### Performance Issues

#### 1. Slow Application Response

**Solutions:**
```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check server resources
top
free -m
df -h

# Optimize database
mysql -u devflow -p
> USE devflow_pro;
> OPTIMIZE TABLE servers, projects, deployments;
```

#### 2. High Memory Usage

**Solutions:**
```bash
# Check queue workers
supervisorctl status

# Reduce worker count if needed
# Edit: /etc/supervisor/conf.d/devflow-pro.conf
# Change numprocs=2 to numprocs=1

# Restart workers
supervisorctl reread
supervisorctl update
```

#### 3. Database Slow Queries

**Solutions:**
```bash
# Check slow query log
tail -f /var/log/mysql/slow-query.log

# Add indexes if needed
php artisan migrate

# Clear old metrics data
php artisan devflow:cleanup-metrics --days=30
```

---

### Nginx Issues

#### 1. 502 Bad Gateway

**Symptoms:** Nginx shows 502 error

**Solutions:**
```bash
# Check PHP-FPM is running
systemctl status php8.2-fpm

# Check PHP-FPM logs
tail -f /var/log/php8.2-fpm.log

# Restart PHP-FPM
systemctl restart php8.2-fpm

# Check socket exists
ls -la /var/run/php/php8.2-fpm.sock
```

#### 2. 504 Gateway Timeout

**Symptoms:** Page loads slowly then times out

**Solutions:**
```bash
# Increase timeout in Nginx config
# Edit: /etc/nginx/sites-available/devflow-pro
# Add under server block:
fastcgi_read_timeout 300;

# Restart Nginx
nginx -t
systemctl restart nginx
```

---

### Permission Issues

#### 1. Can't Write to Storage

**Symptoms:** Errors about permissions when uploading or logging

**Solutions:**
```bash
cd /var/www/devflow-pro

# Fix ownership
chown -R www-data:www-data .

# Fix permissions
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# Verify
ls -la storage/
```

#### 2. Artisan Commands Fail

**Symptoms:** Permission denied when running artisan

**Solutions:**
```bash
# Don't run artisan as root
# Use www-data user instead
sudo -u www-data php artisan cache:clear

# Or fix permissions
chown -R www-data:www-data /var/www/devflow-pro
```

---

### Debug Mode

#### Enable Debug Mode (Development Only)

```bash
# Edit .env
nano /var/www/devflow-pro/.env

# Change
APP_DEBUG=true
APP_ENV=local

# Clear config cache
php artisan config:clear

# IMPORTANT: Disable in production!
```

#### View Detailed Errors

```bash
# Check Laravel logs
tail -f /var/www/devflow-pro/storage/logs/laravel.log

# Check Nginx error logs
tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

---

## Emergency Commands

### Restart Everything

```bash
# Full restart of all services
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart mysql
systemctl restart redis-server
supervisorctl restart all
```

### Clear All Caches

```bash
cd /var/www/devflow-pro
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL
```

### Reset Application

```bash
# Nuclear option - resets everything
cd /var/www/devflow-pro
php artisan migrate:fresh --force
php artisan config:cache
php artisan route:cache
supervisorctl restart all
```

---

## Getting Help

### Check Logs First

Always check logs before asking for help:

```bash
# Application
tail -100 /var/www/devflow-pro/storage/logs/laravel.log

# Nginx
tail -100 /var/log/nginx/error.log

# Queue workers
tail -100 /var/www/devflow-pro/storage/logs/worker.log
```

### Gather System Info

```bash
# System information
uname -a
php -v
mysql --version
docker --version
nginx -v
redis-cli --version

# Service status
systemctl status nginx php8.2-fpm mysql redis-server
supervisorctl status
```

### Report Issues

When reporting issues, include:
1. Error message (exact text)
2. Relevant log excerpts
3. Steps to reproduce
4. System information
5. What you've already tried

---

## Prevention Tips

### Regular Maintenance

```bash
# Weekly tasks
php artisan devflow:cleanup-metrics --days=90
php artisan cache:clear

# Monthly tasks
apt-get update && apt-get upgrade -y
composer update
npm update

# Check disk space
df -h

# Check logs size
du -sh /var/www/devflow-pro/storage/logs/
```

### Monitoring

- Set up server monitoring
- Configure alerts for disk space
- Monitor queue worker health
- Check SSL expiration dates
- Review error logs regularly

### Backups

```bash
# Backup database
mysqldump -u devflow -p devflow_pro > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf devflow_backup_$(date +%Y%m%d).tar.gz /var/www/devflow-pro

# Store backups off-server
```

---

## Livewire Issues (CRITICAL)

### 1. Livewire Actions Return 500 Error

**Symptoms:** Clicking buttons causes 500 errors, "method not found" in logs

**Root Causes:**
- Livewire JavaScript assets not published
- Component cache stale after updates
- PHP-FPM OPcache serving old code
- Eloquent models in component properties

**Solutions:**

```bash
# STEP 1: Publish Livewire Assets (CRITICAL!)
cd /var/www/devflow-pro
php artisan livewire:publish --assets

# Verify assets exist:
ls -la public/vendor/livewire/livewire.min.js
# Should show 144K file

# STEP 2: Clear All Caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# STEP 3: Regenerate Autoload
composer dump-autoload --optimize

# STEP 4: Restart PHP-FPM (CRITICAL!)
systemctl restart php8.2-fpm

# STEP 5: Use the fix script (if available)
./fix-livewire-cache.sh
```

**Prevention:**
- Always run `php artisan livewire:publish --assets` after deployment
- Include in deploy.sh script
- Restart PHP-FPM after component changes
- Never use Eloquent models as public properties in Livewire components

### 2. Component Methods Not Found

**Symptoms:** `Unable to call component method. Public method [methodName] not found`

**Root Cause:** Component cache or dependency injection issues

**Solutions:**

```bash
# Quick fix script:
cd /var/www/devflow-pro
rm -rf storage/framework/cache/livewire*
rm -rf bootstrap/cache/livewire*
composer dump-autoload --optimize
php artisan optimize:clear
systemctl restart php8.2-fpm
```

**Component Best Practices:**
```php
// ❌ DON'T: Use dependency injection in boot()
protected DockerService $dockerService;
public function boot(DockerService $dockerService) {
    $this->dockerService = $dockerService;
}

// ✅ DO: Resolve services on-demand
public function myMethod() {
    $dockerService = app(DockerService::class);
    // use service
}

// ❌ DON'T: Store Eloquent models as properties
public Project $project;

// ✅ DO: Store only IDs, fetch fresh
#[Locked]
public $projectId;

protected function getProject() {
    return Project::findOrFail($this->projectId);
}
```

### 3. Pusher Console Errors

**Symptoms:** "You must pass your app key when you instantiate Pusher"

**Root Cause:** Pusher initialized without valid credentials

**Solution Already Applied:**
Our `resources/js/bootstrap.js` now conditionally loads Pusher only if configured.

**To Configure Pusher (Optional):**
```bash
# Add to .env:
VITE_PUSHER_APP_KEY=your-pusher-key
VITE_PUSHER_APP_CLUSTER=mt1

# Rebuild assets:
npm run build
```

---

## Docker Container Issues

### 1. Docker Container Can't Connect to Host MySQL (Linux)

**Symptoms:** 
```
php_network_getaddresses: getaddrinfo for host.docker.internal failed: Name does not resolve
```

**Root Cause:** `host.docker.internal` only works on Docker Desktop (Mac/Windows), NOT on Linux!

**Solutions:**

```bash
# STEP 1: Find Docker bridge gateway IP
docker network inspect bridge | grep Gateway
# Result: 172.17.0.1 (standard)

# STEP 2: Configure MySQL to listen on all interfaces
sudo sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql

# STEP 3: Grant MySQL access from Docker network
mysql -e "CREATE USER IF NOT EXISTS 'devflow'@'172.17.%' IDENTIFIED BY 'your-password';"
mysql -e "GRANT ALL PRIVILEGES ON database_name.* TO 'devflow'@'172.17.%';"
mysql -e "FLUSH PRIVILEGES;"

# STEP 4: Update container .env
docker exec container-name sh -c 'sed -i "s/DB_HOST=.*/DB_HOST=172.17.0.1/" .env'
docker exec container-name php artisan config:clear
docker exec container-name php artisan optimize

# STEP 5: Test connection
docker exec container-name php artisan migrate:status
```

**Important Notes:**
- On Linux: Use `172.17.0.1` (Docker bridge gateway)
- On Mac/Windows Docker Desktop: Use `host.docker.internal`
- MySQL must listen on `0.0.0.0` not just `127.0.0.1`
- Grant from `172.17.%` to allow all Docker containers

### 2. Container Missing .env File

**Symptoms:** `file_get_contents(/var/www/html/.env): Failed to open stream`

**Solution:**
```bash
# Copy .env from example
docker exec container-name sh -c 'cp .env.example .env'

# Generate APP_KEY
docker exec container-name php artisan key:generate

# Configure database
docker exec container-name sh -c 'sed -i "s/DB_HOST=.*/DB_HOST=172.17.0.1/" .env'
docker exec container-name php artisan config:clear
```

### 3. Container Redis Connection Failed

**Symptoms:** `php_network_getaddresses: getaddrinfo for redis failed`

**Solution - Use File Cache Instead:**
```bash
docker exec container-name sh -c 'sed -i "s/CACHE_STORE=.*/CACHE_STORE=file/" .env'
docker exec container-name sh -c 'sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=file/" .env'
docker exec container-name sh -c 'sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/" .env'
docker exec container-name php artisan config:clear
docker exec container-name php artisan optimize
```

### 4. Docker Container Name Conflicts

**Symptoms:** `Conflict. The container name is already in use`

**Solution Already Implemented:**
Our DockerService automatically stops and removes existing containers before starting new ones.

**Manual Fix (if needed):**
```bash
# Stop and remove conflicting container
docker stop container-name
docker rm -f container-name

# Start new container
docker run...
```

---

## Browser Cache Issues

### Symptoms
- Changes deployed but not visible
- Old JavaScript running
- 404 errors for /livewire/livewire.js
- Buttons not clickable after update

### Solutions

**Hard Refresh:**
- Windows/Linux: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`

**Clear Browser Cache Completely:**
1. Press `Ctrl + Shift + Delete`
2. Select "All time"
3. Check "Cached images and files"
4. Click "Clear data"
5. Close and restart browser

**Test in Incognito:**
1. `Ctrl + Shift + N` (incognito window)
2. Login and test
3. If works in incognito → Browser cache issue

**DevTools Method:**
1. Press `F12`
2. Right-click refresh button
3. Select "Empty Cache and Hard Reload"

---

## Deployment Script Issues

### Deploy Script Must Include

**Critical Steps:**
```bash
# In deploy.sh, ensure these are included:

# 1. Publish Livewire assets
php artisan livewire:publish --assets

# 2. Build frontend assets
npm run build

# 3. Clear all caches
php artisan optimize:clear

# 4. Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Set permissions
chown -R www-data:www-data $PROJECT_PATH
chmod -R 755 $PROJECT_PATH
chmod -R 775 storage bootstrap/cache

# 6. Restart PHP-FPM (after deployment)
systemctl restart php8.2-fpm
```

### Post-Deployment Checklist

After deploying:
1. ✅ Livewire assets published
2. ✅ Frontend assets built
3. ✅ Caches cleared
4. ✅ PHP-FPM restarted
5. ✅ Permissions correct
6. ✅ Test in browser (hard refresh!)

---

## Dark Theme Issues

### Some Elements Not Dark Mode

**Symptoms:** White cards or elements in dark theme

**Solution:**
All our components now include dark mode. If you see white elements:

1. Hard refresh browser: `Ctrl + Shift + R`
2. Check browser console for CSS errors
3. Verify assets rebuilt: `npm run build`
4. Clear view cache: `php artisan view:clear`

**Dark Mode Classes:**
- Cards: `bg-white dark:bg-gray-800`
- Text: `text-gray-900 dark:text-white`
- Borders: `border-gray-200 dark:border-gray-700`
- Badges: Add `dark:bg-*-900/30 dark:text-*-400`

---

## Git Repository Issues

### Dubious Ownership Error

**Symptoms:** `fatal: detected dubious ownership in repository`

**Solution Already Implemented:**
Our GitService automatically configures safe directories.

**Manual Fix:**
```bash
git config --global --add safe.directory /var/www/project-slug
# Or for all:
git config --global --add safe.directory "/var/www/*"
```

---

## Quick Reference - Most Common Fixes

### "500 Error on Livewire Actions"
```bash
php artisan livewire:publish --assets
systemctl restart php8.2-fpm
```

### "Docker Container Can't Connect to MySQL"
```bash
# Use 172.17.0.1 not host.docker.internal
docker exec container sh -c 'sed -i "s/DB_HOST=.*/DB_HOST=172.17.0.1/" .env'
docker exec container php artisan config:clear
```

### "Changes Not Showing in Browser"
```
Hard refresh: Ctrl + Shift + R
Or try incognito window
```

### "Method Not Found on Component"
```bash
composer dump-autoload --optimize
php artisan optimize:clear
systemctl restart php8.2-fpm
```

---

**Still having issues? Check the logs and review the specific fix documentation files for more details.**

