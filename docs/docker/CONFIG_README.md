# DevFlow Pro - Configuration Overview

This document provides an overview of all configuration resources available for DevFlow Pro.

## Configuration Files

### Environment Configuration Files

1. **`.env.example`** - Comprehensive environment configuration template
   - Fully documented with inline comments
   - Organized into logical sections
   - Includes examples for all settings
   - Use as reference when creating your `.env` file

2. **`.env.docker`** - Docker-optimized environment configuration
   - Pre-configured for Docker Compose deployments
   - Uses Docker service names for connections
   - Container-specific path configurations
   - Includes Docker deployment notes

3. **`.env`** - Your actual configuration (create from `.env.example`)
   - Never commit to version control
   - Contains your actual credentials and settings
   - Generated during installation

### Application Configuration Files

Located in `/config` directory:

1. **`config/devflow.php`** - DevFlow Pro core configuration
   - Projects and storage paths
   - Deployment settings
   - Health monitoring configuration
   - Docker settings
   - Notification preferences
   - Security settings
   - Framework-specific configurations
   - Maintenance and rollback settings
   - Timeout configurations

2. **`config/services.php`** - Third-party service integrations
   - Git providers (GitHub, GitLab, Bitbucket)
   - CI/CD platforms (Jenkins)
   - Container registries
   - Notification services (Slack, Discord)
   - Email providers (Mailgun, Postmark, SES)
   - Monitoring services (Sentry)
   - Infrastructure tools (Nginx Proxy Manager)

3. **`config/database.php`** - Database connections
   - PostgreSQL configuration (recommended)
   - MySQL configuration (alternative)
   - Redis configuration

4. **`config/filesystems.php`** - Storage configuration
   - Local storage
   - Cloud storage (S3, GCS, Azure)

## Documentation

### Quick Start Guide

**`docs/QUICK_CONFIG_GUIDE.md`** - Get started in 15-20 minutes
- Essential configuration steps
- Database setup scripts
- Quick configuration templates
- Environment-specific configs
- Common tasks
- Troubleshooting quick fixes

### Comprehensive Guide

**`docs/CONFIGURATION.md`** - Complete configuration reference
- Detailed explanation of all settings
- Configuration best practices
- Environment-specific examples
- Integration setup guides
- Security configuration
- Performance optimization
- Troubleshooting guide

### Configuration Checklist

**`docs/CONFIG_CHECKLIST.md`** - Ensure everything is configured
- Pre-installation requirements
- Configuration validation
- Security checklist
- Performance optimization checklist
- Testing and validation steps
- Validation script

## Quick Reference

### First-Time Setup

1. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

2. **Generate application key**
   ```bash
   php artisan key:generate
   ```

3. **Configure essential settings in `.env`:**
   - `APP_URL` - Your domain
   - Database credentials
   - Redis connection
   - DevFlow paths

4. **Create storage directories**
   ```bash
   sudo mkdir -p /opt/devflow/{projects,backups,logs,ssl}
   sudo chown -R www-data:www-data /opt/devflow
   ```

5. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Set up queue workers**
   ```bash
   # See docs/QUICK_CONFIG_GUIDE.md for Supervisor setup
   ```

### Configuration by Use Case

#### Local Development
```env
APP_ENV=local
APP_DEBUG=true
QUEUE_CONNECTION=sync
CACHE_STORE=file
```
See: `.env.example` - Local Development section

#### Staging Environment
```env
APP_ENV=staging
APP_DEBUG=false
QUEUE_CONNECTION=redis
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
```
See: `docs/CONFIGURATION.md` - Staging Environment

#### Production Deployment
```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
```
See: `docs/CONFIGURATION.md` - Production Environment

#### Docker Deployment
Use `.env.docker` as reference
- Service names instead of localhost
- Container-specific paths
- Docker networking configuration

## Configuration Workflow

### 1. Choose Your Deployment Type

- **Traditional VPS:** Use `.env.example`
- **Docker Deployment:** Use `.env.docker`
- **Local Development:** See Quick Config Guide

### 2. Initial Configuration

Start with `docs/QUICK_CONFIG_GUIDE.md`:
- Covers essential settings
- 15-20 minute setup time
- Gets you running quickly

### 3. Detailed Configuration

Reference `docs/CONFIGURATION.md`:
- Understand all configuration options
- Set up integrations
- Optimize for your use case

### 4. Validation

Use `docs/CONFIG_CHECKLIST.md`:
- Verify all settings
- Check security configuration
- Test functionality

### 5. Optimization

Return to `docs/CONFIGURATION.md`:
- Performance tuning
- Advanced features
- Environment-specific optimization

## Common Configuration Tasks

### Enable Notifications

