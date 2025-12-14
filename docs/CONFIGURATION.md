# DevFlow Pro - Configuration Guide

This comprehensive guide explains all configuration options available in DevFlow Pro and how to customize them for your specific deployment needs.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Application Settings](#application-settings)
3. [Database Configuration](#database-configuration)
4. [Cache & Session Configuration](#cache--session-configuration)
5. [DevFlow Pro Core Settings](#devflow-pro-core-settings)
6. [Docker Configuration](#docker-configuration)
7. [Deployment Settings](#deployment-settings)
8. [Health Monitoring](#health-monitoring)
9. [Notification Configuration](#notification-configuration)
10. [Git Provider Integration](#git-provider-integration)
11. [Advanced Settings](#advanced-settings)
12. [Environment-Specific Examples](#environment-specific-examples)
13. [Troubleshooting](#troubleshooting)

---

## Quick Start

1. **Copy the example environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

3. **Configure essential settings:**
   - Set `APP_URL` to your domain
   - Configure database credentials
   - Set up Redis connection
   - Configure DevFlow paths

4. **Test configuration:**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

---

## Application Settings

### APP_NAME
**Description:** The name of your application, displayed in the UI, emails, and notifications.

**Default:** `"DevFlow Pro"`

**Example:**
```env
APP_NAME="DevFlow Pro - Production"
```

---

### APP_ENV
**Description:** Current application environment. Affects error reporting, caching, and debugging behavior.

**Options:** `local`, `staging`, `production`

**Default:** `production`

**Usage:**
- `local` - Development with debug enabled, detailed error pages
- `staging` - Pre-production testing with minimal debugging
- `production` - Live environment with all optimizations enabled

**Example:**
```env
APP_ENV=production
```

---

### APP_DEBUG
**Description:** Enable/disable debug mode. When enabled, detailed error messages are displayed.

**Options:** `true`, `false`

**Default:** `false`

**Warning:** NEVER enable debug mode in production as it exposes sensitive information.

**Example:**
```env
APP_DEBUG=false
```

---

### APP_URL
**Description:** The base URL of your application. Used for generating links, webhooks, and asset URLs.

**Format:** Full URL including protocol

**Examples:**
```env
# Production
APP_URL=https://devflow.example.com

# Local development
APP_URL=http://localhost:8000

# Staging
APP_URL=https://staging.devflow.example.com
```

---

### APP_TIMEZONE
**Description:** Default timezone for the application. Affects timestamps, scheduling, and date displays.

**Default:** `UTC`

**Example:**
```env
APP_TIMEZONE=America/New_York
```

**Common Timezones:**
- `UTC` - Coordinated Universal Time
- `America/New_York` - Eastern Time
- `America/Los_Angeles` - Pacific Time
- `Europe/London` - GMT/BST
- `Asia/Tokyo` - Japan Standard Time

---

## Database Configuration

### Database Connection Type

**DB_CONNECTION:** Primary database driver

**Options:**
- `pgsql` - PostgreSQL (Recommended for production)
- `mysql` - MySQL/MariaDB
- `sqlite` - SQLite (Development only)

**Example:**
```env
DB_CONNECTION=pgsql
```

---

### PostgreSQL Configuration (Recommended)

PostgreSQL offers better performance, reliability, and advanced features for DevFlow Pro.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password_here
```

**Setup PostgreSQL:**
```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql
CREATE DATABASE devflow_pro;
CREATE USER devflow WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE devflow_pro TO devflow;
\q
```

---

### MySQL Configuration (Alternative)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=your_secure_password_here
```

**Setup MySQL:**
```bash
# Install MySQL
sudo apt install mysql-server

# Create database and user
sudo mysql
CREATE DATABASE devflow_pro;
CREATE USER 'devflow'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### Database Options

```env
# Character set and collation (MySQL)
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Enable strict mode for data integrity
DB_STRICT_MODE=true
```

---

## Cache & Session Configuration

### Cache Configuration

**CACHE_STORE:** Where to store cached data

**Options:**
- `redis` - Redis server (Recommended for production)
- `file` - Local filesystem
- `memcached` - Memcached server
- `database` - Database table
- `array` - In-memory (Testing only)

**Recommended Setup:**
```env
CACHE_STORE=redis
CACHE_PREFIX=devflow_cache
CACHE_TTL=3600
```

**Redis Configuration:**
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_PREFIX=devflow_
```

**Install Redis:**
```bash
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

### Session Configuration

**SESSION_DRIVER:** Where to store user sessions

**Options:**
- `redis` - Redis server (Recommended)
- `file` - Local filesystem
- `database` - Database table
- `cookie` - Browser cookies

**Recommended Setup:**
```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

**Security Options:**
```env
# For HTTPS deployments
SESSION_SECURE_COOKIE=true

# For cross-subdomain sessions
SESSION_DOMAIN=.example.com
```

---

### Queue Configuration

**QUEUE_CONNECTION:** Background job processor

**Options:**
- `redis` - Redis queue (Recommended)
- `database` - Database queue
- `sync` - Synchronous (Development only)
- `sqs` - Amazon SQS

**Recommended Setup:**
```env
QUEUE_CONNECTION=redis
```

**Start Queue Workers:**
```bash
# Using Supervisor (recommended)
sudo supervisorctl start devflow-worker:*

# Manual (development)
php artisan queue:work --tries=3
```

---

## DevFlow Pro Core Settings

### Storage Paths

Define where DevFlow Pro stores managed projects, backups, logs, and SSL certificates.

```env
# Projects Storage Path
# Where all managed project files are stored
DEVFLOW_PROJECTS_PATH=/opt/devflow/projects

# Backups Storage Path
# Where project backups are stored
DEVFLOW_BACKUP_PATH=/opt/devflow/backups

# Logs Storage Path
# Where DevFlow system logs are stored
DEVFLOW_LOGS_PATH=/opt/devflow/logs

# SSL Certificates Path
# Where SSL certificates are stored
DEVFLOW_SSL_PATH=/opt/devflow/ssl
```

**Create Directories:**
```bash
sudo mkdir -p /opt/devflow/{projects,backups,logs,ssl}
sudo chown -R www-data:www-data /opt/devflow
sudo chmod -R 755 /opt/devflow
```

---

## Docker Configuration

### Basic Docker Settings

```env
# Docker socket path
DOCKER_SOCKET=/var/run/docker.sock

# Docker network for managed containers
DOCKER_NETWORK=devflow_network

# Default versions
DEVFLOW_DEFAULT_PHP_VERSION=8.4
DEVFLOW_DEFAULT_NODE_VERSION=20

# Performance optimizations
DEVFLOW_DOCKER_BUILDKIT=true
DEVFLOW_DOCKER_RESTART_POLICY=unless-stopped
```

**Create Docker Network:**
```bash
docker network create devflow_network
```

---

### Docker Timeout Settings

Configure timeout values for Docker operations (in seconds):

```env
# Installation and setup
DEVFLOW_TIMEOUT_DOCKER_INSTALL=300      # 5 minutes

# Build operations
DEVFLOW_TIMEOUT_DOCKER_COMPOSE_BUILD=1200  # 20 minutes
DEVFLOW_TIMEOUT_DOCKER_BUILD=600           # 10 minutes
DEVFLOW_TIMEOUT_DOCKER_PULL=600            # 10 minutes

# Start/stop operations
DEVFLOW_TIMEOUT_DOCKER_COMPOSE_START=300   # 5 minutes
DEVFLOW_TIMEOUT_DOCKER_COMPOSE_CLEANUP=180 # 3 minutes

# Maintenance
DEVFLOW_TIMEOUT_SYSTEM_PRUNE=300           # 5 minutes
```

**Adjust for your infrastructure:**
- Increase for slower servers or large images
- Decrease for powerful servers with fast connections

---

## Deployment Settings

### Basic Deployment Configuration

```env
# Maximum concurrent deployments
# How many projects can be deployed simultaneously
DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS=3

# Deployment log retention (in days)
DEVFLOW_DEPLOYMENT_RETENTION_DAYS=30

# Require approval for deployments
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=false

# Auto backup before deployment
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true

# Backup retention (in days)
DEVFLOW_BACKUP_RETENTION_DAYS=7
```

---

### Deployment Best Practices

**Production Environment:**
```env
DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS=2
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
DEVFLOW_BACKUP_RETENTION_DAYS=30
```

**Development Environment:**
```env
DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS=5
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=false
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=false
DEVFLOW_BACKUP_RETENTION_DAYS=3
```

---

## Health Monitoring

### Health Check Configuration

```env
# Health check interval (in seconds)
# How often to check project health
DEVFLOW_HEALTH_CHECK_INTERVAL=300

# SSL certificate expiry warning (in days)
DEVFLOW_SSL_EXPIRY_WARNING_DAYS=7

# Timeout for health checks (in seconds)
DEVFLOW_TIMEOUT_HEALTH_CHECK=10
DEVFLOW_TIMEOUT_SSL_CHECK=30
```

---

### Resource Monitoring Thresholds

Configure warning and critical thresholds for system resources (percentage):

```env
# Disk Usage
DEVFLOW_DISK_WARNING_THRESHOLD=75
DEVFLOW_DISK_CRITICAL_THRESHOLD=90

# CPU Usage
DEVFLOW_CPU_WARNING_THRESHOLD=75
DEVFLOW_CPU_CRITICAL_THRESHOLD=90

# Memory Usage
DEVFLOW_MEMORY_WARNING_THRESHOLD=75
DEVFLOW_MEMORY_CRITICAL_THRESHOLD=90
```

**Threshold Recommendations:**
- **High-traffic servers:** Lower thresholds (60/80)
- **Development servers:** Higher thresholds (85/95)
- **Production servers:** Conservative thresholds (75/90)

---

### Cache Durations

Configure how long health check results are cached (in seconds):

```env
# Project health check cache (1 minute)
DEVFLOW_CACHE_PROJECT_HEALTH=60

# SSL certificate check cache (1 hour)
DEVFLOW_CACHE_SSL_CERTIFICATE=3600

# Server metrics cache (5 minutes)
DEVFLOW_CACHE_SERVER_METRICS=300

# Container status cache (30 seconds)
DEVFLOW_CACHE_CONTAINER_STATUS=30
```

---

## Notification Configuration

### Enable Notification Channels

```env
# Enable/disable notification channels
DEVFLOW_NOTIFICATIONS_EMAIL=true
DEVFLOW_NOTIFICATIONS_SLACK=false
DEVFLOW_NOTIFICATIONS_DISCORD=false
```

---

### Email Notifications

**SMTP Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@devflow.pro"
MAIL_FROM_NAME="${APP_NAME}"
```

**Gmail Setup:**
1. Enable 2-factor authentication
2. Generate app-specific password
3. Use app password in `MAIL_PASSWORD`

---

### Slack Notifications

```env
DEVFLOW_NOTIFICATIONS_SLACK=true
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
SLACK_CHANNEL=#deployments
```

**Setup Slack Webhook:**
1. Go to https://api.slack.com/messaging/webhooks
2. Create a new webhook for your workspace
3. Copy the webhook URL
4. Set the default channel

---

### Discord Notifications

```env
DEVFLOW_NOTIFICATIONS_DISCORD=true
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR/WEBHOOK/URL
```

**Setup Discord Webhook:**
1. Open Server Settings
2. Go to Integrations
3. Create Webhook
4. Copy webhook URL

---

### Event-Specific Notifications

Configure which channels receive specific events:

```env
# Deployment failures (comma-separated)
DEVFLOW_DEPLOYMENT_FAILURE_CHANNELS=email,slack

# Health check failures
DEVFLOW_HEALTH_CHECK_FAILURE_CHANNELS=email,slack,discord
```

---

## Git Provider Integration

### GitHub Integration

**Setup GitHub Webhook:**
```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_WEBHOOK_SECRET=your_webhook_secret
GITHUB_TOKEN=ghp_your_personal_access_token
```

**Create GitHub App:**
1. Go to Settings > Developer settings > GitHub Apps
2. Create new GitHub App
3. Set webhook URL: `https://devflow.example.com/webhooks/github`
4. Enable repository permissions
5. Copy Client ID, Client Secret, and generate token

---

### GitLab Integration

```env
GITLAB_URL=https://gitlab.com
GITLAB_CLIENT_ID=your_gitlab_client_id
GITLAB_CLIENT_SECRET=your_gitlab_client_secret
GITLAB_WEBHOOK_SECRET=your_webhook_secret
GITLAB_TOKEN=your_gitlab_token
```

**For self-hosted GitLab:**
```env
GITLAB_URL=https://gitlab.yourcompany.com
```

---

### Bitbucket Integration

```env
BITBUCKET_URL=https://api.bitbucket.org/2.0
BITBUCKET_USERNAME=your_username
BITBUCKET_APP_PASSWORD=your_app_password
BITBUCKET_CLIENT_ID=your_client_id
BITBUCKET_CLIENT_SECRET=your_client_secret
BITBUCKET_WEBHOOK_SECRET=your_webhook_secret
```

---

## Advanced Settings

### Nginx Proxy Manager Integration

For automated SSL and domain management:

```env
NGINX_PROXY_MANAGER_URL=http://localhost:81
NGINX_PROXY_MANAGER_EMAIL=admin@example.com
NGINX_PROXY_MANAGER_PASSWORD=your_secure_password
```

---

### Docker Registry Integration

For private Docker registry support:

```env
DOCKER_REGISTRY_URL=registry.example.com
DOCKER_REGISTRY_USERNAME=your_username
DOCKER_REGISTRY_PASSWORD=your_password
```

---

### Error Tracking with Sentry

```env
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=1.0
```

**Setup Sentry:**
1. Create account at https://sentry.io
2. Create new Laravel project
3. Copy DSN from project settings

---

### Laravel Telescope (Development)

```env
# Enable for development/staging only
TELESCOPE_ENABLED=true
```

**Access Telescope:**
```
https://devflow.example.com/telescope
```

---

## Environment-Specific Examples

### Local Development Environment

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=devflow_dev

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

DEVFLOW_PROJECTS_PATH=/home/developer/devflow/projects
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=false
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=false

TELESCOPE_ENABLED=true
```

---

### Staging Environment

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.devflow.example.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=devflow_staging

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

DEVFLOW_PROJECTS_PATH=/opt/devflow-staging/projects
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
DEVFLOW_BACKUP_RETENTION_DAYS=7

DEVFLOW_NOTIFICATIONS_EMAIL=true
DEVFLOW_NOTIFICATIONS_SLACK=true

TELESCOPE_ENABLED=true
```

---

### Production Environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://devflow.example.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=devflow_pro

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_SECURE_COOKIE=true

DEVFLOW_PROJECTS_PATH=/opt/devflow/projects
DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS=2
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
DEVFLOW_BACKUP_RETENTION_DAYS=30

# All notifications enabled
DEVFLOW_NOTIFICATIONS_EMAIL=true
DEVFLOW_NOTIFICATIONS_SLACK=true
DEVFLOW_NOTIFICATIONS_DISCORD=true

# Error tracking
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id

# Telescope disabled
TELESCOPE_ENABLED=false
```

---

### Docker Deployment Environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://devflow.example.com

# Use service names from docker-compose
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Docker-specific paths
DEVFLOW_PROJECTS_PATH=/var/www/projects
DEVFLOW_BACKUP_PATH=/var/www/backups
DEVFLOW_LOGS_PATH=/var/www/logs
DEVFLOW_SSL_PATH=/var/www/ssl
```

---

## Troubleshooting

### Common Issues

**Issue: Database Connection Failed**
```bash
# Check database service
sudo systemctl status postgresql

# Test connection
psql -h 127.0.0.1 -U devflow -d devflow_pro

# Check credentials in .env
php artisan tinker
> DB::connection()->getPdo();
```

---

**Issue: Redis Connection Failed**
```bash
# Check Redis service
sudo systemctl status redis-server

# Test connection
redis-cli ping

# Check Redis configuration
redis-cli INFO
```

---

**Issue: Permission Denied on Storage Paths**
```bash
# Fix permissions
sudo chown -R www-data:www-data /opt/devflow
sudo chmod -R 755 /opt/devflow
sudo chmod -R 777 storage bootstrap/cache
```

---

**Issue: Queue Jobs Not Processing**
```bash
# Check queue workers
sudo supervisorctl status devflow-worker:*

# Restart workers
sudo supervisorctl restart devflow-worker:*

# Monitor queue
php artisan queue:monitor
```

---

**Issue: Docker Timeout Errors**
```env
# Increase timeout values
DEVFLOW_TIMEOUT_DOCKER_COMPOSE_BUILD=2400
DEVFLOW_TIMEOUT_DOCKER_PULL=1200
```

---

### Configuration Validation

**Test configuration:**
```bash
# Clear configuration cache
php artisan config:clear

# Cache configuration
php artisan config:cache

# Validate environment
php artisan about
```

---

**Check specific settings:**
```bash
php artisan tinker
> config('devflow.projects_path')
> config('devflow.deployment.max_concurrent')
> config('services.github.client_id')
```

---

### Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong, unique `APP_KEY` generated
- [ ] Secure database passwords
- [ ] SSL/TLS enabled for all services
- [ ] `SESSION_SECURE_COOKIE=true` for HTTPS
- [ ] Redis password set for remote access
- [ ] Webhook secrets configured
- [ ] `.env` file not in version control
- [ ] File permissions properly set
- [ ] Regular security updates applied

---

## Additional Resources

- **Main Documentation:** See `/docs/README.md`
- **Deployment Guide:** See `/docs/VPS_DEPLOYMENT_GUIDE.md`
- **Troubleshooting:** See `/docs/TROUBLESHOOTING_GUIDE.md`
- **API Documentation:** See `/docs/API_DOCUMENTATION.md`

---

## Getting Help

If you need assistance with configuration:

1. Check the troubleshooting section above
2. Review logs: `tail -f storage/logs/laravel.log`
3. Check DevFlow logs: `tail -f /opt/devflow/logs/devflow.log`
4. Run diagnostics: `php artisan about`
5. Create an issue on GitHub with configuration details (remove sensitive data)

---

**Last Updated:** December 2024
**Version:** 5.x
**Maintained by:** DevFlow Pro Team
