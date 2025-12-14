# DevFlow Pro - Configuration Checklist

Use this checklist to ensure your DevFlow Pro installation is properly configured.

## Pre-Installation Requirements

### System Requirements

- [ ] **Operating System:** Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- [ ] **PHP Version:** 8.4 or higher
- [ ] **Database:** PostgreSQL 13+ or MySQL 8.0+
- [ ] **Redis:** 6.0 or higher
- [ ] **Docker:** 20.10 or higher
- [ ] **Docker Compose:** 2.0 or higher
- [ ] **Nginx/Apache:** Latest stable version
- [ ] **Supervisor:** For queue workers
- [ ] **Node.js:** 20.x or higher (for frontend assets)

### PHP Extensions Required

- [ ] PDO (for database)
- [ ] mbstring (for string handling)
- [ ] xml (for XML parsing)
- [ ] curl (for HTTP requests)
- [ ] zip (for archive handling)
- [ ] gd (for image processing)
- [ ] intl (for internationalization)
- [ ] bcmath (for precise calculations)
- [ ] redis (for Redis connections)
- [ ] openssl (for encryption)

Check with: `php -m`

## Configuration Files

### Environment File (.env)

- [ ] `.env` file exists (copied from `.env.example`)
- [ ] `APP_KEY` is generated (run `php artisan key:generate`)
- [ ] `APP_URL` is set to correct domain
- [ ] `APP_ENV` is set appropriately (local/staging/production)
- [ ] `APP_DEBUG` is `false` for production
- [ ] All required variables are set (no `null` where values needed)

### Database Configuration

- [ ] Database server is installed and running
- [ ] Database exists and is accessible
- [ ] Database credentials in `.env` are correct
- [ ] Database user has sufficient privileges
- [ ] Connection can be established (test with `php artisan tinker`)
- [ ] Migrations have been run (`php artisan migrate`)
- [ ] Database timezone is configured correctly

**Test Command:**
```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT 1');
```

### Redis Configuration

- [ ] Redis server is installed and running
- [ ] Redis is accessible on configured host/port
- [ ] Redis password is set (if required)
- [ ] Connection can be established
- [ ] Redis is set for cache, queue, and session

