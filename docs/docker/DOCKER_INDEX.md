# DevFlow Pro - Docker Setup Index

## Quick Navigation

### Getting Started
1. **Start Here**: [DOCKER_QUICK_START.md](DOCKER_QUICK_START.md) - 5-minute setup guide
2. **Automated Setup**: Run `./docker-setup.sh` for interactive installation
3. **Validate Setup**: Run `./docker-validate.sh` to check configuration

### Documentation
- **[DOCKER_README.md](DOCKER_README.md)** - Complete reference (20KB)
  - Installation guides
  - Service details
  - Configuration reference
  - Troubleshooting
  - Best practices

- **[DOCKER_QUICK_START.md](DOCKER_QUICK_START.md)** - Quick reference (4KB)
  - Quick setup methods
  - Common commands
  - Access points
  - Quick fixes

- **[DOCKER_SETUP_SUMMARY.md](DOCKER_SETUP_SUMMARY.md)** - Overview (10KB)
  - What was created
  - Features list
  - Deployment checklist
  - Performance tuning

## File Organization

### Core Configuration
```
DEVFLOW_PRO/
├── docker-compose.yml                    # Main production config
├── docker-compose.override.yml.example   # Development template
├── Dockerfile                            # Multi-stage build
└── .dockerignore                         # Build exclusions
```

### Docker Configuration
```
docker/
├── nginx/
│   ├── nginx.conf                   # Main Nginx config
│   ├── conf.d/
│   │   └── default.conf             # Virtual host
│   └── snippets/
│       ├── laravel.conf             # Laravel rules
│       └── ssl-params.conf          # SSL security
├── php/
│   ├── php.ini                      # Production PHP
│   ├── php-dev.ini                  # Development PHP
│   ├── opcache.ini                  # Production OPcache
│   ├── opcache-dev.ini              # Development OPcache
│   ├── php-fpm.conf                 # FPM pool config
│   └── php-fpm-healthcheck          # Health check
├── postgres/
│   ├── postgresql.conf              # PostgreSQL tuning
│   └── init/                        # Init SQL scripts
├── entrypoint.sh                    # App initialization
└── scheduler-entrypoint.sh          # Cron scheduler
```

### Helper Scripts
```
├── docker-setup.sh                  # Interactive setup wizard
├── docker-validate.sh               # Validation script
└── Makefile.docker                  # Convenience commands
```

## Services Overview

### Production Services (Always Running)
| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| PostgreSQL | `devflow_postgres` | 5432 | Database |
| Redis | `devflow_redis` | 6379 | Cache/Queue |
| PHP-FPM | `devflow_app` | 9000 | Application |
| Nginx | `devflow_nginx` | 80, 443 | Web Server |
| Queue Worker | `devflow_queue` | - | Background Jobs |
| Scheduler | `devflow_scheduler` | - | Cron Jobs |

### Development Services (Optional)
| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| Mailhog | `devflow_mailhog` | 8025 | Email Testing |
| pgAdmin | `devflow_pgadmin` | 5050 | DB Management |
| Redis Commander | `devflow_redis_commander` | 8081 | Redis GUI |

## Common Tasks

### Initial Setup
```bash
# Method 1: Automated (recommended)
./docker-setup.sh

# Method 2: Using Makefile
make -f Makefile.docker install

# Method 3: Manual
cp .env.example .env
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### Daily Development
```bash
# Start services
make -f Makefile.docker dev

# View logs
make -f Makefile.docker logs-app

# Shell access
make -f Makefile.docker shell

# Run migrations
make -f Makefile.docker migrate

# Clear caches
make -f Makefile.docker cache-clear
```

### Production Deployment
```bash
# Deploy
make -f Makefile.docker production

# Update
make -f Makefile.docker deploy

# Backup database
make -f Makefile.docker db-backup

