# DevFlow Pro - Troubleshooting Guide

## Quick Troubleshooting Index

| Symptom | Possible Cause | Section |
|---------|---------------|---------|
| 500 Error | PHP/Laravel error | [Application Errors](#application-errors) |
| Deployment stuck | Docker/Git issues | [Deployment Issues](#deployment-issues) |
| Container not starting | Configuration error | [Docker Problems](#docker-problems) |
| SSL not working | Certificate issue | [SSL/Domain Issues](#ssldomain-issues) |
| High memory usage | Memory leak | [Performance Issues](#performance-issues) |
| Cannot access project | Network/DNS | [Network Problems](#network-problems) |
| Database connection error | MySQL issue | [Database Issues](#database-issues) |
| Cache not clearing | Redis/permissions | [Cache Problems](#cache-problems) |

---

## Application Errors

### Error: 500 Internal Server Error

#### Symptoms
- White screen of death
- Generic 500 error page
- No specific error message

#### Diagnosis
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check PHP error logs
tail -f /var/log/php8.4-fpm.log

# Check nginx error logs
tail -f /var/log/nginx/error.log

# Enable debug mode temporarily
php artisan config:cache --env=local
```

#### Common Causes & Solutions

**1. Permission Issues**
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Fix log permissions
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
chown www-data:www-data storage/logs/laravel.log
```

**2. Missing Dependencies**
```bash
# Install composer dependencies
composer install --no-dev --optimize-autoloader

# Install npm dependencies
npm install
npm run build
```

**3. Cache Issues**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**4. Database Connection**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Check .env file
cat .env | grep DB_

# Verify MySQL is running
systemctl status mysql
mysql -u root -p -e "SHOW DATABASES"
```

### Error: Class Not Found

#### Diagnosis
```bash
# Check autoload
composer dump-autoload

# Check if file exists
find . -name "ClassName.php"

# Check namespace
grep -r "namespace" app/
```

#### Solution
```bash
# Regenerate autoload files
composer dump-autoload -o

# Clear composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor
composer install
```

### Error: View Not Found

#### Symptoms
```
View [livewire.projects.show] not found
```

#### Solution
```bash
# Clear view cache
php artisan view:clear

# Check view file exists
ls resources/views/livewire/projects/

# Publish Livewire assets
php artisan livewire:publish --assets

# Clear and rebuild
php artisan optimize:clear
php artisan optimize
```

---

## Deployment Issues

### Deployment Stuck in "Running" State

#### Diagnosis
```bash
# Check deployment logs
tail -f storage/logs/deployment-*.log

# Check Docker processes
docker ps
docker-compose ps

# Check Git processes
ps aux | grep git

# Check disk space
df -h
```

#### Solutions

**1. Kill Stuck Processes**
```bash
# Find and kill stuck git process
ps aux | grep git
kill -9 [PID]

# Restart deployment service
supervisorctl restart devflow-deployer
```

**2. Clear Deployment Lock**
```sql
-- Clear deployment lock in database
UPDATE deployments
SET status = 'failed',
    completed_at = NOW()
WHERE status = 'running'
  AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

**3. Manual Deployment Recovery**
```bash
cd /opt/devflow/projects/your-project

# Reset Git state
git reset --hard HEAD
git clean -fd

# Restart Docker containers
docker-compose down
docker-compose up -d --build
```

### Deployment Fails with Git Error

#### Error: "Could not read from remote repository"

**Solution:**
```bash
# Check SSH key
ssh -T git@github.com

# Add SSH key to agent
eval $(ssh-agent -s)
ssh-add ~/.ssh/id_rsa

# Check repository access
git ls-remote https://github.com/user/repo.git

# Use HTTPS with token
git config --global url."https://${GITHUB_TOKEN}@github.com/".insteadOf "git@github.com:"
```

#### Error: "Merge conflict"

**Solution:**
```bash
# Force reset to remote branch
git fetch origin
git reset --hard origin/main

# Or abort merge
git merge --abort
git pull --rebase origin main
```

### Webhook Not Triggering Deployments

#### Diagnosis
```bash
# Check webhook logs
tail -f storage/logs/webhook.log

# Test webhook manually
curl -X POST https://devflow.yourdomain.com/webhook/github/project-slug \
  -H "Content-Type: application/json" \
  -d '{"ref": "refs/heads/main", "commits": [{"id": "test"}]}'
```

#### Solutions
```bash
# Verify webhook secret
php artisan tinker
>>> hash_equals(config('services.github.webhook_secret'), 'your-secret')

# Check webhook URL in GitHub
# Should be: https://devflow.yourdomain.com/webhook/github/{project-slug}

# Regenerate webhook token
php artisan project:webhook-regenerate {project-id}
```

---

## Docker Problems

### Container Fails to Start

#### Diagnosis
```bash
# Check container logs
docker-compose logs app
docker logs container_name --tail 50

# Check container status
docker ps -a
docker inspect container_name

# Check Docker events
docker events --since 1h
```

#### Common Issues

**1. Port Already in Use**
```bash
# Find process using port
lsof -i :80
netstat -tulpn | grep :80

# Kill process or change port
kill -9 [PID]
# Or modify docker-compose.yml to use different port
```

**2. Out of Memory**
```bash
# Check memory usage
docker stats

# Increase memory limit
docker-compose down
# Edit docker-compose.yml
# mem_limit: 1g
docker-compose up -d
```

**3. Image Build Failure**
```bash
# Clean build
docker-compose build --no-cache

# Remove all unused images
docker image prune -a

# Check disk space
df -h
docker system df
```

### Docker Compose Errors

#### Error: "Cannot create network"

**Solution:**
```bash
# Remove existing networks
docker network prune

# Remove specific network
docker network ls
docker network rm network_name

# Restart Docker
systemctl restart docker
```

#### Error: "Cannot start service: driver failed"

**Solution:**
```bash
# Clean up Docker system
docker system prune -a --volumes

# Reset Docker
systemctl stop docker
rm -rf /var/lib/docker
systemctl start docker
```

### Container Networking Issues

#### Containers Cannot Communicate

**Diagnosis:**
```bash
# Check network
docker network ls
docker network inspect bridge

# Test connectivity
docker exec container1 ping container2
```

**Solution:**
```bash
# Recreate network
docker-compose down
docker network create devflow_network
docker-compose up -d
```

---

## SSL/Domain Issues

### SSL Certificate Not Working

#### Diagnosis
```bash
# Check certificate
openssl s_client -connect yourdomain.com:443

# Check certificate expiry
echo | openssl s_client -servername yourdomain.com -connect yourdomain.com:443 2>/dev/null | openssl x509 -noout -dates

# Check nginx configuration
nginx -t
cat /etc/nginx/sites-enabled/your-site
```

#### Solutions

**1. Certificate Not Found**
```bash
# Regenerate certificate with Let's Encrypt
certbot certonly --nginx -d yourdomain.com -d www.yourdomain.com

# Manual certificate installation
cp /path/to/cert.crt /etc/ssl/certs/
cp /path/to/cert.key /etc/ssl/private/
chmod 600 /etc/ssl/private/cert.key
```

**2. Mixed Content Warning**
```bash
# Force HTTPS in Laravel
# Edit .env
APP_URL=https://yourdomain.com

# Edit app/Providers/AppServiceProvider.php
public function boot() {
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

**3. Certificate Renewal Failed**
```bash
# Manual renewal
certbot renew --force-renewal

# Check auto-renewal
systemctl status certbot.timer
cat /etc/cron.d/certbot
```

### Domain Not Resolving

#### Diagnosis
```bash
# Check DNS
nslookup yourdomain.com
dig yourdomain.com

# Check DNS propagation
curl https://dns.google/resolve?name=yourdomain.com

# Check local DNS
cat /etc/resolv.conf
```

#### Solutions
```bash
# Flush DNS cache
systemd-resolve --flush-caches

# Update hosts file (temporary)
echo "192.168.1.100 yourdomain.com" >> /etc/hosts

# Check nginx server blocks
nginx -t
ls -la /etc/nginx/sites-enabled/
```

---

## Performance Issues

### High Memory Usage

#### Diagnosis
```bash
# Check memory usage
free -h
top -o %MEM

# Check PHP memory usage
ps aux | grep php

# Check Docker memory
docker stats
```

#### Solutions

**1. PHP-FPM Optimization**
```ini
# Edit /etc/php/8.4/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Restart PHP-FPM
systemctl restart php8.4-fpm
```

**2. MySQL Memory Optimization**
```ini
# Edit /etc/mysql/my.cnf
[mysqld]
innodb_buffer_pool_size = 256M
key_buffer_size = 16M
max_connections = 50
thread_cache_size = 8

# Restart MySQL
systemctl restart mysql
```

**3. Redis Memory Limit**
```bash
# Edit /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
systemctl restart redis
```

### Slow Response Times

#### Diagnosis
```bash
# Check response time
curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com

# Check slow queries
mysql -u root -p -e "SHOW PROCESSLIST"
tail -f /var/log/mysql/slow-query.log

# Check PHP slow scripts
tail -f /var/log/php8.4-fpm-slow.log
```

#### Solutions

**1. Enable OPcache**
```ini
# Edit /etc/php/8.4/fpm/conf.d/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

# Restart PHP
systemctl restart php8.4-fpm
```

**2. Database Query Optimization**
```sql
-- Add indexes
ALTER TABLE deployments ADD INDEX idx_project_status (project_id, status);
ALTER TABLE projects ADD INDEX idx_server_status (server_id, status);

-- Analyze tables
ANALYZE TABLE projects;
OPTIMIZE TABLE deployments;
```

**3. Enable Laravel Caching**
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable query caching
php artisan cache:table
```

---

## Network Problems

### Cannot Access Application

#### Diagnosis
```bash
# Check if server is reachable
ping server-ip

# Check if port is open
telnet server-ip 80
nmap -p 80,443 server-ip

# Check firewall
ufw status
iptables -L
```

#### Solutions

**1. Firewall Configuration**
```bash
# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp
ufw reload

# Or with iptables
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables-save
```

**2. Nginx Not Running**
```bash
# Check nginx status
systemctl status nginx

# Start nginx
systemctl start nginx
systemctl enable nginx

# Check for configuration errors
nginx -t
journalctl -xeu nginx
```

### Connection Timeout

#### Diagnosis
```bash
# Check server load
uptime
top

# Check network connectivity
mtr yourdomain.com
traceroute yourdomain.com

# Check bandwidth usage
iftop
nethogs
```

#### Solutions
```bash
# Increase timeout values in nginx
# Edit /etc/nginx/nginx.conf
proxy_connect_timeout 600;
proxy_send_timeout 600;
proxy_read_timeout 600;
send_timeout 600;

# Increase PHP timeout
# Edit php.ini
max_execution_time = 300
max_input_time = 300

# Restart services
systemctl restart nginx
systemctl restart php8.4-fpm
```

---

## Database Issues

### Cannot Connect to Database

#### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Diagnosis:**
```bash
# Check MySQL status
systemctl status mysql
ps aux | grep mysql

# Check MySQL port
netstat -an | grep 3306

# Check MySQL error log
tail -f /var/log/mysql/error.log
```

**Solutions:**
```bash
# Start MySQL if stopped
systemctl start mysql

# Check socket file
ls -la /var/run/mysqld/mysqld.sock

# Fix socket issue
mkdir /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# Reset root password if needed
systemctl stop mysql
mysqld_safe --skip-grant-tables &
mysql -u root
mysql> UPDATE mysql.user SET authentication_string=PASSWORD('new_password') WHERE User='root';
mysql> FLUSH PRIVILEGES;
```

### Database Performance Issues

#### Slow Queries

**Diagnosis:**
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Check current queries
SHOW PROCESSLIST;
SHOW FULL PROCESSLIST;

-- Check table sizes
SELECT
    table_name AS "Table",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = 'your_database'
ORDER BY (data_length + index_length) DESC;
```

**Solutions:**
```sql
-- Add missing indexes
EXPLAIN SELECT * FROM deployments WHERE project_id = 1;
ALTER TABLE deployments ADD INDEX idx_project_id (project_id);

-- Optimize tables
OPTIMIZE TABLE projects;
OPTIMIZE TABLE deployments;

-- Update statistics
ANALYZE TABLE projects;
```

### Database Corruption

#### Diagnosis
```sql
-- Check table status
CHECK TABLE projects;
CHECK TABLE deployments EXTENDED;

-- Check for corruption
myisamchk /var/lib/mysql/database_name/*.MYI
```

#### Recovery
```bash
# Stop MySQL
systemctl stop mysql

# Repair tables
myisamchk -r /var/lib/mysql/database_name/*.MYI

# Or use mysqlcheck
mysqlcheck -u root -p --auto-repair --all-databases

# Start MySQL
systemctl start mysql
```

---

## Cache Problems

### Cache Not Clearing

#### Diagnosis
```bash
# Check Redis status
redis-cli ping
redis-cli INFO memory

# Check file permissions
ls -la storage/framework/cache
ls -la bootstrap/cache
```

#### Solutions

**1. Redis Cache Issues**
```bash
# Flush Redis cache
redis-cli FLUSHDB
redis-cli FLUSHALL

# Check Redis connection
php artisan tinker
>>> Cache::put('test', 'value', 60)
>>> Cache::get('test')
```

**2. File Cache Issues**
```bash
# Clear file cache manually
rm -rf storage/framework/cache/data/*
rm -rf bootstrap/cache/*

# Fix permissions
chown -R www-data:www-data storage/framework/cache
chmod -R 775 storage/framework/cache
```

**3. OPcache Issues**
```bash
# Reset OPcache
php -r "opcache_reset();"

# Or via web endpoint
curl https://yourdomain.com/opcache-reset.php
```

### Session Issues

#### Symptoms
- Users randomly logged out
- Session data not persisting
- CSRF token mismatch

#### Solutions
```bash
# Check session driver
grep SESSION_DRIVER .env

# Clear sessions
php artisan cache:clear
rm -rf storage/framework/sessions/*

# Fix session permissions
chown -R www-data:www-data storage/framework/sessions
chmod -R 775 storage/framework/sessions

# Switch to Redis sessions
# Edit .env
SESSION_DRIVER=redis
```

---

## Queue and Job Issues

### Jobs Not Processing

#### Diagnosis
```bash
# Check queue workers
ps aux | grep queue:work
supervisorctl status

# Check failed jobs
php artisan queue:failed
```

#### Solutions
```bash
# Restart queue workers
supervisorctl restart all

# Or manually
php artisan queue:restart
php artisan queue:work --timeout=60

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### Supervisor Not Starting Workers

#### Diagnosis
```bash
# Check supervisor logs
tail -f /var/log/supervisor/supervisord.log
tail -f /var/log/supervisor/devflow-worker-*.log
```

#### Solution
```bash
# Fix supervisor configuration
cat > /etc/supervisor/conf.d/devflow-worker.conf << EOF
[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/devflow/manager/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/opt/devflow/logs/worker.log
EOF

# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl start devflow-worker:*
```

---

## Debugging Tools and Commands

### Essential Debugging Commands

```bash
# Laravel debugging
php artisan tinker
php artisan telescope:install  # If using Telescope
php artisan route:list
php artisan config:show
php artisan env

# System monitoring
htop
iotop
iftop
netstat -tulpn
ss -tulpn

# Log monitoring
tail -f storage/logs/laravel.log
journalctl -f
dmesg -w

# Docker debugging
docker logs container_name --follow
docker exec -it container_name bash
docker inspect container_name
```

### Performance Testing

```bash
# Load testing
ab -n 1000 -c 10 https://yourdomain.com/

# Response time testing
curl -o /dev/null -s -w 'Total: %{time_total}s\n' https://yourdomain.com

# Database query analysis
mysqltuner.pl
pt-query-digest /var/log/mysql/slow-query.log
```

### Security Scanning

```bash
# Check for vulnerabilities
composer audit
npm audit

# Port scanning
nmap -sV yourdomain.com

# SSL testing
sslyze yourdomain.com
testssl.sh yourdomain.com
```

---

## Emergency Recovery Procedures

### System Won't Start

```bash
# Boot in safe mode
1. Access server via console/KVM
2. Boot into recovery mode
3. Mount filesystem
   mount /dev/sda1 /mnt
4. Check logs
   cat /mnt/var/log/syslog
5. Fix issues and reboot
```

### Complete Database Recovery

```bash
# From backup
1. Stop all services
   systemctl stop nginx php8.4-fpm mysql
2. Restore database
   mysql -u root -p < /backups/full-backup.sql
3. Verify data
   mysql -u root -p -e "SELECT COUNT(*) FROM projects"
4. Restart services
   systemctl start mysql php8.4-fpm nginx
```

### Rollback Everything

```bash
#!/bin/bash
# emergency-rollback.sh

# Stop everything
docker-compose down
systemctl stop nginx

# Restore from yesterday's backup
BACKUP_DATE=$(date -d "yesterday" +%Y%m%d)
tar -xzf /backups/full-backup-$BACKUP_DATE.tar.gz -C /

# Restore database
mysql -u root -p < /backups/db-backup-$BACKUP_DATE.sql

# Restart services
docker-compose up -d
systemctl start nginx

echo "Emergency rollback completed"
```

---

## Getting Help

### Support Channels

1. **Documentation**: https://docs.devflow.pro
2. **Community Forum**: https://community.devflow.pro
3. **Discord**: https://discord.gg/devflow
4. **GitHub Issues**: https://github.com/devflow-pro/issues
5. **Emergency Support**: support@devflow.pro

### Information to Provide

When requesting help, include:
```markdown
## Issue Report

**Environment:**
- DevFlow Version: X.X.X
- PHP Version: 8.4.X
- Laravel Version: 12.X
- Server OS: Ubuntu 22.04
- Docker Version: X.X

**Problem Description:**
[Clear description of the issue]

**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]

**Error Messages:**
```
[Include full error messages]
```

**Logs:**
- Laravel log: [Relevant portions]
- System log: [Relevant portions]

**What I've Tried:**
- [Solution 1 attempted]
- [Solution 2 attempted]
```

---

*Last Updated: November 2024*
*Version: 1.0.0*