**Test Command:**
```bash
redis-cli ping
# Should return: PONG

php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

### Storage & Permissions

- [ ] All DevFlow directories exist:
  - [ ] `/opt/devflow/projects` (or configured path)
  - [ ] `/opt/devflow/backups`
  - [ ] `/opt/devflow/logs`
  - [ ] `/opt/devflow/ssl`
- [ ] Laravel storage directory is writable: `storage/`
- [ ] Bootstrap cache is writable: `bootstrap/cache/`
- [ ] Correct ownership: `www-data:www-data` (or web server user)
- [ ] Correct permissions:
  - [ ] Directories: 755
  - [ ] storage/: 777
  - [ ] bootstrap/cache/: 777

**Fix Command:**
```bash
sudo chown -R www-data:www-data /var/www/devflow
sudo chmod -R 755 /var/www/devflow
sudo chmod -R 777 storage bootstrap/cache
```

## DevFlow Pro Specific Configuration

### Core Settings

- [ ] `DEVFLOW_PROJECTS_PATH` is set and directory exists
- [ ] `DEVFLOW_BACKUP_PATH` is set and directory exists
- [ ] `DEVFLOW_LOGS_PATH` is set and directory exists
- [ ] `DEVFLOW_SSL_PATH` is set and directory exists
- [ ] Default PHP version is configured
- [ ] Default Node.js version is configured

### Docker Configuration

- [ ] Docker is installed and running
- [ ] Docker Compose is installed
- [ ] Docker socket is accessible (`/var/run/docker.sock`)
- [ ] Docker network exists (or will be created)
- [ ] BuildKit is enabled (recommended)
- [ ] Current user can access Docker (or using correct permissions)

**Test Command:**
```bash
docker --version
docker-compose --version
docker ps
docker network ls
```

### Deployment Settings

- [ ] Maximum concurrent deployments is set
- [ ] Deployment retention period is configured
- [ ] Auto-backup before deploy is configured
- [ ] Backup retention period is set
- [ ] Deployment approval requirement is set

### Health Monitoring

- [ ] Health check interval is configured
- [ ] SSL expiry warning threshold is set
- [ ] Resource usage thresholds are configured:
  - [ ] Disk warning/critical thresholds
  - [ ] CPU warning/critical thresholds
  - [ ] Memory warning/critical thresholds
- [ ] Cache durations are configured

### Timeout Settings

All timeout settings should be appropriate for your infrastructure:

- [ ] Docker install timeout
- [ ] Docker compose build timeout
- [ ] Docker pull timeout
- [ ] Backup timeout
- [ ] Health check timeout
- [ ] SSL check timeout
- [ ] Command execution timeout

## Optional Integrations

### Git Provider Integration

**GitHub:**
- [ ] Client ID configured
- [ ] Client secret configured
- [ ] Webhook secret configured
- [ ] Personal access token configured
- [ ] Webhook URL configured in GitHub

**GitLab:**
- [ ] GitLab URL configured (if self-hosted)
- [ ] Client ID configured
- [ ] Client secret configured
- [ ] Personal access token configured
- [ ] Webhook secret configured

**Bitbucket:**
- [ ] Bitbucket URL configured
- [ ] Username configured
- [ ] App password configured
- [ ] Client credentials configured

### Notification Channels

**Email:**
- [ ] Mail driver configured (SMTP/Mailgun/SES)
- [ ] Mail credentials configured
- [ ] Mail from address set
- [ ] Test email sent successfully
- [ ] Email notifications enabled

**Slack:**
- [ ] Webhook URL configured
- [ ] Default channel set
- [ ] Slack notifications enabled
- [ ] Test message sent successfully

**Discord:**
- [ ] Webhook URL configured
- [ ] Discord notifications enabled
- [ ] Test message sent successfully

### Infrastructure Management

**Nginx Proxy Manager:**
- [ ] NPM is installed and accessible
- [ ] NPM URL configured
- [ ] NPM credentials configured
- [ ] API access verified

**Docker Registry:**
- [ ] Registry URL configured
- [ ] Registry credentials configured
- [ ] Connection tested

### Monitoring & Error Tracking

**Sentry (Optional):**
- [ ] Sentry DSN configured
- [ ] Sample rate configured
- [ ] Test error sent

**Telescope (Development):**
- [ ] Enabled for dev/staging only
- [ ] Disabled for production
- [ ] Access URL configured

## Queue Workers & Scheduling

### Supervisor Configuration

- [ ] Supervisor is installed
- [ ] Worker configuration file created
- [ ] Worker processes are running
- [ ] Worker logs are being written
- [ ] Workers restart on failure

**Check Command:**
```bash
sudo supervisorctl status devflow-worker:*
```

### Cron Jobs

- [ ] Laravel scheduler cron job added
- [ ] Cron job runs every minute
- [ ] Scheduled tasks are executing

**Check Command:**
```bash
sudo crontab -l | grep devflow
```

## Web Server Configuration

### Nginx/Apache

- [ ] Web server is installed and running
- [ ] Virtual host/server block configured
- [ ] Document root points to `public/` directory
- [ ] PHP-FPM is configured and running
- [ ] Rewrite rules are working
- [ ] HTTPS is configured (production)
- [ ] SSL certificates are valid

### SSL/TLS (Production)

- [ ] SSL certificates installed
- [ ] Auto-renewal configured (Let's Encrypt)
- [ ] HTTPS redirect enabled
- [ ] Security headers configured
- [ ] Certificate expiry monitoring enabled

## Security Configuration

### Application Security

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] `.env` file not in version control
- [ ] `.env` file permissions: 640 or stricter
- [ ] All passwords are strong and unique
- [ ] Webhook signatures verification enabled
- [ ] Audit logging enabled

### Database Security

- [ ] Database user has minimal required privileges
- [ ] Database password is strong
- [ ] Database is not exposed to public internet
- [ ] Regular backups configured
- [ ] Backup encryption enabled (if sensitive data)

### Server Security

- [ ] Firewall configured (UFW/iptables)
- [ ] Only required ports open (80, 443, 22)
- [ ] SSH key authentication enabled
- [ ] Root login disabled
- [ ] Fail2ban installed and configured
- [ ] Regular security updates enabled

### DevFlow Security

- [ ] IP whitelisting configured (if needed)
- [ ] 2FA enabled (if required)
- [ ] Session timeout configured
- [ ] CORS configured properly
- [ ] Rate limiting enabled

## Performance Optimization

### Application Performance

- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] OPcache enabled
- [ ] Redis used for cache
- [ ] Redis used for sessions
- [ ] Queue workers running

### Database Performance

- [ ] Database indexes created (migrations)
- [ ] Query caching enabled
- [ ] Connection pooling configured
- [ ] Slow query log enabled
- [ ] Regular optimization scheduled

### Server Performance

- [ ] Adequate RAM allocated
- [ ] Swap space configured
- [ ] PHP-FPM pool optimized
- [ ] Nginx/Apache tuned for load
- [ ] Docker resources allocated properly

## Monitoring & Logging

### Application Logs

- [ ] Log level configured appropriately
- [ ] Log rotation configured
- [ ] Log retention period set
- [ ] Logs are accessible and readable
- [ ] Error logs monitored

### System Logs

- [ ] System logs accessible
- [ ] DevFlow-specific logs configured
- [ ] Docker logs accessible
- [ ] Worker logs monitored

### Metrics

- [ ] Performance metrics enabled
- [ ] Resource metrics enabled
- [ ] Metrics retention configured
- [ ] Dashboard accessible

## Backup & Disaster Recovery

### Backup Configuration

- [ ] Auto-backup before deployment enabled
- [ ] Regular scheduled backups configured
- [ ] Backup retention policy set
- [ ] Backup storage location configured
- [ ] Off-site backups configured (recommended)

### Disaster Recovery

- [ ] Backup restoration tested
- [ ] Recovery procedures documented
- [ ] Rollback mechanism tested
- [ ] Failover plan exists (if applicable)

## Testing & Validation

### Functional Tests

- [ ] Admin panel accessible
- [ ] User can login
- [ ] Projects can be created
- [ ] Servers can be added
- [ ] Deployments can be triggered
- [ ] Health checks working
- [ ] Notifications sent correctly

### Integration Tests

- [ ] Git provider webhooks working
- [ ] Docker operations successful
- [ ] SSL certificate creation working
- [ ] Backup creation successful
- [ ] Queue jobs processing

### Performance Tests

- [ ] Application response time acceptable
- [ ] Database queries optimized
- [ ] Docker operations not timing out
- [ ] Concurrent deployments working
- [ ] Resource usage within limits

## Post-Installation Tasks

### Initial Setup

- [ ] Admin user created
- [ ] First server configured
- [ ] Test project deployed
- [ ] Monitoring verified
- [ ] Notifications tested

### Documentation

- [ ] Configuration documented
- [ ] Team members trained
- [ ] Emergency procedures documented
- [ ] Maintenance schedule created

### Ongoing Maintenance

- [ ] Update schedule planned
- [ ] Backup verification scheduled
- [ ] Security audit scheduled
- [ ] Performance review scheduled
- [ ] Log review scheduled

## Troubleshooting Commands

```bash
# Check application status
php artisan about

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test Redis connection
php artisan tinker
>>> Cache::put('test', 'value');

