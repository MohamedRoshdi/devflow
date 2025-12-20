# DevFlow Pro - Docker Setup Guide

Complete Docker configuration for DevFlow Pro - Multi-Project Deployment & Management System.

## Table of Contents

- [Quick Start](#quick-start)
- [Prerequisites](#prerequisites)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Services](#services)
- [Development Setup](#development-setup)
- [Production Deployment](#production-deployment)
- [Common Commands](#common-commands)
- [Troubleshooting](#troubleshooting)

## Quick Start

### For Development

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Update .env with your configuration
nano .env

# 3. Copy docker-compose override for development
cp docker-compose.override.yml.example docker-compose.override.yml

# 4. Start services
docker-compose up -d

# 5. Install dependencies
docker-compose exec app composer install

# 6. Generate application key
docker-compose exec app php artisan key:generate

# 7. Run migrations
docker-compose exec app php artisan migrate

# 8. Create storage link
docker-compose exec app php artisan storage:link

# 9. Access the application
# http://localhost:8080 (or http://localhost if no override)
```

### For Production

```bash
# 1. Ensure .env is properly configured
cp .env.example .env
nano .env  # Update with production values

# 2. Build production images
docker-compose build --no-cache

# 3. Start services
docker-compose up -d

# 4. Run migrations (first time only)
docker-compose exec app php artisan migrate --force

# 5. Check service status
docker-compose ps
```

## Prerequisites

- Docker Engine 20.10 or higher
- Docker Compose 2.0 or higher
- Minimum 2GB RAM available for Docker
- At least 10GB disk space

## Project Structure

```
DEVFLOW_PRO/
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf                # Main Nginx configuration
│   │   ├── conf.d/
│   │   │   └── default.conf          # Virtual host configuration
│   │   └── snippets/
│   │       ├── laravel.conf          # Laravel-specific rules
│   │       └── ssl-params.conf       # SSL security parameters
│   ├── php/
│   │   ├── php.ini                   # Production PHP configuration
│   │   ├── php-dev.ini               # Development PHP configuration
│   │   ├── opcache.ini               # Production OPcache settings
│   │   ├── opcache-dev.ini           # Development OPcache settings
│   │   ├── php-fpm.conf              # PHP-FPM pool configuration
│   │   └── php-fpm-healthcheck       # Health check script
│   ├── postgres/
│   │   ├── postgresql.conf           # PostgreSQL configuration
│   │   └── init/                     # Initialization SQL scripts
│   ├── entrypoint.sh                 # Application startup script
│   └── scheduler-entrypoint.sh       # Scheduler startup script
├── docker-compose.yml                # Main compose file (production)
├── docker-compose.override.yml.example  # Development overrides
├── Dockerfile                        # Multi-stage Dockerfile
└── .dockerignore                     # Build context exclusions
```

## Configuration

### Environment Variables

Key environment variables in `.env`:

```bash
# Application
APP_NAME="DevFlow Pro"
APP_ENV=production
APP_KEY=base64:...  # Generate with: php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=devflow_pro
DB_USERNAME=devflow
DB_PASSWORD=secure_password_here

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=  # Optional
REDIS_PREFIX=devflow_

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# DevFlow Paths
PROJECTS_PATH=/opt/devflow/projects
BACKUPS_PATH=/opt/devflow/backups
LOGS_PATH=/opt/devflow/logs
SSL_PATH=/opt/devflow/ssl
```

### Docker-Specific Variables

```bash
# Docker build arguments
PHP_VERSION=8.4
INSTALL_XDEBUG=false  # Set to true for development
USER_ID=1000          # Your host user ID
GROUP_ID=1000         # Your host group ID

# Port mappings
HTTP_PORT=80
HTTPS_PORT=443
DB_PORT=5432
REDIS_PORT=6379

# Data storage
DATA_PATH=./storage/data  # For persistent volumes
```

## Services

### PHP Application (`app`)

- **Image**: Custom PHP 8.4-FPM Alpine
- **Port**: 9000 (internal)
- **Features**:
  - All required PHP extensions
  - Composer 2.7
  - Docker CLI for container management
  - Production optimizations (OPcache, JIT)

### Nginx Web Server (`nginx`)

- **Image**: nginx:1.25-alpine
- **Ports**: 80, 443
- **Features**:
  - Optimized Laravel configuration
  - Gzip compression
  - Static file caching
  - Security headers

### PostgreSQL Database (`postgres`)

- **Image**: postgres:16-alpine
- **Port**: 5432
- **Features**:
  - Production-tuned configuration
  - Automatic backups support
  - Health checks

### Redis Cache (`redis`)

- **Image**: redis:7-alpine
- **Port**: 6379
- **Features**:
  - Persistence enabled (AOF)
  - Memory management (512MB limit)
  - LRU eviction policy

### Queue Worker (`queue`)

- **Purpose**: Process background jobs
- **Command**: `php artisan queue:work redis`
- **Auto-restart**: Yes

### Scheduler (`scheduler`)

- **Purpose**: Run scheduled tasks
- **Frequency**: Every minute
- **Command**: `php artisan schedule:run`

## Development Setup

### Using Docker Compose Override

The `docker-compose.override.yml` is automatically loaded and merges with the main configuration:

```bash
# Copy the example
cp docker-compose.override.yml.example docker-compose.override.yml

# Edit as needed
nano docker-compose.override.yml

# Start with overrides
docker-compose up -d
```

### Development Services

Additional services available in development:

1. **Mailhog** (Email Testing)
   - Web UI: http://localhost:8025
   - SMTP: localhost:1025

2. **pgAdmin** (Database Management)
   - URL: http://localhost:5050
   - Email: admin@devflow.local
   - Password: admin

3. **Redis Commander** (Redis GUI)
   - URL: http://localhost:8081

### Xdebug Configuration

Enable Xdebug in development:

```bash
# In docker-compose.override.yml
services:
  app:
    build:
      args:
        INSTALL_XDEBUG: "true"
    environment:
      - XDEBUG_MODE=develop,debug,coverage
```

Configure your IDE to connect to port 9003.

## Production Deployment

### Initial Deployment

```bash
# 1. Clone repository
git clone https://github.com/your-repo/devflow-pro.git
cd devflow-pro

# 2. Configure environment
cp .env.example .env
nano .env  # Set all production values

# 3. Create required directories
mkdir -p /opt/devflow/{projects,backups,logs,ssl}

# 4. Build and start
docker-compose build
docker-compose up -d

# 5. Initialize application
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
docker-compose exec app php artisan storage:link

# 6. Verify deployment
docker-compose ps
docker-compose logs -f
```

### SSL/TLS Configuration

1. Obtain SSL certificates (Let's Encrypt, etc.)
2. Place certificates in `/opt/devflow/ssl/`
3. Update `docker/nginx/conf.d/default.conf` to enable HTTPS
4. Restart nginx:

```bash
docker-compose restart nginx
```

### Production Updates

```bash
# 1. Pull latest code
git pull origin main

# 2. Rebuild images
docker-compose build --no-cache

# 3. Stop services
docker-compose down

# 4. Start with new images
docker-compose up -d

# 5. Run migrations
docker-compose exec app php artisan migrate --force

# 6. Clear caches
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan optimize
```

## Common Commands

### Service Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart app

# View logs
docker-compose logs -f app
docker-compose logs -f nginx

# Check status
docker-compose ps

# Rebuild images
docker-compose build
docker-compose build --no-cache
```

### Application Commands

```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache

# Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# Database backup
docker-compose exec postgres pg_dump -U devflow devflow_pro > backup.sql

# Redis CLI
docker-compose exec redis redis-cli

# Shell access
docker-compose exec app bash
docker-compose exec app sh
```

### Monitoring

```bash
# View resource usage
docker stats

# Check container health
docker-compose ps

# Inspect container
docker inspect devflow_app

# View PHP-FPM status
docker-compose exec app cat /proc/$(pgrep php-fpm | head -1)/status
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

#### 2. Database Connection Failed

```bash
# Check if PostgreSQL is ready
docker-compose exec postgres pg_isready -U devflow

# View database logs
docker-compose logs postgres

# Recreate database container
docker-compose down postgres
docker-compose up -d postgres
```

#### 3. Port Conflicts

```bash
# Change ports in .env
HTTP_PORT=8080
HTTPS_PORT=8443
DB_PORT=54320

# Or in docker-compose.override.yml
```

#### 4. Build Failures

```bash
# Clear Docker build cache
docker builder prune

# Remove all unused Docker resources
docker system prune -a

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

#### 5. Container Keeps Restarting

```bash
# View container logs
docker-compose logs -f app

# Check entrypoint script
docker-compose exec app cat /usr/local/bin/entrypoint.sh

# Run container without entrypoint for debugging
docker-compose run --entrypoint sh app
```

### Performance Optimization

```bash
# Enable OPcache preloading (PHP 8+)
# Add to docker/php/php.ini:
opcache.preload=/var/www/html/preload.php
opcache.preload_user=www-data

# Adjust PHP-FPM pool size
# Edit docker/php/php-fpm.conf:
pm.max_children = 50
pm.start_servers = 10

# PostgreSQL tuning
# Edit docker/postgres/postgresql.conf based on your RAM
```

### Health Checks

```bash
# Application health
curl http://localhost/health

# PHP-FPM health
docker-compose exec app php-fpm-healthcheck

# Database health
docker-compose exec postgres pg_isready

# Redis health
docker-compose exec redis redis-cli ping
```

## Security Best Practices

1. **Never commit `.env` file** - Contains sensitive credentials
2. **Use strong passwords** - For database, Redis, admin panels
3. **Enable HTTPS** - Always in production
4. **Limit exposed ports** - Only expose what's necessary
5. **Regular updates** - Keep Docker images updated
6. **Scan for vulnerabilities** - Use `docker scan`
7. **Use secrets** - For sensitive data in Docker Swarm/Kubernetes

## Backup & Recovery

### Database Backup

```bash
# Create backup
docker-compose exec postgres pg_dump -U devflow devflow_pro > backup_$(date +%Y%m%d).sql

# Restore backup
docker-compose exec -T postgres psql -U devflow devflow_pro < backup_20240101.sql
```

### Full Volume Backup

```bash
# Backup all volumes
docker run --rm -v devflow_postgres_data:/data -v $(pwd):/backup alpine tar czf /backup/postgres_backup.tar.gz /data
```

## Support

For issues and questions:
- GitHub Issues: https://github.com/your-repo/devflow-pro/issues
- Documentation: https://docs.devflow.pro
- Email: support@devflow.pro