# View health
make -f Makefile.docker health
```

## Key Features

### Performance
- ✓ PHP 8.4 with OPcache and JIT
- ✓ PostgreSQL 16 with production tuning
- ✓ Redis 7 with persistence
- ✓ Nginx with Gzip and caching
- ✓ Multi-stage Docker builds

### Security
- ✓ Non-root containers
- ✓ Security headers configured
- ✓ SSL/TLS ready
- ✓ Hidden sensitive files
- ✓ Health checks enabled

### Developer Experience
- ✓ Hot-reload in development
- ✓ Xdebug support
- ✓ Email testing (Mailhog)
- ✓ Database GUI (pgAdmin)
- ✓ 50+ Makefile commands
- ✓ Interactive setup script

### DevOps
- ✓ Health checks for all services
- ✓ Log rotation
- ✓ Automated migrations
- ✓ Queue worker auto-restart
- ✓ Docker socket access
- ✓ Comprehensive monitoring

## Environment Configuration

### Required Variables
```bash
APP_KEY=                 # Generate with: php artisan key:generate
DB_PASSWORD=             # Set strong password
```

### Important Variables
```bash
APP_ENV=production       # Environment: local, production
APP_DEBUG=false          # Debug mode: true/false
APP_URL=                 # Your domain URL
DB_DATABASE=devflow_pro  # Database name
DB_USERNAME=devflow      # Database user
```

### Optional Variables
```bash
REDIS_PASSWORD=          # Redis password (optional)
HTTP_PORT=80             # HTTP port
HTTPS_PORT=443           # HTTPS port
PROJECTS_PATH=/opt/devflow/projects
BACKUPS_PATH=/opt/devflow/backups
```

## Access Points

### Production
- Application: `http://your-domain.com`
- Health Check: `http://your-domain.com/health`

### Development
- Application: `http://localhost:8080`
- Mailhog UI: `http://localhost:8025`
- pgAdmin: `http://localhost:5050`
  - Email: `admin@devflow.local`
  - Password: `admin`
- Redis Commander: `http://localhost:8081`

## Troubleshooting Quick Reference

| Issue | Solution |
|-------|----------|
| Port 80 in use | Change `HTTP_PORT=8080` in `.env` |
| Permission errors | Run `make -f Makefile.docker permissions` |
| APP_KEY not set | Run `docker-compose exec app php artisan key:generate` |
| DB connection failed | Check `docker-compose logs postgres` |
| Build fails | Run `docker builder prune && docker-compose build --no-cache` |

## Validation Checklist

Run `./docker-validate.sh` to check:
- [ ] Docker and Docker Compose installed
- [ ] All required files present
- [ ] Configuration syntax valid
- [ ] Scripts are executable
- [ ] Environment configured
- [ ] Directories created
- [ ] Services running

## Next Steps

### First Time Setup
1. ✓ Run validation: `./docker-validate.sh`
2. ✓ Review `.env` file
3. ✓ Start services: `docker-compose up -d`
4. ✓ Generate APP_KEY
5. ✓ Run migrations
6. ✓ Test application

### For Production
1. Update `.env` with production values
2. Set strong passwords
3. Configure SSL certificates
4. Enable HTTPS in nginx config
5. Set up backups
6. Configure monitoring
7. Test deployment

### For Development
1. Copy override file: `cp docker-compose.override.yml.example docker-compose.override.yml`
2. Enable Xdebug if needed
3. Configure IDE
4. Start development services
5. Install frontend dependencies

## Resources

### Documentation Files
- `DOCKER_README.md` - Complete reference
- `DOCKER_QUICK_START.md` - Quick start
- `DOCKER_SETUP_SUMMARY.md` - Overview
- `DOCKER_INDEX.md` - This file

### Scripts
- `docker-setup.sh` - Automated setup
- `docker-validate.sh` - Validation
- `Makefile.docker` - Commands

### External Resources
- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Docker Documentation](https://docs.docker.com)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Nginx Documentation](https://nginx.org/en/docs/)

## Support

For issues:
1. Check `DOCKER_README.md` troubleshooting section
2. Run `./docker-validate.sh` for diagnostics
3. Review logs: `docker-compose logs -f`
4. Check service status: `docker-compose ps`

## Version Information

- Laravel: 12
- PHP: 8.4
- PostgreSQL: 16
- Redis: 7
- Nginx: 1.25
- Docker Compose: 3.8

Last Updated: 2025-12-14
