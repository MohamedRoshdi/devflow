# DevFlow Pro - Quick Configuration Guide

This is a quick reference guide to get DevFlow Pro up and running in minutes.

## Quick Start Checklist

- [ ] Copy `.env.example` to `.env`
- [ ] Generate application key
- [ ] Configure database
- [ ] Set up Redis
- [ ] Configure storage paths
- [ ] Run migrations
- [ ] Create admin user
- [ ] Configure queue workers

## 1. Initial Setup (5 minutes)

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate Reverb keys (for WebSockets)
php artisan reverb:install
```

## 2. Essential Configuration

### Minimal `.env` Configuration

```env
# Application
APP_NAME="DevFlow Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://devflow.example.com

# Database (PostgreSQL recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=CHANGE_THIS_PASSWORD

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# DevFlow Paths
DEVFLOW_PROJECTS_PATH=/opt/devflow/projects
DEVFLOW_BACKUP_PATH=/opt/devflow/backups
DEVFLOW_LOGS_PATH=/opt/devflow/logs
DEVFLOW_SSL_PATH=/opt/devflow/ssl
```

## 3. Database Setup

### PostgreSQL (Recommended)

```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql << EOF
CREATE DATABASE devflow_pro;
CREATE USER devflow WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE devflow_pro TO devflow;
\q
EOF
```

### MySQL (Alternative)

```bash
# Install MySQL
sudo apt install mysql-server

# Create database and user
sudo mysql << EOF
CREATE DATABASE devflow_pro;
CREATE USER 'devflow'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON devflow_pro.* TO 'devflow'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
```

## 4. Redis Setup

```bash
# Install Redis
sudo apt install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test connection
redis-cli ping
# Should return: PONG
```

## 5. Create Storage Directories

```bash
# Create directories
sudo mkdir -p /opt/devflow/{projects,backups,logs,ssl}

# Set permissions
sudo chown -R www-data:www-data /opt/devflow
sudo chmod -R 755 /opt/devflow
```

## 6. Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed
```

## 7. Create Admin User

```bash
# Create admin user
php artisan make:filament-user

# Follow the prompts to create an admin account
```

## 8. Configure Queue Workers

```bash
# Install Supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/devflow-worker.conf
```

Add this content:

```ini
[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/devflow/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/devflow/logs/worker.log
stdout_logfile_maxbytes=100MB
```

```bash
# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start devflow-worker:*
```

## 9. Configure Cron Jobs

```bash
# Edit crontab
sudo crontab -e

# Add this line
* * * * * cd /var/www/devflow && php artisan schedule:run >> /dev/null 2>&1
```

## 10. Test Configuration

```bash
# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Check application status
php artisan about

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Test Redis connection
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
>>> exit
```

## Environment-Specific Quick Configs

### Local Development

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file

DEVFLOW_PROJECTS_PATH=/home/developer/devflow/projects
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=false
```

### Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://devflow.example.com

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

DEVFLOW_REQUIRE_DEPLOYMENT_APPROVAL=true
DEVFLOW_AUTO_BACKUP_BEFORE_DEPLOY=true
```

### Docker Deployment

```env
# Use service names from docker-compose.yml
DB_HOST=postgres
REDIS_HOST=redis

# Docker paths
DEVFLOW_PROJECTS_PATH=/var/www/projects
DEVFLOW_BACKUP_PATH=/var/www/backups
```

## Common Configuration Tasks

### Enable Email Notifications

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@devflow.pro"

DEVFLOW_NOTIFICATIONS_EMAIL=true
```

### Enable Slack Notifications

```env
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
SLACK_CHANNEL=#deployments

DEVFLOW_NOTIFICATIONS_SLACK=true
DEVFLOW_DEPLOYMENT_FAILURE_CHANNELS=email,slack
```

### Configure GitHub Integration

```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_WEBHOOK_SECRET=your_webhook_secret
GITHUB_TOKEN=ghp_your_personal_access_token
```

### Enable SSL Management

```env
NGINX_PROXY_MANAGER_URL=http://localhost:81
NGINX_PROXY_MANAGER_EMAIL=admin@example.com
NGINX_PROXY_MANAGER_PASSWORD=your_password
```

## Troubleshooting Quick Fixes

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/devflow
sudo chmod -R 755 /var/www/devflow
sudo chmod -R 777 storage bootstrap/cache
```

### Database Connection Failed

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Redis Connection Failed

```bash
# Check Redis status
sudo systemctl status redis-server

# Test connection
redis-cli ping
```

### Queue Not Processing

```bash
# Check worker status
sudo supervisorctl status devflow-worker:*

# Restart workers
sudo supervisorctl restart devflow-worker:*
```

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Next Steps

After basic configuration:

1. **Configure Your First Server**
   - Go to Servers section
   - Add your VPS/server details
   - Test SSH connection

2. **Add Your First Project**
   - Go to Projects section
   - Create new project
   - Configure repository URL
   - Set up deployment settings

3. **Configure Domains**
   - Set up domain management
   - Configure SSL certificates
   - Test domain resolution

4. **Enable Monitoring**
   - Configure health checks
   - Set up notification channels
   - Test alert system

5. **Review Security Settings**
   - Change default passwords
   - Configure 2FA (optional)
   - Set up IP whitelisting (optional)
   - Review audit logs

## Security Checklist

Before going to production:

- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated
- [ ] Secure database password
- [ ] `SESSION_SECURE_COOKIE=true` (for HTTPS)
- [ ] Redis password set (if exposed)
- [ ] Firewall configured
- [ ] SSL certificates installed
- [ ] Webhook secrets configured
- [ ] Regular backups enabled
- [ ] Monitoring enabled

## Getting Help

- **Full Documentation:** `/docs/CONFIGURATION.md`
- **Deployment Guide:** `/docs/VPS_DEPLOYMENT_GUIDE.md`
- **Troubleshooting:** `/docs/TROUBLESHOOTING_GUIDE.md`

## Useful Commands

```bash
# Check configuration
php artisan about

# Clear caches
php artisan optimize:clear

# Cache configuration
php artisan optimize

# Check queue status
php artisan queue:monitor

# Check logs
tail -f storage/logs/laravel.log

# Check worker logs
tail -f /opt/devflow/logs/worker.log

# Check system status
php artisan devflow:status
```

---

**Quick Start Time:** 15-20 minutes
**Recommended for:** New installations
**For detailed configuration:** See `/docs/CONFIGURATION.md`
