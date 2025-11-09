# DevFlow Pro - Troubleshooting Guide

## Common Issues and Solutions

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

**Still having issues? Check the logs and review the DEPLOYMENT.md guide for more details.**