**Email:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
DEVFLOW_NOTIFICATIONS_EMAIL=true
```

**Slack:**
```env
SLACK_WEBHOOK_URL=https://hooks.slack.com/...
DEVFLOW_NOTIFICATIONS_SLACK=true
```

See: `docs/CONFIGURATION.md` - Notification Configuration

### Configure Git Integration

**GitHub:**
```env
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_secret
GITHUB_WEBHOOK_SECRET=your_webhook_secret
```

See: `config/services.php` - GitHub section

### Set Up Health Monitoring

```env
DEVFLOW_HEALTH_CHECK_INTERVAL=300
DEVFLOW_DISK_WARNING_THRESHOLD=75
DEVFLOW_CPU_WARNING_THRESHOLD=75
```

See: `docs/CONFIGURATION.md` - Health Monitoring

### Configure Deployment Settings

```env
DEVFLOW_MAX_CONCURRENT_DEPLOYMENTS=3
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
```

See: `docs/CONFIGURATION.md` - Deployment Settings

## Configuration Files Reference

| File | Purpose | When to Use |
|------|---------|-------------|
| `.env.example` | Template with all options | Starting point for new installation |
| `.env.docker` | Docker-optimized config | Docker deployments |
| `config/devflow.php` | Core DevFlow settings | Understanding DevFlow options |
| `config/services.php` | Third-party integrations | Setting up external services |
| `docs/QUICK_CONFIG_GUIDE.md` | Quick start guide | First-time setup (15-20 min) |
| `docs/CONFIGURATION.md` | Complete reference | Detailed configuration |
| `docs/CONFIG_CHECKLIST.md` | Validation checklist | Verify configuration |

## Environment Variables Reference

### Essential Variables (Required)

```env
APP_NAME=               # Application name
APP_KEY=                # Encryption key (auto-generated)
APP_URL=                # Full URL to your installation

DB_CONNECTION=          # Database type (pgsql/mysql)
DB_HOST=                # Database host
DB_DATABASE=            # Database name
DB_USERNAME=            # Database user
DB_PASSWORD=            # Database password

REDIS_HOST=             # Redis server host
CACHE_STORE=            # Cache driver (redis)
QUEUE_CONNECTION=       # Queue driver (redis)

DEVFLOW_PROJECTS_PATH=  # Projects storage path
```

### Important Variables (Recommended)

```env
DEVFLOW_BACKUP_PATH=                    # Backups location
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=      # Auto-backup (true/false)
DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=    # Require approval (true/false)
DEVFLOW_NOTIFICATIONS_EMAIL=            # Email notifications (true/false)
```

### Optional Variables (As Needed)

```env
GITHUB_CLIENT_ID=              # GitHub integration
SLACK_WEBHOOK_URL=             # Slack notifications
NGINX_PROXY_MANAGER_URL=       # SSL management
SENTRY_LARAVEL_DSN=            # Error tracking
```

## Best Practices

### Security

1. **Never commit `.env` to version control**
   - Add to `.gitignore`
   - Use `.env.example` for documentation

2. **Use strong passwords**
   - Database passwords
   - Admin passwords
   - Webhook secrets

3. **Production settings**
   - `APP_DEBUG=false`
   - `SESSION_SECURE_COOKIE=true`
   - Enable 2FA if available

### Performance

1. **Use Redis for production**
   - Cache: `CACHE_STORE=redis`
   - Sessions: `SESSION_DRIVER=redis`
   - Queues: `QUEUE_CONNECTION=redis`

2. **Enable Docker BuildKit**
   - `DEVFLOW_DOCKER_BUILDKIT=true`

3. **Configure appropriate timeouts**
   - Adjust based on your infrastructure
   - See: `docs/CONFIGURATION.md` - Timeout Settings

### Reliability

1. **Enable automatic backups**
   - `DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true`

2. **Configure health monitoring**
   - Set appropriate thresholds
   - Enable notifications

3. **Set up queue workers**
   - Use Supervisor for auto-restart
   - Monitor worker logs

## Getting Help

### Configuration Issues

1. Check relevant documentation:
   - Quick start: `docs/QUICK_CONFIG_GUIDE.md`
   - Detailed guide: `docs/CONFIGURATION.md`
   - Checklist: `docs/CONFIG_CHECKLIST.md`

2. Validate configuration:
   ```bash
   php artisan about
   php artisan config:cache
   ```

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Run validation script:
   ```bash
   # See docs/CONFIG_CHECKLIST.md
   ```

### Common Issues

- **Database connection:** Check `DB_*` variables
- **Redis connection:** Verify Redis is running
- **Permission denied:** Check directory ownership
- **Queue not processing:** Verify Supervisor setup

See: `docs/TROUBLESHOOTING_GUIDE.md` for more help

## Additional Resources

- **Main Documentation:** `/docs/README.md`
- **VPS Deployment:** `/docs/VPS_DEPLOYMENT_GUIDE.md`
- **Troubleshooting:** `/docs/TROUBLESHOOTING_GUIDE.md`
- **API Documentation:** `/docs/API_DOCUMENTATION.md`

---

**Need help?** Start with `docs/QUICK_CONFIG_GUIDE.md` for a 15-minute setup, then refer to `docs/CONFIGURATION.md` for detailed options.

**Last Updated:** December 2024
**Version:** 5.x
**Maintained by:** DevFlow Pro Team