# Check queue workers
sudo supervisorctl status devflow-worker:*

# Check logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Check Docker
docker ps
docker-compose ps

# Check disk space
df -h

# Check memory
free -h

# Check processes
ps aux | grep php
```

## Validation Script

Run this script to validate your configuration:

```bash
#!/bin/bash
echo "DevFlow Pro Configuration Validator"
echo "===================================="

# Check PHP version
echo -n "PHP Version: "
php -v | head -n 1

# Check required extensions
echo -n "Checking PHP extensions... "
php -m | grep -E "pdo|mbstring|xml|curl|zip|gd|redis|openssl" | wc -l
echo "found"

# Check database connection
echo -n "Database connection: "
php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"

# Check Redis connection
echo -n "Redis connection: "
redis-cli ping

# Check Docker
echo -n "Docker version: "
docker --version

# Check directories
echo -n "Checking directories... "
ls -ld /opt/devflow/{projects,backups,logs,ssl} 2>/dev/null | wc -l
echo "found"

# Check permissions
echo -n "Storage permissions: "
ls -ld storage | awk '{print $1}'

# Check queue workers
echo "Queue workers:"
sudo supervisorctl status devflow-worker:*

echo ""
echo "Validation complete!"
```

---

**Recommended Review Frequency:**
- Pre-deployment: Complete checklist
- Post-deployment: Test section only
- Monthly: Security and performance sections
- Quarterly: Full checklist review

For detailed configuration guide, see `/docs/CONFIGURATION.md`
